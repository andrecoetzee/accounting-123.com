<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings
require ("settings.php");
require ("core-settings.php");
require("groupware/gw-common.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		case "":
			$OUTPUT = confirm ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("template.php");




# enter new data
function enter ($errors="")
{

	extract ($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='ASSET_COST_ACCOUNT'";
	$cost_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$default_cost_acc = pg_fetch_result($cost_acc_rslt, 0);

	$sql = "SELECT value FROM cubit.settings WHERE constant='ASSET_ACCDEP_ACCOUNT'";
	$accdep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$default_accdep_acc = pg_fetch_result($accdep_acc_rslt, 0);

	$sql = "SELECT value FROM cubit.settings WHERE constant='ASSET_DEP_ACCOUNT'";
	$dep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$default_dep_acc = pg_fetch_result($dep_acc_rslt, 0);

	$fields = array();
	$fields["grpid"] = 0;
	$fields["seryn"] = "yes";
	$fields["qty"] = "1";
	$fields["serial"] = "";
	$fields["serial2"] = "";
	$fields["locat"] = "";
	$fields["des"] = "";
	$fields["details"] = "";
	$fields["units"] = "0";
	$fields["method"] = "add";
	$fields["date_day"] = date("d");
	$fields["date_month"] = date("m");
	$fields["date_year"] = date("Y");
	$fields["amount"] = "0.00";
	$fields["dep_perc"] = 0;
	$fields["dep_month"] = "no";
	$fields["svdate_day"] = date("d");
	$fields["svdate_month"] = date("m");
	$fields["svdate_year"] = date("Y");
	$fields["type_id"] = 0;
	$fields["sv_desc"] = "";
	$fields["cost_acc"] = $default_cost_acc;
	$fields["accdep_acc"] = $default_accdep_acc;
	$fields["dep_acc"] = $default_dep_acc;

	extract ($fields, EXTR_SKIP);

	if ($dep_month == "yes") {
		$dm_yes = "checked";
		$dm_no = "";
	} else {
		$dm_yes = "";
		$dm_no = "checked";
	}

	if ($method == "purch") {
		$meth_purch = "checked";
		$meth_add = "";
	} else {
		$meth_purch = "";
		$meth_add = "checked";
	}

	db_connect();

	$grps = "<select name='grpid' style='width: 135'>";
	$sql = "SELECT * FROM assetgrp WHERE div = '".USER_DIV."' ORDER BY grpname ASC";
	$grpRslt = db_exec($sql);
	if(pg_numrows($grpRslt) < 1){
		return "
			<li>There are no Asset Groups in Cubit.</li><br>"
			.mkQuickLinks(
				ql("assetgrp-new.php", "Add Asset Group"),
				ql("assetgrp-view.php", "View Asset Groups")
			);
	}else{
		while($grp = pg_fetch_array($grpRslt)){
			$sel = fcheck($grpid == $grp["grpid"]);
			$grps .= "<option $sel value='$grp[grpid]'>$grp[grpname]</option>";
		}
	}
	$grps .= "</select>";

	$sql = "SELECT * FROM cubit.asset_types ORDER BY name ASC";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");

	$type_sel = "<select name='type_id' style='width: 135'>";
	$type_sel.= "<option value='0'>[None]</option>";
	while ($type_data = pg_fetch_array($type_rslt)) {
		$sel = fcheck($typeid == $type_data["id"]);
		$type_sel .= "<option value='$type_data[id]' $sel>$type_data[name]</option>";
	}
	$type_sel .= "</select>";

	if ($seryn == "yes") {
		$div_qty_style = "visibility: hidden;";
		$div_serial_style = "";
	} else {
		$div_serial_style = "visibility: hidden;";
		$div_qty_style = "";
	}

	// Cost Account
	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$cost_acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$cost_acc_sel = "<select name='cost_acc' style='width: 100%'>";
	while ($cost_acc_data = pg_fetch_array($cost_acc_rslt)) {
		$sel = ($cost_acc == $cost_acc_data["accid"]) ? "selected='t'" : "";

		$cost_acc_sel .= "
			<option value='$cost_acc_data[accid]' $sel>
				$cost_acc_data[topacc]/$cost_acc_data[accnum] $cost_acc_data[accname]
			</option>";
	}
	$cost_acc_sel .= "</select>";

	// Accumulated Deprecation
	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$accdep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$accdep_acc_sel = "<select name='accdep_acc' style='width: 100%'>";
	while ($accdep_acc_data = pg_fetch_array($accdep_acc_rslt)) {
		$sel = ($accdep_acc == $accdep_acc_data["accid"]) ? "selected='t'" : "";

		$accdep_acc_sel .= "
			<option value='$accdep_acc_data[accid]' $sel>
				$accdep_acc_data[topacc]/$accdep_acc_data[accnum] $accdep_acc_data[accname]
			</option>";
	}
	$accdep_acc_sel .= "</select>";

	// Deprecation
	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$dep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$dep_acc_sel = "<select name='dep_acc' style='width: 100%'>";
	while ($dep_acc_data = pg_fetch_array($dep_acc_rslt)) {
		$sel = ($dep_acc == $dep_acc_data["accid"]) ? "selected='t'" : "";

		$dep_acc_sel .= "
			<option value='$dep_acc_data[accid]' $sel>
				$dep_acc_data[topacc]/$dep_acc_data[accnum] $dep_acc_data[accname]
			</option>";
	}
	$dep_acc_sel .= "</select>";

	$OUT = "
		<script>
			function seryn_update(obj) {
				if (obj.value == 'yes') {
					getObject('div_serial').style.visibility = 'visible';
					getObject('div_qty').style.visibility = 'hidden';
				} else {
					getObject('div_serial').style.visibility = 'hidden';
					getObject('div_qty').style.visibility = 'visible';
				}
			}
		</script>
		<h3>New Asset</h3>
		<form action='".SELF."' method='POST'>
		<table cellpadding='0' cellspacing='0'>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<td colspan='2'>$errors</td>
						</tr>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th colspan='2'>Asset Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Select Group</td>
							<td>$grps</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select Type</td>
							<td>$type_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Location</td>
							<td><input type='text' size='20' name='locat' value='$locat'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Description</td>
							<td><input type='text' size='20' name='des' value='$des'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Details</td>
							<td><textarea name='details' cols='30' rows='4'>$details</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Billing Requirement Ratio (Units)</td>
							<td><input type='text' name='units' value='$units' /></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Percentage of Yearly Depreciation</td>
							<td><input type='text' size='2' name='dep_perc' value='$dep_perc' maxlength='2' />%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Auto Monthly Depreciation</td>
							<td>
								Yes <input type='radio' name='dep_month' value='yes' $dm_yes />
								No <input type='radio' name='dep_month' value='no' $dm_no />
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Method</td>
							<td valign='center'>
								Add Asset <input type='radio' name='method' value='add' $meth_add>
								Purchase Asset <input type='radio' name='method' value='purch' $meth_purch>
							</td>
						</tr>
						<tr>
							<th colspan='2'>Bought</th>
						</tr>
					 	<tr bgcolor='".bgcolorg()."'>
					 		<td>".REQ."Date</td>
					 		<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
					 	</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Amount</td>
							<td>".CUR."<input type='text' size='20' name='amount' value='$amount'></td>
						</tr>
						<!--
						<tr bgcolor='".bgcolorg()."'>
							<td>Cost Account</td>
							<td>$cost_acc_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Accumulated Depreciation Account</td>
							<td>$accdep_acc_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Depreciation Account</td>
							<td>$dep_acc_sel</td>
						</tr>
						-->
						<tr>
							<th colspan='2'>Servicing</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Next Service Date</td>
							<td>".mkDateSelect("svdate", $svdate_year, $svdate_month, $svdate_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Service Description</td>
							<td><input type='text' name='sv_desc' value='$sv_desc' /></td>
						</tr>
						<tr>
							<th colspan='2'>Serial Number/Quantity</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Has Serial Number?</td>
							<td>
								<input onclick='seryn_update(this);' type='radio' name='seryn' value='yes' ".fcheck($seryn!="no")."> Yes
								<input onclick='seryn_update(this);' type='radio' name='seryn' value='no' ".fcheck($seryn=="no")."> No
							</td>
						</tr>
						<tr>
							<td colspan='2' style='margin: 0px; padding: 0px;'>
								<div id='div_qty' style='height: 0px; $div_qty_style'>
									<table ".TMPL_tblDflts." width='100%'>
										<tr bgcolor='".bgcolorg()."'>
											<td>".REQ." Quantity</td>
											<td><input type='text' size='20' name='qty' value='$qty'></td>
										</tr>
									</table>
								</div>
								<div id='div_serial' style='$div_serial_style'>
									<table ".TMPL_tblDflts." width='100%'>
										<tr bgcolor='".bgcolorg()."'>
											<td>".REQ." Serial Number</td>
											<td><input type='text' size='20' name='serial' value='$serial'></td>
										</tr>
										<tr bgcolor='".bgcolorg()."'>
											<td>2nd Serial Number</td>
											<td><input type='text' size='20' name='serial2' value='$serial2'></td>
										</tr>
									</table>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td valign='bottom' colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='asset-view.php'>View Assets</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUT;

}




# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	if (!isset($qty))
		$qty = "1";

	# validate input
	require_lib("validate");
	$v = new validate();
	$v->isOk($grpid, "num", 1, 50, "Invalid Asset Group id.");
	$v->isOk($type_id, "num", 1, 50, "Invalid Type id.");
	$v->isOk($qty, "num", 0, 10, "Invalid quantity.");
	$v->isOk($serial, "string", 0, 20, "Invalid Serial[1] Number.");
	$v->isOk($serial2, "string", 0, 30, "Invalid Serial[2] Number.");
	$v->isOk($locat, "string", 1, 100, "Invalid location.");
	$v->isOk($des, "string", 1, 255, "Invalid description.");
	$v->isOk($details, "string", 0, 255, "Invalid Details.");
	$v->isOk($units, "num", 1, 10, "Invalid units");
	$v->isOk($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk($date_year, "num", 4, 4, "Invalid Date Year.");
	$v->isOk($method, "string", 1, 255, "Invalid method.");
	$v->isOk($dep_perc, "float", 1, 16, "Invalid Yearly Depreciation Percentage.");
	$v->isOk($svdate_day, "num", 1, 2, "Invalid Next Service Date (day)");
	$v->isOk($svdate_month, "num", 1, 2, "Invalid Next Service Date (month)");
	$v->isOk($svdate_year, "num", 4, 4, "Invalid Next Service Date (year)");

/*
	$v->isOk($cost_acc, "num", 1, 9, "Invalid cost account selection.");
	$v->isOk($accdep_acc, "num", 1, 9, "Invalid accumulated depreciation account selection.");
	$v->isOk($dep_acc, "num", 1, 9, "Invalid depreciation account selection.");
 */

	if ($seryn == "yes" && empty($serial)) {
		$v->addError("", "Serial field requires a value.");
	} else if ($seryn == "no" && empty($qty)) {
		$v->addError("", "Quantity field requires a value.");
	}

	if ($seryn == "yes") {
		// Check for duplicate serials
		if (!empty($serial)) {
			$sql = "SELECT serial FROM cubit.assets WHERE serial='$serial'";
			$dupser1_rslt = db_exec($sql) or errDie("Unable to retrieve asset serial.");
			if (pg_num_rows($dupser1_rslt))
				$v->addError(0, "First serial already exists in the system.");
		}

		if (!empty($serial2)) {	
			$sql = "SELECT serial2 FROM cubit.assets WHERE serial2='$serial2'";
			$dupser2_rslt = db_exec($sql) or errDie("Unable to retrieve asset serial.");
			if (pg_num_rows($dupser2_rslt))
				$v->addError(0, "Second serial already exists in the system.");
		}
	}

/*
	// Retrieve cost account
	$sql = "
	SELECT accid, topacc, accnum, accname FROM core.accounts
	WHERE accid='$cost_acc'";
	$cost_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$cost_acc_data = pg_fetch_array($cost_acc_rslt);

	// Retrieve Accumulated Depreciation
	$sql = "
	SELECT accid, topacc, accnum, accname FROM core.accounts
	WHERE accid='$accdep_acc'";
	$accdep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$accdep_acc_data = pg_fetch_array($accdep_acc_rslt);

	// Retrieve Depreciation
	$sql = "
	SELECT accid, topacc, accnum, accname FROM core.accounts
	WHERE accid='$dep_acc'";
	$dep_acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$dep_acc_data = pg_fetch_array($dep_acc_rslt);
 */
	# mix dates
	$date = "$date_day-$date_month-$date_year";

	// Service Date
	$svdate = "$svdate_day-$svdate_month-$svdate_year";

	if (!checkdate($date_month, $date_day, $date_year)){
		$v->isOk($date, "num", 1, 1, "Invalid date.");
	}

	if (!checkdate($svdate_month, $svdate_day, $svdate_year)) {
		$v->isOk($svdate, "num", 1, 1, "Invalid Next Service Date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
		exit;
	}



	# Get group
	db_connect();

	$sql = "SELECT * FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	if (!isset($accnt)) $accnt = "";
	if($method == 'purch'){
		$vmethod = "Purchase Asset";
		$accnt = "";
	}else{
		$vmethod = "Add Asset";
		$accnt = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Contra Account <input align='right' type='button' onClick=\"window.open('core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td>".mkAccSelect("accnt", $accnt)."</td>
			</tr>";
	}

	if ($seryn == "yes") {
		$serdisp = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Serial Number</td>
				<td><input type='hidden' name='serial' value='$serial'>$serial</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>2nd Serial Number</td>
				<td>
					<input type='hidden' name='serial2' value='$serial2'>
					$serial2
				</td>
			</tr>";
	} else {
		$serdisp = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Quantity</td>
				<td><input type='hidden' name='qty' value='$qty'>$qty</td>
			</tr>";
	}

	$sql = "SELECT name FROM cubit.asset_types WHERE id='$type_id'";
	$at_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");
	$type_name = pg_fetch_result($at_rslt, 0);

	$confirm = "
		<h3>Confirm Asset</h3>
		<form action='".SELF."' method='POST' enctype='multipart/form-data' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='type_id' value='$type_id' />".
/*
	<!--
	<input type='hidden' name='cost_acc' value='$cost_acc' />
	<input type='hidden' name='accdep_acc' value='$accdep_acc' />
	<input type='hidden' name='dep_acc' value='$dep_acc' />
	-->
*/
	"
	<table cellpadding='0' cellspacing='0'>
		<tr valign='top'>
			<td>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Asset Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Group</td>
						<td><input type='hidden' name='grpid' value='$grpid'>$grp[grpname]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Type</td>
						<td>$type_name</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Location</td>
						<td><input type='hidden' name='locat' value='$locat'>$locat</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Description</td>
						<td><input type='hidden' name='des' value='$des'>$des</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Details</td>
						<td><input type='hidden' name='details' value='$details'>".nl2br($details)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Billing Requirement Ratio</td>
						<td><input type='hidden' name='units' value='$units' />$units</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Percentage of Yearly Depreciation</td>
						<td>
							<input type='hidden' name='dep_perc' value='$dep_perc' />
							$dep_perc%
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Auto Monthly Depreciation</td>
						<td>
							<input type='hidden' name='dep_month' value='$dep_month' />
							".ucfirst($dep_month)."
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Method</td>
						<td><input type='hidden' name='method' value='$method'>$vmethod</td>
					</tr>
					$accnt
					<tr>
						<th colspan='2'>Bought</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td>
							<input type='hidden' name='date_year' value='$date_year' />
							<input type='hidden' name='date_month' value='$date_month' />
							<input type='hidden' name='date_day' value='$date_day' />
							$date
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Amount</td>
						<td>
							<input type='hidden' name='amount' value='$amount'>".CUR." $amount
						</td>
					</tr>".
/*
			<!--
			<tr bgcolor='".bgcolorg()."'>
				<td>Cost Account</td>
				<td>$cost_acc_data[topacc]/$cost_acc_data[accnum] $cost_acc_data[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Accumulated Depreciation</td>
				<td>$accdep_acc_data[topacc]/$accdep_acc_data[accnum] $accdep_acc_data[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Depreciation</td>
				<td>$dep_acc_data[topacc]/$dep_acc_data[accnum] $dep_acc_data[accname]</td>
			</tr>
			-->
*/
			"
						<tr>
							<th colspan='2'>Servicing</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Next Service Date</td>
							<td>
								<input type='hidden' name='svdate_year' value='$svdate_year' />
								<input type='hidden' name='svdate_month' value='$svdate_month' />
								<input type='hidden' name='svdate_day' value='$svdate_day' />
								$svdate
							</td>
						<tr bgcolor='".bgcolorg()."'>
							<td>Service Description</td>
							<td>
								<input type='hidden' name='sv_desc' value='$sv_desc' />
								$sv_desc
							</td>
						</tr>
						<tr>
							<th colspan='2'>Serial/Quantity</th>
						</tr>
						<input type='hidden' name='seryn' value='$seryn'>
						$serdisp
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>Add Picture</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Image Name</td>
							<td><input type='text' size='40' name='picupload_name'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Image Location</td>
							<td><input type='file' name='picupload_image'></td>
						</tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
							<td valign='bottom' align='right'><input type='submit' value='Write &raquo;'></td>
						</tr>
					</table>
				</td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='asset-view.php'>View Assets</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	global $_FILES, $_POST;

	# get vars
	extract ($_POST);

	if(isset($back)) {
		return enter();
	}

	# validate input
	require_lib("validate");
	$v = new validate();
	$v->isOk($grpid, "num", 1, 50, "Invalid Asset Group id.");
	$v->isOk($type_id, "num", 1, 50, "Invalid Asset Type Id.");
	if (isset($qty)) {
		$v->isOk($qty, "num", 0, 10, "Invalid quantity.");
	} else if (isset($serial) && isset($serial2)) {
		$v->isOk($serial, "string", 0, 20, "Invalid Serial[1] Number.");
		$v->isOk($serial2, "string", 0, 30, "Invalid Serial[2] Number.");
	} else {
		$v->addError("", "Insufficient data for adding an asset supplied.");
	}
	$v->isOk($locat, "string", 1, 100, "Invalid location.");
	$v->isOk($des, "string", 1, 255, "Invalid description.");
	$v->isOk($details, "string", 0, 255, "Invalid Details.");
	$v->isOk($units, "num", 1, 10, "Invalid units.");
	$v->isOk($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk($date_year, "num", 4, 4, "Invalid Date Year.");
	$v->isOk($method, "string", 1, 255, "Invalid method.");
	$v->isOk($dep_perc, "float", 1, 16, "Invalid Yearly Depreciation Percentage.");
	/*
	$v->isOk($svdate_day, "num", 1, 2, "Invalid Next Service Date (day)");
	$v->isOk($svdate_month, "num", 1, 2, "Invalid Next Service Date (month)");
	$v->isOk($svdate_year, "num", 4, 4, "Invalid Next Service Date (year)");
	 */
	if(isset($accnt)){
		$v->isOk ($accnt, "num", 1, 255, "Invalid Contra Account.");
	}

	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return $confirmCust;
	}



	$bdate = "$date_year-$date_month-$date_day";
	$svdate = "$svdate_year-$svdate_month-$svdate_day";
	$date = $bdate;

	db_connect ();

	if (isset($qty)) {
		$serial = "Not Serialized";
		$serial2 = "$qty";
		$nonserial = "1";
	} else {
		$nonserial = "0";
	}
	/*
	$Sl = "
	INSERT INTO assets(grpid, serial, locat, des, date, bdate, amount, div,
		dep_perc, dep_month, serial2, puramt, nonserial, type_id, cost_acc,
		accdep_acc, dep_acc)
	VALUES ('$grpid', '$serial','$locat','$des','$date','$bdate','$amount',
		'".USER_DIV."', '$dep_perc', '$dep_month', '$serial2', '$amount',
		'$nonserial', '$type_id', '$cost_acc', '$accdep_acc', '$dep_acc')";
	 */
	$Sl = "
		INSERT INTO assets (
			grpid, serial, locat, des, date, bdate, 
			amount, div, dep_perc, dep_month, serial2, puramt, 
			nonserial, type_id, details, units
		) VALUES (
			'$grpid', '$serial', '$locat', '$des', '$date', '$bdate', 
			'$amount', '".USER_DIV."', '$dep_perc', '$dep_month', '$serial2', '$amount',
			'$nonserial', '$type_id', '$details', '$units'
		)";

	$Rs = db_exec($Sl) or errDie("Unable to add supplier to the system.");
	if (pg_cmdtuples($Rs) < 1) {
		return "<li class='err'>Unable to add asset to database.</li>";
	}

	$assid = pglib_lastid ("assets", "id");

	// Create basis entry
	$sql = "
		INSERT INTO hire.basis_prices (
			assetid, per_hour, per_day, per_week, per_month, default_basis
		) VALUES (
			'$assid', '0.00', '0.00', '0.00', '0.00', 'per_day'
		)";

	db_exec($sql);

	// Add service date
	$sql = "
		INSERT INTO cubit.asset_svdates (
			asset_id, svdate, des
		) VALUES (
			'$assid', '$svdate', '$sv_desc'
		)";
	$as_rslt = db_exec($sql) or errDie("Unable to add asset service date.");

	// Add to today
	addTodayEntry("Assets", $assid, $svdate, "Service");

	# Get group
	$sql = "SELECT * FROM assetgrp WHERE grpid='$grpid' AND div='".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	if($method == 'purch'){
		header("Location: nonsa-purchase-new.php?assid=$assid&grpid=$grpid&v=yes");
	}else{
		$refnum = getrefnum();
		# dt(costacc) ct(accdep)
		//$date = date("d-m-Y");
		writetrans($grp['costacc'], $accnt, $date, $refnum, $amount, "New Asset $des Added.");

		db_conn('core');

		$Sl = "SELECT * FROM bankacc WHERE accnum='$accnt'";
		$Ri = db_exec($Sl) or errDie("Unable to get accnum");

		if(pg_num_rows($Ri)>0) {
			$bd=pg_fetch_array($Ri);

			db_conn('cubit');

			//$Sl="SELECT * FROM bankacct WHERE

			$sql = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, descript,
					cheqnum, amount, vat, chrgvat, banked, 
					accinv, div
				) VALUES (
					'$bd[accid]', 'withdrawal', '$date', '$des', 'New Asset $des Added.', 
					'0', '$amount', '0', '', 'no',
					'$grp[costacc]', '".USER_DIV."'
				)";
			$Rslt = db_exec ($sql) or errDie("Unable to add bank payment to database.");
		}
	}

	#check if we are uploading a new picture
	if (is_uploaded_file ($_FILES["picupload_image"]["tmp_name"])) {
		# Check file ext
		if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["picupload_image"]["type"], $extension)) {

			$type = $_FILES["picupload_image"]["type"];
			$fname = $_FILES["picupload_image"]["name"];

			// open file in "read, binary" mode
			$img = "";
			$file = fopen ($_FILES['picupload_image']['tmp_name'], "rb");
			while (!feof ($file)) {
				// fread is binary safe
				$img .= fread ($file, 1024);
			}
			fclose ($file);
			# base 64 encoding
			$img = base64_encode($img);

			db_connect();

			$sql = "
				INSERT INTO display_images (
					type, image_name, image_data, image_type, image_filename, 
					ident_id
				) VALUES (
					'asset','$picupload_name','$img','$type', '$fname', 
					'$assid'
				)";
			$run_sql = db_exec($sql);

		}
	}

//	$write = "
//		<table ".TMPL_tblDflts." width='50%'>
//			<tr>
//				<th>Asset added to the system</th>
//			</tr>
//			<tr class='datacell'>
//				<td>New Asset has been added to the system.</td>
//			</tr>
//		</table>
//		<p>
//		<table border='0' cellpadding='2' cellspacing='1'>
//			<tr>
//				<th>Quick Links</th>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td><a href='asset-new.php'>New Asset</a></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td><a href='asset-view.php'>View Assets</a></td>
//			</tr>
//			<script>document.write(getQuicklinkSpecial());</script>
//		</table>";
//	return $write;

	$_POST = array ();
	$_REQUEST = array();
	return enter ("<li class='yay'>Asset has been added.</li><br>");

}



?>
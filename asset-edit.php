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
		default:
			$OUTPUT = enter ($_POST);
	}
} else {
	$OUTPUT = enter ($_GET);
}

# display output
require ("template.php");




# enter new data
function enter ($_GET,$errors="")
{

	extract ($_REQUEST);
	extract($_GET);

//	$fields =

	$id += 0;

	db_connect();

	$Sl = "SELECT * FROM assets WHERE id='$id' LIMIT 1";
	$Ri = db_exec($Sl);

	$data = pg_fetch_array($Ri);

	extract ($data);

	#process date
	$date_arr = explode ("-",$date);
	$date_year = $date_arr[0];
	$date_month = $date_arr[1];
	$date_day = $date_arr[2];

	$qty = $serial2;
	$seryn = "yes";
	if($nonserial == "1")
		$seryn = "no";

	$fields = array();
//	$fields["grpid"] = 0;
//	$fields["seryn"] = "yes";
//	$fields["qty"] = "1";
//	$fields["serial"] = "";
//	$fields["serial2"] = "";
//	$fields["locat"] = "";
//	$fields["des"] = "";
//	$fields["method"] = "add";
//	$fields["date_day"] = date("d");
//	$fields["date_month"] = date("m");
//	$fields["date_year"] = date("Y");
//	$fields["amount"] = "0.00";
//	$fields["dep_perc"] = 0;
//	$fields["dep_month"] = "no";
	$fields["svdate_day"] = date("d");
	$fields["svdate_month"] = date("m");
	$fields["svdate_year"] = date("Y");
	$fields["sv_desc"] = "";
	$fields["details"] = "";
//	$fields["type_id"] = 0;
	$fields["units"] = "0";

	extract ($fields, EXTR_SKIP);

	if ($dep_month == "yes") {
		$dm_yes = "checked";
		$dm_no = "";
	} else {
		$dm_yes = "";
		$dm_no = "checked";
	}

	if (isset($method) && $method == "purch") {
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
			$sel = fsel($grpid == $grp["grpid"]);
			$grps .= "<option $sel value='$grp[grpid]'>$grp[grpname]</option>";
		}
	}
	$grps .= "</select>";

	$sql = "SELECT * FROM cubit.asset_types ORDER BY name ASC";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");

	$type_sel = "<select name='type_id' style='width: 135'>";
	$type_sel .= "<option value='0'>[None]</option>";
	while ($type_data = pg_fetch_array($type_rslt)) {
		$sel = fsel($type_id == $type_data["id"]);
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


	$display_piclist = "";
	$display_iframe = "";
	#check if this cust has any pics ...
	if (isset($id) AND strlen($id) > 0){
		#editing customer ... show frame if we have pics
		$get_pics = "SELECT * FROM display_images WHERE type = 'asset' AND ident_id = '$id' LIMIT 1";
		$run_pics = db_exec($get_pics) or errDie ("Unable to get customer images information.");
		if (pg_numrows($run_pics) < 1){
			#no pics for this customer
			$display_iframe = "";
		}else {

			#compile listing for customer
			$get_piclist = "SELECT * FROM display_images WHERE type = 'asset' AND ident_id = '$id'";
			$run_piclist = db_exec($get_piclist) or errDie ("Unable to get customer images information.");
			if (pg_numrows($run_piclist) < 1){
				#wth .. pic went missing somewhere ...
				#so nothing
			}else {
				$display_piclist = "
					<tr>
						<td colspan='2'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Picture Name</th>
									<th>View</th>
									<th>Remove</th>
								</tr>";
				while ($arr = pg_fetch_array ($run_piclist)){
					$display_piclist .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$arr[image_name]</td>
									<td><a target='iframe1' href='view_image.php?picid=$arr[id]'>View</a></td>
									<td><input type='checkbox' name='rempicid[$arr[id]]' value='yes'></td>
								</tr>";
					#at least 1 picture for this customer
					$display_iframe = "<tr><td colspan='2'><iframe name='iframe1' width='200' height='260' scrolling='false' marginwidth='0' marginheight='0' frameborder='0' src='view_image.php?picid=$arr[id]'></iframe></td></tr>";
				}
				$display_piclist .= "
							</table>
						</td>
					</tr>";
			}
		}
	}


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
						<input type='hidden' name='id' value='$id'>
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
							<td>
								<input type='text' size='2' name='dep_perc' value='$dep_perc' maxlength='2' />%
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Auto Monthly Depreciation</td>
							<td>
								Yes <input type='radio' name='dep_month' value='yes' $dm_yes />
								No <input type='radio' name='dep_month' value='no' $dm_no />
							</td>
						</tr>
						<tr>
							<th colspan=2>Bought</th>
						</tr>
					 	<tr bgcolor='".bgcolorg()."'>
					 		<td>".REQ."Date</td>
					 		<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
					 	</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Amount</td>
							<td>".CUR."<input type='text' size='20' name='amount' value='$amount'></td>
						</tr>
						<tr>
							<th colspan='2'>Servicing</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Add New Service</td>
							<td><input type='checkbox' name='set_service' value='yes'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Service Date</td>
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
								<div id='div_qty' style='$div_qty_style'>
									<table ".TMPL_tblDflts." width='100%'>
										<tr bgcolor='".bgcolorg()."'>
											<td>Quantity</td>
											<td><input type='text' name='qty' value='$serial2'></td>
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
				<td>
					<table ".TMPL_tblDfts.">
						$display_iframe
					</table>
					<table ".TMPL_tblDflts.">
						$display_piclist
					</table>
				</td>
			</tr>
			<tr>
				<td valign='bottom' colspan='2' align='right'>
					<input type='submit' value='Confirm &raquo;'>
				</td>
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

	# validate input
	require_lib("validate");

	$v = new validate();
	$v->isOk($id, "num", 1, 50, "Invalid Asset ID.");
	$v->isOk($grpid, "num", 1, 50, "Invalid Asset Group id.");
	$v->isOk($type_id, "num", 1, 50, "Invalid Type id.");
	$v->isOk($qty, "num", 0, 50, "Invalid quantity.");
	$v->isOk($serial, "string", 0, 20, "Invalid Serial[1] Number.");
	$v->isOk($serial2, "string", 0, 30, "Invalid Serial[2] Number.");
	$v->isOk($locat, "string", 1, 100, "Invalid location.");
	$v->isOk($des, "string", 1, 255, "Invalid description.");
	$v->isOk($details, "string", 0, 255, "Invalid Details.");
	$v->isOk($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk($date_year, "num", 4, 4, "Invalid Date Year.");
	$v->isOk($dep_perc, "float", 1, 16, "Invalid Yearly Depreciation Percentage.");
	if(isset($set_service) AND ($set_service == "yes")){
		$v->isOk($svdate_day, "num", 1, 2, "Invalid Next Service Date (day)");
		$v->isOk($svdate_month, "num", 1, 2, "Invalid Next Service Date (month)");
		$v->isOk($svdate_year, "num", 4, 4, "Invalid Next Service Date (year)");
	}

	if ($seryn == "yes" && empty($serial)) {
		$v->addError("", "Serial field requires a value.");
	} else if ($seryn == "no" && empty($qty)) {
		$v->addError("", "Quantity field requires a value.");
	}

	# mix dates
	$date = "$date_day-$date_month-$date_year";

	if (!checkdate($date_month, $date_day, $date_year)){
		$v->isOk($date, "num", 1, 1, "Invalid date.");
	}

	if(isset($set_service) AND ($set_service == "yes")){
		$svdate_year += 0;
		$svdate_month += 0;
		$svdate_day += 0;
		// Service Date
		$svdate = "<input type='hidden' name='set_service' value='yes'> $svdate_day-$svdate_month-$svdate_year";

		if (!checkdate($svdate_month, $svdate_day, $svdate_year)) {
			$v->isOk($svdate, "num", 1, 1, "Invalid Next Service Date.");
		}
	}else {
		$svdate = "";
	}

	$v->isOk($units, "num", 1, 10, "Invalid units");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($_POST,$confirm);
		exit;
	}

	# Get group
	db_connect();

	$sql = "SELECT * FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	if ($seryn == "yes") {
		$serdisp = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Serial Number</td>
				<td><input type='hidden' name='serial' value='$serial'>$serial</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>2nd Serial Number</td>
				<td><input type='hidden' name='serial2' value='$serial2'>$serial2</td>
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

	if (!isset($rempicid) OR !is_array ($rempicid)){
		$send_rempic = "<input type='hidden' name='rempicid[0]' value=''>";
	}else {
		$send_rempic = "";
		foreach ($rempicid AS $each => $own){
			$send_rempic .= "<input type='hidden' name='rempicid[$each]' value='$own'>";
		}
	}

	$confirm = "
		<h3>Confirm Asset</h3>
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='type_id' value='$type_id' />
			<input type='hidden' name='id' value='$id'>
			$send_rempic
			<input type='hidden' name='grpid' value='$grpid'>
			<input type='hidden' name='locat' value='$locat'>
			<input type='hidden' name='des' value='$des'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='dep_perc' value='$dep_perc' />
			<input type='hidden' name='dep_month' value='$dep_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='svdate_year' value='$svdate_year' />
			<input type='hidden' name='svdate_month' value='$svdate_month' />
			<input type='hidden' name='svdate_day' value='$svdate_day' />
			<input type='hidden' name='sv_desc' value='$sv_desc' />
			<input type='hidden' name='units' value='$units' />
		<table cellpadding='0' cellspacing='0'>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Asset Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Group</td>
							<td>$grp[grpname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Type</td>
							<td>$type_name</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Location</td>
							<td>$locat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td>$des</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Details</td>
							<td>".nl2br($details)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Billing Requirement Ratio (Units)</td>
							<td>$units</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Percentage of Yearly Depreciation</td>
							<td>$dep_perc%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Auto Monthly Depreciation</td>
							<td>".ucfirst($dep_month)."</td>
						</tr>
						<tr>
							<th colspan='2'>Bought</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td>$date</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td>".CUR." $amount</td>
						</tr>
						<tr>
							<th colspan='2'>Servicing</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Next Service Date</td>
							<td>$svdate</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Service Description</td>
							<td>$sv_desc</td>
						</tr>
						<tr>
							<th colspan='2'>Serial/Quantity</th>
						</tr>
						$serdisp
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>Add A New Picture</th>
						</tr>
						<tr>
							<th>Picture Name</th>
							<th>File Location</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' name='picupload_name'></td>
							<td><input type='file' name='picupload_image'></td>
							<td></td>
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
		<table border=0 cellpadding='2' cellspacing='1'>
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

	global $_FILES;

	# get vars
	extract ($_POST);

	if(isset($back)) {
		return enter($_POST);
	}

	# validate input
	require_lib("validate");

	$v = new validate();
	$v->isOk($id, "num", 1, 50, "Invalid Asset ID.");
	$v->isOk($grpid, "num", 1, 50, "Invalid Asset Group id.");
	$v->isOk($type_id, "num", 1, 50, "Invalid Asset Type Id.");
	if (isset($qty)) {
		$v->isOk($qty, "num", 0, 50, "Invalid quantity.");
	} else if (isset($serial) && isset($serial2)) {
		$v->isOk($serial, "string", 0, 20, "Invalid Serial[1] Number.");
		$v->isOk($serial2, "string", 0, 30, "Invalid Serial[2] Number.");
	} else {
		$v->addError("", "Insufficient data for adding an asset supplied.");
	}
	$v->isOk($locat, "string", 1, 100, "Invalid location.");
	$v->isOk($des, "string", 1, 255, "Invalid description.");
	$v->isOk($details, "string", 0, 255, "Invalid Details.");
	$v->isOk($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk($date_year, "num", 4, 4, "Invalid Date Year.");
	$v->isOk($dep_perc, "float", 1, 16, "Invalid Yearly Depreciation Percentage.");
	if(isset($set_service) AND ($set_service == "yes")){
		$svdate = "$svdate_year-$svdate_month-$svdate_day";
		$v->isOk($svdate_day, "num", 1, 2, "Invalid Next Service Date (day)");
		$v->isOk($svdate_month, "num", 1, 2, "Invalid Next Service Date (month)");
		$v->isOk($svdate_year, "num", 4, 4, "Invalid Next Service Date (year)");
	}
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

	$date = $bdate;

	db_connect ();

	if (isset($qty)) {
		$serial = "Not Serialized";
		$serial2 = "$qty";
		$nonserial = "1";
	} else {
		$nonserial = "0";
	}


	$Sl = "
		UPDATE assets 
		SET grpid = '$grpid', serial = '$serial', locat = '$locat', des = '$des', date = '$date', amount = '$amount', 
			div = '".USER_DIV."', dep_perc = '$dep_perc', dep_month = '$dep_month', serial2 = '$serial2', 
			puramt = '$amount', nonserial = '$nonserial', type_id = '$type_id', details = '$details', units = '$units' 
		WHERE id = '$id'";
	$Rs = db_exec($Sl) or errDie("Unable to add supplier to the system.");
	if (pg_cmdtuples($Rs) < 1) {
		return "<li class='err'>Unable to add asset to database.</li>";
	}

	if(isset($set_service) AND ($set_service == "yes")){
		// Add service date
		$sql = "INSERT INTO cubit.asset_svdates (asset_id, svdate, des) VALUES ('$id', '$svdate', '$sv_desc')";
		$as_rslt = db_exec($sql) or errDie("Unable to add asset service date.");

		addTodayEntry("Assets", $id, $svdate, "Service");
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
					type, image_name, image_data, image_type, image_filename, ident_id
				) VALUES (
					'asset','$picupload_name','$img','$type', '$fname', '$id'
				)";
			$run_sql = db_exec($sql);

		}
	}

	if (!isset($rempicid) OR !is_array ($rempicid))
		$rempicid = array ();

	foreach ($rempicid AS $each => $own){
		$rem_sql = "DELETE FROM display_images WHERE id = '$each'";
		$run_rem = db_exec($rem_sql) or errDie ("Unable to remove customer image information.");
	}


	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Asset updated.</th>
			</tr>
			<tr class='datacell'>
				<td>Asset has been updated.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='asset-new.php'>New Asset</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='asset-view.php'>View Assets</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
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
require ("settings.php");
require ("core-settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;

		case "write":
			$OUTPUT = con_data ($_POST);
			break;

		default:
			$OUTPUT = view_data ($_GET);
	}
} else {
	$OUTPUT = view_data ($_GET);
}
# check department-level access

# display output
require ("template.php");
# enter new data
function view_data ($_GET)
{

	extract ($_GET);

	$fields["use_year"] = date("Y");
	$fields["use_month"] = date("m");
	$fields["use_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id,"num", 1,100, "Invalid num.");

        # display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');
	$user =USER_NAME;
	$Sql = "SELECT * FROM assets WHERE (id='$id' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "Asset not Found";}
	$led = pg_fetch_array($Rslt);

	# Get group
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$led[grpid]' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	$led['amount'] = sprint($led['amount']);
	$netval = sprint($led['amount'] - $led['accdep']);

	if(!isset($depamt)) {
		$depamt="";
		$depmonths = "";
		$date_day = date("d");
		$date_month = date("m");
		$date_year = date("Y");
	}

	$view_data = "
	<h3>Asset Depreciation</h3>
	<form action='".SELF."' method='POST'>
	<input type='hidden' name='key' value='confirm'>
	<input type='hidden' name='id' value='$id'>
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan='2'>Asset Details</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Group</td>
		<td>$grp[grpname]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Serial Number</td>
		<td>$led[serial]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Location</td>
		<td>$led[locat]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Description</td>
		<td>$led[des]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Date Bought</td>
		<td>$led[bdate]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Cost Amount</td>
		<td>$led[amount]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Net Value</td>
		<td><input type='hidden' name='netval' value='$netval'>$netval</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Date</td>
		<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Date Brought Into Use</td>
		<td>".mkDateSelect("use", $use_year, $use_month, $use_day)."</td>
	</tr>
	".TBL_BR."
	<tr>
		<th colspan='2'>Enter one of the following</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Depreciation Amount</td>
		<td><input type='text' size='10' name='depamt' value='$depamt'></td>
	</tr>
	<tr>
		<td colspan='2' align='center' style='color: white;'><b>OR</b></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Calculate Depreciation for Period</td>
		<td nowrap='t'>
			<input type='text' size='5' name='depmonths' value='$depmonths' /> Months
		</td>
		<td class='err'>
			This is the number of months for which you wish to apply depreciation.
		</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>at Yearly Percentage</td>
		<td nowrap='t'><input type='text' size='2' name='depperc' value='$led[dep_perc]' /> %</td>
	</tr>
	<tr>
		<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
	</tr>
	</table>
	<p>
	<table border='0' cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='asset-view.php'>View Assets</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view_data;
}

function confirm ($_POST) {
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id,"num", 1,100, "Invalid num.");
	$v->isOk ($depamt, "float", 0, 14, "Invalid Depreciation Amount.");
	$v->isOk($depmonths, "num", 0, 3, "Invalid auto depreciation period.");
	$v->isOk("$depmonths$depamt", "float", 1, 14, "Enter one of Depreciation amount or period.");
	if(!empty($depamt) && $netval < $depamt){
		$v->isOk ("###", "float", 1, 1, "Error : Depreciation amount must not be more than the Net Value.");
	} else if (!empty($depmonths) && $depperc <= 0) {
		$v->addError("###", "Depriaction percentage has to be more than 0 if depreciating by period.");
	}

	$date = mkdate($date_year, $date_month, $date_day);
	$v->isOk($date, "date", 1, 1, "Invalid date.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		return $confirmCust."</li>".view_data($_POST);
	}

	db_conn('cubit');
	$user = USER_NAME;
	$Sql = "SELECT * FROM assets WHERE (id='$id' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "Asset not Found";}
	$led = pg_fetch_array($Rslt);
	
	if (empty($depamt)) {
		$ml_perc = $depperc * (($depmonths % 12) / 12);
		$years = ($depmonths - ($depmonths % 12)) / 12;
		
		$baseamt = $led["amount"] - $led["accdep"];
		$depamt = 0;
		
		/* yearly depreciations */
		for ($i = 1; $i <= $years; ++$i) {
			$depamt += ($baseamt - $depamt) * ($depperc / 100);
		}
		
		/* monthly depreciation */
		$depamt += ($baseamt - $depamt) * ($ml_perc / 100);

	}
	
	vsprint($depamt);

	# Get group
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$led[grpid]' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	$led['amount'] = sprint($led['amount']);
	$netval = sprint($led['amount'] - $led['accdep']);

	$view_data = "
				<h3>Asset Depreciation</h3>
				<h4>Confirm</h4>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='id' value='$id'>
					<input type='hidden' name='cosamt' value='$led[amount]'>
					<input type='hidden' name='depamt' value='$depamt'>
					<input type='hidden' name='depmonths' value='$depmonths' />
					<input type='hidden' name='depperc' value='$depperc' />
					<input type='hidden' name='date' value='$date'>
					<input type='hidden' name='date_day' value='$date_day'>
					<input type='hidden' name='date_month' value='$date_month'>
					<input type='hidden' name='date_year' value='$date_year'>
				<table ".TMPL_tblDflts.">
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'>Asset Details</th>
								</tr>
								<tr class='".bg_class()."'>
									<td>Group</td>
									<td>$grp[grpname]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Serial Number</td>
									<td>$led[serial]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Location</td>
									<td>$led[locat]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Description</td>
									<td>$led[des]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Date Bought</td>
									<td>$led[bdate]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Cost Amount</td>
									<td>$led[amount]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Net Value</td>
									<td><input type='hidden' name='netval' value='$netval'>$netval</td>
								</tr><tr class='".bg_class()."'>
									<td>Depreciation Amount</td>
									<td>$depamt</td>
								</tr>";
	if (!empty($depamt)) {
		$view_data .= "			<tr class='".bg_class()."'>
									<td>Depreciation Period</td>
									<td>$depmonths</td>
								</tr>"; 
	}
	
	$view_data .= "
								<tr class='".bg_class()."'>
									<td>Date</td>
									<td>$date</td>
								</tr>
								<tr>
									<td><input type='submit' name='back' value='&laquo; Correction'></td>
									<td valign='bottom' align='right'><input type='submit' value='Write &raquo;'></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr><td><br></td></tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-view.php'>View Assets</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";

	return $view_data;
}


# Confirm new data
function con_data ($_POST)
{
	# get vars
	extract ($_POST);

	if(isset($back)) {
		return view_data($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 0, 100, "Invalid number.");
	$v->isOk($depamt, "float", 0, 14, "Invalid Depreciation Amount.");
	$v->isOk($depmonths, "num", 0, 3, "Invalid auto depreciation period.");
	$v->isOk("$depmonths$depamt", "float", 1, 14, "Enter one of Depreciation amount or period.");
	if(!empty($depamt) && $netval < $depamt){
		$v->isOk ("###", "float", 1, 1, "Error : Depreciation amount must not be more than the Net Value.");
	}else if (!empty($depmonths) && $depperc <= 0) {
		$v->addError("###", "Depriaction percentage has to be more than 0 if depreciating by period.");
	}
	$v->isOk ($date, "date", 1, 14, "Invalid account open date.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');
	$user =USER_NAME;
	$Sql = "SELECT * FROM assets WHERE (id='$id' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "Asset not Found";}
	$led = pg_fetch_array($Rslt);
	
	# Get group
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$led[grpid]' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	# get last ref number
	$refnum = getrefnum($date);

	if ($led["dep_acc"]) {
		$dep_acc = $led["dep_acc"];
	} else {
		// Maintain backwards compatibiltiy
		$sql = "
		SELECT accid FROM core.accounts
		WHERE topacc='2200' AND accnum='000'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
		$dep_acc = pg_fetch_result($acc_rslt, 0);
	}

	if ($led["accdep_acc"]) {
		$accdep_acc = $led["accdep_acc"];
	} else {
		// Maintain backwards compatibiltiy
		$accdep_acc = $grp["accdacc"];
	}

	pglib_transaction("BEGIN");

	# dt(depacc) ct(accdep)
	writetrans($dep_acc, $accdep_acc, $date, $refnum, $depamt,
		"$led[des] Depreciation");

	db_connect();
	$sql = "UPDATE assets SET accdep = (accdep + '$depamt') WHERE (id='$id' AND div = '".USER_DIV."')";
	$up = db_exec($sql) or errdie("Could not update assets table.");

	$snetval = ($netval - $depamt);
	$sdate = date("Y-m-d");
	$sql = "INSERT INTO assetledger(assetid, asset, date, depamt, netval, div) 
			VALUES ('$id', '$led[des]', '$date', '$depamt', '$snetval', '".USER_DIV."')";
	$rec = db_exec($sql) or errdie("Could not write to asset ledger.");

	$cc = "<script> CostCenter('ct', 'Asset Depreciation', '$date', '$led[des] Depreciation', '$depamt', ''); </script>";

	pglib_transaction("COMMIT");

	$write = "
				$cc
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Asset Depreciation</th>
					</tr>
					<tr class='datacell'>
						<td>Asset Depreciation has been recorded</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-new.php'>New Asset</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-view.php'>View Assets</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";

	return $write;
}
?>

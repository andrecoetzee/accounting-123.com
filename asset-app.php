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
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
		$date_day=date("d");
		$date_month=date("m");
		$date_year=date("Y");
	}

	$view_data = "
				<h3>Asset Appreciation</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
				<table cellpadding=0 cellspacing=0>
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
									<td>Serial Number</td>
									<td>$led[serial]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Location</td>
									<td>$led[locat]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Description</td>
									<td>$led[des]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Date Bought</td>
									<td>$led[bdate]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Cost Amount</td>
									<td>$led[amount]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Net Value</td>
									<td><input type='hidden' name='netval' value='$netval'>$netval</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Appreciation Amount</td>
									<td><input type='text' size='10' name='depamt' value='$depamt'></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Date</td>
									<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td valign='bottom' align='right'><input type='submit' value='Confirm &raquo;'></td>
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
	return $view_data;
	
}


function confirm ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id,"num", 1,100, "Invalid num.");
	$v->isOk ($depamt, "float", 1, 14, "Invalid Depreciation Amount.");

	$date = $date_day."-".$date_month."-".$date_year;

	$date_year+=0;
	$date_month+=0;
	$date_day+=0;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust.view_data($_POST);
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

	$view_data = "
					<h3>Asset Appreciation</h3>
					<h4>Confirm</h4>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='cosamt' value='$led[amount]'>
						<input type='hidden' name='depamt' value='$depamt'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='date_day' value='$date_day'>
						<input type='hidden' name='date_month' value='$date_month'>
						<input type='hidden' name='date_year' value='$date_year'>
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
										<td>Serial Number</td>
										<td>$led[serial]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Location</td>
										<td>$led[locat]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Description</td>
										<td>$led[des]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Date Bought</td>
										<td>$led[bdate]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Cost Amount</td>
										<td>$led[amount]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Net Value</td>
										<td><input type='hidden' name='netval' value='$netval'>$netval</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Appreciation Amount</td>
										<td>$depamt</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
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
	$v->isOk ($depamt, "float", 1, 14, "Invalid Depreciation Amount.");
	if($netval < $depamt){
		$v->isOk ("###", "float", 1, 1, "Error : Depreciation amount must not be more than the Net Value.");
	}
	$v->isOk ($date, "date", 1, 14, "Invalid account open date.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
		$dep_acc = $grp["depacc"];
	}

	if ($led["accdep_acc"]) {
		$accdep_acc = $led["accdep_acc"];
	} else {
		// Maintain backwards compatibiltiy
		$accdep_acc = $grp["accdacc"];
	}

	# dt(depacc) ct(accdep)
	writetrans($accdep_acc, $dep_acc,  $date, $refnum, $depamt, "$led[des] Appreciation");

	db_connect();
	$sql = "UPDATE assets SET accdep = (accdep - '$depamt') WHERE (id='$id' AND div = '".USER_DIV."')";
	$up = db_exec($sql) or errdie("Could not update assets table.");

	$snetval = ($netval + $depamt);
	$sdate = date("Y-m-d");
	$sql = "INSERT INTO assetledger(assetid, asset, date, depamt, netval, div) VALUES ('$id', '$led[des]', '$sdate', '-$depamt', '$snetval', '".USER_DIV."')";
	$rec = db_exec($sql) or errdie("Could not write to asset ledger.");


	#resort date
	$cdarr = explode("-",$date);
	$cyear = $cdarr[2];
	$cmonth = $cdarr[1];
	$cday = $cdarr[0];
	
	$cdate = "$cyear-$cmonth-$cday";
	
	$cc = "<script> CostCenter('dt', 'Asset Appreciation', '$cdate', '$led[des] Appreciation', '$depamt', ''); </script>";

	$write ="
				$cc
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Asset Appreciation</th>
					</tr>
					<tr class='datacell'>
						<td>Asset Appreciation has been recorded</td>
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

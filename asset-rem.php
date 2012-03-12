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

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
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
  foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num", 1,100, "Invalid num.");

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
	$Data = pg_fetch_array($Rslt);

	foreach ($Data as $key => $value) {
		$$key = $value;
	}

	# Get group
	db_connect();
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);


	$view_data =
	"<h3>Confirm Asset Removal</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Asset Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Group</td><td>$grp[grpname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Serial Number</td><td>$serial</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Location</td><td>$locat</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$des</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Date Bought</td><td>$bdate</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td>$amount</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Date Added</td><td>$date</td></tr>
		</table>
	</td></tr>
	<tr><td valign=bottom><input type=submit value='Remove &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='asset-view.php'>View Assets</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $view_data;
}

# confirm new data
function con_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num",0 ,100, "Invalid number.");

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

	$sql = "UPDATE cubit.assets SET remaction='Removed'
			WHERE id='$id' AND div = '".USER_DIV."'";
	db_exec($sql) or errDie ("Unable to remove asset.");

	$sql = "SELECT * FROM cubit.assets WHERE id='$id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
	$db = pg_fetch_array($asset_rslt);

	$defaults = array();
	$defaults["serial"] = "''";
	$defaults["locat"] = "''";
	$defaults["des"] = "''";
	$defaults["date"] = "NULL";
	$defaults["bdate"] = "NULL";
	$defaults["amount"] = "'0'";
	$defaults["div"] = "'0'";
	$defaults["grpid"] = "'0'";
	$defaults["accdep"] = "'0'";
	$defaults["dep_perc"] = "'0'";
	$defaults["dep_month"] = "''";
	$defaults["team_id"] = "'0'";
	$defaults["puramt"] = "'0'";
	$defaults["conacc"] = "'0'";
	$defaults["remaction"] = "''";
	$defaults["saledate"] = "NULL";
	$defaults["saleamt"] = "'0'";
	$defaults["invid"] = "'0'";
	$defaults["autodepr_date"] = "NULL";
	$defaults["sdate"] = "NULL";
	$defaults["temp_asset"] = "'n'";
	$defaults["nonserial"] = "''";
	$defaults["type_id"] = "'0'";
	$defaults["split_from"] = "'1'";
	$defaults["serial2"] = "''";
	
	$db = array();
	foreach ($defaults as $key=>$val) {
		$db[$key] = (empty($asset[$key])) ? $val : "'$asset[$key]'";
	}
	
	$sql = "
		INSERT INTO cubit.assets_prev (asset_id, serial, locat, des, date,
			bdate, amount, div, grpid, accdep, dep_perc, dep_month, team_id,
			puramt, conacc, remaction, saledate, saleamt, invid, autodepr_date,
			sdate, temp_asset, nonserial, type_id, split_from, serial2)
			VALUES ('$id', $db[serial], $db[locat], $db[des], $db[date],
				$db[bdate], $db[amount], $db[div], $db[grpid], $db[accdep],
				$db[dep_perc], $db[dep_month], $db[team_id], $db[puramt],
				$db[conacc], 'Removed', NULL, '0',
				'0', $db[autodepr_date], $db[sdate], $db[temp_asset],
				$db[nonserial], $db[type_id], $db[split_from], '$db[serial2]')";
	db_exec($sql) or errDie("Unable to add to previous asset.");

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Asset Removed</th></tr>
	<tr class=datacell><td>Asset has been deleted from the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='asset-new.php'>New Asset</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='asset-view.php'>View Assets</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

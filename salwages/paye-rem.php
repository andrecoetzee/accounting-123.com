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

require ("../settings.php");

if ($_POST) {
	if ($_POST["key"] == "write") {
		# remove paye
		$OUTPUT = remPaye ($_POST);
	}
} else {
	# confirm removal
	$OUTPUT = confirmPaye ($_GET);
}


require ("../template.php");

##
# Functions
##

# confirm removal
function confirmPaye ($_GET)
{
	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");

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

	# connect to db
	db_connect ();

	# select paye bracket
	$sql = "SELECT * FROM paye WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to select PAYE bracket from database.", SELF);
	if (pg_numrows ($payeRslt) < 1) {
		return "No PAYE brackets found in database.";
	}
	# get result
	$myPaye = pg_fetch_array ($payeRslt);

	$confirmPaye =
"
<h3>Confirm removal of PAYE bracket</h3>

<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=id value='$myPaye[id]'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=right>".CUR." $myPaye[min]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=right>".CUR." $myPaye[max]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=right>$myPaye[percentage]%</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Cash amount extra</td><td align=right>".CUR." $myPaye[extra]</td></tr>
<tr><td><br></td><td align=right><input type=submit value='Remove PAYE bracket &raquo;'></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmPaye;
}

# remove entry
function remPaye ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");

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

	# connect to db
	db_connect ();

	# remove job
	$sql = "DELETE FROM paye WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to remove PAYE bracket.", SELF);
	if (pg_cmdtuples ($payeRslt) < 1) {
		return "Failed to delete PAYE bracket.";
	}

	$remPaye =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>PAYE bracket removed</th></tr>
<tr class=datacell><td>PAYE bracket has been successfully removed.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $remPaye;
}

?>

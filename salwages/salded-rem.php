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
require ("../settings.php");

$OUTPUT = "<li class='err'>Disabled</li>";
require("../template.php");
exit;

# decide what to do
if (isset ($_GET["id"])) {
	$OUTPUT = confirmDeduct ($_GET["id"]);
} elseif (isset ($_POST["key"])) {
	$OUTPUT = ($_POST["key"] == "rem") ? remDeduct ($_POST["id"]) : "Invalid use of module.";
} else {
	$OUTPUT = "Invalid use of module.";
}

# display output
require ("../template.php");

# confirm new data
function confirmDeduct($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid deduction id.");

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

	# get deduction info
	db_connect ();
	$sql = "SELECT * FROM salded WHERE id='$id' AND div = '".USER_DIV."'";
	$salRslt = db_exec ($sql) or errDie ("Unable to select salary deduction info from database.");
	if (pg_numrows ($salRslt) < 1) {
		return "Invalid reference number.";
	}
	$mySal = pg_fetch_array ($salRslt);

	$confirmDeduct =
"
<h3>Confirm removal of salary deduction</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=rem>
<input type=hidden name=id value='$id'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Name of deduction</td><td align=center>$mySal[deduction]</td></tr>
<tr class='bg-even'><td>Creditor name</td><td align=center>$mySal[creditor]</td></tr>
<tr class='bg-odd'><td>Reference no.</td><td align=center>$mySal[refno]</td></tr>
<tr class='bg-even'><td>Creditor details</td><td align=center>$mySal[details]</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Delete &raquo;'></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmDeduct;
}

# remove entry from db
function remDeduct ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "string", 1, 20, "Invalid reference number.");

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

	# delete from db
	$sql = "DELETE FROM salded WHERE id='$id' AND div = '".USER_DIV."'";
	$salRslt = db_exec ($sql) or errDie ("Unable to delete salary deduction from database.");

	# delete from db
	$sql = "DELETE FROM empdeduct WHERE dedid='$id' AND div = '".USER_DIV."'";
	$salRslt = db_exec($sql) or errDie("Unable to delete salary deduction from employee lists.");

	$writeDeduct =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Salary deduction deleted</th></tr>
<tr class=datacell><td>Salary deduction has been successfully deleted.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeDeduct;
}

?>

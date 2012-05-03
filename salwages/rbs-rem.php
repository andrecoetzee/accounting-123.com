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

$OUTPUT = "<li class='err'>Disabled feature.</li>";
# display output
require ("../template.php");
exit;

# decide what to do
if (isset ($_GET["id"])) {
	$OUTPUT = confirmAllow ($_GET["id"]);
} elseif (isset ($_POST["key"])) {
	$OUTPUT = ($_POST["key"] == "rem") ? remAllow ($_POST["id"]) : "Invalid use of module.";
} else {
	$OUTPUT = "Invalid use of module.";
}

# confirm new data
function confirmAllow ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid reimbursement ID.");

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
	$sql = "SELECT * FROM rbs WHERE id='$id' AND div = '".USER_DIV."'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select reimbursement info from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid reimbursement ID.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	$confirmAllow =
"
<h3>Confirm removal of reimbursement</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=rem>
<input type=hidden name=id value='$id'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Name of reimbursement</td><td align=center>$myAllow[name]</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Delete &raquo;'></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmAllow;
}

# remove entry from db
function remAllow ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid reimbursement ID.");

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
	$sql = "DELETE FROM rbs WHERE id='$id' AND div = '".USER_DIV."'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to delete reimbursement from database.");
	if(pg_cmdtuples ($allowRslt) < 1) {
		return "Unable to delete reimbursement from database.";
	}

	$writeAllow =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Reimbursement deleted</th></tr>
<tr class=datacell><td>Reimbursement, $id, has been successfully deleted.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeAllow;
}

?>

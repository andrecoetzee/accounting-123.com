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
	$OUTPUT = confirmFringe ($_GET["id"]);
} elseif (isset ($_POST["key"])) {
	$OUTPUT = ($_POST["key"] == "rem") ? remFringe ($_POST["id"]) : "Invalid use of module.";
} else {
	$OUTPUT = "Invalid use of module.";
}

# display output
require ("../template.php");

# confirm new data
function confirmFringe ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");

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
	$sql = "SELECT * FROM fringebens WHERE id='$id'";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefit from database.");
	if (pg_numrows ($fringeRslt) < 1) {
		return "Invalid fringe benefit ID.";
	}
	$myFringe = pg_fetch_array ($fringeRslt);

	$confirmFringe =
"
<h3>Confirm removal of fringe benefit</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=rem>
<input type=hidden name=id value='$id'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Fringe benefit</td><td align=center>$myFringe[fringeben]</td></tr>
<tr><td colspan=1 align=left><input type=button value='Back' onclick='javascript:history.back();'</td><td valign=center><input type=submit value='Delete &raquo;'></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmFringe;
}

# remove entry from db
function remFringe ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");

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
	$sql = "DELETE FROM fringebens WHERE id='$id'";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to delete fringe benefit from database.");
	if (pg_cmdtuples ($fringeRslt) < 1) {
		return "Unable to delete fringe benefit from database.";
	}

	$writeFringe =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Fringe benefit deleted</th></tr>
<tr class=datacell><td>Fringe benefit has been successfully deleted.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeFringe;
}

?>

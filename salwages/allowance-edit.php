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

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmAllow ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeAllow ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterAllow ($HTTP_POST_VARS["id"]);
	}
} else {
	$OUTPUT = enterAllow ($HTTP_GET_VARS["id"]);
}

# display output
require ("../template.php");

# enter new data
function enterAllow ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");

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
	$sql = "SELECT * FROM allowances WHERE id='$id' AND div = '".USER_DIV."'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid allowance ID.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, $myAllow["type"]);

	# get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$myAllow[accid]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acc = pg_fetch_array($accRslt);


	$enterAllow =
	"<h3>Edit allowance</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of allowance</td><td align=center><input type=text size=20 name=allowance value='$myAllow[allowance]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account</td><td align=center>$acc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Taxable</td><td align=center><select name=taxable><option value='yes'>Yes</option><option value='no'>No</option></select></td></tr>
	<!--<tr bgcolor='".TMPL_tblDataColor2."'><td>Allowance Type</td><td>$seltype</td></tr>//-->
	<input type=hidden name=type value='$myAllow[type]'>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $enterAllow;
}

# confirm new data
function confirmAllow ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($allowance, "string", 1, 100, "Invalid allowance name.");
	$v->isOk ($taxable, "string", 1, 4, "Invalid taxablility option.");
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");

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

	$confirmAllow =
	"<h3>Confirm allowance</h3>

	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=allowance value='$allowance'>
	<input type=hidden name=taxable value='$taxable'>
	<input type=hidden name=type value='$type'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of allowance</td><td align=center>$allowance</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Taxable</td><td align=center>$taxable</td></tr>
	<!--<tr bgcolor='".TMPL_tblDataColor2."'><td>Allowance Type</td><td align=center>$type</td></tr>//-->
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmAllow;
}

# write new data
function writeAllow ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($allowance, "string", 1, 100, "Invalid allowance name.");
	$v->isOk ($taxable, "string", 1, 4, "Invalid taxablility option.");
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");

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

	# write to db
	$sql = "UPDATE allowances SET type='$type', allowance='$allowance', taxable='$taxable' WHERE id='$id' AND div = '".USER_DIV."'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to add allowance to database.", SELF);

	$writeAllow =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Allowance edited</th></tr>
<tr class=datacell><td>Allowance, $allowance, has been successfully edited.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeAllow;
}

?>

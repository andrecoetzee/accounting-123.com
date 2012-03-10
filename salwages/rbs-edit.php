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
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid reimbursement ID.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	# get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$myAllow[account]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acc = pg_fetch_array($accRslt);


	$enterAllow =
	"<h3>Edit reimbursement</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of reimbursement</td><td align=center><input type=text size=20 name=name value='$myAllow[name]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account</td><td align=center>$acc[accname]</td></tr>
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
	$v->isOk ($name, "string", 1, 100, "Invalid reimbursement name.");
	//$v->isOk ($taxable, "string", 1, 4, "Invalid taxablility option.");
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

	$confirmAllow =
"
<h3>Confirm reimbursement</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=id value='$id'>
<input type=hidden name=name value='$name'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of reimbursement</td><td align=center>$name</td></tr>
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
	$v->isOk ($name, "string", 1, 100, "Invalid reimbursement name.");
	//$v->isOk ($taxable, "string", 1, 4, "Invalid taxablility option.");
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

	# connect to db
	db_connect ();

	# write to db
	$sql = "UPDATE rbs SET name='$name' WHERE id='$id' AND div = '".USER_DIV."'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to add reimbursement to database.", SELF);

	$writeAllow =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Reimbursement edited</th></tr>
<tr class=datacell><td>Reimbursement, $name, has been successfully edited.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeAllow;
}

?>

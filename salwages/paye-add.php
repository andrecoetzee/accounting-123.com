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

$OUTPUT = "<li class=err>This is a disabled feature.</li>";
require("../template.php");

# get settings
require ("../settings.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmPaye ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writePaye ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterPaye ($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = enterPaye ($HTTP_POST_VARS);
}

# display output
require ("../template.php");

# enter new paye bracket details
function enterPaye ($HTTP_POST_VARS)
{
	extract($HTTP_POST_VARS);

	if(!isset($min)) {
		$min="";
		$max="";
		$percentage="";
		$extra="";
	}

	$enterPaye ="<h3>New PAYE bracket</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum</td><td align=center><table><tr><td>".CUR."</td><td><input type=text size=10 name=min class=right value='$min'></td></tr></table></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum</td><td align=center><table><tr><td>".CUR."</td><td><input type=text size=10 name=max class=right value='$max'></td></tr></table></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=center><table><tr><td><input type=text size=10 name=percentage class=right value='$percentage'></td><td>%</td></tr></table></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td align=center><table><tr><td>".CUR."</td><td><input type=text size=10 name=extra class=right value='$extra'></td></td></tr></table></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $enterPaye;
}

# confirm new paye bracket details
function confirmPaye ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid taxable rate.");
	$v->isOk ($extra, "num", 0, 9, "Invalid extra cash amount.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		return $confirmCust."</li>".enterPaye($HTTP_POST_VARS);
	}

	$confirmPaye ="<h3>Confirm new PAYE bracket</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=min value='$min'>
	<input type=hidden name=max value='$max'>
	<input type=hidden name=percentage value='$percentage'>
	<input type=hidden name=extra value='$extra'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum</td><td align=right>".CUR." $min</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum</td><td align=right>".CUR." $max</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=right>$percentage %</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td align=right>".CUR." $extra</td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $confirmPaye;
}

# write new paye bracket
function writePaye ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		return enterPaye($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid taxable rate.");
	$v->isOk ($extra, "num", 0, 9, "Invalid extra cash amount.");

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

	# add PAYE to db
	$sql = "INSERT INTO paye (min, max, percentage, extra) VALUES ('$min', '$max', '$percentage', '$extra')";
	$payeRslt = db_exec ($sql) or errDie ("Unable to add PAYE bracket to database.", SELF);

	$writePaye ="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>PAYE bracket added to database</th></tr>
	<tr class=datacell><td>New PAYE bracket has been successfully added to Cubit.</td></tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writePaye;
}

?>

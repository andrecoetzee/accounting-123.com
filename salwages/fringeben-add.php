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
require ("../libs/ext.lib.php");


# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmFringe();
			break;
		case "write":
			$OUTPUT = writeFringe();
			break;
		default:
			$OUTPUT = enterFringe();
	}
} else {
	$OUTPUT = enterFringe();
}

# display output
require ("../template.php");

# enter new data
function enterFringe() {
	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, "Percentage");

	$OUTPUT =
	"<h3>Add Fringe Benefit to system</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=type value='Amount'>
	<tr><th colspan=2>Fringe Benefit Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center><input type=text size=20 name=fringeben></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $OUTPUT;
}

# confirm new data
function confirmFringe() {
	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fringeben, "string", 1, 100, "Invalid fringe benefit name.");
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


	$OUTPUT =
	"<h3>Confirm New Fringe Benefit</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=fringeben value='$fringeben'>
	<input type=hidden name=type value='$type'>
	<tr><th colspan=2>Fringe Benefit Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$fringeben</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $OUTPUT;
}

# write new data
function writeFringe() {
	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fringeben, "string", 1, 100, "Invalid fringe benefit name.");
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
	$sql = "INSERT INTO fringebens(fringeben, type, div)
			VALUES ('$fringeben', '$type', '".USER_DIV."')";
	$rslt = db_exec ($sql) or errDie ("Unable to add Fringe Benefit to database (DBE).");
	if (pg_cmdtuples ($rslt) < 1) {
		return "Unable to add Fringe Benefit to database (CNT).";
	}

	$OUTPUT =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Fringe Benefit added to system</th></tr>
	<tr class=datacell><td>New Fringe Benefit, $fringeben, has been successfully added to Cubit.</td></tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $OUTPUT;
}
?>

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
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid ID.");

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
	$sql = "SELECT * FROM fringebens WHERE id='$id' AND div = '".USER_DIV."'";
	$rslt = db_exec ($sql) or errDie ("Unable to select info from database.");
	if (pg_numrows ($rslt) < 1) {
		return "Invalid fringe benefit ID.";
	}
	$myFringe = pg_fetch_array ($rslt);

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, $myFringe["type"]);

	# get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$myFringe[accid]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acc = pg_fetch_array($accRslt);

	$OUTPUT =
	"<h3>Edit Fringe Benefit</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=get>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center><input type=text size=20 name=fringeben value='$myFringe[fringeben]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Type</td><td>$seltype</td></tr>
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
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);
        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fringeben, "string", 1, 100, "Invalid name.");
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");
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
	"<h3>Confirm Finge Benefit</h3>

	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=fringeben value='$fringeben'>
	<input type=hidden name=type value='$type'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$fringeben</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Type</td><td align=center>$type</td></tr>
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
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fringeben, "string", 1, 100, "Invalid name.");
	$v->isOk ($id, "num", 1, 20, "Invalid ID.");
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
	$sql = "UPDATE fringebens SET type='$type', fringeben='$fringeben', WHERE id='$id' AND div = '".USER_DIV."'";
	$rslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to database.", SELF);

	$OUTPUT =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Fringe Benefit edited</th></tr>
<tr class=datacell><td>Fringe Benefit, $fringeben, has been successfully edited.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $OUTPUT;
}

?>

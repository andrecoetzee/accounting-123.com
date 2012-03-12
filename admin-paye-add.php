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
require ("settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirmPaye ($_POST);
			break;
		case "write":
			$OUTPUT = writePaye ($_POST);
			break;
		default:
			$OUTPUT = enterPaye ();
	}
} else {
	$OUTPUT = enterPaye ();
}

# display output
require ("template.php");

# enter new paye bracket details
function enterPaye ()
{
	# connect to db
	db_connect ();

	$enterPaye =
"
<h3>New PAYE bracket</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=center>".CUR." <input type=text size=20 name=min class=right></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=center>".CUR." <input type=text size=20 name=max class=right></td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage to deduct</td><td align=center><input type=text size=20 name=percentage class=right>%</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
";
	return $enterPaye;
}

# confirm new paye bracket details
function confirmPaye ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid deduction percentage.");

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

	$confirmPaye =
"
<h3>Confirm new PAYE bracket</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=min value='$min'>
<input type=hidden name=max value='$max'>
<input type=hidden name=percentage value='$percentage'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=right>".CUR." $min</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=right>".CUR." $max</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage to deduct</td><td align=right>$percentage %</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Add new PAYE &raquo;'></td></tr>
</form>
</table>
";
	return $confirmPaye;
}

# write new paye bracket
function writePaye ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid deduction percentage.");

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
	$sql = "INSERT INTO paye (min, max, percentage) VALUES ('$min', '$max', '$percentage')";
	$payeRslt = db_exec ($sql) or errDie ("Unable to add PAYE bracket to database.", SELF);

	$writePaye =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>PAYE bracket added to database</th></tr>
<tr class=datacell><td>New PAYE bracket (R$min - R$max) has been successfully added to Cubit.</td></tr>
</table>
";
	return $writePaye;
}

?>

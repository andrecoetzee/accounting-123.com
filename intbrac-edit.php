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

##
# admin-paye-add.php :: New PAYE bracket
##

# get settings
require ("settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;

		case "write":
			$OUTPUT = write ($_POST);
			break;

		default:
			if (isset($_GET['id'])){
				$OUTPUT = edit ($_GET['id']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['id'])){
		$OUTPUT = edit ($_GET['id']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}


# display output
require ("template.php");

# Enter new paye bracket details
function edit ($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid interest bracket id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	db_connect();
	# get info
	$sql = "SELECT * FROM intbracs WHERE id = '$id'";
	$intRslt = db_exec ($sql) or errDie ("Unable to select interest bracket from database.", SELF);
	if (pg_numrows ($intRslt) > 0) {
		# get result
		$int = pg_fetch_array ($intRslt);
	} else {
		return "Invalid interest bracket ID.";
	}

	$enter =
	"<h3>Edit Interest bracket</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum</td><td align=center><table><tr><td>".CUR."</td><td><input type=text size=10 name=min value='$int[min]' class=right></td></tr></table></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum</td><td align=center><table><tr><td>".CUR."</td><td><input type=text size=10 name=max value='$int[max]' class=right></td></tr></table></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=center><table><tr><td><input type=text size=10 name=percentage value='$int[percentage]' class=right></td><td>%</td></tr></table></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# Confirm new paye bracket details
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid interest bracket id.");
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid interest percentage.");

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

	$confirm =
	"<h3>Confirm new Interest bracket</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=min value='$min'>
	<input type=hidden name=max value='$max'>
	<input type=hidden name=percentage value='$percentage'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum</td><td align=right>".CUR." $min</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum</td><td align=right>".CUR." $max</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=right>$percentage %</td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='intbrac-view.php'>View Interest Brackets</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new paye bracket
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid interest bracket id.");
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid interest percentage.");

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
	$sql = "UPDATE intbracs SET min = '$min', max = '$max', percentage = '$percentage' WHERE id = '$id'";
	$pRslt = db_exec ($sql) or errDie ("Unable to updates Interest bracket to database.", SELF);

	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Interest bracket edited</th></tr>
	<tr class=datacell><td>Interest bracket has been successfully edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='intbrac-view.php'>View Interest Brackets</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</tr>";

	return $write;
}
?>

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
# admin-paye-rem.php :: Delete PAYE bracket from database
##

require ("settings.php");

if ($_POST) {
	if ($_POST["key"] == "write") {
		# remove paye
		$OUTPUT = remInt ($_POST);
	}
} else {
	if (isset($_GET['id'])){
		$OUTPUT = confirmInt ($_GET);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}


require ("template.php");

##
# Functions
##

# confirm removal
function confirmInt ($_GET)
{
	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid Interest bracket ID.");

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

	# select paye bracket
	$sql = "SELECT * FROM intbracs WHERE id = '$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to select Interest bracket from database.", SELF);
	if (pg_numrows ($payeRslt) < 1) {
		return "No Interest brackets found in database.";
	}
	# get result
	$myInt = pg_fetch_array ($payeRslt);

	$confirmInt =
	"<h3>Confirm removal of Interest bracket</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$myInt[id]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum</td><td align=right>".CUR." $myInt[min]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum</td><td align=right>".CUR." $myInt[max]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=right>$myInt[percentage]%</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2><input type=submit value='Remove bracket &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirmInt;
}

# remove entry
function remInt ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid Interest bracket ID.");

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

	# remove job
	$sql = "DELETE FROM intbracs WHERE id = '$id'";
	$pRslt = db_exec ($sql) or errDie ("Unable to remove interest bracket.", SELF);

	$remInt =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Interest bracket removed</th></tr>
	<tr class=datacell><td>Interest bracket has been successfully removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='intbrac-add.php'>Add Interest Bracket</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='intbrac-view.php'>View Interest Brackets</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $remInt;
}

?>

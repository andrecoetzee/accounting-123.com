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
require ("libs/ext.lib.php");

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
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("template.php");

# enter new data
function enter ()
{
	$Sl = "SELECT * FROM posround";
	$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);
	if (pg_numrows ($Rs) < 1) {
		$Sl = "INSERT INTO posround (setting) VALUES ('No')";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);
		$Sl = "SELECT * FROM posround";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);
	}
	$Dets = pg_fetch_array($Rs);

	$ops = array("5cent"=>"Yes(to nearest 5c)","No"=>"No");
	$Ops = extlib_cpsel("op", $ops,$Dets['setting']);

	$enter =
	"<h3>Point of Sale Setting</h3>
	<form action='".SELF."' method=post>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Setting</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>POS rounding</td><td>$Ops</td></tr>
		</table>
	</td></tr>
	<tr><td valign=bottom><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($op, "string", 2, 13, "Invalid Option.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return $confirm;
		exit;
	}

	$confirm =
	"<h3>Confirm Point of Sale Setting</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Setting</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>POS rounding</td><td><input type=hidden name=op value='$op'>$op</td></tr>
		</table>
	</td></tr>
	<tr><td valign=bottom><input type=submit value='Write &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($op, "string", 2, 13, "Invalid Option.");

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

	db_connect ();

	$Sl = "UPDATE posround SET setting='$op'";
	$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);
	if (pg_cmdtuples ($Rs) < 1) {
		return "<li class=err>Unable to add asset to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Setting Updated</th></tr>
	<tr class=datacell><td>Point of Sale Setting has been updated.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

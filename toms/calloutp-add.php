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
require ("../template.php");

# enter new data
function enter ()
{

	$enter =
	"<h3>Add Call Out Person</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type='hidden' name='key' value='confirm'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class='bg-odd'><td>Call Out Person</td><td><input type='text' size='20' name='calloutp'></td></tr>
		<tr class='bg-even'><td>Contact Number</td><td><input type='text' size='20' name='telno'></td></tr>
		<tr><td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border='0' cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out People</a></td></tr>
		<tr class='bg-odd'><td><a href='../callout-new.php'>New Call Out Document</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutp, "string", 1, 255, "Invalid Call Out Person name.");
	$v->isOk ($telno, "string", 1, 255, "Invalid Call Out Person Contact Number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$confirm =
	"<h3>Confirm Call Out Person</h3>
	<form action='".SELF."' method='post'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type='hidden' name='key' value='write'>
		<input type='hidden' name='calloutp' value='$calloutp'>
		<input type='hidden' name='telno' value='$telno'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class='bg-odd'><td>Call Out Person</td><td>$calloutp</td></tr>
		<tr class='bg-even'><td>Contact Number</td><td>$telno</td></tr>
		<tr><td align=right></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out People</a></td></tr>
		<tr class='bg-odd'><td><a href='../callout-new.php'>New Call Out Document</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutp, "string", 1, 255, "Invalid Call Out Person name.");
	$v->isOk ($telno, "string", 1, 255, "Invalid Call Out Person Contact Number.");

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
	db_conn ("exten");

	# write to db
	$sql = "INSERT INTO calloutpeople(calloutp,telno,div) VALUES ('$calloutp','$telno','".USER_DIV."')";
	$salespRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);
	if (pg_cmdtuples ($salespRslt) < 1) {
		return "<li class=err>Unable to add Call Out Person to database.";
	}

	$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Call Out Person added to system</th></tr>
			<tr class=datacell><td>New Call Out Person <b>$calloutp</b>, has been successfully added to the system.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out People</a></td></tr>
			<tr class='bg-odd'><td><a href='../callout-new.php'>New Call Out Document</a></td></tr>
			<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

	return $write;
}
?>

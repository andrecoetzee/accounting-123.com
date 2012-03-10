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

# Decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# Display output
require ("../template.php");

# enter new data
function enter ()
{

	$enter =
	"<h3>Add Overdue Term</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><font class='required'>*</font>Overdue Term(days)</td><td align=center><input type=text size=20 name=days></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='od-view.php'>View Overdue Terms</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# Confirm new data
function confirm ($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($days, "num", 1, 255, "Invalid Overdue term.");

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
	"<h3>Confirm Overdue Term</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=days value='$days'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Overdue Term</td><td align=center>$days</td></tr>
	<tr><td></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='od-view.php'>View Overdue terms</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($days, "num", 1, 255, "Invalid Overdue term.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# Connect to db
	db_conn ("exten");

	# Write to db
	$sql = "INSERT INTO  od(days, div) VALUES ('$days', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add Overdue term to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class=err>Unable to add Overdue term to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Overdue term added to system</th></tr>
	<tr class=datacell><td>New Overdue term <b>$days</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='od-view.php'>View Overdue Terms</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

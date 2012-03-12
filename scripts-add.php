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
# admin-usradd.php :: Module to add users to the system
##

require ("settings.php");

if ($_POST) {
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

require ("template.php");

##
# functions
##

# enter new user's details
function enter ()
{
	# connect to db
	db_connect ();
$enter =
"
<h3>New Script to access control database</h3>

<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Script function </td><td align=right><input type=text size=20 name=name></td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>File </td><td align=right><input type=text size=20 name=file></td></tr>
<tr><td align=right colspan=2><input type=submit value='Confirm &raquo'></td></tr>
</form>
</table>
";
	return $enter;
}

# confirm entered info
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($name, "string", 1, 255, "Invalid script function.");
	$v->isOk ($file, "string", 1, 255, "Invalid file name.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	# connect to db
	db_connect ();

	$confirm ="
        <h3>New script</h3>
        <h4>Confirm entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=name value='$name'>
        <input type=hidden name=file value='$file'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Script Function</td><td>$name</td>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>File</td><td>$file</td>
        <tr><td align=right colspan=2><input type=submit value='Add script &raquo'></td></tr>
        </form>
        </table>";
	return $confirm;
}

# write user to db
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name, "string", 1, 255, "Invalid script Function.");
	$v->isOk ($file, "string", 1, 255, "Invalid file name.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	# connect to db
	db_connect ();

        # exit if script exists
	$sql = "SELECT * FROM scripts WHERE name = '$file'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for scripts scripts.");
	if (pg_numrows ($Rslt) > 0) {
		return "Script, <b> $file </b>, already exists in database.";
	}

	$sql = "INSERT INTO scripts (name, script) VALUES ('$file', '$name')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add script to database.");

	# status report
	$write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>New script added to database</th></tr>
        <tr class=datacell><td>New file, $name ($file), was successfully added to Cubit.</td></tr>
        </table>";

        return $write;
}
?>

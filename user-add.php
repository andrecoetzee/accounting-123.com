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
			$OUTPUT = confirmUser ($_POST);
			break;
		case "write":
			$OUTPUT = writeUser ($_POST);
			break;
		default:
			$OUTPUT = enterUser ();
	}
} else {
	$OUTPUT = enterUser ();
}

require ("template.php");

##
# functions
##

# enter new user's details
function enterUser ()
{
	# connect to db
	db_connect ();
$enterUser =
"
<h3>Add new user to database</h3>

<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>User name</td><td align=right><input type=text size=20 name=username></td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Password</td><td align=right><input type=password size=20 name=password></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Confirm password</td><td align=right><input type=password size=20 name=password2></td></tr>
<tr><td align=right colspan=2><input type=submit value='Confirm &raquo'></td></tr>
</form>
</table>
";
	return $enterUser;
}

# confirm entered info
function confirmUser ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($username, "string", 1, 20, "Invalid user name.");
	$v->isOk ($password, "string", 7, 20, "Invalid password.");
	$v->isOk ($password2, "string", 7, 20, "Invalid password 2.");
        $v->pwMatch ($password, $password2, "Passwords do not match.");

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

	$confirmUser ="
        <h3>Add user to database</h3>
        <h4>Confirm entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=username value='$username'>
        <input type=hidden name=password value='$password'>
        <input type=hidden name=password2 value='$password2'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>User name</td><td>$username</td>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Password</td><td>*</td>
        <tr><td colspan=2><br></td></tr>
        <tr><th colspan=2>Select user permissions</th></tr>";

        // list scripts
        db_connect();
        $sql = "SELECT * FROM scripts";
        $rslt = db_exec($sql);
        $i = 0;
        while($scr = pg_fetch_array($rslt)){
                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $confirmUser .="<tr bgcolor='$bgColor'><td colspan=2><input type=checkbox name=perm[] value='$scr[name]'>$scr[script]</td></tr>";
                $i++;
        }


        $confirmUser .="
        <tr><td align=right colspan=3><input type=submit value='Add user &raquo'></td></tr>
        </form>
        </table>";
	return $confirmUser;
}

# write user to db
function writeUser ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($username, "string", 1, 20, "Invalid user name.");
	$v->isOk ($password, "string", 7, 20, "Invalid password.");
	$v->isOk ($password2, "string", 7, 20, "Invalid password 2.");
        $v->pwMatch ($password, $password2, "Passwords do not match.");

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

	# exit if user exists
	$sql = "SELECT username FROM users WHERE username='$username'";
	$usrRslt = db_exec ($sql) or errDie ("Unable to check database for existing username.");
	if (pg_numrows ($usrRslt) > 0) {
		return "User, $username, already exists in database.";
	}

	# get md5 hash of password
	$password = md5 ($password);

	$sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
	$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");

        # write permissions
        foreach($perm as $key => $value){
                $sql = "INSERT INTO userscripts (username, script) VALUES ('$username', '$value')";
	        $nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
        }

	# status report
	$writeUser ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>New user added to database</th></tr>
        <tr class=datacell><td>New user, $username, was successfully added to Cubit.</td></tr>
        </table>
        <p>
        <tr>
        <table border=0 cellpadding='2' cellspacing='1'>
        <tr><th>Quick Links</th></tr>
        <tr bgcolor='#88BBFF'><td><a href='admin-usradd.php'>Add another user</a></td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </tr>";

        return $writeUser;
}
?>

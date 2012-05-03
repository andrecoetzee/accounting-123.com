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
require("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# Get template
require("template.php");

# Default view
function view()
{
	//layout
	$view = "<h3>Change Password</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Username</td><td>".USER_NAME."</td></tr>
	<tr class='bg-even'><td>".REQ."Old Password</td><td><input type=password size=20 name='password'></td></tr>
	<tr class='bg-odd'><td>".REQ."New Password</td></td><td><input type=password size=20 name='passwd'></td></tr>
	<tr class='bg-even'><td>".REQ."Retype New Password</td></td><td><input type=password size=20 name='passwd2'></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td valign=center align=right><input type=submit value='Confirm &raquo'></td></tr>
	</table>
	<P>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
	<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</form>
	</table>";

	return $view;
}

# confirm
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($password, "string", 1, 20, "Invalid password.");
	$v->isOk ($passwd, "string", 1, 20, "Invalid new password.");
	$v->isOk ($passwd2, "string", 1, 20, "Invalid new password.");
	$v->pwMatch ($passwd, $passwd2, "New Passwords do not match.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm."</li>".view();
	}

	# Make MD#5 of old password
	$MD5_PASS = md5 ($password);
	db_connect();
	$sql = "SELECT * FROM users WHERE username = '".USER_NAME."'";
	$rslt = db_exec($sql) or errDie("Unable to insert stock category to Cubit.",SELF);
	$user = pg_fetch_array($rslt);

	if($MD5_PASS != $user['password']){
		return "<li class=err> - Invalid Old Password</li>".view();
	}

	// Layout
	$confirm =
	"<h3>Change Password</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=password value='$password'>
	<input type=hidden name=passwd value='$passwd'>
	<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
	<tr class='bg-odd'><td>Username</td><td>".USER_NAME."</td></tr>
	<tr class='bg-odd'><td>New Password</td></td><td>******</td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Write &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write
function write($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($password, "string", 1, 20, "Invalid password.");
	$v->isOk ($passwd, "string", 1, 20, "Invalid new password.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "</li><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";

		return $confirm;
	}

	$MD5_PASS = md5 ($passwd);

	// insert into stock
	db_connect();
	$sql = "UPdate users SET password = '$MD5_PASS' WHERE username = '".USER_NAME."' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to insert stock category to Cubit.",SELF);

	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='200'>
  		<tr><th>Change Password</th></tr>
		<tr class='bg-odd'><td align=center>Your Password has been changed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

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

/*
 * admin-usrrem.php :: Module to remove users
 */

require ("settings.php");          // Get global variables & functions

// If form was submitted, confirm removal or remove entry

if ($_GET) {
	if ($_GET['username']) {
		// confirm removal
		$OUTPUT = confirmRem ($_GET['username']);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} elseif ($_POST) {
	if ($_POST['a'] == "rem") {
		// remove entry
		$OUTPUT = remUser ($_POST['username']);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} else {
	// Invalid use, display error
	errDie ("ERROR: Invalid use of module.", SELF);
}

# require template
require ("template.php");





// Confirm removal
function confirmRem ($username)
{

	// Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	$username = substr ($username, 0, 255);

	// Do some regex checking to make sure the stuff entered is ok
	if (preg_match ("/[^\w]/", $username)) {                        // Alphanum & space chars, 4-10
		$OUTPUT = "Invalid user name.\n<br><a href='Javascript:history.back();'>Back</a>\n";
	} else {
		// Connect to database
		Db_Connect ();

		# Prevent root user from being removed
		if($username == "Root" || $username == "root" || $username == "admin"){
			return "<li> - Admin User cannot be removed.";
		}

		// Query server
		$sql = "SELECT * FROM users WHERE username='$username' AND div = '".USER_DIV."'";
		$prnUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to select user: $username", SELF);          // Die with custom error if failed

		$myUsr = pg_fetch_array ($prnUsrRslt);

		$OUTPUT = "
			<h3>Confirm user removal</h3>
			<h4>Are you sure you want to delete this user?
			<p>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='a' value='rem'>
				<input type='hidden' name='username' value='$username'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='datacell'>
					<td>User ID</td>
					<td align='center'>$myUsr[userid]</td>
				</tr>
				<tr class='datacell'>
					<td>User name</td>
					<td align='center'>$username</td>
				</tr>
				<tr class='datacell2'>
					<td>Password</td>
					<td align='center'>*</td>
				</tr>
				<tr>
					<td><br></td>
					<td align='center'><input type='button' value='&laquo; Cancel' onClick='Javascript:history.back();'>&nbsp;<input type='submit' value='Remove user &raquo;'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
		        <tr>
		        	<th>Quick Links</th>
		        </tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}
	return $OUTPUT;

}




// Removes stock from database
function remUser ($username)
{

	$username = substr ($username, 0, 255);                      // Chop off anything after 10 chars

	// check content of variable
	if (preg_match ("/[^\w\s]/", $username)) {                      // Alphanum & space chars, 4-10
		$OUTPUT = "Invalid user name.";
	} else {
		// Connect to database
		Db_Connect ();

		// Query server
		$sql = "DELETE FROM users WHERE username='$username'";
		$RemRslt = db_exec ($sql) or errDie ("ERROR: Unable to delete user: $username", SELF);          // Die with custom error if failed
		if (pg_cmdtuples ($RemRslt) < 1) {
			return "Failed to delete user.";
		}

		// remove user access
		$sql = "DELETE FROM userscripts WHERE username='$username'";
		$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to delete access for user: $username", SELF);

		// Provide some info on status
		$OUTPUT = "
			<table ".TMPL_tblDflts." width='50%'>
				<tr>
					<th>User deleted from database</th>
				</tr>
				<tr class='datacell'>
					<td>User, '$username', was successfully deleted.</td>
				</tr>
			</table>
			<table ".TMPL_tblDflts.">
		        <tr>
		        	<th>Quick Links</th>
		        </tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}
	return $OUTPUT;

}



?>
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
 * user-view.php :: Module to view users
 */

require ("settings.php");          // Get global variables & functions

// show current users
$OUTPUT = printUsers ();


require ("template.php");

/*
 * Functions
 *
 */

// Prints a form to enter new stock details into

function printUsers ()
{
	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM users";
	$prnUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to view users", SELF);          // Die with custom error if failed
	$numrows = pg_numrows ($prnUsrRslt);

	if ($numrows < 1) {
		$OUTPUT = "No users currently in database.";

	} else {
		// Set up table to display in

		$OUTPUT = "
		<h3>View current users</h3>

		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>User ID</th><th>User name</th><th colspan=2 class=plain><br></th></tr>
		";

		// display all stock

		for ($i=0; $i < $numrows; $i++) {
			$myUsr = pg_fetch_array ($prnUsrRslt);

			if ($i % 2) {                                                              // every other row gets a diff color
				$bgColor = TMPL_tblDataColor1;
			} else {
				$bgColor = TMPL_tblDataColor2;
			}
			$OUTPUT .= "<tr bgcolor='$bgColor'><td>$myUsr[userid]</td><td>$myUsr[username]</td><td><a href='admin-usredit.php?username=$myUsr[username]'>Edit</a></td><td><a href='admin-usrrem.php?username=$myUsr[username]'>Remove</td></tr>";
		}
		$OUTPUT .= "</table>";
	}

	// call template to display the info and die
	return $OUTPUT;
}

?>

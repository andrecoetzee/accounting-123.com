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

// Get global variables & functions
require ("settings.php");

// Show current users
$OUTPUT = printSet();


require ("template.php");

// Prints a form to enter new stock details into
function printSet ()
{
	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE div = '".USER_DIV."'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class=err> No Setting currently in database.";
	} else {
		// Set up table to display in
		$OUTPUT = "
		<h3>View Current Settings</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Setting Type</th><th>Current Setting</th></tr>";

        	// display all settings
		for ($i = 0; $set =pg_fetch_array ($rslt); $i++) {
			$OUTPUT .= "<tr class='".bg_class()."'><td>$set[type]</td><td>$set[descript]</td></tr>";
		}
		$OUTPUT .= "</table>";
	}

	$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}
?>

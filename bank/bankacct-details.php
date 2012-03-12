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
require ("../settings.php");          // Get global variables & functions

// show current stock
editAccnt($_GET['accname']);

/*
 * Functions
 *
 */

// Prints a form to enter new stock details into

function editAccnt ($accname)
{
        // Set up table to display in

        $OUTPUT = "
	<h3>Edit Bank Account</h3>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Field</th><th>Value</th></tr>
	";

	// Connect to database
	Db_Connect ();
        $sql = "SELECT * FROM bankacct WHERE accname='$accname'";
        $bankRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank account details from database.", SELF);
	$numrows = pg_numrows ($bankRslt);

        if ($numrows > 1) {
		$OUTPUT = "There are more than one accounts with the same name.";
		require ("../template.php");
	}

       if ($numrows < 1) {
		$OUTPUT = "Bank account with the name <b>$accname</b> was not found in Cubit.";
		require ("../template.php");
	}

        $accnt = pg_fetch_array($bankRslt)
        $OUTPUT .= "
        
        ";

       // all template to display the info and die
	require ("../template.php");
}

?>

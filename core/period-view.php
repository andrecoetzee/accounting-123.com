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

require ("settings.php");          // Get global variables & functions

// show current period and year
printyr();

/*
 * Functions
 *
 */

// Default View
function printyr()
{
	// Set up table to display in
        $OUTPUT = "
	<h3>View active year and period</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
        <tr><th>Year Name</th><th>Period Name</th></tr>
	";

	// Connect to database
	core_Connect ();
        $sql = "SELECT * FROM active";
        $Rslt = db_exec ($sql) or errDie ("ERROR: Uable to get active period details from database.", SELF);
	$numrows = pg_numrows ($Rslt);

        if ($numrows < 1) {
		$OUTPUT = "<li>There are no Active periods/years defined in Cubit.";
		require ("template.php");
	}
        $act = pg_fetch_array ($Rslt);
        $OUTPUT .= "<tr class='bg-odd'><td align=center>$act[yrname]</td><td align=center>$act[prdname]</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='../reporting/index-reports.php'>Financials</a></td></tr>
		<tr class='bg-odd'><td><a href='../reporting/index-reports-other.php'>Other Reports</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

        // all template to display the info and die
	require ("template.php");
}
?>

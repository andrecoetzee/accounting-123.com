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
require ("../settings.php");

$OUTPUT = view ();

require ("../template.php");



function view ()
{

	core_Connect ();

	$sql = "SELECT * FROM save_bal_sheet WHERE div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to Balance Sheets", SELF);

	if (pg_numrows ($Rslt) < 1) {
		$OUTPUT = "<li class='err'> There are no saved Balance Sheets.</li>";
	} else {
		// Set up table to display in
		$OUTPUT = "
			<h3>View Saved Balance Sheets</h3>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Balance Sheet No.</th>
					<th>Date</th>
					<th colspan='2'>Options</th>
				</tr>";

		// display all statements
		for ($i=0; $sheet = pg_fetch_array ($Rslt); $i++) {
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$sheet[id]($sheet[des])</td>
					<td>$sheet[gendate]</td>
					<td><a target='_blank' href='bal-sheet-print.php?id=$sheet[id]'>Print</a></td>
					<td><a href='../xls/bal-xls.php?id=$sheet[id]'>Spreadsheet</a></td>
				</tr>";
		}
		$OUTPUT .= "</table>";
	}

	$OUTPUT .= "
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>
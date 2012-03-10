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

// show current users
$OUTPUT = view ();

require ("../template.php");

function view ()
{
	// Connect to database
	core_Connect ();

	// Query server
	$sql = "SELECT * FROM save_income_stmnt WHERE div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to view users", SELF);          // Die with custom error if failed

	if (pg_numrows ($Rslt) < 1) {
		$OUTPUT = "<li> There are no saved Income Statements.";
	} else {
		// Set up table to display in
		$OUTPUT = "
		<h3>View Saved Income Statements</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Statement No.</th><th>Date</th><th colspan=2>Options</th></tr>";

		// display all statements
		for ($i=0; $st = pg_fetch_array ($Rslt); $i++) {
			if ($i % 2) {                                                              // every other row gets a diff color
				$bgColor = TMPL_tblDataColor2;
			} else {
				$bgColor = TMPL_tblDataColor1;
			}
			$OUTPUT .= "<tr bgcolor='$bgColor'><td>$st[id]($st[des])</td><td>$st[gendate]</td>
			<td><a target='_blank' href='income-stmnt-print.php?id=$st[id]'>Print</a></td>
			<td><a href='../xls/income-xls.php?id=$st[id]'>Spreadsheet</a></td></tr>";
		}
		$OUTPUT .= "</table>";
	}
	$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='datacell'><td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
	<tr class='datacell'><td align='center'><a href='index-reports.php'>Financials</a></td></tr>
	<tr class='datacell'><td align='center'><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	// call template to display the info and die
	return $OUTPUT;
}
?>

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
	$cf = new dbSelect("save_cashflow", "core", grp(
		m("where", "div='".USER_DIV."'")
	));
	$cf->run();

	if ($cf->num_rows() < 1) {
		$OUTPUT = "<li> There are no saved Cash Flow Statements.";
	} else {
		// Set up table to display in
		$OUTPUT = "
		<h3>View Saved Cash Flow Statements</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr>
			<th>Statement No.</th>
			<th>Generated</th>
			<th colspan=2>Options</th>
		</tr>";

		// display all statements
		while ($stmnt = $cf->fetch_array()) {
			$OUTPUT .= "
			<tr class='".bg_class()."'>
				<td>$stmnt[id]($stmnt[des])</td>
				<td>$stmnt[gentime]</td>
				<td><a target='_blank' href='cash-flow-print.php?id=$stmnt[id]'>Print</a></td>
				<td><a href='cash-flow-print.php?id=$stmnt[id]&xls=t'>Spreadsheet</a></td>
			</tr>";
		}
		$OUTPUT .= "</table>";
	}

	$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class=datacell><td><a href='index-reports.php'>Financials</a></td></tr>
	<tr class=datacell><td><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	// call template to display the info and die
	return $OUTPUT;
}
?>

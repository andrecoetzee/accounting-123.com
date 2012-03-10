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

require ("../settings.php");

$OUTPUT = "<center>
	<table border=0 width='100%'><tr>
	<td valign=top width='100%' align=center>
	<table border=0 width='100%'>
	<tr><td align=center><h3>Financial Statements</h3></td></tr>
	<tr><td align=center><b><a href='ledger_export.php' class=nav>Export General Ledger Spreadsheet</a></b></td></tr>
	<tr><td align=center><b><a href='trial_bal.php' class=nav>Generate Trial Balance</a></b></td></tr>
	<tr><td align=center><b><a href='trial_bal-view.php' class=nav>View Saved Trial Balances</a></b></td></tr>
	<tr><td align=center><b><a href='income-stmnt.php' class=nav>Generate Income Statement</a></b></td></tr>
	<tr><td align=center><b><a href='income-stmnt-view.php' class=nav>View Saved Income Statements</a></b></td></tr>
	<tr><td align=center><b><a href='bal-sheet.php' class=nav>Generate Balance Sheet</a></b></td></tr>
	<tr><td align=center><b><a href='bal-sheet-view.php' class=nav>View Saved Balance Sheets</a></b></td></tr>
	<tr><td align=center><b><a href='cash-flow.php' class=nav>Generate Statement of Cash Flow</a></b></td></tr>
	<tr><td align=center><b><a href='cash-flow-view.php' class=nav>View Saved Cash Flow Statements</a></b></td></tr>
	</table></td>
	</tr>
	</table>"
	.mkQuickLinks(
		ql("index-reports.php", "All Report Options"),
		ql("index-reports-banking.php", "Banking Reports"),
		ql("index-reports-debtcred.php", "Debtors & Creditors Reports"),
		ql("index-reports-journal.php", "General Ledger Reports"),
		ql("index-reports-other.php", "Other Reports")
	);

require ("../template.php");
?>

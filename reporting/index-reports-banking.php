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
	<td valign=top align=center><table width='100%'>
	<tr><td align=center><h3>Banking</h3></td></tr>
	<tr><td align=center><b><a href=# onClick=printer2('reporting/bank-recon') class=nav>Bank Reconciliation</a></b></td></tr>
	<tr><td align=center><b><a href='not-banked.php' class=nav>List Outstanding Bank Payments/Receipts</a></b></td></tr>
	<tr><td align=center><b><a href='banked.php' class=nav>Cash Book Analysis of Payments/Receipts</a></b></td></tr>
	<tr><td align=center><b><a href='bank-recon-saved.php' class=nav>View Saved Bank Reconciliations</a></b></td></tr>
	</table></td>
	</tr></table>"
	.mkQuickLinks(
		ql("index-reports.php", "All Report Options"),
		ql("index-reports-stmnt.php", "Current Year Financial Statements"),
		ql("index-reports-debtcred.php", "Debtors & Creditors Reports"),
		ql("index-reports-journal.php", "General Ledger Reports"),
		ql("index-reports-other.php", "Other Reports")
	);

require ("../template.php");
?>

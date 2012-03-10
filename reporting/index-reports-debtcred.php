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

$OUTPUT = "
<center>
<table border='0' width='100%'>
	<tr>
		<td valign=top width='100%' align='center'>
			<table width='100%' align='center'>
				<tr><td align='center'><h3>Debtors & Creditors</h3></td></tr>
				<tr><td align='center'><b><a href='debt-age-analysis.php' class='nav'>Debtors Age Analysis</a></b></td></tr>
				<tr><td align='center'><b><a href='cust-ledger.php' class='nav'>Debtors Ledger</a></b></td></tr>
				<tr><td align='center'><b><a href='cred-age-analysis.php' class='nav'>Creditors Age Analysis</a></b></td></tr>
				<tr><td align='center'><b><a href='supp-ledger.php' class='nav'>Creditors Ledger</a></b></td></tr>
			</table>
		</td>
	</tr>
</table>"
	.mkQuickLinks(
		ql("index-reports.php", "All Report Options"),
		ql("index-reports-banking.php", "Banking Reports"),
		ql("index-reports-stmnt.php", "Current Year Financial Statements"),
		ql("index-reports-journal.php", "General Ledger Reports"),
		ql("index-reports-other.php", "Other Reports")
	);

require ("../template.php");
?>

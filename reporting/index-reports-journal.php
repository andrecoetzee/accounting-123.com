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
	<table width='100%'>
	<tr><td align=center><h3>General Ledger</h3></td></tr>
	<tr><td align=center><b><a href='ledger.php' class=nav>Individual Ledger Accounts</a></b></td></tr>
	<tr><td align=center><b><a href='ledger-prd.php' class=nav>Period Range General Ledger</a></b></td></tr>
	<tr><td align=center><b><a href='alltrans-refnum.php' class=nav>Detailed General Ledger</a></b></td></tr>
	<tr><td align=center><b><a href='ledger-ytd.php' class=nav>Year Review General Ledger</a></b></td></tr>
	<tr><td align=center><b><a href='ledger_export.php' class=nav>Export Account Movement Report</a></b></td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr><td align=center><h3>Journals</h3></td></tr>
	<tr><td align=center><b><a href='alltrans.php' class=nav>All Journal Entries</a></b></td></tr>
	<tr><td align=center><b><a href='trans-amt.php' class=nav>All Journal Entries By Ref no.</a></b></td></tr>
	<tr><td align=center><b><a href='alltrans-prd.php' class=nav>All Journal Entries (Period Range)</a></b></td></tr>
	<tr><td align=center><b><a href='acc-trans.php' class=nav>Journal Entries Per Account</a></b></td></tr>
	<tr><td align=center><b><a href='acc-trans-prd.php' class=nav>Journal Entries Per Account (Period Range)</a></b></td></tr>
	<tr><td align=center><b><a href='accsub-trans.php' class=nav>Journal Entries Per Main Account</a></b></td></tr>
	<tr><td align=center><b><a href='cat-trans.php' class=nav>Journal Entries Per Category</a></b></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center><h3>ISA 240 assistance report</h3></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>(Incomplete)List of Incomplete/Late transactions (processed after month or year end) - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>(Duplicates) Duplicate reference numbers or descriptions - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>(Permissions)Users tried access scripts not allowed/permissions list time/date - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>(Processing Variances)Amounts of transactions by \"Root\" user vs other users in percentages and represented in value - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>Average user = amount of users divided by total transactions - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>Data Test Result analysis: Random (sampling), Systematic, Block, Monetary - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>Independent spreadsheet exports - Cubit v2.8</a></b></td></tr>
	<tr><td align=center><b><a href='index-reports-journal.php' class='nav'>Select high value, static, or negative transactions and balances, for review - Cubit v2.8</a></b></td></tr>
	</table>
	</td>
	</tr>
	</table>"
	.mkQuickLinks(
		ql("index-reports.php", "All Report Options"),
		ql("index-reports-banking.php", "Banking Reports"),
		ql("index-reports-stmnt.php", "Current Year Financial Statements"),
		ql("index-reports-debtcred.php", "Debtors & Creditors Reports"),
		ql("index-reports-other.php", "Other Reports")
	);

require ("../template.php");


?>
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

/*
 * index-accnt.php :: accounts index
 */

require ("settings.php");
// <td valign=top align=center width='25%'><a href=audit/yr-prd-trans-new.php target=mainframe class=nav onMouseOver='imgSwop(\"transactions\", \"images/journalsh.gif\");' onMouseOut='imgSwop(\"transactions\", \"images/journal.gif\");'><img src='images/journal.gif' border=0 alt='Enter Previous Year Transaction' title='Enter Previous Year Transaction' name=transactions><br>Enter Previous Year Transaction</a></td>
// 	<td valign=top align=center width='25%'><a href=audit/trans-view.php target=mainframe class=nav onMouseOver='imgSwop(\"mtransactions\", \"images/multijournalsh.gif\");' onMouseOut='imgSwop(\"mtransactions\", \"images/multijournal.gif\");'><img src='images/multijournal.gif' border=0 alt='View Previous Year Transaction' title='View Previous Year Transaction' name=mtransactions><br>View Previous Year Transaction</a></td>
// 	
$OUTPUT = "
<br>
<center>
<h3>Audit</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
	<td valign=top align=center width='25%'><a href=audit/trial-bal.php target=mainframe class=nav onMouseOver='imgSwop(\"trial\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"trial\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Generate Previous Year Trial Balance' title='Generate Previous Year Trial Balance' name=trial><br>Generate Previous Year Trial Balance</a></td>
	<td valign=top align=center width='25%'><a href=audit/yr-income-stmnt.php target=mainframe class=nav onMouseOver='imgSwop(\"inc\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"inc\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Generate Previous Year Income Statement' title='Generate Previous Year Income Statement' name=inc><br>Generate Previous Year <br>Income Statement</a></td>
	<td valign=top align=center width='25%'><a href=audit/balance-sheet.php target=mainframe class=nav onMouseOver='imgSwop(\"sheet\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"sheet\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Generate Previous Year Balance Sheet' title='Generate Previous Year Balance Sheet' name=sheet><br>Generate Previous Year <br>Balance Sheet</a></td>
	<td valign=top align=center width='25%'><a href=audit/ledger-audit.php target=mainframe class=nav onMouseOver='imgSwop(\"sheeta\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"sheeta\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='View Previous Year General Ledger' title='View Previous Year General Ledger' name=sheeta><br>View Previous Year<br>General Ledger</a></td>
	</tr>
	<tr>
	<td valign=top align=center width='25%'><a href=audit/ledger-audit-prd.php target=mainframe class=nav onMouseOver='imgSwop(\"asheeta\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"asheeta\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='View Previous Year General Ledger' title='View Previous Year General Ledger' name=asheeta><br>View Previous Year<br>General Ledger by Period Range</a></td>
	<td valign=top align=center width='25%'><a href=audit/cust-ledger-audit.php target=mainframe class=nav onMouseOver='imgSwop(\"transactions\", \"images/journalsh.gif\");' onMouseOut='imgSwop(\"transactions\", \"images/journal.gif\");'><img src='images/journal.gif' border=0 alt='View Previous Year Debtors Ledger' title='View Previous Year Debtors Ledger' name=transactions><br>View Previous Year Debtors Ledger</a></td>
	<td valign=top align=center width='25%'><a href=audit/supp-ledger-audit.php target=mainframe class=nav onMouseOver='imgSwop(\"mtransactions\", \"images/multijournalsh.gif\");' onMouseOut='imgSwop(\"mtransactions\", \"images/multijournal.gif\");'><img src='images/multijournal.gif' border=0 alt='View Previous Year Creditors Ledger' title='View Previous Year Creditors Ledger' name=mtransactions><br>View Previous Year Creditors Ledger</a></td>
	<td valign=top align=center width='25%'><a href=audit/stock-ledger-audit.php target=mainframe class=nav onMouseOver='imgSwop(\"smtransactions\", \"images/multijournalsh.gif\");' onMouseOut='imgSwop(\"smtransactions\", \"images/multijournal.gif\");'><img src='images/multijournal.gif' border=0 alt='View Previous Year Stock Ledger' title='View Previous Year Stock Ledger' name=smtransactions><br>View Previous Year Stock Ledger</a></td>
</tr>
<tr>

</tr>
</table>
<table border=0 cellpadding='2' cellspacing='1' width=15%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
</center>
</table>


";

require ("template.php");
?>

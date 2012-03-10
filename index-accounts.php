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

$OUTPUT = "
<br>
<center>
<h3>Accounting</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
	<td valign=top align=center width='20%'><a href=index-company.php target=mainframe class=nav onMouseOver='imgSwop(\"company\", \"images/banksh.gif\");' onMouseOut='imgSwop(\"company\", \"images/bank.gif\");'><img src='images/bank.gif' border=0 alt='Company' title='Company' name=company><br>Company</a></td>
	<td valign=top align=center width='20%'><a href=core/trans-new.php target=mainframe class=nav onMouseOver='imgSwop(\"transactions\", \"images/journalsh.gif\");' onMouseOut='imgSwop(\"transactions\", \"images/journal.gif\");'><img src='images/journal.gif' border=0 alt='Transactions' title='Transactions' name=transactions><br>Journal Entry</a></td>
	<td valign=top align=center width='20%'><a href=core/trans-new-sep.php target=mainframe class=nav onMouseOver='imgSwop(\"st\", \"images/multijournalsh.gif\");' onMouseOut='imgSwop(\"st\", \"images/multijournal.gif\");'><img src='images/multijournal.gif' border=0 alt='Multiple Transactions' title='Transactions' name=st><br>Journal Entries (One DT/CT, multiple CT/DT)</a></td>
	<td valign=top align=center width='20%'><a href=core/multi-trans.php target=mainframe class=nav onMouseOver='imgSwop(\"mtransactions\", \"images/multijournalsh.gif\");' onMouseOut='imgSwop(\"mtransactions\", \"images/multijournal.gif\");'><img src='images/multijournal.gif' border=0 alt='Multiple Transactions' title='Multiple Transactions' name=mtransactions><br>Multiple Journal Entries</a></td>
	<td valign=top align=center width='20%'><a href=reporting/index-reports.php target=mainframe class=nav onMouseOver='imgSwop(\"reports\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"reports\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Financials' title='Financials' name=reports><br>Financials</a></td>
</tr>
<tr>
	<td valign=top align=center width='20%'><a href=core/trans-batch-new.php target=mainframe class=nav onMouseOver='imgSwop(\"batch\", \"images/batchsh.gif\");' onMouseOut='imgSwop(\"batch\", \"images/batch.gif\");'><img src='images/batch.gif' border=0 alt='Add Batch Transactions' title='Add Batch Transactions' name=batch><br>Add Transaction to batch</a></td>
	<td valign=top align=center width='20%'><a href=core/trans-batch.php target=mainframe class=nav onMouseOver='imgSwop(\"batchs\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"batchs\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='Add Batch Transactions' title='Add Batch Transactions' name=batchs><br>Add Multiple Transactions to batch</a></td>
	<td valign=top align=center width='20%'><a href=core/batch-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vbatch\", \"images/viewmultibatchsh.gif\");' onMouseOut='imgSwop(\"vbatch\", \"images/viewmultibatch.gif\");'><img src='images/viewmultibatch.gif' border=0 alt='View Batch Entries' title='View Batch Entries' name=vbatch><br>View Batch Entries</a></td>
	<td valign=top align=center width='20%'><a href=ledger/ledger-new.php target=mainframe class=nav onMouseOver='imgSwop(\"ah\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"ah\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='Add New High Speed Input Ledger' title='Add New High Speed Input Ledger' name=ah><br>Add New High Speed Input Ledger</a></td>
	<td valign=top align=center width='20%'><a href=ledger/ledger-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vh\", \"images/viewmultibatchsh.gif\");' onMouseOut='imgSwop(\"vh\", \"images/viewmultibatch.gif\");'><img src='images/viewmultibatch.gif' border=0 alt='View High Speed Input Ledgers' title='View High Speed Input Ledgers' name=vh><br>View High Speed Input Ledgers</a></td>
</tr>
<tr>
	<td valign=top align=center width='20%'><a href=index-assets.php target=mainframe class=nav onMouseOver='imgSwop(\"settings\", \"images/viewassetsh.gif\");' onMouseOut='imgSwop(\"settings\", \"images/viewasset.gif\");'><img src='images/viewasset.gif' border=0 alt='Asset Ledger' title='Ledger' name=settings><br>Asset Ledger</a></td>
	<td valign=top align=center width='20%'><a href='bank/index-cashbook.php' target=mainframe class=nav onMouseOver='imgSwop(\"addnewtransfer\", \"images/cashbooksh.gif\");' onMouseOut='imgSwop(\"addnewtransfer\", \"images/cashbook.gif\");'><img src='images/cashbook.gif' border=0 alt='Cash Book' title='Cash Book' name=addnewtransfer ><br>Cash Book</a></td>
	<td valign=top align=center width='20%'><a href='bank/index-pettycashbook.php' target=mainframe class=nav onMouseOver='imgSwop(\"pcash\", \"images/cashbooksh.gif\");' onMouseOut='imgSwop(\"pcash\", \"images/cashbook.gif\");'><img src='images/cashbook.gif' border=0 alt='Petty Cash Book' title='Petty Cash Book' name=pcash><br>Petty Cash Book</a></td>
	<td valign=top align=center width='20%'><a href=index-recuring.php target=mainframe class=nav onMouseOver='imgSwop(\"recuring\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"recuring\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='Recurring Transactions' title='Recurring Transactions' name=recuring><br>Recurring Transactions</a></td>
	<td valign=top align=center width='20%'><a href=bank/bankacct-new.php target=mainframe class=nav onMouseOver='imgSwop(\"addbankaccount\", \"images/banksh.gif\");' onMouseOut='imgSwop(\"addbankaccount\", \"images/bank.gif\");'><img src='images/bank.gif' border=0 alt='Add bank account' title='Add bank account' name=addbankaccount><br>Add bank account</a></td>
</tr>
<tr>
	<td valign=top align=center width='20%'><a href=bank/bankacct-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewbankaccount\", \"images/viewbanksh.gif\");' onMouseOut='imgSwop(\"viewbankaccount\", \"images/viewbank.gif\");'><img src='images/viewbank.gif' border=0 alt='View bank account' title='View bank account' name=viewbankaccount ><br>View bank account</a></td>
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

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


header("Location: ../index-accounts.php");
exit;

require ("../settings.php");

$OUTPUT = "
<br>
<center>
<h3>Banking</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>

<tr>
	<td valign=top align=center width='20%'><a href=bankacct-new.php target=mainframe class=nav onMouseOver='imgSwop(\"addbankaccount\", \"../images/banksh.gif\");' onMouseOut='imgSwop(\"addbankaccount\", \"../images/bank.gif\");'><img src='../images/bank.gif' border=0 alt='Add bank account' title='Add bank account' name=addbankaccount><br>Add bank account</a></td>
	<td valign=top align=center width='20%'><a href=bankacct-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewbankaccount\", \"../images/viewbanksh.gif\");' onMouseOut='imgSwop(\"viewbankaccount\", \"../images/viewbank.gif\");'><img src='../images/viewbank.gif' border=0 alt='View bank account' title='View bank account' name=viewbankaccount ><br>View bank account</a></td>
	<td valign=top align=center width='20%'><a href=bank-stmnt.php target=mainframe class=nav onMouseOver='imgSwop(\"stmnt\", \"../images/multibankingsh.gif\");' onMouseOut='imgSwop(\"stmnt\", \"../images/multibanking.gif\");'><img src='../images/multibanking.gif' border=0 alt='Add Multiple Transactions' title='Add Multiple Transactions' name=stmnt ><br>Add Multiple Bank Transaction</a></td>
</tr>
<tr>
	<td valign=top align=center width='20%'><a href='index-cashbook.php' target=mainframe class=nav onMouseOver='imgSwop(\"addnewtransfer\", \"../images/cashbooksh.gif\");' onMouseOut='imgSwop(\"addnewtransfer\", \"../images/cashbook.gif\");'><img src='../images/cashbook.gif' border=0 alt='Cash Book' title='Cash Book' name=addnewtransfer ><br>Cash Book</a></td>
	<td valign=top align=center width='20%'><a href='index-pettycashbook.php' target=mainframe class=nav onMouseOver='imgSwop(\"pcash\", \"../images/cashbooksh.gif\");' onMouseOut='imgSwop(\"pcash\", \"../images/cashbook.gif\");'><img src='../images/cashbook.gif' border=0 alt='Petty Cash Book' title='Petty Cash Book' name=pcash><br>Petty Cash Book</a></td>
</tr>
</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
</table></center>";

require ("../template.php");
?>

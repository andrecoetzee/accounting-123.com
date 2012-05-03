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
<br>
<center>
<h3>Cash Book</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
	<td valign=top align=center width='25%'><a href=bank-pay-add.php target=mainframe class=nav onMouseOver='imgSwop(\"addchequepayment\", \"../images/bankpaymentsh.gif\");' onMouseOut='imgSwop(\"addchequepayment\", \"../images/bankpayment.gif\");'><img src='../images/bankpayment.gif'  border=0 alt='Add Bank payment' title='Add Bank payment' name=addchequepayment><br>Add Bank Payment</a></td>
	<td valign=top align=center width='25%'><a href=multi-bank-pay-add.php target=mainframe class=nav onMouseOver='imgSwop(\"multiaddchequepayment\", \"../images/bankpaymentsh.gif\");' onMouseOut='imgSwop(\"multiaddchequepayment\", \"../images/bankpayment.gif\");'><img src='../images/bankpayment.gif'  border=0 alt='Add Multiple Bank payment' title='Add Bank payment' name=multiaddchequepayment><br>Add Multiple Bank Payment</a></td>
	<td valign=top align=center width='25%'><a href=bank-recpt-add.php target=mainframe class=nav onMouseOver='imgSwop(\"adddeposit\", \"../images/bankreceiptsh.gif\");' onMouseOut='imgSwop(\"adddeposit\", \"../images/bankreceipt.gif\");'><img src='../images/bankreceipt.gif'  border=0 alt='Add deposit' title='Add deposit' name=adddeposit><br>Add Bank Receipt</a></td>
	<td valign=top align=center width='25%'><a href=multi-bank-recpt-add.php target=mainframe class=nav onMouseOver='imgSwop(\"multiadddeposit\", \"../images/bankreceiptsh.gif\");' onMouseOut='imgSwop(\"multiadddeposit\", \"../images/bankreceipt.gif\");'><img src='../images/bankreceipt.gif'  border=0 alt='Add deposit' title='Add deposit' name=multiadddeposit><br>Add Multi Bank Receipt</a></td>
</tr>
<tr>
	<td valign=top align=center width='25%'><a href=bank-recpt-inv.php target=mainframe class=nav onMouseOver='imgSwop(\"adddepositinv\", \"../images/bankcreditreceiptsh.gif\");' onMouseOut='imgSwop(\"adddepositinv\", \"../images/bankcreditreceipt.gif\");'><img src='../images/bankcreditreceipt.gif'  border=0 alt='Add deposit' title='Add deposit' name=adddepositinv><br>Add Bank Receipt (for Customers)</a></td>
	<td valign=top align=center width='25%'><a href=bank-pay-supp.php target=mainframe class=nav onMouseOver='imgSwop(\"addsupp\", \"../images/bankcreditreceiptsh.gif\");' onMouseOut='imgSwop(\"addsupp\", \"../images/bankcreditreceipt.gif\");'><img src='../images/bankcreditreceipt.gif'  border=0 alt='Add Payment' title='Add Payment' name=addsupp><br>Add Bank Payment (to Suppliers)</a></td>
	<td valign=top align=center width='25%'><a href='cashbook-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"viewnewtransfer\", \"../images/cashbooksh.gif\");' onMouseOut='imgSwop(\"viewnewtransfer\", \"../images/cashbook.gif\");'><img src='../images/cashbook.gif'  border=0 alt='View Cash Book' title='View Cash Book' name=viewnewtransfer><br>View Cash Book</a></td>
	<td valign=top align=center width='25%'><a href=../bank/bank-stmnt.php target=mainframe class=nav onMouseOver='imgSwop(\"stmnt\", \"../images/multibankingsh.gif\");' onMouseOut='imgSwop(\"stmnt\", \"../images/multibanking.gif\");'><img src='../images/multibanking.gif' border=0 alt='Add Multiple Transactions' title='Add Multiple Transactions' name=stmnt ><br>Add Multiple Bank Transaction</a></td>
</tr>
<tr>
	<td valign=top align=center width='25%'><a href=bank-payment-customer.php target=mainframe class=nav onMouseOver='imgSwop(\"addsuppc\",\"../images/bankcreditreceiptsh.gif\");' onMouseOut='imgSwop(\"addsuppc\",\"../images/bankcreditreceipt.gif\");'><img src='../images/bankcreditreceipt.gif'  border=0 alt='Add Payment' title='Add Payment' name=addsuppc><br>Add Bank Payment (to Customers)</a></td>
	<td valign=top align=center width='25%'><a href=bank-recpt-supp.php target=mainframe class=nav onMouseOver='imgSwop(\"adddepositsupp\", \"../images/bankcreditreceiptsh.gif\");' onMouseOut='imgSwop(\"adddepositsupp\", \"../images/bankcreditreceipt.gif\");'><img src='../images/bankcreditreceipt.gif'  border=0 alt='Add deposit' title='Add deposit' name=adddepositsupp><br>Add Bank Receipt (from Supplier)</a></td>
</tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
       	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table></center>";

require ("../template.php");
?>

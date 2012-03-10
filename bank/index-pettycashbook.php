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
<h3>Petty Cash Book</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='20%'><a href=../core/cash-link.php target=mainframe class=nav onMouseOver='imgSwop(\"spt\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"spt\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0 alt='Set Petty Cash Account' title='Set Petty Cash Account' name=spt><br>Set Petty Cash Account</a></td>
<td valign=top align=center width='20%'><a href=petty-trans.php target=mainframe class=nav onMouseOver='imgSwop(\"pt\", \"../images/transfermoneysh.gif\");' onMouseOut='imgSwop(\"pt\", \"../images/transfermoney.gif\");'><img src='../images/transfermoney.gif'  border=0 alt='Transfer Funds To Petty Cash Account' title='Transfer Funds To Petty Cash Account' name=pt><br>Transfer Funds To Petty Cash Account</a></td>
<td valign=top align=center width='20%'><a href=petty-bank.php target=mainframe class=nav onMouseOver='imgSwop(\"bpc\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"bpc\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0  name=bpc><br>Bank Petty Cash</a></td>
<td valign=top align=center width='20%'><a href=petty-req-add.php target=mainframe class=nav onMouseOver='imgSwop(\"pra\", \"../images/requisitionssh.gif\");' onMouseOut='imgSwop(\"pra\", \"../images/requisitions.gif\");'><img src='../images/requisitions.gif'  border=0 alt='Add Petty Cash Requisistion' title='Add Petty Cash Requisistion' name=pra><br>Add Petty Cash Requisistion</a></td>
<td valign=top align=center width='20%'><a href=petty-pay-cust.php target=mainframe class=nav onMouseOver='imgSwop(\"ppc\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"ppc\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0 name=ppc><br>Pay Customer</a></td>
</tr>
<tr>
<td valign=top align=center width='20%'><a href=petty-pay-supp.php target=mainframe class=nav onMouseOver='imgSwop(\"pps\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"pps\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0 name=pps><br>Pay Supplier</a></td>
<td valign=top align=center width='20%'><a href=petty-recpt-cust.php target=mainframe class=nav onMouseOver='imgSwop(\"prc\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"prc\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0 name=prc><br>Receive from Customer</a></td>
<td valign=top align=center width='20%'><a href=petty-recpt-supp.php target=mainframe class=nav onMouseOver='imgSwop(\"prs\", \"../images/pettyaccountsh.gif\");' onMouseOut='imgSwop(\"prs\", \"../images/pettyaccount.gif\");'><img src='../images/pettyaccount.gif'  border=0 name=prs><br>Receive from Supplier</a></td>
<td valign=top align=center width='20%'><a href=pettycashbook-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vpcb\", \"../images/vpettycashbooksh.gif\");' onMouseOut='imgSwop(\"vpcb\", \"../images/vpettycashbook.gif\");'><img src='../images/vpettycashbook.gif'  border=0 alt='Petty Cash Book' title='Petty Cash Requisistions' name=vpcb><br>View Petty Cash Requisistions</a></td>
<td valign=top align=center width='33%'><a href=pettycash-rep.php target=mainframe class=nav onMouseOver='imgSwop(\"reports\", \"../images/reportsh.gif\");' onMouseOut='imgSwop(\"reports\", \"../images/report.gif\");'><img src='../images/report.gif' border=0 alt='Petty Cash Report' title='Petty Cash Report' name=reports><br>Petty Cash Report</a></td>
</tr>

</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
</table></center>";

require ("../template.php");
?>

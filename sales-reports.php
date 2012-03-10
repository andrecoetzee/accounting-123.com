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

require ("settings.php");

$OUTPUT ="<br><center><h3>Sales Reports</h3>
<table width='90%'>
<tr>
<td valign=top align=center width='33%'><a href='invoice-disc-rep.php' target=mainframe class=nav onMouseOver='imgSwop(\"invdisc\", \"images/invoicediscountsh.gif\");' onMouseOut='imgSwop(\"invdisc\", \"images/invoicediscount.gif\");'><img src='images/invoicediscount.gif' border=0 alt='Invoice Discount' title='Invoice Discount' name=invdisc><br>Invoice Discount</a></td>
<td valign=top align=center width='33%'><a href='stock-sales-rep.php' target=mainframe class=nav onMouseOver='imgSwop(\"sales\", \"images/invoicediscountsh.gif\");' onMouseOut='imgSwop(\"sales\", \"images/invoicediscount.gif\");'><img src='images/invoicediscount.gif' border=0 alt='Stock Sales Report' title='Stock Sales Report' name=sales><br>Stock Sales Report</a></td>
<td valign=top align=center width='33%'><a href='coms-report.php' target=mainframe class=nav onMouseOver='imgSwop(\"invdisca\", \"images/invoicediscountsh.gif\");' onMouseOut='imgSwop(\"invdisca\", \"images/invoicediscount.gif\");'><img src='images/invoicediscount.gif' border=0 alt='Commision Report' title='Commision Report' name=invdisca><br>Sales Rep Commision Report</a></td>
</tr>
</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
</table></center>";

	require ("template.php");
?>

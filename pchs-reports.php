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

db_connect ();

$OUTPUT ="<br><center><h3>Purchases Reports</h3>
<table width='90%'>
<tr>
<td valign=top align=center width='25%'><a href='supp-tran-rep.php' target=mainframe class=nav onMouseOver='imgSwop(\"invdisc\", \"images/invoicediscountsh.gif\");' onMouseOut='imgSwop(\"invdisc\", \"images/invoicediscount.gif\");'><img src='images/invoicediscount.gif' border=0 alt='Supplier Transactions' title='Supplier Transactions' name=invdisc><br>Supplier Transactions</a></td>
</tr>
</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
</center>
</table>";

        require ("template.php");
?>

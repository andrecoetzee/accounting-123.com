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
	<td valign=top align=center width='25%'><a href='core/rectrans-new.php' target=mainframe class=nav onMouseOver='imgSwop(\"recuringnew\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"recuringnew\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='Add Recurring Transactions' title='Add Recurring Transactions' name=recuringnew><br>Add Recurring Transactions</a></td>
	<td valign=top align=center width='25%'><a href='core/rectrans-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"recuringview\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"recuringview\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='View Recurring Transactions' title='View Recurring Transactions' name=recuringview><br>View Recurring Transactions</a></td>
</tr>
</table>
<table border=0 cellpadding='2' cellspacing='1' width=15%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
</center>
</table>


";

require ("template.php");
?>

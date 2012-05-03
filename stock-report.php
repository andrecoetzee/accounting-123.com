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

	$OUTPUT = "<center><h3>Stock control reports</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='25%'><a href=stock-sales-rep-stk.php target=mainframe class=nav onMouseOver='imgSwop(\"stocksal\", \"images/availablestocksh.gif\");' onMouseOut='imgSwop(\"stocksal\", \"images/availablestock.gif\");'><img src='images/availablestock.gif' width=75 height=75 border=0 alt='Stock Sales' title='Stock Sales' name=stocksal><br>Stock Sales Report</a></td>
		<td valign=top align=center width='25%'><a href=stock-avail.php target=mainframe class=nav onMouseOver='imgSwop(\"stockavail\", \"images/availablestocksh.gif\");' onMouseOut='imgSwop(\"stockavail\", \"images/availablestock.gif\");'><img src='images/availablestock.gif' width=75 height=75 border=0 alt='Available Stock' title='Available Stock' name=stockavail><br>Available Stock</a></td>
		<td valign=top align=center width='25%'><a href=stock-lvl-rep.php target=mainframe class=nav onMouseOver='imgSwop(\"stocklvl\", \"images/availablestocksh.gif\");' onMouseOut='imgSwop(\"stocklvl\", \"images/availablestock.gif\");'><img src='images/availablestock.gif' width=75 height=75 border=0 alt='Stock Levels' title='Stock Levels' name=stocklvl><br>Stock Levels</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

	require ("template.php");
?>

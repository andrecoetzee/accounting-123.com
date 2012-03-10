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

	$OUTPUT = "<center><h3>Stock</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='33.33%'><a href=stock-add.php target=mainframe class=nav onMouseOver='imgSwop(\"stock\", \"images/addstocksh.gif\");' onMouseOut='imgSwop(\"stock\", \"images/addstock.gif\");'><img src='images/addstock.gif' border=0 alt='Add stock' title='Add stock' name=stock width=65 height=65><br>Add stock</a></td>
		<td valign=top align=center width='33.33%'><a href=stock-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewstockf\", \"images/viewstocksh.gif\");' onMouseOut='imgSwop(\"viewstockf\", \"images/viewstock.gif\");'><img src='images/viewstock.gif' border=0 alt='View stock' title='View stock' name=viewstockf width=65 height=65><br>View stock</a></td>
		<td valign=top align=center width='33.33%'><a href=stock-search.php target=mainframe class=nav onMouseOver='imgSwop(\"search\", \"images/viewstocksh.gif\");' onMouseOut='imgSwop(\"search\", \"images/viewstock.gif\");'><img src='images/viewstock.gif' border=0 alt='search stock' title='search stock' name=search width=65 height=65><br>Search stock</a></td>
	</tr>
	<tr>
		<td valign=top align=center width='33.33%'><a href=stock-transfer.php target=mainframe class=nav onMouseOver='imgSwop(\"trans\", \"images/warehousesh.gif\");' onMouseOut='imgSwop(\"trans\", \"images/warehouse.gif\");'><img src='images/warehouse.gif' border=0 alt='Stock Transfer (store)' title='Stock Transfer (store)' name=trans><br>Stock Transfer (store)</a></td>
		<td valign=top align=center width='33.33%'><a href=stock-transfer-bran.php target=mainframe class=nav onMouseOver='imgSwop(\"transbranch\", \"images/warehousesh.gif\");' onMouseOut='imgSwop(\"transbranch\", \"images/warehouse.gif\");'><img src='images/warehouse.gif' border=0 alt='Stock Transfer (branch)' title='Stock Transfer (branch)' name=transbranch><br>Stock Transfer (branch)</a></td>
		<td valign=top align=center width='33.33%'><a target='_blank' href=stock-taking.php target=mainframe class=nav onMouseOver='imgSwop(\"take\", \"images/stockreportsh.gif\");' onMouseOut='imgSwop(\"take\", \"images/stockreport.gif\");'><img src='images/stockreport.gif' border=0 alt='Stock Taking' title='Stock Taking' name=take width=65 height=65><br>Stock Taking</a></td>
	</tr>
	<tr>
		<td valign=top align=center width='33.33%'><a href=stock-transit-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewstocktransit\", \"images/viewstocksh.gif\");' onMouseOut='imgSwop(\"viewstocktransit\", \"images/viewstock.gif\");'><img src='images/viewstock.gif' border=0 alt='View stock in Transit' title='View stock in Transit' name=viewstocktransit width=65 height=65><br>View stock in Transit</a></td>
		<td valign=top align=center width='33.33%'><a href=stock-report.php target=mainframe class=nav onMouseOver='imgSwop(\"report\", \"images/stockreportsh.gif\");' onMouseOut='imgSwop(\"report\", \"images/stockreport.gif\");'><img src='images/stockreport.gif' border=0 alt='Stock Reports' title='Stock Reports' name=report width=65 height=65><br>Reports</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

	require ("template.php");
?>

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

$OUTPUT = "
<br>
<center>
<!--<img src='imgs/nd_logo.gif' width=141 height=56 border=0 alt='Cubit Accounting' title='Cubit Accounting'>-->
<br><br>

<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>

<tr>
<td valign=top align=center width='25%'><a href='reports-tokens-stats.php' target=mainframe class=nav onMouseOver='imgSwop(\"aasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"aasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Query Statistics' title='Query Statistics' name=aasettings><br>Outstanding Query Statistics</a></td>
<td valign=top align=center width='25%'><a href='reports-tokens-closed.php' target=mainframe class=nav onMouseOver='imgSwop(\"scaasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"scaasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Search Closed Query' title='Search Closed Query' name=scaasettings><br>Search Closed Queries</a></td>
<td valign=top align=center width='25%'><a href='reports-tokens-closed2.php' target=mainframe class=nav onMouseOver='imgSwop(\"caasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"caasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='List Closed Query' title='List Closed Query' name=caasettings><br>List Closed Queries</a></td>
</tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>
</center>";

require ("template.php");
?>
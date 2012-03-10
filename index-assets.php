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

##
 # index-assets.php :: assets index
##

require ("settings.php");

$OUTPUT = "
<br>
<center>
<h3>Asset Ledger</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
	<td valign=top align=center width='25%'><a href=asset-new.php target=mainframe class=nav onMouseOver='imgSwop(\"transactions\", \"images/addassetsh.gif\");' onMouseOut='imgSwop(\"transactions\", \"images/addasset.gif\");'><img src='images/addasset.gif' border=0 alt='New Asset' title='New' name=transactions><br>Add Asset</a></td>
	<td valign=top align=center width='25%'><a href=asset-view.php target=mainframe class=nav onMouseOver='imgSwop(\"mtransactions\", \"images/listsh.gif\");' onMouseOut='imgSwop(\"mtransactions\", \"images/list.gif\");'><img src='images/list.gif' border=0 alt='View Assets' title='View' name=mtransactions><br>View Assets</a></td>
	<td valign=top align=center width='25%'><a href=assetgrp-new.php target=mainframe class=nav onMouseOver='imgSwop(\"grp\", \"images/addassetsh.gif\");' onMouseOut='imgSwop(\"grp\", \"images/addasset.gif\");'><img src='images/addasset.gif' border=0 alt='New Asset Group' title='New' name=grp><br>Add Asset Group</a></td>
	<td valign=top align=center width='25%'><a href=assetgrp-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vgrp\", \"images/listsh.gif\");' onMouseOut='imgSwop(\"vgrp\", \"images/list.gif\");'><img src='images/list.gif' border=0 alt='View Asset Groups' title='View' name=vgrp><br>View Asset Groups</a></td>
</tr>
</table>
<table border=0 cellpadding='2' cellspacing='1' width=15%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</center>
</table>";

require ("template.php");
?>
<td valign=top align=center width='33%'><a href=asset-report.php target=mainframe class=nav onMouseOver='imgSwop(\"reports\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"reports\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Asset Reports' title='Reports' name=reports><br>Asset Reports</a></td>

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
<td valign=top align=center width='25%'><a href='team-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"aasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"aasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Add Team' title='Add Team' name=aasettings><br>Add Team</a></td>
<td valign=top align=center width='25%'><a href='team-list.php' target=mainframe class=nav onMouseOver='imgSwop(\"asettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"asettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='View Teams' title='View Teams' name=asettings><br>View Teams</a></td>
<td valign=top align=center width='25%'><a href='tcat-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"aaasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"aaasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Add Query Category' title='Add Query Category' name=aaasettings><br>Add Query Category</a></td>
<td valign=top align=center width='25%'><a href='tcat-list.php' target=mainframe class=nav onMouseOver='imgSwop(\"tasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"tasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='View Teams' title='View Teams' name=tasettings><br>View Query Categories</a></td>
</tr>
<tr>
<td valign=top align=center width='25%'><a href='action-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"qaaasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"qaaasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Add Action' title='Add Action' name=qaaasettings><br>Add Action</a></td>
<td valign=top align=center width='25%'><a href='action-list.php' target=mainframe class=nav onMouseOver='imgSwop(\"xtasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"xtasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='View Actions' title='View Actions' name=xtasettings><br>View Actions</a></td>
<td valign=top align=center width='25%'><a href='crms-allocate.php' target=mainframe class=nav onMouseOver='imgSwop(\"ctasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"ctasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Set default user teams' title='Set default user teams' name=ctasettings><br>Set default user teams</a></td>
<td valign=top align=center width='25%'><a href='crms-list.php' target=mainframe class=nav onMouseOver='imgSwop(\"cctasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"cctasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Select multiple teams for a user' title='Select multiple teams for a user' name=cctasettings><br>Select multiple teams for a user</a></td>
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

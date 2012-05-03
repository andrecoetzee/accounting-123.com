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
			<center>
			<h3>Maintenance Settings</h3>
			<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
				<tr>
					<td valign='top' align='center' width='33.33%'><a href=maint.php target=mainframe class=nav onMouseOver='imgSwop(\"maaasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"maaasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Maintenance' title='Maintenance' name=maaasettings><br>Maintenance</a></td>
					<td valign='top' align='center' width='33.33%'><a href=company-export.php target=mainframe class=nav onMouseOver='imgSwop(\"accountc\", \"images/defaultaccountsh.gif\");' onMouseOut='imgSwop(\"accountc\", \"images/defaultaccount.gif\");'><img src='images/defaultaccount.gif' border=0 alt='Backup' title='Backup' name=accountc><br>Backup</a></td>
					<td valign='top' align='center' width='33.33%'><a href=company-import.php target=mainframe class=nav onMouseOver='imgSwop(\"aaccountc\", \"images/defaultaccountsh.gif\");' onMouseOut='imgSwop(\"aaccountc\", \"images/defaultaccount.gif\");'><img src='images/defaultaccount.gif' border=0 alt='Restore Backup' title='Restore Backup' name=aaccountc><br>Restore Backup</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

        require ("template.php");
?>

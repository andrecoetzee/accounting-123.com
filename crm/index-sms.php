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
<td valign=top align=center width='25%'><a href='https_face.php?target=crm/msgpurch-loadstep.php?step=1' target=mainframe class=nav onMouseOver='imgSwop(\"aasettings\", \"images/requisitionssh.gif\");' onMouseOut='imgSwop(\"aasettings\", \"images/requisitions.gif\");'><img src='images/requisitions.gif' border=0 alt='Purchases SMS' title='Purchases SMS' name=aasettings><br>Purchases SMS</a></td>
<td valign=top align=center width='25%'><a href='https_face.php?target=crm/reports-loadstep.php?step=1' target=mainframe class=nav onMouseOver='imgSwop(\"asettings\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"asettings\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Reports' title='Reports' name=asettings><br>Reports</a></td>
<td valign=top align=center width='25%'><a href='https_face.php?target=crm/send-messages.php&type=general&step=1' target=mainframe class=nav onMouseOver='imgSwop(\"aaasettings\", \"images/addcustomersh.gif\");' onMouseOut='imgSwop(\"aaasettings\", \"images/addcustomer.gif\");'><img src='images/addcustomer.gif' border=0 alt='General SMS' title='General SMS' name=aaasettings><br>General SMS</a></td>
<td valign=top align=center width='25%'><a href='https_face.php?target=crm/send-messages.php&type=bulk&step=1' target=mainframe class=nav onMouseOver='imgSwop(\"asettingsd\", \"images/departmentsh.gif\");' onMouseOut='imgSwop(\"asettingsd\", \"images/department.gif\");'><img src='images/department.gif' border=0 alt='Bulk SMS' title='Bulk SMS' name=asettingsd><br>Bulk SMS</a></td>
</tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>
</center>";

require ("template.php");
?>
<td valign=top align=center width='25%'><a href='crm/birthday-messages.php' target=mainframe class=nav onMouseOver='imgSwop(\"caasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"caasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='Birthday Messages' title='Birthday Messages' name=caasettings><br>Birthday Messages</a></td>

<tr>
<td valign=top align=center width='25%'><a href='crm/.php' target=mainframe class=nav onMouseOver='imgSwop(\"asettingsd\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"asettingsd\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='' title='' name=asettingsd><br></a></td>
<td valign=top align=center width='25%'><a href='crm/.php' target=mainframe class=nav onMouseOver='imgSwop(\"asettingsf\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"asettingsf\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='' title='' name=asettingsf><br></a></td>
<td valign=top align=center width='25%'><a href='crm/.php' target=mainframe class=nav onMouseOver='imgSwop(\"maaasettings\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"maaasettings\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='' title='' name=maaasettings><br></a></td>
<td valign=top align=center width='25%'><a href='crm/.php' target=mainframe class=nav onMouseOver='imgSwop(\"splash\", \"images/settingsh.gif\");' onMouseOut='imgSwop(\"splash\", \"images/setting.gif\");'><img src='images/setting.gif' border=0 alt='' title='' name=splash><br></a></td>
</tr>

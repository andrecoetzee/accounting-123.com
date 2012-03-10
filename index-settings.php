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

$OUTPUT = "<center><h3>Admin Settings</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='25%'><a href=set.php target=mainframe class=nav onMouseOver='imgSwop(\"naccount\", \"images/setaccountsh.gif\");' onMouseOut='imgSwop(\"naccount\", \"images/setaccount.gif\");'><img src='images/setaccount.gif' border=0 alt='Set Account Creation' title='Set Account Creation' name=naccount><br>Set Account Creation</a></td>
<td valign=top align=center width='25%'><a href=defdep-slct.php target=mainframe class=nav onMouseOver='imgSwop(\"accountc\", \"images/defaultaccountsh.gif\");' onMouseOut='imgSwop(\"accountc\", \"images/defaultaccount.gif\");'><img src='images/defaultaccount.gif' border=0 alt='Set default Accounts' title='Set Default Accounts' name=accountc><br>Set Default Accounts</a></td>
<td valign=top align=center width='25%'><a href=core/finyearnames-new.php target=mainframe class=nav onMouseOver='imgSwop(\"sname\", \"images/yearnamessh.gif\");' onMouseOut='imgSwop(\"sname\", \"images/yearnames.gif\");'><img src='images/yearnames.gif' border=0 alt='Set financial year names' title='Set financial year names' name=sname><br>Set financial year names</a></td>
<td valign=top align=center width='25%'><a href=core/finyearnames-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vsname\", \"images/viewyearnamessh.gif\");' onMouseOut='imgSwop(\"vsname\", \"images/viewyearnames.gif\");'><img src='images/viewyearnames.gif' border=0 alt='View year names' title='View year names' name=vsname><br>View year names</a></td>
</tr>
<tr>
<td valign=top align=center width='25%'><a href=core/finyear-range.php target=mainframe class=nav onMouseOver='imgSwop(\"pname\", \"images/periodrangesh.gif\");' onMouseOut='imgSwop(\"pname\", \"images/periodrange.gif\");'><img src='images/periodrange.gif' width=75 height=75 border=0 alt='Set Periods' title='Set Periods' name=pname><br>Set Period Range</a></td>
<td valign=top align=center width='25%'><a href=core/yr-open.php target=mainframe class=nav onMouseOver='imgSwop(\"oyear\", \"images/openyearsh.gif\");' onMouseOut='imgSwop(\"oyear\", \"images/openyear.gif\");'><img src='images/openyear.gif' border=0 alt='Open year' title='Open year' name=oyear><br>Open year</a></td>
<td valign=top align=center width='25%'><a href=core/prd-close.php target=mainframe class=nav onMouseOver='imgSwop(\"cperiod\", \"images/closeperiodsh.gif\");' onMouseOut='imgSwop(\"cperiod\", \"images/closeperiod.gif\");'><img src='images/closeperiod.gif' border=0 alt='Close period' title='Close period' name=cperiod><br>Close period</a></td>
<td valign=top align=center width='25%'><a href=core/yr-close.php target=mainframe class=nav onMouseOver='imgSwop(\"cyear\", \"images/closeyearsh.gif\");' onMouseOut='imgSwop(\"cyear\", \"images/closeyear.gif\");'><img src='images/closeyear.gif' width=75 height=75 border=0 alt='Close year' title='Close year' name=cyear><br>Close year</a></td>
</tr>
<tr>
<td valign=top align=center width='25%'><a href=set-debt-age.php target=mainframe class=nav onMouseOver='imgSwop(\"setdebtage\", \"images/closeperiodsh.gif\");' onMouseOut='imgSwop(\"setdebtage\", \"images/closeperiod.gif\");'><img src='images/closeperiod.gif' border=0 alt='Set Debtors Age Analysis Period Type' title='Set Debtors Age Analysis Period Type' name=setdebtage><br>Set Debtors Age Analysis Period Type</a></td>
<td valign=top align=center width='25%'><a href=set-int-type.php target=mainframe class=nav onMouseOver='imgSwop(\"setinttype\", \"images/yearnamessh.gif\");' onMouseOut='imgSwop(\"setinttype\", \"images/yearnames.gif\");'><img src='images/yearnames.gif' border=0 alt='Set Interest Calculation Method' title='Set Interest Calculation Method' name=setinttype><br>Set Interest Calculation Method</a></td>
</tr>

</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='settings-index.php'>Settings</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        require ("template.php");
?>

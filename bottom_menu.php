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
<hr size=1 noshade>

<center>
<table border=0 cellpadding=0 cellspacing=0 width='100%'>
<tr><td align=center>
        <table border=0 cellpadding=0 cellspacing=0>
        <tr>
        <td><a href=main.php target=mainframe class=nav onMouseOver='imgSwop(\"mainmenu\", \"menu/imgs/mainmenu1.gif\");' onMouseOut='imgSwop(\"mainmenu\", \"menu/imgs/mainmenu2.gif\");'><img src='menu/imgs/mainmenu2.gif' width=101 height=34 border=0 alt='Main menu' name=mainmenu></a></td>
        <td><a href='javascript:history.back();' target=mainframe class=nav onMouseOver='imgSwop(\"back\", \"menu/imgs/back1.gif\");' onMouseOut='imgSwop(\"back\", \"menu/imgs/back2.gif\");'><img src='menu/imgs/back2.gif' width=53 height=34 border=0 alt='Back' name=back></a></td>
        <td><a href=index-accounts.php target=mainframe class=nav onMouseOver='imgSwop(\"accounting\", \"menu/imgs/accounting1.gif\");' onMouseOut='imgSwop(\"accounting\", \"menu/imgs/accounting2.gif\");'><img src='menu/imgs/accounting2.gif' width=89 height=34 border=0 alt='Accounting' name=accounting></a></td>
        <td><a href=index-admin.php target=mainframe class=nav onMouseOver='imgSwop(\"admin\", \"menu/imgs/admin1.gif\");' onMouseOut='imgSwop(\"admin\", \"menu/imgs/admin2.gif\");'><img src='menu/imgs/admin2.gif' width=66 height=34 border=0 alt='Admin' name=admin></a></td>
        <td><a href=index-pchs.php target=mainframe class=nav onMouseOver='imgSwop(\"purchase\", \"menu/imgs/purchase1.gif\");' onMouseOut='imgSwop(\"purchase\", \"menu/imgs/purchase2.gif\");'><img src='menu/imgs/purchase2.gif' width=90 height=34 border=0 alt='Purchases' name=purchase></a></td>
        <td><a href=index-sales.php target=mainframe class=nav onMouseOver='imgSwop(\"sales\", \"menu/imgs/sales1.gif\");' onMouseOut='imgSwop(\"sales\", \"menu/imgs/sales2.gif\");'><img src='menu/imgs/sales2.gif' width=63 height=34 border=0 alt='Sales' name=sales></a></td>
		<td><a href=index-stock.php target=mainframe class=nav onMouseOver='imgSwop(\"stock\", \"menu/imgs/stock1.gif\");' onMouseOut='imgSwop(\"stock\", \"menu/imgs/stock2.gif\");'><img src='menu/imgs/stock2.gif' width=62 height=34 border=0 alt='Stock' name=stock></a></td>
		<td><a href=index-salaries.php target=mainframe class=nav onMouseOver='imgSwop(\"salwag\", \"menu/imgs/salwag1.gif\");' onMouseOut='imgSwop(\"salwag\", \"menu/imgs/salwag2.gif\");'><img src='menu/imgs/salwag2.gif' width=75 height=34 border=0 alt='Salaries & Wages' name=salwag></a></td>
        <td><a href=bank/index-bankaccnt.php target=mainframe class=nav onMouseOver='imgSwop(\"bank\", \"menu/imgs/bank1.gif\");' onMouseOut='imgSwop(\"bank\", \"menu/imgs/bank2.gif\");'><img src='menu/imgs/bank2.gif' width=81 height=34 border=0 alt='Banking' name=bank></a></td>
        <td><a href=license.html target=mainframe class=nav onMouseOver='imgSwop(\"license\", \"menu/imgs/license1.gif\");' onMouseOut='imgSwop(\"license\", \"menu/imgs/license2.gif\");'><img src='menu/imgs/license2.gif' width=70 height=34 border=0 alt='License' name=license></a></td>
        <td><a href=logout.php target=_parent class=nav onMouseOver='imgSwop(\"log\", \"menu/imgs/log1.gif\");' onMouseOut='imgSwop(\"log\", \"menu/imgs/log2.gif\");'><img src='menu/imgs/log2.gif' width=75 height=34 border=0 alt='Log out' name=log></a></td>
        </table>
</td>
<td align=center rowspan=1>
	<table border=1 cellpadding=4 cellspacing=0>
	<tr><th>Logged in<br>as ".USER_NAME."</th></tr>
	</table>
</td></tr>
</table>
</center>
";

require ("template.php");
?>

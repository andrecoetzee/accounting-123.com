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

require ("../settings.php");

Db_Connect ();

	$OUTPUT = "<center><h3>Imports</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
	<td valign=top align=center width='25%'><a href=import-stock.php class=nav onMouseOver='imgSwop(\"ac\", \"../images/settingsh.gif\");' onMouseOut='imgSwop(\"ac\", \"../images/setting.gif\");'><img src='../images/setting.gif' border=0 alt='Import Stock' title='Import Stock' name=ac><br>Import Stock</a></td>
       	<td valign=top align=center width='25%'><a href=import-customers.php class=nav onMouseOver='imgSwop(\"1ac\", \"../images/settingsh.gif\");' onMouseOut='imgSwop(\"1ac\", \"../images/setting.gif\");'><img src='../images/setting.gif' border=0 alt='Import Customers' title='Import Customers' name=1ac><br>Import Customers</a></td>
       	<td valign=top align=center width='25%'><a href=import-suppliers.php class=nav onMouseOver='imgSwop(\"ac2\", \"../images/settingsh.gif\");' onMouseOut='imgSwop(\"ac2\", \"../images/setting.gif\");'><img src='../images/setting.gif' border=0 alt='Import Suppliers' title='Import Suppliers' name=ac2><br>Import Suppliers</a></td>
	<td valign=top align=center width='25%'><a href=import-tb.php class=nav onMouseOver='imgSwop(\"ac2\", \"../images/settingsh.gif\");' onMouseOut='imgSwop(\"ac2\", \"../images/setting.gif\");'><img src='../images/setting.gif' border=0 alt='Import Trial Balance' title='Import Trial Balance' name=ac2><br>Import Trial Balance</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	</center>";

        require ("../template.php");
?>

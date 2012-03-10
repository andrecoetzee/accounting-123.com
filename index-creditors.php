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

$OUTPUT = "<center><h3>Creditors</h3><br>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='33.33%'>
<a href='supp-new.php' target=mainframe class=nav onMouseOver='imgSwop(\"addsupp\", \"images/addsuppliersh.gif\");' onMouseOut='imgSwop(\"addsupp\", \"images/addsupplier.gif\");'><img src='images/addsupplier.gif' border=0 alt='Add Supplier' title='Add Supplier' name=addsupp><br>Add Supplier</a></td>
<td valign=top align=center width='33.33%'><a href='supp-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"viewsupp\", \"images/viewsuppliersh.gif\");' onMouseOut='imgSwop(\"viewsupp\", \"images/viewsupplier.gif\");'><img src='images/viewsupplier.gif' border=0 alt='View Suppliers' title='View Suppliers' name=viewsupp><br>View Suppliers</a></td>
<td valign=top align=center width='33.33%'><a href='supp-find.php' target=mainframe class=nav onMouseOver='imgSwop(\"fviewsupp\", \"images/viewsuppliersh.gif\");' onMouseOut='imgSwop(\"fviewsupp\", \"images/viewsupplier.gif\");'><img src='images/viewsupplier.gif' border=0 alt='Find Supplier' title='Find Supplier' name=fviewsupp><br>Find Supplier</a></td>
<td valign=top align=center width='33.33%'><a href='supp-group-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"addsuppgrp\", \"images/categorysh.gif\");' onMouseOut='imgSwop(\"addsuppgrp\", \"images/category.gif\");'><img src='images/category.gif' border=0 alt='Add Supplier Group' title='Add Supplier Group' name=addsuppgrp><br>Add Supplier Group</a></td>
<tr>
<td valign=top align=center width='33.33%'><a href='supp-group-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"suppgrpview\", \"images/categorysh.gif\");' onMouseOut='imgSwop(\"suppgrpview\", \"images/category.gif\");'><img src='images/category.gif' border=0 alt='View Supplier Groups' title='View Supplier Groups' name=suppgrpview><br>View Supplier Groups</a></td>
</tr>
</table>

<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
</table></center>";

	require ("template.php");
?>

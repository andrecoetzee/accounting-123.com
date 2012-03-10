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

db_connect ();

$OUTPUT =
"
<br>
<center>
<h3>Sales Settings</h3>
<table width='90%'>
<tr>
	<td valign=top align=center width='25%'><a href='toms/dept-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"adept\", \"images/departmentsh.gif\");' onMouseOut='imgSwop(\"adept\", \"images/department.gif\");'><img src='images/department.gif' border=0 alt='Add Department' title='Add Department' name=adept><br>Add Department</a></td>
	<td valign=top align=center width='25%'><a href='toms/dept-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vdept\", \"images/viewdepartmentsh.gif\");' onMouseOut='imgSwop(\"vdept\", \"images/viewdepartment.gif\");'><img src='images/viewdepartment.gif' border=0 alt='View Department' title='View Department' name=vdept><br>View Department</a></td>
	<td valign=top align=center width='25%'><a href='toms/salesp-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"asalesp\", \"images/salespersonsh.gif\");' onMouseOut='imgSwop(\"asalesp\", \"images/salesperson.gif\");'><img src='images/salesperson.gif' border=0 alt='Add Sales Person' title='Add Sales Person' name=asalesp><br>Add Sales Person</a></td>
	<td valign=top align=center width='25%'><a href='toms/salesp-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vsalesp\", \"images/viewsalespersonsh.gif\");' onMouseOut='imgSwop(\"vsalesp\", \"images/viewsalesperson.gif\");'><img src='images/viewsalesperson.gif' border=0 alt='View Sales Person' title='View Sales Person' name=vsalesp><br>View Sales People</a></td>
</tr>
<tr>
	<td valign=top align=center width='25%'><a href='toms/cat-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"acat\", \"images/categorysh.gif\");' onMouseOut='imgSwop(\"acat\", \"images/category.gif\");'><img src='images/category.gif' border=0 alt='Add Category' title='Add Category' name=acat><br>Add Category</a></td>
	<td valign=top align=center width='25%'><a href='toms/cat-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vcat\", \"images/viewcategorysh.gif\");' onMouseOut='imgSwop(\"vcat\", \"images/viewcategory.gif\");'><img src='images/viewcategory.gif' border=0 alt='View Category' title='View Category' name=vcat><br>View Category</a></td>
	<td valign=top align=center width='25%'><a href='toms/class-add.php' target=mainframe class=nav onMouseOver='imgSwop(\"aclass\", \"images/classificationsh.gif\");' onMouseOut='imgSwop(\"aclass\", \"images/classification.gif\");'><img src='images/classification.gif' border=0 alt='Add Classification' title='Add Classification' name=aclass><br>Add Classification</a></td>
	<td valign=top align=center width='25%'><a href='toms/class-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vclass\", \"images/viewclassificationsh.gif\");' onMouseOut='imgSwop(\"vclass\", \"images/viewclassification.gif\");'><img src='images/viewclassification.gif' border=0 alt='View Classification' title='View Classification' name=vclass><br>View Classification</a></td>
</tr>
<tr>
	<td valign=top align=center width='25%'><a href='core/sales-link.php?type=B&payname=VAT' target=mainframe class=nav onMouseOver='imgSwop(\"vatacc\", \"images/vatsh.gif\");' onMouseOut='imgSwop(\"vatacc\", \"images/vat.gif\");'><img src='images/vat.gif' width=80 height=67 border=0 alt='Set VAT Account' title='Set VAT Account' name=vatacc><br>Set VAT Account</a></td>
	<td valign=top align=center width='25%'><a href='core/sales-link.php?type=E&payname=sales_variance' target=mainframe class=nav onMouseOver='imgSwop(\"svatacc\", \"images/vatsh.gif\");' onMouseOut='imgSwop(\"svatacc\", \"images/vat.gif\");'><img src='images/vat.gif' width=80 height=67 border=0 alt='Set variance account' title='Set variance account' name=svatacc><br>Set Variance account</a></td>
	<td valign=top align=center width='25%'><a href='toms/invid-set.php' target=mainframe class=nav onMouseOver='imgSwop(\"setinvid\", \"images/setinvoicenosh.gif\");' onMouseOut='imgSwop(\"setinvid\", \"images/setinvoiceno.gif\");'><img src='images/setinvoiceno.gif' border=0 alt='Set Invoice No' title='Set Invoice No' name=setinvid><br>Set Invoice No</a></td>
	<td valign=top align=center width='25%'><a href='coms-edit.php' target=mainframe class=nav onMouseOver='imgSwop(\"vsalespc\", \"images/viewsalespersonsh.gif\");' onMouseOut='imgSwop(\"vsalespc\", \"images/viewsalesperson.gif\");'><img src='images/viewsalesperson.gif' border=0 alt='View Sales Person' title='View Sales Person' name=vsalespc><br>Set Sales Rep Commision</a></td>
</tr>
</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
</center>
</table>";

        require ("template.php");
?>

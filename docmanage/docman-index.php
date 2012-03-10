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
require ("../core-settings.php");
if(isset($HTTP_POST_VARS["key"])){
	switch($HTTP_POST_VARS["key"]){
		case "view":
			$OUTPUT=printCat($HTTP_POST_VARS);
		break;
	default:
			$OUTPUT="iNVALID";
	}
}

$OUTPUT = "<center>
<table border=0 width='90%'><tr>
<td valign=top width='33%'><table width='90%'>
<tr><td align=center><h3>Document Management</h3></td></tr>
<tr ><th colspan=2>Document Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href ='tdocadd.php' class=nav><b> Add Document</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href ='tdocview.php' class=nav><b> View Documents</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href='doctypeadd.php' class=nav><b>Add Document Type</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href ='doctypeview.php' class=nav><b>View Document Type</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href='foladd.php' class=nav><b>Add New Folder</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href='usradd.php' class=nav><b> User Management</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td nowrap><a href='grpadd.php' class=nav><b> Group Management</b></a></td></tr>
</table></td>

<td valign=top width='33%'>
<table border=0 width='90%'>
<tr><td align=center nowrap><h3>Document Data</h3></td></tr>
<tr><th>Type</th><th>Ref</th><th>Document</th><th>Date</th><th>Description</th><th>Filename</th></tr>

</table></td>

<td valign=top width='33%'>
<table width='50%'>
<tr><td align=center nowrap><h3>Document Action</h3></td></tr>
<tr><th>Actions</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='view.php'>VIEW</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='email.php'>E-MAIL</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='checkout.php'>CHECKOUT</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tlist-docview.php'>DELETE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='history.php'>HISTORY</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='move.php'>MOVE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a  href='subscribe.php'>SUBSCRIBE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='discussion.php'>DISCUSSION</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='archive.php'>ARCHIVE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='link.php'>LINK</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='publish.php'>PUBLISH</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>

</table></td>
";

require ("../template.php");
?>

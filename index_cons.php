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

<br><br>

<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>

<tr>
<td valign=top align=center width='33.33%'><a href=new_con.php class=nav onMouseOver='imgSwop(\"new\", \"images/newcontactsh.gif\");' onMouseOut='imgSwop(\"new\", \"images/newcontact.gif\");'><img src='images/newcontact.gif'  border=0 alt='New contact' title='Add' name=new><br>New Contact</a></td>
<td valign=top align=center width='33.33%'><a href=list_cons.php class=nav onMouseOver='imgSwop(\"view\", \"images/listcontactsh.gif\");' onMouseOut='imgSwop(\"view\", \"images/listcontact.gif\");'><img src='images/listcontact.gif'  border=0 alt='List contacts' title='View' name=view><br>List Contacts</a></td>
<td valign=top align=center width='33.33%'><a href=find_con.php class=nav onMouseOver='imgSwop(\"find\", \"images/searchcontactsh.gif\");' onMouseOut='imgSwop(\"find\", \"images/searchcontact.gif\");'><img src='images/searchcontact.gif'  border=0 alt='Search contacts' title='Find' name=find ><br>Search Contacts</a></td>
</tr>


</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
</center>
";

require ("template.php");
?>

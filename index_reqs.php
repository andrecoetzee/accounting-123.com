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


  # <td valign=top align=center width='20%'><a href=req_leave.php class=nav onMouseOver='imgSwop(\"mod\", \"imgs/modcon1.gif\");' onMouseOut='imgSwop(\"mod\", \"imgs/modcon2.gif\");'><img src='imgs/modcon2.gif'  border=0 alt='Modify contact' title='Modify contact' name=mod><br>Modify contact</a></td>


require ("settings.php");

$OUTPUT = "
<br>
<center>


<br><br>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='50%'><a href=req_gen.php class=nav onMouseOver='imgSwop(\"rem\", \"imgs/messagesh.gif\");' onMouseOut='imgSwop(\"rem\", \"imgs/message.gif\");'><img src='imgs/message.gif'  border=0 alt='Leave Message' title='Leave Message' name=rem ><br>Leave Message</a></td>
<td valign=top align=center width='50%'><a href=view_req.php class=nav onMouseOver='imgSwop(\"req\", \"images/viewmessagesh.gif\");' onMouseOut='imgSwop(\"req\", \"images/viewmessage.gif\");'><img src='images/viewmessage.gif'  border=0 alt='View Messages' title='View Messages' name=req ><br>View Messages</a></td>

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

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

header("Location: diary/diary-index.php");
exit;

require ("settings.php");

$OUTPUT = "
<br>
<center>
 <br><br>

<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>

<tr>
<td valign=top align=center width='33.33'><a href=diary.php class=nav onMouseOver='imgSwop(\"find\", \"images/viewdiarysh.gif\");' onMouseOut='imgSwop(\"find\", \"images/viewdiary.gif\");'><img src='images/viewdiary.gif'  border=0 alt='View Own Diary' title='View Own Diary' name=find ><br>Own Diary</a></td>
<td valign=top align=center width='33.33'><a href=glodiary.php class=nav onMouseOver='imgSwop(\"finds\", \"images/viewdiariessh.gif\");' onMouseOut='imgSwop(\"finds\", \"images/viewdiaries.gif\");'><img src='images/viewdiaries.gif'  border=0 alt='Global Diary' title='Global Diary' name=finds ><br>Global Diary</a></td>
<td valign=top align=center width='33.33'><a href=todo.php class=nav onMouseOver='imgSwop(\"new\", \"images/singelappointmentsh.gif\");' onMouseOut='imgSwop(\"new\", \"images/singelappointment.gif\");'><img src='images/singelappointment.gif'  border=0 alt='TO DO LIST' title='TO DO LIST' name=new ><br>TO DO LIST</a></td>

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

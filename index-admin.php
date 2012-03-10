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

$OUTPUT = "<p>
<center>
<h3>Administration</h3>

<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
        <td valign=top align=center width='33.33%'><a href=admin-usradd.php target=mainframe class=nav onMouseOver='imgSwop(\"adduser\", \"images/addusersh.gif\");' onMouseOut='imgSwop(\"adduser\", \"images/adduser.gif\");'><img src='images/adduser.gif' border=0 alt='Add user' title='Add user' name=adduser><br>Add user</a></td>
        <td valign=top align=center width='33.33%'><a href=admin-usrview.php target=mainframe class=nav onMouseOver='imgSwop(\"viewuser\", \"images/viewusersh.gif\");' onMouseOut='imgSwop(\"viewuser\", \"images/viewuser.gif\");'><img src='images/viewuser.gif' border=0 alt='View user' title='View user' name=viewuser><br>View user</a></td>
        <td valign=top align=center width='33.33%'><a href=compinfo-view.php target=mainframe class=nav onMouseOver='imgSwop(\"compinfo\", \"images/companyinfosh.gif\");' onMouseOut='imgSwop(\"compinfo\", \"images/companyinfo.gif\");'><img src='images/companyinfo.gif' border=0 alt='Company Details' title='Company Details' name=compinfo><br>Company Details</a></td>
</tr>
<tr>
        <td valign=top align=center width='33.33%'><a href=admin-deptadd.php target=mainframe class=nav onMouseOver='imgSwop(\"depadd\", \"images/userdepartmentsh.gif\");' onMouseOut='imgSwop(\"depadd\", \"images/userdepartment.gif\");'><img src='images/userdepartment.gif' border=0 alt='' title='' name=depadd><br>Add User Department</a></td>
        <td valign=top align=center width='33.33%'><a href=admin-deptview.php target=mainframe class=nav onMouseOver='imgSwop(\"depview\", \"images/viewuserdepartmentsh.gif\");' onMouseOut='imgSwop(\"depview\", \"images/viewuserdepartment.gif\");'><img src='images/viewuserdepartment.gif' border=0 alt='' title='' name=depview><br>View User Department</a></td>
    	<td valign=top align=center width='33%'><a href=index-audit.php target=mainframe class=nav onMouseOver='imgSwop(\"Audit\", \"images/multibatchsh.gif\");' onMouseOut='imgSwop(\"Audit\", \"images/multibatch.gif\");'><img src='images/multibatch.gif' border=0 alt='Audit' title='Audit' name=Audit><br>Audit</a></td>
</tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
</center>
";
        require ("template.php");
?>

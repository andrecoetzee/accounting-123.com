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

##
# index-salaries.php :: Salaries & wages index & menu
##

require ("settings.php");

db_connect ();

$OUTPUT =
"<center><h3>Salaries</h3>
<br>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='25%'><a href=salwages/salaries-staff.php target=mainframe class=nav onMouseOver='imgSwop(\"staffsalaries\", \"images/staffsalariessh.gif\");' onMouseOut='imgSwop(\"staffsalaries\", \"images/staffsalaries.gif\");'><img src='images/staffsalaries.gif' border=0 alt='Staff salaries' title='Staff salaries' name=staffsalaries><br>Process Salaries per Individual</a></td>
<td valign=top align=center width='25%'><a href=salwages/payslips.php target=mainframe class=nav onMouseOver='imgSwop(\"viewsal\", \"images/viewstaffsalariessh.gif\");' onMouseOut='imgSwop(\"viewsal\", \"images/viewstaffsalaries.gif\");'><img src='images/viewstaffsalaries.gif' border=0 alt='' title='' name=viewsal ><br>View Salaries paid by month</a></td>
<td valign=top align=center width='25%'><a href=salwages/payslip.php target=mainframe class=nav onMouseOver='imgSwop(\"vsal\", \"images/employeesalsh.gif\");' onMouseOut='imgSwop(\"vsal\", \"images/employeesal.gif\");'><img src='images/employeesal.gif' border=0 alt='' title='' name=vsal ><br>View Employee Salary</a></td>
<td valign=top align=center width='25%'><a href=salwages/employee-resources.php target=mainframe class=nav onMouseOver='imgSwop(\"employeeresources\", \"images/resoucessh.gif\");' onMouseOut='imgSwop(\"employeeresources\", \"images/resouces.gif\");'><img src='images/resouces.gif' border=0 alt='Employee resources' title='Employ resources' name=employeeresources ><br>Employee resources</a></td>

</tr>
<tr>
<td valign=top align=center width='25%'><a href=admin-employee-add.php target=mainframe class=nav onMouseOver='imgSwop(\"addemployee\", \"images/addstaffsh.gif\");' onMouseOut='imgSwop(\"addemployee\", \"images/addstaff.gif\");'><img src='images/addstaff.gif' border=0 alt='Add Employee' title='Add Employee' name=addemployee ><br>Add Employee</a></td>
<td valign=top align=center width='25%'><a href=admin-employee-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewemployee\", \"images/viewstaffsh.gif\");' onMouseOut='imgSwop(\"viewemployee\", \"images/viewstaff.gif\");'><img src='images/viewstaff.gif' border=0 alt='View Employee' title='View Employee' name=viewemployee ><br>View Employees/Process Salaries per Batch</a></td>
<!--<td valign=top align=center width='25%'><a href=salwages/sal-settings.php target=mainframe class=nav onMouseOver='imgSwop(\"settings\", \"images/salarysettingssh.gif\");' onMouseOut='imgSwop(\"settings\", \"images/salarysettings.gif\");'><img src='images/salarysettings.gif' border=0 alt='Settings' title='Settings' name=settings><br>Settings</a></td>-->
<td valign=top align=center width='25%'><a href=admin-lemployee-view.php target=mainframe class=nav onMouseOver='imgSwop(\"aviewemployee\", \"images/viewstaffsh.gif\");' onMouseOut='imgSwop(\"aviewemployee\", \"images/viewstaff.gif\");'><img src='images/viewstaff.gif' border=0 alt='View Past Employee' title='View Past Employee' name=aviewemployee ><br>View Past Employees</a></td>


</tr></table>









 <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        require ("template.php");
?>

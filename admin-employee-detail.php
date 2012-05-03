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
#
# admin-employee-detail.php :: View employee detail
##

require ("settings.php");
require ("libs/ext.lib.php");

if ($_GET) {
	# confirm removal
	$OUTPUT = viewEmp ($_GET['empnum']);
} else {
	$OUTPUT = "Invalid option.";
}

require ("template.php");

##
# Functions
##

# view employee details
function viewEmp ($empnum)
{
	if (empty ($empnum)) {
		return "Employee number missing.$empnum";
	}
	$empnum = preg_replace ("/[^\w\s-]/", "", substr ($empnum, 0, 20));

	# connect to db
	db_connect ();

	# get employee info to edit
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid clock number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	if($myEmpl['resident']=="t") {$myEmpl['resident']="Yes";} else {$myEmpl['resident']="No";}
	if($myEmpl['sex']=="M") {$myEmpl['sex']="Male";} else {$myEmpl['sex']="Female";}
	# Set up table & form

	//$logoimage = "<br><img src='salwages/employee-view-image.php?id=$myEmpl[empnum]' width=230 height=47><br><br>";
	//$image="employee-view-image.php?id=$myEmpl[empnum]";
	//print $logoimage;

	db_conn('cubit');
	$Sl="SELECT * FROM eimgs WHERE emp='$myEmpl[empnum]'";
	$Ry=db_exec($Sl) or errDie("Unable to get emp image.");

	if(pg_numrows($Ry)>0) {
		$img="<img src='employee-view-image.php?id=$myEmpl[empnum]' width=300 height=300>";
	} else {
		$img="To add a photo for this employee, use '<a href='admin-employee-edit.php?empnum=$myEmpl[empnum]'>Edit Employee</a>'";
	}


	$viewEmp =
	"
	<h3>Employee Details</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td valign=top><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2>Employee Details</th></tr>
	<tr class='bg-odd'><td>Emp Num</td><td>$myEmpl[enum]</td></tr>
	<tr class='bg-even'><td>Surname</td><td valign=center>$myEmpl[sname]</td></tr>
	<tr class='bg-odd'><td>First Names</td><td valign=center>$myEmpl[fnames]</td></tr>
	<tr class='bg-even'><td>Sex</td><td valign=center>$myEmpl[sex]</td></tr>
	<tr class='bg-odd'><td>Race</td><td>$myEmpl[race]</td></tr>
	<tr class='bg-even'><td>Disabled Status</td><td>$myEmpl[disabled_stat]</td></tr>
	<tr class='bg-odd'><td>Marital Status</td><td valign=center>$myEmpl[marital]</td></tr>
	<tr class='bg-even'><td>Resident</td><td valign=center>$myEmpl[resident]</td></tr>
	<tr class='bg-odd'><td>Nature</td><td valign=center>$myEmpl[nature]</td></tr>
	<tr class='bg-even'><td>Company/CC/Trust number</td><td valign=center>$myEmpl[cc_number]</td></tr>
	<tr class='bg-odd'><td>Income Tax number</td><td valign=center>$myEmpl[tax_number]</td></tr>
	<tr class='bg-odd'><td>Hire Date</td><td valign=center>$myEmpl[hiredate]</td></tr>
	<tr class='bg-even'><td>Telephone No</td><td valign=center>$myEmpl[telno]</td></tr>
	<tr class='bg-odd'><td>E-mail</td><td valign=center>$myEmpl[email]</td></tr>
	<tr class='bg-even'><td>Basic Salary</td><td valign=center>".CUR." $myEmpl[basic_sal]</td></tr>
	<tr class='bg-odd'><td>Pay Type</td><td valign=center>$myEmpl[paytype]</td></tr>
	<tr class='bg-even'><td>Bank Name</td><td valign=center>$myEmpl[bankname]</td></tr>
	<tr class='bg-odd'><td>Branch Code</td><td valign=center>$myEmpl[bankcode]</td></tr>
	<tr class='bg-even'><td>Bank Account Type</td><td valign=center>$myEmpl[bankacctype]</td></tr>
	<tr class='bg-odd'><td>Bank Account No</td><td valign=center>$myEmpl[bankaccno]</td></tr>
	</table></td>
	<td valign=top><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th colspan=2>Employee Details</th></tr>
	<tr class='bg-odd'><td>Designation (Job Title)</td><td>$myEmpl[designation]</td></tr>
	<tr class='bg-even'><td>Department</td><td>$myEmpl[department]</td></tr>
	<tr class='bg-odd'><td>Occupational Category</td><td>$myEmpl[occ_cat]</td></tr>
	<tr class='bg-even'><td>Occupational Level</td><td>$myEmpl[occ_level]</td></tr>
	<tr class='bg-odd'><td>This Position Filled</td><td>$myEmpl[pos_filled]</td></tr>
	<tr class='bg-even'><td>Temporary (Employee or Contract)</td><td>$myEmpl[temporary]</td></tr>
	<tr class='bg-odd'><td>If Temporary: Termination Date</td><td>$myEmpl[termination_date]</td></tr>
	<tr class='bg-even'><td>Recruitment From</td><td>$myEmpl[recruitment_from]</td></tr>
	<tr class='bg-odd'><td>Reason for Employment</td><td>$myEmpl[employment_reason]</td></tr>
	<tr class='bg-even'><td>Union Name</td><td>$myEmpl[union_name]</td></tr>
	<tr class='bg-odd'><td>Union Membership Number</td><td>$myEmpl[union_mem_num]</td></tr>
	<tr class='bg-even'><td>Union Position</td><td>$myEmpl[union_pos]</td></tr>
	<tr class='bg-odd'><td>ID Num</td><td>$myEmpl[idnum]</td></tr>
	<tr class='bg-even'><td>Passport Num</td><td>$myEmpl[passportnum]</td></tr>
	<tr class='bg-odd'><td>Income Tax Ref No.</td><td>$myEmpl[taxref]</td></tr>
	<tr class='bg-even'><td>Residential Address</td><td valign=center>$myEmpl[res1]</td></tr>
	<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[res2]</td></tr>
	<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[res3]</td></tr>
	<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[res4]</td></tr>
	<tr class='bg-even'><td>Postal Address</td><td valign=center>$myEmpl[pos1]</td></tr>
	<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[pos2]</td></tr>
	<tr class='bg-even'><td>Postal Code</td><td valign=center>$myEmpl[pcode]</td></tr>
	<tr><th colspan=2>Friend Not Living With Employee</th></tr>
	<tr class='bg-odd'><td>Surname</td><td valign=center>$myEmpl[contsname]</td></tr>
	<tr class='bg-even'><td>First Names</td><td valign=center>$myEmpl[contfnames]</td></tr>
	<tr class='bg-odd'><td>Residential Address</td><td valign=center>$myEmpl[contres1]</td></tr>
	<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[contres2]</td></tr>
	<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[contres3]</td></tr>
	<tr class='bg-even'><td>Telephone No</td><td valign=center>$myEmpl[conttelno]</td></tr>
	</table></td>
	<td valign=top><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Employee Photo</th></tr>
	<tr><td>$img</td></tr>
	</table>
	</td>
	</tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $viewEmp;
}
?>

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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Emp Num</td><td>$myEmpl[enum]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Surname</td><td valign=center>$myEmpl[sname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>First Names</td><td valign=center>$myEmpl[fnames]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Sex</td><td valign=center>$myEmpl[sex]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Race</td><td>$myEmpl[race]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Disabled Status</td><td>$myEmpl[disabled_stat]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Marital Status</td><td valign=center>$myEmpl[marital]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Resident</td><td valign=center>$myEmpl[resident]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Nature</td><td valign=center>$myEmpl[nature]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Company/CC/Trust number</td><td valign=center>$myEmpl[cc_number]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Income Tax number</td><td valign=center>$myEmpl[tax_number]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Hire Date</td><td valign=center>$myEmpl[hiredate]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No</td><td valign=center>$myEmpl[telno]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>E-mail</td><td valign=center>$myEmpl[email]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Basic Salary</td><td valign=center>".CUR." $myEmpl[basic_sal]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Pay Type</td><td valign=center>$myEmpl[paytype]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Name</td><td valign=center>$myEmpl[bankname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch Code</td><td valign=center>$myEmpl[bankcode]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account Type</td><td valign=center>$myEmpl[bankacctype]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank Account No</td><td valign=center>$myEmpl[bankaccno]</td></tr>
	</table></td>
	<td valign=top><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th colspan=2>Employee Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Designation (Job Title)</td><td>$myEmpl[designation]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$myEmpl[department]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Occupational Category</td><td>$myEmpl[occ_cat]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Occupational Level</td><td>$myEmpl[occ_level]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>This Position Filled</td><td>$myEmpl[pos_filled]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Temporary (Employee or Contract)</td><td>$myEmpl[temporary]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>If Temporary: Termination Date</td><td>$myEmpl[termination_date]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Recruitment From</td><td>$myEmpl[recruitment_from]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Reason for Employment</td><td>$myEmpl[employment_reason]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Union Name</td><td>$myEmpl[union_name]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Union Membership Number</td><td>$myEmpl[union_mem_num]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Union Position</td><td>$myEmpl[union_pos]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>ID Num</td><td>$myEmpl[idnum]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Passport Num</td><td>$myEmpl[passportnum]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Income Tax Ref No.</td><td>$myEmpl[taxref]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Residential Address</td><td valign=center>$myEmpl[res1]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[res2]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[res3]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[res4]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Postal Address</td><td valign=center>$myEmpl[pos1]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[pos2]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Postal Code</td><td valign=center>$myEmpl[pcode]</td></tr>
	<tr><th colspan=2>Friend Not Living With Employee</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Surname</td><td valign=center>$myEmpl[contsname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>First Names</td><td valign=center>$myEmpl[contfnames]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Residential Address</td><td valign=center>$myEmpl[contres1]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[contres2]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[contres3]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No</td><td valign=center>$myEmpl[conttelno]</td></tr>
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

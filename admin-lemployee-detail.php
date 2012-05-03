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
	$sql = "SELECT * FROM lemployees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid clock number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	if($myEmpl['resident']=="t") {$myEmpl['resident']="Yes";} else {$myEmpl['resident']="No";}
	if($myEmpl['sex']=="M") {$myEmpl['sex']="Male";} else {$myEmpl['sex']="Female";}
	# Set up table & form
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
	<tr class='bg-odd'><td>Marital Status</td><td valign=center>$myEmpl[marital]</td></tr>
	<tr class='bg-even'><td>Resident</td><td valign=center>$myEmpl[resident]</td></tr>
	<tr class='bg-odd'><td>Hire Date</td><td valign=center>$myEmpl[hiredate]</td></tr>
	<tr class='bg-even'><td>Telephone No</td><td valign=center>$myEmpl[telno]</td></tr>
	<tr class='bg-odd'><td>E-mail</td><td valign=center>$myEmpl[email]</td></tr>
	<tr class='bg-even'><td>Basic Salary</td><td valign=center>".CUR." $myEmpl[basic_sal]</td></tr>
	<tr class='bg-odd'><td>Pay Type</td><td valign=center>$myEmpl[paytype]</td></tr>
	<tr class='bg-even'><td>Bank Name</td><td valign=center>$myEmpl[bankname]</td></tr>
	<tr class='bg-odd'><td>Branch Code</td><td valign=center>$myEmpl[bankcode]</td></tr>
	<tr class='bg-even'><td>Bank Account Type</td><td valign=center>$myEmpl[bankacctype]</td></tr>
	<tr class='bg-odd'><td>Bank Account No</td><td valign=center>$myEmpl[bankaccno]</td></tr>
	<tr class='bg-even'><td>Leave Reason</td><td>$myEmpl[leavereason]</td></tr>
	<tr class='bg-odd'><td>Date</td><td>$myEmpl[leavedate]</td></tr>
	<tr class='bg-even'><td>Description</td><td>$myEmpl[leavedescription]</td></tr>
	</table></td>
	<td valign=top><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th colspan=2>Employee Details</th></tr>
	<tr class='bg-even'><td>ID Num</td><td>$myEmpl[idnum]</td></tr>
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
	</table></td></tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee")
	);
	return $viewEmp;
}
?>

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

if ($_GET) {
	# confirm removal
	$OUTPUT = viewEmp ($_GET['empnum']);
} else {
	$OUTPUT = "Invalid option.";
}

require ("../template.php");

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
	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid clock number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	# Set up table & form
	$viewEmp =
"
<h3>View employee details</h3>

<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Surname</td><td valign=center>$myEmpl[sname]</td></tr>
<tr class='bg-even'><td>First Names</td><td valign=center>$myEmpl[fnames]</td></tr>
<tr class='bg-odd'><td>Sex</td><td valign=center>$myEmpl[sex]</td></tr>
<tr class='bg-even'><td>Marital Status</td><td valign=center>$myEmpl[marital]</td></tr>
<tr class='bg-odd'><td>Resident</td><td valign=center>$myEmpl[resident]</td></tr>
<tr class='bg-even'><td>Hire Date</td><td valign=center>$myEmpl[hiredate]</td></tr>
<tr class='bg-odd'><td>Telephone No</td><td valign=center>$myEmpl[telno]</td></tr>
<tr class='bg-even'><td>Email</td><td valign=center>$myEmpl[email]</td></tr>
<tr class='bg-odd'><td>Remuneration</td><td valign=center>$myEmpl[basic_sal]</td></tr>
<tr class='bg-even'><td>Pay Type</td><td valign=center>$myEmpl[paytype]</td></tr>
<tr class='bg-odd'><td>Bank Name</td><td valign=center>$myEmpl[bankname]</td></tr>
<tr class='bg-even'><td>Branch Code</td><td valign=center>$myEmpl[bankcode]</td></tr>
<tr class='bg-odd'><td>Bank Account Type</td><td valign=center>$myEmpl[bankacctype]</td></tr>
<tr class='bg-even'><td>Bank Account No</td><td valign=center>$myEmpl[bankaccno]</td></tr>
<tr class='bg-odd'><td>Residential Address</td><td valign=center>$myEmpl[res1]</td></tr>
<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[res2]</td></tr>
<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[res3]</td></tr>
<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[res4]</td></tr>
<tr class='bg-odd'><td>Postal Address</td><td valign=center>$myEmpl[pos1]</td></tr>
<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[pos2]</td></tr>
<tr class='bg-odd'><td>Postal Code</td><td valign=center>$myEmpl[pcode]</td></tr>
<tr><th colspan=2>Friend Not Living With Employee</th></tr>
<tr class='bg-even'><td>Surname</td><td valign=center>$myEmpl[contsname]</td></tr>
<tr class='bg-odd'><td>First Names</td><td valign=center>$myEmpl[contfnames]</td></tr>
<tr class='bg-even'><td>Residential Address</td><td valign=center>$myEmpl[contres1]</td></tr>
<tr class='bg-odd'><td><br></td><td valign=center>$myEmpl[contres2]</td></tr>
<tr class='bg-even'><td><br></td><td valign=center>$myEmpl[contres3]</td></tr>
<tr class='bg-even'><td>Telephone No</td><td valign=center>$myEmpl[conttelno]</td></tr>
</table>
<p>
";
	return $viewEmp;
}
?>

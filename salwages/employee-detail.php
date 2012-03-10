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

if ($HTTP_GET_VARS) {
	# confirm removal
	$OUTPUT = viewEmp ($HTTP_GET_VARS['empnum']);
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
<tr bgcolor='".TMPL_tblDataColor1."'><td>Surname</td><td valign=center>$myEmpl[sname]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>First Names</td><td valign=center>$myEmpl[fnames]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Sex</td><td valign=center>$myEmpl[sex]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Marital Status</td><td valign=center>$myEmpl[marital]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Resident</td><td valign=center>$myEmpl[resident]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Hire Date</td><td valign=center>$myEmpl[hiredate]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Telephone No</td><td valign=center>$myEmpl[telno]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Email</td><td valign=center>$myEmpl[email]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Remuneration</td><td valign=center>$myEmpl[basic_sal]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Pay Type</td><td valign=center>$myEmpl[paytype]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank Name</td><td valign=center>$myEmpl[bankname]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch Code</td><td valign=center>$myEmpl[bankcode]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank Account Type</td><td valign=center>$myEmpl[bankacctype]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account No</td><td valign=center>$myEmpl[bankaccno]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Residential Address</td><td valign=center>$myEmpl[res1]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[res2]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[res3]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[res4]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Address</td><td valign=center>$myEmpl[pos1]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[pos2]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Code</td><td valign=center>$myEmpl[pcode]</td></tr>
<tr><th colspan=2>Friend Not Living With Employee</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Surname</td><td valign=center>$myEmpl[contsname]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>First Names</td><td valign=center>$myEmpl[contfnames]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Residential Address</td><td valign=center>$myEmpl[contres1]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td valign=center>$myEmpl[contres2]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td valign=center>$myEmpl[contres3]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No</td><td valign=center>$myEmpl[conttelno]</td></tr>
</table>
<p>
";
	return $viewEmp;
}
?>

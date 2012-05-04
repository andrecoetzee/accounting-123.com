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

# get settings
require ("../settings.php");

$OUTPUT = viewLoans ();



# display output
require ("../template.php");

# enter new data
function viewLoans ()
{
	# select employees with loans
	$employees = "";
	$i = 0;
	db_connect ();
	$sql = "SELECT * FROM employees WHERE gotloan='t'::bool AND div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees with loans from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employee-loans found in database.<p>"
		.mkQuickLinks(
			ql("loan-add.php", "Add New Loan"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	}
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$totloan = sprint($myEmp['loaninstall']*$myEmp['loanperiod']);
		$totout= sprint($myEmp['loanamt']);
		$employees .= "<tr class='".bg_class()."'><td>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td><td align=right>".CUR." $totloan</td><td align=right>".CUR." $totout</td><td align=right>".CUR." $myEmp[loaninstall]</td><td align=right>$myEmp[loanint] %</td><td align=right>$myEmp[loanperiod] months</td><td><a href='loan-edit.php?empnum=$myEmp[empnum]'>Edit</a></td></tr>\n";
		$i++;
	}

	$viewLoans =
"
<h3>View current employee loans</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<input type=hidden name=key value=input>
<tr><th>Employee</th><th>Loan amount(incl interest)</th><th>Amount outstanding</th><th>Monthly installment</th><th>Loan interest</th><th>Payback period</th></tr>
$employees
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $viewLoans;
}

?>

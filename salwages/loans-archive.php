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

	$sql = "SELECT * FROM emp_loanarchive WHERE donedata IS NOT NULL AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees with loans from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "<li class='err'>No previous employee loans found.</li><br>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	}

	while ($loaninfo = pg_fetch_array ($empRslt)) {
		$emp_sql = "SELECT * FROM employees WHERE empnum='$loaninfo[empnum]'";
		$emp_rslt = db_exec($emp_sql) or errDie("Error reading employee info.");

		if ( pg_num_rows($emp_rslt) < 1 ) continue;

		$myEmp = pg_fetch_array($emp_rslt);

		$employees .= "
			<tr class='".bg_class()."'>
				<td>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
				<td align='right'>".CUR." $loaninfo[loanamt]</td>
				<td align='right'>".CUR." $loaninfo[loaninstall]</td>
				<td align='right'>$loaninfo[loanint] %</td>
				<td align='right'>$loaninfo[loanperiod] months</td>
				<td align='center'>$loaninfo[loandate]</td>
				<td align='center'>$loaninfo[donedata]</td>
			</tr>\n";
		$i++;
	}

	$viewLoans = "
		<h3>View archived employee loans</h3>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='input'>
		<tr>
			<th>Employee</th>
			<th>Loan amount(incl interest)</th>
			<th>Monthly installment</th>
			<th>Loan interest</th>
			<th>Payback period</th>
			<th>Loan Date</th>
			<th>Completion Date</th>
		</tr>
		$employees
		</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $viewLoans;

}



?>
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

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmLoan ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeLoan ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
} else {
	$OUTPUT = enterLoan ($HTTP_GET_VARS);
}

# display output
require ("../template.php");




# enter loan details (or immediately reject)
function enterLoan ($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect ();

	# get employee info
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	$enterLoan = "
		<h3>Edit current loan</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Loan amount</td>
				<td align='center'>".CUR."<br><input type='text' size='10' name='loanamt' value='$myEmp[loanamt]' class=right></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Interest on loan</td>
				<td align='center'><input type='text' size='10' name='loanint' value='$myEmp[loanint]'><br>%</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Payback period (months)</td>
				<td align='center'><input type='text' size='10' name='loanperiod' value='$myEmp[loanperiod]'></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $enterLoan;
}




# confirm new data
function confirmLoan ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect ();

	# get employee info
	$sql = "SELECT sname, fnames, empnum, enum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# calculate monthly instalmyEmp[lments
	if($loanperiod > 0) {
		$loaninstall = sprintf ("%01.2f", ($loanamt + (($loanamt * $loanint) / 100)) / $loanperiod);
	} else {
		$loaninstall = 0;
	}
	# format loanamt (2 decimal places)
	$loanamt = sprintf ("%01.2f", $loanamt);

	$confirmLoan = "
		<h3>Edit current loan</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='loanamt' value='$loanamt'>
			<input type='hidden' name='loanint' value='$loanint'>
			<input type='hidden' name='loanperiod' value='$loanperiod'>
			<input type='hidden' name='loaninstall' value='$loaninstall'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Loan amount</td>
				<td align='center'>".CUR." $loanamt</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Interest on loan</td>
				<td align='center'>$loanint %</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Payback period</td>
				<td align='center'>$loanperiod months</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Monthly installment amount</td>
				<td align='center'>".CUR." $loaninstall</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo;'> <input type='button' value='Back' onclick='javascript:history.back();'</td>
				<td valign='left'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $confirmLoan;

}




# write new data
function writeLoan ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk ($loaninstall, "float", 1, 10, "Invalid monthly installment.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect ();

	# write to db
	$sql = "
		UPDATE employees 
		SET loanamt='$loanamt', loanint='$loanint', loanperiod='$loanperiod', loaninstall='$loaninstall',
			loanpayslip='$loanamt', gotloan='t'::bool 
		WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$loanRslt = db_exec ($sql) or errDie ("Unable to add loan to system.", SELF);
	if (pg_cmdtuples ($loanRslt) < 1) {
		return "Unable to add loan to system.";
	}

	$writeLoan = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Existing loan modified</th>
			</tr>
			<tr class='datacell'>
				<td>Existing loan has been successfully modified.</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $writeLoan;

}



?>
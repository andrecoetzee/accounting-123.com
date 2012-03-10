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
require ("../core-settings.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "write":
			$OUTPUT = writeLoan ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = confirmLoan ($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = confirmLoan ($HTTP_POST_VARS);
}


# display output
require ("../template.php");





# confirm new data
function confirmLoan ($HTTP_POST_VARS)
{

	global $HTTP_GET_VARS;

	if(!isset($HTTP_GET_VARS["id"]) OR (strlen($HTTP_GET_VARS["id"]) < 1)){
		return "<li class='err'>Invalid Use Of Mudule. Invalid Loan ID.</li>";
	}

	db_connect ();

	$get_loan_app = "SELECT * FROM loan_requests WHERE id = '$HTTP_GET_VARS[id]' LIMIT 1";
	$run_loan_app = db_exec($get_loan_app) or errDie("Unable to get loan application information.");
	if(pg_numrows($run_loan_app) < 1){
		return "<li class='err'>Could not get loan application information.</li>";
	}else {
		$larr = pg_fetch_array($run_loan_app);
		extract ($larr);
	}

	#extract the loan date
	$ldarr = explode ("-",$larr['ldate']);
	$lyear = $ldarr[0];
	$lmonth = $ldarr[1];
	$lday = $ldarr[2];

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk ($lday, "num", 1, 2, "Invalid day.");
	$v->isOk ($lmonth, "num", 1, 2, "Invalid month.");
	$v->isOk ($lyear, "num", 4, 4, "Invalid year.");
	$v->isOk ($accid, "num", 1, 9, "Invalid bank account selected.");
	$v->isOk ($account, "num", 1, 9, "Invalid contra account selected.");
	$v->isOk ($loan_account, "num", 1, 9, "Invalid loan account selected.");
	$v->isOk ($loan_type, "num", 1, 9, "Invalid loan type selected.");

	if ( ! checkdate($lmonth, $lday, $lyear) ) {
		$v->addError("", "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return enterLoan($confirmCust);
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

	$fringeset = getCSetting("EMPLOAN_FRINGEINT");

	# calculate monthly installments
	#why does the int amount get multiplied by the repayment years ? you only pay interest ONCE
//	$loaninstall = sprint (((($loanamt * $loanint/100) * ($loanperiod/12)) + $loanamt) / $loanperiod);
//	$fringeinstall = sprint (((($loanamt * $fringeset/100) * ($loanperiod/12)) + $loanamt) / $loanperiod);
	$loaninstall = sprint (((($loanamt * $loanint/100) * (1)) + $loanamt) / $loanperiod);
	$fringeinstall = sprint (((($loanamt * $fringeset/100) * (1)) + $loanamt) / $loanperiod);

	$totaldue = $loaninstall * $loanperiod;
	$totalinterest = $totaldue - $loanamt;

	$fringebenefit = sprint((($fringeinstall * $loanperiod) - $loanamt) - $totalinterest);

	# format loanamt (2 decimal places)
	$loanamt = sprintf ("%01.2f", $loanamt);

	if(($account != 0 && $accid != 0) || ($account == 0 && $accid == 0)) {
		return enterLoan("<li class='err'>Please select a bank account OR a general ledger account.</li>");
	}

	/* get bank acc/contra acc info */
	if($account > 0) {
		db_conn('core');
		$sql = "SELECT * FROM accounts WHERE accid='$account'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}
		$acc = pg_fetch_array($accRslt);

		$ac = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$acc[accname]</td>
			</tr>";
	} else {
		# Get bank account name
		$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		$ac = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td>$bank[bankname] - $bank[accname]</td>
			</tr>";
	}

	/* loan acc info */
	db_conn('core');

	$sql = "SELECT * FROM accounts WHERE accid='$loan_account'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}
	$acc = pg_fetch_array($accRslt);

	$loanac = "
		<tr bgcolor='".bgcolorg()."'>
			<td>Account</td>
			<td>$acc[accname]</td>
		</tr>";

	/* date description */
	$datedesc = date("j F Y", mktime(0, 0, 0, $lmonth, $lday, $lyear));

	db_connect ();

	#get loan type description
	$get_loan_type = "SELECT * FROM loan_types WHERE id = '$loan_type' LIMIT 1";
	$run_loan_type = db_exec($get_loan_type) or errDie("Unable to get loan type information.");
	if(pg_numrows($run_loan_type) < 1){
		$showloantype = "Unknown Loan Type";
	}else {
		$larr = pg_fetch_array($run_loan_type);
		$showloantype = $larr['loan_type'];
	}

	if (isset($_REQUEST["deny"])) {
		$confirmLoan = "<h3>Confirm Denial Of New Loan</h3>";
	} else {
		$confirmLoan = "<h3>Confirm Approval Of New Loan</h3>";
	}

	$confirmLoan .= "
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='loanid' value='$HTTP_GET_VARS[id]'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='lday' value='$lday'>
			<input type='hidden' name='lmonth' value='$lmonth'>
			<input type='hidden' name='lyear' value='$lyear'>
			<input type='hidden' name='loanamt' value='$loanamt'>
			<input type='hidden' name='loanint' value='$loanint'>
			<input type='hidden' name='loanperiod' value='$loanperiod'>
			<input type='hidden' name='loaninstall' value='$loaninstall'>
			<input type='hidden' name='fringebenefit' value='$fringebenefit'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='account' value='$account'>
			<input type='hidden' name='loan_account' value='$loan_account'>
			<input type='hidden' name='loan_type' value='$loan_type'>
			".(isset($_GET["deny"])?"<input type='hidden' name='deny' value='t' />":"")."
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Loan Date</td>
				<td align='center'>$datedesc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Loan Approval/Denial Date</td>
				<td align='center'>".mkDateSelect("arch", $lyear, $lmonth, $lday)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Loan Type</td>
				<td align='center'>$showloantype</td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td>Installment amount (at 11% Interest)</td>
				<td align='center'>".CUR." $fringeinstall</td>
			</tr>
			$loanac
			$ac
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
				<td valign='left'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("loan_apply.php", "Add Loan Application"),
			ql("loan_apply_view.php", "View Loan Applications"),
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
	$v->isOk ($loanid, "num", 1, 20, "Invalid loan ID.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk ($loaninstall, "float", 1, 10, "Invalid monthly installment.");
	$v->isOk ($fringebenefit, "float", 1, 10, "Invalid fringe benefit amount.");
	$v->isOk ($accid, "num", 1, 9, "Invalid bank account selected.");
	$v->isOk ($account, "num", 1, 9, "Invalid contra account selected.");
	$v->isOk ($loan_account, "num", 1, 9, "Invalid loan account selected.");
	$v->isOk ($loan_type, "num", 1, 9, "Invalid loan type selected.");
	$ldate = mkdate($lyear, $lmonth, $lday);
	$v->isOk($ldate, "date", 1, 1, "Invalid loan date.");
	$archdate = mkdate($arch_year, $arch_month, $arch_day);
	$v->isOk($archdate, "date", 1, 1, "Invalid approval/denial date.");

	if ( ! checkdate($lmonth, $lday, $lyear) ) {
		$v->addError("", "Invalid date.");
	}

	if ( ($account > 0 && isb($account)) || isb($loan_account) ) {
		$v->addError("", "Main accounts blocked. Please select sub accounts.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return enterLoan($confirmCust);
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($ldate) >= strtotime($blocked_date_from) AND strtotime($ldate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($archdate) >= strtotime($blocked_date_from) AND strtotime($archdate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# get employee details
	db_connect ();

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	if (!isset($deny)) {
		# check for previous loan
		$sql = "SELECT empnum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."' AND gotloan='t'::bool";
		$chkRslt = db_exec ($sql) or errDie ("Unable to check existing loans for employee.");
		if (pg_numrows ($chkRslt) > 0) {
			return "<li class='err'>Loan already exists for employee number: $empnum.</li>";
		}
	}

	$date = date("Y-m-d");

	pglib_transaction("BEGIN");

	$totamount = sprint($loanperiod * $loaninstall);
	$loanint_amt = $totamount - $loanamt;

	$sql = "
		INSERT INTO emp_loanarchive (
			empnum, loanamt, loaninstall, loanint, loanperiod, loandate, archdate, loan_type, 
			div, status
		) VALUES (
			'$empnum', '$totamount', '$loaninstall', '$loanint', '$loanperiod', '$ldate', '$archdate', '$loan_type', 
			'".USER_DIV."', '".(isset($deny)?"D":"A")."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to pre archive loan.");

	$loanaid = pglib_lastid('emp_loanarchive', 'id');

	$rem_sql = "DELETE FROM loan_requests WHERE id = '$loanid'";
	$run_rem = db_exec($rem_sql) or errDie("Unable to get loan requests information.");

	if (!isset($deny)) {
		$refnum = getrefnum();

		if ($accid > 0) {
			$bankacc = getbankaccid($accid);
		}

		if ($account > 0) {
			$bankacc = $account;
		}

		writetrans($loan_account, $bankacc, $archdate, $refnum, $loanamt, "Loan granted to employee $myEmp[fnames] $myEmp[sname].");

		if ($accid > 0) {
			banktrans($accid, "withdrawal", $archdate, "$myEmp[fnames] $myEmp[sname]", "Loan granted to employee $myEmp[fnames] $myEmp[sname].", 0, $loanamt, $loan_account);
		}

		# write to db
		$sql = "
			UPDATE cubit.employees 
			SET loanamt = '$totamount', loanint = '$loanint', loanint_amt = '$loanint_amt', loanint_unpaid = '$loanint_amt', 
				loanperiod = '$loanperiod', loaninstall = '$loaninstall', gotloan = 't'::bool, loanpayslip = '$loanamt', 
				loanfringe = '$fringebenefit', loandate = '$archdate', expacc_loan = '$loan_account', 
				loanamt_tot = '$totamount', loanid = '$loanaid' 
			WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
		$loanRslt = db_exec ($sql) or errDie ("Unable to add loan to system.", SELF);

		if (pg_cmdtuples ($loanRslt) < 1) {
			return "Unable to add loan to system.";
		}
	}

	pglib_transaction("COMMIT");

	$OUT = "<table ".TMPL_tblDflts.">";

	if (isset($deny)) {
		$OUT .= "
			<tr>
				<th>Loan Denied And Request Archived.</th>
			</tr>";
	} else {
		$OUT .= "
			<tr>
				<th>Loan Granted And Added To System</th>
			</tr>";
	}

	$OUT .= "
		<tr class='datacell'>
			<td>Loan information successfully updated.</td>
		</tr>
		".TBL_BR;

	if (!isset($deny)) {
		$OUT .= "
			<tr>
				<td><input type='button' onclick=\"document.location='../reporting/loan_approval.php?id=$loanaid'\" value='Generate Approval Report'></td>
			</tr>";
	}

	$OUT .= "
		</table><br>"
		.mkQuickLinks(
			ql("loan_apply.php", "Add Loan Application"),
			ql("loan_apply_view.php", "View Loan Applications"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $OUT;

}



# Self explainatory
function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv)
{

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account number.");
	$v->isOk ($trantype, "string", 1, 50, "Invalid Transaction type.");
	$v->isOk ($date, "date", 1, 14, "Invalid Bank Transaction date.");
	$v->isOk ($name, "string", 1, 50, "Invalid Name.");
	$v->isOk ($details, "string", 0, 255, "Invalid Bank Transacton details.");
	$v->isOk ($cheqnum, "num", 0, 50, "Invalid Bank Transacton cheque number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Bank Transacton Amount.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Bank Transaction account involved.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$OUTPUT = $write."<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		require("../template.php");
	}


	# record the payment record
	db_connect();

	$sql = "
		INSERT INTO cashbook (
			bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div
		) VALUES (
			'$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

}


?>

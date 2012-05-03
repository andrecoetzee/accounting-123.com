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
require("emp-functions.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "input":
			$OUTPUT = enterLoan ();
			break;
		case "confirm":
			$OUTPUT = confirmLoan ($_POST);
			break;
		case "write":
			$OUTPUT = writeLoan ($_POST);
			break;
		default:
			$OUTPUT = slctEmployee ();
	}
} else {
	$OUTPUT = slctEmployee ();
}

# display output
require ("../template.php");




# enter new data
function slctEmployee ()
{

	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.";
	}

	# select employees
	$employees = "<select size='1' name='empnum'>\n";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>\n";
	}
	$employees .= "</select>\n";


	$slctEmployee = "
		<h3>Select employee applying for loan</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='input'>
			<tr>
				<th colspan='2'>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Apply &raquo;'></td>
			</tr>
		</form>
		</table><br>"
	.mkQuickLinks(
		ql("loan_apply.php", "Add Loan Application"),
		ql("loan_apply_view.php", "View Loan Applications"),
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $slctEmployee;

}


# enter loan details (or immediately reject)
function enterLoan ($err="")
{

	global $_POST;

	# get vars
	extract ($_POST);

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

	if (($fields["loanint"] = getCSetting("EMPLOAN_INT")) == "") {
		$fields["loanint"] = 9;
	}

	if (($fields["loanperiod"] = getCSetting("EMPLOAN_MTHS")) == "") {
		$fields["loanperiod"] = "6";
	}

	$fields["loanamt"] = "0.00";
	$fields["lday"] = date("d");
	$fields["lmonth"] = date("m");
	$fields["lyear"] = date("Y");
	$fields["accid"] = 0;
	$fields["account"] = 0;
	$fields["loan_account"] = 0;

	foreach ( $fields as $k => $v ) {
		if ( ! isset($$k) ) {
			$$k = $v;
		}
	}

	$day = "<select name='lday'>";
	for ( $i = 1; $i <= 31; $i++ ) {
		if ( $i == $lday ) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$day .= "<option $sel value='$i'>$i</option>";
	}
	$day .= "</select>";

	$month = empMonList("lmonth", $lmonth);

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' AND btype='loc' ORDER BY accname ASC";
	$bnks = db_exec($sql);
	if(pg_numrows($bnks) < 1){
		return "<li class='err'> There are no bank accounts found in Cubit.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$banks = "
		<select name='accid'>
			<option value='0'>Select Bank Account</option>";
	while($acc = pg_fetch_array($bnks)){

		if ( $acc["bankid"] == $accid ) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$banks .= "<option $sel value='$acc[bankid]'>$acc[accname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	/* create account selection drop downs */
	$accounts = "
		<select name='account'>
			<option value='0'>Select Account</option>";

	$loan_accounts="
		<select name='loan_account'>
			<option value='0'>Select Loan Account</option>";

	db_conn('core');

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY topacc,accnum ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}
	$accs_found = array();
	$prev_main = "000";
	while($acc = pg_fetch_array($accRslt)){
		if(isb($acc['accid'])) {
			continue;
		}
		// sub account indentation logic
		if ( $acc["accnum"] == "000" || $prev_main != $acc["topacc"] ) {
			$spaces = "";
			$prev_main = $acc["topacc"];
		} else {
			$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}

		if ( $acc["accid"] == $account ) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$accounts .= "<option $sel value='$acc[accid]'>$acc[topacc]/$acc[accnum] $spaces- $acc[accname]</option>";

		if ( $acc["accid"] == $loan_account ) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$loan_accounts .= "<option $sel value='$acc[accid]'>$acc[topacc]/$acc[accnum] $spaces- $acc[accname]</option>";
	}
	$accounts .= "</select>";
	$loan_accounts .= "</select>";

	db_connect ();

	$get_loants = "SELECT * FROM loan_types ORDER BY loan_type";
	$run_loants = db_exec($get_loants) or errDie("Unable to get loan types information.");
	if(pg_numrows($run_loants) < 1){
		return "<li>There are No Loan Types in Cubit.</li><br><br>"
			.mkQuickLinks(
				ql("../loan_type_add.php", "Add Loan Type"),
				ql("../loan_type_view.php", "View Loan Types")
			);
	}else {
		if(!isset($loan_type))
			$loan_type = "";
		$loan_type_drop = "<select name='loan_type'>";
		while ($larr = pg_fetch_array($run_loants)){
			if($loan_type == $larr['id']){
				$loan_type_drop .= "<option selected value='$larr[id]'>$larr[loan_type]</option>";
			}else {
				$loan_type_drop .= "<option value='$larr[id]'>$larr[loan_type]</option>";
			}
		}
		$loan_type_drop .= "</select>";
	}

	$enterLoan = "
		<h3>Complete Details To Apply For Loan</h3>
		$err
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date Granted</td>
				<td>$day $month</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan Type</td>
				<td>$loan_type_drop</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan amount</td>
				<td>".CUR."<input type='text' size='10' name='loanamt' class='right' value='$loanamt'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Interest on loan</td>
				<td><input type='text' size='5' name='loanint' class='right' value='$loanint'>%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Payback period (months)</td>
				<td><input type='text' size='5' name='loanperiod' class='right' value='$loanperiod'></td>
			</tr>
			<tr>
				<td colspan='2'>
					<li class='err'>You must FIRST create an employee loan account which must be a sub account of<br>
						the main account called 'Employee Loans'.</li>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Loan Account</td>
				<td align='center'>$loan_accounts</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td colspan='2'>
					<li class='err'>Select one of the following. This selection is for the account to be Credited,<br>
					in other words, where the money comes from.</li>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Bank Account</td>
				<td>$banks</td>
			</tr>
			<tr>
				<td colspan='2' align='center'><b>OR</b></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Account</td>
				<td align='center'>$accounts</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<br />"
		.mkQuickLinks(
			ql("loan_apply.php", "Add Loan Application"),
			ql("loan_apply_view.php", "View Loan Applications"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $enterLoan;

}



# confirm new data
function confirmLoan ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk ($lday, "num", 1, 2, "Invalid day.");
	$v->isOk ($lmonth, "num", 1, 2, "Invalid month.");
	$v->isOk ($accid, "num", 1, 9, "Invalid bank account selected.");
	$v->isOk ($account, "num", 1, 9, "Invalid contra account selected.");
	$v->isOk ($loan_account, "num", 1, 9, "Invalid loan account selected.");
	$v->isOk ($loan_type, "num", 1, 9, "Invalid loan type selected.");
	
	if (empty($loanperiod)) {
		$v->addError("", "You need to enter a payback period.");
	}

	$lyear = getYearOfEmpMon($lmonth);

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
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$acc[accname]</td>
			</tr>";
	} else {
		# Get bank account name
		$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		$ac = "
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td>$bank[bankname] - $bank[accname]</td>
			</tr>";
	}

	/* loan acc info */
	db_conn('core');

	$sql = "SELECT * FROM accounts WHERE accid='$loan_account'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return enterLoan("<li class='err'>Invalid Loan Account Selected.</li><br>");
	}
	$acc = pg_fetch_array($accRslt);

	$loanac = "
		<tr class='".bg_class()."'>
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

	$confirmLoan = "
		<h3>Confirm new loan application</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
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
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan Date</td>
				<td align='center'>$datedesc</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan Type</td>
				<td align='center'>$showloantype</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan amount</td>
				<td align='center'>".CUR." $loanamt</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Interest on loan</td>
				<td align='center'>$loanint %</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Payback period</td>
				<td align='center'>$loanperiod months</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Monthly installment amount</td>
				<td align='center'>".CUR." $loaninstall</td>
			</tr>
			<tr class='".bg_class()."'>
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
function writeLoan ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk ($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk ($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk ($loaninstall, "float", 1, 10, "Invalid monthly installment.");
	$v->isOk ($fringebenefit, "float", 1, 10, "Invalid fringe benefit amount.");
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



	# get employee details
	db_connect ();
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# check for previous loan
	$sql = "SELECT empnum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."' AND gotloan='t'::bool";
	$chkRslt = db_exec ($sql) or errDie ("Unable to check existing loans for employee.");
	if (pg_numrows ($chkRslt) > 0) {
		return "<li class='err'>Loan already exists for employee number: $myEmp[enum].</li>";
	}

	if($accid > 0) {
		$bankacc = getbankaccid($accid);
	}

	# Debit salaries control acc and credit Bank acc
	$date = date("Y-m-d");
	$ldate = mkdate($lyear, $lmonth, $lday);
	$refnum = getrefnum();

	if($account > 0) {
		$bankacc = $account;
	}

//	writetrans($loan_account, $bankacc, $date, $refnum, $loanamt, "Loan granted to employee $myEmp[fnames] $myEmp[sname].");

	if($accid > 0) {
	# issue bank record
//		banktrans($accid, "withdrawal", date("d-m-Y"), "$myEmp[fnames] $myEmp[sname]", "Loan granted to employee $myEmp[fnames] $myEmp[sname].", 0, $loanamt, $loan_account);
	}

	$totamount = sprint($loanperiod*$loaninstall);
	$loanint_amt = $totamount - $loanamt;

	# connect to db
	db_connect ();

	$ldate = "$lyear-$lmonth-$lday";

	$insert_sql = "
		INSERT INTO loan_requests (
			empnum, loanamt, loaninstall, loanint, loanperiod, loandate, 
			loan_type, div, loan_account, bankacc, date, totamount, 
			loanint_amt, fringebenefit, ldate, account, accid
		) VALUES (
			'$empnum', '$loanamt', '$loaninstall', '$loanint', '$loanperiod', '$ldate', 
			'$loan_type', '".USER_DIV."', '$loan_account', '$bankacc', '$date', '$totamount', 
			'$loanint_amt', '$fringebenefit', '$ldate', '$account', '$accid'
		)";
	$run_insert = db_exec($insert_sql) or errDie("Unable to add loan application request.");


// 	pglib_transaction("BEGIN");
//
// 	$sql = "INSERT INTO emp_loanarchive (empnum, loanamt, loaninstall, loanint, loanperiod,loandate, loan_type, div)
// 			VALUES('$empnum', '$totamount', '$loaninstall', '$loanint', '$loanperiod', CURRENT_DATE, '$loan_type', '".USER_DIV."')";
// 	$rslt = db_exec($sql) or errDie("Unable to pre archive loan.");
//
// 	$loanid = pglib_lastid('emp_loanarchive', 'id');
//
// 	# write to db
// 	$sql = "UPDATE employees
// 			SET loanamt='$totamount', loanint='$loanint', loanint_amt='$loanint_amt',
// 				loanint_unpaid='$loanint_amt', loanperiod='$loanperiod', loaninstall='$loaninstall',
// 				gotloan='t'::bool, loanpayslip='$loanamt', loanfringe='$fringebenefit', loandate='$ldate',
// 				expacc_loan='$loan_account', loanamt_tot='$totamount', loanid='$loanid'
// 			WHERE empnum='$empnum' AND div = '".USER_DIV."'";
// 	$loanRslt = db_exec ($sql) or errDie ("Unable to add loan to system.", SELF);
//
// 	if (pg_cmdtuples ($loanRslt) < 1) {
// 		return "Unable to add loan to system.";
// 	}
//
// 	pglib_transaction("COMMIT");

	$writeLoan = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Loan granted and added to system</th>
			</tr>
			<tr class='datacell'>
				<td>New loan application has been successfully added to Cubit.
				<a href='../groupware/req_gen.php'>Send</a> an instant message.</td>
			</tr>
		</table><br>"
		.mkQuickLinks(
			ql("loan_apply.php", "Add Loan Application"),
			ql("loan_apply_view.php", "View Loan Applications"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $writeLoan;

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



	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

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
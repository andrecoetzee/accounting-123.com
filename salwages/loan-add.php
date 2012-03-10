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
		case "input":
			$OUTPUT = enterLoan ();
			break;
		case "confirm":
			$OUTPUT = confirmLoan ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeLoan ($HTTP_POST_VARS);
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
	# select employees
	$employees = "<select size=1 name=empnum>\n";
	db_connect ();
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.";
	}
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</option>\n";
	}
	$employees .= "</select>\n";


	$slctEmployee =
	"
	<h3>Select employee to grant loan to</h3>

	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=input>
	<tr><th colspan=2>Employee</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Employee</td><td align=center>$employees</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Enter loan &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $slctEmployee;
}

# enter loan details (or immediately reject)
function enterLoan ($err="")
{
	global $HTTP_POST_VARS;
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	if (($fields["loanint"] = getCSetting("EMPLOAN_INT")) == "") {
		$fields["loanint"] = 8;
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

	$day = "<select name=lday>";
	for ( $i = 1; $i <= 31; $i++ ) {
		if ( $i == $lday ) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$day .= "<option $sel value='$i'>$i</option>";
	}
	$day .= "</select>";

	$month = "<select name=lmonth>";
	for ( $i = 1; $i <= 12; $i++ ) {
		if ( $i == $lmonth ) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$month .= "<option $sel value='$i'>".date("F", mktime(0, 0, 0, $i, 1, 2000))."</option>";
	}
	$month .= "</select>";

	$year = "<select name=lyear>";
	for ( $i = 1980; $i <= 2027; $i++ ) {
		if ( $i == $lyear ) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$year .= "<option $sel value='$i'>$i</option>";
	}
	$year .= "</select>";

	$banks = "<select name=accid>
	<option value='0'>Select Bank Account</option>";
		db_connect();
		$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' AND btype='loc' ORDER BY accname ASC";
		$bnks = db_exec($sql);
		if(pg_numrows($bnks) < 1){
			return "<li class=err> There are no bank accounts found in Cubit.
			<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}
		while($acc = pg_fetch_array($bnks)){

			if ( $acc["bankid"] == $accid ) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$banks .= "<option $sel value=$acc[bankid]>$acc[accname] ($acc[acctype])</option>";
		}
	$banks .= "</select>";

	/* create account selection drop downs */
	$accounts="
		<select name=account>
			<option value='0'>Select Account</option>";

	$loan_accounts="
		<select name=loan_account>
			<option value='0'>Select Loan Account</option>";

	db_conn('core');
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY topacc,accnum ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
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
	$accounts .="</select>";
	$loan_accounts .="</select>";

	$enterLoan ="<h3>Grant Loan</h3>
	$err
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=empnum value='$empnum'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Date Granted</td><td>".CUR."$day $month $year</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Loan amount</td><td>".CUR."<input type=text size=10 name=loanamt class=right value='$loanamt'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Interest on loan</td><td><input type=text size=5 name=loanint class=right value='$loanint'>%</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Payback period (months)</td><td><input type=text size=5 name=loanperiod class=right value='$loanperiod'></td></tr>
	<tr><td colspan=2><li class=err>You must FIRST create an employee loan account which must be a sub account of<br>
									the main account called 'Employee Loans'.</li></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Loan Account</td><td align=center>$loan_accounts</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr><td colspan=2><li class=err>Select one of the following. This selection is for the account to be Credited,<br>
		in other words, where the money comes from.</li></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Bank Account</td><td>$banks</td></tr>
	<tr><td colspan=2 align=center><b>OR</b></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Account</td><td align=center>$accounts</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
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
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

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
	$v->isOk($accid, "num", 1, 9, "Invalid bank account selected.");
	$v->isOk($account, "num", 1, 9, "Invalid contra account selected.");
	$v->isOk($loan_account, "num", 1, 9, "Invalid loan account selected.");

	if ( ! checkdate($lmonth, $lday, $lyear) ) {
		$v->addError("", "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
		}
		return enterLoan($confirmCust);
	}

	# connect to db
	db_connect ();

	# get employee info
	$sql = "SELECT sname, fnames, empnum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# calculate monthly installments
	$loaninstall = sprint (((($loanamt * $loanint/100) * ($loanperiod/12)) + $loanamt) / $loanperiod);
	$fringeinstall = sprint (((($loanamt * 8/100) * ($loanperiod/12)) + $loanamt) / $loanperiod);

	$totaldue = $loaninstall * $loanperiod;
	$totalinterest = $totaldue - $loanamt;

	$fringebenefit = sprint((($fringeinstall * $loanperiod) - $loanamt) - $totalinterest);

	# format loanamt (2 decimal places)
	$loanamt = sprintf ("%01.2f", $loanamt);

	if(($account!=0&&$accid!=0)||($account==0&&$accid==0)) {
		return enterLoan("<li class=err>Please select a bank account OR a general ledger account.</li>");
	}

	/* get bank acc/contra acc info */
	if($account>0) {
		db_conn('core');
		$sql = "SELECT * FROM accounts WHERE accid='$account'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li>There are No accounts in Cubit.";
		}
		$acc = pg_fetch_array($accRslt);

		$ac="<tr bgcolor='".TMPL_tblDataColor2."'><td>Account</td><td>$acc[accname]</td></tr>";
	} else {
		# Get bank account name
		$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		$ac="<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank[bankname] - $bank[accname]</td></tr>";
	}

	/* loan acc info */
	db_conn('core');
	$sql = "SELECT * FROM accounts WHERE accid='$loan_account'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
	}
	$acc = pg_fetch_array($accRslt);

	$loanac="<tr bgcolor='".TMPL_tblDataColor2."'><td>Account</td><td>$acc[accname]</td></tr>";

	/* date description */
	$datedesc = date("j F Y", mktime(0, 0, 0, $lmonth, $lday, $lyear));

	$confirmLoan =
	"
	<h3>Confirm new loan</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=empnum value='$empnum'>
	<input type=hidden name=lday value='$lday'>
	<input type=hidden name=lmonth value='$lmonth'>
	<input type=hidden name=lyear value='$lyear'>
	<input type=hidden name=loanamt value='$loanamt'>
	<input type=hidden name=loanint value='$loanint'>
	<input type=hidden name=loanperiod value='$loanperiod'>
	<input type=hidden name=loaninstall value='$loaninstall'>
	<input type=hidden name=fringebenefit value='$fringebenefit'>
	<input type=hidden name=accid value='$accid'>
	<input type=hidden name=account value='$account'>
	<input type=hidden name=loan_account value='$loan_account'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Loan Date</td><td align=center>$datedesc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Loan amount</td><td align=center>".CUR." $loanamt</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Interest on loan</td><td align=center>$loanint %</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Payback period</td><td align=center>$loanperiod months</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Monthly installment amount</td><td align=center>".CUR." $loaninstall</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Installment amount (at 8% Interest)</td><td align=center>".CUR." $fringeinstall</td></tr>
	$loanac
	$ac
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td><td valign=left></td></tr>
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
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk($loanamt, "float", 1, 10, "Invalid loan amount.");
	$v->isOk($loanint, "float", 1, 5, "Invalid loan interest.");
	$v->isOk($loanperiod, "num", 1, 3, "Invalid payback period.");
	$v->isOk($loaninstall, "float", 1, 10, "Invalid monthly installment.");
	$v->isOk($fringebenefit, "float", 1, 10, "Invalid fringe benefit amount.");
	$v->isOk($lday, "num", 1, 2, "Invalid day.");
	$v->isOk($lmonth, "num", 1, 2, "Invalid month.");
	$v->isOk($lyear, "num", 4, 4, "Invalid year.");
	$v->isOk($accid, "num", 1, 9, "Invalid bank account selected.");
	$v->isOk($account, "num", 1, 9, "Invalid contra account selected.");
	$v->isOk($loan_account, "num", 1, 9, "Invalid loan account selected.");

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
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
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
		return "Loan already exists for employee number: $empnum.";
	}

	if($accid>0) {
		$bankacc = getbankaccid($accid);
	}

	# Debit salaries control acc and credit Bank acc
	$date = date("Y-m-d");
	$refnum = getrefnum();

	if($account>0) {
		$bankacc=$account;
	}

	writetrans($loan_account, $bankacc, $date, $refnum, $loanamt, "Loan granted to employee $myEmp[fnames] $myEmp[sname].");

	if($accid>0) {
	# issue bank record
		banktrans($accid, "withdrawal", date("d-m-Y"), "$myEmp[fnames] $myEmp[sname]", "Loan granted to employee $myEmp[fnames] $myEmp[sname].", 0, $loanamt, $loan_account);
	}

	$totamount=sprint($loanperiod*$loaninstall);
	$loanint_amt = $totamount - $loanamt;

	# connect to db
	db_connect ();

	$ldate = "$lyear-$lmonth-$lday";

	pglib_transaction("BEGIN");

	$sql = "INSERT INTO emp_loanarchive (empnum, loanamt, loaninstall, loanint, loanperiod,loandate, div)
			VALUES('$empnum', '$totamount', '$loaninstall', '$loanint', '$loanperiod', CURRENT_DATE, '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to pre archive loan.");

	$loanid = pglib_lastid('emp_loanarchive', 'id');

	# write to db
	$sql = "UPDATE employees
			SET loanamt='$totamount', loanint='$loanint', loanint_amt='$loanint_amt',
				loanint_unpaid='$loanint_amt', loanperiod='$loanperiod', loaninstall='$loaninstall',
				gotloan='t'::bool, loanpayslip='$loanamt', loanfringe='$fringebenefit', loandate='$ldate',
				expacc_loan='$loan_account', loanamt_tot='$totamount', loanid='$loanid'
			WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$loanRslt = db_exec ($sql) or errDie ("Unable to add loan to system.", SELF);

	if (pg_cmdtuples ($loanRslt) < 1) {
		return "Unable to add loan to system.";
	}

	pglib_transaction("COMMIT");

	$writeLoan =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Loan granted and added to system</th></tr>
	<tr class=datacell><td>New loan has been successfully added to Cubit.</td></tr>
	</table>"
	.mkQuickLinks(
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
			$write .= "<li class=err>".$e["msg"];
		}
		$OUTPUT = $write."<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		require("../template.php");
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];


	# record the payment record
    db_connect();
	$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div) VALUES ('$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
}

?>

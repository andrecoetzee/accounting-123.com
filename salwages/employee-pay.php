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

require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT ="Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT = "Invalid.";
}

$OUTPUT.="<p>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

require("../template.php");




function enter($HTTP_VARS)
{

	extract($HTTP_VARS);

	$id += 0;

	if(!isset($paidamount)) {
		$paidamount = "0.00";
	}

	if(!isset($bankid)) {
		$bankid = 0;
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
	}


	db_conn('cubit');

	$Sl = "SELECT * FROM employees WHERE empnum='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return "Invalid employee.";
	}

	$edata = pg_fetch_array($Ri);
	
	if(!isset($bankpay))
		$bankpay = "";

	/* if we came from the cashbook, always use bank payment */
	if ($bankpay && $bankpay == "t") {
		$edata["paytype"] = "Cheque";
	} else {
		$bankpay = "f";
	}

	if ( $edata["paytype"] == "EFT" && (empty($edata["bankname"]) || empty($edata["bankaccno"]))) {
		return "Employee banking information not entered.<br>
			Click <a href='../admin-employee-edit.php?empnum=$id'>here</a> employee banking information.";
	}

	if($edata['paytype'] == "Cash") {

		$row = "<tr class='".bg_class()."'><td colspan='2'>Paid Cash</td></tr>";

	} elseif($edata['paytype'] == "Ledger Account") {

		db_conn('core');

		$Sl = "SELECT accid,accname FROM accounts ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "
			<select name='account'>
				<option value='#'>Select Account</option>";
		while($ad = pg_fetch_array($Ri)) {
			if(isset($account) && $account == $ad['accid']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
		}
		$accounts .= "</select>";

		$row = "
			<tr class='".bg_class()."'>
				<td>Ledger Account</td>
				<td>$accounts</td>
			</tr>";

	}else {
		$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$Ry = db_exec($Sl) or errDie("Unable to get bank account.");

		if (pg_numrows($Ry) < 1) {
			return "<li class='err'> There are no bank accounts found in Cubit.
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}

		$banks = "<select name='accid'>";
		while($acc = pg_fetch_array($Ry)){
			if ($acc['bankid'] == $bankid) {
				$sel = "selected";
			} else {
				$sel = "";
			}

			$banks .= "<option value='$acc[bankid]' $sel>($acc[acctype]) $acc[bankname] - $acc[accname]</option>";
		}
		$banks .= "</select>";

		$row = "
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td>$banks</td>
			</tr>";

	}

	if (!isset ($date_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}
	}

	$out = "
		<h3>Pay Employee</h3>
		<table ".TMPL_tblDflts."'>
		<form action='".SELF."' method='post'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='bankpay' value='$bankpay' />
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount due to employee</td>
				<td>".CUR." $edata[balance]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount paid now</td>
				<td><input type=text size=8 name=paidamount value='$paidamount'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			$row
			<tr><td><input type=submit name=back value='&laquo; Correction'></td><td colspan=1 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</form>
		</table>";
	return $out;

}



function confirm($HTTP_VARS)
{

	extract($HTTP_VARS);

	if(isset($back)) {
		header("Location: ../bank/cashbook-entry.php");
		exit;
	}

	$id += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid employee number.");
	if(isset($account)) {
		$v->isOk ($account, "num", 1, 20, "Invalid account.");
	}
	$v->isOk ($paidamount, "float", 1, 10, "Invalid amount.");

	$date = $date_day."-".$date_month."-".$date_year;

	$date_day += 0;
	$date_month += 0;
	$date_year += 0;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust.enter($HTTP_VARS);
	}

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM employees WHERE empnum='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return "Invalid employee.";
	}

	$edata = pg_fetch_array($Ri);
	
	/* if we came from the cashbook, always use bank payment */
	if ($bankpay && $bankpay == "t") {
		$edata["paytype"] = "Cheque";
	} else {
		$bankpay = "f";
	}

	if($edata['paytype'] == "Cash") {
		$row = "
			<tr class='".bg_class()."'>
				<td colspan='2'>Paid Cash</td>
			</tr>
			<input type='hidden' name='bankid' value='0'>";

	} elseif($edata['paytype'] == "Ledger Account") {

		db_conn('core');

		$sql = "SELECT * FROM accounts WHERE accid = '$account' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);

		$bank = pg_fetch_array($bankRslt);

		$row = "
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname]</td>
			</tr>
			<input type='hidden' name='account' value='$account'>
			<input type='hidden' name='bankid' value='0'>";

	}else {
		$accid += 0;

		$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);

		$bank = pg_fetch_array($bankRslt);

		$row = "
			<tr class='".bg_class()."'>
				<td>Bank</td>
				<td>$bank[accname]</td>
			</tr>
			<input type='hidden' name='bankid' value='$accid'>";

	}

	$paidamount = sprint($paidamount);

	$out = "
		<h3>Pay Employee</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='bankpay' value='$bankpay' />
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount due to employee</td>
				<td>".CUR." $edata[balance]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount paid now</td>
				<td><input type='hidden' name='paidamount' value='$paidamount'>".CUR." $paidamount</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			$row
			<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo;'></td></tr>
		</form>
		</table>";
	return $out;

}




function write($HTTP_VARS)
{

	extract($HTTP_VARS);

	if(isset($back)) {
		return enter($HTTP_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($id, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($bankid, "num", 1, 20, "Invalid bank number.");
	$v->isOk ($paidamount, "float", 1, 10, "Invalid amount.");

	if(isset($account)) {
		$v->isOk ($account, "num", 1, 100, "Invalid account.");
	}

	$ydate = mkdate($date_year, $date_month, $date_day);

	$v->isOk($ydate, "date", 1, 1, "Invalid payment date selected.");

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

	//$date=date("d-m-Y");

	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$cash_account= gethook("accnum", "salacc", "name", "cash");
	
	pglib_transaction("BEGIN");

	$refnum = getrefnum($ydate);

	$paidamount = sprint($paidamount);

	db_conn('cubit');

	$Sl = "SELECT * FROM employees WHERE empnum='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return "Invalid employee.";
	}

	$edata = pg_fetch_array($Ri);
	
	/* if we came from the cashbook, always use bank payment */
	if ($bankpay && $bankpay == "t") {
		$edata["paytype"] = "Cheque";
	} else {
		$bankpay = "f";
	}

	db_conn('cubit');

	$Sl = "UPDATE employees SET balance=balance-'$paidamount' WHERE empnum = '$id' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get employee details.");

// 	if($edata['paytype']=="Cash") {
//
// 		writetrans($salconacc, $cash_account, $date, $refnum, $paidamount, "Salary Payment(Cash) for employee,  $edata[fnames] $edata[sname].");
//
// 		empledger($id, $cash_account, $ydate, $refnum,"Payment(Cash)" ,  $paidamount, "d");
//
// 	}
	if($edata['paytype'] == "Ledger Account") {
		writetrans($salconacc, $account, $ydate, $refnum, $paidamount, "Salary Payment(Ledger Account) for employee,  $edata[fnames] $edata[sname].");

		empledger($id, $account, $ydate, $refnum,"Payment(Ledger Account)" ,  $paidamount, "d");
	} else {
		core_connect();
		if ($edata["paytype"] == "Cash") {
			$bank = qryAccountsName("Cash on Hand", "accid");
			$bankacc = $bank["accid"];
		} else {
			$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
			$Rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
			# check if link exists
			if(pg_numrows($Rslt) < 1){
				return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
			}

			$bank = pg_fetch_array($Rslt);
			$bankacc = $bank["accnum"];
			
			banktrans($bankid, "withdrawal", $ydate, "$edata[fnames] $edata[sname]", "Salary Payment for employee,  $edata[fnames] $edata[sname]", 0, $paidamount, $salconacc,$edata['empnum']);
		}

		writetrans($salconacc, $bankacc, $ydate, $refnum, $paidamount, "Salary Payment for employee,  $edata[fnames] $edata[sname].");

		empledger($id, $bankacc, $ydate, $refnum,"Payment(Bank)" ,  $paidamount, "d");
	}

	pglib_transaction("COMMIT");

	$out = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Payment Processed</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee payment has been processed.</td>
			</tr>
		</table>";
	return $out;

}

function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv, $empnum)
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
			bankid, trantype, date, name, descript, 
			cheqnum, amount, banked, accinv, div, 
			fcid, empnum
		) VALUES (
			'$bankacc', '$trantype', '$date', '$name', '$details', 
			'$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."', 
			(SELECT fcid FROM cubit.currency WHERE curcode='ZAR'), '$empnum'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
}


?>

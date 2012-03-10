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

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT ="Invalid";
	}
} elseif(Isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = enter($HTTP_GET_VARS);
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
	$edata['paytype'] = "Ledger Account";

	if ( $edata["paytype"] == "EFT" && (empty($edata["bankname"]) || empty($edata["bankaccno"]))) {
		return "Employee banking information not entered.<br>
			Click <a href='../admin-employee-edit.php?empnum=$id'>here</a> employee banking information.";
	}

	if($edata['paytype'] == "Cash") {

		$row = "<tr bgcolor='".bgcolorg()."'><td colspan='2'>Paid Cash</td></tr>";

	} elseif($edata['paytype'] == "Ledger Account") {

		db_conn('core');
		$Sl = "SELECT accid,accname FROM accounts ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "
			<select name='account'>
				<option value='#'>Select Account</option>";
		while($ad = pg_fetch_array($Ri)) {
			if(isb($ad['accid'])) {
				continue;
			}
			if(isset($account) && $account == $ad['accid']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
		}
		$accounts .= "</select>";

		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Ledger Account</td>
				<td>$accounts</td>
			</tr>";

	}else {

		$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$Ry = db_exec($Sl) or errDie("Unable to get bank account.");

		if(pg_numrows($Ry) < 1){
			return "<li class='err'> There are no bank accounts found in Cubit.
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}

		$banks = "<select name='accid'>";
		while($acc = pg_fetch_array($Ry)){
			if($acc['bankid'] == $bankid) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$banks .= "<option value='$acc[bankid]' $sel>$acc[accname]</option>";
		}
		$banks .= "</select>";

		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td>$banks</td>
			</tr>";

	}

	$entd = "";
	$entc = "checked=yes";
	if(isset($tran)){
		if($tran == "dt"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

	$out = "
		<h3>Employee Transaction</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='id' value='$id'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount due to employee</td>
				<td>".CUR." $edata[balance]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td><input type='text' size='8' name='paidamount' value='$paidamount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td><input type='text' size='2' name='day' maxlength='2' value='$day'>-<input type='text' size='2' name='mon' maxlength='2' value='$mon'>-<input type='text' size='4' name='year' maxlength='4' value='$year'></td>
			</tr>
			$row
			<tr bgcolor='".bgcolorg()."'>
				<td>Entry Type</td>
				<td><input type='radio' name='entry' value='DT' $entd> Debit(Decrease) | <input type='radio' name='entry' value='CT' $entc>Credit(Increase)</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td><input type='text' size='40' name='description' value=''></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}




function confirm($HTTP_VARS)
{

	extract($HTTP_VARS);

	$id += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid employee number.");

	if(isset($account)) {
		$v->isOk ($account, "num", 1, 20, "Invalid account.");
	}

	$v->isOk ($paidamount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($description, "string", 1, 100, "Invalid description.");

	$date = $day."-".$mon."-".$year;

	$day += 0;
	$mon += 0;
	$year += 0;

	if(!checkdate($mon, $day, $year)){
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

	db_conn('cubit');

	$Sl = "SELECT * FROM employees WHERE empnum='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid employee.";
	}

	$edata = pg_fetch_array($Ri);
	$edata['paytype'] = "Ledger Account";
	if($edata['paytype'] == "Cash") {

		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>Paid Cash</td>
			</tr>
			<input type='hidden' name='bankid' value='0'>";

	} elseif($edata['paytype'] == "Ledger Account") {

		db_conn('core');

		$sql = "SELECT * FROM accounts WHERE accid = '$account' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);

		$bank = pg_fetch_array($bankRslt);

		$row = "
			<tr bgcolor='".bgcolorg()."'>
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
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank</td>
				<td>$bank[accname]</td>
			</tr>
			<input type='hidden' name='bankid' value='$accid'>";

	}

	$paidamount = sprint($paidamount);



	$out = "
		<h3>Employee Transaction</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='day' value='$day'>
			<input type='hidden' name='mon' value='$mon'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='entry' value='$entry'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount due to employee</td>
				<td>".CUR." $edata[balance]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount paid now</td>
				<td><input type='hidden' name='paidamount' value='$paidamount'>".CUR." $paidamount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			$row
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td><input type='hidden' name='description' value='$description'>$description</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
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

	$date = $day."-".$mon."-".$year;
	$ydate=$year."-".$mon."-".$day;

	$day += 0;
	$mon += 0;
	$year += 0;

	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"];
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	//$date=date("d-m-Y");

	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$cash_account= gethook("accnum", "salacc", "name", "cash");

	$refnum = getrefnum($date);

	$paidamount = sprint($paidamount);

	db_conn('cubit');

	$Sl = "SELECT * FROM employees WHERE empnum='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return "Invalid employee.";
	}

	$edata = pg_fetch_array($Ri);

	$edata['paytype'] = "Ledger Account";

	if($entry != "DT") {
		$paidamount =- $paidamount;
	}

	db_conn('cubit');

	$Sl = "UPDATE employees SET balance=balance-'$paidamount' WHERE empnum = '$id' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get employee details.");

	if($entry != "DT") {
		$paidamount =- $paidamount;
	}

	if($edata['paytype'] == "Cash") {

		writetrans($salconacc, $cash_account, $date, $refnum, $paidamount, "Salary Payment(Cash) for employee,  $edata[fnames] $edata[sname].");

		empledger($id, $cash_account, $ydate, $refnum,"Payment(Cash)" ,  $paidamount, "d");

	} elseif($edata['paytype'] == "Ledger Account") {

		//print $entry;exit;
		if($entry == "DT") {

			writetrans($salconacc, $account, $date, $refnum, $paidamount, "$description  $edata[fnames] $edata[sname].");

			empledger($id, $account, $ydate, $refnum,$description ,  $paidamount, "d");

		} else {
			writetrans($account, $salconacc, $date, $refnum, $paidamount, "$description,  $edata[fnames] $edata[sname].");

			empledger($id, $account, $ydate, $refnum,$description ,  $paidamount, "c");
		}

	} else {


		core_connect();
		$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
		$Rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
		# check if link exists
		if(pg_numrows($Rslt) < 1){
			return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
		}

		$bank = pg_fetch_array($Rslt);
		$bankacc = $bank["accnum"];

		writetrans($salconacc, $bankacc, $date, $refnum, $paidamount, "Salary Payment for employee,  $edata[fnames] $edata[sname].");

		empledger($id, $bankacc, $ydate, $refnum,"Payment(Bank)" ,  $paidamount, "d");

		# issue bank record
		banktrans($bankid, "withdrawal", $date, "$edata[fnames] $edata[sname]", "Salary Payment for employee,  $edata[fnames] $edata[sname]", 0, $paidamount, $salconacc,$edata['empnum']);

	}



	$out = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Transaction Done</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction with employee has been recorded</td>
			</tr>
		</table>";
	return $out;

}



function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv,$id)
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
			bankid, trantype, date, name, descript, 
			cheqnum, amount, banked, accinv, div, fcid
		) VALUES (
			'$bankacc', '$trantype', '$date', '$name', '$details', 
			'$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."','$id'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

}



?>

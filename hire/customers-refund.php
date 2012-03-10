<?php

require ("../settings.php");
require ("../core-settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter($errors="")
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["cusnum"] = 0;
	$fields["amount"] = "0.00";
	$fields["cash_id"] = 0;
	
	extract ($fields, EXTR_SKIP);
	
	$sql = "SELECT cusnum, accno, surname FROM cubit.customers ORDER BY surname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
	
	$cust_sel = "
	<select name='cusnum' style='width: 100%' onchange='javascript:document.form.submit()'>
		<option value='0'>[None]</option>";
	while ($cust_data = pg_fetch_array($cust_rslt)) {
		if ($cusnum == $cust_data["cusnum"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}
		
		$cust_sel .= "
		<option value='$cust_data[cusnum]' $sel>
			$cust_data[surname]
		</option>";
	}
	
	$cash_out = "";
	if (!empty($cusnum)) {
		$sql = "
		SELECT id, invnum, date, cash
		FROM hire.cash
			LEFT JOIN hire.hire_invoices ON cash.invid=hire_invoices.invid
		WHERE cusnum='$cusnum'
		ORDER BY date DESC";
		$cash_rslt = db_exec($sql) or errDie("Unable to retrieve deposits.");
		
		while ($cash_data = pg_fetch_array($cash_rslt)) {
			if ($cash_id == $cash_data["id"]) {
				$sel = "checked='checked'";
			} else {
				$sel = "";
			}
		
			$cash_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='cash_id' value='$cash_data[id]' $sel /></td>
				<td>$cash_data[date]</td>
				<td>$cash_data[invnum]</td>
				<td>$cash_data[cash]</td>
			</tr>";
			
			if (empty($cash_out)) {
				$cash_out = "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='4'>
						<li>No deposits found for this customer</li>
					</td>
				</tr>";
			}
		}
	} else {
		$cash_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>
				<li>Please select a customer</li>
			</td>
		</tr>";
	}
	
	if (empty($cash_out)) {
		$cash_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>
				<li>No results found</li>
			</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Customer Refund</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors &nbsp;</td>
		</tr>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='right'>Customer</td>
			<td>$cust_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Amount</td>
			<td><input type='text' name='amount' value='$amount' style='text-align: right; width: 100%' /></td>
		</tr>
	</table>
	<h4>Deposit</h4>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>&nbsp;</th>
			<th>Date</th>
			<th>Hire No.</th>
			<th>Deposit Amount</th>
		</tr>
		$cash_out
	</table>
	<input type='submit' value='Confirm &raquo' />
	</form>
	</center>";
	
	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);
	
	if (!isset($cash_id)) {
		return enter();
	}
	
	// Sanity checks
	require_lib("validate");
	
	$v = new validate;
	$v->isOk($cusnum, "num", 1, 20, "Invalid customer selection.");
	$v->isOk($amount, "float", 1, 20, "Invalid amount.");
	$v->isOk($cash_id, "num", 1, 20, "Invalid deposit selection.");
	
	// Retrieve cash on hand account
	list($coh_acc) = array_values(qryAccountsName("Cash on Hand"));

	// Retrieve cash on hand account balance
	$sql = "
	SELECT (debit - credit) AS balance FROM core.trial_bal
	WHERE period='".PRD_DB."' AND accid='$coh_acc'";
	$bal_rslt = db_exec($sql) or errDie("Unable to retrieve cash on hand balance.");
	$coh_bal = pg_fetch_result($bal_rslt, 0);

	// See if we have enough money available in cash on hand
	if ($coh_bal < $amount) {
		$v->addError(0, "Not enough cash available in cash on hand account.");
	}
	
	// Make sure the refund amount is not more than the deposit
	if (is_numeric($cash_id)) {
		$sql = "SELECT cash FROM hire.cash WHERE id='$cash_id'";
		$deposit_rslt = db_exec($sql) or errDie("Unable to retrieve deposit.");
		$deposit_amount = pg_fetch_result($deposit_rslt, 0);
		
		if ($deposit_amount < $amount) {
			$v->addError(0, "Refund amount cannot be more than the deposit amount.");
		}
	}
	
	if ($v->isError()) {
		return enter($v->genErrors());
	}
	
	// Retrieve customer details
	$sql = "SELECT surname, accno FROM cubit.customers WHERE cusnum='$cusnum'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer details.");
	list($surname, $accno) = pg_fetch_array($cust_rslt);

	$sql = "
	SELECT id, invnum, cash.invid, date, cash
	FROM hire.cash
		LEFT JOIN hire.hire_invoices ON cash.invid=hire_invoices.invid
	WHERE id='$cash_id'";
	$cash_rslt = db_exec($sql) or errDie("Unable to retrieve deposits.");
	$cash_data = pg_fetch_array($cash_rslt);
	
	$cash_out = "
	<tr bgcolor='".bgcolorg()."'>
		<td>$cash_data[date]</td>
		<td>$cash_data[invnum]</td>
		<td>$cash_data[cash]</td>
	</tr>";

	$OUTPUT = "
	<center>
	<h3>Customer Refund</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='cusnum' value='$cusnum' />
	<input type='hidden' name='amount' value='$amount' />
	<input type='hidden' name='cash_id' value='$cash_id' />
	<input type='hidden' name='invnum' value='$cash_data[invnum]' />
	<input type='hidden' name='invid' value='$cash_data[invid]' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer</td>
			<td>$surname</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Amount</td>
			<td align='right'>".sprint($amount)."</td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Hire No.</th>
			<th>Amount</th>
		</tr>
		$cash_out
	</table>
	<input type='submit' name='key' value='&laquo Correction' />
	<input type='submit' value='Write &raquo' />
	</form>
	</center>";
	
	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	require_lib("validate");
	
	$v = new validate;
	$v->isOk($cusnum, "num", 1, 20, "Invalid customer selection.");
	$v->isOk($amount, "float", 1, 20, "Invalid amount.");
	
	// Retrieve cash on hand and customer accounts
	list($coh_acc) = array_values(qryAccountsName("Cash on Hand"));
	list($cus_acc) = array_values(qryAccountsName("Customer Control Account"));

	// Retrieve cash on hand account balance
	$sql = "
	SELECT (debit - credit) AS balance FROM core.trial_bal
	WHERE period='".PRD_DB."' AND accid='$coh_acc'";
	$bal_rslt = db_exec($sql) or errDie("Unable to retrieve cash on hand balance.");
	$coh_bal = pg_fetch_result($bal_rslt, 0);

	// See if we have enough money available in cash on hand
	if ($coh_bal < $amount) {
		$v->addError(0, "Not enough cash available in cash on hand account.");
	}

	// Make sure the refund amount is not more than the deposit
	if (is_numeric($cash_id)) {
		$sql = "SELECT cash FROM hire.cash WHERE id='$cash_id'";
		$deposit_rslt = db_exec($sql) or errDie("Unable to retrieve deposit.");
		$deposit_amount = pg_fetch_result($deposit_rslt, 0);
		
		if ($deposit_amount < $amount) {
			$v->addError(0, "Refund amount cannot be more than the deposit amount.");
		}
	}

	if ($v->isError()) {
		return enter($v->genErrors());
	}
	
	$refnum = getrefnum();
	$date = date("Y-m-d");
	
	pglib_transaction("BEGIN");
	
	$sql = "
	UPDATE cubit.customers SET balance=(balance + '$amount')
	WHERE cusnum='$cusnum'";
	db_exec($sql) or errDie("Unable to update customer balance.");
	
	// Do the reversal
	writetrans($cus_acc, $coh_acc, $date, $refnum, $amount,
		"Customer Refund for Hire No H$invnum");

	// Retrieve bank account
	$sql = "SELECT bankid FROM cubit.customers WHERE cusnum='$cusnum'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
	$bankid = pg_fetch_result($bank_rslt, 0);

	if (!$bankid) {
		$sql = "SELECT bankid FROM cubit.bankacct";
		$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank");
		$bankid = pg_fetch_result($bank_rslt, 0);
	}
	custledger($cusnum, $coh_acc, $date, $invnum,
		"Customer Refund for Hire No H$invnum", $amount, "d");

	$sql = "
	INSERT INTO cubit.stmnt (cusnum, invid, docref, amount, date, type, div)
	VALUES ('$cusnum', '$invid', '$invnum', '$amount', '$date',
		'Customer Refund for Hire No H$invnum', '".USER_DIV."')";
	db_exec($sql) or errDie("Unable to add to statement.");

	$sql = "SELECT cash FROM hire.cash WHERE id='$cash_id'";
	$cash_rslt = db_exec($sql) or errDie("Unable to retrieve cash.");
	$cash_amount = pg_fetch_result($cash_rslt, 0);
	
	if (($cash_amount - $amount) == 0) {
		$sql = "DELETE FROM hire.cash WHERE id='$cash_id'";
		db_exec($sql) or errDie("Unable to remove deposit.");
	} else {
		$sql = "UPDATE hire.cash SET cash=(cash-'$amount') WHERE id='$cash_id'";
		db_exec($sql) or errDie("Unable to update deposit.");
	}
	
	pglib_transaction("COMMIT");
	
	$OUTPUT = "
	<h3>Customer Refund</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully refunded customer.</li></td>
		</tr>
	</table>";
	
	return $OUTPUT;
}	

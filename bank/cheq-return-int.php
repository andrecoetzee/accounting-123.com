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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "bank":
			$OUTPUT = bank($_POST);
			break;
		default:
			$OUTPUT = confirm($_GET['cashid']);
	}
} else {
	# Display default output
	if(isset($_GET['cashid'])){
		$OUTPUT = confirm($_GET['cashid']);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get template
require("../template.php");



function confirm($cashid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	// Connect to database
	Db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT = "<li clss='err'>The deposit with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}
	$accnt = pg_fetch_array($accntRslt);

	$confirm = "
					<h3>Confirm Entry</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='bank'>
						<input type='hidden' name='cashid' value='$accnt[cashid]'>";

	if(strlen($accnt['accids']) > 0){
		$accinv['accname'] = "Multiple Accounts";
	}else{
		# get account name for the account involved
		$AccRslt = get("core","accname","accounts", "accid", $accnt['accinv']);
		$accinv = pg_fetch_array($AccRslt);
	}

	$AccRslt = get("cubit","*","bankacct", "bankid", $accnt['bankid']);
	$bank = pg_fetch_array($AccRslt);

	$confirm .= "
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Name</td>
							<td>$bank[bankname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Number</td>
							<td>$bank[accnum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Transaction Type</td>
							<td>$accnt[trantype]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date of Transaction</td>
							<td>$accnt[date]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Paid to/Received from</td>
							<td>$accnt[name]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Description</td>
							<td>$accnt[descript]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Amount</td>
							<td>".CUR." $accnt[amount] | $accnt[currency] $accnt[famount]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Transaction Contra Account</td>
							<td>$accinv[accname]</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='right'><input type='submit' value='Write &raquo'></td>
						</tr>
					</form>
					</table>";
	return $confirm;

}



# Write
function bank($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Reference number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get cash book record
	Db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows($accntRslt) < 1) {
		$OUTPUT = "<li clss='err'>The entry with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}
	$accnt = pg_fetch_array($accntRslt);

	$rate = sprint($accnt['amount']/$accnt['famount']);

	# Update xrate
	if($accnt['fcid'] != 0){
		cus_xrate_update($accnt['fcid'], $rate);
		xrate_update($accnt['fcid'], $rate, "invoices", "invid");
		xrate_update($accnt['fcid'], $rate, "custran", "id");
		bank_xrate_update($accnt['fcid'], $rate);
	}

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$accnt[bankid]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$bank = pg_fetch_array($rslt);

	# Date
	$sdate = date("Y-m-d");

	# If tis customer payment
	if($accnt['cusnum'] > 0){
		db_connect();

		# Get invoice Ids and Amounts
		$invids = explode("|", $accnt['rinvids']);
		$famounts = explode("|", $accnt['famounts']);
		$amounts = explode("|", $accnt['amounts']);
		$invprds = explode("|", $accnt['invprds']);
		$rages = explode("|", $accnt['rages']);

		# Return the amount that was surppose to be paid to invoices
		foreach($invids as $key => $invid){
			db_connect();
			# Skip all nulls and check existance
			if($invids[$key] > 0 && ext_ex("invoices", "invid", $invids[$key]) && $invprds[$key] != 0){
				db_connect();
				$sql = "UPDATE invoices SET balance = (balance + '$amounts[$key]'::numeric(13,2)), fbalance = (fbalance + '$famounts[$key]'::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
			}elseif($invids[$key] > 0 && ext_ex("nons_invoices", "invid", $invids[$key]) && $invprds[$key] == 0){
				db_connect();
				$sql = "UPDATE nons_invoices SET balance = (balance + '$amounts[$key]'::numeric(13,2)), fbalance = (fbalance + '$famounts[$key]'::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
				frecordDTA($famounts[$key], $accnt['cusnum'], $rate, $rages[$key], $accnt['fcid']);
				// custDTA($amounts[$key], $accnt['cusnum'], $rages[$key]);
			}elseif($invids[$key] > 0){
				db_conn($invprds[$key]);
				# check if invoice exitsts on prd
				if(ext_ex("invoices", "invid", $invids[$key])){
					# if found, Move the invoice back
					if(moveback($invids[$key], $invprds[$key], $famounts[$key], $amounts[$key])){
					}
				}
			}
		}

		# Begin updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			db_connect();
			# Update the customer (make balance more)
			$sql = "UPDATE customers SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE cusnum = '$accnt[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$sql = "UPDATE bankacct SET fbalance = (fbalance - '$accnt[famount]'::numeric(13,2)), balance = (balance - '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# Record the transaction on the statement
			$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div, allocation_date) VALUES('$accnt[cusnum]', '0', '$accnt[famount]','$sdate', 'Cheque/Payment for Invoices Returned.', '".USER_DIV."', '$accnt[date]')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Delete cashbook ID
			$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

			copyEntry($cashid);

			if($accnt['lcashid'] > 0){
				// Connect to database
				db_Connect ();
				$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
				$laccnt = pg_fetch_array($laccntRslt);

				$sql = "UPDATE bankacct SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				// Delete cashbook ID
				$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
			}

		# Commit updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

		# Make ledge record
		custledger($accnt['cusnum'], $bank['accnum'], $sdate, "cancel", "Payment for Invoices Returned.", $accnt['amount'], "d");

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");
		# debit customer account, credit bank account (customer takes money back)
		writetrans($accnt['accinv'], $bank['accnum'], $date, $refnum, $accnt['amount'], $descript);

	}elseif($accnt['supid'] > 0){
		db_connect();
		# Begin updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$ids = explode("|", $accnt['ids']);
			$purids = explode("|", $accnt['purids']);
			$fpamounts = explode("|", $accnt['fpamounts']);
			$pamounts = explode("|", $accnt['pamounts']);
			$pdates = explode("|", $accnt['pdates']);
			if(count($ids) > 0){
				foreach($ids as $key => $vale){
					if($ids[$key] > 0){
						rerecord($ids[$key], $accnt['supid'], $purids[$key], $fpamounts[$key], $pamounts[$key], $pdates[$key]);
					}
				}
			}
			# if the amount was overpaid
			if(array_sum($fpamounts) < $accnt['famount']){
				# get and record amount that was overpaid to balance the equation
				$rem = ($accnt['famount'] - array_sum($fpamounts));
				rerecord('0', $accnt['supid'], '0', $rem, 0,$accnt['date']);
			}

			# Update the supplier (make balance more)
			$sql = "UPDATE suppliers SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE supid = '$accnt[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$sql = "UPDATE bankacct SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# Record the payment on the statement
			$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, ref, descript, amount, div) VALUES('$accnt[supid]', '$sdate', '$bank[accnum]', '$accnt[cheqnum]', 'Cheque/Payment to Supplier Returned.', '$accnt[famount]', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

			# Delete cashbook ID
			$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

			copyEntry($cashid);

			if($accnt['lcashid'] > 0){
				// Connect to database
				db_Connect ();
				$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
				$laccnt = pg_fetch_array($laccntRslt);

				$sql = "UPDATE bankacct SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				// Delete cashbook ID
				$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
			}

		# Commit updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

		suppledger($accnt['supid'], $bank['accnum'], $sdate, $accnt['cheqnum'], "Payment to Supplier Returned", $accnt['amount'], "c");
		db_connect();

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");
		# debit bank, credit supplier account
		writetrans($bank['accnum'], $accnt['accinv'], $date, $refnum, $accnt['amount'], $descript);
	}elseif($accnt['suprec'] > 0){
		db_connect();
		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$accnt[suprec]','-$accnt[amount]','$accnt[date]', 'Receipt Returned','$accnt[cheqnum]','0', '".USER_DIV."')";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$accnt[amount]'::numeric(13,2)) WHERE supid = '$accnt[suprec]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		suppDT($accnt['amount'], $accnt['suprec']);

		db_connect();
		# Delete cashbook ID
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

		copyEntry($cashid);

		$sql = "UPDATE bankacct SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		if($accnt['lcashid'] > 0){
			# Delete cashbook ID
			$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
		}

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");
		# debit bank, credit supplier account
		writetrans($bank['accnum'], $accnt['accinv'], $date, $refnum, $accnt['amount'], $descript);

	}elseif(strlen($accnt['accids']) > 0){
		/* -- Start Hooks -- */

			$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		/* -- End Hooks -- */

		multican($accnt, $bank, $vatacc);
	}else{
		$amount = $accnt['amount'];
		$vat = $accnt['vat'];
		$chrgvat = $accnt['chrgvat'];

		$amount -= $vat;

		/* -- Start Hooks -- */

			$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		/* -- End Hooks -- */

		db_connect();
		# Delete cashbook ID
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

		copyEntry($cashid);

		if($accnt['trantype'] == "deposit"){
			$sql = "UPDATE bankacct SET fbalance = (fbalance - '$accnt[famount]'::numeric(13,2)), balance = (balance - '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		}else{
			$sql = "UPDATE bankacct SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		}

		/* ---- the Others ---- */
		if($accnt['lcashid'] > 0){
			//Connect to database
			db_Connect ();
			$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
			$laccnt = pg_fetch_array($laccntRslt);

			if($laccnt['trantype'] == "deposit"){
				$sql = "UPDATE bankacct SET fbalance = (fbalance - '$laccnt[famount]'::numeric(13,2)), balance = (balance - '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}else{
				$sql = "UPDATE bankacct SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}
			# Delete cashbook ID
			$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
		}
		/* ---- End the Others ---- */
		
		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");

		if($accnt['trantype'] == "deposit"){
			# DT(account involved), CT(bank)
			writetrans($accnt['accinv'], $bank['accnum'], $date, $refnum, $amount, $descript);
			if($vat <> 0){
				# DT(Vat), CT(Bank)
				writetrans($vatacc, $bank['accnum'], $date, $refnum, $vat, $descript);
			}
			$cc_trantype = cc_TranTypeAcc($accnt['accinv'], $bank['accnum']);
		}else{
			# DT(bank), CT(account invoilved)
			writetrans($bank['accnum'], $accnt['accinv'], $date, $refnum, $amount, $descript);
			if($vat <> 0){
				# DT(Vat), CT(Bank)
				writetrans($bank['accnum'], $vatacc, $date, $refnum, $vat, $descript);
			}
			$cc_trantype = cc_TranTypeAcc($bank['accnum'], $accnt['accinv']);
		}
	}

	if(isset($cc_trantype) && $cc_trantype != false){
		$cc = "<script> CostCenter('$cc_trantype', 'Returned, Unpaid Bank Transaction', '$date', '$descript', '".($accnt['amount'] - $accnt['vat'])."', '../'); </script>";
	}else{
		$cc = "";
	}

	# Status report
	$bank = "
				$cc
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Cash Book</th>
					</tr>
					<tr class='datacell'>
						<td>Cash Book Entry was successfully canceled .</td>
					</tr>
				</table>";

	# Main table (layout with menu)
	$OUTPUT = "
				<center>
				<table width='90%'>
					<tr valign='top'>
						<td width='60%'>$bank</td>
						<td align='center'>
							<table ".TMPL_tblDflts." width='80%'>
								<tr>
									<th>Quick Navigation</th>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='cashbook-view.php'>View Cash Book</td>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='../reporting/not-banked.php'>View Outstanding Cash Book Entries</td>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='bank-pay-add.php'>Add bank Payment</td>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='bank-recpt-add.php'>Add Bank Receipt</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>";
	return $OUTPUT;

}



# Cancel multiple Transactions
function multican($accnt, $bank, $vatacc){

	$accids = explode("|", $accnt['accids']);
	$amounts = explode("|", $accnt['amounts']);
	$vats = explode("|", $accnt['vats']);
	$chrgvats = explode("|", $accnt['chrgvats']);
	$refnum = getrefnum();
	$descript = $accnt['descript']." Returned, Unpaid";
	$date = date("Y-m-d");

	foreach($amounts as $key => $amount){
		# SQL Array Rule: Thou shalt skip Zero Reference
		if($key < 1)
			continue;

		$accid = $accids[$key];
		$vat = $vats[$key];
		$chrgvat = $chrgvats[$key];
		$amount -= $vat;

		if($accnt['trantype'] == "deposit"){
			# DT(account involved), CT(bank)
			writetrans($accid, $bank['accnum'], $date, $refnum, $amount, $descript);

			if($vat <> 0){
				# DT(Vat), CT(Bank)
				writetrans($vatacc, $bank['accnum'], $date, $refnum, $vat, $descript);
			}
		}else{
			# DT(bank), CT(account invoilved)
			writetrans($bank['accnum'], $accid, $date, $refnum, $amount, $descript);

			if($vat <> 0){
				# DT(Vat), CT(Bank)
				writetrans($bank['accnum'], $vatacc, $date, $refnum, $vat, $descript);
			}
		}
	}

	db_connect();
	# Delete cashbook ID
	$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$accnt[cashid]' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

	copyEntry($accnt['cashid']);

	if($accnt['lcashid'] > 0){
		# Delete cashbook ID
		$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
	}
}



# Record
function rerecord($id, $supid, $purid, $famount, $amount, $date)
{
	db_connect();
	if(ext_ex("suppurch", "id", $id)){
		# Remove make amount less
		$sql = "UPDATE suppurch SET fbalance = (fbalance + '$famount'::numeric(13,2)), balance = (balance + '$amount'::numeric(13,2)) WHERE id = '$id' AND div = '".USER_DIV."'";
		$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	}else{
		/* Make transaction record for age analysis */
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fbalance, div) VALUES('$supid', '$purid', '$date', '$amount', '$famount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}
}



function moveback($invid, $prd, $famount, $amount){

	/* start moving invoice */

	# Move back invoices that are fully paid
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to get finished Invoice information in Cubit.",SELF);

	# false if not found
	if(pg_numrows($invbRslt) < 1){
		return false;
	}

	while($invb = pg_fetch_array($invbRslt)){
		db_connect();
		# Insert invoice to cubit DB
		$sql = "INSERT INTO invoices(invid, invnum, deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, fcid, currency, xrate, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, fbalance, balance, location, prd, age, comm, discount, delivery, printed, done, docref, div)";
		$sql .= " VALUES('$invb[invid]','$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', '$invb[fcid]', '$invb[currency]', '$invb[xrate]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , '$invb[total]', '$famount', '$amount', '$invb[location]', '$prd', '$invb[age]', '$invb[comm]', '$invb[discount]', '$invb[delivery]', 'y', 'y', '$invb[docref]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		# get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT * FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			db_connect();
			# insert invoice items into cubit Db
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invb[invid]', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_conn($prd);
		# Remove those invoices from prd DB
		$sql = "DELETE FROM invoices WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information on Cubit.",SELF);

		# Remove those invoice items from prd DB
		$sql = "DELETE FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	}

	/* end moving invoices */
	return true;
}



function copyEntry($cashid){
	# Get cash book record
	Db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database11.", SELF);
	$accnt_cp = pg_fetch_array($accntRslt);
	if($accnt_cp['trantype'] == "deposit"){
		$trantype = "withdrawal";
	}else{
		$trantype = "deposit";
	}
	$sql  = "INSERT INTO cashbook(trantype, bankid, date, name, descript, cheqnum, amount, banked, accinv, lnk, supid, cusnum, rinvids, amounts, invprds, ids, purids, pamounts, pdates, div, accids, suprec, vat, chrgvat, vats, chrgvats, rages, famount, fpamounts, famounts, lcashid, fcid, currency, location, opt)
	VALUES('$trantype', '$accnt_cp[bankid]', '$accnt_cp[date]', '$accnt_cp[name]', '$accnt_cp[descript] Returned, Unpaid', '$accnt_cp[cheqnum]', '$accnt_cp[amount]', '$accnt_cp[banked]', '$accnt_cp[accinv]', '$accnt_cp[lnk]', '$accnt_cp[supid]', '$accnt_cp[cusnum]', '$accnt_cp[rinvids]', '$accnt_cp[amounts]', '$accnt_cp[invprds]', '$accnt_cp[ids]', '$accnt_cp[purids]', '$accnt_cp[pamounts]', '$accnt_cp[pdates]', '$accnt_cp[div]', '$accnt_cp[accids]', '$accnt_cp[suprec]', '$accnt_cp[vat]', '$accnt_cp[chrgvat]', '$accnt_cp[vats]', '$accnt_cp[chrgvats]', '$accnt_cp[rages]', '$accnt_cp[famount]', '$accnt_cp[fpamounts]', '$accnt_cp[famounts]', '$accnt_cp[lcashid]', '$accnt_cp[fcid]', '$accnt_cp[currency]', '$accnt_cp[location]', 'n')";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to insert cashbook entry details to database11.", SELF);
}



# records for CT
function frecordDTA($amount, $cusnum, $rate, $age, $fcid)
{
	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND fbalance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
  		while($dat = pg_fetch_array($rs)){
			$lamount = ($amount * $rate);
   			if(floatval($amount) > 0){
				if($dat['fbalance'] < $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET fbalance = (fbalance + '$amount'::numeric(13,2)), balance = (balance + '$lamount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0 ;
				}else{
					# remove small ones
					// if($dat['fbalance'] > $amount){
						$amount -= $dat['fbalance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					//}
				}
			}
		}
		if($amount > 0){
			$lamount = ($amount * $rate);

			/* Make transaction record for age analysis */
			$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, age, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '$age', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$lamount = ($amount * $rate);

		/* Make transaction record for age analysis */
		$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, age, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '$age', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}


?>
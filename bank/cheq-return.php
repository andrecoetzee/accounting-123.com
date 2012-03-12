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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "bank":
			$OUTPUT = bank($_POST);
			break;
		default:
			$OUTPUT = confirm($_POST, $_POST['cashid']);
	}
} else {
	# Display default output
	if(isset($_GET['cashid'])){
		$OUTPUT = confirm($_GET, $_GET['cashid']);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of mudule</li>";
	}
}

# get template
require("../template.php");




function confirm($VARS, $cashid)
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
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.1", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT = "<li clss='err'>The deposit with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}
	$accnt = pg_fetch_array($accntRslt);

	array_merge($accnt, $VARS);
	extract($accnt);

	if (!isset($date_day)) {
		$date_day = extractDay($date);
	}

	if (!isset($date_month)) {
		$date_month = extractMonth($date);
	}

	if (!isset($date_year)) {
		$date_year = extractYear($date);
	}

	$AccRslt = get("cubit","*","bankacct", "bankid", $accnt['bankid']);
	$bank = pg_fetch_array($AccRslt);

	if($accnt['location'] == "int" || $bank['btype'] == 'int')
		header("Location: cheq-return-int.php?cashid=$cashid");

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

	$accnt['amount'] = sprint ($accnt['amount']);

	$confirm .= "
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Bank Name</td>
		<td>$bank[bankname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account Number</td>
		<td>$bank[accnum]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Transaction Type</td>
		<td>$accnt[trantype]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Date of Transaction</td>
		<td>$accnt[date]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Date of Return</td>
		<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Paid to/Received from</td>
		<td>$accnt[name]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Description</td>
		<td>$accnt[descript]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Amount</td>
		<td>".CUR." $accnt[amount]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Transaction Contra Account</td>
		<td>$accinv[accname]</td>
	</tr>
	<tr><td><br></td></tr>
	<tr>
		<td></td>
		<td align='right'><input type='submit' value='Write &raquo'></td>
	</tr>
	</form></table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
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
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

	$v->isOk("$date_day$date_month$date_year", "num", 6, 8, "Invalid date selected.");

	if (!checkdate($date_month, $date_day, $date_year)) {
		$v->addError("", "Invalid date selected. No such date possible.");
	}

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



	$seldate = "$date_year-$date_month-$date_day";

	$salconacc = gethook("accnum", "salacc", "name", "salaries control");

	# Get cash book record
	Db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database11.", SELF);
	if (pg_numrows($accntRslt) < 1) {
		$OUTPUT = "<li clss='err'>The entry with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}
	$accnt = pg_fetch_array($accntRslt);

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

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);



	# If tis customer payment
	if (($accnt['cusnum'] > 0 || $accnt["multicusnum"] != "") && strlen($accnt['rinvids']) > 0) {

		db_connect();

		# Get invoice Ids and Amounts
		$invids = explode("|", $accnt['rinvids']);
		$amounts = explode("|", $accnt['amounts']);
		$invprds = explode("|", $accnt['invprds']);
		$rages = explode("|", $accnt['rages']);
		
		if ($accnt["multicusnum"] != "") {
			$cusnums = explode(",", $accnt["multicusnum"]);
			$cusamts = explode(",", $accnt["multicusamt"]);
		} else {
			$cusnums = array($accnt["cusnum"]);
			$cusamts = array($accnt["amount"]);
		}

		$oa = 0;

		# Return the amount that was surppose to be paid to invoices
		foreach($invids as $key => $invid) {
			if ($invids[$key] <= 0) {
				continue;
			}
			
			db_connect();
			if(ext_ex("invoices", "invid", $invids[$key]) && $invprds[$key] != 0){
				db_connect();
				$sql = "
					UPDATE invoices 
					SET balance = (balance + '$amounts[$key]'::numeric(13,2)) 
					WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				if(open()) {
					$sql = "SELECT invnum FROM invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$idata = pg_fetch_array($payRslt);

					$sql = "
						UPDATE open_stmnt 
						SET balance = (balance + '$amounts[$key]'::numeric(13,2)) 
						WHERE invid = '$idata[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$oa = $oa-$amounts[$key];
				}
			} else if (ext_ex("nons_invoices", "invid", $invids[$key]) && $invprds[$key] == 0) {

				db_connect();
				$sql = "
					UPDATE nons_invoices 
					SET balance = (balance + '$amounts[$key]'::numeric(13,2)) 
					WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
				/*$Sll="SELECT sdate FROM nons_invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$Rii=db_exec($Sll) or errDie("Unable to get invoice data.");
				$dii=pg_fetch_array($Rii);*/

				$cnsql = "SELECT cusid FROM cubit.nons_invoices WHERE invid='$invids[$key]'";
				$cnrslt = db_exec($cnsql) or errDie("Error reading customer info from nonstock invoice.");

				$invcusid = pg_fetch_result($cnrslt, 0, 0);
				custDTA($amounts[$key], $invcusid, $rages[$key], $seldate);
			} else if ($invprds[$key] != 0 && ext_ex("pinvoices", "invid", $invids[$key], $invprds[$key])) {
				$sql = "
					UPDATE \"$invprds[$key]\".pinvoices 
					SET balance = (balance + '$amounts[$key]'::numeric(13,2)) 
					WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				$sql = "SELECT cusnum, balance FROM \"$invprds[$key]\".pinvoices WHERE invid='$invids[$key]'";
				$rslt = db_exec($sql) or errDie("Error reading customer info from nonstock invoice.");
				$invcusid = pg_fetch_result($rslt, 0, 0);
				
				custDTA($amounts[$key], $invcusid, $rages[$key], $seldate);
			} else if ($invprds[$key] > 0) {
				if(open()) {
					db_conn($invprds[$key]);

					$sql = "SELECT invnum FROM  invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$idata = pg_fetch_array($payRslt);

					db_conn('cubit');

					$sql = "
						UPDATE open_stmnt 
						SET balance = (balance + '$amounts[$key]'::numeric(13,2)) 
						WHERE invid = '$idata[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$oa = $oa - $amounts[$key];
				}

				db_conn($invprds[$key]);
				# check if invoice exitsts on prd
				if(ext_ex("invoices", "invid", $invids[$key])){
					# if found, Move the invoice back
					if(moveback($invids[$key], $invprds[$key], $amounts[$key])){
					}
				}
			}
		}

		foreach ($cusnums as $cuskey => $cusnum) {
			$accnt["cusnum"] = $cusnum;
			
			$cusamt = $cusamts[$cuskey];

			db_connect();
			# Update the customer (make balance more)
			$sql = "UPDATE customers SET balance = (balance + '$cusamt'::numeric(13,2)) 
					WHERE cusnum = '$accnt[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit1.",SELF);

			# Record the transaction on the statement
			$sql = "
				INSERT INTO stmnt (
					cusnum, invid, amount, date, type, 
					div, allocation_date
				) VALUES (
					'$accnt[cusnum]', '0', '$cusamt', '$seldate', 'Cheque/Payment for Invoices Returned.', 
					'".USER_DIV."', '$accnt[date]'
				)";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			if(sprint($accnt['amount'] + $oa) > 0) {

				# Record the transaction on the statement
				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, date, 
						type, div, balance
					) VALUES (
						'$accnt[cusnum]', '0', '".sprint($accnt['amount']+$oa)."', '$seldate', 
						'Cheque/Payment for Invoices Returned.', '".USER_DIV."', '$cusamt'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			}
		}

		# Delete cashbook ID
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

		copyEntry($cashid);

		if($accnt['lcashid'] > 0){
			// Connect to database
			db_Connect ();
			$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.2", SELF);
			$laccnt = pg_fetch_array($laccntRslt);

			$sql = "
				UPDATE bankacct 
				SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) 
				WHERE bankid = '$laccnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit2.",SELF);

			# Delete cashbook ID
			$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
		}

		# Make ledge record
//		custledger($accnt['cusnum'], $bank['accnum'], $accnt['date'], "cancel", "Payment Returned.", $accnt['amount'], "d");
		foreach ($cusnums as $cuskey => $cusnum) {
			$cusamt = $cusamts[$cuskey];
			custledger($cusnum, $bank['accnum'], $seldate, "cancel", "Payment Returned.", $cusamt, "d");
		}

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");

		# debit customer account, credit bank account (customer takes money back)
//		writetrans($accnt['accinv'], $bank['accnum'], $accnt['date'], $refnum, $accnt['amount'], $descript);
		writetrans($accnt['accinv'], $bank['accnum'], $seldate, $refnum, $accnt['amount'], $descript);

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		if($accnt['vat'] <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $bank['accnum'], $accnt['date'], $accnt['reference'], $vat, $accnt['descript']);
		}

	} else if (($accnt['cusnum'] > 0 || $accnt["multicusnum"] != "") && $accnt['trantype'] != "withdrawal") {

		$refnum = getrefnum();
		$date = date("Y-m-d");

//		recordDT($accnt['amount'], $accnt['cusnum']);
		recordCT($accnt['amount'], $accnt['cusnum']);

		if ($accnt["multicusnum"] != "") {
			$cusnums = explode(",", $accnt["multicusnum"]);
			$cusamts = explode(",", $accnt["multicusamt"]);
		} else {
			$cusnums = array($accnt["cusnum"]);
			$cusamts = array($accnt["amount"]);
		}


		db_connect();

		foreach ($cusnums as $cuskey => $cusnum) {
			$accnt["cusnum"] = $cusnum;
			
			$cusamt = $cusamts[$cuskey];

			# receipt from customer returned
			$sql = "
					INSERT INTO stmnt 
						(cusnum, invid, amount, date, type, st, div, allocation_date) 
					VALUES 
						('$accnt[cusnum]', '0', '$cusamt', '$seldate', 'Cheque/Payment returned', 'n', '".USER_DIV."', '$accnt[date]')";
			$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

			$sql = "INSERT INTO open_stmnt(cusnum, invid, amount, date, type, st, div,balance) VALUES('$accnt[cusnum]', '0', '$cusamt', '$seldate', '$accnt[descript], Cheque/Payment returned', 'n', '".USER_DIV."','$cusamt')";
			$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

			# update the customer (make balance more)
			$sql = "UPDATE customers SET balance = (balance + '$cusamt') WHERE cusnum = '$accnt[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

		}

		copyEntry($cashid);

		foreach ($cusnums as $cuskey => $cusnum) {
			$cusamt = $cusamts[$cuskey];
			# Make ledge record
	//		custledger($accnt['cusnum'], $bank['accnum'], $accnt['date'], $refnum, "Cheque/Payment returned.", $accnt['amount'], "c");
			custledger($cusnum, $bank['accnum'], $seldate, $refnum, "Cheque/Payment returned.", $cusamt, "d");
		}

		db_conn('cubit');
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

//		writetrans ($accnt['accinv'],$bank['accnum'], $accnt['date'], $refnum, $accnt['amount'], "Cheque/Payment returned.$accnt[descript]");
		writetrans ($accnt['accinv'],$bank['accnum'], $seldate, $refnum, $accnt['amount'], "Cheque/Payment returned.$accnt[descript]");

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		if($accnt['vat'] <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $bank['accnum'], $accnt['date'], $accnt['reference'], $vat, $accnt['descript']);
		}

	} elseif($accnt['cusnum'] > 0) {

		$refnum = getrefnum();
		$date = date("Y-m-d");

		recordCT($accnt['amount'], $accnt['cusnum']);

		db_connect();
		# receipt from customer returned
		$sql = "
				INSERT INTO stmnt 
					(cusnum, invid, amount, date, type, st, div, allocation_date) 
				VALUES 
					('$accnt[cusnum]', '0', '-$accnt[amount]', '$seldate', 'Cheque/Payment returned', 'n', '".USER_DIV."', '$accnt[date]')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		$sql = "INSERT INTO open_stmnt(cusnum, invid, amount, date, type, st, div,balance) VALUES('$accnt[cusnum]', '0', '-$accnt[amount]', '$seldate', '$accnt[descript], Cheque/Payment returned', 'n', '".USER_DIV."','-$accnt[amount]')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance - '$accnt[amount]') WHERE cusnum = '$accnt[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

		copyEntry($cashid);

		# Make ledge record
		custledger($accnt['cusnum'], $bank['accnum'], $accnt['date'], $refnum, "Cheque/Payment returned.", $accnt['amount'], "c");

		db_conn('cubit');
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

//		writetrans ($bank['accnum'],$accnt['accinv'], $date, $refnum, $accnt['amount'], "Cheque/Payment returned.$accnt[descript]");
		writetrans( $bank['accnum'], $accnt['accinv'], $accnt['date'], $refnum, $accnt['amount'], "Cheque/Payment returned.$accnt[descript]");

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		if($accnt['vat'] <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $bank['accnum'], $accnt['date'], $accnt['reference'], $vat, $accnt['descript']);
		}

	} elseif($accnt['supid'] > 0) {

		db_connect();

			$ids = explode("|", $accnt['ids']);
			$purids = explode("|", $accnt['purids']);
			$pamounts = explode("|", $accnt['pamounts']);
			$pdates = explode("|", $accnt['pdates']);
			if(count($ids) > 0){
				foreach($ids as $key => $vale){
					if($ids[$key] > 0){
						rerecord($ids[$key], $accnt['supid'], $purids[$key], $pamounts[$key], $pdates[$key]);
					}
				}
			}
			# if the amount was overpaid
			if(array_sum($pamounts) < $accnt['amount']){
				# get and record amount that was overpaid to balance the equation
				$rem = ($accnt['amount'] - array_sum($pamounts));
				rerecord('0', $accnt['supid'], '0', $rem, $accnt['date']);
			}

			# Update the supplier (make balance more)
			$sql = "UPDATE suppliers SET balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE supid = '$accnt[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit3.",SELF);

			# Record the payment on the statement
			$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, ref, descript, amount, div) VALUES('$accnt[supid]', '$seldate', '$bank[accnum]', '$accnt[cheqnum]', 'Cheque/Payment to Supplier Returned.', '$accnt[amount]', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

			# Delete cashbook ID
			$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

			copyEntry($cashid);

			if($accnt['lcashid'] > 0){
				// Connect to database
				db_Connect ();
				$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database3.", SELF);
				$laccnt = pg_fetch_array($laccntRslt);

				$sql = "UPDATE bankacct SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.4",SELF);

				# Delete cashbook ID
				$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
				$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
			}

		suppledger($accnt['supid'], $bank['accnum'], $accnt['date'], $accnt['cheqnum'], "Payment to Supplier Returned", $accnt['amount'], "c");
		db_connect();

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");
		# debit bank, credit supplier account
		writetrans($bank['accnum'], $accnt['accinv'], $accnt['date'], $refnum, $accnt['amount'], $descript);

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		if($accnt['vat'] <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $bank['accnum'], $accnt['date'], $accnt['reference'], $vat, $accnt['descript']);
		}
	} elseif($accnt['suprec'] > 0) {

		db_connect();
		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$accnt[suprec]','-$accnt[amount]','$accnt[date]', 'Receipt Returned','$accnt[cheqnum]','0', '".USER_DIV."')";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$accnt[amount]'::numeric(13,2)) WHERE supid = '$accnt[suprec]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.5",SELF);

		suppDT($accnt['amount'], $accnt['suprec']);

		suppledger($accnt['suprec'], $bank['accnum'], $accnt['date'], $accnt['cheqnum'], "Receipt from Supplier Returned", $accnt['amount'], "d");

		db_connect();
		# Delete cashbook ID
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

		copyEntry($cashid);

		if($accnt['lcashid'] > 0){
			# Delete cashbook ID
			$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
		}

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");
		# debit bank, credit supplier account
		writetrans( $accnt['accinv'], $bank['accnum'],$accnt['date'], $refnum, $accnt['amount'], $descript);

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		if($accnt['vat'] <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $bank['accnum'], $accnt['date'], $accnt['reference'], $vat, $accnt['descript']);
		}
	} else if (($accnt["empnum"] != "0" && strlen($accnt["empnum"]) > 0) && $accnt['trantype'] == "withdrawal") {

		$refnum = getrefnum();
		$date = date("Y-m-d");
		
		$sql = "UPDATE cubit.employees SET balance = balance + '$accnt[amount]' 
				WHERE empnum='$accnt[empnum]' AND div = '".USER_DIV."'";
		db_exec($sql) or errDie("Unable to get employee details.");
		
		$sql = "SELECT fnames,sname FROM cubit.employees WHERE empnum='$accnt[empnum]'";
		$rslt = db_exec($sql);
		$empinfo = pg_fetch_array($rslt);
		$empname = "$empinfo[fnames] $empinfo[sname]";

		copyEntry($cashid);

		empledger($accnt["empnum"], $bank['accnum'], $accnt["date"], $refnum, "Cheque/Payment Returned" ,  $accnt['amount'], "c");

		db_conn('cubit');
		$sql = "UPDATE cashbook SET opt = 'n' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

		writetrans($bank['accnum'], $accnt['accinv'], $accnt['date'], $refnum, $accnt['amount'], "Cheque/Payment returned for $empname");
	} elseif(strlen($accnt['accids']) > 0) {

		/* -- Start Hooks -- */
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		/* -- End Hooks -- */
		
		multican($accnt, $bank, $vatacc, $accnt['vatcode']);
	} else {

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
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.5",SELF);
		}else{
			$sql = "UPDATE bankacct SET fbalance = (fbalance + '$accnt[famount]'::numeric(13,2)), balance = (balance + '$accnt[amount]'::numeric(13,2)) WHERE bankid = '$accnt[bankid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.6",SELF);
		}

		/* ---- the Others ---- */
		if($accnt['lcashid'] > 0){
			//Connect to database
			db_Connect ();
			$sql = "SELECT * FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$laccntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.4", SELF);
			$laccnt = pg_fetch_array($laccntRslt);

			if($laccnt['trantype'] == "deposit"){
				$sql = "UPDATE bankacct SET fbalance = (fbalance - '$laccnt[famount]'::numeric(13,2)), balance = (balance - '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.7",SELF);
			}else{
				$sql = "UPDATE bankacct SET fbalance = (fbalance + '$laccnt[famount]'::numeric(13,2)), balance = (balance + '$laccnt[amount]'::numeric(13,2)) WHERE bankid = '$laccnt[bankid]'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.8",SELF);
			}
			# Delete cashbook ID
			$sql = "DELETE FROM cashbook WHERE cashid = '$accnt[lcashid]' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);
		/* ---- End the Others ---- */
		}

		$descript = $accnt['descript']." Returned, Unpaid";
		$refnum = getrefnum();
		$date = date("Y-m-d");

		if ($accnt['trantype'] == "deposit") {
			$vatacc = gethook("accnum", "salesacc", "name", "VAT","a");
			# DT(account involved), CT(bank)
//			writetrans($accnt['accinv'], $bank['accnum'], $accnt['date'], $refnum, $amount, $descript);
			writetrans($accnt['accinv'], $bank['accnum'], $seldate, $refnum, $amount, $descript);
			if($vat <> 0){
				# DT(Vat), CT(Bank)
				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes WHERE id='$accnt[vatcode]'";
				$Ri=db_exec($Sl);
				$vd=pg_fetch_array($Ri);

//				vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,$descript,-($amount+$vat),-$vat);
				vatr($vd['id'],$seldate,"OUTPUT",$vd['code'],$refnum,$descript,-($amount+$vat),-$vat);
//				writetrans($vatacc, $bank['accnum'], $accnt['date'], $refnum, $vat, $descript);
				writetrans($vatacc, $bank['accnum'], $seldate, $refnum, $vat, $descript);
			}
			$cc_trantype = cc_TranTypeAcc($accnt['accinv'], $bank['accnum']);
		} else {
			# DT(bank), CT(account invoilved)
//			writetrans($bank['accnum'], $accnt['accinv'], $accnt['date'], $refnum, $amount, $descript);
			writetrans($bank['accnum'], $accnt['accinv'], $seldate, $refnum, $amount, $descript);
			if($vat <> 0){
				# DT(Vat), CT(Bank)
				db_conn('cubit');
				$Sl = "SELECT * FROM vatcodes WHERE id='$accnt[vatcode]'";
				$Ri = db_exec($Sl);
				$vd = pg_fetch_array($Ri);

//				vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descript,($amount+$vat),$vat);
				vatr($vd['id'],$seldate,"INPUT",$vd['code'],$refnum,$descript,($amount+$vat),$vat);
//				writetrans($bank['accnum'], $vatacc, $accnt['date'], $refnum, $vat, $descript);
				writetrans($bank['accnum'], $vatacc, $seldate, $refnum, $vat, $descript);

			}
			$cc_trantype = cc_TranTypeAcc($bank['accnum'], $accnt['accinv']);
		}
		
		/* stock purchase/sale */
		if (!empty($accnt["stkinfo"])) {
			list($si_stkid, $si_unitnum, $si_cost, $si_vat) = explode("|", $accnt["stkinfo"]);
			
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$stk = pg_fetch_array($stkRslt);
			
			if ($accnt['trantype'] == "deposit") {
				db_connect();
				$sql = "UPDATE stock SET csamt = (csamt + '$si_cost'), 
							units = (units + '$si_unitnum') 
						WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		
				stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $seldate, $si_unitnum, $si_cost, "Returned receipt for: $accnt[descript]");
				
				db_connect();
				$cspric = sprint($si_cost/$si_unitnum);
				$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
						VALUES('$seldate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'inc', '$si_unitnum', '$si_cost', '$cspric', 'Returned receipt for: $accnt[descript]', '".USER_DIV."')";
				$recRslt = db_exec($sql);
		
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
				$stk = pg_fetch_array($stkRslt);
		
				if($stk['units'] <> 0){
					$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}else{
					$csprice = sprint($si_cost/$si_unitnum);
					$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}
			} else {
				db_connect();
				$sql = "UPDATE stock SET csamt = (csamt - $si_cost), 
							units = (units - '$si_unitnum') 
						WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		
				stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $seldate, $si_unitnum, $si_cost, "Returned payment for: $accnt[descript]");
				
				db_connect();
				$cspric = sprint($si_cost/$si_unitnum);
				$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
						VALUES('$seldate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'dec', '-$si_unitnum', '$si_cost', '$cspric', 'Returned payment for: $accnt[descript]', '".USER_DIV."')";
				$recRslt = db_exec($sql);
				
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
				$stk = pg_fetch_array($stkRslt);
		
				if($stk['units'] <> 0){
					$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}else{
					$csprice = sprint($si_cost/$si_unitnum);
					$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$si_stkid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}
			}
		}
	}

	if(isset($cc_trantype) && $cc_trantype != false){
		$cc = "<script> CostCenter('$cc_trantype', 'Returned, Unpaid Bank Transaction', '$seldate', '$descript', '".($accnt['amount'] - $accnt['vat'])."', '../'); </script>";
	}else{
		$cc = "";
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

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
								<th>Quick Links</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td align='center'><a href='cashbook-view.php'>View Cash Book</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td align='center'><a href='../reporting/not-banked.php'>View Outstanding Cash Book Entries</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td align='center'><a href='bank-pay-add.php'>Add Bank Payment</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td align='center'><a href='bank-recpt-add.php'>Add Bank Receipt</td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>";
	return $OUTPUT;

}


# Cancel multiple Transactions
function multican($accnt, $bank, $vatacc,$vatcode)
{

	$accids = explode("|", $accnt['accids']);
	$amounts = explode("|", $accnt['amounts']);
	$vatcodes = explode("|", $accnt["vatcodes"]);
	$vats = explode("|", $accnt['vats']);
	$chrgvats = explode("|", $accnt['chrgvats']);
	$refnum = getrefnum();
	$descript = $accnt['descript']." Returned, Unpaid";
	$date = date("Y-m-d");

	foreach($amounts as $key => $amount){
		# SQL Array Rule: Thou shalt skip Zero Reference
		if ($key < 1) {
			continue;
		}

		$accid = $accids[$key];
		$vat = $vats[$key];
		$chrgvat = $chrgvats[$key];
		$amount -= $vat;
		if (isset($vatcodes[$key])) {
			$curvc = $vatcodes[$key];
		} else {
			$curvc = $vatcode;
		}
		
		if($accnt['trantype'] == "deposit"){
			$vatacc = gethook("accnum", "salesacc", "name", "VAT","a");
			# DT(account involved), CT(bank)
			writetrans($accid, $bank['accnum'], $accnt['date'], $refnum, $amount, $descript);

			if($vat <> 0){
				# DT(Vat), CT(Bank)
				db_conn('cubit');
				$Sl = "SELECT * FROM vatcodes WHERE id='$curvc'";
				$Ri = db_exec($Sl);
				$vd = pg_fetch_array($Ri);

				vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,$descript,-($amount+$vat),-$vat);
				writetrans($vatacc, $bank['accnum'], $accnt['date'], $refnum, $vat, $descript);
			}
		}else{
			$vatacc = gethook("accnum", "salesacc", "name", "VAT");
			# DT(bank), CT(account invoilved)
			writetrans($bank['accnum'], $accid, $accnt['date'], $refnum, $amount, $descript);

			if($vat <> 0){
				# DT(Vat), CT(Bank)
				db_conn('cubit');
				$Sl = "SELECT * FROM vatcodes WHERE id='$curvc'";
				$Ri = db_exec($Sl);
				$vd = pg_fetch_array($Ri);

				vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descript,($amount+$vat),$vat);
				writetrans($bank['accnum'], $vatacc, $accnt['date'], $refnum, $vat, $descript);
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
function rerecord($id, $supid, $purid, $amount, $date)
{

	db_connect();

	if(ext_ex("suppurch", "id", $id)){
		# Remove make amount less
		$sql = "UPDATE suppurch SET balance = (balance + '$amount'::numeric(13,2)) WHERE id = '$id' AND div = '".USER_DIV."'";
		$dRs = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	}else{
		/* Make transaction record for age analysis */
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '$purid', '$date', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

}



function copyEntry($cashid)
{

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
	$sql  = "
		INSERT INTO cashbook (
			trantype, bankid, date, name, descript, 
			cheqnum, amount, banked, accinv, lnk, supid, 
			cusnum, rinvids, amounts, invprds, ids, purids, 
			pamounts, pdates, div, accids, suprec, vat, 
			chrgvat, vats, chrgvats, rages, famount, 
			fpamounts, famounts, lcashid, fcid, 
			currency, location, opt
		) VALUES (
			'$trantype', '$accnt_cp[bankid]', '$accnt_cp[date]', '$accnt_cp[name]', '$accnt_cp[descript] Returned, Unpaid', 
			'$accnt_cp[cheqnum]', '$accnt_cp[amount]', '$accnt_cp[banked]', '$accnt_cp[accinv]', '$accnt_cp[lnk]', '$accnt_cp[supid]', 
			'$accnt_cp[cusnum]', '$accnt_cp[rinvids]', '$accnt_cp[amounts]', '$accnt_cp[invprds]', '$accnt_cp[ids]', '$accnt_cp[purids]', 
			'$accnt_cp[pamounts]', '$accnt_cp[pdates]', '$accnt_cp[div]', '$accnt_cp[accids]', '$accnt_cp[suprec]', '$accnt_cp[vat]', 
			'$accnt_cp[chrgvat]', '$accnt_cp[vats]', '$accnt_cp[chrgvats]', '$accnt_cp[rages]', '$accnt_cp[famount]', 
			'$accnt_cp[fpamounts]', '$accnt_cp[famounts]', '$accnt_cp[lcashid]', '$accnt_cp[fcid]', 
			'$accnt_cp[currency]', '$accnt_cp[location]', 'n'
		)";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to insert cashbook entry details to database11.", SELF);

}



# records for CT
function moveback($invid, $prd, $amount)
{

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
		$sql = "
			INSERT INTO invoices (
				invid, invnum, deptid, cusnum, deptname, cusacc, cusname, 
				surname, cusaddr, cusvatno, cordno, ordno, chrgvat, 
				terms, traddisc, salespn, odate, delchrg, subtot, vat, 
				total, balance, prd, age, comm, discount, delivery, printed, done, 
				docref, div
			) VALUES (
				'$invb[invid]', '$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', 
				'$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', 
				'$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , 
				'$invb[total]', '$amount', '$prd', '$invb[age]', '$invb[comm]', '$invb[discount]', '$invb[delivery]', 'y', 'y', 
				'$invb[docref]', '".USER_DIV."'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		# get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT * FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			db_connect();
			# insert invoice items into cubit Db
			$sql = "
				INSERT INTO inv_items (
					invid, whid, stkid, qty, unitcost, amt, 
					disc, discp, div, vatcode, del
				) VALUES (
					'$invb[invid]', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', 
					'$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]','$stkd[del]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_conn($prd);
		# Remove those invoices from prd DB
		$sql = "DELETE FROM invoices WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# Remove those invoice items from prd DB
		$sql = "DELETE FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	}

	/* end moving invoices */
	return true;

}



# records for CT
function recordCT($amount, $cusnum)
{

	db_connect();

	$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '-$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



function recordDT($amount, $cusnum)
{

	db_connect();

	$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



?>
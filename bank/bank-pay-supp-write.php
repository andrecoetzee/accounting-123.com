<?

function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		return alloc($HTTP_POST_VARS);
	}

	$all = $all_val;
	$OUT1 = $out1_val;
	$OUT2 = $out2_val;
	$OUT3 = $out3_val;
	$OUT4 = $out4_val;
	$OUT5 = $out5_val;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($overpay, "float", 1, 15, "Invalid unallocated payment amount.");
	$v->isOk ($setamt, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk ($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(current).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");

	if(isset($invids)) {
		foreach($invids as $key => $value){
   			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No.");
			$v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		die("$confirm");
	}



	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."' AND accid!=0";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);

	# check if link exists
	if(pg_numrows($rslt) < 1){
		$Sl = "SELECT * FROM accounts WHERE accname='Cash on Hand'";
		$Rg = db_exec($Sl);
		if(pg_num_rows($Rg) < 1) {
			if($bankid == 0) {
				return "There is no 'Cash on Hand' account, there was one, but its not there now, you must have deleted it, if you want to use cash functionality please create a 'Cash on Hand' account.";
			} else {
				return "Invalid bank acc.";
			}
		}
		$add = pg_fetch_array($Rg);
		$bank['accnum'] = $add['accid'];
	} else {
		$bank = pg_fetch_array($rslt);
	}

	db_connect();
	# Supplier name
	$sql = "SELECT supid,supno,supname,deptid FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	# date format
	$sdate = $date;
	$cheqnum = 0 + $cheqnum;
	$pay = "";
	$accdate = $sdate;

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";

	if($overpay < 0)
		$overpay = 0.00;

	$refnum = getrefnum($accdate);

	db_conn('core');
	$Sl = "SELECT * FROM bankacc WHERE accid = '$bankid'";
	$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
	if(pg_numrows($Rx) < 1) {
		return "Invalid bank acc.";
	}
	$link = pg_fetch_array($Rx);

	$link['accnum'] = $bank['accnum'];

	pglib_transaction("BEGIN");

	db_conn("cubit");

	#record this payment for the print script ...
	$save_sql = "
		INSERT INTO supp_payment_print (
			supid, account, pay_date, sdate, refno, 
			cheqno, total_amt, set_amt, overpay_amt, descript
		) VALUES (
			'$supid', '$bankid', '$accdate', 'now', '$reference', 
			'$cheqnum', '$amt', '$setamt', '$overpay', '$descript'
		)";
	$run_save = db_exec($save_sql) or errDie ("Unable to record information.");

	$supp_pay_id = pglib_lastid("supp_payment_print","id");



	if($all == 2)
	{
		$ids = "";
		$purids = "";
		$pamounts = "";
		$pdates = "";

		db_conn('cubit');

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Begin updates
			if(isset($invids))
			{

				db_connect();

				foreach($invids as $key => $value)
				{
					# Get debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$paidamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$samount = ($paidamt[$key] - ($paidamt[$key] * 2));
					$Sl = "
						INSERT INTO sup_stmnt (
							supid, amount, edate, 
							descript, ref, cacc, 
							div
						) VALUES (
							'$sup[supid]', '$samount', '$sdate', 
							'Payment - Purchase: $invids[$key]', '$cheqnum', '$bank[accnum]', 
							'".USER_DIV."'
						)";
					$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

					#record the settlement discount on the statement
					#we record the total settlement below with a negative (correct?) amount ... 
					#why do this here ??? -> so we have individual amount on statement ...
					#rather use this ... but with negative amount ... (fixed)
					if($stock_setamt[$key] > 0){

						db_conn('core');
						#get settlement accid
						$get_setacc = "SELECT accid FROM accounts WHERE accname = 'Creditors Settlement Discount'";
						$run_setacc = db_exec($get_setacc) or errDie ("Unable to get settlement account information");
						$setaccid = pg_fetch_result ($run_setacc,0,0);

						db_connect ();

						$sql = "
							INSERT INTO sup_stmnt (
								supid, amount, edate, 
								descript, 
								ref, cacc, div
							) VALUES (
								'$sup[supid]', '".sprint($stock_setamt[$key] - (2*$stock_setamt[$key]))."', '$sdate', 
								'Settlement Discount for Invoice (Ref. $reference): $invids[$key]', 
								'$cheqnum','$setaccid', '".USER_DIV."'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						# reduce the money (settlement) that has been paid
						$sql = "UPDATE suppurch SET balance = (balance - '$stock_setamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						#record settlement discount in supp ledger
						suppledger($sup['supid'], $setaccid, $sdate, $invids[$key], "Settlement Discount On Payment for Purchase No. $invids[$key]", $stock_setamt[$key], "d");
					}

					db_connect();

					# Update the supplier (make balance less)
					$sql = "UPDATE suppliers SET balance = (balance - '$stock_setamt[$key]'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

					#record purchase in supp ledger
					suppledger($sup['supid'], $bank['accnum'], $sdate, $invids[$key], "Payment for Purchase No. $invids[$key]", $paidamt[$key], "d");
	
					db_connect();

					# record the payment on the statement

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$pamounts .= "|$paidamt[$key]";
					$pdates .= "|$pur[pdate]";

					#record this for printing ...
					$save_sql2 = "
							INSERT INTO supp_payment_print_items (
								payment_id, supid, purchase, tdate, sdate, 
								paid_amt, sett_amt
							) VALUES (
								'$supp_pay_id', '$supid', '$invids[$key]', '$date', 'now', 
								'$paidamt[$key]','$stock_setamt[$key]'
							)";
					$run_save2 = db_exec($save_sql2) or errDie ("Unable to record item information.");

				}
			}

		$samount = ($amt - ($amt * 2));

		#handle overpay ...
		if ($overpay > 0){

			$sql = "
				INSERT INTO sup_stmnt (
					supid, amount, edate, 
					descript, 
					ref, cacc, div
				) VALUES (
					'$sup[supid]', '-$overpay', '$sdate', 
					'Payment  (Ref. $reference)', 
					'$cheqnum','$bank[accnum]', '".USER_DIV."'
				)";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			$sql = "UPDATE suppliers SET balance = (balance - '$overpay'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			#record general transaction in supp ledger
			suppledger($sup['supid'], $bank['accnum'], $sdate, "General Transaction", "Unallocated Payment for Supplier", $overpay, "d");

			db_connect();

			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$sup[supid]', '0', '$date', '-$overpay', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

		}

		db_conn('cubit');
		# Record the payment record
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, 
				descript, cheqnum, amount, banked, accinv, 
				supid, ids, purids, pamounts, pdates, reference, 
				div
			) VALUES (
				'$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 
				'Supplier Payment to $sup[supname]', '$cheqnum', '".sprint ($amt+$overpay)."', 'no', '$dept[credacc]', 
				'$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference', 
				'".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		writetrans($dept['credacc'],$link['accnum'], $accdate, $refnum, sprint ($amt+$overpay), "Supplier Payment to $sup[supname]");

		db_conn('cubit');
	}

	#do journal for the settlement discount here ... now ...
	if($setamt > 0){
		db_conn('core');
		#get settlement accid
		$get_setacc = "SELECT accid FROM accounts WHERE accname = 'Creditors Settlement Discount'";
		$run_setacc = db_exec($get_setacc) or errDie ("Unable to get settlement account information");
		$setaccid = pg_fetch_result ($run_setacc,0,0);

		#calculate the settlement vat ... and amt
		if(isset($setvat) AND $setvat == 'inc'){

			db_connect ();
			$get_vcode = "SELECT * FROM vatcodes WHERE id = '$setvatcode' LIMIT 1";
			$run_vcode = db_exec($get_vcode) or errDie ("Unable to get vatcode informtion.");
			if(pg_numrows($run_vcode) < 1){
				return "<li class='err'>Settlement Discount VAT Code Not Set.</li>";
			}
			$vd = pg_fetch_array ($run_vcode);

			#vat inc ... recalculate the amts
			$setvatamt = sprint(($setamt)*($vd['vat_amount']/(100+$vd['vat_amount'])));
			$setamt = sprint ($setamt - $setvatamt);

			$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");

			#process the vat amt ...
			writetrans($dept['credacc'], $vatacc, $accdate, $refnum, $setvatamt, "VAT Received on Settlement Discount for Supplier : $sup[supname]");
			vatr($vd['id'],$accdate,"INPUT",$vd['code'],$refnum,"VAT for Settlement Discount for Supplier : $sup[supname]",$setamt+$setvatamt,$setvatamt);
		}else {
			#no vat for set amt ... do nothing
			$setvatamt = 0;
		}

		writetrans($dept['credacc'], $setaccid, $accdate, $refnum, $setamt, "Settlement Discount For $sup[supname]");

		db_connect ();

		$Sl = "
				INSERT INTO sup_stmnt (
					supid, amount, edate, 
					descript, ref, cacc, div
				) VALUES (
					'$sup[supid]', '".sprint($setamt - ($setamt * 2))."', '$sdate', 
					'Settlement Discount','$cheqnum','$bank[accnum]', '".USER_DIV."'
				)";
//		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		#record this paid settlement discount for reporting ...
		$settl_sql = "
					INSERT INTO settlement_sup (
						supplier, amt, setamt, setvatamt, setvat, 
						setvatcode, tdate, sdate, refnum
					) VALUES (
						'$sup[supid]', '$amt', '$setamt', '$setvatamt', '$setvat', 
						'$setvatcode', '$accdate','now','$refnum'
					)";
		$run_settl = db_exec($settl_sql) or errDie ("Unable to get debtor settlement information.");

	}

    db_conn('cubit');
	$Sl = "DELETE FROM suppurch WHERE balance=0::numeric(13,2)";
	$Rx = db_exec($Sl);

	#check if date setting is in db ...
	$checkdate = getCSetting("SUPP_PAY_DATE");
	if(!isset($checkdate) OR strlen($checkdate) < 1){
		#no date ... insert
		$ins_sql = "
					INSERT INTO settings (
						constant, label, value, type, 
						datatype, minlen, maxlen, div, readonly
					) VALUES (
						'SUPP_PAY_DATE', 'Last Supplier Payment Date Used', '$date', 'general', 
						'string', '10', '10', '0', 'n'
					);
					";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record supplier payment date information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$date' WHERE constant = 'SUPP_PAY_DATE'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update supplier payment date setting.");
	}

	pglib_transaction("COMMIT");

}


?>

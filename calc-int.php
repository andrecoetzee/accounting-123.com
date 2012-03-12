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
require ("settings.php");
require ("libs/ext.lib.php");
require ("core-settings.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "calc":
			$OUTPUT = calculate ();
			break;
		default:
			$OUTPUT = confirm ();
	}
} else {
	$OUTPUT = confirm ();
}

# get template
require("template.php");




# confirm
function confirm()
{

	db_conn('cubit');

	$Sl = "SELECT * FROM monthcloses WHERE type='Interest' ORDER BY id DESC LIMIT 1";
	$Rx = db_exec($Sl) or errDie("Unable to get monthclose from db.");

	if(pg_numrows($Rx) < 1) {
		$Note = "This is the first time you are calculating interest";
	} else {
		$data = pg_fetch_array($Rx);
		$Note = "<li class='err'>The last interest calculation was on $data[closedate] by $data[closeby].</li>";
	}

	// Layout
	$confirm = "
		<h3>Confirm Interest Calculation</h3>
		$Note
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='calc'>
			<tr>
				<th colspan='2'>Date</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>".date("d F Y")."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='left'><input type='submit' value='Calculate &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write
function calculate ()
{

	db_connect();

	# Get Interest type
	$sql = "SELECT value FROM set WHERE label = 'INT_TYPE'";
	$intRs = db_exec($sql);
	if(pg_numrows($intRs) < 1){
		return "<li class='err'> Interest Calculation method has not been set. Please set <a href='set-int-type.php'>this option</a> first.";
	}
	$int = pg_fetch_array($intRs);

	$ints = calcPerc($int['value']);

	// var_dump($ints);

	db_conn('cubit');

	$date = date("Y-m-d");

	$Sl = "INSERT INTO monthcloses (type, closedate, closeby) VALUES ('Interest', '$date', '".USER_NAME."')";
	$Rx = db_exec($Sl) or errDie("Monthend was done, but error makeing record of it.");

	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Interest has been calculated</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date : ".date("d F Y")."</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



function calcPerc($type)
{

	core_connect();

	$intacc = gethook("accnum", "salesacc", "name", "SalesInt");

	db_connect();

	# Get all client that must be charged interest
	$sql = "SELECT surname, cusnum, balance, fbalance, deptid, intrate, location, fcid FROM customers WHERE chrgint = 'yes' AND (balance > 0 OR fbalance > 0) AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to get clients from Cubit.");
	if(pg_numrows($rs) < 1){
		# Return if they are not charging any interest
		return;
	}
	#go through matching customers ...
	while($clnt = pg_fetch_array($rs)){
		
		if($clnt['location'] != 'int'){
			#local customer ...
			
			#figure out the percentage ... based on setting ...
			if($type == 'brac'){
				# Get from bracket
				$sql = "SELECT percentage as perc FROM intbracs WHERE min <= '$clnt[balance]' AND max >= '$clnt[balance]'";
				$bRs = db_exec($sql);
				if(pg_numrows($bRs) > 0){
					$brac = pg_fetch_array($bRs);
					$perc = $brac['perc'];
				}else{
					$perc = 0;
				}
			}elseif($type[0] == 'r'){
				$perc = $clnt['intrate'];
			}else{
				$perc = $type;
			}

			//calculate the amount to charge interest on ...
			$overdue = getOverdue($clnt['cusnum']);

			# Get Perc of overdue
			$pamt = sprint((($perc/12)/100) * $overdue);
			$totamt = ($pamt + $clnt['balance']);
			$ret[$clnt['cusnum']] = $totamt;

			# Get department
			db_conn("exten");

			$sql = "SELECT * FROM departments WHERE deptid = '$clnt[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			if($pamt > 0) {
				$sdate = date("Y-m-d");
				$invnum = intInvoice($clnt['cusnum'], $pamt,$intacc);
				db_connect();
				# Record the payment on the statement
				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, 
						type, div, allocation_date
					) VALUES (
						'$clnt[cusnum]', '$invnum', '$pamt', '$sdate', 
						'Interest on Outstanding balance', '".USER_DIV."', '$sdate'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, balance, date, 
						type, div
					) VALUES (
						'$clnt[cusnum]', '$invnum', '$pamt', '$pamt','$sdate', 
						'Interest on Outstanding balance', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				# Update the customer (make balance more)
				$sql = "UPDATE customers SET balance = (balance + '$pamt'::numeric(13,2)) WHERE cusnum = '$clnt[cusnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				# Make ledge record
				custledger($clnt['cusnum'], $intacc, $sdate, 0, "Interest on Outstanding balance", $pamt, "d");
				# Write transaction  (debit debtors control, credit contra account)
				$refnum = getrefnum();
				writetrans($dept['debtacc'], $intacc, date("d-m-Y"), $refnum, $pamt,  "Interest Received from customer : $clnt[surname].");
				recordDT($pamt, $clnt['cusnum']);
			}
		}else{
			if($type == 'brac'){
				# Get from bracket
				$sql = "SELECT percentage as perc FROM intbracs WHERE min <= '$clnt[balance]' AND max >= '$clnt[balance]'";
				$bRs = db_exec($sql);
				if(pg_numrows($bRs) > 0){
					$brac = pg_fetch_array($bRs);
					$perc = $brac['perc'];
				}else{
					$perc = 0;
				}
			}elseif($type[0] == 'r'){
				$perc = $clnt['intrate'];
			}else{
				$perc = $type;
			}

	//		$overdue = getfOverdue($clnt['cusnum']);
			$overdue = getOverdue($clnt['cusnum']);

			$rate = sprint($clnt['balance'] / $clnt['fbalance']);

			# Get Perc of overdue
			$pamt = sprint((($perc/12)/100) * $overdue);
			$totamt = ($pamt + $clnt['fbalance']);
			$ret[$clnt['cusnum']] = $totamt;

			if($rate == 0) $rate = 1;
			$ltotamt = sprint($totamt * $rate);
			$lpamt = sprint($ltotamt - $clnt['balance']);

			# Get department
			db_conn("exten");

			$sql = "SELECT * FROM departments WHERE deptid = '$clnt[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			if($pamt > 0) {
				$sdate = date("Y-m-d");
				$invnum = fintInvoice($clnt['cusnum'], $pamt, $rate);
				db_connect();
				# Record the payment on the statement
				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, 
						type, div, allocation_date
					) VALUES (
						'$clnt[cusnum]', '$invnum', '$pamt','$sdate', 
						'Interest on Outstanding balance', '".USER_DIV."', '$sdate'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				# Update the customer (make balance more)
				$sql = "UPDATE customers SET fbalance = (fbalance + '$pamt'::numeric(13,2)), balance = '$ltotamt'::numeric(13,2) WHERE cusnum = '$clnt[cusnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				# Make ledge record
				custledger($clnt['cusnum'], $intacc, $sdate, 0, "Interest on Outstanding balance", $lpamt, "d");
				# Write transaction  (debit debtors control, credit contra account)
				$refnum = getrefnum();
				writetrans($dept['debtacc'], $intacc, date("d-m-Y"), $refnum, $lpamt,  "Interest Received from customer : $clnt[surname].");
				frecordDT($pamt, $lpamt, $clnt['cusnum'], $clnt['fcid']);
			}
		}
	}
	return $ret;

}



# Get clients overdue amount
function getOverdue($cusnum)
{

	if (!isset($cusnum) OR strlen($cusnum) < 1){
		return 0;
	}

	db_connect();

	#get customer info ...
	$get_cust = "SELECT * FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
	$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_cust) > 0){
		$cust_data = pg_fetch_array ($run_cust);
	}else {
		return 0;
	}

	$to_month = date ("m");
	$to_date = date ("Y-m-d");
	$from_date = date ("Y-m-d",mktime (0,0,0,date("m"),"01",date("Y")));

	$age30 = cust_age($cust_data['cusnum'], 59, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
	$age60 = cust_age($cust_data['cusnum'], 89, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
	$age90 = cust_age($cust_data['cusnum'], 119, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
	$age120 = cust_age($cust_data['cusnum'], 149, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);

	return $age120 + $age30 + $age60 + $age90;

//	# Get the client's overdue period
//	$sql = "SELECT cusnum,overdue,location FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
//	$clntRs = db_exec($sql) or errDie("Unable to get clients from Cubit.");
//	$cust = pg_fetch_array($clntRs);
//
//	# Check type of age analisys
//	if(div_isset("DEBT_AGE", "mon")){
//		$overd = oageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
//	}else{
//		$overd = oage($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
//	}
//	return $overd;

}




# Get clients overdue amount
function getfOverdue($cusnum)
{

	db_connect();

	# Get the client's overdue period
	$sql = "SELECT cusnum,overdue,fcid,location FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$clntRs = db_exec($sql) or errDie("Unable to get clients from Cubit.");
	$cust = pg_fetch_array($clntRs);

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$overd = oageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
	}else{
		$overd = oage($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
	}
	return $overd;

}

# Records for DT
function recordDT($amount, $cusnum)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND age = 0 AND balance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			$odate = date("Y-m-d");
			$sql = "
				INSERT INTO custran (
					cusnum, odate, balance, div
				) VALUES (
					'$cusnum', '$odate', '$amount', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		$odate = date("Y-m-d");
		$sql = "
			INSERT INTO custran (
				cusnum, odate, balance, div
			) VALUES (
				'$cusnum', '$odate', '$amount', '".USER_DIV."'
			)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




# records for CT
function frecordDT($amount, $lamount, $cusnum, $fcid)
{

	db_connect();

	/* Make transaction record for age analysis */
	$odate = date("Y-m-d");
	$sql = "
		INSERT INTO custran (
			cusnum, odate, fcid, balance, fbalance, div
		) VALUES (
			'$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."'
		)";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




function oage($cusnum, $days, $loc)
{

	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum'] );

}




function oageage($cusnum, $age, $loc)
{

	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}



?>
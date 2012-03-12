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
require ("../libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "alloc":
			$OUTPUT = alloc($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = sel_sup();
	}
}elseif(isset($_GET["supid"])) {
        # Display default output
        $OUTPUT =  alloc($_GET);
}else {
        # Display default output
        $OUTPUT = sel_sup();
}

$OUTPUT .= "<br><br>"
			.mkQuickLinks(
				ql("bank-pay-supp.php", "Add Supplier Payment"),
				ql("bank-pay-add.php","Add Bank Payment"),
				ql("bank-recpt-add.php","Add Bank Receipt"),
				ql("cashbook-view.php","View Cash Book")
			);

# get templete
require("../template.php");








# confirm
function alloc($_POST)
{
	
	# get vars
	extract ($_POST);

	$date_arr = explode ("-",$tdate);
	$date_year = $date_arr[0];
	$date_month = $date_arr[1];
	$date_day = $date_arr[2];

	$all = 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	if(($amt<0.01)){$v->isOk ($amt, "float", 5, 1, "Amount too small.");}

	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");

	$date = mkdate($date_year, $date_month, $date_day);
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm;
	}

        $out = 0;

	$confirm = "
			<h3>New Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='descript' value='$descript'>
				<input type='hidden' name='reference' value='$reference'>
				<input type='hidden' name='cheqnum' value='$cheqnum'>
				<input type='hidden' name='amt' value='$amt'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
				<input type='hidden' name='pur' value=''>
				<input type='hidden' name='inv' value=''>";

	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	if(pg_num_rows($bankRslt) < 1) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	# Supplier name
	$sql = "SELECT supno,supname FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);


	$confirm .= "
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid To</td>
				<td valign='center'>($sup[supno]) $sup[supname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'>$reference</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt</td>
			</tr>";

		db_conn('cubit');

		$Sl = "SELECT purnum,supinv FROM purchases";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {
			$pid = $pd['purnum'];
			$supinv[$pid] = $pd['supinv'];
		}

		for($i = 1; $i < 13; $i++) {
			db_conn($i);
			$Sl = "SELECT purnum,supinv FROM purchases";
			$Ri = db_exec($Sl);

			while($pd = pg_fetch_array($Ri)) {
				$pid = $pd['purnum'];
				$supinv[$pid] = $pd['supinv'];
			}
		}

	if($all == 0)
	{
		$out = $amt;
		// Connect to database
		db_conn("cubit");
		$sql = "SELECT purid as invid,intpurid as invid2,SUM(balance) AS balance,pdate as odate
				FROM suppurch WHERE supid = '$supid' AND div = '".USER_DIV."'
				GROUP BY purid, intpurid, pdate
				HAVING SUM(balance) > 0
				ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql) or errDie("unable to get invoices.");
		$i = 0;
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{

			if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
			if($i == 0)
			{
				$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Purchases</h3></td>
				</tr>
				<tr>
					<th>Purchase</th>
					<th>Supplier Invoice No.</th>
					<th>Outstanding Amount</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
			}

			$invid = $inv['invid'];
			if(isset($supinv[$invid])) {
				$sinv = $supinv[$invid];
			} else {
				$sinv = "";
			}
			$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
					<td>$sinv</td>
					<td>".CUR." $inv[balance]</td>
					<td>$inv[odate]</td>";
			if($out >= $inv['balance']) {
				$val = $inv['balance'];
				$out = $out - $inv['balance'];
			}else {
				$val = $out;
				$out = 0;
			}
			$i++;
			$val = sprint($val);
			$confirm .= "
				<td><input type='hidden' name='paidamt[$invid]' size='10' value='$val'>".CUR." $val</td>
			</tr>";
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out >= 0.01) {
			$confirm .="
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out)." </b>
				</td>
			</tr>";
		}
	}

	if ($all == 1) {
		$confirm .= "
		<tr>
			<td>
				<table ".TMPL_tblDflts.">
					".TBL_BR."
					<tr>
						<th>Current</th>
						<th>30 days</th>
						<th>60 days</th>
						<th>90 days</th>
						<th>120 days</th>
						<th>Total Outstanding</th>
					</tr>";

		$curr = sage($supid, 29);
		$age30 = sage($supid, 59);
		$age60 = sage($supid, 89);
		$age90 = sage($supid, 119);
		$age120 = sage($supid, 149);

		$supttot = sprint($curr + $age30 + $age60 + $age90 + $age120);

		if(!isset($OUT1)) {
			$OUT1 = "";
			$OUT2 = "";
			$OUT3 = "";
			$OUT4 = "";
			$OUT5 = "";
		}

		vsprint($OUT1);
		vsprint($OUT2);
		vsprint($OUT3);
		vsprint($OUT4);
		vsprint($OUT5);

		# Alternate bgcolor
		$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>".CUR." $curr</td>
						<td>".CUR." $age30</td>
						<td>".CUR." $age60</td>
						<td>".CUR." $age90</td>
						<td>".CUR." $age120</td>
						<td>".CUR." $supttot</td>
					</tr>";
		$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='text' size='7' name='out1' value='$OUT1'></td>
					<td><input type='text' size='7' name='out2' value='$OUT2'></td>
					<td><input type='text' size='7' name='out3' value='$OUT3'></td>
					<td><input type='text' size='7' name='out4' value='$OUT4'></td>
					<td><input type='text' size='7' name='out5' value='$OUT5'></td>
					<td></td>
				</tr>";

		$confirm .= "
					".TBL_BR."
				</table>
			</td>
		</tr>";
	}

	if($all == 2)
	{
		db_conn("cubit");
		$sql = "SELECT purid as invid,intpurid as invid2,SUM(balance) AS balance,pdate as odate
				FROM suppurch WHERE supid = '$supid' AND div = '".USER_DIV."'
				GROUP BY purid, intpurid, pdate
				HAVING SUM(balance) > 0
				ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		if(pg_numrows($prnInvRslt) < 1) {return "The selected supplier has no outstanding purchases<br>
				To make a payment in advance please select Auto Allocation";}
		$i = 0;
		while(($inv = pg_fetch_array($prnInvRslt)))
		{
			if ($inv['invid'] == 0) {continue;}
			if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
			if($i == 0)
			{
				$confirm .= "
					".TBL_BR."
					<tr>
						<td colspan='2'><h3>Outstanding Purchases</h3></td>
					</tr>
					<tr>
						<th>Purchase</th>
						<th>Supplier Invoice No.</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";
			}

			$invid = $inv['invid'];
			$val = '';
			if(pg_numrows($prnInvRslt) == 1) {$val = $amt;}

			if(isset($paidamt[$i])) {
				$val = $paidamt[$i];
			}

			if(isset($supinv[$invid])) {
				$sinv = $supinv[$invid];
			} else {
				$sinv = "";
			}

			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
						<td>$sinv</td>
						<td>".CUR." $inv[balance]</td>
						<td>$inv[odate]</td>";
			$i++;

			$confirm .= "<td><input type='text' name='paidamt[$invid]' size='10' value='$val'></td></tr>";
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out >= 0.01) {
			$confirm .="
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out)." </b>
				</td>
			</tr>";
		}
	}

	vsprint($out);

	$confirm .= "
			<input type='hidden' name='out' value='$out'>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# confirm
function confirm($_POST)
{
	
	# get vars
	extract ($_POST);

	if(isset($back)) {
		header ("Location: bank-pay-supp.php?supid=$supid&paidamt[]=$amt&descript=$descript&reference=$reference");
//		return method($supid);
	}

	if(!isset($out1)) {$out1 = '';}
	if(!isset($out2)) {$out2 = '';}
	if(!isset($out3)) {$out3 = '';}
	if(!isset($out4)) {$out4 = '';}
	if(!isset($out5)) {$out5 = '';}

	$OUT1 = $out1;
	$OUT2 = $out2;
	$OUT3 = $out3;
	$OUT4 = $out4;
	$OUT5 = $out5;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($descript, "string", 1, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(currant).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");
	$v->isOk ($supid, "num", 1, 10, "Invalid Supplier number.");
	if(isset($invids))
	{
		foreach($invids as $key => $value){
			if($paidamt[$invids[$key]] < 0.01){
				continue;
			}
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No. [$key]");
			$v->isOk ($paidamt[$invids[$key]], "float", 1, 20, "Invalid amount to be paid. [$key]");
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
		return $confirm;
	}
	$out1 += 0;
	$out2 += 0;
	$out3 += 0;
	$out4 += 0;
	$out5 += 0;

		# check invoice payments
		$tot = 0;
		if(isset($invids))
		{
			foreach($invids as $key => $value){
				if($paidamt[$invids[$key]] < 0.01){
					continue;
				}
				$tot += $paidamt[$invids[$key]];
			}
		}


		if(sprint($tot + $out + $out1 + $out2 + $out3 + $out4 + $out5) != sprint($amt)){

				return "<li class='err'>$tot - $amt The total amount is not equal to the amount paid. Please check the details.</li>".alloc($_POST);
		}

	vsprint($out);

	$confirm = "
			<h3>New Bank Payment</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='descript' value='$descript'>
				<input type='hidden' name='reference' value='$reference'>
				<input type='hidden' name='cheqnum' value='$cheqnum'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='out' value='$out'>
				<input type='hidden' name='amt' value='$amt'>";

	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	if(pg_num_rows($bankRslt)<1) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	# Supplier name
	$sql = "SELECT supno,supname FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$confirm .= "
			<tr>
				<th colspan='2'>Payment Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid To</td>
				<td valign='center'>($sup[supno]) $sup[supname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'>$reference</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt</td>
			</tr>";

	if($all == 0)
	{
		// Layout
		$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Purchases</h3></td>
				</tr>
				<!--<table ".TMPL_tblDflts." width='90%'>-->
					<tr>
						<th>Purchase</th>
						<th>Outstanding amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

		$i = 0; // for bgcolor
		if(isset($invids))
		{
			foreach($invids as $key => $value){
				if($paidamt[$invids[$key]] < 0.01){
					continue;
				}

				db_conn("cubit");
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE purid='$invids[$key]' AND supid = '$supid' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE intpurid='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}

				$invid = $inv['invid'];

				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>
							<td>".CUR." <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
						</tr>";
				$i++;

			}
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out >= 0.01) {
			$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out)." </b>
				</td>
			</tr>";
		}
	}

	if($all == 1)
	{
		$age30 = sage($supid, 59);
		$age60 = sage($supid, 89);
		$age90 = sage($supid, 119);
		$age120 = sage($supid, 149);
		$bgColor = bgcolorg();
		$i = 0;
		if($out1 > 0)
		{
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid' AND balance>0 AND pdate >='".extlib_ago(29)."' AND pdate <='".extlib_ago(-1)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
					".TBL_BR."
					<tr>
						<td colspan='2'><h3>Outstanding Purchases</h3></td>
					</tr>
					<tr>
						<th>Purchase</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out1 >= $inv['balance']) {
					$val = $inv['balance'];
					$out1 = $out1 - $inv['balance'];
				}else {
					$val = $out1;
					$out1 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out1 >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out2 > 0)
		{
			if($out2 > $age30) {
				$_POST['OUT1'] = $OUT1;
				$_POST['OUT2'] = $OUT2;
				$_POST['OUT3'] = $OUT3;
				$_POST['OUT4'] = $OUT4;
				$_POST['OUT5'] = $OUT5;

				$out2 = sprint ($out2);
				
				return "<li class='err'>You cannot allocate ".CUR." $out2 to 30 days, you only owe ".CUR." $age30</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid' AND balance>0 AND pdate >='".extlib_ago(59)."' AND pdate <='".extlib_ago(29)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							".TBL_BR."
							<tr>
								<td colspan='2'><h3>Outstanding Purchases</h3></td>
							</tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out2 >= $inv['balance']) {
					$val = $inv['balance'];
					$out2 = $out2 - $inv['balance'];
				}else {
					$val = $out2;
					$out2 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out2 >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out3 > 0)
		{
			if($out3 > $age60) {
				$_POST['OUT1'] = $OUT1;
				$_POST['OUT2'] = $OUT2;
				$_POST['OUT3'] = $OUT3;
				$_POST['OUT4'] = $OUT4;
				$_POST['OUT5'] = $OUT5;

				$out3 = sprint ($out3);
				
				return "<li class='err'>You cannot allocate ".CUR." $out3 to 60 days, you only owe ".CUR." $age60 </lI>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid' AND balance>0 AND pdate >='".extlib_ago(89)."' AND pdate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							".TBL_BR."
							<tr>
								<td colspan='2'><h3>Outstanding Purchases</h3></td>
							</tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out3 >= $inv['balance']) {
					$val = $inv['balance'];
					$out3 = $out3 - $inv['balance'];
				}else {
					$val = $out3;
					$out3 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out3 >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out4 > 0)
		{
			if($out4 > $age90) {
				$_POST['OUT1'] = $OUT1;
				$_POST['OUT2'] = $OUT2;
				$_POST['OUT3'] = $OUT3;
				$_POST['OUT4'] = $OUT4;
				$_POST['OUT5'] = $OUT5;
				
				$out4 = sprint($out4);

				return "<li class='err'>You cannot allocate ".CUR." $out4 to 90 days, you only owe ".CUR." $age90</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid' AND balance>0 AND pdate >='".extlib_ago(119)."' AND pdate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							".TBL_BR."
							<tr>
								<td colspan='2'><h3>Outstanding Purchases</h3></td>
							</tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out4 >= $inv['balance']) {
					$val = $inv['balance'];
					$out4 = $out4 - $inv['balance'];
				}else {
					$val = $out4;
					$out4 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out4 >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out5 > 0)
		{
			if($out5 > $age120) {
				$_POST['OUT1'] = $OUT1;
				$_POST['OUT2'] = $OUT2;
				$_POST['OUT3'] = $OUT3;
				$_POST['OUT4'] = $OUT4;
				$_POST['OUT5'] = $OUT5;
				
				$out5 = sprint ($out5);

				return "<li class='err'>You cannot allocate ".CUR." $out5 to 120 days, you only owe ".CUR." $age120</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid' AND balance>0 AND pdate >='".extlib_ago(149)."' AND pdate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							".TBL_BR."
							<tr>
								<td colspan='2'><h3>Outstanding Purchases</h3></td>
							</tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out5 >= $inv['balance']) {
					$val = $inv['balance'];
					$out5 = $out5 - $inv['balance'];
				}else {
					$val = $out5;
					$out5 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out5 >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
	}

	if($all == 2)
	{
		// Layout
		$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Purchases</h3></td>
				</tr>
				<!--<table ".TMPL_tblDflts." width='90%'>-->
				<tr>
					<th>Purchase</th>
					<th>Outstanding amount</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";

		$i = 0; // for bgcolor
		if(isset($invids))
		{
			foreach($invids as $key => $value){
				if($paidamt[$invids[$key]] < 0.01){
					continue;
				}

				db_conn("cubit");
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE purid='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE intpurid='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}

				$invid = $inv['invid'];

				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>
							<td>".CUR." <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
						</tr>";
				$i++;

			}
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out >= 0.01) {
			$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out)." </b>
				</td>
			</tr>";
		}
	}

	vsprint($out1);
	vsprint($out2);
	vsprint($out3);
	vsprint($out4);
	vsprint($out5);
	vsprint($OUT1);
	vsprint($OUT2);
	vsprint($OUT3);
	vsprint($OUT4);
	vsprint($OUT5);

	$confirm .= "
				<input type='hidden' name='out1' value='$out1'>
				<input type='hidden' name='out2' value='$out2'>
				<input type='hidden' name='out3' value='$out3'>
				<input type='hidden' name='out4' value='$out4'>
				<input type='hidden' name='out5' value='$out5'>
				<input type='hidden' name='OUT1' value='$OUT1'>
				<input type='hidden' name='OUT2' value='$OUT2'>
				<input type='hidden' name='OUT3' value='$OUT3'>
				<input type='hidden' name='OUT4' value='$OUT4'>
				<input type='hidden' name='OUT5' value='$OUT5'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right'><input type='submit' value='Write &raquo'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $confirm;

}




# write
function write($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		unset($_POST["back"]);
		return alloc($_POST);
	}

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
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(current).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");

	if(isset($invids))
	{
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
		return $confirm;
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
	$accdate=$sdate;

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";

	db_conn("cubit");

	pglib_transaction("BEGIN");

	if($all == 0)
	{
		$ids = "";
		$purids = "";
		$pamounts = "";
		$pdates = "";

			if (isset($invids)) {
				foreach($invids as $key => $value)
				{
					#debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$paidamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$pamounts .= "|$paidamt[$key]";
					$pdates .= "|$pur[pdate]";
				}
			}

		$samount = ($amt - ($amt * 2));

		if ($out > 0) {
			recordDT($out, $sup['supid'],$sdate);
		}

		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate', 'Payment','$cheqnum','$bank[accnum]', '".USER_DIV."')";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		db_connect();

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		suppledger($sup['supid'], $bank['accnum'], $sdate, $cheqnum, "Payment for purchases", $amt, "d");

		db_connect();
		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank['accnum'];

		writetrans($dept['credacc'], $link['accnum'], $accdate, $refnum, $amt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');
	}


	if($all == 1)
	{
		$ids = "";
		$purids = "";
		$pamounts = "";
		$pdates = "";

			if(isset($invids)) {
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
					$sql = "UPDATE suppurch SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$pamounts .= "|$paidamt[$key]";
					$pdates .= "|$pur[pdate]";

				}
			}

		$samount = ($amt - ($amt * 2));

		if ($out1 > 0) {
			recordDT($out1, $sup['supid'],$sdate);
		}

		if ($out2 > 0) {
			recordDT($out2, $sup['supid'],$sdate,"1");
		}

		if ($out3 > 0) {
			recordDT($out3, $sup['supid'],$sdate,"2");
		}

		if ($out4 > 0) {
			recordDT($out4, $sup['supid'],$sdate,"3");
		}

		if ($out5 > 0) {
			recordDT($out5, $sup['supid'],$sdate,"4");
		}

		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate', 'Payment','$cheqnum','$bank[accnum]', '".USER_DIV."')";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank['accnum'];

		writetrans($dept['credacc'],$link['accnum'], $accdate, $refnum, $amt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');
		suppledger($sup['supid'], $bank['accnum'], $sdate, $cheqnum, "Payment to Supplier", $amt, "d");
		db_connect();
	}


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
					$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc,div) VALUES('$sup[supid]','$samount','$sdate', 'Payment - Purchase: $invids[$key]','$cheqnum','$bank[accnum]', '".USER_DIV."')";
					$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

					suppledger($sup['supid'], $bank['accnum'], $sdate, $invids[$key], "Payment for Purchase No. $invids[$key]", $paidamt[$key], "d");
					db_connect();

					# record the payment on the statement

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$pamounts .= "|$paidamt[$key]";
					$pdates .= "|$pur[pdate]";


				}
			}

		$samount = ($amt - ($amt * 2));



		db_conn('cubit');
		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank['accnum'];

		writetrans($dept['credacc'],$link['accnum'], $accdate, $refnum, $amt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');
	}

    db_conn('cubit');
	$Sl = "DELETE FROM suppurch WHERE balance=0::numeric(13,2)";
	$Rx = db_exec($Sl);

	pglib_transaction("COMMIT");

	# status report
	$write = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>Bank Payment</th>
				</tr>
				<tr class='datacell'><td>Bank Payment added to cash book.</td></tr>
			</table>";

	# main table (layout with menu)
	$OUTPUT = "<center>
	<table width='90%'>
		<tr valign='top'>
			<td width='50%'>$write</td>
			<td align='center'>
				<table ".TMPL_tblDflts." width='80%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='bank-pay-add.php'>Add Bank Payment</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='cashbook-view.php'>View Cash Book</a></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>";
	return $OUTPUT;

}



function sage($supid, $days)
{

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current outstanding
	db_conn("cubit");
	$sql = "SELECT sum(balance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] += 0);

}



function recordDT($amount, $supid,$edate,$age="0")
{

	db_connect();

	$py = array();
	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid > 0 AND balance > 0 OR supid = '$supid' AND intpurid > 0 AND balance > 0 ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					if($dat['purid'] > 0){
						$py[] = "$dat[id]|$dat[purid]|$amount|$dat[pdate]";
					}else{
						$py[] = "$dat[id]|$dat[intpurid]|$amount|$dat[pdate]";
					}
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
						if($dat['purid'] > 0){
							$py[] = "$dat[id]|$dat[purid]|$dat[balance]|$dat[pdate]";
						}else{
							$py[] = "$dat[id]|$dat[intpurid]|$dat[balance]|$dat[pdate]";
						}
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div $extra1) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div $extra1) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	return $py;

}


?>
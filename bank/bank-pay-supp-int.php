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

if(isset($_GET["supid"])){
	$OUTPUT = sel_bank($_GET["supid"]);
}elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "method":
			$OUTPUT = method($_POST);
			break;
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
} else {
        # Display default output
        $OUTPUT = sel_sup();
}

# get templete
require("../template.php");




# Insert details
function sel_sup()
{

	// suppliers Drop down selections
	db_connect();

	$supp = "<select name='supid'>";
	$sql = "SELECT supid, supno, supname FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supname,supno";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		return "<li> There are no Creditors in Cubit.";
	}
	while($sup = pg_fetch_array($supRslt)){
		$supp .= "<option value='$sup[supid]'>($sup[supno]) $sup[supname]</option>";
	}
	$supp .="</select>";

	// layout
	$add = "
			<h3>New International Bank Payment</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='method'>
				<tr>
					<th colspan='2'>Select Supplier</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Suppliers</td>
					<td>$supp</td>
				</tr>
				<tr>
					<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Enter Details >'></td>
				</tr>
			</form>
			</table>";

	# main table (layout with menu)
	$OUTPUT = "
			<center>
			<table width='100%'>
				<tr>
					<td width='65%' align='left'>$add</td>
					<td valign='top' align='center'>
						<table ".TMPL_tblDflts." width='65%'>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>";
	return $OUTPUT;

}


# Insert details
function sel_bank($supid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");

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



	db_connect();
	# Supplier name
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$currs = getSymbol($sup['fcid']);
	$rate = getRate($sup['fcid']);

	# Drop down selections
	db_connect();
	$banks = "<select name='bankid'>";
	$sql = "SELECT * FROM bankacct WHERE (fcid = '$sup[fcid]' OR  btype != 'int') AND div = '".USER_DIV."'";
	$bnkRs = db_exec($sql);
	if(pg_numrows($bnkRs) < 0){
		return "<li> There are no Bank Accounts in Cubit.</li>";
	}
	while($acc = pg_fetch_array($bnkRs)){
		$banks .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	// layout
	$add = "
			<h3>New International Bank Payment</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='method'>
				<input type='hidden' name='supid' value='$supid'>
				<tr>
					<th colspan='2'>Select Customer</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Banks</td>
					<td>$banks</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Supplier</td>
					<td valign='center'>($sup[supno]) $sup[supname]</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Enter Details >'></td>
				</tr>
			</table>";

	# main table (layout with menu)
	$OUTPUT = "
			<center>
			<table width='100%'>
			<tr><td width='65%' align='left'>$add</td>
			<td valign='top' align='center'>
				<table ".TMPL_tblDflts." width='65%'>
					<tr><th>Quick Links</th></tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>
			</td></tr>
			</table>";
	return $OUTPUT;

}



# Insert details
function method($_POST,$err = "")
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");
	$v->isOk ($bankid, "num", 1, 10, "Invalid Bank Account number.");

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



	db_connect();
	# Supplier name
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$currs = getSymbol($sup['fcid']);
	$rate = getRate($sup['fcid']);

	# Get bank account name
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	if($bank['btype'] == 'int'){
		$bcur = $currs['symbol'];
	}else{
		$bcur = CUR;
	}

	$alls = "
			<select name='all'>
				<option value='0' selected>Auto</option>
				<option value='1'>Allocate To Age Analysis</option>
				<option value='2'>Allocate To Each invoice</option>
			</select>";

	if(!isset($date_day)){
		$date_year = date("Y");
		$date_month = date("m");
		$date_day = date("d");
	}

	// layout
	$add = "
			<h3>New International Bank Payment</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='alloc'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='bankid' value='$bankid'>
				<tr><th colspan='2'>Payment Details</th></tr>
				$err
				<tr class='".bg_class()."'>
					<td>Account</td>
					<td>$bank[accname] - $bank[bankname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Paid To</td>
					<td valign='center'>($sup[supno]) $sup[supname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Cheque Number</td>
					<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Amount</td>
					<td valign='center'>$bcur <input type='text' size='13' name='amt' value='$amt'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $sup[currency] <input type='text' size='8' name='rate' value='$rate'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Allocation</td>
					<td>$alls</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Allocate >'></td>
				</tr>
			</form>
			</table>";

	$printCust = "
			<h3>Creditors Age Analysis</h3>
			<table ".TMPL_tblDflts.">
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

	$supttot = ($curr + $age30 + $age60 + $age90 + $age120);

	#clean the vars
	$curr = sprint($curr);
	$age30 = sprint($age30);
	$age60 = sprint($age60);
	$age90 = sprint($age90);
	$age120 = sprint($age120);
	$supttot = sprint($supttot);



	# Alternate bgcolor
	$printCust .= "
				<tr class='".bg_class()."'>
					<td>$sup[currency] ".sprint ($curr)."</td>
					<td>$sup[currency] ".sprint ($age30)."</td>
					<td>$sup[currency] ".sprint ($age60)."</td>
					<td>$sup[currency] ".sprint ($age90)."</td>
					<td>$sup[currency] ".sprint ($age120)."</td>
					<td>$sup[currency] ".sprint ($supttot)."</td>
				</tr>";
	$printCust .= "<tr><td><br></td></tr></table>";

	$OUTPUT = "
			<center>
			<table border='0' width='100%'>
				<tr>
					<td width='65%' align='left'>$add</td>
					<td valign='top' align='center'>
						<table ".TMPL_tblDflts." width='65%'>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>
			</center>
			$printCust";
	return $OUTPUT;

}



# confirm
function alloc($_POST)
{

	# get vars
	extract ($_POST);

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
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");

	if(($amt<0.01)){$v->isOk ($amt, "float", 5, 1, "Amount to small.");}

	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");
	$date = $date_day."-".$date_month."-".$date_year;
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
		return method ($_POST,$confirm);
	}

	$rate += 0;
	if($rate == 0) $rate = 1;

	# Get bank account name
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Supplier name
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);
	$currs = getSymbol($sup['fcid']);

	if($bank['btype'] == 'int'){
		$bcur = $currs['symbol'];
		$amt = sprint($amt);
		$lamt = sprint($amt * $rate);
	}else{
		$lamt = sprint($amt);
		$amt = sprint($amt/$rate);
		$bcur = CUR;
	}
	$out = 0;

	$rate = sprint ($rate);

	$confirm = "
			<h3>New International Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='descript' value='$descript'>
				<input type='hidden' name='cheqnum' value='$cheqnum'>
				<input type='hidden' name='amt' value='$amt'>
				<input type='hidden' name='rate' value='$rate'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Account</td>
					<td>$bank[accname] - $bank[bankname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td valign='center'>$date</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Paid To</td>
					<td valign='center'>($sup[supno]) $sup[supname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td valign='center'>".nl2br($descript)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Cheque Number</td>
					<td valign='center'>$cheqnum</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Amount</td>
					<td valign='center'>$sup[currency] $amt | ".CUR." $lamt</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $sup[currency] $rate</td>
				</tr>";

	if($all == 0)
	{
		$out = $amt;
		// Connect to database
		db_connect();
		$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql) or errDie("unable to get invoices.");
		$i = 0;
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{
			//if ($inv['invid']==0) {continue;}
			if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
			if($i == 0)
			{
				$confirm .= "
						<tr>
							<td colspan='2'><br></td>
						</tr>
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
					<tr class='".bg_class()."'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
						<td>$sup[currency] $inv[fbalance]</td>
						<td>$inv[odate]</td>";

			if($out >= $inv['fbalance']) {
				$val = $inv['fbalance'];
				$out = $out-$inv['fbalance'];
			}else {
				$val = $out;
				$out = 0;
			}

			$i++;

			$confirm .= "
						<td><input type='hidden' name='paidamt[$invid]' size='10' value='$val'>$sup[currency] $val</td>
					</tr>";
		}
		if($out > 0) {
			$confirm .= "
					<tr class='".bg_class()."'>
						<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out </b></td>
					</tr>";}
	}

	if($all == 1)
	{
		$confirm .= "
				<tr>
					<td>
						<table ".TMPL_tblDflts.">
							<tr><td><br></td></tr>
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

		$supttot = ($curr + $age30 + $age60 + $age90 + $age120);

		# Alternate bgcolor
		$confirm .= "
							<tr class='".bg_class()."'>
								<td>$sup[currency] ".sprint ($curr)."</td>
								<td>$sup[currency] ".sprint ($age30)."</td>
								<td>$sup[currency] ".sprint ($age60)."</td>
								<td>$sup[currency] ".sprint ($age90)."</td>
								<td>$sup[currency] ".sprint ($age120)."</td>
								<td>$sup[currency] ".sprint ($supttot)."</td>
							</tr>";
		$confirm .= "
							<tr class='".bg_class()."'>
								<td><input type='text' size='7' name='out1'></td>
								<td><input type='text' size='7' name='out2'></td>
								<td><input type='text' size='7' name='out3'></td>
								<td><input type='text' size='7' name='out4'></td>
								<td><input type='text' size='7' name='out5'></td>
								<td></td>
							</tr>";

		$confirm .= "
							<tr><td><br></td></tr>
						</table>
					</td>
				</tr>";
	}

	if($all == 2)
	{
		db_connect();
		$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
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
						<tr><td colspan='2'><br></td></tr>
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
			$val = '';
			if(pg_numrows($prnInvRslt) == 1) {$val = $amt;}
			$confirm .= "
					<tr class='".bg_class()."'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
						<td>$sup[currency] $inv[fbalance]</td>
						<td>$inv[odate]</td>";
			$i++;

			$confirm .= "
						<td><input type='text' name='paidamt[$invid]' size='10' value='$val'></td>
					</tr>";
		}
		if($out > 0) {
			$confirm .= "
					<tr class='".bg_class()."'>
						<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out </b></td>
					</tr>";}
	}

	$confirm .= "
				<input type='hidden' name='out' value='$out'>
				<tr>
					<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
					<td align='right'><input type='submit' value='Confirm &raquo'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
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

	if(!isset($out1)) {$out1 = '';}
	if(!isset($out2)) {$out2 = '';}
	if(!isset($out3)) {$out3 = '';}
	if(!isset($out4)) {$out4 = '';}
	if(!isset($out5)) {$out5 = '';}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
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

	$amt = sprint($amt);
	$lamt = sprint($amt * $rate);

	if(sprint(($tot+$out+$out1+$out2+$out3+$out4+$out5) - $amt) != 0){
			return "<li>$tot - $amt The total amount for Invoices not equal to the amount received. Please check the details.<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}


	$confirm = "
			<h3>New International Bank Payment</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='descript' value='$descript'>
				<input type='hidden' name='cheqnum' value='$cheqnum'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='out' value='$out'>
				<input type='hidden' name='amt' value='$amt'>
				<input type='hidden' name='rate' value='$rate'>";

	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Supplier name
	$sql = "SELECT supno,supname,currency FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$confirm .= "
			<tr>
				<th colspan='2'>Payment Details</th>
			</tr>
	<tr class='".bg_class()."'>
		<td>Account</td>
		<td>$bank[accname] - $bank[bankname]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Date</td>
		<td valign='center'>$date</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Paid To</td>
		<td valign='center'>($sup[supno]) $sup[supname]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Description</td>
		<td valign='center'>$descript</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Cheque Number</td>
		<td valign='center'>$cheqnum</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Amount</td>
		<td valign='center'>$sup[currency] $amt | ".CUR." $lamt</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Exchange rate</td>
		<td valign='center'>".CUR." / $sup[currency] $rate</td>
	</tr>";

	if($all==0)
	{
		// Layout
		$confirm .= "
				<tr><td colspan='2'><br></td></tr>
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

				db_connect();
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE purid='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE intpurid='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class='err'> - Invalid ord number $invids[$key].";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2']>0) {$inv['invid'] = $inv['invid2'];}

				$invid = $inv['invid'];


				$confirm .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>$sup[currency] $inv[fbalance]</td>
							<td>$inv[odate]</td>";
				$confirm .= "
							<td>$sup[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
						</tr>";
				$i++;

			}
		}
		if($out > 0){
			$confirm .= "
					<tr class='".bg_class()."'>
						<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out </b></td>
					</tr>";
		}

	}

	if($all == 1)
	{
		$age30 = sage($supid, 59);
		$age60 = sage($supid, 89);
		$age90 = sage($supid, 119);
		$age120 = sage($supid, 149);
		$bgColor = TMPL_tblDataColor2;
		$i = 0;
		if($out1 > 0)
		{
			// Connect to database
			db_connect();
			$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND pdate >='".extlib_ago(29)."' AND pdate <='".extlib_ago(-1)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
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
						<tr class='".bg_class()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>$sup[currency] $inv[fbalance]</td>
							<td>$inv[odate]</td>";
				if($out1 >= $inv['fbalance']) {
					$val = $inv['fbalance'];
					$out1 = $out1-$inv['fbalance'];
				}else {
					$val = $out1;
					$out1 = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$sup[currency] $val</td>
						</tr>";
				$i++;
			}
			if($out1 > 0){
				$confirm .= "
						<tr class='".bg_class()."'>
							<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out1 (Current) </b></td>
						</tr>";}
		}
		if($out2 > 0)
		{
			if($out2 > $age30){return "You cannot allocate $sup[currency] $out2 to 30 days, you only owe $sup[currency] $age30";}
			// Connect to database
			db_connect();
			$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND pdate >='".extlib_ago(59)."' AND pdate <='".extlib_ago(29)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'><h3>Outstanding Purchases</h3></td></tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
								<td>$sup[currency] $inv[fbalance]</td>
								<td>$inv[odate]</td>";
				if($out2 >= $inv['fbalance']) {
					$val = $inv['fbalance'];
					$out2 = $out2-$inv['fbalance'];
				}else {
					$val = $out2;
					$out2 = 0;
				}

				$confirm .= "
								<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$sup[currency] $val</td>
							</tr>";
				$i++;
			}
			if($out2 > 0) {$confirm .= "
							<tr class='".bg_class()."'>
								<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out2 (30 days)</b></td>
							</tr>";}
		}
		if($out3 > 0)
		{
			if($out3 > $age60){
				return "You cannot allocate $sup[currency] $out3 to 60 days, you only owe $sup[currency] $age60";
			}

			// Connect to database
			db_connect();
			$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND pdate >='".extlib_ago(89)."' AND pdate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
								<tr><td colspan='2'><br></td></tr>
								<tr><td colspan='2'><h3>Outstanding Purchases</h3></td></tr>
								<tr>
									<th>Purchase</th>
									<th>Outstanding Amount</th>
									<th>Date</th>
									<th>Amount</th>
								</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
								<td>$sup[currency] $inv[fbalance]</td>
								<td>$inv[odate]</td>";
				if($out3 >= $inv['fbalance']){
					$val = $inv['fbalance'];
					$out3 = $out3-$inv['fbalance'];
				}else {
					$val = $out3;
					$out3 = 0;
				}

				$confirm .= "
								<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$sup[currency] $val</td>
							</tr>";
				$i++;
			}
			if($out3 > 0) {$confirm .= "
							<tr class='".bg_class()."'>
								<td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out3 (60 days)</b></td>
							</tr>";}
		}
		if($out4 > 0)
		{
			if($out4 > $age90){return "You cannot allocate $sup[currency] $out4 to 90 days, you only owe $sup[currency] $age90";}
			// Connect to database
			db_connect();
			$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND pdate >='".extlib_ago(119)."' AND pdate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'><h3>Outstanding Purchases</h3></td></tr>
							<tr>
								<th>Purchase</th>
								<th>Outstanding Amount</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];

				$confirm .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
								<td>$sup[currency] $inv[fbalance]</td>
								<td>$inv[odate]</td>";
				if($out4 >= $inv['fbalance']) {$val = $inv['fbalance'];$out4 = $out4 - $inv['fbalance'];}
				else {$val = $out4;$out4 = 0;}

				$confirm .= "<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$sup[currency] $val</td></tr>";
				$i++;
			}
			if($out4 > 0) {$confirm .= "<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will debit the supplier's account with $sup[currency] $out4 (90 days)</b></td></tr>";}
		}
		if($out5 > 0)
		{
			if($out5 > $age120){return "You cannot allocate $sup[currency] $out5 to 120 days, you only owe $sup[currency] $age120";}
			// Connect to database
			db_connect();
			$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE supid = '$supid' AND fbalance>0 AND pdate >='".extlib_ago(149)."' AND pdate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if ($inv['invid'] == 0) {continue;}
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				if($i == 0)
				{
					$confirm .= "
						<tr><td colspan=2><br></td></tr>
						<tr><td colspan=2><h3>Outstanding Purchases</h3></td></tr>
						<tr><th>Purchase</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invid]</td><td>$sup[currency] $inv[fbalance]</td><td>$inv[odate]</td>";
				if($out5 >= $inv['fbalance']) {$val = $inv['fbalance'];$out5 = $out5 - $inv['fbalance'];}
				else {$val = $out5;$out5 = 0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>$sup[currency] $val</td></tr>";
				$i++;
			}
			if($out5 > 0) {$confirm .= "<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will debit the supplier's account with $sup[currency] $out5 (120 days)</b></td></tr>";}
		}
	}

	if($all == 2)
	{
		// Layout
		$confirm .= "<tr><td colspan=2><br></td></tr>
		<tr><td colspan='2'><h3>Outstanding Purchases</h3></td></tr>
		<!--<table ".TMPL_tblDflts." width=90%>-->
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

				db_connect();
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE purid='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,fbalance,pdate as odate FROM suppurch WHERE intpurid='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class=err> - Invalid ord number $invids[$key].";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2']>0) {$inv['invid']=$inv['invid2'];}

				$invid = $inv['invid'];

				$confirm .= "
						<tr bgcolor='$bgColor'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invid]</td>
							<td>$sup[currency] $inv[fbalance]</td>
							<td>$inv[odate]</td>
							<td>$sup[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
						</tr>";
				$i++;

			}
		}
		if($out > 0) {$confirm .="<tr class='".bg_class()."'><td colspan='5'><b>A general transaction will debit the supplier's account with $sup[currency] $out </b></td></tr>";}

	}

	$confirm .= "
		<input type='hidden' name='out1' value='$out1'>
		<input type='hidden' name='out2' value='$out2'>
		<input type='hidden' name='out3' value='$out3'>
		<input type='hidden' name='out4' value='$out4'>
		<input type='hidden' name='out5' value='$out5'>
		<tr>
			<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
			<td align='right'><input type='submit' value='Write &raquo'></td>
		</tr>
	</form>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
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

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
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
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
			return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$bank = pg_fetch_array($rslt);

	db_connect();
	# Supplier name
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	# Update xrate
	xrate_update($sup['fcid'], $rate, "suppurch", "id");
	sup_xrate_update($sup['fcid'], $rate);
	bank_xrate_update($sup['fcid'], $rate);
	$lamt = sprint($amt * $rate);

	# date format
	$sdate = explode("-", $date);
	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
	$cheqnum = 0 + $cheqnum;
	$pay = "";
	$accdate=$sdate;

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";

	db_connect();

	if($all == 0)
	{
		$ids = "";
		$purids = "";
		$fpamounts = "";
		$pamounts = "";
		$pdates = "";

		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		if(isset($invids))
		{
			foreach($invids as $key => $value)
			{
				$lpaidamt[$key] = sprint($paidamt[$key] * $rate);

				#debt invoice info
				$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY fbalance LIMIT 1";
				$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'>Invalid Invoice Number.</li>";
				}
				$pur = pg_fetch_array($invRslt);

				# reduce the money that has been paid
				$sql = "UPDATE suppurch SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - '$paidamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				$ids .= "|$pur[id]";
				$purids .= "|$invids[$key]";
				$fpamounts .= "|$paidamt[$key]";
				$pamounts .= "|$lpaidamt[$key]";
				$pdates .= "|$pur[pdate]";
			}
		}

		$samount = ($amt - ($amt * 2));

		if($out > 0) { recordDT($out, $sup['supid']); }

		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate', 'Payment','$cheqnum','$bank[accnum]', '".USER_DIV."')";
		$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		suppledger($sup['supid'], $bank['accnum'], $sdate, $cheqnum, "Payment for purchases", $lamt, "d");
		db_connect();

		# Update the supplier (make fbalance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Record the payment record
		// $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '".USER_DIV."')";
		//2 $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, supid, ids, purids, pamounts, pdates, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '".USER_DIV."')";
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, supid, ids, purids, pamounts, fpamounts, pdates, fcid, currency, location, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$fpamounts', '$pdates', '$sup[fcid]', '$sup[currency]', '$sup[location]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Update the bankacct table (make fbalance less) [used for cashbook fc value]
		$sql = "UPDATE bankacct SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx)<1) {
			return "Invalid bank acc.";
		}
		$link=pg_fetch_array($Rx);

		writetrans($dept['credacc'],$link['accnum'], $accdate, $refnum, $lamt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');

		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	if($all == 1)
	{
		$ids = "";
		$purids = "";
		$fpamounts = "";
		$pamounts = "";
		$pdates = "";

		# Begin updates
		//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$lpaidamt[$key] = sprint($paidamt[$key] * $rate);

					# Get debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY fbalance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$fpamounts .= "|$paidamt[$key]";
					$pamounts .= "|$lpaidamt[$key]";
					$pdates .= "|$pur[pdate]";


				}
			}

		$samount = ($amt - ($amt * 2));

		if($out1 > 0) {recordDT($out1, $sup['supid']);}
		if($out2 > 0) {recordDT($out2, $sup['supid']);}
		if($out3 > 0) {recordDT($out3, $sup['supid']);}
		if($out4 > 0) {recordDT($out4, $sup['supid']);}
		if($out5 > 0) {recordDT($out5, $sup['supid']);}

		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate', 'Payment','$cheqnum','$bank[accnum]', '".USER_DIV."')";
		$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the supplier (make fbalance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Record the payment record
		// $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '".USER_DIV."')";
		//2 $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, supid, ids, purids, pamounts, pdates, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '".USER_DIV."')";
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, supid, ids, purids, pamounts, fpamounts, pdates, fcid, currency, location, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$fpamounts', '$pdates', '$sup[fcid]', '$sup[currency]', '$sup[location]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Update the bankacct table (make fbalance less) [used for cashbook fc value]
		$sql = "UPDATE bankacct SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		writetrans($dept['credacc'],$link['accnum'], $accdate, $refnum, $lamt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');
		# Commit updates
		//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

		suppledger($sup['supid'], $bank['accnum'], $sdate, $cheqnum, "Payment to Supplier", $lamt, "d");
		db_connect();
	}


	if($all == 2)
	{
		$ids = "";
		$purids = "";
		$fpamounts = "";
		$pamounts = "";
		$pdates = "";

		# Begin updates
		#pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$lpaidamt[$key] = sprint($paidamt[$key] * $rate);

					# Get debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY fbalance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - '$paidamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$samount = ($paidamt[$key] - ($paidamt[$key] * 2));
					$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc,div) VALUES('$sup[supid]','$samount','$sdate', 'Payment - Purchase: $invids[$key]','$cheqnum','$bank[accnum]', '".USER_DIV."')";
					$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

					suppledger($sup['supid'], $bank['accnum'], $sdate, $invids[$key], "Payment for Purchase No. $invids[$key]", $paidamt[$key], "d");
					db_connect();

					# record the payment on the statement

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$fpamounts .= "|$paidamt[$key]";
					$pamounts .= "|$lpaidamt[$key]";
					$pdates .= "|$pur[pdate]";


				}
			}

		$samount = ($amt - ($amt * 2));

		# Update the supplier (make fbalance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, supid, ids, purids, pamounts, fpamounts, pdates, fcid, currency, location, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$fpamounts', '$pdates', '$sup[fcid]', '$sup[currency]', '$sup[location]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Update the bankacct table (make fbalance less) [used for cashbook fc value]
		$sql = "UPDATE bankacct SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$refnum = getrefnum($accdate);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		writetrans($dept['credacc'], $link['accnum'], $accdate, $refnum, $lamt, "Supplier Payment to $sup[supname]");

		db_conn('cubit');
		# Commit updates
		#pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}

    db_conn('cubit');
	$Sl = "DELETE FROM suppurch WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2)";
	$Rx = db_exec($Sl);

	# status report
	$write = "
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>International Bank Payment</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>International Bank Payment added to cash book.</td>
		</tr>
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
					<tr class='".bg_class()."'>
						<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='bank-pay-add.php'>Add Bank Payment</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
					</tr>
					<tr class='".bg_class()."'>
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
	$sql = "SELECT sum(fbalance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <='".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Take care of nasty zero
	return $sum['sum'] += 0;

}


function recordDT($amount, $supid, $rate =0)
{

	db_connect();

	$py = array();
	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid > 0 AND fbalance > 0 OR supid = '$supid' AND intpurid > 0 AND fbalance > 0 ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['fbalance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET fbalance = (fbalance - '$amount'::numeric(13,2)), balance = (balance - '$lamount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					if($dat['purid'] > 0){
						$py[] = "$dat[id]|$dat[purid]|$amount|$dat[pdate]";
					}else{
						$py[] = "$dat[id]|$dat[intpurid]|$amount|$dat[pdate]";
					}
					$amount = 0;
				}else{
					# remove small ones
					if($dat['fbalance'] < $amount){
						$amount -= $dat['fbalance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
						if($dat['purid'] > 0){
							$py[] = "$dat[id]|$dat[purid]|$dat[fbalance]|$dat[pdate]";
						}else{
							$py[] = "$dat[id]|$dat[intpurid]|$dat[fbalance]|$dat[pdate]";
						}
					}
				}
			}
		}
		if($amount > 0){
			$lamount = ($amount * $rate);
  			/* Make transaction record for age analysis */
			$edate = date("Y-m-d");
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fbalance, div) VALUES('$supid', '0', '$edate', '-$lamount', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$lamount = ($amount * $rate);
		/* Make transaction record for age analysis */
		$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fbalance, div) VALUES('$supid', '0', '$edate', '-$lamount', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	return $py;

}


?>

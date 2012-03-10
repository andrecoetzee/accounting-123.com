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

# Get settings
require("../settings.php");
require("../core-settings.php");
require ("../libs/ext.lib.php");

if(isset($HTTP_GET_VARS["cusid"])){
	$OUTPUT = sel_bank($HTTP_GET_VARS["cusid"]);
}elseif (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "method":
			$OUTPUT = method($HTTP_POST_VARS);
			break;
		case "alloc":
			$OUTPUT = alloc($HTTP_POST_VARS);
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = sel_bank();
	}
} else {
	# Display default output
	$OUTPUT = sel_bank();
}

# get templete
require("../template.php");



# Insert details
function sel_cus()
{
	// customers Drop down selections
	db_connect();
	$cust = "<select name='cusid'>";
	$sql = "SELECT cusnum,cusname,surname,currency,fcid FROM customers WHERE location = 'int' AND div = '".USER_DIV."' ORDER BY surname,cusname";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li> There are no Debtors in Cubit.";
	}
	while($cus = pg_fetch_array($cusRslt)){
		$cust .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$cust .="</select>";

	// layout
	$add = "
			<h3>New International Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='method'>
				<tr>
					<th colspan='2'>Select Customer</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customers</td>
					<td>$cust</td>
				</tr>
				<tr>
					<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Enter Details >'></td>
				</tr>
			</table>";
		
			# main table (layout with menu)
			$OUTPUT = "
			<center>
			<table width='100%'>
				<tr>
					<td width='65%' align='left'>$add</td>
					<td valign='top' align='center'>
						<table ".TMPL_tblDflts." width='65%'>
							<tr><th>Quick Links</th></tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>";

	return $OUTPUT;
}

# Insert details
function sel_bank($cusid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");

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


	global $HTTP_GET_VARS;

	// customers Drop down selections
	db_connect();
	$sql = "SELECT cusname,surname,currency,fcid,fcid,currency,accno,contname,tel FROM customers WHERE cusnum ='$cusid' AND div = '".USER_DIV."'";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li> Invalid Debtor.</li>";
	}
	$cus = pg_fetch_array($cusRslt);

	// customers Drop down selections
	db_connect();
	$banks = "<select name='bankid'>";
	$sql = "SELECT * FROM bankacct WHERE (fcid = '$cus[fcid]' OR  btype != 'int') AND div = '".USER_DIV."'";
	$bnkRs = db_exec($sql);
	if(pg_numrows($bnkRs) < 0){
		return "<li> There are no Bank Accounts in Cubit.</li>";
	}
	while($acc = pg_fetch_array($bnkRs)){
		$banks .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	if(isset($HTTP_GET_VARS['cash'])) {
		$banks .= "<option value='0'>Receive Cash</option>";
	}

	$banks .="</select>";

	// layout
	$add = "
			<h3>New International Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='method'>
				<input type='hidden' name='cusid' value='$cusid'>
				<tr>
					<th colspan='2'>Select Customer</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Banks</td>
					<td>$banks</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td>$cus[cusname] $cus[surname]</td>
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
				<tr>
					<td width='65%' align='left'>$add</td>
					<td valign='top' align='center'>
						<table ".TMPL_tblDflts." width='65%'>
							<tr><th>Quick Links</th></tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>";
	return $OUTPUT;

}



# Insert details
function method($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusid, "num", 1, 10, "Invalid Customer number.");
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

	// customers Drop down selections
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum ='$cusid' AND div = '".USER_DIV."'";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li> Invalid Debtor.</li>";
	}
	$cus = pg_fetch_array($cusRslt);
	$cust = "$cus[cusname] $cus[surname]";
	$currs = getSymbol($cus['fcid']);
	$rate = getRate($cus['fcid']);

	if($bankid!=0) {

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
	} else {
		$bcur = CUR;

		$bank['accname']='Cash';
		$bank['bankname']="";
	}

	$alls = "
			<select name='all'>
				<option value='0' selected>Auto</option>
				<option value='1'>Allocate To Age Analysis</option>
				<option value='2'>Allocate To Each invoice</option>
			</select>";

	$rate = sprint ($rate);

	// layout
	$add = "
			<h3>New International Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='alloc'>
				<input type='hidden' name='cusid' value='$cusid'>
				<input type='hidden' name='bankid' value='$bankid'>
				<tr>
					<th colspan='2'>Receipt Details</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td>$bank[accname] - $bank[bankname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date</td>
					<td>".mkDateSelect("date")."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Received from</td>
					<td valign='center'>$cust</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Description</td>
					<td valign='center'><textarea col='18' rows='3' name='descript'></textarea></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Cheque Number</td>
					<td valign='center'><input size='20' name='cheqnum'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount</td>
					<td valign='center'>$bcur <input type='text' size='13' name='amt'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $cus[currency] <input type='text' size='8' name='rate' value='$rate'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Allocation</td>
					<td>$alls</td>
				</tr>
				<tr>
					<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Allocate >'></td>
				</tr>
			</form>
			</table>";

	 $printCust = "
			<h3>Debtors Age Analysis</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Acc no.</th>
					<th>Contact Name</th>
					<th>Tel No.</th>
					<th>Current</th>
					<th>30 days</th>
					<th>60 days</th>
					<th>90 days</th>
					<th>120 days</th>
					<th>Total Outstanding</th>
				</tr>";

	$curr = sprint(age($cusid, 29));
	$age30 = sprint(age($cusid, 59));
	$age60 = sprint(age($cusid, 89));
	$age90 = sprint(age($cusid, 119));
	$age120 = sprint(age($cusid, 149));

	# Customer total
	$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

	$custtot = sprint($custtot);

	# Alternate bgcolor
	$printCust .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$cus[accno]</td>
				<td>$cus[contname]</td>
				<td>$cus[tel]</td>
				<td>$cus[currency] $curr</td>
				<td>$cus[currency] $age30</td>
				<td>$cus[currency] $age60</td>
				<td>$cus[currency] $age90</td>
				<td>$cus[currency] $age120</td>
				<td>$cus[currency] $custtot</td>
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
function alloc($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

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
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
	if(($amt<0.01)){$v->isOk ($amt, "float", 5, 1, "Amount to small.");}
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$rate += 0;
	if($rate == 0) $rate = 1;

	if($bankid!=0) {

		# Get bank account name
		db_connect();
		$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		if($bank['btype'] == 'int'){
			// Retrieve the currency of the foreign bank account
			db_conn("cubit");
			$sql = "SELECT * FROM currency WHERE fcid='$bank[fcid]'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve currency of the foreign bank account from Cubit.");
			$currs = pg_fetch_array($rslt);

			$bcur = $currs['symbol'];
			$amt = sprint($amt);
			$lamt = sprint($amt * $rate);
		}else{
			$lamt = sprint($amt);
			$amt = sprint($amt/$rate);
			$bcur = CUR;
		}

	} else {
		$bcur = CUR;

		$bank['accname']='Cash';
		$bank['bankname']="";

		$lamt = sprint($amt);
		$amt = sprint($amt/$rate);
		$bcur = CUR;
	}



	# Customer name
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
	$cusRslt = db_exec($sql);
	$cus = pg_fetch_array($cusRslt);
	$currs = getSymbol($cus['fcid']);


	$out = 0;

	$confirm = "
			<h3>New International Bank Receipt</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='cusid' value='$cusid'>
				<input type='hidden' name='descript' value='$descript'>
				<input type='hidden' name='cheqnum' value='$cheqnum'>
				<input type='hidden' name='amt' value='$amt'>
				<input type='hidden' name='rate' value='$rate'>
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
					<td>Received from</td>
					<td valign='center'>$cus[cusname] $cus[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Description</td>
					<td valign='center'>".nl2br($descript)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Cheque Number</td>
					<td valign='center'>$cheqnum</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount</td>
					<td valign='center'>$cus[currency] $amt | ".CUR." $lamt</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $cus[currency] $rate</td>
				</tr>";

	if($all==0)
	{
		$out=$amt;
		// Connect to database
		db_connect();
		$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		$i = 0;
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{
			if($i==0)
			{
				$confirm .= "
						<tr>
							<td colspan='2'><br></td>
						</tr>
						<tr>
							<td colspan='2'><h3>Outstanding Invoices</h3></td>
						</tr>
						<tr>
							<th>Invoice</th>
							<th>Outstanding Amount</th>
							<th>Terms</th>
							<th>Date</th>
							<th>Amount</th>
						</tr>";
			}

			$invid = $inv['invid'];
			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
						<td>$cus[currency] $inv[fbalance]</td>
						<td>$inv[terms] days</td>
						<td>$inv[odate]</td>";
			if($out>=$inv['fbalance']) {$val=$inv['fbalance'];$out=$out-$inv['fbalance'];}
			else {$val=$out;$out=0;}
			$i++;
			$val=sprint($val);
			$confirm .= "<td><input type=hidden name='paidamt[$invid]' size=10 value='$val'>$cus[currency] $val</td></tr>";
		}

		$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{
			if($i==0)
			{
				$confirm .= "
						<tr>
							<td colspan='2'><br></td>
						</tr>
						<tr>
							<td colspan='2'><h3>Outstanding Invoices</h3></td>
						</tr>
						<tr>
							<th>Invoice</th>
							<th>Outstanding Amount</th>
							<th></th>
							<th>Date</th>
							<th>Amount</th>
						</tr>";
			}

			$invid = $inv['invid'];
			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
						<td>$cus[currency] $inv[fbalance]</td>
						<td></td>
						<td>$inv[odate]</td>";
			if($out>=$inv['fbalance']) {$val=$inv['fbalance'];$out=$out-$inv['fbalance'];}
			else {$val=$out;$out=0;}
			$i++;
			$val=sprint($val);
			$confirm .= "
						<td><input type=hidden name='paidamt[$invid]' value='$val'><input type=hidden name=itype[$invid] value='Yes'>$cus[currency] $val</td>
					</tr>";
		}
		$out=sprint($out);

		if($out>0) {
			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out </b></td>
					</tr>";}
	}

	if($all==1)
	{
		$confirm .= "
				<tr>
					<td>
						<table ".TMMPL_tblDflts.">
							<tr><td><br></td></tr>
							<tr>
								<th>Current</th>
								<th>30 days</th>
								<th>60 days</th>
								<th>90 days</th>
								<th>120 days</th>
								<th>Total Outstanding</th>
							</tr>";

		$curr = sprint(age($cusid, 29));
		$age30 = sprint(age($cusid, 59));
		$age60 = sprint(age($cusid, 89));
		$age90 = sprint(age($cusid, 119));
		$age120 = sprint(age($cusid, 149));

		# Customer total
		$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

		# Alternate bgcolor
		$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$cus[currency] $curr</td>
					<td>$cus[currency] $age30</td>
					<td>$cus[currency] $age60</td>
					<td>$cus[currency] $age90</td>
					<td>$cus[currency] $age120</td>
					<td>$cus[currency] $custtot</td>
				</tr>";
		$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
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

	if($all==2)
	{
		// Connect to database
		db_connect();
		$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND div = '".USER_DIV."'";
		$prnInvRslt = db_exec($sql);
		$tempi=pg_numrows($prnInvRslt);
		if(pg_numrows($prnInvRslt) < 1){
			$sql = "SELECT invnum FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND div = '".USER_DIV."'";
			$prnInvRslt = db_exec($sql);
			if(pg_numrows($prnInvRslt) < 1){
				return "<li class='err'> There are no outstanding invoices for the selected debtor in Cubit.<br>
					To make a payment in advance please select Auto Allocation";
			}

		} elseif ($tempi>0) {
			$confirm .= "
					<tr><td colspan='2'><br></td></tr>
					<tr>
						<td colspan='2'><h3>Outstanding Invoices</h3></td>
					</tr>
					<tr>
						<th>Invoice</th>
						<th>Outstanding Amount</th>
						<th>Terms</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

			$i = 0; // for bgcolor
			while($inv = pg_fetch_array($prnInvRslt)){

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				$val='';
				if(pg_numrows($prnInvRslt)==1) {$val=$amt;}
				$confirm .= "
							<td><input type='text' name='paidamt[$invid]' size='10' value='$val'></td>
						</tr>";
			}
		}

		$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND div = '".USER_DIV."'";
		$prnInvRslt = db_exec($sql);
		if(pg_numrows($prnInvRslt)>0) {
			$confirm .= "
					<tr><td colspan='2'><br></td></tr>
					<tr>
						<td colspan='2'><h3>Outstanding Invoices</h3></td>
					</tr>
					<tr>
						<th>Invoice</th>
						<th>Outstanding Amount</th>
						<th></th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";
			$i = 0; // for bgcolor
			while($inv = pg_fetch_array($prnInvRslt)){

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				$val='';
				if(pg_numrows($prnInvRslt)==1) {$val=$amt;}
				$confirm .= "
							<td><input type='text' name='paidamt[$invid]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='YnYn'></td>
						</tr>";
			}
		}
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
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $confirm;
}

# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(!isset($out1)) {$out1='';}
	if(!isset($out2)) {$out2='';}
	if(!isset($out3)) {$out3='';}
	if(!isset($out4)) {$out4='';}
	if(!isset($out5)) {$out5='';}

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

	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
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

	$out +=0;
	$out1 +=0;
	$out2 +=0;
	$out3 +=0;
	$out4 +=0;
	$out5 +=0;

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

	$tot = sprint($tot);
	$amt = sprint($amt);
	$lamt = sprint($amt * $rate);

	$out=sprint($out);
	if(sprint(($tot+$out+$out1+$out2+$out3+$out4+$out5) - $amt) != 0){
			return "<li>The total amount for Invoices not equal to the amount received. Please check the details.<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
			<p>
			<table ".TMPL_tblDflts.">
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}


	$confirm = "
			<h3>New International Bank Receipt</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='bankid' value='$bankid'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='cusid' value='$cusid'>
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

	if($bankid==0) {
		$bank['accname']="Cash";
		$bank['bankname']="";
	}

	# Customer name
	$sql = "SELECT cusname,surname,currency,fcid FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
	$cusRslt = db_exec($sql);
	$cus = pg_fetch_array($cusRslt);

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
				<td>Received from</td>
				<td valign='center'>$cus[cusname] $cus[surname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>$cus[currency] $amt | ".CUR." $lamt</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Exchange rate</td>
				<td valign='center'>".CUR." / $cus[currency] $rate</td>
			</tr>";

	if($all==0)
	{
		// Layout
		$confirm .= "
				<tr><td colspan='2'><br></td></tr>
				<tr>
					<td colspan='2'><h3>Invoices</h3></td>
				</tr>
				<!--<table ".TMPL_tblDflts." width='90%'>-->
					<tr>
						<th>Invoice Number</th>
						<th>Outstanding amount</th>
						<th>Terms</th>
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

				$ii=$invids[$key];
				if(!isset($itype[$ii])) {

					# Get all the details
					$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}
					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					$confirm .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
								<td>$cus[currency] $inv[fbalance]</td>
								<td>$inv[terms] days</td>
								<td>$inv[odate]</td>";
					$confirm .= "
								<td>$cus[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
							</tr>";
					$i++;

				} else {

					# Get all the details
					$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}
					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					$confirm .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
								<td>$cus[currency] $inv[fbalance]</td>
								<td></td>
								<td>$inv[odate]</td>";
					$confirm .= "
								<td>$cus[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'> <input type='hidden' name='itype[$invid]' value='y'>$paidamt[$invid]</td>
							</tr>";
					$i++;

				}
			}
		}
	$out=sprint($out);
	if($out>0) {
		$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out </b></td>
				</tr>";
	}

	}
	if($all==1)
	{
		$age30 = sprint(age($cusid, 59));
		$age60 = sprint(age($cusid, 89));
		$age90 = sprint(age($cusid, 119));
		$age120 = sprint(age($cusid, 149));

		$i = 0;
		if($out1>0)
		{
			// Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND age = 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND odate >='".extlib_ago(29)."' AND odate <='".extlib_ago(-1)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'>
								<h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th>Terms</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				if($out1>=$inv['fbalance']) {$val=$inv['fbalance'];$out1=$out1-$inv['fbalance'];}
				else {$val=$out1;$out1=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}

			// Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND age = 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND sdate >='".extlib_ago(29)."' AND sdate <='".extlib_ago(-1)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'>
								<h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th></th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				if($out1>=$inv['fbalance']) {$val=$inv['fbalance'];$out1=$out1-$inv['fbalance'];}
				else {$val=$out1;$out1=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='n'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}

			$out1=sprint($out1);
			if($out1>0) {
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out1 (Current) </b></td>
						</tr>";
			}
		}
		if($out2>0)
		{
			if($out2>$age30){
				return "You cannot allocate $cus[currency] $out2 to 30 days, the client's 30 days balance is only $cus[currency] $age30 <p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND age = 1 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND odate >='".extlib_ago(59)."' AND odate <='".extlib_ago(29)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'>
								<h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th>Terms</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				if($out2>=$inv['fbalance']) {$val=$inv['fbalance'];$out2=$out2-$inv['fbalance'];}
				else {$val=$out2;$out2=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND age = 1 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND sdate >='".extlib_ago(59)."' AND sdate <='".extlib_ago(29)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr>
								<td colspan='2'><h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th></th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				if($out2>=$inv['fbalance']) {$val=$inv['fbalance'];$out2=$out2-$inv['fbalance'];}
				else {$val=$out2;$out2=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='no'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}
			$out2=sprint($out2);
			if($out2>0) {
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out2 (30 days)</b></td>
						</tr>";
			}
		}
		if($out3>0)
		{
			if($out3>$age60){return "You cannot allocate $cus[currency] $out3 to 60 days, the client only owe you $cus[currency] $age60";}
			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND age = 2 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND odate >='".extlib_ago(89)."' AND odate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'>
								<h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th>Terms</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				if($out3>=$inv['fbalance']) {$val=$inv['fbalance'];$out3=$out3-$inv['fbalance'];}
				else {$val=$out3;$out3=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND age = 2 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND sdate >='".extlib_ago(89)."' AND sdate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr><td colspan='2'>
								<h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th></th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				if($out3>=$inv['fbalance']) {$val=$inv['fbalance'];$out3=$out3-$inv['fbalance'];}
				else {$val=$out3;$out3=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='1'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}
			$out3=sprint($out3);
			if($out3>0) {
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out3 (60 days)</b></td>
						</tr>";
			}
		}
		if($out4>0)
		{
			if($out4>$age90){return "You cannot allocate $cus[currency] $out4 to 90 days, the client only owe you $cus[currency] $age90";}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND age = 3 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND odate >='".extlib_ago(119)."' AND odate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr>
								<td colspan='2'><h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th>Terms</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				if($out4>=$inv['fbalance']) {$val=$inv['fbalance'];$out4=$out4-$inv['fbalance'];}
				else {$val=$out4;$out4=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND age = 3 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND sdate >='".extlib_ago(119)."' AND sdate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr>
								<td colspan='2'><h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th></th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				if($out4>=$inv['fbalance']) {$val=$inv['fbalance'];$out4=$out4-$inv['fbalance'];}
				else {$val=$out4;$out4=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='2'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}
			$out4=sprint($out4);
			if($out4>0) {
				$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out4 (90 days)</b></td>
					</tr>";}
		}
		if($out5>0)
		{
			if($out5>$age120){return "You cannot allocate $cus[currency] $out5 to 120 days, the client only owe you $cus[currency] $age120";}
			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND age = 4 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND fbalance>0 AND odate >='".extlib_ago(149)."' AND odate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr>
								<td colspan='2'><h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th>Terms</th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				if($out5>=$inv['fbalance']) {$val=$inv['fbalance'];$out5=$out5-$inv['fbalance'];}
				else {$val=$out5;$out5=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND age = 4 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND fbalance>0 AND sdate >='".extlib_ago(149)."' AND sdate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if($i==0)
				{
					$confirm .= "
							<tr><td colspan='2'><br></td></tr>
							<tr>
								<td colspan='2'><h3>Outstanding Invoices</h3></td>
							</tr>
							<tr>
								<th>Invoice</th>
								<th>Outstanding Amount</th>
								<th></th>
								<th>Date</th>
								<th>Amount</th>
							</tr>";
				}

				$invid = $inv['invid'];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				if($out5>=$inv['fbalance']) {$val=$inv['fbalance'];$out5=$out5-$inv['fbalance'];}
				else {$val=$out5;$out5=0;}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'><input type='hidden' name='itype[$invid]' value='my'>$cus[currency] $val</td>
						</tr>";
				$i++;
			}
			$out5=sprint($out5);
			if($out5>0) {
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='5'><b>A general transaction will credit the client's account with $cus[currency] $out5 (120 days)</b></td>
						</tr>";
			}
		}
	}

	if($all==2)
	{
		// Layout
		$confirm .= "
				<tr><td colspan='2'><br></td></tr>
				<tr>
					<td colspan='2'><h3>Invoices</h3></td>
				</tr>
				<!--<table ".TMPL_tblDflts." width='90%'>-->
					<tr>
						<th>Invoice Number</th>
						<th>Outstanding amount</th>
						<th>Terms</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

		$i = 0; // for bgcolor
		foreach($invids as $key => $value){
			if($paidamt[$invids[$key]] < 0.01){
				continue;
			}

			$ii=$invids[$key];
			if(!isset($itype[$ii])) {

				db_connect();
				# Get all the details
				$sql = "SELECT invnum,invid,fbalance,terms,odate FROM invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'> - Invalid ord number $invids[$key].";
				}
				$inv = pg_fetch_array($invRslt);

				$invid = $inv['invid'];


				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>";
				$confirm .= "
							<td>$cus[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'>$paidamt[$invid]</td>
						</tr>";
				$i++;
			} else {

				db_connect();
				# Get all the details
				$sql = "SELECT invnum,invid,fbalance,sdate as odate FROM nons_invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'> - Invalid ord number $invids[$key].";
				}
				$inv = pg_fetch_array($invRslt);

				$invid = $inv['invid'];

				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
							<td>$cus[currency] $inv[fbalance]</td>
							<td></td>
							<td>$inv[odate]</td>";
				$confirm .= "
							<td>$cus[currency] <input type='hidden' name='paidamt[]' size='7' value='$paidamt[$invid]'><input type='hidden' name='itype[$invid]' value='PcP'>$paidamt[$invid]</td>
						</tr>";
				$i++;
			}
		}
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
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";

        return $confirm;
}

# write
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

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
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(currant).");
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
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);

	# check if link exists
	if(pg_numrows($rslt)<1) {
		$Sl="SELECT * FROM accounts WHERE accname='Cash on Hand'";
		$Rg=db_exec($Sl);
		if(pg_num_rows($Rg)<1) {
			if($bankid==0) {
				return "There is no 'Cash on Hand' account, there was one, but its not there now, you must have deleted it, if you want to use cash functionality please create a 'Cash on Hand' account.";
			} else {
				return "Invalid bank acc.";
			}
		}
		$add=pg_fetch_array($Rg);
		$bank['accnum']=$add['accid'];
	} else {
		$bank=pg_fetch_array($rslt);
	}

	db_connect();
	# Customer name
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
	$cusRslt = db_exec($sql);
	$cus = pg_fetch_array($cusRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT debtacc FROM departments WHERE deptid ='$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	# Update xrate
	cus_xrate_update($cus['fcid'], $rate);
	xrate_update($cus['fcid'], $rate, "invoices", "invid");
	xrate_update($cus['fcid'], $rate, "custran", "id");
	bank_xrate_update($cus['fcid'], $rate);
	$lamt = sprint($amt * $rate);

	# date format
	$sdate = explode("-", $date);
	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
	$cheqnum = 0 + $cheqnum;
	$pay = "";

	$accdate = date("Y-m-d");

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$famounts = "";
	$amounts = "";
	$invprds = "";
	$rages = "";

	db_connect();

	if($all==0)
	{
		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$ii = $invids[$key];
					$lpaidamt[$key] = sprint($paidamt[$key] * $rate);
					if(!isset($itype[$ii])) {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,odate FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'>Invalid Invoice Number.</li>";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE invoices SET balance = (balance - $lpaidamt[$key]::numeric(13,2)),fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "
							INSERT INTO stmnt 
								(cusnum, invid, amount, date, type, div, allocation_date) 
							VALUES 
								('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $lpaidamt[$key], "c");
						db_connect();

						$rinvids .= "|$invids[$key]";
						$famounts .= "|$paidamt[$key]";
						$amounts .= "|$lpaidamt[$key]";
						$invprds .= "|$inv[prd]";
						$rages .= "|0";
						$invidsers .= " - $inv[invnum]";
					} else {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,descrip,age,odate FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'>Invalid Invoice Number.</li>";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE nons_invoices SET balance = (balance - $lpaidamt[$key]::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] += 0;
						# record the payment on the statement
						$sql = "
							INSERT INTO stmnt 
								(cusnum, invid, amount, date, type, div, allocation_date) 
							VALUES 
								('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$inv[odate]')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $lpaidamt[$key], "c");
						db_connect();

						frecordCT($paidamt[$key], $cus['cusnum'], $rate, $cus['fcid']);

						$rinvids .= "|$invids[$key]";
						$famounts .= "|$paidamt[$key]";
						$amounts .= "|$lpaidamt[$key]";
						$invprds .= "|0";
						$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";
					}
				}
			}

			# update the customer (make fbalance less)
			$sql = "UPDATE customers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# record the payment record
			// $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			// 2 $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, famounts, invprds, rages, fcid, currency, location, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$famounts', '$invprds', '$rages', '$cus[fcid]', '$cus[currency]', '$cus[location]', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			# Update the bankacct table (make fbalance less) [used for cashbook fc value]
			$sql = "UPDATE bankacct SET balance = (balance + '$lamt'::numeric(13,2)), fbalance = (fbalance + '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$refnum = getrefnum($sdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid' AND accid!=0";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				$Sl="SELECT * FROM accounts WHERE accname='Cash on Hand'";
				$Rg=db_exec($Sl);
				if(pg_num_rows($Rg)<1) {
					if($bankid==0) {
						return "There is no 'Cash on Hand' account, there was one, but its not there now, if you want to use cash functionality please create a 'Cash on Hand' account.";
					} else {
						return "Invalid bank acc.";
					}
				}
				$add=pg_fetch_array($Rg);
				$link['accnum']=$add['accid'];
			} else {
				$link=pg_fetch_array($Rx);
			}

			writetrans($link['accnum'],$dept['debtacc'], $sdate, $refnum, $lamt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");
			db_conn('cubit');

		if($out > 0) {
			frecordCT($out, $cus['cusnum'], $rate, $cus['fcid']);
			$Sl = "
				INSERT INTO stmnt 
					(cusnum, invid, amount, date, type, div, allocation_date) 
				VALUES 
					('$cus[cusnum]','0','".($out*(-1))."','$sdate', 'Payment Received.', '".USER_DIV."', '$sdate')";
			$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

			custledger($cus['cusnum'], $bank['accnum'], $sdate, "PAYMENT", "Payment received.", ($out * $rate), "c");
			db_connect();
		}
		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}

	if($all==1)
	{
		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$ii=$invids[$key];

					$lpaidamt[$key] = sprint($paidamt[$key] * $rate);

					if(!isset($itype[$ii])) {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,odate FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE invoices SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "
							INSERT INTO stmnt 
								(cusnum, invid, amount, date, type, div, allocation_date) 
							VALUES 
								('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $lpaidamt[$key], "c");
						db_connect();

						$rinvids .= "|$invids[$key]";
						$famounts .= "|$paidamt[$key]";
						$amounts .= "|$lpaidamt[$key]";
						$invprds .= "|$inv[prd]";
						$rages .= "|0";
						$invidsers .= " - $inv[invnum]";

					} else {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,descrip,age,odate FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE nons_invoices SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] += 0;
						# record the payment on the statement
						$sql = "
							INSERT INTO stmnt 
								(cusnum, invid, amount, date, type, div, allocation_date) 
							VALUES 
								('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$inv[odate]')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $lpaidamt[$key], "c");
						db_connect();

						frecordCT($paidamt[$key], $cus['cusnum'], $rate, $cus['fcid']);

						$rinvids .= "|$invids[$key]";
						$famounts .= "|$paidamt[$key]";
						$amounts .= "|$lpaidamt[$key]";
						$invprds .= "|0";
						$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";

					}
				}
			}

			# update the customer (make fbalance less)
			$sql = "UPDATE customers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# record the payment record
			// $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			// 2 $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, famounts, invprds, rages, fcid, currency, location, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$famounts', '$invprds', '$rages', '$cus[fcid]', '$cus[currency]', '$cus[location]', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			# Update the bankacct table (make fbalance less) [used for cashbook fc value]
			$sql = "UPDATE bankacct SET balance = (balance + '$lamt'::numeric(13,2)), fbalance = (fbalance + '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$refnum = getrefnum($sdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid' AND accid!=0";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				$Sl="SELECT * FROM accounts WHERE accname='Cash on Hand'";
				$Rg=db_exec($Sl);
				if(pg_num_rows($Rg)<1) {
					if($bankid==0) {
						return "There is no 'Cash on Hand' account, there was one, but its not there now, if you want to use cash functionality please create a 'Cash on Hand' account.";
					} else {
						return "Invalid bank acc.";
					}
				}
				$add=pg_fetch_array($Rg);
				$link['accnum']=$add['accid'];
			} else {
				$link=pg_fetch_array($Rx);
			}

			writetrans($link['accnum'],$dept['debtacc'], $sdate, $refnum, $lamt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

			db_conn('cubit');

			if(($out1+$out2+$out3+$out4+$out5)>0) {
				$Sl = "
					INSERT INTO stmnt 
						(cusnum, invid, amount, date, type, div, allocation_date) 
					VALUES 
						('$cus[cusnum]','0','".(($out1+$out2+$out3+$out4+$out5)*(-1))."','$sdate', 'Payment Received.', '".USER_DIV."', '$sdate')";
				$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $bank['accnum'], $sdate, "PAYMENT", "Payment received.", (($out1+$out2+$out3+$out4+$out5) * $rate), "c");
				db_connect();
			}

			if($out1>0) {frecordCT($out1, $cus['cusnum'], $rate, $cus['fcid']);}
			if($out2>0) {frecordCT($out2, $cus['cusnum'], $rate, $cus['fcid']);}
			if($out3>0) {frecordCT($out3, $cus['cusnum'], $rate, $cus['fcid']);}
			if($out4>0) {frecordCT($out4, $cus['cusnum'], $rate, $cus['fcid']);}
			if($out5>0) {frecordCT($out5, $cus['cusnum'], $rate, $cus['fcid']);}
		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	if($all==2)
	{
		# Begin updates
		//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# Debtors
			foreach($invids as $key => $value)
			{
				$ii = $invids[$key];
				$lpaidamt[$key] = sprint($paidamt[$key] * $rate);
				if(!isset($itype[$ii])) {

					# Get debt invoice info
					$sql = "SELECT prd,invnum,odate FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$inv = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE invoices SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO stmnt 
							(cusnum, invid, amount, date, type, div, allocation_date) 
						VALUES 
							('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $lpaidamt[$key], "c");
					db_connect();

					$rinvids .= "|$invids[$key]";
					$famounts .= "|$paidamt[$key]";
					$amounts .= "|$lpaidamt[$key]";
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				} else {

					# Get debt invoice info
					$sql = "SELECT prd,invnum,descrip,age,odate FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$inv = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE nons_invoices SET balance = (balance - '$lpaidamt[$key]'::numeric(13,2)), fbalance = (fbalance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO stmnt 
							(cusnum, invid, amount, date, type, div, allocation_date) 
						VALUES 
							('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$inv[odate]')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $lpaidamt[$key], "c");
					db_connect();

					frecordCT($paidamt[$key], $cus['cusnum'], $rate, $cus['fcid']);

					$rinvids .= "|$invids[$key]";
					$famounts .= "|$paidamt[$key]";
					$amounts .= "|$lpaidamt[$key]";
					$invprds .= "|0";
					$rages .= "|$inv[age]";
					$invidsers .= " - $inv[invnum]";
				}
			}

			# update the customer (make fbalance less)
			$sql = "UPDATE customers SET balance = (balance - '$lamt'::numeric(13,2)), fbalance = (fbalance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# record the payment record
			//2 $sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, famounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$famounts', '$invprds', '$rages', '".USER_DIV."')";

			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, famount, banked, accinv, cusnum, rinvids, amounts, famounts, invprds, rages, fcid, currency, location, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$lamt', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$famounts', '$invprds', '$rages', '$cus[fcid]', '$cus[currency]', '$cus[location]', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			# Update the bankacct table (make fbalance less) [used for cashbook fc value]
			$sql = "UPDATE bankacct SET balance = (balance + '$lamt'::numeric(13,2)), fbalance = (fbalance + '$amt'::numeric(13,2)) WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$refnum = getrefnum($accdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid' AND accid!=0";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				$Sl="SELECT * FROM accounts WHERE accname='Cash on Hand'";
				$Rg=db_exec($Sl);
				if(pg_num_rows($Rg)<1) {
					if($bankid==0) {
						return "There is no 'Cash on Hand' account, there was one, but its not there now, if you want to use cash functionality please create a 'Cash on Hand' account.";
					} else {
						return "Invalid bank acc.";
					}
				}
				$add=pg_fetch_array($Rg);
				$link['accnum']=$add['accid'];
			} else {
				$link=pg_fetch_array($Rx);
			}

			writetrans($link['accnum'],$dept['debtacc'], $sdate, $refnum, $lamt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");


		# Commit updates
		//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	db_conn('cubit');
	/* start moving invoices */

	# move invoices that are fully paid
	$sql = "SELECT * FROM invoices WHERE fbalance = 0 AND balance = 0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	while($invb = pg_fetch_array($invbRslt))
	{
		if($invb['prd'] == 0)
			$invb['prd'] = PRD_DB;
		db_conn($invb['prd']);

		# Insert invoice to period DB
		$sql = "INSERT INTO invoices(invid, invnum, deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, fcid, currency, xrate, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, fbalance, location, age, comm, discount, delivery, printed, done, div)";
		$sql .= " VALUES('$invb[invid]','$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', '$invb[fcid]', '$invb[currency]', '$invb[xrate]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , '$invb[total]', '0', '$invb[location]', '$invb[age]', '$invb[comm]', '$invb[discount]', '$invb[delivery]', 'y', 'y', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);
		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			db_conn($invb['prd']);
			# insert invoice items
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invb[invid]', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_connect();
		# Remove those invoices from running DB
		$sql = "DELETE FROM invoices WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# Remove those invoice items from running DB
		$sql = "DELETE FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	}

	/* end moving invoices */

	# status report
	$write = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr><th>Bank Receipt</th></tr>
				<tr class='datacell'><td>Bank Receipt added to cash book.</td></tr>
			</table>";

	# main table (layout with menu)
	$OUTPUT = "
			<center>
			<table width='90%'>
				<tr valign='top'>
					<td width=50%>$write</td>
					<td align='center'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr><th>Quick Links</th></tr>
							<tr bgcolor='".bgcolorg()."'><td><a href='bank-pay-add.php'>Add Bank Payment</a></td></tr>
							<tr bgcolor='".bgcolorg()."'><td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td></tr>
							<tr bgcolor='".bgcolorg()."'><td><a href='bank-recpt-inv.php'>Add Customer Payment</a></td></tr>
							<tr bgcolor='".bgcolorg()."'><td><a href='cashbook-view.php'>View Cash Book</a></td></tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
				</tr>
			</table>";

        return $OUTPUT;
}

function age($cusnum, $days)
{
	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	if(div_isset("DEBT_AGE", "mon")){
		switch($days){
			case 29:
				return ageage($cusnum, 0);
			case 59:
				return ageage($cusnum, 1);
			case 89:
				return ageage($cusnum, 2);
			case 119:
				return ageage($cusnum, 3);
			case 149:
				return ageage($cusnum, 4);
		}
	}

	# Get the current oustanding
	$sql = "SELECT sum(fbalance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <='".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(fbalance) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <='".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;
}

function ageage($cusnum, $age){
	# Get the current oustanding
	$sql = "SELECT sum(fbalance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(fbalance) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;
}


# records for CT
function frecordCT($amount, $cusnum, $rate, $fcid)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND fbalance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['fbalance'] > $amount){
					$lamount = ($amount * $rate);
					# Remove make amount less
					$sql = "UPDATE custran SET fbalance = (fbalance - '$amount'::numeric(13,2)), balance = (balance - '$lamount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0 ;
				}else{
					# remove small ones
					//if($dat['fbalance'] > $amount){
						$amount -= $dat['fbalance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					//}
				}
			}
		}
		if($amount > 0){
			$amount = ($amount * (-1));
			$lamount = ($amount * $rate);

			/* Make transaction record for age analysis */
			$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));
		$lamount = ($amount * $rate);

		/* Make transaction record for age analysis */
		$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}
?>

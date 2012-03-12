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
} elseif(isset($_GET["account"])) {
        # Display default output
        $OUTPUT =  method($_GET["account"]);
}elseif(isset($_GET["supid"])) {
        # Display default output
        $OUTPUT =  method($_GET["supid"]);
}else {
        # Display default output
        $OUTPUT = sel_sup();
}

# get templete
require("../template.php");




# Insert details
function sel_sup()
{

	global $_POST;

	extract($_POST);

	if(!isset($supid)) {
		$supid = 0;
	}

	// suppliers Drop down selections
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND location = 'loc' ORDER BY supname,supno";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
			return "
					<li> There are no Creditors in Cubit.</li>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				";
	}

//				<input type='hidden' size='5' name='pur' value=''>
//				<input type='hidden' size='5' name='inv' value=''>

	// layout
	$add = "
			<h3>New Bank Payment</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='method'>
				<tr>
					<th>Select Amount Of Payments</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><input type='text' name='rec_amount' size='3'></td>
				</tr>
				".TBL_BR."
				<tr>
					<td align='right'><input type='submit' value='Enter Details &raquo;'></td>
				</tr>
			</form>
			</table>";

			# main table (layout with menu)
			$OUTPUT = "
					<center>
					<table width='100%'>
						<tr>
							<td width='65%' align='left'>$add</td>
							<td valign='top' align='center'>"
								.mkQuickLinks(
									ql("bank-pay-supp.php", "Add Supplier Payment")
								)."
							</td>
						</tr>
					</table>";
        return $OUTPUT;

}



# Insert details
function method($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($rec_amount, "num", 1, 10, "Invalid supplier payment amount.");

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



	global $_GET;

	if(isset($_GET["e"])) {
		$ex = "<input type='hidden' name='e' value='y'>";
	} else {
		$ex = "";
	}

	if(!isset($all)) {
		$all = 0;
	}

	$as1 = "";
	$as2 = "";
	$as3 = "";

	if($all == 0) {
		$as1 = "selected";
	} elseif($all == 1) {
		$as2 = "selected";
	} else {
		$as3 = "selected";
	}

	$alls = "
			<select name='all'>
				<option value='0' $as1>Auto</option>
				<option value='1' $as2>Allocate To Age Analysis</option>
				<option value='2' $as3>Allocate To Each invoice</option>
			</select>";

	// layout
        $add = "
			<h3>New Multiple Bank Payments</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' size='5' name='pur' value=''>
				<input type='hidden' size='5' name='inv' value=''>
				<input type='hidden' name='key' value='alloc'>
				<input type='hidden' name='rec_amount' value='$rec_amount'>
				<tr>
					<th colspan='2'>Allocation</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' align='center'>$alls</td>
				</tr>
				".TBL_BR."
				$ex
				<tr>
					<th>Supplier</th>
					<th>Bank Account</th>
					<th>Date</th>
					<th>Description</th>
					<th>Reference</th>
					<th>Cheque Number</th>
					<th>Amount</th>
				</tr>";


	for($t = 0;$t < $rec_amount;$t++){
	
		db_connect();
	
		$get_sup = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND location = 'loc' ORDER BY supname,supno";
		$run_sup = db_exec($get_sup) or errDie("Unable to get supplier information.");
		if(pg_numrows($run_sup) < 1){
			return "
					<li class='err'>No Suppliers Found.</li>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
		}else {
			$supdrop = "<select name='supid[$t]'>";
			while ($sarr = pg_fetch_array($run_sup)){
				$supdrop .= "<option value='$sarr[supid]'>($sarr[supno]) $sarr[supname]</option>";
			}
			$supdrop .= "</select>";
		}
	
	
		$bankacc = "<select name='bankid[$t]'>";
		$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
		$banks = db_exec($sql);
		$numrows = pg_numrows($banks);
		if(empty($numrows)){
			return "<li class='err'> There are no accounts held at the selected Bank.
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}
		while($acc = pg_fetch_array($banks)){
			$bankacc .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}
		$bankacc .= "</select>";
	
	// 	if(isset($_GET["cash"])) {
	// 		$add.="<option value='0'>Pay Cash</option>";
	// 	}
	
	
	
		if(!isset($amt[$t])) {
			$amt[$t] = "";
			$descript[$t] = "";
			$cheqnum[$t] = "";
			$reference[$t] = "";
		}
	
		if(!isset($date_day[$t])){
			$date_year[$t] = date("Y");
			$date_month[$t] = date("m");
			$date_day[$t] = date("d");
		}
	
	
	
		$add .= "
				<tr bgcolor='".bgcolorg()."'>
					<td valign='center'>$supdrop</td>
					<td valign='center'>$bankacc</td>
					<td>".mkDateSelecta("date",$t,$date_year[$t],$date_month[$t],$date_day[$t])."</td>
					<td valign='center'><textarea cols='20' rows='3' name='descript[$t]'>$descript[$t]</textarea></td>
					<td valign='center'><input type='text' size='20' name='reference[$t]' value='$reference[$t]'></td>
					<td valign='center'><input size='16' name='cheqnum[$t]' value='$cheqnum[$t]'></td>
					<td valign='center'>".CUR." <input type='text' size='12' name='amt[$t]' value='$amt[$t]'></td>
				</tr>";
	
	}


	$add .= "
			".TBL_BR."
			<tr>
				<td valign='center' align='right' colspan='7'><input type='submit' value='Allocate &raquo;'></td>
			</tr>
		</form>
		</table>";

	$OUTPUT = "
			$add
			<p>
			<table ".TMPL_tblDflts.">
				<tr><th>Quick Links</th></tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='bank-pay-supp.php'>Add supplier payment</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $OUTPUT;

}



# confirm
function alloc($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		if(isset($e)) {
			header("Location: cashbook-entry.php");
			exit;
		}
		return sel_sup();
	}

	$passon = "";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($rec_amount, "num", 1, 10, "Invalid supplier payment amount.");

	for ($t = 0;$t < $rec_amount;$t++){
		$v->isOk ($supid[$t], "num", 1, 10, "Invalid supplier number. ($t)");
		$v->isOk ($bankid[$t], "num", 1, 30, "Invalid Bank Account. ($t)");
		$v->isOk ($date_day[$t], "num", 1,2, "Invalid Date day. ($t)");
		$v->isOk ($date_month[$t], "num", 1,2, "Invalid Date month. ($t)");
		$v->isOk ($date_year[$t], "num", 1,4, "Invalid Date Year. ($t)");
		if(strlen($date_year[$t]) <> 4){
			$v->isOk ($bankname, "num", 1, 1, "Invalid Date year. ($t)");
		}
		$v->isOk ($descript[$t], "string", 0, 255, "Invalid Description. ($t)");
		$v->isOk ($reference[$t], "string", 0, 50, "Invalid Reference Name/Number. ($t)");
		$v->isOk ($cheqnum[$t], "num", 0, 30, "Invalid Cheque number. ($t)");
		$v->isOk ($amt[$t], "float", 1, 10, "Invalid amount. ($t)");
		if(($amt[$t] < 0.01)){
			$v->isOk ($amt[$t], "float", 5, 1, "Amount too small. ($t)");
		}
		$date[$t] = mkdate($date_year[$t], $date_month[$t], $date_day[$t]);
		if(!checkdate($date_month[$t], $date_day[$t], $date_year[$t])){
			$v->isOk ($date[$t], "num", 1, 1, "Invalid date. ($t)");
		}

		$passon .= "
				<input type='hidden' name='bankid[$t]' value='$bankid[$t]'>
				<input type='hidden' name='date[$t]' value='$date[$t]'>
				<input type='hidden' name='supid[$t]' value='$supid[$t]'>
				<input type='hidden' name='descript[$t]' value='$descript[$t]'>
				<input type='hidden' name='reference[$t]' value='$reference[$t]'>
				<input type='hidden' name='cheqnum[$t]' value='$cheqnum[$t]'>
				<input type='hidden' name='amt[$t]' value='$amt[$t]'>
				<input type='hidden' name='date_day[$t]' value='$date_day[$t]'>
				<input type='hidden' name='date_month[$t]' value='$date_month[$t]'>
				<input type='hidden' name='date_year[$t]' value='$date_year[$t]'>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		//$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm.method($supid);
	}



	$confirm = "
			<h3>Confirm Multiple Bank Receipts</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='rec_amount' value='$rec_amount'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='pur' value=''>
				<input type='hidden' name='inv' value=''>
				$passon";


	for ($t=0;$t<$rec_amount;$t++){

		//$out = array();

		# Get bank account name
		db_connect();
		$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid[$t]' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank[$t] = pg_fetch_array($bankRslt);
	
		if(pg_num_rows($bankRslt)<1) {
			$bank[$t]['accname']="Cash";
			$bank[$t]['bankname']="";
		}
	
		# Supplier name
		$sql = "SELECT supno,supname FROM suppliers WHERE supid = '$supid[$t]' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		$sup = pg_fetch_array($supRslt);

		$bank1 = $bank[$t]['accname'];
		$bank2 = $bank[$t]['bankname'];

		$confirm .= "
				".TBL_BR."
				".TBL_BR."
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Supplier</h3></td>
				</tr>
				<tr>
					<th>Account</th>
					<th>Date</th>
					<th>Paid To</th>
					<th>Description</th>
					<th>Reference</th>
					<th>Cheque Number</th>
					<th>Amount</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$bank1 - $bank2</td>
					<td valign='center'>$date[$t]</td>
					<td valign='center'>($sup[supno]) $sup[supname]</td>
					<td valign='center'>".nl2br($descript[$t])."</td>
					<td valign='center'>$reference[$t]</td>
					<td valign='center'>$cheqnum[$t]</td>
					<td valign='center'>".CUR." $amt[$t]</td>
				</tr>";

			db_conn('cubit');

			$Sl = "SELECT purnum,supinv FROM purchases";
			$Ri = db_exec($Sl);

			while($pd = pg_fetch_array($Ri)) {
				$pid = $pd['purnum'];
				$supinv[$pid] = $pd['supinv'];
			}

			for($i = 1;$i < 13;$i++) {
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

			$out[$t] = $amt[$t];

			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,SUM(balance) AS balance,pdate as odate
					FROM suppurch WHERE supid = '$supid[$t]' AND div = '".USER_DIV."'
					GROUP BY purid, intpurid, pdate
					HAVING SUM(balance) > 0
					ORDER BY odate ASC";
			$prnInvRslt = db_exec($sql) or errDie("unable to get invoices.");
			$i = 0;
			while(($inv = pg_fetch_array($prnInvRslt))and($out[$t]>0))
			{
				//if ($inv['invid']==0) {continue;}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
					<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>$sinv</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out[$t] = $out[$t]-$inv['balance'];
				}else {
					$val = $out[$t];
					$out[$t] = 0;
				}
				$i++;
				$val = sprint($val);
				$confirm .= "
							<td><input type='hidden' name='paidamt[$invid]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out[$t] >= 0.01) {
				$confirm .="
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out[$t])." </b>
					</td>
				</tr>";
			}
		}



		if ($all == 1) {

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

			$curr[$t] = sage($supid[$t], 29);
			$age30[$t] = sage($supid[$t], 59);
			$age60[$t] = sage($supid[$t], 89);
			$age90[$t] = sage($supid[$t], 119);
			$age120[$t] = sage($supid[$t], 149);

			$supttot[$t] = sprint($curr[$t] + $age30[$t] + $age60[$t] + $age90[$t] + $age120[$t]);

			if(!isset($out1[$t])) {
				$out1[$t] = "";
				$out2[$t] = "";
				$out3[$t] = "";
				$out4[$t] = "";
				$out5[$t] = "";
			}

			vsprint($out1[$t]);
			vsprint($out2[$t]);
			vsprint($out3[$t]);
			vsprint($out4[$t]);
			vsprint($out5[$t]);

			# Alternate bgcolor
			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>".CUR." $curr[$t]</td>
							<td>".CUR." $age30[$t]</td>
							<td>".CUR." $age60[$t]</td>
							<td>".CUR." $age90[$t]</td>
							<td>".CUR." $age120[$t]</td>
							<td>".CUR." $supttot[$t]</td>
						</tr>";
			$confirm .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='text' size='7' name='out1[$t]' value='$out1[$t]'></td>
						<td><input type='text' size='7' name='out2[$t]' value='$out2[$t]'></td>
						<td><input type='text' size='7' name='out3[$t]' value='$out3[$t]'></td>
						<td><input type='text' size='7' name='out4[$t]' value='$out4[$t]'></td>
						<td><input type='text' size='7' name='out5[$t]' value='$out5[$t]'></td>
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
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,SUM(balance) AS balance,pdate as odate
					FROM suppurch WHERE supid = '$supid[$t]' AND div = '".USER_DIV."'
					GROUP BY purid, intpurid, pdate
					HAVING SUM(balance) > 0
					ORDER BY odate ASC";
			$prnInvRslt = db_exec($sql);
			if(pg_numrows($prnInvRslt)<1) {
				return "The selected supplier has no outstanding purchases<br>
					To make a payment in advance please select Auto Allocation";
			}
			$i = 0;
			while(($inv = pg_fetch_array($prnInvRslt)))
			{
				if ($inv['invid'] == 0) {
					continue;
				}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
						<tr><td><br></td></tr>
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
				if(pg_numrows($prnInvRslt) == 1) {
					$val = $amt[$t];
				}
	
				if(isset($paidamt[$t][$i])) {
					$val = $paidamt[$t][$i];
				}
	
				if(isset($supinv[$invid])) {
					$sinv = $supinv[$invid];
				} else {
					$sinv = "";
				}
	
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>$sinv</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				$i++;
	
				$confirm .= "
							<td><input type='text' name='paidamt[$t][$invid]' size='10' value='$val'></td>
						</tr>";
			}
	
			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out[$t] >= 0.01) {
				$confirm .="
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out[$t])." </b>
					</td>
				</tr>";
			}
		}

		vsprint($out[$t]);
		$confirm .= "<input type='hidden' name='out[$t]' value='$out[$t]'>";

	}


	$confirm .= "
			
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='6'><input type='submit' value='Confirm &raquo'></td>
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
		return method($supid);
	}



	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($rec_amount, "num", 1, 10, "Invalid supplier payment amount.");

	for ($t = 0;$t < $rec_amount;$t++){

		if(!isset($out1[$t])) {$out1[$t] = '';}
		if(!isset($out2[$t])) {$out2[$t] = '';}
		if(!isset($out3[$t])) {$out3[$t] = '';}
		if(!isset($out4[$t])) {$out4[$t] = '';}
		if(!isset($out5[$t])) {$out5[$t] = '';}

	// 	$OUT1=$out1;
	// 	$OUT2=$out2;
	// 	$OUT3=$out3;
	// 	$OUT4=$out4;
	// 	$OUT5=$out5;

		$v->isOk ($bankid[$t], "num", 1, 30, "Invalid Bank Account.");
		$v->isOk ($date[$t], "date", 1, 14, "Invalid Date.");
		$v->isOk ($descript[$t], "string", 0, 255, "Invalid Description.");
		$v->isOk ($reference[$t], "string", 0, 50, "Invalid Reference Name/Number.");
		$v->isOk ($cheqnum[$t], "num", 0, 30, "Invalid Cheque number.");
		$v->isOk ($amt[$t], "float", 1, 10, "Invalid amount.");
		$v->isOk ($out[$t], "float", 1, 10, "Invalid out amount.");
		$v->isOk ($out1[$t], "float", 0, 10, "Invalid paid amount(currant).");
		$v->isOk ($out2[$t], "float", 0, 10, "Invalid paid amount(30).");
		$v->isOk ($out3[$t], "float", 0, 10, "Invalid paid amount(60).");
		$v->isOk ($out4[$t], "float", 0, 10, "Invalid paid amount(90).");
		$v->isOk ($out5[$t], "float", 0, 10, "Invalid paid amount(120).");

		$v->isOk ($supid[$t], "num", 1, 10, "Invalid Supplier number.");
		if(isset($invids[$t]))
		{
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$invids[$t][$key]] < 0.01){
					continue;
				}
				$v->isOk ($invids[$t][$key], "num", 1, 50, "Invalid Invoice No. [$key]");
				$v->isOk ($paidamt[$t][$invids[$t][$key]], "float", 1, 20, "Invalid amount to be paid. [$key]");
			}
		}

		$out1[$t] += 0;
		$out2[$t] += 0;
		$out3[$t] += 0;
		$out4[$t] += 0;
		$out5[$t] += 0;

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


	for ($t = 0;$t < $rec_amount;$t++){

		# check invoice payments
		$tot[$t] = 0;
		if(isset($invids[$t]))
		{
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$invids[$t][$key]] < 0.01){
					continue;
				}
				$tot[$t] += $paidamt[$t][$invids[$t][$key]];
			}
		}


		if(sprint(($tot[$t]+$out[$t]+$out1[$t]+$out2[$t]+$out3[$t]+$out4[$t]+$out5[$t]) - $amt[$t]) != 0){
//				return "<li class='err'>$tot[$t] - $amt[$t] The total amount is not equal to the amount paid. Please check the details.</li>".alloc($_POST);
		}

		vsprint($out[$t]);

		$passon .= "
				<input type='hidden' name='bankid[$t]' value='$bankid[$t]'>
				<input type='hidden' name='date[$t]' value='$date[$t]'>
				<input type='hidden' name='supid[$t]' value='$supid[$t]'>
				<input type='hidden' name='descript[$t]' value='$descript[$t]'>
				<input type='hidden' name='reference[$t]' value='$reference[$t]'>
				<input type='hidden' name='cheqnum[$t]' value='$cheqnum[$t]'>
				<input type='hidden' name='out[$t]' value='$out[$t]'>
				<input type='hidden' name='amt[$t]' value='$amt[$t]'>";
		$passon2 = "";

	}

	$confirm = "
			<h3>New Bank Payment</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='all' value='$all'>
				<input type='hidden' name='rec_amount' value='$rec_amount'>
				$passon";



for ($t = 0;$t < $rec_amount;$t++){
	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid[$t]' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	if(pg_num_rows($bankRslt)<1) {
		$bank[$t]['accname'] = "Cash";
		$bank[$t]['bankname'] = "";
	}

	# Supplier name
	$sql = "SELECT supno,supname FROM suppliers WHERE supid = '$supid[$t]' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$bank1 = $bank[$t]['accname'];
	$bank2 = $bank[$t]['bankname'];

	$confirm .= "
			".TBL_BR."
			".TBL_BR."
			<tr>
				<td colspan='2'><h3>Supplier</h3></td>
			</tr>
			<tr>
				<th>Supplier</th>
				<th>Account</th>
				<th>Date</th>
				<th>Description</th>
				<th>Reference</th>
				<th>Cheque Number</th>
				<th>Amount</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='center'>($sup[supno]) $sup[supname]</td>
				<td>$bank1 - $bank2</td>
				<td valign='center'>$date[$t]</td>
				<td valign='center'>$descript[$t]</td>
				<td valign='center'>$reference[$t]</td>
				<td valign='center'>$cheqnum[$t]</td>
				<td valign='center'>".CUR." $amt[$t]</td>
			</tr>";

	if($all == 0)
	{
		// Layout
		$confirm .= "
				<tr><td><br></td></tr>
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
		if(isset($invids[$t]))
		{
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$invids[$t][$key]] < 0.01){
					continue;
				}

				db_conn("cubit");
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE purid='$invids[$t][$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE intpurid='$invids[$t][$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class=err> - Invalid ord number $invids[$t][$key].";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}

				$invid = $inv['invid'];

				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>
							<td>".CUR." <input type='hidden' name='paidamt[$t][]' size='7' value='$paidamt[$t][$invid]'>$paidamt[$t][$invid]</td>
						</tr>";
				$i++;

			}
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out[$t] >= 0.01) {
			$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out[$t])." </b>
				</td>
			</tr>";
		}
	}

	if($all == 1)
	{
		$age30[$t] = sage($supid[$t], 59);
		$age60[$t] = sage($supid[$t], 89);
		$age90[$t] = sage($supid[$t], 119);
		$age120[$t] = sage($supid[$t], 149);
		$bgColor = bgcolorg();
		$i = 0;
		if($out1[$t] > 0)
		{
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid[$t]' AND balance>0 AND pdate >='".extlib_ago(29)."' AND pdate <='".extlib_ago(-1)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1[$t]>0))
			{
				if ($inv['invid'] == 0) {
					continue;
				}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
					<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out1[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out1[$t] = $out1[$t]-$inv['balance'];
				}else {
					$val = $out1[$t];
					$out1[$t] = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[$t][]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out1[$t] >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out[$t])." </b>
					</td>
				</tr>";
			}
		}


		if($out2[$t] > 0)
		{
			if($out2[$t] > $age30[$t]) {
				$_POST["out1[$t]"] = $out1[$t];
				$_POST["out2[$t]"] = $out2[$t];
				$_POST["out3[$t]"] = $out3[$t];
				$_POST["out4[$t]"] = $out4[$t];
				$_POST["out5[$t]"] = $out5[$t];

				return "<li class='err'>You cannot allocate ".CUR." $out2[$t] to 30 days, you only owe ".CUR." $age30[$t]</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid[$t]' AND balance>0 AND pdate >='".extlib_ago(59)."' AND pdate <='".extlib_ago(29)."'  AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2[$t]>0))
			{
				if ($inv['invid'] == 0) {
					continue;
				}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
							<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out2[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out2[$t] = $out2[$t]-$inv['balance'];
				}else {
					$val = $out2[$t];
					$out2[$t] = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[$t][]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out2[$t] >= 0.01) {
				$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out[$t])." </b>
					</td>
				</tr>";
			}
		}
		if($out3[$t] > 0)
		{
			if($out3[$t] > $age60[$t]) {
				$_POST["out1[$t]"] = $out1[$t];
				$_POST["out2[$t]"] = $out2[$t];
				$_POST["out3[$t]"] = $out3[$t];
				$_POST["out4[$t]"] = $out4[$t];
				$_POST["out5[$t]"] = $out5[$t];

				return "<li class='err'>You cannot allocate ".CUR." $out3[$t] to 60 days, you only owe ".CUR." $age60[$t] </li>".alloc($_POST);
			}

			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid[$t]' AND balance>0 AND pdate >='".extlib_ago(89)."' AND pdate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3[$t]>0))
			{
				if ($inv['invid']==0) {
					continue;
				}
				if($inv['invid2']>0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
							<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out3[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out3[$t] = $out3[$t]-$inv['balance'];
				}else {
					$val = $out3[$t];
					$out3[$t] = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out3[$t] >= 0.01) {
				$confirm .="
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out4[$t] > 0)
		{
			if($out4[$t] > $age90[$t]) {
				$_POST["out1[$t]"] = $out1[$t];
				$_POST["out2[$t]"] = $out2[$t];
				$_POST["out3[$t]"] = $out3[$t];
				$_POST["out4[$t]"] = $out4[$t];
				$_POST["out5[$t]"] = $out5[$t];

				return "<li class='err'>You cannot allocate ".CUR." $out4[$t] to 90 days, you only owe ".CUR." $age90[$t]</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid[$t]' AND balance>0 AND pdate >='".extlib_ago(119)."' AND pdate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4[$t]>0))
			{
				if ($inv['invid'] == 0) {
					continue;
				}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
							<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out4[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out4[$t] = $out4[$t]-$inv['balance'];
				}else {
					$val = $out4[$t];
					$out4[$t] = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out4[$t] >= 0.01) {
				$confirm .="
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account
						with ".CUR." ".sprint($out)." </b>
					</td>
				</tr>";
			}
		}
		if($out5[$t] > 0)
		{
			if($out5[$t] > $age120[$t]) {
				$_POST["out1[$t]"] = $out1[$t];
				$_POST["out2[$t]"] = $out2[$t];
				$_POST["out3[$t]"] = $out3[$t];
				$_POST["out4[$t]"] = $out4[$t];
				$_POST["out5[$t]"] = $out5[$t];

				return "<li class='err'>You cannot allocate ".CUR." $out5[$t] to 120 days, you only owe ".CUR." $age120[$t]</li>".alloc($_POST);
			}
			// Connect to database
			db_conn("cubit");
			$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE supid = '$supid[$t]' AND balance>0 AND pdate >='".extlib_ago(149)."' AND pdate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY pdate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5[$t]>0))
			{
				if ($inv['invid'] == 0) {
					continue;
				}
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}
				if($i == 0)
				{
					$confirm .= "
							<tr><td><br></td></tr>
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
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				if($out5[$t] >= $inv['balance']) {
					$val = $inv['balance'];
					$out5[$t] = $out5[$t]-$inv['balance'];
				}else {
					$val = $out5[$t];
					$out5[$t] = 0;
				}

				$confirm .= "
							<td><input type='hidden' name='paidamt[]' size='10' value='$val'>".CUR." $val</td>
						</tr>";
				$i++;
			}

			// 0.01 because of high precisions like 0.0000000001234 still matching
			if ($out5[$t] >= 0.01) {
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
				<tr><td><br></td></tr>
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
		if(isset($invids[$t]))
		{
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$invids[$t][$key]] < 0.01){
					continue;
				}

				$ii = $invids[$t][$key];
				$pp = $paidamt[$t][$key];

				db_conn("cubit");
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE purid='$ii' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE intpurid='$ii' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class='err'> - Invalid ord number $ii.</li>";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2'] > 0) {
					$inv['invid'] = $inv['invid2'];
				}

				$invid = $inv['invid'];

				$ppp = $paidamt[$t][$invid];
				$confirm .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' size='20' name='invids[$t][]' value='$inv[invid]'>$inv[invid]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[odate]</td>";
				$confirm .= "
							<td>".CUR." <input type='hidden' name='paidamt[$t][]' size='7' value='$ppp'>$ppp</td>
						</tr>";
				$i++;

			}
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out[$t] >= 0.01) {
			$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><b>A general transaction will debit the supplier's account
					with ".CUR." ".sprint($out)." </b>
				</td>
			</tr>";
		}
	}

	vsprint($out1[$t]);
	vsprint($out2[$t]);
	vsprint($out3[$t]);
	vsprint($out4[$t]);
	vsprint($out5[$t]);
// 	vsprint($OUT1);
// 	vsprint($OUT2);
// 	vsprint($OUT3);
// 	vsprint($OUT4);
// 	vsprint($OUT5);

	$passon2 .= "
		<input type='hidden' name='out1[$t]' value='$out1[$t]'>
		<input type='hidden' name='out2[$t]' value='$out2[$t]'>
		<input type='hidden' name='out3[$t]' value='$out3[$t]'>
		<input type='hidden' name='out4[$t]' value='$out4[$t]'>
		<input type='hidden' name='out5[$t]' value='$out5[$t]'>
		<input type='hidden' name='date_day[$t]' value='$date_day[$t]'>
		<input type='hidden' name='date_month[$t]' value='$date_month[$t]'>
		<input type='hidden' name='date_year[$t]' value='$date_year[$t]'>";
}

/*
				<input type='hidden' name='OUT1' value='$OUT1'>
				<input type='hidden' name='OUT2' value='$OUT2'>
				<input type='hidden' name='OUT3' value='$OUT3'>
				<input type='hidden' name='OUT4' value='$OUT4'>
				<input type='hidden' name='OUT5' value='$OUT5'>
*/
	$confirm .= "
				$passon2
				".TBL_BR."
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right' colspan='3'><input type='submit' value='Write &raquo'></td>
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
	$v->isOk ($rec_amount, "num", 1, 10, "Invalid supplier payment amount.");

	for ($t=0;$t<$rec_amount;$t++){
		$v->isOk ($bankid[$t], "num", 1, 30, "Invalid Bank Account.");
		$v->isOk ($date[$t], "date", 1, 14, "Invalid Date.");
		$v->isOk ($out[$t], "float", 1, 10, "Invalid out amount.");
		$v->isOk ($descript[$t], "string", 0, 255, "Invalid Description.");
		$v->isOk ($reference[$t], "string", 0, 50, "Invalid Reference Name/Number.");
		$v->isOk ($cheqnum[$t], "num", 0, 30, "Invalid Cheque number.");
		$v->isOk ($amt[$t], "float", 1, 10, "Invalid amount.");
		$v->isOk ($supid[$t], "num", 1, 10, "Invalid supplier number.");
		$v->isOk ($out1[$t], "float", 0, 10, "Invalid paid amount(current).");
		$v->isOk ($out2[$t], "float", 0, 10, "Invalid paid amount(30).");
		$v->isOk ($out3[$t], "float", 0, 10, "Invalid paid amount(60).");
		$v->isOk ($out4[$t], "float", 0, 10, "Invalid paid amount(90).");
		$v->isOk ($out5[$t], "float", 0, 10, "Invalid paid amount(120).");
	
		if(isset($invids[$t]))
		{
			foreach($invids[$t] as $key => $value){
				$v->isOk ($invids[$t][$key], "num", 1, 50, "Invalid Invoice No.");
				$v->isOk ($paidamt[$t][$key], "float", 1, 20, "Invalid amount to be paid.");
			}
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



for ($t = 0;$t < $rec_amount;$t++){
	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid[$t]' AND div = '".USER_DIV."' AND accid!=0";
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
		$bank[$t]['accnum'] = $add['accid'];
	} else {
		$bank[$t] = pg_fetch_array($rslt);
	}

	db_connect();
	# Supplier name
	$sql = "SELECT supid,supno,supname,deptid FROM suppliers WHERE supid = '$supid[$t]' AND div = '".USER_DIV."'";
    $supRslt = db_exec($sql);
    $sup = pg_fetch_array($supRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	# date format
        $sdate[$t] = $date[$t];
	$cheqnum[$t] = 0 + $cheqnum[$t];
	$pay = "";
	$accdate[$t] = $sdate[$t];

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";

	db_conn("cubit");

	if($all == 0)
	{
		$ids = "";
		$purids = "";
		$pamounts = "";
		$pdates = "";

		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if (isset($invids[$t])) {
				foreach($invids[$t] as $key => $value)
				{
					$ii = $invids[$t][$key];
					$pp = $paidamt[$t][$key];

					#debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$ii' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$pp'::numeric(13,2)) WHERE purid = '$ii' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$t][$key]";
					$pamounts .= "|$paidamt[$t][$key]";
					$pdates .= "|$pur[pdate]";
				}
			}

		$samount = ($amt[$t] - ($amt[$t] * 2));

		if ($out>0) {
			recordDT($out[$t], $sup['supid'],$sdate[$t]);
		}

		$bank1 = $bank[$t]['accnum'];

		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate[$t]', 'Payment','$cheqnum[$t]','$bank1', '".USER_DIV."')";
		$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		db_connect();

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$amt[$t]'::numeric(13,2)) WHERE supid = '$sup[supid]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		suppledger($sup['supid'], $bank[$t]['accnum'], $sdate[$t], $cheqnum[$t], "Payment for purchases", $amt[$t], "d");

		db_connect();
		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid[$t]', 'withdrawal', '$sdate[$t]', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum[$t]', '$amt[$t]', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference[$t]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate[$t]);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid[$t]'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank[$t]['accnum'];

		writetrans($dept['credacc'], $link['accnum'], $accdate[$t], $refnum, $amt[$t], "Supplier Payment to $sup[supname]");

		db_conn('cubit');

		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	if($all == 1)
	{
		$ids = "";
		$purids = "";
		$pamounts = "";
		$pdates = "";

		# Begin updates
		//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids[$t])) {
				foreach($invids[$t] as $key => $value)
				{
					$ii = $invids[$t][$key];
					$pp = $paidamt[$t][$key];

					# Get debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$ii' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - $pp::numeric(13,2)) WHERE purid = '$ii' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$ids .= "|$pur[id]";
					$purids .= "|$invids[$t][$key]";
					$pamounts .= "|$paidamt[$t][$key]";
					$pdates .= "|$pur[pdate]";


				}
			}

		$samount = ($amt[$t] - ($amt[$t] * 2));

		if ($out1[$t] > 0) {
			recordDT($out1[$t], $sup['supid'],$sdate[$t]);
		}

		if ($out2[$t] > 0) {
			recordDT($out2[$t], $sup['supid'],$sdate[$t]);
		}

		if ($out3[$t] > 0) {
			recordDT($out3[$t], $sup['supid'],$sdate[$t]);
		}

		if ($out4[$t] > 0) {
			recordDT($out4[$t], $sup['supid'],$sdate[$t]);
		}

		if ($out5[$t] > 0) {
			recordDT($out5[$t], $sup['supid'],$sdate[$t]);
		}

		$bank1 = $bank[$t]['accnum'];
		$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate[$t]', 'Payment','$cheqnum[$t]','$bank1', '".USER_DIV."')";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$amt[$t]'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid[$t]', 'withdrawal', '$sdate[$t]', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum[$t]', '$amt[$t]', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference[$t]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate[$t]);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid[$t]'";
		$Rx= db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx) < 1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank[$t]['accnum'];

		writetrans($dept['credacc'],$link['accnum'], $accdate[$t], $refnum, $amt[$t], "Supplier Payment to $sup[supname]");

		db_conn('cubit');
		# Commit updates
		//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

		suppledger($sup['supid'], $bank[$t]['accnum'], $sdate[$t], $cheqnum[$t], "Payment to Supplier", $amt[$t], "d");
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
		$sql = "UPDATE suppliers SET balance = (balance - '$amt[$t]'::numeric(13,2)) WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Begin updates
		#pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			if(isset($invids[$t]))
			{
				foreach($invids[$t] as $key => $value)
				{

					$ii = $invids[$t][$key];
					$pp = $paidamt[$t][$key];

					# Get debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$ii' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.";
					}
					$pur = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$pp'::numeric(13,2)) WHERE purid = '$ii' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$samount = ($paidamt[$t][$key] - ($paidamt[$t][$key] * 2));
					$bank1 = $bank[$t]['accnum'];
					$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc,div) VALUES('$sup[supid]','$samount','$sdate[$t]', 'Payment - Purchase: $ii','$cheqnum[$t]','$bank1', '".USER_DIV."')";
					$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

					suppledger($sup['supid'], $bank1, $sdate[$t], $ii, "Payment for Purchase No. $ii", $pp, "d");
					db_connect();

					# record the payment on the statement

					$ids .= "|$pur[id]";
					$purids .= "|$ii";
					$pamounts .= "|$pp";
					$pdates .= "|$pur[pdate]";


				}
			}

		$samount = ($amt[$t] - ($amt[$t] * 2));



		db_conn('cubit');
		# Record the payment record
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid[$t]', 'withdrawal', '$sdate[$t]', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum[$t]', '$amt[$t]', 'no', '$dept[credacc]', '$sup[supid]', '$ids', '$purids', '$pamounts', '$pdates', '$reference[$t]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$refnum = getrefnum($accdate[$t]);

		db_conn('core');
		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid[$t]'";
		$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
		if(pg_numrows($Rx)<1) {
			return "Invalid bank acc.";
		}
		$link = pg_fetch_array($Rx);

		$link['accnum'] = $bank[$t]['accnum'];

		writetrans($dept['credacc'],$link['accnum'], $accdate[$t], $refnum, $amt[$t], "Supplier Payment to $sup[supname]");

		db_conn('cubit');
		# Commit updates
		#pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}
}

	db_conn('cubit');
	$Sl = "DELETE FROM suppurch WHERE balance=0::numeric(13,2)";
	$Rx = db_exec($Sl);


	# status report
	$write = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>Bank Payment</th>
				</tr>
				<tr class='datacell'><td>Bank Payment added to cash book.</td></tr>
			</table>";

	# main table (layout with menu)
	$OUTPUT = "
			<center>
			<table width = 90%>
				<tr valign='top'>
					<td width='50%'>$write</td>
					<td align='center'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr><th>Quick Links</th></tr>
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


function recordDT($amount, $supid,$edate)
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
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	return $py;

}


?>
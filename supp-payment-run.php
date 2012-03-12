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

require ("settings.php");
require ("libs/ext.lib.php");

if (isset($_POST["key"])){
	switch ($_POST["key"]){
		case "do_process":
			if (isset($_POST["process"])){
				$OUTPUT = get_process ($_POST);
			}else {
				$OUTPUT = printSupp ($_POST);
			}
			break;
		case "alloc":
			$OUTPUT = alloc_process ($_POST);
			break;
		default:
			$OUTPUT = printSupp ($_POST);
	}
}else {
	$OUTPUT = printSupp ($_POST);
}


require ("template.php");




# show stock
function printSupp ($_POST,$err="")
{

	extract ($_POST);

	# Set up table to display in
	global $PRDMON;
	$cur = date("m");
	$from = getMonthName($PRDMON[1]) . " " . getYearOfFinMon($PRDMON[1]);
	$to = getMonthName($cur) . " " . getYearOfFinMon($cur);

	$sel1 = "";
	$sel2 = "";
	$sel3 = "";
	$sel4 = "";
	$sel5 = "";
	$sel6 = "";

	if(isset($search)){
		switch ($search){
			case "cur":
				$sel2 = "selected";
				break;
			case "30":
				$sel3 = "selected";
				break;
			case "60":
				$sel4 = "selected";
				break;
			case "90":
				$sel5 = "selected";
				break;
			case "120":
				$sel6 = "selected";
				break;
			default:
				$sel1 = "selected";
		}
	}else {
		$sel1 = "selected";
	}

	if (!isset ($creditor))
		$creditor = "";
	if (!isset ($bankid))
		$bankid = "";

	$csel1 = "";
	$csel3 = "";
	if ($creditor == "0") $csel1 = "selected";
	if ($creditor == "2") $csel3 = "selected";




	db_connect ();

	$get_bankaccs = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$run_bankaccs = db_exec($get_bankaccs) or errDie("Unable to get bank account information");
	if(pg_numrows($run_bankaccs) < 1){
		$bankacclist = "No Bank Accounts Found.";
	}else {
		$bankacclist = "<select name='bankid'>";
		while ($barr = pg_fetch_array($run_bankaccs)){
			if($bankid == $barr['bankid']){
				$bankacclist .= "<option value='$barr[bankid]' selected>$barr[accname] - $barr[bankname] ($barr[acctype])</option>";		
			}else {
				$bankacclist .= "<option value='$barr[bankid]'>$barr[accname] - $barr[bankname] ($barr[acctype])</option>";
			}
		}
		$bankacclist .= "</select>";
	}



	$get_subs = "SELECT * FROM supp_groups WHERE id != '0' ORDER BY groupname";
	$run_subs = db_exec($get_subs) or errDie ("Unable to get supplier group information");
	if(pg_numrows($run_subs) < 1){
		$sub_list = "No Groups Found.";
	}else {
		$sub_list = "
						<select name='creditor' onChange='document.form1.submit();'>
							<option value='' disabled selected>Select Sub Contractor Type</option>
							<option value='0' $csel1>Sub Contractors</option>
							<option value='2' $csel3>Creditors</option>
						</select>";
	}

	if($creditor == "0"){
		$age_heading = "";
	}else {
		$age_heading = "
							<th>30 days</th>
							<th>60 days</th>
							<th>90 days</th>
							<th>120 days</th>
					";
	}


	$printSupp = "
					<h3>Creditors Payment Run</h3>
					$err
					<h4>Period: $from to $to</h4>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form1'>
						<input type='hidden' name='key' value='do_process'>
						<tr>
							<th colspan='4'>Show Only</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='4'>$sub_list</td>
						</tr>
						<tr>
							<th colspan='4'>Date Range (With Balances)</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='4'>
								<select name='search' onChange='document.form1.submit();'>
									<option value='' disabled $sel1>Select An Option</option>
									<option value='cur' $sel2>Current</option>
									<option value='30' $sel3>30 Days</option>
									<option value='60' $sel4>60 Days</option>
									<option value='90' $sel5>90 Days</option>
									<option value='120' $sel6>120 Days</option>
								</select>
							</td>
						</tr>
						".TBL_BR."
						<tr>
							<th colspan='4'>Select Bank Account To Use</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='4'>$bankacclist</td>
						</tr>
						<tr>
							<th colspan='4'>Process Date</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='4'>".mkDateSelect("date")."</td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='8'></td>
							<th>Cheque Starting Number</th>
						</tr>
						<tr>
							<td colspan='8'></td>
							<td bgcolor='".bgcolorg()."' align='center'><input type='text' size='5' name='cheq_start'></td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Acc no.</th>
							<th>Suppliers</th>
							<th>Current</th>
							$age_heading
							<th>Total Outstanding</th>
							<th>Potential Settlement Discount</th>
							<th>Amount To Pay</th>
							<th>Remarks</th>
							<th>Process</th>
						</tr>";

	# connect to database
	db_connect();

	$cred_search = "";
	if (strlen($creditor) > 0){
		switch($creditor){
			case "0":
				$groups = "grpid = '3' OR grpid = '4'";
				break;
			case "2":
				$groups = "grpid = '2'";
				break;
			default:
				$groups = "";
		}
		$get_creds = "SELECT supid FROM supp_grpowners WHERE $groups";
		$run_creds = db_exec($get_creds) or errDie("Unable to get supplier groups");
		if (pg_numrows($run_creds) > 0){
			while ($arr = pg_fetch_array($run_creds)){
				$cred_search .= "supid = '$arr[supid]' OR ";
			}
		}else {
			$cred_search = "supid = '0' AND";
		}
	}else {
		$cred_search = "supid  > '0' AND ";
	}

	if(strlen($cred_search) > 1)
		$cred_search = substr($cred_search,0,-4);

	# Query server
	$i = 0;
	$sql = "SELECT * FROM suppliers WHERE $cred_search ORDER BY supname ASC";
	$suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		unset ($_POST['creditor']);
	//	return printsupp($_POST,"<li class='err'>No Suppliers matching requirement found.</li>");
	}

	# totals
	$totcurr = 0;
	$tot30 = 0;
	$tot60 = 0;
	$tot90 = 0;
	$tot120 = 0;
	$alltot = 0;

	$i = 0;


	$newarr = array ();

	while ($supp = pg_fetch_array ($suppRslt)) {

		$setdate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$supp['setdays'],date("Y")));

		$get_spurch = "SELECT sum(balance) FROM suppurch WHERE supid = '$supp[supid]' AND pdate >= '$setdate' AND pdate <= 'now'";
		$run_spurch = db_exec($get_spurch) or errDie ("Unable to get outstanding supplier balance information.");

		if(pg_numrows($run_spurch) < 1){
			$supp['setamt'] = sprint (0);
		}else {
			$sparr = pg_fetch_array ($run_spurch);

			$setsave = sprint (($sparr['sum']/100) * $supp['setdisc']);
			$supp['setamt'] = $setsave;
		}
		$newarr[$setsave] = $supp;
	}

	krsort($newarr);

	if(isset($creditor) AND (strlen($creditor) > 0))
	foreach($newarr AS $setsave => $supp) {
		# Get all ages
		$curr = age($supp['supid'], 29);
		$age30 = age($supp['supid'], 59);
		$age60 = age($supp['supid'], 89);
		$age90 = age($supp['supid'], 119);
		$age120 = age($supp['supid'], 149);

		# Suppliers total
		$supptot = sprint($curr + $age30 + $age60 + $age90 + $age120);

		if($supptot < $supp['balance']) {
			$curr = sprint($curr + ($supp['balance'] - $supptot));
			$supptot = sprint($supptot + $supp['balance'] - $supptot);
		}

		if(isset($search)){
			switch ($search){
				case "curr":
					if ($curr < 0.01)
						continue 2;
					break;
				case "30":
					if ($age30 < 0.01)
						continue 2;
					break;
				case "60":
					if ($age60 < 0.01)
						continue 2;
					break;
				case "90":
					if ($age90 < 0.01)
						continue 2;
					break;
				case "120":
					if ($age120 < 0.01)
						continue 2;
					break;
				default:
			}
		}

		if($creditor == "0"){
			$age_entries = "";
		}else {
			$age_entries = "
								<td>".CUR." $age30</td>
								<td>".CUR." $age60</td>
								<td>".CUR." $age90</td>
								<td>".CUR." $age120</td>
						";
		}

//for settlement disc ... we check suppurch for outstanding amounts/date
		if($supp['setdays'] != "0"){

			$setdate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$supp['setdays'],date("Y")));

			#get suppurch
			$get_spurch = "SELECT sum(balance) FROM suppurch WHERE supid = '$supp[supid]' AND pdate >= '$setdate' AND pdate <= 'now'";
			$run_spurch = db_exec($get_spurch) or errDie ("Unable to get outstanding supplier balance information.");
			if (pg_numrows($run_spurch) < 1){
				#no entries ... we owe him nothing ...
				$payamount = 0.00;
				$setsave = sprint (0);
				$parr['supid'] = "0";
				$parr['purid'] = "0";
				$parr['remit'] = "0";
				$remarks = "";
			}else {
				$sparr = pg_fetch_array ($run_spurch);
				$payamount = 0.00;
				$setsave = sprint (($sparr['sum']/100) * $supp['setdisc']);
				$parr['supid'] = "0";
				$parr['purid'] = "0";
				$parr['remit'] = "0";
				$remarks = "";
			}
		}else {
			$payamount = 0.00;
			$setsave = sprint (0);
			$parr['supid'] = "0";
			$parr['purid'] = "0";
			$parr['remit'] = "0";
			$remarks = "";
		}


		$dochecked = "";
		if(isset($runids[$i]))
			$dochecked = "checked='yes'";

##PTH/CUBIT
		$printSupp .= "
						<input type='hidden' name='supno[]' value='$supp[supid]'>
						<input type='hidden' name='conid[]' value='$parr[purid]'>
						<input type='hidden' name='remit[]' value='0'>
						<tr bgcolor='".bgcolorg()."'>
							<td>$supp[supno]</td>
							<td><a target='_blank' href='recon_statement_ct.php?key=display&supid=$supp[supid]'>$supp[supname]</a></td>
							<td>".CUR." $curr</td>
							$age_entries
							<td>".CUR." $supptot</td>
							<td nowrap>".CUR." $setsave</td>
							<td><input type='text' size='8' name='amt[]' value='$payamount'></td>
							<td><textarea name='remarks[]' cols='20' rows='2'>".nl2br($remarks)."</textarea></td>
							<td><input type='checkbox' $dochecked name='runids[]' value='$i'></td>
						</tr>";

		# hold totals
		$totcurr += $curr;
		$tot30 += $age30;
		$tot60 += $age60;
		$tot90 += $age90;
		$tot120 += $age120;
		$alltot += $supptot;

		$i++;
	}

	$totcurr = sprint($totcurr);
	$tot30 = sprint($tot30);
	$tot60 = sprint($tot60);
	$tot90 = sprint($tot90);
	$tot120 = sprint($tot120);
	$alltot = sprint($alltot);


	$printSupp .= "
							<tr><td><br></td></tr>
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='2'><b>Totals</b></td>
								<td nowrap><b>".CUR." $totcurr</b></td>
								<td nowrap><b>".CUR." $tot30</b></td>
								<td nowrap><b>".CUR." $tot60</b></td>
								<td nowrap><b>".CUR." $tot90</b></td>
								<td nowrap><b>".CUR." $tot120</b></td>
								<td nowrap><b>".CUR." $alltot</b></td>
							</tr>
							<tr><td><br></td></tr>
							<tr>
								<td colspan='9' align='right'><input type='submit' name='process' value='Process Selected'></td>
							</tr>
						</form>
						</table>";
	return $printSupp;

}








function get_process ($_POST)
{

	extract($_POST);

	if(!isset($runids) OR !is_array($runids))
		return printSupp($_POST,"<li class='err'>Please Select At Least 1 Creditor To Process</li");

	# validate input
	require_lib("validate");
	$v = new  validate ();

	foreach ($runids AS $each => $own){
		$v->isOk ($amt[$each], "float", 1, 10, "Invalid payment amount.($amt[$each])");
		$v->isOk ($own, "num", 1, 10, "Invalid supplier number.($own)");

		if(isset($amt[$each]) AND ($amt[$each] < 0.01)){
			$v->addError ($amt[$each],"Payment Amount is too small.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
//		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return printSupp($_POST,$confirm);
	}




	if(!isset($bankid))
		$bankid = 0;

	#handle the first cheque number
	$cheq_start = $cheq_start + 0;

	// layout
	$add = "<table ".TMPL_tblDflts.">";
	$i = 0;
	
	#generate a unique entry for this run ...
	$run_id = mktime (date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
//print "$run_id<br>";
	foreach ($runids AS $each){

		db_connect();
		# Supplier name
		$sql = "SELECT supno,supname,balance FROM suppliers WHERE supid = '$supno[$each]' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		$sup = pg_fetch_array($supRslt);

		if(!isset($amt)) {
			$amt="";
			$descript="";
			$cheqnum="";
			$reference = "";
		}

		if(!isset($date_day)){
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}

		$proc_amt = str_pad($amt[$each],8,"0","PAD_LEFT");

		$val1 = substr($proc_amt,0,2);
		$val2 = substr($proc_amt,2,1);
		$val3 = substr($proc_amt,3,1);
		$val4 = substr($proc_amt,4,1);
		$val5 = substr($proc_amt,5,1);
		$val6 = substr($proc_amt,6,1);
		$val7 = substr($proc_amt,7,1);
		$val8 = substr($proc_amt,9,2);

		#lets calculate the stars to show on the cheque
		$star_mils = calc_stars ($val1);
		$star_hundred_thou = calc_stars ($val2);
		$star_ten_thou = calc_stars ($val3);
		$star_thou = calc_stars ($val4);
		$star_hundreds = calc_stars ($val5);
		$star_tens = calc_stars ($val6);
		$star_units = calc_stars ($val7);
		$star_cents = calc_stars ($val8,TRUE);

		#display this cheque
		$add .= "
					<input type='hidden' name='supid[]' value='$supno[$each]'>
					<input type='hidden' size='5' name='pur' value=''>
					<input type='hidden' size='5' name='inv' value=''>
					<input type='hidden' name='all' value='0'>
					<tr>
						<td valign='center'> $sup[supname]</td>
						<td colspan='8'></td>
						<td align='right'>$date_year/$date_month/$date_day</td>
					</tr>
					<tr>
						<td></td>
						<td width='40'>$star_mils</td>
						<td width='40'>$star_hundred_thou</td>
						<td width='40'>$star_ten_thou</td>
						<td width='40'>$star_thou</td>
						<td width='40'>$star_hundreds</td>
						<td width='40'>$star_tens</td>
						<td width='40'>$star_units</td>
						<td width='40'>$star_cents</td>
						<td align='left'>R ".sprint($amt[$each])."</td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='10'><hr></td>
					</tr>
				";

		db_conn ('contract');

##PTH/CUBIT
		#store this cheque as having been printed...
/*		$cheq_sql = "INSERT INTO supp_creditor_run_cheques (supid,bankid,amount,cheq_num,proc_date,printed,handed_over,received,conid,remit) VALUES 
															('$supno[$each]','$bankid','$amt[$each]','$cheq_start','$date_year-$date_month-$date_day','yes','no','no','$conid[$each]','$remit[$each]')";*/
		$cheq_sql = "INSERT INTO supp_creditor_run_cheques (supid,bankid,amount,cheq_num,proc_date,printed,handed_over,received,remit,remarks) VALUES 
															('$supno[$each]','$bankid','$amt[$each]','$cheq_start','$date_year-$date_month-$date_day','yes','no','no','$remit[$each]','$remarks[$each]')";
		$run_cheq = db_exec($cheq_sql) or errDie("Unable to store cheque information.");

		$entry_id = pglib_lastid("supp_creditor_run_cheques","id");

		#also store this as a run ...
		$run_sql = "
						INSERT INTO credit_runs 
							(run_id,entry_id,supid,bankid,amount,cheq_num,proc_date,printed,handed_over,received,remit,remarks) 
						VALUES 
							('$run_id','$entry_id','$supno[$each]','$bankid','$amt[$each]','$cheq_start','$date_year-$date_month-$date_day','yes','no','no','$remit[$each]','$remarks[$each]')";
		$run_run = db_exec($run_sql) or errDie ("");

		$cheq_start++;
		$i++;
	}

##PTH/CUBIT
//	db_conn ('contract');

//	foreach ($conid AS $each => $own){
//		if ($own != "0"){
//			$get_upd = "UPDATE contract_recs SET remarks = '$remarks[$each]' WHERE conid = '$own' AND remit = '$remit[$each]'";
//			$run_upd = db_exec($get_upd) or errDie("Unable to get previous remittance information.");
//		}
//	}



	$add .= "</table>";



	$OUTPUT = "
			<table border='0' width='100%'>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='alloc'>
				$add
			</form>
			</table>
    		";
 //       return $OUTPUT;
	require ("tmpl-print.php");

}



function calc_stars ($amount,$cents = FALSE)
{

	if(!isset($amount) OR strlen($amount) < 1 OR ($amount > 100)){
		return "";
	}

	$amount = $amount + 0;

	if ($cents)
		return "$amount";
		
	if ($amount > 15)
		return $amount;

	switch ($amount){
		case "0":
			$stars = "*****";
			break;
		case "1":
			$stars = "ONE";
			break;
		case "2":
			$stars = "TWO";
			break;
		case "3":
			$stars = "THREE";
			break;
		case "4":
			$stars = "FOUR";
			break;
		case "5":
			$stars = "FIVE";
			break;
		case "6":
			$stars = "SIX";
			break;
		case "7":
			$stars = "SEVEN";
			break;
		case "8":
			$stars = "EIGHT";
			break;
		case "9":
			$stars = "NINE";
			break;
		case "10":
			$stars = "TEN";
			break;
		case "11":
			$stars = "ELEVEN";
			break;
		case "12":
			$stars = "TWELVE";
			break;
		case "13":
			$stars = "THIRTEEN";
			break;
		case "14":
			$stars = "FOURTEEN";
			break;
		case "15":
			$stars = "FIFTEEN";
			break;
		default:
			$stars = "*****";
	}

	return $stars;

}


function alloc_process ($_POST)
{

	extract ($_POST);

	if (isset($back))
		return printSupp ($_POST);

}




# check age
function age($supid, $days)
{

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	db_connect ();
	# Get the current outstanding
	$sql = "SELECT sum(balance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	/*
	# Get the current outstanding
	$sql = "SELECT sum(balance) FROM purch_int WHERE supid = '$supid' AND balance > 0 AND received = 'y' AND pdate >='".extlib_ago($days)."' AND pdate <='".extlib_ago($days-30)."'";
	$rsint = db_exec($sql) or errDie("Unable to access database");
	$sumint = pg_fetch_array($rsint);
	*/

	# Take care of nasty zero
	return sprint($sum['sum']);

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



?>

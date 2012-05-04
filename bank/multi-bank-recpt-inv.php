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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "method":
			$OUTPUT = method($_POST);
			break;
		case "alloc":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = method($_POST);
			}else {
				$OUTPUT = alloc($_POST);
			}
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = method($_POST);
	}
} elseif(isset($_GET["cusnum"])) {
	# Display default output
	$OUTPUT = method($_GET["cusnum"]);
} else {
	# Display default output
	$OUTPUT = method($_POST);
}

# get templete
require("../template.php");



function method($_POST,$ex="")
{

	extract ($_POST);

	// customers Drop down selections
	db_connect();

	$cust = "<select name='cusid'>";
	$sql = "SELECT cusnum,cusname,surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname";
	$cusRslt = db_exec($sql) or errDie("Unable to get customers information.");
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li class='err'> There are no Debtors in Cubit.</li>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	}

	if (!isset ($rec_amount)) 
		$rec_amount = 1;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($rec_amount, "num", 1, 10, "Invalid amount of receipt.");

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


	$jump_bot = "";
	if (isset ($another)) {
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
		$rec_amount++;
	}

	if(isset($_GET["e"])) {
		$ex = "<input type='hidden' name='e' value='y'>";
	} else {
		$ex = "";
	}

	$listing = "";
	for($i = 0;$i < $rec_amount;$i++){

		#here we will make the entries ...

		##################[ check the vars ]#####################
		if(!isset($cusid[$i])) {
			$bankid[$i] = "0";
			$descript[$i] = "";
			$cheqnum[$i] = "";
			$amt[$i] = "";
			$setamt[$i] = "";
			$setvat[$i] = "";
			$setvatcode = "";
			$reference[$i] = "";
			$setvat[$i] = "";
			$setvatcode[$i] = "";
		}

		if(!isset($date_day[$i])) {

			$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
			if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
				$trans_date_value = getCSetting ("TRANSACTION_DATE");
				$date_arr = explode ("-", $trans_date_value);
				$date_year[$i] = $date_arr[0];
				$date_month[$i] = $date_arr[1];
				$date_day[$i] = $date_arr[2];
			}else {
				if (isset($_SESSION["global_day"]) AND strlen ($_SESSION["global_day"]) > 0) 
					$date_day[$i] = $_SESSION["global_day"];
				else 
					$date_day[$i] = date("d");
				if (isset($_SESSION["global_month"]) AND strlen ($_SESSION["global_month"]) > 0) 
					$date_month[$i] = $_SESSION["global_month"];
				else 
					$date_month[$i] = date("m");
				if (isset($_SESSION["global_year"]) AND strlen ($_SESSION["global_year"]) > 0) 
					$date_year[$i] = $_SESSION["global_year"];
				else 
					$date_year[$i] = date("Y");
			}
		}
		#########################################################


		##################[ get customer information ]###########
		db_connect();

		$customerdrop = "<select name='cusid[$i]'>";
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname";
		$cusRslt = db_exec($sql) or errDie("Unable to get customer information");
		if (pg_numrows($cusRslt) < 1){
			return "
				<li> There are no Customers in Cubit.</li>
				<p>
				".mkQuickLinks(
					ql("trans-new.php", "Journal Transactions"),
					ql("../customers-view.php", "View Customers")
				);
		}

		if(!isset($cusid[$i])) {
			$cusid[$i] = 0;
		}

		while($cus = pg_fetch_array($cusRslt)){
			if($cus['cusnum'] == $cusid[$i]) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$customerdrop .= "<option $sel value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
		}
		$customerdrop .= "</select>";
		#########################################################


		##################[ get Bank accounts ]##################
		$bankaccs = "<select name='bankid[$i]'>";
		db_connect();
		$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
		$banks = db_exec($sql) or errDie("Unable to get bank accounts information.");
		$numrows = pg_numrows($banks);
		if(empty($numrows)){
			return "
				<li class='err'> There are no accounts held at the selected Bank.
				<p>
				<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}
		while($acc = pg_fetch_array($banks)){
			$sel = fsel($bankid[$i] == $acc["bankid"]);
			$bankaccs .= "<option $sel value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}
		if(isset($_GET['cash'])) {
			$sel = fsel($bankid[$i] == $acc["bankid"]);
			$bankaccs .= "<option $sel value='0'>Receive Cash</option>";
		}
		$bankaccs .= "</select>";
		##########################################################

		$setamt[$i] = sprint ($setamt[$i]);

		$get_vatcodes = "SELECT * FROM vatcodes ORDER BY code";
		$run_vatcodes = db_exec($get_vatcodes) or errDie ("Unable to get vat code information.");
		if(pg_numrows($run_vatcodes) < 1){
			$setvatcode_drop = "<input type='hidden' name='setvatcode[$i]' value=''>";
		}else {
			$setvatcode_drop = "<select name='setvatcode[$i]'>";
			while ($vcarr = pg_fetch_array ($run_vatcodes)){
				if($setvatcode[$i] == $vcarr['id']){
					$setvatcode_drop .= "<option value='$vcarr[id]' selected>$vcarr[code] $vcarr[description]</option>";
				}else {
					$setvatcode_drop .= "<option value='$vcarr[id]'>$vcarr[code] $vcarr[description]</option>";
				}
			}
			$setvatcode_drop .= "</select>";
		}


		$listing .= "
			<tr class='".bg_class()."'>
				<td>$customerdrop</td>
				<td>$bankaccs</td>
				<td>".mkDateSelecta("date",$i,$date_year[$i],$date_month[$i],$date_day[$i])."</td>
				<td><textarea cols='25' rows='2' name='descript[$i]'>$descript[$i]</textarea></td>
				<td><input type='text' name='reference[$i]' value='$reference[$i]'></td>
				<td><input type='text' name='cheqnum[$i]' value='$cheqnum[$i]'></td>
				<td nowrap>".CUR." <input type='text' name='amt[$i]' size='7' value='$amt[$i]'></td>
				<td nowrap>".CUR." <input type='text' name='setamt[$i]' size='7' value='$setamt[$i]'></td>
				<td nowrap>
					$setvatcode_drop <br>
					<input type='radio' name='setvat[$i]' value='inc' checked='yes'> VAT Inclusive <br>
					<input type='radio' name='setvat[$i]' value='novat'> No VAT
				</td>
			</tr>";
	}

	$listing .= "
		<tr class='".bg_class()."'>
			<td colspan='6' align='right'><b>Total:</b></td>
			<td align='right'>".CUR." ".sprint(array_sum ($amt))."</td>
			<td colspan='2'></td>
		</tr>";

	#set method option
	if(!isset($all)) {
		$all = "0";
	}
//	$as1 = "";
//	$as2 = "";
//	$as3 = "";
//	if ($all == 0) {
//		$as1 = "selected";
//	} else if($all == 1) {
//		$as2 = "selected";
//	} else if ($all == 2) {
//		$as3 = "selected";
//	}
//	$alls = "
//			<select name='all'>
//				<option value='0' $as1>Auto</option>
//				<option value='1' $as2>Allocate To Age Analysis</option>
//				<option value='2' $as3>Allocate To Each invoice</option>
//			</select>";
//		<tr>
//			<th colspan='2'>Receive Method</th>
//		</tr>
//		<tr class='".bg_class()."'>
//			<td>Allocation</td>
//			<td>$alls</td>
//		</tr>

	// layout
	$add = "
		<h3>New Multiple Receipts</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			$ex
			<input type='hidden' name='rec_amount' value='$rec_amount'>
			<input type='hidden' name='key' value='alloc'>
			<input type='hidden' name='cusid' value='$cusid'>
			<input type='hidden' name='all' value='0'>
			".TBL_BR."
			<tr>
				<td colspan='9'><li class='err'>NOTE: This functionality will automatically allocate any amount received to the oldest unpaid/partially paid recorded invoice.</li></td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='9'>Receipt Details</th>
			</tr>
			<tr>
				<th>Customer</th>
				<th>Bank Account / Cash</th>
				<th>Date</th>
				<th>Description</th>
				<th>Reference</th>
				<th>Cheque Number</th>
				<th>Amount</th>
				<th>Settlement Amount</th>
				<th>Settlement VAT</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center' align='right'><input type='submit' value='Allocate >'></td>
			</tr>
			".TBL_BR."
		</form>
		</table>
		<a name='bottom'>
		$jump_bot"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $add;

}



// allocation
function alloc($_POST)
{

	extract($_POST);

	if (isset($back)) {
		if(isset($e)) {
			header("Location: cashbook-entry.php");
			exit;
		}

		return sel_cus($_POST);
	}

	$passon = "";
	require_lib("validate");
	$v = new validate();

	$v->isOk($all, "num", 1, 1, "Invalid allocation.");

	for($i = 0; $i < $rec_amount; $i++){

		if (!isset ($descript[$i]) OR !isset ($reference[$i]) OR !isset ($setamt[$i]) OR empty ($descript[$i]) OR empty ($reference[$i]) OR empty ($setamt[$i])) 
			continue;

		$v->isOk($bankid[$i], "num", 1, 30, "Select Bank Account.");
		$v->isOk($date_day[$i], "num", 1,2, "Invalid Date day.");
		$v->isOk($date_month[$i], "num", 1,2, "Invalid Date month.");
		$v->isOk($date_year[$i], "num", 1,4, "Invalid Date Year.");
		$v->isOk($descript[$i], "string", 0, 255, "Invalid Description.");
		$v->isOk($reference[$i], "string", 0, 50, "Invalid Reference Name/Number.");
		$v->isOk($cheqnum[$i], "num", 0, 30, "Invalid Cheque number.");
		$v->isOk($amt[$i], "float", 1, 40, "Invalid amount.");
		$v->isOk($setamt[$i], "float", 1, 40, "Invalid Settlement Amount.");
		$v->isOk($setvat[$i], "string", 1, 10, "Invalid Settlement VAT Option.");
		$v->isOk($setvatcode[$i], "string", 1, 40, "Invalid Settlement VAT code");
		$v->isOk($cusid[$i], "num", 1, 10, "Invalid customer number.");

		if (strlen($date_year[$i]) != 4){
			$v->isOk($bankname, "num", 1, 1, "Invalid Date year.");
		}

		if ($amt < 0.01) {
			$v->addError($amt[$i], "Amount too small.");
		}

		$date[$i] = $date_day[$i]."-".$date_month[$i]."-".$date_year[$i];
		if(!checkdate($date_month[$i], $date_day[$i], $date_year[$i])){
			$v->isOk ($date[$i], "num", 1, 1, "Invalid date.");
		}

		// bank account name
		if (($bank = qryBankAcct($bankid[$i], "accname, bankname")) === false) {
			$bank[$i]['accname'] = "Cash";
			$bank[$i]['bankname'] = "";
		}

		// customer name
		$cus[$i] = qryCustomer($cusid[$i], "cusnum, cusname, surname");

	}

	if ($v->isError()) {
		$confirm = $v->genErrors();
		return $confirm.method($_POST);
	}




//	<input type='hidden' name='bankid' value='$bankid'>

	$cust_arr = array ();

	$confirm = "
		<h3>New Bank Receipt</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='accnum' value=''>
			<input type='hidden' name='rec_amount' value='$rec_amount'>
			<input type='hidden' name='all' value='$all'>
		<table ".TMPL_tblDflts.">";

	for($t = 0; $t < $rec_amount; $t++){

		if (!isset ($descript[$t]) OR !isset ($reference[$t]) OR !isset ($setamt[$t]) OR empty ($descript[$t]) OR empty ($reference[$t]) OR empty ($setamt[$t])) 
			continue;

		$cus0 = $cus[$t]['cusnum'];
		$cus1 = $cus[$t]['cusname'];
		$cus2 = $cus[$t]['surname'];

// we dont do this ...
//		$amt[$t] = $amt[$t] + $setamt[$t];

		$amt[$t] = sprint ($amt[$t]);
		$setamt[$t] = sprint ($setamt[$t]);

		if($setvat[$t] == "inc")
			$showsetvat = "VAT Inclusive";
		else 
			$showsetvat = "No VAT";

		$confirm .= "
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
				<td valign='center'>$date[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Received from</td>
				<td valign='center'>$cus1 $cus2</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript[$t])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td valign='center'>$reference[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Settlement Discount</td>
				<td valign='center'>".CUR." $setamt[$t] $showsetvat</td>
			</tr>";



		/* OPTION 1 : AUTO ALLOCATE (allocate) */

		if ($all == 0) {
			$out[$t] = $amt[$t];
			$invs_arr = array();

			// Connect to database
			db_connect();

			#####################[ GET OUTSTANDING INVOICES ]######################
			$sql = "SELECT invnum, invid, balance, terms, odate FROM invoices WHERE cusnum = '$cusid[$t]' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
			$prnInvRslt = db_exec($sql);
			while (($inv = pg_fetch_array($prnInvRslt)) && ($out > 0)) {
				$invs_arr[] = array ("s",$inv['odate'],"$inv[invid]","$inv[balance]");
			}


			#####################[ GET OUTSTANDING NON STOCK INVOICES ]######################
			$sql = "SELECT invnum, invid, balance, odate FROM nons_invoices WHERE cusid='$cusid[$t]' AND done='y' AND balance>0 AND div='".USER_DIV."' ORDER BY odate ASC";
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt)) && ($out > 0)) {
				$invs_arr[] = array ("n",$inv['odate'],"$inv[invid]","$inv[balance]");
			}

			#####################[ GET OUTSTANDING POS INVOICES ]######################
			$sqls = array();
			for ($i = 1; $i <= 12; ++$i) {
				$sqls[] = "
					SELECT invnum, invid, balance, odate 
					FROM \"$i\".pinvoices 
					WHERE cusnum='$cusid[$t]' AND done='y' AND balance > 0 AND div='".USER_DIV."'";
			}
			$sql = implode(" UNION ", $sqls);
			$prnInvRslt = db_exec($sql);
			while($inv = pg_fetch_array($prnInvRslt)){
				$invs_arr[] = array ("p",$inv['odate'],"$inv[invid]","$inv[balance]");
			}

			if (isset($invs_arr) AND is_array ($invs_arr)){
				$confirm .= "
					<tr><td><br></td></tr>
					<tr>
						<th>Type</th>
						<th>Invoice</th>
						<th>Outstanding Amount</th>
						<th></th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";
			}

			#compile results into an array we can sort by date
			$search_arr = array ();
			foreach ($invs_arr AS $key => $array){
				$search_arr[$key] = $array[1];
			}

			#sort array by date
			asort ($search_arr);

			#add sorted invoices to payment listing
			foreach ($search_arr AS $key => $date_arr){

				$arr = $invs_arr[$key];

				if ($arr[0] == "s"){
					$get_sql = "SELECT invnum, invid, balance, terms, odate FROM invoices WHERE cusnum = '$cusid[$t]' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' AND invid = '$arr[2]'  LIMIT 1";
					$run_sql = db_exec($get_sql) or errDie ("Unable to get stock invoice information.");
					if (pg_numrows($run_sql) > 0){

						$inv = pg_fetch_array ($run_sql);
						$invid = $inv['invid'];

						if (in_array ($invid, $cust_arr[$cus0])) {
							continue;
						}else {
							$val = allocamt($out[$t], $inv["balance"]);
							if ($val > 0) 
								$cust_arr[$cus0][] = $invid;
							else 
								continue;
						}


						$confirm .= "
							<input type='hidden' name='paidamt[$t][$invid]' size='10' value='$val'>
							<input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>
							<tr bgcolor='".bgcolor($i)."'>
								<td>Stock Invoice</td>
								<td>$inv[invnum]</td>
								<td>".CUR." $inv[balance]</td>
								<td>$inv[terms] days</td>
								<td>$inv[odate]</td>
								<td>".CUR." $val</td>
							</tr>";
					}
				}elseif ($arr[0] == "n"){

					$get_sql = "SELECT invnum, invid, balance, odate FROM nons_invoices WHERE cusid='$cusid[$t]' AND done='y' AND balance>0 AND div='".USER_DIV."' AND invid = '$arr[2]' LIMIT 1";
					$run_sql = db_exec($get_sql) or errDie ("Unable to get non stock information.");
					if (pg_numrows($run_sql) > 0){

						$inv = pg_fetch_array ($run_sql);
						$invid = $inv['invid'];

						if (in_array ($invid, $cust_arr[$cus0])) {
							continue;
						}else {
							$val = allocamt($out[$t], $inv["balance"]);
							if ($val > 0) 
								$cust_arr[$cus0][] = $invid;
							else 
								continue;
						}

						$confirm .= "
							<input type='hidden' name='paidamt[$t][$invid]' value='$val'>
							<input type='hidden' name='itype[$t][$invid]' value='Yes'>
							<tr bgcolor='".bgcolor($i)."'>
								<td>Non Stock Invoice</td>
								<td><input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>$inv[invnum]</td>
								<td>".CUR." $inv[balance]</td>
								<td></td>
								<td>$inv[odate]</td>
								<td>".CUR." $val</td>
							</tr>";
					}
				}else {

					$sqls = array();
					for ($i = 1; $i <= 12; ++$i) {
						$sqls[] = "
							SELECT invnum, invid, balance, odate 
							FROM \"$i\".pinvoices 
							WHERE cusnum='$cusid[$t]' AND done='y' AND balance > 0 AND div='".USER_DIV."' AND invid = '$arr[2]'";
					}
					$get_sql = implode(" UNION ", $sqls);
					$run_sql = db_exec($get_sql) or errDie ("Unable to get pos invoice information.");
					if (pg_numrows($run_sql) > 0){

						$inv = pg_fetch_array ($run_sql);
						$invid = $inv['invid'];

						if (in_array ($invid, $cust_arr[$cus0])) {
							continue;
						}else {
							$val = allocamt($out[$t], $inv["balance"]);
							if ($val > 0) 
								$cust_arr[$cus0][] = $invid;
							else 
								continue;
						}

						$confirm .= "
							<input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>
							<input type='hidden' name='paidamt[$t][$invid]' size='10' value='$val'>
							<input type='hidden' name='ptype[$t][$invid]' value='YnYn'>
							<tr bgcolor='".bgcolor($i)."'>
								<td>POS Invoice</td>
								<td>$inv[invnum]</td>
								<td>".CUR." $inv[balance]</td>
								<td></td>
								<td>$inv[odate]</td>
								<td>".CUR." $val</td>
							</tr>";
					}

					$out[$t] = sprint($out[$t]);

				}

			}
		}

		if ($out[$t] > 0) {
			$out[$t] = sprint ($out[$t]);
			$confirm .= "
				<tr class='".bg_class()."'>
					<td colspan='4'><b>A general transaction will credit the client's account with ".CUR." $out[$t] </b></td>
				</tr>";
		}

		$confirm .= TBL_BR.TBL_BR.TBL_BR;

	}

	for($i = 0;$i < $rec_amount; $i++){
		$passon .= "
			<input type='hidden' name='bankid[$i]' value='$bankid[$i]'>
			<input type='hidden' name='date[$i]' value='$date[$i]'>
			<input type='hidden' name='cusid[$i]' value='$cusid[$i]'>
			<input type='hidden' name='date_day[$i]' value='$date_day[$i]'>
			<input type='hidden' name='date_month[$i]' value='$date_month[$i]'>
			<input type='hidden' name='date_year[$i]' value='$date_year[$i]'>
			<input type='hidden' name='descript[$i]' value='$descript[$i]'>
			<input type='hidden' name='reference[$i]' value='$reference[$i]'>
			<input type='hidden' name='cheqnum[$i]' value='$cheqnum[$i]'>
			<input type='hidden' name='amt[$i]' value='$amt[$i]'>
			<input type='hidden' name='setamt[$i]' value='$setamt[$i]'>
			<input type='hidden' name='setvat[$i]' value='$setvat[$i]'>
			<input type='hidden' name='setvatcode[$i]' value='$setvatcode[$i]'>
			<input type='hidden' name='out[$i]' value='$out[$i]'>";
	}


	$confirm .= "
			$passon
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $confirm;

}



/* confirm function */
function confirm($_POST)
{

	extract($_POST);

	if (isset($back)) {
		unset($back);
		return method($_POST);
	}

	require_lib("validate");
	$v = new  validate ();

	$v->isOk($all, "num", 1,1, "Invalid allocation.");

	for($t = 0; $t < $rec_amount; $t++){

		if (!isset ($descript[$t]) OR !isset ($reference[$t]) OR !isset ($setamt[$t]) OR empty ($descript[$t]) OR empty ($reference[$t]) OR empty ($setamt[$t])) 
			continue;

		if(!isset($out[$t]) OR (strlen($out[$t]) < 1)){
			$out[$t] = $amt[$t];
		}
		if (!isset($out1[$t])) $out1[$t] = '';
		if (!isset($out2[$t])) $out2[$t] = '';
		if (!isset($out3[$t])) $out3[$t] = '';
		if (!isset($out4[$t])) $out4[$t] = '';
		if (!isset($out5[$t])) $out5[$t] = '';

		$v->isOk($bankid[$t], "num", 1, 30, "Invalid Bank Account.");
		$v->isOk($date[$t], "date", 1, 14, "Invalid Date.");
		$v->isOk($descript[$t], "string", 0, 255, "Invalid Description.");
		$v->isOk($reference[$t], "string", 0, 50, "Invalid Reference Name/Number.");
		$v->isOk($cheqnum[$t], "num", 0, 30, "Invalid Cheque number.");
		$v->isOk($amt[$t], "float", 1, 40, "Invalid amount.");
		$v->isOk($setamt[$t], "float", 1, 40, "Invalid settlement amount.");
		$v->isOk($setvat[$t], "string", 1, 10, "Invalid Settlement VAT Option.");
		$v->isOk($setvatcode[$t], "string", 1, 40, "Invalid Settlement VAT code");
		$v->isOk($out[$t], "float", 1, 40, "Invalid out amount.");
		$v->isOk($out1[$t], "float", 0, 40, "Invalid paid amount(currant).");
		$v->isOk($out2[$t], "float", 0, 40, "Invalid paid amount(30).");
		$v->isOk($out3[$t], "float", 0, 40, "Invalid paid amount(60).");
		$v->isOk($out4[$t], "float", 0, 40, "Invalid paid amount(90).");
		$v->isOk($out5[$t], "float", 0, 40, "Invalid paid amount(120).");
		$v->isOk ($cusid[$t], "num", 1, 10, "Invalid customer number.");

		if (isset($invids[$t])) {
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$key] < 0.01){
					continue;
				}

				$v->isOk ($invids[$t][$key], "num", 1, 50, "Invalid Invoice No. [$key]");
				$v->isOk ($paidamt[$t][$key], "float", 1, 40, "Invalid amount to be paid. [$key]");
			}
		}

	}

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}

		for($t = 0; $t < $rec_amount; $t++){
			//$temp1 = $out1[$t];
			$_POST["out1[$t]"] = $out1[$t] + 0;
			$_POST["out2[$t]"] = $out2[$t] + 0;
			$_POST["out3[$t]"] = $out3[$t] + 0;
			$_POST["out4[$t]"] = $out4[$t] + 0;
			$_POST["out5[$t]"] = $out5[$t] + 0;
		}
		return $confirm.alloc($_POST);
	}

	$passon = "";
	for($t = 0; $t < $rec_amount; $t++){

		if (!isset ($descript[$t]) OR !isset ($reference[$t]) OR !isset ($setamt[$t]) OR empty ($descript[$t]) OR empty ($reference[$t]) OR empty ($setamt[$t])) 
			continue;

		$tot[$t] = 0;
		if (isset($invids[$t])) {
			foreach($invids[$t] as $key => $value){
				if($paidamt[$t][$key] < 0.01){
					continue;
				}

				$tot[$t] += $paidamt[$t][$key];
			}
		}

		if (isset($open_amount[$t])) {
			$tot[$t] += array_sum($open_amount[$t]);
		}

		$passon .= "
			<input type='hidden' name='bankid[$t]' value='$bankid[$t]'>
			<input type='hidden' name='date[$t]' value='$date[$t]'>
			<input type='hidden' name='cusid[$t]' value='$cusid[$t]'>
			<input type='hidden' name='descript[$t]' value='$descript[$t]'>
			<input type='hidden' name='reference[$t]' value='$reference[$t]'>
			<input type='hidden' name='cheqnum[$t]' value='$cheqnum[$t]'>
			<input type='hidden' name='out[$t]' value='$out[$t]'>
			<input type='hidden' name='date_day[$t]' value='$date_day[$t]'>
			<input type='hidden' name='date_month[$t]' value='$date_month[$t]'>
			<input type='hidden' name='date_year[$t]' value='$date_year[$t]'>
			<input type='hidden' name='out1[$t]' value='$out1[$t]'>
			<input type='hidden' name='out2[$t]' value='$out2[$t]'>
			<input type='hidden' name='out3[$t]' value='$out3[$t]'>
			<input type='hidden' name='out4[$t]' value='$out4[$t]'>
			<input type='hidden' name='out5[$t]' value='$out5[$t]'>
			<input type='hidden' name='amt[$t]' value='$amt[$t]'>
			<input type='hidden' name='setamt[$t]' value='$setamt[$t]'>
			<input type='hidden' name='setvat[$t]' value='$setvat[$t]'>
			<input type='hidden' name='setvatcode[$t]' value='$setvatcode[$t]'>";
	}

	$confirm = "
		<h3>New Bank Receipt</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='rec_amount' value='$rec_amount'>
			$passon
		<table ".TMPL_tblDflts.">";

	$passon2 = "";
	for($t = 0; $t < $rec_amount; $t++){

		$out[$t] += 0;
		$OUT1[$t] = $out1[$t] + 0;
		$OUT2[$t] = $out2[$t] + 0;
		$OUT3[$t] = $out3[$t] + 0;
		$OUT4[$t] = $out4[$t] + 0;
		$OUT5[$t] = $out5[$t] + 0;

		$tot[$t] = sprint($tot[$t]);
		$amt[$t] = sprint($amt[$t]);
		$out[$t] = sprint($out[$t]);

		if (sprint(($tot[$t] + $out[$t] + $out1[$t] + $out2[$t] + $out3[$t] + $out4[$t] + $out5[$t]) - $amt[$t]) != sprint(0)) {
			$_POST["out1[$t]"] = $out1;
			$_POST["out2[$t]"] = $out2;
			$_POST["out3[$t]"] = $out3;
			$_POST["out4[$t]"] = $out4;
			$_POST["out5[$t]"] = $out5;

		//	return "<li class='err'>The total amount for invoices not equal to the amount received.
		//		Please check the details.</li>".alloc($_POST);
		}

		if (isset($bout[$t])) {
			$out[$t] = $bout[$t];
		}

		/* bank account name */
		if (($bank = qryBankAcct($bankid[$t], "accname, bankname")) === false) {
			$bank['accname'] = "Cash";
			$bank['bankname'] = "";
		}

		/* customer name */
		$cus[$t] = qryCustomer($cusid[$t], "cusname, surname");

		$cus1 = $cus[$t]['cusname'];
		$cus2 = $cus[$t]['surname'];

		$setamt[$t] = sprint ($setamt[$t]);

		if($setvat[$t] == "inc")
			$showsetvat = "VAT Inclusive";
		else 
			$showsetvat = "No VAT";


		$confirm .= "
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
				<td valign='center'>$date[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Received from</td>
				<td valign='center'>$cus1 $cus2</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>$descript[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td valign='center'>$reference[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt[$t]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Settlement Amount</td>
				<td valign='center'>".CUR." $setamt[$t] $showsetvat</td>
			</tr>";

		/* OPTION 1 : AUTO ALLOCATE (confirm) */
		if ($all == 0) {
			// Layout
			$confirm .= "
			".TBL_BR."
			<tr>
				<td colspan='2'><h3>Invoices</h3></td>
			</tr>
			<tr>
				<th>Invoice Number</th>
				<th>Outstanding amount</th>
				<th>Terms</th>
				<th>Date</th>
				<th>Amount</th>
			</tr>";

			$i = 0;
			if (isset($invids[$t])) {
				foreach($invids[$t] as $key => $value){
					if($paidamt[$t][$invids[$t][$key]] < 0.01){
						continue;
					}

					db_connect();
					$ii = $invids[$t][$key];

					if (!isset($itype[$t][$ii]) && !isset($ptype[$t][$ii])) {
						# Get all the details
						$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices
								WHERE invid = '$ii' AND div = '".USER_DIV."'";

						$invRslt = db_exec($sql) or errDie("Unable to access database.");

						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'> - Invalid ord number $invids[$key].</li>";
						}

						$inv = pg_fetch_array($invRslt);

						$invid = $inv['invid'];

						$pp = $paidamt[$t][$invid];

						$confirm .= "
							<input type='hidden' name='paidamt[$t][$invid]' size='7' value='$pp'>
							<input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>
							<td>".CUR." $pp</td>
						</tr>";
					} else if (!isset($ptype[$t][$ii])) {
						$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices
								WHERE invid = '$ii' AND div = '".USER_DIV."'";
						$invRslt = db_exec($sql) or errDie("Unable to access database.");

						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'> - Invalid ord number $ii.</li>";
						}

						$inv = pg_fetch_array($invRslt);

						$invid = $inv['invid'];

						$pp = $paidamt[$t][$invid];

						$confirm .= "
						<input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>
						<input type='hidden' name='paidamt[$t][$invid]' size='7' value='$pp'>
						<input type='hidden' name='itype[$t][$invid]' value='y'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td></td>
							<td>$inv[odate]</td>
							<td>".CUR." $pp</td>
						</tr>";
					} else {
						$sqls = array();
						for ($i = 1; $i <= 12; ++$i) {
							$sqls[] = "SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices 
									WHERE invid='$ii' AND div = '".USER_DIV."'";
						}
						$sql = implode(" UNION ", $sqls);

						$prnInvRslt = db_exec($sql);

						$inv = pg_fetch_array($prnInvRslt);

						$invid = $inv['invid'];

						$pp = $paidamt[$t][$invid];

						$confirm .= "
						<input type='hidden' size='20' name='invids[$t][$invid]' value='$inv[invid]'>
						<input type='hidden' name='paidamt[$t][$invid]' size='7' value='$pp'>
						<input type='hidden' name='ptype[$t][$invid]' value='y'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td></td>
							<td>$inv[odate]</td>
							<td>".CUR." $pp</td>
						</tr>";
					}
				}
			}

			if ($out[$t] > 0) {

				/* START OPEN ITEMS */
				$ox = "";

				db_conn('cubit');
				$sql = "SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid[$t]' ORDER BY date";
				$rslt = db_exec($sql) or errDie("Unable to get open items.");

				$open_out[$t] = $out[$t];

				$i = 0;

				while ($od = pg_fetch_array($rslt)) {
					if($open_out[$t] == 0) {
						continue;
					}

					$oid = $od['id'];

					$bgColor = bgcolor($i);

					if ($open_out[$t] >= $od['balance']) {
						$open_amount[$t][$oid] = $od['balance'];
						$open_out[$t] = sprint($open_out[$t]-$od['balance']);
						$ox .= "
							<input type='hidden' size='20' name='open[$t][$oid]' value='$oid'>
							<input type='hidden' name='open_amount[$t][$oid]' value='$open_amount[$t][$oid]'>
							<tr class='".bg_class()."'>
								<td>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td>".CUR." $open_amount[$t][$oid]</td>
							</tr>";
					} else if ($open_out[$t] < $od['balance']) {
						$open_amount[$t][$oid] = $open_out[$t];
						$open_out[$t] = 0;

						$ox .= "
							<input type='hidden' size='20' name='open[$t][$oid]' value='$od[id]'>
							<input type='hidden' name='open_amount[$t][$oid]' value='$open_amount[$t][$oid]'>
							<tr bgcolor='".bgcolor($i)."'>
								<td>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td>".CUR." $open_amount[$t][$oid]</td>
							</tr>";
					}
				}

				if (open()) {
					$confirm .= "
					".TBL_BR."
					<tr>
						<td colspan='2'><h3>Outstanding Transactions</h3></td>
					</tr>
					<tr>
						<th>Description</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

					$confirm .= $ox;
					$bout[$t] = $out[$t];
					$out[$t] = $open_out[$t];
					$out[$t] = sprint ($out[$t]);
					if ($out[$t] > 0) {
						$confirm .= "
						<tr class='".bg_class()."'>
							<td colspan='4'><b>A general transaction will credit the
								client's account with ".CUR." $out[$t] </b></td>
						</tr>";
					}

					$out[$t] = $bout[$t];
				} else {
					$out[$t] = sprint ($out[$t]);
					$confirm .= "
					<tr class='".bg_class()."'>
						<td colspan='5'><b>A general transaction will credit the
							client's account with ".CUR." $out[$t] </b></td>
					</tr>";
				}
			}
			$confirm .= TBL_BR;
		}

		$confirm .= TBL_BR.TBL_BR;


//		$passon2 .= "
//	<input type='hidden' name='out1[$t]' value='$out1[$t]'>
//	<input type='hidden' name='out2[$t]' value='$out2[$t]'>
//	<input type='hidden' name='out3[$t]' value='$out3[$t]'>
//	<input type='hidden' name='out4[$t]' value='$out4[$t]'>
//	<input type='hidden' name='out5[$t]' value='$out5[$t]'>
//			";
	}
/*
	<tr>
		<td colspan='5' align='right'><input type='submit' name='batch' value='Add To Batch'></td>
	</tr>
*/
	$confirm .= "
			$passon2
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='4'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $confirm;

}



/* write function */
function write($_POST)
{

	extract($_POST);

	if (isset($back)) {
		unset($_POST["back"]);
		return alloc($_POST);
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	require_lib("validate");

	$v = new  validate ();

	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($rec_amount, "num", 1,5, "Invalid amount of entries.");

	for ($t = 0; $t < $rec_amount; $t++){

		if (!isset ($descript[$t]) OR !isset ($reference[$t]) OR !isset ($setamt[$t]) OR empty ($descript[$t]) OR empty ($reference[$t]) OR empty ($setamt[$t])) 
			continue;

		$v->isOk($bankid[$t], "num", 1, 30, "Invalid Bank Account.");
		$v->isOk($date[$t], "date", 1, 14, "Invalid Date.");
		$v->isOk($out[$t], "float", 1, 40, "Invalid out amount.");
		$v->isOk($descript[$t], "string", 0, 255, "Invalid Description.");
		$v->isOk($reference[$t], "string", 0, 50, "Invalid Reference Name/Number.");
		$v->isOk($cheqnum[$t], "num", 0, 30, "Invalid Cheque number.");
		$v->isOk($amt[$t], "float", 1, 40, "Invalid amount.");
		$v->isOk($setamt[$t], "float", 1, 40, "Invalid Settlement amount.");
		$v->isOk($setvat[$t], "string", 1, 10, "Invalid Settlement VAT Option.");
		$v->isOk($setvatcode[$t], "string", 1, 40, "Invalid Settlement VAT code");
		$v->isOk($cusid[$t], "num", 1, 40, "Invalid customer number.");
		$v->isOk($out1[$t], "float", 0, 40, "Invalid paid amount(currant).");
		$v->isOk($out2[$t], "float", 0, 40, "Invalid paid amount(30).");
		$v->isOk($out3[$t], "float", 0, 40, "Invalid paid amount(60).");
		$v->isOk($out4[$t], "float", 0, 40, "Invalid paid amount(90).");
		$v->isOk($out5[$t], "float", 0, 40, "Invalid paid amount(120).");

		if (isset($invids[$t])) {
			foreach($invids[$t] as $key => $value){
				$v->isOk ($invids[$t][$key], "num", 1, 50, "Invalid Invoice No.");
				$v->isOk ($paidamt[$t][$key], "float", 1, 40, "Invalid amount to be paid.");
			}
		}

		if (strtotime($date[$t]) >= strtotime($blocked_date_from) AND strtotime($date[$t]) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
			return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
		}

	}

	if ($v->isError ()) {
		$confirm = $v->genErrors();
		return $confirm.confirm($_POST);
	}




	for ($t = 0; $t < $rec_amount; $t++){

		if (!isset ($descript[$t]) OR !isset ($reference[$t]) OR !isset ($setamt[$t]) OR empty ($descript[$t]) OR empty ($reference[$t]) OR empty ($setamt[$t])) 
			continue;

		/* get bank account id */
		if(($bank_acc[$t] = getbankaccid($bankid[$t])) === false) {
			$sql = "SELECT accid FROM core.accounts WHERE accname='Cash on Hand'";
			$rslt = db_exec($sql);

			if (pg_num_rows($rslt) < 1) {
				if ($bankid[$t] == 0) {
					return "There is no 'Cash on Hand' account, there was one, but
						its not there now, you mudst have deleted it, if you want
						to use cash functionality please create a 'Cash on Hand' account.";
				} else {
					return "Invalid bank acc.";
				}
			}

			$bank_acc[$t] = pg_fetch_result($rslt, 0);
		}

		$cus = qryCustomer($cusid[$t], "cusnum, deptid, cusname, surname");
		$dept = qryDepartment($cus["deptid"], "debtacc");
		$refnum = getrefnum();

		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# date format
		$sdate[$t] = explode("-", $date[$t]);
		$sdate[$t] = $sdate[$t][2]."-".$sdate[$t][1]."-".$sdate[$t][0];
		$cheqnum[$t] = 0 + $cheqnum[$t];
		$pay = "";
		$accdate[$t] = $sdate[$t];

		/* Paid invoices */
		$invidsers = "";
		$rinvids = "";
		$amounts = "";
		$invprds = "";
		$rages = "";

		/* OPTION 1 : AUTO ALLOCATE (write) */
		if ($all == 0) {
			# update the customer (make balance less)
			$sql = "UPDATE cubit.customers SET balance = (balance - '$amt[$t]'::numeric(13,2))
					WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			if (isset($invids[$t])) {
				foreach($invids[$t] as $key => $value) {
					$ii = $invids[$t][$key];
					$pp = $paidamt[$t][$key];
					/* OPTION 1: STOCK INVOICES */
					if (!isset($itype[$t][$ii]) && !isset($ptype[$t][$ii])) {
						$sql = "SELECT prd,invnum,odate FROM cubit.invoices
								WHERE invid ='$ii' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'>Invalid Invoice Number.</li>";
						}
						$inv = pg_fetch_array($invRslt);

						$inv['invnum'] += 0;

						// reduce invoice balance
						$sql = "UPDATE cubit.invoices 
								SET balance = (balance - $pp::numeric(13,2))
								WHERE invid = '$ii' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE cubit.open_stmnt 
								SET balance = (balance - $pp::numeric(13,2))
								WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						# record the payment on the statement
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, amount, date, 
								type, div, allocation_date
							) VALUES (
								'$cus[cusnum]', '$inv[invnum]', '".($pp - ($pp * 2))."', '$sdate[$t]', 
								'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank_acc[$t], $sdate[$t], $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$t][$key], "c");

						$rinvids .= "|$invids[$t][$key]";
						$amounts .= "|$pp";

						if ($inv['prd'] == "0") {
							$inv['prd'] = PRD_DB;
						}

						$invprds .= "|$inv[prd]";
						$rages .= "|0";
						$invidsers .= " - $inv[invnum]";
					/* OPTION 1: NONS STOCK INVOICES */
					} else if (!isset($ptype[$t][$ii])) {
						$sql = "SELECT prd,invnum,descrip,age,odate FROM cubit.nons_invoices
								WHERE invid ='$ii' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'>Invalid Invoice Number.</li>";
						}

						$inv = pg_fetch_array($invRslt);

						$inv['invnum'] += 0;

						# reduce the money that has been paid
						$sql = "UPDATE cubit.nons_invoices
								SET balance = (balance - $pp::numeric(13,2))
								WHERE invid = '$ii' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE cubit.open_stmnt
								SET balance = (balance - $pp::numeric(13,2))
								WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						# record the payment on the statement
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, amount, date, 
								type, div, allocation_date
							) VALUES (
								'$cus[cusnum]', '$inv[invnum]', '".($pp - ($pp * 2))."', '$sdate[$t]', 
								'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$inv[odate]'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank_acc[$t], $sdate[$t], $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$t][$key], "c");

						recordCT($pp, $cus['cusnum'],$inv['age'],$accdate[$t]);

						$rinvids .= "|$ii";
						$amounts .= "|$pp";
						$invprds .= "|0";
						$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";
					} else {
						/* pos invoices */
						$sqls = array();
						for ($i = 1; $i <= 12; ++$i) {
							$sqls[] = "SELECT '$i' AS prd,invid,invnum,odate FROM \"$i\".pinvoices 
									WHERE invid='$ii' AND div='".USER_DIV."'";
						}
						$sql = implode(" UNION ", $sqls);

						$invRslt = db_exec($sql) or errDie ("Unable to retrieve invoice details from database.");

						if (pg_numrows ($invRslt) < 1) {
							return "<li class='err'>Invalid Invoice Number.</li>";
						}

						$inv = pg_fetch_array($invRslt);

						// reduce the invoice balance
						$sql = "UPDATE \"$inv[prd]\".pinvoices 
								SET balance = (balance - $pp::numeric(13,2)) 
								WHERE invid = '$ii' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE cubit.open_stmnt 
								SET balance = (balance - $pp::numeric(13,2)) 
								WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						# record the payment on the statement
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, amount, date, type, div, allocation_date
							) VALUES (
								'$cus[cusnum]','$inv[invnum]', '".($pp - ($pp * 2))."','$sdate[$t]', 'Payment for Non Stock Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank_acc[$t], $sdate[$t], $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum]", $paidamt[$t][$key], "c");

						recordCT($paidamt[$t][$key], $cus['cusnum'],0,$accdate[$t]);

						$rinvids .= "|$invids[$t][$key]";
						$amounts .= "|$paidamt[$t][$key]";
						$invprds .= "|$inv[prd]";
						//$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";
					}
				}
			}

			$cols = grp(
				m("bankid", $bankid[$t]),
				m("trantype", "deposit"),
				m("date", $sdate[$t]),
				m("name", "$cus[cusname] $cus[surname]"),
				m("descript", "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]"),
				m("cheqnum", $cheqnum[$t]),
				m("amount", $amt[$t]),
				m("banked", "no"),
				m("accinv", $dept["debtacc"]),
				m("cusnum", $cus["cusnum"]),
				m("rinvids", $rinvids),
				m("amounts", $amounts),
				m("invprds", $invprds),
				m("rages", $rages),
				m("reference", $reference[$t]),
				m("div", USER_DIV)
			);

			$dbobj = new dbUpdate("cashbook", "cubit", $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();

			/*
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript,
						cheqnum, amount, banked, accinv, cusnum, rinvids, amounts,
						invprds, rages, reference, div)
					VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]',
						'',
						'$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]',
						'$rinvids', '$amounts', '$invprds', '$rages', '$reference',
						'".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
			*/
			
			$refnum = getrefnum($accdate[$t]);
			
			writetrans($bank_acc[$t], $dept['debtacc'], $accdate[$t], $refnum, $amt[$t],
				"Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

			db_conn('cubit');
			if ($out > 0) {
				/* START OPEN ITEMS */
				$openstmnt = new dbSelect("open_stmnt", "cubit", grp(
					m("where", "balance>0 AND cusnum='$cusid[$t]'"),
					m("order", "date")
				));
				$openstmnt->run();

				$open_out[$t] = $out[$t];
				$i = 0;
				$ox = "";

				while ($od = $openstmnt->fetch_array()) {
					if ($open_out[$t] == 0) {
						continue;
					}

					$oid = $od['id'];
					if ($open_out[$t] >= $od['balance']) {
						$open_amount[$t][$oid] = $od['balance'];
						$open_out[$t] = sprint($open_out[$t]-$od['balance']);
						$ox .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' size='20' name='open[$t][$oid]' value='$oid'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type='hidden' name='open_amount[$t][$oid]' value='$open_amount[$t][$oid]'>".CUR." $open_amount[$t][$oid]</td>
							</tr>";

						$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'".$open_amount[$t][$oid]."' WHERE id='$oid'";
						$Ri = db_exec($Sl) or errDie("Unable to update statement.");

					} elseif($open_out < $od['balance']) {
						$open_amount[$t][$oid] = $open_out[$t];
						$open_out = 0;
						$ox .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' size='20' name='open[$t][$oid]' value='$od[id]'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type='hidden' name='open_amount[$t][$oid]' value='$open_amount[$t][$oid]'>".CUR." $open_amount[$t][$oid]</td>
							</tr>";

						$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'".$open_amount[$t][$oid]."' WHERE id='$oid'";
						$Ri = db_exec($Sl)or errDie("Unable to update statement.");
					}
					$i++;
				}

				if(open()) {
					$bout[$t] = $out[$t];
					$out[$t] = $open_out[$t];
					if($out > 0) {
						$sql = "
							INSERT INTO cubit.open_stmnt (
								cusnum, invid, amount, balance, date, 
								type, st, div
							) VALUES (
								'$cus[cusnum]', '0', '-$out[$t]', '-$out[$t]', '$sdate[$t]', 
								'Payment Received', 'n', '".USER_DIV."'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
						//$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
					}

					$out[$t] = $bout[$t];
				} else  {//$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
				}
			}

			if ($out[$t] > 0) {
				recordCT($out[$t], $cus['cusnum'],0,$accdate[$t]);

				$cols = grp(
					m("cusnum", $cus["cusnum"]),
					m("invid", 0),
					m("amount", -$out[$t]),
					m("date", $sdate[$t]),
					m("type", "Payment Received"),
					m("div", USER_DIV),
					m("allocation_date", $accdate[$t])
				);

				$dbobj = new dbUpdate("stmnt", "cubit", $cols);
				$dbobj->run(DB_INSERT);
				$dbobj->free();

				custledger($cus['cusnum'], $bank_acc[$t], $sdate[$t], "PAYMENT", "Payment received.", $out[$t], "c");
			}
		}


		/* start moving invoices */
		// move invoices that are fully paid
		$sql = "SELECT * FROM cubit.invoices WHERE balance=0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
		$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		while ($x = pg_fetch_array($invbRslt)) {
			if (($prd = $x['prd']) == "0") {
				$prd = PRD_DB;
			}

			// move invoice
			$cols = grp(
				m("invid", $x["invid"]),
				m("invnum", $x["invnum"]),
				m("deptid", $x["deptid"]),
				m("cusnum", $x["cusnum"]),
				m("deptname", $x["deptname"]),
				m("cusacc", $x["cusacc"]),
				m("cusname", $x["cusname"]),
				m("surname", $x["surname"]),
				m("cusaddr", $x["cusaddr"]),
				m("cusvatno", $x["cusvatno"]),
				m("cordno", $x["cordno"]),
				m("ordno", $x["ordno"]),
				m("chrgvat", $x["chrgvat"]),
				m("terms", $x["terms"]),
				m("traddisc", $x["traddisc"]),
				m("salespn", $x["salespn"]),
				m("odate", $x["odate"]),
				m("delchrg", $x["delchrg"]),
				m("subtot", $x["subtot"]),
				m("vat", $x["vat"]),
				m("total", $x["total"]),
				m("age", $x["age"]),
				m("comm", $x["comm"]),
				m("discount", $x["discount"]),
				m("delivery", $x["delivery"]),
				m("docref", $x["docref"]),
				m("prd", $x["prd"]),
				m("delvat", $x["delvat"]),
				m("balance", 0),
				m("printed", "y"),
				m("done", "y"),
				m("username", USER_NAME),
				m("div", USER_DIV)
			);

			$dbobj = new dbUpdate("invoices", $prd, $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();

			// record movement
			$cols = grp(
				m("invtype", "inv"),
				m("invnum", $x["invnum"]),
				m("prd", $x["prd"]),
				m("docref", $x["docref"]),
				m("div", USER_DIV)
			);

			$dbobj->setTable("movinv", "cubit");
			$dbobj->setOpt($cols);
			$dbobj->run();
			$dbobj->free();

			// move invoice items
			$inv_items = new dbSelect("inv_items", "cubit", grp(
				m("where", wgrp(
					m("invid", $x["invid"]),
					m("div", USER_DIV)
				))
			));
			$inv_items->run();

			while ($xi = $inv_items->fetch_array()){
				$xi['vatcode'] += 0;
				$xi['account'] += 0;
				$xi['del'] += 0;

				$cols = grp(
					m("invid", $x["invid"]),
					m("whid", $xi["whid"]),
					m("stkid", $xi["stkid"]),
					m("qty", $xi["qty"]),
					m("unitcost", $xi["unitcost"]),
					m("amt", $xi["amt"]),
					m("disc", $xi["disc"]),
					m("discp", $xi["discp"]),
					m("vatcode", $xi["vatcode"]),
					m("account", $xi["account"]),
					m("description", $xi["description"]),
					m("del", $xi["del"]),
					m("noted", $xi["noted"]),
					m("serno", $xi["serno"]),
					m("div", USER_DIV)
				);

				$dbobj->setTable("inv_items", $prd);
				$dbobj->setOpt($cols);
				$dbobj->run();
				$dbobj->free();
			}

			/* remove invoice from cubit schema */
			$dbobj = new dbDelete("invoices", "cubit", wgrp(
				m("invid", $x["invid"]),
				m("div", USER_DIV)
			));
			$dbobj->run();

			$dbobj->setTable("inv_items", "cubit");
			$dbobj->run();
		}

		/* start moving invoices */
		// move invoices that are fully paid
		$sql = "SELECT * FROM cubit.invoices WHERE balance=0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
		$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		while ($x = pg_fetch_array($invbRslt)) {
			if (($prd = $x['prd']) == "0") {
				$prd = PRD_DB;
			}

			// move invoice
			$cols = grp(
				m("invid", $x["invid"]),
				m("invnum", $x["invnum"]),
				m("deptid", $x["deptid"]),
				m("cusnum", $x["cusnum"]),
				m("deptname", $x["deptname"]),
				m("cusacc", $x["cusacc"]),
				m("cusname", $x["cusname"]),
				m("surname", $x["surname"]),
				m("cusaddr", $x["cusaddr"]),
				m("cusvatno", $x["cusvatno"]),
				m("cordno", $x["cordno"]),
				m("ordno", $x["ordno"]),
				m("chrgvat", $x["chrgvat"]),
				m("terms", $x["terms"]),
				m("traddisc", $x["traddisc"]),
				m("salespn", $x["salespn"]),
				m("odate", $x["odate"]),
				m("delchrg", $x["delchrg"]),
				m("subtot", $x["subtot"]),
				m("vat", $x["vat"]),
				m("total", $x["total"]),
				m("age", $x["age"]),
				m("comm", $x["comm"]),
				m("discount", $x["discount"]),
				m("delivery", $x["delivery"]),
				m("docref", $x["docref"]),
				m("prd", $x["prd"]),
				m("delvat", $x["delvat"]),
				m("balance", 0),
				m("printed", "y"),
				m("done", "y"),
				m("username", USER_NAME),
				m("div", USER_DIV)
			);

			$dbobj = new dbUpdate("invoices", $prd, $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();

			// record movement
			$cols = grp(
				m("invtype", "inv"),
				m("invnum", $x["invnum"]),
				m("prd", $x["prd"]),
				m("docref", $x["docref"]),
				m("div", USER_DIV)
			);

			$dbobj->setTable("movinv", "cubit");
			$dbobj->setOpt($cols);
			$dbobj->run();
			$dbobj->free();

			// move invoice items
			$inv_items = new dbSelect("inv_items", "cubit", grp(
				m("where", wgrp(
					m("invid", $x["invid"]),
					m("div", USER_DIV)
				))
			));
			$inv_items->run();

			while ($xi = $inv_items->fetch_array()){
				$xi['vatcode'] += 0;
				$xi['account'] += 0;
				$xi['del'] += 0;

				$cols = grp(
					m("invid", $x["invid"]),
					m("whid", $xi["whid"]),
					m("stkid", $xi["stkid"]),
					m("qty", $xi["qty"]),
					m("unitcost", $xi["unitcost"]),
					m("amt", $xi["amt"]),
					m("disc", $xi["disc"]),
					m("discp", $xi["discp"]),
					m("vatcode", $xi["vatcode"]),
					m("account", $xi["account"]),
					m("description", $xi["description"]),
					m("del", $xi["del"]),
					m("noted", $xi["noted"]),
					m("serno", $xi["serno"]),
					m("div", USER_DIV)
				);

				$dbobj->setTable("inv_items", $prd);
				$dbobj->setOpt($cols);
				$dbobj->run();
				$dbobj->free();
			}

			/* remove invoice from cubit schema */
			$dbobj = new dbDelete("invoices", "cubit", wgrp(
				m("invid", $x["invid"]),
				m("div", USER_DIV)
			));
			$dbobj->run();
			$dbobj->setTable("inv_items", "cubit");
			$dbobj->run();
		}

		#do journal for the settlement discount here ... now ...
		if($setamt[$t] > 0){

			db_conn('core');
			#get settlement accid
			$get_setacc = "SELECT accid FROM accounts WHERE accname = 'Debtors Settlement Discount'";
			$run_setacc = db_exec($get_setacc) or errDie ("Unable to get settlement account information");
			$setaccid = pg_fetch_result ($run_setacc,0,0);

			#calculate the settlement vat ... and amt
			if(isset($setvat[$t]) AND $setvat[$t] == 'inc'){
				db_connect ();
				$get_vcode = "SELECT * FROM vatcodes WHERE id = '$setvatcode[$t]' LIMIT 1";
				$run_vcode = db_exec($get_vcode) or errDie ("Unable to get vatcode informtion.");
				if(pg_numrows($run_vcode) < 1){
					return "<li class='err'>Settlement Discount VAT Code Not Set.</li>";
				}
				$vd = pg_fetch_array ($run_vcode);

				#vat inc ... recalculate the amts
				$setvatamt = sprint(($setamt[$t])*($vd['vat_amount']/(100+$vd['vat_amount'])));
				$setamt[$t] = sprint ($setamt[$t] - $setvatamt);

				$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");

				$svattot = sprint ($setamt[$t]+$setvatamt - (($setamt[$t]+$setvatamt) * 2));
				$svatamt = sprint ($setvatamt - ($setvatamt * 2));

				#process the vat amt ...
				writetrans($vatacc, $dept['debtacc'],  $accdate[$t], $refnum, $setvatamt, "VAT Received on Settlement Discount for Customer : $cus[cusname] $cus[surname]");
				vatr($vd['id'],$accdate[$t],"OUTPUT",$vd['code'],$refnum,"VAT for Settlement Discount for Customer : $cus[cusname] $cus[surname]",$svattot,$svatamt);
			}else {
				#no vat for set amt ... do nothing
				$setvatamt = 0;
				$svattot = 0;
				$svatamt = 0;
			}

			writetrans($setaccid, $dept['debtacc'],  $accdate[$t], $refnum, sprint ($setamt[$t]), "Settlement Discount For $cus[cusname] $cus[surname]");

			custledger($cus['cusnum'], $bank_acc[$t], $sdate[$t], "$refnum", "Payment Settlement Discount Received.", sprint ($setamt[$t]+$setvatamt), "c");

			$sql = "
				INSERT INTO cubit.stmnt (
					cusnum, invid, amount, date, 
					type, div, allocation_date
				) VALUES (
					'$cus[cusnum]', '0', '".($svattot)."', '$sdate[$t]', 
					'Settlement Discount for Payment. Ref $refnum', '".USER_DIV."', '$accdate[$t]'
				)";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			db_connect ();

			#record this paid settlement discount for reporting ...
			$settl_sql = "
				INSERT INTO settlement_cus (
					customer, amt, setamt, setvatamt, setvat, 
					setvatcode, tdate, sdate, refnum
				) VALUES (
					'$cus[cusnum]', '$amt[$t]', '$setamt[$t]', '$setvatamt', '$setvat[$t]', 
					'$setvatcode[$t]', '$accdate[$t]', 'now', '$refnum[$t]'
				)";
			$run_settl = db_exec($settl_sql) or errDie ("Unable to get debtor settlement information.");

		}

		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	}


	// status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Bank Receipt</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Receipt added to cash book.</td>
			</tr>
		</table>";

	$OUTPUT = "
		<center>
		<table width='90%'>
			<tr valign='top'>
				<td width='50%'>$write</td>
				<td align='center'>"
					.mkQuickLinks(
						ql("bank-pay-add.php", "Add Bank Payment"),
						ql("bank-recpt-add.php", "Add Bank Receipt"),
						ql("bank-recpt-inv.php", "Add Customer Payment"),
						ql("cashbook-view.php", "View Cash Book")
					)."
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
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y'
				AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}


function ageage($cusnum, $age)
{

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum'
				AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;

}


# records for CT
function recordCT($amount, $cusnum, $age, $date="", $changemon = false)
{

	/*
	db_connect();

	if($date=="") {
	$date=date("Y-m-d");
	}

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
	while($dat = pg_fetch_array($rs)){
	if(floatval($amount) > 0){
	if($dat['balance'] > $amount){
	# Remove make amount less
	$sql = "UPDATE custran SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	$amount =0 ;
	}else{
	# remove small ones
	//if($dat['balance'] > $amount){
	$amount -= $dat['balance'];
	$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	//}
	}
	}
	}
	if($amount > 0){
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}
	}else{
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	*/

	db_connect();

	if($date == "") {
		$date = date("Y-m-d");
	}

	$amount = ($amount * (-1));

	/*if ($changemon === false) {
	$date_ins = "'$date'";
	} else {
	$prd = $age * 30;
	$date_ins = "('$date'::date - '$prd days'::interval)::date";
	}*/

	$date_ins = "'$date'";

	$sql = "
		INSERT INTO custran (
			cusnum, odate, balance, div, age
		) VALUES (
			'$cusnum', $date_ins, '$amount', '".USER_DIV."', '$age'
		)";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

}


function allocamt(&$tot, $invbal)
{

	if ($tot >= $invbal) {
		$val = $invbal;
		$tot -= $invbal;
	} else {
		$val = $tot;
		$tot = 0;
	}

	return sprint($val);

}



?>
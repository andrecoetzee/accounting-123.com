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
			if(strlen($_POST["accnum"])==0) {
				# redirect if not local supplier
				if(!is_local("customers", "cusnum", $_POST["cusid"])){
					// print "SpaceBar";
					header("Location: bank-recpt-inv-int.php?cusid=$_POST[cusid]");
					exit;
				}
			}
			$OUTPUT = method($_POST["cusid"]);
			break;
		case "alloc":
			$OUTPUT = alloc($_POST);
			break;
		case "confirm":
			if(isset($_POST["confirm"]))
				$OUTPUT = confirm($_POST);
			else 
				$OUTPUT = alloc($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = sel_cus($_POST);
	}
} elseif(isset($_GET["cusnum"])) {
	# Display default output
	$OUTPUT = alloc ($_GET);//method($_GET["cusnum"]);
} else {
	# Display default output
	$OUTPUT = sel_cus($_POST);
}

# get templete
require("../template.php");




# Insert details
function sel_cus($_POST)
{

	extract($_POST);

	// customers Drop down selections
	db_connect();
	$cust = "<select name='cusid'>";
	$sql = "SELECT accno, cusnum, cusname, surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li> There are no Debtors in Cubit.</li>"
		.mkQuickLinks(
			ql("../core/trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	}

	if(!isset($cusid)) {
		$cusid = 0;
	}

	while($cus = pg_fetch_array($cusRslt)){
		if($cus['cusnum'] == $cusid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$cust .= "<option $sel value='$cus[cusnum]'>$cus[accno] - $cus[cusname] $cus[surname]</option>";
	}
	$cust .= "</select>";


//	<tr bgcolor='".bgcolorg()."'>
//		<td colspan='2' align='center'>OR</td>
//	</tr>
//	<tr bgcolor='".bgcolorg()."'>
//		<td>Input customer account number</td>
//		<td><input type='text' name='accnum' size='10'></td>
//	</tr>


	// layout
	$add = "
		<h3>New Bank Receipt</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='alloc'>
			<tr>
				<th colspan='2'>Select Customer</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customers</td>
				<td>$cust</td>
			</tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Enter Details >'></td>
			</tr>
		</table>".
		mkQuickLinks(
			ql("../core/trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $add;

}




//function method($cusid)
//{
//
//	# validate input
//	require_lib("validate");
//	$v = new  validate ();
//	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
//
//	# display errors, if any
//	if ($v->isError ()) {
//		$confirm = "";
//		$errors = $v->getErrors();
//		foreach ($errors as $e) {
//			$confirm .= "<li class='err'>".$e["msg"]."</li>";
//		}
//		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
//		return $confirm;
//	}
//
//
//
//	global $_POST;
//	global $_GET;
//
//	extract($_POST);
//
//	if(isset($accnum)) {
//		$accnum = remval($accnum);
//		if(strlen($accnum)>0) {
//			db_conn('cubit');
//
//			$Sl = "SELECT * FROM customers WHERE lower(accno)=lower('$accnum')";
//			$Ri = db_exec($Sl);
//			if(pg_num_rows($Ri) < 1) {
//				return "<li class='err'>Invalid account number</li>".sel_cus($_POST);
//			}
//
//			$cd = pg_fetch_array($Ri);
//
//			$cusid = $cd['cusnum'];
//		}
//	}
//
//	// customers Drop down selections
//	db_connect();
//	$sql = "SELECT cusname,surname,accno,contname,tel,balance FROM customers WHERE cusnum ='$cusid' AND div = '".USER_DIV."'";
//	$cusRslt = db_exec($sql);
//	$numrows = pg_numrows($cusRslt);
//	if(empty($numrows)){
//		return "<li> Invalid Debtor.</li>";
//	}
//	$cus = pg_fetch_array($cusRslt);
//	$cust = "$cus[cusname] $cus[surname]";
//
//	if(isset($_GET["e"])) {
//		$ex = "<input type='hidden' name='e' value='y'>";
//	} else {
//		$ex = "";
//	}
//
//	// layout
//	$add = "
//			<h3>New Receipt</h3>
//			<table ".TMPL_tblDflts.">
//			<form action='".SELF."' method='POST' name='form'>
//				$ex
//				<input type='hidden' name='key' value='alloc'>
//				<input type='hidden' name='cusid' value='$cusid'>
//				<tr>
//					<th colspan='2'>Receipt Details</th>
//				</tr>
//				<tr bgcolor='".bgcolorg()."'>
//					<td>Bank Account / Cash</td>
//					<td valign='center'>
//						<select name='bankid'>";
//
//	db_connect();
//	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
//	$banks = db_exec($sql);
//	$numrows = pg_numrows($banks);
//
//	if(empty($numrows)){
//		return "<li class='err'> There are no accounts held at the selected Bank.</li>
//		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
//	}
//
//	if(!isset($bankid))
//		$bankid = "";
//
//	while($acc = pg_fetch_array($banks)){
//		if($bankid == $acc['bankid']){
//			$add .= "<option value='$acc[bankid]' selected>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
//		}else {
//			$add .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
//		}
//	}
//
//	if(isset($_GET['cash']) OR isset($_POST["cash"])) {
//		if($bankid == "0"){
//			$add .= "<option value='0' selected>Receive Cash</option>";
//		}else {
//			$add .= "<option value='0'>Receive Cash</option>";
//		}
//		$add .= "<input type='hidden' name='cash' value='yes'>";
//	}
//
//	if(!isset($all)) {
//		$all = "0";
//	}
//
//	$as1 = "";
//	$as2 = "";
//	$as3 = "";
//
//	if ($all == 0) {
//		$as1 = "selected";
//	} else if($all == 1) {
//		$as2 = "selected";
//	} else if ($all == 2) {
//		$as3 = "selected";
//	}
//
//	if(!isset($descript)) {
//		$descript = "";
//		$cheqnum = "";
//		$amt = "";
//		$reference = "";
//	}
//
//	if(!isset($date_day)) {
//		$date_day = date("d");
//		$date_month = date("m");
//		$date_year = date("Y");
//	}
//
//
//
//	if(!isset($setamt))
//		$setamt = sprint (0);
//
//	$add .= "
//					</select>
//				</td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Payment Date</td>
//				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Received from</td>
//				<td valign='center'>$cust</td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Description</td>
//				<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Reference</td>
//				<td valign='center'><input size='25' name='reference' value='$reference'></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Cheque Number</td>
//				<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Amount</td>
//				<td valign='center'>".CUR." <input type='text' size='13' name='amt' value='$amt'></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Settlement Discount Amount</td>
//				<td>".CUR." <input type='text' size='13' name='setamt' value='$setamt'></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>Settlement Discount VAT</td>
//				<td>$vatcode_drop <input type='radio' name='setvat' value='inc' checked='yes'>VAT Inclusive <input type='radio' name='setvat' value='novat'> No VAT</td>
//			</tr>
//			<input type='hidden' name='all' value='2'>
//			<tr>
//				<td><input type='submit' name='back' value='&laquo; Correction'></td>
//				<td valign='center' align='right'><input type='submit' value='Allocate >'></td>
//			</tr>
//		</form>
//		</table>";
//
//	$printCust = "
//			$add
//			<h3>Debtors Age Analysis</h3>
//			<table ".TMPL_tblDflts.">
//			<tr>
//				<th>Acc no.</th>
//				<th>Contact Name</th>
//				<th>Tel No.</th>
//				<th>Current</th>
//				<th>30 days</th>
//				<th>60 days</th>
//				<th>90 days</th>
//				<th>120 days</th>
//				<th>Total Outstanding</th>
//			</tr>";
//
//	$curr = age($cusid, 29);
//	$age30 = age($cusid, 59);
//	$age60 = age($cusid, 89);
//	$age90 = age($cusid, 119);
//	$age120 = age($cusid, 149);
//
//	# Customer total
//	$custtot = sprint($curr + $age30 + $age60 + $age90 + $age120);
//
//	if(sprint($custtot) != sprint($cus['balance'])) {
//		$curr = sprint($curr+$cus['balance']-$custtot);
//		$custtot = sprint($cus['balance']);
//	}
//
//	# Alternate bgcolor
//	$printCust .= "
//			<tr bgcolor='".bgcolorg()."'>
//				<td>$cus[accno]</td>
//				<td>$cus[contname]</td>
//				<td>$cus[tel]</td>
//				<td>".CUR." $curr</td>
//				<td>".CUR." $age30</td>
//				<td>".CUR." $age60</td>
//				<td>".CUR." $age90</td>
//				<td>".CUR." $age120</td>
//				<td>".CUR." $custtot</td>
//			</tr>";
//
//	$printCust .= TBL_BR."</table>";
//
//	$OUTPUT =
//	mkQuickLinks(
//		ql("../core/trans-new.php", "Journal Transactions"),
//		ql("../customers-view.php", "View Customers")
//	).$printCust;
//	return $OUTPUT;
//
//}



// allocation
function alloc($_POST,$err="")
{

	extract($_POST);

	if (isset($quickpay)){
		$date = "$date_year-$date_month-$date_day";
		if (!isset($bulk_pay))
			$bulk_pay = "";
		if (!isset($print_recpt))
			$print_recpt = "";
		if ($amt <= 0){
			unset ($_POST['quickpay']);
			return alloc($_POST,"<li class='err'>Amount too small</li>");
		}
		header ("Location: bank-recpt-inv-quick.php?cusid=$cusid&amt=$amt&cheqnum=$cheqnum&reference=$reference&descript=$descript&bankid=$bankid&tdate=$date&pur=&inv=&bulk_pay=$bulk_pay&print_recpt=$print_recpt");
		exit;
	}

	if(!isset($bankid))
		$bankid = "";
	if(!isset($descript))
		$descript = "";
	if(!isset($reference))
		$reference = "";
	if(!isset($cheqnum))
		$cheqnum = "";
	if(!isset($setvat))
		$setvat = " ";
	if(!isset($setvatcode))
		$setvatcode = " ";
	if(!isset($cusid))
		$cusid = $cusnum;
	if(!isset($paidamt) OR !is_array($paidamt))
		$paidamt = array (0);
	if(!isset($stock_setamt) OR !is_array($stock_setamt))
		$stock_setamt = array (0);
	if (!isset($print_recpt) OR strlen ($print_recpt) < 1){
		if ($key == "confirm"){
			$print_recpt_sel = "";
			$print_recpt = "";
		}else {
			$print_recpt_setting = getCSetting("CUST_PRINT_RECPT");
			if (!isset($print_recpt_setting) OR strlen ($print_recpt_setting) < 1) {
				$print_recpt_sel = "";
				$print_recpt = "";
			}else {
				$print_recpt_sel = "checked='yes'";
				$print_recpt = $print_recpt_setting;
			}
		}
	}else {
		$print_recpt_sel = "checked='yes'";
	}

	if (!isset ($amt)) {
		$amt = sprint (array_sum($paidamt));
	}
	$setamt = sprint (array_sum ($stock_setamt));
	$all = 2;

	if(!isset($date_day)) {
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			if (isset($_SESSION["global_day"]) AND strlen ($_SESSION["global_day"]) > 0) 
				$date_day = $_SESSION["global_day"];
			else 
				$date_day = date ("d");
			if (isset($_SESSION["global_month"]) AND strlen ($_SESSION["global_month"]) > 0) 
				$date_month = $_SESSION["global_month"];
			else 
				$date_month = date("m");
			if(isset($_SESSION["global_year"]) AND strlen ($_SESSION["global_year"]) > 0) 
				$date_year = $_SESSION["global_year"];
			else 
				$date_year = date("Y");
		}
	}




	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 0, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
	$v->isOk($setamt, "float", 1, 40, "Invalid Settlement Amount.");
	$v->isOk($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
	$v->isOk($cusid, "num", 1, 10, "Invalid customer number.");
	$v->isOk($print_recpt, "string", 0, 10, "Invalid Print Receipt Setting.");

	if (strlen($date_year) != 4){
		$v->isOk($bankname, "num", 1, 1, "Invalid Date year.");
	}

	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	if ($v->isError()) {
		$confirm = $v->genErrors();
		return $confirm;
	}



	$doset = TRUE;


	db_connect();


######make bank account drop down
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);
	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$bank_drop = "<select name='bankid'>";
	while($acc = pg_fetch_array($banks)){
		if($bankid == $acc['bankid']){
			$bank_drop .= "<option value='$acc[bankid]' selected>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}else {
			$bank_drop .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}
	}
	if(isset($_GET['cash']) OR isset($_POST["cash"])) {
		if($bankid == "0"){
			$bank_drop .= "<option value='0' selected>Receive Cash</option>";
		}else {
			$bank_drop .= "<option value='0'>Receive Cash</option>";
		}
		$send = "<input type='hidden' name='cash' value='yes'>";
	}else {
		$send = "";
	}
	$bank_drop .= "</select>";


###### customer name
	$cus = qryCustomer($cusid, "accno, cusname, surname, setdisc, setdays");

	$setamt = sprint ($setamt);
//	$amt = $amt + $setamt;

	if(!isset($paidamt))
		$paidamt = array (0);

	if (!isset ($overpay)) {
		$overpay = sprint ($amt - array_sum($paidamt));
	}
	if($overpay < 0)
		$overpay = 0.00;


######get vat codes for dropdown
	$get_vatc = "SELECT * FROM vatcodes ORDER BY code";
	$run_vatc = db_exec($get_vatc) or errDie ("Unable to get vat codes information.");
	if(pg_numrows($run_vatc) < 1){
		$vatcode_drop = "<input type='hidden' name='setvatcode' value=''>";
	}else {
		$vatcode_drop = "<select name='setvatcode'>";
		while ($varr = pg_fetch_array ($run_vatc)){
			if(isset($setvatcode) AND $setvatcode == $varr['id']){
				$vatcode_drop .= "<option value='$varr[id]' selected>$varr[code] $varr[description]</option>";
			}else {
				$vatcode_drop .= "<option value='$varr[id]'>$varr[code] $varr[description]</option>";
			}
		}
		$vatcode_drop .= "</select>";
	}

	$setvatsel1 = "";
	$setvatsel2 = "";
	if($setvat == "novat")
		$setvatsel2 = "checked='yes'";
	else 
		$setvatsel1 = "checked='yes'";
	//				var total = document.getElementById('total_id'.counter).value);
//<input type='hidden' name='date' value='$date'>
	$confirm = "
		<h3>New Bank Receipt</h3>
		$err
		<script type=\"application/x-javascript\">
			function updateStockTotal (counter){
				var total_val = getObj('total_id'+counter);
				var htotal_val = getObj('total_hid'+counter);
				var set_val = getObj('set_id'+counter);
				var hset_val = getObj('set_hid'+counter);

				var button_val = getObj('button'+counter);

				if (total_val.value == '0.00'){
					total_val.value = htotal_val.value;
				}else {
					total_val.value = '0.00';
				}
				if (set_val.value == '0.00'){
					set_val.value = hset_val.value;
				}else {
					set_val.value = '0.00';
				}
				button_val.blur();
			}

			function updateNonStockTotal (counter){
				var total_val = getObj('ntotal_id'+counter);
				var htotal_val = getObj('ntotal_hid'+counter);
				var set_val = getObj('nset_id'+counter);
				var hset_val = getObj('nset_hid'+counter);

				var button_val = getObj('nbutton'+counter);

				if (total_val.value == '0.00'){
					total_val.value = htotal_val.value;
				}else {
					total_val.value = '0.00';
				}
				if (set_val.value == '0.00'){
					set_val.value = hset_val.value;
				}else {
					set_val.value = '0.00';
				}
				button_val.blur();
			}

			function updatePosStockTotal (counter){
				var total_val = getObj('ptotal_id'+counter);
				var htotal_val = getObj('ptotal_hid'+counter);
				var set_val = getObj('pset_id'+counter);
				var hset_val = getObj('pset_hid'+counter);

				var button_val = getObj('pbutton'+counter);

				if (total_val.value == '0.00'){
					total_val.value = htotal_val.value;
				}else {
					total_val.value = '0.00';
				}
				if (set_val.value == '0.00'){
					set_val.value = hset_val.value;
				}else {
					set_val.value = '0.00';
				}
				button_val.blur();
			}

		</script>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='accnum' value=''>
			
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='cusid' value='$cusid'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amt' value='$amt'>
			<input type='hidden' name='setamt' value='$setamt'>
			<input type='hidden' name='setvat' value='$setvat'>
			<input type='hidden' name='setvatcode' value='$setvatcode'>
			$send
		<table ".TMPL_tblDflts.">
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account</td>
							<td>$bank_drop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Payment Date</td>
							<td valign='center'>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Received from</td>
							<td valign='center'>$cus[accno] - $cus[cusname] $cus[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference</td>
							<td valign='center'><input size='25' name='reference' value='$reference'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cheque Number</td>
							<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." $amt</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Settlement Discount</td>
							<td>".CUR." $setamt</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Settlement Discount VAT</td>
							<td>
								$vatcode_drop 
								<input type='radio' name='setvat' value='inc' $setvatsel1>VAT Inclusive 
								<input type='radio' name='setvat' value='novat' $setvatsel2> No VAT
							</td>
						</tr>
					</table>
				</td>
				<td width='5%'></td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'><font style='color:red'>Quick</font> Payment</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." <input type='text' size='6' name='amt' value='$amt'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bulk Payments/Single Statement Entry</td>
							<td><input type='checkbox' name='bulk_pay' value='yes'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='right'><input type='submit' name='quickpay' value='Allocate the above payment automatically'></td>
						</tr>
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
						<tr>
							<th><input type='checkbox' $print_recpt_sel name='print_recpt' value='yes'> Print Receipt</th>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='submit' name='confirm' value='Allocate the payments below &raquo'></td>
			</tr>
			".TBL_BR."";



	/* OPTION 3 : ALLOCATE TO EACH INVOICE (allocate) */
	if ($all == 2) {

		if(!isset($paidamt))
			$paidamt = array (0);
		if(!isset($stock_setamt))
			$stock_setamt = array (0);

		$confirm .= "
			</table>
			<table ".TMPL_tblDflts.">
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>Add unallocated payment to customer statement &nbsp;</td>
					<td>&nbsp;".CUR." <input type='text' size='10' name='overpay' value='$overpay'></td>
				</tr>
			</table>
			<table ".TMPL_tblDflts.">";

		/* NORMAL INVOICES */
		db_connect();
		$sql = "
			SELECT invnum, invid, balance, terms, odate FROM invoices 
			WHERE cusnum='$cusid' AND printed='y' AND balance>0 AND div='".USER_DIV."' 
			ORDER BY odate";
		$prnInvRslt = db_exec($sql);
		$tempi = pg_numrows($prnInvRslt);

		if (pg_numrows($prnInvRslt) < 1) {
			$sql = "
				SELECT invnum FROM nons_invoices
				WHERE cusid='$cusid' AND done='y' AND balance>0 AND div='".USER_DIV."'";
			$prnInvRslt = db_exec($sql);

			#no invoices ... check for open and normal nons invoices
			#user must use auto allocation button .... can no longer simply moan...
			if (open()) {
				if (pg_numrows($prnInvRslt) < 1){
					$sql ="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
					$rslt = db_exec($sql) or errDie("Unable to get open items.");
//					if(pg_numrows($rslt) < 1){
//						return "<li class='err'> There are no outstanding invoices for the selected debtor in Cubit.<br>
//						To make a payment in advance please select Auto Allocation</li>".alloc($cusid);
//					}
				}
			} else {
				if (pg_numrows($prnInvRslt) < 1) {
					$sql = "
						SELECT invnum, invid, balance, odate 
						FROM \"".PRD_DB."\".pinvoices 
						WHERE cusnum = '$cusid' AND done = 'y' AND balance>0 AND div = '".USER_DIV."'";
					$prnInvRslt = db_exec($sql);
//					if(pg_numrows($prnInvRslt) < 1){
//						return "<li class='err'> There are no outstanding invoices for the selected debtor in Cubit.<br>
//						To make a payment in advance please select Auto Allocation</li>".method($cusid);
//					}
				}
			}

		} elseif ($tempi > 0) {

			if($doset)
				$showsethead = "
					<th>Settlement</th>
					<th>Potential Settlement Discount</th>";
			else 
				$showsethead = "";

			$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Stock Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th>Terms</th>
					<th>Date</th>
					<th>Amount</th>
					$showsethead
				</tr>";

			$i = 0;
			$invtot = 0;
			$counter = 0;
			while($inv = pg_fetch_array($prnInvRslt)) {
				if (pg_numrows($prnInvRslt)==1) {
					$val = $amt;
				} else {
					$val = "";
				}

				$invid = $inv['invid'];

				if (isset($paidamt[$invid]) AND strlen ($paidamt[$invid]) > 0) {
					$val = sprint ($paidamt[$invid]);
				}

				$val = sprint ($val);

				if($doset) {
					#check if we can find a recommended settlement amt ...
					if ($cus['setdisc'] != "0"){

						#generate the dates ...
						if ($cus['setdays'] == 0)
							$month = $date_month + 1;
						else 
							$month = $date_month;
						$startmonth = $month - 1;
						$firstdate = date("Y-m-d",mktime(0,0,0,$startmonth,$cus['setdays'],$date_year));
						$discdate = date("Y-m-d",mktime (0,0,0,$month,$cus['setdays'],$date_year));
						$lastdate = date("Y-m-d",mktime(0,0,0,$date_month+1,-1,$date_year));

						if (($inv['odate'] > $firstdate) AND ($inv['odate'] < $lastdate) AND ($inv['odate'] <= "$date_year-$date_month-$date_day")){
							#discount applies ...
							#calculate it ...
							$setrec = sprint (($inv['balance'] / 100) * $cus['setdisc']);
						}else {
							#no discount
							$setrec = sprint (0);
						}
					}else {
						$setrec = sprint (0);
					}
					if (!isset($stock_setamt[$invid])){
						$stock_setamt[$invid] = sprint (0);
					}elseif (strlen($stock_setamt[$invid]) > 0){
						$stock_setamt[$invid] = sprint ($stock_setamt[$invid]);
					}

					$showset = "
						<td><input id='set_id$counter' type='text' size='10' name='stock_setamt[$invid]' value='$stock_setamt[$invid]'></td>
						<td><input id='set_hid$counter' type='hidden' name='stock_setamt_val' value='$setrec'>".CUR." $setrec</td>";
				}else {
					$showset = "
						<input id='set_id$counter' type='hidden' name='stock_setamt[$invid]' value='0'>
						<input id='set_hid$counter' type='hidden' name='stock_setamt_val' value='0'>";
				}




				$confirm .= "
					<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
					<input id='total_hid$counter' type='hidden' name='ignore_me' value='$inv[balance]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td>$inv[terms] days</td>
						<td>$inv[odate]</td>
						<td><input id='total_id$counter' type='text' name='paidamt[$invid]' size='10' value='$val'></td>
						$showset
						<td><input id='button$counter' type='checkbox' onClick=\"updateStockTotal($counter);\"></td>
					</tr>";
				$invtot = $invtot + $val;
//				if($counter == 15){
				if($counter != 0 AND $counter % 15 == 0){
					$confirm .= "
						<tr>
							<td colspan='4' align='right'><input type='submit' value='Update'></td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
						</tr>";
//					$counter = 0;
				}
				$counter++;
			}
			$confirm .= "
				<tr>
					<td colspan='4' align='right'><input type='submit' value='Update'></td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
				</tr>";
		}

		/* NON STOCK INVOICES */
		db_connect ();
		$sql = "
			SELECT invnum,invid,balance,odate FROM nons_invoices 
			WHERE cusid='$cusid' AND done='y' AND balance>0 AND div = '".USER_DIV."' 
			ORDER BY odate";
		$prnInvRslt = db_exec($sql);

		if (pg_numrows($prnInvRslt) > 0) {

			if($doset)
				$showsethead = "
					<th>Settlement</th>
					<th>Potential Settlement Discount</th>";
			else 
				$showsethead = "";

			$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Non Stock Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th>&nbsp;</th>
					<th>Date</th>
					<th>Amount</th>
					$showsethead
				</tr>";

			$invtot = 0;
			$counter = 0;
			while($inv = pg_fetch_array($prnInvRslt)){
				$invid = $inv['invid'];

// 				if (pg_numrows($prnInvRslt) == 1) {
// 					$val = $amt;
// 				} else {
// 					$val = "";
// 				}

				if (isset($paidamt["i$invid"]) AND strlen ($paidamt["i$invid"]) > 0) {
					$val = sprint ($paidamt["i$invid"]);
				}else {
					$val = 0;
				}

				$val = sprint ($val);

				if($doset) {
					#check if we can find a recommended settlement amt ...
					if ($cus['setdisc'] != "0"){

						#generate the dates ...
						if ($cus['setdays'] == 0)
							$month = $date_month + 1;
						else 
							$month = $date_month;
						$startmonth = $month - 1;
						$firstdate = date("Y-m-d",mktime(0,0,0,$startmonth,$cus['setdays'],$date_year));
						$discdate = date("Y-m-d",mktime (0,0,0,$month,$cus['setdays'],$date_year));
						$lastdate = date("Y-m-d",mktime(0,0,0,$date_month+1,-1,$date_year));

						if (($inv['odate'] > $firstdate) AND ($inv['odate'] < $lastdate) AND ($inv['odate'] <= "$date_year-$date_month-$date_day")){
							#discount applies ...
							#calculate it ...
							$setrec = sprint (($inv['balance'] / 100) * $cus['setdisc']);
						}else {
							#no discount
							$setrec = sprint (0);
						}
					}else {
						$setrec = sprint (0);
					}
					$iinvid = "i$invid";
					if (!isset($stock_setamt[$iinvid])){
						$stock_setamt[$iinvid] = sprint (0);
					}elseif (strlen ($stock_setamt[$iinvid]) > 0){
						$stock_setamt[$iinvid] = sprint ($stock_setamt[$iinvid]);
					}

					$showset = "
						<td><input id='nset_id$counter' type='text' size='10' name='stock_setamt[i$invid]' value='$stock_setamt[$iinvid]'></td>
						<td><input id='nset_hid$counter' type='hidden' name='stock_setamt_val' value='$setrec'>".CUR." $setrec</td>";
				}else {
					$showset = "
						<input id='nset_hid$counter' type='hidden' name='stock_setamt[$invid]' value='0'>
						<input id='nset_hid$counter' name='stock_setamt_val' value='0'>";
				}


				$confirm .= "
					<input type='hidden' size='20' name='invids[i$invid]' value='$inv[invid]'>
					<input type='hidden' name='itype[i$invid]' value='YnYn'>
					<input id='ntotal_hid$counter' type='hidden' name='ignore_me2' value='$inv[balance]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td><input id='ntotal_id$counter' type='text' name='paidamt[i$invid]' size='10' value='$val'></td>
						$showset
						<td><input id='nbutton$counter' type='checkbox' onClick=\"updateNonStockTotal($counter);\"></td>
					</tr>";
				$invtot = $invtot + $val;
				if($counter == 15){
					$confirm .= "
						<tr>
							<td colspan='4' align='right'><input type='submit' value='Update'></td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
						</tr>";
					$counter = 0;
				}
				$counter++;
			}
			$confirm .= "
				<tr>
					<td colspan='4' align='right'><input type='submit' value='Update'></td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))."</td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))."</td>
				</tr>";
		}

		/* POS INVOICES */
		$sqls = array();
		for ($i = 1; $i <= 12; ++$i) {
			$sqls[] = "
				SELECT invnum, invid, balance, odate 
				FROM \"$i\".pinvoices 
				WHERE cusnum = '$cusid' AND done = 'y' AND balance>0 AND div = '".USER_DIV."'";
		}
		$sql = implode(" UNION ", $sqls);
		$sql .= " ORDER BY odate";
		$prnInvRslt = db_exec($sql);

		if (pg_numrows($prnInvRslt) > 0) {

			if($doset)
				$showsethead = "
					<th>Settlement</th>
					<th>Potential Settlement Discount</th>";
			else 
				$showsethead = "";

			$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding POS Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th></th>
					<th>Date</th>
					<th>Amount</th>
					$showsethead
					$warning
				</tr>";

			$invtot = 0;
			$counter = 0;
			while($inv = pg_fetch_array($prnInvRslt)){
				$invid = $inv['invid'];

// 				if (pg_numrows($prnInvRslt) == 1) {
// 					$val = $amt;
// 				} else {
// 					$val = "";
// 				}

				if (isset($paidamt["p$invid"]) AND strlen ($paidamt["p$invid"]) > 0) {
					$val = sprint ($paidamt["p$invid"]);
				}else {
					$val = 0;
				}

				$val = sprint ($val);

				if($doset) {
					#check if we can find a recommended settlement amt ...
					if ($cus['setdisc'] != "0"){

						#generate the dates ...
						if ($cus['setdays'] == 0)
							$month = $date_month + 1;
						else 
							$month = $date_month;
						$startmonth = $month - 1;
						$firstdate = date("Y-m-d",mktime(0,0,0,$startmonth,$cus['setdays'],$date_year));
						$discdate = date("Y-m-d",mktime (0,0,0,$month,$cus['setdays'],$date_year));
						$lastdate = date("Y-m-d",mktime(0,0,0,$date_month+1,-1,$date_year));

						if (($inv['odate'] > $firstdate) AND ($inv['odate'] < $lastdate) AND ($inv['odate'] <= "$date_year-$date_month-$date_day")){
							#discount applies ...
							$setrec = sprint (($inv['balance'] / 100) * $cus['setdisc']);
						}else {
							#no discount
							$setrec = sprint (0);
						}
					}else {
						$setrec = sprint (0);
					}
					$pinvid = "p$invid";
					if (!isset($stock_setamt[$pinvid])){
						$stock_setamt[$pinvid] = sprint (0);
					}elseif (strlen ($stock_setamt[$pinvid]) > 0){
						$stock_setamt[$pinvid] = sprint ($stock_setamt[$pinvid]);
					}

					$showset = "
						<td><input id='pset_id$counter' type='text' size='10' name='stock_setamt[p$invid]' value='$stock_setamt[$pinvid]'></td>
						<td><input id='pset_hid$counter' type='hidden' name='stock_setamt_val' value='$setrec'>".CUR." $setrec</td>";
				}else {
					$showset = "
						<input id='pset_id$counter' type='hidden' name='stock_setamt[$invid]' value='0'>
						<input id='pset_hid$counter' name='stock_setamt_val' value='0'>";
				}

				$confirm .= "
					<input type='hidden' size='20' name='invids[p$invid]' value='$inv[invid]'>
					<input type='hidden' name='ptype[p$invid]' value='YnYn'>
					<input id='ptotal_hid$counter' type='hidden' name='ignore_me' value='$inv[balance]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td><input id='ptotal_id$counter' type='text' name='paidamt[p$invid]' size='10' value='$val'></td>
						$showset
						<td><input id='pbutton$counter' type='checkbox' onClick=\"updatePosStockTotal($counter);\"></td>
					</tr>";
				$invtot = $invtot + $val;
				if($counter == 15){
					$confirm .= "
						<tr>
							<td colspan='4' align='right'><input type='submit' value='Update'></td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
							<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
						</tr>";
					$counter = 0;
				}
				$counter++;
			}
			$confirm .= "
				<tr>
					<td colspan='4' align='right'><input type='submit' value='Update'></td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
					<td bgcolor='".bgcolorg()."'>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
				</tr>";
		}

		if(open()) {
			db_conn('cubit');

			$Sl = "SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid'  AND type!='Invoice' AND type!='Non-Stock Invoice'  AND type!='Interest on Outstanding balance' ORDER BY date";
			$Ri = db_exec($Sl) or errDie("Unable to get open items.");

			//$open_out=$out;
			$ox = "";

			$i = 0;

			while($od = pg_fetch_array($Ri)) {
				$oid = $od['id'];

				if(!isset($open_amount[$oid])) {
					$open_amount[$oid] = "";
				}

				$ox .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' size='20' name='open[$oid]' value='$oid'>$od[type]</td>
						<td>".CUR." $od[balance]</td>
						<td>$od[date]</td>
						<td><input type='text' name='open_amount[$oid]' value='$open_amount[$oid]'></td>
					</tr>";

				$i++;
			}

			$confirm .= "
				<tr><td colspan='2'><br></td></tr>
				<tr>
					<td colspan='2'><h3>Outstanding Transactions</h3></td>
				</tr>
				<tr>
					<th>Description</th>
					<th>Outstanding Amount</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>
				$ox";
		}
	}

	vsprint($out);

	$confirm .= "
			<input type='hidden' name='out' value='$out'>
			<tr>
				<td colspan='5'></td>
				<td align='right'><input type='submit' name='confirm' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../core/trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $confirm;

}





/* confirm function */
function confirm($_POST)
{

	extract($_POST);

	if (isset($back)) {
		return method ($cusid);
	}

//	$date = "$date_day-$date_month-$date_year";
	$amt = sprint (array_sum($paidamt));
	$setamt = sprint (array_sum ($stock_setamt));

	if (!isset($print_recpt))
		$print_recpt = "";
	if (!isset($descript) OR strlen($descript) < 1)
		$descript = $reference;

	if (!isset($out1)) $out1 = '';
	if (!isset($out2)) $out2 = '';
	if (!isset($out3)) $out3 = '';
	if (!isset($out4)) $out4 = '';
	if (!isset($out5)) $out5 = '';

	$date = "$date_year-$date_month-$date_day";

	require_lib("validate");

	$v = new  validate ();
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($date, "date", 1, 14, "Invalid Date.");
	$v->isOk($descript, "string", 1, 255, "Invalid Description.");
	$v->isOk($reference, "string", 1, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
	$v->isOk($setamt, "float", 1, 40, "Invalid Settlement Amount.");
	$v->isOk($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
//	$v->isOk($out, "float", 1, 40, "Invalid out amount.");
	$v->isOk($out1, "float", 0, 40, "Invalid paid amount(currant).");
	$v->isOk($out2, "float", 0, 40, "Invalid paid amount(30).");
	$v->isOk($out3, "float", 0, 40, "Invalid paid amount(60).");
	$v->isOk($out4, "float", 0, 40, "Invalid paid amount(90).");
	$v->isOk($out5, "float", 0, 40, "Invalid paid amount(120).");
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$v->isOk ($overpay, "float", 1, 40, "Invalid Unallocated Amount.");
	$v->isOk($print_recpt, "string", 0, 10, "Invalid Print Receipt Setting.");
	if (($amt + $overpay) <= 0)
		$v->addError(0,"Invalid Amount Allocated To Receipt.");

	if (isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}
			if(!isset($stock_setamt[$key]) OR strlen($stock_setamt[$key]) < 1)
				$stock_setamt[$key] = 0;
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No. [$key]");
			$v->isOk ($paidamt[$key], "float", 1, 40, "Invalid amount to be paid. [$key]");
			$v->isOk ($stock_setamt[$key], "float", 1, 40, "Invalid Settlement Discount Amount");
		}
	}

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}

		$_POST['OUT1'] = $out1 + 0;
		$_POST['OUT2'] = $out2 + 0;
		$_POST['OUT3'] = $out3 + 0;
		$_POST['OUT4'] = $out4 + 0;
		$_POST['OUT5'] = $out5 + 0;

		return $confirm.alloc($_POST);
	}


	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}



	$out += 0;
	$OUT1 = $out1 + 0;
	$OUT2 = $out2 + 0;
	$OUT3 = $out3 + 0;
	$OUT4 = $out4 + 0;
	$OUT5 = $out5 + 0;

	$tot = 0;
	if (isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}
			$tot += $paidamt[$key];
		}
	}

	if (isset($open_amount)) {
		$tot += array_sum($open_amount);
	}

	$tot = sprint($tot);
	$amt = sprint($amt);
	$out = sprint($out);

	if (sprint(($tot + $out + $out1 + $out2 + $out3 + $out4 + $out5) - $amt) > sprint(0)) {
		$_POST['OUT1'] = $OUT1;
		$_POST['OUT2'] = $OUT2;
		$_POST['OUT3'] = $OUT3;
		$_POST['OUT4'] = $OUT4;
		$_POST['OUT5'] = $OUT5;

		return "<li class='err'>The total amount for invoices is greater than the amount received.
			Please check the details.</li>".alloc($_POST);
	}
	
	if (sprint ($setamt) > 0){
		if (array_sum ($stock_setamt) != $setamt){
			return "<li class='err'>The total settlement amount for invoices is not equal to the amount received.
			Please check the details.</li>".alloc($_POST);
		}
	}

	if (isset($bout)) {
		$out = $bout;
	}
	if (!isset($overpay))
		$overpay = 0;
	$overpay = sprint ($overpay);

	#generate a receipt number
	$receiptnumber = divlastid ("receipt");

	$confirm = "
		<h3>New Bank Receipt</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='cusid' value='$cusid'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='out' value='$out'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='overpay' value='$overpay'>
			<input type='hidden' name='OUT1' value='$OUT1'>
			<input type='hidden' name='OUT2' value='$OUT2'>
			<input type='hidden' name='OUT3' value='$OUT3'>
			<input type='hidden' name='OUT4' value='$OUT4'>
			<input type='hidden' name='OUT5' value='$OUT5'>
			<input type='hidden' name='amt' value='$amt'>
			<input type='hidden' name='setamt' value='$setamt'>
			<input type='hidden' name='setvat' value='$setvat'>
			<input type='hidden' name='setvatcode' value='$setvatcode'>
			<input type='hidden' name='print_recpt' value='$print_recpt'>
		<table ".TMPL_tblDflts.">";

	/* bank account name */
	if (($bankid == "0") OR (($bank = qryBankAcct($bankid, "accname, bankname")) === false)) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	/* customer name */
	$cus = qryCustomer($cusid, "accno, cusname, surname");

	if($setvat == "inc")
		$showsetvat = "VAT Inclusive";
	else 
		$showsetvat = "No VAT";

//	$overpay = sprint ($amt - array_sum($paidamt));
	$overpay = sprint ($overpay);
	if($overpay < 0)
		$overpay = 0.00;

	if ($print_recpt == "yes")
		$show_print_recpt = "Yes";
	else 
		$show_print_recpt = "No";

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
			<td>Payment Date</td>
			<td valign='center'>$date</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Received from</td>
			<td valign='center'>$cus[accno] - $cus[cusname] $cus[surname]</td>
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
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Settlement Discount</td>
			<td valign='center'>".CUR." $setamt $showsetvat</td>
		</tr>
		".TBL_BR."
		<tr bgcolor='".bgcolorg()."'>
			<td>Print Receipt</td>
			<td>$show_print_recpt</td>
		</tr>
		".TBL_BR."
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'><b>A general transaction will credit the client's account with ".CUR." $overpay </b></td>
		</tr>";



	if(sprint ($setamt) > 0)
		$doset = TRUE;
	else 
		$doset = FALSE;

	/* OPTION 3 : ALLOCATE TO EACH INVOICE (confirm) */
	if ($all == 2) {

		if ($doset) 
			$showsethead = "<th>Settlement</th>";
		else 
			$showsethead = "";

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
				$showsethead
			</tr>";

		$i = 0;
		foreach ($invids as $key => $value){

			if ($paidamt[$key] < 0.01){
				continue;
			}

			$paidamt[$key] = sprint ($paidamt[$key]);

			$ii = $invids[$key];
			if (!isset($itype[$key]) && (!isset($ptype[$key]))) {
				/* STOCK INVOICE ! */
				db_connect();
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices
						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");

				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'> -S- Invalid ord number $invids[$key].</li>";
				}

				$inv = pg_fetch_array($invRslt);
				$invid = $inv['invid'];

				#handle warnings ...
				if (($paidamt[$invid] + $stock_setamt[$invid]) < sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying Less Than Total Amount.</li></td>";
				}elseif (($paidamt[$invid] + $stock_setamt[$invid]) > sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying More Than Total Amount Outstanding.</li></td>";
				}else {
					$warning = "";
				}

				if($doset) {
					if(!isset($stock_setamt[$invid]))
						$stock_setamt[$invid] = "";
					$showset = "<td>".CUR." ".sprint ($stock_setamt[$invid])."</td>";
				}else {
					$showset = "<td></td>";
				}

				$confirm .= "
					<input type='hidden' name='paidamt[$key]' size='7' value='$paidamt[$invid]'>
					<input type='hidden' name='stock_setamt[$key]' value='$stock_setamt[$invid]'>
					<input type='hidden' size='20' name='invids[$key]' value='$inv[invid]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td>$inv[terms] days</td>
						<td>$inv[odate]</td>
						<td>".CUR." $paidamt[$key]</td>
						$showset
						$warning
					</tr>";
			} else if(!isset($ptype[$key])) {
				/* NON STOCK INVOICE ! */
				db_connect();

				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices
						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");

				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'> -N- Invalid ord number $invids[$key].</li>";
				}

				$inv = pg_fetch_array($invRslt);
				$invid = "i".$inv['invid'];

				#handle warnings ...
				if (($paidamt[$invid] + $stock_setamt[$invid]) < sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying Less Than Total Amount.</li></td>";
				}elseif (($paidamt[$invid] + $stock_setamt[$invid]) > sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying More Than Total Amount Outstanding.</li></td>";
				}else {
					$warning = "";
				}

				if($doset) {
					if(!isset($stock_setamt[$invid]))
						$stock_setamt[$invid] = "";
					$showset = "<td>".CUR." ".sprint ($stock_setamt[$invid])."</td>";
				}else {
					$showset = "<td></td>";
				}

				$confirm .= "
					<input type='hidden' size='20' name='invids[$key]' value='$inv[invid]'>
					<input type='hidden' name='paidamt[$key]' size='7' value='".$paidamt[$key]."'>
					<input type='hidden' name='stock_setamt[$key]' value='$stock_setamt[$key]'>
					<input type='hidden' name='itype[$key]' value='PcP'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td>".CUR." ".$paidamt[$key]."</td>
						$showset
						$warning
					</tr>";
			} else {
				/* POS INVOICE ! */
				$sqls = array();
				for ($i = 1; $i <= 12; ++$i) {
					$sqls[] = "SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				}
				$sql = implode(" UNION ", $sqls);

// (1jun07) only checks the current prd ??????
//				db_conn(PRD_DB);
//				$sql = "SELECT invnum,invid,balance,odate FROM pinvoices
//						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");

				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'> -P- Invalid ord number $invids[$key].</li>";
				}

				$inv = pg_fetch_array($invRslt);
				$invid = "p".$inv['invid'];

				#handle warnings ...
				if (($paidamt[$invid] + $stock_setamt[$invid]) < sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying Less Than Total Amount.</li></td>";
				}elseif (($paidamt[$invid] + $stock_setamt[$invid]) > sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying More Than Total Amount Outstanding.</li></td>";
				}else {
					$warning = "";
				}

				if($doset) {
					if(!isset($stock_setamt[$invid]))
						$stock_setamt[$invid] = "";
					$showset = "<td>".CUR." ".sprint ($stock_setamt[$invid])."</td>";
				}else {
					$showset = "<td></td>";
				}

				$confirm .= "
					<input type='hidden' size='20' name='invids[$key]' value='$inv[invid]'>
					<input type='hidden' name='paidamt[$key]' size='7' value='".$paidamt[$key]."'>
					<input type='hidden' name='stock_setamt[$key]' value='$stock_setamt[$key]'>
					<input type='hidden' name='ptype[$key]' value='PcP'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td>".CUR." ".$paidamt[$key]."</td>
						$showset
						$warning
					</tr>";
			}
		}

		if(open()) {
			db_conn('cubit');

			$Sl = "SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri = db_exec($Sl) or errDie("Unable to get open items.");

			//$open_out=$out;
			$ox = "";

			$i = 0;

			while($od = pg_fetch_array($Ri)) {
				$oid = $od['id'];

				if(!(isset($open_amount[$oid])) || $open_amount[$oid] == 0) {
					continue;
				}

				$ox .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' size='20' name='open[$oid]' value='$oid'>$od[type]</td>
						<td>".CUR." $od[balance]</td>
						<td>$od[date]</td>
						<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
					</tr>";

				$i++;
			}
			$confirm .= "
				<tr><td colspan='2'><br></td></tr>
				<tr><td colspan='2'>
					<h3>Outstanding Transactions</h3></td>
				</tr>
				<tr>
					<th>Description</th>
					<th>Outstanding Amount</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>
				$ox";
		}
	}

	vsprint($out);
	vsprint($out1);
	vsprint($out2);
	vsprint($out3);
	vsprint($out4);
	vsprint($out5);
/*
	<tr>
		<td colspan='5' align='right'><input type='submit' name='batch' value='Add To Batch'></td>
	</tr>
*/
	$confirm .= "
		<input type='hidden' name='out1' value='$out1'>
		<input type='hidden' name='out2' value='$out2'>
		<input type='hidden' name='out3' value='$out3'>
		<input type='hidden' name='out4' value='$out4'>
		<input type='hidden' name='out5' value='$out5'>
		".TBL_BR."
		<tr>
			<td><input type='submit' name='back' value='&laquo; Correction'></td>
			<td align='right' colspan='4'><input type='submit' value='Write &raquo'></td>
		</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../core/trans-new.php", "Journal Transactions"),
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

	require_lib("validate");
	$v = new  validate ();
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($date, "date", 1, 14, "Invalid Date.");
	$v->isOk($out, "float", 1, 40, "Invalid out amount.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
	$v->isOk($setamt, "float", 1, 40, "Invalid Settlement Amount.");
	$v->isOk($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
	$v->isOk($cusid, "num", 1, 40, "Invalid customer number.");
	$v->isOk($out1, "float", 0, 40, "Invalid paid amount(current).");
	$v->isOk($out2, "float", 0, 40, "Invalid paid amount(30).");
	$v->isOk($out3, "float", 0, 40, "Invalid paid amount(60).");
	$v->isOk($out4, "float", 0, 40, "Invalid paid amount(90).");
	$v->isOk($out5, "float", 0, 40, "Invalid paid amount(120).");
	$v->isOk($overpay, "float", 1, 20, "Invalid Overpay Amount.");

	if (isset($invids)) {
		foreach($invids as $key => $value){
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No.");
			$v->isOk ($paidamt[$key], "float", 1, 40, "Invalid amount to be paid.");
			$v->isOk ($stock_setamt[$key], "float", 1, 40, "Invalid Settlement Discount Amount");
		}
	}

	if ($v->isError ()) {
		$confirm = $v->genErrors();
		return $confirm.confirm($_POST);
	}




	/* get bank account id of cash on hand account IF this entry is cash */
	if ((($bank_acc = getbankaccid($bankid)) === false) OR ($bankid == "0")) {
	//old function didnt check if cash is selected ... if(($bank_acc = getbankaccid($bankid)) === false) {
		$sql = "SELECT accid FROM core.accounts WHERE accname='Cash on Hand'";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) < 1) {
			if ($bankid == 0) {
				return "There is no 'Cash on Hand' account, there was one, but
					its not there now, you must have deleted it, if you want
					to use cash functionality please create a 'Cash on Hand' account.";
			} else {
				return "Invalid bank acc.";
			}
		}

		$bank_acc = pg_fetch_result($rslt, 0);
	}

	$cus = qryCustomer($cusid, "cusnum, deptid, cusname, surname");
	$dept = qryDepartment($cus["deptid"], "debtacc");
	$refnum = getrefnum();

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# date format
	$sdate = explode("-", $date);

	$_SESSION["global_day"] = $sdate[2];
	$_SESSION["global_month"] = $sdate[1];
	$_SESSION["global_year"] = $sdate[0];

//	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
	$sdate = "$date_year-$date_month-$date_day";
	$cheqnum = 0 + $cheqnum;
	$pay = "";
	$accdate = $sdate;
//	$accdate = "$date_year-$date_month-$date_day";

	/* Paid invoices */
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";
	$setamts = "";


	#get settlement accid
	$get_setacc = "SELECT accid FROM accounts WHERE accname = 'Debtors Settlement Discount'";
	$run_setacc = db_exec($get_setacc) or errDie ("Unable to get settlement account information");
	$setaccid = pg_fetch_result ($run_setacc,0,0);

	$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");

	$amt += $overpay;


	/* OPTION 3 : ALLOCATE TO EACH INVOICE (confirm) */
	if ($all == 2) {
		$sql = "UPDATE cubit.customers SET balance = (balance - '$amt'::numeric(16,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		if (isset($invids)) {
			foreach($invids as $key => $value) {
				$ii = $invids[$key];

				# some logic ...
				# because the customer account should be 0 when paid fully, we need
				# to also deduct the settlement amount ...
				$paidamt[$key] = $paidamt[$key] + $stock_setamt[$key];
				
				# with the amount added to the paid amount, we tract it using a new
				# seperate setamt db column


				if(!isset($itype[$key]) && (!isset($ptype[$key]))) {
					$sql = "SELECT prd,invnum,odate FROM cubit.invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$inv = pg_fetch_array($invRslt);

					// reduce invoice balance
					$sql = "
						UPDATE cubit.invoices
						SET balance = (balance - $paidamt[$key]::numeric(16,2))
						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "
						UPDATE cubit.open_stmnt
						SET balance = (balance - $paidamt[$key]::numeric(16,2))
						WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, date, type, div, allocation_date, docref, 
							allocation_balance
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', 
							'".(($paidamt[$key] - $stock_setamt[$key]) - (($paidamt[$key] - $stock_setamt[$key]) * 2))."', 
							'$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]', '$reference', 
							'".abs((($paidamt[$key] - $stock_setamt[$key]) - (($paidamt[$key] - $stock_setamt[$key]) * 2)))."'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);


					#record the settlement discount on the statement
					if($stock_setamt[$key] > 0){
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, amount, 
								date, type, 
								div, allocation_date, docref, allocation_balance
							) VALUES (
								'$cus[cusnum]', '$inv[invnum]', '".($stock_setamt[$key] - ($stock_setamt[$key] * 2))."', 
								'$sdate', 'Settlement Discount for Invoice No.$inv[invnum] Ref. $refnum', 
								'".USER_DIV."', '$inv[odate]', '$reference', '".abs($stock_setamt[$key] - ($stock_setamt[$key] * 2))."'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}

					#deduct setamt for records ...
					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key]-$stock_setamt[$key], "c");
					db_connect();

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					if($inv['prd'] == "0") {
						$inv['prd'] = PRD_DB;
					}
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
					$setamts .= "|$stock_setamt[$key]";
				} elseif(!isset($ptype[$key])) {
					$sql = "
						SELECT prd,invnum,descrip,age,odate 
						FROM cubit.nons_invoices 
						WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$inv = pg_fetch_array($invRslt);

					// reduce the invoice balance
					$sql = "
						UPDATE cubit.nons_invoices 
						SET balance = (balance - $paidamt[$key]::numeric(16,2)) 
						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "
						UPDATE cubit.open_stmnt 
						SET balance = (balance - $paidamt[$key]::numeric(16,2)) 
						WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				
					if (!isset($inv['odate']) OR strlen($inv['odate']) < 1){
						$inv['odate'] = $sdate;
					}
				
					// add payment to statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, 
							date, type, 
							div, allocation_date, docref, allocation_balance
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', 
							'".(($paidamt[$key] - $stock_setamt[$key]) - (($paidamt[$key] - $stock_setamt[$key]) * 2))."', 
							'$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', 
							'".USER_DIV."', '$inv[odate]', '$reference', '".abs(($paidamt[$key] - $stock_setamt[$key]) - (($paidamt[$key] - $stock_setamt[$key]) * 2))."'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					#record the settlement discount on the statement
					if($stock_setamt[$key] > 0){
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, amount, 
								date, type, 
								div, allocation_date, docref, allocation_balance
							) VALUES (
								'$cus[cusnum]', '$inv[invnum]', '".($stock_setamt[$key] - ($stock_setamt[$key] * 2))."', 
								'$sdate', 'Settlement Discount for Invoice No.$inv[invnum] Ref. $refnum', 
								'".USER_DIV."', '$inv[odate]', '$reference', '".abs($stock_setamt[$key] - ($stock_setamt[$key] * 2))."'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}

					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");
					db_connect();

					//recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$accdate);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|0";
					$rages .= "|$inv[age]";
					$invidsers .= " - $inv[invnum]";
					$setamts .= "|$stock_setamt[$key]";
				} else {
					/* pos invoices */
					$sqls = array();
					for ($i = 1; $i <= 12; ++$i) {
						$sqls[] = "
							SELECT '$i' AS prd,invid,invnum,odate 
							FROM \"$i\".pinvoices 
							WHERE invid='$invids[$key]' AND div='".USER_DIV."'";
					}
					$sql = implode(" UNION ", $sqls);

					$invRslt = db_exec($sql) or errDie ("Unable to retrieve invoice details from database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}

					$inv = pg_fetch_array($invRslt);

					// reduce the invoice balance
					$sql = "
						UPDATE \"$inv[prd]\".pinvoices 
						SET balance = (balance - $paidamt[$key]::numeric(16,2)) 
						WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "
						UPDATE cubit.open_stmnt 
						SET balance = (balance - $paidamt[$key]::numeric(16,2)) 
						WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					// add payment to statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, amount, date, 
							type, div, 
							allocation_date, docref, allocation_balance
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', '".(($paidamt[$key] - $stock_setamt[$key]) * -1)."', '$sdate', 
							'Payment for POS Invoice No. $inv[invnum]', '".USER_DIV."', 
							'$inv[odate]', '$reference', '".abs(($paidamt[$key] - $stock_setamt[$key]) * -1)."'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					#record the settlement discount on the statement
					if($stock_setamt[$key] > 0){
						$sql = "
							INSERT INTO cubit.stmnt (
								cusnum, invid, 
								amount, date, 
								type, 
								div, allocation_date, docref, allocation_balance
							) VALUES (
								'$cus[cusnum]', '$inv[invnum]', 
								'".($stock_setamt[$key] - ($stock_setamt[$key] * 2))."', '$sdate', 
								'Settlement Discount for Invoice No.$inv[invnum] Ref. $refnum', 
								'".USER_DIV."', '$inv[odate]', '$reference', '".abs($stock_setamt[$key] - ($stock_setamt[$key] * 2))."'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}

					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for POS Invoice No. $inv[invnum]", $paidamt[$key], "c");
					//recordCT($paidamt[$key], $cus['cusnum'],"0",$accdate);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
					$setamts .= "|$stock_setamt[$key]";
				}
			}
		}

		if (open()) {
			db_conn('cubit');

			$Sl = "SELECT * FROM cubit.open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri = db_exec($Sl) or errDie("Unable to get open items.");

			//$open_out=$out;
			$ox = "";

			$i = 0;

			while ($od = pg_fetch_array($Ri)) {
				$oid = $od['id'];

				if (!isset($open_amount[$oid]) || $open_amount[$oid] == 0) {
					continue;
				}

				$ox .= "
					<input type='hidden' size='20' name='open[$oid]' value='$oid'>
					<input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$od[type]</td>
						<td>".CUR." $od[balance]</td>
						<td>$od[date]</td>
						<td>".CUR." $open_amount[$oid]</td>
					</tr>";

				$sql = "
					UPDATE cubit.open_stmnt 
					SET balance = (balance - $open_amount[$oid] ::numeric(16,2)) 
					WHERE id = '$oid' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				// record the payment on the statement
				$sql = "
					INSERT INTO cubit.stmnt (
						cusnum, invid, amount, date, 
						type, div, allocation_date, docref, allocation_balance
					) VALUES (
						'$cus[cusnum]', '0', '".-$open_amount[$oid] ."', '$sdate', 
						'Payment received', '".USER_DIV."', '$accdate', '$reference', '".abs($open_amount[$oid])."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $bank_acc, $sdate, 0, "Payment received", $open_amount[$oid] , "c");
				recordCT($open_amount[$oid], $cus['cusnum'],0,$accdate);
			}

		}

		// record the payment record
		$cols = grp(
			m("bankid", $bankid),
			m("trantype", "deposit"),
			m("date", $sdate),
			m("name", "$cus[cusname] $cus[surname]"),
			m("descript", "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]"),
			m("cheqnum", $cheqnum),
			m("amount", $amt),
			m("banked", "no"),
			m("accinv", $dept["debtacc"]),
			m("cusnum", $cus["cusnum"]),
			m("rinvids", $rinvids),
			m("amounts", $amounts),
			m("invprds", $invprds),
			m("rages", $rages),
			m("reference", $reference),
			m("div", USER_DIV)
		);

		$dbobj = new dbUpdate("cashbook", "cubit", $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		$cashbook_id = pglib_lastid("cashbook","cashid");

		writetrans($bank_acc, $dept['debtacc'], $accdate, $refnum, $amt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");
	}

	/* start moving invoices */
	// move invoices that are fully paid
	$sql = "SELECT * FROM cubit.invoices WHERE balance='0' AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
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
	if($setamt > 0){
		db_conn('core');
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

			#process the vat amt ...
			writetrans($vatacc, $dept['debtacc'],  $accdate, $refnum, $setvatamt, "VAT Received on Settlement Discount (Ref.$refnum) for Customer : $cus[cusname] $cus[surname]");
			vatr($vd['id'],$accdate,"OUTPUT",$vd['code'],$refnum,"VAT for Settlement Discount (Ref.$refnum) for Customer : $cus[cusname] $cus[surname]",($setamt+$setvatamt)*(-1),$setvatamt*(-1));
		}else {
			#no vat for set amt ... do nothing
			$setvatamt = 0;
		}

		custledger($cus['cusnum'], $setaccid, $accdate, $refnum, "Settlement Discount (Ref.$refnum)", $setamt + $setvatamt, "c");
		writetrans($setaccid, $dept['debtacc'],  $accdate, $refnum, $setamt, "Settlement Discount (Ref.$refnum) For $cus[cusname] $cus[surname]");

		db_connect ();

		#record this paid settlement discount for reporting ...
		$settl_sql = "
			INSERT INTO settlement_cus (
				customer, amt, setamt, setvatamt, setvat, setvatcode, tdate, sdate, refnum
			) VALUES (
				'$cus[cusnum]', '$amt', '$setamt', '$setvatamt', '$setvat', '$setvatcode', '$accdate', 'now', '$refnum'
			)";
		$run_settl = db_exec($settl_sql) or errDie ("Unable to get debtor settlement information.");
	}

//	$overpay = sprint ($amt - array_sum($paidamt));
	if(!isset($overpay) OR ($overpay < 0))
		$overpay = 0.00;


	if ($overpay > 0) {
		recordCT($overpay, $cus['cusnum'],0,$accdate);

		$cols = grp(
			m("cusnum", $cus["cusnum"]),
			m("invid", 0),
			m("amount", -$overpay),
			m("date", $sdate),
			m("type", "Payment Received (Receipt ".pglib_lastid("cashbook", "cashid").")"),
			m("div", USER_DIV),
			m("allocation_date", $accdate), 
			m("docref", $reference)
		);

		$dbobj = new dbUpdate("stmnt", "cubit", $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		custledger($cus['cusnum'], $bank_acc, $sdate, "PAYMENT", "Payment received.", $overpay, "c");
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// status report
//	$write = "
//				<table ".TMPL_tblDflts." width='100%'>
//					<tr>
//						<th>Bank Receipt</th>
//					</tr>
//					<tr bgcolor='".bgcolorg()."'>
//						<td>Bank Receipt added to cash book.</td>
//					</tr>
//				</table>
//			";
//
//	$OUTPUT = "<center>
//        <table width='90%'>
//        <tr valign='top'>
//        	<td width='50%'>$write</td>
//	        <td align='center'>"
//				.mkQuickLinks(
//					ql("bank-pay-add.php", "Add Bank Payment"),
//					ql("bank-recpt-add.php", "Add Bank Receipt"),
//					ql("bank-recpt-inv.php", "Add Customer Payment"),
//					ql("cashbook-view.php", "View Cash Book")
//				)."
//			</td>
//		</tr>
//		</table>";
//	return $OUTPUT;

	if (isset($print_recpt) AND $print_recpt == "yes")
		$showreceipt = "printer ('bank/bank-recpt-inv-print.php?recid=$cashbook_id');";
	else 
		$showreceipt = "";


	return "
		<script>
			move ('../customers-view.php?offset=0&fval=&filter=surname&nozerobal=yes');
			$showreceipt
		</script>";

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
	$sql = "
		SELECT sum(balance) FROM cubit.invoices
		WHERE cusnum = '$cusnum' AND printed = 'y'
			AND odate >='".extlib_ago($ldays)."'
			AND odate <'".extlib_ago($days-30)."'
			AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "
		SELECT sum(balance) FROM cubit.custran
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
	$sql = "
		SELECT sum(balance) FROM cubit.invoices 
		WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "
		SELECT sum(balance) FROM cubit.custran 
		WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;

}



# records for CT
function recordCT($amount, $cusnum, $age, $date="", $changemon = false)
{

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

	$date_ins = "$date";

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
		$date_ins = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
		$extra1 = ",actual_date";
		$extra2 = ",'$date'";
	}else {
		$extra1 = "";
		$extra2 = "";
	}

	$sql = "
		INSERT INTO custran (
			cusnum, odate, balance, div, age $extra1
		) VALUES (
			'$cusnum', '$date_ins', '$amount', '".USER_DIV."', '$age' $extra2
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
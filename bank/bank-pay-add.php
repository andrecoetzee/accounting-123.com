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
require("../libs/ext.lib.php");

// Merge get vars and post vars
foreach ($_GET as $key => $value) {
	$_POST[$key] = $value;
}

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = add();
	}
} else {
        # Display default output
        $OUTPUT = add();
}

# get templete
require("../template.php");



# Insert details
function add()
{

	global $_POST;
	extract($_POST);

	# Accounts Drop down selections
	core_connect();

	if (isset($account) && strlen ($account) > 0){
		$accinv = $account;
	}

	$glacc = mkAccSelect ("accinv",$accinv);

	# Income accounts ($inc)
// 	$glacc = "<select name='accinv'>";
// 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
// 	$accRslt = db_exec($sql);
// 	$numrows = pg_numrows($accRslt);
// 	if(empty($numrows)){
// 		$glacc = "There are no Income accounts yet in Cubit.";
// 	}
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 		if((isset($accinv) && ($accinv == $acc['accid'])) ||
// 			(isset($account) && ($account == $acc['accid']))) {
// 			$sel = "selected";
// 		} else {
// 			$sel = "";
// 		}
// 		$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
// 	}
// 	$glacc .= "</select>";

	if(!isset($name)) {
		$bankid = 0;
		$name = "";
		$descript = "";
		$cheqnum = "";
		$amount = "";
		$reference = "";
	}

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$bank = "<select name='bankid'>";
	while($acc = pg_fetch_array($banks)){
		if($bankid == $acc['bankid']){
			$bank .= "<option value='$acc[bankid]' selected>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}else {
			$bank .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}
	}
	$bank .= "</select>";


	if(!isset($vatcode))
		$vatcode = "";

	if(!isset($chrgvat))
		$chrgvat = "";

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='vatcode'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vatcode == $vd['id']){
			$sel = "selected";
		}else {
			if(($vd['del'] == "Yes") AND (strlen($vatcode) < 1)) {
				$sel = "selected";
			} else {
				$sel = "";
			}
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes .= "</select>";

	if(!isset($date_day)) {

		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			#check if the global date is set .... else set it manually
			if (isset($_SESSION["global_day"]) AND strlen ($_SESSION["global_day"]) > 0) 
				$date_day = $_SESSION["global_day"];
			else 
				$date_day = date("d");
			if (isset($_SESSION["global_month"]) AND strlen ($_SESSION["global_month"]) > 0) 
				$date_month = $_SESSION["global_month"];
			else 
				$date_month = date("m");
			if (isset($_SESSION["global_year"]) AND strlen ($_SESSION["global_year"]) > 0) 
				$date_year = $_SESSION["global_year"];
			else 
				$date_year = date("Y");
		}

	}

	$cvat1 = "";
	$cvat2 = "";
	$cvat3 = "";
	if($chrgvat == "inc"){
		$cvat1 = "checked='yes'";
	}elseif ($chrgvat == "exc"){
		$cvat2 = "checked='yes'";
	}else {
		$cvat3 = "checked='yes'";
	}

	# layout
	$add = "
		<h3>New Bank Payment</h3>
		<table ".TMPL_tblDflts." width='80%'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td valign='center'>$bank</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)." DD-MM-YYYY</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Paid to</td>
				<td valign='center'><input size='20' name='name' value='$name'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Description</td>
				<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td valign='center'><input type='text' size='25' name='reference' value='$reference'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT </td>
				<td><input type='radio' name='chrgvat' value='inc' $cvat1>Inclusive &nbsp;&nbsp; <input type='radio' name='chrgvat' value='exc' $cvat2>Exclusive &nbsp;&nbsp; &nbsp;&nbsp; <input type='radio' name='chrgvat' value='nov' $cvat3>No VAT</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Code</td>
				<td>$Vatcodes</td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Select Contra Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td>$glacc</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
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
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}



# confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		header("Location: cashbook-entry.php");
		exit;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Account type (account involved).");
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
		//$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm.add($_POST);
	}

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get bank account name
	db_connect();

	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='".getPRDDB($date)."' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);

	# Get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv);
	$accnt = pg_fetch_array($accRslt);

	# Start rattling vat
//	$vatp = TAX_VAT;
	$totamt = $amount;

	$vatcode += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$vd = pg_fetch_array($Ri);
	$vatp = $vd['vat_amount'];

// 	if(pg_num_rows($Ri)>0) {
// 		$chrgvat="no";
// 	}

	if($chrgvat == "exc"){
		$vat = "<input type='text' name='vat' value='".sprint(($vatp/100) * $amount)."'>";
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		//$vat=sprint((sprint($amount*100/(100+$vatp)))*$vatp/100);
		$vat = "<input type='text' name='vat' value='".sprint($amount*$vatp/($vatp+100))."'>";

		//$vat = sprint(($amount/(100 + $vatp)) * $vatp);
	}else{
		$vat = "No VAT";
	}

	# Layout
	$confirm = "
		<center>
		<h3>New Bank Payment</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<table ".TMPL_tblDflts." width='60%'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<input type='hidden' name='accinv' value='$accinv'>
			<input type='hidden' name='vatcode' value='$vatcode'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_day' value='$date_day'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Balance</td>
				<td>".CUR." $bal[bal]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Paid to</td>
				<td valign='center'>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td valign='center'>$reference</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $totamt</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT </td>
				<td>$vat</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Ledger Account Paid To</td>
				<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' name='batch' value='Add to Batch &raquo'></td>
			</tr>
			<tr><td><br></td></tr>
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
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write
function write($_POST)
{

	# processes
	db_connect();

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return add($_POST);
	}

	if(!isset($vat) OR (strlen($vat) < 1))
		$vat = 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to/received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "string", 1, 255, "Invalid account number (account involved).");
	$v->isOk ($vat, "float", 0, 16, "Invalid vat amount.");

	$vatcode += 0;

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



	if (empty($descript)) {
		$descript = "Payment to $name";
	}

	# date format
	$date_arr = explode("-", $date);
	$date = $date_arr[2]."-".$date_arr[1]."-".$date_arr[0];

	#update/set the global date
	$_SESSION["global_day"] = $date_arr[0];
	$_SESSION["global_month"] = $date_arr[1];
	$_SESSION["global_year"] = $date_arr[2];

	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

	#refnum
	$refnum = getrefnum();

	# Start rattling vat
//	$vatp = TAX_VAT;
	$totamt = $amount;

// 	db_conn('cubit');
// 	$Sl="SELECT * FROM vatcodes WHERE id='$vatcode' AND zero='Yes'";
// 	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");
//
// 	$vd = pg_fetch_array($Ri);

// 	if(pg_num_rows($Ri)>0) {
// 		$chrgvat="no";
// 	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$vd = pg_fetch_array($Ri);
	$vatp = $vd['vat_amount'];

	if($chrgvat == "exc"){
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		$amount = sprint($totamt-$vat);
	}else{
		$vat = "No VAT";
	}

	/* -- Start Hooks -- */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	# Get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	/* -- End Hooks -- */

	$cheqnum = 0 + $cheqnum;
	$vat += 0;
	$vatcode += 0;

	pglib_transaction("BEGIN");

	if(isset($batch)) {

		db_connect();

		$sql = "
			INSERT INTO batch_cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				vat, chrgvat, banked, accinv, div, vatcode, reference, bt
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$totamt', 
				'$vat', '$chrgvat', 'no', '$accinv', '".USER_DIV."', '$vatcode', '$reference', 'payment'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Status report
		$write = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>Bank Payment</th>
				</tr>
				<tr class='datacell'>
					<td>Bank Payment added to batch.</td>
				</tr>
			</table>";
	} else {


		# Record the payment record
		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, reference, vatcode
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$totamt', '$vat', '$chrgvat', 'no', '$accinv', '".USER_DIV."', '$reference', '$vatcode'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);


		vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descript,-($amount+$vat),-$vat);

		# DT(account involved), CT(bank)
		writetrans($accinv, $banklnk['accnum'], $date, $refnum, $amount, $descript);

		if($vat <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $descript);
		}

		if(cc_TranTypeAcc($accinv, $banklnk['accnum']) != false){
			$cc_trantype = cc_TranTypeAcc($accinv, $banklnk['accnum']);
			$cc = "<script> CostCenter('$cc_trantype', 'Bank Transaction', '$date', '$descript', '$amount', '../'); </script>";
		}else{
			$cc = "";
		}

		$dif = sprint(($amount+$vat)-$totamt);

		if($dif > 0) {
			writetrans($banklnk['accnum'], $varacc, $date, $refnum, $dif, "Variance on bank payment Ref: $refnum");
		} elseif($dif < 0) {
			$dif = $dif * -1;
			writetrans($varacc, $banklnk['accnum'], $date, $refnum, $dif, "Variance on bank payment Ref: $refnum");
		}

		# Status report
		$write ="
			$cc
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>Bank Payment</th>
				</tr>
				<tr class='datacell'>
					<td>Bank Payment added to cash book.</td>
				</tr>
			</table>";

	}

	pglib_transaction("COMMIT");

	# Main table (layout with menu)
	$OUTPUT = "
		<center>
		<table width='90%'>
			<tr valign='top'>
				<td width='50%'>$write</td>
				<td align='center'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr>
							<th>Quick Links</th>
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
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>

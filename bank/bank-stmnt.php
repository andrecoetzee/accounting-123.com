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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "enter":
			$OUTPUT = enter($_POST);
			break;
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = enter($_POST);
			}else {
				$OUTPUT = confirm($_POST);
			}
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "":
			$OUTPUT = enter ($_POST);
			break;
		default:
			$OUTPUT = view();
	}
} else {
    # Display default output
    $OUTPUT = view();
}

# get template
require("../template.php");



function view()
{

	global $_POST;
	extract($_POST);

	if(!isset($number)) {
		$number = 1;
		$bankid = 0;
	}

	#banks dropdown
	db_connect();

	$bankaccs = "<select name='bankid'>";
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY bankname,branchname";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no Bank accounts in Cubit.</li>";
	}
	for($i = 0; $acc = pg_fetch_array($banks); $i++){
		if($acc['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$bankaccs .= "<option value='$acc[bankid]' $sel>[ $acc[bankname] ] $acc[accname] &nbsp($acc[acctype])</option>";
	}
	$bankaccs .= "</select>";



	// Layout
	$view = "
		<h3>Select Bank account</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='enter'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td>$bankaccs</td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Enter Data &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}



# Default View
function enter($_POST, $error="")
{

	# Get vars
	extract ($_POST);

	if (!isset ($number)) 
		$number = 1;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 20, "Invalid Bank ID.");
	$v->isOk ($number, "num", 1, 3, "Invalid number of entries.");

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
		$number++;
	}

	# Accounts Drop down
	core_connect();

	$glacc = mkAccSelect ("accinv[]",1);

// 	$glacc = "<select name='accinv[]'>";
// 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."'";
// 	$accRslt = db_exec($sql);
// 	if(pg_numrows($accRslt) < 1){
// 		return "<li> There are no accounts yet in Cubit.</li>";
// 	}
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 		$glacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
// 	}
// 	$glacc .= "</select>";

	# Get bank acc details
	$bankRslt = get("cubit", "*", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	$vatarr = array("nov"=>"No VAT", "inc"=>"Inclusive", "exc"=>"Exclusive");

	// Layout
	$enter = "
		<center>
		<h3>Type in Bank statement</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='number' value='$number'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='4'>$error</td>
			</tr>
			<tr class='".bg_class()."'>
				<th align='center' colspan='10'>Bank Account : <b>($bank[accnum]) $bank[accname]</b></th>
			</tr>
			<tr>
				<th>Date</th>
				<th>Paid to/Received from</th>
				<th>Transaction type</th>
				<th>Transaction Description</th>
				<th>Reference</th>
				<th>Cheque Number</th>
				<th>Amount</th>
				<th>VAT</th>
				<th>VAT Code</th>
				<th>Contra Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
			</tr>";

	for($i = 0; $i < $number; $i++){
		if (!isset($date[$i])) {
			$date[$i] = false;
		}

		if(strlen($date[$i]) < 1){
			$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
			if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
				$trans_date_value = getCSetting ("TRANSACTION_DATE");
				$date_arr = explode ("-", $trans_date_value);
				$o_year[$i] = $date_arr[0];
				$o_month[$i] = $date_arr[1];
				$o_day[$i] = $date_arr[2];
				$date[$i] = "$o_year[$i]-$o_month[$i]-$o_day[$i]-";
			}else {
				if(!isset($o_day[$i]) OR (strlen($o_day[$i]) < 1)){
					if (isset($_SESSION["global_day"]) AND strlen ($_SESSION["global_day"]) > 0) 
						$o_day[$i] = $_SESSION["global_day"];
					else 
						$o_day[$i] = date("d");
					if (isset($_SESSION["global_month"]) AND strlen ($_SESSION["global_month"]) > 0) 
						$o_month[$i] = $_SESSION["global_month"];
					else 
						$o_month[$i] = date("m");
					if (isset($_SESSION["global_year"]) AND strlen ($_SESSION["global_year"]) > 0) 
						$o_year[$i] = $_SESSION["global_year"];
					else 
						$o_year[$i] = date("Y");
				}
				$date[$i] = "$o_year[$i]-$o_month[$i]-$o_day[$i]-";
			}
		}

		explodeDate($date[$i], $o_year[$i], $o_month[$i], $o_day[$i]);

		if(!isset($to[$i])){
			$to[$i] = "";
			$trantype[$i] = "";
			$descript[$i] = "";
			$ref[$i] = "";
			$cheqnum[$i] = "";
			$amount[$i] = "";
			$chrgvat[$i] = "";
			$accinv[$i] = "";
		}

		switch(strtolower($trantype[$i])){
			case "deposit":
				$ch1 = "selected";
				$ch2 = "";
				break;
			case "withdrawal":
				$ch1 = "";
				$ch2 = "selected";
				break;
			default:
				$ch1 = "";
				$ch2 = "selected";
				break;
		}

		$vatsel = extlib_cpsel("chrgvat[]", $vatarr, $chrgvat[$i]);

		$glacc = mkAccSelect ("accinv[]",$accinv[$i]);

		# Accounts Drop down
// 		core_connect();
// 		$glacc = "<select name='accinv[]'>";
// 		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname";
// 		$accRslt = db_exec($sql);
// 		if(pg_numrows($accRslt) < 1){
// 			return "<li> There are no accounts yet in Cubit.</li>";
// 		}
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			$sel = "";
// 			if($acc['accid'] == $accinv[$i]) $sel = "selected";
// 			$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
// 		}
// 		$glacc .= "</select>";

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl) or errDie("cant get vat data.");

		$vats = "<select name='vatcode[]'>";
		while($vd = pg_fetch_array($Ri)) {
			$vats .= "<option value='$vd[id]'>$vd[code]</option>";
		}
		$vats .= "</select>";

		$enter .= "
			<tr class='".bg_class()."'>
				<td align='center' nowrap='t'>".mkDateSelectA("o", $i, $o_year[$i], $o_month[$i], $o_day[$i])."</td>
				<td align='center'><input type='text' name='to[]' value='$to[$i]'></td>
				<td align='center'>
					<select name='trantype[]'>
						<option value='Withdrawal' $ch2>Payment</option>
						<option value='Deposit' $ch1>Receipt</option>
					</select>
				</td>
				<td align='center'><input type='text' name='descript[]' value='$descript[$i]'></td>
				<td align='center'><input type='text' name='ref[]' value='$ref[$i]' size=7></td>
				<td align='center'><input type='text' name='cheqnum[]' value='$cheqnum[$i]' size='7'></td>
				<td align='center'>
					<table>
						<tr>
							<td>".CUR."</td>
							<td><input type='text' name='amount[]' value='$amount[$i]' size='8'></td>
						</tr>
					</table>
				</td>
				<td align='center'>$vatsel</td>
				<td align='center'>$vats</td>
				<td align='center'>$glacc</td>
			</tr>";
	}

	$total_amount = 0;

	$payment_list = array_keys ($trantype, "Deposit");
	foreach ($payment_list AS $each => $own){
		$total_amount += $amount[$own];
	}

	$receipt_list = array_keys ($trantype, "Withdrawal");
	foreach ($receipt_list AS $each => $own){
		$total_amount -= $amount[$own];
	}

	$enter .= "
		<tr class='".bg_class()."'>
			<td colspan='6' align='right'><b>Total:</b></td>
			<td align='right'>".CUR." ".sprint($total_amount)."</td>
			<td colspan='3'></td>
		</tr>";

	$enter .= "
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td><input type='submit' name='another' value='Add Another'></td>
					<td colspan='4' align='right'><input type='submit' value='Confirm &raquo'></td>
				</tr>
			</table>
			</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
		<a name='bottom'>
		$jump_bot";
	return $enter;

}



# Alt confirm
function confirm($_POST)
{

	extract($_POST);

	if (isset($back)) {
		return view();
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 20, "Invalid Bank ID.");

	foreach($amount as $key => $value){
	//	if($amount[$key] > 0){
			# check all vars
			$v->isOk ($to[$key], "string", 0, 255, "Invalid receipient/depositor.");
			$v->isOk ($trantype[$key], "string", 1, 20, "Invalid transaction type.");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid description.");
			$v->isOk ($ref[$key], "string", 0, 255, "Invalid reference <b>[$key]</b>.");
			$v->isOk ($cheqnum[$key], "num", 0, 20, "Invalid cheque number <b>[$key]</b>.");
			$v->isOk ($amount[$key], "float", 0, 8, "Invalid amount <b>[$key]</b>.");
			$v->isOk ($chrgvat[$key], "string", 1, 4, "Invalid VAT option.");
			$v->isOk ($accinv[$key], "num", 1, 20, "Invalid account involved <b>[$key]</b>.");

			if (empty($amount[$key])) {
				unset($amount[$key]);
			}

			# put date together and check
			$date[$key] = mkdate($o_year[$key], $o_month[$key], $o_day[$key]);
			$v->isOk ($date[$key], "date", 1, 1, "Invalid date <b>[$key]</b>.");
	//	}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return enter($_POST, $confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get bank acc details
	$bankRslt = get("cubit", "*", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	# Layout
	$confirm ="
		<center>
		<h3>Type in Bank Statement</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='number' value='$number'>
		<table ".TMPL_tblDflts.">
			<tr class='".bg_class()."'>
				<td align='center' colspan='4'>Bank Account : <b>($bank[accnum]) $bank[accname]</b></td>
			</tr>
			<tr>
				<th>Date</th>
				<th>Paid to/Received from</th>
				<th>Transaction type</th>
				<th>Transaction Description</th>
				<th>Reference</th>
				<th>Cheque Number</th>
				<th>Transaction Amount</th>
				<th>VAT</th>
				<th>VAT Code</th>
				<th>Contra Account</th>
			</tr>";

	# Display the trans
	$trans = "";
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			# get account name
			$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv[$key]);
			$acc = pg_fetch_array($accRslt);

			$vd = qryVatcode($vatcode[$key]);

			if ($vd["zero"] == "Yes") {
				$chrgvat[$key] = "no";
			}

			$vatp = $vd["vat_amount"];

			$totamt = $amount[$key];
			if($chrgvat[$key] == "exc"){
				$vat = sprint(($vatp/100) * $amount[$key]);
				$totamt += $vat;
				$vatin = CUR . " <input type='text' size='6' name='vat[$key]' value='$vat' />";
			} elseif($chrgvat[$key] == "inc"){
				$vat = sprint(($amount[$key]/(100 + $vatp)) * $vatp);
				$vatin = CUR . " <input type='text' size='6' name='vat[$key]' value='$vat' />";
			}else{
				$vat = "No VAT";
				$vatd="$vat";
				$vatin = "No VAT";
			}

			# alternate bgcolor
			$bgColor = bgcolorc($key);
			vsprint($totamt);

			$trans .= "
				<input type='hidden' size='2' name='date[$key]' value='$date[$key]' />
				<input type='hidden' name='to[$key]' value='$to[$key]' />
				<input type='hidden' name='trantype[$key]' value='$trantype[$key]' />
				<input type='hidden' name='descript[$key]' value='$descript[$key]' />
				<input type='hidden' name='ref[$key]' value='$ref[$key]' />
				<input type='hidden' name='cheqnum[$key]' value='$cheqnum[$key]' />
				<input type='hidden' name='amount[$key]' value='$amount[$key]' />
				<input type='hidden' name='chrgvat[$key]' value='$chrgvat[$key]' />
				<input type='hidden' name='vatcode[$key]' value='$vatcode[$key]' />
				<input type='hidden' name='accinv[$key]' value='$accinv[$key]' />
				<tr bgcolor='$bgColor'>
					<td align='center'>$date[$key]</td>
					<td align='center'>$to[$key]</td>
					<td align='center'>$trantype[$key]</td>
					<td align='center'>$descript[$key]</td>
					<td align='center'>$ref[$key]</td>
					<td align='center'>$cheqnum[$key]</td>
					<td align='center' nowrap='t'>".CUR." $totamt</td></td>
					<td align='center' nowrap='t'>$vatin</td>
					<td align='center'>$vd[code]</td>
					<td align='center'>$acc[topacc]/$acc[accnum] - $acc[accname]</td></td>
				</tr>";

		}
	}

	if(strlen($trans) < 5){
		$err = "<li class='err'> - Please enter full transaction details";
		return enter($_POST, $err);
	}

	$confirm .= "
			$trans
			<tr>
				<td></td>
				<td align='right'><input type='submit' name='batch' value='Add to Batch &raquo'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='4'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks();
	return $confirm;

}



function write($_POST)
{

	extract($_POST);

	if(isset($back)) {
		unset($_POST["back"]);
		return enter($_POST);
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 20, "Invalid Bank ID.");
	foreach($amount as $key => $value){
		# check all vars
		$v->isOk ($to[$key], "string", 1, 255, "Invalid receipient/depositor.");
		$v->isOk ($trantype[$key], "string", 1, 20, "Invalid transaction type.");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid description.");
		$v->isOk ($ref[$key], "string", 0, 255, "Invalid reference <b>[$key]</b>.");
		$v->isOk ($cheqnum[$key], "num", 0, 20, "Invalid cheque number <b>[$key]</b>.");
		$v->isOk ($amount[$key], "float", 1, 8, "Invalid amount <b>[$key]</b>.");
		$v->isOk ($accinv[$key], "num", 1, 20, "Invalid account involved <b>[$key]</b>.");
		$v->isOk ($date[$key], "date",1, 15, "Invalid date <b>[$key]</b>.");

		if (strtotime($date[$key]) >= strtotime($blocked_date_from) AND strtotime($date[$key]) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
			return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
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

	# Processes
	db_connect();

	# Begin Transaction
	pglib_transaction("BEGIN");

	# Some info
	$bankacc = getbankaccid($bankid);
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	foreach($amount as $key => $amt){
		$totamt = $amount[$key];
		if ($chrgvat[$key] == "exc") {
			$totamt += $vat[$key];
		} elseif($chrgvat[$key] == "inc"){
			$amount[$key] -= $vat[$key];
		}else{
			$vat[$key] = "No VAT";
		}

		if($cheqnum[$key] == '')
			$cheqnum[$key] = 0;

		if (!isset($batch)) {
			$refnum = getrefnum();
			if (strtolower($trantype[$key]) == 'deposit') {
				$vatacc = gethook("accnum", "salesacc", "name", "VAT", "1");
				writetrans($bankacc, $accinv[$key], $date[$key], $refnum, $amount[$key], $descript[$key]);
				if($vat[$key] <> 0){
					# DT(Bank), CT(VAT)
					$vat[$key] += 0;
					writetrans($bankacc, $vatacc, $date[$key], $refnum, $vat[$key], $descript[$key]." VAT");

					db_conn('cubit');
					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
					$Rt = db_exec($Sl) or errDie("Unable to get data.");

					$vd = pg_fetch_array($Rt);

					vatr($vatcode[$key],$date[$key],"OUTPUT",$vd['code'],$refnum,$descript[$key]." VAT",$totamt,$vat[$key]);
				}
			} else {
				$vatacc = gethook("accnum", "salesacc", "name", "VAT");
				writetrans($accinv[$key], $bankacc, $date[$key], $refnum, $amount[$key], $descript[$key]);
				if($vat[$key] <> 0){
					# DT(Bank), CT(VAT)
					$vat[$key] += 0;
					writetrans($vatacc, $bankacc, $date[$key], $refnum, $vat[$key], $descript[$key]." VAT");

					db_conn('cubit');
					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
					$Rt = db_exec($Sl) or errDie("Unable to get data.");

					$vd = pg_fetch_array($Rt);

					vatr($vatcode[$key],$date[$key],"INPUT",$vd['code'],$refnum,$descript[$key]." VAT",-$totamt,-$vat[$key]);
				}
			}

			$vat[$key] += 0;

			db_connect();
			$sql = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, descript, cheqnum, 
					amount, banked, accinv, div,chrgvat,vat,reference
				) VALUES (
					'$bankid', lower('$trantype[$key]'), '$date[$key]', '$to[$key]', '$descript[$key]', '$cheqnum[$key]', 
					'$totamt', 'no', '$accinv[$key]', '".USER_DIV."','$chrgvat[$key]', '$vat[$key]','$ref[$key]'
				)";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		} else {
			db_connect();
			$vat[$key] += 0;
			$sql = "
				INSERT INTO batch_cashbook (
					bankid, trantype, date, name, descript, cheqnum, 
					amount, banked, accinv, div, chrgvat, vat, vatcode, 
					reference
				) VALUES (
					'$bankid', lower('$trantype[$key]'), '$date[$key]', '$to[$key]', '$descript[$key]', '$cheqnum[$key]', 
					'$totamt', 'no', '$accinv[$key]', '".USER_DIV."','$chrgvat[$key]','$vat[$key]','$vatcode[$key]', 
					'$ref[$key]'
				)";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		}
	}

	# Get bank details
	$bankAccRslt = get("cubit","*","bankacct", "bankid", $bankid);
	$bankacc = pg_fetch_array($bankAccRslt);

	pglib_transaction("COMMIT");

	if (!isset($batch)) {
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Statement Recorded</th>
				</tr>
				<tr class='datacell'>
					<td>New Statement Details for account, <b>$bankacc[accname] ($bankacc[accnum])</b><br>held at <b>$bankacc[bankname]</b>, was successfully added to Cubit.</td>
				</tr>
			</table>";
	} else {
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Batch entries recorded</th>
				</tr>
				<tr class='datacell'>
					<td>New batch items for account, <b>$bankacc[accname] ($bankacc[accnum])</b><br>held at <b>$bankacc[bankname]</b>, was successfully added to Cubit.</td>
				</tr>
			</table>";
	}

	# Main table (layout with menu)
	$OUTPUT = "
		<center>
		<table width='90%'>
			<tr valign='top'>
				<td width='50%'>$write</td>
				<td align='center'>"
					.mkQuickLinks(
						ql("bank-pay-add.php", "Add Bank Payment"),
						ql("bank-recpt-add.php", "Add Bank Receipt"),
						ql("cashbook-view.php", "View Cash Book"),
						ql("batch-cashbook-view.php", "View Batch Cashbook")
					)."
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>

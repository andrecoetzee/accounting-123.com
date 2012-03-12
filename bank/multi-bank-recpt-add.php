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
		case "add":
			$OUTPUT = add($_POST);
			break;
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = add($_POST);
			}else {
				$OUTPUT = confirm($_POST);
			}
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = add($_POST);
	}
} else {
	# Display default output
	$OUTPUT = add($_POST);
}

# get templete
require("../template.php");




# Insert details
function add($_POST)
{

	# Get vars
	extract ($_POST);

	if (!isset ($lnum)) 
		$lnum = 1;

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($lnum, "num", 1, 30, "Invalid Number of ledger accounts.");

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
		$lnum++;
	}

	db_connect();

	# bank accounts to choose from
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
	$bankRs = db_exec($sql);
	if(pg_numrows($bankRs) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name='bankid'>";
	while($bank = pg_fetch_array($bankRs)){
		if (isset ($bankid) AND $bankid == $bank['bankid']){
			$banks .= "<option value='$bank[bankid]' selected>$bank[accname] - $bank[bankname] ($bank[acctype])</option>";
		}else {
			$banks .= "<option value='$bank[bankid]'>$bank[accname] - $bank[bankname] ($bank[acctype])</option>";
		}
	}
	$banks .= "</select>";


	# compose accounts list
	$accounts = "";
	for($i = 0; $i < $lnum; $i++){
		if(!isset($accinv[$i])){
			$accinv[$i] = 0;
			$accamt[$i] = 0;
			$vatcode[$i] = 0;
			$chrgvat[$i] = 'nov';
		}

		switch($chrgvat[$i]){
			case "nov":
				$chexc = "";
				$chinc = "";
				$chnov = "checked=yes";
				break;
			case "inc":
				$chexc = "";
				$chinc = "checked=yes";
				$chnov = "";
				break;
			case "exc":
				$chexc = "checked=yes";
				$chinc = "";
				$chnov = "";
				break;
			default:
				$chexc = "";
				$chinc = "";
				$chnov = "checked=yes";
				break;
		}

		$glacc = mkAccSelect ("accinv[]",$accinv[$i]);

		# Accounts Drop down selections
// 		core_connect();
// 
// 		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname";
// 		$accRslt = db_exec($sql);
// 		if(pg_numrows($accRslt) < 1){
// 			$glacc = "<li>There are no Income accounts yet in Cubit.</li>";
// 		}
// 		$glacc = "<select name='accinv[]' style='width: 167'>";
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			$sel = ($acc['accid'] == $accinv[$i]) ? "selected" : "";
// 			$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
// 		}
// 		$glacc .="</select>";


		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$Vatcodes = "<select name='vatcode[$i]'>";
		while($vd = pg_fetch_array($Ri)) {
			if($vatcode[$i] == $vd['id']){
				$sel = "selected";
			}else {
				if(($vd['del'] == "Yes") AND (strlen($vatcode[$i]) < 1)) {
					$sel = "selected";
				} else {
					$sel = "";
				}
			}
			$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
		}
		$Vatcodes .= "</select>";

		$accounts .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$glacc</td>
				<td align='center'>".CUR." <input type='text' size='8' name='accamt[]' value='$accamt[$i]'></td>
				<td>$Vatcodes</td>
				<td>
					<input type='radio' name='chrgvat[$i]' value='inc' $chinc>Inclusive &nbsp;&nbsp; 
					<input type='radio' name='chrgvat[$i]' value='exc' $chexc>Exclusive &nbsp;&nbsp; 
					<input type='radio' name='chrgvat[$i]' value='nov' $chnov>No VAT
				</td>
			</tr>";
	}

	$accounts .= "
		<tr bgcolor='".bgcolorg()."'>
			<td align='right'><b>Total:</b></td>
			<td align='right'>".CUR." ".sprint(array_sum ($accamt))."</td>
			<td colspan='2'></td>
		</tr>";

	# error control
	if(!isset($errata)){
		$errata = "";
	}

	if (!isset($name)){
		$name = "";
		$descript = "";
		$cheqnum = "";
		$reference = "";
	}

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

	// Layout
	$add = "
		<h3>New Bank Receipt</h3>
		$errata
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='lnum' value='$lnum'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td>$banks</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Received from</td>
				<td valign='center'><input size='20' name='name' value='$name'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'><textarea col='20' rows='5' name='descript'>$descript</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'><input size='25' name='reference' value='$reference'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr><td><br></td></tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Amount</th>
				<th>Vat Code</th>
				<th>VAT</th>
			</tr>
			$accounts
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
		<a name='bottom'>
		$jump_bot";
	return $add;

}




# confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return number();
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	//$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	foreach($accinv as $key => $vaccid){
		if ($accamt[$key] <= 0) 
			continue;
		$v->isOk ($vaccid, "num", 1, 20, "Invalid Account (account involved).");
		$v->isOk ($accamt[$key], "float", 1, 10, "Invalid amount.");
		$v->isOk ($chrgvat[$key], "string", 1, 4, "Invalid VAT option.");
	}
	if(strlen($date_year) <> 4){
		$v->isOk ($date_year, "num", 0, 0, "Invalid Date year.");
	}
	$date = mkdate($date_year, $date_month, $date_day);
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	$amount = sprint (array_sum($accamt));

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$_POST['errata'] = $confirm;
		return add($_POST);
	}



	# Get bank account name
	db_connect();

	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	$accounts = "";
	$gamt = 0;
	# Get all the Accounts involved
	foreach($accinv as $key => $vaccid){

		if ($accamt[$key] <= 0) 
			continue;

		$vatcode[$key] += 0;

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id = '$vatcode[$key]'";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];
		$totamt = $accamt[$key];

		if($chrgvat[$key] == "exc"){
			$vat = sprint(($vatp/100) * $accamt[$key]);
			$showvat = "<input type='text' size='5' name='getvat[]' value='$vat'>";
			$totamt += $vat;
		} elseif($chrgvat[$key] == "inc"){
			$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
			$vat = sprint ($vat);
			$showvat = "<input type='text' size='5' name='getvat[]' value='$vat'>";
		}else{
			$vat = "No VAT";
			$showvat = "<input type='hidden' name='getvat[]' value='0'>$vat";
		}

		$gamt = sprint($gamt + $totamt);

		# Get account name
		$accRslt = get("core", "accid,accname,topacc,accnum", "accounts", "accid", $vaccid);
		$accnt = pg_fetch_array($accRslt);
		$accounts .= "
			<input type='hidden' name='accinv[]' value='$vaccid'>
			<input type='hidden' name='accamt[]' value='$accamt[$key]'>
			<input type='hidden' name='chrgvat[]' value='$chrgvat[$key]'>
			<input type='hidden' name='vatcode[]' value='$vatcode[$key]'>
			<tr bgcolor='".bgcolorg()."'>
				<td>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
				<td>".CUR." $totamt</td>
				<td>".CUR." $showvat</td>
			</tr>";
	}

	$gamt = sprint($gamt);
	$amount = sprint($amount);

	$diff = sprint($amount - $gamt);

	if($diff > 0){
		$_POST['errata'] = "<li class='err'>ERROR : Total transaction amount is more than the amount allocated to accounts by ".CUR." $diff .</lI>";
		return add($_POST);
	}elseif($diff < 0){
		$diff = sprint($diff * (-1));
		$_POST['errata'] = "<li class='err'>ERROR : Total transaction amount is less than the amount allocated to accounts by ".CUR." $diff .</lI>";
		return add($_POST);
	}

	// Layout
	$confirm = "
		<center>
		<h3>New Bank Receipt</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='lnum' value='$lnum'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<tr><th>Field</th><th>Value</th></tr>
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
				<td valign='center'>$name</td>
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
				<td valign='center'>".CUR." $gamt</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th>Account</th>
				<th>Amount (Incl VAT)</th>
				<th>VAT</th>
			</tr>
			$accounts
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
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
    return $confirm;

}




function write($_POST)
{


	extract ($_POST);

	if(isset($back)) {
		unset($_POST["back"]);
		return add($_POST);
	}

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

	foreach($accinv as $key => $vaccid){
		$v->isOk ($vaccid, "num", 1, 20, "Invalid Account (account involved).");
		$v->isOk ($accamt[$key], "float", 1, 10, "Invalid amount.");
		$v->isOk ($chrgvat[$key], "string", 1, 4, "Invalid VAT option.");
	}
	
	if (empty($descript)) {
		$descript = "Payment Received from $name";
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



	db_connect();

	$vatacc = gethook("accnum", "salesacc", "name", "VAT", "VAT");

	# Get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) < 1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	/* -- End Hooks -- */

	$date_arr = explode ("-",$date);
	$_SESSION["global_day"] = $date_arr[2];
	$_SESSION["global_month"] = $date_arr[1];
	$_SESSION["global_year"] = $date_arr[0];

	# Refnum
	$refnum = getrefnum();

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$amounts = "";
	$accids = "";
	$vats = "";
	$chrgvats = "";
	$gamt = 0;
	$vatcodes = "";
	foreach($accinv as $key => $vaccid){
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]' AND zero='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		# Start rattling vat
		$novatamt = $accamt[$key];
		$totamt = $accamt[$key];
		$vat = $getvat[$key];
		if($chrgvat[$key] == "exc"){
		//	$vat = sprint(($vatp/100) * $accamt[$key]);
			$totamt += $vat;
		} elseif($chrgvat[$key] == "inc"){
		//	$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
			$amount -= $vat;
			$novatamt -= $vat;
		}else{
		//	$vat = 0;
		}

		# Start Rattling vat
// 		$vatp = TAX_VAT;
// 		$totamt = $accamt[$key];
// 		if($chrgvat[$key] == "exc"){
// 			$vat = sprint(($vatp/100) * $accamt[$key]);
// 			$totamt += $vat;
// 		} elseif($chrgvat[$key] == "inc"){
// 			$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
// 			$accamt[$key] -= $vat;
// 		}else{
// 			$vat = 0;
// 		}

		$amounts .= "|$totamt";
		$accids .= "|$vaccid";
		$vats .= "|$vat";
		$chrgvats .= "|$chrgvat[$key]";
		$vatcodes .= "|$vatcode[$key]";

		# Date format
		//$date = explode("-", $date);
		//$date = $date[2]."-".$date[1]."-".$date[0];
		
		if(!isset($batch)) {
			#record entry for vat report
			vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,$descript,$totamt,$vat);

			#  DT(bank), CT(account involved)
			writetrans($banklnk['accnum'], $vaccid, $date, $refnum, $novatamt, $descript);

			if($getvat[$key] <> 0){
				# write journal entry ... DT(Bank), CT(VAT)
				writetrans($banklnk['accnum'], $vatacc, $date, $refnum, $vat, $descript);
			}
		}

		$gamt += $totamt;
	}

	$cheqnum = 0 + $cheqnum;

	# Record the Receipt record
	if (!isset($batch)) {
		db_connect();
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts,  
				chrgvats, vats, reference, vatcodes, div
			) VALUES (
				'$bankid', 'deposit', '$date', '$name', '$descript', '$cheqnum', '$gamt', 'no', '$accids', '$amounts', 
				'$chrgvats', '$vats', '$reference', '$vatcodes', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank Receipt to database.",SELF);
	} else {
		db_connect();
		$sql = "
			INSERT INTO batch_cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, 
				chrgvats, vats, reference, vatcodes, div
			) VALUES (
				'$bankid', 'deposit', '$date', '$name', '$descript', '$cheqnum', '$gamt', 'no', '$accids', '$amounts', 
				'$chrgvats', '$vats', '$reference', '$vatcodes', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank Receipt to database.",SELF);
	}

	# Status report
	$write ="
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Bank Receipt</th>
			</tr>
			<tr class='datacell'>
				<td>Bank Receipt added to cash book.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='cashbook-view.php'>View Cash Book</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
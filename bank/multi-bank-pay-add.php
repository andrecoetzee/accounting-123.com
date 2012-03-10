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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "add":
			$OUTPUT = add($HTTP_POST_VARS);
			break;
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = add($HTTP_POST_VARS);
			}else {
				$OUTPUT = confirm($HTTP_POST_VARS);
			}
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = add($HTTP_POST_VARS);
	}
} else {
	# Display default output
	$OUTPUT = add();
}

# Get templete
require("../template.php");



# Insert details
function add($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

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

	if(!isset($bankid)) {
		$bankid = 0;
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
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY bankname,branchname";
	$bankRs = db_exec($sql);
	if(pg_numrows($bankRs) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name='bankid'>";
	while($bank = pg_fetch_array($bankRs)){
		if($bank['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$banks .= "<option value='$bank[bankid]' $sel>$bank[accname] - $bank[bankname] ($bank[acctype])</option>";
	}
	$banks .= "</select>";

	# compose accounts list
	$accounts = "";
	for($i = 0; $i < $lnum; $i++){
		if(!isset($accinv[$i])){
			$accinv[$i] = 0;
			$accamt[$i] = 0;
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
// 		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
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
// 		$glacc .= "</select>";

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl) or errDie("cant get vat data.");

		$vats = "
			<select name='vatcode[]'>
				<option value='0'>Select VAT Code</option>";
		while($vd = pg_fetch_array($Ri)) {
				if($vd['del'] == "Yes") {
					$sel = "selected";
				} else {
					$sel = "";
				}
			$vats .= "<option value='$vd[id]' $sel>$vd[code]</option>";
		}
		$vats .= "</select>";

		$accamt[$i] = sprint ($accamt[$i]);
		
		$accounts .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$glacc</td>
				<td align='center'>".CUR." <input type='text' size='8' name='accamt[]' value='$accamt[$i]'></td>
				<td>
					<input type='radio' name='chrgvat[$i]' value='inc' $chinc>Inclusive &nbsp;&nbsp;
					<input type='radio' name='chrgvat[$i]' value='exc' $chexc>Exclusive &nbsp;&nbsp;
					<input type='radio' name='chrgvat[$i]' value='nov' $chnov>No VAT
				</td>
				<td>$vats</td>
			</tr>";
	}

	$accounts .= "
		<tr bgcolor='".bgcolorg()."'>
			<td align='right'><b>Total:</b></td>
			<td>".CUR." ".sprint(array_sum ($accamt))."</td>
			<td colspan='2'></td>
		</tr>";

	if(!isset($errata)) {
		$errata="";
	}

	# error control
	if(!isset($name)){
		$errata = "";
		$name = "";
		$descript = "";
		$cheqnum = "";
		$reference = "";
	}

	if (!isset($date_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			if (isset($_SESSION["global_day"]) AND strlen($_SESSION["global_day"]) > 0) 
				$date_day = $_SESSION["global_day"];
			else 
				$date_day = date("d");
			if (isset($_SESSION["global_month"]) AND strlen($_SESSION["global_month"]) > 0) 
				$date_month = $_SESSION["global_month"];
			else 
				$date_month = date("m");
			if (isset($_SESSION["global_year"]) AND strlen($_SESSION["global_year"]) > 0) 
				$date_year = $_SESSION["global_year"];
			else 
				$date_year = date("Y");
		}
	}

	// Layout
	$add = "
		<h3>New Bank Payment</h3>
		$errata
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='lnum' value='$lnum' />
			".TBL_BR."
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
				<td>Paid to</td>
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
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Amount</th>
				<th>VAT</th>
				<th>VAT Code</th>
			</tr>
			$accounts
			".TBL_BR."
			<tr>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		<a name='bottom'>
		$jump_bot
	".mkQuickLinks();
	return $add;

}


# confirm
function confirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to/received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	//$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	foreach($accinv as $key => $vaccid){
		$accamt[$key]+=0;
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

	$amount = sprint (array_sum($accamt));

	$date_year += 0;
	$date_mon += 0;
	$date_day += 0;

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
		$HTTP_POST_VARS['errata'] = $confirm."</li>";
		return add($HTTP_POST_VARS);
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

		# Start rattling vat
	//	$vatp = TAX_VAT;

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

// 		if(pg_num_rows($Ri)>0) {
// 			$chrgvat[$key]="novat";
// 		}

		$tchrgvat[$key] = $chrgvat[$key] ;

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		$totamt = $accamt[$key];
		if($chrgvat[$key] == "exc"){
			$vat = sprint(($vatp/100) * $accamt[$key]);
			$vat = sprint ($vat);
			$showvat = "<input type='text' size='10' name='getvat[$key]' value='$vat'>";
			$totamt += $vat;
		} elseif($chrgvat[$key] == "inc"){
			$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
			$vat = sprint ($vat);
			$showvat = "<input type='text' size='10' name='getvat[$key]' value='$vat'>";
		}else{
			$vat = "No VAT";
			$showvat = "<input type='hidden' name='getvat[$key]' value='0'>$vat";
		}

		$chrgvat[$key] = $tchrgvat[$key] ;

		$gamt += $totamt;

		# Get account name
		$accRslt = get("core", "accid,accname,topacc,accnum", "accounts", "accid", $vaccid);
		$accnt = pg_fetch_array($accRslt);

		$accounts .= "
			<input type='hidden' name='accinv[]' value='$vaccid'>
			<input type='hidden' name='vatcode[]' value='$vatcode[$key]'>
			<input type='hidden' name='accamt[]' value='$accamt[$key]'>
			<input type='hidden' name='chrgvat[]' value='$chrgvat[$key]'>
			<tr bgcolor='".bgcolorg()."'>
				<td>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
				<td>".CUR." ".sprint($totamt)."</td>
				<td>$showvat</td>
			</tr>";
	}
	$gamt = sprint($gamt);
	$amount = sprint($amount);

	$diff = sprint($amount - $gamt);

	if($diff > 0){
		$HTTP_POST_VARS['errata'] = "<li class='err'>ERROR : Total transaction amount is more than the amount allocated to accounts by ".CUR." $diff .</li>";
		return add($HTTP_POST_VARS);
	}elseif($diff < 0){
		$diff = sprint($diff * (-1));
		$HTTP_POST_VARS['errata'] = "<li class='err'>ERROR : Total transaction amount is less than the amount allocated to accounts by ".CUR." $diff .</li>";
		return add($HTTP_POST_VARS);
	}

	// Layout
	$confirm = "
			<center>
			<h3>New Bank Payment</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<form action='".SELF."' method='POST'>
			<table ".TMPL_tblDflts.">
				<input type='hidden' name='key' value='write' />
				<input type='hidden' name='bankid' value='$bankid' />
				<input type='hidden' name='date' value='$date' />
				<input type='hidden' name='name' value='$name' />
				<input type='hidden' name='descript' value='$descript' />
				<input type='hidden' name='reference' value='$reference' />
				<input type='hidden' name='cheqnum' value='$cheqnum' />
				<input type='hidden' name='amount' value='$amount' />
				<input type='hidden' name='lnum' value='$lnum' />
				<input type='hidden' name='date_day' value='$date_day' />
				<input type='hidden' name='date_month' value='$date_month' />
				<input type='hidden' name='date_year' value='$date_year' />
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
					<td>Paid to/Received from</td>
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
			".TBL_BR."
			<tr>
				<th>Account</th>
				<th>Amount</th>
				<th>VAT</th>
			</tr>
			$accounts
			".TBL_BR."
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type='submit' name='batch' value='Add to Batch &raquo'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='2'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>"
	.mkQuickLinks();

	return $confirm;
}

# Write
function write($HTTP_POST_VARS)
{

	# Processes
	db_connect();

	extract($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		return add($HTTP_POST_VARS);
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


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	if (empty($descript)) {
		$descript = "Payment to $name";
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

	$date_arr = explode ("-",$date);

	$_SESSION["global_day"] = $date_arr[2];
	$_SESSION["global_month"] = $date_arr[1];
	$_SESSION["global_year"] = $date_arr[0];

	# Refnum
	$refnum = getrefnum();

	$amounts = "";
	$accids = "";
	$vats = "";
	$chrgvats = "";
	$vatcodes = "";
	$gamt = 0;
	pglib_transaction("BEGIN");
	foreach($accinv as $key => $vaccid){

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

// 		if(pg_num_rows($Ri)>0) {
// 			$chrgvat[$key]="novat";
// 		}

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		# Start Rattling vat
//		$vatp = TAX_VAT;
		$vat = $getvat[$key];
		$totamt = $accamt[$key];
		if($chrgvat[$key] == "exc"){
		//	$vat = sprint(($vatp/100) * $accamt[$key]);
		//	$totamt += $vat;
			$totamt += $getvat[$key];
		
		} elseif($chrgvat[$key] == "inc"){
		//	$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
		//	$accamt[$key] -= $vat;
			$accamt[$key] -= $getvat[$key];
		}else{
			//$vat = 0;			
		}

		$amounts .= "|$totamt";
		$vatcodes .= "|$vatcode[$key]";
		$accids .= "|$vaccid";
		$vats .= "|$vat";
		$chrgvats .= "|$chrgvat[$key]";
		
		if(!isset($batch)) {
			vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descript,-$totamt,-$vat);

			# DT(account involved), CT(bank)
			writetrans($vaccid, $banklnk['accnum'], $date, $refnum, $accamt[$key], $descript);

			if($vat <> 0){
				# DT(VAT), CT(Bank)
				writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $descript);
			}
		}

		$gamt = sprint($gamt + $totamt);
	}

	$cheqnum = 0 + $cheqnum;

	if (!isset($batch)) {
		# Record the payment record
		db_connect();
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, chrgvats, vats, reference, div, vatcodes) VALUES ('$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$gamt', 'no', '$accids', '$amounts', '$chrgvats', '$vats', '$reference', '".USER_DIV."', '$vatcodes')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	} else {
		db_connect();
		$sql = "INSERT INTO batch_cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, chrgvats, vats, div, reference, vatcodes) VALUES ('$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$gamt', 'no', '$accids', '$amounts', '$chrgvats', '$vats', '".USER_DIV."', '$reference', '$vatcodes')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}

	pglib_transaction("COMMIT");

	# Status report
	$write ="
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Bank Payment</th>
				</tr>
				<tr class='datacell'>
					<td>Bank Payment added to cash book.</td>
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

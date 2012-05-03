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
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = details($_POST);
			}else {
				$OUTPUT = confirm($_POST);
			}
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "details":
			$OUTPUT = details($_POST);
			break;
		default:
			$OUTPUT = slctacc($_POST);
	}
} else {
	# Display default output
	$OUTPUT = slctacc($_POST);
}

# get templete
require("template.php");




# Select Accounts
function slctacc($_POST)
{

	extract($_POST);

	if(!isset($date_year)) {
		$accid = 0;
		$tran = "dt";
		$numcont = 1;
	}

	core_connect();

	$accs = mkAccSelect ("accid", $accid);

	if($tran == "dt") {
		$c1 = "checked=yes";
		$c2 = "";
	} else {
		$c1 = "";
		$c2 = "checked=yes";
	}

	if (!isset ($date_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}
	}

	// Layout
	$slctacc = "
		<center>
		<h3> Journal transaction </h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$accs <input type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Type </td>
				<td><input type='radio' name='tran' value='dt' $c1>Debit &nbsp;&nbsp; <input type='radio' name='tran' value='ct' $c2>Credit</td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctacc;

}

# Enter Details of Transaction
function details($_POST)
{

	# Get vars
	extract ($_POST);

	if (!isset ($numcont)) 
		$numcont = 1;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($tran, "string", 1, 3, "Invalid type of transaction.");
	$v->isOk ($numcont, "num", 1, 10, "Invalid number of contra accounts.");


	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.slctacc($_POST);
	}

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# get account
	$accRs = get("core","*","accounts","accid",$accid);
	$macc  = pg_fetch_array($accRs);

	$temprefnum = getrefnum();
	/*refnum*/

	$jump_bot = "";
	if (isset ($another)) {
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
		$numcont++;
	}

	// Deatils
	$details ="
		<p>
		<h3> Journal transactions details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='tran' value='$tran'>
			<input type='hidden' name='numcont' value='$numcont'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes&set_key=details','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Credit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes&set_key=details','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";



	for($i = 0; $i != $numcont; $i++){

		if(isset($amount[$i])) {
			$ta = $amount[$i];
		} else {
			$ta = "";
		}

		if(isset($descript[$i])) {
			$da = $descript[$i];
		} else {
			$da = "";
		}

		if(!isset($caccid[$i])) {
			$tc = 0;
		} else {
			$tc = $caccid[$i];
		}

		if(!isset($each_day[$i])){
			$each_day[$i] = $date_day;
			$each_month[$i] = $date_month;
			$each_year[$i] = $date_year;
		}

		if (!isset ($refnum[$i])){
			$refnum[$i] = $temprefnum;
		}

		# details
		$details .= "
			<tr bgcolor=".bgcolorg().">
				<td>".mkDateSelecta("each",$i,$each_year[$i],$each_month[$i],$each_day[$i])."</td>
				<td align='center'><input type='text' size='5' name='refnum[]' value='$refnum[$i]'></td>";

		core_connect();
		$sql = "SELECT * FROM accounts WHERE accid != '$accid' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}

		$accs = mkAccSelect ("caccid[]",$tc);

// 		$accs = "<select name='caccid[]'>";
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			if($tc == $acc['accid']) {
// 				$sel = "selected";
// 			} else {
// 				$sel = "";
// 			}
// 			$accs .= "<option value='$acc[accid]' $sel>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 		}
// 		$accs .= "</select>";

		if($tran == 'dt'){
			$accts  = "<td>$macc[topacc]/$macc[accnum] - $macc[accname]</td><td>$accs</td>";
		}else{
			$accts  = "<td>$accs</td><td>$macc[topacc]/$macc[accnum] - $macc[accname]</td>";
		}

		$details .= "
				$accts
				<td><input type='text' size='7' name='amount[]' value='$ta'></td>
				<td><input type='text' size='20' name='descript[]' value='$da'></td>
			</tr>";
	}

	$details .= "
		<tr class='".bg_class()."'>
			<td colspan='4' align='right'><b>Total:</b></td>
			<td>".CUR." ".sprint(array_sum ($amount))."</td>
			<td></td>
		</tr>";

	$details .= "
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center' colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>
		<a name='bottom'>
		$jump_bot
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}



# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return slctacc($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($tran, "string", 1, 3, "Invalid type of transaction.");
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			$v->isOk ($caccid[$key], "num", 1, 50, "Invalid Contra account.[$key]");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
			$date[$key] = $each_day[$key]."-".$each_month[$key]."-".$each_year[$key];
			if(!checkdate($each_month[$key], $each_day[$key], $each_year[$key])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
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
		return $confirm.details($_POST);
	}

	# get account
	$accRs = get("core","*","accounts","accid",$accid);
	$macc  = pg_fetch_array($accRs);

	// Layout
	$confirm = "
		<center>
		<h3>Confirm transactions</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='tran' value='$tran'>
			<input type='hidden' name='numcont' value='$numcont'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
		<table ".TMPL_tblDflts." width='700'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	$trans = "";
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			# get account to be debited
			$caccRs = get("core","*","accounts","accid",$caccid[$key]);
			if(pg_numrows($caccRs) < 1){
					return "<li> Contra Account does not exist.</li>";
			}
			$cacc = pg_fetch_array($caccRs);

			# how to view
			if($tran == 'dt'){
				$accts  = "
					<td>$macc[topacc]/$macc[accnum] - $macc[accname]</td>
					<td><input type='hidden' name='caccid[]' value='$cacc[accid]'>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>";
			}else{
				$accts  = "
					<td><input type='hidden' name='caccid[]' value='$cacc[accid]'>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
					<td>$macc[topacc]/$macc[accnum] - $macc[accname]</td>";
			}

			# Transactions
			$trans .= "
				<input type='hidden' name='each_day[]' value='$each_day[$key]'>
				<input type='hidden' name='each_month[]' value='$each_month[$key]'>
				<input type='hidden' name='each_year[]' value='$each_year[$key]'>
				<tr bgcolor=".bgcolorg().">
					<td><input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]</td>
					<td><input type='hidden' size='10' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
					$accts
					<td><input type='hidden' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
					<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
				</tr>";
		}
	}

	if(strlen($trans) < 5){
		return "<li class='err'> - Please enter full transaction details</li>".details($_POST);
	}

	$confirm .= "
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='2'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<br>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}




# Write
function write($_POST)
{

	# print "<pre>"; var_dump($_POST);exit;
	# Get vars
	extract ($_POST);

	if(isset($back)) {
		unset($_POST["back"]);
		return details($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($tran, "string", 1, 3, "Invalid type of transaction.");
	foreach($amount as $key => $value){
		if ($value > 0){
			$v->isOk ($caccid[$key], "num", 1, 50, "Invalid Contra account.");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.");
			$v->isOk ($date[$key], "date", 1, 14, "Invalid Date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	# get account
	$accRs = get("core","*","accounts","accid",$accid);
	$macc  = pg_fetch_array($accRs);

	# them transactions
	foreach($amount as $key => $value){
		if ($value > 0){
			# how to write
			if($tran == 'dt'){
				# write transaction
				writetrans($accid, $caccid[$key], $date[$key], $refnum[$key], $amount[$key], $descript[$key]);
			}else{
				# write transaction
				writetrans($caccid[$key], $accid, $date[$key], $refnum[$key], $amount[$key], $descript[$key]);
			}
		}
	}

	// Start layout
	$write ="
		<center>
		<h3>Journal transactions have been recorded</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	foreach($amount as $key => $value){

		if ($amount <= 0) 
			continue;

		# Accounts details
		$caccRs = get("core","*","accounts","accid",$caccid[$key]);
		$cacc = pg_fetch_array($caccRs);
		if(pg_numrows($caccRs) < 1){
			return "<li>There are No accounts in Cubit.$caccid[$key]</li>";
		}

		# how to view
		if($tran == 'dt'){
			$accts  = "<td>$macc[topacc]/$macc[accnum] - $macc[accname]</td><td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>";
		}else{
			$accts  = "<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td><td>$macc[topacc]/$macc[accnum] - $macc[accname]</td>";
		}

		$write .= "
			<tr bgcolor=".bgcolorg().">
				<td>$date[$key]</td>
				<td>$refnum[$key]</td>
				$accts
				<td>".CUR." $amount[$key]</td>
				<td>$descript[$key]</td>
			</tr>";
	}

	$write .= "
		</table>
		<br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>

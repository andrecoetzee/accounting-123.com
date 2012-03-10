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
			$OUTPUT = add($HTTP_GET_VARS);
			break;

		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = add($HTTP_GET_VARS);
	}
} else {
	# Display default output
	$OUTPUT = add($HTTP_GET_VARS);
}

# Get templete
require("../template.php");


# Insert details
function add($HTTP_GET_VARS)
{

	//global $HTTP_GET_VARS;

	# Get vars
	extract ($HTTP_GET_VARS);

	if(!isset($id) OR (strlen($id) < 1)){
		return "Invalid use of module";
	}

	$id += 0;

	db_connect ();

	#get the amount and number of entries
	$get_info = "SELECT * FROM batch_cashbook WHERE cashid = '$id' LIMIT 1";
	$run_info = db_exec($get_info) or errDie ("Unable to get batch information");
	if(pg_numrows($run_info) < 1){
		return "Invalid use of module";
	}

	$arr = pg_fetch_array($run_info);

	$amount = $arr['amount'];

	#get amount of accounts ..
	$accs = explode("|",$arr['amounts']);

	$newaccs = array();
	foreach($accs as $temp){
		if(strlen($temp) > 0)
			$newaccs[] = $temp;
	}

	$lnum = sizeof($newaccs);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($lnum, "num", 1, 30, "Invalid Number of ledger accounts.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

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

	if(!isset($arr['bankid']) OR (strlen($arr['bankid']) < 1)){
		$bankid = 0;
	}else {
		$bankid = $arr['bankid'];
	}



	db_connect();
	# bank accounts to choose from
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY bankname,branchname";
	$bankRs = db_exec($sql);
	if(pg_numrows($bankRs) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.
		<p><input type='button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name=bankid>";
	while($bank = pg_fetch_array($bankRs)){
		if($bank['bankid']==$bankid) {
			$sel="selected";
		} else {
			$sel="";
		}
		$banks .= "<option value=$bank[bankid] $sel>$bank[accname] - $bank[bankname] ($bank[acctype])</option>";
	}
	$banks .= "</select>";


############################[ Set Some Vars ]###########################
	if(strlen($arr['date']) < 1){
		$date = date("Y-m-d");
	}else {
		$date = $arr['date'];
	}
	$db_date = $date;
	$date_arr = explode("-",$db_date);
	$date_year = $date_arr[0];
	$date_month = $date_arr[1];
	$date_day = $date_arr[2];

	if(!isset($arr['name']) OR (strlen($arr['name']) < 1)){
		$name = "";
	}else {
		$name = $arr['name'];
	}

	if(!isset($arr['descript']) OR (strlen($arr['descript']) < 1)){
		$descript = "";
	}else {
		$descript = $arr['descript'];
	}

	if(!isset($arr['cheqnum']) OR (strlen($arr['cheqnum']) < 1)){
		$cheqnum = "";
	}else {
		$cheqnum = $arr['cheqnum'];
	}
	
	if(!isset($arr['reference']) OR (strlen($arr['reference']) < 1)){
		$reference = "";
	}else {
		$reference = $arr['reference'];
	}
#########################################################################



######################[ Clear Vars ]########################
	$accs_arr = explode("|",$arr['accids']);
	$new_accs_arr = array();
	foreach($accs_arr as $temp){
		if(strlen($temp) > 0)
			$new_accs_arr[] = $temp;
	}

	$amounts_arr = explode("|",$arr['amounts']);
	$new_amounts_arr = array();
	foreach($amounts_arr as $temp){
		if(strlen($temp) > 0)
			$new_amounts_arr[] = $temp;
	}

	$vatcodes_arr = explode("|",$arr['vatcodes']);
	$new_vatcodes_arr = array();
	foreach($vatcodes_arr as $temp){
		if(strlen($temp) > 0)
			$new_vatcodes_arr[] = $temp;
	}

	$chrgvats_arr = explode("|",$arr['chrgvats']);
	$new_chrgvats_arr = array();
	foreach($chrgvats_arr as $temp){
		if(strlen($temp) > 0)
			$new_chrgvats_arr[] = $temp;
	}
###########################################################

	# compose accounts list
	$accounts = "";
	for($i = 0; $i < $lnum; $i++){

			switch($new_chrgvats_arr[$i]){
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

			# Accounts Drop down selections
			core_connect();
			$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				$glacc = "<li>There are no Income accounts yet in Cubit.";
			}
			$glacc = "<select name='accinv[]' style='width: 167'>";
			while($acc = pg_fetch_array($accRslt)){
				# Check Disable
				if(isDisabled($acc['accid']))
					continue;
				$sel = ($acc['accid'] == $new_accs_arr[$i]) ? "selected" : "";
				$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
			}
			$glacc .="</select>";


			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("cant get vat data.");

			$vats="<select name=vatcode[]>
			<option value='0'>Select VAT Code</option>";
// 			var_dump($new_vat_arr);
			while($vd=pg_fetch_array($Ri)) {
					if($vd['id'] == $new_vatcodes_arr[$i]) {
						$sel="selected";
					} else {
						$sel="";
					}
				$vats.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$vats.="</select>";

			$new_amounts_arr[$i] = sprint ($new_amounts_arr[$i]);
			
			$accounts .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$glacc</td>
						<td align='center'>".CUR." <input type='text' size='8' name='accamt[]' value='$new_amounts_arr[$i]'></td>
						<td>
							<input type='radio' name='chrgvat[$i]' value='inc' $chinc>Inclusive &nbsp;&nbsp;
							<input type='radio' name='chrgvat[$i]' value='exc' $chexc>Exclusive &nbsp;&nbsp;
							<input type='radio' name='chrgvat[$i]' value='nov' $chnov>No VAT
						</td>
						<td>$vats</td>
					</tr>";
	}

	if(!isset($errata)) {
		$errata="";
	}

//	$amount = sprint ($amount);

	// Layout
	$add = "
			<h3>New Bank Payment</h3>
			$errata
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='id' value='$id'>
				<input type='hidden' name='lnum' value='$lnum'>
				<input type='hidden' name='amount' value='$amount'>
				<tr>
					<td colspan='2'></td>
				</tr>
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
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount</td>
					<td valign='center'>".CUR." $amount</td>
				</tr>
				<tr><td><br></td></tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>Select Accounts Involved</td>
				<tr>
				<tr>
					<th>Account</th>
					<th>Amount</th>
					<th>VAT</th>
					<th>VAT Code</th>
				</tr>
				$accounts
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";

	return $add;
}

# confirm
function confirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		header("Location: batch-cashbook-view.php");
	}

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
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	foreach($accinv as $key => $vaccid){
		$accamt[$key]+=0;
		$v->isOk ($vaccid, "num", 1, 20, "Invalid Account (account involved).");
		$v->isOk ($accamt[$key], "float", 1, 10, "Invalid amount.");
		$v->isOk ($chrgvat[$key], "string", 1, 4, "Invalid VAT option.");
	}
	if(strlen($date_year) <> 4){
		$v->isOk ($date_year, "num", 0, 0, "Invalid Date year.");
	}
	$date = $date_day."-".$date_month."-".$date_year;

	$date_year += 0;
	$date_month += 0;
	$date_day += 0;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$HTTP_GET_VARS['errata'] = $confirm."</li>";
		$HTTP_GET_VARS['id'] = "$id";
		return add($HTTP_GET_VARS);
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

		db_conn('cubit');

		$Sl="SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$tchrgvat[$key]=$chrgvat[$key] ;

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		$totamt = $accamt[$key];
		if($chrgvat[$key] == "exc"){
			$vat = sprint(($vatp/100) * $accamt[$key]);
			$vat = sprint ($vat);
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

		$chrgvat[$key]=$tchrgvat[$key] ;

		$totamt = sprint ($totamt);
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
					<td>".CUR." $totamt</td>
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
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value=write>
				<input type='hidden' name='id' value='$id'>
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
				<tr><td><br></td></tr>
				<tr>
					<th>Account</th>
					<th>Amount</th>
					<th>VAT</th>
				</tr>
				$accounts
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right' colspan='2'><input type='submit' name='batch' value='Update Batch Entry &raquo'></td>
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

# Write
function write($HTTP_POST_VARS)
{

	# Processes
	db_connect();

	# Get vars
	extract ($HTTP_POST_VARS);

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
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	/* -- Start Hooks -- */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	/* -- End Hooks -- */

	# Refnum
	$refnum = getrefnum();

	$amounts = "";
	$accids = "";
	$vats = "";
	$chrgvats = "";
	$vatcodes = "";
	$gamt = 0;
	pglib_transaction("BEGIN");
	foreach($accinv as $key => $vaccid) {

		db_conn('cubit');

		$Sl="SELECT * FROM vatcodes WHERE id='$vatcode[$key]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		# Start Rattling vat
		$totamt = $accamt[$key];
		$vat = $getvat[$key];
		if($chrgvat[$key] == "exc"){
		//	$vat = sprint(($vatp/100) * $accamt[$key]);
			$totamt += $vat;
		} elseif($chrgvat[$key] == "inc"){
		//	$vat = sprint(($accamt[$key]/(100 + $vatp)) * $vatp);
			$accamt[$key] -= $vat;
		}else{
		//	$vat = 0;
		}
		
		$totamt = sprint ($totamt);

		$amounts .= "|$totamt";
		$vatcodes .= "|$vatcode[$key]";
		$accids .= "|$vaccid";
		$vats .= "|$vat";
		$chrgvats .= "|$chrgvat[$key]";

		$gamt = sprint($gamt + $totamt);
	}

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$cheqnum = 0 + $cheqnum;

	db_connect();

	$sql = "UPDATE batch_cashbook SET bankid = '$bankid', trantype = 'withdrawal', date = '$date', name = '$name',";
	$sql .= "descript = '$descript', cheqnum = '$cheqnum', amount = '$gamt', banked = 'no', accids = '$accids',";
	$sql .= "amounts = '$amounts', chrgvats = '$chrgvats', vats = '$vats', div = '".USER_DIV."',";
	$sql .= "vatcodes = '$vatcodes', reference = '$reference' WHERE cashid = '$id'";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	pglib_transaction("COMMIT");

	# Status report
	$write = "
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
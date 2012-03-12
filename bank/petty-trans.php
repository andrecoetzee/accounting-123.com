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
		case "confirm":
			$OUTPUT = confirm($_POST);
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

	extract($_POST);

	if(!isset($name)) {
		$bankid = 0;
		$laccid = 0;
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
		$name = "Petty Cash";
		$descript = "";
		$cheqnum = "";
		$amount = "";
		$frm = "";
	}

	if(!isset($laccid)) {
		$laccid = 0;
	}

	if(!isset($bankid)) {
		$bankid = 0;
	}

	core_connect();

	# Get Petty cash account
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	# Get account name for thy lame User's Sake
	$accRslt = get("core", "*", "accounts", "accid", $cashacc);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$acc = pg_fetch_array($accRslt);


	# Accounts Drop down selections
	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY topacc,accnum ASC";
	$laccRslt = db_exec($sql);
	if(pg_numrows($laccRslt) < 1){
		return "<li class='err'> There are no accounts yet in Cubit.</li>";
	}

	$glacc = mkAccSelect ("laccid", $laccid);

// 	$glacc = "<select name='laccid'>";
// 	while($lacc = pg_fetch_array($laccRslt)){
// 		# Check Disable
// 		if(isDisabled($lacc['accid']))
// 			continue;
// 		if($laccid == $lacc['accid']) {
// 			$sel = "selected";
// 		} else {
// 			$sel = "";
// 		}
// 		$glacc .= "<option value='$lacc[accid]' $sel>$lacc[topacc]/$lacc[accnum] - $lacc[accname]</option>";
// 	}
// 	$glacc .= "</select>";



	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY bankname,branchname";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "
			<li class='err'> There are no bank accounts yet on the Database.</li>
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$banksel = "<select name='bankid'>";
	while($bacc = pg_fetch_array($banks)){
		if($bacc['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$banksel .= "<option value='$bacc[bankid]' $sel>$bacc[accname] - $bacc[bankname] ($bacc[acctype])</option>";
	}
	$banksel .= "</select>";

	if($frm == "gl") {
		$c1 = "";
		$c2 = "checked=yes";
	} else {
		$c1 = "checked=yes";
		$c2 = "";
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
	$add = "
		<h3>Funds transfer to Petty cash</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th width='40%'>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='frm' value='bnk' $c1>From Bank Account</td>
				<td>$banksel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='frm' value='gl' $c2>From Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td>$glacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td valign='center'><input size='20' name='name' value='$name'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'><textarea cols='18' rows='2' name='descript'>$descript</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='10' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account</td>
				<td><input type='hidden' name='accinv' value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
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

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if($frm == 'bnk'){
		$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	}else{
		$v->isOk ($laccid, "num", 1, 30, "Invalid Account.");
	}
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($date_day, "num", 10, 1, "Invalid Date year.");
	}
	$v->isOk ($name, "string", 1, 255, "Invalid Name Paid To.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Account type (account involved).");
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.add($_POST);
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	if($frm == 'bnk'){
		# Get bank account name
		db_connect();
		$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);
		$account = "
			<input type='hidden' name='bankid' value='$bankid'>
			<tr bgcolor='".bgcolorg()."'>
				<td>From Bank Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>";
	}else{
		# get account name
		$laccRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $laccid);
		$lacc = pg_fetch_array($laccRslt);
		$account = "
			<input type='hidden' name='laccid' value='$laccid'>
			<tr bgcolor='".bgcolorg()."'>
				<td>From Account</td>
				<td>$lacc[topacc]/$lacc[accnum] - $lacc[accname]</td>
			</tr>";
	}

	# get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv);
	$accnt = pg_fetch_array($accRslt);

	// Layout
	$confirm = "
		<h3>Funds transfer to Petty cash</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='frm' value='$frm'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='accinv' value='$accinv'>
			<input type='hidden' name='day' value='$date_day'>
			<input type='hidden' name='mon' value='$date_month'>
			<input type='hidden' name='year' value='$date_year'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			$account
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td valign='center'>$name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account</td>
				<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
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

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return add($_POST);
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	if($frm == 'bnk'){
		$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	}else{
		$v->isOk ($laccid, "num", 1, 30, "Invalid Account.");
	}
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to/received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($accinv, "string", 1, 255, "Invalid account number (account involved).");

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

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# nasty zero
	$cheqnum += 0;

	if($frm == 'bnk'){
		# Get bank account name
		db_connect();
		$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);
		$frmaccid = getbankaccid($bankid);
		$details = "Transfer From Bank Account : $bank[accname] - $bank[bankname]";
	}else{
		# get account name
		$laccRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $laccid);
		$lacc = pg_fetch_array($laccRslt);
		$frmaccid = $laccid;
		$details = "Transfer From Account : $lacc[topacc]/$lacc[accnum] - $lacc[accname]";
	}

	pglib_transaction("BEGIN");

	# Some info
	$refnum = getrefnum();

	# write trans
	writetrans($accinv, $frmaccid, $date, $refnum, $amount, $descript);

	if($frm == 'bnk'){
		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}

	db_connect();

	# Record tranfer for patty cash report
	$sql = "
		INSERT INTO pettyrec (
			date, type, det, amount, name, div
		) VALUES (
			'$date', 'Transfer', '$descript', '$amount', '$details', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	pglib_transaction("COMMIT");

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Funds transfer to Petty cash</th>
			</tr>
			<tr class='datacell'>
				<td>Funds transfer to Petty cash has been added to the Petty cash book.</td>
			</tr>
		</table>";

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
						<tr bgcolor='".bgcolorg()."'>
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

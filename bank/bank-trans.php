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

	if(!isset($fbankid)) {
		$fbankid = 0;
		$tbankid = 0;
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
		$descript = "";
		$cheqnum = "";
		$amount = "";
		$reference = "";
	}

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet on the Database.</lI>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$frombanksel = "<select name='fbankid'>";
	while($bacc = pg_fetch_array($banks)){
		$frombanksel .= "<option value='$bacc[bankid]'>$bacc[accname] - $bacc[bankname] ($bacc[acctype])</option>";
	}
	$frombanksel .= "</select>";

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet on the Database.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$tobanksel = "<select name='tbankid'>";
	while($bacc = pg_fetch_array($banks)){
		$tobanksel .= "<option value='$bacc[bankid]'>$bacc[accname] - $bacc[bankname] ($bacc[acctype])</option>";
	}
	$tobanksel .= "</select>";

	$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
	if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
		$trans_date_value = getCSetting ("TRANSACTION_DATE");
		$date_arr = explode ("-", $trans_date_value);
		$date_year = $date_arr[0];
		$date_month = $date_arr[1];
		$date_day = $date_arr[2];
	}else {
		$date_year = date ("Y");
		$date_month = date ("m");
		$date_day = date ("d");
	}

	// Layout
	$add = "
		<h3>Bank transfer</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th width='40%'>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From</td>
				<td>$frombanksel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To</td>
				<td>$tobanksel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'><textarea cols='18' rows='2' name='descript'>$descript</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'><input type='text' size='25' name='reference' value='$reference'>
			</td>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='10' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
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
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
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
	$v->isOk ($fbankid, "num", 1, 30, "Invalid From Bank Account.");
	$v->isOk ($tbankid, "num", 1, 30, "Invalid To Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ("#", "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
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
		return $confirm.add($_POST);
	}



	# Get bank account name
	db_connect();

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$tbankid' AND div = '".USER_DIV."'";
	$tbankRslt = db_exec($sql);
	$tbank = pg_fetch_array($tbankRslt);

	// Layout
	$confirm = "
		<h3>Bank transfer</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='fbankid' value='$fbankid'>
			<input type='hidden' name='tbankid' value='$tbankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='day' value='$date_day'>
			<input type='hidden' name='mon' value='$date_month'>
			<input type='hidden' name='year' value='$date_year'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From</td>
				<td>$fbank[accname] - $fbank[bankname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To</td>
				<td>$tbank[accname] - $tbank[bankname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
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
				<td valign='center'>".CUR." $amount</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='5' align='right'><input type='submit' name='batch' value='Add to Batch &raquo;'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form></table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
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
	$v->isOk ($fbankid, "num", 1, 30, "Invalid From Bank Account.");
	$v->isOk ($tbankid, "num", 1, 30, "Invalid To Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");

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


	# Get bank account name
	db_connect();

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$tbankid' AND div = '".USER_DIV."'";
	$tbankRslt = db_exec($sql);
	$tbank = pg_fetch_array($tbankRslt);

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# nasty zero
	$cheqnum += 0;

	$faccid = getbankaccid($fbankid);
	$taccid = getbankaccid($tbankid);

	# Some info
	$refnum = getrefnum();

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	if(isset($batch)) {

		# Begin Updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO batch_cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				banked, accinv, div, bt, reference, rid
			) VALUES (
				'$fbankid', 'withdrawal', '$date', '$tbank[accname] - $tbank[bankname]', '$descript', '$cheqnum', '$amount', 
				'no', '$taccid', '".USER_DIV."', 'transfer', '$reference', '$tbankid'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

// 			$lcashid = pglib_lastid("cashbook", "cashid");
//
// 			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div) VALUES ('$tbankid', 'deposit', '$date', '$fbank[accname] - $fbank[bankname]', '$descript', '$cheqnum', '$amount', 'no', '$faccid', '".USER_DIV."')";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//
// 			$lcashid2 = pglib_lastid("cashbook", "cashid");
//
// 			# restore link
// 			$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//
// 			$sql = "UPDATE cashbook SET lcashid = '$lcashid' WHERE cashid = '$lcashid2'";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//
		# Commit Updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	} else {
		# Begin Updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write trans
		writetrans($taccid, $faccid, $date, $refnum, $amount, $descript);

		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				banked, accinv, reference, div
			) VALUES (
				'$fbankid', 'withdrawal', '$date', '$tbank[accname] - $tbank[bankname]', '$descript', '$cheqnum', '$amount', 
				'no', '$taccid', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid = pglib_lastid("cashbook", "cashid");

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, 
				amount, banked, accinv, reference, div
			) VALUES (
				'$tbankid', 'deposit', '$date', '$fbank[accname] - $fbank[bankname]', '$descript', '$cheqnum', 
				'$amount', 'no', '$faccid', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid2 = pglib_lastid("cashbook", "cashid");

		# restore link
		$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$sql = "UPDATE cashbook SET lcashid = '$lcashid' WHERE cashid = '$lcashid2'";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Commit Updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Bank transfer</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank transfer has been added to the Cash book.</td>
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
							<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
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

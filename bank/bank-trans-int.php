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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "add":
			$OUTPUT = add($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = slct();
	}
} else {
	# Display default output
	$OUTPUT = slct();
}

# get templete
require("../template.php");



# Insert details
function slct()
{

	# Accounts Drop down selections
	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$bnkRs = db_exec($sql);
	if(pg_numrows($bnkRs) < 1){
		return "<li class='err'> There are no local bank accounts found.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name='bankid'>";
	while($bnk = pg_fetch_array($bnkRs)){
		$banks .= "<option value='$bnk[bankid]'>$bnk[accname] - $bnk[bankname]</option>";
	}
	$banks .= "</select>";

	$sql = "SELECT * FROM bankacct WHERE btype = 'int' AND div = '".USER_DIV."'";
	$fbnkRs = db_exec($sql);
	if(pg_numrows($fbnkRs) < 1){
		return "
			<li class='err'>No foreign bank accounts were found.</li>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='bankacct-new.php'>Add Bank Account</a></td>
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
			</table>";
	}
	$fbanks = "<select name='fbankid'>";
	while($fbnk = pg_fetch_array($fbnkRs)){
		$fbanks .= "<option value='$fbnk[bankid]'>$fbnk[accname] - $fbnk[bankname]</option>";
	}
	$fbanks .= "</select>";

	# ttype array
	$ttypearr = array("loc" => "From Local To Foreign","int" => "From Foreign To Local");
	$ttypesel = extlib_mksel("ttype", $ttypearr);

	# layout
	$slct = "
		<h3>Bank Transfer</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='add'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Local Bank Account</td>
				<td valign='center'>$banks</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Foreign Bank Account</td>
				<td>$fbanks</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Transfer Type</td>
				<td>$ttypesel</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



# Insert details
function add($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Local Bank Account.");
	$v->isOk ($fbankid, "num", 1, 30, "Invalid Foreign Bank Account.");
	$v->isOk ($ttype, "string", 1, 4, "Invalid Transfer type option.");

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

	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	$sql = "SELECT * FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$curr = getsymbol($fbank['fcid']);
	$rate = getRate($fbank['fcid']);

	$scurr = ($ttype == 'loc') ? CUR : $curr['symbol'];

	# ttype array
	$ttypearr = array("loc" => "From Local To Foreign","int" => "From Foreign To Local");

	# layout
	$add = "
		<h3>Bank Transfer</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='fbankid' value='$fbankid'>
			<input type='hidden' name='ttype' value='$ttype'>
		<table ".TMPL_tblDflts." width='80%'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Local Bank Account</td>
				<td valign='center'>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Foreign Bank Account</td>
				<td valign='center'>$fbank[accname] - $fbank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date")." DD-MM-YYYY</td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Description</td>
				<td valign='center'><textarea col='18' rows='3' name='descript'></textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td><input type='text' size='25' name='reference'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='20' name='cheqnum'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>$scurr <input type='text' size='10' name='amount'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Exchange Rate</td>
				<td valign='center'>".CUR."/$curr[symbol] <input type='text' size='5' name='rate' value='$rate'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Tranfer Type</td>
				<td valign='center'>$ttypearr[$ttype]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
			</tr>
		</table>
		</form>";

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

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($fbankid, "num", 1, 30, "Invalid Foreign Bank Account.");
	$date = $v->chkDate($date_day, $date_month, $date_year, "Invalid date.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
	$v->isOk ($ttype, "string", 1, 4, "Invalid Transfer type option.");

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

	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	$sql = "SELECT * FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$curr = getsymbol($fbank['fcid']);

	# ttype array
	$ttypearr = array("loc" => "From Local To Foreign","int" => "From Foreign To Local");

	$scurr = ($ttype == 'loc') ? CUR : $curr['symbol'];

	# Layout
	$confirm = "
		<center>
		<h3>Bank Transfer</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<table ".TMPL_tblDflts." width='60%'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='fbankid' value='$fbankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='rate' value='$rate'>
			<input type='hidden' name='ttype' value='$ttype'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Local Bank Account</td>
				<td valign='center'>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Foreign Bank Account</td>
				<td valign='center'>$fbank[accname] - $fbank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
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
				<td valign='center'>$scurr $amount</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Exchange Rate</td>
				<td valign='center'>".CUR."/$curr[symbol] $rate</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Tranfer Type</td>
				<td valign='center'>$ttypearr[$ttype]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
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

	db_connect();

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($fbankid, "num", 1, 30, "Invalid Foreign Bank Account.");
	$date = $v->chkrDate($date, "Invalid date.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");
	$v->isOk ($ttype, "string", 1, 4, "Invalid Transfer type option.");

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

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get bank account name
	db_connect();

	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	$sql = "SELECT * FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	bank_xrate_update($fbank['fcid'], $rate);

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	#refnum
	$refnum = getrefnum();

	/* -- Start Hooks -- */

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$fbankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$fbanklnk = pg_fetch_array($rslt);

	/* -- End Hooks -- */

	$cheqnum = 0 + $cheqnum;

	if($ttype == 'loc'){
		$famount = sprint($amount/$rate);

		# Record the payment record
		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				famount, vat, chrgvat, banked, accinv, reference, div
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$fbank[accname] $fbank[bankname]', '$descript', '$cheqnum', '$amount', 
				'$famount', '0', 'no', 'no', '$fbanklnk[accnum]', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid = pglib_lastid("cashbook", "cashid");

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				famount, vat, chrgvat, banked, accinv, lcashid, reference, div
			) VALUES (
				'$fbankid', 'deposit', '$date', '$bank[accname] $bank[bankname]', '$descript', '$cheqnum', '$amount', 
				'$famount', '0', 'no', 'no', '$banklnk[accnum]',  '$lcashid', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid2 = pglib_lastid("cashbook", "cashid");

		#restore link
		$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Update the bankacct table (make fbalance less) [used for cashbook fc value]
		$sql = "UPDATE bankacct SET balance = (balance + '$amount'::numeric(13,2)), fbalance = (fbalance + '$famount'::numeric(13,2)) WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# DT(account involved), CT(bank)
		writetrans($fbanklnk['accnum'], $banklnk['accnum'], $date, $refnum, $amount, $descript);
	}else{
		$lamount = sprint($amount * $rate);

		# Record the payment record
		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				famount, vat, chrgvat, banked, accinv, reference, div
			) VALUES (
				'$bankid', 'deposit', '$date', '$fbank[accname] $fbank[bankname]', '$descript', '$cheqnum', '$lamount', 
				'$amount' , '0', 'no', 'no', '$fbanklnk[accnum]', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid = pglib_lastid("cashbook", "cashid");

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				famount, vat, chrgvat, banked, accinv, lcashid, reference, div
			) VALUES (
				'$fbankid', 'withdrawal', '$date', '$bank[accname] $bank[bankname]', '$descript', '$cheqnum', '$lamount', 
				'$amount', '0', 'no', 'no', '$banklnk[accnum]', '$lcashid', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		$lcashid2 = pglib_lastid("cashbook", "cashid");

		#restore link
		$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# Update the bankacct table (make fbalance less) [used for cashbook fc value]
		$sql = "UPDATE bankacct SET balance = (balance - '$lamount'::numeric(13,2)), fbalance = (fbalance - '$amount'::numeric(13,2)) WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# DT(account involved), CT(bank)
		writetrans($banklnk['accnum'], $fbanklnk['accnum'], $date, $refnum, $lamount, $descript);
	}

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Bank Payment</th>
			</tr>
			<tr class='datacell'>
				<td>Bank Transfer added to cash book.</td>
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
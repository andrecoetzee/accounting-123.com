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

	if(!isset($day)) {
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
		$cusnum = 0;
		$bankid = 0;
		$descript = "";
		$reference = "";
		$cheqnum = "";
		$amount = "";
	}

	global $_GET;

	if(isset($_GET["cusnum"])) {
		$cusnum = $_GET["cusnum"];
	}


	# Customers Drop down selections
	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' AND location != 'int' ORDER BY surname,accno";
	$custRslt = db_exec($sql);
	if(pg_numrows($custRslt) < 1){
		return "<li> There are no Creditors in Cubit.</li>";
	}
	$custs = "<select name='cusnum'>";
	while($cust = pg_fetch_array($custRslt)){
		if($cust['cusnum']==$cusnum) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$custs .= "<option value='$cust[cusnum]' $sel>($cust[accno]) $cust[cusname] $cust[surname]</option>";
	}
	$custs .= "</select>";

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY bankname,branchname";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$bank = "<select name='bankid'>";
	while($acc = pg_fetch_array($banks)){
		if($bankid == $acc['bankid']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$bank .= "<option value='$acc[bankid]' $sel>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$bank .= "</select>";

	if (!isset ($date_year)){
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
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Bank Account</td>
				<td valign='center'>$bank</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Paid to</td>
				<td valign='center'>$custs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Description</td>
				<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'><input type='text' size='25' name='reference' value='$reference'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
			</tr>
			".TBL_BR."
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
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
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
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer account.");
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

	# Get customer
	$custRslt = get("cubit", "*", "customers", "cusnum", $cusnum);
	$cust = pg_fetch_array($custRslt);

	if(!isset($acc['acctype']))
		$acc['acctype'] = "";

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
			<input type='hidden' name='cusnum' value='$cusnum'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='day' value='$date_day'>
			<input type='hidden' name='mon' value='$date_month'>
			<input type='hidden' name='year' value='$date_year'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname] ($acc[acctype])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td valign='center'>($cust[accno]) $cust[cusname] $cust[surname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td>$reference</td>
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
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
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

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer account.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
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

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];
	# refnum
	$refnum = getrefnum();
	# cheq number
	$cheqnum = 0 + $cheqnum;

	# Get customer
	$custRslt = get("cubit", "*", "customers", "cusnum", $cusnum);
	$cust = pg_fetch_array($custRslt);

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# DT(customer control), CT(bank)
		writetrans($dept['debtacc'], $banklnk['accnum'], $date, $refnum, $amount, $descript);
		recordDT($amount, $cust['cusnum'],$date);

		# Record the payment record
		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, cusnum, name, descript, 
				cheqnum, amount, vat, chrgvat, banked, accinv, reference, div
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$cusnum', '($cust[accno]) $cust[cusname] $cust[surname]', '$descript', 
				'$cheqnum', '$amount', '0', 'no', 'no', '$dept[debtacc]', '$reference', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# record the payment on the statement
		$sql = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, type, st, div, allocation_date
			) VALUES (
				'$cust[cusnum]', '0', '$amount', '$date', '$descript', 'n', '".USER_DIV."', '$date'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# record the payment on the statement
		$sql = "
			INSERT INTO open_stmnt (
				cusnum, invid, amount, date, type, st, div, balance
			) VALUES (
				'$cust[cusnum]', '0', '$amount', '$date', '$descript', 'n', '".USER_DIV."', '$amount'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$amount') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

		# Make ledge record
		custledger($cust['cusnum'], $banklnk['accnum'], $date, $refnum, $descript, $amount, "d");

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Bank Payment</th>
			</tr>
			<tr class='datacell'>
				<td>Bank Payment added to cash book.</td>
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
						<tr class='datacell'>
							<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='bank-pay-add.php'>Add Bank Payment</a></td></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
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



# records for DT
function recordDT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



?>

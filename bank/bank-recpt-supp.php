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

	if(!isset($supid)) {
		$supid = 0;
		$bankid = 0;
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
		$descript = "";
		$reference = "";
		$cheqnum = "";
		$amount = "";
	}

	global $_GET;
	if(isset($_GET["account"])) {
		$supid = $_GET["account"];
	}

	# Suppliers Drop down selections
	db_connect();

	$sql = "SELECT supid,supno,supname FROM suppliers WHERE div = '".USER_DIV."' AND location != 'int' ORDER BY supname,supno";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		return "<li> There are no Creditors in Cubit.</li>";
	}

	$supp = "<select name='supid'>";
	while($sup = pg_fetch_array($supRslt)){
		if($sup['supid'] == $supid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$supp .= "<option value='$sup[supid]' $sel>$sup[supname] ($sup[supno])</option>";
	}
	$supp .= "</select>";

	$add = "
		<h3>New Bank Receipt</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td valign='center'>
					<select name='bankid'>";

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);

	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = pg_fetch_array($banks)){
		if($acc['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$add .= "<option value='$acc[bankid]' $sel>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

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

	$add .= "
					</select>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Supplier Received from</td>
				<td valign='center'>$supp</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'><textarea col='20' rows='5' name='descript'>$descript</textarea></td>
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
				<td>".REQ."Amount</td>
				<td valign='center'>".CUR." <input type='text' size='18' name='amount' value='$amount'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
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



# Confirm
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
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier account.");
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
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Get account name
	$supRslt = get("cubit", "*", "suppliers", "supid", $supid);
	$sup = pg_fetch_array($supRslt);

	if (!isset ($acc['acctype']))
		$acc['acctype'] = "";

	$confirm = "
					<center>
					<h3>New Bank Receipt</h3>
					<h4>Confirm entry (Please check the details)</h4>
					<table ".TMPL_tblDflts." width='60%'>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='bankid' value='$bankid'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='descript' value='$descript'>
						<input type='hidden' name='reference' value='$reference'>
						<input type='hidden' name='cheqnum' value='$cheqnum'>
						<input type='hidden' name='amount' value='$amount'>
						<input type='hidden' name='supid' value='$supid'>
						<input type='hidden' name='day' value='$date_day'>
						<input type='hidden' name='mon' value='$date_month'>
						<input type='hidden' name='year' value='$date_year'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account</td>
							<td>$bank[accname] - $bank[bankname] ($acc[acctype])</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Received from</td>
							<td valign='center'>$sup[supno] - $sup[supname]</td>
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
							<td valign='center'>".CUR." $amount</td>
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

	# Processes
	db_connect();

	# Get vars
	extract($_POST);

	if(isset($back)) {
		return add($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier account.");

	# Display errors, if any
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

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$cheqnum = 0 + $cheqnum;

	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Get account name
	$supRslt = get("cubit", "*", "suppliers", "supid", $supid);
	$sup = pg_fetch_array($supRslt);

	$bankaccid = getbankaccid($bankid);


	db_conn("exten");
	# Get debtors control account
	$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	pglib_transaction("BEGIN");

	db_connect();
	$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$supid','$amount','$date', '$descript','$cheqnum','$bankaccid', '".USER_DIV."')";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Update the supplier (make balance less)
	$sql = "UPDATE suppliers SET balance = (balance + '$amount') WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	suppCT($amount, $supid,$date);

	# record the payment record
	db_connect();
	$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, suprec, reference, div) VALUES ('$bankid', 'deposit', '$date', '$sup[supno] - $sup[supname]', '$descript', '$cheqnum', '$amount', 'no', '$dept[credacc]', '$supid', '$reference', '".USER_DIV."')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	$refnum = getrefnum();

	# DT(account involved), CT(bank)
	writetrans($bankaccid, $dept['credacc'], $date, $refnum, $amount, $descript);

	suppledger($supid, $bankaccid , $date, 0, $descript,$amount , 'c');

	pglib_transaction("COMMIT");

	# Status report
	$write = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Bank Receipt</th>
					</tr>
					<tr class='datacell'>
						<td>Bank Receipt from supplier : $sup[supname] added to cash book.</td>
					</tr>
				</table>";

	# main table (layout with menu)
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
							</table>
						</td>
					</tr>
				</table>";
	return $OUTPUT;

}


?>

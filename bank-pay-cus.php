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
		$day=date("d");
		$mon=date("m");
		$year=date("Y");
		$cusnum=0;
		$bankid=0;
		$descript="";
		$cheqnum="";
		$amount="";
	}

	global $_GET;

	if(isset($_GET["cusnum"])) {
		$cusnum=$_GET["cusnum"];
	}
		
	
	# Customers Drop down selections
	db_connect();
	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' AND location != 'int' ORDER BY surname,accno";
	$custRslt = db_exec($sql);
	if(pg_numrows($custRslt) < 1){
		return "<li> There are no Creditors in Cubit.";
	}
	$custs = "<select name='cusnum'>";
	while($cust = pg_fetch_array($custRslt)){
		if($cust['cusnum']==$cusnum) {
			$sel="selected";
		} else {
			$sel="";
		}
		$custs .= "<option value='$cust[cusnum]' $sel>($cust[accno]) $cust[cusname] $cust[surname]</option>";
	}
	$custs .="</select>";

	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY bankname,branchname";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
			return "<li class=err> There are no accounts held at the selected Bank.
			<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$bank = "<select name=bankid>";
	while($acc = pg_fetch_array($banks)){
		if($bankid==$acc['bankid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$bank .= "<option value='$acc[bankid]' $sel>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$bank .= "</select>";

	# layout
	$add = "<h3>New Bank Payment</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Bank Account</td><td valign=center>$bank</td></tr>
	<tr class='bg-even'><td>Date</td><td><input type=text size=2 name=day maxlength=2 value='$day'>-<input type=text size=2 name=mon maxlength=2 value='$mon'>-<input type=text size=4 name=year maxlength=4 value='$year'></td></tr>
	<tr class='bg-odd'><td>Paid to</td><td valign=center>$custs</td></tr>
	<tr class='bg-even'><td valign=top>Description</td><td valign=center><textarea col=18 rows=3 name=descript>$descript</textarea></td></tr>
	<tr class='bg-odd'><td>Cheque Number</td><td valign=center><input size=20 name=cheqnum value='$cheqnum'></td></tr>
	<tr class='bg-even'><td>Amount</td><td valign=center>".CUR." <input type=text size=10 name=amount value='$amount'></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td valign=center align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>";

	# main table (layout with menu)
	$OUTPUT = "<center>
	<table width = 100%>
	<tr><td width=65% align=left>$add</td>
	<td valign=top align=center>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=65%>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>
	</td></tr>
	</table>";

	return $OUTPUT;
}

# confirm
function confirm($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($mon, "num", 1,2, "Invalid Date month.");
	$v->isOk ($year, "num", 1,4, "Invalid Date Year.");
	if(strlen($year) <> 4){
			$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer account.");
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return $confirm."</li>".add($_POST);
	}

	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Get customer
	$custRslt = get("cubit", "*", "customers", "cusnum", $cusnum);
	$cust = pg_fetch_array($custRslt);

	# Layout
	$confirm ="<center><h3>New Bank Payment</h3>
	<h4>Confirm entry (Please check the details)</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=60%>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=bankid value='$bankid'>
	<input type=hidden name=date value='$date'>
	<input type=hidden name='cusnum' value='$cusnum'>
	<input type=hidden name=descript value='$descript'>
	<input type=hidden name=cheqnum value='$cheqnum'>
	<input type=hidden name=amount value='$amount'>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=mon value='$mon'>
	<input type=hidden name=year value='$year'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Account</td><td>$bank[accname] - $bank[bankname] ($acc[acctype])</td></tr>
	<tr class='bg-even'><td>Date</td><td valign=center>$date</td></tr>
	<tr class='bg-odd'><td>Paid to</td><td valign=center>($cust[accno]) $cust[cusname] $cust[surname]</td></tr>
	<tr class='bg-even'><td>Description</td><td valign=center>$descript</td></tr>
	<tr class='bg-odd'><td>Cheque Number</td><td valign=center>$cheqnum</td></tr>
	<tr class='bg-even'><td>Amount</td><td valign=center>".CUR." $amount</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo'></td></tr>
	</form></table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write
function write($_POST)
{
	# processes
	db_connect();

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	
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
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

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
		return "<i class=err>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# DT(customer control), CT(bank)
		writetrans($dept['debtacc'], $banklnk['accnum'], $date, $refnum, $amount, $descript);
		recordDT($amount, $cust['cusnum'],$date);

		# Record the payment record
		db_connect();
		$sql = "INSERT INTO cashbook(bankid, trantype, date, cusnum, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div) VALUES ('$bankid', 'withdrawal', '$date', '$cusnum', '($cust[accno]) $cust[cusname] $cust[surname]', '$descript', '$cheqnum', '$amount', '0', 'no', 'no', '$dept[debtacc]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		# record the payment on the statement
		$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, st, div) VALUES('$cust[cusnum]', '0', '$amount', '$date', '$descript', 'n', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# record the payment on the statement
		$sql = "INSERT INTO open_stmnt(cusnum, invid, amount,balance, date, type, st, div) VALUES('$cust[cusnum]', '0', '$amount','$amount', '$date', '$descript', 'n', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$amount') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

		# Make ledge record
		custledger($cust['cusnum'], $banklnk['accnum'], $date, $refnum, $descript, $amount, "d");

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Status report
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th>Bank Payment</th></tr>
		<tr class=datacell><td>Bank Payment added to cash book.</td></tr>
	</table>";

	# Main table (layout with menu)
	$OUTPUT = "<center>
	<table width = 90%>
		<tr valign=top><td width=50%>$write</td>
		<td align=center>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='bank-pay-add.php'>Add Bank Payment</a></td></tr>
			<tr class='bg-odd'><td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td></tr>
			<tr class='bg-odd'><td><a href='cashbook-view.php'>View Cash Book</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		</td></tr>
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

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
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "details":
			if(isset($_POST['details'])){
					$OUTPUT = details($_POST);
			}else{
					$OUTPUT = details2($_POST);
			}
			break;
		default:
			if (isset($_GET['cusnum'])){
				$OUTPUT = slctacc ($_GET);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['cusnum'])){
		$OUTPUT = slctacc ($_GET);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# Get templete
require("template.php");




# Select Accounts
function slctacc($_GET)
{

	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}




	# refnum
	$refnum = getrefnum();

	# Select customer
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li> Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# Accounts drop down
	core_connect();
	$accounts = "<select name='accid'>";
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li>There are No accounts in Cubit.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			$sel = "";
			if(isset($cacc)){
				if($cacc == $acc['accid'])
					$sel = "selected";
			}
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;

			$accounts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
		}
	$accounts .="</select>";

	$entd = "";
	$entc = "checked=yes";
	if(isset($tran)){
		if($tran == "dt"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

	// Accounts (debit)
	$view = "
			<h3> Journal transaction </h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='details'>
				<input type='hidden' name='cusnum' value='$cusnum'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Account Number</td>
					<td>$cust[accno]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer</td>
					<td>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>".mkDateSelect("date")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Reference Number</td>
					<td><input type='text' size='10' name='refnum' value='".($refnum++)."'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Entry Type</td>
					<td><input type='radio' name='entry' value='DT' $entd> Debit | <input type='radio' name='entry' value='CT' $entc>Credit</td>
				</tr>
				<tr class='".bg_class()."'>
					<td rowspan='2'>Cotra Account</td>
					<td>$accounts <input name='details' type='submit' value='Enter Details'></td>
				</tr>
				<tr class='".bg_class()."'>
					<!--        Rowspan      -->
					<td><input type='text' name='accnum' size='20'> <input type='submit' value='Enter Details'></td>
				</tr>
			</table>
			<p>
			<input type='button' value='Go Back' onClick='javascript:history.back();'>
			</form>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				".TBL_BR."
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='trans-new.php'>Journal Transactions</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='../customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $view;

}



# Enter Details of Transaction
function details($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");

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

		# get contra account details
		$accRs = get("core","*","accounts","accid",$accid);
		$acc  = pg_fetch_array($accRs);

		# Select customer
		db_connect();
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($custRslt) < 1){
			return "<li> Invalid Customer ID.</li>";
		}else{
			$cust = pg_fetch_array($custRslt);
		}

		# Probe tran type
		if($entry == "CT"){
			$tran = "
						<tr class='".bg_class()."'>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
							<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
						</tr>
					";
		}else{
			$tran = "
						<tr class='".bg_class()."'>
							<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						</tr>
					";
		}

        // Layout Details
        $details = "
			<h3> Journal transaction details</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name='entry' value='$entry'>
			<table ".TMPL_tblDflts." width='500'>
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				".TBL_BR."
				".TBL_BR."
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td valign='center'>$date</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Reference No.</td>
					<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Amount</td>
					<td valign='center'>$cust[currency]<input type='text' size='20' name='amount'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $cust[currency] <input type='text' size='8' name='rate' value='1'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Transaction Details</td>
					<td valign='center'><textarea cols='20' rows='5' name='details'></textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Person Authorising</td>
					<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
				</tr>
				".TBL_BR."
				<tr>
					<td><input type='button' value=Back OnClick='javascript:history.back()'></td>
					<td valign='center'><input type='submit' value='Record Transaction'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='trans-new.php'>Journal Transactions</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='../customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $details;

}



# Enter Details of Transaction
function details2($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");

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



	$accnum = explode("/", rtrim($accnum));

    if(count($accnum) < 2){
		// account numbers
		$accRs = get("core","*","accounts","topacc",$accnum[0]."' AND accnum = '000");
		if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum[0] does not exist.</li>";
		}
		$acc  = pg_fetch_array($accRs);
    }else{
		// account numbers
		$accRs = get("core","*","accounts","topacc","$accnum[0]' AND accnum = '$accnum[1]");
		if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum[0]/$accnum[1] does not exist.</li>";
		}
		$acc  = pg_fetch_array($accRs);
    }

	# Select customer
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li> Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# probe tran type
	if($entry == "CT"){
		$tran = "
				<tr class='".bg_class()."'>
					<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
				</tr>";
	}else{
		$tran = "
				<tr class='".bg_class()."'>
					<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
					<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				</tr>";
	}

	// Layout Details
	$details = "
			<h3>Journal transaction details</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name='entry' value='$entry'>
			<table ".TMPL_tblDflts." width='500'>
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				".TBL_BR."
				".TBL_BR."
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td valign='center'>$date</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Reference No.</td>
					<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Amount</td>
					<td valign='center'>$cust[currency] <input type='text' size='20' name='amount'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Exchange rate</td>
					<td valign='center'>".CUR." / $cust[currency] <input type='text' size='8' name='rate' value='1'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Transaction Details</td>
					<td valign='center'><textarea cols='20' rows='5' name='details'></textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Person Authorising</td>
					<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
				</tr>
				".TBL_BR."
				<tr>
					<td><input type='button' value=Back OnClick='javascript:history.back()'></td>
					<td valign='center'><input type='submit' value='Record Transaction'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='trans-new.php'>Journal Transactions</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='../customers-view.php'>View Customers</a></td>
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

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[2], $datea[0])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
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

	# Get contra account details
	$accRs = get("core","*","accounts","accid",$accid);
	$acc  = pg_fetch_array($accRs);

	# Select customer
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li> Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# Probe tran type
	if($entry == "CT"){
		$tran = "
					<tr class='".bg_class()."'>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
					</tr>
				";
	}else{
		$tran = "
					<tr class='".bg_class()."'>
						<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					</tr>
				";
	}

	$lamt = sprint($amount * $rate);
	$amount = sprint($amount);

	// Layout
	$confirm = "
			<h3>Record Journal transaction</h3>
			<h4>Confirm entry</h4>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name=accname value='$acc[accname]'>
				<input type='hidden' name=date value='$date'>
				<input type='hidden' name=refnum value='$refnum'>
				<input type='hidden' name=entry value='$entry'>
				<input type='hidden' name=amount value='$amount'>
				<input type='hidden' name=rate value='$rate'>
				<input type='hidden' name=details value='$details'>
				<input type='hidden' name=author value='$author'>
			<table ".TMPL_tblDflts." width='500'>
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				".TBL_BR."
				".TBL_BR."
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>$date</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Reference number</td>
					<td>$refnum</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Amount</td>
					<td valign='center'>$cust[currency] $amount | ".CUR." $lamt</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Details</td>
					<td>$details</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Authorising Person</td>
					<td>$author</td>
				</tr>
				".TBL_BR."
				<tr>
					<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
					<td align='right'><input type='submit' value='Confirm Transaction &raquo'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='trans-new.php'>Journal Transactions</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='../customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $confirm;

}




# Write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[2], $datea[0])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
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



	 # Accounts details
    $accRs = get("core","*","accounts","accid",$accid);
    $acc  = pg_fetch_array($accRs);

	# Select customer
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li> Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$famt = sprint($amount);
	$amount = sprint($amount * $rate);

	cus_xrate_update($cust['fcid'], $rate);
	xrate_update($cust['fcid'], $rate, "invoices", "invid");
	xrate_update($cust['fcid'], $rate, "custran", "id");

	# Probe tran type
	if($entry == "CT"){
		# Write transaction  (debit contra account, credit debtors control)
		writetrans($accid, $dept['debtacc'], $date, $refnum, $amount, $details." - Customer $cust[cusname] $cust[surname]");
		$tran = "
				<tr class='".bg_class()."'>
					<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
				</tr>";
		$samount = sprint($amount - ($amount * 2));
		$sfamt = sprint($famt - ($famt * 2));
		// recordCT($samount, $cust['cusnum']);
		frecordCT($famt, $amount, $cust['cusnum'], $cust['fcid'],$date);

		$type = 'c';
	}else{
		# Write transaction  (debit debtors control, credit contra account)
		writetrans($dept['debtacc'], $accid, $date, $refnum, $amount, $details." - Customer $cust[cusname] $cust[surname]");
		$tran = "
				<tr class='".bg_class()."'>
					<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
					<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				</tr>";
		$samount = $amount;
		$sfamt = $famt;
		// recordDT($samount, $cust['cusnum']);
		frecordDT($famt, $amount, $cust['cusnum'], $cust['fcid'],$date);
		$type = 'd';
	}

	db_connect();
	# Begin updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$sdate = date("Y-m-d");
		# record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, amount, date, type, st, div, allocation_date) 
			VALUES 
				('$cust[cusnum]', '0', '$sfamt', '$date', '$details', 'n', '".USER_DIV."', '$date')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$samount'), fbalance = (fbalance + '$sfamt') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Make ledge record
	custledger($cust['cusnum'], $accid, $date, $refnum, $details, $amount, $type);

		// Start layout
        $write ="
			<h3>Journal transaction has been recorded</h3>
			<table ".TMPL_tblDflts." width='500'>
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				".TBL_BR."
				<tr colspan='2'>
					<td><h4>Amount</h4></td>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='2'><b>".CUR." $famt</b></td>
				</tr>
			</table>
			<P>
			<table ".TMPL_tblDflts." width='25%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='trans-new.php'>Journal Transactions</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center'><a href='../customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $write;

}




# psuedo functions
function frecordDT($amount, $lamount, $cusnum, $fcid,$odate)
{

	db_connect();

	/* Make transaction record for age analysis */
	//.$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

}



function frecordCT($amount, $lamount, $cusnum, $fcid,$odate)
{

	db_connect();

	/* Make transaction record for age analysis */
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '-$lamount', '-$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

}



?>
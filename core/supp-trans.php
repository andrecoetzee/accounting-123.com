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

# Get settings
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
		case "":
			$OUTPUT = slctacc($_POST);
			break;
		default:
			if (isset($_GET['supid'])){
				$OUTPUT = slctacc ($_GET);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['supid'])){
		$OUTPUT = slctacc ($_GET);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# get templete
require("template.php");






# Select Accounts
function slctacc($_GET)
{

	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# Select supplier
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
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
			} else {
				if(isset($accid) && $accid == $acc['accid']) {
					$sel=" selected";
				}
			}
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;
			$accounts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
		}
	$accounts .= "</select>";

	# get entry type
	$entd = "";
	$entc = "checked=yes";
	if(isset($entry)){
		if($entry == "DT"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

// 	if(!isset($date_year)){
// 		$date_year = date("Y");
// 	}
// 	if(!isset($date_month)){
// 		$date_month = date("m");
// 	}
// 	if(!isset($date_day)){
// 		$date_day = date("d");
// 	}
	if(!isset($refnum)){
		# get last ref number
		$refnum = getrefnum();
		/*refnum*/
		$refnum++;
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

	// Accounts (debit)
	$view = "
		<h3> Journal transaction </h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='supid' value='$supid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Supplier Number</td>
				<td>$supp[supno]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Supplier</td>
				<td>$supp[supname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference Number</td>
				<td><input type='text' size='10' name='refnum' value='$refnum'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Entry Type</td>
				<td>
					<li class='err'>This will debit/credit the supplier account selected</li>
					<input type='radio' name='entry' value='DT' $entd> Debit | 
					<input type='radio' name='entry' value='CT' $entc>Credit
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td rowspan='2'>Contra Account <br><input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td>$accounts <input name='details' type='submit' value='Enter Details'></td>
			</tr>
			<tr class='".bg_class()."'>
				<!--       Rowspan      -->
				<td><input type='text' name='accnum' size='20'> <input type='submit' value='Enter Details'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Charge VAT</td>
				<td><input type='radio' name='chrgvat' value='yes'> Yes <input type='radio' name='chrgvat' value='no' checked='yes'> No</td>
			</tr>
		</table>
		<p>
		</form>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
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

	if (!isset($chrgvat))
		$chrgvat = "yes";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");
	$v->isOk ($chrgvat, "string", 1, 10, "Invalid Charge VAT Option.");

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

	# Select supplier
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Probe tran type
	if($entry == "CT"){
		$tran = "
			<tr class='".bg_class()."'>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				<td>$supp[supno] - $supp[supname]</td>
			</tr>";
	}else{
		$tran = "
			<tr class='".bg_class()."'>
				<td>$supp[supno] - $supp[supname]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}

	if(!isset($amount)) {
		$amount = "";
		$details = "";
	}

	db_connect ();

	#get vat codes for dropdown
	$get_vatc = "SELECT * FROM vatcodes ORDER BY code";
	$run_vatc = db_exec($get_vatc) or errDie ("Unable to get vat codes information.");
	if(pg_numrows($run_vatc) < 1){
		$vatcode_drop = "<input type='hidden' name='vatcode' value=''>";
	}else {
		$vatcode_drop = "<select name='vatcode'>";
		while ($varr = pg_fetch_array ($run_vatc)){
			if(isset($vatcode) AND ($vatcode == $varr['id'])){
				$vatcode_drop .= "<option value='$varr[id]' selected>$varr[code] $varr[description]</option>";
			}else {
				$vatcode_drop .= "<option value='$varr[id]'>$varr[code] $varr[description]</option>";
			}
		}
		$vatcode_drop .= "</select>";
	}

	if($chrgvat == "yes"){
		$showvat = "
			<tr class='".bg_class()."'>
				<td>VAT</td>
				<td>
					$vatcode_drop <br>
					<input type='radio' name='vatinc' value='inc' checked='yes'> Inclusive <br>
					<input type='radio' name='vatinc' value='excl'> Exclusive <br>
				</td>
			</tr>";
	}else {
		$showvat = "
			<input type='hidden' name='vatcode' value='0'>
			<input type='hidden' name='vatinc' value='0'>";
	}

	// Layout Details
	$details = "
		<h3> Journal transaction details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='entry' value='$entry'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
		<table ".TMPL_tblDflts.">
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
				<td valign='center'>".CUR."<input type='text' size='20' name='amount' value='$amount'></td>
			</tr>
			$showvat
			<tr class='".bg_class()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$details</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
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
				<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
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
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");

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
		if(isDisabled($acc['accid'])) 
			return "<li> Accounts number : $accnum[0]/$accnum[1] is invalid. (Blocked).</li>".slctacc($_POST);
    }else{
		// account numbers
		$accRs = get("core","*","accounts","topacc","$accnum[0]' AND accnum = '$accnum[1]");
		if(pg_numrows($accRs) < 1){
			return "<li> Accounts number : $accnum[0]/$accnum[1] does not exist.</li>";
		}
		$acc  = pg_fetch_array($accRs);
		if(isDisabled($acc['accid'])) 
			return "<li> Accounts number : $accnum[0]/$accnum[1] is invalid. (Blocked).</li>".slctacc($_POST);
    }

	# Select supplier
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li>Supplier has been blocked.</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# probe tran type
	if($entry == "CT"){
		$tran = "
			<tr class='".bg_class()."'>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				<td>$supp[supno] - $supp[supname]</td>
			</tr>";
	}else{
		$tran = "
			<tr class='".bg_class()."'>
				<td>$supp[supno] - $supp[supname]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}

	// Layout Details
    $details = "
		<h3>Journal transaction details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='entry' value='$entry'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
		<table ".TMPL_tblDflts.">
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
				<td valign='center'>".CUR."<input type='text' size='20' name='amount'></td>
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
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center' align='right'><input type='submit' value='Write &raquo;'></td>
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
				<td align=center><a href='../supp-view.php'>View Suppliers</a></td>
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
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($chrgvat, "string", 1, 10, "Invalid Charge VAT Option.");
	$v->isOk ($vatinc, "string", 1, 10, "Invalid VAT Inclusive Exclusive Option.");
	$v->isOk ($vatcode, "num", 1, 5, "Invalid Vat Code Option.");

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

	# Select supplier
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Probe tran type
	if($entry == "CT"){
		$tran = "
			<tr class='".bg_class()."'>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				<td>$supp[supno] - $supp[supname]</td>
			</tr>";
	}else{
		$tran = "
			<tr class='".bg_class()."'>
				<td>$supp[supno] - $supp[supname]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}

	if(isset($chrgvat) AND $chrgvat == "yes"){

		db_connect ();

		#get selected vatcode
		$get_vatcode = "SELECT vat_amount FROM vatcodes WHERE id = '$vatcode' LIMIT 1";
		$run_vatcode = db_exec($get_vatcode) or errDie ("Unable to get vat code information.");
		if(pg_numrows($run_vatcode) < 1){
			#vatcode not found .... 
			return "<li class='err'>Unable to get vat code information.</li>";
		}
		$vd = pg_fetch_array ($run_vatcode);

		if($vatinc == "inc"){
			#vat inc ...  recalc value
			
			$vatamt = sprint(($amount)*($vd['vat_amount']/(100+$vd['vat_amount'])));
			$showamount = sprint ($amount - $vatamt);
			$showvat = sprint ($vatamt);
		}else {
			#vat excl
			$showamount = sprint ($amount);
			$vatamt = ($amount / 100) * $vd['vat_amount'];
			$showvat = sprint ($vatamt);
		}
	}else {
		#vat not set
		$showamount = sprint ($amount);
		$showvat = sprint (0);
	}

    // Layout
    $confirm = "
		<h3>Record Journal transaction</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='accname' value='$acc[accname]'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='refnum' value='$refnum'>
			<input type='hidden' name='entry' value='$entry'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='author' value='$author'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<input type='hidden' name='vatcode' value='$vatcode'>
			<input type='hidden' name='vatinc' value='$vatinc'>
		<table ".TMPL_tblDflts.">
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
				<td>".CUR." $showamount</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT</td>
				<td>".CUR." $showvat</td>
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
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
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
				<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
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

	if(isset($back)) {
		unset($_POST["back"]);
		return details($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($chrgvat, "string", 1, 10, "Invalid Charge VAT Option.");
	$v->isOk ($vatinc, "string", 1, 10, "Invalid VAT Inclusive Exclusive Option.");
	$v->isOk ($vatcode, "num", 1, 5, "Invalid Vat Code Option.");

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


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	if(isset($chrgvat) AND $chrgvat == "yes"){

		db_connect ();

		#get selected vatcode
		$get_vatcode = "SELECT * FROM vatcodes WHERE id = '$vatcode' LIMIT 1";
		$run_vatcode = db_exec($get_vatcode) or errDie ("Unable to get vat code information.");
		if(pg_numrows($run_vatcode) < 1){
			#vatcode not found .... 
			return "<li class='err'>Unable to get vat code information.</li>";
		}
		$vd = pg_fetch_array ($run_vatcode);

		if($vatinc == "inc"){
			#vat inc ...  recalc value
			$vatamt = sprint(($amount) * ($vd['vat_amount'] / (100 + $vd['vat_amount'])));
			$amount = sprint ($amount - $vatamt);
		}else {
			#vat excl
			$amount = sprint ($amount);
			$vatamt = sprint (($amount / 100) * $vd['vat_amount']);
		}
	}else {
		#vat not set
		$amount = sprint ($amount);
		$vatamt = sprint (0);
	}

	$date = "$datea[2]-$datea[1]-$datea[0]";

	# Accounts details
	$accRs = get("core","*","accounts","accid",$accid);
	$acc  = pg_fetch_array($accRs);

	# Select supplier
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$supp['supname'] = remval($supp['supname']);

	#get vat acc ...
//	$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","");

	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Probe tran type
	if($entry == "CT"){
		# Write transaction  (debit contra account, credit debtors control)
		writetrans($accid, $dept['credacc'], $date, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "
			<tr class='".bg_class()."'>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				<td>$supp[supno] - $supp[supname]</td>
			</tr>";
		$samount = ($amount - ($amount * 2));
		$svatamount = ($vatamt - ($vatamt * 2));
		recordCT(-$amount, $supp['supid'],$date);
		$type = 'c';

		if(isset($chrgvat) AND $chrgvat == "yes"){
			writetrans($vatacc, $dept['credacc'],  $date, $refnum, $vatamt, "VAT for Transaction: $refnum for Supplier : $supp[supname]");
			vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,"VAT for Transaction: $refnum for Supplier : $supp[supname]",$samount+$svatamount,$svatamount);
		}
		$balamt = sprint ($amount + $vatamt);
	}else{
		# Write transaction  (debit debtors control, credit contra account)
		writetrans($dept['credacc'], $accid, $date, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "
			<tr class='".bg_class()."'>
				<td>$supp[supno] - $supp[supname]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
		$samount = $amount;
		$svatamount = $vatamt;
		recordDT($amount, $supp['supid'],$date);
		$type = 'd';

		if(isset($chrgvat) AND $chrgvat == "yes"){
//			writetrans($vatacc, $dept['credacc'], $date, $refnum, $vatamt, "VAT for Transaction: $refnum for Supplier : $supp[supname]");
			writetrans($dept['credacc'], $vatacc, $date, $refnum, $vatamt, "VAT for Transaction: $refnum for Supplier : $supp[supname]");
			vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,"VAT for Transaction: $refnum for Supplier : $supp[supname]",$samount+$svatamount,$svatamount);
		}
		$balamt = sprint (($amount - ($amount * 2)) + ($vatamt - ($vatamt * 2)));
	}

	db_connect();

	$edate = date("Y-m-d");
	# record the payment on the statement
	$sql = "
		INSERT INTO sup_stmnt (
			supid, edate, ref, cacc, descript, amount, div
		) VALUES (
			'$supp[supid]', '$date', '0', '$accid', '$details', '".sprint ($balamt)."', '".USER_DIV."'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

	# update the supplier (make balance more)
	$sql = "UPDATE suppliers SET balance = (balance + '$balamt') WHERE supid = '$supp[supid]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

	# Ledger Records
	suppledger($supp['supid'], $accid, $date, $refnum, $details, sprint($amount+$vatamt), $type);

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Start layout
	$write = "
		<h3>Supplier Journal transaction has been recorded</h3>
		<table ".TMPL_tblDflts.">
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
				<td colspan='2'><b>".CUR." $amount</b></td>
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
				<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}




function recordDT($amount, $supid,$edate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance > 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$supid', '0', '$edate', '-$amount', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "
			INSERT INTO suppurch (
				supid, purid, pdate, balance, div
			) VALUES (
				'$supid', '0', '$edate', '-$amount', '".USER_DIV."'
			)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




# records for CT
function recordCT($amount, $supid,$edate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance < 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			$amount = ($amount * (-1));

  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$supid', '0', '$edate', '$amount', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "
			INSERT INTO suppurch (
				supid, purid, pdate, balance, div
			) VALUES (
				'$supid', '0', '$edate', '$amount', '".USER_DIV."'
			)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



?>

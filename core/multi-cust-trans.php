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


# trans-new.php :: debit-credit Transaction
#
##

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
			if (isset ($_POST['add_batch'])){
				$OUTPUT = add_to_batch ($_POST);
			}elseif (isset ($_POST['remove_entries'])) {
				$OUTPUT = remove_batch_entries ($_POST);
			}elseif (isset ($_POST['process_batch'])) {
				$OUTPUT = write ($_POST);
			}else {
				if(isset($_POST['details'])){
					$OUTPUT = details($_POST);
				}else{
					$OUTPUT = details2($_POST);
				}
			}
			break;
		case "import":
			$OUTPUT = import_csv_file($_POST);
			break;
		default:
			if (isset($_GET['cusnum'])){
				$OUTPUT = slctacc ($_GET);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	$OUTPUT = slctacc ($_GET);
}

# Get templete
require("template.php");




# Select Accounts
function slctacc($_GET, $err="")
{

	extract ($_GET);

	if (!isset($refnu))
		$refnum = getrefnum();

	# Select customer
	db_connect();

	$get_cust = "SELECT * FROM customers WHERE blocked = 'no'";
	$run_cust = db_exec ($get_cust) or errDie ("Unable to get customers information.");
	if (pg_numrows ($run_cust) < 1){
		return "No Valid Customers Found.";
	}

	$cust_drop = "<select name='cusnum'>";
	while ($carr = pg_fetch_array ($run_cust)){
		if (isset ($cusnum) AND $cusnum == $carr['cusnum']){
			$cust_drop .= "<option value='$carr[cusnum]' selected>$carr[accno] $carr[surname]</option>";
		}else {
			$cust_drop .= "<option value='$carr[cusnum]'>$carr[accno] $carr[surname]</option>";
		}
	}
	$cust_drop .= "</select>";

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
	$accounts .= "</select>";

	$entd = "";
	$entc = "checked=yes";
	if(isset($tran)){
		if($tran == "dt"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

	if (!isset($ct_year)){
		$ct_year = date ("Y");
		$ct_month = date ("m");
		$ct_day = date ("d");
	}

	db_connect ();

	$get_batch = "SELECT * FROM cust_trans_batch ORDER BY proc_date, cusnum";
	$run_batch = db_exec ($get_batch) or errDie ("Unable to get batch customer transaction information.");
	if (pg_numrows ($run_batch) < 1){
		$show_batch_listing = "
			<tr class='".bg_class()."'>
				<td colspan='9'>No entries Found.</td>
			</tr>";
	}else {
		$show_batch_listing = "";
		while ($barr = pg_fetch_array ($run_batch)){

			db_connect ();

			$get_cust = "SELECT accno, surname FROM customers WHERE cusnum = '$barr[cusnum]' LIMIT 1";
			$run_cust = db_exec ($get_cust) or errDie ("Unable to get customer information.");
			$showcusnum = "(".pg_fetch_result ($run_cust,0,0).") ".pg_fetch_result ($run_cust,0,1);

			if(isset($barr['chrg_vat']) AND $barr['chrg_vat'] != "0"){
				$get_vatcode = "SELECT vat_amount FROM vatcodes WHERE id = '$barr[vatcode]' LIMIT 1";
				$run_vatcode = db_exec($get_vatcode) or errDie ("Unable to get vat code information.");
				if(pg_numrows($run_vatcode) < 1){
					#vatcode not found .... 
					return "<li class='err'>Unable to get vat code information.</li>";
				}
				$vd = pg_fetch_array ($run_vatcode);
				if($barr['chrg_vat'] == "inc"){
					$vatamt = sprint(($barr['amount'])*($vd['vat_amount']/(100+$vd['vat_amount'])));
					$showamount = sprint ($barr['amount'] - $vatamt);
					$showvat = sprint ($vatamt)." (Inclusive)";
				}else {
					$showamount = sprint ($barr['amount']);
					$vatamt = ($barr['amount'] / 100) * $vd['vat_amount'];
					$showvat = sprint ($vatamt)." (Exclusive)";
				}
			}else {
				#vat not set
				$showamount = sprint ($barr['amount']);
				$showvat = CUR." ".sprint (0)." (No VAT)" ;
			}

			core_connect ();

			$get_acc = "SELECT accname FROM accounts WHERE accid = '$barr[contra_account]' LIMIT 1";
			$run_acc = db_exec ($get_acc) or errDie ("Unable to get account information.");
			$showaccount = pg_fetch_result ($run_acc,0,0);

			$show_batch_listing .= "
				<tr class='".bg_class()."'>
					<td>$showcusnum</td>
					<td>$barr[proc_date]</td>
					<td>$barr[ref_num]</td>
					<td>$barr[entry_type]</td>
					<td>$showaccount</td>
					<td>$showamount</td>
					<td>$showvat</td>
					<td><input type='checkbox' name='rem_trans[$barr[id]]' value='yes'></td>
					<td><input type='checkbox' name='proc_trans[$barr[id]]' value='yes' checked='yes'></td>
				</tr>";
			$totamount += $showamount;
			$totvatamt += $showvat;
		}
		$show_batch_listing .= "
			<tr>
				<td colspan='3'></td>
				<th colspan='2'>TOTALS:</td>
				<td nowrap class='".bg_class()."'>".CUR." $totamount</td>
				<td nowrap class='".bg_class()."'>".CUR." $totvatamt</td>
			</tr>
			<tr>
				<td colspan='3'></td>
				<th colspan='2'>TOTAL INC VAT</th>
				<td nowrap class='".bg_class()."' colspan='2'>".CUR." ".sprint ($totamount + $totvatamt)."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='9' align='right'><input type='submit' name='remove_entries' value='Remove Selected'></td>
			</tr>
			<tr>
				<td colspan='9' align='right'><input type='submit' name='process_batch' value='Process Selected Entries'></td>
			</tr>";
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


	// Accounts (debit)
	$view = "
		<h3>Multiple Journal Transaction </h3>
		<form action='".SELF."' method='POST' name='form0' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='import'>
			<input type='file' name='import_file'>
			<input type='submit' value='Import File'>
		</form>
		<form action='".SELF."' method='POST' name='form'>
			$err
			<input type='hidden' name='key' value='details'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td>$cust_drop</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("ct",$ct_year,$ct_month,$ct_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference Number</td>
				<td><input type='text' size='10' name='refnum' value='".($refnum++)."'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Entry Type</td>
				<td>
					<li class='err'>This will debit/credit the customer account selected</li>
					<input type='radio' name='entry' value='DT' $entd> Debit | <input type='radio' name='entry' value='CT' $entc>Credit</td>
			</tr>
			<tr class='".bg_class()."'>
				<td rowspan='2'>Contra Account</td>
				<td>$accounts <input name='details' type='submit' value='Enter Details'></td>
			</tr>
			<tr class='".bg_class()."'>
				<!--        Rowspan      -->
				<td><input type='text' name='accnum' size='20'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Charge VAT</td>
				<td>
					$vatcode_drop
					<input type='radio' name='vatinc' value='inc'> Inclusive 
					<input type='radio' name='vatinc' value='excl' checked='yes'> Exclusive 
					<input type='radio' name='vatinc' value='0'> No VAT
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='20' name='amount' value='$amount'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='40' rows='4' name='details'>$details</textarea></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' name='add_batch' value='Add To Batch'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts." width='90%'>
			<tr>
				<th>Customer Number</th>
				<th>Process Date</th>
				<th>Reference Number</th>
				<th>Entry Type</th>
				<th>Contra Account</th>
				<th>Amount</th>
				<th>VAT</th>
				<th>Remove</th>
				<th>Process</th>
			</tr>
			$show_batch_listing
			".TBL_BR."
		</table>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $view;

}




function add_to_batch ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($ct_day, "num", 1,2, "Invalid to Date ct_day.");
	$v->isOk ($ct_month, "num", 1,2, "Invalid to Date ct_monthth.");
	$v->isOk ($ct_year, "num", 1,4, "Invalid to Date Year.");
	$date = $ct_day."-".$ct_month."-".$ct_year;
	if(!checkdate($ct_month, $ct_day, $ct_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");
	$v->isOk ($vatinc, "string", 1, 10, "Invalid Charge VAT Option.");
	$v->isOk ($vatcode, "num", 1, 5, "Invalid Vat Code Option.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");



	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctacc($_POST,"$confirm<br>");
	}


	$ct_date = "$ct_year-$ct_month-$ct_day";

	db_connect ();

	$ins_sql = "
		INSERT INTO cust_trans_batch (
			cusnum, proc_date, ref_num, entry_type, contra_account, chrg_vat, vatcode, amount, description
		) VALUES (
			'$cusnum', '$ct_date', '$refnum', '$entry', '$accid', '$vatinc', '$vatcode', '$amount', '$details'
		)";
	$run_ins = db_exec ($ins_sql) or errDie ("Unable to record customer batch transaction.");

	return slctacc ();

}


function remove_batch_entries ($_POST)
{

	extract ($_POST);

	if (!isset ($rem_trans) OR !is_array ($rem_trans)) {
		return slctacc($_POST, "<li class='err'>Please Select Transaction(s) To Remove</li><br>");
	}


	db_connect ();

	foreach ($rem_trans AS $remid => $value){

		$rem_tran = "DELETE FROM cust_trans_batch WHERE id = '$remid'";
		$run_tran = db_exec ($rem_tran) or errDie ("Unable to remove transaction information.");

	}

	return slctacc ($_POST,"<li class='yay'>Selected Transaction(s) Have Been Removed.</li><br>");

}






# Write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	if (!isset ($proc_trans) OR !is_array ($proc_trans)) 
		return slctacc($_POST, "<li class='err'>Please Select Transaction(s) To Process</li>");

	db_connect ();

	# validate input
	require_lib("validate");
	$v = new  validate ();

	foreach ($proc_trans AS $procid => $value){

		$get_trans = "SELECT * FROM cust_trans_batch WHERE id = '$procid' LIMIT 1";
		$run_trans = db_exec ($get_trans) or errDie ("Unable to get transaction information.");
		if (pg_numrows ($run_trans) < 1){
			return slctacc ($_POST,"<li class='err'>Transaction Not Found: (ID:$procid)</li>");
		}

		$parr = pg_fetch_array ($run_trans);

		$v->isOk ($parr['cusnum'], "num", 1, 50, "Invalid Customer number.");
		$v->isOk ($parr['contra_account'], "num", 1, 50, "Invalid Contra Account.");
		$v->isOk ($parr['ref_num'], "num", 1, 10, "Invalid Reference number.");
		$v->isOk ($parr['amount'], "float", 1, 20, "Invalid Amount.");
		$v->isOk ($parr['description'], "string", 0, 255, "Invalid Details.");
// 		$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

		$datea = explode("-", $parr['proc_date']);
		if(count($datea) == 3){
			if(!checkdate($datea[1], $datea[2], $datea[0])){
				$v->isOk ($parr['proc_date'], "num", 1, 1, "Invalid date. (1)");
			}
		}else{
			$v->isOk ($parr['proc_date'], "num", 1, 1, "Invalid date. (2)");
		}
		$v->isOk ($parr['chrg_vat'], "string", 1, 10, "Invalid Charge VAT Option.");
	// 	$v->isOk ($vatinc, "string", 1, 10, "Invalid VAT Inclusive Exclusive Option.");
		$v->isOk ($parr['vatcode'], "num", 1, 5, "Invalid Vat Code Option.");
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
	foreach ($proc_trans AS $procid => $value){

		db_connect ();

		$get_trans = "SELECT * FROM cust_trans_batch WHERE id = '$procid' LIMIT 1";
		$run_trans = db_exec ($get_trans) or errDie ("Unable to get transaction information.");
		if (pg_numrows ($run_trans) < 1){
			return slctacc ($_POST,"<li class='err'>Transaction Not Found: (ID:$procid)</li>");
		}

		$parr = pg_fetch_array ($run_trans);

		$cusnum = $parr['cusnum'];
		$vatinc = $parr['chrg_vat'];
		if (isset ($vatinc) AND $vatinc != "0"){
			$chrgvat = "yes";
		}else {
			$chrgvat = "no";
		}
// 		$chrgvat = $parr['chrg_vat'];
		$vatcode = $parr['vatcode'];
		$amount = $parr['amount'];
		$type = 1;
		$entry = $parr['entry_type'];
		$date = $parr['proc_date'];
		$datea = explode("-", $parr['proc_date']);
		$accid = $parr['contra_account'];
		$refnum = $parr['ref_num'];
		$details = $parr['description'];

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
				$vatamt = sprint(($amount)*($vd['vat_amount']/(100+$vd['vat_amount'])));
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

// 		$date = "$datea[2]-$datea[1]-$datea[0]";

		# Accounts details
		$accRs = get("core","*","accounts","accid",$accid);
		$acc  = pg_fetch_array($accRs);

		# Select customer
		db_connect();

		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($custRslt) < 1){
			return slctacc($_POST,"<li class='err'>Invalid customer ID, or customer has been blocked.</li>");
		}else{
			$cust = pg_fetch_array($custRslt);
		}

		# Get department
		db_conn("exten");

		$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			return slctacc($_POST, "<i class='err'>Department Not Found</i>");
		}else{
			$dept = pg_fetch_array($deptRslt);
		}

		#get vat acc ...
		$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");

		# Begin updates
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Probe tran type
		if($entry == "CT"){
			# Write transaction  (debit contra account, credit debtors control)
			writetrans($accid, $dept['debtacc'], $date, $refnum, $amount, $details." - Customer $cust[cusname] $cust[surname]");
			$tran = "
				<tr class='".bg_class()."'>
				      <td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				      <td>$cust[accno] - $cust[cusname] $cust[surname]</td>
			      </tr>";
			$samount = ($amount - ($amount * 2));
			$svatamt = ($vatamt - ($vatamt * 2));
			recordCT($samount, $cust['cusnum'],$date);
			$type = 'c';
		
			if(isset($chrgvat) AND $chrgvat == "yes"){
				writetrans($vatacc, $dept['debtacc'],  $date, $refnum, $vatamt, "VAT for Transaction: $refnum for Customer : $cust[cusname] $cust[surname]");
				vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,"VAT for Transaction: $refnum for Customer : $cust[cusname] $cust[surname]",$samount+$svatamt,$svatamt);
			}
		}else{
			# Write transaction  (debit debtors control, credit contra account)
			writetrans($dept['debtacc'], $accid, $date, $refnum, $amount, $details." - Customer $cust[cusname] $cust[surname]");
			$tran = "
				<tr class='".bg_class()."'>
					<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
					<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				</tr>";
			$samount = $amount;
			$svatamt = $vatamt;
			recordDT($samount, $cust['cusnum'],$date);
			$type = 'd';

			if(isset($chrgvat) AND $chrgvat == "yes"){
				writetrans($dept['debtacc'], $vatacc, $date, $refnum, $vatamt, "VAT for Transaction: $refnum for Customer : $cust[cusname] $cust[surname]");
				vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,"VAT for Transaction: $refnum for Customer : $cust[cusname] $cust[surname]",$amount+$vatamt,$vatamt);
			}
		}

		db_connect();

		$sdate = date("Y-m-d");
		# record the payment on the statement
		$sql = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, type, st, div, allocation_date
			) VALUES (
				'$cust[cusnum]', '0', '".sprint($samount+$svatamt)."', '$date', '$details', 'n', '".USER_DIV."', '$date'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		$sql = "
			INSERT INTO open_stmnt (
				cusnum, invid, amount, balance, date, type, st, div
			) VALUES (
				'$cust[cusnum]', '0', '".sprint($samount+$svatamt)."', '".sprint($samount+$svatamt)."', '$date', '$details', 'n', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$samount') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

		# Make ledge record
	//	custledger($cust['cusnum'], $accid, $date, $refnum, $details, $amount, $type);
		custledger($cust['cusnum'], $accid, $date, $refnum, $details, sprint($amount+$vatamt), $type);

		db_connect ();

		$rem_batch = "DELETE FROM cust_trans_batch WHERE id = '$procid'";
		$run_batch = db_exec ($rem_batch) or errDie ("Unable to remove customer batch transaction information.");

		# Commit updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	}
	return slctacc($_POST,"<li class='yay'>Transaction(s) Have Been Processed.</li><br>");

}



# records for CT
function recordCT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance,div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
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
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



function import_csv_file ($_POST)
{

	extract ($_POST);

	$file = file ($_FILES['import_file']['tmp_name']);

	if (!is_array ($file) OR count($file) < 1)
		return slctacc (array(), "<li class='err'>Please Ensure File Format Is Correct.</li><br>");

	$refnum = getrefnum();
	foreach ($file AS $line) {

		$cleanline = trim ($line);

		if (strlen ($cleanline) < 8)
			continue;

		$line_arr = explode (",",$cleanline);

		db_connect ();

		$get_cusnum = "SELECT cusnum FROM customers WHERE accno = '$line_arr[0]' LIMIT 1";
		$run_cusnum = db_exec ($get_cusnum) or errDie ("Unable to get customer information.");
		$cusnum = pg_fetch_result ($run_cusnum,0,0);

// 		$refnum = $line_arr[3];

// 		$cusnum = $line_arr[0];

		$darr = explode ("/",$line_arr[7]);
		$date_day = $darr[1];
		$date_month = $darr[0];
		$date_year = "20".$darr[2];
		$date = "$date_year-$date_month-$date_day";

		$aarr = explode ("/",$line_arr[2]);
		$acc_topacc = $aarr[0];
		$acc_accnum = $aarr[1];

		$get_vatcode = "SELECT id FROM vatcodes WHERE code = '$line_arr[4]' LIMIT 1";
		$run_vatcode = db_exec ($get_vatcode) or errDie ("Unable to get vat code information.");
		$vatcode = pg_fetch_result ($run_vatcode,0,0);

		core_connect ();

		$get_acc = "SELECT accid FROM accounts WHERE topacc = '$acc_topacc' AND accnum = '$acc_accnum' LIMIT 1";
		$run_acc = db_exec ($get_acc) or errDie ("Unable to get contra account information.");
		$accid = pg_fetch_result ($run_acc,0,0);

		db_connect ();

		$ins_sql = "
			INSERT INTO cust_trans_batch (
				cusnum, proc_date, ref_num, entry_type, contra_account, chrg_vat, vatcode, amount, description
			) VALUES (
				'$cusnum', '$date', '$refnum', '$line_arr[5]', '$accid', '$line_arr[6]', '$vatcode','$line_arr[9]', '$line_arr[8]'
			)";
		$run_sql = db_exec ($ins_sql) or errDie ("Unable to record customer transaction.");
		$refnum++;

	}

	return slctacc (array (),"<li class='yay'>$numtrans Transaction(s) Has Been Imported.</li><br>");

}

?>
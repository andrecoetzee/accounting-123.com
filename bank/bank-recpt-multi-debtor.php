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

require("../settings.php");
require("../core-settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "selectcus":
			$OUTPUT = selectcus();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "alloc":
			$OUTPUT = alloc();
			break;
		case "confirm_alloc":
			$OUTPUT = confirm_alloc();
			break;
		case "write":
			$OUTPUT = write();
			break;
		case "method":
		default:
			$OUTPUT = method();
			break;
	}
} else {
	$OUTPUT = method();
}

require("../template.php");




function method($err = "")
{

	extract($_POST);

	$fields = array(
		"descript" => "",
		"cheqnum" => "",
		"amt" => "",
		"reference" => "",
		"date_day" => false
	);

	extract($fields, EXTR_SKIP);

//	if ($date_day === false) {
	if (!isset($date_day) OR strlen ($date_day) < 1) {
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			if (isset($_SESSION["global_day"]) AND strlen ($_SESSION["global_day"]) > 0) 
				$date_day = $_SESSION["global_day"];
			else 
				$date_day = date("d");
			if (isset($_SESSION["global_month"]) AND strlen ($_SESSION["global_month"]) > 0) 
				$date_month = $_SESSION["global_month"];
			else 
				$date_month = date("m");
			if (isset($_SESSION["global_year"]) AND strlen ($_SESSION["global_year"]) > 0) 
				$date_year = $_SESSION["global_year"];
			else 
				$date_year = date("Y");
		}
	}

	if (isset($_GET["e"])) {
		$ex = "<input type='hidden' name='e' value='y'>";
	} else {
		$ex = "";
	}

	if(!isset($cusid))
		$cusid = "";

	$OUT = "
		<h3>New Receipt</h3>
		$err
		<form action='".SELF."' method='post' name='form'>
		<table ".TMPL_tblDflts." width='450'>
			$ex
			<input type='hidden' name='key' value='selectcus'>
			<input type='hidden' name='cusid' value='$cusid'>
			<tr>
				<td colspan='2'><li class='err'>NOTE: This functionality will not automatically allocate received amounts to the oldest recorded invoices. If you would like to do that, <a href='multi-bank-recpt-inv.php'>please click here.</a> Or, alternatively use the \"Add Receipt\" functionality <a href='../customers-view.php'> here</a></li></tr>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Receipt Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Account / Cash</td>
				<td valign='center'>
					<select name='bankid'>";

	$sql = "SELECT * FROM cubit.bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$rslt = db_exec($sql);

	if(pg_num_rows($rslt) < 1){
		return "<li class='err'>There are no accounts held at the selected Bank.</li>";
	}

	while($acc = pg_fetch_array($rslt)){
		$OUT .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	if(isset($_GET['cash'])) {
		$OUT .= "<option value='0'>Receive Cash</option>";
	}

	if(!isset($all))
		$all = "";

	$as1 = "";
	$as2 = "";
	$as3 = "";

	if ($all == 0) {
		$as1 = "selected";
	} else if($all == 1) {
		$as2 = "selected";
	} else if ($all == 2) {
		$as3 = "selected";
	}

	if (isset($cusids)) {
		foreach ($cusids as $k => $cusid) {
			$OUT .= "
			<input type='hidden' name='cusids[$k]' value='$cusid' />
			<input type='hidden' name='amts[$k]' value='$amts[$k]' />";
		}
	}

//	$alls = "
//	<select name='all'>
//		<option value='0' $as1>Auto</option>
//		<option value='1' $as2>Allocate To Age Analysis</option>
//		<option value='2' $as3>Allocate To Each invoice</option>
//	</select>";
//
//	<tr class='".bg_class()."'>
//		<td>Allocation</td>
//		<td>$alls</td>
//	</tr>

	$OUT .= "
					</select>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference</td>
				<td valign='center'><input size='25' name='reference' value='$reference'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Cheque Number</td>
				<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='13' name='amt' value='$amt'></td>
			</tr>
			<input type='hidden' name='all' value='0'>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center' align='right'><input type='submit' value='Allocate >'></td>
			</tr>
		</table>
		</form>";

	$OUT .=
	mkQuickLinks(
		ql("trans-new.php", "Journal Transactions"),
		ql("../customers-view.php", "View Customers (New Window)", true)
	);
	return $OUT;

}




function selectcus($err = "")
{

	extract($_POST);

	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 1, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");

	if ($all == 0) {
		$all_desc = "Auto Allocation";
	} else if ($all == 1) {
		$all_desc = "Allocate to Age Analysis";
	} else if ($all == 2) {
		$all_desc = "Allocate to Each Invoice";
	} else {
		$v->addError("", "Invalid allocation method selected.");
	}

	if ($v->isError()) {
		return method($v->genErrors());
	}

	$date = mkdate($date_year, $date_month, $date_day);

	if (!isset($cusids)) {
		$cusids = array();
		$amts = array();
	}

	/* remove selected allocations */
	if (isset($rem)) {
		foreach ($rem as $k => $dummy) {
			if (isset($cusids[$k])) {
				unset($cusids[$k]);
				unset($amts[$k]);
			}
		}
	}

	/* add the newly entered amount to the array */
	if (isset($btn_new)) {
		if ($new_amt != "0.00" && !empty($new_amt) &&
				$v->isOk($new_amt, "float", 1, 1, "") && $v->isOk($new_cus, "num", 1, 10, "")) {
			$cusids[] = $new_cus;
			$amts[] = $new_amt;

			$new_cus = false;
			$new_amt = "0.00";
		} else {
			$err = "<li class='err'>Invalid value entered/selected</li>";
		}
	}

	if (sprint(array_sum($amts)) > sprint($amt)) {
		$err = "<li class='err'>Sum of debtor amounts exceeds receipt amount</li>";
	}

	if (isset($_POST["btn_back"])) {
		return method();
	}

	if (empty($err) && (isset($btn_new) || isset($btn_update))) {
		if (sprint(array_sum($amts)) == sprint($amt)) {
			$_POST["amts"] = $amts;
			$_POST["cusids"] = $cusids;
			return confirm();
		}
	}

	if(!isset($new_cus))
		$new_cus = "";

	if(!isset($new_amt))
		$new_amt = "";

	db_connect ();

	#get bank account info
	$get_bank = "SELECT * FROM bankacct WHERE bankid = '$bankid' LIMIT 1";
	$run_bank = db_exec($get_bank) or errDie ("Unable to get banking information.");
	if (pg_numrows($run_bank) < 1){
		$bank = array ();
		$bank['accname'] = "";
		$bank['bankname'] = "";
	}else {
		$bank = pg_fetch_array ($run_bank);
	}

	$qry = qryCustomer();
	$sel_cust = db_mksel($qry, "new_cus", $new_cus, "#cusnum", "#surname, #cusname");

	$OUT = "
		<h3>New Bank Receipt</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='selectcus'>
			<input type='hidden' name='accnum' value='' />
			<input type='hidden' name='bankid' value='$bankid' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='all' value='$all' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='descript' value='$descript' />
			<input type='hidden' name='reference' value='$reference' />
			<input type='hidden' name='cheqnum' value='$cheqnum' />
			<input type='hidden' name='amt' value='$amt' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Receipt Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
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
				<td>Allocation</td>
				<td valign='center'>$all_desc</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount Unallocated</td>
				<td valign='center'>".CUR." ".sprint($amt-array_sum($amts))."</td>
			</tr>
		</table>
		<br />
		$err
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='3' align='right'>
					<input type='submit' name='btn_back' value='&laquo; Back' />
					| <input type='submit' name='btn_update' value='Update' />
				</td>
			</tr>
			<tr>
				<th>Customer</th>
				<th>Amount</th>
				<th>Remove</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$sel_cust</td>
				<td><input type='text' size='6' name='new_amt' value='$new_amt' />
				<td><input type='submit' name='btn_new' value='Add &raquo;' /></td>
			</tr>";

	foreach ($cusids as $k => $cusid) {
		$ci = qryCustomer($cusid);

		$OUT .= "
			<input type='hidden' name='cusids[$k]' value='$cusid' />
			<tr class='".bg_class()."'>
				<td>$ci[surname], $ci[cusname]</td>
				<td><input type='text' size='6' name='amts[$k]' value='$amts[$k]' />
				<td><input type='checkbox' name='rem[$k]' /></td>
			</tr>";
	}

	$OUT .= "
			<tr>
				<td colspan='3' align='right'>
					<input type='submit' name='btn_back' value='&laquo; Back' />
					| <input type='submit' name='btn_update' value='Update' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers (New Window)", true)
		);
	return $OUT;

}




function confirm($err="")
{

	extract($_POST);

	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 1, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");

	if ($all == 0) {
		$all_desc = "Auto Allocation";
	} else if ($all == 1) {
		$all_desc = "Allocate to Age Analysis";
	} else if ($all == 2) {
		$all_desc = "Allocate to Each Invoice";
	} else {
		$v->addError("", "Invalid allocation method selected.");
	}

	if ($v->isError()) {
		return method($v->genErrors());
	}

	foreach ($cusids as $k => $cusid) {
		if ($v->isOk($cusid, "num", 1, 10, "An invalid customer was selected.")) {
			$c = qryCustomer($cusid);
			$v->isOk($amts[$k], "float", 1, 40, "Invalid amount for customer: $c[surname], $c[cusname]");
		}
	}

	if ($v->isError()) {
		return selectcus($v->genErrors());
	}

	if (sprint(array_sum($amts)) != sprint($amt)) {
		return selectcus("<li class='err'>Sum of customer amounts does not equal receipt amount.</li>");
	}

	$date = mkdate($date_year, $date_month, $date_day);

	db_connect ();

	#get bank account info
	$get_bank = "SELECT * FROM bankacct WHERE bankid = '$bankid' LIMIT 1";
	$run_bank = db_exec($get_bank) or errDie ("Unable to get banking information.");
	if (pg_numrows($run_bank) < 1){
		$bank = array ();
		$bank['accname'] = "";
		$bank['bankname'] = "";
	}else {
		$bank = pg_fetch_array ($run_bank);
	}

	$OUT = "
		<h3>New Bank Receipt</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='alloc'>
			<input type='hidden' name='accnum' value='' />
			<input type='hidden' name='bankid' value='$bankid' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='all' value='$all' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='descript' value='$descript' />
			<input type='hidden' name='reference' value='$reference' />
			<input type='hidden' name='cheqnum' value='$cheqnum' />
			<input type='hidden' name='amt' value='$amt' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Receipt Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
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
				<td valign='center'>".CUR." $amt</td>
			</tr>
			</table>
			<br />
			$err
			<table ".TMPL_tblDflts.">
				<tr>
					<td colspan='3' align='right'>
						<input type='submit' name='cbtn_back' value='&laquo; Back' />
						| <input type='submit' name='cbtn_update' value='Allocate' />
					</td>
				</tr>
				<tr>
					<th>Customer</th>
					<th>Amount</th>
				</tr>";

	foreach ($cusids as $k => $cusid) {
		$ci = qryCustomer($cusid);

		$OUT .= "
			<input type='hidden' name='cusids[$k]' value='$cusid' />
			<input type='hidden' name='amts[$k]' value='$amts[$k]' />
			<tr class='".bg_class()."'>
				<td>$ci[surname], $ci[cusname]</td>
				<td>".CUR." $amts[$k]</td>
			</tr>";
	}

	$OUT .= "
			<tr>
				<td colspan='3' align='right'>
					<input type='submit' name='cbtn_back' value='&laquo; Back' />
					| <input type='submit' name='cbtn_update' value='Allocate' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers (New Window)", true)
		);
	return $OUT;

}




function alloc($err = "")
{

	if (isset($_POST["cbtn_back"])) {
		return selectcus();
	}

	extract($_POST);

	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 1, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");

	if ($all == 0) {
		$all_desc = "Auto Allocation";
	} else if ($all == 1) {
		$all_desc = "Allocate to Age Analysis";
	} else if ($all == 2) {
		$all_desc = "Allocate to Each Invoice";
	} else {
		$v->addError("", "Invalid allocation method selected.");
	}

	if ($v->isError()) {
		return method($v->genErrors());
	}

	foreach ($cusids as $k => $cusid) {
		if ($v->isOk($cusid, "num", 1, 10, "An invalid customer was selected.")) {
			$c = qryCustomer($cusid);
			$v->isOk($amts[$k], "float", 1, 40, "Invalid amount for customer: $c[surname], $c[cusname]");
		}
	}

	if ($v->isError()) {
		return selectcus($v->genErrors());
	}

	if (sprint(array_sum($amts)) != sprint($amt)) {
		return selectcus("<li class='err'>Sum of customer amounts does not equal receipt amount.</li>");
	}

	db_connect ();

	#get bank account info
	$get_bank = "SELECT * FROM bankacct WHERE bankid = '$bankid' LIMIT 1";
	$run_bank = db_exec($get_bank) or errDie ("Unable to get banking information.");
	if (pg_numrows($run_bank) < 1){
		$bank = array ();
		$bank['accname'] = "";
		$bank['bankname'] = "";
	}else {
		$bank = pg_fetch_array ($run_bank);
	}

	$OUT = "
		<script>
			function updateAgeTot(cusid) {
				tot = (i = parseFloat(getObject('out1[' + cusid + ']').value)) ? i : 0;
				tot += (i = parseFloat(getObject('out2[' + cusid + ']').value)) ? i : 0;
				tot += (i = parseFloat(getObject('out3[' + cusid + ']').value)) ? i : 0;
				tot += (i = parseFloat(getObject('out4[' + cusid + ']').value)) ? i : 0;
				tot += (i = parseFloat(getObject('out5[' + cusid + ']').value)) ? i : 0;
				getObject('agetot[' + cusid + ']').innerHTML = '".CUR." ' + tot.toFixed(2);
			}
		</script>
		<h3>New Bank Receipt</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm_alloc'>
			<input type='hidden' name='accnum' value='' />
			<input type='hidden' name='bankid' value='$bankid' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='all' value='$all' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='descript' value='$descript' />
			<input type='hidden' name='reference' value='$reference' />
			<input type='hidden' name='cheqnum' value='$cheqnum' />
			<input type='hidden' name='amt' value='$amt' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Receipt Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
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
				<td valign='center'>".CUR." $amt</td>
			</tr>
		</table>
		<br />
		$err
		<table ".TMPL_tblDflts.">
			<tr>
				<td align='right'>
					<input type='submit' name='abtn_back' value='&laquo; Back' />
					| <input type='submit' name='abtn_update' value='Process' />
				</td>
			</tr>";

	$cust_arr = array ();
	foreach ($cusids as $k => $cusid) {
		$ci = qryCustomer($cusid);

		$x = cusalloc($all, $k, $cusid, $amts[$k], $cust_arr);

		if ($x[0] === false) {
			return selectcus("<li class='err'>$x[1]</li>");
		}

		$OUT .= "
			<tr>
				<td><h2>$ci[surname], $ci[cusname]</h2></td>
			</tr>
			<tr>
				<td>$x[1]</td>
			</tr>"
			.TBL_BR;
	}

	$OUT .= "
			<tr>
				<td align='right'>
					<input type='submit' name='abtn_back' value='&laquo; Back' />
					| <input type='submit' name='atn_update' value='Process' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers (New Window)", true)
		);
	return $OUT;

}




function cusalloc($all, $k, $cusid, $totamt, &$cust_arr)
{

	$custinfo = qryCustomer($cusid);

	$cus0 = $custinfo['cusnum'];
	$amt = $totamt;

	$OUT = "
		<input type='hidden' name='cusids[$k]' value='$cusid' />
		<input type='hidden' name='amts[$k]' value='$totamt' />";

	$OUT .= "<table ".TMPL_tblDflts.">";
		
	if ($all != 0) {
		$OUT .= "
			<tr>
				<th colspan='10'>Amount to Allocate: ".CUR." $amt</th>
			</tr>";
	}

	/* OPTION 1 : AUTO ALLOCATE (allocate) */
	if ($all == 0) {

		/* stock invoices */
		$sql = "
			SELECT invnum,invid,balance,terms,odate FROM cubit.invoices 
			WHERE cusnum = '$cusid' AND printed = 'y' AND balance > 0 AND div = '".USER_DIV."' 
			ORDER BY odate ASC";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) > 0) {
			$OUT .= "
				<tr>
					<td colspan='2'><h3>Outstanding Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th>Terms</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}

		$i = 0;
		while (($inv = pg_fetch_array($rslt)) && ($amt > 0)) {
			$invid = $inv['invid'];

			if (in_array ($invid, $cust_arr[$cus0])) {
				continue;
			}else {
				$val = allocamt($amt, $inv["balance"]);
				if ($val > 0) 
					$cust_arr[$cus0][] = $invid;
				else 
					continue;
			}

//			$val = allocamt($amt, $inv["balance"]);

			$OUT .= "
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td>$inv[terms] days</td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* non stock invoices */
		$sql = "
			SELECT invnum, invid, balance, sdate AS odate 
			FROM cubit.nons_invoices 
			WHERE cusid='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."' 
			ORDER BY odate ASC";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) > 0) {
			$OUT .= "
				<tr>
					<td colspan='2'><h3>Outstanding Non-Stock Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th></th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}

		$i = 0;
		while(($inv = pg_fetch_array($rslt)) && ($amt > 0)) {
			$invid = $inv['invid'];

			if (in_array ($invid, $cust_arr[$cus0])) {
				continue;
			}else {
				$val = allocamt($amt, $inv["balance"]);
				if ($val > 0) 
					$cust_arr[$cus0][] = $invid;
				else 
					continue;
			}

//			$val = allocamt($amt, $inv["balance"]);

			$OUT .= "
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='itype[$k][$invid]' value='Yes' />
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td>&nbsp;</td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* pos invoices */
		$sqls = array();
		for ($i = 1; $i <= 12; ++$i) {
			$sqls[] = "
				SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices 
				WHERE cusnum='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."'";
		}
		$sql = implode(" UNION ", $sqls);
		$rslt = db_exec($sql);

		if(pg_num_rows($rslt) > 0) {
			$OUT .= "
				<tr>
					<td colspan='2'><h3>Outstanding POS Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th></th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}

		$i = 0;
		while($inv = pg_fetch_array($rslt)){
			$invid = $inv['invid'];

			if (in_array ($invid, $cust_arr[$cus0])) {
				continue;
			}else {
				$val = allocamt($amt, $inv["balance"]);
				if ($val > 0) 
					$cust_arr[$cus0][] = $invid;
				else 
					continue;
			}

//			$val = allocamt($amt, $inv["balance"]);

			$OUT .= "
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='ptype[$k][$invid]' value='YnYn' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td></td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* open items */
		if (sprint($amt) > 0) {
			$ox = "";
			$sql = "
				SELECT * FROM cubit.open_stmnt 
				WHERE balance>0 AND cusnum='$cusid' AND type!='Invoice' AND type!='Non-Stock Invoice' 
					AND type!='Interest on Outstanding balance' 
				ORDER BY date";
			$rslt = db_exec($sql) or errDie("Unable to get open items.");

			$open_out = $amt;
			$i = 0;
			while ($od = pg_fetch_array($rslt)) {
				if ($open_out == 0) {
					continue;
				}
				$bgColor = bgcolor($i);
				$oid = $od['id'];
				if($open_out >= $od['balance']) {
					$open_out = sprint($open_out - $od['balance']);

					$ox .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' size='20' name='open[$k][$oid]' value='$oid'>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td><input type='hidden' name='open_amount[$k][$oid]' value='$od[balance]'>".CUR." $od[balance]</td>
						</tr>";
				} elseif($open_out<$od['balance']) {
					$tmp = $open_out;
					$open_out = 0;

					$ox .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' name='open[$k][$oid]' value='$od[id]'>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td><input type='hidden' name='open_amount[$k][$oid]' value='$tmp'>".CUR." $tmp</td>
						</tr>";
				}
			}

			if (open()) {
				$OUT .= "
					<input type='hidden' name='bout[$k]' value='$amt'>
					<tr>
						<td colspan='2'><h3>Outstanding Transactions</h3></td>
					</tr>
					<tr>
						<th>Description</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

				$OUT .= $ox;

				$bout = $amt;
				$amt = $open_out;
				if(sprint($amt) > 0) {
					$OUT .="
						<tr class='".bg_class()."'>
							<td colspan='4'><b>A general transaction will credit the client's
								account with ".CUR." ".sprint($amt)."</b>
							</td>
						</tr>";
				}
			} else {
				$amt = sprint ($amt);
				$OUT .= "
					<tr class='".bg_class()."'>
						<td colspan='4'><b>A general transaction will credit the client's
							account with ".CUR." $amt</b>
						</td>
					</tr>";
			}
		}
	}

	vsprint($amt);

	$OUT .= "<input type='hidden' name='out[$k]' value='$amt'>";
	$OUT .= "</table>";

	return array($amt, $OUT);

}




function confirm_alloc()
{

	if (isset($_POST["abtn_back"])) {
		return selectcus();
	}

	extract($_POST);

	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 1, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");

	if ($all == 0) {
		$all_desc = "Auto Allocation";
	} else if ($all == 1) {
		$all_desc = "Allocate to Age Analysis";
	} else if ($all == 2) {
		$all_desc = "Allocate to Each Invoice";
	} else {
		$v->addError("", "Invalid allocation method selected.");
	}

	if ($v->isError()) {
		return method($v->genErrors());
	}

	foreach ($cusids as $k => $cusid) {
		if ($v->isOk($cusid, "num", 1, 10, "An invalid customer was selected.")) {
			$c = qryCustomer($cusid);
			$v->isOk($amts[$k], "float", 1, 40, "Invalid amount for customer: $c[surname], $c[cusname]");
		}
	}

	if ($v->isError()) {
		return selectcus($v->genErrors());
	}

	if (sprint(array_sum($amts)) != sprint($amt)) {
		return selectcus("<li class='err'>Sum of customer amounts does not equal receipt amount.</li>");
	}

	db_connect ();

	#get bank account info
	$get_bank = "SELECT * FROM bankacct WHERE bankid = '$bankid' LIMIT 1";
	$run_bank = db_exec($get_bank) or errDie ("Unable to get banking information.");
	if (pg_numrows($run_bank) < 1){
		$bank = array ();
		$bank['accname'] = "";
		$bank['bankname'] = "";
	}else {
		$bank = pg_fetch_array ($run_bank);
	}

	$OUT = "
		<h3>New Bank Receipt</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='accnum' value='' />
			<input type='hidden' name='bankid' value='$bankid' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='all' value='$all' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='descript' value='$descript' />
			<input type='hidden' name='reference' value='$reference' />
			<input type='hidden' name='cheqnum' value='$cheqnum' />
			<input type='hidden' name='amt' value='$amt' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Receipt Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
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
				<td valign='center'>".CUR." $amt</td>
			</tr>
		</table>
		<br />
		<table ".TMPL_tblDflts.">
			<tr>
				<td align='right'>
					<input type='submit' name='cabtn_back' value='&laquo; Back' />
					| <input type='submit' name='cabtn_update' value='Process' />
				</td>
			</tr>";

	foreach ($cusids as $k => $cusid) {
		$ci = qryCustomer($cusid);

		$x = confirm_cusalloc($all, $k, $cusid, $amts[$k]);
		
		if (!is_array($x)) {
			return $x;
		}

		if ($x[0] === false) {
			return alloc("<li class='err'>$x[1]</li>");
		}

		$OUT .= "
			<tr>
				<td><h2>$ci[surname], $ci[cusname]</h2></td>
			</tr>
			<tr>
				<td>$x[1]</td>
			</tr>"
			.TBL_BR;
	}

	$OUT .= "
			<tr>
				<td align='right'>
					<input type='submit' name='cabtn_back' value='&laquo; Back' />
					| <input type='submit' name='cabtn_update' value='Process' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers (New Window)", true)
		);
	return $OUT;

}




function confirm_cusalloc($all, $k, $cusid, $totamt)
{

	$custinfo = qryCustomer($cusid);

	$OUT = "
		<input type='hidden' name='cusids[$k]' value='$cusid' />
		<input type='hidden' name='amts[$k]' value='$totamt' />";

	$OUT .= "<table ".TMPL_tblDflts.">";

	/* OPTION 1 : AUTO ALLOCATE (allocate)
	   OPTION 3 : ALLOCATE TO EACH INVOICE (allocate) */
	if ($all == 0 || $all == 2) {

		$amt = $totamt;

		/* stock invoices */
		$sql = "
			SELECT invnum,invid,balance,terms,odate FROM cubit.invoices 
			WHERE cusnum = '$cusid' AND printed = 'y' AND balance > 0 AND div = '".USER_DIV."' 
			ORDER BY odate ASC";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) > 0) {
			$OUT .= "
				<tr>
					<td colspan='2'><h3>Outstanding Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th>Terms</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}

		$i = 0;
		while (($inv = pg_fetch_array($rslt)) && ($amt > 0)) {
			$invid = $inv['invid'];
			
			if (!isset($_POST["invids"][$k][$invid])) {
				continue;
			}

			$amt -= $val = $_POST["paidamt"][$k][$invid];

			if ($val > $inv["balance"]) {
				return array(false, "Amount of ".CUR." $val exceeds
					invoice balance for invoice number $inv[invnum],
					customer: $custinfo[surname], $custinfo[cusname].");
			}

			$OUT .= "
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td>$inv[terms] days</td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* non stock invoices */
		$sql = "
			SELECT invnum,invid,balance,sdate as odate FROM cubit.nons_invoices 
			WHERE cusid='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."' 
			ORDER BY odate ASC";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) > 0) {
			$OUT .= "
				<tr>
					<td colspan='2'><h3>Outstanding Non-Stock Invoices</h3></td>
				</tr>
				<tr>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th></th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}

		$i = 0;
		while(($inv = pg_fetch_array($rslt)) && ($amt > 0)) {
			$invid = $inv['invid'];
			
			if (!isset($_POST["invids"][$k][$invid])) {
				continue;
			}

			$amt -= $val = $_POST["paidamt"][$k][$invid];

			if ($val > $inv["balance"]) {
				return array(false, "Amount of ".CUR." $val exceeds
					invoice balance for invoice number $inv[invnum],
					customer: $custinfo[surname], $custinfo[cusname].");
			}

			$OUT .= "
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='itype[$k][$invid]' value='Yes' />
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td>&nbsp;</td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* pos invoices */
		$sqls = array();
		for ($i = 1; $i <=12; ++$i) {
			$sqls[] = "
				SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices 
				WHERE cusnum='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."'";
		}
		$sql = implode(" UNION ", $sqls);
		$rslt = db_exec($sql);

		if(pg_num_rows($rslt) > 0) {
			$OUT .= "
			<tr>
				<td colspan='2'><h3>Outstanding POS Invoices</h3></td>
			</tr>
			<tr>
				<th>Invoice</th>
				<th>Outstanding Amount</th>
				<th></th>
				<th>Date</th>
				<th>Amount</th>
			</tr>";
		}

		$i = 0;
		while($inv = pg_fetch_array($rslt)){
			$invid = $inv['invid'];
			
			if (!isset($_POST["invids"][$k][$invid])) {
				continue;
			}

			$amt -= $val = $_POST["paidamt"][$k][$invid];

			if ($val > $inv["balance"]) {
				return array(false, "Amount of ".CUR." $val exceeds
					invoice balance for invoice number $inv[invnum],
					customer: $custinfo[surname], $custinfo[cusname].");
			}

			$OUT .= "
				<input type='hidden' name='invids[$k][$invid]' value='$inv[invid]' />
				<input type='hidden' name='paidamt[$k][$invid]' value='$val' />
				<input type='hidden' name='ptype[$k][$invid]' value='YnYn' />
				<tr bgcolor='".bgcolor($i)."'>
					<td>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td></td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
		}

		/* open items */
		if (sprint($amt) > 0) {
			$ox = "";
			$sql = "
				SELECT * FROM cubit.open_stmnt 
				WHERE balance>0 AND cusnum='$cusid' AND type!='Invoice' AND type!='Non-Stock Invoice' 
					AND type!='Interest on Outstanding balance' 
				ORDER BY date";
			$rslt = db_exec($sql) or errDie("Unable to get open items.");

			$open_out = $amt;
			$i = 0;
			while ($od = pg_fetch_array($rslt)) {
				if ($open_out == 0) {
					continue;
				}
				$bgColor = bgcolor($i);
				$oid = $od['id'];

				$amt -= $val = $_POST["open_amount"][$k][$oid];

				$ox .= "
					<input type='hidden' name='open_amount[$k][$oid]' value='$val'>
					<input type='hidden' name='open[$k][$oid]' value='$oid'>
					<tr class='".bg_class()."'>
						<td>$od[type]</td>
						<td>".CUR." $od[balance]</td>
						<td>$od[date]</td>
						<td>".CUR." $val</td>
					</tr>";
			}

			if (open()) {
				$OUT .= "
					<input type='hidden' name='bout[$k]' value='$amt'>
					<tr>
						<td colspan='2'><h3>Outstanding Transactions</h3></td>
					</tr>
					<tr>
						<th>Description</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
					</tr>";

				$OUT .= $ox;

				$bout = $amt;
				$amt = $open_out;
				if (sprint($amt) > 0) {
					$amt = sprint ($amt);
					$OUT .= "
						<tr class='".bg_class()."'>
							<td colspan='5'><b>A general transaction will credit the client's account with ".CUR." $amt</b></td>
						</tr>";
				}
			} else {
				$amt = sprint ($amt);
				$OUT .= "
					<tr class='".bg_class()."'>
						<td colspan='5'><b>A general transaction will credit the client's account with ".CUR." $amt</b></td>
					</tr>";
			}
		}
	}

	vsprint($amt);

	$OUT .= "<input type='hidden' name='out[$k]' value='$amt'>";

	$OUT .= "</table>";

	return array($amt, $OUT);

}




/* write function */
function write()
{

	extract($_POST);

	if (isset($cabtn_back)) {
		return alloc();
	}

	require_lib("validate");
	$v = new  validate ();
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($date, "date", 1, 14, "Invalid Date.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");

	foreach ($cusids as $k => $cusid) {
		$v->isOk($out[$k], "float", 1, 40, "Invalid outstanding amount.");
		$v->isOk($amts[$k], "float", 1, 40, "Invalid amount.");
		$v->isOk($cusid, "num", 1, 40, "Invalid customer number.");

		if (isset($out1[$k])) {
			$v->isOk($out1[$k], "float", 0, 40, "Invalid paid amount(currant).");
			$v->isOk($out2[$k], "float", 0, 40, "Invalid paid amount(30).");
			$v->isOk($out3[$k], "float", 0, 40, "Invalid paid amount(60).");
			$v->isOk($out4[$k], "float", 0, 40, "Invalid paid amount(90).");
			$v->isOk($out5[$k], "float", 0, 40, "Invalid paid amount(120).");
		}

		if (isset($invids[$k])) {
			foreach($invids[$k] as $key => $value){
				$v->isOk ($invids[$k][$key], "num", 1, 50, "Invalid Invoice No.");
				$v->isOk ($paidamt[$k][$key], "float", 1, 40, "Invalid amount to be paid.");
			}
		}
	}

	if ($v->isError()) {
		return alloc($v->genErrors());
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	/* get bank account id */
	if(($bank_acc = getbankaccid($bankid)) === false) {
		$sql = "SELECT accid FROM core.accounts WHERE accname='Cash on Hand'";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) < 1) {
			if ($bankid == 0) {
				return "There is no 'Cash on Hand' account, there was one, but
						its not there now, you must have deleted it, if you want
						to use cash functionality please create a 'Cash on Hand' account.";
			} else {
				return "Invalid bank acc.";
			}
		}

		$bank_acc = pg_fetch_result($rslt, 0);
	}

	$date_arr = explode ("-",$date);
	$_SESSION["global_day"] = $date_arr[2];
	$_SESSION["global_month"] = $date_arr[1];
	$_SESSION["global_year"] = $date_arr[0];

	pglib_transaction("BEGIN");
	
	$cheqnum += 0;
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";
	$deptacc = array();

	foreach ($cusids as $k => $cusid) {
		if(!isset($invids[$k]))
			$invids[$k] = array ();
		if(!isset($paidamt[$k]))
			$paidamt[$k] = array();
		$vars = array(
					"bankid" => $bankid,
					"bank_acc" => $bank_acc,
					"date" => $date,
					"descript" => $descript,
					"reference" => $reference,
					"cheqnum" => $cheqnum,
					"out1" => isset($out1[$k]) ? $out1[$k] : 0,
					"out2" => isset($out2[$k]) ? $out2[$k] : 0,
					"out3" => isset($out3[$k]) ? $out3[$k] : 0,
					"out4" => isset($out4[$k]) ? $out4[$k] : 0,
					"out5" => isset($out5[$k]) ? $out5[$k] : 0,
					"amt" => $amts[$k],
					"out" => $out[$k],
					"cusid" => $cusid,
					"invids" => $invids[$k],
					"paidamt" => $paidamt[$k],
					"itype" => isset($itype[$k]) ? $itype[$k] : array(),
					"ptype" => isset($ptype[$k]) ? $ptype[$k] : array(),
					"all" => $all
				);

		$x = write_cus($vars);

		$rinvids .= $x["rinvids"];
		$amounts .= $x["amounts"];
		$invprds .= $x["invprds"];
		$rages .= $x["rages"];
		$deptacc[$x["deptacc"]] = $x["deptacc"];
	}

	if (count($deptacc) == 1) {
		$ledgeracc_col = "accinv";
		$pfxhack = "";
	} else {
		$ledgeracc_col = "accids";
		$pfxhack = "|";
	}

	if(!isset($cus['cusname']))
		$cus['cusname'] = "";

	if(!isset($cus['surname']))
		$cus['surname'] = "";

	if(!isset($invidsers))
		$invidsers = "";

	$cols = grp(
		m("bankid", $bankid),
		m("trantype", "deposit"),
		m("date", $date),
		m("name", "$cus[cusname] $cus[surname]"),
		m("descript", "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]"),
		m("cheqnum", $cheqnum),
		m("amount", $amt),
		m("banked", "no"),
		m($ledgeracc_col, $pfxhack.implode("|", $deptacc)),
		m("cusnum", "-1"),
		m("rinvids", $rinvids),
		m("amounts", $amounts),
		m("invprds", $invprds),
		m("rages", $rages),
		m("multicusnum", implode(",", $cusids)),
		m("multicusamt", implode(",", $amts)),
		m("reference", $reference),
		m("div", USER_DIV)
	);

	$dbobj = new dbUpdate("cashbook", "cubit", $cols);
	$dbobj->run(DB_INSERT);
	$dbobj->free();

	pglib_transaction("COMMIT");

	$OUT = "
		<center>
	    <table ".TMPL_tblDflts.">
		    <tr>
		    	<th>Bank Receipt</th>
		    </tr>
		    <tr class='".bg_class()."'>
		    	<td>Bank Receipt added to cash book.</td>
		    </tr>
	    </table>
	    <br />"
		.mkQuickLinks(
			ql("bank-pay-add.php", "Add Bank Payment"),
			ql("bank-recpt-add.php", "Add Bank Receipt"),
			ql("bank-recpt-inv.php", "Add Customer Payment"),
			ql("cashbook-view.php", "View Cash Book")
		);
	return $OUT;

}




function write_cus($vars)
{

	extract($vars);

	$cus = qryCustomer($cusid, "cusnum, deptid, cusname, surname");
	$dept = qryDepartment($cus["deptid"], "debtacc");
	$refnum = getrefnum();

	# date format
	$sdate = $date;
	$cheqnum = 0 + $cheqnum;
	$pay = "";
	$accdate = $sdate;

	/* Paid invoices */
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";

	/* OPTION 1 : AUTO ALLOCATE (write) */
	if ($all == 0) {
		# update the customer (make balance less)
		$sql = "UPDATE cubit.customers SET balance = (balance - '$amt'::numeric(13,2))
				WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		if (isset($invids)) {
			foreach($invids as $key => $value) {
				$ii = $invids[$key];
				/* OPTION 1: STOCK INVOICES */
				if (!isset($itype[$ii]) && !isset($ptype[$ii])) {
					$sql = "SELECT prd,invnum,odate FROM cubit.invoices
							WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$inv = pg_fetch_array($invRslt);

					$inv['invnum'] += 0;

					// reduce invoice balance
					$sql = "UPDATE cubit.invoices
							SET balance = (balance - '$paidamt[$key]'::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - '$paidamt[$key]'::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, amount, 
							date, type, div, allocation_date
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', '".($paidamt[$key] - ($paidamt[$key] * 2))."', 
							'$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");
					//recordCT($paidamt[$key], $cus['cusnum'],0,$inv["odate"]);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";

					if ($inv['prd'] == "0") {
						$inv['prd'] = PRD_DB;
					}

					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				/* OPTION 1: NONS STOCK INVOICES */
				} else if (!isset($ptype[$ii])) {
					$sql = "SELECT prd,invnum,descrip,odate,age FROM cubit.nons_invoices
							WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}

					$inv = pg_fetch_array($invRslt);

					$inv['invnum'] += 0;

					# reduce the money that has been paid
					$sql = "UPDATE cubit.nons_invoices
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt 
							(cusnum, invid, amount, date, type, div, allocation_date) 
						VALUES 
							('$cus[cusnum]','$inv[invnum]', '".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$inv[odate]')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");
					//recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$inv["odate"]);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|0";
					$rages .= "|$inv[age]";
					$invidsers .= " - $inv[invnum]";
				} else {
					/* pos invoices */
					$sqls = array();
					for ($i = 1; $i <=12; ++$i) {
						$sqls[] = "SELECT '$i' AS prd,invid,invnum,odate FROM \"$i\".pinvoices
									WHERE invid='$invids[$key]' AND div='".USER_DIV."'";
					}
					$sql = implode(" UNION ", $sqls);

					$invRslt = db_exec($sql) or errDie ("Unable to retrieve invoice details from database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}

					$inv = pg_fetch_array($invRslt);

					// reduce the invoice balance
					$sql = "UPDATE \"$inv[prd]\".pinvoices
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt 
							(cusnum, invid, amount, date, type, div, allocation_date) 
						VALUES 
							('$cus[cusnum]','$inv[invnum]', '".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum]", $paidamt[$key], "c");
					//recordCT($paidamt[$key], $cus['cusnum'],0,$inv["odate"]);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				}
			}
		}

		/*
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript,
					cheqnum, amount, banked, accinv, cusnum, rinvids, amounts,
					invprds, rages, reference, div)
				VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]',
					'',
					'$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]',
					'$rinvids', '$amounts', '$invprds', '$rages', '$reference',
					'".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		*/

		writetrans($bank_acc, $dept['debtacc'], $accdate, $refnum, $amt,
			"Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

		db_conn('cubit');
		if (sprint($out) > 0) {
			/* START OPEN ITEMS */
			$openstmnt = new dbSelect("open_stmnt", "cubit", grp(
				m("where", "balance>0 AND cusnum='$cusid'"),
				m("order", "date")
			));
			$openstmnt->run();

			$open_out = $out;
			$i = 0;
			$ox = "";

			while ($od = $openstmnt->fetch_array()) {
				if ($open_out == 0) {
					continue;
				}

				$oid = $od['id'];
				if ($open_out >= $od['balance']) {
					$open_amount[$oid] = $od['balance'];
					$open_out = sprint($open_out-$od['balance']);
					$ox .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' size='20' name='open[$oid]' value='$oid'>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
						</tr>";

					$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
					$Ri = db_exec($Sl) or errDie("Unable to update statement.");

				} elseif($open_out < $od['balance']) {
					$open_amount[$oid] = $open_out;
					$open_out = 0;
					$ox .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' size='20' name='open[$oid]' value='$od[id]'>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
						</tr>";

					$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
					$Ri = db_exec($Sl)or errDie("Unable to update statement.");
				}
				$i++;
			}

			if(open()) {
				$bout = $out;
				$out = $open_out;
				if($out > 0) {
					$sql = "
						INSERT INTO cubit.open_stmnt (
							cusnum, invid, amount, balance, date, type, st, div
						) VALUES (
							'$cus[cusnum]', '0', '-$out', '-$out', '$sdate', 'Payment Received', 'n', '".USER_DIV."'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
					//$OUT .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
				}

				$out = $bout;
			} else  {//$OUT .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
			}

		}

		if (sprint($out) > 0) {
			recordCT($out, $cus['cusnum'],0,$accdate);

			$cols = grp(
				m("cusnum", $cus["cusnum"]),
				m("invid", 0),
				m("amount", -$out),
				m("date", $sdate),
				m("type", "Payment Received"),
				m("div", USER_DIV),
				m("allocation_date", $accdate)
			);

			$dbobj = new dbUpdate("stmnt", "cubit", $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();
			
			custledger($cus['cusnum'], $bank_acc, $sdate, "PAYMENT", "Payment received.", $out, "c");
		}
	}

	/* start moving invoices */
	// move invoices that are fully paid
	$sql = "SELECT * FROM cubit.invoices WHERE balance=0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	while ($x = pg_fetch_array($invbRslt)) {
		if (($prd = $x['prd']) == "0") {
			$prd = PRD_DB;
		}

		// move invoice
		$cols = grp(
			m("invid", $x["invid"]),
			m("invnum", $x["invnum"]),
			m("deptid", $x["deptid"]),
			m("cusnum", $x["cusnum"]),
			m("deptname", $x["deptname"]),
			m("cusacc", $x["cusacc"]),
			m("cusname", $x["cusname"]),
			m("surname", $x["surname"]),
			m("cusaddr", $x["cusaddr"]),
			m("cusvatno", $x["cusvatno"]),
			m("cordno", $x["cordno"]),
			m("ordno", $x["ordno"]),
			m("chrgvat", $x["chrgvat"]),
			m("terms", $x["terms"]),
			m("traddisc", $x["traddisc"]),
			m("salespn", $x["salespn"]),
			m("odate", $x["odate"]),
			m("delchrg", $x["delchrg"]),
			m("subtot", $x["subtot"]),
			m("vat", $x["vat"]),
			m("total", $x["total"]),
			m("age", $x["age"]),
			m("comm", $x["comm"]),
			m("discount", $x["discount"]),
			m("delivery", $x["delivery"]),
			m("docref", $x["docref"]),
			m("prd", $x["prd"]),
			m("delvat", $x["delvat"]),
			m("balance", 0),
			m("printed", "y"),
			m("done", "y"),
			m("username", USER_NAME),
			m("div", USER_DIV)
		);

		$dbobj = new dbUpdate("invoices", $prd, $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		// record movement
		$cols = grp(
			m("invtype", "inv"),
			m("invnum", $x["invnum"]),
			m("prd", $x["prd"]),
			m("docref", $x["docref"]),
			m("div", USER_DIV)
		);

		$dbobj->setTable("movinv", "cubit");
		$dbobj->setOpt($cols);
		$dbobj->run();
		$dbobj->free();

		// move invoice items
		$inv_items = new dbSelect("inv_items", "cubit", grp(
			m("where", wgrp(
				m("invid", $x["invid"]),
				m("div", USER_DIV)
			))
		));
		$inv_items->run();

		while ($xi = $inv_items->fetch_array()){
			$xi['vatcode'] += 0;
			$xi['account'] += 0;
			$xi['del'] += 0;

			$cols = grp(
				m("invid", $x["invid"]),
				m("whid", $xi["whid"]),
				m("stkid", $xi["stkid"]),
				m("qty", $xi["qty"]),
				m("unitcost", $xi["unitcost"]),
				m("amt", $xi["amt"]),
				m("disc", $xi["disc"]),
				m("discp", $xi["discp"]),
				m("vatcode", $xi["vatcode"]),
				m("account", $xi["account"]),
				m("description", $xi["description"]),
				m("del", $xi["del"]),
				m("noted", $xi["noted"]),
				m("serno", $xi["serno"]),
				m("div", USER_DIV)
			);

			$dbobj->setTable("inv_items", $prd);
			$dbobj->setOpt($cols);
			$dbobj->run();
			$dbobj->free();
		}

		/* remove invoice from cubit schema */
		$dbobj = new dbDelete("invoices", "cubit", wgrp(
			m("invid", $x["invid"]),
			m("div", USER_DIV)
		));
		$dbobj->run();

		$dbobj->setTable("inv_items", "cubit");
		$dbobj->run();
	}

	return array(
		"rinvids" => $rinvids,
		"amounts" => $amounts,
		"invprds" => $invprds,
		"rages" => $rages,
		"deptacc" => $dept["debtacc"]
	);

}



function age($cusnum, $days)
{

	$ldays  = $days;
	if($days == 149)
	$ldays = (365 * 10);

	if(div_isset("DEBT_AGE", "mon")){
		switch($days){
			case 29:
				return ageage($cusnum, 0);
			case 59:
				return ageage($cusnum, 1);
			case 89:
				return ageage($cusnum, 2);
			case 119:
				return ageage($cusnum, 3);
			case 149:
				return ageage($cusnum, 4);
		}
	}

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y'
				AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}



function ageage($cusnum, $age)
{

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum'
				AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;

}



# records for CT
function recordCT($amount, $cusnum, $age, $date="", $changemon = false) {
	/*
	db_connect();

	if($date=="") {
	$date=date("Y-m-d");
	}

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
	while($dat = pg_fetch_array($rs)){
	if(floatval($amount) > 0){
	if($dat['balance'] > $amount){
	# Remove make amount less
	$sql = "UPDATE custran SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	$amount =0 ;
	}else{
	# remove small ones
	//if($dat['balance'] > $amount){
	$amount -= $dat['balance'];
	$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	//}
	}
	}
	}
	if($amount > 0){
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}
	}else{
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	*/

	db_connect();

	if($date=="") {
		$date=date("Y-m-d");
	}

	$amount = ($amount * (-1));

	/*if ($changemon === false) {
	$date_ins = "'$date'";
	} else {
	$prd = $age * 30;
	$date_ins = "('$date'::date - '$prd days'::interval)::date";
	}*/

	$date_ins = "'$date'";

	$sql = "INSERT INTO cubit.custran(cusnum, odate, balance, div, age)
			VALUES('$cusnum', $date_ins, '$amount', '".USER_DIV."', '$age')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
}

function allocamt(&$tot, $invbal) {
	if ($tot >= $invbal) {
		$val = $invbal;
		$tot -= $invbal;
	} else {
		$val = $tot;
		$tot = 0;
	}

	return sprint($val);
}


?>

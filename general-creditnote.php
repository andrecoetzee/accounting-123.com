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
require ("libs/ext.lib.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
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
		case "recv_print":
			$OUTPUT = recv_print();
			break;
		default:
			$OUTPUT = slctacc ($_POST);
	}
} else {
	$OUTPUT = slctacc ($_POST);
}

$OUTPUT .= 
		mkQuickLinks(
			ql("general-creditnote.php","Generate General Credit Note"),
			ql("core/trans-new.php", "Journal Transactions"),
			ql("cust-credit-stockinv.php","New Invoice"),
			ql("customers-new.php","Add Customer"),
			ql("settings/credit-note-accounts.php","Set Accounts For Use On General Credit Note"),
			ql("customers-view.php", "View Customers")
		);

# get templete
require("template.php");



function slctacc($_GET, $err="")
{

	extract ($_GET);

	if(!isset($accid))
		$accid = "";
	if(!isset($cusnum))
		$cusnum = 0;
	if(!isset($amount))
		$amount = 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 0, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	$refnum = getrefnum();
/*refnum*/

	// get customer drop
	db_connect();
	$sql = "SELECT accno,cusnum,cusname,surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname,accno";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li class='err'> There are no Debtors in Cubit.</li><br>";		
	}

	$cust = "<select name='cusnum'>";
	while($cus = pg_fetch_array($cusRslt)){
		$sel = "";
		if($cus['cusnum'] == $cusnum) {$sel = "selected";}
		$cust .= "<option $sel value='$cus[cusnum]'>$cus[accno] - $cus[cusname] $cus[surname]</option>";
	}
	$cust .= "</select>";

	#get list off accounts to use for drop down
	$get_accs = "SELECT accid FROM credit_note_accounts";
	$run_accs = db_exec($get_accs) or errDie ("Unable to get credit note account information.");
	if(pg_numrows($run_accs) < 1){
		$accs = array ();
	}else {
		$accs = array ();
		while ($aarr = pg_fetch_array($run_accs)){
			$accs[] = $aarr['accid'];
		}
	}


//".mkAccSelect("accid", $accid)."
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
//			if(isDisabled($acc['accid']))
//				continue;

			if(!in_array($acc['accid'],$accs))
				continue;

			$accounts .= "<option value='$acc[accid]' $sel>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
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

	$vatsel1 = "";
	$vatsel2 = "";
	$vatsel3 = "";
	if(!isset($vatinc) OR strlen($vatinc) < 1){
		$vatsel1 = "checked='yes'";
	}elseif ($vatinc == "yes"){
		$vatsel1 = "checked='yes'";
	}elseif ($vatinc == "no"){
		$vatsel2 = "checked='yes'";
	}else {
		$vatsel3 = "checked='yes'";
	}

	if (!isset ($ct_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$ct_year = $date_arr[0];
			$ct_month = $date_arr[1];
			$ct_day = $date_arr[2];
		}else {
			$ct_year = date("Y");
			$ct_month = date("m");
			$ct_day = date("d");
		}
	}

	// Accounts (debit)
	$view = "
		<h3>Generate Credit Note</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cusnum' value='$cusnum'>
			<input type='hidden' name='entry' value='CT'>
			<input type='hidden' name='accnum' value=''>
			<input type='hidden' name='details' value=''>
		<table ".TMPL_tblDflts.">
			$err
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td>$cust</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("ct", $ct_year, $ct_month, $ct_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference Number</td>
				<td><input type='text' size='10' name='refnum' value='".($refnum++)."'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='16' name='amount' value='".sprint ($amount)."'> Amount Includes VAT<input type='radio' name='vatinc' value='yes' $vatsel1> Amount Excludes VAT<input type='radio' name='vatinc' value='no' $vatsel2> Transaction Has No VAT<input type='radio' name='vatinc' value='novat' $vatsel3></td>
			</tr>
			<tr class='".bg_class()."'>
				<td rowspan='3'>Contra Account</td>
				<td>$accounts <a href='settings/credit-note-accounts.php'>Set Accounts For Use On General Credit Note</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='checkbox' name='gotstock' value='yes' checked='yes'> Were any stock items returned?</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><li class='err'>Stock Can Only Be Returned By Selecting Inventory Account, and unselecting the above checkbox</li></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Next'></td>
			</tr>
			".TBL_BR."
		</table>";
	return $view;

}




# Enter Details of Transaction
function details($_POST,$err="")
{

	# Get vars
	extract ($_POST);

	if (isset($back) AND isStock($accid))
		return get_stock_items($_POST);
	elseif (isset($back))
		return slctacc($_POST);

	$accid += 0;
	$amount = $amount + 0;

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
	$v->isOk ($vatinc, "string", 1, 6, "Invalid Transaction VAT Option.");

	if (sprint ($amount) <= 0){
		$v->addError($amount, "Invalid Or Too Small Amount Entered.");
	}

	if ($accid == "0")
		$v->addError($accid,"No Allowed Accounts Found. <a href='settings/credit-note-accounts.php'>Please Add One First</a>");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctacc($_POST, $confirm."<br>");
	}

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return slctacc($_POST, "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li><br>");
	}

	#if stock returned is selected ... override the setting
	if (isset($gotstock) AND strlen($gotstock) > 0){
		#get a stock id 
		db_conn ('exten');
		$get_stkid = "SELECT stkacc FROM warehouses ORDER BY whid ASC LIMIT 1";
		$run_stkid = db_exec($get_stkid) or errDie ("Unable to get inventory account information.");
		if (pg_numrows($run_stkid) < 1){
			return "<li class='err'>No Inventory Account Found.</li>";
		}else {
			$accid = pg_fetch_result ($run_stkid,0,0);
		}
	}

	# get contra account details
	$accRs = get("core","*","accounts","accid",$accid);
	$acc  = pg_fetch_array($accRs);

	#### handle the stock we selected
	if((isStock($accid) OR isset($gotstock)) AND !isset($stockcontinue)){
		#for whatever reason ... we need to get stock ...
		return get_stock_items($_POST);
	}

	db_connect ();

	# Select customer
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li class='err'>Invalid customer ID, or customer has been blocked.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Customer Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	#get a stock total
	$stock_total = 0;
	if(isset($stock_items) AND is_array($stock_items))
		foreach ($stock_items AS $each => $own){
			$stock_total= $stock_total + ($stock_cost[$each] * $own);
		}



	$stkamount = 0;
	$send_stock = "";
	$show_stock = "";
	if(isset($stock_items) AND is_array($stock_items)){

		$unit_total = array_sum ($stock_items);
		if ($unit_total == 0)
			$stock_unit_avg_cost = 0;
		else 
			$stock_unit_avg_cost = sprint (($amount - $stock_total) / $unit_total);


		foreach ($stock_items AS $each => $own){
			if($own > 0){

				if(!isset($own) OR strlen($own) < 1)
					$own = 1;
				if(!isset($stock_cost[$each]) OR strlen($stock_cost[$each]) < 1)
					$stock_cost[$each] = 0;


				$send_stock .= "<input type='hidden' name='stock_items[$each]' value='$own'>\n";
				$send_stock .= "<input type='hidden' name='stock_cost[$each]' value='$stock_cost[$each]'>\n";

				db_connect ();

				$get_stk = "SELECT stkdes,whid FROM stock WHERE stkid = '$each' LIMIT 1";
				$run_stk = db_exec($get_stk) or errDie ("Unable to get stock information.");
				if(pg_numrows($run_stk) < 1){
					$stock_name = "Unknown";
				}else {
					$stock_name = pg_fetch_result($run_stk,0,0);
					$whid = pg_fetch_result ($run_stk,0,1);
					db_conn('exten');
					$get_cos = "SELECT cosacc FROM warehouses WHERE whid = '$whid' LIMIT 1";
					$run_cos = db_exec($get_cos) or errDie ("Unable to get cost of sale information. (1)");
					if (pg_numrows($run_cos) < 1){
						$show_cos = "";
					}else {
						$cos_id = pg_fetch_result ($run_cos,0,0);
						db_conn ('core');
						$get_acc = "SELECT topacc,accnum,accname FROM accounts WHERE accid = '$cos_id' LIMIT 1";
						$run_acc = db_exec ($get_acc) or errDie ("Unable to get cost of sale information. (2)");
						if (pg_numrows($run_acc) < 1){
							$show_cos = "";
						}else {
							$aarr = pg_fetch_array ($run_acc);
							$show_cos = "$aarr[topacc]/$aarr[accnum] - $aarr[accname]";
						}
					}
				}

				if (!isset($stock_prof[$each]))
					$stock_prof[$each] = sprint ($stock_unit_avg_cost * $own);

				$show_stock .= "
									<tr class='".bg_class()."'>
										<td colspan='2'>$stock_name</td>
										<td>$own</td>
										<td nowrap>".CUR." ".sprint ($stock_cost[$each])."</td>
										<td nowrap>".CUR." ".sprint ($stock_cost[$each] * $own)."</td>
										<td><input type='text' size='7' name='stock_prof[$each]' value='$stock_prof[$each]'></td>
										<td nowrap>$show_cos</td>
									</tr>
								";
				
				$stkamount = $stkamount + ($stock_cost[$each] * $own);
			}
		}
	}

	if(strlen($send_stock) == 0){
		$send_stock = "<input type='hidden' name='stock_items' value='0'>";
		$send_stock .= "<input type='hidden' name='stock_cost' value='0'>";
		$send_stock .= "<input type='hidden' name='stock_prof' value='0'>";
		$get_gds_note = "";
	}else {
		$send_stock .= "<input type='hidden' name='stockcontinue' value='0'>";
		$send_stock .= "<input type='hidden' name='gotstock' value='1'>";

		if (isset($gds_note) AND strlen($gds_note) > 0){
			$gds_note_sel = "checked='yes'";
		}else {
			$gds_note_sel = "";
		}

		$get_gds_note = "
							<tr class='".bg_class()."'>
								<td>Print Goods Received Note</td>
								<td><input type='checkbox' name='gds_note' value='yes' $gds_note_sel></td>
							</tr>
						";
	}

	db_conn ('core');
	$get_sales_acc = "SELECT topacc,accnum,accname FROM accounts WHERE accid = '$dept[incacc]' LIMIT 1";
	$run_sales_acc = db_exec($get_sales_acc) or errDie ("Unable to get sales account information. (1)");
	if (pg_numrows($run_sales_acc) < 1){
		$show_sales = "";
	}else {
		$sarr = pg_fetch_array ($run_sales_acc);
		$show_sales = "$sarr[topacc]/$sarr[accnum] - $sarr[accname]";
	}

	if(strlen($show_stock) > 0){
		$show_stock = "
						<tr>
							<th colspan='2'>Stock Description</th>
							<th>Number Of Units Returned</th>
							<th>Unit Cost</th>
							<th>Total</th>
							<th>Profit/Loss</th>
							<th>Cost Of Sale Account</th>
						</tr>
						$show_stock
						<tr class='".bg_class()."'>
							<td colspan='4' align='right'><b>Total:</b></td>
							<td nowrap>".CUR." ".sprint($stock_total)."</td>
							<td colspan='2' nowrap><li class='err'>Difference ($amount - ".sprint ($stock_total)."): ".CUR." ".sprint ($amount - $stock_total)."</li></td>
						</tr>
						<tr>
							<td colspan='7'><li class='err'>Sales Account To Be Used: $show_sales</li></td>
						</tr>";
	}


	# Probe tran type
	if($entry == "CT"){
		$tran = "
					<tr class='".bg_class()."'>
						<td colspan='3'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						<td colspan='2'>$cust[accno] - $cust[cusname] $cust[surname]</td>
					</tr>";
	}else{
		$tran = "
					<tr class='".bg_class()."'>
						<td colspan='3'>$cust[accno] - $cust[cusname] $cust[surname]</td>
						<td colspan='2'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					</tr>";
	}

	if(!isset($amount)) {
		$amount = $stkamount;
		$details = "";
	}

	if(!isset($vataccid))
		$vataccid = 0;

	if(isset($vatinc) AND $vatinc != "novat"){

		db_connect ();

		$get_vatcodes = "SELECT * FROM vatcodes ORDER BY code";
		$run_vatcodes = db_exec($get_vatcodes) or errDie ("Unable to get vat code inoformation.");
		if(pg_numrows($run_vatcodes) < 1){
			return "No Vatcodes Found. Please Add One First.";
		}else {
			$vatcode_drop = "<select name='vatcode'>";
			while ($varr = pg_fetch_array ($run_vatcodes)){
				$vatcode_drop .= "<option value='$varr[id]'>($varr[code]) $varr[description]</option>";
			}
			$vatcode_drop .= "</select>";
		}

		db_conn('core');
		$vatacc = "<select name='vataccid'>";
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}

		$vatacc_newid = gethook("accnum", "salesacc", "name", "VAT", "VAT");

		$vataccid = getCSetting("CRED_NOTE_VAT_ACC");
		if (!isset($vataccid) OR (strlen($vataccid) < 1) OR $vataccid == "0")
			$vataccid = $vatacc_newid;


//		if (!isset($vataccid) OR $vataccid == "0")
//			$vataccid = $vatacc_newid;

//		while($acc_arr = pg_fetch_array($accRslt)){
//			# Check Disable
//			if(isDisabled($acc_arr['accid']))
//				continue;
//			if($vataccid == $acc_arr['accid']) {
//				$sel = "selected";
//			} else {
//				$sel = "";
//			}
//			$vatacc .= "<option value='$acc_arr[accid]' $sel>$acc_arr[topacc]/$acc_arr[accnum] - $acc_arr[accname]</option>";
//		}
//		$vatacc .= "</select>";

		if(isset($vatdedacc) AND $vatdedacc == "dt")
			$dsel1 = "checked='yes'";
		else 
			$dsel1 = "";

		if(isset($vatdedacc) AND $vatdedacc == "ct")
			$dsel2 = "checked='yes'";
		else 
			$dsel2 = "";

		if($dsel1 == "" AND $dsel2 == "")
			$dsel1 = "checked='yes'";

		if (isStock($accid) AND isset($gotstock)){
			$showgetvatacc = "<input type='hidden' name='vatdedacc' value='dt'>";
		}else {
			$showgetvatacc = "
								<tr class='".bg_class()."'>
									<td colspan='2'valign='top'>VAT Deductable Account</td>
									<td colspan='2'>
										<input type='radio' name='vatdedacc' value='dt' $dsel1 />$acc[topacc]/$acc[accnum] - $acc[accname]<br />
										<input type='radio' name='vatdedacc' value='ct' $dsel2 />$cust[accno] - $cust[surname]
									</td>
								</tr>
							";
		}

		db_conn ('core');

		$get_vatacc = "SELECT accname FROM accounts WHERE accid = '$vataccid' LIMIT 1";
		$run_vatacc = db_exec($get_vatacc) or errDie ("Unable to get vat account details.");
		if (pg_numrows($run_vatacc) < 1){
			$showvatacc = "";
		}else {
			$vatacc_id = pg_fetch_result ($run_vatacc,0,0);
			$showvatacc = "
							<tr class='".bg_class()."'>
								<td colspan='2'>VAT Account</td>
								<td colspan='3'>$vatacc_id <a target='_blank' href='settings/credit-note-accounts.php'>Change Account</a></td>
							</tr>
						";
		}

//		<tr class='".bg_class()."'>
//			<td colspan='2'>VAT Account</td>
//			<td colspan='3'>$vatacc</td>
//		</tr>
		$get_vats = "
						".TBL_BR."
						<input type='hidden' name='vataccid' value='$vataccid'>
						<tr>
							<th colspan='5'>VAT Detail</th>
						</tr>
						$showgetvatacc
						$showvatacc
						<tr class='".bg_class()."'>
							<td colspan='2'>VAT Code</td>
							<td colspan='3'>$vatcode_drop</td>
						</tr>
						".TBL_BR."
					";
	}else {
		$get_vats = "
						<input type='hidden' name='vatinc' value='novat'>
						<input type='hidden' name='vatcode' value='0'>
					";
	}

    // Layout Details
    $details = "
    				<h3>Confirm Credit Note Details</h3>
    				$err
    				<form action='".SELF."' method='POST' name='form'>
				        <input type='hidden' name='key' value='write'>
						<input type='hidden' name='type' value='1'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='cusnum' value='$cusnum'>
				        <input type='hidden' name='accid' value='$accid'>
				        <input type='hidden' name='accname' value='$acc[accname]'>
						<input type='hidden' name='entry' value='$entry'>
						<input type='hidden' name='ct_day' value='$ct_day'>
				        <input type='hidden' name='ct_month' value='$ct_month'>
				        <input type='hidden' name='ct_year' value='$ct_year'>
				        <input type='hidden' name='vatinc' value='$vatinc'>
				        <input type='hidden' name='ac' value=''>
				        <input type='hidden' name='amount' value='$amount'>
				        <input type='hidden' name='refnum' value='$refnum'>
				        <input type='hidden' name='difference' value='".sprint ($amount-$stock_total)."'>
				        $send_stock
			        <table ".TMPL_tblDflts." width='500'>
						<tr>
							<th colspan='3'>Debit</th>
							<th colspan='2'>Credit</th>
						</tr>
						$tran
						<tr><td><br></td></tr>
						<tr><td><br></td></tr>
						$show_stock
    					$get_vats
    				</table>
    				<table ".TMPL_tblDflts." width='500'>
    					".TBL_BR."
    					<tr>
    						<th colspan='2'>Transaction Details</th>
    					</tr>
						<tr class='".bg_class()."'>
							<td>Transaction Details</td>
							<td valign='center'><textarea cols='30' rows='5' name='details'>$details</textarea></td>
						</tr>
						$get_gds_note
						<tr class='".bg_class()."'>
							<td>Person Authorising</td>
							<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
							<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
						</tr>
			        </table>
			        </form>";
	return $details;

}



# Write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return details($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid Customer number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($vatinc, "string", 1, 6, "Invalid Transaction VAT Option.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
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

	if ((isset($stock_prof) AND is_array ($stock_prof)) AND sprint (array_sum ($stock_prof)) != $difference){
		return details ($_POST,"<li class='err'>Please ensure differences matches total difference.</li>");
	}

//print "<pre>";
//var_dump ($_POST);
//print "</pre>";



	$date = "$datea[2]-$datea[1]-$datea[0]";

	# Accounts details
    $accRs = get("core","*","accounts","accid",$accid);
    $acc = pg_fetch_array($accRs);

	# Select customer
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li class='err'>Invalid customer ID, or customer has been blocked.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Customer Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Probe tran type
	if($entry == "CT"){

		$refnum = getrefnum();

		#update stock ...

		$stock_total = 0;
		if(isset($stock_items) AND is_array($stock_items)){
			$used_stock = TRUE;
			
			foreach ($stock_items AS $stkid => $unitnum){

				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
				if(pg_numrows($stkRslt) < 1){
					return "<li> Invalid Stock ID.</li>";
				}else{
					$stk = pg_fetch_array($stkRslt);
				}

				if($stk['units'] < 0) {
					$min_stock = abs($stk['units']);
					if ( $unitnum < $min_stock ) {
						$min_stock = $unitnum;
					}
				} else {
					$min_stock=0;
				}

				# Get warehouse name
				db_conn("exten");
				$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# calculate actual cost amount
//				$temp = $cost;
//				$cost = sprint($cost * $unitnum);

				#temp = unitprice
				$temp = sprint ($stock_cost[$stkid]);
				#cost = total price
				$cost = sprint ($stock_cost[$stkid]*$unitnum);
				$cost_amt = sprint ($cost);

				$stock_total = $stock_total + $cost_amt;

				$stock_cost[$stkid] = $cost_amt/$unitnum;

				#temp = unitprice
				$temp = sprint ($stock_cost[$stkid]);
				#cost = total price
				$cost = sprint ($stock_cost[$stkid]*$unitnum);

				$tipo = "Increase";
				if($tipo == 'Increase'){

					/* do the journals for stock sold before purchase 
						this will only be done by a purchase */
					if($min_stock>0) {

						db_conn("exten");
						$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);
						$stockacc = $wh['stkacc'];
						$cosacc = $wh['cosacc'];
					}

					# Update Stock
					db_connect();
					$sql = "UPDATE stock
							SET units = (units + '$unitnum'),
								lcsprice = '$temp',
								csamt = (csamt + $cost),
								csprice = (
									SELECT
										CASE WHEN (units != -$unitnum) THEN (csamt+$cost)/(units+$unitnum)
										ELSE 0
										END
									FROM cubit.stock
									WHERE stkid = '$stkid' AND div = '".USER_DIV."'
								)
							WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

					$sdate = $date;
					# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
					stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $sdate, $unitnum, $cost, $details);

					db_connect();
					if ($unitnum == 0) {
						$csprice = 0;
					} else {
						$csprice = sprint($cost/$unitnum);
					}

					$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
							VALUES('$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'note', '$unitnum', '".sprint ($cost+$stock_prof[$stkid])."', '$csprice', '$details', '".USER_DIV."')";
					$recRslt = db_exec($sql);

					db_connect();
					$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
					if(pg_numrows($stkRslt) < 1){
						return "<li> Invalid Stock ID.</li>";
					}else{
						$stk = pg_fetch_array($stkRslt);
					}

					# balance transaction
					# Debit STock account and Credit Contra Account
					#ct was $dept['debtacc']
					writetrans($wh['stkacc'], $wh['cosacc'], $date, $refnum, $cost, "Cost Of Sales for: $details for Customer: $cust[surname]");

					$cc_trantype = cc_TranTypeAcc($wh['stkacc'], $dept['debtacc']);
				}
			}
		}else {
			$used_stock = FALSE;
		}


		#we'll handle the discrincy seperately
		if ($stock_total != 0){
			$amount_dif = sprint ($amount - $stock_total);
//			$amount = sprint ($stock_total);
		}else {
			$amount_dif = 0;
//			$amount = sprint ($amount);
		}

		#do vat trans ...
		if(isset($vatinc) AND $vatinc != "novat"){

			#process vat

			db_connect ();
			$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
			$Ri = db_exec($Sl);
			$vd = pg_fetch_array($Ri);
			$VATP = $vd['vat_amount'];

			#calculate amounts
			if($vatinc == 'yes'){
				$vatamt = sprint((($amount/($VATP + 100)) * $VATP));
				$amt = sprint($amount - $vatamt);
				$totamt = sprint($amount);
			}else{
				$vatamt = sprint((($VATP/100) * $amount));
				$amt = sprint($amount);
				$totamt = sprint($amount + $vatamt);
			}

			$datea = explode("-", $date);
			$cdate = $date;	

			# Check VAt Deductable account
			if($vatdedacc == 'dt'){
				vatr($vd['id'],$cdate,"INPUT",$vd['code'],$refnum,"$details VAT",-$totamt,-$vatamt);
				writetrans($vataccid, $dept['debtacc'], $date, $refnum, $vatamt, "VAT Return for: $details");
			}elseif($vatdedacc == 'ct'){
				vatr($vd['id'],$cdate,"OUTPUT",$vd['code'],$refnum,"$details.  VAT",$totamt,$vatamt);
				writetrans($accid, $vataccid, $date, $refnum, $vatamt, "VAT Return for: $details");
			}
			if ($used_stock){
				$accid = $dept['incacc'];
				$details = "Debtors Control for: $details";
			}
			writetrans($accid, $dept['debtacc'], $date, $refnum, $amt, $details." - Customer $cust[cusname] $cust[surname]");
		}else{
			$totamt = sprint($amount);
			$amt = sprint ($amount);

			$getacc_arr = getAccn("2190","000");
			$getgacc  = $getacc_arr['accid'];

			if ($used_stock){
				$accid = $dept['incacc'];
				$details = "Debtors Control for: $details";
			}

			# Write transaction  (debit contra account, credit debtors control)
			writetrans($accid, $dept['debtacc'], $date, $refnum, $totamt, $details." - Customer $cust[cusname] $cust[surname]");
		}


		$tran = "
					<tr class='".bg_class()."'>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						<td>$cust[accno] - $cust[cusname] $cust[surname]</td>
					</tr>";
		$samount = ($amount - ($amount * 2));
		recordCT($samount, $cust['cusnum'],$date);
		$type = 'c';
	}

	db_connect();

		$stotamt = ($totamt  - ($totamt * 2));

		$sdate = date("Y-m-d");

		# record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, amount, date, type, st, div, allocation_date) 
			VALUES 
				('$cust[cusnum]', '0', '$stotamt', '$date', '$details', 'n', '".USER_DIV."', '$date')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		$sql = "INSERT INTO open_stmnt(cusnum, invid, amount, balance, date, type, st, div) VALUES('$cust[cusnum]', '0', '$samount', '$samount', '$date', '$details', 'n', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$samount') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);


		# Make ledge record
		custledger($cust['cusnum'], $accid, $date, $refnum, $details, $totamt, $type);

		db_connect ();

		$get_credid = "SELECT last_value FROM seq WHERE type = 'cred_note' LIMIT 1";
		$run_credid = db_exec($get_credid) or errDie ("Unable to get credit note number.");
		$crednote_num = pg_fetch_result ($run_credid,0,0);

		if(!isset($vataccid))
			$vataccid = 0;
		if(!isset($vatamt))
			$vatamt = 0;
		if(!isset($vatdedacc))
			$vatdedacc = 0;
		if(!isset($vatcode))
			$vatcode = 0;


		#record this credit note for records ...
		$ins_sql = "
						INSERT INTO credit_notes 
							(cusnum,creditnote_num,tdate,sdate,refnum,contra,charge_vat,vatinc,vatacc,vatamt,vatacc_type,vatcode,used_stock,amount,totamt) 
						VALUES 
							('$cusnum','$crednote_num','$date','now','$refnum','$accid','$vatinc','$vatinc','$vataccid','$vatamt','$vatdedacc','$vatcode','$used_stock','$amt','$totamt')
					";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record credit note information.");

		$cred_id = pglib_lastid ("credit_notes","id");

		if($used_stock){
			foreach ($stock_items AS $stkid => $unitnum){

				$ins_creditnote_sql = "
										INSERT INTO credit_notes_stock
											(creditnote_id,stkid,stkunits,stkcosts) 
										VALUES 
											('$cred_id','$stkid','$unitnum','".sprint ((($unitnum * $stock_cost[$stkid]) + $stock_prof[$stkid]) / $unitnum)."')";
				$run_creditnote_sql = db_exec($ins_creditnote_sql) or errDie ("Unable to record credit note returned stock item information.");

			}
		}

	#update seq
	$upd_seq = "UPDATE seq SET last_value = last_value + 1 WHERE type = 'cred_note'";
	$run_seq = db_exec($upd_seq) or errDie ("Unable to update credit note number.");

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if (isset($gds_note) AND strlen ($gds_note) > 0){
		$show_gds_note = "
					<script>
						window.open(\"".SELF."?key=recv_print&genid=$cred_id\");
					</script>
						";
	}else {
		$show_gds_note = "";
	}

	// Start layout
    $write = "
    			<script>
    				window.open('credit-note-print.php?id=$cred_id');
    			</script>
    			$show_gds_note
    			<h3>Credit Note Has Been Recorded</h3>
		        <table ".TMPL_tblDflts." width='500'>
		        	<tr>
		        		<td width='50%'><h3>Debit</h3></td>
		        		<td width='50%'><h3>Credit</h3></td>
		        	</tr>
		        	$tran
		        	<tr><td><br></td></tr>
		        	<tr colspan='2'>
		        		<td><h4>Amount</h4></td>
		        	</tr>
		        	<tr class='".bg_class()."'>
		        		<td colspan='2'><b>".CUR." $amount</b></td>
		        	</tr>
		        	".TBL_BR."
		        </table>";
	return $write;

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
			$sql = "INSERT INTO custran(cusnum, odate, balance,div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
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



function get_stock_items($_POST)
{

	extract ($_POST);

	if(isset($search)){
		$showsearch = "WHERE lower(stkcod) LIKE lower('%$search%') OR lower(stkdes) LIKE lower('$search%')";
	}else {
		$showsearch = "WHERE stkid='0'";
	}

	db_connect ();

	$get_stock = "SELECT stkid,stkcod,stkdes,csprice FROM stock $showsearch ORDER BY stkcod,stkdes";
	$run_stock = db_exec($get_stock) or errDie ("Unable to get stock information.");
	if(pg_numrows($run_stock) < 1){
		$stks = "
					<tr class='".bg_class()."'>
						<td colspan='4'>No Stock Found.</td>
					</tr>
				";
	}else {
		$stks = "";
		while ($sarr = pg_fetch_array($run_stock)){
			$val1 = "";
			$val2 = "";
			if(isset($stock_items[$sarr['stkid']]))
				$val1 = $stock_items[$sarr['stkid']];
			if(isset($stock_cost[$sarr['stkid']]))
				$val2 = $stock_cost[$sarr['stkid']];

			$recom = sprint ($sarr['csprice']);

			$stks .= "
						<tr class='".bg_class()."'>
							<td nowrap>$sarr[stkcod] $sarr[stkdes]</td>
							<td><input type='text' size='6' name='stock_items[$sarr[stkid]]' value='$val1'></td>
							<td><input type='hidden' name='stock_cost[$sarr[stkid]]' value='$recom'>".CUR." $recom</td>
						</tr>";
		}
	}

	$dtct = "inc"; // was "entry" for stock-balance.php ...

	if(!isset($search))
		$search = "";
	if(!isset($accnum))
		$accnum = "";

	$send_all = "";
	if (!isset($stock_items) OR !is_array ($stock_items))
		$stock_items = array (0);
	if (!isset($stock_cost) OR !is_array ($stock_cost))
		$stock_cost = array (0);

	if(is_array($stock_items)){
		foreach ($stock_items AS $each => $own){
			$send_all .= "
							<input type='hidden' name='stock_items[$each]' value='$own'>
							<input type='hidden' name='stock_cost[$each]' value='$stock_cost[$each]'>
						";
		}
	}

	$OUT = "
				<h3>You Selected a Stock Control account</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='details'>
					<input type='hidden' name='entry' value='$entry' />
					<input type='hidden' name='ct_day' value='$ct_day'>
					<input type='hidden' name='ct_month' value='$ct_month'>
					<input type='hidden' name='ct_year' value='$ct_year'>
					<input type='hidden' name='cusnum' value='$cusnum'>
					<input type='hidden' name='accid' value='$accid' />
					<input type='hidden' name='refnum' value='$refnum'>
					<input type='hidden' name='details' value='$details'>
					<input type='hidden' name='accnum' value='$accnum'>
					<input type='hidden' name='vatinc' value='$vatinc'>
					<input type='hidden' name='gotstock' value='yes'>
					<input type='hidden' name='amount' value='$amount'>
					$send_all
				<table ".TMPL_tblDflts." width='300'>
					<tr>
						<th colspan='3'>Stock Filter</th>
					</tr>
					<tr class='".bg_class()."'>
						<td nowrap colspan='3'><input type='text' size='35' name='search' value='$search'> <input type='submit' value='Filter'></td>
					</tr>
					".TBL_BR."
					<tr>
						<th>Stock Description</th>
						<th>Number Of Units Returned</th>
						<th>Average Cost</th>
					</tr>
					$stks
					<tr>
						<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()' /></td>
						<td align='center'><input type='submit' name='stockcontinue' value='Continue &raquo;' /></td>
					</tr>
				</table>
				</form>
			";
	return $OUT;

}

function recv_print()
{

	extract ($_REQUEST);

	db_connect ();

	$union_pur = "SELECT * FROM credit_notes WHERE id='$genid'";
	$pur_rslt = db_exec($union_pur) or errDie("Unable to retrieve purchases.");
	$pur_data = pg_fetch_array($pur_rslt);

	$union_items = "SELECT stkid, stkunits, stkcosts FROM credit_notes_stock WHERE creditnote_id='$genid'";
	$item_rslt = db_exec($union_items) or errDie("Unable to retrieve purchase items.");

	$item_out = "";
	while ($item_data = pg_fetch_array($item_rslt)) {

		db_connect ();

		#get stock info for this item
		$get_stock = "SELECT stkcod, stkdes, whid FROM stock WHERE stkid = '$item_data[stkid]' LIMIT 1";
		$run_stock = db_exec($get_stock) or errDie ("Unable to get stock information.");
		if (pg_numrows($run_stock) < 1){
			$stkcod = "";
			$stkdes = "";
			$whname = "";
		}else {
			$starr = pg_fetch_array ($run_stock);
			$stkcod = $starr['stkcod'];
			$stkdes = $starr['stkdes'];

			#get whname
			db_conn('exten');
			$get_wh = "SELECT whname FROM warehouses WHERE whid = '$starr[whid]' LIMIT 1";
			$run_wh = db_exec($get_wh) or errDie ("Unable to get warehouse information.");
			if(pg_numrows($run_wh) < 1){
				$whname = "";
			}else {
				$whname = pg_fetch_result ($run_wh,0,0);
			}
		}

		$item_out .= "
		<tr>
			<td>$whname&nbsp;</td>
			<td>$stkcod&nbsp;</td>
			<td>$stkdes&nbsp;</td>
			<td>$item_data[stkunits]&nbsp;</td>
			<td>$pur_data[tdate]&nbsp;</td>
			<td>$item_data[stkcosts]&nbsp;</td>

		</tr>";
	}

	$OUTPUT = "
	<table ".TMPL_tblDflts." border='1'>
		<tr><td colspan='10'><h3>Goods Received</h3><td></tr>
		<tr>
			<td colspan='10'>
			<table ".TMPL_tblDflts." width='100%'>
				<tr><td width='50%'>
				<table width='100%'>
				</table>
				</td>
				<td width='50%'>
				<table width='100%'>
					<tr>
						<td colspan='2'>
							<strong>Credit Note Details</strong>
						</td>
					</tr>
					<tr>
						<td>Credit Note No.</td>
						<td>$pur_data[creditnote_num]</td>
					</tr>
					<tr>
						<td>Date</td>
						<td>$pur_data[tdate]</td>
					</tr>
					<tr>
						<td>VAT Inclusive</td>
						<td>$pur_data[vatinc]</td>
					</tr>
				</table>
				</td></tr>
			</table>
			</td>
		</tr>
		<tr>
			<td><b>Store</b></td>
			<td><b>Stock</b></td>
			<td><b>Description</b></td>
			<td><b>Qty Received</b></td>
			<td><b>Delivery Date</b></td>
			<td><b>Amount</b></td>
		</tr>
		$item_out
	</table>";
	require ("tmpl-print.php");

}

?>

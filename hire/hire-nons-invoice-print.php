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

// Navigation logic
if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
	case "update_qty":
		$OUTPUT = update_qty();
		break;
	}
} else if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "cconfirm":
		$OUTPUT = cconfirm();
		break;
	case "cwrite":
		$OUTPUT = cwrite();
		break;
	}
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
 }	

require("../template.php");



# Customer Confirm
function cconfirm($errors="")
{

	extract($_REQUEST);
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$confirm = "$err<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	if (!isset($monthly)) $monthly = 0;

	$vattot = 0;
	$stocktot = 0;

	$pcash = $pcredit = $pcc = $pcheque = "0.00";

	# Get Invoice info
	db_connect();
	$sql = "SELECT *, extract('epoch' FROM odate) AS e_date
			FROM cubit.nons_invoices
			WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	#get trade discount percentage for this hire .... so we can include in calcs ...
	$sql = "SELECT traddisc FROM hire.hire_invoices WHERE invid='$inv[hire_invid]'";
	$disc_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
	$traddisc = pg_fetch_result($disc_rslt, 0);

	// Stock Display
	$sql = "
	SELECT hire_stock_items.id, stkcod, stkdes, qty, unitcost, amount, excl_amount, vatamount FROM hire.hire_stock_items
		LEFT JOIN cubit.stock ON hire_stock_items.stkid=stock.stkid
	WHERE invid='".monthly_invid($inv["hire_invid"])."'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");

	$stock_out = "";
	if (pg_num_rows($stock_rslt)) {
		$stock_out .= "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STOCK</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>AMOUNT</th>
			</tr>";
		while ($stock_data = pg_fetch_array($stock_rslt)) {
			
			if (!isset($stock_qty[$stock_data["id"]])) {
				$stkqty = $stock_data["qty"];
			} else {
				$stkqty = $stock_qty[$stock_data["id"]];
			}

			if ($traddisc > 0){
				if ($stock_data['qty'] != 0)
					$vattot += ((($stock_data['vatamount']/$stock_data['qty']) * $stkqty)/100) * (100 - $traddisc);
			}else {
				if ($stock_data['qty'] != 0)
					$vattot += ($stock_data['vatamount']/$stock_data['qty']) * $stkqty;
			}

//			$vattot += ($stock_data['vatamount']/$stock_data['qty']) * $stkqty;
			if ($stock_data['qty'] != 0)
				$stocktot += ($stock_data['excl_amount']/$stock_data['qty']) * $stkqty;

			$stock_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$stock_data[stkcod] - $stock_data[stkdes]</td>
					<td align='center'>
						<input type='text' name='stock_qty[$stock_data[id]]'
						size='3' value='$stkqty' />
					</td>
					<td>$stock_data[unitcost]</td>
					<td>$stock_data[unitcost]</td>
				</tr>";
//					<td>$stock_data[amount]</td>    <--------- last <td> entry
		}
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='5%'>#</th>
				<th width='40%'>DESCRIPTION</th>
				<th width='10%'>QTY</th>
				<th width='10%'>UNIT PRICE</th>
				<th width='10%'>AMOUNT</th>
			<tr>";



	$sql = "SELECT EXTRACT('epoch' FROM from_time) AS e_from FROM hire.hires
			WHERE inv_id='$inv[hire_invid]'";
	$time_rslt = db_exec($sql) or errDie("Unable to retrieve times.");
	$e_time = pg_fetch_result($time_rslt, 0);

	$sql = "UPDATE hire.hires SET to_time=current_timestamp WHERE inv_id='$invid'";
	db_exec($sql) or errDie("Unable to update to time.");

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	$totamt = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$totamt += $stkd["amt"];
		if ($traddisc > 0){
			$vattot += sprint ((($stkd["amt"]/100)*(100 - $traddisc)) / 100 * 14);
		}else {
			$vattot += sprint ($stkd["amt"] / 100 * 14);
		}

		// Retrieve hire sales accounts
		$sql = "SELECT * FROM core.accounts WHERE topacc='1050' AND accnum='000'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
		$acc = pg_fetch_array($acc_rslt);

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$sql = "SELECT * FROM hire.hire_invitems WHERE id='$stkd[item_id]'";
		$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
		$hinv_data = pg_fetch_array($hinv_rslt);

		$from_date = $hinv_data["from_date"];
		$from_date = explode ("-", $from_date);
		$from_year = $from_date[0];
		$from_month = $from_date[1];
		$from_day = $from_date[2];

		$sql = "SELECT extract('epoch' FROM hired_time) AS e_time
				FROM hire.assets_hired WHERE item_id='$stkd[item_id]'";
		$et_rslt = db_exec($sql) or errDie("Unable to retrieve hired time.");
		$e_time = pg_fetch_result($et_rslt, 0);

		$i++;
		# put in product
		$products .= "
			<input type='hidden' name='stkaccs[$stkd[id]]' value='$acc[accid]'>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td>
					<input type='hidden' name='description[$stkd[id]]' value='$stkd[description]' />
					$stkd[description]
				</td>
				<td align='center'>
					<input type='text' name='qty[$stkd[id]]' value='$stkd[qty]' size='3' />
				</td>
				<td>".sprint($stkd["unitcost"])."</td>
				<td>".CUR.sprint($stkd["amt"])."</td>
			</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */
	
	$sql = "
	SELECT sum(excl_amount) FROM hire.hire_stock_items
	WHERE invid='".monthly_invid($inv["hire_invid"])."'";
	$stkamt_rslt = db_exec($sql) or errDie("Unable to retrieve stock prices.");
	$stkamt = pg_fetch_result($stkamt_rslt, 0) + 0;

	# Get subtotal
	$SUBTOT = sprint($totamt + $inv["delivery"] - $inv["discount"] + $stocktot);//$stkamt);

	# Get vat
	//$VAT = sprint($SUBTOT/100*14);
	$VAT = sprint ($vattot);

	# Get Total
	$TOTAL = $SUBTOT + $VAT;
	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	db_connect();
	# cust % bank
	if($ctyp == 's'){
		$cust = qryCustomer($cusnum);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$cust[addr1]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$cust[vatnum]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Balance (Excl this Invoice)</td>
					<td>".CUR.sprint($cust["balance"])."</td>
				</tr>
			</table>";
	}elseif($ctyp == 'c'){
		$dept = qryDepartment($deptid);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='deptid' value='$deptid'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
			</table>";
	}elseif($ctyp == 'cb'){

		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='bankid' value='$inv[accid]'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td>$supacc</td>
				</tr>
			</table>";
	}elseif($ctyp == 'ac'){
		$accountc += 0;
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $accountc);

		$accd = pg_fetch_array($accRs);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Customer Details </th>
				</tr>
				<input type='hidden' name='accountc' value='$accountc'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td valign='center'>$accd[accname]</td>
				</tr>
			</table>";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if ($monthly) {
		$correction = "<input type='button' value='&laquo Correction' onclick='javascript:move(\"hire-invoice-new.php?invid=$inv[hire_invid]&monthly=true\");' />";
	} else {
		$correction = "";
	}

	if (!isset($recvpay)) $recvpay = "";
	if (!isset($pc)) $pc = "";
	if (!isset($inv["pcash"])) $inv["pcash"] = "";
	if (!isset($inv["pcheque"])) $inv["pcheque"] = "";
	if (!isset($inv["pcc"])) $inv["pcc"] = "";
	if (!isset($inv["pcredit"])) $inv["pcredit"] = "";

	$hirenum = "H".getHirenum($inv["hire_invid"], 1);

	if (!isset($updated)) {
		$updated = 1;
		$updated_out = "<input type='hidden' name='button[update_qty]' value='Update Qty' />";
		$updated_out .= "<script>document.form.submit()</script>";
	} else {
		$updated_out = "";
	}

	/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Invoice Details</h3>
	<script>
		function ptot_recvpay() {
			if (ptot_amt() > 0) {
				return true;
			} else {
				alert('Enter amounts received by customer above.');
				return false;
			}
		}

		function pfld_num(fn) {
			i = getObject(fn).value;
			if (i) {
				return parseFloat(i);
			} else {
				return 0;
			}
		}

		function ptot_amt(nocredit) {
			i = pfld_num('pcash');
			i += pfld_num('pcc');
			i += pfld_num('pcheque');
			if (!nocredit && getObject('pcredit')) {
				i += pfld_num('pcredit');
			}
			return i.toFixed(2);
		}

		function ptot_update() {
			getObject('ptot').innerHTML = '".CUR." ' + ptot_amt();
			if (o = getObject('recvpay')) {
				o.value = 'Receive Payment: ".CUR." ' + ptot_amt(true);
			}
		}

		function paytotal(id) {
			if (getObject('pcredit')) getObject('pcredit').value = '0.00';
			ptot_update();
		}
	</script>

	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='cwrite'>
		<input type='hidden' name='invid' value='$invid'>
		<input type='hidden' name='ctyp' value='$ctyp'>
		<input type='hidden' name='monthly' value='$monthly' />
		<input type='hidden' name='hirenum' value='$hirenum' />
		<input type='hidden' name='updated' value='1' />
	<table ".TMPL_tblDflts." width='95%'>
		<tr>
			<td valign='top'>$details</td>
			<td valign='top' align='right'>
				<table ".TMPL_tblDflts.">
					<tr><td colspan='2'>$errors</td></tr>
					<tr><th colspan=2> Non-Stock Invoice Details </th></tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Non-Stock Invoice No.</td>
						<td valign='center'>T $inv[invid]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Hire No.</td>
						<td valign='center'>$hirenum</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td valign='center'>".date("d-m-Y", $inv["e_date"])."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT Inclusive</td>
						<td valign='center'>$inv[chrgvat]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Terms</td>
						<td valign='center'>$inv[terms] Days</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr><td><br></td></tr>
		<tr>
			<td colspan='2'>$products</td>
		</tr>
		<tr>
			<td colspan='2'>$stock_out</td>
		</tr>
		<tr>
			<td>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Payment Details </th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>User</td>
						<td><input type='hidden' name='user' value='".USER_NAME."'>".USER_NAME."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Amount Paid Cash</td>
						<td nowrap='t'>
							<input size='12' type='text' name='pcash' id='pcash' value='$pcash' onchange='ptot_update();'>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Amount Paid Cheque</td>
						<td nowrap='t'>
							<input size='12' type='text' name='pcheque' id='pcheque' value='$pcheque' onchange='ptot_update();'>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Amount Paid Credit Card</td>
						<td nowrap='t'>
							<input size='12' type='text' name='pcc' id='pcc' value='$pcc' onchange='ptot_update();'>
						</td>
					</tr>
					$recvpay
					$pc
					<!--<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Total Covered</td>
						<td nowrap='t' id='ptot'>".CUR." ".sprint($inv["pcash"] + $inv["pcheque"] + $inv["pcc"] + $inv["pcredit"])."</td>
					</tr>-->
					<tr>
						<th width='40%'>Quick Links</th>
						<th width='45%'>Remarks</th>
						<td rowspan='5' valign='top' width='15%'><br></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td align='center'><a href='javascript:popupOpen(\"../nons-invoice-new.php\")'>New Non-Stock Invoices</a></td>
						<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td align='center'><a href='javascript:popupOpen(\"../nons-invoice-view.php\")'>View Non-Stock Invoices</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>
			</td>
			<td align='right'>
				<table ".TMPL_tblDflts." width='80%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Delivery Charge</td>
						<td align='right'>".CUR."$inv[delivery]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Trade Discount</td>
						<td align='right'>".CUR."$inv[discount]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>SUBTOTAL</td>
						<td align='right'>".CUR." $SUBTOT</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT $vat14</td>
						<td align='right'>".CUR." $VAT</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<th>GRAND TOTAL</th>
						<td align='right'>".CUR." ".sprint($TOTAL)."</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align=right>
				$correction
				<input type='submit' name='button[update_qty]' value='Update Qty' />
				<input type='submit' value='Write &raquo'>$updated_out
			</td>
		</tr>
	</table>
	</form>
	</center>";
	return $details;

}




# Customer write
function cwrite()
{

	extract($_REQUEST);
	$showvat = TRUE;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

// 	if(isset($stkaccs)){
// 		foreach($stkaccs as $key => $accid){
// 			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
// 		}
// 	}else{
// 		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
// 	}

	if (!isset($description) && !count($description)) {
		$v->addError(0, "No items selected.");
	}

	foreach ($qty as $id=>$value) {
		if (is_numeric($invid) && is_numeric($value)) {
			$sql = "
			SELECT qty FROM hire.hire_nons_inv_items
			WHERE id='$id'";
			$qty_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
			$inv_qty = pg_fetch_result($qty_rslt, 0);

			if ($value > $inv_qty || $value <= 0) {
				$v->addError(0, "Invalid quantity to be returned");
			}
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	pglib_transaction("BEGIN");

	// Update descriptions
	foreach ($description as $key=>$value) {
		$sql = "UPDATE hire.hire_nons_inv_items SET description='$value' WHERE id='$key'";
		db_exec($sql) or errDie("Unable to update descriptions.");
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$td = $inv['odate'];

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($td) >= strtotime($blocked_date_from) AND strtotime($td) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	db_connect();
	# cust % bank
	if($ctyp == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);

		$details = "
		<tr><td>$cus[surname]</td></tr>
		<tr><td>".nl2br($cus['addr1'])."</td></tr>
		<tr><td>VAT No. $cus[vatnum]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $cus['surname'];
	} elseif($ctyp == 'c') {
		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $inv['cusname'];
	} else {
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $inv['cusname'];
	}
# Begin updates
	$refnum = getrefnum();

	/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT","NO VAT");
	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

	/* - End Hooks - */
	//lock(2);

	$real_invid = divlastid('inv', USER_DIV);

	//unlock(2);
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.");

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

        # Put in product
	$i = 0;
	$page = 0;
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$sql = "SELECT qty FROM hire.hire_invitems WHERE id='$stk[item_id]'";
		$stkqty_rslt = db_exec($sql) or errDie("Unable to retrieve hire qty total.");
		$stk["qty"] = pg_fetch_result($stkqty_rslt, 0);

		$sql = "SELECT basis, hours, weeks,
					extract('epoch' from from_date) AS e_from,
					extract('epoch' from to_date) AS e_to,
					total_days
				FROM hire.hire_invitems
				WHERE id='$stk[item_id]'";
		$hired_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$hired_data = pg_fetch_array($hired_rslt);

		// --------------------------------------------------------------------
		$sql = "SELECT * FROM cubit.assets WHERE id='$stk[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		$sql = "SELECT traddisc FROM hire.hire_invoices WHERE invid='$inv[hire_invid]'";
		$disc_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
		$traddisc = pg_fetch_result($disc_rslt, 0);

		$sql = "UPDATE hire.assets_hired SET return_time=CURRENT_TIMESTAMP
				WHERE item_id='$stk[item_id]'";
		db_exec($sql) or errDie("Unable to update hired assets.");
		if (isset($monthly) && !$monthly) {
			if (!isSerialized($asset_data["id"])) {
				$new_qty = $asset_data["serial2"] + $stk["qty"];

				$sql = "
				SELECT serial2 FROM cubit.assets
				WHERE id='$stk[asset_id]'";
				$serial2_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
				$serial2 = pg_fetch_result($serial2_rslt, 0);

				$sql = "
				UPDATE cubit.assets
				SET serial2=($serial2 + '{$qty[$stk["id"]]}')
				WHERE id='$stk[asset_id]'";
				db_exec($sql) or errDie("Unable to update assets.");
			}

			$hire_num = getHirenum($inv["hire_invid"]);
// 			if ($hire_num) {
// 				$sql = "SELECT * FROM hire.hire_invoices WHERE invnum='$hire_num'";
// 				$hi_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
// 				
// 				while ($hi_data = pg_fetch_array($hi_rslt)) {
// 					$sql = "DELETE FROM hire.hire_invitems
// 							WHERE invid='$hi_data[invid]'";
// 					db_exec($sql) or errDie("Unable to remove old items.");
// 				}
// 				
// 				$sql = "DELETE FROM hire.hire_invoices WHERE invnum='$hire_num'";
// 				db_exec($sql) or errDie("Unable to remove invoices.");
// 				
// 				$sql = "DELETE FROM hire.monthly_invoices WHERE invnum='$hire_num'";
// 				db_exec($sql) or errDie("Unable to remove invoices.");
// 				
// 				$sql = "UPDATE hire.assets_hired SET return_time=current_timestamp
// 						WHERE invnum='$hire_num'";
// 				db_exec($sql) or errDie("Unable to update return time.");
// 			}

			$sql = "SELECT invid FROM hire.hire_invoices WHERE invnum='$hire_num'";
			$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve hire notes.");
			
			$sql = "SELECT total_days FROM hire.hire_invitems
					WHERE id='$stk[item_id]'";
			$total_rslt = db_exec($sql) or errDie("Unable to retrieve total days.");
			$total_days = pg_fetch_result($total_rslt, 0);
			if (empty($total_days)) $total_days = 0;

			while (list($rem_invid) = pg_fetch_array($hinv_rslt)) {
				if ($stk["qty"] == $qty[$stk["id"]]) {
					$sql = "DELETE FROM hire.hire_invitems
							WHERE invid='$rem_invid' AND asset_id='$stk[asset_id]'";
					db_exec($sql) or errDie("Unable to remove items.");

					$sql = "DELETE FROM hire.reprint_invitems
							WHERE invid='$rem_invid' AND asset_id='$stk[asset_id]'";
					db_exec($sql) or errDie("Unable to remove items from reprint.");
				} else {
					$sql = "
					UPDATE hire.hire_invitems
					SET qty=(qty - {$qty[$stk["id"]]})
					WHERE invid='$rem_invid' AND asset_id='$stk[asset_id]'";
					db_exec($sql) or errDie("Unable to update items.");

					$sql = "UPDATE hire.reprint_invitems
							SET qty=(qty - {$qty[$stk["id"]]})
							WHERE invid='$rem_invid' AND asset_id='$stk[asset_id]'";
					db_exec($sql) or errDie("Unable to update items to reprint.");
				}
			}

			if ($stk["qty"] == $qty[$stk["id"]]) {
				$sql = "DELETE FROM hire.monthly_invitems
					WHERE invnum='$hire_num' AND asset_id='$stk[asset_id]'";
				db_exec($sql) or errDie("Unable to remove old items.");
			} else {
				$sql = "
				UPDATE hire.monthly_invitems
				SET qty=(qty - {$qty[$stk["id"]]})
				WHERE invnum='$hire_num' AND asset_id='$stk[asset_id]'";
				db_exec($sql) or errDie("Unable to update items.");
			}

			$sql = "UPDATE hire.assets_hired SET return_time=current_timestamp
					WHERE item_id='$stk[item_id]'";
			db_exec($sql) or errDie("Unable to remove old items.");
			
// 			$sql = "DELETE FROM hire.monthly_invoices
// 					WHERE invid='$inv[hire_invid]'";
// 			db_exec($sql) or errDie("Unable to remove monthly.");
			
// 			$sql = "DELETE FROM hire.hire_invitems
// 					WHERE invid='$inv[hire_invid]'";
// 			db_exec($sql) or errDie("Unable to remove monthly.");
		}
		$stkacc = $stkaccs[$stk['id']];

		$Sl = "SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$stk['vatex'] = "y";
		}

		//print $inv['chrgvat'];exit;

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$t = $inv['chrgvat'];

		$VATP = TAX_VAT;
		# keep records for transactions
		if(isset($totstkamt[$stkacc])){
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] += vats($stk['amt'], 'novat', $vd['vat_amount']);
				$va = 0;
				$inv['chrgvat'] = "";
			} else {
				$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va = sprint($stk['amt'] - vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount'] / 100);
				}
			}
		}else{
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] = $stk['amt'];
				$inv['chrgvat'] = "";
				$va = 0;
			} else {
				// Seems only this one is used for our hiring purposes
				$totstkamt[$stkacc] = $stk['amt'];
				$va = sprint($stk['amt'] - vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount'] / 100);
				}
			}
		}

// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat']);
// 		}
		$sql = "UPDATE hire.hire_nons_inv_items SET accid = '$stkacc' WHERE id = '$stk[id]'";
		$sRslt = db_exec($sql);

		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

// 		$time_from = "$from_day-$from_month-$from_year $from_hour:$from_minute";
// 		$time_to = "$to_day-$to_month-$to_year $to_hour:$to_minute";

		if ($hired_data["weeks"]) {
			$hired_days = sprint($hired_data["weeks"] * 7);
		} elseif ($hired_data["e_from"] > 0) {
			$secs = $hired_data["e_to"] - $hired_data["e_from"];
			$hired_days = sprint(($secs / (60 * 60 * 24)) + 1);
		} elseif ($hired_data["hours"]) {
			$secs = $hired_data["hours"] / 24;
			$hired_days = sprint($secs);
		} else {
			$hired_days = 0;
		}

		if ($hired_data["total_days"] > 0) {
			$hired_days = $hired_data["total_days"];
		}

		$hired_days = floor($hired_days);

		switch ($hired_data["basis"]) {
			case "per_hour":
				$basis = "Hourly";
				$basis_s = "hour";
				$basis_p = "per_hour";
				break;
			case "per_day":
				$basis = "Daily";
				$basis_s = "day";
				$basis_p = "per_day";
				break;
			case "per_week":
				$basis = "Weekly";
				$basis_s = "week";
				$basis_p = "per_week";
		}

		$rate = basisPrice($inv["cusnum"], $stk["asset_id"], $basis_p);
		if (empty($rate)) $rate = "0.00";

		$sql = "UPDATE hire.hire_nons_inv_items SET hired_days='$hired_days',
			rate='$rate' WHERE id='$stk[id]'";
		db_exec($sql) or errDie("Unable to save to items.");
/*
		$sql = "SELECT total_days FROM hire.hire_invitems
				WHERE id='$stk[item_id]'";
		$total_rslt = db_exec($sql) or errDie("Unable to retrieve total days.");
		$total_days = pg_fetch_result($total_rslt, 0);
		if (empty($total_days)) $total_days = 1;
 */
		$total_days = $hired_days;
		$products[$page][] = "
		<tr valign='top'>
			<td style='border-right: 2px solid #000'>$ex $stk[description]&nbsp;</td>
			<td style='border-right: 2px solid #000'>{$qty[$stk["id"]]}&nbsp;</td>
			<td style='border-right: 2px solid #000'>$total_days&nbsp;</td>
			<td align='right' style='border-right: 2px solid #000'>($basis) ".sprint($rate)." &nbsp;</td>
			<td align='right'>".CUR.sprint($stk["amt"])."&nbsp;</td>
		</tr>";

		$i++;
	}

	$inv['chrgvat'] = $t;

 	$blank_lines = 25;
	foreach ($products as $key=>$val) {
		$count = 0;
		if ($key == $page) {
			$sql = "
			SELECT count(id) FROM hire.hire_stock_items
			WHERE invid='".monthly_invid($inv["hire_invid"])."'";
			$count_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");
			$count = pg_fetch_result($count_rslt, 0) + 1;
		}

 		$bl = $blank_lines - count($products[$key]) - $count;
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "
 			<tr>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
  				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td>&nbsp;</td>
 			</tr>";
 		}
 	}

	$sql = "
		INSERT INTO hire.hires (
			inv_id, user_id, cust_id, from_time
		) VALUES (
			'$inv[invid]', ".USER_ID.", '$inv[cusnum]', CURRENT_TIMESTAMP
		)";
	db_exec($sql) or errDie("Unable to create new hire.");

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","novat");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");


	if(isset($bankid)) {
		$bankid += 0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'>Bank not Found.</li>";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$deptd = pg_fetch_array($deptRslt);
		}

		db_conn('core');

		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$rd = db_exec($Sl) or errDie("Unable to get data.");
		$data = pg_fetch_array($rd);

		$BA = $data['accnum'];
	}

	$tot_post = 0;
	# bank  % cust
	if($ctyp == 's'){
		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class='err'>Department not Found.</li>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp = 0;

		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt) {

			$wamt += $inv["delivery"] / count($totstkamt);
			$wamt -= $inv["discount"] / count($totstkamt);

			# Debit Customer and Credit stock
			$tot_post += $wamt;

			writetrans($dept['debtacc'], $stkacc, $td, $refnum, $SUBTOT, "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($dept['debtacc'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cus[surname].");
		}

		$sdate = date("Y-m-d");
	}else{

		if(!isset($accountc)) {
			$accountc = 0;
		}

		if(!isset($dept['pca'])) {
			$accountc += 0;
			$dept['pca'] = $accountc;
			$dept['debtacc'] = $accountc;
		}

		if(isset($bankid)) {
			$dept['pca'] = $BA;

		}

		$tpp = 0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			if(!(isset($cust['surname']))) {
				$cust['surname'] = $inv['cusname'];
				$cust['addr1'] = $inv['cusaddr'];
			}

			# Debit Customer and Credit stock
			$wamt += $inv["delivery"] / count($totstkamt);
			$tot_post += $wamt;

			writetrans($dept['pca'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cust[surname].");
		}

		if(isset($bankid)) {
			db_connect();
			$bankid += 0;
			$sql = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, 
					descript, cheqnum, 
					amount, vat, chrgvat, banked, accinv, div
				) VALUES (
					'$bankid', 'deposit', '$td', '$inv[cusname]', 
					'Non-Stock Sales on invoice No.$real_invid customer $inv[cusname]', '0', 
					'$TOTAL', '$VAT', '$inv[chrgvat]', 'no', '$stkacc', '".USER_DIV."'
				)";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$sql = "UPDATE cubit.hire_nons_invoices SET jobid='$bankid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cust[surname].");
		}

		$sdate = date("Y-m-d");
	}

	$tot_post = sprint($tot_post);

	db_connect();
	if($ctyp == 's'){
		$sql = "UPDATE cubit.nons_invoices SET cusid = '$cusnum', ctyp = '$ctyp', cusname = '$cus[surname]', cusaddr = '$cus[addr1]', cusvatno = '$cus[vatnum]', done = 'y', invnum = '$real_invid', balance = total WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
//
// 		# Record the payment on the statement
// 		$sql = "INSERT INTO stmnt(cusnum, invid, docref, amount, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL','$inv[odate]', 'Non-Stock Invoice', '".USER_DIV."')";
// 		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
//
// 		# Record the payment on the statement
// 		$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL','$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."')";
// 		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
//
// 		# Update the customer (make balance more)
// 		$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
// 		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
//
// 		# Make ledge record
// 		custledger($cusnum,$stkacc , $td, $real_invid, "Non Stock Invoice No. $real_invid", $TOTAL, "d");
// 		custDT($TOTAL, $cusnum, $td);
//
// 		$tot_dif=sprint($tot_post-$TOTAL);

		if (!isset($tot_dif)) $tot_dif = 0;

		if($tot_dif > 0) {
			writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif < 0) {
			$tot_dif = $tot_dif * -1;
			writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	} else {
		$date = date("Y-m-d");

		$sql = "UPDATE cubit.nons_invoices SET balance=total, cusname = '$cust[surname]', accid = '$dept[pca]', ctyp = '$ctyp', cusaddr = '$cust[addr1]', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

		$tot_dif = sprint($tot_post-$TOTAL);

		if($tot_dif > 0) {
			writetrans($varacc,$dept['pca'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif < 0) {
			$tot_dif = $tot_dif * -1;
			writetrans($dept['pca'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	}

	db_connect();
	$sql = "
		INSERT INTO salesrec (
			edate, invid, invnum, debtacc, vat, 
			total, typ, div
		) VALUES (
			'$inv[odate]', '$invid', '$real_invid', '$dept[debtacc]', '$VAT', 
			'$TOTAL', 'non', '".USER_DIV."'
		)";
	$recRslt = db_exec($sql);

	com_invoice($inv['salespn'],($TOTAL-$VAT),0,$real_invid,$inv["odate"]);

	db_conn('cubit');

	if(!isset($cusnum))
		$cusnum = 0;

	$Sl = "
		INSERT INTO sj (
			cid, name, des, date, 
			exl, vat, inc, div
		) VALUES (
			'$cusnum', '$na', 'Non stock Invoice $real_invid', '$inv[sdate]', 
			'".sprint($TOTAL-$VAT)."', '$VAT', '".sprint($TOTAL)."', '".USER_DIV."'
		)";
	$Ri = db_exec($Sl);

	// Customer Statement -----------------------------------------------------
	# Record the payment on the statement
	$sql = "
		INSERT INTO stmnt (
			cusnum, invid, docref, amount, 
			date, type, div
		) VALUES (
			'$inv[cusnum]', '$real_invid', '$inv[cordno]', '$TOTAL',
			'$inv[odate]', 'Hire Invoice H$real_invid', '".USER_DIV."'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record");

	# Record the payment on the statement
	$sql = "
		INSERT INTO open_stmnt (
			cusnum, invid, docref, amount, balance, 
			date, type, div
		) VALUES (
			'$inv[cusnum]', '$inv[invid]', '$inv[invnum]', '$TOTAL', '$TOTAL', 
			'$inv[odate]', 'Hire Invoice no H$real_invid', '".USER_DIV."'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record.");

	# Update the customer (make balance more)
	$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2))
			WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$stkacc = qryAccountsName("Hire Sales");
	$stkacc = $stkacc["accid"];

	# Make ledger record
	custledger($inv["cusnum"], $stkacc, $inv["odate"], $inv["invid"],
		"Hire Invoice No. H$real_invid", $TOTAL, "d");
	custDT($TOTAL, $inv["cusnum"], $inv["odate"]);
	// ------------------------------------------------------------------------

	# Get selected stock in this invoice
	$sql = "SELECT * FROM hire.hire_nons_inv_items
			WHERE invid='$invid' AND div='".USER_DIV."'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$item_count = pg_num_rows($item_rslt);
	
	$totamt = 0;
	while ($item_data = pg_fetch_array($item_rslt)) {
		$totamt += $item_data["amt"];

		$sql = "SELECT * FROM cubit.assets WHERE id='$item_data[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		$discount = $item_data["amt"] / 100 * $traddisc;

		// Add up revenue
		$sql = "
			INSERT INTO hire.revenue (
				group_id, asset_id, total, discount,
				hire_invnum, inv_invnum, cusname
			) VALUES (
				'$asset_data[grpid]', '$item_data[asset_id]', '$item_data[amt]', '$discount', 
				'$hirenum', '$real_invid', '$inv[cusname]'
			)";
		db_exec($sql) or errDie("Unable to update revenue");

		$sql = "
			INSERT INTO cubit.nons_inv_items (
				invid, qty, description, div, 
				amt, unitcost, vatex, accid, 
				asset_id
			) VALUES (
				'$invid', '$item_data[qty]', '$item_data[description]', '$item_data[div]', 
				'$item_data[amt]', '$item_data[amt]', '2', '$item_data[accid]', 
				'$item_data[asset_id]'
			)";
		db_exec($sql) or errDie("Unable to add non stock items.");

		$sql = "UPDATE hire.assets_hired SET return_time=CURRENT_TIMESTAMP,
					inv_invnum='$real_invid', value='$item_data[amt]'
				WHERE item_id='$item_data[item_id]'";
		db_exec($sql) or errDie("Unable to record asset return time.");
	}

	$sql = "SELECT * FROM hire.hire_stock_items WHERE invid='".monthly_invid($inv["hire_invid"])."'"; 
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$totamt += $stock_data["amount"];

		$discount = $stock_data["amount"] / 100 * $traddisc;

		$sql = "
			INSERT INTO hire.revenue (
				group_id, asset_id, total, discount,
				hire_invnum, inv_invnum, cusname
			) VALUES (
				'0', '$stock_data[stkid]', '$stock_data[amount]', '$discount',
				'$hirenum', '$real_invid', '$inv[cusname]'
			)";
		db_exec($sql) or errDie("Unable to add to revenue");
	}

	// Add the delivery discount to the total revenue
	if ($inv["delivery"]) {
		$discount = $inv["delivery"] / 100 * $traddisc;

		$sql = "INSERT INTO hire.revenue (discount) VALUES ('$discount')";
		db_exec($sql) or errDie("Unable to update revenue");
	}

	$cc = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', ''); </script>";


	 db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<tr><td><b>Sales Person:</b> $inv[salespn]</td></tr>";
	} else {
		$sp = "";
	}
	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}

	if ($inv["remarks"] == "") {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve the default comments from Cubit.");
		$inv["remarks"] = pg_fetch_result($commRslt, 0);
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Retrieve the company information
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='2' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;
	";

// 	$nolr_borders = "
// 		border-top: 2px solid #000;
// 		border-left: none;
// 		border-right: none;
// 		border-bottom: none;
// 	";

	$sql = "UPDATE hire.hire_invoices SET done='y', delivery='0.00'
			WHERE invnum='".getHirenum($inv["hire_invid"])."'";
	db_exec($sql) or errDie("Unable to update invoices.");

	vatr($vd['id'], $td, "OUTPUT", $vd['code'], $refnum,
		"Non-Stock Sales, invoice No.$real_invid", $TOTAL, $inv["vat"]);

	$details = "";

	$SUBTOT = sprint($totamt);

	for ($i = 0; $i <= $page; $i++) {
		if ($monthly) {
			$monthly_out = "
			<tr>
				<td style='border-right: 2px solid #000'>Invoiced to date ".date("d-m-Y")."</td>
			</tr>";
		} else {
			$monthly_out = "";
		}

		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$sql = "SELECT count(id) FROM hire.hire_stock_items WHERE invid='".monthly_invid($inv["hire_invid"])."'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve stock count.");
		$count = pg_fetch_result($count_rslt, 0);

		$stock_out = "";
		if ($i == $page && $count > 0) {
			$stock_out = "
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='
					border-bottom: 2px solid #000;
					border-top: 2px solid #000;
					border-right: 2px solid #000'>
						<b>Stock</b>
					</td>
					<td style='
					border-bottom: 2px solid #000;
					border-top: 2px solid #000;
					border-right: 2px solid #000'>
						<b>Qty</b>
					</td>
					<td style='
					border-bottom: 2px solid #000;
					border-top: 2px solid #000;
					border-right: 2px solid #000'>
						<b>Unit Price</b>
					</td>
					<td style='
					border-bottom: 2px solid #000;
					border-top: 2px solid #000;
					boreder-right: 2px solid #000;'
					align='right'>
						<b>Amount</b>
					</td>
				</tr>";
			$sql = "
			SELECT whname, stkcod, stkdes, qty, unitcost, amount FROM hire.hire_stock_items
				LEFT JOIN exten.warehouses ON hire_stock_items.whid=warehouses.whid
				LEFT JOIN cubit.stock ON hire_stock_items.stkid=stock.stkid
			WHERE invid='".monthly_invid($inv["hire_invid"])."'";
			$stock_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
			while ($stock_data = pg_fetch_array($stock_rslt)) {
				$stock_out .= "
				<tr>
					<td style='border-right: 2px solid #000'>
						$stock_data[stkcod] - $stock_data[stkdes]
					</td>
					<td style='border-right: 2px solid #000'>
						$stock_data[qty]
					</td>
					<td style='border-right: 2px solid #000' align='right'>$stock_data[unitcost]</td>
					<td align='right'>$stock_data[unitcost]</td>
				</tr>";
//					<td align='right'>$stock_data[amount]</td> <--------- hack for price
				$SUBTOT += $stock_data["amount"];
			}
			$stock_out .= "
			</table>
			</td></tr>";
		}

		if ($pcash > 0) {
			$cash_out = "
			<td stle='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Paid Cash</b></td>
			<td><b>".CUR.sprint($pcash)."</b></td>";
		} else {
			$cash_out = "";
		}

		if ($pcheque > 0) {
			$cheque_out = "
			<td stle='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Paid Cheque</b></td>
			<td><b>".CUR.sprint($pcheque)."</b></td>";
		} else {
			$cheque_out = "";
		}

		if ($pcc > 0) {
			$credit_out = "
			<td stle='border-ddbottom: 2px solid #000; border-right: 2px solid #000;'><b>Paid Credit</b></td>
			<td><b>".CUR.sprint($pcc)."</b></td>";
		} else {
			$credit_out = "";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$details .= "
		<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left' rowspan='2'><img src='../compinfo/getimg.php' width='230' height='47'></td>
					<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Invoice</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr1]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr1]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr2]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr2]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr3]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr3]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr4]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[postcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>REG:</b> $comp_data[regnum]</b>&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>$bank_data[bankname]</b>&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT REG:</b> $comp_data[vatnum]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch</b> $bank_data[branchname]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Tel:</b> $comp_data[tel]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch Code:</b> $bank_data[branchcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Fax:</b> $comp_data[fax]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Acc Num:</b> $bank_data[accnum]&nbsp;</td>
				</tr>
			</table>
			</td><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date</b></td>
					<td><b>Page Number</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$inv[odate]</td>
					<td>".($i + 1)."</td>
				</tr>

				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr><td>&nbsp</td></tr>
				<tr><td>&nbsp</td></tr>

				<tr>
					<td colspan='2'><b>Invoice No:</b> $real_invid</td>
				</tr>
				<tr>
					<td colspan='2'><b>Hire No:</b> $hirenum</td>
				</tr>
				$sp
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Tax Invoice To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$cus[surname]</b>&nbsp;</td>
					<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
					<td width='33%'><b>Delivery Address</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>".nl2br($cus["addr1"])."&nbsp;</td>
					<td style='border-right: 2px solid #000'>".nl2br($cus["paddr1"])."&nbsp;</td>
					<td>".nl2br($cus["del_addr1"])."&nbsp;</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $cus[vatnum]</td>
					<td width='33%'><b>Customer Order No:</b> $inv[cordno]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>No of Days</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Rate</b></td>
					<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
				</tr>
				$products_out
			</table>
			</td></tr>
			$stock_out
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$inv[remarks]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms:</b> $inv[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td><b>".CUR.sprint($inv["subtot"])."</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Delivery</b></td>
					<td><b>".CUR."$inv[delivery]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Discount</b></td>
					<td><b>".CUR."$inv[discount]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td><b>".CUR."$inv[vat]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Signed:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td><b>".CUR."$inv[total]</b></td>
				</tr>
				<tr>	
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
					$cash_out
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					$credit_out
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					$cheque_out
				</tr>
				$monthly_out
			</table>
		</table>
		";
	}

	//update_amounts($inv["invid"]);

	$amt = $pcash + $pcheque + $pcc;
	$_POST["amt"] = $amt;
	$_POST["date"] = $inv["odate"];

	$sql = "
	SELECT id, stock.stkid, hire_stock_items.whid, qty,
		hire_stock_items.vatcode, unitcost, amount, csprice
	FROM hire.hire_stock_items
		LEFT JOIN cubit.stock ON hire_stock_items.stkid=stock.stkid
	WHERE invid='".monthly_invid($inv["hire_invid"])."'";
	$stk_rslt = db_exec($sql) or errDie("Unable to update stock.");

	while ($stk_data = pg_fetch_array($stk_rslt)) {
		$sql = "
		UPDATE cubit.stock SET units=(units-'$stk_data[qty]')
		WHERE stkid='$stk_data[stkid]'";
		db_exec($sql) or errDie("Unable to update stock items.");

		$sql = "DELETE FROM hire.hire_stock_items WHERE id='$stk_data[id]'";
		db_exec($sql) or errDie("Unable to remove old items.");

		$sql = "SELECT invnum FROM hire.hire_invoices WHERE invid='$inv[hire_invid]'";
		$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
		$hinvnum = pg_fetch_result($hinv_rslt, 0);

		$sql = "
			INSERT INTO hire.hire_stock_items_reprint (
				invnum, stkid, whid, qty,
				vatcode, unitcost, amount, hire_invnum
			) VALUES (
				'$real_invid', '$stk_data[stkid]', '$stk_data[whid]', '$stk_data[qty]', 
				'$stk_data[vatcode]', '$stk_data[unitcost]', '$stk_data[amount]', '$hinvnum'
			)";
		db_exec($sql) or errDie("Unable to add to reprints.");

		$cos_acc = qryAccountsName("Cost of Sales");
		$cos_acc = $cos_acc["accid"];

		$inv_acc = qryAccountsName("Inventory");
		$inv_acc = $inv_acc["accid"];

		$csprice = $stk_data["qty"] * $stk_data["csprice"];

		writetrans($cos_acc, $inv_acc, $td, $refnum, $csprice,
			"Cost of Sales for Hire Invoice $real_invid");
	}

	recvpayment_write();

	$sql = "
	UPDATE cubit.nons_invoices SET cash='$pcash', cheque='$pcheque',
		credit='$pcc'
	WHERE invid='$inv[invid]'";
	db_exec($sql) or errDie("Unable to update cash value.");

	pglib_transaction("COMMIT");
	
	// Retrieve the template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$OUTPUT = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');</script>
			$details";
		require("../tmpl-print.php");
	} else {
		$OUTPUT = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');
		move (\"../$template?invid=$inv[invid]&type=nons\");
		</script>";
		require ("template.php");
	}

}




# Customer details
function cdetails($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	if(!isset($button)&&(isset($starting))) {
		return slct($_GET);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>$err";
		return $confirm;
	}




	if($ctyp == "ac") {
		return acdetails($_GET);
	}

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$details = "";

	if($ctyp == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cust = pg_fetch_array($custRslt);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$cust[addr1]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$cust[vatnum]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Balance (Excl this Invoice)</td>
					<td>".CUR.sprint($cust["balance"])."</td>
				</tr>
			</table>";
	}elseif($ctyp == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='deptid' value='$deptid'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>
			</table>";
	}elseif($ctyp == 'cb'){
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		} else {
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='bankid' value='$inv[accid]'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td>$supacc</td>
				</tr>
			</table>";
	}

	$stkacc = "";
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}
			$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
	$stkacc .= "</select>";

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='5%'>#</th>
				<th width='55%'>DESCRIPTION</th>
				<th width='10%'>QTY</th>
				<th width='10%'>UNIT PRICE</th>
				<th width='10%'>AMOUNT</th>
				<th width='10%'>ACCOUNT</th>
			<tr>";

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatex]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td>$ex $stkd[description]</td>
				<td>$stkd[qty]</td>
				<td>$stkd[unitcost]</td>
				<td>".CUR." $stkd[amt]</td>
				<td ".ass("Select the account you wish to Credit")."><select name='stkaccs[$stkd[id]]'>$stkacc</td>
			</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Non-Stock Invoice Details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='cconfirm'>
			<input type='hidden' name='invid' value=$invid>
			<input type='hidden' name='ctyp' value=$ctyp>
		<table ".TMPL_tblDflts." width=95%>
			<tr>
				<td valign='top'>$details</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr><th colspan='2'> Non-Stock Invoice Details </th></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>T $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Hire No.</td>
							<td valign='center'>H".getHirenum($inv["hire_invid"], 1)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$sday-$smon-$syear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'>$products</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>
								<a href='javascript:popupOpen(\"../nons-invoice-new.php\")'>New Non-Stock Invoices</a>
							</td>
							<td bgcolor='".bgcolorg()."' rowspan=4 align=center valign=top>".nl2br($inv['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='javascript:popupOpen(\"nons-invoice-view.php\")'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}

function recvpayment_write()
{

	if (isset($_POST["btn_back"])) {
		return details($_POST);
	}
	extract($_POST);

	$bank_acc = qryAccountsName("Cash on Hand");
	$bank_acc = $bank_acc["accid"];

	$cred_acc = qryAccountsName("POS Credit Card Control");
	$cred_acc = $cred_acc["accid"];

	$v = new validate();
	$v->isOk($cusnum, "num", 1, 10, "Invalid customer id.");
	$v->isOk($bank_acc, "num", 1, 10, "Invalid cash account selected.");
	$v->isOk($pcc, "float", 1, 40, "Invalid credit card amount.");
	$v->isOk($pcash, "float", 1, 40, "Invalid cash amount.");
	$v->isOk($pcheque, "float", 1, 40, "Invalid cheque amount.");
	$v->isOk($amt, "float", 1, 40, "Invalid total received amount.");
	$v->isOk($date, "date", 1, 1, "Invalid invoice date.");

	if ($v->isError()) {
		return details($_POST, $v->genErrors());
	}



	$sdate = $date;

	$cus = qryCustomer($cusnum);
	$dept = qryDepartment($cus["deptid"], "debtacc");
	$refnum = getrefnum();

	pglib_transaction("BEGIN");

	/* do the calculations/recordings */
	# update the customer (make balance less)
	$sql = "UPDATE cubit.customers SET balance = (balance - '$amt'::numeric(13,2))
			WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$sql = "SELECT prd,invnum,descrip,age FROM cubit.nons_invoices
			WHERE invid ='$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invalid Invoice Number.</li>";
	}

	$inv = pg_fetch_array($invRslt);

	$inv['invnum'] += 0;

	# reduce the money that has been paid
	if ($amt) {
		$sql = "UPDATE cubit.nons_invoices
				SET balance = (balance - $amt::numeric(13,2))
				WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		$sql = "UPDATE cubit.open_stmnt
				SET balance = (balance - $amt::numeric(13,2))
				WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# record the payment on the statement
		$sql = "
			INSERT INTO cubit.stmnt (
				cusnum, invid, amount, date, 
				type, div
			) VALUES (
				'$cus[cusnum]', '$inv[invnum]', '".($amt - ($amt * 2))."', '$sdate', 
				'Payment for Hire Invoice No. $inv[invnum]', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		$cash_amt = $pcash + $pcheque;
		$cred_amt = $pcc;

		custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Hire Invoice No. $inv[invnum]", $cash_amt, "c");
		custledger($cus["cusnum"], $cred_acc, $sdate, $inv["invnum"], "Payment for Hire Invoice No. $inv[invnum]", $cred_amt, "c");

		custCT($amt, $cus["cusnum"], $sdate);
		//recordCT($amt, $cus['cusnum'],$inv['age'],$sdate);
	}

	if (!isset($invids[$key])) $invids[$key] = 0;
	if (!isset($rinvids)) $rinvids = 0;
	if (!isset($amounts)) $amounts = 0;
	if (!isset($invprds)) $invprds = 0;
	if (!isset($rages)) $rages = 0;
	if (!isset($invidsers)) $invidsers = 0;

	$rinvids .= "|$invids[$key]";
	$amounts .= "|$amt";
	$invprds .= "|0";
	$rages .= "|$inv[age]";
	$invidsers .= " - $inv[invnum]";

	$sql = "SELECT * FROM core.accounts WHERE topacc='6400' AND accnum='000'";
	$acc_rslt = db_exec($sql);
	$deptacc = pg_fetch_result($acc_rslt, 0);

	if ((float)$pcash) {
		writetrans($bank_acc, $deptacc, $sdate, $refnum, $pcash,
			"Payment for Invoice $inv[invnum] from customer $cus[cusname] $cus[surname]");
	}
	if ((float)$pcc) {
		$sql = "SELECT accid FROM core.accounts WHERE topacc='7300' AND accnum='000'";
		$acc_rslt = db_exec($sql);
		$accid = pg_fetch_result($acc_rslt, 0);

		writetrans($accid, $deptacc, $sdate, $refnum, $pcc,
			"Payment for Invoice $inv[invnum] from customer $cus[cusname] $cus[surname]");
	}
	if ((float)$pcheque) {
		$sql = "SELECT accid FROM core.accounts WHERE topacc='7200' AND accnum='000'";
		$acc_rslt = db_exec($sql);
		$accid = pg_fetch_result($acc_rslt, 0);

		writetrans($accid, $deptacc, $sdate, $refnum, $pcheque,
			"Payment for Invoice $inv[invnum] from customer $cus[cusname] $cus[surname]");
	}

	db_conn('cubit');

	pglib_transaction("COMMIT");

	$_POST["pcc"] = $_POST["pcheque"] = $_POST["pcash"] = "0.00";

	return cdetails($_POST, "<li class='err'>Payment received successfully</li>");

}

function recordCT($amount, $cusnum, $age, $date="", $changemon = false)
{

	db_connect();
	if ($date == "") {
		$date = date("Y-m-d");
	}

	$amount = ($amount * (-1));
	$date_ins = "'$date'";

	$sql = "
		INSERT INTO custran (
			cusnum, odate, balance, div, age
		) VALUES (
			'$cusnum', $date_ins, '$amount', '".USER_DIV."', '$age'
		)";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.");

}




function allocamt(&$tot, $invbal)
{

	if ($tot >= $invbal) {
		$val = $invbal;
		$tot -= $invbal;
	} else {
		$val = $tot;
		$tot = 0;
	}

	return sprint($val);

}




# vats
function vats($amt, $inc, $VATP)
{

	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	} elseif($inc == "yes") {
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	} else {
		$ret = ($amt);
	}

	return $ret;

}




function update_amounts($invid)
{

	$subtotal = 0;

	$sql = "
	SELECT hire_invnum, hire_invid FROM cubit.nons_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	list($hire_invnum, $hire_invid) = pg_fetch_array($inv_rslt);

	$sql = "SELECT id, qty FROM hire.hire_invitems WHERE invid='$hire_invid'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve items.");
	if (!pg_num_rows($rslt)) return;

	while (list($id, $qty) = pg_fetch_array($rslt)) {
		$sql = "SELECT unitcost FROM hire.hire_nons_inv_items WHERE item_id='$id'";
		$unitcost_rslt = db_exec($sql) or errDie("Unable to retrieve cost.");
		$unitcost = pg_fetch_result($unitcost_rslt, 0);
		
		$amt = $unitcost * $qty;
		$subtotal += $amt;

		$sql = "UPDATE hire.hire_invitems SET amt='$amt' WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update line total.");

		$sql = "UPDATE hire.reprint_invitems SET amt='$amt' WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update line total.");

		$sql = "UPDATE hire.monthly_invitems SET amt='$amt' WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update monthly line total.");

		$sql = "UPDATE hire.hire_nons_inv_items SET amt='$amt' WHERE item_id='$id'";
		db_exec($sql) or errDie("Unable to update non-stock line total.");
	}
	
	$vat = ($subtotal / 100) * 14;
	$total = $subtotal + $vat;

	$sql = "UPDATE hire.hire_invoices SET vat='$vat', subtot='$subtotal', total='$total' WHERE invid='$hire_invid'";
	db_exec($sql) or errDie("Unable to update invoice.");

	$sql = "UPDATE hire.monthly_invoices SET vat='$vat', subtot='$subtotal', total='$total' WHERE invid='$hire_invid'";
	db_exec($sql) or errDie("Unable to update monthly invoice.");

	$sql = "UPDATE cubit.nons_invoices SET vat='$vat', subtot='$subtotal', total='$total' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update non-stock invoice.");
	return;

}




function update_qty()
{

	extract($_REQUEST);

	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$sql = "SELECT traddisc FROM hire.hire_invoices WHERE invid='$inv[hire_invid]'";
	$disc_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
	$traddisc = pg_fetch_result($disc_rslt, 0);

	$total_amt = 0;
	$vattot = 0;
	foreach ($qty as $item_id=>$new_qty) {
		$sql = "
		SELECT unitcost, item_id AS note_item_id
		FROM hire.hire_nons_inv_items
		WHERE id='$item_id'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$item = pg_fetch_array($item_rslt);

		$sql = "SELECT basis, hours, weeks, qty,
					extract('epoch' from from_date) AS e_from,
					extract('epoch' from to_date) AS e_to
				FROM hire.hire_invitems
				WHERE id='$item[note_item_id]'";
		$hired_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$hired_data = pg_fetch_array($hired_rslt);

		if ($new_qty > $hired_data["qty"] || $new_qty < 1) {
			return cconfirm("
			<li class='err'>
				Invalid quantity, check that the quantity you're trying to
				return is not more than what was hired out.
			</li>");
		}
				

		if ($hired_data["weeks"]) {
			$hired_days = sprint($hired_data["weeks"] * 7);
		} elseif ($hired_data["e_from"] > 0) {
			$secs = $hired_data["e_to"] - $hired_data["e_from"];
			$hired_days = sprint(($secs / (60 * 60 * 24)) + 1);
		} elseif ($hired_data["hours"]) {
			$secs = $hired_data["hours"] / 24;
			$hired_days = sprint($secs);
		} else {
			$hired_days = 0;
		}

		$hired_days = floor($hired_days);


		$amt = $item["unitcost"] * $new_qty;
		$total_amt += $amt;

		if ($traddisc > 0){
			$vattot += (($amt/100) * (100 - $traddisc)) / 100 * 14;
		}else {
			$vattot += $amt / 100 * 14;
		}

		$sql = "
		UPDATE hire.hire_nons_inv_items SET amt='$amt', qty='$new_qty'
		WHERE id='$item_id'";
		db_exec($sql) or errDie("Unable to update item amount.");
	}

	if (isset($stock_qty) && is_array($stock_qty)) {
		foreach ($stock_qty as $id=>$value) {
			$sql = "SELECT stkid, unitcost, qty, excl_amount, vatamount FROM hire.hire_stock_items WHERE id='$id'";
			$cost_rslt = db_exec($sql) or errDie("Unable to retrieve cost.");
			list($stkid, $cost, $oqty, $excl, $vatamount) = pg_fetch_array($cost_rslt);
			$cost += 0;

			if ($traddisc > 0){
				if ($oqty != 0)
					$stkamt = sprint(((($excl/$oqty) * $value) / 100) * (100 - $traddisc));
			}else {
				if ($oqty != 0)
					$stkamt = sprint(($excl/$oqty) * $value);
			}
			if (!isset($stkamt))
				$stkamt = 0;
			$total_amt += $stkamt;

			if ($traddisc > 0){
				if ($oqty != 0)
					$vattot += ((($vatamount / $oqty) * $value) / 100) * (100 - $traddisc);
			}else {
				if ($oqty != 0)
					$vattot += ($vatamount / $oqty) * $value;
			}

			if ($oqty == 0)
				$oqty = 1;

			$sql = "
			UPDATE cubit.stock SET alloc=((alloc-'$oqty')+'$value')
			WHERE stkid='$stkid'";
			db_exec($sql) or errDie("Unable to update stock allocation.");

			$sql = "
			UPDATE hire.hire_stock_items SET amount='$stkamt', qty='$value', excl_amount=excl_amount/$oqty*$value, vatamount=vatamount/$oqty*$value
			WHERE id='$id'";
			db_exec($sql) or errDie("Unable to update amount.");
		}
	}

	// Retrieve trade discount and delivery cost
	$sql = "
	SELECT traddisc, delivery FROM hire.hire_invoices
	WHERE invid='$inv[hire_invid]'";
	$note_rslt = db_exec($sql) or errDie("Unable to retrieve hire note.");
	list($traddisc, $delivery) = pg_fetch_array($note_rslt);

	$traddiscamt = sprint(($total_amt / 100) * $traddisc);
	$subtotal = sprint($total_amt - $traddiscamt + $delivery);
	$vat = sprint ($vattot);//sprint(($subtotal/100)*14);
	$total = sprint($subtotal + $vat);

	$sql = "
	UPDATE cubit.nons_invoices SET discount='$traddiscamt',
		subtot='$subtotal', vat='$vat', total='$total'
	WHERE invid='$inv[invid]'";
	db_exec($sql) or errDie("Unable to update invoice.");

	/*$total_amt = 0;
	while ($items = pg_fetch_array($items_rslt)) {
		$amt = ($items["rate"] * $items["hired_days"]) * $items["qty"];
		$unitcost = $amt / $items["qty"];

		$sql = "
		UPDATE hire.hire_nons_inv_items SET amt='$amt', unitcost='$unitcost'
		WHERE item_id='$items[id]'";
		db_exec($sql) or errDie("Unable to update qtys");

		$total_amt += $amt;
	}

	// Retrieve trade discount and delivery cost
	$sql = "
	SELECT traddisc, delivery FROM hire.hire_invoices
	WHERE invid='$inv[hire_invid]'";
	$note_rslt = db_exec($sql) or errDie("Unable to retrieve hire note.");
	list($traddisc, $delivery) = pg_fetch_array($note_rslt);

	$traddiscamt = ($total_amt/100)*$traddisc;
	$subtotal = $total_amt - $traddisc + $delivery;

	$sql = "
	UPDATE cubit.nons_invoices SET discount='$traddiscamt',
		delivery='$delivery', subtot='$subtotal'
	WHERE invid='$inv[hire_invid]'";

	$sql = "
	UPDATE hire.hire_invoices SET delivery='0'
	WHERE invid='$inv[hire_invid]'";
	db_exec($sql) or errDie("Unable to update hire invoice.");*/

	return cconfirm();

}



?>

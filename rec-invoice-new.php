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
require("libs/ext.lib.php");

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$OUTPUT = details($_GET);
} else if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
			case "details":
				$OUTPUT = details($_POST);
				break;
		}
	} else {
		$OUTPUT = details($_POST);
	}
}

# get templete
require("template.php");




# Default view
function view()
{

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Recurring Invoice</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("rec-invoice-view.php", "View Recurring Invoices"),
			ql("customers-new.php", "New Customer")
		);
	return $view;

}




# Default view
function view_err($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else {
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if(isset($deptid) && $dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Recurring Invoice</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' value='$letters' maxlength='5'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("rec-invoice-view.php", "View Recurring Invoices"),
			ql("customers-new.php", "New Customer")
		);
	return $view;

}




# create a dummy invoice
function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
//	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;

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
	$odate = "$date_year-$date_month-$date_day";

	// $invid = divlastid('inv', USER_DIV);

	# insert invoice to DB
	$sql = "
		INSERT INTO rec_invoices (
			deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, 
			delchrg, subtot, vat, total, balance, comm, username, done, prd, div
		) VALUES (
			'$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', 
			'$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 'n', '".PRD_DB."', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = pglib_lastid ("rec_invoices", "invid");
	return $invid;

}




# Details
function details($_POST, $error="")
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}

	if (isset($deptid)) {
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	}

	if (isset($letters)) {
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	
	
	if (!isset($deptid)) {
		$deptid = 0;
	} else if (isset($invid)) {
		db_conn("cubit");
		$sql = "UPDATE rec_invoices SET deptid='$deptid' WHERE invid='$invid' AND deptid<>'$deptid'";
		db_exec($sql) or errDie("Error updating invoice department.");
	}

	if(!isset($invid)){
		$invid = create_dummy($deptid);
		$stkerr = "0,0";
	}

	if (!isset($done)) {
		$done = "";
	}

	if (!isset($stkerr)) {
		$stkerr = "0,0";
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected Customer info
	if (isset($letters)) {
		db_connect();
		$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		if (pg_numrows ($custRslt) < 1) {

			db_connect();

			if ($inv['deptid'] == 0){
				$searchdept = "";
			}else {
				$searchdept = "deptid = '$inv[deptid]' AND ";
			}

			# Query server for customer info
			$sql = "
				SELECT * FROM customers 
				WHERE $searchdept location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' 
				ORDER BY surname";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
			if (pg_numrows ($custRslt) < 1) {
				$ajax_err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
				//return view_err($_POST, $err);
			}else{
				$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				$customers .= "<option value='-S' selected>Select Customer</option>";
				while($cust = pg_fetch_array($custRslt)){
					$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
				}
				$customers .= "</select>";
			}
			# Take care of the unset vars
			$cust['addr1'] = "";
			$cust['cusnum'] = "";
			$cust['vatnum'] = "";
			$cust['accno'] = "";
		}else{
			$cust = pg_fetch_array($custRslt);

			$sql = "SELECT * FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
			$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
			# Moarn if customer account has been blocked
			if($cust['blocked'] == 'yes'){
				$error .= "<li class='err'>Error : Selected customer account has been blocked.</li>";
			}

			// $customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
			$cusnum = $cust['cusnum'];

			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				// $customers .= "<option value='-S' selected>Select Customer</option>";
				while($cus = pg_fetch_array($cusRslt)){
					$sel = "";
					if($cust['cusnum'] == $cus['cusnum']){
						$sel = "selected";
					}
					$customers .= "<option value='$cus[cusnum]' $sel>$cus[cusname] $cus[surname]</option>";
				}
			$customers .= "</select>";
		}
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

//	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$whs = "<select name='whidss[]'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			if (!user_in_store_team($wh["whid"], USER_ID)) continue;
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# Get sales people
	db_conn("exten");

	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class='err'> There are no Sales People found in Cubit.</li>";
	}else{
		$salesps = "<select name='salespn'>";
		while($salesp = pg_fetch_array($salespRslt)){
			if($salesp['salesp'] == $inv['salespn']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$salesps .= "<option value='$salesp[salesp]' $sel>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}

	# Days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($inv['chrgvat'] == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}

	# Format date
	list($rinv_year, $rinv_month, $rinv_day) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */
	// get the ID of the first warehouse
	db_conn("exten");

	$sql = "SELECT whid FROM warehouses ORDER BY whid ASC LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading warehouses (FWH).");

	if ( pg_num_rows($rslt) > 0 ) {
		$FIRST_WH = pg_fetch_result($rslt, 0, 0);
	} else {
		$FIRST_WH = "-S";
	}

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>VAT CODE</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM recinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account'] += 0;
		if($stkd['account'] != 0) {

			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');

			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accounts[]'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				if($ad['accid'] == $stkd['account']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}
			$Accounts .= "</select>";

			$sernos = "";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";

			$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'>";

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($stkd['vatcode'] == $vd['id']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			# Put in product
			$products .= "
				<tr class='".bg_class()."'>
					<td colspan='2'>$Accounts<input type='hidden' name='whids[]' value='$stkd[whid]'></td>
					<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'>$Vatcodes</td>
					<td><input type='text' size='20' name='descriptions[]' value='$stkd[description]'> $sernos</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td><input type='hidden' name='disc[]' value='$stkd[disc]'><input type='hidden' name='discp[]' value='$stkd[discp]'></td>
					<td nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." ".sprint($stkd['amt'])."</td>
					<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
				</tr>";
			$key++;

		} else {

			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			# Get warehouse name
			db_conn("exten");

			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Get selected stock in this warehouse
			db_connect();

			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$sernos = "<input type='hidden' name='sernos[]' value='$stkd[serno]'>$stkd[serno]";

			# check permissions
			if(perm("invoice-unitcost-edit.php")){
				$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'>";
			}else{
				$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='$stkd[unitcost]'>$stkd[unitcost]";
			}

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($stkd['vatcode'] == $vd['id']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			# Put in product
			$products .= "
				<input type='hidden' name='accounts[]' value='0'>
				<input type='hidden' name='descriptions[]' value=''>
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					$sernos
					<td>$Vatcodes</td>
					<td>".extlib_rstr($stk['stkdes'], 30)."</td>
					<td><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
					<td>$viewcost</td>
					<td><input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%</td>
					<td nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." ".sprint($stkd['amt'])."</td>
					<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
				</tr>";
			$key++;
		}
	}

	# Look above(remprod keys)
	$keyy = $key;

	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}else{
		$SCROLL = "yes";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S" && isset($cust['pricelist'])){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# Get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				$sernos = "<input type='hidden' name='sernos[]' value=''>";

				# Get price from price list if it is set
				if(isset($cust['pricelist'])){
					# get selected stock in this warehouse
					db_conn("exten");
					$sql = "SELECT price FROM plist_prices WHERE listid = '$cust[pricelist]' AND stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
					$plRslt = db_exec($sql);
					if(pg_numrows($plRslt) > 0){
						$pl = pg_fetch_array($plRslt);
						$stk['selamt'] = $pl['price'];
					}
				}

				/* -- Start Some Checks -- */
				# check if they are selling too much
				if(($stk['units'] - $stk['alloc']) < $qtyss[$key]){
					if(!in_array($stk['stkid'], explode(",", $stkerr))){
						if($stk['type'] != 'lab'){
							$stkerr .= ",$stk[stkid]";
							$error .= "<li class='err'>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}
				/* -- End Some Checks -- */

				# Calculate the Discount discount
				if($discs[$key] < 1){
					if($discps[$key] > 0){
						$discs[$key] = round((($discps[$key]/100) * $stk['selamt']), 2);
					}
				}else{
					$discps[$key] = round((($discs[$key] * 100) / $stk['selamt']), 2);
				}

				# Calculate amount
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				$stk['selamt'] = sprint($stk['selamt']);

				# Check permissions
				if(perm("invoice-unitcost-edit.php")){
					$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stk[selamt]'>";
				}else{
					$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='$stk[selamt]'>$stk[selamt]";
				}

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[]'>
						<option value='0'>Select</option>";
				while($vd = pg_fetch_array($Ri)) {
					if($stk['vatcode'] == $vd['id']) {
						$sel = "selected";
					} else {
						$sel = "";
					}
					$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
				}
				$Vatcodes .= "</select>";

				$amt[$key] = sprint ($amt[$key]);

				# Put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[]' value='0'>
					<input type='hidden' name='descriptions[]' value=''>
					<tr class='".bg_class()."'>
						<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
						<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						$sernos
						<td>$Vatcodes</td>
						<td>".extlib_rstr($stk['stkdes'], 30)."</td>
						<td><input type='text' size='3' name='qtys[]' value='$qtyss[$key]'></td>
						<td>$viewcost</td>
						<td><input type='text' size='4' name='disc[]' value='$discs[$key]'> OR <input type='text' size='4' name='discp[]' value='$discps[$key]' maxlength='5'>%</td>
						<td nowrap><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			}elseif(isset($accountss[$key]) && $accountss[$key] != "0" && isset($cust['pricelist'])){

				db_conn('core');
				$Sl = "SELECT * FROM accounts WHERE accid='$accountss[$key]'";
				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

				if(pg_num_rows($Ri) < 1) {
					return "invalid.";
				}

				$ad = pg_fetch_array($Ri);

				# Calculate amount
				$amt[$key] = sprint($qtyss[$key] * ($unitcosts[$key]));

				# Input qty if not serialised
				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";

				# Check permissions
				$viewcost = "<input type='text' size='8' name='unitcost[]' value='$unitcosts[$key]'>";

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[]'>
						<option value='0'>Select</option>";
				while($vd = pg_fetch_array($Ri)) {
					if($vatcodess[$key] == $vd['id']) {
						$sel = "selected";
					} else {
						$sel = "";
					}
					$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
				}
				$Vatcodes .= "</select>";

				# Put in selected warehouse and stock
				$products .= "
					<tr class='".bg_class()."'>
						<td colspan='2'>$ad[accname]<input type='hidden' name='accounts[]' value='$accountss[$key]'><input type='hidden' name='whids[]' value='0'></td>
						<td>$Vatcodes<input type='hidden' name='stkids[]' value='0'></td>
						<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$key]'></td>
						<td>$qtyin</td>
						<td>$viewcost</td>
						<td><input type='hidden' name='disc[]' value='0'><input type='hidden' name='discp[]' value='0'></td>
						<td nowrap><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					# skip if not selected
					if($whid == "-S"){
						continue;
					}

					if(!isset($addnon)) {

						# get warehouse name
						db_conn("exten");

						$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);

						if(isset($ria) && $ria != "") {
							$len = strlen($ria);
							if($ria == "Show All"){
								$Wh = "";
								$ria = "";
							}else {
								$Wh = "AND (lower(stkdes) LIKE lower('%$ria%')) OR (lower(stkcod) LIKE lower('%$ria%'))";
//								$Wh = "AND lower(substr(stkcod,1,'$len'))=lower('$ria')";
							}
						} else {
							$Wh = "AND FALSE";
							$ria = "";
						}

						# get stock on this warehouse
						db_connect();

						$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' AND serd = 'no' $Wh ORDER BY stkcod ASC";
						$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
						if (pg_numrows ($stkRslt) < 1) {
							$error .= "<li class='err'>There are no stock items in the selected warehouse.</li>";
							continue;
						}
						if (pg_numrows ($stkRslt) == 1) {
							$ex = "selected";
						} else {$ex = "";}

						if (!isset($sel_frm) || $sel_frm == "stkcod") {
							$cods = "<select class='width : 15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
							$cods .= "<option value='-S' disabled selected>Select Number</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								$cods .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
							}
							$cods .= "</select> ";

							$descs = "";
						} else {
							$descs = "<select class='width : 15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
							$descs .= "<option value='-S' disabled selected>Select Description</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								$descs .= "<option value='$stk[stkid]'>$stk[stkdes] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
							}
							$descs .= "</select> ";

							$cods = "";
						}

						# put in drop down and warehouse
						$products .= "
							<input type='hidden' name='accountss[]' value='0'>
							<input type='hidden' name='descriptionss[]' value=''>
							<tr class='".bg_class()."'>
								<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
								<td>$cods</td>
								<td>&nbsp;</td>
								<td>$descs</td>
								<td><input type='text' size='3' name='qtyss[]'  value='1'></td>
								<td> </td>
								<td><input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
								<td><input type='hidden' name='amts[]' value='0.00'>".CUR." 0.00</td>
								<td></td>
							</tr>";
					}else{

						db_conn('core');

						$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
						$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

						$Accounts = "
							<select name='accountss[]'  onChange='javascript:document.form.submit();'>
								<option value='0'>Select Account</option>";
						while($ad = pg_fetch_array($Ri)) {
							if(isb($ad['accid'])) {
								continue;
							}
							$Accounts .= "<option value=$ad[accid]>$ad[accname]</option>";
						}
						$Accounts .= "</select>";

						db_conn('cubit');

						$Sl = "SELECT * FROM vatcodes ORDER BY code";
						$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

						$Vatcodes = "
							<select name='vatcodess[]'>
								<option value='0'>Select</option>";
						while($vd = pg_fetch_array($Ri)) {
							if($vd['del'] == "Yes") {
								$sel = "selected";
							} else {
								$sel = "";
							}
							$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
						}
						$Vatcodes .= "</select>";

//<input type='hidden' name='stkidss[]' value=''>
						$products .= "
							<tr class='".bg_class()."'>
								<td colspan='2'>$Accounts<input type='hidden' name='whidss[]' value='$FIRST_WH'></td>
								<td>$Vatcodes</td>
								<td><input type='text' size='20' name='descriptionss[]'></td>
								<td><input type='text' size='3' name='qtyss[]' value='1'></td>
								<td><input type='text' name='unitcosts[]' size='7'></td>
								<td></td>
								<td>".CUR." 0.00</td>
								<td><input type='hidden' name='discs[]' value='0'><input type='hidden' name='discps[]' value='0' ></td>
							</tr>";
					}
				}
			}
		}
	}else{
		if(! (isset($diffwhBtn) || isset($addnon))){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd) && $wtd != 0){$whid = $wtd;}

				# get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				if(isset($ria) && $ria != "") {
					$len = strlen($ria);
					if($ria == "Show All"){
						$Wh = "";
						$ria = "";
					}else {
						$Wh = "AND lower(substr(stkcod,1,'$len'))=lower('$ria')";
						$ria = "";
					}
				} else {
					$Wh = "";
					$ria = "";
				}

				# get stock on this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' AND serd = 'no' $Wh ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!isset($err)){
						$err = "";
					}
					$err .= "<li>There are no stock items in the selected store.</li>";
					//ontinue;
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";

				$products .= "
					<input type='hidden' name='descriptionss[]' value=''>
					<input type='hidden' name='vatcodess[]' value=''>
					<input type='hidden' name='accountss[]' value='0'>
					<tr class='".bg_class()."'>
						<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
						<td>$stks</td>
						<td></td>
						<td></td>
						<td><input type='hidden' size='3' name='qtyss[]' value='1'>1</td>
						<td></td>
						<td><input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
						<td>".CUR." 0.00</td>
						<td></td>
					</tr>";
			}else{
				$products .= "
					<tr class='".bg_class()."'>
						<td>$whs</td>
						<td> </td>
						<td></td>
						<td> </td>
						<td> </td>
						<td><input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
						<td>".CUR." 0.00</td>
						<td></td>
					</tr>";
			}
		} else if ( isset($addnon) ) {
			db_conn('core');
			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accountss[]'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
			}
			$Accounts .= "</select>";

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodess[]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($vd['del'] == "Yes") {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";


			$products .="
				<tr class='".bg_class()."'>
					<td colspan='2'>$Accounts<input type='hidden' name='whidss[]' value='$FIRST_WH'></td>
					<input type='hidden' name='stkidss[]' value='-S'>
					<td>$Vatcodes</td>
					<td><input type='text' size='20' name='descriptionss[]'></td>
					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					<td><input type='text' name='unitcosts[]' size='7'></td>
					<td></td>
					<td>".CUR." 0.00</td>
					<td><input type='hidden' name='discs[]' value='0'><input type='hidden' name='discps[]' value='0'></td>
				</tr>";
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "
			<tr class='".bg_class()."'>
				<td>$whs</td>
				<td> </td>
				<td></td>
				<td> </td>
				<td> </td>
				<td><input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
				<td>".CUR." 0.00</td>
				<td></td>
			</tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = sprint(($inv['traddisc']/100) * ($SUBTOT  + $inv['delchrg']));
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

/* --- End Some calculations --- */


/*--- Start checks --- */
	# check only if the customer is selected
	if(isset($cusnum) && $cusnum != "-S"){
		#check againg credit limit
		if(($TOTAL + $cust['balance']) > $cust['credlimit']){
			$error .= "<li class='err'>Warning : Customers Credit limit of <b>".CUR." ".sprint($cust["credlimit"])."</b> has been exceeded";
		}
		$avcred = ($cust['credlimit'] - $cust['balance']);
	}else{
		$avcred = "0.00";
	}

	$inv['delvat'] += 0;

	if($inv['delvat'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$inv['delvat'] = $vd['id'];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $inv['delvat']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM costcenters";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {

		$ctd = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Cost Center</th>
					<th>Percentage</th>
				</tr>";

		$i = 0;

		while($data = pg_fetch_array($Ri)) {

			$Sl = "SELECT * FROM invc WHERE inv='$invid' AND cid='$data[ccid]'";
			$Rq = db_exec($Sl);

			$cd = pg_fetch_array($Rq);

			$ctd .= "
				<tr class='".bg_class()."'>
					<td>$data[centername]</td>
					<td><input type='text' name='ct[$data[ccid]]' size='5' value='$cd[amount]'>%</td>
				</tr>";

			$i++;
		}

		$ctd .= "</table>";
	} else {
		$ctd = "";
	}

	// Retrieve default comments from Cubit
	if (empty($inv["comm"])) {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve default comment from Cubit.");
		$comment = base64_decode(pg_fetch_result($commRslt, 0));
	} else {
		$comment = $inv["comm"];
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Which display method was selected
	if (isset($sel_frm) && $sel_frm == "stkdes") {
		$sel_frm_cod = "";
		$sel_frm_des = "checked";
	} else {
		$sel_frm_cod = "checked";
		$sel_frm_des = "";
	}
/*--- Start checks --- */

/* -- Final Layout -- */
	$details_begin = "
		<center>
		<h3>Recurring Invoice</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='stkerr' value='$stkerr'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<div id='cust_selection'>";

	if (empty($ajax_err) && (isset($cusnum) || AJAX)) {
		if (isset($cusnum)) {
			$OTS_OPT = onthespot_encode(
				SELF,
				"cust_selection",
				"deptid=$inv[deptid]&letters=$letters&cusnum=$cusnum&invid=$invid"
			);
			$custedit = "
				<td nowrap>
					<a href='javascript: popupSized(\"cust-edit.php?cusnum=$cusnum&onthespot=$OTS_OPT\", \"edit_cust\", 700, 630);'>
						Edit Customer Details
					</a>
				</td>";
		} else {
			$custedit = "";
		}

		$ajaxOut = "
			<input type='hidden' name='letters' value='$letters'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Department</td>
					<td valign='center'>$dept[deptname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Account No.</td>
					<td valign='center'>$cust[accno]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer</td>
					<td valign='center'>$customers</td>
					$custedit
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Customer Address</td>
					<td valign='center'>".nl2br($cust['addr1'])."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer Order number</td>
					<td valign='center'><input type='text' size='10' name='cordno' value='$inv[cordno]'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer VAT Number</td>
					<td>$cust[vatnum]</td>
				</tr>
				<tr>
					<th colspan='2'>Point of Sale</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Barcode</td>
					<td><input type='text' size='13' name='bar' value=''></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Using</td>
					<td>Stock Code<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod><br>Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des></td>
				</tr>
				<tr class='".bg_class()."' ".ass("Type the first letters of the stock code you are looking for.").">
					<td>Stock Filter</td>
					<td nowrap><input type='text' size='13' name='ria' value='$ria'> <input type='submit' value='Search'> <input type='submit' name='ria' value='Show All'></td>
				</tr>
			</table>";
	} else {
		# Query server for depts
		db_conn("exten");

		$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			return "<li class='err'>There are no Departments found in Cubit.</li>";
		}else{
			$depts = "<select id='deptid'>";
			$depts .= "<option value='0'>All Departments</option>";
			while($dept = pg_fetch_array($deptRslt)){
				$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
			}
			$depts .= "</select>";
		}

		if (!isset($ajax_err)) $ajax_err = "";

		$ajaxOut = "
			<script>
				function updateCustSelection() {
					deptid = getObject('deptid').value;
					letters = getObject('letters').value;
					ajaxRequest('".SELF."', 'cust_selection', AJAX_SET, 'letters='+letters+'&deptid='+deptid+'&invid=$invid');
				}
			</script>
			$ajax_err
			<table ".TMPL_tblDflts." width='400'>
				<tr>
					<th colspan='2'>New Recurring Invoice</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Department</td>
					<td valign='center'>$depts</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>First Letters of customer</td>
					<td valign='center'><input type='text' size='5' id='letters' maxlength='5'></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td valign='center'><input type='button' value='Update' onClick='updateCustSelection();'></td>
				</tr>
			</table>";
	}


	if (isset ($diffwhBtn) OR isset ($addprodBtn) OR isset ($addnon) OR isset ($upBtn) OR isset ($saveBtn) OR isset ($ria)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details_end = "
					</div>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Invoice No.</td>
							<td valign='center'>RI $inv[invid]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Document Ref No.</td>
							<td valign='center'><input type='text' size='5' name='docref' value='$inv[docref]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sales Order No.</td>
							<td valign='center'><input type='text' size='5' name='ordno' value='$inv[ordno]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin> No<input type='radio' size='7' name='chrgvat' value='exc' $chex></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sales Person</td>
							<td valign='center'>$salesps</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Invoice Date</td>
							<td valign='center'>".mkDateSelect("rinv",$rinv_year,$rinv_month,$rinv_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Available Credit</td>
							<td>".CUR." ".sprint($avcred)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='text' size='5' name='traddisc' value='$inv[traddisc]'>%</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='text' size='7' name='delchrg' value='$inv[delchrg]'>$Vatcodes</td>
						</tr>
						<tr>
							<td colspan='2'>$ctd</td>
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
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<td rowspan='2'>"
							.mkQuickLinks(
								ql("cust-credit-stockinv.php", "New Invoice"),
								ql("rec-invoice-view.php", "View Recurring Invoices"),
								ql("customers-new.php", "New Customer")
							)."
							</td>
							<th width='25%'>Comments</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$comment</textarea></td>
						</tr>
					</table>
				</td>
				<td align='right' valign='top'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr class='".bg_class()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $inv[discount]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charge</td>
							<td align='right' nowrap>".CUR." $inv[delivery]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right' nowrap>".CUR." $VAT</td>
						</tr>
						<tr class='".bg_class()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input name='diffwhBtn' type='submit' value='Different Store'> | <input name='addprodBtn' type='submit' value='Add Product'> | <input name='addnon' type='submit' value='Add Non stock Product'> | <input type='submit' name='upBtn' value='Update'> </td>
				<td> | <input type='submit' name='saveBtn' value='Save &raquo'></td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";

	if (AJAX) {
		return $ajaxOut;
	} else {
		return "$details_begin$ajaxOut$details_end";
	}

}



# details
function write($_POST)
{

	# Get vars
	extract ($_POST);

	if (!isset($cusnum)) {
		return details($_POST, "<li class='err'>Please select a customer.</li>");
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if (isset($cusnum)) $v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	if (isset($cordno)) $v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	if (!isset($ria)) {$ria="";}
	$v->isOk ($ria, "string", 0, 20, "Invalid stock code(fist letters).");

	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($docref, "string", 0, 20, "Invalid Document Reference No.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid sales order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($rinv_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($rinv_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($rinv_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $rinv_year."-".$rinv_month."-".$rinv_day;
	if(!checkdate($rinv_month, $rinv_day, $rinv_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

			$v->isOk ($qty, "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
			if($disc[$keys] > $unitcost[$keys]){
				$v->isOk ($disc[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than the unitcost.");
			}
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
			if($discp[$keys] > 100){
				$v->isOk ($discp[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than 100 %.");
			}
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty <= 0){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be more than zero. Product number : <b>".($keys+1)."</b>");
			}
		}
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	
	
	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	$inv['chrgvat'] = $chrgvat;

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM inv_data WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);

		$inv['deptid'] = $cust['deptid'];

		# If customer was just selected, get the following
		if($inv['cusnum'] == 0){
			$traddisc = $cust['traddisc'];
			$terms = $cust['credterm'];
		}
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	$vatamount = 0;
	$showvat = TRUE;

	# insert invoice to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* -- Start remove old items -- */
	# get selected stock in this invoice
	$sql = "SELECT * FROM recinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);

	# remove old items
	$sql = "DELETE FROM recinv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
	/* -- End remove old items -- */

		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod) && in_array($keys, $remprod)){

				}elseif(isset($accounts[$keys]) && $accounts[$keys] != 0){
					$accounts[$keys] += 0;
					# Get selamt from selected stock
					db_conn('core');

					$Sl = "SELECT * FROM accounts WHERE accid='$accounts[$keys]'";
					$Ri = db_exec($Sl) or errDie("Unable to get account data.");

					$ad = pg_fetch_array($Ri);

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));

					db_conn('cubit');

					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
						return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
					}

					$vd = pg_fetch_array($Ri);

					if($vd['zero'] == "Yes") {
						$excluding = "y";
					} else {
						$excluding = "";
					}

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs = explode("|",$vr);
					$ivat = $vrs[0];
					$iamount = $vrs[1];

					$vatamount += $ivat;

					# Check Tax Excempt
					if($vd['zero'] == "Yes"){
						$taxex += $amt[$keys];
						$exvat = "y";
					} else {
						$exvat = "n";
					}

					//$newvat+=vatcalc($amt[$keys],$chrgvat,$exvat,$traddisc);
					$vatcodes[$keys] += 0;
					$accounts[$keys] += 0;
					$descriptions[$keys] = remval($descriptions[$keys]);
					$wtd = $whids[$keys];
					# insert invoice items
					$sql = "
						INSERT INTO recinv_items (
							invid, whid, stkid, qty, unitcost, 
							amt, disc, discp,  div, vatcode, 
							description, account
						) VALUES (
							'$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
							'$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."', '$vatcodes[$keys]', 
							'$descriptions[$keys]', '$accounts[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

				}else{
					# Get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					# Calculate the Discount discount
					if($disc[$keys] < 1){
						if($discp[$keys] > 0){
							$disc[$keys] = (($discp[$keys]/100) * $unitcost[$keys]);
						}
					}else{
						$discp[$keys] = (($disc[$keys] * 100) / $unitcost[$keys]);
					}

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));

					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
						return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
					}
					$vd = pg_fetch_array($Ri);

					if($vd['zero'] == "Yes") {
						$excluding = "y";
					} else {
						$excluding = "";
					}

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs = explode("|",$vr);
					$ivat = $vrs[0];
					$iamount = $vrs[1];

					$vatamount += $ivat;

					# Check Tax Excempt
					if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes"){
						$taxex += $amt[$keys];
						$exvat = "y";
					} else {
						$exvat = "n";
					}

					$wtd = $whids[$keys];
					if(!isset($sernos[$keys])) {
						$sernos[$keys] = "";
					}
					# insert invoice items
					$sql = "
						INSERT INTO recinv_items (
							invid, whid, stkid, qty, unitcost, 
							amt, disc, discp, serno, div, 
							vatcode
						) VALUES (
							'$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
							'$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '$sernos[$keys]', '".USER_DIV."', 
							'$vatcodes[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = " | <input name='doneBtn' type='submit' value='Done'>";
			}
		}else{
			$_POST["done"] = "";
		}

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$delvat'";
		$Ri = db_exec($Sl);

// 		if(pg_num_rows($Ri)>0) {
// 			$taxex += $delchrg;
// 		}

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$excluding = "y";
		} else {
			$excluding = "";
		}

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$_POST['showvat'] = $showvat;

		$vr = vatcalc($delchrg,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);

		$vrs = explode("|",$vr);
		$ivat = $vrs[0];
		$iamount = $vrs[1];

		$vatamount += $ivat;


		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($chrgvat == "exc"){
			$taxex = sprint($taxex - ($taxex * $traddisc / 100));
			$subtotal = sprint($sub + $delchrg);
			$traddiscmt =sprint($subtotal * $traddisc / 100);
			$subtotal = sprint($subtotal - $traddiscmt);
			//$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = $vatamount;
			$SUBTOT = $sub;
			$TOTAL = sprint($subtotal + $VAT);
			$delexvat = sprint($delchrg);

		}elseif($chrgvat == "inc"){
			$ot = $taxex;
			$taxex = sprint($taxex - ($taxex * $traddisc / 100));
			$subtotal = sprint($sub + $delchrg);
			$traddiscmt = sprint($subtotal * $traddisc / 100);
			$subtotal = sprint($subtotal - $traddiscmt);
			//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$VAT = $vatamount;
			$SUBTOT = sprint($sub);
			$TOTAL = sprint($subtotal);
			$delexvat = sprint(($delchrg));
			$traddiscmt = sprint($traddiscmt);

		} else {
			$subtotal = sprint($sub + $delchrg);
			$traddiscmt = sprint($subtotal * $traddisc / 100);
			$subtotal = sprint($subtotal - $traddiscmt);
			$VAT = sprint(0);
			$SUBTOT = $sub;
			$TOTAL = $subtotal;
			$delexvat = sprint($delchrg);
		}

		/* --- ----------- Clac --------------------- */
		##----------------------END----------------------

		db_conn('cubit');

		$Sl = "SELECT * FROM costcenters";
		$Ri = db_exec($Sl);

		$i = 0;

		$Sl = "DELETE FROM invc WHERE inv='$invid'";
		$Rl = db_exec($Sl);

		while($data = pg_fetch_array($Ri)) {

			if($ct[$data['ccid']] > 0) {
				$Sl = "INSERT INTO invc (cid,inv,amount) VALUES ('$data[ccid]','$invid','".$ct[$data['ccid']]."')";
				$Rl = db_exec($Sl);
			}

			$i++;
		}

		/* --- ----------- Clac ---------------------

		# calculate subtot
		$SUBTOT = 0.00;
		if(isset($amt))
			$SUBTOT = array_sum($amt);

		$SUBTOT -= $taxex;

		# duplicate
		$SUBTOTAL = $SUBTOT;

		$VATP = TAX_VAT;
		if($chrgvat == "exc"){
			$SUBTOTAL = $SUBTOTAL;
			$delexvat= ($delchrg);
		}elseif($chrgvat == "inc"){
			$SUBTOTAL = sprint(($SUBTOTAL * 100)/(100 + $VATP));
			$delexvat = sprint(($delchrg * 100)/($VATP + 100));
		}else{
			$SUBTOTAL = ($SUBTOTAL);
			$delexvat = ($delchrg);
		}

		$SUBTOT = $SUBTOTAL;
		$EXVATTOT = $SUBTOT;
		$EXVATTOT += $delexvat;

		# Minus trade discount from taxex
		if($traddisc > 0){
			$traddiscmtt = (($traddisc/100) * $taxex);
		}else{
			$traddiscmtt = 0;
		}
		$taxext = ($taxex - $traddiscmtt);

		if($traddisc > 0) {
			$traddiscmt = ($EXVATTOT * ($traddisc/100));
		}else{
			$traddiscmt = 0;
		}
		$EXVATTOT -= $traddiscmt;
		// $EXVATTOT -= $taxex;

		$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

		if($chrgvat != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

		# insert invoice to DB
		$sql = "
			UPDATE rec_invoices 
			SET delvat='$delvat', cusnum = '$cusnum', deptid = '$dept[deptid]', deptname = '$dept[deptname]', 
				cusacc = '$cust[accno]', cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', 
				cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', docref = '$docref',
				chrgvat = '$chrgvat', terms = '$terms', salespn = '$salespn', odate = '$odate', traddisc = '$traddisc', 
				delchrg = '$delchrg', subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', 
				comm = '$comm', serd = 'y', discount='$traddiscmt', delivery='$delexvat' 
			WHERE invid = '$invid'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	if (strlen($bar) > 0) {

		$Sl = "SELECT * FROM possets WHERE div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

		if (pg_numrows ($Rs) < 1) {
			return details($_POST,"<a href='pos-set.php'>Please set the point of sale setting by clicking here.</a>");
		}
		$Dets = pg_fetch_array($Rs);
		if($Dets['opt'] == "No") {

			switch (substr($bar,(strlen($bar)-1),1)) {
				case "0":
					$tab = "ss0";
					break;
				case "1":
					$tab = "ss1";
					break;
				case "2":
					$tab = "ss2";
					break;
				case "3":
					$tab = "ss3";
					break;
				case "4":
					$tab = "ss4";
					break;
				case "5":
					$tab = "ss5";
					break;
				case "6":
					$tab = "ss6";
					break;
				case "7":
					$tab = "ss7";
					break;
				case "8":
					$tab = "ss8";
					break;
				case "9":
					$tab = "ss9";
					break;
				default:
					return details($_POST,"The code you selected is invalid");
			}
			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = barext_dbget($tab,'code',$bar,'stock');

			if(!($stid > 0)){
				return details($_POST,"The bar code you selected is not in the system or is not available.");
			}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "
				INSERT INTO recinv_items (
					invid, whid, stkid, qty, unitcost, amt, disc, discp, ss, div
				) VALUES (
					'$invid', '$s[whid]', '$stid', '1','$s[selamt]', '$s[selamt]', '0', '0', '$bar', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			$Sl = "UPDATE ".$tab." SET active = 'no' WHERE code = '$bar' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}else{
			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = ext_dbget('stock','bar',$bar,'stkid');

			if(!($stid > 0)){return details($_POST,"The bar code you selected is not in the system or is not available.");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "
				INSERT INTO recinv_items (
					invid, whid, stkid, qty, unitcost, amt, disc, discp,ss, div
				) VALUES (
					'$invid', '$s[whid]', '$stid', '1', '$s[selamt]', '$s[selamt]', '0', '0', '$bar',  '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}

	}

/* --- Start button Listeners --- */
	if(isset($saveBtn)){

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Recurring Invoice Saved</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Recurring Invoice for customer <b>$cust[cusname] $cust[surname]</b> has been saved.</td>
				</tr>
			</table>"
			.mkQuickLinks(
				ql("rec-invoice-view.php", "View Recurring Invoices"),
				ql("customers-new.php", "New Customer")
			);
		return $write;
	}else{
		if(isset($wtd)){$_POST['wtd'] = $wtd;}
		if(strlen($ria) > 0){$_POST['ria'] = $ria;}
		return details($_POST);
	}
/* --- End button Listeners --- */

}



?>
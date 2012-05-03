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
require("libs/ext.lib.php");

# decide what to do
if (isset($_GET["sordid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "details":
				if(isset($_POST["ctyp"]) && $_POST["ctyp"] == 'int')
					header("Location: intsorder-new.php?deptid=$_POST[deptid]&letters=$_POST[letters]");
				$OUTPUT = details($_POST);
				break;
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = view();
		}
	} else {
		$OUTPUT = view();
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
		$depts .= "<option value='0'>All Departments</option>";
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
				<th colspan='2'>New Sales Order</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer type</td>
				<td valign='center'><input type='radio' size='7' name='ctyp' value='loc' checked='yes'> Local | <input type='radio' size='7' name='ctyp' value='int'> International</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center' align='right'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("sorder-view.php", "View Sales Orders"),
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
	}else{
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

	//layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Sales Order</th>
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
			<tr class='".bg_class()."'>
				<td>Customer type</td>
				<td valign='center'><input type='radio' size='7' name='ctyp' value='loc' checked='yes'> Local | <input type='radio' size='7' name='ctyp' value='int'> International</td>
			</tr>
			".TBL_BR."
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("sorder-view.php", "View Sales Orders"),
			ql("customers-new.php", "New Customer")
		);
	return $view;

}



# create a dummy Sales Order
function create_dummy($deptid)
{

	$days_in_month = date("t");
	define("SECONDS_IN_MONTH", ($days_in_month * 86400));

	db_connect();

	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
//	$odate = date("Y-m-d");
	$ddate = date("Y-m-d", (time() + SECONDS_IN_MONTH));
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;
	$costs = 'yes';
	$proforma = "no";

	$pinvnum = 0;

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

	// $sordid = divlastid ("sord", USER_DIV);

	# insert Sales Order to DB
	$sql = "
		INSERT INTO sorders (
			deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, 
			delchrg, subtot, vat, total, balance, comm, username, accepted, done, display_costs, 
			proforma, pinvnum, div, ddate
		) VALUES (
			'$deptid', '$cusnum', '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', 
			'$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '$costs', 
			'$proforma', '$pinvnum', '".USER_DIV."', '$ddate'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Sales Order to Cubit.",SELF);

	# get next ordnum
	$sordid = pglib_lastid ("sorders", "sordid");
	return $sordid;

}



# details
function details($_POST, $error="")
{

	extract($_POST);

	# validate input
	include("libs/validate.lib.php");

	$v = new validate();
	if(isset($sordid)){
		$v->isOk ($sordid, "num", 1, 20, "Invalid sales order number.");
	}

	if (isset($deptid)) {
		$v->isOk($deptid, "num", 1, 20, "Invalid department number.");
	}

	if (isset($letters)) {
		$v->isOk($letters, "string", 0, 5, "Invalid First 3 Letters.");
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



	if(!isset($sordid)){
		$sordid = create_dummy($deptid);
		$stkerr = "0,0";
	}

	if(!isset($proforma))
		$proforma = "";

	if(!isset($done)){
		$done = "";
	}

	# Get Sales Order info
	db_connect();

	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li class='err'>Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);

	# check if Sales Order has been printed
	if($sord['accepted'] == "y"){
		$error = "<li class='err'> Error : Sales Order number <b>$sordid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$sord[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$sord[cusnum]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {

		db_connect();

		if ($deptid == "0"){
			$searchdept = "";
		}else {
			$searchdept = "deptid = '$sord[deptid]' AND ";
		}

		# Query server for customer info
		$sql = "SELECT cusnum, cusname, surname FROM customers WHERE $searchdept location != 'int' AND lower(surname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
			return view_err($_POST, $err);
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='-S' selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}
		# take care of the unset vars
		$cust['addr1'] = "";
		$cust['cusnum'] = "";
		$cust['vatnum'] = "";
		$cust['accno'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
		# moarn if customer account has been blocked
		if($cust['blocked'] == 'yes'){
			return "<li class='err'>Error : Selected customer account has been blocked.</li>";
		}
		$customers = "<input type='hidden' name='cusnum' value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
		$cusnum = $cust['cusnum'];
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

	# get sales people
	db_conn("exten");

	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class='err'> There are no Sales People found in Cubit.</li>";
	}else{
		$salesps = "<select name='salespn'>";
		while($salesp = pg_fetch_array($salespRslt)){
			if($salesp['salesp'] == $sord['salespn']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$salesps .= "<option value='$salesp[salesp]' $sel>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}

	# days drop downs
	$days = array("0" => "0","7" => "7","14" => "14","30" => "30","60" => "60","90" => "90","120" => "120");
	$termssel = extlib_cpsel("terms", $days, $sord['terms']);

	# Keep the charge vat option stable
	if($sord['chrgvat'] == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($sord['chrgvat'] == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}

	if ($sord["display_costs"] == "yes") {
		$dc_sel["yes"] = "checked";
		$dc_sel["no"] = "";
	} else {
		$dc_sel["yes"] = "";
		$dc_sel["no"] = "checked";
	}

	# format date
	list($sord_year, $sord_month, $sord_day) = explode("-", $sord['odate']);
	list($ddate_year, $ddate_month, $ddate_day) = explode("-", $sord["ddate"]);

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

	# select all products
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
			</tr>";

	# get selected stock in this Sales Order
	db_connect();

	$sql = "SELECT * FROM sorders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		$stkd['account'] += 0;
		if ($stkd['account'] != 0) {

			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			$Accounts = "
				<select name='accounts[]'>
					<option value='0'>Select Account</option>";

			$useaccdrop = getCSetting ("USE_NON_STOCK_ACCOUNTS");
			if (isset ($useaccdrop) AND $useaccdrop == "yes"){
				db_connect ();
				$acc_sql = "SELECT * FROM non_stock_account_list ORDER BY accname";
				$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
				if (pg_numrows ($run_acc) > 0){
					while($acc = pg_fetch_array($run_acc)){
						if ($acc['accid'] == $stkd['account']) {
							$Accounts .= "<option value='$acc[accid]' selected>$acc[accname]</option>";
						}else {
							$Accounts .= "<option value='$acc[accid]'>$acc[accname]</option>";
						}
					}
					$Accounts .= "</select>";
				}
			}else {
				db_conn('core');
				$Sl = "SELECT accid, topacc, accnum, accname FROM accounts WHERE acctype='I' ORDER BY accname";
				$Ri = db_exec($Sl) or errDie("Unable to get accounts.");
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
			}

			$sernos = "";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";

			$stkd['unitcost'] = sprint ($stkd['unitcost']);
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


			$stkd['amt'] = sprint ($stkd['amt']);

			# Put in product
			$products .= "
				<tr class='".bg_class()."'>
					<td colspan='2'>
						$Accounts
						<input type='hidden' name='whids[]' value='$stkd[whid]'>
					</td>
					<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'>$Vatcodes</td>
					<td><input type='text' size='20' name='descriptions[]' value='$stkd[description]'> $sernos</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td><input type='hidden' name='disc[]' value='$stkd[disc]'><input type='hidden' name='discp[]' value='$stkd[discp]'></td>
					<td nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
					<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
				</tr>";
			$key++;

		} else {

			# keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			# get selected stock in this warehouse
			db_connect();

			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# get warehouse name
			db_conn("exten");

			$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[]'>";
					//<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($stkd['vatcode'] == $vd['id']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			$stkd['unitcost'] = sprint ($stkd['unitcost']);
			$stkd['amt'] = sprint ($stkd['amt']);

//			$sql = "SELECT * FROM manufact.jobcards WHERE recipe!='yes' AND completion!='1'";
//			$job_rslt = db_exec($sql) or errDie("Unable to retrieve jobs.");
//
//			$job_sel = "<select name='job_id[]' style='width: 100%'>";
//			while ($job_data = pg_fetch_array($job_rslt)) {
//				if ($stkd["jobcard_id"] == $job_data["id"]) {
//					$sel = "selected";
//				} else {
//					$sel = "";
//				}
//
//				$job_sel .= "<option value='$job_data[id]' $sel>
//					$job_data[id] $job_data[description]
//				</option>";
//			}
//			$job_sel .= "</select>";

//	<tr>
//				<td bgcolor='#ff0000' width='10%'>
//					$job_sel<br />
//					<input type='submit' name='pur[]' value='Add To Purchase Resource Planning'>
//				</td>
//			</tr>
// rowspan='2'
			# put in product
			$products .= "
				<input type='hidden' name='accounts[]' value='0'>
				<input type='hidden' name='descriptions[]' value=''>
				<input type='hidden' name='amt[]' value='$stkd[amt]'>
				<input type='hidden' name='pqty[$stk[stkid]]' value='$stkd[qty]' />
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$Vatcodes</td>
					<td>".extlib_rstr($stk['stkdes'], 30)."</td>
					<td><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
					<td><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
					<td><input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength=5>%</td>
					<td>".CUR." $stkd[amt]</td>
					<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
				</tr>";
			$key++;
		}
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	#get negative stock setting
	$neg_setting = getCsetting ("SORDER_NEG_STOCK");
	if (!isset($neg_setting) OR strlen($neg_setting) < 1)
		$neg_setting = "yes";

	if ($neg_setting == "yes") 
		$search_neg_stock = "";
	else 
		$search_neg_stock = " AND (units > 0) ";

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S" && isset($cust['pricelist'])){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}



				# get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get price from price list if it is set
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

				$amt[$key] = sprint($amt[$key]);

				$stk['selamt'] = sprint ($stk['selamt']);
				# put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[]' value='0'>
					<input type='hidden' name='descriptions[]' value=''>
					<input type='hidden' name='whids[]' value='$whid'>
					<input type='hidden' name='stkids[]' value='$stk[stkid]'>
					<input type='hidden' name='amt[]' value='$amt[$key]'>
					<tr class='".bg_class()."'>
						<td>$wh[whname]</td>
						<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$Vatcodes</td>
						<td>".extlib_rstr($stk['stkdes'], 30)."</td>
						<td><input type='text' size='3' name='qtys[]' value='$qtyss[$key]'></td>
						<td><input type='text' size='8' name='unitcost[]' value='$stk[selamt]'></td>
						<td>
							<input type='text' size='4' name='disc[]' value='$discs[$key]'>
							OR
							<input type='text' size='4' name='discp[]' value='$discps[$key]' maxlength=5>%
						</td>
						<td nowrap>".CUR." $amt[$key]</td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			} else if (isset($accountss[$key]) && $accountss[$key] != "0" && isset($cust['pricelist'])) {
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
				$unitcosts[$key] = sprint ($unitcosts[$key]);
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

				$amt[$key] = sprint($amt[$key]);

				# Put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[]' value='$accountss[$key]'>
					<input type='hidden' name='whids[]' value='0'>
					<input type='hidden' name='stkids[]' value='0'>
					<input type='hidden' name='disc[]' value='0'>
					<input type='hidden' name='discp[]' value='0'>
					<input type='hidden' name='amt[]' value='$amt[$key]'>
					<tr class='".bg_class()."'>
						<td colspan='2'>$ad[accname]</td>
						<td>$Vatcodes</td>
						<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$key]'></td>
						<td>$qtyin</td>
						<td>$viewcost</td>
						<td>&nbsp;</td>
						<td nowrap> ".CUR." $amt[$key]</td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			}else{

				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				if (!isset($addnon)) {

					if (isset ($filter_store) AND $filter_store != "0"){
						# get warehouse name
						db_conn("exten");
						$sql = "SELECT whname FROM warehouses WHERE whid = '$filter_store' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);
					}

					# get stock on this warehouse
					db_connect();

					if(isset($ria) AND $ria != "") {
						$len = strlen($ria);
						if($ria == "Show All"){
							$Wh = "";
							$ria = "";
						}else {
							$Wh = "AND (lower(stkdes) LIKE lower('%$ria%')) OR (lower(stkcod) LIKE lower('%$ria%'))";
							$ria = "";
						}
					} else {
						$Wh = "AND FALSE";
						$ria = "";
					}

					$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

					if (isset ($check_setting) AND $check_setting == "yes"){
						if (isset ($filter_class) AND $filter_class != "0"){
							$Wh .= " AND prdcls = '$filter_class'";
						}
						if (isset ($filter_cat) AND $filter_cat != "0"){
							$Wh .= " AND catid = '$filter_cat'";
						}
					}

					if (isset($filter_store) AND $filter_store != "0"){
						$Wh .= " AND whid = '$filter_store'";
					}

					$sql = "SELECT * FROM stock WHERE blocked = 'n' $search_neg_stock AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class='err'>There are no stock items in the selected store.</li>";
						continue;
					}
					if ($sel_frm == "stkcod") {
						$cods = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
						$cods .= "<option value='-S' disabled selected>Select Number</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							$cods .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
						}
						$cods .= "</select> ";

						$descs = "";
					} else {
						$descs = "<select style='width:250px' name='stkidss[]' onChange='javascript:document.form.submit();'>";
						$descs .= "<option value='-S' disabled selected>Select Description</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							$descs .= "<option value='$stk[stkid]'>$stk[stkdes] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
						}
						$descs .= "</select> ";

						$cods = "";
					}

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

					# put in drop down and warehouse
					$products .= "
						<input type='hidden' name='accountss[]' value='0'>
						<input type='hidden' name='descriptionss[]' value=''>
						<input type='hidden' name='whidss[]' value='$filter_store'>
						<input type='hidden' name='amts[]' value='0.00'>
						<tr class='".bg_class()."'>
							<td></td>
							<td>$cods<input type='hidden' name='vatcodess' value='0'></td>
							<td>&nbsp;</td>
							<td>$descs</td>
							<td><input type='text' size='3' name='qtyss[]'  value='1'></td>
							<td>&nbsp;</td>
							<td>
								".CUR." <input type='text' size='4' name='discs[]' value='0'>
								OR
								<input type='text' size='4' name='discps[]' value='0' maxlength='5'>%
							</td>
							<td>".CUR." 0.00</td>
							<td>&nbsp;</td>
						</tr>";
				} else {

					$Accounts = "
						<select name='accountss[]'>
							<option value='0'>Select Account</option>";

					$useaccdrop = getCSetting ("USE_NON_STOCK_ACCOUNTS");
					if (isset ($useaccdrop) AND $useaccdrop == "yes"){
						db_connect ();
						$acc_sql = "SELECT * FROM non_stock_account_list ORDER BY accname";
						$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
						if (pg_numrows ($run_acc) > 0){
							while($acc = pg_fetch_array($run_acc)){
								$Accounts .= "<option value='$acc[accid]'>$acc[accname]</option>";
							}
							$Accounts .= "</select>";
						}
					}else {
						db_conn('core');
						$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
						$Ri = db_exec($Sl) or errDie("Unable to get accounts.");
						while($ad = pg_fetch_array($Ri)) {
							if(isb($ad['accid'])) {
								continue;
							}
							$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
						}
						$Accounts .= "</select>";
					}

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


					$products .= "
						<input type='hidden' name='whidss[]' value='$FIRST_WH'>
						<inpu type='hidden' name='stkidss[]' value=''>
						<input type='hidden' name='discs[]' value='0'>
						<input type='hidden' name='discps[]' value='0' >
						<tr class='".bg_class()."'>
							<td colspan='2'>$Accounts</td>
							<td>$Vatcodes</td>
							<td><input type='text' size='20' name='descriptionss[]'></td>
							<td><input type='text' size='3' name='qtyss[]' value='1'></td>
							<td><input type='text' name='unitcosts[]' size='7'></td>
							<td>&nbsp;</td>
							<td>".CUR." 0.00</td>
							<td>&nbsp;</td>
						</tr>";
				}
			}
		}
	}else{
		if( ! isset($addnon)){

			if (isset ($filter_store) AND $filter_store != "0"){
				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$filter_store' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
			}

			# get stock on this warehouse
			db_connect();

			if(isset($ria) AND $ria != "") {
				$len = strlen($ria);
				if($ria == "Show All"){
					$Wh = "";
					$ria = "";
				}else {
					$Wh = "AND (lower(stkdes) LIKE lower('%$ria%')) OR (lower(stkcod) LIKE lower('%$ria%'))";
					$ria = "";
				}
			} else {
				$Wh = "AND FALSE";
				$ria = "";
			}

			$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

			if (isset ($check_setting) AND $check_setting == "yes"){
				if (isset ($filter_class) AND $filter_class != "0"){
					$Wh .= " AND prdcls = '$filter_class'";
				}
				if (isset ($filter_cat) AND $filter_cat != "0"){
					$Wh .= " AND catid = '$filter_cat'";
				}
			}

			if (isset($filter_store) AND $filter_store != "0"){
				$Wh .= " AND whid = '$filter_store'";
			}

			$sql = "SELECT * FROM stock WHERE blocked = 'n' $search_neg_stock AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				if(!(isset($err))) {$err="";}
				$err .= "<li>There are no stock items in the selected warehouse.</li>";
			}
			$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
			$stks .= "<option value='-S' disabled selected>Select Number</option>";
			$count = 0;
			while($stk = pg_fetch_array($stkRslt)){
				$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
			}
			$stks .= "</select> ";

			$products .= "
				<input type='hidden' name='accountss[]' value='0'>
				<input type='hidden' name='descriptionss[]' value=''>
				<input type='hidden' name='vatcodess[]' value=''>
				<input type='hidden' name='whidss[]' value='$filter_store'>
				<tr class='".bg_class()."'>
					<td></td>
					<td>$stks</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					<td>&nbsp;</td>
					<td>
						<input type='text' size='4' name='discs[]' value='0'>
						OR
						<input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
					<td>".CUR." 0.00</td>
					<td>&nbsp;</td>
				</tr>";

		} else if ( isset($addnon) ) {

			$Accounts = "
				<select name='accountss[]'>
					<option value='0'>Select Account</option>";

			$useaccdrop = getCSetting ("USE_NON_STOCK_ACCOUNTS");
			if (isset ($useaccdrop) AND $useaccdrop == "yes"){
				db_connect ();
				$acc_sql = "SELECT * FROM non_stock_account_list ORDER BY accname";
				$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
				if (pg_numrows ($run_acc) > 0){
					while($acc = pg_fetch_array($run_acc)){
						$Accounts .= "<option value='$acc[accid]'>$acc[accname]</option>";
					}
					$Accounts .= "</select>";
				}
			}else {
				db_conn('core');
				$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
				$Ri = db_exec($Sl) or errDie("Unable to get accounts.");
				while($ad = pg_fetch_array($Ri)) {
					if(isb($ad['accid'])) {
						continue;
					}
					$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
				}
				$Accounts .= "</select>";
			}

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


			$products .= "
				<inpu type='hidden' name='stkidss[]' value=''>
				<input type='hidden' name='whidss[]' value='$FIRST_WH'>
				<input type='hidden' name='discs[]' value='0'>
				<input type='hidden' name='discps[]' value='0' >
				<tr class='".bg_class()."'>
					<td colspan='2'>$Accounts</td>
					<td>$Vatcodes</td>
					<td><input type='text' size='20' name='descriptionss[]'></td>
					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					<td><input type='text' name='unitcosts[]' size='7'></td>
					<td>&nbsp;</td>
					<td>".CUR." 0.00</td>
					<td>&nbsp;</td>
				</tr>";
		}
	}

// 	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($sord['subtot']);

	# Calculate tradediscm
	if($sord['traddisc'] > 0){
		$traddiscm = sprint(($sord['traddisc']/100) * ($SUBTOT  + $sord['delchrg']));
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($sord['subtot']);
 	$VAT = sprint($sord['vat']);
	$TOTAL = sprint($sord['total']);
	$sord['delchrg'] = sprint($sord['delchrg']);

/* --- End Some calculations --- */

/*--- Start checks --- */
	# check only if the customer is selected
	if(isset($cusnum) && $cusnum != "-S"){
		#check againg credit limit
		if($cust['credlimit'] != 0 && ($TOTAL + $cust['balance']) > $cust['credlimit']){
			$error .= "<li class='err'>Warning : Customers Credit limit of <b>".CUR." $cust[credlimit]</b> has been exceeded:</li>";
		}
		$avcred = ($cust['credlimit'] - $cust['balance']);
	}else{
		$avcred = "0.00";
	}
/*--- Start checks --- */

	if(!(isset($letters))){$letters = "";}


	$sord['delvat'] += 0;

	if($sord['delvat'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$sord['delvat'] = $vd['id'];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $sord['delvat']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$psel1 = "";
	$psel2 = "";
	if(isset($proforma) AND ($proforma == "yes")){
		$psel1 = "checked=yes";
	}else {
		$psel2 = "checked=yes";
	}

	if($proforma == "yes"){

		#generate a unique id IF it hasnt been done yet

		if (!isset ($pinvnum) OR $pinvnum == 0){

			pglib_transaction("BEGIN") or errDie("Could not start database transaction");

			#get unique id
			$get_uni = "INSERT INTO unique_id (entry) VALUES ('value')";
			$run_uni = db_exec($get_uni) or errDie("Unable to get unique id");

			$pinvnum = pglib_lastid("unique_id","id");

			$rem_sql = "DELETE FROM unique_id WHERE id = '$pinvnum'";
			$run_rem = db_exec($rem_sql) or errDie("Unable to remove unique id check");

			#further check to see if an invoice has this is should be done here...

			pglib_transaction("COMMIT") or errDie ("Could not commit database transaction");
		}

		$getpinvnum = "
			<tr class='".bg_class()."'>
				<td>Proforma Invoice Number</td>
				<td><input type='hidden' name='pinvnum' value='$pinvnum'>$pinvnum</td>
			</tr>";
	}else {
		$getpinvnum = "<input type='hidden' name='pinvnum' value='0'>";
	}

	// Which display method was selected
	if (isset($sel_frm) && $sel_frm == "stkdes") {
		$sel_frm_cod = "";
		$sel_frm_des = "checked";
	} else {
		$sel_frm_cod = "checked";
		$sel_frm_des = "";
	}

	// Retrieve VAT Setting
	$sql = "SELECT value FROM cubit.settings WHERE constant='VAT_INC'";
	$vatinc_rslt = db_exec($sql) or errDie("Unable to retrieve vat setting.");
	$vatinc = pg_fetch_result($vatinc_rslt, 0);

	if ($vatinc != "yes" && $vatinc != "no") {
		$vatinc = "no";
	}

	if ($vatinc == 'yes') {
		$chrgvat = "inc";
	} else {
		$chrgvat = "exc";
	}

	if (isset ($diffwhBtn) OR isset ($addprodBtn) OR isset ($addnon) OR isset ($saveBtn) OR isset ($upBtn) OR isset ($doneBtn) OR isset ($donePrnt) OR isset ($ria)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$optional_filter_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

	if (isset ($optional_filter_setting) AND $optional_filter_setting == "yes"){

		db_connect ();

		$catsql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
		$catRslt = db_exec($catsql);
		if(pg_numrows($catRslt) < 1){
			$cat_drop = "<input type='hidden' name='filter_cat' value='0'>";
		}else{
			$cat_drop = "<select name='filter_cat'>";
			$cat_drop .= "<option value='0'>All Categories</option>";
			while($cat = pg_fetch_array($catRslt)){
				if (isset ($filter_cat) AND $filter_cat == $cat['catid']){
					$cat_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
				}else {
					$cat_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
				}
			}
			$cat_drop .= "</select>";
		}

		# Select classification
		$classsql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
		$clasRslt = db_exec($classsql);
		if(pg_numrows($clasRslt) < 1){
			$class_drop = "<input type='hidden' name='filter_class' value='0'>";
		}else{
			$class_drop = "<select name='filter_class' style='width: 167'>";
			$class_drop .= "<option value='0'>All Classifications</option>";
			while($clas = pg_fetch_array($clasRslt)){
				if (isset ($filter_class) AND $filter_class == $clas['clasid']){
					$class_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
				}else {
					$class_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
				}
			}
			$class_drop .= "</select>";
		}

		$display_optional_filters = "
			<tr class='".bg_class()."'>
				<td>Select Category</td>
				<td>$cat_drop</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Classification</td>
				<td>$class_drop</td>
			</tr>";
	}

	db_conn("exten");

	$sql = "SELECT whid, whname, whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		$store_drop = "<input type='hidden' name='filter_store' value='0'>";
	}else {

		if (!isset ($filter_store)){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$filter_store = $set['value'];
			}
		}

		$store_drop = "<select name='filter_store'>";
		$store_drop .= "<option value='0'>All Stores</option>";
		while($wh = pg_fetch_array($whRslt)){
			if (isset ($filter_store) AND $filter_store == $wh['whid']){
				$store_drop .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
			}else {
				$store_drop .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
		}
		$store_drop .= "</select>";
	}

	$cust_del_addr = $sord['del_addr'];

	$details = "
		<center>
		<h3>New Sales Order</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='sordid' value='$sordid'>
			<input type='hidden' name='letters' value='$letters'>
			<input type='hidden' name='stkerr' value='$stkerr'>
			<input type='hidden' name='chrgvat' value='$chrgvat' />
		<table ".TMPL_tblDflts." width='95%'>
				<tr>
				<td valign='top'>
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
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($cust['addr1'])."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Current Delivery Address</td>
							<td valign='center'>".nl2br($cust_del_addr)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Delivery Address</td>
							<td valign='center'>
								<input type='button' onClick=\"
									var windowReference;
									function openPopup() {
										windowReference = window.open('sorder-new-deladdr.php?sordid=$sordid','windowName','width=500,height=400,status=1');
										if (!windowReference.opener)
											windowReference.opener = self;
									}
									openPopup();\" value='Change Delivery Address'>
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Customer Order number</td>
							<td valign='center'><input type='text' size='10' name='cordno' value='$sord[cordno]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Customer VAT Number</td>
							<td>$cust[vatnum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Select Using</td>
							<td>Stock Code<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod><br>Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>Additional Filters</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Select Store</td>
							<td>$store_drop</td>
						</tr>
						$display_optional_filters
						<tr class='".bg_class()."' ".ass("Type the first letters of the stock code you are looking for.").">
							<td>Stock Filter</td>
							<td nowrap><input type='text' size='13' name='ria' value='$ria'> <input type='submit' value='Search'> <input type='submit' name='ria' value='Show All'></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Sales Order Details </th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sales Order No.</td>
							<td valign='center'>$sord[sordid]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Proforma Invoice</td>
							<td valign='center'>Yes <input type='radio' name='proforma' value='yes' $psel1 onChange='javascript:document.form.submit();'> No <input type='radio' name='proforma' value='no' $psel2 onChange='javascript:document.form.submit();'></td>
						</tr>
						$getpinvnum
						<tr class='".bg_class()."'>
							<td>Display Costs</td>
							<td>Yes <input type='radio' name='costs' value='yes' $dc_sel[yes]> No <input type='radio' name='costs' value='no' $dc_sel[no]></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Order No.</td>
							<td valign='center'><input type='text' size='5' name='ordno' value='$sord[ordno]'></td>
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
							<td>Sales Order Date</td>
							<td valign='center'>".mkDateSelect("sord",$sord_year,$sord_month,$sord_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery/Due Date</td>
							<td valign='center'>
								".mkDateSelect("ddate", $ddate_year, $ddate_month, $ddate_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Available Credit</td>
							<td>".CUR." ".sprint($avcred)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='text' size='5' name='traddisc' value='$sord[traddisc]'>%</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='text' size='7' name='delchrg' value='$sord[delchrg]'>$Vatcodes</td>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2'><table ".TMPL_tblDflts.">$products</table></td>
			</tr>
			<tr>
				<td>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<td rowspan='2'>"
								.mkQuickLinks(
									ql("sorder-view.php", "View Sales Orders"),
									ql("customers-new.php", "New Customer")
								)."
							</td>
							<th width='25%'>Comments</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$sord[comm]</textarea></td>
						</tr>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr class='".bg_class()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $sord[discount]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charge</td>
							<td align='right' nowrap>".CUR." $sord[delivery]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><b>VAT $vat14</b>
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
				<td colspan='2' align='center'><input name='addprodBtn' type='submit' value='Add Product'>| <input name='addnon' type='submit' value='Add Non stock Product'> | <input type='submit' name='saveBtn' value='Save'> | <input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";
	return $details;

}



# write
function write($_POST)
{

	#get vars
	extract ($_POST);

	if(isset($Cancel)) {
		db_connect();
		$Sl = "DELETE FROM sorders WHERE sordid='$sordid' AND cusnum=0 AND div = '".USER_DIV."'";
		$Rs = db_exec($Sl) or errDie ("Unable to delete Sales Order information");
		$sordid--;
		$Sql = "SELECT setval('sorders_sordid_seq', '$sordid')";
		$Rslt = db_exec ($Sql) or errDie ("Unable to set salesorder id.");
		header ("Location: main.php");
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($sord_day, "num", 1, 2, "Invalid Sales Order Date day.");
	$v->isOk ($sord_month, "num", 1, 2, "Invalid Sales Order Date month.");
	$v->isOk ($sord_year, "num", 1, 5, "Invalid Sales Order Date year.");

	$v->isOk ($ddate_day, "num", 1, 2, "Invalid Delivery Date day.");
	$v->isOk ($ddate_month, "num", 1, 2, "Invalid Delivery Date month.");
	$v->isOk ($ddate_year, "num", 1, 5, "Invalid Delivery Date year.");

	$v->isOk ($proforma, "string", 1, 3, "Invalid Proforma Invoice Selection.");
	$v->isOk ($pinvnum, "num", 1, 20, "Invalid Proforma Invoice Number.");

	if (!isset($ria)) {$ria="";}
	$v->isOk ($ria, "string", 0, 20, "Invalid stock code(fist letters).");

	$odate = $sord_year."-".$sord_month."-".$sord_day;
	if(!checkdate($sord_month, $sord_day, $sord_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Sales Order Date.");
	}

	$ddate = $ddate_year."-".$ddate_month."-".$ddate_day;
	if(!checkdate($ddate_month, $ddate_day, $ddate_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Sales Order Date.");
	}

	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";

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
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
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


	
	$msg = "";

	if (isset($pur)) {
		print $pur[0];
		foreach ($pur as $id => $value) {
			for ($i = 0; $i < $pqty[$id]; $i++) {
				$sql = "INSERT INTO manufact.required_purchases (stock_id, jobcard_id) VALUES ('$id', '$job_id[$id]')";
				db_exec($sql) or errDie("Unable to create new required purchases.");
			}
		}
		return details($_POST);
	}

	# Get Sales Order info
	db_connect();

	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li>- Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);

	$sord['chrgvat'] = $chrgvat;

	# check if Sales Order has been printed
	if($sord['accepted'] == "y"){
		$error = "<li class='err'> Error : Sales Order number <b>$sordid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM sord_data WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);

		$sord['deptid'] = $cust['deptid'];

		# If customer was just selected, get the following
		if($sord['cusnum'] == 0){
			$traddisc = $cust['traddisc'];
			$terms = $cust['credterm'];
		}
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$sord[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	# insert Sales Order to DB
	db_connect();

	$vatamount = 0;
	$showvat = TRUE;

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	// Add to required purchases
	if (isset($pur)) {
		foreach ($pur as $key=>$value) {
			for ($i = 0; $i < $qtys[$key]; $i++) {
				$sql = "INSERT INTO manufact.required_purchases (jobcard_id, stock_id) VALUES ('$job_id[$key]', '$stkids[$key]')";
				db_exec($sql) or errDie("Unable to add required purchase.");
			}
		}
	}

	/* -- Start remove old items -- */
	# get selected stock in this Sales Order
	db_connect();

	$sql = "SELECT * FROM sorders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);


	while($stkt = pg_fetch_array($stktRslt)){
		# update stock(alloc + qty)
		$sql = "SELECT alloc FROM cubit.stock WHERE stkid='$stkt[stkid]'";
		$stkalloc_rslt = db_exec($sql) or errDie("Unable to retrieve allocation.");
		$stkalloc = pg_fetch_result($stkalloc_rslt, 0);

		// Prevent minus minus a minus going positive
		if ($stkalloc < 0 && $stkt["qty"] < 0) {
			$alloc_amt = $stkalloc + $stkt["qty"];
		} else {
			$alloc_amt = $stkalloc - $stkt["qty"];
		}
		$sql = "UPDATE stock SET alloc='$alloc_amt' WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
	}

	# remove old items
	$sql = "DELETE FROM sorders_items WHERE sordid='$sordid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update Sales Order items in Cubit.",SELF);
	/* -- End remove old items -- */

	$taxex = 0;
	if(isset($qtys)){
		foreach($qtys as $keys => $value){
			if(isset($remprod) && in_array($keys, $remprod)){

			}elseif(isset($accounts[$keys]) && $accounts[$keys] != 0){
				$sql = "SELECT csprice, selamt FROM cubit.stock WHERE stkid='$stkids[$keys]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
				list($csprice, $selamt) = pg_fetch_array($stock_rslt);
			
				if ($csprice > $unitcost[$keys]) {
					if ($csprice > $selamt) {
						$unitcost[$keys] = $csprice;
					} else {
						$unitcost[$keys] = $selamt;
					}
					$msg .= "<li class='err'>Unit price cannot be lower than cost price.</li>";
				}

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

				$vr = vatcalc($amt[$keys],$sord['chrgvat'],$excluding,$sord['traddisc'],$vd['vat_amount']);
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
				if (!isset($job_id[$keys]) || empty($job_id[$keys])) {
					$job_id[$keys] = 0;
				}
					
				$sql = "
					INSERT INTO sorders_items (
						sordid, whid, stkid, qty, unitcost, amt, 
						disc, discp, div, vatcode, description, 
						account, jobcard_id
					) VALUES (
						'$sordid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
						'$disc[$keys]', '$discp[$keys]', '".USER_DIV."', '$vatcodes[$keys]', '$descriptions[$keys]', 
						'$accounts[$keys]', '$job_id[$keys]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			}else{
				$sql = "SELECT csprice, selamt FROM cubit.stock WHERE stkid='$stkids[$keys]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
				list($csprice, $selamt) = pg_fetch_array($stock_rslt);
				
				if ($csprice > $unitcost[$keys]) {
					$unitcost[$keys] = $selamt;
					$msg .= "<li class='err'>Unit price cannot be lower than cost price.</li>";
				}

				# get selamt from selected stock
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

				$vr = vatcalc($amt[$keys],$sord['chrgvat'],$excluding,$sord['traddisc'],$vd['vat_amount']);
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
				if (!isset($job_id[$keys]) || empty($job_id[$keys])) {
					$job_id[$keys] = 0;
				}

				# insert Sales Order items
				$sql = "
					INSERT INTO sorders_items (
						sordid, whid, stkid, qty, unitcost, 
						amt, disc, discp, div,vatcode, jobcard_id
					) VALUES (
						'$sordid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
						'$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."','$vatcodes[$keys]', '$job_id[$keys]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Sales Order items to Cubit.",SELF);

				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}
			# everything is set place done button
			$_POST["done"] = "&nbsp; | &nbsp;<input name='doneBtn' type='submit' value='Done'>
			&nbsp; | &nbsp;<input type='submit' name='donePrnt' value='Done, Print and make another'>";
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

	$vr = vatcalc($delchrg,$sord['chrgvat'],$excluding,$sord['traddisc'],$vd['vat_amount']);

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
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
	//	$VAT=sprint(($subtotal-$taxex)*$VATP/100);
		$VAT = $vatamount;
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);

	}elseif($chrgvat == "inc"){
		$ot = $taxex;
		$taxex = sprint($taxex - ($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
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

		$delvat += 0;

		# insert Sales Order to DB
		$sql = "
			UPDATE sorders 
			SET delvat='$delvat', cusnum = '$cusnum', deptid = '$dept[deptid]', deptname = '$dept[deptname]', 
				cusacc = '$cust[accno]', cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', 
				cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', chrgvat = '$chrgvat', 
				terms = '$terms', salespn = '$salespn', odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', 
				subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', comm = '$comm', 
				discount='$traddiscmt', delivery='$delexvat', display_costs='$costs', proforma = '$proforma', 
				pinvnum = '$pinvnum', ddate = '$ddate' 
			WHERE sordid = '$sordid'";
		$rslt = db_exec($sql) or errDie("Unable to update Sales Order in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM sord_data WHERE sordid='$sordid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Sales Order data in Cubit.",SELF);

		# pu in new data
		$sql = "INSERT INTO sord_data(sordid, dept, customer, addr1, div) VALUES ('$sordid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert Sales Order data to Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* --- Start button Listeners --- */
	if (isset($donePrnt)) {
		// Accepted ?
		$sql = "SELECT set FROM cubit.picking_slip_setting";
		$set_rslt = db_exec($sql) or errDie("Unable to retrieve setting.");
		$set = pg_fetch_result($set_rslt, 0);

		if ($set == "y") {
			$accepted = ", accepted='y'";
		} else {
			$accepted = "";
		}
		
		$sql = "UPDATE sorders SET done='y' WHERE sordid='$sordid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve Sales Order status in Cubit.");

		$OUTPUT = "
			<script>
				printer('sorder-print.php?invid=$sordid');
				move('sorder-new.php');
			</script>";
		return $OUTPUT;
	}

	if(isset($doneBtn)){

		// Accepted ?
		$sql = "SELECT set FROM cubit.picking_slip_setting";
		$set_rslt = db_exec($sql) or errDie("Unable to retrieve setting.");
		$set = pg_fetch_result($set_rslt, 0);

		if ($set == "y") {
			$accepted = ", accepted='y'";
		} else {
			$accepted = "";
		}
		# insert Sales Order to DB
		$sql = "UPDATE sorders SET done = 'y' WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Sales Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New Sales Order</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>
						Sales Order for customer <b>$cust[cusname] $cust[surname]</b>
						has been recorded.
					</td>
					<td><a href='javascript: printer(\"sorder-print.php?invid=$sordid\");'>Print Sales Order</a></td>
				</tr>
			</table>"
			.mkQuickLinks(
				ql("sorder-view.php", "View Sales Orders"),
				ql("customers-new.php", "New Customer")
			);
		return $write;

	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr><th>New Sales Order Saved</th></tr>
				<tr class='".bg_class()."'>
					<td>Sales Order for customer <b>$cust[cusname] $cust[surname]</b> has been saved.</td>
				</tr>
			</table>"
			.mkQuickLinks(
				ql("sorder-view.php", "View Sales Orders"),
				ql("customers-new.php", "New Customer")
			);
		return $write;
	}else{
		if(isset($wtd)){$_POST['wtd'] = $wtd;}
		if(strlen($ria) > 0){$_POST['ria'] = $ria;}
		return details($_POST, $msg);
	}
/* --- End button Listeners --- */
}


?>
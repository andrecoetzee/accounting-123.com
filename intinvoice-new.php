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
if (isset($HTTP_GET_VARS["deptid"]) && isset($HTTP_GET_VARS["letters"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}elseif (isset($HTTP_GET_VARS["invid"]) && isset($HTTP_GET_VARS["cont"])) {
	$HTTP_GET_VARS["stkerr"] = '0,0';
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
            case "details":
				$OUTPUT = details($HTTP_POST_VARS);
				break;
			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
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
		return "<li class='err'>There are no Departments found in Cubit.";
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
				<th colspan='2'>New International Invoice</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}



# Default view
function view_err($HTTP_POST_VARS, $err = "")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>No Departments found in Cubit.</li>";
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

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New International Invoice</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' value='$letters' maxlength='5'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
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
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;

	$fcid = getDef_fcid();

	$curr = getSymbol($fcid);
	$xrate = getRate($fcid);

	# insert invoice to DB
	$sql = "
		INSERT INTO invoices (
			deptid, cusnum, cordno, ordno, chrgvat, fcid, currency, xrate, terms, 
			traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, 
			username, location, printed, done, prd, div
		) VALUES (
			'$deptid', '$cusnum', '$cordno', '$ordno', '$chrgvat', '$fcid', '$curr[symbol]', '$xrate', '$terms', 
			'$traddisc', '$salespn', '$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', 
			'".USER_NAME."', 'int', 'n', 'n', '".PRD_DB."', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = lastinvid();
	return $invid;

}



# Details
function details($HTTP_POST_VARS, $error="")
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}else{
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	if(isset($deptid)){
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$deptid' AND location = 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
			return view_err($HTTP_POST_VARS, $err);
		}
	}

	if(!isset($invid)){
		$invid = create_dummy($deptid);
		$stkerr = "0,0";
	}

	if(!isset($done)){
		$done = "";
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# Check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$currs = getSymbol($inv['fcid']);

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
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]'  AND location = 'int' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$inv[deptid]' AND location = 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
			return view_err($HTTP_POST_VARS, $err);
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

		$sql = "SELECT cusnum, cusname, surname FROM customers WHERE deptid = '$inv[deptid]' AND location = 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
		# Moarn if customer account has been blocked
		if($cust['blocked'] == 'yes'){
			$error .= "<li class='err'>Error : Selected customer account has been blocked.";
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

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
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
	list($o_year, $o_month, $o_day) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>VAT CODE</th>
				<th>SERIAL NO.</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan='2'>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

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

		# Serial number
		if($stk['serd'] == 'yes' && $inv['serd'] == 'n'){
			$sers = ext_getavserials($stkd['stkid']);
			$sernos = "<select class='width : 15' name='sernos[]'>";
			foreach($sers as $skey => $ser){
				$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
			}
			$sernos .= "</select>";
		}else{
			$sernos = "<input type='hidden' name='sernos[]' value='$stkd[serno]'>$stkd[serno]";
		}

		# Input qty if not serialised
		$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";
		if($stk['serd'] == 'yes'){
			$qtyin = "<input type='hidden' size='3' name='qtys[]' value='$stkd[qty]'>$stkd[qty]";
		}

		# check permissions
		if(perm("invoice-unitcost-edit.php")){
			$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stkd[funitcost]'>";
			$cunitcost = "<input type='text' size='8' name='cunitcost[]' value='$stkd[unitcost]'>";
		}else{
			$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='$stkd[funitcost]'>$stkd[funitcost]";
			$cunitcost = "<input type='hidden' size='8' name='cunitcost[]' value='$stkd[unitcost]'>$stkd[unitcost]";
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
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
				<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$Vatcodes</td>
				<td>$sernos</td>
				<td>".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>$qtyin</td>
				<td>".CUR." $viewcost</td>
				<td>$inv[currency] $cunitcost</td>
				<td>$inv[currency]<input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%</td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> $inv[currency] $stkd[amt]</td>
				<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
			</tr>";
		$key++;
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

				if($stk['serd'] == 'yes'){
					$sers = ext_getavserials($stkidss[$key]);
					$sernos = "<select class='width : 15'name='sernos[]' onChange='javascript:document.form.submit();'>";
					foreach($sers as $skey => $ser){
						$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
					}
					$sernos .= "</select>";
				}else{
					$sernos = "<input type='hidden' name='sernos[]' value=''>";
				}

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
				$stk['cselamt'] = sprint($stk['selamt']/$inv['xrate']);

				# Input qty if not serialised
				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";
				if($stk['serd'] == 'yes'){
					$qtyin = "<input type='hidden' size='3' name='qtys[]' value='$qtyss[$key]'>$qtyss[$key]";
				}

//				$stk['cselamt'] = sprint ($stk['cselamt']);
				$stk['selamt'] = sprint ($stk['selamt']);
				# Check permissions
				if(perm("invoice-unitcost-edit.php")){
					$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stk[selamt]'>";
					$cunitcost = "<input type='text' size='8' name='cunitcost[]' value='$stk[cselamt]'>";
				}else{
					$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='$stk[selamt]'>$stk[selamt]";
					$cunitcost = "<input type='hidden' size='8' name='cunitcost[]' value='$stk[cselamt]'>$stk[cselamt]";
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
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
						<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$Vatcodes</td>
						<td>$sernos</td>
						<td>".extlib_rstr($stk['stkdes'], 30)."</td>
						<td>$qtyin</td>
						<td>".CUR." $viewcost</td>
						<td>$inv[currency] $cunitcost</td>
						<td>$inv[currency]  <input type='text' size='4' name='disc[]' value='$discs[$key]'> OR <input type='text' size='4' name='discp[]' value='$discps[$key]' maxlength='5'>%</td>
						<td><input type='hidden' name='amt[]' value='$amt[$key]'> $inv[currency] $amt[$key]</td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					# skip if not selected
					if($whid == "-S"){
						continue;
					}

					# get warehouse name
					db_conn("exten");

					$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					if(isset($ria) && $ria != "") {$len=strlen($ria);$Wh="AND lower(substr(stkcod,1,'$len'))=lower('$ria')";} else {$Wh="";$ria="";}

					# get stock on this warehouse
					db_connect();

					$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class='err'>There are no stock items in the selected warehouse.";
						continue;
					}
					if (pg_numrows ($stkRslt) == 1) {
						$ex = "selected";
					} else {
						$ex = "";
					}
					$stks = "<select class='width : 15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
					$stks .= "<option value='-S' disabled selected>Select Number</option>";
					$count = 0;
					while($stk = pg_fetch_array($stkRslt)){
						$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
					}
					$stks .= "</select> ";

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
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
							<td>$stks</td>
							<td><input type='hidden' name='vatcodess' value='0'></td>
							<td> </td>
							<td> </td>
							<td><input type='hidden' size='3' name='qtyss[]'  value='1'>1</td>
							<td> </td>
							<td> </td>
							<td>$inv[currency] <input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
							<td><input type='hidden' name='amts[]' value='0.00'>$inv[currency] 0.00</td>
							<td></td>
						</tr>";
				}
			}
		}
	}else{
		if(!isset($diffwhBtn)){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd)){$whid=$wtd;}

				# get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				if(isset($ria) && $ria != "") {$len = strlen($ria);$Wh = "AND lower(substr(stkcod,1,'$len'))=lower('$ria')";} else {$Wh="";$ria="";}

				# get stock on this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!isset($err)){$err="";}
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
					<input type='hidden' name='vatcodess[]' value=''>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
						<td>$stks</td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td><input type='hidden' size='3' name='qtyss[]' value='1'>1</td>
						<td> </td>
						<td> </td>
						<td>$inv[currency] <input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
						<td>$inv[currency] 0.00</td>
						<td></td>
					</tr>";
			}else{
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$whs</td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td>$inv[currency]<input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
						<td>$inv[currency] 0.00</td>
						<td></td>
					</tr>";
			}
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$whs</td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td>$inv[currency]<input type='text' size='4' name='discs[]' value='0'> OR <input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td>
				<td>$inv[currency] 0.00</td>
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
		$traddiscm = sprint(($inv['traddisc']/100) * ($SUBTOT + $inv['delchrg']));
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
		db_connect();
		#check againg credit limit
		if(($TOTAL + $cust['balance']) > $cust['credlimit']){
			$error .= "<li class='err'>Warning : Customers Credit limit of <b>$inv[currency] $cust[credlimit]</b> has been exceeded";
			# Check permissions
			if(!perm("invoice-limit-override.php")){
				$done = "";
			}
		}
		$avcred = ($cust['credlimit'] - $cust['balance']);
	}else{
		$avcred = "0.00";
	}
/*--- Start checks --- */

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
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$chp = "checked";
	} else {
		$chp = "";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if(!isset($stkerr))
		$stkerr = "";

	$avcred = sprint ($avcred);

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>New International Invoice</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='letters' value='$letters'>
			<input type='hidden' name='stkerr' value='$stkerr'>
			<table ".TMPL_tblDflts." width='95%'>
				<tr>
					<td valign='top' width='50%'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Customer Details </th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Department</td>
								<td valign='center'>$dept[deptname]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Account No.</td>
								<td valign='center'>$cust[accno]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Customer</td>
								<td valign='center'>$customers</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td valign='top'>Customer Address</td>
								<td valign='center'>".nl2br($cust['addr1'])."</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Customer Order number</td>
								<td valign='center'><input type='text' size='10' name='cordno' value='$inv[cordno]'></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Customer Vat Number</td>
								<td>$cust[vatnum]</td>
							</tr>
							<tr>
								<th colspan='2'>Point of Sale</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Barcode</td>
								<td><input type='text' size='13' name='bar' value=''></td>
							</tr>
							<tr bgcolor='".bgcolorg()."' ".ass("Type the first letters of the stock code you are looking for.").">
								<td>Stock Filter</td>
								<td><input type='text' size='13' name='ria' value='$ria' onkeyup='javasript:predict()'></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Print Delivery Note</td>
								<td><input type='checkbox' name='printdel' $chp></td>
							</tr>
						</table>
					</td>
					<td valign='top' align='right' width='50%'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Invoice Details </th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Invoice No.</td>
								<td valign='center'>TI $inv[invid]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Proforma Inv No.</td>
								<td valign='center'><input type='text' size='5' name='docref' value='$inv[docref]'></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Sales Order No.</td>
								<td valign='center'><input type='text' size='5' name='ordno' value='$inv[ordno]'></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Foreign Currency</td>
								<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." <input type='text' size='7' name='xrate' value='$inv[xrate]'></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>VAT Inclusive</td>
								<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin> No<input type='radio' size='7' name='chrgvat' value='exc' $chex></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Terms</td>
								<td valign='center'>$termssel Days</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Sales Person</td>
								<td valign='center'>$salesps</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Invoice Date</td>
								<td valign='center'>".mkDateSelect("o",$o_year,$o_month,$o_day)."</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Available Credit</td>
								<td>$inv[currency] $avcred</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Trade Discount</td>
								<td valign='center'><input type='text' size='5' name='traddisc' value='$inv[traddisc]'>%</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Delivery Charge</td>
								<td valign='center'>$inv[currency]<input type='text' size='7' name='delchrg' value='$inv[delchrg]'>$Vatcodes</td>
							</tr>
						</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td colspan='2'>$products</td></tr>
			<tr>
				<td>
					<p>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Comments</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='cust-credit-stockinv.php'>New Invoice</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$inv[comm]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right' valign='top'>
					<table ".TMPL_tblDflts." width='50%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>$inv[currency] <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right'>$inv[currency] $inv[discount]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right'>$inv[currency] $inv[delivery]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right'>$inv[currency] $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>$inv[currency] $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input name='diffwhBtn' type='submit' value='Different Store'> | <input name='addprodBtn' type='submit' value='Add Product'> | <input type='submit' name='saveBtn' value='Save'> </td>
				<td>| <input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>";
	return $details;

}


# details
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	if (!isset($ria)) {$ria = "";}
	$v->isOk ($ria, "string", 0, 20, "Invalid stock code(fist letters).");

	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($docref, "string", 0, 20, "Invalid Document Reference No.");
	$v->isOk ($ordno, "num", 0, 20, "Invalid sales order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($o_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($o_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($o_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $o_year."-".$o_month."-".$o_day;
	if(!checkdate($o_month, $o_day, $o_year)){
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

	# check is serai no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && !(strlen($sernos[$keys]) > 0)){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}
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
			$unitcost[$keys] += 0;
			$cunitcost[$keys] += 0;
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($cunitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
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
			$v->isOk ($amount, "float", 1, 20, "Invalid  Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($HTTP_POST_VARS, $err);
	}



	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	$inv['traddisc'] = $traddisc;
	$inv['chrgvat'] = $chrgvat;

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	db_connect();

	if(isset($printdel)) {

		$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri) < 1) {
			$Sl = "INSERT INTO settings (constant,value,div) VALUES ('Delivery Note','Yes','".USER_DIV."')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE settings SET value='Yes' WHERE constant='Delivery Note' AND div='".USER_DIV."'";
			$Ri = db_exec($Sl);
		}
	} else {
		$Sl = "UPDATE settings SET value='No' WHERE constant='Delivery Note' AND div='".USER_DIV."'";
		$Ri = db_exec($Sl);
	}

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

		# currency
		$currs = getSymbol($inv['fcid']);
	}else{
		$cust = pg_fetch_array($custRslt);

		# If customer was just selected/changed, get the following
		if($inv['cusnum'] != $cusnum){
			$traddisc = $cust['traddisc'];
			$terms = $cust['credterm'];
			$xrate = getRate($cust['fcid']);
		}
		# currency
		$currs = getSymbol($cust['fcid']);
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$xrate += 0;
	if($xrate == 0) $xrate = 1;
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
			$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);

			while($stkt = pg_fetch_array($stktRslt)){
				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

				if(strlen($stkt['serno']) > 0)
					ext_unresvSer($stkt['serno'], $stkt['stkid']);
			}

			# remove old items
			$sql = "DELETE FROM inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
		/* -- End remove old items -- */

		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)&&in_array($keys, $remprod)){

// 				if(isset($remprod)){
// 					if(in_array($keys, $remprod)){
// 						# skip product (wonder if $keys still align)
// 						$amt[$keys] = 0;
// 						continue;
// 					}else{
// 						# get selamt from selected stock
// 						$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
// 						$stkRslt = db_exec($sql);
// 						$stk = pg_fetch_array($stkRslt);
//
// 						$t=$cunitcost[$keys];
//
// 						# Calculate the unitcost
// 						if($cunitcost[$keys] > 0 && $unitcost[$keys] == 0){
// 							$unitcost[$keys] = ($cunitcost[$keys] * $xrate);
// 						}else{
// 							$cunitcost[$keys] = ($unitcost[$keys]/$xrate);
// 						}
//
// 						# Calculate the Discount discount
// 						if($disc[$keys] < 1){
// 							if($discp[$keys] > 0){
// 								$disc[$keys] = (($discp[$keys]/100) * $t);
// 							}
// 						}else{
// 							$discp[$keys] = (($disc[$keys] * 100) / $t);
// 						}
//
// 						# Calculate amount
// 						$funitcost[$keys] = $unitcost[$keys];
// 						$famt[$keys] = ($qtys[$keys] * ($funitcost[$keys]));
//
// 						# Calculate amount
// 						// $amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));
// 						$unitcost[$keys] = sprint($funitcost[$keys]/$xrate);
// 						$amt[$keys] = sprint($famt[$keys]/$xrate-($disc[$keys]));
//
// 						$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
// 						$Ri=db_exec($Sl);
//
// 						if(pg_num_rows($Ri)<1) {
// 							return details($HTTP_POST_VARS, "<li class=err>Please select the vatcode for all your items.</li>");
// 						}
// 						$vd=pg_fetch_array($Ri);
//
// 						# Check Tax Excempt
// 						if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
// 							$taxex += $amt[$keys];
// 						}
//
// 						# insert invoice items
// 						$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, funitcost, amt, famt, disc, discp, serno, div,vatcode,del) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$funitcost[$keys]', '$amt[$keys]', '$famt[$keys]', '$disc[$keys]', '$discp[$keys]', '$sernos[$keys]', '".USER_DIV."','$vatcodes[$keys]','0')";
// 						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
//
// 						if(strlen($stkt['serno']) > 0)
// 							ext_resvSer($stkt['serno'], $stk['stkid']);
//
// 						# update stock(alloc + qty)
// 						$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
// 						$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
// 					}
				}else{
					# Get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					/*# Calculate the unitcost
					if($cunitcost[$keys] > 0 && $unitcost[$keys] == 0){
						$unitcost[$keys] = ($cunitcost[$keys] * $xrate);
					}else{
						$cunitcost[$keys] = ($unitcost[$keys]/$xrate);
					}*/

					$t=$cunitcost[$keys];

					# Calculate the unitcost
					if($unitcost[$keys] > 0 && $cunitcost[$keys] == 0){
						$cunitcost[$keys] = ($unitcost[$keys]/$xrate);
					}else{
						$unitcost[$keys] = ($cunitcost[$keys]*$xrate);
					}

					# Calculate the Discount discount
					if($disc[$keys] < 1){
						if($discp[$keys] > 0){
							$disc[$keys] = (($discp[$keys]/100) * $t);
						}
					}else{
						$discp[$keys] = (($disc[$keys] * 100) / $t);
					}

					if($xrate < 1) $xrate = 1;

					//$disc[$keys]=$disc[$keys]*$xrate;

					# Calculate amount
					$funitcost[$keys] = $unitcost[$keys];
					$famt[$keys] = ($qtys[$keys] * ($funitcost[$keys] ));
					//$famt[$keys] = ($qtys[$keys] * ($funitcost[$keys] - $disc[$keys]));

					# Calculate amount
					// $amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));
					$unitcost[$keys] = sprint($funitcost[$keys]/$xrate);
					$amt[$keys] = sprint($famt[$keys]/$xrate-($disc[$keys]));
					//$amt[$keys] = sprint($famt[$keys]/$xrate);

					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
						return details($HTTP_POST_VARS, "<li class='err'>Please select the vatcode for all your items.</li>");
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
					}

					# insert invoice items
					$sql = "
						INSERT INTO inv_items (
							invid, whid, stkid, qty, unitcost, 
							funitcost, amt, famt, disc, 
							discp, serno, div, vatcode, del
						) VALUES (
							'$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
							'$funitcost[$keys]', '$amt[$keys]', '$famt[$keys]', '$disc[$keys]', '$discp[$keys]', 
							'$sernos[$keys]', '".USER_DIV."', '$vatcodes[$keys]', '0'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

					if(strlen($sernos[$keys]) > 0)
						ext_resvSer($sernos[$keys], $stk['stkid']);

					# update stock(alloc + qty)
					$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
				$HTTP_POST_VARS["done"] = " | <input name='doneBtn' type='submit' value='Process'>";
			}
		}else{
			$HTTP_POST_VARS["done"] = "";
		}


		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$delvat'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

// 		if(pg_num_rows($Ri)>0) {
// 			$taxex += $delchrg;
// 		}

		if($vd['zero'] == "Yes") {
			$excluding = "y";
		} else {
			$excluding = "";
		}

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$HTTP_POST_VARS['showvat'] = $showvat;

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
			$traddiscmt = sprint($subtotal * $traddisc / 100);
			$subtotal = sprint($subtotal - $traddiscmt);
		//	$VAT=sprint(($subtotal-$taxex)*$VATP/100);
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

		$FTOTAL = sprint($TOTAL * $xrate);

/* --- ----------- Clac --------------------- */

		# insert invoice to DB
		$sql = "
			UPDATE invoices 
			SET delvat='$delvat', cusnum = '$cusnum', deptname = '$dept[deptname]', cusacc = '$cust[accno]', 
				cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', 
				cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', chrgvat = '$chrgvat', docref = '$docref', 
				terms = '$terms', salespn = '$salespn', fcid = '$cust[fcid]', currency = '$currs[symbol]', xrate = '$xrate', 
				odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', subtot = '$SUBTOT', vat = '$VAT', 
				total = '$TOTAL', balance = '$FTOTAL', fbalance = '$TOTAL', comm = '$comm', location = '$cust[location]', 
				serd = 'y', discount='$traddiscmt', delivery='$delexvat' 
			WHERE invid = '$invid'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM inv_data WHERE invid='$invid'  AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# pu in new data
		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	if (strlen($bar) > 0) {

		$Sl = "SELECT * FROM possets WHERE div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

		if (pg_numrows ($Rs) < 1){
			return details($HTTP_POST_VARS,"<a href='pos-set.php'>Please set the point of sale setting by clicking here.</a>");
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
						return details($HTTP_POST_VARS,"The code you selected is invalid");

			}

			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = barext_dbget($tab,'code',$bar,'stock');

			if(!($stid > 0)){return details($HTTP_POST_VARS,"The bar code you selected is not in the system or is not available.");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "
				INSERT INTO inv_items (
					invid, whid, stkid, qty, unitcost, amt, disc, discp,ss, div, del
				) VALUES (
					'$invid', '$s[whid]', '$stid', '1', '$s[selamt]', '$s[selamt]', '0', '0', '$bar', '".USER_DIV."', '0'
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

			if(!($stid > 0)){return details($HTTP_POST_VARS,"The bar code you selected is not in the system or is not available.");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "
				INSERT INTO inv_items (
					invid, whid, stkid, qty, unitcost, amt, disc, discp,ss, div, del
				) VALUES (
					'$invid', '$s[whid]', '$stid', '1', '$s[selamt]', '$s[selamt]', '0', '0','$bar', '".USER_DIV."', '0'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}

	}

/* --- Start button Listeners --- */
	if(isset($doneBtn)){

		# Check if stock was selected(yes = put done button)
		db_connect();

		$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class='err'> Error : Invoice number has no items.</li>";
			return details($HTTP_POST_VARS, $error);
		}

		# Insert quote to DB
		$sql = "UPDATE invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);

		$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		$data = pg_fetch_array($Ri);

		if($data['value'] == "Yes") {
			# Print the invoice
			$OUTPUT = "<script>nhprinter('invoice-delnote.php?invid=$invid','Delivery Note');printer('intinvoice-print.php?invid=$invid');move('main.php');</script>";
		} else {
			# Print the invoice
			$OUTPUT = "<script>printer('intinvoice-print.php?invid=$invid');move('main.php');</script>";
		}
		require("template.php");


	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New International Invoice Saved</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>International Invoice for customer <b>$cust[cusname] $cust[surname]</b> has been saved.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='invoice-view.php'>View Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}else{
		if(isset($wtd)){$HTTP_POST_VARS['wtd'] = $wtd;}
		if(strlen($ria) > 0){$HTTP_POST_VARS['ria'] = $ria;}
		return details($HTTP_POST_VARS);
	}

}


?>
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
require_lib("validate");
require_lib("customers");

# decide what to do
if (isset($HTTP_GET_VARS["invid"]) && isset($HTTP_GET_VARS["cont"])) {
	$HTTP_GET_VARS["stkerr"] = '0,0';
	$HTTP_GET_VARS["done"] = '';
	$HTTP_GET_VARS["client"] = '';
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "recvpayment_write":
				$OUTPUT = recvpayment_write();
				break;
			case "details":
				$OUTPUT = details($HTTP_POST_VARS);
				break;
			case "update":
				if (isset($_POST["recvpay"])) {
					$OUTPUT = recvpayment();
				} else {
					$OUTPUT = write($HTTP_POST_VARS);
				}
				break;
			default:
				$OUTPUT = details($HTTP_POST_VARS);
		}
	} else {
		$OUTPUT = details($HTTP_POST_VARS);
	}
}

# get templete
require("template.php");

# select department
function view()
{

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT deptid,deptname FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
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
					<tr>
						<th colspan='2'>New Point of Sale Invoice(Cash)</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>"
				.mkQuickLinks(
					ql("pos-invoice-list.php", "View Point of Sale Invoices"),
					ql("customers-new.php", "New Customer")
				);
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
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
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
					<tr>
						<th colspan='2'>New Invoice</th>
					</tr>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>"
				.mkQuickLinks(
					ql("pos-invoice-list.php", "View Point of Sale Invoices"),
					ql("customers-new.php", "New Customer")
				);
	return $view;

}


# create a dummy invoice
function create_dummy($deptid){

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
	$vatnum = "";
	$cusacc = "";
	$telno = "";

	// $invid = divlastid('pinv', USER_DIV);

	# insert invoice to DB
	$sql = "INSERT INTO cubit.pinvoices(deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn,
				odate, delchrg, subtot, vat, total, balance, comm, username, printed, done, prd, vatnum,
				cusacc, telno, div)
			VALUES('$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn',
				'$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."',
				'n', 'n', '".PRD_DB."', '$vatnum', '$cusacc', '$telno', '".USER_DIV."')";
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

	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}else{
		$client="";
		$vatnum = "";
		$cordno = "";
		$deptid=2;
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
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

	// Initial values
	if(!isset($invid)) {
		$invid = create_dummy($deptid);
		$stkerr = "0,0";
		$cusnum = 0;
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if(!isset($vatnum))
		$vatnum = $inv['vatnum'];
	if(!isset($cordno))
		$cordno = $inv['cordno'];

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// get the ID of the first warehouse
	db_conn("exten");
	$sql = "SELECT whid FROM warehouses ORDER BY whid ASC LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading warehouses (FWH).");

	if ( pg_num_rows($rslt) > 0 ) {
		$FIRST_WH = pg_fetch_result($rslt, 0, 0);
	} else {
		$FIRST_WH = "-S";
	}

	# Get selected Customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND location != 'int' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		db_connect();
		# Query server for customer info   AND lower(surname) LIKE lower('$letters%')
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE location != 'int' AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='0' selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='0' selected>Select Customer</option>";
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
		$cust["bustel"] = $inv["telno"];
		$cust["tel"] = "";
		$cust["cellno"] = "";
	}else{
		$cust = pg_fetch_array($custRslt);

		$sql = "SELECT cusnum, cusname, surname FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
		# Moarn if customer account has been blocked   AND lower(surname) LIKE lower('$letters%')
		if($cust['blocked'] == 'yes'){
			$error .= "<li class=err>Error : Selected customer account has been blocked.";
		}

		// $customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
		$cusnum = $cust['cusnum'];

		$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
		$customers .= "<option value='0' selected>Select Customer</option>";
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

	/* after first customer selection, set telno to customer's (if any) */
	if (isset($prev_cusnum) && $prev_cusnum != $cusnum) {
		if (trim($cust["bustel"]) != "") {
			$inv["telno"] = $cust["bustel"];
		} else if (trim($cust["tel"]) != "") {
			$inv["telno"] = $cust["tel"];
		} else {
			$inv["telno"] = $cust["cellno"];
		}
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
			if (!user_in_store_team($wh["whid"], USER_ID)) continue;
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

	# get sales people
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

	/* we came as a correction from receive payment page */
	if (isset($_POST["key"]) && $_POST["key"] == "recvpayment_write") {
		$inv["odate"] == $_POST["date"];
		$inv["pcc"] = $_POST["pcc"];
		$inv["pcheque"] = $_POST["pcheque"];
		$inv["pcash"] = $_POST["pcash"];
	} else {
		list($pinv_year, $pinv_month, $pinv_day) = explode("-", $inv['odate']);
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
	<table ".TMPL_tblDflts." width='100%'>
	<tr>
		<th>STORE</th>
		<th>ITEM NUMBER</th>
		<th>VAT CODE</th>
		<th>SERIAL NO.</th>
		<th>DESCRIPTION</th>
		<th>QTY</th>
		<th>UNIT PRICE</th>
		<th>UNIT DISCOUNT</th>
		<th>AMOUNT</th>
		<th>Remove</th>
	<tr>";

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$line_count = pg_num_rows($stkdRslt);
	$i = 0;
	$key = 0;
	while ($stkd = pg_fetch_array($stkdRslt)) {
		$stkd['account']+=0;
		if($stkd['account']!=0) {
			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');
			$Sl="SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri=db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts="<select name='accounts[]'>
			<option value='0'>Select Account</option>";

			while($ad=pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				if($ad['accid']==$stkd['account']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Accounts.="<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}

			$Accounts.="</select>";

			$sernos = "
				<input type='hidden' name='sernos[]' value='$stkd[serno]'>
				<input type='hidden' name='sernos_ss[]' value='$stkd[serno]'>";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";

			$viewcost = "<input type='text' size='8' name='unitcost[]' value='".sprint($stkd["unitcost"])."'>";

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes="<select name='vatcodes[]'>";

			while($vd=pg_fetch_array($Ri)) {
				if($stkd['vatcode']==$vd['id']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}

			$Vatcodes.="</select>";

			# Put in product
			$products .="
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$Accounts<input type='hidden' name='whids[]' value='$stkd[whid]'></td>
				<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'>$Vatcodes</td>
				<td>&nbsp;</td>
				<td><input type='text' size='20' name='descriptions[]' value='$stkd[description]'> $sernos</td>
				<td>$qtyin</td>
				<td>$viewcost</td>
				<td>
					<input type='hidden' name='disc[]' value='$stkd[disc]'>
					<input type='hidden' name='discp[]' value='$stkd[discp]'>
				</td>
				<td><input type='hidden' name='amt[]' value='".sprint($stkd["amt"])."'> ".CUR." $stkd[amt]</td>
				<td>
					<input type='checkbox' name='remprod[]' value='$key'>
				</td>
			</tr>";
			$key++;
		}else{
			# keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			if($stk['units']<=$stk['minlvl']) {
				$error.="<li class='err'>$stk[stkcod] is below minimum level, please notify stock controller.</li>";
			}

			# Serial number
			if($stk['serd'] == 'yes' && ($inv['serd'] == 'n' || $stkd["serno"] == "")){
				$sers = ext_getavserials($stkd['stkid']);
				$sernos = "<select name='sernos[]'>";
				foreach($sers as $skey => $ser){
					$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
				}
				$sernos .= "</select>
							<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
			}else{
				$sernos = "
					<input type='hidden' name='sernos_ss[]' value='$stkd[ss]' />
					<input type='hidden' name='sernos[]' value='$stkd[serno]'>$stkd[ss]";
			}

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";
			if($stk['serd'] == 'yes'){
				$qtyin = "<input type='hidden' size='3' name='qtys[]' value='$stkd[qty]'>$stkd[qty]";
			}

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes="<select name='vatcodes[]'>";

			while($vd=pg_fetch_array($Ri)) {
				if($stkd['vatcode']==$vd['id']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}

			$Vatcodes.="</select>";

			# check permissions
			if(perm("invoice-unitcost-edit.php")){
				$viewcost = "<input type='text' size='8' name='unitcost[]' value='".sprint($stkd["unitcost"])."'>";
			}else{
				$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='".sprint($stkd["unitcost"])."'>".sprint($stkd["unitcost"]);
			}

			# put in product
			$products .= "
			<input type='hidden' name='accounts[]' value='0'>
			<input type='hidden' name='descriptions[]' value=''>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
				<td>
					<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
					<a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
				</td>
				<td>$Vatcodes</td>
				<td>$sernos</td>
				<td>".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>$qtyin</td>
				<td>$viewcost</td>
				<td>
					<input type='text' size='4' name='disc[]' value='$stkd[disc]'><b> OR </b>
					<input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%
				</td>
				<td><input type='hidden' name='amt[]' value='".sprint($stkd["amt"])."'> ".CUR. sprint($stkd["amt"])."</td>
				<td>
					<input type='checkbox' name='remprod[]' value='$key'>
				</td>
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
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S" && (strlen($stkidss[$key]) > 0)){
				# skip if not selected
				if ($whid == "-S") {
					continue;
				}

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				if($stk['serd'] == 'yes'){
					$sers = ext_getavserials($stkidss[$key]);
					$sernos = "<select name='sernos[]' onChange='javascript:document.form.submit();'>";
					foreach($sers as $skey => $ser){
						$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
					}
					$sernos .= "</select>
								<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
				}else{
					$sernos = "
						<input type='hidden' name='sernos_ss[]' value=''>
						<input type='hidden' name='sernos[]' value=''>";
				}

				/* -- Start Some Checks -- */
				# check if they are selling too much
				if(($stk['units'] - $stk['alloc']) < $qtyss[$key]) {
					if(!in_array($stk['stkid'], explode(",", $stkerr))) {
						if($stk['type'] != 'lab') {
							$stkerr .= ",$stk[stkid]";
							$error .= "<li class='err'>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}
				/* -- End Some Checks -- */

				# Calculate the Discount discount
				if ($discs[$key] < 1) {
					if($discps[$key] > 0){
						$discs[$key] = round((($discps[$key]/100) * $stk['selamt']), 2);
					}
				} else {
					$discps[$key] = round((($discs[$key] * 100) / $stk['selamt']), 2);
				}

				# Calculate amount
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				# Input qty if not serialised
				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";
				if($stk['serd'] == 'yes'){
					$qtyin = "<input type='hidden' size='3' name='qtys[]' value='$qtyss[$key]'>$qtyss[$key]";
				}

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes ORDER BY code";
				$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes="<select name='vatcodes[]'>";

				while($vd=pg_fetch_array($Ri)) {
					if($stk['vatcode']==$vd['id']) {
						$sel="selected";
					} else {
						$sel="";
					}
					$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
				}

				$Vatcodes.="</select>";

				if (perm("invoice-unitcost-edit.php")) {
					$viewcost = "<input type='text' size='8' name='unitcost[]' value='".sprint($stk["selamt"])."'>";
				} else {
					$viewcost = "<input type='hidden' size='8' name='unitcost[]' value='".sprint($stk["selamt"])."'>".sprint($stk["selamt"]);
				}

				# put in selected warehouse and stock
				$products .= "
				<input type='hidden' name='accounts[]' value='0'>
				<input type='hidden' name='descriptions[]' value=''>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
					<td>
						<input type='hidden' name='stkids[]' value='$stk[stkid]'>
						<a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
					</td>
					<td>$Vatcodes</td>
					<td>$sernos</td>
					<td>".extlib_rstr($stk['stkdes'], 30)."</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td>
						<input type='text' size='4' name='disc[]' value='$discs[$key]'><b> OR </b>
						<input type='text' size='4' name='discp[]' value='$discps[$key]' maxlength='5'>%
					</td>
					<td><input type='hidden' name='amt[]' value='".sprint($amt[$key])."'> ".CUR. sprint($amt[$key])."</td>
					<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
				</tr>";
				$line_count = 1;
				$keyy++;
			} else if (isset($accountss[$key]) && $accountss[$key] != "0" ) {
				db_conn('core');
				$Sl="SELECT * FROM accounts WHERE accid='$accountss[$key]'";
				$Ri=db_exec($Sl) or errDie("Unable to get account data.");

				if(pg_num_rows($Ri)<1) {
					return "invalid.";
				}

				$ad=pg_fetch_array($Ri);

				# Calculate amount
				$amt[$key] =sprint($qtyss[$key] * ($unitcosts[$key]));

				# Input qty if not serialised
				//$qtyin = "<input type=text size=3 name=qtemp value='$qtyss[$key]'>";
				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";

				# Check permissions
				$viewcost = "<input type='text' size='8' name='unitcost[]' value='".sprint($unitcosts[$key])."'>";

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes ORDER BY code";
				$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes="<select name='vatcodes[]'>";

				while($vd=pg_fetch_array($Ri)) {
					if($vatcodess[$key]==$vd['id']) {
						$sel="selected";
					} else {
						$sel="";
					}
					$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
				}

				$Vatcodes.="</select>";

				# Put in selected warehouse and stock
				$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan=2>
						$ad[accname]<input type='hidden' name='accounts[]' value='$accountss[$key]'>
						<input type='hidden' name='whids[]' value='0'>
					</td>
					<td>$Vatcodes<input type='hidden' name='stkids[]' value='0'></td>
					<td>&nbsp;</td>
					<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$key]'></td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td>
						<input type='hidden' name='disc[]' value='0'>
						<input type='hidden' name='discp[]' value='0'>
					</td>
					<td><input type='hidden' name='amt[]' value='".sprint($amt[$key])."'> ".CUR . sprint($amt[$key])."</td>
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

						if(isset($des)and $des!="") {
							$len=strlen($des);
							if($des == "Show All"){
								$Wh = "";
								$des = "";
							}else {
								$Wh="AND (lower(substr(stkdes,1,'$len'))=lower('$des') OR lower(substr(stkcod,1,'$len'))=lower('$des'))";
							}
						} else {
							$Wh="AND FALSE";
							$des="";
						}

						# get stock on this warehouse
						db_connect();
						$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY $sel_frm ASC";
						$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
						if (pg_numrows ($stkRslt) < 1) {
							$error .= "<li class='err'>There are no stock items in the selected warehouse.";
							continue;
						}

/*						# get selected stock in this warehouse
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
							$sernos = "<input type=hidden name=sernos[] value=''>";
						}
*/

						if (isset($sel_frm) && $sel_frm == "stkdes") {
							$descs = "<select class='width : 15'name='stkidss[]' onChange=\"document.form.des.value=''; javascript:document.form.submit();\">";
							$descs .= "<option value='-S' disabled selected>Select Description</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								// Check if this stock item has been blocked
								if (stock_is_blocked($stk["stkid"])) {
									continue;
								}
								
								if ($stk["units"] <= 0) {
									continue;
								}
								
								$descs .= "<option value='$stk[stkid]'>$stk[stkdes] (".($stk['units'] - $stk['alloc']).")</option>";
							}
							$descs .= "</select> ";

							$cods = "";
						} else {
							$cods = "<select class='width : 15'name='stkidss[]' onChange=\"document.form.des.value=''; javascript:document.form.submit();\">";
							$cods .= "<option value='-S' disabled selected>Select Number</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								// Check if this stock item has been blocked
								if (stock_is_blocked($stk["stkid"])) {
									continue;
								}

								if ($stk["units"] <= 0) {
									continue;
								}

								$cods .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
							}
							$cods .= "</select> ";

							$descs = "";
						}

						# put in drop down and warehouse
						$products .= "
						<input type='hidden' name='accountss[]' value='0'>
						<input type='hidden' name='descriptionss[]' value=''>
						<tr bgcolor='".bgcolorg()."'>
							<td>
								<input type='hidden' name='whidss[]' value='$whid'>
								$wh[whname]
							</td>
							<td>$cods</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>$descs</td>
							<td>
								<input type='text' size='3' name='qtyss[]' value='1'>
							</td>
							<td>&nbsp;</td>
							<td>
								<input type='text' size='4' name='discs[] value='0'>
								<b> OR </b>
								<input type='text' size='4' name='discps[] value='0' maxlength='5'>%
							</td>
							<td>
								<input type='hidden' name='amts[]' value='0.00'>
								".CUR." 0.00
							</td>
							<td>&nbsp;</td>
						</tr>";
					}else{
						db_conn('core');
						$Sl="SELECT accid,topacc,accnum,accname FROM accounts
							WHERE acctype='I' ORDER BY accname";
						$Ri=db_exec($Sl) or errDie("Unable to get accounts.");

						$Accounts="<select name='accountss[]' onChange='javascript:document.form.submit();'>
						<option value='0'>Select Account</option>";

						while($ad=pg_fetch_array($Ri)) {
							if(isb($ad['accid'])) {
								continue;
							}
							$Accounts.="
							<option value=$ad[accid]>
								$ad[accname]
							</option>";
						}

						$Accounts.="</select>";

						db_conn('cubit');
						$Sl="SELECT * FROM vatcodes ORDER BY code";
						$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

						$Vatcodes="<select name='vatcodess[]'>";

						while($vd=pg_fetch_array($Ri)) {
							if($vd['del']=="Yes") {
								$sel="selected";
							} else {
								$sel="";
							}
							$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
						}

						$Vatcodes.="</select>";


						$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2'>$Accounts<input type='hidden' name='whidss[]' value='$FIRST_WH'></td>
							<input type='hidden' name='stkidss[]' value=''>
							<td>$Vatcodes</td>
							<td>&nbsp;</td>
							<td><input type='text' size='20' name='descriptionss[]'></td>
							<td><input type='text' size='3' name='qtyss[]' value='1'></td>
							<td><input type='text' name='unitcosts[]' size='7'></td>
							<td>&nbsp;</td>
							<td>".CUR." 0.00</td>
							<td>
								<input type='hidden' name='discs[]' value='0'>
								<input type='hidden' name='discps[]' value='0' >
							</td>
						</tr>";
					}
				}
			}
		}
	} else {
		if(!(isset($diffwhBtn) || isset($addnon))){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd)&&$wtd!=0){$whid=$wtd;}
    			# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				if(isset($des) and $des!="") {
					$len=strlen($des);
					if($des == "Show All"){
						$Wh = "";
						$des = "";
					}else {
						$Wh="AND (lower(substr(stkdes,1,'$len'))=lower('$des') OR lower(substr(stkcod,1,'$len'))=lower('$des'))";
					}
				} else {
					$Wh="AND FALSE";
					$des="";
				}

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!(isset($err))){$err="";}
					$err .= "<li>There are no stock items in the selected store.";
				}
				$stks = "<select name='stkidss[]' onChange=\"document.form.des.value=''; javascript:document.form.submit();\">";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					// Check if this stock item has been blocked
					if (stock_is_blocked($stk["stkid"])) {
						continue;
					}
					
					if ($stk["units"] <= 0) {
						continue;
					}

					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
				$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<input type='hidden' name='accountss[]' value='0'>
					<input type='hidden' name='descriptionss[]' value=''>
					<input type='hidden' name='vatcodess[]' value=''>
					<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
					<td>$stks</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					<td>&nbsp;</td>
					<td>
						<input type='text' size='4' name='discs[]' value='0'><b> OR </b>
						<input type='text' size='4' name='discps[]' value='0' maxlength='5'>%</td><td>".CUR." 0.00</td>
					<td>&nbsp;</td>
				</tr>";
			}else{
				$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$whs</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>
						<input type='text' size='4' name='discs[]' value='0'><b> OR </b>
						<input type='text' size='4' name='discps[]' value='0' maxlength='5'>%
					</td>
					<td>".CUR." 0.00</td>
					<td>&nbsp;</td>
				</tr>";
			}
		} else if ( isset($addnon) ) {
			db_conn('core');
			$Sl="SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri=db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts="<select name='accountss[]' onChange='javascript:document.form.submit();'>
			<option value='0'>Select Account</option>";

			while($ad=pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				$Accounts.="<option value='$ad[accid]'>$ad[accname]</option>";
			}

			$Accounts.="</select>";

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes="<select name='vatcodess[]'>";

			while($vd=pg_fetch_array($Ri)) {
				if($vd['del']=="Yes") {
					$sel="selected";
				} else {
					$sel="";
				}
				$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}

			$Vatcodes.="</select>";

//				<input type=hidden name='stkidss[]' value=''>
			$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan=2>
					$Accounts<input type='hidden' name='whidss[]' value='$FIRST_WH'>
				</td>
				<td>$Vatcodes</td>
				<td>&nbsp;</td>
				<td><input type='text' size='20' name='descriptionss[]'></td>
				<td><input type='text' size='3' name='qtyss[]' value='1'></td>
				<td><input type='text' name='unitcosts[]' size='7'></td>
				<td>&nbsp;</td>
				<td>".CUR." 0.00</td>
				<td>
					<input type='hidden' name='discs[]' value='0'>
					<input type='hidden' name='discps[]' value='0' >
				</td>
			</tr>";
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$whs</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>
				<input type='text' size='4' name='discs[]' value='0'><b> OR </b>
				<input type='text' size='4' name='discps[]' value='0' maxlength='5'>%
			</td>
			<td>".CUR." 0.00</td>
			<td>&nbsp;</td>
		</tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */
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

	if(!(isset($done))) {
		$done="";
	}

/* --- End Some calculations --- */

	if($inv['terms']==1) {
		$tc1="";
		$tc2="checked";
	} else {
		$tc1="checked";
		$tc2="";
	}

	db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='PSALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sc="checked";
	} else {
		$sc="";
	}

	$sales="<td>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$salesps</td>
				<td>Print</td>
				<td><input type='checkbox' name='printsales' $sc></td>
			</tr>
		</table>
		</td>";

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $inv['deptid']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM users WHERE username='".USER_NAME."'";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	if($data['help']!="S") {
		$save="|<input type='submit' name='saveBtn' value='Save'>";
	} else {
		$save="";
	}

	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rd = "
				<tr bgcolor='".bgcolorg()."'>
					<td>Rounding</td>
					<td align='right'>R $inv[rounding]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<th>Amount Due</th>
					<td align='right'>R $due</td>
				</tr>";

	} else {
		$rd="";
	}

	$inv['delvat']+=0;

	if($inv['delvat']==0) {
		$Sl="SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		$inv['delvat']=$vd['id'];
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name='delvat'>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['id']==$inv['delvat']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes.="</select>";

	if(strlen($client) < 1){
		$client = $inv['cusname'];
	}

	if($inv['cusnum']==0) {
		$cd = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td valign='center'><input type='text' size='20' name='client' value='$client'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Number</td>
				<td valign='center'><input type='text' size='20' name='vatnum' value='$vatnum'></td>
			</tr>
			";
		$pc="<input type='hidden' name='pcredit' value='0'>";
	} else {
		$cd = "
			<tr bgcolor='".bgcolorg()."'>
				<td valign=top>Customer Address</td>
				<td valign=center>".nl2br($cust['addr1'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer VAT Number</td>
				<td>$cust[vatnum]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer Balance</td>
				<td nowrap='t'>
					".CUR." $cust[balance]
					<a href='javascript: printer(\"cust-stmnt.php?cusnum=$cusnum&print=t\");'>Print Statement</a>
				</td>
			</tr>";
		$pc = "
		<tr bgcolor='".bgcolorg()."'>
			<td>Amount On Credit</td>
			<td nowrap='t'>
				<input size='12' type='text' name='pcredit' id='pcredit' value='$inv[pcredit]' onchange='ptot_update();'>
				<input type='button' value='&laquo Total' onclick='paytotal(\"pcredit\");' />
			</td>
		</tr>";

		if ($line_count > 0) {
			$recvpay = "";
		} else {
			$recvpay = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'><input type='submit' name='recvpay' id='recvpay' onclick='return ptot_recvpay();' value='Receive Payment: ".CUR." ".sprint($inv["pcc"] + $inv["pcheque"] + $inv["pcash"])."' /></td>
			</tr>";
		}
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if(!isset($des)) {
		$des="";
	}

	if (isset($sel_frm) && $sel_frm == "stkdes") {
		$sel_frm_cod = "";
		$sel_frm_des = "checked";
	} else {
		$sel_frm_cod = "checked";
		$sel_frm_des = "";
	}

	if (!isset($recvpay)) $recvpay = "";

	if (empty($inv["comm"])) {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_POS_COMMENTS'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
		$inv["comm"] = base64_decode(pg_fetch_result($rslt, 0));
	}

/* -- Final Layout -- */
	$details = "
	<script language=\"JavaScript\"><!--
		var windowReference;

	function openRFPopup() {
		windowReference = window.open('rfid_batch.php?invid=$invid','windowName','height=500,width=700,toolbar=no,menubar=no,scrollbars=no');
		if (!windowReference.opener)
			windowReference.opener = self;
		}
	//--></script>

	<center><h3>New Point of Sale Invoice</h3>
	<form method='POST' name='formName'>
	<input type='hidden' name='key' value='update' />
	<input type='hidden' name='invid' value='$invid' />
	<input type='hidden' name='SCROLL' value='yes'>
	</form>
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
		getObject('pcash').value = '0.00';
		getObject('pcc').value = '0.00';
		getObject('pcheque').value = '0.00';
		if (getObject('pcredit')) getObject('pcredit').value = '0.00';

		getObject(id).value = getObject('itotal').value;

		ptot_update();
	}
	</script>
	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='update'>
		<input type='hidden' name='invid' value='$invid'>
		<input type='hidden' name='stkerr' value='$stkerr'>
		<input type='hidden' id='itotal' value='$TOTAL' />
		<input type='hidden' name='prev_cusnum' value='$cusnum' />
		<input type='hidden' name='SCROLL' value='yes'>
	<table ".TMPL_tblDflts." width='95%'>
 		<tr>
 			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'> Customer Details </th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Customer</td>
						<td valign='center'>$customers</td>
					</tr>
					$cd
					<tr bgcolor='".bgcolorg()."'>
						<td>Customer Telephone Number</td>
						<td valign='center'><input type='text' size='20' name='telno' value='$inv[telno]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Customer Order number</td>
						<td valign='center'><input type='text' size='10' name='cordno' value='$cordno'></td>
					</tr>
					<tr>
						<th colspan='2'>Point of Sale</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Barcode</td>
						<td>
							<input type='text' size='13' name='bar' value=''>
							<input type='button' onClick='javascript:openRFPopup();' value='RFID Batch'>
						</td>
					</tr>
					<tr>
						<th colspan='2'>Options</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Select Using</td>
						<td>Stock Code<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod> Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des></td>
					</tr>
					<tr><td><br></td></tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Stock Filter</td>
						<td><input type='text' size='13' name='des' value='$des'> <input type='submit' value='Search'> <input type='submit' name='des' value='Show All'></td>
					</tr>
				</table>
			</td>
			<td valign='top' align='right'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Invoice Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Invoice No.</td>
						<td valign='center'>$inv[invid]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sales Order No.</td>
						<td valign='center'><input type='text' size='5' name='ordno' value='$inv[ordno]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Invoice Date</td>
						<td valign='center' nowrap='t'>".mkDateSelect("pinv",$pinv_year,$pinv_month,$pinv_day)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>VAT Inclusive</td>
						<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin> No<input type='radio' size='7' name='chrgvat' value='exc' $chex></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sales Person</td>
						$sales
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Trade Discount</td>
						<td valign='center'><input type='text' size='5' name='traddisc' value='$inv[traddisc]'>%</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Delivery Charge</td>
						<td valign='center'><input type='text' size='7' name='delchrg' value='$inv[delchrg]'>$Vatcodes</td>
					</tr>
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
							<input size='12' type='text' name='pcash' id='pcash' value='$inv[pcash]' onchange='ptot_update();'>
							<input type='button' value='&laquo Total' onclick='paytotal(\"pcash\");' />
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Amount Paid Cheque</td>
						<td nowrap='t'>
							<input size='12' type='text' name='pcheque' id='pcheque' value='$inv[pcheque]' onchange='ptot_update();'>
							<input type='button' value='&laquo Total' onclick='paytotal(\"pcheque\");' />
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Amount Paid Credit Card</td>
						<td nowrap='t'>
							<input size='12' type='text' name='pcc' id='pcc' value='$inv[pcc]' onchange='ptot_update();'>
							<input type='button' value='&laquo Total' onclick='paytotal(\"pcc\");' />
						</td>
					</tr>
					$recvpay
					$pc
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Total Covered</td>
						<td nowrap='t' id='ptot'>".CUR." ".sprint($inv["pcash"] + $inv["pcheque"] + $inv["pcc"] + $inv["pcredit"])."</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr><td><br></td></tr>
		<tr>
			<td colspan='2'>$products</td>
		</tr>
		<tr>
			<td width='70%' valign='top'>
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<td rowspan='2'>"
							.mkQuickLinks(
								ql("pos-invoice-new-no-neg.php", "New POS Invoice"),
								ql("pos-invoice-list.php", "View POS Invoices"),
								ql("customers-new.php", "New Customer")
							)."
						</td>
						<th width='30%'>Comments</th>
						<td rowspan='5' valign='top' width='40%'>$error</td></tr>
					<tr bgcolor='".bgcolorg()."'>
						<td rowspan='4' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$inv[comm]</textarea></td>
					</tr>
				</table>
			</td>
			<td align='right' valign='top' width='30%'>
				<table ".TMPL_tblDflts." width='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>SUBTOTAL</td>
						<td align='right'>".CUR." <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Trade Discount</td>
						<td align='right'>".CUR." $inv[discount]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Delivery Charge</td>
						<td align='right'>".CUR." $inv[delivery]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><b>VAT $vat14</b></td>
						<td align='right'>".CUR." $VAT</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<th>GRAND TOTAL</th>
						<td align='right'>".CUR." $TOTAL</td>
					</tr>
					$rd
				</table>
			</td>
		</tr>
		<tr>
			<td align='right'><input name='diffwhBtn' type='submit' value='Different Store'> | <input name='addprodBtn' type='submit' value='Add Product'>| <input name='addnon' type='submit' value='Add Non stock Product'>$save </td>
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

	#get vars
	extract ($HTTP_POST_VARS);

	if (isset($cusnum) && customer_overdue($cusnum)) {
		return details($HTTP_POST_VARS, "<li class='err'>Customer is overdue, account blocked!</li>");
	}

	$pcredit+=0;

	$pcash+=0;
	$pcheque+=0;
	$pcc+=0;

	$deptid+=0;

	db_conn('cubit');

	if(isset($printsales)) {

		$Sl="SELECT * FROM settings WHERE constant='PSALES'";
		$Ri=db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri)<1) {
			$Sl="INSERT INTO settings (constant,value,div) VALUES ('PSALES','Yes','".USER_DIV."')";
			$Ri=db_exec($Sl);
		} else {
			$Sl="UPDATE settings SET value='Yes' WHERE constant='PSALES' AND div='".USER_DIV."'";
			$Ri=db_exec($Sl);
		}
	} else {
		$Sl="UPDATE settings SET value='No' WHERE constant='PSALES' AND div='".USER_DIV."'";
		$Ri=db_exec($Sl);
	}

	//$it+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(isset($client)) {
		$v->isOk ($client, "string", 0, 20, "Invalid Customer.");
	} else {
		$client="";
	}
	if(isset($vatnum)) {
		$v->isOk ($vatnum, "string", 0, 30, "Invalid VAT Number.");
	} else {
		$vatnum="";
	}

	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($telno, "string", 0, 20, "Invalid Customer Telephone Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid sales order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($pinv_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($pinv_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($pinv_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $pinv_year."-".$pinv_month."-".$pinv_day;
	if(!checkdate($pinv_month, $pinv_day, $pinv_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");
	$odate = $pinv_year."-".$pinv_month."-".$pinv_day;
	if(!checkdate($pinv_month, $pinv_day, $pinv_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}

	# used to generate errors
	$error = "asa@";

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			//$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check is serial no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			if (is_numeric($stkid)) {
				$sql = "SELECT units, stkcod FROM cubit.stock WHERE stkid='$stkid'";
				$stock_rslt = db_exec($sql)
					or errDie("Unable to retrieve stock.");
				list($stock_units, $stock_code) = pg_fetch_array($stock_rslt);

				if ($qtys[$keys] > $stock_units) {
					$v->addError(0, "Not enough stock available for $stock_code");
				}
			}


			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number (2): <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && strlen($sernos[$keys]) <= 0 && strlen($sernos_ss[$keys]) <= 0){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number (1): <b>".($keys+1)."</b>");
			}
		}
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
			if($disc[$keys] > $unitcost[$keys]){
				$v->isOk ($disc[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than the unitcost.");
			}
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
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

	$cusnum+=0;

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

	$des=remval($des);

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($HTTP_POST_VARS, $err);
	}



	if(strlen($client)<1) {$client="Cash Sale";}
	if(strlen($vatnum)<1) {$vatnum="";}
	$HTTP_POST_VARS['client'] = $client;
	$HTTP_POST_VARS['vatnum'] = $vatnum;
	$HTTP_POST_VARS['telno'] = $telno;
	$HTTP_POST_VARS['cordno'] = $cordno;

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
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
	$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);

	while($stkt = pg_fetch_array($stktRslt)){
		# update stock(alloc + qty)
		$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		if (strlen($stkt['serno']) > 0) {
			ext_unresvSer($stkt['serno'], $stkt['stkid']);
		}
	}

	# remove old items
	$sql = "DELETE FROM pinv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				/* set the serial ss field for serials selected from list */
				if ($sernos_ss[$keys] == "*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*") {
					$sernos_ss[$keys] = $sernos[$keys];
				}

				if(isset($remprod) && in_array($keys, $remprod)){
					if ($sernos[$keys] == $sernos_ss[$keys] && $sernos_ss[$keys] != "") {
						$chr = substr($sernos[$keys], strlen($sernos[$keys])-1, 1);

						$tab = "ss$chr";

						/* mark barcoded item as unavailable */
						$sql = "UPDATE ".$tab." SET active='yes' WHERE code = '$sernos[$keys]' AND div = '".USER_DIV."'";
						db_exec($sql);
					}
				} else if (isset($accounts[$keys]) && $accounts[$keys]!=0) {
					$accounts[$keys]+=0;
					# Get selamt from selected stock
					db_conn('core');
					$Sl="SELECT * FROM accounts WHERE accid='$accounts[$keys]'";
					$Ri=db_exec($Sl) or errDie("Unable to get account data.");

					$ad=pg_fetch_array($Ri);

					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));

					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return details($HTTP_POST_VARS, "<li class='err'>Please select the vatcode for all your items.</li>");
					}

					$vd=pg_fetch_array($Ri);

					if($vd['zero']=="Yes") {
						$excluding="y";
					} else {
						$excluding="";
					}

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs=explode("|",$vr);
					$ivat=$vrs[0];
					$iamount=$vrs[1];

					$vatamount += $ivat;

					# Check Tax Excempt
					if($vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$exvat="y";
					} else {
						$exvat="n";
					}

					//$newvat+=vatcalc($amt[$keys],$chrgvat,$exvat,$traddisc);
					$vatcodes[$keys]+=0;
					$accounts[$keys]+=0;
					$descriptions[$keys]=remval($descriptions[$keys]);
					$wtd = $whids[$keys];
					# insert invoice items
					$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost,
								amt, disc, discp, ss, serno, div,vatcode,description,
								account)
							VALUES('$invid', '$whids[$keys]', '$stkids[$keys]',
								'$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]',
								'$disc[$keys]', '$discp[$keys]', '', '','".USER_DIV."',
								'$vatcodes[$keys]','$descriptions[$keys]',
								'$accounts[$keys]')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				} else {
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

					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return details($HTTP_POST_VARS, "<li class=err>Please select the vatcode for all your items.</li>");
					}
					$vd=pg_fetch_array($Ri);

					if($vd['zero']=="Yes") {
						$excluding="y";
					} else {
						$excluding="";
					}

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs=explode("|",$vr);
					$ivat=$vrs[0];
					$iamount=$vrs[1];

					$vatamount += $ivat;

					# Check Tax Excempt
					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$exvat="y";
					} else {
						$exvat="n";
					}

					$wtd = $whids[$keys];
					# insert invoice items
					$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty,
								unitcost, amt, disc, discp, ss, serno, div,vatcode)
							VALUES('$invid', '$whids[$keys]', '$stkids[$keys]',
								'$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]',
								'$disc[$keys]', '$discp[$keys]', '$sernos_ss[$keys]', '$sernos[$keys]',
								'".USER_DIV."','$vatcodes[$keys]')";
					// $sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]','$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

					if (strlen($sernos[$keys]) > 0) {
						ext_resvSer($sernos[$keys], $stk['stkid']);
					}

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
		$Sl="SELECT * FROM vatcodes WHERE id='$delvat'";
		$Ri=db_exec($Sl);

// 		/*if(pg_num_rows($Ri)>0) {
// 			*/$taxex += $delchrg;
// 		}

		$vd = pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$HTTP_POST_VARS['showvat'] = $showvat;

		$vr=vatcalc($delchrg,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
		$vrs=explode("|",$vr);
		$ivat=$vrs[0];
		$iamount=$vrs[1];

		$vatamount += $ivat;

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($chrgvat == "exc"){
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
// 			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = sprint($vatamount);
			$SUBTOT = $sub;
			$TOTAL=sprint($subtotal+$VAT);
			$delexvat=sprint($delchrg);
		}elseif($chrgvat == "inc"){
			$ot=$taxex;
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
// 			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$VAT = sprint($vatamount);
			$SUBTOT=sprint($sub);
			$TOTAL=sprint($subtotal);
			$delexvat=sprint(($delchrg));
			$traddiscmt=sprint($traddiscmt);

		} else {
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(0);
			$SUBTOT=$sub;
			$TOTAL=$subtotal;
			$delexvat=sprint($delchrg);
		}

		$Sl="SELECT * FROM posround";
		$Ri=db_exec($Sl);

		$data=pg_fetch_array($Ri);

		if($data['setting']=="5cent") {
			if(sprint(floor(sprint($TOTAL/0.05)))!=sprint($TOTAL/0.05)) {
				$otot=$TOTAL;
				$nTOTAL=sprint(sprint(floor($TOTAL/0.05))*0.05);
				$rounding=($otot-$nTOTAL);
			} else {
				$rounding=0;
			}
		} else {
			$rounding=0;
		}

		//print sprint(floor($TOTAL/0.05));

		#get accno if invoice is on credit
		if($cusnum != "0"){
			$get_acc = "SELECT * FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
			$run_acc = db_exec($get_acc) or errDie("Unable to get customer information");
			if(pg_numrows($run_acc) < 1){
				$accno = "";
			}else {
				$arr = pg_fetch_array($run_acc);
				$cusacc = $arr['accno'];
			}
		}else {
			$cusacc = "";
		}

	//	die($cusnum);

		# insert invoice to DB
		$sql = "UPDATE pinvoices SET pcredit='$pcredit',cusnum='$cusnum',delvat='$delvat',rounding='$rounding',pcash='$pcash',pcheque='$pcheque',
		pcc='$pcc',deptid='$deptid',deptname = '$dept[deptname]', cusname = '$client', cordno = '$cordno', ordno = '$ordno',chrgvat = '$chrgvat',
		salespn = '$salespn', odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL',
		balance = '$pcredit', comm = '$comm', discount='$traddiscmt', delivery='$delexvat', vatnum='$vatnum', cusacc = '$cusacc', telno='$telno'
		WHERE invid = '$invid' AND div = '".USER_DIV."'";

		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM pinv_data WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# put in new data
		$sql = "INSERT INTO pinv_data(invid, dept, customer, div) VALUES('$invid', '$dept[deptname]', '$client', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

# commit updatin

	if (strlen($bar) > 0) {
		/* check if there a stock item with global barcode matching input barcode */
		$sql = "SELECT * FROM stock WHERE bar='$bar' AND div = '".USER_DIV."'";
		$barRslt = db_exec($sql);

		if (pg_num_rows($barRslt) <= 0) {
			/* fetch last character of barcode */
			$chr = substr($bar, strlen($bar)-1, 1);

			/* invalid barcode */
			if (!is_numeric($chr)) {
				return details($HTTP_POST_VARS,"The code you selected is invalid");
			}

			/* which barcode table to scan for stock id */
			$tab = "ss$chr";
			$stid = barext_dbget($tab, 'code', $bar, 'stock');

			$stab = "serial$chr";
			$sstid = serext_dbget($stab, 'serno', $bar, 'stkid');

			/* non-existing barcode, check for serial number */
			if ($stid <= 0) {
				if ($sstid <= 0) {
					return details($HTTP_POST_VARS,"<li class='err'>The serial number/bar code you selected is not in the system or is not available.</li>");
				}

				if (serext_dbnum($stab, 'serno', $bar, 'stkid') > 1) {
					return details($HTTP_POST_VARS,"<li class='err'>Duplicate serial numbers found, please scan barcode or select stock item.</li>");
				}

				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$stab." SET rsvd='y' WHERE serno='$bar'";
				db_exec($sql);

				$serno_bar = "$bar";

				$stid = $sstid;
			} else {
				if ($sstid > 0) {
					return details($HTTP_POST_VARS,"<li class='err'>A serial and barcode with same value, please scan other value or select product manually.</li>");
				}

				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$tab." SET active='no' WHERE code='$bar' AND div='".USER_DIV."'";
				db_exec($sql);

				$serno_bar = "$bar";
			}

			/* fetch stock row for selected item */
			$sql = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$barRslt = db_exec($sql);
		} else {
			$serno_bar = "";
		}

		$s = pg_fetch_array($barRslt);

		/* allocate stock item */
		$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$s[stkid]' AND div = '".USER_DIV."'";
		db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt,
					disc, discp, ss, serno, div)
				VALUES('$invid', '$s[whid]', '$s[stkid]', '1','$s[selamt]',
					'$s[selamt]', '0', '0','$bar', '$serno_bar', '".USER_DIV."')";
		db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
	}

pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


/* --- Start button Listeners --- */
	if(isset($doneBtn)){
		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM pinv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class='err'> Error : Invoice number has no items.";
			return details($HTTP_POST_VARS, $error);
		}

		$TOTAL=sprint($TOTAL-$rounding);

		#check for credit limit
		if($cusnum != "0"){
			#customer is selected ... get info
			$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND location != 'int' AND div = '".USER_DIV."'";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
			if(pg_numrows($custRslt) < 1){
				$cust['balance'] = "0";
				$cust['creditlimit'] = "0";
			}else {
				$cust = pg_fetch_array ($custRslt);
			}
			#customer is set check for response
			if(($pcredit + $cust['balance']) > $cust['credlimit']){
				#limit reached ... check for block
				db_conn("cubit");
				$get_check = "SELECT value FROM set WHERE label = 'CUST_INV_WARN' LIMIT 1";
				$run_check = db_exec($get_check) or errDie("Unable to get credit limit response setting");
				if(pg_numrows($run_check) < 1){
					#no setting ? do nothing ....
				}else {
					$sarr = pg_fetch_array($run_check);
					if($sarr['value'] == "block"){
						#block account ...
						return details($HTTP_POST_VARS, "<li class='err'>Warning : Customers Credit limit of <b>".CUR." ".sprint($cust["credlimit"])."</b> has been exceeded.</li>");
					}
				}
				# Check permissions
				if(!perm("invoice-limit-override.php")){
					return details($HTTP_POST_VARS, "<li class='err'>Warning : Customers Credit limit of <b>".CUR." ".sprint($cust["credlimit"])."</b> has been exceeded.</li>");
				}
			}
		}


		if(($pcash+$pcheque+$pcc+$pcredit)<$TOTAL) {
			return details($HTTP_POST_VARS, "<li class='err'>The total of all the payments is less than the invoice total</li>");
		}

		$change=sprint(sprint($pcash+$pcheque+$pcc+$pcredit)-sprint($TOTAL));

		$pcash=sprint($pcash-$change);

		if($pcash<0) {
			$pcash=0;
		}

		if(sprint($pcash+$pcheque+$pcc+$pcredit)!=sprint($TOTAL)) {

			return details($HTTP_POST_VARS, "<li class='err'>The total of all the payments is not equal to the invoice total.<br>
			(You can only overpay with cash)</li>");

		}


		# insert quote to DB
		$sql = "UPDATE pinvoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);
		# print the invoice
		$OUTPUT = "
					<script>printer2('pos-invoice-print.php?invid=$invid');</script>
					<input type='button' value='Create New POS Invoice' onClick=\"move('pos-invoice-new-no-neg.php');\">";
		require("template.php");


	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>New Point of Sale Invoice Saved</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice for <b>$client</b> has been saved.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-new-no-neg.php'>New Point of Sale Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $write;
	}elseif(isset($cancel)){

		// Final Laytout
		$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>New Point of Sale Invoice Saved</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice for <b>$client</b> has been saved.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-new-no-neg.php'>New Point of Sale Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $write;
	}else{
	if(isset($wtd)){$HTTP_POST_VARS['wtd']=$wtd;}
		return details($HTTP_POST_VARS);
	}
/* --- End button Listeners --- */
}

function recvpayment() {
	extract($_POST);

	$v = new validate();
	$v->isOk($cusnum, "num", 1, 10, "Invalid customer id.");
	$v->isOk($invid, "num", 1, 10, "Invalid invoice id.");
	$v->isOk($pcc, "float", 1, 40, "Invalid credit card amount.");
	$v->isOk($pcash, "float", 1, 40, "Invalid cash amount.");
	$v->isOk($pcheque, "float", 1, 40, "Invalid cheque amount.");

	$date = mkdate($pinv_year, $pinv_month, $pinv_day);
	$v->isOk($date, "date", 1, 1, "Invalid invoice date.");

	if ($v->isError()) {
		return details($_POST, $v->genErrors());
	}

	$amt = sprint($pcc + $pcash + $pcheque);

	$cus = qryCustomer($cusnum);
	$bank_acc = qryAccountsName("Cash on Hand");

	$OUT = "
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan='2'>Payment Details</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account</td>
		<td>$bank_acc[topacc]/$bank_acc[accnum] $bank_acc[accname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Date</td>
		<td valign='center'>$date</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Received from</td>
		<td valign='center'>$cus[cusname] $cus[surname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Description</td>
		<td valign='center'>POS Payment Received</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Amount</td>
		<td valign='center'>".CUR." $amt</td>
	</tr>
	</table>

	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='recvpayment_write' />
	<input type='hidden' name='bank_acc' value='$bank_acc[accid]' />
	<input type='hidden' name='invid' value='$invid' />
	<input type='hidden' name='pcc' value='$pcc' />
	<input type='hidden' name='pcash' value='$pcash' />
	<input type='hidden' name='pcheque' value='$pcheque' />
	<input type='hidden' name='amt' value='$amt' />
	<input type='hidden' name='date' value='$date' />
	<input type='hidden' name='cusnum' value='$cusnum' />
	<input type='hidden' name='descript' value='POS Payment Received' />

	<input type='hidden' name='stkerr' value='$stkerr' />
	<input type='hidden' name='prev_cusnum' value='$prev_cusnum' />
	<input type='hidden' name='deptid' value='$deptid' />
	<input type='hidden' name='telno' value='$telno' />
	<input type='hidden' name='cordno' value='$cordno' />
	<input type='hidden' name='bar' value='$bar' />
	<input type='hidden' name='des' value='$des' />
	<input type='hidden' name='sel_frm' value='$sel_frm' />
	<input type='hidden' name='ordno' value='$ordno' />
	<input type='hidden' name='pinv_day' value='$pinv_day' />
	<input type='hidden' name='pinv_month' value='$pinv_month' />
	<input type='hidden' name='pinv_year' value='$pinv_year' />
	<input type='hidden' name='chrgvat' value='$chrgvat' />
	<input type='hidden' name='salespn' value='$salespn' />
	<input type='hidden' name='traddisc' value='$traddisc' />
	<input type='hidden' name='delchrg' value='$delchrg' />
	<input type='hidden' name='delvat' value='$delvat' />
	<input type='hidden' name='user' value='$user' />
	<input type='hidden' name='SUBTOT' value='$subtot' />
	<input type='hidden' name='comm' value='$comm' />

	<table ".TMPL_tblDflts.">";

	// Connect to database
	db_connect();
	$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices
			WHERE cusnum = '$cusnum' AND printed = 'y' AND balance>0
				AND div = '".USER_DIV."'
			ORDER BY odate ASC";
	$prnInvRslt = db_exec($sql);

	$i = 0;
	while (($inv = pg_fetch_array($prnInvRslt)) && ($amt > 0)) {
		if ($i == 0) {
			$OUT .= "
			<tr>
				<td colspan='2'>&nbsp;</td>
			</tr>
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

		$invid = $inv['invid'];

		$val = allocamt($amt, $inv["balance"]);

		$OUT .= "
		<input type='hidden' name='paidamt[$invid]' size=10 value='$val'>
		<input type='hidden' size=20 name=invids[$invid] value='$inv[invid]'>
		<tr bgcolor='".bgcolor($i)."'>
			<td>$inv[invnum]</td>
			<td>".CUR." $inv[balance]</td>
			<td>$inv[terms] days</td>
			<td>$inv[odate]</td>
			<td>".CUR." $val</td>
		</tr>";
	}

	$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices
			WHERE cusid='$cusnum' AND done='y' AND balance>0
				AND div='".USER_DIV."'
			ORDER BY odate ASC";
	$prnInvRslt = db_exec($sql);

	while(($inv = pg_fetch_array($prnInvRslt)) && ($amt > 0)) {
		if ($i == 0) {
			$OUT .= "
			<tr>
				<td colspan='2'>&nbsp;</td>
			</tr>
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

		$invid = $inv['invid'];

		$val = allocamt($amt, $inv["balance"]);

		$OUT .= "
				<input type='hidden' name='paidamt[$invid]' value='$val'>
				<input type='hidden' name='itype[$invid]' value='Yes'>
				<tr bgcolor='".bgcolor($i)."'>
					<td><input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>$inv[invnum]</td>
					<td>".CUR." $inv[balance]</td>
					<td></td>
					<td>$inv[odate]</td>
					<td>".CUR." $val</td>
				</tr>";
	}

	$amt = sprint($amt);

	/* pos invoices */
	$sqls = array();
	for ($i = 1; $i <=12; ++$i) {
		$sqls[] = "SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices
					WHERE cusnum='$cusnum' AND done='y' AND balance>0
						AND div='".USER_DIV."'";
	}
	$sql = implode(" UNION ", $sqls);

	$prnInvRslt = db_exec($sql);

	if(pg_numrows($prnInvRslt) > 0) {
		$OUT .= "
		<tr>
			<td colspan='2'><br></td>
		</tr>
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

		$i = 0;
		while($inv = pg_fetch_array($prnInvRslt)){
			$invid = $inv['invid'];

			$val = allocamt($amt, $inv["balance"]);

			$OUT .= "
			<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
			<input type='hidden' name='paidamt[$invid]' size=10 value='$val'>
			<input type='hidden' name='ptype[$invid]' value='YnYn'>
			<tr bgcolor='".bgcolor($i)."'>
				<td>$inv[invnum]</td>
				<td>".CUR." $inv[balance]</td>
				<td></td>
				<td>$inv[odate]</td>
				<td>".CUR." $val</td>
			</tr>";
		}
	}

	if ($amt > 0) {
		/* START OPEN ITEMS */
		$ox="";

		$sql = "SELECT * FROM cubit.open_stmnt WHERE balance>0 AND cusnum='$cusnum'
					AND type!='Invoice' AND type!='Non-Stock Invoice'
					AND type!='Interest on Outstanding balance'
				ORDER BY date";
		$rslt = db_exec($sql) or errDie("Unable to get open items.");

		$open_out=$amt;

		$i=0;

		while ($od = pg_fetch_array($rslt)) {
			if($open_out==0) {
				continue;
			}
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$oid=$od['id'];
			if($open_out>=$od['balance']) {
				$open_amount[$oid]=$od['balance'];
				$open_out=sprint($open_out-$od['balance']);
				$ox .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' size='20' name='open[$oid]' value='$oid'>$od[type]</td>
					<td>".CUR." $od[balance]</td>
					<td>$od[date]</td>
					<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
				</tr>";
			} elseif($open_out<$od['balance']) {
				$open_amount[$oid]=$open_out;
				$open_out=0;
				$ox .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' size='20' name='open[$oid]' value='$od[id]'>$od[type]</td>
					<td>".CUR." $od[balance]</td>
					<td>$od[date]</td>
					<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
				</tr>";
			}
			$i++;
		}

		if (open()) {
			$OUT .= "
			".TBL_BR."
			<input type='hidden' name='bout' value='$amt'>
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

			$bout=$amt;
			$amt=$open_out;
			if($amt>0) {
				$OUT .="
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='4'><b>A general transaction will credit the client's account with ".CUR." $amt</b></td>
				</tr>";
			}

			//$amt=$bout;
		} else {
			$OUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'><b>A general transaction will credit the client's account with ".CUR." $amt </b></td>
			</tr>";
		}
	}

	$OUT .= "
	<input type='hidden' name='out' value='$amt' />
	<tr>
		<td colspan='5' align='right'>
			<input type='submit' name='btn_back' value='&laquo; Correction' />
			<input type='submit' value='Record Payment' />
		</td>
	</table>";

	return $OUT;
}

function recvpayment_write() {
	if (isset($_POST["btn_back"])) {
		return details($_POST);
	}

	extract($_POST);

	$v = new validate();
	$v->isOk($cusnum, "num", 1, 10, "Invalid customer id.");
	$v->isOk($bank_acc, "num", 1, 10, "Invalid cash account selected.");
	$v->isOk($pcc, "float", 1, 40, "Invalid credit card amount.");
	$v->isOk($pcash, "float", 1, 40, "Invalid cash amount.");
	$v->isOk($pcheque, "float", 1, 40, "Invalid cheque amount.");
	$v->isOk($amt, "float", 1, 40, "Invalid total received amount.");
	$v->isOk($out, "float", 1, 40, "Invalid unallocated amount.");
	$v->isOk($descript, "string", 1, 255, "Invalid description.");
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

	if (isset($invids)) {
		foreach($invids as $key => $value) {
			$ii = $invids[$key];
			/* OPTION 1: STOCK INVOICES */
			if (!isset($itype[$ii]) && !isset($ptype[$ii])) {
				$sql = "SELECT prd,invnum,odate FROM cubit.invoices
						WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class=err>Invalid Invoice Number.";
				}
				$inv = pg_fetch_array($invRslt);

				$inv['invnum'] += 0;

				// reduce invoice balance
				$sql = "UPDATE cubit.invoices
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
						('$cus[cusnum]','$inv[invnum]', '".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");

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
				$sql = "SELECT prd,invnum,descrip,age,odate FROM cubit.nons_invoices
						WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

				if (pg_numrows ($invRslt) < 1) {
					return "<li class=err>Invalid Invoice Number.";
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

				recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$sdate);

				$rinvids .= "|$invids[$key]";
				$amounts .= "|$paidamt[$key]";
				$invprds .= "|0";
				$rages .= "|$inv[age]";
				$invidsers .= " - $inv[invnum]";
			} else {
				/* pos invoices */
				$sql = "SELECT * FROM cubit.prd_pinvoices
						WHERE invid='$invids[$key]' AND div='".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie ("Unable to retrieve invoice details from database.");

				if (pg_numrows ($invRslt) < 1) {
					return "<li class='err'>Invalid Invoice Number.</li>";
				}

				$inv = pg_fetch_array($invRslt);

				// reduce the invoice balance
				$sql = "UPDATE \"$inv[iprd]\".pinvoices
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

				recordCT($paidamt[$key], $cus['cusnum'],0,$sdate);

				$rinvids .= "|$invids[$key]";
				$amounts .= "|$paidamt[$key]";
				$invprds .= "|$inv[prd]";
				//$rages .= "|$inv[age]";
				$invidsers .= " - $inv[invnum]";
			}
		}
	}

	writetrans($bank_acc, $dept['debtacc'], $sdate, $refnum, $amt,
		"Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

	db_conn('cubit');
	if ($out > 0) {
		/* START OPEN ITEMS */
		$openstmnt = new dbSelect("open_stmnt", "cubit", grp(
			m("where", "balance>0 AND cusnum='$cusnum'"),
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
			if ($open_out>=$od['balance']) {
				$open_amount[$oid]=$od['balance'];
				$open_out=sprint($open_out-$od['balance']);
				$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
					<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
					".CUR." $open_amount[$oid]</td></tr>";

				$Sl="UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
				$Ri=db_exec($Sl) or errDie("Unable to update statement.");

			} elseif($open_out<$od['balance']) {
				$open_amount[$oid]=$open_out;
				$open_out=0;
				$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$od[id]'>$od[type]</td>
					<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
					".CUR." $open_amount[$oid]</td></tr>";

				$Sl="UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
				$Ri=db_exec($Sl)or errDie("Unable to update statement.");
			}
			$i++;
		}

		if(open()) {
			$bout=$out;
			$out=$open_out;
			if($out>0) {
				$sql = "INSERT INTO cubit.open_stmnt(cusnum, invid, amount, balance, date, type, st, div) VALUES('$cus[cusnum]', '0', '-$out', '-$out', '$sdate', 'Payment Received', 'n', '".USER_DIV."')";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
				//$confirm .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
			}

			$out=$bout;
		} else  {//$confirm .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
		}

	}

	if ($out > 0) {
		recordCT($out, $cus['cusnum'],0,$sdate);

		$cols = grp(
			m("cusnum", $cus["cusnum"]),
			m("invid", 0),
			m("amount", -$out),
			m("date", $sdate),
			m("type", "Payment Received"),
			m("div", USER_DIV),
			m("allocation_date", $sdate)
		);

		$dbobj = new dbUpdate("stmnt", "cubit", $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		custledger($cus['cusnum'], $bank_acc, $sdate, "PAYMENT", "Payment received.", $out, "c");
	}

	$sql = "INSERT INTO cubit.payrec(date,by,multiinv,amount,method,prd,note)
			VALUES('$sdate','".USER_NAME."', '$invidsers', '$pcash','Cash','".PRD_DB."','0')";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.payrec(date,by,multiinv,amount,method,prd,note)
			VALUES('$sdate','".USER_NAME."', '$invidsers', '$pcc','Credit Card','".PRD_DB."','0')";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.payrec(date,by,multiinv,amount,method,prd,note)
			VALUES('$sdate','".USER_NAME."', '$invidsers', '$pcheque','Cheque','".PRD_DB."','0')";
	db_exec($sql) or errDie("Unable to insert data.");

	pglib_transaction("COMMIT");

	$_POST["pcc"] = $_POST["pcheque"] = $_POST["pcash"] = "0.00";

	return details($_POST, "<li class='err'>Payment received successfully</li>");
}

function recordCT($amount, $cusnum, $age, $date="", $changemon = false) {
	db_connect();
	if ($date=="") {
		$date = date("Y-m-d");
	}

	$amount = ($amount * (-1));
	$date_ins = "'$date'";

	$sql = "INSERT INTO custran(cusnum, odate, balance, div, age)
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

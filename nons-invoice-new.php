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
require_lib("customers");

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["done"] = "";
	$OUTPUT = details($_GET);
} else {
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
		case "details":
			$OUTPUT = details($_POST);
			break;
		case "update":
			$OUTPUT = write($_POST);
			break;
		case "slct":
			$_POST["done"] = "";
			$OUTPUT = details($_POST);
			break;
		default:
			$OUTPUT = slct();
		}
	} else {
		$OUTPUT = slct();
	}
}

# get templete
require("template.php");




# Details
function slct($err = "")
{

	global $_POST;

	extract($_POST);

	if(isset($letters)) {
		$letters = remval($letters);
		$whe = "AND lower(surname) LIKE lower('%$letters%')";
	} else {
		$letters = "";
		$whe = "";
	}

	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' AND location != 'int' $whe ORDER BY lower(surname) ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);
	$custs = "<select name='sval'>";
	if(pg_numrows($cusRslt) < 1) $custs .= "<option value='-S'></option>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[surname]</option>";
	}
	$custs .= "</select>";



	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$Rs = db_exec($sql);

	$numrows = pg_numrows($Rs);
	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name='bankid'>";
	while($acc = pg_fetch_array($Rs)){
		$banks .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name='cval'>";
	if(pg_numrows($deptRslt) < 1) $depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value=$dept[deptid]>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	//<tr class='bg-odd' ".ass("Select when the sale of non stock goods is a bank sale")."><td><input type=radio name=ctyp value='cb'>Bank Sale</td><td>$banks</td></tr>


	$details = "
		<center>
		<h3>New Non-Stock Invoice</h3>
		<h4>Customer Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slct'>
			<input type='hidden' name='starting' value=''>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'> Invoice Details </th>
			</tr>
			<tr class='".bg_class()."' ".ass("Select when selling non stock goods to your customers").">
				<td><input type='radio' name='ctyp' value='s' checked='yes'> Select Customer</td>
				<td>$custs</td>
			</tr>
			<tr class='".bg_class()."' ".ass("Select when the sale of non stock goods is a cash sale").">
				<td><input type='radio' name='ctyp' value='c'>Cash Sale</td>
				<td>$depts</td>
			</tr>
			<tr class='".bg_class()."' ".ass("Select when the sale of non stock goods is not a cash sale").">
				<td><input type='radio' name='ctyp' value='ac'>Ledger Accounts Sale</td>
				<td class='err'>This selection will debit the amount of the invoice<br /> to a General Ledger account
					instead of Debtors Control.</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Search by surname</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' size='10' name='letters' value='$letters'></td>
				<td><input type='submit' value='Search &raquo;'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'></td>
				<td align='center'><input type='submit' name='button' value='Continue &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $details;

}



# Starting dummy
function create_dummy($deptid, $ctyp, $tval,$acc)
{

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

	db_connect();

	# Insert purchase to DB
	$sql = "
		INSERT INTO nons_invoices (
			cusname, cusaddr, cusvatno, chrgvat, sdate, odate, subtot, balance, vat, total, done, username, prd, invnum, 
			typ, ctyp, tval, div, accid
		) VALUES (
			'', '', '', 'yes', CURRENT_DATE, '$odate', 0, 0, 0, 0, 'n', '".USER_NAME."', '".PRD_DB."', 0, 
			'inv', '$ctyp', '$tval', '".USER_DIV."', '$acc'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);
	return lastinvid();

}



# details
function details($_POST, $error="")
{

	# Get vars
	extract ($_POST);

	if(!isset($button)&&(isset($starting))) {
		return slct();
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if( isset($invid) ){
		$v->isOk ($invid, "num", 1, 20, "Invalid Non-Stock Invoice number.");
	}elseif(isset($ctyp)){
		$val = $ctyp."val";
		if(isset($$val)) {
			$tval = $$val;
			$v->isOk ($tval, "num", 1, 20, "Invalid Selection.");
		}
	}

	if (isset($cusnum) && customer_overdue($cusnum)) {
		$v->addError(0, "Customer is overdue, account blocked!");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slct($error);
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	if(!isset($invid) && isset($ctyp)) {
		$val = $ctyp."val";
		if(!isset($$val)) {
			$$val = "";
		}
		$tval = $$val;

		if(isset($bankid)) {
			$bankid += 0;
			$acc = $bankid;
		} else {
			$acc = 0;
		}
		$invid = create_dummy(0, $ctyp, $tval,$acc);
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);



	# check if invoice has been printed
	if($inv['done'] == "y"){
		$error = "<li class='err'> Error : invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

/* --- Start Drop Downs --- */

	# format date
	list($ninv_year, $ninv_month, $ninv_day) = explode("-", $inv['odate']);

	# keep the charge vat option stable
	if($inv['chrgvat'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
		$chnone = "";
	}elseif ($inv['chrgvat'] == "no"){
		$chy = "";
		$chn = "checked=yes";
		$chnone = "";
	} else {
		$chy = "";
		$chn = "";
		$chnone = "checked=yes";
	}

	# Days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>AMOUNT</th>
				<th>VAT Code</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while ($stkd = pg_fetch_array($stkdRslt)) {
		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];

		$stkd['amt'] = round($stkd['amt'], 2);

		$chk = "";
		if($stkd['vatex'] == 'y')
			$chk = "checked=yes";

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl);

		$vats = "<select name='vatcodes[]'>";
		while($vd = pg_fetch_array($Ri)) {
			if($stkd['vatex'] == $vd['id']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$vats .= "<option value='$vd[id]' $sel>$vd[code]</option>";
		}
		$vats .= "</option>";

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='50' name='des[]' value='$stkd[description]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td><input type='hidden' name='amt[]' value='".sprint($stkd["amt"])."'> ".CUR." ".sprint($stkd["amt"])."</td>
				<!--<td align='center'><input type='checkbox' name='vatex[]' value='$i' $chk></td>-->
				<td align='center'>$vats</td>
				<td align='center'><input type='checkbox' name='remprod[]' value='$i'><input type='hidden' name='SCROLL' value='yes'></td>
			</tr>";
		$i++;
	}

	# Look above(remprod keys)
	$keyy = $i;

	# look above(if i = 0 then there are no products)
	if( $i == 0 ) {
		$done = "";
	}

	if ( $i == 0 || isset($diffwhBtn) ) {
		# add one
		$products .= "
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='50' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]'></td>
				<td>".CUR." 0.00</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $inv['subtot'];

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

/* --- End Some calculations --- */

	db_conn('cubit');

	if($inv['ctyp'] == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$inv[tval]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cust = pg_fetch_array($custRslt);

		if (!empty($cust["cusname"] )) {
			$cn = "$cust[cusname] $cust[surname]";
		} else {
			$cn = "$cust[surname]";
		}
	
		$details = "
			<tr>
				<th colspan='2'> Customer Details </th>
			</tr>
			<input type='hidden' name='cusnum' value='$cust[cusnum]' />
			<input type='hidden' name='cusname' value='$cn'>
			<input type='hidden' name='cusaddr' value='$cust[addr1]'>
			<input type='hidden' name='cusvatno' value='$cust[vatnum]'>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td valign='center'>$cust[cusname] $cust[surname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer Address</td>
				<td valign='center'><pre>$cust[addr1]</pre></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer VAT Number</td>
				<td valign='center'>$cust[vatnum]</td>
			</tr>";
	}elseif($inv['ctyp'] == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$inv[tval]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
			<tr>
				<th colspan='2'> Customer Details </th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td valign='middle'><input type='text' name='cusname' value='$inv[cusname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer Address</td>
				<td valign='middle'><textarea name='cusaddr' cols='18' rows='3'>$inv[cusaddr]</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer VAT No.</td>
				<td valign='middle'><input type='text' name='cusvatno' value='$inv[cusvatno]'></td>
			</tr>";
	}else{
		$details = "
			<tr>
				<th colspan='2'> Customer Details </th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td valign='middle'><input type='text' name='cusname' value='$inv[cusname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer Address</td>
				<td valign='middle'><textarea name='cusaddr' cols='18' rows='3'>$inv[cusaddr]</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer VAT No.</td>
				<td valign='middle'><input type='text' name='cusvatno' value='$inv[cusvatno]'></td>
			</tr>";
	}

	# Get sales people
	db_conn("exten");

	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class='err'> There are no Sales People found in Cubit.</li>";
	}else{

		if (isset($cust) AND is_array ($cust) AND strlen ($inv['salespn']) < 1){
			#if salespn == NULL search for customer default ...
			$get_salesp = "SELECT salesp FROM salespeople WHERE salespid = '$cust[sales_rep]' LIMIT 1";
			$run_salesp = db_exec ($get_salesp) or errDie ("Unable to get sales perosn information.");
			$inv['salespn'] = pg_fetch_result ($run_salesp,0,0);
		}

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

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sc = "checked";
	} else {
		$sc = "";
	}

	$sales = "
		<td>
			<table ".TMPL_tblDflts.">
				<tr>
					<td>$salesps</td>
					<td>Print</td>
					<td><input type='checkbox' name='printsales' $sc></td>
				</tr>
			</table>
		</td>";

	// Retrieve the default comments
	db_conn("cubit");

	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
	$cmntRslt = db_exec($sql) or errDie("Unable to retrieve default comment from Cubit.");
	if (empty($inv["remarks"])) {
		$remarks = base64_decode(pg_fetch_result($cmntRslt, 0));
	} else {
		$remarks = $inv["remarks"];
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$deldate = explode("-", $inv["deldate"]);

	if (isset ($diffwhBtn) OR isset ($upBtn) OR isset ($doneBtn) OR isset ($print)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>New Non-Stock Invoices</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						$details
						<tr class='".bg_class()."'>
							<td>Customer Order number</td>
							<td valign='center'><input type='text' size='10' name='cordno' value='$inv[cordno]'></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Non-Stock Invoice Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>TI $inv[invid]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Proforma Invoice No.</td>
							<td><input type='text' name='docref' value='$inv[docref]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("ninv",$ninv_year,$ninv_month,$ninv_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='yes' $chy> No<input type=radio size=7 name=chrgvat value='no' $chn></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sales Person</td>
							$sales
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
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td class='".bg_class()."'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$remarks</textarea></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
					<table ".TMPL_tblDflts." width='80%'>
						<tr class='".bg_class()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr class='".bg_class()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input name='diffwhBtn' type='submit' value='Add Item'> |</td>
				<td><input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";
	return $details;

}



# details
function write($_POST)
{

	# get vars
	extract ($_POST);

	// prevent from updating
	if (isset($cusnum) && customer_overdue($cusnum)) {
		return details($_POST);
	}

	db_conn('cubit');

	if(isset($printsales)) {

		$Sl = "SELECT * FROM settings WHERE constant='SALES'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri)<1) {
			$Sl = "INSERT INTO settings (constant,value,div) VALUES ('SALES','Yes','".USER_DIV."')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE settings SET value='Yes' WHERE constant='SALES' AND div='".USER_DIV."'";
			$Ri = db_exec($Sl);
		}
	} else {
		$Sl = "UPDATE settings SET value='No' WHERE constant='SALES' AND div='".USER_DIV."'";
		$Ri = db_exec($Sl);
	}


	# validate input
	require_lib("validate");

	$v = new  validate ();
	if (empty($ninv_year)) {
		list($ninv_year, $ninv_month, $ninv_day) = date("Y-m-d");
	}
	$odate = mkdate($ninv_year, $ninv_month, $ninv_day);
	$v->isOk($odate, "date", 1, 1, "Invalid Date.");

	# used to generate errors
	$error = "asa@";

        // check the invoice details
	$v->isOK($cusname, "string", 1, 100, "Invalid customer name");
	$v->isOK($cusaddr, "string", 0, 400, "Invalid customer address");
	$v->isOK($cusvatno, "string", 0, 50, "Invalid customer vat number");
	$v->isOK($docref, "string", 0, 20, "Invalid Document Reference No.");
	$v->isOK($cordno, "string", 0, 20, "Invalid Customer Order Number.");

	if ( $chrgvat != "yes" && $chrgvat != "no" && $chrgvat!="none")
		$v->addError($chrgvat, "Invalid vat option");

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($des[$keys], "string", 1, 255, "Invalid Description.");
			if($qty <= 0){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity can't be zero or less. Product number: <b>".($keys+1)."</b>");
			}
		}
	}

	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 16, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$_POST['done'] = "";
		return details($_POST, $err);
	}



	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- invoices Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	$inv['chrgvat'] = $chrgvat;

	# check if purchase has been printed
	if($inv['done'] == "y"){
		$error = "<li class='err'> Error : invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$vatamount = 0;
	$showvat = TRUE;

	# insert purchase to DB
	db_conn("cubit");

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		# remove old items
		$sql = "DELETE FROM nons_inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);

		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)){
					if(in_array($keys, $remprod)){
						# skip product (wonder if $keys still align)
						$amt[$keys] = 0;
						continue;
					} else {
						# Calculate amount
						$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

						if(!isset($vatcodes[$keys])) {
							$vatcodes[$keys] = 0;
						}

						$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
						$Ri = db_exec($Sl);

// 						if(pg_num_rows($Ri)<1) {
// 							return "Please select the vatcode for all your stock.";
// 						}

						$vd = pg_fetch_array($Ri);

						if($vd['zero'] == "Yes") {
							$excluding = "y";
						} else {
							$excluding = "";
						}

						if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
							$showvat = FALSE;
						}

						$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,0,$vd['vat_amount']);
						$vrs = explode("|",$vr);
						$ivat = $vrs[0];
						$iamount = $vrs[1];

						$vatamount += $ivat;

						$vate = 'n';
						if((isset($vatex) && in_array($keys, $vatex))||$vd['zero']=="Yes"){
							$taxex += $amt[$keys];
							$vate = 'y';
						}

						$vate = $vatcodes[$keys];

						# insert purchase items
						$sql = "
							INSERT INTO nons_inv_items (
								invid, qty, amt, unitcost, description, vatex, 
								div
							) VALUES (
								'$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', '$vate', 
								'".USER_DIV."'
							)";
						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
					}
				} else {
					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

					if(!isset($vatcodes[$keys])) {
						$vatcodes[$keys] = 0;
					}

					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

// 					if(pg_num_rows($Ri)<1) {
// 						return "Please select the vatcode for all your stock.";
// 					}

					$vd = pg_fetch_array($Ri);

					if($vd['zero'] == "Yes") {
						$excluding = "y";
					} else {
						$excluding = "";
					}

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,0,$vd['vat_amount']);
					$vrs = explode("|",$vr);
					$ivat = $vrs[0];
					$iamount = $vrs[1];

					$vatamount += $ivat;

					$vate = 'n';
					if((isset($vatex) && in_array($keys, $vatex))||$vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$vate = 'y';
					}

					$vate = $vatcodes[$keys];

					$qtys[$keys] = sprint3($qtys[$keys]);
					$amt[$keys] = sprint ($amt[$keys]);
					$unitcost[$keys] = sprint ($unitcost[$keys]);

					# insert purchase items
					$sql = "
						INSERT INTO nons_inv_items (
							invid, qty, amt, unitcost, description, vatex, 
							div
						) VALUES (
							'$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', '$vate', 
							'".USER_DIV."'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = " | <input name='doneBtn' type='submit' value='Done'> | <input name='print' type='submit' value='Process'>";
			}
		}else{
			$_POST["done"] = "";
		}


		$_POST['showvat'] = $showvat;

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($chrgvat == "no"){
			$subtotal = sprint($sub);
			$subtotal = sprint($subtotal);
// 			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = $vatamount;
			$SUBTOT = $sub;
			$TOTAL = sprint($subtotal+$VAT);

		}elseif($chrgvat == "yes"){
			$subtotal = sprint($sub);
			$subtotal = sprint($subtotal);
// 			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$VAT = $vatamount;
			$SUBTOT = sprint($sub);
			$TOTAL = sprint($subtotal);

		} else {
			$subtotal = sprint($sub);
			$traddiscmt = sprint($subtotal);
			$subtotal = sprint($subtotal);
			$VAT = sprint(0);
			$SUBTOT = $sub;
			$TOTAL = $subtotal;
		}

		/* --- ----------- Clac --------------------- */
		##----------------------END----------------------

		/* --- Clac ---
		# calculate subtot
		if( isset($amt) ){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}

		$SUBTOT -= $taxex;

		$VATP = TAX_VAT;
		if($chrgvat == "no"){
			$SUBTOT = $SUBTOT;
		}elseif($chrgvat == "yes"){
			$SUBTOT = sprint(($SUBTOT * 100)/(100 + $VATP));
		}else{
			$SUBTOT = ($SUBTOT);
		}

		if($chrgvat != "none"){
			$VAT = sprint($SUBTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($SUBTOT + $VAT + $taxex);
		$SUBTOT += $taxex;

		/* --- End Clac --- */

		$salespn = remval($salespn);

		if (!isset($bankid)){
			if (isset($cusnum) AND strlen($cusnum) > 0){
				#get bankid from customer info
				$get_cbank = "SELECT bankid FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
				$run_cbank = db_exec($get_cbank) or errDie ("Unable to get bank information for customer.");
				if (pg_numrows($run_cbank) > 0)
					$bankid = pg_fetch_result($run_cbank,0,0);
				else 
					$bankid = "2";
			}else {
				$bankid = "2";
			}
		}

		# insert purchase to DB
		$sql = "
			UPDATE nons_invoices 
			SET salespn = '$salespn', cusname = '$cusname', cusaddr = '$cusaddr', cusvatno = '$cusvatno', 
				cordno = '$cordno', docref = '$docref', chrgvat = '$chrgvat', odate = '$odate', terms = '$terms', 
				subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL',remarks = '$remarks', bankid = '$bankid' 
			WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(isset($print)) {
		$OUTPUT = "<script>printer('nons-invoice-print.php?invid=$invid');move('nons-invoice-new.php');</script>";
		require("template.php");
	}


	if( !isset($doneBtn) ){
		return details($_POST);
	} else {
		//$rslt = db_exec($sql) or errDie("Unable to update invoices status in Cubit.$sql",SELF);

		# Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Non-Stock Invoices</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Non-Stock Invoices for Customer <b>$cusname</b> has been recorded.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}

}


?>
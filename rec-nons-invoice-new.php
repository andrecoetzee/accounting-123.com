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
if (isset($HTTP_GET_VARS["invid"]) && isset($HTTP_GET_VARS["cont"])) {
	$HTTP_GET_VARS["done"] = "";
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
		case "slct":
			$HTTP_POST_VARS["done"] = "";
			$OUTPUT = details($HTTP_POST_VARS);
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

	global $HTTP_POST_VARS;

	extract($HTTP_POST_VARS);

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
			$banks .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name='cval'>";
	if(pg_numrows($deptRslt) < 1) $depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	//<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select when the sale of non stock goods is a bank sale")."><td><input type=radio name=ctyp value='cb'>Bank Sale</td><td>$banks</td></tr>


	$details = "
		<center>
		<h3>New Recurring Non-Stock Invoice</h3>
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
			<tr bgcolor='".bgcolorg()."' ".ass("Select when selling non stock goods to your customers").">
				<td><input type='radio' name='ctyp' value='s' checked='yes'> Select Customer</td>
				<td>$custs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when the sale of non stock goods is a cash sale").">
				<td><input type='radio' name='ctyp' value='c'>Cash Sale</td>
				<td>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when the sale of non stock goods is not a cash sale").">
				<td><input type='radio' name='ctyp' value='ac'>Ledger Accounts Sale</td>
				<td></td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Search by surname</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' size='10' name='letters' value='$letters'></td>
				<td><input type='submit' value='Search &raquo;'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='center'></td>
				<td align='center'><input type='submit' name='button' value='Continue &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $details;

}



# Starting dummy
function create_dummy($deptid, $ctyp, $tval, $acc, $remarks)
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
		INSERT INTO rnons_invoices (
			cusname, cusaddr, cusvatno, chrgvat, sdate, subtot, balance, vat, total, 
			done, username, prd, invnum, typ, ctyp, tval, div, accid, remarks, odate
		) VALUES (
			'', '', '', 'yes', CURRENT_DATE, 0, 0, 0, 0, 
			'n', '".USER_NAME."', '".PRD_DB."', 0, 'inv', '$ctyp', '$tval', '".USER_DIV."', '$acc', '$remarks', '$odate'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	# Get next ordnum
	$purid = pglib_lastid ("rnons_invoices", "invid");
	return $purid;

}




# details
function details($HTTP_POST_VARS, $error="")
{

	# Get vars
	extract ($HTTP_POST_VARS);

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

		// Retrieve default comments
		db_conn("cubit");

		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
		$comment = base64_decode(pg_fetch_result($commRslt, 0));

		$invid = create_dummy(0, $ctyp, $tval, $acc, $comment);
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM rnons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);



/* --- Start Drop Downs --- */

	# format date
	list($rinv_year, $rinv_month, $rinv_day) = explode("-", $inv['sdate']);

	# keep the charge vat option stable
	if($inv['chrgvat'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
		$chnone="";
	}elseif ($inv['chrgvat'] == "no"){
		$chy = "";
		$chn = "checked=yes";
		$chnone="";
	} else {
		$chy = "";
		$chn = "";
		$chnone="checked=yes";
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
				<th>Account</th>
				<th>VAT Code</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM rnons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$vats .= "</select>";

		db_conn('core');

		$Sl = "SELECT accid,accname FROM accounts WHERE div='".USER_DIV."' ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "<select name='accounts[]'>";

		while($ad = pg_fetch_array($Ri)) {
			if(isb($ad['accid'])) {
				continue;
			}
			if($ad['accid'] == $stkd['account']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
		}
		$accounts .= "</select>";

		db_conn('cubit');

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='50' name='des[]' value='$stkd[description]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." ".sprint($stkd["amt"])."</td>
				<td>$accounts</td>
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

		db_conn('core');

		$Sl = "SELECT accid,accname FROM accounts WHERE div='".USER_DIV."' ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "<select name='accounts[]'>";
		while($ad = pg_fetch_array($Ri)) {
			if(isb($ad['accid'])) {
				continue;
			}
			$accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
		}
		$accounts.="</select>";


		# add one
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='50' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]'></td>
				<td>".CUR." 0.00</td>
				<td>$accounts</td>
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

		$details = "
			<tr>
				<th colspan='2'> Customer Details </th>
			</tr>
			<input type='hidden' name='cusname' value='$cust[surname]'>
			<input type='hidden' name='cusaddr' value='$cust[addr1]'>
			<input type='hidden' name='cusvatno' value='$cust[vatnum]'>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td valign='center'>$cust[surname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer Address</td>
				<td valign='center'><pre>$cust[addr1]</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer Vat Number</td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td valign='center'><input type='text' name='cusname' value='$inv[cusname]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Customer Address</td>
				<td valign='center'><textarea name='cusaddr' cols='18' rows='3'>$inv[cusaddr]</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Customer VAT No.</td>
				<td valign='center'><input type='text' name='cusvatno' value='$inv[cusvatno]'></td>
			</tr>";
	}else{

		db_conn('core');
		$Sl = "SELECT accid,accname FROM accounts WHERE div='".USER_DIV."' ORDER BY accname";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$accountss = "<select name=account>";
		while($ad = pg_fetch_array($Ri)) {
			if($ad['accid'] == $inv['tval']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accountss .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
		}
		$accountss.="</select>";

		$details = "
			<tr>
				<th colspan='2'> Customer Details </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td valign='center'><input type='text' name='cusname' value='$inv[cusname]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Customer Address</td>
				<td valign='center'><textarea name='cusaddr' cols='18' rows='3'>$inv[cusaddr]</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Customer VAT No.</td>
				<td valign='center'><input type='text' name='cusvatno' value='$inv[cusvatno]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Ledger Account</td>
				<td>$accountss</td>
			</tr>";
	}

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

		while($data=pg_fetch_array($Ri)) {

			$Sl = "SELECT * FROM ninvc WHERE inv='$invid' AND cid='$data[ccid]'";
			$Rq = db_exec($Sl);

			$cd = pg_fetch_array($Rq);

			$ctd .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$data[centername]</td>
					<td><input type='text' name='ct[$data[ccid]]' size='5' value='$cd[amount]'>%</td>
				</tr>";
			$i++;
		}

		$ctd .= "</table>";
	} else {
		$ctd = "";
	}

	if (empty($inv["remarks"])) {
		// Retrieve default comments
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
		$comment = base64_decode(pg_fetch_result($commRslt, 0));
	} else {
		$comment = $inv["remarks"];
 	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if (isset ($diffwhBtn) OR isset ($upBtn) OR isset ($doneBtn)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details = "
		<center>
		<h3>New Recurring Non-Stock Invoices</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						$details
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'><input type='text' size='10' name='cordno' value='$inv[cordno]'></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Recurring Non-Stock Invoice No.</td>
							<td valign='center'>RI $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Invoice No.</td>
							<td><input type='text' name='docref' value='$inv[docref]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("rinv",$rinv_year,$rinv_month,$rinv_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='yes' $chy> No<input type='radio' size='7' name='chrgvat' value='no' $chn></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr>
							<td colspan='2'>$ctd</td>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
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
							<td bgcolor='".bgcolorg()."'><a href='rec-nons-invoice-view.php'>View Recurring Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$comment</textarea></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
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
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(isset($account)) {
		$account += 0;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$sdate = $rinv_year."-".$rinv_month."-".$rinv_day;
	if( !checkdate($rinv_month, $rinv_day, $rinv_year) ){
		$v->addError($sdate, "Invalid Date.");
	}

	# used to generate errors
	$error = "asa@";

        // check the invoice details
	$v->isOK($cusname, "string", 1, 100, "Invalid customer name");
	$v->isOK($cusaddr, "string", 0, 100, "Invalid customer address");
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
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 40, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$HTTP_POST_VARS['done'] = "";
		return details($HTTP_POST_VARS, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM rnons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		# remove old items
		$sql = "DELETE FROM rnons_inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
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
						if((isset($vatex) && in_array($keys, $vatex)) || $vd['zero'] == "Yes"){
							$taxex += $amt[$keys];
							$vate = 'y';
						}

						$vate = $vatcodes[$keys];

						# insert purchase items
						$sql = "
							INSERT INTO rnons_inv_items (
								invid, qty, amt, unitcost, description, 
								vatex, div, account
							) VALUES (
								'$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', 
								'$vate', '".USER_DIV."', '$accounts[$keys]'
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
					if((isset($vatex) && in_array($keys, $vatex)) || $vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$vate = 'y';
					}

					$vate=$vatcodes[$keys];

					# insert purchase items
					$sql = "
						INSERT INTO rnons_inv_items (
							invid, qty, amt, unitcost, description, 
							vatex, div,account
						) VALUES (
							'$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', 
							'$vate', '".USER_DIV."','$accounts[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
				# everything is set place done button
				$HTTP_POST_VARS["done"] = " | <input name='doneBtn' type='submit' value='Done'>";
			}
		}else{
			$HTTP_POST_VARS["done"] = "";
		}

		$HTTP_POST_VARS['showvat'] = $showvat;

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
			//$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = $vatamount;
			$SUBTOT = $sub;
			$TOTAL = sprint($subtotal+$VAT);

		}elseif($chrgvat == "yes"){
			$subtotal = sprint($sub);
			$subtotal = sprint($subtotal);
			//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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

		db_conn('cubit');

		$Sl = "SELECT * FROM costcenters";
		$Ri = db_exec($Sl);

		$i = 0;

		$Sl = "DELETE FROM ninvc WHERE inv='$invid'";
		$Rl = db_exec($Sl);

		while($data = pg_fetch_array($Ri)) {

			if($ct[$data['ccid']] > 0) {
				$Sl = "INSERT INTO ninvc(cid,inv,amount) VALUES ('$data[ccid]','$invid','".$ct[$data['ccid']]."')";
				$Rl = db_exec($Sl);
			}

			$i++;
		}

		if(isset($account)) {
			$whe = ",tval='$account'";
		} else {
			$whe = "";
		}

		# insert purchase to DB
		$sql = "
			UPDATE rnons_invoices 
			SET cusname = '$cusname', cusaddr = '$cusaddr', cusvatno = '$cusvatno', cordno = '$cordno', 
				docref = '$docref' $whe, chrgvat = '$chrgvat', sdate = '$sdate', odate = '$sdate', 
				terms = '$terms', subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', remarks = '$remarks' 
			WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);




	if(isset($print)) {
		$OUTPUT = "<script>printer('nons-invoice-print.php?invid=$invid');move('main.php');</script>";
		require("template.php");
	}


	if( !isset($doneBtn) ){
		return details($HTTP_POST_VARS);
	} else {
		//$rslt = db_exec($sql) or errDie("Unable to update invoices status in Cubit.$sql",SELF);

		# Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Recurring Non-Stock Invoice</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Recurring Non-Stock Invoices for Customer <b>$cusname</b> has been recorded.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-nons-invoice-new.php'>New Recurring Non-Stock Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-nons-invoice-view.php'>View Recurring Non-Stock Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}

}



?>
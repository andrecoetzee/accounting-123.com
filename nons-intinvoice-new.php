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
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["done"] = "";
	$OUTPUT = details($_GET);
}else{
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

	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' AND location = 'int' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	$custs = "<select name='cusnum'>";
	if(pg_numrows($cusRslt) < 1)
		 $custs .= "<option value='-S'></option>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	$details = "
		<center>
		<h3>New International Non-Stock Invoice</h3>
		<h4>Customer Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slct'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'> Invoice Details </th>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when selling non stock goods to your customers").">
				<td>Select Customer</td>
				<td>$custs</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='center'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>";
	return $details;

}

# Starting dummy
function create_dummy($deptid, $cusnum)
{

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	$cust = pg_fetch_array($custRslt);

	$curr = getSymbol($cust['fcid']);
	$xrate = getRate ($cust['fcid']);

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
			cusname, cusaddr, cusvatno, chrgvat, fcid, currency, 
			xrate, odate, sdate, subtot, balance, vat, total, done, username, prd, invnum, typ, ctyp, 
			tval, location, div
		) VALUES (
			'$cust[cusname] $cust[surname]', '$cust[addr1]', '$cust[vatnum]', 'yes', '$cust[fcid]', '$curr[symbol]', 
			'$xrate', '$odate', CURRENT_DATE, 0, 0, 0, 0, 'n', '".USER_NAME."', '".PRD_DB."', 0, 'inv', 's', 
			'$cusnum', 'int', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	# Get next ordnum
	$invid = lastinvid();

	return $invid;

}



function details($_POST, $error="")
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if( isset($invid) ){
		$v->isOk ($invid, "num", 1, 20, "Invalid Non-Stock Invoice number.");
	} elseif(isset($cusnum)) {
		$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");
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

	if(!isset($invid))
		$invid = create_dummy(0, $cusnum);

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
		$error = "<li class='err'> Error : invoice number <b>$invid</b> has already been printed</li>.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}
	$currs = getSymbol($inv['fcid']);

/* --- Start Drop Downs --- */

	# format date
	list($s_year, $s_month, $s_day) = explode("-", $inv['sdate']);

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
/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan='2'>UNIT PRICE</th>
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
		$Sl="SELECT * FROM vatcodes ORDER BY code";
		$Ri=db_exec($Sl);

		$vats="<select name='vatcodes[]'>";

		while($vd=pg_fetch_array($Ri)) {
			if($stkd['vatex']==$vd['id']) {
				$sel="selected";
			} else {
				$sel="";
			}
			$vats.="<option value='$vd[id]' $sel>$vd[code]</option>";
		}

		$vats.="</option>";

		$stkd['amt'] = sprint ($stkd['amt']);

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='50' name='des[]' value='$stkd[description]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td align='center'> ".CUR." <input type='text' size='8' name='cunitcost[]' value='$stkd[cunitcost]'></td>
				<td align='center'> $inv[currency] <input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> $inv[currency] $stkd[amt]</td>
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
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl);

		$vats = "<select name='vatcodes[]'>";
		while($vd = pg_fetch_array($Ri)) {
			if($vd['del'] == "Yes") {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$vats .= "<option value='$vd[id]' $sel>$vd[code]</option>";
		}
		$vats .= "</option>";

		# add one
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='50' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td align='center'>".CUR." <input type='text' size='8' name='cunitcost[]'></td>
				<td align='center'>$inv[currency] <input type='text' size='8' name='unitcost[]'></td>
				<td>$inv[currency] 0.00</td>
				<td>$vats</td>
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

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[tval]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cust = pg_fetch_array($custRslt);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$details = "
		<tr>
			<th colspan='2'> Customer Details </th>
		</tr>
		<input type='hidden' name='cusname' value='$cust[cusname] $cust[surname]'>
		<input type='hidden' name='cusaddr' value='$cust[addr1]'>
		<input type='hidden' name='cusvatno' value='$cust[vatnum]'>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer</td>
			<td valign='center'>$cust[cusname] $cust[surname]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer Address</td>
			<td valign='center'><pre>$cust[addr1]</pre></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer Vat Number</td>
			<td valign='center'>$cust[vatnum]</td>
		</tr>";

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
		<h3>New International Non-Stock Invoice</h3>
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
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>TI $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Invoice No.</td>
							<td><input type='text' name='docref' value='$inv[docref]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center' nowrap='t'>".mkDateSelect("s",$s_year,$s_month,$s_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Foreign Currency</td>
							<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." <input type='text' size='7' name='xrate' value='$inv[xrate]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='yes' $chy> No<input type='radio' size='7' name='chrgvat' value='no' $chn></td>
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
							<td bgcolor='".bgcolorg()."'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$inv[remarks]</textarea></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>$inv[currency] <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>$inv[currency] $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>$inv[currency] <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input name='diffwhBtn' type='submit' value='Add Item'> |</td>
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

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$sdate = $s_year."-".$s_month."-".$s_day;
	if( !checkdate($s_month, $s_day, $s_year) ){
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
	$xrate += 0;
	$v->isOk ($xrate, "float", 1, 20, "Invalid Exchange rate.");

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$unitcost[$keys] += 0;
			$cunitcost[$keys] += 0;
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

	if($xrate == 0)
		$xrate = 1;

	# insert purchase to DB
	db_connect();

	$vatamount = 0;
	$showvat = TRUE;

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* -- Start remove old items -- */
	# remove old items
	$sql = "DELETE FROM nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
					# Calculate the unitcost
					if($unitcost[$keys] > 0 && $cunitcost[$keys] == 0){
						$cunitcost[$keys] = ($unitcost[$keys] * $xrate);
					}else{
						$unitcost[$keys] = ($cunitcost[$keys]/$xrate);
					}

					if(!isset($vatcodes[$keys])) {
						$vatcodes[$keys] = 0;
					}

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);


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

					# format ddate
					$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

					# insert purchase items
					$sql = "
						INSERT INTO nons_inv_items (
							invid, qty, amt, cunitcost, unitcost, 
							description, vatex, div
						) VALUES (
							'$invid', '$qtys[$keys]', '$amt[$keys]', '$cunitcost[$keys]', '$unitcost[$keys]', 
							'$des[$keys]', '$vate', '".USER_DIV."'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}

			} else {

				# Calculate the unitcost
				if($unitcost[$keys] > 0 && $cunitcost[$keys] == 0){
					$cunitcost[$keys] = ($unitcost[$keys] * $xrate);
				}else{
					$unitcost[$keys] = ($cunitcost[$keys]/$xrate);
				}

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);


				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
				$Ri = db_exec($Sl);

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
						invid, qty, amt, cunitcost, unitcost, 
						description, vatex, div
					) VALUES (
						'$invid', '$qtys[$keys]', '$amt[$keys]', '$cunitcost[$keys]', '$unitcost[$keys]', 
						'$des[$keys]', '$vate', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
			}
			# everything is set place done button
			$_POST["done"] = " | <input name='doneBtn' type='submit' value='Done'>";
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

	$FTOTAL = sprint($TOTAL * $xrate);

	/* --- End Clac --- */

	# insert purchase to DB
	$sql = "
		UPDATE nons_invoices 
		SET cusname = '$cusname', cusaddr = '$cusaddr', cordno = '$cordno', cusvatno = '$cusvatno', docref = '$docref', 
			chrgvat = '$chrgvat', xrate = '$xrate', odate='$sdate', sdate='$sdate', subtot = '$SUBTOT', vat = '$VAT', 
			total = '$TOTAL', balance = '$FTOTAL', fbalance = '$TOTAL', remarks = '$remarks' 
		WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if( !isset($doneBtn) ){
		return details($_POST);
	} else {
		$rslt = db_exec($sql) or errDie("Unable to update invoices status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Non-Stock Invoices</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Non-Stock Invoices for Customer <b>$cusname</b> has been recorded.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;

	}

}


?>
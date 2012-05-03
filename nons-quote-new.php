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
		default:
			$_GET["done"] = "";
			$OUTPUT = details($_GET);
		}
	} else {
		$_GET["done"] = "";
		$OUTPUT = details($_GET);
	}
}

# Get templete
require("template.php");




# Starting dummy
function create_dummy($deptid)
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

	$ncdate = date ("Y-m-d");

	# Insert purchase to DB
	$sql = "
		INSERT INTO nons_invoices (
			cusname, cusaddr, cusvatno, chrgvat, sdate, odate, subtot, balance, vat, total, done, username, prd, invnum, 
			typ, div, ncdate
		) VALUES (
			'', '', '', 'yes', CURRENT_DATE, '$odate', 0, 0, 0, 0, 'n', '".USER_NAME."', '".PRD_DB."', 0, 
			'quo', '".USER_DIV."', '$ncdate'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Quote.",SELF);
	return lastinvid();

}



# details
function details($_POST, $error="")
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if( isset($invid) ){
		$v->isOk ($invid, "num", 1, 20, "Invalid Non-Stock Quote number.");
	} else {
		$invid = create_dummy(0);
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



	# Get quote info
	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Quote Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if quote has been printed
	if($inv['done'] == "y"){
		$error = "<li class='err'> Error : quote number <b>$invid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$lead = $inv["lead"];

	if(strlen($inv['ncdate']) < 1){
		$ncdate_year = date("Y");
		$ncdate_month = date("m",mktime(0,0,0,date("m"),date("d")+5,date("Y")));
		$ncdate_day = date("d",mktime(0,0,0,date("m"),date("d")+5,date("Y")));
	}else {
		$darr = explode ("-",$inv['ncdate']);
		$ncdate_year = $darr['0'];
		$ncdate_month = $darr['1'];
		$ncdate_day = $darr['2'];
	}

/* --- Start Drop Downs --- */

	# format date
	list($nquo_year, $nquo_month, $nquo_day) = explode("-", $inv['odate']);

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
			$chk = "checked='yes'";

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

		$stkd['amt'] = sprint($stkd['amt']);

		# put in product
		$products .= "
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='50' name='des[]' value='$stkd[description]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
				<td align='center'>$vats</td>
				<td><input type='checkbox' name='remprod[]' value='$i'><input type='hidden' name='SCROLL' value='yes'></td>
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
				<td align='center'><input type='text' size='50' name=des[] value=''></td>
				<td align='center'><input type='text' size='3' name=qtys[] value='1'></td>
				<td align='center'><input type='text' size='8' name=unitcost[]></td>
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

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

/* --- End Some calculations --- */

	$sel = "";
	if(isset($lead) AND (strlen($lead) > 0))
		$sel = "checked=yes";

	if (!isset ($old_customer_select)) 
		$old_customer_select = "";

	#get customers
	$get_cust = "SELECT cusnum, surname, vatnum, paddr1 FROM customers WHERE blocked = 'no' AND location = 'loc' ORDER BY cusname";
	$run_cust = db_exec ($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows ($run_cust) < 1){
		$cust_drop = "<input type='hidden' name='customer_select' value=''>No Customers Found.";
	}else {
		$cust_drop = "<select name='customer_select' onChange=\"document.form.submit();\">";
		$cust_drop .= "<option value=''>Select Customer Or Enter Details</option>";
		while ($carr = pg_fetch_array ($run_cust)){
			if (isset ($customer_select) AND $customer_select == $carr['cusnum']){
				$cust_drop .= "<option value='$carr[cusnum]' selected>$carr[surname]</option>";

				if ($old_customer_select != $customer_select) {
					$inv['cusname'] = $carr['surname'];
					$inv['cusaddr'] = $carr['paddr1'];
					$inv['cusvatno'] = $carr['vatnum'];
				}else {
					$inv['cusname'] = $cusname;
					$inv['cusaddr'] = $cusaddr;
					$inv['cusvatno'] = $cusvatno;
				}
			}else {
				$cust_drop .= "<option value='$carr[cusnum]'>$carr[surname]</option>";
			}
		}
		$cust_drop .= "</select>";
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
		<h3>New Non-Stock Quotes</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='old_customer_select' value='$customer_select'>
			<input type='hidden' name='invid' value='$invid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Select Customer</td>
							<td>$cust_drop</td>
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
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Next Contact Date</td>
							<td valign='center'>".mkDateSelect("ncdate",$ncdate_year,$ncdate_month,$ncdate_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Add As Lead</td>
							<td valign='center'><input type='checkbox' name='lead' $sel value='yes'></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Quote Details </th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Non-Stock Quote No.</td>
							<td valign='center'>TI $inv[invid]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("nquo",$nquo_year,$nquo_month,$nquo_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
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
							<td class='".bg_class()."'><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$inv[remarks]</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>
				</td>
				<td align='right'>
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

	#get vars
	extract ($_POST);

	#only process details if we are not changing the customer
	if (isset ($customer_select) AND isset ($old_customer_select) AND ($customer_select != $old_customer_select)) 
		return details ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$sdate = mkdate($nquo_year, $nquo_month, $nquo_day);
	$v->isOk($sdate, "date", 1, 1, "Invalid Date.");

	# used to generate errors
	$error = "asa@";

    // check the quote details
	$v->isOK($cusname, "string", 1, 100, "Invalid customer name");
	$v->isOK($cusaddr, "string", 0, 100, "Invalid customer address");
	$v->isOK($cusvatno, "string", 0, 50, "Invalid customer vat number");

	if ( $chrgvat != "yes" && $chrgvat != "no" && $chrgvat!="none")
		$v->addError($chrgvat, "Invalid vat option");

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
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
		$error = "<li class='err'> Error : quote number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$vatamount = 0;
	$showvat = TRUE;

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	db_connect();

	/* -- Start remove old items -- */
	# remove old items
	$sql = "DELETE FROM nons_inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update quote items in Cubit.",SELF);
	$taxex = 0;
	/* -- End remove old items -- */

	if(isset($qtys)){
		foreach($qtys as $keys => $value){
			if(isset($remprod) && in_array($keys, $remprod)){

			} else {
				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

				if(!isset($vatcodes[$keys])) {
					$vatcodes[$keys] = 0;
				}

				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
				$Ri = db_exec($Sl);

// 				if(pg_num_rows($Ri)<1) {
// 					return "Please select the vatcode for all your stock.";
// 				}

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
				if((isset($vatex) && in_array($keys, $vatex))||$vd['zero'] == "Yes"){
					$taxex += $amt[$keys];
					$vate = 'y';
				}

				$vate = $vatcodes[$keys];

				# insert purchase items
				$sql = "
					INSERT INTO nons_inv_items (
						invid, qty, amt, unitcost, description, vatex, div
					) VALUES (
						'$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]','$vate',  '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert quote items to Cubit.",SELF);
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
//		$VAT=sprint(($subtotal-$taxex)*$VATP/100);
		$VAT = $vatamount;
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
	}elseif($chrgvat == "yes"){
		$subtotal = sprint($sub);
		$subtotal = sprint($subtotal);
	//	$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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

	$ncdate = "$ncdate_year-$ncdate_month-$ncdate_day";

	if (!isset($lead))
		$lead = "";

	# insert purchase to DB
	$sql = "
		UPDATE nons_invoices 
		SET cusname = '$cusname', cusaddr = '$cusaddr', cusvatno = '$cusvatno', chrgvat = '$chrgvat', odate = '$sdate', 
			subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', remarks = '$remarks', lead = '$lead', ncdate = '$ncdate' 
		WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update quote in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if( !isset($doneBtn) ){
		return details($_POST);
	} else {
		$rslt = db_exec($sql) or errDie("Unable to update invoices status in Cubit.",SELF);

		#add lead
		if(isset($lead) AND ($lead == "yes")){
			db_conn("crm");
			$sql = "
				INSERT INTO leads (
					surname, date, by, con, div, supp_id, cust_id, lead_source, birthdate, reports_to_id, assigned_to, 
					assigned_to_id, account_id, gender, website, salespid, ncdate, team_id, dept_id, tell, hadd, ref
				) VALUES (
					'$cusname', 'now', '".USER_NAME."', 'No', '".USER_DIV."', '0', '0', '0', 'now', '0', '".USER_NAME."', 
					'0', '0', 'Male', 'http://', '0', '$ncdate', '0', '0', '', '$cusaddr', ''
				)";
			$rslt = db_exec($sql) or errDie ("Unable to add lead to database.");
			$lead_id = pglib_lastid("leads", "id");
		}

		// Final Laytout
		$write = "
			<script>
				printer('nons-quote-print.php?invid=$invid');
			</script>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New Non-Stock Quotes</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Non-Stock Quotes for Customer <b>$cusname</b> has been recorded.</td>
					<td><a href=\"javascript:printer('nons-quote-print.php?invid=$invid')\">Print Quote</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
		return $write;

// 		return "
// 			<script>
// 				printer('nons-quote-print.php?invid=$invid');
// 				document.location='nons-quote-new.php';
// 			</script>";
	}


}


?>
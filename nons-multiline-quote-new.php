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
		default:
			$HTTP_GET_VARS["done"] = "";
			$OUTPUT = details($HTTP_GET_VARS);
		}
	} else {
		$HTTP_GET_VARS["done"] = "";
		$OUTPUT = details($HTTP_GET_VARS);
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

	# Insert purchase to DB
	$sql = "
		INSERT INTO nons_invoices (
			cusname, cusaddr, cusvatno, chrgvat, sdate, odate, subtot, balance, vat, total, done, username, prd, invnum, 
			typ, div, multiline 
		) VALUES (
			'', '', '', 'yes', CURRENT_DATE, '$odate', 0, 0, 0, 0, 'n', '".USER_NAME."', '".PRD_DB."', 0, 
			'quo', '".USER_DIV."', 'yes'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Quote.",SELF);
	return lastinvid();

}



# details
function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

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
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' nowrap><input type='hidden' name='des[]' value='$stkd[description]'>".nl2br($stkd['description'])."</td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					<table id='tblCtrls' width='420px' height='30px' border='0' cellspacing='0' cellpadding='0' bgcolor='#D6D3CE'>
						<tr>
							<td class='tdClass'>
								<img alt='Bold' class='buttonClass' src='images/bold.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBold()'>
								<img alt='Italic' class='buttonClass' src='images/italic.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doItalic()'>
								<img alt='Underline' class='buttonClass' src='images/underline.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doUnderline()'>
								<img alt='Left' class='buttonClass' src='images/left.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doLeft()'>
								<img alt='Center' class='buttonClass' src='images/center.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doCenter()'>
								<img alt='Right' class='buttonClass' src='images/right.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRight()'>
								<img alt='Ordered List' class='buttonClass' src='images/ordlist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doOrdList()'>
								<img alt='Bulleted List' class='buttonClass' src='images/bullist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBulList()'>
								<img alt='Horizontal Rule' class='buttonClass' src='images/rule.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRule()'>
							</td>
							<td class='tdClass' align=right>
								<select name='selSize' onChange='doSize(this.options[this.selectedIndex].value)'>
									<option value=''>-- Font Size --</option>
									<option value='1'>Very Small</option>
									<option value='2'>Small</option>
									<option value='3'>Medium</option>
									<option value='4'>Large</option>
									<option value='5'>Larger</option>
									<option value='6'>Very Large</option>
								</select>
							</td>
						</tr>
					</table>
					<iframe name='editArea' id='editArea' style='width: 420px; height:160px; background: #FFFFFF;'></iframe>
					<input type='hidden' name='bodydata' value=''>
					<input type='hidden' name='counter' value='$i'>
				</td>
				<td align='center'><input type='text' size='3' name='qtys[$i]' value='1'></td>
				<td align='center'><input type='text' size='8' name='unitcost[$i]'></td>
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
		$sel = "checked='yes'";

	$showdoc_html = "'".str_replace ("<div style=\"text-align: left;\"><br></div>","",$bodydata)."'";

	if (!isset ($old_customer_select)) 
		$old_customer_select = "";

	#get customers
	$get_cust = "SELECT cusnum, surname, vatnum, paddr1 FROM customers WHERE blocked = 'no' AND location = 'loc' ORDER BY cusname";
	$run_cust = db_exec ($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows ($run_cust) < 1){
		$cust_drop = "<input type='hidden' name='customer_select' value=''>No Customers Found.";
	}else {
		$cust_drop = "<select name='customer_select' onChange=\"document.editForm.submit();\">";
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


	$details = "
		<script language='JavaScript'>
			function update() {
				document.editForm.bodydata.value = editArea.document.body.innerHTML;
				document.editForm.submit();
			}
			function Init() {
				editArea.document.designMode = 'On';
				editArea.document.body.innerHTML = $showdoc_html;
				editArea.document.execCommand('justifyleft', false, null);
			}
			function controlSelOn(ctrl) {
				ctrl.style.borderColor = '#000000';
				ctrl.style.backgroundColor = '#B5BED6';
				ctrl.style.cursor = 'hand';
			}
			function controlSelOff(ctrl) {
				ctrl.style.borderColor = '#D6D3CE';
				ctrl.style.backgroundColor = '#D6D3CE';
			}
			function controlSelDown(ctrl) {
				ctrl.style.backgroundColor = '#8492B5';
			}
			function controlSelUp(ctrl) {
				ctrl.style.backgroundColor = '#B5BED6';
			}
			function doBold() {
				editArea.document.execCommand('bold', false, null);
			}
			function doItalic() {
				editArea.document.execCommand('italic', false, null);
			}
			function doUnderline() {
				editArea.document.execCommand('underline', false, null);
			}
			function doLeft() {
				editArea.document.execCommand('justifyleft', false, null);
			}
			function doCenter() {
				editArea.document.execCommand('justifycenter', false, null);
			}
			function doRight() {
				editArea.document.execCommand('justifyright', false, null);
			}
			function doOrdList() {
				editArea.document.execCommand('insertorderedlist', false, null);
			}
			function doBulList() {
				editArea.document.execCommand('insertunorderedlist', false, null);
			}
			function doRule() {
				editArea.document.execCommand('inserthorizontalrule', false, null);
			}
			function doSize(fSize) {
				if(fSize != '')
					editArea.document.execCommand('fontsize', false, fSize);
			}
			window.onload = Init;
		</script>
		<center>
		<h3>New Multi Line Quote</h3>
		<form action='".SELF."' method='POST' name='editForm' enctype='multipart/form-data'>
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
						<tr bgcolor='".bgcolorg()."'>
							<td>Select Customer</td>
							<td>$cust_drop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='middle'><input type='text' name='cusname' value='$inv[cusname]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='middle'><textarea name='cusaddr' cols='18' rows='3'>$inv[cusaddr]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer VAT No.</td>
							<td valign='middle'><input type='text' name='cusvatno' value='$inv[cusvatno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Next Contact Date</td>
							<td valign='center'>".mkDateSelect("ncdate",$ncdate_year,$ncdate_month,$ncdate_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
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
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Quote No.</td>
							<td valign='center'>TI $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("nquo",$nquo_year,$nquo_month,$nquo_day)."</td>
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
							<td bgcolor='".bgcolorg()."'><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$inv[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
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
				<td align='right'><input name='diffwhBtn' onClick='update();' type='submit' value='Add Item'> |</td>
				<td><input type='submit' name='upBtn' onClick='update();' value='Update'>$done</td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



# details
function write($HTTP_POST_VARS)
{

	#get vars
	extract ($HTTP_POST_VARS);

	#only process details if we are not changing the customer
	if (isset ($customer_select) AND isset ($old_customer_select) AND ($customer_select != $old_customer_select)) 
		return details ($HTTP_POST_VARS);

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

	if (!isset($bodydata))
		$bodydata = "";

	$bodydata = str_replace("'","",$bodydata);
	//$bodydata = str_replace("<br>","",$bodydata);

	$bodydata = str_replace("  "," ",$bodydata);
	
	$bodydata = str_replace("&nbsp;&nbsp;"," ",$bodydata);
	$bodydata = str_replace(" &nbsp;"," ",$bodydata);
	$bodydata = str_replace("&nbsp; "," ",$bodydata);
//[key] was $counter ... but it wasnt set ??
	$des[] = $bodydata;

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
//			$v->isOk ($des[$keys], "url", 1, 255, "Invalid Description.");
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
		$HTTP_POST_VARS['done'] = "";
		return details($HTTP_POST_VARS, $err);
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
			if(isset($remprod)&&in_array($keys, $remprod)){

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
//		$VAT=sprint(($subtotal-$taxex)*$VATP/100);
		$VAT = $vatamount;
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal+$VAT);
	}elseif($chrgvat == "yes"){
		$subtotal = sprint($sub);
		$subtotal = sprint($subtotal);
	//	$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
		$VAT = $vatamount;
		$SUBTOT = sprint($sub - $vatamount);
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

	$TOTAL = sprint($SUBTOT + $VAT);

	/*# if vat is not included
	$VATP = TAX_VAT;
	if($chrgvat == "yes"){
		$SUBTOT = sprintf("%0.2f", $TOTAL * 100 / (100 + $VATP) );
	} elseif($chrgvat == "no") {
		$SUBTOT = $TOTAL;
		$TOTAL = sprintf("%0.2f", $TOTAL * (100 + $VATP) /100 );
	}else{
		$SUBTOT = $TOTAL;
	}

	// compute the sub total (total - vat), done this way because the specified price already includes vat
	$VAT = $TOTAL - $SUBTOT;

	/* --- End Clac --- */

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
		return details($HTTP_POST_VARS);
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
				<tr bgcolor='".bgcolorg()."'>
					<td>Non-Stock Quotes for Customer <b>$cusname</b> has been recorded.</td>
					<td><input type='button' onClick=\"printer('nons-quote-print.php?invid=$invid');\" value='Print Quote'></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
		return $write;

// 		return "
// 			<script>
// 				printer('nons-quote-print.php?invid=$invid');
// 				document.location='nons-multiline-quote-new.php';
// 			</script>";
	}

}


?>
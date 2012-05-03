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
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
				$OUTPUT = write($_POST);
				break;

			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# Get templete
require("template.php");

# details
function details($_GET, $errata = "") {
	$showvat = TRUE;

	extract($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

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
		<th width='20%'>ACCOUNT</th>
	<tr>";

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT *,(qty - rqty) as qty FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkd['accid']);
		$acc = pg_fetch_array($accRs);

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
					<tr class='".bg_class()."'>
						<td align='center'>$i<input type='hidden' name=ids[] value='$stkd[id]'></td>
						<td>$stkd[description]</td>
						<td><input type='hidden' name=oqtys[] value='$stkd[qty]'><input type='text' size='4' name=qtys[] value='$stkd[qty]'></td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td nowrap>".CUR." $stkd[amt]</td>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
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

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	# format date
	list($ninv_year, $ninv_month, $ninv_day) = explode("-", $inv['odate']);

	if(!isset($ninv_year) OR strlen($ninv_year) < 1){
		$ninv_year = date("Y");
	}

	if(!isset($ninv_month) OR strlen($ninv_month) < 1){
		$ninv_month = date("m");
	}

	if(!isset($ninv_day) OR strlen($ninv_day) < 1){
		$ninv_day = date("d");
	}

	/* -- Final Layout -- */
	$details = "
			<center>
			<h3>Non-Stock Credit Note Details</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='invid' value=$invid>
			<table ".TMPL_tblDflts." width='95%'>
				<tr>
					<td valign='top'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Customer Details </th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer</td>
								<td valign='center'>$inv[cusname]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Address</td>
								<td valign='center'><pre>$inv[cusaddr]</pre></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer VAT Number</td>
								<td valign='center'>$inv[cusvatno]</td>
							</tr>
						</table>
					</td>
					<td valign='top' align='right'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Non-Stock Invoice Details </th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Non-Stock Invoice No.</td>
								<td valign='center'>$inv[invnum]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Date</td>
								<td valign='center'>".mkDateSelect("ninv",$ninv_year,$ninv_month,$ninv_day)."</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT Inclusive</td>
								<td valign='center'>$inv[chrgvat]</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td>$errata</td>
				</tr>
				<tr>
					<td colspan='2'>$products</td>
				</tr>
				<tr>
					<td>
						<table ".TMPL_tblDflts.">
							<tr>
								<th width=40%>Quick Links</th>
								<th width=45%>Remarks</th>
								<td rowspan='5' valign='top' width=15%><br></td>
							</tr>
							<tr>
								<td class='".bg_class()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
								<td class='".bg_class()."' rowspan=4 align=center valign=top><textarea name='remarks' cols='20' rows='5'>".nl2br($inv['remarks'])."</textarea></td>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
					<td align='right'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr class='".bg_class()."'>
								<td>SUBTOTAL</td>
								<td align='right' nowrap>".CUR." $inv[subtot]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT $vat14</td>
								<td align='right' nowrap>".CUR." $inv[vat]</td>
							</tr>
							<tr class='".bg_class()."'>
								<th>GRAND TOTAL</th>
								<td align='right' nowrap>".CUR." $inv[total]</td>
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


# confirm
function confirm($_POST)
{

	$showvat = TRUE;

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid remarks.");
	$sdate = $ninv_year."-".$ninv_month."-".$ninv_day;
	if( !checkdate($ninv_month, $ninv_day, $ninv_year) ){
		$v->addError($sdate, "Invalid Date.");
	}

	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
		$v->isOk ($qtys[$key], "float", 1, 20, "Invalid Item quantity.");
		if($qtys[$key] > $oqtys[$key]){
			$v->isOk ("##", "num", 1, 1, "Error: Item quantity cannot be more than invoiced quantity.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "$err<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return details($_POST, $err);
		return $confirm;
	}

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

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
						<th width='25%'>ACCOUNT</th>
					<tr>";

	# get selected stock in this Invoice
	$any = false;
	$i = 0;
	$vatmin=0;
	$vatamount = 0;

	foreach($ids as $key => $id){
		if($qtys[$key] <= 0){
			continue;
		}
		$any = true;
		db_connect();
		$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		$stkacc = $stkd['accid'];
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkacc);
		$acc = pg_fetch_array($accRs);

		# Calculate amount
		$amt[$key] = ($qtys[$key] * $stkd['unitcost']);

		db_connect();
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		$vr=vatcalc($amt[$key],$inv['chrgvat'],$excluding,0,$vd['vat_amount']);
		$vrs=explode("|",$vr);
		$ivat=$vrs[0];
		$iamount=$vrs[1];

		$vatamount += $ivat;

// 		if($stkd['vatex']=="y"||$vd['zero']=="Yes") {
// 			$vatmin+=$amt[$key];
// 		}

		$amt[$key] = sprint ($amt[$key]);

		$any = true;
		$i++;
		# put in product
		$products .= "
					<tr class='".bg_class()."'>
						<td align=center>$i<input type='hidden' name=ids[] value='$stkd[id]'></td>
						<td>$stkd[description]</td>
						<td><input type='hidden' name='qtys[]' value='$qtys[$key]'>$qtys[$key]</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td nowrap><input type='hidden' name='amts[]' value='$amt[$key]'>".CUR." $amt[$key]</td>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					</tr>";
	}
	$products .= "</table>";

	# if there isn't any products
	if(!$any){
		$err = "<li class='err'> Error : There are no products selected.";
		return details($_POST, $err);
		return "<li class='err'> Error : There are no products selected.";
	}

	$taxex=sprint($vatmin);
	$chrgvat=$inv['chrgvat'];

/* --- Start Some calculations --- */
	/*
	# calculate subtot
	if( isset($amt) ){
		$TOTAL = array_sum($amt);
	}else{
		$TOTAL = 0.00;
	}

	$vatmin=sprint($vatmin);

	$temp_TOTAL=$TOTAL;

	$TOTAL=$TOTAL-$vatmin;


	# if vat is not included
	$VATP = TAX_VAT;
	if($inv['chrgvat'] == "yes"){
		$SUBTOT = sprintf("%0.2f", $TOTAL * 100 / (100 + $VATP) );
	} elseif($inv['chrgvat'] == "no") {
		$SUBTOT = $TOTAL;
		$TOTAL = sprintf("%0.2f", $TOTAL * (100 + $VATP) /100 );
	}else{
		$SUBTOT = $TOTAL;
	}

	$TOTAL=sprint($TOTAL+$vatmin);
	$SUBTOT=sprint($SUBTOT+$vatmin);

	// compute the sub total (total - vat), done this way because the specified price already includes vat
	$VAT = sprint($TOTAL - $SUBTOT);*/

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($chrgvat == "no"){
			$subtotal=sprint($sub);
			$subtotal=sprint($subtotal);
		//	$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = sprint($vatamount);
			$SUBTOT = $sub;
			$TOTAL=sprint($subtotal+$VAT);

		}elseif($chrgvat == "yes"){
			$subtotal=sprint($sub);
			$subtotal=sprint($subtotal);
		//	$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$VAT = sprint($vatamount);
			$SUBTOT=sprint($sub);
			$TOTAL=sprint($subtotal);

		} else {
			$subtotal=sprint($sub);
			$traddiscmt=sprint($subtotal);
			$subtotal=sprint($subtotal);
			$VAT=sprint(0);
			$SUBTOT=$sub;
			$TOTAL=$subtotal;
		}

/* --- End Some calculations ---

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

	# Format date
	// list($ninv_year, $ninv_month, $ninv_day) = explode("-", $inv['sdate']);
*/

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
			<h3>Non-Stock Credit Note</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='invid' value=$invid>
				<input type='hidden' name='remarks' value='$remarks'>
			<table ".TMPL_tblDflts." width='95%'>
				<tr>
					<td valign='top'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Customer Details </th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer</td>
								<td valign='center'>$inv[cusname]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Address</td>
								<td valign='center'><pre>$inv[cusaddr]</pre></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer VAT Number</td>
								<td valign='center'>$inv[cusvatno]</td>
							</tr>
						</table>
					</td>
					<td valign='top' align='right'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Non-Stock Invoice Details </th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Non-Stock Invoice No.</td>
								<td valign='center'>$inv[invnum]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Date</td>
								<td valign='center'>
									<input type='hidden' size='2' name='ninv_day' maxlength='2' value='$ninv_day'>$ninv_day-
									<input type='hidden' size='2' name='ninv_month' maxlength='2' value='$ninv_month'>$ninv_month-
									<input type='hidden' size='4' name='ninv_year' maxlength='4' value='$ninv_year'>$ninv_year
								</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT Inclusive</td>
								<td valign='center'>$inv[chrgvat]</td>
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
							<tr>
								<td class='".bg_class()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
								<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($remarks)."</td>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
					<td align='right'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr class='".bg_class()."'>
								<td>SUBTOTAL</td>
								<td align='right' nowrap><input type='hidden' name='subtot' value='$SUBTOT'>".CUR." $SUBTOT</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT $vat14</td>
								<td align='right' nowrap><input type='hidden' name='vat' value='$VAT'>".CUR." $VAT</td>
							</tr>
							<tr class='".bg_class()."'>
								<th>GRAND TOTAL</th>
								<td align='right' nowrap><input type='hidden' name='total' value='$TOTAL'>".CUR." $TOTAL</td>
							</tr>
						</table>
					</td></tr>
					<tr>
						<td align='right'><input type='submit' value='Write &raquo'></td>
					</tr>
				</table>
				</form>
				</center>";
	return $details;

}


# Details
function write($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$sndate = $ninv_year."-".$ninv_month."-".$ninv_day;
	if( !checkdate($ninv_month, $ninv_day, $ninv_year) ){
		$v->addError($sdate, "Invalid Date.");
	}

	$td=$sndate;

	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
		$v->isOk ($qtys[$key], "float", 1, 20, "Invalid Item quantity.");
		$v->isOk ($amts[$key], "float", 1, 20, "Invalid Item amount.");
	}
	$v->isOk ($subtot, "float", 1, 20, "Invalid sub-total amount.");
	$v->isOk ($vat, "float", 1, 20, "Invalid vat amount.");
	$v->isOk ($total, "float", 1, 20, "Invalid total amount.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM hire.hire_nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	db_conn("hire");
	$noteid = pglib_lastid("hire_nons_inv_notes", "noteid");
	$noteid++;

# Begin updates
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* --- Start Products Display --- */

	$refnum = getrefnum();
/*refnum*/

	$real_noteid = divlastid('note', USER_DIV);
	$vattot = 0;
	$amttot = 0;

	db_connect();

	# Products layout
	$products = array();
	$i = 0;
	$page = 0;
	foreach($ids as $key => $id){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$temp=$stkd['vatex'];

		if($vd['zero']=="Yes") {
			$stkd['vatex']="y";
		}

		$t=$inv['chrgvat'];

	//	$VATP = TAX_VAT;
		$VATP = $vd['vat_amount'];

		$stkacc = $stkd['accid'];
		# keep records for transactions
		if(isset($totstkamt[$stkacc])){
			if($stkd['vatex']=="y") {
				$totstkamt[$stkacc] +=$amts[$key];
				$va=0;
				$inv['chrgvat']="";
			} else {
				$totstkamt[$stkacc] += vats($amts[$key], $inv['chrgvat'],$vd['vat_amount']);
				$va=sprint($stkd['amt']-vats($amts[$key], $inv['chrgvat'],$vd['vat_amount']));
				if($inv['chrgvat']=="no") {
					$va=sprint($amts[$key]*$VATP/100);
				}
			}
		}else{
			if($stkd['vatex']=="y") {
				$totstkamt[$stkacc] = $amts[$key];
				$va=0;
				$inv['chrgvat']="";
			} else {
				$totstkamt[$stkacc] = vats($amts[$key], $inv['chrgvat'],$vd['vat_amount']);
				$va=sprint($amts[$key]-vats($amts[$key], $inv['chrgvat'],$vd['vat_amount']));
				if($inv['chrgvat']=="no") {
					$va=sprint($amts[$key]*$VATP/100);
				}
			}
		}

		#add this entry's vat to a total
		$vattot = $vattot + $va;

		vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid Customer $inv[cusname].",(-vats($amts[$key], $inv['chrgvat'],$vd['vat_amount']) -$va),-$va);

		$inv['chrgvat']=$t;

		$sql = "UPDATE hire.hire_nons_inv_items SET rqty = (rqty + '$qtys[$key]') WHERE id = '$stkd[id]'";
		$sRslt = db_exec($sql);

		if($stkd['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		$stkd['vatex']=$temp;

		#add this entry's amt to a total
		$amttot = $amttot + $amts[$key];

		$sql = "INSERT INTO hire.hire_nons_note_items(noteid, qty, description, amt, unitcost, vatcode) VALUES('$noteid', '$qtys[$key]', '$stkd[description]', '$amts[$key]', '$stkd[unitcost]', '$stkd[vatex]')";
		$stkdRslt = db_exec($sql);

		#the credit note entry will get any remark entered here ? so we dont update the invoice entry ...
	//	db_conn("cubit");
	//	$sql = "UPDATE nons_invoices SET remarks='$remarks' WHERE invid='$invid'";
	//	$rslt = db_exec($sql) or errDie("Unable to save the comments to Cubit.");

		$products[$page][] = "
						<tr valign='top'>
							<td style='border-right: 2px solid #000'>$ex $stkd[description]&nbsp;</td>
							<td style='border-right: 2px solid #000'>$qtys[$key]&nbsp;</td>
							<td style='border-right: 2px solid #000' align='right' nowrap>".CUR." $stkd[unitcost]&nbsp;</td>
							<td align='right' nowrap>".CUR." $amts[$key]&nbsp;</td>
						</tr>";

		$i++;
	}

 	$blank_lines = 25;
 	foreach ($products as $key=>$val) {
 		$bl = $blank_lines - count($products[$key]);
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "
				 			<tr>
				 				<td style='border-right: 2px solid #000'>&nbsp;</td>
				 				<td style='border-right: 2px solid #000'>&nbsp;</td>
				 				<td style='border-right: 2px solid #000'>&nbsp;</td>
				 				<td>&nbsp;</td>
				 			</tr>";
 		}
 	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($subtot);
	$VAT = sprint($vat);
	$TOTAL = sprint($total);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","vat");
	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	// print $inv['ctyp']; exit;

	db_connect();

	$tot_post=0;

	# bank  % cust
	if($inv['ctyp'] == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusid]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);



		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp=0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			$tot_post+=$wamt;
			writetrans($stkacc, $dept['debtacc'], $td, $refnum, $wamt, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid Customer $inv[cusname].");
		}
		if($VAT <> 0){
			$tot_post+=$VAT;
			writetrans($vatacc, $dept['debtacc'], $td, $refnum, $VAT, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid VAT. Customer $inv[cusname].");
		}

		$tot_dif=sprint($tot_post-$TOTAL);

		if($tot_dif>0) {
			writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on Credit note No.$real_noteid");
		} elseif($tot_dif<0) {
			$tot_dif=$tot_dif*-1;
			writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on Credit note No.$real_noteid");
		}
	}elseif($inv['ctyp'] == 'b'){
		$dept['debtacc'] = getbankaccid($inv['accid']);
		$amounts = "";
		$accids = "";
		$vats = "";
		$chrgvats = "";
		$gamt = 0;

		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Cook vars
			$amounts .= "|$wamt";
			$accids .= "|$stkacc";
			$vats .= "|0";
			$chrgvats .= "|no";

			# Debit Customer and Credit stock
			$tot_post+=$wamt;
			writetrans($stkacc, $dept['debtacc'], $td, $refnum, $wamt, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid.");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			# Cook vars
			$amounts .= "|$VAT";
			$accids .= "|$vatacc";
			$vats .= "|0";
			$chrgvats .= "|no";
			$tot_post+=$VAT;
			writetrans($vatacc, $dept['debtacc'], $td, $refnum, $VAT, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid VAT.");
		}
	}else{
		$cusacc = $inv['accid'];
		$sdate = date("Y-m-d");
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Customer and Credit stock
			$tot_post+=$wamt;
			writetrans($stkacc, $cusacc,  $td, $refnum, $wamt, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid.");
			pettyrec($cusacc, $td, "dt", "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid.", $wamt, "Account Sale Credit note");
		}

		# Debit bank and credit the account involved
		$tot_post+=$VAT;
		writetrans($vatacc, $cusacc, $td, $refnum, $VAT, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid VAT.");
		pettyrec($cusacc, $td, "dt", "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid VAT.", $VAT, "Account Sale Credit note VAT");

		$tot_dif=sprint($tot_post-$TOTAL);

		if($tot_dif>0) {
			writetrans($cusacc, $varacc, $td, $refnum, $tot_dif, "Sales Variance on Credit note No.$real_noteid");
		} elseif($tot_dif<0) {
			$tot_dif=$tot_dif*-1;
			writetrans($varacc,$cusacc , $td, $refnum, $tot_dif, "Sales Variance on Credit note No.$real_noteid");
		}
	}
	$sdate = date("Y-m-d");

	db_connect();
	if($inv['ctyp'] == 's'){
		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, amount, date, type, div, allocation_date) 
			VALUES 
				('$inv[cusid]', '$real_noteid', '-$TOTAL','$td', 'Non Stock Credit Note, for invoice $inv[invnum]', '".USER_DIV."', '$inv[odate]')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance less)
		$sql = "UPDATE customers SET balance = (balance - '$TOTAL'::numeric(13,2)) WHERE cusnum = '$inv[cusid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update the customer (make balance less)
		$sql = "UPDATE open_stmnt SET balance = (balance - '$TOTAL'::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Make ledge record
		custledger($inv['cusid'],$stkacc , $td, $real_noteid, "Non Stock Credit note $real_noteid", $TOTAL, "c");

		#record entry for age analysis ...
		#this function seems a little ... broken
		//custfCT($TOTAL, $inv['cusid'], $inv['age']);
		#lets rather use the system wide function and send it the invoice transaction date to do the entry for that age
		custCT($TOTAL, $inv['cusid'],$inv['odate']);
	}elseif($inv['ctyp'] == 'cb'){
		$date = date("Y-m-d");

		# Record the Receipt record
		db_connect();
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts,  chrgvats, vats, div,accinv) VALUES ('$inv[jobid]', 'withdrawal', '$td', '$inv[cusname]', 'Nons Stock Credit note for invoice $inv[invnum]', '0', '$TOTAL', 'no', '', '0', '$inv[chrgvat]', '0', '".USER_DIV."','$stkacc')";
		die($sql);
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank Receipt to database.",SELF);
	}

	db_connect();
	$sql = "UPDATE hire.hire_nons_invoices SET balance = (balance - '$TOTAL'::numeric(13,2)) WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

	# write note
	$sql = "INSERT INTO hire.hire_nons_inv_notes(invid, invnum, cusname, cusaddr, cusvatno, chrgvat, date, subtot, vat, total, username, prd, notenum, ctyp, remarks, div)";
	$sql .= " VALUES('$inv[invid]', '$inv[invnum]', '$inv[cusname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[chrgvat]', '$td', $SUBTOT, $VAT, $TOTAL, '".USER_NAME."', '".PRD_DB."', '$real_noteid', '$inv[ctyp]', '$remarks', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	# write note items
	foreach($ids as $key => $id){
		$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$nstk = pg_fetch_array($stkdRslt);
	}

	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES('$td', '$noteid', '$real_noteid', '0', '$VAT', '$TOTAL', 'nnon', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusid]','$inv[cusname]','Credit Note: $real_noteid, Invoice $inv[invnum]','$td','".-sprint($TOTAL-$VAT)."','-$VAT','".-sprint($TOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);

	com_invoice($inv['salespn'],-($TOTAL-$VAT),0,$inv['invnum'],$td);

# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$cc = "<script> CostCenter('ct', 'Credit Note', '$td', 'Non Stock Credit Note No.$real_noteid', '".($TOTAL-$VAT)."', ''); </script>";

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

	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusid]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cust_data = pg_fetch_array($cust_rslt);

	if($inv['cusid'] == "0"){
		$cust_data['surname'] = $inv['cusname'];
		$cust_data['addr1'] = $inv['cusaddr'];
		$cust_data['paddr1'] = $inv['cusaddr'];
	}

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
	$details = "";

	for ($i = 0; $i <= $page; $i++) {
		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$vattot = sprint ($vattot);
		$amttot = sprint ($amttot);

		$details .= "<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left' rowspan='2'><img src='compinfo/getimg.php' width=230 height=47></td>
					<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Credit Note</b></font></td>
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

				<tr>
					<td colspan='2'><b>Credit Note No:</b> $real_noteid</td>
				</tr>
				<tr>
					<td colspan='2'><b>Invoice No:</b> $inv[invnum]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Proforma Inv No:</b> $inv[docref]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Credit Note To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$cust_data[surname]</b></td>
					<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
					<td width='33%'><b>Delivery Address</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
					<td>&nbsp</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $inv[cusvatno]</td>
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
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000' align='right'><b>Unit Price</b></td>
					<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
				</tr>
				$products_out
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$remarks</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms:</b> $inv[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td nowrap><b>".CUR." $SUBTOT</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td nowrap><b>".CUR." $VAT</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td nowrap><b>".CUR." $TOTAL</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
				</tr>
				</tr>
			</table>
		</table>
		";
	}

	#fix teh date
	$date_arr = explode ("-",$date);
	$cdate = "$date_arr[2]-$date_arr[1]-$date_arr[0]";

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$OUTPUT = "
			<script>
				CostCenter('ct', 'Credit Note', '$cdate', 'Non Stock Credit Note No.$real_noteid', '".($TOTAL-$VAT)."', '');
			</script>
			$details";

		require("tmpl-print.php");
	} else {
		$OUTPUT = "
			<script>
				CostCenter('ct', 'Credit Note', '$cdate', 'Non Stock Credit Note No.$real_noteid', '".($TOTAL-$VAT)."', '');
				move(\"$template?noteid=$noteid&type=nonsnote\");
			</script>";
		require ("template.php");
	}
}


function custfCT($amount, $cusnum,$age)
{

		$odate = date("Y-m-d");

	db_connect();

	$amount = ($amount * (-1));

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' AND age='$age' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) < 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			$sql = "INSERT INTO custran(cusnum, odate, balance,div,age) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."','$age')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		$sql = "INSERT INTO custran(cusnum, odate, balance, div,age) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."','$age')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}


# vats
function vats($amt, $inc, $VATP){
	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "yes"){
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}else{
		$ret = ($amt);
	}

	return $ret;
}


?>

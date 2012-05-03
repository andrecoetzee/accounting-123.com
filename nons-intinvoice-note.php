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
function details($_GET, $errata = "")
{

	# Get vars
	extract ($_GET);

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
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);
	$currs = getSymbol($inv['fcid']);

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
	$sql = "SELECT *,(qty - rqty) as qty FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkd['accid']);
		$acc = pg_fetch_array($accRs);

		# put in product
		$products .= "
						<tr class='".bg_class()."'>
							<td align='center'>$i<input type='hidden' name='ids[]' value='$stkd[id]'></td>
							<td>$stkd[description]</td>
							<td><input type='hidden' name='oqtys[]' value='$stkd[qty]'><input type='text' size='4' name='qtys[]' value='$stkd[qty]'></td>
							<td>$inv[currency] $stkd[unitcost]</td>
							<td>$inv[currency] $stkd[amt]</td>
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

	# format date
	list($s_year, $s_month, $s_day) = explode("-", $inv['sdate']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Non-Stock Credit Note Details</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='invid' value='$invid'>
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
										<td>Customer Vat Number</td>
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
										<td valign='center'>".mkDateSelect("s",$s_year,$s_month,$s_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Foreign Currency</td>
										<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." $inv[xrate]</td>
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
										<th width='40%'>Quick Links</th>
										<th width='45%'>Remarks</th>
										<td rowspan='5' valign='top' width='15%'><br></td>
									</tr>
									<tr>
										<td class='".bg_class()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
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
										<td align='right'>$inv[currency] $inv[subtot]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right'>$inv[currency] $inv[vat]</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>$inv[currency] $inv[total]</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' value='Continue &raquo'></td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}


# confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$sdate = $s_year."-".$s_month."-".$s_day;
	if( !checkdate($s_month, $s_day, $s_year) ){
		$v->addError($sdate, "Invalid Date.");
	}
	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
//		is_int fails if the value is 1 ??? ctype_digit seems to work better
//		if (!is_int($qtys[$key])) {
		if (!ctype_digit($qtys[$key])) {
			$v->addError(0, "Invalid Item Quantity.");
		}
		if($qtys[$key] > $oqtys[$key]){
			$v->isOk ("##", "num", 1, 1, "Error : Item quantity cannot be more than invoiced quantity.");
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
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);
	$currs = getSymbol($inv['fcid']);

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
	$totamt = 0;
	foreach($ids as $key => $id){
		if($qtys[$key] < 1){
			continue;
		}
		$any = true;
		db_connect();
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		$stkacc = $stkd['accid'];
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkacc);
		$acc = pg_fetch_array($accRs);

		# Calculate amount
		$totamt += ($amt[$key] = ($qtys[$key] * $stkd['unitcost']));

		$any = true;
		$i++;
		# put in product
		$products .= "
			<tr class='".bg_class()."'>
				<td align='center'>$i<input type='hidden' name='ids[]' value='$stkd[id]'></td>
				<td>$stkd[description]</td>
				<td><input type='hidden' name='qtys[]' value='$qtys[$key]'>$qtys[$key]</td>
				<td>$inv[currency] $stkd[unitcost]</td>
				<td><input type='hidden' name='amts[]' value='$amt[$key]'>$inv[currency] $amt[$key]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}
	$products .= "</table>";

	# if there isn't any products
	if(!$any){
		$err = "<li class='err'> Error : There are no products selected.</li>";
		return details($_POST, $err);
		return "<li class='err'> Error : There are no products selected.</li>";
	}

/* --- Start Some calculations --- */

//  commented: 2006-04-25
// 	# calculate subtot
// 	if( isset($amt) ){
// 		$TOTAL = array_sum($amt);
// 	}else{
// 		$TOTAL = 0.00;
// 	}
//
// 	# if vat is not included
// 	$VATP = TAX_VAT;
// 	if($inv['chrgvat'] == "yes"){
// 		$SUBTOT = sprintf("%0.2f", $TOTAL * 100 / (100 + $VATP) );
// 	} elseif($inv['chrgvat'] == "no") {
// 		$SUBTOT = $TOTAL;
// 		$TOTAL = sprintf("%0.2f", $TOTAL * (100 + $VATP) /100 );
// 	}else{
// 		$SUBTOT = $TOTAL;
// 	}
//
// 	// compute the sub total (total - vat), done this way because the specified price already includes vat
// 	$VAT = sprint($TOTAL - $SUBTOT);

	// inclusive
	$SUBTOT = sprint($totamt);
	if ($inv["chrgvat"] == "yes") {
		$VAT = $totamt - ($totamt / (1 + TAX_VAT/100));
	} else {
		$VAT = $SUBTOT * TAX/100;
		$TOTAL = $SUBTOT + $VAT;
	}
	
	vsprint($VAT);
	vsprint($TOTAL);

/* --- End Some calculations --- */

	# Format date
	// list($syear, $smon, $sday) = explode("-", $inv['sdate']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Non-Stock Credit Note</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='invid' value=$invid>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign=top>
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
										<td>Customer Vat Number</td>
										<td valign='center'>$inv[cusvatno]</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr><th colspan='2'> Non-Stock Invoice Details </th></tr>
									<tr class='".bg_class()."'>
										<td>Non-Stock Invoice No.</td>
										<td valign='center'>$inv[invnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>".mkDateSelect("s",$s_year,$s_month,$s_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Foreign Currency</td>
										<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." $inv[xrate]</td>
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
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
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
										<td align='right'><input type='hidden' name='subtot' value='$SUBTOT'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right'><input type='hidden' name='vat' value='$VAT'>$inv[currency] $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'><input type='hidden' name='total' value='$totamt'>$inv[currency] $totamt</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' value='Confirm &raquo'></td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}


# Details
function write($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$sndate = mkdate($s_year, $s_month, $s_day);
	if( !checkdate($s_month, $s_day, $s_year) ){
		$v->addError($sdate, "Invalid Date.");
	}
	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
//		if (!is_int($qtys[$key])) {
		if (!ctype_digit($qtys[$key])){
			$v->addError(0, "Invalid Item Quantity.");
		}
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
		$err .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Update xrate
	cus_xrate_update($inv['fcid'], $inv['xrate']);
	xrate_update($inv['fcid'], $inv['xrate'], "invoices", "invid");
	xrate_update($inv['fcid'], $inv['xrate'], "custran", "id");

	db_connect();
# Begin updates
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$refnum = getrefnum();
/*refnum*/

	$real_noteid = divlastid('note', USER_DIV);
	db_connect();
	/* --- Start Products Display --- */
	$td = $sndate;
	# Products layout
	$products = "";
	foreach($ids as $key => $id){
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		$stkacc = $stkd['accid'];
		# keep records for transactions
// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($amts[$key], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($amts[$key], $inv['chrgvat']);
// 		}
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$stkd['vatex']="y";
		}

		$t=$inv['chrgvat'];

		$VATP = TAX_VAT;
		$stkacc = $stkd['accid'];
		# keep records for transactions
		if(isset($totstkamt[$stkacc])){

			if($stkd['vatex']=="y") {

				$totstkamt[$stkacc] +=$amts[$key];
				$va=0;
				$inv['chrgvat']="";
			} else {

				$totstkamt[$stkacc] += vats($amts[$key], $inv['chrgvat']);
				$va=sprint($stkd['amt']-vats($amts[$key], $inv['chrgvat']));
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
				$totstkamt[$stkacc] = vats($amts[$key], $inv['chrgvat']);
				$va=sprint($amts[$key]-vats($amts[$key], $inv['chrgvat']));
				if($inv['chrgvat']=="no") {
					$va=sprint($amts[$key]*$VATP/100);
				}
			}
		}

		$f=-vats($amts[$key], $inv['chrgvat']);
		$f=$f * $inv['xrate'];
		$va=$va* $inv['xrate'];

		vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid Customer $inv[cusname].",($f-$va),-$va);

		$inv['chrgvat']=$t;


		$sql = "UPDATE nons_inv_items SET rqty = (rqty + '$qtys[$key]') WHERE id = '$stkd[id]'";
		$sRslt = db_exec($sql);
		$products .= "
						<tr valign='top'>
							<td>$stkd[description]</td>
							<td>$qtys[$key]</td>
							<td>$inv[currency] $stkd[unitcost]</td>
							<td>$inv[currency] $amts[$key]</td>
						</tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($subtot);
	$VAT = sprint($vat);
	$TOTAL = sprint($total);

	$LVAT = sprint($VAT * $inv['xrate']);
	$LTOTAL = sprint($TOTAL * $inv['xrate']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT", "VAT");
	/* - End Hooks - */

	# todays date
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusid]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if(pg_numrows($custRslt) < 1){
		return "<li class='err'>Error : Customer not Found.</li>";
	}
	$cus = pg_fetch_array($custRslt);

	$na=$cus['surname'];

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}
	
	$wtot = array_sum($totstkamt) + $va;
	$lwtot = sprint($wtot * $inv['xrate']);
	$lva = sprint($va * $inv["xrate"]);

	$tpp=0;
	# record transaction  from data
	foreach($totstkamt as $stkacc => $wamt){
		writetrans($stkacc, $dept['debtacc'], $sndate, $refnum, $lwtot, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid Customer $inv[cusname].");
	}
	if($lva <> 0){
		writetrans($vatacc, $dept['debtacc'], $sndate, $refnum, $lva, "Non-Stock invoice No. $inv[invnum] Credit note No.$real_noteid Vat. Customer $inv[cusname].");
	}
	
	db_connect();
	# Record the payment on the statement
	$sql = "
		INSERT INTO stmnt 
			(cusnum, invid, amount, date, type, div, allocation_date) 
		VALUES 
			('$inv[cusid]', '$real_noteid', '-$wtot','$sndate', 'Non Stock Credit Note, for invoice $inv[invnum]', '".USER_DIV."', '$sndate')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Update the customer (make balance less)
	$sql = "UPDATE customers 
			SET balance = (balance - '$lwtot'::numeric(13,2)), 
				fbalance = (fbalance - '$wtot'::numeric(13,2)) WHERE cusnum = '$inv[cusid]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Make ledge record
	custledger($inv['cusid'], $dept['incacc'], $sndate, $real_noteid, "Non Stock Credit note $real_noteid", $lwtot, "c");
	frecordCT($wtot, $inv['cusid'], $inv['xrate'], $inv['fcid'], $sndate);
	// custCT($TOTAL, $inv['cusid']);

	db_connect();
	$sql = "UPDATE nons_invoices 
			SET balance = (balance - '$lwtot'::numeric(13,2)), 
				fbalance = (fbalance - '$wtot'::numeric(13,2)) WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

	# write note
	$sql = "INSERT INTO nons_inv_notes(invid, invnum, cusname, cusaddr, cusvatno, chrgvat, location, currency, date, subtot, vat, total, username, prd, notenum, ctyp, div)";
	$sql .= " VALUES('$inv[invid]', '$inv[invnum]', '$inv[cusname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[chrgvat]', 'int', '$inv[currency]', '$sndate', '".sprint($wtot-$va)."', $va, $wtot, '".USER_NAME."', '".PRD_DB."', '$real_noteid', '$inv[ctyp]', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	$noteid = pglib_lastid("nons_inv_notes", "noteid");

	# write note items
	foreach($ids as $key => $id){
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$nstk = pg_fetch_array($stkdRslt);

		$sql = "INSERT INTO nons_note_items(noteid, qty, description, amt, unitcost) 
				VALUES('$noteid', '$qtys[$key]', '$nstk[description]', '$amts[$key]', 
					'$nstk[unitcost]')";
		$stkdRslt = db_exec($sql);
	}

	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES('$sndate', '$noteid', '$real_noteid', '0', '$lva', '$lwtot', 'nnon', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	db_conn('cubit');

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusid]','$na','Credit note: $real_noteid, Non-stock International Invoice $inv[invnum] ','$sndate','".-sprint($lwtot-$lva)."','-$lva','".-sprint($lwtot)."','".USER_DIV."')";
	$Ri=db_exec($Sl);

# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* -- Format the remarks boxlet -- */
	$inv["remarks"] = "<table border=1><tr><td>Remarks:<br>$inv[remarks]</td></tr></table>";

	$cc = "<script> CostCenter('ct', 'Credit Note', '$sndate', 'Non Stock Credit Note No.$real_noteid', '".($LTOTAL-$LVAT)."', ''); </script>";

	/* -- Final Layout -- */
	$details = "
					$cc
					<center>
					<h2>Credit Note</h2>
					<table cellpadding='0' cellspacing='4' border='0' width='750'>
						<tr>
							<td valign='top' width='30%'>
								<table ".TMPL_tblDflts.">
									<tr>
										<td>$inv[cusname]</td>
									</tr>
									<tr>
										<td>".nl2br($inv['cusaddr'])."</td>
									</tr>
									<tr>
										<td>(Vat No. $inv[cusvatno])</td>
									</tr>
								</table>
							</td>
							<td valign='top' width='30%'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
								Reg No. ".COMP_REGNO."<br>
								Vat No. ".COMP_VATNO."
							</td>
							<td width='20%'><img src='compinfo/getimg.php' width=230 height=47></td>
							<td valign='bottom' align='right' width='20%'>
								<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
									<tr>
										<td><b>Credit Note No.</b></td>
										<td valign='center'>$real_noteid</td>
									</tr>
									<tr>
										<td><b>Invoice No.</b></td>
										<td valign='center'>$inv[invnum]</td>
									</tr>
									<tr>
										<td><b>Date</b></td>
										<td valign='center'>$sndate</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'>
								<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
									<tr>
										<th width='65%'>DESCRIPTION</th>
										<th width='10%'>QTY</th>
										<th width='10%'>UNIT PRICE</th>
										<th width='10%'>AMOUNT</th>
									<tr>
									$products
								</table>
							</td>
						</tr>
						<tr>
							<td>$inv[remarks]</td>
							<td align='right' colspan='3'>
								<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
									<tr>
										<td><b>SUBTOTAL</b></td>
										<td align='right'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr>
										<td><b>VAT @ ".TAX_VAT."%</b></td>
										<td align='right'>$inv[currency] $VAT</td>
									</tr>
									<tr>
										<th><b>GRAND TOTAL<b></th>
										<td align='right'>$inv[currency] $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
					</table>
					</center>";
	$OUTPUT = $details;

	require("tmpl-print.php");

}


# records for CT
function frecordCT($amount, $cusnum, $rate, $fcid, $odate = "")
{
	if($odate == "")
		$odate = date("Y-m-d");

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND fbalance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['fbalance'] > $amount){
					$lamount = ($amount * $rate);
					# Remove make amount less
					$sql = "UPDATE custran SET fbalance = (fbalance - '$amount'::numeric(13,2)), balance = (balance - '$lamount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0 ;
				}else{
					# remove small ones
					//if($dat['fbalance'] > $amount){
						$amount -= $dat['fbalance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					//}
				}
			}
		}
		if($amount > 0){
			$amount = ($amount * (-1));
			$lamount = ($amount * $rate);

			/* Make transaction record for age analysis */
			$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));
		$lamount = ($amount * $rate);

		/* Make transaction record for age analysis */
		$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

# vats
function vats($amt, $inc){
	# If vat is not included
	$VATP = TAX_VAT;
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

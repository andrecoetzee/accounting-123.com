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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = details($_POST);
	}
} else {
	$OUTPUT = details($_GET);
}

# Get templete
require("template.php");




# Details
function details($_GET)
{

	$showvat = TRUE;

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");

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

	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$inv['chrgvat'] = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$inv['chrgvat'] = "No";
	}else{
		$inv['chrgvat'] = "Non VAT";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>SERIAL NO.</th>
							<th>QTY RETURNED</th>
							<th>UNIT PRICE</th>
							<th>UNIT DISCOUNT</th>
							<th>AMOUNT</th>
						<tr>";

	# get selected stock in this invoice
	db_conn($prd);
	$sql = "SELECT *, (qty - noted) as qty FROM pinv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	if(pg_numrows($stkdRslt) < 1){
		return "<li> The are not items on the invoice, all items have been returned.</li>";
	}

	$taxex = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		if($stkd['qty'] == 0){
			continue;
		}

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

		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$taxex += ($stkd['amt']);
		}

		if($stk['stkid']==0) {
			$stk['stkdes']=$stkd['description'];
			$stk['stkid']=0;
			$stk['stkcod']="";
		}

		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# Put in product
		$products .= "
		<input type='hidden' name=sids[] value='$stkd[id]'>
		<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
		<tr class='".bg_class()."'>
			<td>$wh[whname]</td>
			<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
			<td>$stk[stkdes]</td>
			<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$stkd[serno]'>$stkd[serno]</td>
			<td><input type='hidden' size='4' name='qts[]' value='$stkd[qty]'><input type='text' size='4' name=qtys[] value='$stkd[qty]'></td>
			<td nowrap>".CUR." $stkd[unitcost]</td>
			<td><input type='hidden' size='4' name='disc[]' value='$stkd[disc]'>$stkd[disc] OR <input type='hidden' size='4' name=discp[] value='$stkd[discp]' maxlength='5'>$stkd[discp]%</td>
			<td nowrap>".CUR." ".sprint($stkd['amt'])."</td>
		</tr>";
	}
	$products .= "</table>";

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

	db_conn('cubit');
	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cash'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$data=pg_fetch_array($Ri);

		$pcash=$data['amount'];
	} else {
		$pcash=0;
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cheque'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$data=pg_fetch_array($Ri);

		$pcheque=$data['amount'];
	} else {
		$pcheque=0;
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit Card'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$data=pg_fetch_array($Ri);

		$pcc=$data['amount'];
	} else {
		$pcc=0;
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$data=pg_fetch_array($Ri);

		$pcredit=$data['amount'];
	} else {
		$pcredit=0;
	}

	if($inv['cusnum']==0) {
		// See if we got a value for the Customer input field
		if (!isset($client)) $client = $inv['cusname'];

		$cd = "
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td valign='center'><input type='text' size='20' name='client' value='$client'></td>
			</tr>";
		$pc = "<input type='hidden' name='pcredit' value='0'>";
	} else {

		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get cus data.");


		$cust=pg_fetch_array($Ri);

		$cd = "
			<tr class='".bg_class()."'>
				<td valign='center'>Customer Name</td>
				<td valign='center'>$cust[surname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer Address</td>
				<td valign='center'>".nl2br($cust['addr1'])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer VAT Number</td>
				<td>$cust[vatnum]</td>
			</tr>";
		$pcredit = sprint ($pcredit);
		$pc = "
			<tr class='".bg_class()."'>
				<td>Amount On Credit</td>
				<td><input size='12' type='text' name='pcredit' value='$pcredit'></td>
			</tr>";
	}

	if (!isset($showvat))
	$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$inv['rounding'] = sprint ($inv['rounding']);

	// Check rounding
	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rd = "
			<tr class='".bg_class()."'>
				<td>Rounding</td>
				<td align='right'>R $inv[rounding]</td>
			</tr>
			<tr class='".bg_class()."'>
				<th>Amount</th>
				<td align='right'>R $due</td>
			</tr>";

	} else {
		$rd="";
	}

	/* -- Final Layout -- */
	$details = "
	<center><h3>Credit Note</h3>
	<form action='".SELF."' method='POST'>
	<input type='hidden' name='key' value='confirm'>
	<input type='hidden' name='invid' value='$invid'>
	<input type='hidden' name='prd' value='$prd'>
	<table ".TMPL_tblDflts." width='95%'>
		<tr>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'> Customer Details </th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Department</td>
						<td valign='center'>$inv[deptname]</td>
					</tr>
					$cd
					<tr>
						<th colspan='2' valign='top'>Comments</th>
					</tr>
					<tr class='".bg_class()."'>
						<td colspan='2' align=center><textarea name='comm' rows='4' cols='20'>$inv[comm]</textarea></td>
					</tr>
				</table>
			</td>
			<td valign='top' align='right'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'> Invoice Details </th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Order No.</td>
						<td valign='center'>$inv[ordno]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>VAT Inclusive</td>
						<td valign='center'>$inv[chrgvat]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Terms</td>
						<td valign='center'>$termssel Days</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Sales Person</td>
						<td valign='center'>$inv[salespn]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Credit Note Date</td>
						<td valign='center'>".mkDateSelect("o")."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Trade Discount</td>
						<td valign='center'><input type='hidden' size='7' name='traddisc' value='$inv[traddisc]'>$inv[traddisc]%</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Delivery Charge</td>
						<td valign='center'><input type='hidden' name='dct' value='$dct'><input type='text' size='7' name='delchrg' value='$dct'></td></tr>
					<tr>
						<th colspan='2'>Payment Details </th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Amount Paid Cash</td>
						<td><input size='12' type='text' name='pcash' value='$pcash''></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Amount Paid Cheque</td>
						<td><input size='12' type='text' name='pcheque' value='$pcheque'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Amount Paid Credit Card</td>
						<td><input size='12' type='text' name='pcc' value='$pcc'></td>
					</tr>
					$pc
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
					<p>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='pos-invoice-new.php'>New POS Invoice</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='pos-invoice-list.php'>View POS Invoices</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>
			</td>
			<td align='right'>
				<table ".TMPL_tblDflts." width='80%'>
					<tr class='".bg_class()."'>
						<td>SUBTOTAL</td>
						<td align='right' nowrap>".CUR." $SUBTOT</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Trade Discount</td>
						<td align='right' nowrap>".CUR." $inv[discount]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Delivery Charge</td>
						<td align='right' nowrap>".CUR." $inv[delivery]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td><b>VAT $vat14</b></td>
						<td align='right' nowrap>".CUR." $VAT</td>
					</tr>
					<tr class='".bg_class()."'>
						<th>GRAND TOTAL</th>
						<td align='right' nowrap>".CUR." $TOTAL</td>
					</tr>
					$rd
				</table>
			</td>
		</tr>
		<tr><td></td></tr>
		<tr>
			<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
			<td><input type='submit' value='Confirm'></td>
		</tr>
	</table>
	</form>
	</center>";
	return $details;

}




# Error
function error($_GET, $err = "")
{

	$showvat = TRUE;

	# Get vars
	extract ($_GET);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");

	# Display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<TH>SERIAL NO.</TH>
							<th>QTY RETURNED</th>
							<th>UNIT PRICE</th>
							<th>UNIT DISCOUNT</th>
							<th>AMOUNT</th>
						<tr>";

	# Get selected stock in this invoice
	db_conn($prd);
	$sql = "SELECT *,(qty - noted) as qty FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$tcosamt = 0;
	$taxex = 0;
	while ($stkd = pg_fetch_array($stkdRslt)) {
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

		# cost amount
		$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
		$tcosamt += $cosamt;

		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$taxex += $stkd['amt'];
		}

		if($stk['stkid']==0) {
			$stk['stkdes']=$stkd['description'];
			$stk['stkid']=0;
			$stk['stkcod']="";
		}

		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# Put in product
		$products .= "
		<input type='hidden' name='sids[]' value='$stkd[id]'>
		<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
		<tr class='".bg_class()."'>
			<td>$wh[whname]</td>
			<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
			<td>$stk[stkdes]</td>
			<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$stkd[serno]'>$stkd[serno]</td>
			<td><input type='hidden' size='4' name='qts[]' value='$stkd[qty]'><input type='text' size='4' name='qtys[]' value='$stkd[qty]'></td>
			<td nowrap>".CUR." $stkd[unitcost]</td>
			<td><input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%</td>
			<td nowrap>".CUR." $stkd[amt]</td>
		</tr>";
	}
	$products .= "</table>";

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $terms);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = round((($inv['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

	$pcash+=0;
	$pcheque+=0;
	$pcc+=0;

	if($inv['chrgvat'] == "inc"){
		$inv['chrgvat'] = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$inv['chrgvat'] = "No";
	}else{
		$inv['chrgvat'] = "Non VAT";
	}

	if($inv['cusnum']==0) {

		if (!isset($client)) $client = $inv['cusname'];

		$cd = "
				<tr class='".bg_class()."'>
					<td>Customer</td>
					<td valign='center'><input type='text' size='20' name='client' value='$client'></td>
				</tr>";
		$pc="<input type='hidden' name='pcredit' value='0'>";
	} else {

		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get cus data.");

		$cust=pg_fetch_array($Ri);

		$cd = "
				<tr class='".bg_class()."'>
					<td valign='top'>Customer Address</td>
					<td valign='center'>".nl2br($cust['addr1'])."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer VAT Number</td>
					<td>$cust[vatnum]</td>
				</tr>";
		$pc = "
				<tr class='".bg_class()."'>
					<td>Amount On Credit</td>
					<td><input size='12' type='text' name='pcredit' value='$pcredit'></td>
				</tr>";
	}

	if (!isset($showvat))
	$showvat = TRUE;

	if ($showvat == TRUE) {
		$vat14 = AT14;
	} else {
		$vat14 = "";
	}

	$pcash = sprint ($pcash);
	$pcheque = sprint ($pcheque);
	$pcc = sprint ($pcc);


	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Credit Note</h3>
					<form action='".SELF."' method='post'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='invid' value='$invid'>
						<input type='hidden' name='prd' value='$prd'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td>$err</td>
						</tr>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$inv[deptname]</td>
									</tr>
									$cd
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr class='".bg_class()."'>
										<td colspan='2' align='center'><textarea name='comm' rows='4' cols='20'>$comm</textarea></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Invoice Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Order No.</td>
										<td valign='center'>$inv[ordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Charge VAT</td>
										<td valign='center'>$inv[chrgvat]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$termssel Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Sales Person</td>
										<td valign='center'>$inv[salespn]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Invoice Date</td>
										<td valign='center'>".mkDateSelect("o",$o_year,$o_month,$o_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td valign='center'><input type='hidden' size='7' name='traddisc' value='$inv[traddisc]'>$inv[traddisc]%</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td valign='center'><input type='hidden' name='dct' value='$dct'><input type='text' size='7' name='delchrg' value='$delchrg'></td>
									</tr>
									<tr>
										<th colspan='2'>Payment Details</th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Cash</td>
										<td><input size='12' type='text' name='pcash' value='$pcash'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Cheque</td>
										<td><input size='12' type='text' name='pcheque' value='$pcheque'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Credit Card</td>
										<td><input size='12' type=text name='pcc' value='$pcc'></td>
									</tr>
									$pc
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
									<p>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='pos-invoice-new.php'>New POS Invoice</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='pos-invoice-list.php'>View POS Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right' nowrap>".CUR." $inv[discount]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right' nowrap>".CUR." $inv[delivery]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT $vat14</b></td>
										<td align='right' nowrap>".CUR." $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td></td></tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td><input type='submit' value='Confirm'></td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}



# details
function confirm($_POST)
{

	$showvat = TRUE;

	# get vars
	extract ($_POST);

	$pcash+=0;
	$pcheque+=0;
	$pcc+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($o_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($o_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($o_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $o_day."-".$o_month."-".$o_year;
	if(!checkdate($o_month, $o_day, $o_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	if($delchrg > $dct){
		$v->isOk ($delchrg, "float", 0, 0, "Error : Delivery Charge amount must not be more than the amount in the Invoice.");
	}

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if (isset($qtys)) {
		foreach ($qtys as $keys => $qty) {
			if ($qtys[$keys] > $qts[$keys]) {
				$v->isOk ($qty, "float", 0, 0, "The Returned Quantity cannot be more than the quantity sold.");
			}
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($vatcode[$keys], "float", 0, 20, "Invalid vat code.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	} else {
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$stkid+=0;
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		# $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return error($_POST, $err);
	}



	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$vchrgvat = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$vchrgvat = "No";
	}else{
		$vchrgvat = "Non VAT";
	}

	/* --- Start Products Display --- */
	
	$vatamount = 0;

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<TH>SERIAL NO.</TH>
							<th>QTY RETURNED</th>
							<th>UNIT PRICE</th>
							<th>UNIT DISCOUNT</th>
							<th>AMOUNT</th>
						<tr>";

	$c = 0;
	$taxex = 0;
	foreach($qtys as $keys => $value) {
		if($qtys[$keys] > 0){

			if($stkids[$keys]!=0) {

				//if($stkids[$keys]
				db_connect();
				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# get selected stock in this invoice
				db_conn($prd);
				$sql = "SELECT * FROM pinv_items WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				# get warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Calculate the Discount discount
				if($disc[$keys] < 1){
					if($discp[$keys] > 0){
						$disc[$keys] = (($discp[$keys]/100) * $stkd['unitcost']);
					}
				}else{
					$discp[$keys] = (($disc[$keys] * 100) / $stkd['unitcost']);
				}

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

				db_connect();
				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd=pg_fetch_array($Ri);

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				//			# Check Tax Excempt
				//			if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
				//				$taxex += $amt[$keys];
				//			}

				if($vd['zero']=="Yes") {
					$excluding="y";
				} else {
					$excluding="";
				}

				$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs=explode("|",$vr);
				$ivat=$vrs[0];
				$iamount=$vrs[1];

				$vatamount += $ivat;

				if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

				$serial = $sers[$stk['stkid']][$keys];

				if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}

				# Put in product
				$products .= "
				<input type='hidden' name='sids[]' value='$stkd[id]'>
				<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
				<tr class='".bg_class()."'>
					<td>$wh[whname]</td>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
					<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>".sprint($discp[$keys],2)."%</td>
					<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." ".sprint($amt[$keys])."</td>
				</tr>";
				$c++;
			} else {
				//if($stkids[$keys]
				# get selected stock in this invoice
				$stkids[$keys]+=0;
				db_conn($prd);
				$sql = "SELECT * FROM pinv_items  WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				# Calculate the Discount discount
				if($disc[$keys] < 1){
					if($discp[$keys] > 0){
						$disc[$keys] = (($discp[$keys]/100) * $stkd['unitcost']);
					}
				}else{
					$discp[$keys] = (($disc[$keys] * 100) / $stkd['unitcost']);
				}

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

				db_connect();
				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd=pg_fetch_array($Ri);

				if($vd['zero']=="Yes") {
					$excluding="y";
				} else {
					$excluding="";
				}

				$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs=explode("|",$vr);
				$ivat=$vrs[0];
				$iamount=$vrs[1];

				$vatamount += $ivat;

				//			# Check Tax Excempt
				//			if($vd['zero']=="Yes"){
				//				$taxex += $amt[$keys];
				//			}

				//if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

				//$serial = $sers[$stk['stkid']][$keys];

				//if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}
				$serial="";

				$amt[$keys] = sprint ($amt[$keys]);

				# Put in product
				$products .="
				<input type='hidden' name='sids[]' value='$stkd[id]'>
				<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
				<tr class='".bg_class()."'>
					<td></td>
					<td><input type='hidden' name='stkids[]' value='0'></td>
					<td>$stkd[description]</td>
					<td><input type='hidden' name=sers[$stkd[stkid]][] value='$serial'>$serial</td>
					<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name=discp[] value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
					<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
				</tr>";
				$c++;
			}
		}
	}
	$products .= "</table>";



	if($c < 1){
		$err = "<li class=err>Please enter quantity.</li>";
		return error($_POST, $err);
	}

	/* calculate delivery charge vat */
	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri=db_exec($Sl);
	$vd = pg_fetch_array($Ri);

	$vr = vatcalc($delchrg, $inv['chrgvat'], $vd['zero'] == "Yes" ? "y" : "", $inv['traddisc'], $vd['vat_amount']);
	$vrs = explode("|", $vr);
	$ivat = $vrs[0];

	$vatamount += $ivat;

	$chrgvat=$inv['chrgvat'];

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
		//$VAT=sprint(($subtotal-$taxex)*$VATP/100);
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
		//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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


	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------

	db_conn('cubit');
	$Sl="SELECT * FROM posround";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	$pcash = sprint ($pcash);
	$pcheque = sprint ($pcheque);
	$pcc = sprint ($pcc);
	$pcredit = sprint ($pcredit);

	if($data['setting']=="5cent") {
		if(sprint(floor(sprint($TOTAL/0.05)))!=sprint($TOTAL/0.05)) {
			$otot=$TOTAL;
			$nTOTAL=sprint(sprint(floor($TOTAL/0.05))*0.05);
			$rounding=sprint($otot-$nTOTAL);

			$ptot=$inv['total'];
			$pnTOTAL=sprint(sprint(floor($inv['total']/0.05))*0.05);
			$prounding=sprint($ptot-$pnTOTAL);
			if($prounding>0) {
				$nTOTAL=$TOTAL;
				$rounding = $prounding;
			}

		} else {
			$rounding=0;
		}
	} else {
		$rounding=0;
	}

	vsprint($pcash);
	vsprint($pcheque);
	vsprint($pcc);
	vsprint($pcredit);

	if(sprint($pcash+$pcheque+$pcc+$pcredit)!=sprint($TOTAL-$rounding)) {
		return error($_POST, "<li class=err>The payments are not equal to the Grand Total</li>");
	}

	if($rounding>0) {
		$due=sprint($TOTAL-$rounding);
		$rd = "
				<tr class='".bg_class()."'>
					<td>Rounding</td>
					<td align='right'>R $rounding</td>
				</tr>
				<tr class='".bg_class()."'>
					<th>Amount</th>
					<td align='right'>R $due</td>
				</tr>";

	} else {
		$rd = "";
	}

	//<tr class='".bg_class()."'><td>Customer</td><td valign='center'>$inv[cusname] $inv[surname]</td></tr>


	if($inv['cusnum']==0) {
		if (!isset($client)) $client = "$inv[cusname] $inv[surname]";
		$cd="
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td valign='center'><input type='text' size='20' name='client' value='$client'></td>
			</tr>";
	} else {

		db_conn('cubit');

		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get cus data.");
		$cust=pg_fetch_array($Ri);

		$cd = "
			<tr class='".bg_class()."'>
				<td valign='center'>Customer Name</td>
				<td valign='center'>$cust[surname]</td>
			<tr class='".bg_class()."'>
				<td valign='top'>Customer Address</td>
				<td valign='center'>".nl2br($cust['addr1'])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer VAT Number</td>
				<td>$cust[vatnum]</td>
			</tr>";
	}

	if (!isset($showvat))
	$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}


	$pcash = sprint ($pcash);
	$pcheque = sprint ($pcheque);
	$pcc = sprint ($pcc);
	$pcredit = sprint ($pcredit);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Credit Note</h3>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='invid' value='$invid'>
						<input type='hidden' name='prd' value='$prd'>
						<input type='hidden' name='rounding'  value='$rounding'>
						<input type='hidden' name='o_day' value='$o_day'>
						<input type='hidden' name='o_month' value='$o_month'>
						<input type='hidden' name='o_year' value='$o_year'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$inv[deptname]</td>
									</tr>
									$cd
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr class='".bg_class()."'>
										<td colspan='2' align='center'><input type='hidden' name='comm' value='$comm'>".nl2br($comm)."</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Invoice Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Order No.</td>
										<td valign='center'>$inv[ordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$vchrgvat</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'><input type='hidden' size='7' name='terms' value='$terms'>$terms Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Sales Person</td>
										<td valign='center'>$inv[salespn]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Invoice Date</td>
										<td valign='center'><input type='hidden' name='odate' value='$odate'>$odate</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td valign='center'><input type='hidden' size='7' name='traddisc' value='$traddisc'>$traddisc%</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td valign='center'><input type='hidden' size='7' name='delchrg' value='$delchrg'>$delchrg</td>
									</tr>
									<tr>
										<th colspan='2'>Payment Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Cash</td>
										<td><input type='hidden' name='pcash' value='$pcash'>$pcash</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Cheque</td>
										<td><input type='hidden' name='pcheque' value='$pcheque'>$pcheque</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Credit Card</td>
										<td><input type='hidden' name='pcc' value='$pcc'>$pcc</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Amount Paid Credit</td>
										<td><input type='hidden' name='pcredit' value='$pcredit'>$pcredit</td>
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
									<p>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='pos-invoice-new.php'>New POS Invoice</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='pos-invoice-list.php'>View POS Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'><input type='hidden' name='SUBTOT' value='$SUBTOT'>".CUR." $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right'>".CUR." $traddiscmt</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>".CUR." $delexvat</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT $vat14</b></td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." $TOTAL</td>
									</tr>
									$rd
								</table>
							</td>
						</tr>
						<tr><td></td></tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td><input type='submit' value='Write'></td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}



# details
function write($_POST)
{

	# get vars
	extract ($_POST);

	$rounding+=0;
	$pcredit+=0;
	$vatamount = 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($odate, "date", 1, 14, "Invalid Invoice note date.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# Check stkids[]
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.$stkid");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# Check amt[]
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Amount, please enter all details.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return error($_POST, $err);
	}



	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
	/* -------------------------------- */
	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	if($rounding>0) {
		db_conn('core');
		$Sl="SELECT * FROM salesacc WHERE name='rounding'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please set the rounding account, under sales settings.";
		}

		$ad=pg_fetch_array($Ri);

		$rac=$ad['accnum'];

	}

	$notenum = divlastid('note', USER_DIV);

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$taxex = 0;
	foreach($qtys as $keys => $value){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT * FROM pinv_items  WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		#check serial number
		if(strlen($stkd['ss']) > 0){
			$me = $stkd['ss'];
		}else {
			$me = $stkd['serno'];
		}
		#determine which table to connect to
		switch (substr($me,(strlen($me)-1),1)) {
			case "0":
				$tab="ss0";
				break;
			case "1":
				$tab="ss1";
				break;
			case "2":
				$tab="ss2";
				break;
			case "3":
				$tab="ss3";
				break;
			case "4":
				$tab="ss4";
				break;
			case "5":
				$tab="ss5";
				break;
			case "6":
				$tab="ss6";
				break;
			case "7":
				$tab="ss7";
				break;
			case "8":
				$tab="ss8";
				break;
			case "9":
				$tab="ss9";
				break;
			default:
				$tab = "ss0";
		}
		db_connect ();
		$upd = "UPDATE $tab SET active = 'yes' WHERE code = '$stkd[ss]' OR code = '$stkd[serno]'";
		$run_upd = db_exec($upd) or errDie("Unable to update stock serial numbers");

		# get warehouse name
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# Calculate the Discount discount
		if($disc[$keys] < 1){
			if($discp[$keys] > 0){
				$disc[$keys] = (($discp[$keys]/100) * $stkd['unitcost']);
			}
		}else{
			$discp[$keys] = (($disc[$keys] * 100) / $stkd['unitcost']);
		}

		# Calculate amount
		$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

		db_connect();
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
		$vrs=explode("|",$vr);
		$ivat=$vrs[0];
		$iamount=$vrs[1];

		$vatamount += $ivat;

		# Check Tax Excempt
		if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
			$taxex += $amt[$keys];
		}

		if($stkd['account']!=0) {
			# put in product
			$products .= "
			<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
			<tr>
				<td colspan='2'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stkd[description]</td>
				<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
			</tr>";
		} else {

			# put in product
			$products .= "
			<input type='hidden' name='vatcode[]' value='$stkd[vatcode]' />
			<tr>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
			</tr>";
		}
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* calculate delivery charge vat */
	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri=db_exec($Sl);
	$vd = pg_fetch_array($Ri);

	$vr = vatcalc($delchrg, $inv['chrgvat'], $vd['zero'] == "Yes" ? "y" : "", $inv['traddisc'], $vd['vat_amount']);
	$vrs = explode("|", $vr);
	$ivat = $vrs[0];

	$vatamount += $ivat;

	/* --- ----------- Clac ---------------------

	# calculate subtot
	$SUBTOT = 0.00;
	if(isset($amt))
	$SUBTOT = array_sum($amt);

	$SUBTOT -= $taxex;

	# duplicate
	$SUBTOTAL = $SUBTOT;

	$VATP = TAX_VAT;
	if($inv['chrgvat'] == "exc"){
	$SUBTOTAL = $SUBTOTAL;
	$delexvat= ($delchrg);
	}elseif($inv['chrgvat'] == "inc"){
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
	$traddiscm = $traddiscmt;

	if($inv['chrgvat'] != "nov"){
	$VAT = sprint($EXVATTOT * ($VATP/100));
	}else{
	$VAT = 0;
	}

	$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
	$SUBTOT += $taxex;

	/* --- ----------- Clac --------------------- */

	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$chrgvat=$inv['chrgvat'];

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
		//$VAT=sprint(($subtotal-$taxex)*$VATP/100);
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
		//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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

	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------

	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if($inv['balance']>=$TOTAL) {
		$invpay=$TOTAL;
		$examt=0;
	} else {
		$invpay=$inv['balance'];
		$examt=($TOTAL-$invpay);
	}

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT", "VAT");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
	/*refnum*/

	# insert invoice to period DB
	if($inv['cusnum'] != "0"){
		#then get the actual customer
		db_connect ();
		$get_cus = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
		$run_cus = db_exec($get_cus) or errDie("Unable to get customer information");
		if(pg_numrows($run_cus) < 1){
			#do nothing
		}else {
			$carr = pg_fetch_array($run_cus);
			$inv['cusname'] = "$carr[cusname]";
			$inv['surname'] = "$carr[surname]";
		}
	}

	db_conn($prd);
	# Format date
	$odate = explode("-", $odate);
	$rodate = $odate[2]."-".$odate[1]."-".$odate[0];
	$td=$rodate;

	# Insert invoice credit note to DB
	$sql = "INSERT INTO inv_notes(deptid, notenum, invnum, invid, cusnum, cordno, ordno,
				chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, comm,
				username, div, surname, cusaddr, cusvatno, telno, deptname, prd)";
	$sql .= " VALUES('$inv[deptid]', '$notenum', '$inv[invnum]', '$inv[invid]',
				'$inv[cusnum]', '$inv[cordno]', '$inv[ordno]', '$inv[chrgvat]',
				'$terms', '$traddiscmt', '$inv[salespn]', '$rodate', '$delexvat',
				'$SUBTOT', '$VAT' , '$TOTAL', '$comm', '".USER_NAME."', '".USER_DIV."',
				'$inv[cusname] $inv[surname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[telno]',
				'$inv[deptname]', $inv[prd])";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	$invnum=$inv['invnum'];

	# Get next ordnum
	$noteid = pglib_lastid ("inv_notes", "noteid");

	db_conn($prd);
	# Begin updating

	$nbal = ($inv['nbal'] + $TOTAL);

	# Update the invoice (make balance less)
	$sql = "UPDATE pinvoices SET nbal = '$nbal', rdelchrg = (rdelchrg + '$delchrg'), balance = balance - '$invpay' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$av=$VAT;
	$at=$TOTAL-$VAT;
	/*
	$inv['pcash']=$pcash;
	$inv['pcheque']=$pcheque;
	$inv['pcc']=$pcc;
	$inv['pcredit']=$pcredit;*/

	$sd=date("Y-m-d");

	db_conn('cubit');
	$Sl="SELECT * FROM payrec WHERE inv='$invnum'";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	$user=$data['by'];

	$ro=$rounding;
	$ro+=0;
	$nsp=0;
	# Commit updating
	$inv['pcash']=$pcash;
	$inv['pcheque']=$pcheque;
	$inv['pcc']=$pcc;
	$inv['pcredit']=$pcredit;

	$pcreditback=$pcredit;

	# Make ledge record
	//custledger($inv['cusnum'], $dept['incacc'], $td, $notenum, "Credit Note No. $notenum for invoice No. $inv[invnum]", $TOTAL, "c");
	$commision=0;
	if($examt>0) {
		# Make record for age analisys
		//custCTP($examt, $inv['cusnum'],$td);
	}
	$discs = 0;
	$salesp = qrySalesPersonN($inv["salespn"]);
	foreach($qtys as $keys => $value){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT * FROM pinv_items  WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		//print $sql;
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		$stkd['account']+=0;

		if ($stkd['account']==0) {
			# Keep track of discounts
			$discs += ($stkd['disc'] * $stkd['qty']);

			db_connect();

			$Sl="SELECT * FROM scr WHERE inv='$inv[invnum]' AND stkid='$stkd[stkid]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)>0) {
				$cd=pg_fetch_array($Ri);

				$stk['csprice']=$cd['amount'];
			} else {
				$stk["csprice"] = 0;
			}

			# cost amount
			if ($stk['csprice'] == "0.00") {
				$cosamt = round(($qtys[$keys] * $stk['lcsprice']), 2);
			} else {
				$cosamt = round(($qtys[$keys] * $stk['csprice']), 2);
			}

			db_connect();
			# Update stock(onhand + qty)
			$sql = "UPDATE stock SET csamt = (csamt + '$cosamt'), units = (units + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			
			# fix stock cost amount
			$Sl="UPDATE stock set csprice=csamt/units WHERE stkid = '$stkids[$keys]' AND units>0";
			$Ri=db_exec($Sl) or errDie("Unable to update stock cost price in Cubit.",SELF);

			if($stk['serd'] == 'yes')
				ext_InSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $notenum, 'note',$td);

			# negetive values to minus profit
			$nqty = ($qtys[$keys] * (1));
			$namt = ($amt[$keys] * (-1));
			$ncsprice = ($cosamt * (-1));

			$noted = ($stkd['noted'] + $qtys[$keys]);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			stockrec($stkd['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $td, $nqty, $cosamt, "Credit note for Customer : $inv[surname] - Credit note No. $notenum");
			db_connect();

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $amt[$keys];

			###################VAT CALCS#######################

			$Sl="SELECT * FROM cubit.vatcodes WHERE id='$stk[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			if($stk['exvat'] == 'yes'||$vd['zero']=="Yes") {
				$excluding="y";
			} else {
				$excluding="";
			}

			$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd["vat_amount"]);
			$vrs=explode("|",$vr);
			$ivat=$vrs[0];
			$iamount=$vrs[1];

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[surname]",-$iamount,-$ivat);

			####################################################

			$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
				VALUES('$td', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'note', '$qtys[$keys]', '$amtexvat', '$cosamt', 'Credit note for Customer : $inv[surname] - Credit note No. $notenum', '".USER_DIV."')";
			$recRslt = db_exec($sql);
			
			if ($salesp["com"] > 0) {
				$itemcommission = $salesp['com'];
			} else {
				$itemcommission = $stk["com"];
			}

			$commision=$commision+coms($inv['salespn'],$amt[$keys] ,$itemcommission);

			# Get selected stock in this invoice
			db_conn($prd);
			$sql = "UPDATE pinv_items SET noted = '$noted' WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);


			# get accounts
			db_conn("exten");
			$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);
			$stockacc = $wh['stkacc'];
			$cosacc = $wh['cosacc'];

			# dt(stock) ct(cos)
			writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum.");

			db_conn($prd);
			# insert invoice items
			$sql = "INSERT INTO inv_note_items(noteid, whid, stkid, qty, amt, div, vatcode) 
					VALUES('$noteid', '$stkd[whid]', '$stkids[$keys]', '$qtys[$keys]', 
						'$amt[$keys]', '".USER_DIV."', '$vatcode[$keys]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();
			$date = date("Y-m-d");
			$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
			VALUES('$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nstk', '".USER_DIV."')";
			$recRslt = db_exec($sql);

		} else {
			db_connect();

			###################VAT CALCS#######################
			$noted = ($stkd['noted'] + $qtys[$keys]);
			db_conn($prd);
			$sql = "UPDATE pinv_items SET noted = '$noted' WHERE id = '$sids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);


			db_conn($prd);
			# insert invoice items
			$sql = "INSERT INTO inv_note_items(noteid, whid, stkid, qty, amt, div,description, vatcode) 
					VALUES('$noteid', '$stkd[vatcode]', '$stkids[$keys]', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."', '$stkd[description]', '$vatcode[$keys]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			if($vd['zero']=="Yes") {
				$excluding="y";
			} else {
				$excluding="";
			}
			
			$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'], $vd["vat_amount"]);
			$vrs=explode("|",$vr);
			$ivat=$vrs[0];
			$iamount=$vrs[1];
			$av-=$ivat;
			$at-=$iamount;

			vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

			####################################################

			$amtexvat = sprint($stkd['amt']);
			db_connect();
			$sdate = date("Y-m-d");

			$nsp+=sprint($iamount-$ivat);
			
			if ($salesp["com"] > 0) {
				$itemcommission = $salesp['com'];
			} else {
				$itemcommission = 0;
			}

			$commision = $commision+coms($inv['salespn'],$amt[$keys] ,$itemcommission);

			// 				//writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
			// 				writetrans($dept['debtacc'], $stkd['account'],$inv['odate'], $refnum, ($iamount-$ivat), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

			db_connect();
			$date = date("Y-m-d");
			$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
			VALUES('$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nnon', '".USER_DIV."')";
			$recRslt = db_exec($sql);

			if($inv['pcash']>0) {
				$min=$ro;
				$inv['pcash']+=$ro;
				$ro=0;
				//$amount=$inv['pcash'];
				if($inv['pcash']>=$ivat) {
					writetrans($vatacc, $dept['pca'], $td, $refnum, $ivat, "VAT Returned for Credit note No. $notenum");
					$inv['pcash']=sprint($inv['pcash']-$ivat);
					$ivat=0;
					if($inv['pcash']>0) {
						if($inv['pcash']>=$iamount) {
							writetrans($stkd['account'] , $dept['pca'],$td, $refnum, $iamount, "Sales for Credit note No. $notenum");
							$inv['pcash']=sprint($inv['pcash']-$iamount);
							$iamount=0;
						} elseif($inv['pcash']<$iamount) {
							writetrans($stkd['account'] , $dept['pca'],$td, $refnum,$inv['pcash'] , "Sales for Credit note No. $notenum");
							$iamount=sprint($iamount-$inv['pcash']);
							$inv['pcash']=0;
						}
					}
				} else {
					writetrans($vatacc, $dept['pca'], $td, $refnum, $inv['pcash'], "VAT Returned for Credit note No. $notenum");
					$ivat=sprint($ivat-$inv['pcash']);
					$inv['pcash']=0;
				}

				// 					db_conn('cubit');
				//
				// 					$inv['pcash']-=$min;
				//
				// 					$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$inv[pcash]','Cash','".PRD_DB."','0')";
				// 					$Ri=db_exec($Sl) or errDie("Unable to insert data.");
			}


			if($inv['pcheque']>0) {
				$min=$ro;
				$inv['pcheque']+=$ro;
				$ro=0;
				//$amount=$inv['pcash'];
				if($inv['pcheque']>=$ivat) {
					writetrans($vatacc, $dept['pca'], $td, $refnum, $ivat, "VAT Returned for Credit note No. $notenum");
					$inv['pcheque']=sprint($inv['pcheque']-$ivat);
					$ivat=0;
					if($inv['pcheque']>0) {
						if($inv['pcheque']>=$iamount) {
							writetrans($stkd['account'] , $dept['pca'],$td, $refnum, $iamount, "Sales for Credit note No. $notenum");
							$inv['pcheque']=sprint($inv['pcheque']-$iamount);
							$iamount=0;
						} elseif($inv['pcheque']<$iamount) {
							writetrans($stkd['account'] , $dept['pca'],$td, $refnum,$inv['pcheque'] , "Sales for Credit note No. $notenum");
							$iamount=sprint($iamount-$inv['pcheque']);
							$inv['pcheque']=0;
						}
					}
				} else {
					writetrans( $vatacc, $dept['pca'],$td, $refnum, $inv['pcheque'], "VAT Returned for Credit note No. $notenum");
					$ivat=sprint($ivat-$inv['pcheque']);
					$inv['pcheque']=0;
				}

				db_conn('cubit');
				$inv['pcash']-=$min;
				$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','-$inv[pcash]','Cash','".PRD_DB."','$noteid')";
				$Ri=db_exec($Sl) or errDie("Unable to insert data.");
			}


			if($inv['pcc']>0) {
				db_conn('core');
				$Sl="SELECT * FROM salacc WHERE name='cc'";
				$Ri=db_exec($Sl);
				if(pg_num_rows($Ri)<1) {
					return "Please set a link for the POS credit card control account";
				}
				$cd=pg_fetch_array($Ri);
				$cc=$cd['accnum'];
				$min=$ro;
				$inv['pcc']+=$ro;
				$ro=0;
				//$amount=$inv['pcash'];
				if($inv['pcc']>=$ivat) {
					writetrans($vatacc, $cc, $td, $refnum, $ivat, "VAT Returned for Credit note No. $notenum");
					$inv['pcc']=sprint($inv['pcc']-$ivat);
					$ivat=0;
					if($inv['pcc']>0) {
						if($inv['pcc']>=$iamount) {
							writetrans($stkd['account'] , $cc,$td, $refnum, $iamount, "Sales for Credit note No. $notenum");
							$inv['pcc']=sprint($inv['pcc']-$iamount);
							$iamount=0;
						} elseif($inv['pcc']<$iamount) {
							writetrans($stkd['account'] , $cc,$td, $refnum,$inv['pcc'] , "Sales for Credit note No. $notenum");
							$iamount=sprint($iamount-$inv['pcc']);
							$inv['pcc']=0;
						}
					}
				} else {
					writetrans($vatacc, $cc, $td, $refnum, $inv['pcc'], "VAT Returned for Credit note No. $notenum");
					$ivat=sprint($ivat-$inv['pcc']);
					$inv['pcc']=0;
				}

				db_conn('cubit');
				$inv['pcash']-=$min;
				$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','-$inv[pcash]','Cash','".PRD_DB."','$noteid')";
				$Ri=db_exec($Sl) or errDie("Unable to insert data.");
			}


			if($inv['pcredit']>0) {
				db_conn('core');
				$min=$ro;
				$inv['pcredit']+=$ro;
				$ro=0;
				//$amount=$inv['pcash'];
				if($inv['pcredit']>=$ivat) {
					writetrans($vatacc, $dept['debtacc'], $td, $refnum, $ivat, "VAT Returned for Credit note No. $notenum");
					$inv['pcredit']=sprint($inv['pcredit']-$ivat);
					$ivat=0;
					if($inv['pcredit']>0) {
						if($inv['pcredit']>=$iamount) {
							writetrans($stkd['account'] , $dept['debtacc'],$td, $refnum, $iamount, "Sales for Credit note No. $notenum");
							$inv['pcredit']=sprint($inv['pcredit']-$iamount);
							$iamount=0;
						} elseif($inv['pcredit']<$iamount) {
							writetrans($stkd['account'] , $dept['debtacc'],$td, $refnum,$inv['pcredit'] , "Sales for Credit note No. $notenum");
							$iamount=sprint($iamount-$inv['pcredit']);
							$inv['pcredit']=0;
						}
					}
				} else {
					writetrans($vatacc, $dept['debtacc'], $td, $refnum, $inv['pcredit'], "VAT Returned for Credit note No. $notenum");
					$ivat=sprint($ivat-$inv['pcredit']);
					$inv['pcredit']=0;
				}

				db_conn('cubit');
				$inv['pcash']-=$min;
				$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','-$inv[pcash]','Cash','".PRD_DB."','$noteid')";
				$Ri=db_exec($Sl) or errDie("Unable to insert data.");
			}

		}
	}

	db_connect();
	# save invoice discount
	$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div) VALUES('$inv[cusnum]', '$invid', '0', '-$discs', '$inv[odate]', '0', '".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	/* - Start Transactoins - */

	###################VAT CALCS#######################

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE del='Yes'";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)<1) {
		$Sl="SELECT * FROM vatcodes";
		$Ri=db_exec($Sl);
	}

	$vd=pg_fetch_array($Ri);

	$excluding="";

	$vr=vatcalc($delexvat,$inv['chrgvat'],$excluding,$inv['traddisc'], $vd["vat_amount"]);
	$vrs=explode("|",$vr);
	$ivat=$vrs[0];
	$iamount=$vrs[1];

	vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note No. $notenum, Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

	####################################################

	/*
	# dt(income) ct(debtors)
	writetrans($dept['pia'], $dept['pca'], $td, $refnum, ($TOTAL-$VAT), "Debtors Control for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	# dt(vat) ct(debtors)
	writetrans($vatacc, $dept['pca'], $td, $refnum, $VAT, "VAT Return for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");
	*/

//	db_connect();
//	$date = date("Y-m-d");
//	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
//	VALUES('$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$VAT', '$TOTAL', 'nstk', '".USER_DIV."')";
//	$recRslt = db_exec($sql);

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusnum]','$inv[surname]','Credit Note:$notenum, POS Invoice $inv[invnum]','$rodate','".-sprint($TOTAL-$VAT)."','-$VAT','".-sprint($TOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);


	// 	$av=$VAT;
	// 	$at=$TOTAL-$VAT;
	//
	// 	$inv['pcash']=$pcash;
	// 	$inv['pcheque']=$pcheque;
	// 	$inv['pcc']=$pcc;
	// 	$inv['pcredit']=$pcredit;
	//
	// 	$sd=date("Y-m-d");
	//
	// 	db_conn('cubit');
	// 	$Sl="SELECT * FROM payrec WHERE inv='$invnum'";
	// 	$Ri=db_exec($Sl);
	//
	// 	$data=pg_fetch_array($Ri);
	//
	// 	$user=$data['by'];
	//
	// 	$ro=$rounding;
	// 	$ro+=0;

	if($inv['pcash']>0) {
		$min=$ro;
		$inv['pcash']+=$ro;
		$ro=0;
		$amount=$inv['pcash'];
		if($amount>=$av) {
			writetrans($vatacc, $dept['pca'], $td, $refnum, $av, "VAT Returned for POS Credit note: $notenum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['pia'], $dept['pca'], $td, $refnum, $amount, "Sales for POS Credit note: $notenum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($vatacc, $dept['pca'], $td, $refnum, $amount, "VAT Returned for POS Credit note: $notenum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');
		$inv['pcash']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','$user','$invnum','-$inv[pcash]','Cash','".PRD_DB."','$noteid')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcheque']>0) {
		$min=$ro;
		$inv['pcheque']+=$ro;
		$ro=0;
		$amount=$inv['pcheque'];
		if($amount>=$av) {
			writetrans($vatacc, $dept['pca'], $td, $refnum, $av, "VAT Returned for POS Credit note: $notenum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans( $dept['pia'], $dept['pca'],$td, $refnum, $amount, "Sales for POS Credit note: $notenum.");
				$at=$at-$amount;
			}
		} else {
			writetrans( $vatacc, $dept['pca'],$td, $refnum, $amount, "VAT Returned for POS Credit note: $notenum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');
		$inv['pcheque']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','$user','$invnum','-$inv[pcheque]','Cheque','".PRD_DB."','$noteid')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcc']>0) {
		db_conn('core');
		$Sl="SELECT * FROM salacc WHERE name='cc'";
		$Ri=db_exec($Sl);
		if(pg_num_rows($Ri)<1) {
			return "Please set a link for the POS credit card control account";
		}
		$cd=pg_fetch_array($Ri);
		$cc=$cd['accnum'];
		$min=$ro;
		$inv['pcc']+=$ro;
		$ro=0;
		$amount=$inv['pcc'];
		if($amount>=$av) {
			writetrans( $vatacc, $cc,$td, $refnum, $av, "VAT Returned for POS Credit note: $notenum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['pia'], $cc, $td, $refnum, $amount, "Sales for POS Credit note: $notenum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($vatacc, $cc, $td, $refnum, $amount, "VAT Returned for POS Credit note: $notenum.");
			$av=$av-$amount;
			$amount=0;
		}
		db_conn('cubit');
		$inv['pcc']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','-$inv[pcc]','Credit Card','".PRD_DB."','$noteid')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcredit']>0) {
		db_conn('core');
		$cc=$dept['debtacc'];
		$min=$ro;
		$inv['pcredit']+=$ro;
		$ro=0;
		$amount=$inv['pcredit'];
		if($amount>=$av) {
			writetrans( $vatacc, $cc,$td, $refnum, $av, "VAT Returned for POS Credit note: $notenum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['pia'], $cc, $td, $refnum, $amount, "Sales for POS Credit note: $notenum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($vatacc, $cc, $td, $refnum, $amount, "VAT Returned for POS Credit note: $notenum.");
			$av=$av-$amount;
			$amount=0;
		}
		db_conn('cubit');
		$inv['pcc']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','-$inv[pcredit]','Credit','".PRD_DB."','$noteid')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['rounding']>0) {
		if($inv['pcash']>0) {
			writetrans($dept['pca'], $rac,$td, $refnum, $inv['rounding'], "Rounding  on Credit note: $notenum.");
		} elseif($inv['pcheque']>0) {
			writetrans($dept['pca'], $rac,$td, $refnum, $inv['rounding'], "Rounding on Credit note: $notenum.");
		} elseif($inv['pcc']>0) {
			writetrans($cc, $rac,$td, $refnum, $inv['rounding'], "Rounding on Credit note: $notenum.");
		}elseif($inv['pcredit']>0) {
			writetrans($dept['debtacc'], $rac,$td, $refnum, $inv['rounding'], "Rounding on Credit note: $notenum.");
		}
	}

	com_invoice($inv['salespn'],-($TOTAL-$VAT),-$commision,$invnum,$td);

	db_conn('cubit');



	$Sl="INSERT INTO pr(userid,username,amount,pdate,inv,cust,t) VALUES ('".USER_ID."','".USER_NAME."','-$TOTAL','$td','$invnum','$inv[cusname]','$inv[terms]')";
	$Ry=db_exec($Sl) or errDie("Unable to insert pos record.");

	if($rounding>0) {

		$Sl="INSERT INTO pcnc (note,amount) VALUES ('$notenum','$rounding')";
		$Ri=db_exec($Sl);

	}

	$inv['pcredit']=$pcreditback;

	if($inv['cusnum']>0&&$inv['pcredit']>0) {
		$nt=$inv['pcredit'];
		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, docref, amount, date, type, div, allocation_date) 
			VALUES 
				('$inv[cusnum]', '$invnum', '0', '-$nt', '$inv[odate]', 'Credit Note $notenum', '".USER_DIV."', '$inv[odate]')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance - '$nt') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update the invoice (make balance less)
		$sql = "UPDATE open_stmnt SET balance = balance-'$pcreditback' WHERE invid = '$inv[invnum]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);


		custledger($inv['cusnum'], $dept['incacc'], $inv['odate'], $invnum, "Credit note $notenum", $nt, "c");

		//print $nt;exit;

		recordCT($nt, $inv['cusnum'],$inv['odate']);



	}

	pglib_transaction("COMMIT");
	//die("<br /><br />NOTE: TRANSACTION ROLLBACK!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");

	/* - End Transactoins - */

	/* -- Final Layout -- */
	$details = "
					<center>
					<h2>Credit Note</h2>
					<table cellpadding='0' cellspacing='4' border=0 width='750'>
						<tr>
							<td valign='top' width='30%'>
								<table ".TMPL_tblDflts.">
									<tr>
										<td>$inv[surname]</td>
									</tr>
								</table>
							</td>
							<td valign='top' width='25%'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
							</td>
							<td width='20%'>
								<img src='compinfo/getimg.php' width='230' height='47'>
							</td>
							<td valign='bottom' align='right' width='25%'>
								<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
									<tr>
										<td><b>Credit Note No.</b></td>
										<td valign='center'>$notenum</td>
									</tr>
									<tr>
										<td><b>Invoice No.</b></td>
										<td valign='center'>$inv[invnum]</td>
									</tr>
									<tr>
										<td><b>Order No.</b></td>
										<td valign='center'>$inv[ordno]</td>
									</tr>
									<tr>
										<td><b>Terms</b></td>
										<td valign='center'>$terms Days</td>
									</tr>
									<tr>
										<td><b>Credit note Date</b></td>
										<td valign='center'>$rodate</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'>
								<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
									<tr>
										<th>ITEM NUMBER</th>
										<th width='45%'>DESCRIPTION</th>
										<th>QTY RETURNED</th>
										<th>UNIT PRICE</th>
										<th>AMOUNT</th>
									<tr>
									$products
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<table border='1' cellspacing='0' bordercolor='#000000'>
									<tr>
										<td>".nl2br($comm)."</td>
									</tr>
								</table>
							</td>
							<td align='right' colspan='3'>
								<table cellpadding='5' cellspacing='0' border='1' width='50%' bordercolor='#000000'>
									<tr>
										<td><b>SUBTOTAL</b></td>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr>
										<td><b>Trade Discount</b></td>
										<td align='right'>".CUR." $traddiscmt</td>
									</tr>
									<tr>
										<td><b>Delivery Charge</b></td>
										<td align='right'>".CUR." $delexvat</td>
									</tr>
									<tr>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr>
										<th><b>GRAND TOTAL<b></th>
										<td align='right'>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td>
								<table ".TMPL_tblDflts." border='1'>
						        	<tr>
						        		<th>VAT No.</th>
						        		<td align='center'>".COMP_VATNO."</td>
						        	</tr>
						        </table>
							</td>
							<td><br></td>
						</tr>
					</table>
					</center>";
	$OUTPUT = "<script>printer2('pos-note-slip.php?noteid=$noteid&prd=$prd&cccc=true');move('main.php');</script>";
	require ("template.php");

}



function recordCT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '1111' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '-$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
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
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance,div) VALUES('$cusnum', '$odate', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}


?>

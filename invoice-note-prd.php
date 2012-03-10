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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = details($HTTP_POST_VARS);
		}
} else {
	$OUTPUT = details($HTTP_GET_VARS);
}

# Get templete
require("template.php");



# Details
function details($HTTP_GET_VARS,$err="")
{

	$showvat = TRUE;

	# Get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

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

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$inv['chrgvat'] = "Non Vat";
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

	$sql = "SELECT *, (qty - noted) as qty FROM inv_items  WHERE invid = '$invid' AND qty > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	if(pg_numrows($stkdRslt) < 1){
		return "<li> The are no items on the invoice, all items have been returned.</li>";
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

		if($stkd['account'] > 0) {
			$stk['stkid'] = 0;
			$stk['stkcod'] = "";
			$stk['stkdes'] = $stkd['description'];
		}

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		if(pg_numrows($Ri)<1) {
	//		return details($HTTP_GET_VARS, "<li class='err'>Please select the vatcode for all your items.</li>");
		}

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$stkd['amt'] = sprint($stkd['amt']);

		# Put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><input type='hidden' name=ids[] value='$stkd[id]'><input type='hidden' name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$stkd[serno]'>$stkd[serno]</td>
				<td><input type='hidden' size='4' name=qts[] value='$stkd[qty]'><input type='text' size='4' name=qtys[] value='$stkd[qty]'></td>
				<td>$stkd[unitcost]</td>
				<td><input type='hidden' size='4' name=disc[] value='$stkd[disc]'>$stkd[disc] OR <input type='hidden' size='4' name=discp[] value='$stkd[discp]' maxlength='5'>$stkd[discp]%</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
	}
	$products .= "</table>";

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = round((($inv['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);
	$traddiscm = sprint($traddiscm);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

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
		<h3>Credit Note</h3>
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
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Vat Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'><textarea name='comm' rows='4' cols='20'>$inv[comm]</textarea></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Sales Person</td>
							<td valign='center'>$inv[salespn]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Credit Note Date</td>
							<td valign='center'>".mkDateSelect("o")."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='hidden' size='7' name='traddisc' value='$inv[traddisc]'>$inv[traddisc]%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='hidden' name='dct' value='$dct'><input type='text' size='7' name='delchrg' value='$dct'></td>
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
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right'>".CUR." $traddiscm</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right'>".CUR." $inv[delchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right'>".CUR . sprint($VAT)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $TOTAL</td>
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



# Error
function error($HTTP_GET_VARS, $err = "")
{

	$showvat = TRUE;

	# Get vars
	extract ($HTTP_GET_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

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

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		db_connect();

		$sql = "SELECT *,(qty - noted) as qty FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		$tcosamt = 0;
		$taxex = 0;
		while($stkd = pg_fetch_array($stkdRslt)){

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

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
		//		return details($HTTP_POST_VARS, "<li class='err'>Please select the vatcode for all your items.</li>");
			}

			$vd = pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			$stkd['amt'] = sprint ($stkd['amt']);

			# Put in product
			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><input type='hidden' name=ids[] value='$stkd[id]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' name=sers[$stkd[stkid]][] value='$stkd[serno]'>$stkd[serno]</td>
					<td><input type='hidden' size='4' name=qts[] value='$stkd[qty]'><input type='text' size='4' name='qtys[]' value='$stkd[qty]'></td>
					<td>$stkd[unitcost]</td>
					<td><input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%</td>
					<td>".CUR." $stkd[amt]</td>
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

	$traddiscm=sprint($traddiscm);

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Credit Note</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='prd' value='$prd'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr><td>$err</td></tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr><th colspan='2'> Customer Details </th></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Vat Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'><textarea name='comm' rows='4' cols='20'>$comm</textarea></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Charge VAT</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Sales Person</td>
							<td valign='center'>$inv[salespn]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Date</td>
							<td valign='center'>".mkDateSelect("o",$o_year,$o_month,$o_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='hidden' size='7' name='traddisc' value='$inv[traddisc]'>$inv[traddisc]%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='hidden' name='dct' value='$dct'><input type='text' size='7' name='delchrg' value='$delchrg'></td>
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
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right'>".CUR." $traddiscm</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right'>".CUR." $delchrg</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right'>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $TOTAL</td>
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
function confirm($HTTP_POST_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
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
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ($qty, "float", 0, 0, "The Returned Quantity cannot be more than the quantity sold.");
			}
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
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
		return error($HTTP_POST_VARS, $err);
	}



	# Get invoice info
	db_conn($prd);

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$vchrgvat = "Non Vat";
	}

	$vatamount = 0;

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

		$c = 0;
		$taxex = 0;
		foreach($qtys as $keys => $value){
			if($qtys[$keys] > 0){
				db_connect();
				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				db_conn($prd);

				# get selected stock in this invoice
				$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				if($stkd['account'] == 0) {

					# get warehouse name
					db_conn("exten");

					$sql = "SELECT whname FROM warehouses WHERE whid = '2' AND div = '".USER_DIV."'";
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

					$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
				//		return "Please select the vatcode for all your stock.";
					}

					$vd = pg_fetch_array($Ri);

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes") {
						$excluding = "y";
					} else {
						$excluding = "";
					}

					# Check Tax Excempt
					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
						$taxex += $amt[$keys];
					}

					$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs = explode("|",$vr);
					$ivat = $vrs[0];
					$iamount = $vrs[1];

					$vatamount += $ivat;

					if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

					$serial = $sers[$stk['stkid']][$keys];

					if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}

					$amt[$keys] = sprint ($amt[$keys]);

					# Put in product
					$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$wh[whname]</td>
							<td><input type='hidden' name='ids[]' value='$ids[$keys]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
							<td>$stk[stkdes]</td>
							<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
							<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>".sprint3($qtys[$keys])."</td>
							<td>$stkd[unitcost]</td>
							<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
							<td><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
						</tr>";
					$c++;
				} else {
					# get warehouse name
					db_conn("core");
					$sql = "SELECT accname FROM accounts WHERE accid = '$stkd[account]'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					$disc[$keys]=0;

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

					db_connect();

					$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
			//			return "Please select the vatcode for all your stock.";
					}

					$vd = pg_fetch_array($Ri);

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes") {
						$excluding = "y";
					} else {
						$excluding = "";
					}

					$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
					$vrs = explode("|",$vr);
					$ivat = $vrs[0];
					$iamount = $vrs[1];

					$vatamount += $ivat;

					if($stkd['account'] > 0) {
						$wh['whname'] = "";
						$stk['stkid'] = 0;
						$stk['stkcod'] = $wh['accname'];
						$stk['stkdes'] = $stkd['description'];
					}


					# Check Tax Excempt
					if($vd['zero'] == "Yes"){
						$taxex += $amt[$keys];
					}

					if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

					$serial = $sers[$stk['stkid']][$keys];

					if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}

					$amt[$keys] = sprint ($amt[$keys]);

					# Put in product
					$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$wh[whname]</td>
							<td><input type='hidden' name='ids[]' value='$ids[$keys]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
							<td>$stk[stkdes]</td>
							<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
							<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>".sprint3($qtys[$keys])."</td>
							<td>$stkd[unitcost]</td>
							<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
							<td><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
						</tr>";

					$c++;
				}
			}
		}
	$products .= "</table>";

	if($c < 1){
		$err = "<li class='err'>Please enter quantity.</li>";
		return error($HTTP_POST_VARS, $err);
	}

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

//		$VATP = TAX_VAT;

		if($inv['chrgvat'] == "exc"){
			$taxex = sprint($taxex - ($taxex * $traddisc / 100));
			$subtotal = sprint($sub + $delchrg);
			$traddiscmt = sprint($subtotal * $traddisc/100);
			$subtotal = sprint($subtotal - $traddiscmt);
//			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$VAT = sprint ($vatamount);
			$SUBTOT = $sub;
			$TOTAL = sprint($subtotal + $VAT);
			$delexvat = sprint($delchrg);

		}elseif($inv['chrgvat'] == "inc"){
			$ot = $taxex;
			$taxex = sprint($taxex - ($taxex * $traddisc / 100));
			$subtotal = sprint($sub + $delchrg);
			$traddiscmt = sprint($subtotal * $traddisc / 100);
			$subtotal = sprint($subtotal - $traddiscmt);
//			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$VAT = sprint($vatamount);
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

/* --- ----------- Clac --------------------- *

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

		if($inv['chrgvat'] != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;

/* -------------- Clac -------------------- */

	$traddiscmt = sprint($traddiscmt);

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
		<h3>Credit Note</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='prd' value='$prd'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Vat Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'><input type='hidden' name='comm' value='$comm'>".nl2br($comm)."</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$vchrgvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'><input type='hidden' size='7' name='terms' value='$terms'>$terms Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Sales Person</td>
							<td valign='center'>$inv[salespn]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Date</td>
							<td valign='center'><input type='hidden' name='odate' value='$odate'>$odate</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='hidden' size='7' name='traddisc' value='$traddisc'>$traddisc%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='hidden' size='7' name='delchrg' value='$delchrg'>$delchrg</td>
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
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'><input type='hidden' name='SUBTOT' value='$SUBTOT'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right'>".CUR." $traddiscmt</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right'>".CUR." $delexvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right'>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td><input type='submit' value='Write'></td></tr>
		</table>
		</form>
		</center>";
	return $details;

}



# details
function write($HTTP_POST_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($odate, "date", 1, 14, "Invalid Invoice note date.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids[]
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# check amt[]
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
		return error($HTTP_POST_VARS, $err);
	}

/* -------------------------------- */
	# Get invoice info
	db_conn($prd);

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($inv['odate']) >= strtotime($blocked_date_from) AND strtotime($inv['odate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$notenum = divlastid('note', USER_DIV);

	$vatamount = 0;

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

		db_conn($prd);

		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if($stkd['account'] == 0) {

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

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
		//		return "Please select the vatcode for all your stock.";
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
			if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
				$taxex += $amt[$keys];
			}



			# put in product
			$products .= "
				<tr>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td>$stkd[unitcost]</td>
					<td><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
				</tr>";
		} else {

			# get warehouse name
			db_conn("core");

			$sql = "SELECT accname FROM accounts WHERE accid = '$stkd[account]'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			$discp[$keys]=0;

			# Calculate amount
			$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

			db_connect();

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
		//		return "Please select the vatcode for all your stock.";
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

			$wh['whname'] = "";
			$stk['stkid'] = 0;
			$stk['stkcod'] = $wh['accname'];
			$stk['stkdes'] = $stkd['description'];

			# put in product
			$products .= "
				<tr>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td>$stkd[unitcost]</td>
					<td><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
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


	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$sub = 0.00;
	if(isset($amt)) {
		$sub = sprint(array_sum($amt));
	}

	$VATP = TAX_VAT;

	if($inv['chrgvat'] == "exc"){
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc / 100);
		$subtotal = sprint($subtotal - $traddiscmt);
//		$VAT = sprint(($subtotal - $taxex) * $VATP / 100);
		$VAT = sprint ($vatamount);
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);

	}elseif($inv['chrgvat'] == "inc"){
		$ot = $taxex;
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc / 100);
		$subtotal = sprint($subtotal - $traddiscmt);
//		$VAT = sprint(($subtotal - $taxex) * $VATP / (100 + $VATP));
		$VAT = sprint ($vatamount);
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


	# Get invoice info
	db_conn($prd);

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

/* A quick fix by jupiter
	$allnoted = true;
	foreach($qtys as $keys => $value){
		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);
		if($stkd['qty'] != $qtys[$keys]){
			$allnoted = false;
		}
	}

	if($allnoted){
		$SUBTOT = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);
		$delchrg = sprint($inv['delivery']);
		$traddiscmt = sprint($inv['discount']);
		$SUBTOTAL = sprint($TOTAL - $VAT);
	}
/* End A quick fix by jupiter */

	if($inv['balance'] >= $TOTAL) {
		$invpay = $TOTAL;
		$examt = 0;
	} else {
		$invpay = $inv['balance'];
		$examt = ($TOTAL - $invpay);
	}

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","no");
	/* - End Hooks - */

	# Todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/

	# Insert invoice to period DB
	db_conn($inv['prd']);

	# Format date
	$odate = explode("-", $odate);
	$rodate = $odate[2]."-".$odate[1]."-".$odate[0];
	$td = $rodate;

	# Insert invoice credit note to DB
	$sql = "
		INSERT INTO inv_notes (
			deptid, notenum, invnum, invid, cusnum, cordno, ordno, 
			chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, 
			total, comm, username, div, surname, cusaddr, cusvatno, 
			deptname, prd
		) VALUES (
			'$inv[deptid]', '$notenum', '$inv[invnum]', '$inv[invid]', '$inv[cusnum]', '$inv[cordno]', '$inv[ordno]', 
			'$inv[chrgvat]', '$terms', '$traddiscmt', '$inv[salespn]', '$rodate', '$delexvat', '$SUBTOT', '$VAT', 
			'$TOTAL', '$comm', '".USER_NAME."', '".USER_DIV."', '$inv[surname]', '$inv[cusaddr]', '$inv[cusvatno]', 
			'$inv[deptname]', $inv[prd]
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# Get next ordnum
	$noteid = pglib_lastid ("inv_notes", "noteid");



	# Begin updating
	#pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$nbal = ($inv['nbal'] + $TOTAL);

	db_conn($prd);

	# Update the invoice (make balance less)
	$sql = "
		UPDATE invoices 
		SET nbal = '$nbal', rdelchrg = (rdelchrg + '$delchrg'), balance = balance - '$invpay' 
		WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	db_connect();

		# Update the invoice (make balance less)
		$sql = "UPDATE open_stmnt SET balance = balance-'$TOTAL' WHERE invid = '$inv[invnum]'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update the customer (make balance less)
		$sql = "UPDATE customers SET balance = (balance - '$TOTAL') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update invoice's discounts
		# $sql = "UPDATE inv_discs SET traddisc = (traddisc - '$traddiscm'), itemdisc = (itemdisc - '$discs') WHERE cusnum = '$inv[cusnum]' AND invid = '$invid'";
		# $stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# record the payment on the statement
		$sql = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, 
				type, div, allocation_date
			) VALUES (
				'$inv[cusnum]', '$notenum', '".($TOTAL - ($TOTAL * 2))."', '$rodate', 
				'Credit Note for invoice No. $inv[invnum]', '".USER_DIV."', '$rodate'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		$disc = 0;

	# Commit updating
	#pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	$nsp=0;
	# Make ledge record
	custledger($inv['cusnum'], $dept['incacc'], $sdate, $notenum, "Credit Note No. $notenum for invoice No. $inv[invnum]", $TOTAL, "c");

	if($examt > 0) {
		# Make record for age analisys
		custCTP($examt, $inv['cusnum']);
	}

	foreach($qtys as $keys => $value){

		db_connect();

		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		db_conn($prd);

		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE id='$ids[$keys]' AND invid='$invid' AND div='".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if($stkd['account'] == 0) {

			# Keep track of discounts
			$disc += ($stkd['disc'] * $stkd['qty']);

			db_connect();

			$Sl = "SELECT * FROM scr WHERE inv='$inv[invnum]' AND stkid='$stkd[stkid]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {

				$cd = pg_fetch_array($Ri);

				$stk['csprice'] = $cd['amount'];

			} else {
				$stk['csprice'] = 0;
			}

			# cost amount
			$cosamt = round(($qtys[$keys] * $stk['csprice']), 2);

			db_connect();

			# Update stock(onhand + qty)
			$sql = "UPDATE stock SET csamt = (csamt + '$cosamt'), units = (units + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			db_connect();

			# fix stock cost amount
			$Sl = "UPDATE stock set csprice=csamt/units WHERE stkid = '$stkids[$keys]' AND units>0";
			$Ri = db_exec($Sl) or errDie("Unable to update stock cost price in Cubit.",SELF);

			if($stk['serd'] == 'yes') {
				ext_InSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $notenum, 'note', $rodate);
			}

			# negetive values to minus profit
			$nqty = ($qtys[$keys] * (1));
			$namt = ($amt[$keys] * (-1));
			$ncsprice = ($cosamt * (-1));

			$noted = ($stkd['noted'] + $qtys[$keys]);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			stockrec($stkd['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $td, $nqty, $cosamt, "Credit note for Customer : $inv[surname] - Credit note No. $notenum");


			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $amt[$keys];

			###################VAT CALCS#######################
			db_connect();

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
	//			return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

			####################################################

			db_connect();
	
			$sql = "
				INSERT INTO stockrec (
					edate, stkid, stkcod, stkdes, trantype, qty, csprice, 
					csamt, details, div
				) VALUES (
					'$td', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'note', '$qtys[$keys]', '$amtexvat', 
					'$cosamt', 'Credit note for Customer : $inv[surname] - Credit note No. $notenum', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);

			db_conn($inv['prd']);

			# Get selected stock in this invoice
			$sql = "UPDATE inv_items SET noted = '$noted' WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);
			$stkds = pg_fetch_array($stkdsRslt);

			# get accounts
			db_conn("exten");

			$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);
			$stockacc = $wh['stkacc'];
			$cosacc = $wh['cosacc'];

			# sales rep commission
			# coms($inv['salespn'], $amt[$keys], $stk['com'], 'anything');

			//$commision=$commision+coms($inv['salespn'], $stkd['amt'], $stk['com']);

			# dt(stock) ct(cos)
			writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

			db_conn($inv['prd']);

			# insert invoice items
			$sql = "
				INSERT INTO inv_note_items (
					noteid, whid, stkid, qty, amt, div
				) VALUES (
					'$noteid', '$stkd[whid]', '$stkids[$keys]', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();

			$sql = "
				INSERT INTO salesrec (
					edate, invid, invnum, debtacc, vat, total, typ, div
				) VALUES (
					'$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nstk', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);

		} else {
			# Keep track of discounts
			//$disc += ($stkd['disc'] * $stkd['qty']);

			# negetive values to minus profit
			$nqty = ($qtys[$keys] * (1));
			$namt = ($amt[$keys] * (-1));
			//$ncsprice = ($cosamt * (-1));

			$noted = ($stkd['noted'] + $qtys[$keys]);

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $amt[$keys];

			###################VAT CALCS#######################

			db_connect();

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
	//			return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

			####################################################

			db_conn($inv['prd']);

			# Get selected stock in this invoice
			$sql = "UPDATE inv_items SET noted = '$noted' WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);
			$stkds = pg_fetch_array($stkdsRslt);

			$nsp += sprint($iamount-$ivat);

			//writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
			writetrans($stkd['account'],$dept['debtacc'],$td, $refnum, ($iamount-$ivat), "Debtors control for Credit note: $notenum");

			//# dt(stock) ct(cos)
		//	writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

			db_conn($inv['prd']);

			# insert invoice items
			$sql = "
				INSERT INTO inv_note_items (
					noteid, whid, stkid, qty, amt, div, description
				) VALUES (
					'$noteid', '$stkd[account]', '0', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."', '$stkd[description]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();

			$sql = "
				INSERT INTO salesrec (
					edate, invid, invnum, debtacc, vat, total, typ, div
				) VALUES (
					'$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nnon', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);

		}
	}

	db_connect();

	# save invoice discount
	$sql = "
		INSERT INTO inv_discs (
			cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div
		) VALUES (
			'$inv[cusnum]', '$invid', '0', '-$disc', '$inv[odate]', '0', '".USER_DIV."'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	###################VAT CALCS#######################

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes";
		$Ri = db_exec($Sl);
	}

	$vd = pg_fetch_array($Ri);

	$excluding = "";

	$vr = vatcalc($delexvat,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs = explode("|",$vr);
	$ivat = $vrs[0];
	$iamount = $vrs[1];

	vatr($vd['id'],$sdate,"OUTPUT",$vd['code'],$refnum,"Vat for Credit note No. $notenum, Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

	####################################################

	/* - Start Transactoins - */
	if(($TOTAL - $VAT - $nsp) > 0) {
		# dt(income) ct(debtors)
		writetrans($dept['incacc'], $dept['debtacc'], $date, $refnum, ($TOTAL-$VAT-$nsp), "Debtors Control for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	}

	# dt(vat) ct(debtors)
	writetrans($vatacc, $dept['debtacc'], $date, $refnum, $VAT, "Vat Return for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	db_connect();
//	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
//	VALUES('$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$VAT', '$TOTAL', 'nstk', '".USER_DIV."')";
//	$recRslt = db_exec($sql);

	$Sl = "
		INSERT INTO sj (
			cid, name, des, date, 
			exl, vat, inc, div
		) VALUES (
			'$inv[cusnum]', '$inv[surname]', 'Credit Note:$notenum, Invoice $inv[invnum]', '$rodate', 
			'".-sprint($TOTAL-$VAT)."', '-$VAT', '".sprint(-$TOTAL)."', '".USER_DIV."'
		)";
	$Ri = db_exec($Sl);

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Transactoins - */

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
		<h2>Credit Note</h2>
		<table cellpadding='0' cellspacing='4' border='0' width='750'>
			<tr>
				<td valign='top' width='30%'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td>$inv[surname]</td>
						</tr>
						<tr>
							<td>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr>
							<td>(Vat No. $inv[cusvatno])</td>
						</tr>
					</table>
				</td>
				<td valign='top' width='25%'>
					".COMP_NAME."<br>
					".COMP_ADDRESS."<br>
					".COMP_TEL."<br>
					".COMP_FAX."<br>
				</td>
				<td width='20%'><img src='compinfo/getimg.php' width='230' height='47'></td>
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
						<tr><td>".nl2br($comm)."</td></tr>
					</table>
				</td>
				<td align='right' colspan='3'>
					<table cellpadding='5' cellspacing='0' border='1' width=50% bordercolor='#000000'>
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
							<td><b>VAT $vat14</b></td>
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
	//$OUTPUT = "<script>printer('invoice-note-reprint.php?noteid=$noteid&prd=$inv[prd]&cccc=yes');move('index-sales.php');</script>";

	header("Location: invoice-note-reprint.php?noteid=$noteid&prd=$inv[prd]&cccc=yes");
	exit;

	require ("tmpl-print.php");

}


?>

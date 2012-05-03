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

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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
	db_connect();
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
		db_connect();
		$sql = "SELECT *, (qty - noted) as qty FROM inv_items  WHERE invid = '$invid' AND qty > 0 AND div = '".USER_DIV."'";
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

			# Put in product
			$products .= "
							<tr class='".bg_class()."'>
								<td>$wh[whname]</td>
								<td><input type='hidden' name='ids[]' value='$stkd[id]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
								<td>$stk[stkdes]</td>
								<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$stkd[serno]'>$stkd[serno]</td>
								<td><input type='hidden' size='4' name='qts[]' value='$stkd[qty]'><input type='text' size=4' name='qtys[]' value='$stkd[qty]'></td>
								<td>$stkd[unitcost]</td>
								<td>".CUR."  <input type='hidden' size='4' name='disc[]' value='$stkd[disc]'>$stkd[disc] OR <input type='hidden' size='4' name=discp[] value='$stkd[discp]' maxlength='5'>$stkd[discp]%</td>
								<td>$inv[currency] $stkd[amt]</td>
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

	/* --- End Some calculations --- */

	$traddiscm=sprint($traddiscm);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Credit Note</h3>
					<form action='".SELF."' method='POST'>
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
										<td>Department</td>
										<td valign='center'>$inv[deptname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer</td>
										<td valign='center'>$inv[cusname] $inv[surname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign=top>Customer Address</td>
										<td valign='center'>".nl2br($inv['cusaddr'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Order number</td>
										<td valign='center'>$inv[cordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Vat Number</td>
										<td>$inv[cusvatno]</td>
									</tr>
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr class='".bg_class()."'>
										<td colspan='2' align='center'><textarea name='comm' rows='4' cols='20'>$inv[comm]</textarea></td>
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
									<tr class='".bg_class()."'>
										<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='invoice-view.php'>View Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right'>$inv[currency] $traddiscm</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>$inv[currency] $inv[delchrg]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>$inv[currency] $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>$inv[currency] $TOTAL</td>
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
function error($_GET, $err = "")
{

	# Get vars
	extract ($_GET);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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
	db_connect();
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
							<th>SERIAL NO.</th>
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

			# Put in product
			$products .= "
							<tr class='".bg_class()."'>
								<td>$wh[whname]</td>
								<td><input type='hidden' name='ids[]' value='$stkd[id]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
								<td>$stk[stkdes]</td>
								<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$stkd[serno]'>$stkd[serno]</td>
								<td><input type='hidden' size='4' name='qts[]' value='$stkd[qty]'><input type='text' size='4' name='qtys[]' value='$stkd[qty]'></td>
								<td>$stkd[unitcost]</td>
								<td>".CUR."  <input type='text' size='4' name='disc[]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[]' value='$stkd[discp]' maxlength='5'>%</td>
								<td>$inv[currency] $stkd[amt]</td>
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

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Credit Note</h3>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='invid' value='$invid'>
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
									<tr class='".bg_class()."'>
										<td>Customer</td>
										<td valign='center'>$inv[cusname] $inv[surname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign=top>Customer Address</td>
										<td valign='center'>".nl2br($inv['cusaddr'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Order number</td>
										<td valign='center'>$inv[cordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Vat Number</td>
										<td>$inv[cusvatno]</td>
									</tr>
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
										<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='invoice-view.php'>View Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right'>$inv[currency] $traddiscm</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>$inv[currency] $delchrg</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>$inv[currency] $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>$inv[currency] $TOTAL</td>
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

	# get vars
	extract ($_POST);

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
		return error($_POST, $err);
	}

//print "<pre>";
//var_dump($_POST);
//print "</pre>";

	# Get invoice info
	db_connect();
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

				# get selected stock in this invoice
				$sql = "SELECT * FROM inv_items  WHERE stkid = '$stkids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
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
				$amt[$keys] = sprint($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

				db_connect();
				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd=pg_fetch_array($Ri);

				# Check Tax Excempt
				if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
					$taxex += $amt[$keys];
				}
				if(isset($sers[$stk['stkid']][$keys])) {
					$serial = $sers[$stk['stkid']][$keys];
				} else {
					$serial="";
				}
				# Put in product
				$products .= "
								<tr class='".bg_class()."'>
									<td>$wh[whname]</td>
									<td><input type='hidden' name='ids[]' value='$ids[$keys]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
									<td>$stk[stkdes]</td>
									<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
									<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
									<td>$stkd[unitcost]</td>
									<td>".CUR."  <input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
									<td><input type='hidden' name='amt[]' value='$amt[$keys]'>$inv[currency] $amt[$keys]</td>
								</tr>";
				$c++;
			}
		}
	$products .= "</table>";

	if($c < 1){
		$err = "<li class='err'>Please enter quantity.</li>";
		return error($_POST, $err);
	}

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($inv['chrgvat'] == "exc"){
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$SUBTOT = $sub;
			$TOTAL=sprint($subtotal+$VAT);
			$delexvat=sprint($delchrg);

		}elseif($inv['chrgvat'] == "inc"){
			$ot=$taxex;
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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

		if($inv['chrgvat'] != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;

/* -------------- Clac --------------------- */

	$traddiscmt = sprint($traddiscmt);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Credit Note</h3>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='invid' value='$invid'>
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
									<tr class='".bg_class()."'>
										<td>Customer</td>
										<td valign='center'>$inv[cusname] $inv[surname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign=top>Customer Address</td>
										<td valign='center'>".nl2br($inv['cusaddr'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Order number</td>
										<td valign='center'>$inv[cordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Vat Number</td>
										<td>$inv[cusvatno]</td>
									</tr>
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
										<td valign='center'><input type='hidden' size='7' name=terms value='$terms'>$terms Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Sales Person</td>
										<td valign='center'>$inv[salespn]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Invoice Date</td>
										<td valign='center'><input type=hidden name=odate value='$odate'>$odate</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td valign='center'><input type='hidden' size='7' name='traddisc' value='$traddisc'>$traddisc%</td>
									</tr>
									<tr class='".bg_class()."'>
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
									<tr class='".bg_class()."'>
										<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='invoice-view.php'>View Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'><input type='hidden' name='SUBTOT' value='$SUBTOT'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right'>$inv[currency] $traddiscmt</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>$inv[currency] $delexvat</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>$inv[currency] $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>$inv[currency] $TOTAL</td>
									</tr>
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
			$v->isOk ($amount, "float", 1, 20, "Invalid  Amount, please enter all details.");
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



	/* -------------------------------- */
	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	cus_xrate_update($inv['fcid'], $inv['xrate']);

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
		$sql = "SELECT * FROM inv_items  WHERE stkid = '$stkids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
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
							<td><input type='hidden' name='amt[]' value='$amt[$keys]'>$inv[currency] $amt[$keys]</td>
						</tr>";
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
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$SUBTOT = $sub;
			$TOTAL=sprint($subtotal+$VAT);
			$delexvat=sprint($delchrg);

		}elseif($inv['chrgvat'] == "inc"){
			$ot=$taxex;
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
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

		$SUBTOTAL = sprint($SUBTOT);
		$traddiscm = $traddiscmt;

		/* --- ----------- Clac --------------------- */
		##----------------------END----------------------

/* --- ----------- Clac ---------------------

		# calculate subtot
		$SUBTOT = 0.00;
		if(isset($amt))
			$SUBTOT = sprint(array_sum($amt));

		$SUBTOT -= $taxex;

		# duplicate
		$SUBTOTAL = sprint($SUBTOT);

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

	$FSUBTOT = sprint($SUBTOT * $inv['xrate']);
	$FSUBTOTAL = sprint($SUBTOTAL * $inv['xrate']);
	$FVAT = sprint($VAT * $inv['xrate']);
	$FTOTAL = sprint($TOTAL * $inv['xrate']);
	$fdelchrg = sprint($delchrg * $inv['xrate']);
	$ftraddiscm = sprint($traddiscm * $inv['xrate']);

/* --- ----------- Clac --------------------- */

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invoice Not Found</li>";
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
		$traddiscm = sprint($inv['discount']);
		$SUBTOTAL = sprint($TOTAL - $VAT);

		$FSUBTOT = sprint($SUBTOT * $inv['xrate']);
		$FSUBTOTAL = sprint($SUBTOTAL * $inv['xrate']);
		$FVAT = sprint($VAT * $inv['xrate']);
		$FTOTAL = sprint($TOTAL * $inv['xrate']);
		$fdelchrg = sprint($delchrg * $inv['xrate']);
		$ftraddiscm = sprint($traddiscm * $inv['xrate']);
	}
/*End A quick fix by jupiter */

 	$invpay = $TOTAL;

	/*if($inv['fbalance'] >= $TOTAL) {
		$invpay = $TOTAL;
		$examt = 0;
	} else {
		$invpay = $inv['fbalance'];
		$examt = ($TOTAL-$invpay);
	}

	# Make it local
	 $examt = ($examt * $inv['xrate']);*/

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
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

	$td=$rodate;

	# Insert invoice credit note to DB
	$sql = "INSERT INTO inv_notes(deptid, notenum, invnum, invid, cusnum, cordno, ordno, chrgvat, fcid, currency, xrate, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, comm, username, div, surname, cusaddr, cusvatno, deptname, location, prd)";
	$sql .= " VALUES('$inv[deptid]', '$notenum', '$inv[invnum]', '$inv[invid]', '$inv[cusnum]', '$inv[cordno]', '$inv[ordno]', '$inv[chrgvat]', '$inv[fcid]', '$inv[currency]', '$inv[xrate]', '$terms', '$traddiscm', '$inv[salespn]', '$rodate', '$delexvat', '$SUBTOT', '$VAT' , '$TOTAL', '$comm', '".USER_NAME."', '".USER_DIV."', '$inv[surname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[deptname]', '$inv[location]', $inv[prd])";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# Get next ordnum
	$noteid = pglib_lastid ("inv_notes", "noteid");

	db_connect();

	# Begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		$nbal = ($inv['nbal'] + $TOTAL);

		# Update the invoice (make balance less)
		$sql = "UPDATE invoices SET nbal = '$nbal', rdelchrg = (rdelchrg + '$delchrg'), fbalance = fbalance - '$invpay', balance = balance - '$FTOTAL' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update the customer (make balance less)
		$sql = "UPDATE customers SET balance = (balance - '$FTOTAL'), fbalance = (fbalance - '$TOTAL') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update invoice's discounts
		# $sql = "UPDATE inv_discs SET traddisc = (traddisc - '$traddiscm'), itemdisc = (itemdisc - '$discs') WHERE cusnum = '$inv[cusnum]' AND invid = '$invid'";
		# $stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, amount, date, type, div, allocation_date) 
			VALUES 
				('$inv[cusnum]','$inv[invnum]','".($TOTAL - ($TOTAL * 2))."', '$rodate', 'Credit Note for invoice No. $inv[invnum]', '".USER_DIV."', '$rodate')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		$disc = 0;

	# Commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Make ledge record
	custledger($inv['cusnum'], $dept['incacc'], $td, $notenum, "Credit Note No. $notenum for invoice No. $inv[invnum]", $FTOTAL, "c");

		/*
		if($examt > 0) {
			# Make record for age analisys
			custCTP($examt, $inv['cusnum']);
		}
		*/

		foreach($qtys as $keys => $value){

			$famt[$keys] = sprint($amt[$keys] * $inv['xrate']);

			db_connect();
			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# get selected stock in this invoice
			$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			# Keep track of discounts
			$disc += (($stkd['disc'] * $stkd['qty']) * $inv['xrate']);

			# cost amount
			$cosamt = round(($qtys[$keys] * $stk['csprice']), 2);

			# Update stock(onhand + qty)
			$sql = "UPDATE stock SET csamt = (csamt + '$cosamt'), units = (units + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

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

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $famt[$keys];
			if($inv['chrgvat'] == "inc" && $stk['exvat'] != 'yes'&&$vd['zero']!="Yes"){
				$amtexvat = sprint(($famt[$keys] * 100)/(100 + $VATP));
			}

			###################VAT CALCS#######################

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
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

			$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc']);
			$vrs=explode("|",$vr);
			$ivat=$vrs[0];
			$iamount=$vrs[1];

			$iamount=$iamount* $inv['xrate'];
			$ivat=$ivat* $inv['xrate'];

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);

			####################################################


			$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
			VALUES('$td', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'note', '$qtys[$keys]', '$amtexvat', '$cosamt', 'Credit note for Customer : $inv[surname] - Credit note No. $notenum', '".USER_DIV."')";
			$recRslt = db_exec($sql);

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

			# dt(stock) ct(cos)
			writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

			db_conn($inv['prd']);
			# insert invoice items
			$sql = "INSERT INTO inv_note_items(noteid, whid, stkid, qty, amt, div,vatcode) VALUES('$noteid', '$stkd[whid]', '$stkids[$keys]', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."','$stkd[vatcode]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_connect();
		# save invoice discount
		$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div) VALUES('$inv[cusnum]', '$invid', '0', '-$disc', '$inv[odate]', '0', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	/* - Start Transactoins - */

	# dt(income) ct(debtors)
	writetrans($dept['incacc'], $dept['debtacc'], $td, $refnum, ($FTOTAL-$FVAT), "Debtors Control for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	# dt(vat) ct(debtors)
	writetrans($vatacc, $dept['debtacc'], $td, $refnum, $FVAT, "Vat Return for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	db_connect();
	$date = date("Y-m-d");
	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES('$rodate', '$noteid', '$notenum', '$dept[debtacc]', '$FVAT', '$FTOTAL', 'nstk', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	db_conn('cubit');

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusnum]','$inv[surname]','Credit Note: $notenum, International Invoice $inv[invnum]','$rodate','".-sprint($FTOTAL-$FVAT)."','-$FVAT','".-sprint($FTOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);

	/* - End Transactoins - */

	/* -- Final Layout -- */
	$details = "
					<center>
					<h2>Credit Note</h2>
					<table cellpadding='0' cellspacing='4' border=0 width=750>
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
							<td width='20%'><img src='compinfo/getimg.php' width=230 height=47></td>
							<td valign='bottom' align='right' width='25%'>
								<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
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
								<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
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
								<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
									<tr>
										<td><b>SUBTOTAL</b></td>
										<td align='right'>$inv[currency] $SUBTOT</td>
									</tr>
									<tr>
										<td><b>Trade Discount</b></td>
										<td align='right'>$inv[currency] $traddiscmt</td>
									</tr>
									<tr>
										<td><b>Delivery Charge</b></td>
										<td align='right'>$inv[currency] $delexvat</td>
									</tr>
									<tr>
										<td><b>VAT @ $VATP%</b></td>
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
						<tr>
							<td>
								<table ".TMPL_tblDflts." border=1>
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
	$OUTPUT = "<script>printer('intinvoice-note-reprint.php?noteid=$noteid&prd=$inv[prd]&cccc=yes');move('main.php');</script>";
	require ("template.php");

}


?>

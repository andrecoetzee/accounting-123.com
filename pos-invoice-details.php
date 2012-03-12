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
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($_GET)
{

	$showvat = TRUE;

	# get vars
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
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	if($inv['cusnum'] != "0"){
		#then get the actual customer
		db_connect ();
		$get_cus = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
		$run_cus = db_exec($get_cus) or errDie("Unable to get customer information");
		if(pg_numrows($run_cus) < 1){
			#do nothing
		}else {
			$carr = pg_fetch_array($run_cus);
			$inv['cusname'] = "$carr[cusname] $carr[surname]";
		}
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>SERIAL NO.</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>UNIT DISCOUNT</th>
							<th>AMOUNT</th>
						<tr>";
		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM pinv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			// Stock or non stock description?
			if ($stkd["account"] > 0) {
				$description = $stkd["description"];
			} else {
				$description = $stk["stkdes"];
			}

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			# put in product
			$products .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$wh[whname]</td>
								<td>$stk[stkcod]</td>
								<td>$stkd[serno]</td>
								<td>$description</td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
								<td nowrap>".CUR." $stkd[amt]</td>
							</tr>";
	}
	$products .= "</table>";

/* --- Start Some calculations --- */

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

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
					<h3>Invoice Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts." width='60%'>
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr bgcolor='".TMPL_tblDataColor1."'>
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
										<td colspan='2' align='center'>".nl2br($inv['comm'])."</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Invoice Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Invoice No.</td>
										<td valign='center'>$inv[invid]</td>
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
										<td>Sales Person</td>
										<td valign='center'>$inv[salespn]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Invoice Date</td>
										<td valign='center'>$inv[odate]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td valign='center'>$inv[traddisc]%</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td valign='center'>$inv[delchrg]</td>
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
										<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='pos-invoice-list.php'>View point of sale Invoices</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $SUBTOT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td align='right' nowrap>".CUR." $inv[discount]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td align='right' nowrap>".CUR." $inv[delivery]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><b>VAT $vat14</b></td>
										<td align='right' nowrap>".CUR." $VAT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}


?>
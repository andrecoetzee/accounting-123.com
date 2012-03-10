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

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid purchase number.");

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

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
							<th width='65%'>DESCRIPTION</th>
							<th width='10%'>QTY</th>
							<th width='10%'>UNIT PRICE</th>
							<th width='10%'>AMOUNT</th>
						<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$i</td>
							<td>$stkd[description]</td>
							<td>$stkd[qty]</td>
							<td>$stkd[unitcost]</td>
							<td>".CUR." $stkd[amt]</td>
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
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	if($inv['invnum']==0) {
		$inv['invnum']=$inv['invid'];
	}

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
					<h3>Non-Stock Invoice Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
					<tr>
						<td valign='top'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'> Customer Details </th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Customer</td>
									<td valign='center'>$inv[cusname]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Customer Address</td>
									<td valign='center'><pre>$inv[cusaddr]</pre></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Customer Vat Number</td>
									<td valign='center'>$inv[cusvatno]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Customer Order number</td>
									<td valign='center'>$inv[cordno]</td>
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
									<td valign='center'>$inv[invnum]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Proforma Inv No.</td>
									<td valign='center'>$inv[docref]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Date</td>
									<td valign='center'>$sday-$smon-$syear</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>VAT Inclusive</td>
									<td valign='center'>$inv[chrgvat]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Terms</td>
									<td valign='center'>$inv[terms] Days</td>
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
									<td bgcolor='".bgcolorg()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
									<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
								</tr>
								<script>document.write(getQuicklinkSpecial());</script>
							</table>
						</td>
						<td align='right'>
							<table ".TMPL_tblDflts." width='80%'>
								<tr bgcolor='".bgcolorg()."'>
									<td>SUBTOTAL</td>
									<td align='right'>".CUR." $inv[subtot]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>VAT $vat14</td>
									<td align='right'>".CUR." $inv[vat]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<th>GRAND TOTAL</th>
									<td align='right'>".CUR." $inv[total]</td>
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
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
if (isset($HTTP_GET_VARS["purid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

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

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY OUTSTANDING</th>
							<th>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
						<tr>";

		# get selected stock in this Order
		db_connect();
		$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			# format date
			list($dyear, $dmon, $dday) = explode("-", $stkd['ddate']);

			# put in product
			$products .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$stkd[cod]</td>
								<td>$stkd[des]</td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>$dday-$dmon-$dyear</td>
								<td nowrap>".CUR." $stkd[amt]</td>
							</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

	/* --- End Some calculations --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	// format the vat inclusive variable for nicer display
	if ( $pur['vatinc'] == "novat")
		$pur['vatinc'] = "No Vat";

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Non-Stock Order Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Account number</td>
										<td valign='center'><pre>$pur[supaddr]</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Non-Stock Order Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Non-Stock Order No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Order No.</td>
										<td valign='center'>$pur[ordernum]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Supplier Invoice No</td>
										<td valign='center'>$pur[supinv]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Ref No.</td>
										<td valign='center'>$pur[refno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$pur[vatinc]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charges</td>
										<td valign='center'>".CUR." $pur[shipchrg]</td>
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
										<td bgcolor='".bgcolorg()."'><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
										<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." $pur[subtot]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charges</td>
										<td align='right'>".CUR." $pur[shipping]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right'>".CUR." $pur[vat]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." $pur[total]</td>
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
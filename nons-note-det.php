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
if (isset($_GET["id"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid Non-Stock Order number.");
	
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

	# Get Non-Stock Order info
	db_conn("cubit");
	$sql = "SELECT * FROM nons_purchasesn WHERE id = '$id' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Non-Stock Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Purchase Not Found </i>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
						<tr>";
		# get selected stock in this Non-Stock Order
		$sql = "SELECT * FROM nons_pur_itemsn  WHERE noteid = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# format date
			list($dyear, $dmon, $dday) = explode("-", $stkd['ddate']);

			# put in product
			$products .= "
							<tr class='".bg_class()."'>
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

	# Format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Returned Non-Stock Order Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier Address</td>
										<td valign='center'><pre>$pur[supaddr]</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Non-Stock Order Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Ref No.</td>
										<td valign='center'>$pur[refno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$pur[vatinc]</td></tr>
									<tr class='".bg_class()."'>
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
										<td class='".bg_class()."'><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign=top>".nl2br($pur['remarks'])."</td></tr>
									<tr class='".bg_class()."'><td><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td></tr>
									<tr class='".bg_class()."'><td><a href='main.php'>Main Menu</a></td></tr>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $pur[subtot]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td align='right' nowrap>".CUR." $pur[shipping]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right' nowrap>".CUR." $pur[vat]</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>".CUR." $pur[total]</td>
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
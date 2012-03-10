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
if (isset($HTTP_GET_VARS["rpurid"]) && isset($HTTP_GET_VARS["prd"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.</li>";
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
	$v->isOk ($rpurid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm;
	}

	db_conn($prd);
	$sql = "SELECT * FROM rnons_purch_int WHERE purid='$rpurid'";
	$purRslt = db_exec($sql) or errDie("Unable to retrieve purchase information from Cubit.");
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table ".TMPL_tblDflts." width=100%>
	<tr>
		<th>ITEM NUMBER</th>
		<th>DESCRIPTION</th>
		<th>QTY</th>
		<th>UNIT PRICE</th>
		<th>AMOUNT</th>
	<tr>";
	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT * FROM rnons_purint_items  WHERE purid='$rpurid'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){
		$amt = sprint($stkd['unitcost'] * $stkd['qty']);

		# put in product
		$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$stkd[cod]</td>
							<td>$stkd[des]</td>
							<td align='right'>$stkd[qty]</td>
							<td align='right' nowrap>".CUR." $stkd[unitcost]</td>
							<td align='right' nowrap>".CUR." $amt</td>
						</tr>";
	}
	$products .= "</table>";

	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	/* --- End Some calculations --- */

	# format date
	list($ryear, $rmon, $rday) = explode("-", $pur['rdate']);


	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Returned International Non Stock Purchase Details</h3>
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
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($pur['supaddr'])."</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Purchase Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
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
										<td valign='center'>$rday-$rmon-$ryear</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td>&nbsp</td></tr>
						<tr>
							<td colspan='2'>$products</td>
						</tr>
						<tr>
							<td>
								<table ".TMPL_tblDflts.">
									<tr>
										<th width='50%'>Quick Links</th>
										<th width='45%'>Remarks</th>
										<td rowspan='5' valign='top' width='15%'><br></td>
									</tr>
									<tr>
										<td bgcolor='".bgcolorg()."'>
											<a href='purch-int-new.php'>New International Purchase</a>
										</td>
										<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>
											".nl2br($pur['remarks'])."
										</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='purch-int-view-prd.php'>View Received International Purchases</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<th>Total Cost Returned</th>
										<td align='right' nowrap>$pur[curr] $SUBTOT</td>
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
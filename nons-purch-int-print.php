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
require("tmpl-print.php");

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
	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	# Currency
	$currs = getSymbol($pur['fcid']);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table cellpadding='2' cellspacing='0' border='1' width='100%'>
						<tr>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th colspan='2'>UNIT PRICE</th>
							<th colspan='2'>DUTY</th>
							<th>LINE TOTAL</th>
						<tr>";
		# get selected stock in this Order
		db_connect();
		$sql = "SELECT * FROM nons_purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# put in product
			$products .= "
							<tr>
								<td>$stkd[cod]</td>
								<td>$stkd[des]</td>
								<td>$stkd[qty]</td>
								<td>$pur[curr] $stkd[cunitcost] or </td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>$pur[curr] $stkd[duty] or </td>
								<td>$stkd[dutyp]%</td>
								<td nowrap>$pur[curr] $stkd[amt]</td>
							</tr>";
		}

	$products .= "</table>";

	/* --- End Products Display --- */

 	/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get tax
	$tax = sprint($pur['tax']);

	/* --- End Some calculations --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>International Non-Stock Order Details</h3>
					<table cellpadding='0' cellspacing='4' border='0' width='95%'>
						<tr>
							<td valign='top'>
								<table cellpadding='2' cellspacing='0' border='1'>
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr>
										<td>Supplier Address</td>
										<td valign='center'><pre>$pur[supaddr]</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table cellpadding='2' cellspacing='0' border='1'>
									<tr>
										<th colspan='2'> Non-Stock Order Details </th>
									</tr>
									<tr>
										<td>Non-Stock Order No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr>
										<td>Order No.</td>
										<td valign='center'>$pur[ordernum]</td>
									</tr>
									<tr>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr>
										<td>Foreign Currency</td>
										<td valign='center'>$currsel &nbsp;&nbsp;Exchange rate $pur[curr] $pur[xrate]</td>
									</tr>
									<tr>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr>
										<td>Shipping Charges</td>
										<td valign='center'>$pur[curr] $pur[shipchrg]</td>
									</tr>
									<tr>
										<td>Delivery Date</td>
										<td valign='center'>$dday-$dmon-$dyear</td>
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
								<table cellpadding='2' cellspacing='0' border='1'>
									<tr>
										<th width='40%'>Remarks</th>
										<td rowspan='5' valign='top' width='15%'><br></td>
									</tr>
									<tr>
										<td rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
								</table>
							</td>
							<td align='right'>
								<table cellpadding='2' cellspacing='0' border='1' width='40%'>
									<tr>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>$pur[curr] $pur[subtot]</td>
									</tr>
									<tr>
										<td>Delivery Charges</td>
										<td align='right' nowrap>$pur[curr] $pur[shipping]</td>
									</tr>
									<tr>
										<td>Tax </td>
										<td align='right' nowrap>$pur[curr] $pur[tax]</td>
									</tr>
									<tr>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>$pur[curr] $pur[total]</td>
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
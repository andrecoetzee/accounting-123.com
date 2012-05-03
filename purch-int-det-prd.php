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
if (isset($_GET["purid"]) && isset($_GET["prd"])) {
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
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class=err>Purchase Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>DUTY</th>
							<th>AMT</th>
							<th>TOTAL COST AMT</th>
						<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

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

	/* -- Calculations -- */

		# Calculate cost amount bought
		$totamt = ($stkd['qty'] * $stkd['unitcost']);

		# Calculate percentage from subtotal
		$perc = (($totamt/$pur['subtot']) * 100);

		# Get percentage from shipping charges
		$shipchrg = (($perc / 100) * $pur['shipchrg']);

		# add shipping charges to amt
		$totamt = sprint (round(($totamt + $shipchrg), 2));

	/* -- End Calculations --*/


		# put in product
		$products .= "
						<tr class='".bg_class()."'>
							<td>$wh[whname]</td>
							<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
							<td>".extlib_rstr($stk['stkdes'], 30)."</td>
							<td>$stkd[qty]</td>
							<td>$pur[curr] $stkd[cunitcost] &nbsp;&nbsp;or &nbsp;&nbsp;R $stkd[unitcost]</td>
							<td>".CUR." $stkd[duty] &nbsp;&nbsp; or &nbsp;&nbsp;$stkd[dutyp]%</td>
							<td nowrap>$pur[curr] $stkd[amt]</td>
							<td align='right' nowrap>".CUR." $totamt</td>
						</tr>";
	}
	$products .= "</table>";

	# Get supplier
	db_connect();
	$sql = "SELECT supname,supno FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		$sup['supname'] = "<li class='err'>Supplier not found";
		$sup['supno'] = "";
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Received International Purchase Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier</td>
										<td valign='center'>$sup[supname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Account number</td>
										<td valign='center'>$sup[supno]</td>
									</tr>
									<tr class='".bg_class()."'>
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
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
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
										<td>Foreign Currency</td>
										<td valign='center'>$pur[curr]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Exchange rate</td>
										<td>".CUR." $pur[xrate]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Shipping Charges</td>
										<td valign='center'>$pur[curr] $pur[fshipchrg]</td>
									</tr>
									<tr class='".bg_class()."'>
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
								<table ".TMPL_tblDflts.">
									<tr>
										<th width='40%'>Quick Links</th>
										<th width='45%'>Remarks</th>
										<td rowspan='5' valign='top' width='15%'><br></td>
									</tr>
									<tr>
										<td class='".bg_class()."'><a href='purch-int-new.php'>New International Purchase</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='purch-int-view-prd.php'>View Received International Purchases</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>$pur[curr] $pur[subtot]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Shipping Charges</td>
										<td align='right' nowrap>$pur[curr] $pur[shipchrg]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax </td>
										<td align='right' nowrap>$pur[curr] $pur[tax]</td>
									</tr>
									<tr class='".bg_class()."'>
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
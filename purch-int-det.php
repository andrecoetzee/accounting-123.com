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
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
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

	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Order Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# select all products
	$products = "
		<table cellpadding='2' cellspacing='0' border='1' width='100%'>
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

	# get selected stock in this Order
	db_connect();

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

		// Prevent division by zero
		if ($totamt && $pur["subtot"]) {
			# Calculate percentage from subtotal
			$perc = (($totamt/$pur['subtot']) * 100);
		} else {
			$perc = 0;
		}

		# Get percentage from shipping charges
		$shipchrg = (($perc / 100) * $pur['shipchrg']);

		# add shipping charges to amt
		$totamt = round(($totamt + $shipchrg), 2);

	/* -- End Calculations --*/


		# put in product//<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'></a>
		$products .= "
			<tr>
				<td>$wh[whname]</td>
				<td>$stk[stkcod]</td>
				<td>".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>".sprint3($stkd['qty'])."</td>
				<td nowrap>$pur[curr] ".sprint($stkd['cunitcost'])." &nbsp;&nbsp;or &nbsp;&nbsp;R ".sprint($stkd['unitcost'])."</td>
				<td nowrap>$pur[curr] $stkd[duty] &nbsp;&nbsp; or &nbsp;&nbsp;$stkd[dutyp]%</td>
				<td nowrap>$pur[curr] ".sprint($stkd["amt"])."</td>
				<td align='right' nowrap>$pur[curr] ".sprint($totamt)."</td>
			</tr>";
	}
	$products .= "</table>";

	# Get supplier
	db_connect();
	$sql = "SELECT supname,supno FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		$sup['supname'] = "<li class='err'>Supplier not found.</li>";
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
					<h3>International Order Details</h3>
					<table cellpadding='0' cellspacing='4' border='0' width='95%'>
						<tr>
							<td valign='top'>
								<table cellpadding='2' cellspacing='0' border='1'>
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr>
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td>
									</tr>
									<tr>
										<td>Supplier</td>
										<td valign='center'>$sup[supname]</td>
									</tr>
									<tr>
										<td>Account number</td>
										<td valign='center'>$sup[supno]</td>
									</tr>
									<tr>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($pur['supaddr'])."</td>
									</tr>
								</table>
							</td>
							<td valign='top'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_PADDR."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
								Reg No. ".COMP_REGNO."<br>
								VAT No. ".COMP_VATNO."<br>
							</td>
							<td valign='top' align='right'>
								<table cellpadding='2' cellspacing='0' border='1'>
									<tr>
										<th colspan='2'> Order Details </th>
									</tr>
									<tr>
										<td>Order No.</td>
										<td valign='center'>$pur[purnum]</td>
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
										<td valign='center'>$pur[curr]</td>
									</tr>
									<tr>
										<td>Exchange rate</td>
										<td>".CUR." $pur[xrate]</td>
									</tr>
									<tr>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr>
										<td>Shipping Charges</td>
										<td valign='center'>$pur[curr] $pur[fshipchrg]</td>
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
							<td colspan='3'>$products</td>
						</tr>
						<tr>
							<td colspan='2'></td>
							<td align='right'>
								<table cellpadding='2' cellspacing='0' border='1' width='80%'>
									<tr>
										<td>SUBTOTAL</td>
										<td align='right'>$pur[curr] $pur[subtot]</td>
									</tr>
									<tr>
										<td>Shipping Charges</td>
										<td align='right'>$pur[curr] $pur[shipchrg]</td>
									</tr>
									<tr>
										<td>Tax </td>
										<td align='right'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr>
										<th>GRAND TOTAL</th>
										<td align='right'>$pur[curr] $pur[total]</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
					</center>";
	$OUTPUT=$details;
	require("tmpl-print.php");

}


?>
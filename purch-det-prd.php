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
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

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
	db_conn($prd);

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Purchase Not Found </i>";
	}
	$pur = pg_fetch_array($purRslt);

	db_connect ();

	$get_codes = "SELECT * FROM suppstock WHERE suppid = '$pur[supid]' ORDER BY stkid";
	$run_codes = db_exec ($get_codes) or errDie ("Unable to get supplier stock code information");
	if (pg_numrows ($run_codes) > 0){
		while ($codarr = pg_fetch_array ($run_codes)){
			if (strlen ($codarr['stkcod']) > 0) 
				$stockcodes[$codarr['stkid']]['stkcod'] = $codarr['stkcod'];
			if (strlen ($codarr['stkdes']) > 0) 
				$stockcodes[$codarr['stkid']]['stkdes'] = $codarr['stkdes'];
		}
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_conn($prd);

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		# format date
		list($dyear, $dmon, $dday) = explode("-", $stkd['ddate']);

		if($stkd['account']>0) {
			$stk['stkdes']=$stkd['description'];
			$stk['stkid']="";
			$stk['stkcod']="";
		}

		# put in product
		$products .= "
			<tr class='".bg_class()."'>
				<td>$wh[whname]</td>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td align='right'>".sprint3($stkd['qty'])."</td>
				<td align='right' nowrap>".CUR." ".sprint($stkd['unitcost'])."</td>
				<td align='right'>$stkd[udiscount]</td>
				<td align='center'>$dday-$dmon-$dyear</td>
				<td align='right' nowrap>".CUR." $stkd[amt]</td>
			</tr>";
	}
	$products .= "</table>";

	/*
	# Get supplier
	db_connect();
	$sql = "SELECT supname,supno FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		$sup['supname'] = "<li class=err>Supplier not found";
	}else{
		$sup = pg_fetch_array($supRslt);
	}
	*/

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}


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


	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Received Purchase Details</h3>
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
							<td valign='center'>$pur[supname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account number</td>
							<td valign='center'>$pur[supno]</td>
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
							<td>Order No.</td>
							<td valign='center'>$pur[ordernum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Approved By</td>
							<td valign='center'>$pur[appname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Approved Date</td>
							<td valign='center'>$pur[appdate]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'>$pur[refno]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Inv</td>
							<td>$pur[supinv]</td>
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
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges</td>
							<td valign='center' nowrap>".CUR." $pur[shipchrg]</td>
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
							<th width='50%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr>
							<td class='".bg_class()."'><a href='purchase-new.php'>New Purchase</a></td>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='purchase-view-prd.php'>View Received Purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
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

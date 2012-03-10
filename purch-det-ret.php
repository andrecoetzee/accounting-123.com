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
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
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
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_conn($prd);

	$sql = "SELECT * FROM purch_ret WHERE rpurid = '$rpurid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Returned Purchase Not Found </i>";
	}
	$pur = pg_fetch_array($purRslt);

	db_connect ();

	$get_codes = "SELECT * FROM suppstock WHERE suppid = (SELECT supid FROM suppliers WHERE supname = '$pur[supname]' LIMIT 1) ORDER BY stkid";
	$run_codes = db_exec ($get_codes) or errDie ("Unable to get supplier stock code information");
	if (pg_numrows ($run_codes) > 0){
		while ($codarr = pg_fetch_array ($run_codes)){
			if (strlen ($codarr['stkcod']) > 0) 
				$stockcodes[$codarr['stkid']]['stkcod'] = $codarr['stkcod'];
			if (strlen ($codarr['stkdes']) > 0) 
				$stockcodes[$codarr['stkid']]['stkdes'] = $codarr['stkdes'];
		}
	}

	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM purchases WHERE purid = '$pur[purid]' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Purchase Not Found </i>";
	}
	$rpur = pg_fetch_array($purRslt);

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
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT * FROM retpur_items  WHERE rpurid = '$rpurid'";
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

		db_conn($prd);
		# Get selected stock line
		$sql = "SELECT * FROM pur_items WHERE id = '$stkd[itemid]' AND purid = '$rpur[purid]' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);
		$stkt = pg_fetch_array($stktRslt);

		if ( $stk["stkid"] == 0 ) {
			$stk["stkcod"] = "";
			$stk["stkdes"] = $stkt["description"];
		}

		# format date
		list($dyear, $dmon, $dday) = explode("-", $stkt['ddate']);

		$amt = sprint($stkd['unitcost'] * $stkd['qty']);

		# put in product
		$products .="
			<tr bgcolor='".TMPL_tblDataColor1."'>
				<td>$wh[whname]</td>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td align='right'>$stkd[qty]</td>
				<td align='right' nowrap>".CUR." $stkd[unitcost]</td>
				<td align='center'>$dday-$dmon-$dyear</td>
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

	db_conn('cubit');

	$Sl="SELECT * FROM dnotes WHERE purid='$pur[purid]'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$dnotes = "
					<h3>Credit Notes</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Date</th>
							<th>Subtotal</th>
							<th>VAT</th>
							<th>Total</th>
						</tr>";

	$sub=0;
	$vat=0;
	$tot=0;
	$i=0;
	while($dd=pg_fetch_array($Ri)) {

		$dnotes .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$dd[date]</td>
							<td>$dd[sub]</td>
							<td>$dd[vat]</td>
							<td>$dd[tot]</td>
						</tr>";
		$sub+=$dd['sub'];
		$vat+=$dd['vat'];
		$tot+=$dd['tot'];
	}

	$sub=sprint($sub);
	$vat=sprint($vat);
	$tot=sprint($tot);

	$dnotes .= "
					<tr bgcolor='".bgcolorg()."'>
						<td></td>
						<td>$sub</td>
						<td>$vat</td>
						<td>$tot</td>
					</tr>
			</table>";


	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Returned Purchase Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Supplier</td>
										<td valign='center'>$pur[supname]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Account number</td>
										<td valign='center'>$rpur[supno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($rpur['supaddr'])."</td>
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
										<td valign='center'>$rpur[purnum]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Ref No.</td>
										<td valign='center'>$rpur[refno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Supplier Inv</td>
										<td>$rpur[supinv]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Terms</td>
										<td valign='center'>$rpur[terms] Days</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Date</td>
										<td valign='center'>$rday-$rmon-$ryear</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$rpur[vatinc]</td>
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
										<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New Purchase</a></td>
										<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='purchase-view-prd.php'>View Received Purchases</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<th>Total Cost Returned</th>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
					$dnotes
					</center>";
	return $details;

}


?>

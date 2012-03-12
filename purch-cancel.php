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
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = "<li class='err'> Ivalid use of module.</li>";
		}
	} else {
		$OUTPUT = "<li class='err'> Ivalid use of module.</li>";
	}
}

# get templete
require("template.php");




# details
function details($_POST, $error="")
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

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

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# check if purchase has never been received
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND rqty <> 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	if(pg_numrows($stkdRslt) > 0){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has been partly received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/*
	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class=err> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}
	*/

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Warehouses found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

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
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td>".sprint3($stkd['qty'])."</td>
				<td>".sprint($stkd['unitcost'])."</td>
				<td>$sday-$smon-$syear</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $pur['subtot'];

	# Get Total
	$TOTAL = $pur['total'];

	# Get vat
	$VAT = $pur['vat'];

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Order Cancel</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr><th colspan='2'> Supplier Details </th></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$pur[supname]</td>
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
							<th colspan='2'> Order Details </th>
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
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New Order</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View Orders</a></td>
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
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' name='upBtn' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}




# Details
function write($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/*
	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class=err>Supplier not found";
	}else{
		$sup = pg_fetch_array($supRslt);
	}
	*/

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();

		# Get selected stock
		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		while($stkt = pg_fetch_array($stktRslt)){
			# update stock(ordered + qty)
			$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			$sql = "
				INSERT INTO pur_canc_items (
					purid, whid, stkid, qty, ddate, div, 
					qpack, upack, ppack, svat, rqty, tqty, 
					unitcost, amt, iqty, vatcode, description, account
				) VALUES (
					'$stkt[purid]', '$stkt[whid]', '$stkt[stkid]', '$stkt[qty]', '$stkt[ddate]', '$stkt[div]', 
					'$stkt[qpack]', '$stkt[upack]', '$stkt[ppack]', '$stkt[svat]', '$stkt[rqty]', '$stkt[tqty]', 
					'$stkt[unitcost]', '$stkt[amt]', '$stkt[iqty]', '$stkt[vatcode]', '$stkt[description]', '$stkt[account]'
				)";
			db_exec($sql) or errDie("Unable to update stock to Cubit.");
		}

		# remove items
// 		$sql = "DELETE FROM pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
// 		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		# remove purchase
		$sql = "DELETE FROM purchases WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

		# Insert record
		$sql = "INSERT INTO cancelled_purch(purid, deptid, supid, supaddr, terms, pdate, ddate, remarks, received, refno, vatinc, prd, ordernum, part, div, purnum, edit, supname, supno, shipchrg, subtot, total, balance, vat, supinv, apprv, appname, rvat, rshipchrg, rsubtot, rtotal, jobid, jobnum, toggle, cash, shipping, invcd, rshipping, noted, returned, iamount, ivat, delvat, username) VALUES('$pur[purid]', '$pur[deptid]', '$pur[supid]', '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[remarks]', '$pur[received]', '$pur[refno]', '$pur[vatinc]', '$pur[prd]', '$pur[ordernum]', '$pur[part]', '$pur[div]', '$pur[purnum]', '$pur[edit]', '$pur[supname]', '$pur[supno]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '$pur[balance]', '$pur[vat]', '$pur[supinv]', '$pur[apprv]', '$pur[appname]', '$pur[rvat]', '$pur[rshipchrg]', '$pur[rsubtot]', '$pur[rtotal]', '$pur[jobid]', '$pur[jobnum]', '$pur[toggle]', '$pur[cash]', '$pur[shipping]', '$pur[invcd]', '$pur[rshipping]', '$pur[noted]', '$pur[returned]', '$pur[iamount]', '$pur[ivat]', '$pur[delvat]', '".USER_NAME."')";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

		# update purchase on the DB
		// $sql = "UPDATE purchases SET refno = '$refno', remarks = '$remarks' WHERE purid = '$purid'";
		// $rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Order Cancel</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Order from Supplier <b>$pur[supname]</b> has been cancelled.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-view.php'>View Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>

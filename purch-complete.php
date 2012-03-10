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

# Decide what to do
if (isset($HTTP_GET_VARS["purid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;
			case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = "<li class=err> Invalid use of module.";
		}
	} else {
		$OUTPUT = "<li class=err> Invalid use of module.";
	}
}

# Get templete
require("template.php");



# Details
function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# Validate input
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
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.</li>";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

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
	$whs .="</select>";

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
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RECEIVED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND (rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$stkd['amt'] = ($stkd['unitcost'] * $stkd['rqty']);

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td>$stkd[rqty]</td>
				<td>$stkd[unitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Purchase Complete</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
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
							<td valign='center'><input type='text' name='refno' size='10' value='$pur[refno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear DD-MM-YYYY</td>
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
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New purchase</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='text' name='subtot' size='10' value='$pur[subtot]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>".CUR." <input type='text' name='shipping' size='10' value='$pur[shipping]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." <input type='text' name='vat' size='10' value='$pur[vat]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='text' name='total' size='10' value='$pur[total]'></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' name='upBtn' value='Confirm'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



# Confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($subtot, "float", 1, 20, "Invalid Sub Total.");
	$v->isOk ($shipping, "float", 1, 20, "Invalid Delivery Charges.");
	$v->isOk ($vat, "float", 1, 20, "Invalid VAT");
	$v->isOk ($total, "float", 1, 20, "Invalid Total");

	$confirm="";
	$error="";
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
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.</li>";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

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
	$whs .="</select>";

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
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RECEIVED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND (rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$stkd['amt'] = ($stkd['unitcost'] * $stkd['rqty']);

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td>$stkd[rqty]</td>
				<td>$stkd[unitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

	$subtot = sprint($subtot);
	$vat = sprint($vat);
	$shipping = sprint($shipping);
	$total = sprint($subtot + $vat + $shipping);

/* -- Final Layout -- */

	$confirm = "
		<center>
		<h3>Confirm Purchase Complete</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
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
							<td valign='center'><input type='text' name='refno' size='10' value='$refno'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear DD-MM-YYYY</td>
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
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'><br></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New purchase</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$remarks</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='subtot' size='10' value='$subtot'>$subtot</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>".CUR." <input type='hidden' name='shipping' size='10' value='$shipping'>$shipping</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." <input type='hidden' name=vat size='10' value='$vat'>$vat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='hidden' name='total' size='10' value='$total'>$total</td>
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
	return $confirm;

}



# details
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($subtot, "float", 1, 20, "Invalid Sub Total.");
	$v->isOk ($shipping, "float", 1, 20, "Invalid Delivery Charges.");
	$v->isOk ($vat, "float", 1, 20, "Invalid VAT");
	$v->isOk ($total, "float", 1, 20, "Invalid Total");

	# used to generate errors
	$error = "asa@";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($HTTP_POST_VARS, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		// code here
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	# Get department info
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'> - Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}
	
	$sdate = date("Y-m-d");
	$date = $pur["pdate"];
	$refnum = getrefnum();

	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$subtot = sprint($subtot);
	$shipping = sprint($shipping);
	$vat = sprint($vat);
	$total = sprint($total);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# get selected stock in this purchase
		db_connect();
		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		while($stkt = pg_fetch_array($stktRslt)){
			$cqty = ($stkt['qty'] - $stkt['rqty']);

			# update stock(ordered - qty)
			$sql = "UPDATE stock SET ordered = (ordered - '$cqty')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

		/* - End Hooks - */

		# Record the payment on the statement
		db_connect();


		db_connect();
		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$total') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$sql = "
			INSERT INTO sup_stmnt (
				supid, edate, cacc, amount, descript, ref, ex, div
			) VALUES (
				'$pur[supid]', '$date', '$dept[credacc]', '$total', 'Stock Received - Purchase $pur[purnum]', '$refnum','$pur[purnum]','".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $dept['credacc'], $date, $refnum, ($total - $vat), "Invoice Received for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Transfer vat
		writetrans($vatacc, $dept['credacc'], $date, $refnum, $vat, "Vat Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Ledger Records
		suppledger($pur['supid'], $wh['conacc'], $date, $pur['purid'], "Purchase No. $pur[purnum] received.", $total, 'c');
		db_connect();

		/* End Transactions */

		/* Make transaction record  for age analysis */
			db_connect();
			# update the supplier age analysis (make balance less)
			if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
				# Found? Make amount less
				$sql = "UPDATE suppurch SET balance = (balance + '$total') WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}else{
				/* Make transaction record for age analysis */
				$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$pur[supid]', '$pur[purnum]', '$date', '$total', '".USER_DIV."')";
				$purcRslt = db_exec($sql) or errDie("Unable to update Order information in Cubit.",SELF);
			}

		/* Make transaction record  for age analysis */

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* Start moving if purchase */
	# copy purchase
	db_conn($pur['prd']);
	$sql = "
		INSERT INTO purchases (
			purid, deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, shipping, remarks, refno, received, done, div, purnum, supinv
		) VALUES (
			'$purid', '$pur[deptid]', '$pur[supid]', '$pur[supname]', '$pur[supaddr]', '$pur[supno]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$subtot', '$total', '0', '$pur[vatinc]', '$vat', '$shipping', '$remarks', '$refno', 'y', 'y', '".USER_DIV."', '$pur[purnum]','$pur[supinv]'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Purchase to Cubit.",SELF);


	/*-- Cost varience -- */
	$nsubtot = ($total - $vat);
	if($pur['rsubtot'] > $nsubtot){
		$diff = ($pur['rsubtot'] - $nsubtot);
		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $cvacc, $date, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
	}elseif($nsubtot > $pur['rsubtot']){
		$diff = ($nsubtot - $pur['rsubtot']);
		# Debit Stock Control and Credit Creditors control
		writetrans($cvacc, $wh['conacc'], $date, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
	}
	/*-- End Cost varience -- */

	db_connect();
	# Get selected stock
	$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND (rqty) > 0 AND div = '".USER_DIV."'";
	$stktcRslt = db_exec($sql);

	while($stktc = pg_fetch_array($stktcRslt)){
		$stktc['amt'] = ($stktc['unitcost'] * $stktc['rqty']);

		# Insert purchase items
		db_conn($pur['prd']);
		$sql = "
			INSERT INTO pur_items (
				purid, whid, stkid, qty, rqty, unitcost, amt, ddate, div, vatcode
			) VALUES (
				'$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[rqty]', '$stktc[rqty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[ddate]', '".USER_DIV."', '$stktc[vatcode]'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);
	}

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();
		# Remove the purchase from running DB
		$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

		# Record where purchase is
		$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
		$movRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

		# Remove those purchase items from running DB
		$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

	/* End moving purchase received */

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	// Final Layout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase Completed</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Purchase receipt from Supplier <b>$pur[supname]</b> has been completed.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-view.php'>View purchases</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>

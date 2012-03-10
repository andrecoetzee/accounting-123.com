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
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

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

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class=err> There are no Warehouses found in Cubit.";
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
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>STORE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY RECEIVED</th><th>UNIT PRICE</th><th>DELIVERY DATE</th><th>AMOUNT</th><tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT *,(qty - rqty) as qty FROM pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$stkd[whid]'>$wh[whname]</td><td><input type=hidden name=ids[] value='$stkd[id]'><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$stk[stkdes]</td><td><input type=hidden size=5 name=qts[] value='$stkd[qty]'><input type=text size=5 name=qtys[] value='$stkd[qty]'></td><td><input type=hidden size=4 name=unitcost[] value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "<center><h3>Stock Order received</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
   			<tr bgcolor='".TMPL_tblDataColor2."'><td>Supplier</td><td valign=center>$sup[supname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account number</td><td valign=center>$sup[supno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Supplier Address</td><td valign=center>".nl2br($supaddr)."</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Purchase Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Purchase No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Ref No.</td><td valign=center><input type=text name=refno size=10 value='$pur[refno]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td valign=center>$pday-$pmon-$pyear DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Inclusive</td><td valign=center>$pur[vatinc]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td valign=center>".CUR." <input type=hidden name=shipchrg size=10 value='$pur[shipchrg]'>$pur[shipchrg]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='purchase-new.php'>New Stock Order</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View Stock Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td align=right>".CUR." $pur[shipping]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input type=submit name='upBtn' value='Write'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($HTTP_POST_VARS)
{

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			/*if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}*/
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
		}
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($HTTP_POST_VARS, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li> - Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
		$dept['deptname'] = "<i class=err> - Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Insert purchase to DB
	db_connect();

	# If vat is not included (shipchrg)
	$VATP = TAX_VAT;
	if($pur['vatinc'] == "no"){
		$scvat = sprint(($VATP/100) * $shipchrg);
		$shipexvat = $shipchrg;
	}elseif($pur['vatinc'] == "yes"){
		$scvat = sprint(($shipchrg/($VATP+100)) * $VATP);
		$shipexvat = ($shipchrg - $scvat);
	}else{
		$scvat = 0;
		$shipexvat  = $shipchrg;
	}

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	if(isset($qtys)){
		# amount of stock in
		$totstkamt = array();
		$resub = 0;

		foreach($qtys as $keys => $value){
			# Skip zeros
			if($qtys[$keys]< 1){
				continue;
			}

			# Get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# Get selected stock line
			$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			if($pur['vatinc'] == "yes"){
				$unitcost[$keys] = sprint(($stkd['amt'] - $stkd['svat'])/$stkd['qty']);
			}else{
				$unitcost[$keys] = sprint(($stkd['amt'])/$stkd['qty']);
			}

			$perc[$keys] = sprint(($unitcost[$keys]/$pur['subtot']) * 100);

			# Get percentage from shipping charges excluding vat
			$shipc[$keys] = sprint(($perc[$keys] / 100) * $shipexvat);

			# add delivery charges = amt + del chrg excluding vat
			$unitcost[$keys] += $shipc[$keys];

			$amt[$keys] = sprint($qtys[$keys] * $unitcost[$keys]);

			$resub += $amt[$keys];

			# Update purchase items
			$sql = "UPDATE pur_items SET rqty = (rqty + '$qtys[$keys]') WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

			# Update stock(ordered + qty, units + qty, csamt + (csamt + amt))
			$sql = "UPDATE stock SET ordered = (ordered - '$qtys[$keys]'), units = (units + '$qtys[$keys]'), csamt = (csamt + '$amt[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# Keep records for transactions
			if(isset($totstkamt[$stk['whid']])){
				$totstkamt[$stk['whid']] += $amt[$keys];
			}else{
				$totstkamt[$stk['whid']] = $amt[$keys];
			}

			# Get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			$sdate = date("Y-m-d");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $sdate, $qtys[$keys], $amt[$keys], "Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]");
			db_connect();
			$cspric = sprint($amt[$keys]/$qtys[$keys]);
			$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
			VALUES('$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'purchase', '$qtys[$keys]', '$amt[$keys]', '$cspric', 'Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]', '".USER_DIV."')";
			$recRslt = db_exec($sql);

			# Just wanted to fix the xxx.xxxxxxe-x value
			if($stk['units'] > 0){
				$csprice = round(($stk['csamt']/$stk['units']), 2);
			}else{
				$csprice = round($stk['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice', lcsprice = '$cspric' WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# check if there are any outstanding items
			$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			# if none the set to received
			if(pg_numrows($stkdRslt) < 1){
				# update surch_int(received = 'y')
				$sql = "UPDATE purchases SET received = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update Orders in Cubit.",SELF);
			}
		}
	}

	# Update purchase on the DB
	if($pur['part'] == 'y'){
		$sql = "UPDATE purchases SET rsubtot = (rsubtot + '$resub'), refno = '$refno', remarks = '$remarks', edit = 1 WHERE purid = '$purid'";
	}else{
		$sql = "UPDATE purchases SET part = 'y', rsubtot = (rsubtot + '$resub'), refno = '$refno', remarks = '$remarks', edit = 1 WHERE purid = '$purid'";
	}
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	/* Transactions */

		db_conn(PRD_DB);
		# get last ref number
		$refnum = getrefnum();

	/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

	/* - End Hooks - */

		# Record transaction  from data
		foreach($totstkamt as $whid => $wamt){
			# get whouse info
			db_conn("exten");
			$sql = "SELECT stkacc,conacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Debit Stock and Credit Stock control
			writetrans($wh['stkacc'], $wh['conacc'], date("d-m-Y"), $refnum, $wamt, "Stock Received for Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* Start moving if Order received and invoiced */

		# Get purchase info
		db_connect();
		$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li> - Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y" && $pur['invcd'] == 'y'){

			# copy purchase
			db_conn($pur['prd']);
			$sql = "INSERT INTO purchases(purid, deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, shipping, remarks, refno, received, done, div, purnum, supinv)";
			$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[shipping]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]','$pur[supinv]')";
			$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

			/*-- Cost varience -- */
			$nsubtot = ($pur['total'] - $pur['vat']);
			if($pur['rsubtot'] > $nsubtot){
				$diff = ($pur['rsubtot'] - $nsubtot);
				# Debit Stock Control and Credit Creditors control
				writetrans($wh['conacc'], $cvacc, date("d-m-Y"), $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}elseif($nsubtot > $pur['rsubtot']){
				$diff = ($nsubtot - $pur['rsubtot']);
				# Debit Stock Control and Credit Creditors control
				writetrans($cvacc, $wh['conacc'], date("d-m-Y"), $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}
			/*-- End Cost varience -- */

			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert purchase items
				db_conn($pur['prd']);
				$sql = "INSERT INTO pur_items(purid, whid, stkid, qty, rqty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();
			# Remove the purchase from running DB
			$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Record where purchase is
			$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

/* End moving purchase received */

	// Final Layout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Order received</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Order receipt from Supplier <b>$sup[supname]</b> has been recorded.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

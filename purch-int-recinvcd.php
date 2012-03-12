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
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");

# details
function details($_POST, $error="")
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# Display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been received
	if($pur['invcd'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.";
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
		$sup['supname'] = "<li class='err'> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# Days drop downs
	$days = array("0"=>"0", "30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# currency drop downs
	$currs = array("R"=>"Rand","USD"=>"US Dollar","EU"=>"Euro","UKP"=>"UK Pound");
	$currsel = extlib_cpsel("curr", $currs, $pur['curr']);

	# Format dates
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

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
			<th>DUTY</th>
			<th>LINE TOTAL</th>
			<th>COST PER UNIT</th>
		<tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

	/* -- Calculations -- */

		# Calculate cost amount bought
		$totamt = ($stkd['qty'] * $stkd['cunitcost']);

		# Calculate percentage from subtotal
		$perc = (($totamt/$pur['subtot']) * 100);

		# Get percentage from shipping charges
		$shipchrg = (($perc / 100) * $pur['shipchrg']);

		# add shipping charges to amt
		$totamt = sprint(($totamt + $shipchrg), 2);

	/* -- End Calculations --*/

		# put in product
		$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td>$stkd[qty]</td>
					<td>$pur[curr] $stkd[cunitcost] &nbsp;&nbsp;or &nbsp;&nbsp;".CUR." $stkd[unitcost]</td>
					<td>$pur[curr] $stkd[duty]&nbsp;&nbsp; or &nbsp;&nbsp;$stkd[dutyp]%</td>
					<td nowrap>$pur[curr] $stkd[amt]</td>
					<td align='right' nowrap>$pur[curr] $totamt</td>
				</tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$details = "
			<center>
			<h3>Record International Purchase Invoice</h3>
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
								<th colspan='2'> Order Details </th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Order No.</td>
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
								<td valign='center'>$pday-$pmon-$pyear</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Foreign Currency</td>
								<td valign='center'>$pur[curr] &nbsp;&nbsp;Exchange rate ".CUR." $pur[xrate]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Tax</td>
								<td valign='center'>$pur[curr] $pur[tax]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Shipping Charges</td>
								<td valign='center' nowrap>$pur[curr] $pur[fshipchrg]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
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
						<p>
						<table ".TMPL_tblDflts.">
							<tr>
								<th width='25%'>Quick Links</th>
								<th width='25%'>Remarks</th>
								<td rowspan='5' valign='top' width='50%'>$error</td>
							</tr>
							<tr>
								<td bgcolor='".bgcolorg()."'><a href='purch-int-view.php'>View International Orders</a></td>
								<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
					<td align=right>
						<table ".TMPL_tblDflts." width='80%'>
							<tr bgcolor='".bgcolorg()."'>
								<td>SUBTOTAL</td>
								<td align='right' nowrap>$pur[curr] $pur[subtot]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Shipping Charges</td>
								<td align='right' nowrap>$pur[curr] $pur[shipchrg]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Tax </td>
								<td align='right' nowrap>$pur[curr] $pur[tax]</td></tr>
							<tr bgcolor='".bgcolorg()."'>
								<th>GRAND TOTAL</th>
								<td align='right' nowrap>$pur[curr] $pur[total]</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | </td>
					<td><input type='submit' name='upBtn' value='Write'></td>
				</tr>
			</table>
			</form>
			</center>";

	return $details;
}

# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");

	# used to generate errors
	$error = "asa@";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pur['pdate']) >= strtotime($blocked_date_from) AND strtotime($pur['pdate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# check if Order has been received
	if($pur['invcd'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.</li>";
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

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}
	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# insert Order to DB
	db_connect();
# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* --- Transactions --- */
		db_conn(PRD_DB);
		$refnum = getrefnum();

	/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

	/* - End Hooks - */

		# Record the payment on the statement
		db_connect();
		$sdate = date("Y-m-d");
		$taxamt = ($pur['tax'] * (-1));

		$ltotal = sprint($pur['total'] * $pur['xrate']);
		$ltax = sprint($pur['tax'] * $pur['xrate']);

		db_connect();
		# update all supplies xchange rate first
		xrate_update($pur['fcid'], $pur['xrate'], "suppurch", "id");
		sup_xrate_update($pur['fcid'], $pur['xrate']);


		db_connect();
		# Update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$ltotal'), fbalance = (fbalance + '$pur[total]') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$DAte = date("Y-m-d");

		$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, amount, descript, ref, ex, div) VALUES('$pur[supid]','$pur[pdate]', '$dept[credacc]','$pur[total]','International - Stock Received', '$refnum','$pur[purnum]', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);


		$Sl="SELECT * FROM vatcodes WHERE id='$pur[jobnum]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd=pg_fetch_array($Ri);

		vatr($vd['id'],$pur['pdate'],"INPUT",$vd['code'],$refnum,"VAT for Purchase No. $pur[purnum]",-($ltotal + $ltax),-$ltax);

		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $dept['credacc'], $pur['pdate'], $refnum, ($ltotal - $ltax), "Invoice Received for Purchase No. $pur[purnum] from Supplier : $sup[supname].");

		# Debit bank and credit the account involved
		writetrans($vatacc, $dept['credacc'], $pur['pdate'], $refnum, $ltax, "Tax Paid on International Orders No. $pur[purnum] from Supplier $sup[supname].");

		# Ledger Records
		suppledger($pur['supid'], $wh['stkacc'], $pur['pdate'], $pur['purid'], "International Order No. $pur[purnum] received.", $ltotal, 'c');
		db_connect();

	/* --- End Transactions --- */

	/* Make transaction record  for age analysis */

		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fcid, fbalance, div) VALUES('$pur[supid]', '$pur[purnum]', '$pur[pdate]', '$ltotal', '$pur[fcid]', '$pur[total]', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

	/* Make transaction record  for age analysis */

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


/* Start moving if Order received and invoiced */
	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	if($pur['received'] == "y"){
		# Copy Order
		db_conn($pur['prd']);
		$sql = "INSERT INTO purch_int(purid, deptid, supid, supaddr, terms, pdate, ddate, xrate, fcid, curr, tax, shipchrg, fshipchrg, duty, subtot, total, balance, fbalance, remarks, refno, received, done, div, purnum)";
		$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[xrate]', '$pur[fcid]', '$pur[curr]', '$pur[tax]', '$pur[shipchrg]', '$pur[fshipchrg]', '$pur[duty]', '$pur[subtot]', '$pur[total]', '0', '$pur[fbalance]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]')";
		$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

	/*-- Cost varience -- */
		$nsubtot = sprint($pur['total'] - $pur['tax']);
		$nsubtot = sprint($nsubtot * $pur['xrate']);

		if($pur['rlsubtot'] > $nsubtot){
			$diff = sprint(($pur['rlsubtot'] - $nsubtot));
			# Debit Stock Control and Credit Creditors control
			writetrans($wh['conacc'], $cvacc, $pur['pdate'], $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}elseif($nsubtot > $pur['rlsubtot']){
			$diff = sprint(($nsubtot - $pur['rlsubtot']));
			# Debit Stock Control and Credit Creditors control
			writetrans($cvacc, $wh['conacc'], $pur['pdate'], $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}
	/*-- End Cost varience -- */

		db_connect();
		# Get selected stock
		$sql = "SELECT * FROM purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktcRslt = db_exec($sql);

		while($stktc = pg_fetch_array($stktcRslt)){
			# Insert Order items
			db_conn($pur['prd']);
			$sql = "INSERT INTO purint_items(purid, whid, stkid, qty, unitcost, cunitcost, duty, dutyp, amt, ddate, recved, div) VALUES('$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[cunitcost]', '$stktc[duty]', '$stktc[dutyp]', '$stktc[amt]', '$stktc[ddate]', 'y', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
		}

		db_connect();
		# Remove the Order from running DB
		$sql = "DELETE FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

		# Record where Order is
		$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('int', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
		$movRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

		# Remove those Order items from running DB
		$sql = "DELETE FROM purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
	}else{
		# Insert Order to DB
		$sql = "UPDATE purch_int SET invcd = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);
	}

/* End moving Order received */

	// Final Layout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>International Purchase Invoiced</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Purchase Invoice from Supplier <b>$sup[supname]</b> has been recorded.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purch-int-view.php'>View International Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}

?>

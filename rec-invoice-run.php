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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			# decide what to do
			if (isset($HTTP_GET_VARS["invid"])) {
				$OUTPUT = details($HTTP_GET_VARS);
			} else {
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
			}
		}
} else {
	# decide what to do
	if (isset($HTTP_GET_VARS["invid"])) {
		$OUTPUT = details($HTTP_GET_VARS);
	} else {
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
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
	$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");

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

	# Get recuring invoice info
	db_connect();

	$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	if (isset($inv['cusnum']) AND $inv['cusnum'] != "0"){
		#customer set ... check if he's blocked
		$cust_sql = "SELECT cusnum FROM customers WHERE cusnum = '$inv[cusnum]' AND blocked != 'yes'";
		$run_cust = db_exec ($cust_sql) or errDie ("Unable to get customer information.");
		if (pg_numrows($run_cust) < 1) {
			#blocked? customer found !!
			return "<li class='err'>Invalid Customer/Customer is blocked.</li><br><input type='button' onClick=\"document.location='rec-invoice-view.php';\" value='&laquo Correction'>";
		}
	}

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$inv['chrgvat'] = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$inv['chrgvat'] = "No";
	}else{
		$inv['chrgvat'] = "Non Vat";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>WAREHOUSE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
			<tr>";

		# get selected stock in this recuring invoice
		db_connect();

		$sql = "SELECT * FROM recinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			# get warehouse name
			db_conn("exten");

			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			if($stkd['account'] == 0) {
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
			} else {
				$wh['whname'] = "";
				$stk['stkcod'] = "";
				$stk['stkdes'] = $stkd['description'];
			}

// 			# get selected stock in this warehouse
// 			db_connect();
// 			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
// 			$stkRslt = db_exec($sql);
// 			$stk = pg_fetch_array($stkRslt);

			# put in product
			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td>".sprint3($stkd['qty'])."</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
					<td nowrap>".CUR." $stkd[amt]</td>
				</tr>";
		}
	$products .= "</table>";

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if(strlen($inv['traddisc']) > 0){
		$traddiscm = sprint(($inv['traddisc']/100) * $SUBTOT);
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	# Format date
	list($o_year, $o_month, $o_day) = explode("-", $inv['odate']);

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Accept Recurring Invoice</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='invid' value='$invid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts." width='40%'>
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Vat Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'>".nl2br($inv['comm'])."</pre></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice No.</td>
							<td valign='center'>RI $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Sales Person</td>
							<td valign='center'>$inv[salespn]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Date</td>
							<td valign='center'>".mkDateSelect("o",$o_year,$o_month,$o_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'>$inv[traddisc]%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'>$inv[delchrg]</td>
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
						<p>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='rec-invoice-new.php'>New Recurring Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $traddiscm</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right' nowrap>".CUR." $inv[delchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT @ $VATP%</b></td>
							<td align='right' nowrap>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td><input type='submit' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}




# Details
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	$odate = $o_year."-".$o_month."-".$o_day;
	if(!checkdate($o_month, $o_day, $o_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	# Get recuring invoice info
	db_connect();

	$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	db_connect();

	/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Insert invoice to DB
	$sql = "
		INSERT INTO invoices (
			deptid, cusnum, deptname, cusacc, cusname, surname, 
			cusaddr, cusvatno, cordno, ordno, chrgvat, terms, 
			traddisc, salespn, odate, delchrg, subtot, vat, discount, 
			delivery, total, balance, comm, printed, done, prd, serd, div, 
			jobid
		) VALUES (
			'$inv[deptid]', '$inv[cusnum]', '$inv[deptname]', '$inv[cusacc]', '$inv[cusname]', '$inv[surname]', 
			'$inv[cusaddr]', '$inv[cusvatno]', '$inv[cordno]', '$inv[ordno]', '$inv[chrgvat]', '$inv[terms]', 
			'$inv[traddisc]', '$inv[salespn]', '$odate', '$inv[delchrg]', '$inv[subtot]', '$inv[vat]' , '$inv[discount]', 
			'$inv[delivery]', '$inv[total]', '$inv[total]', '$inv[comm]', 'n', 'y', '".PRD_DB."', 'n', '".USER_DIV."', 
			'$invid'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = lastinvid();

	# get selected stock in this recuring invoice
	db_connect();

	$sql = "SELECT * FROM recinv_items  WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$serd = "y";
	while($stkd = pg_fetch_array($stkdRslt)){
		# Insert one by one per quantity
		if(ext_isSerial("stock", "stkid", $stkd['stkid'])){
			$stkd['amt'] = sprint($stkd['amt']/$stkd['qty']);
			$serd = "n";
			for($i = 0; $i < $stkd['qty']; $i++){
				# insert invoice items
				$sql = "
					INSERT INTO inv_items (
						invid, whid, stkid, qty, unitcost, amt, disc, 
						discp, div, account, vatcode, description
					) VALUES (
						'$invid', '$stkd[whid]', '$stkd[stkid]', '1', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', 
						'$stkd[discp]', '".USER_DIV."', '$stkd[account]', '$stkd[vatcode]', '$stkd[description]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
			}
		}else{
			# insert invoice items
			$sql = "
				INSERT INTO inv_items (
					invid, whid, stkid, qty, unitcost, amt, disc, 
					discp, div, account, vatcode, description
				) VALUES (
					'$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', 
					'$stkd[discp]', '".USER_DIV."', '$stkd[account]', '$stkd[vatcode]', '$stkd[description]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		# update stock(alloc + qty)
		$sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
	}

	# set to not serialised
	$sql = "UPDATE invoices SET serd = '$serd' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	/* - End Copying - */

	// Final Laytout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Recurring Invoice Proccesed</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>New Invoice for customer <b>$inv[cusname] $inv[surname]</b> has been created from a Recurring Invoice No. RI $inv[invid]</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
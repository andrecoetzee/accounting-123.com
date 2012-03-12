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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "write":
				$OUTPUT = write($_POST);
				break;

            default:
				# decide what to do
				if (isset($_GET["sordid"])) {
					$OUTPUT = details($_GET);
				} else {
					$OUTPUT = "<li class='err'>Invalid use of module.</li>";
				}
			}
} else {
	# decide what to do
	if (isset($_GET["sordid"])) {
		$OUTPUT = details($_GET);
	} else {
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
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
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order number.");

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

	# Get Sales Order info
	db_connect();
	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$sord = pg_fetch_array($sordRslt);

	# Keep the charge vat option stable
	if($sord['chrgvat'] == "inc"){
		$sord['chrgvat'] = "Yes";
	}elseif($sord['chrgvat'] == "exc"){
		$sord['chrgvat'] = "No";
	}else{
		$sord['chrgvat'] = "Non Vat";
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
		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM sorders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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

			# put in product
			$products .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$wh[whname]</td>
								<td>$stk[stkcod]</td>
								<td>$stk[stkdes]</td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
								<td nowrap>".CUR." $stkd[amt]</td>
							</tr>";
	}
	$products .= "</table>";

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = $sord['subtot'];

	# Calculate tradediscm
	if(strlen($sord['traddisc']) > 0){
		$traddiscm = round((($sord['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($sord['subtot']);
 	$VAT = sprint($sord['vat']);
	$TOTAL = sprint($sord['total']);
	$sord['delchrg'] = sprint($sord['delchrg']);

	/*
	# minus discount
	# $SUBTOT -= $disc; --> already minused

	# duplicate
	$SUBTOTAL = $SUBTOT;

	# minus trade discount
	$SUBTOTAL -= $traddiscm;

	# add del charge
	$SUBTOTAL += $sord['delchrg'];


	# if vat must be charged
	if($sord['chrgvat'] == "yes"){
		$VATP = TAX_VAT;
		$VAT = sprintf("%01.2f", (($VATP/100) * $SUBTOTAL));
	}else{
		$VATP = 0;
		$VAT = "0.00";
	}

	# total
	$TOTAL = $SUBTOTAL + $VAT;
	*/

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Cancel Sales Order</h3>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='sordid' value='$sordid'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts." width='40%'>
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Department</td>
										<td valign='center'>$sord[deptname]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer</td>
										<td valign='center'>$sord[cusname] $sord[surname]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td valign='top'>Customer Address</td>
										<td valign='center'>".nl2br($sord['cusaddr'])."</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer Order number</td>
										<td valign='center'>$sord[cordno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer Vat Number</td>
										<td>$sord[cusvatno]</td>
									</tr>
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td colspan='2' align='center'>".nl2br($sord['comm'])."</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Sales Order Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Sales Order No.</td>
										<td valign='center'>$sord[sordid]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Order No.</td>
										<td valign='center'>$sord[ordno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$sord[chrgvat]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Terms</td>
										<td valign='center'>$sord[terms] Days</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Sales Person</td>
										<td valign='center'>$sord[salespn]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Sales Order Date</td>
										<td valign='center'>$sord[odate]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td valign='center'>$sord[traddisc]%</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td valign='center'>$sord[delchrg]</td>
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
										<td><a href='sorder-new.php'>New Sales Order</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='sorder-view.php'>View Sales Orders</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align=right>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td align='right'>".CUR." $traddiscm</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td align='right'>".CUR." $sord[delchrg]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." $TOTAL</td>
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


# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}

	# Get Sales Order info
	db_connect();
	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND accepted != 'c' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li class='err'>Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);

	db_connect();
	/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# todays date (sql formatted)
		$date = date("Y-m-d");

		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM sorders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# update stock(alloc - qty)
			$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# remove the Sales Order
 		$sql = "DELETE FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
 		$rslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit.",SELF);

		$sql = "INSERT INTO cancelled_sord (sordid, deptid, username, date,
					deptname, div, cusnum, cordno, ordno, chrgvat, terms,
					salespn, odate, comm, cusacc, cusname, surname, cusaddr,
					cusordno, cusvatno, prd, disc, discp, delchrg, subtot,
					traddisc, balance, vat, total, jobid, jobnum, dir,
					location, fcid, currency, xrate, fbalance, fsubtot,
					discount, delivery, delvat, display_costs, proforma,
					pinvnum)
				VALUES ('$sordid', '$sord[deptid]', '".USER_NAME."', '$date',
					'$sord[deptname]', '".USER_DIV."', '$sord[cusnum]',
					'$sord[cordno]', '$sord[ordno]', '$sord[chrgvat]',
					'$sord[terms]', '$sord[salespn]', '$sord[odate]',
					'$sord[comm]', '$sord[cusacc]', '$sord[cusname]',
					'$sord[surname]', '$sord[cusaddr]', '$sord[cusordno]',
					'$sord[cusvatno]', '$sord[prd]', '$sord[disc]',
					'$sord[discp]', '$sord[delchrg]', '$sord[subtot]',
					'$sord[traddisc]', '$sord[balance]', '$sord[vat]',
					'$sord[total]', '$sord[jobid]', '$sord[jobnum]',
					'$sord[dir]', '$sord[location]', '$sord[fcid]',
					'$sord[currency]', '$sord[xrate]', '$sord[fbalance]',
					'$sord[fsubtot]', '$sord[discount]', '$sord[delivery]',
					'$sord[delvat]', '$sord[display_costs]',
					'$sord[proforma]', '$sord[pinvnum]')";
		$rslt = db_exec($sql) or errDie("Unable to insert Sales Order record to Cubit.",SELF);


	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	/* - End Copying - */

	// Final Laytout
	$write = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Sales Order Cancelled</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Sales Order has been cancelled.</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='sorder-view.php'>View Sales Orders</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}


?>
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
				$OUTPUT = "<li class=err>Invalid use of module.";
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
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Orders number.");

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
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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
		$sord['chrgvat'] = "Non VAT";
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
		$sql = "SELECT * FROM corders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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

			// Stock or non stock description?
			if ($stkd["account"] > 0) {
				$description = $stkd["description"];
			} else {
				$description = $stk["stkdes"];
			}

			# put in product
			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td>$stk[stkcod]</td>
					<td>$description</td>
					<td>$stkd[qty]</td>
					<td>$stkd[unitcost]</td>
					<td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
					<td>".CUR." $stkd[amt]</td>
				</tr>";
		}
	$products .= "</table>";

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($sord['subtot']);

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($sord['subtot']);
 	$VAT = sprint($sord['vat']);
	$TOTAL = sprint($sord['total']);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Accept Consignment Order</h3>
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
							<td valign=top>Customer Address</td>
							<td valign='center'>".nl2br($sord['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$sord[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer VAT Number</td>
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
							<th colspan='2'>Consignment Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Consignment Order No.</td>
							<td valign='center'>$sord[sordid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$sord[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td>$sord[chrgvat]</td>
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
							<td>Date</td>
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
			<tr><td colspan='2'>$products</td></tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<p>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-new.php'>New Consignment Order</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-view.php'>View Consignment Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right'>".CUR." $sord[discount]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right'>".CUR." $sord[delivery]</td>
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
				<td><input type='submit' value='Invoice'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}




# details
function write($_POST)
{

	# Get vars
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
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li class='err'>Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);

/* - Start Copying - */
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

//'$sord[cordno]'

		db_connect();
		# Insert invoice to DB
		$sql = "
			INSERT INTO invoices (
				deptid, cusnum, deptname, cusacc, 
				cusname, surname, cusaddr, cusvatno, 
				cordno, ordno, chrgvat, terms, 
				traddisc, salespn, odate, delchrg, 
				subtot, vat, discount, delivery, 
				total, balance, comm, printed, 
				done, serd, prd, div
			) VALUES (
				'$sord[deptid]', '$sord[cusnum]', '$sord[deptname]', '$sord[cusacc]', 
				'$sord[cusname]', '$sord[surname]', '$sord[cusaddr]', '$sord[cusvatno]', 
				'$sord[sordid]', '$sord[ordno]', '$sord[chrgvat]', '$sord[terms]', 
				'$sord[traddisc]', '$sord[salespn]', '$sord[odate]', '$sord[delchrg]', 
				'$sord[subtot]', '$sord[vat]' , '$sord[discount]', '$sord[delivery]', 
				'$sord[total]', '$sord[total]', '$sord[comm]', 'n', 
				'y', 'n', '".PRD_DB."', '".USER_DIV."'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

		# get next ordnum
		$invid = lastinvid();

		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM corders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$serd = "y";
		while($stkd = pg_fetch_array($stkdRslt)){
			# Insert one by one per quantity
			if(ext_isSerial("stock", "stkid", $stkd['stkid'])){
				$stkd['amt'] = sprint($stkd['amt']/$stkd['qty']);
				$serd = "n";
				for($i = 0; $i < $stkd['qty']; $i++){
					# insert invoice items
					$stkd['vatcode'] += 0;
					$stkd['account'] += 0;
					$sql = "
						INSERT INTO inv_items (
							invid, whid, stkid, qty, unitcost, 
							amt, disc, discp, div, 
							vatcode, description, account
						) VALUES (
							'$invid', '$stkd[whid]', '$stkd[stkid]', '1', '$stkd[unitcost]', 
							'$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."', 
							'$stkd[vatcode]','$stkd[description]','$stkd[account]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
			}else{
				$stkd['vatcode'] += 0;
				$stkd['account'] += 0;
				# insert invoice items
				$sql = "
					INSERT INTO inv_items (
						invid, whid, stkid, qty, unitcost, 
						amt, disc, discp, div, vatcode, 
						description, account
					) VALUES (
						'$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', 
						'$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]', 
						'$stkd[description]','$stkd[account]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
			}

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# set to not serialised
		db_connect();
		$sql = "UPDATE invoices SET serd = '$serd' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		# get selected stock in this Sales Order
		$sql = "SELECT * FROM cord_data  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$dataRslt = db_exec($sql);
		$data = pg_fetch_array($dataRslt);

		$sql = "
			INSERT INTO inv_data (
				invid, dept, customer, addr1, div
			) VALUES (
				'$invid', '$data[dept]', '$data[customer]', '$data[addr1]', '".USER_DIV."'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

		# remove access data
		$sql = "DELETE FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		//$rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

		$sql = "DELETE FROM corders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		//$rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

		$sql = "DELETE FROM cord_data WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		//$rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

/*- End copying -*/
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Copying - */
	header ("Location: cust-credit-stockinv.php?invid=$invid&cont=true&letters=&done=");
	exit;


	// Final Laytout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Consignment Order accepted</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Consignment Order for customer <b>$sord[cusname] $sord[surname]</b> has been accepted</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='corder-view.php'>View Consignment Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
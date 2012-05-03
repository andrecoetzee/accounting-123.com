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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			# decide what to do
			if (isset($_GET["quoid"])) {
				$OUTPUT = confirm($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
	}
} else {
	# decide what to do
	if (isset($_GET["quoid"])) {
		$OUTPUT = confirm($_GET);
	} else {
		$OUTPUT = "<li class=err>Invalid use of module.";
	}
}

# get templete
require("template.php");


# details
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($quoid, "num", 1, 20, "Invalid Quote number.");

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

	# Get quote info
	db_connect();
	$sql = "SELECT * FROM pos_quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

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

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM pos_quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
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
							<tr class='".bg_class()."'>
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
	$SUBTOT = sprint($quo['subtot']);

	# Calculate tradediscm
	if(strlen($quo['traddisc']) > 0){
		$traddiscm = sprint(($quo['traddisc']/100) * $SUBTOT);
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);
	$quo['delchrg'] = sprint($quo['delchrg']);

	/*
	# minus discount
	# $SUBTOT -= $disc; --> already minused

	# duplicate
	$SUBTOTAL = $SUBTOT;

	# Minus trade discount
	$SUBTOTAL -= $traddiscm;

	# Add del charge
	$SUBTOTAL += $quo['delchrg'];

	# If vat must be charged
	if($quo['chrgvat'] == "yes"){
		$VATP = TAX_VAT;
		$VAT = sprintf("%01.2f", (($VATP/100) * $SUBTOTAL));
	}else{
		$VATP = 0;
		$VAT = "0.00";
	}

	# Total
	$TOTAL = sprint($SUBTOTAL + $VAT);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$confirm = "
					<center>
					<h3>Accept Quote</h3>
					<h4>Confirm</h4>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='quoid' value='$quoid'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts." width='40%'>
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$quo[deptname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer</td>
										<td valign='center'>$quo[cusname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign='top'>Customer Address</td>
										<td valign='center'>".nl2br($quo['cusaddr'])."</td>
									</tr>
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr class='".bg_class()."'>
										<td colspan='2' align='center'>".nl2br($quo['comm'])."</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Quote Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Quote No.</td>
										<td valign='center'>$quo[quoid]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Order No.</td>
										<td valign='center'>$quo[ordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Charge VAT</td>
										<td valign='center'>$quo[chrgvat]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$quo[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Sales Person</td>
										<td valign='center'>$quo[salespn]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Quote Date</td>
										<td valign='center'>$quo[odate]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td valign='center'>$quo[traddisc]%</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td valign='center'>$quo[delchrg]</td>
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
									<tr><th>Quick Links</th></tr>
									<tr class='".bg_class()."'><td><a href='quote-new.php'>New Quote</a></td></tr>
									<tr class='".bg_class()."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Trade Discount</td>
										<td align='right'>".CUR." $traddiscm</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>".CUR." $quo[delchrg]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
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
	return $confirm;

}


# Details
function write($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($quoid, "num", 1, 20, "Invalid Quote number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	# Get quote info
	db_connect();
	$sql = "SELECT * FROM pos_quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<li class='err'>Quote Not Found</li>";
	}
	$quo = pg_fetch_array($quoRslt);

/* - Start Copying - */
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();

		$Sl="SELECT * FROM posround";
		$Ri=db_exec($Sl);

		$data=pg_fetch_array($Ri);

		$TOTAL=$quo['total'];

		if($data['setting']=="5cent") {
			if(sprint(floor(sprint($TOTAL/0.05)))!=sprint($TOTAL/0.05)) {
				$otot=$TOTAL;
				$nTOTAL=sprint(sprint(floor($TOTAL/0.05))*0.05);
				$rounding=($otot-$nTOTAL);
			} else {
				$rounding=0;
			}
		} else {
			$rounding=0;
		}


		# Insert invoice to DB
		$sql = "INSERT INTO pinvoices(deptid, deptname, cusname, surname, cusaddr, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, printed, done, prd, div,rounding,delvat)";
		$sql .= " VALUES('$quo[deptid]', '$quo[deptname]', '$quo[cusname]', '$quo[surname]', '$quo[cusaddr]', '$quo[ordno]', '$quo[chrgvat]', '$quo[terms]', '$quo[traddisc]', '$quo[salespn]', '$quo[odate]', '$quo[delchrg]', '$quo[subtot]', '$quo[vat]' , '$quo[total]', '$quo[total]', '$quo[comm]', 'n', 'y', '".PRD_DB."', '".USER_DIV."','$rounding', '$quo[delvat]')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

		# get next ordnum
		$invid = lastinvid();

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM pos_quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);


		while($stkd = pg_fetch_array($stkdRslt)){
			# insert invoice items
			$stkd['vatcode']+=0;
			$stkd['account']+=0;
			$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div,vatcode,description,account) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]','$stkd[description]','$stkd[account]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		db_connect();
		# set to accepted
		$sql = "UPDATE pos_quotes SET accepted = 'y' WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		/* Remove access data
		$sql = "DELETE FROM pos_quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		$sql = "DELETE FROM pos_quote_items WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);
		*/

/* - End Copying - */
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	#redirect to pos invoice screen ...
	header ("Location: pos-invoice-new.php?invid=$invid&cont=1");
	
	// Final Laytout
	$write = "
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quote accepted</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Quote for customer <b>$quo[cusname]</b> has been accepted</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='pos-quote-view.php'>View Pos Quotes</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
//	return $write;

}


?>
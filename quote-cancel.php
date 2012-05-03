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
				if (isset($_GET["quoid"])) {
					$OUTPUT = details($_GET);
				} else {
					$OUTPUT = "<li class='err'>Invalid use of module.</li>";
				}
			}
} else {
	# decide what to do
	if (isset($_GET["quoid"])) {
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

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
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	# Keep the charge vat option stable
	if($quo['chrgvat'] == "inc"){
		$quo['chrgvat'] = "Yes";
	}elseif($quo['chrgvat'] == "exc"){
		$quo['chrgvat'] = "No";
	}else{
		$quo['chrgvat'] = "Non Vat";
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
		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			if($stkd['account']==0) {

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
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			# put in product
			$products .= "
							<tr class='".bg_class()."'>
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
	$SUBTOT = $quo['subtot'];

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Cancel Quote</h3>
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
										<td valign='center'>$quo[cusname] $quo[surname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign=top>Customer Address</td>
										<td valign='center'>".nl2br($quo['cusaddr'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Order number</td>
										<td valign='center'>$quo[cordno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Customer Vat Number</td>
										<td>$quo[cusvatno]</td>
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
										<td>VAT Inclusive</td>
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
									<tr>
										<th>Quick Links</th></tr>
									<tr class='".bg_class()."'>
										<td><a href='quote-new.php'>New Quote</a></td></tr>
									<tr class='".bg_class()."'>
										<td><a href='quote-view.php'>View Quotes</a></td></tr>
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
										<td align='right'>".CUR." $quo[discount]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charge</td>
										<td align='right'>".CUR." $quo[delivery]</td>
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

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
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND accepted != 'c' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<li class='err'>Quote Not Found</li>";
	}
	$quo = pg_fetch_array($quoRslt);

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$quo[cusnum]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM quo_data WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	db_connect();
	/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# todays date (sql formatted)
		$date = date("Y-m-d");

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		# remove the Quote
		$sql = "DELETE FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Quote from Cubit.",SELF);

		#record (quoid, username, date)
		$sql = "INSERT INTO cancelled_quo(quoid, deptid, username, date, div) VALUES('$quoid', '$quo[deptid]', '".USER_NAME."', '$date', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert Quote record to Cubit.",SELF);


	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	/* - End Copying - */

	// Final Laytout
	$write = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quote canceled</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Quote for customer <b>$cust[cusname] $cust[surname]</b> has been canceled.</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='quote-view.php'>View Quotes</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}


?>
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
		$OUTPUT = "<li class=err>Invalid use of module.";
	}
}

# get templete
require("template.php");

# details
function details($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Sales Order info
	db_connect();
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<i class=err>Not Found</i>";
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
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>";
		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM corders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td><td>".CUR." $stkd[amt]</td></tr>";
	}
	$products .= "</table>";

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = $sord['subtot'];

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($sord['subtot']);
 	$VAT = sprint($sord['vat']);
	$TOTAL = sprint($sord['total']);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "<center><h3>Cancel Consignment Order</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=sordid value='$sordid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$sord[deptname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer</td><td valign=center>$sord[cusname] $sord[surname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Customer Address</td><td valign=center>".nl2br($sord['cusaddr'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Order number</td><td valign=center>$sord[cordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer VAT Number</td><td>$sord[cusvatno]</td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center>".nl2br($sord['comm'])."</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Sales Order Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Order No.</td><td valign=center>$sord[sordid]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order No.</td><td valign=center>$sord[ordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td>$sord[chrgvat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$sord[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Person</td><td valign=center>$sord[salespn]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$sord[odate]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td valign=center>$sord[traddisc]%</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td valign=center>$sord[delchrg]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<p>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='corder-new.php'>New Consignment Order</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='corder-view.php'>View Consignment Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td align=right>".CUR." $sord[discount]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td align=right>".CUR." $sord[delivery]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Cancel'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	#get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get Sales Order info
	db_connect();
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND accepted != 'c' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li class=err>Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$sord[cusnum]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM cord_data WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

/* - Start Copying - */
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# todays date (sql formatted)
		$date = date("Y-m-d");

		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM corders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# update stock(alloc - qty)
			$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# remove the Sales Order
		$sql = "DELETE FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit.",SELF);

		#record (sordid, username, date)
		$sql = "INSERT INTO cancelled_cord(sordid, deptid, username, date, deptname, div) VALUES('$sordid', '$sord[deptid]', '".USER_NAME."', '$date','$sord[deptname]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert Sales Order record to Cubit.",SELF);

/* - End Copying - */
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Consignement Order Cancelled</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Consignment Order for customer <b>$cust[cusname] $cust[surname]</b> has been cancelled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='corder-view.php'>View Consignment Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

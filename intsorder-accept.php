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
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Orders number.");

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
	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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
		$sord['chrgvat'] = "Non Vat";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th colspan=2>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>";
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
			$products .="<tr class='bg-odd'><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>".CUR." $stkd[funitcost]</td><td align=right>$sord[currency] $stkd[unitcost]</td><td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td><td>$sord[currency] $stkd[amt]</td></tr>";
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
	$sord['delchrg'] = sprint($sord['delchrg']);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "<center><h3>Accept International Sales Order</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=sordid value='$sordid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$sord[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$sord[cusname] $sord[surname]</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($sord['cusaddr'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center>$sord[cordno]</td></tr>
			<tr class='bg-odd'><td>Customer Vat Number</td><td>$sord[cusvatno]</td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr class='bg-even'><td colspan=2 align=center>".nl2br($sord['comm'])."</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Sales Order Details </th></tr>
			<tr class='bg-even'><td>Sales Order No.</td><td valign=center>$sord[sordid]</td></tr>
			<tr class='bg-odd'><td>Order No.</td><td valign=center>$sord[ordno]</td></tr>
			<tr class='bg-even'><td>VAT Inclusive</td><td valign=center>$sord[chrgvat]</td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$sord[terms] Days</td></tr>
			<tr class='bg-even'><td>Sales Person</td><td valign=center>$sord[salespn]</td></tr>
			<tr class='bg-odd'><td>Sales Order Date</td><td valign=center>$sord[odate]</td></tr>
			<tr class='bg-even'><td>Trade Discount</td><td valign=center>$sord[traddisc]%</td></tr>
			<tr class='bg-odd'><td>Delivery Charge</td><td valign=center>$sord[delchrg]</td></tr>
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
			<tr class='bg-odd'><td><a href='sorder-new.php'>New Sales Order</a></td></tr>
			<tr class='bg-odd'><td><a href='sorder-view.php'>View Sales Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>$sord[currency] $SUBTOT</td></tr>
			<tr class='bg-even'><td>Trade Discount</td><td align=right>$sord[currency] $sord[discount]</td></tr>
			<tr class='bg-odd'><td>Delivery Charge</td><td align=right>$sord[currency] $sord[delivery]</td></tr>
			<tr class='bg-even'><td><b>VAT @ $VATP%</b></td><td align=right>$sord[currency] $VAT</td></tr>
			<tr class='bg-odd'><th>GRAND TOTAL</th><td align=right>$sord[currency] $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Invoice'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	# Get vars
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
	$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<li class=err>Sales Order Not Found</li>";
	}
	$sord = pg_fetch_array($sordRslt);


/* - Start Copying - */
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();
		# Insert invoice to DB
		$sql = "INSERT INTO invoices(deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, fcid, currency, xrate, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, location, printed, done, serd, prd, div)";
		$sql .= " VALUES('$sord[deptid]', '$sord[cusnum]', '$sord[deptname]', '$sord[cusacc]', '$sord[cusname]', '$sord[surname]', '$sord[cusaddr]', '$sord[cusvatno]', '$sord[cordno]', '$sord[ordno]', '$sord[chrgvat]', '$sord[fcid]', '$sord[currency]', '$sord[xrate]', '$sord[terms]', '$sord[traddisc]', '$sord[salespn]', '$sord[odate]', '$sord[delchrg]', '$sord[subtot]', '$sord[vat]' , '$sord[total]', '$sord[total]', '$sord[comm]', '$sord[location]', 'n', 'y', 'n', '".PRD_DB."', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

		# get next ordnum
		$invid = lastinvid();

		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM sorders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$serd = "y";
		while($stkd = pg_fetch_array($stkdRslt)){
			# Insert one by one per quantity
			if(ext_isSerial("stock", "stkid", $stkd['stkid'])){
				$stkd['amt'] = sprint($stkd['amt']/$stkd['qty']);
				$serd = "n";
				for($i = 0; $i < $stkd['qty']; $i++){
					# insert invoice items
					$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, funitcost, amt, famt, disc, discp, div) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '1', '$stkd[unitcost]', '$stkd[funitcost]', '$stkd[amt]', '$stkd[famt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
			}else{
				# insert invoice items
				$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, funitcost, amt, famt, disc, discp, div) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[funitcost]', '$stkd[amt]', '$stkd[famt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
			}
		}

		# get selected stock in this Sales Order
		db_connect();
		$sql = "SELECT * FROM sord_data  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$dataRslt = db_exec($sql);
		$data = pg_fetch_array($dataRslt);

		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$data[dept]', '$data[customer]', '$data[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

		# set to accepted
		$sql = "UPDATE sorders SET accepted = 'y' WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		# set to not serialised
		$sql = "UPDATE invoices SET serd = '$serd' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		// # remove access data
		// $sql = "DELETE FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		// $rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

		// $sql = "DELETE FROM sorders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		// $rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

		// $sql = "DELETE FROM sord_data WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
		// $rslt = db_exec($sql) or errDie("Unable to update Sales Orders in Cubit.",SELF);

# End updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	header ("Location: intinvoice-new.php?invid=$invid&cont=true&letters=&done=");
	exit;
	/* - End Copying - */

	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>International Sales Order accepted</th></tr>
		<tr class='bg-even'><td>Sales Order for customer <b>$sord[cusname] $sord[surname]</b> has been accepted</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='sorder-view.php'>View Sales Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

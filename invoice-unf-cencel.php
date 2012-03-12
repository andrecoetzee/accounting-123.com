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
					$OUTPUT = "<li class=err>Invalid use of module.";
				}
			}
} else {
	# decide what to do
	if (isset($_GET["quoid"])) {
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

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

	# Get quote info
	db_connect();
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>";
		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# put in product
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td><td>".CUR." $stkd[amt]</td></tr>";
	}
	$products .= "</table>";

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$quo[cusnum]'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM quote_data WHERE quoid = '$quoid'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$quo[deptid]'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}


	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = $quo['subtot'];

	# Calculate tradediscm
	if(strlen($quo['traddisc']) > 0){
		$traddiscm = round((($quo['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	# minus discount
	# $SUBTOT -= $disc; --> already minused

	# duplicate
	$SUBTOTAL = $SUBTOT;

	# minus trade discount
	$SUBTOTAL -= $traddiscm;

	# add del charge
	$SUBTOTAL += $quo['delchrg'];


	# if vat must be charged
	if($quo['chrgvat'] == "yes"){
		$VATP = TAX_VAT;
		$VAT = sprintf("%01.2f", (($VATP/100) * $SUBTOTAL));
	}else{
		$VATP = 0;
		$VAT = "0.00";
	}

	# total
	$TOTAL = $SUBTOTAL + $VAT;

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "<center><h3>Accept Quote</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=quoid value='$quoid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer</td><td valign=center>$cust[cusname] $cust[surname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Customer Address</td><td valign=center>".nl2br($cust['addr1'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Order number</td><td valign=center>$quo[cordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Vat Number</td><td>$cust[vatnum]</td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center>".nl2br($quo['comm'])."</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Quote Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Quote No.</td><td valign=center>$quo[quoid]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order No.</td><td valign=center>$quo[ordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Charge VAT</td><td valign=center>$quo[chrgvat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$quo[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Person</td><td valign=center>$quo[salespn]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Quote Date</td><td valign=center>$quo[odate]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td valign=center>$quo[traddisc]%</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td valign=center>$quo[delchrg]</td></tr>
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
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-new.php'>New Quote</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td align=right>".CUR." $traddiscm</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td align=right>".CUR." $quo[delchrg]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Write'></td></tr>
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get quote info
	db_connect();
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<li class=err>Quote Not Found</li>";
	}
	$quo = pg_fetch_array($quoRslt);

	db_connect();
	/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# insert invoice to DB
		$sql = "INSERT INTO invoices(deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, printed)";
		$sql .= " VALUES('$quo[deptid]', '$quo[cusnum]',  '$quo[cordno]', '$quo[ordno]', '$quo[chrgvat]', '$quo[terms]', '$quo[traddisc]', '$quo[salespn]', '$quo[odate]', '$quo[delchrg]', '$quo[subtot]', '$quo[vat]' , '$quo[total]', '$quo[total]', '$quo[comm]', 'n')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

		# get next ordnum
		$invid = lastinvid();

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# insert invoice items
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			# $sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]'";
			# $rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}
		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_data  WHERE quoid = '$quoid'";
		$dataRslt = db_exec($sql);
		$data = pg_fetch_array($dataRslt);

		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, addr2, addr3) VALUES('$invid', '$data[dept]', '$data[customer]', '$data[addr1]', '$data[addr2]', '$data[addr3]')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

		$sql = "UPDATE quotes SET accepted = 'y' WHERE quoid = '$quoid'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	/* - End Copying - */
}
?>

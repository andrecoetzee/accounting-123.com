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
				if (isset($HTTP_GET_VARS["quoid"])) {
					$OUTPUT = details($HTTP_GET_VARS);
				} else {
					$OUTPUT = "<li class=err>Invalid use of module.";
				}
			}
} else {
	# decide what to do
	if (isset($HTTP_GET_VARS["quoid"])) {
		$OUTPUT = details($HTTP_GET_VARS);
	} else {
		$OUTPUT = "<li class=err>Invalid use of module.";
	}
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
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
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class=err>Not Found</i>";
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
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>";
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

			}else {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			# put in product
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td><td>".CUR." $stkd[amt]</td></tr>";
		}
	$products .= "</table>";

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($quo['subtot']);

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "<center><h3>Accept Quote</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=quoid value='$quoid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$quo[deptname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer</td><td valign=center>$quo[cusname] $quo[surname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Customer Address</td><td valign=center>".nl2br($quo['cusaddr'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Order number</td><td valign=center>$quo[cordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Vat Number</td><td>$quo[cusvatno]</td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center>".nl2br($quo['comm'])."</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Quote Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Quote No.</td><td valign=center>$quo[quoid]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order No.</td><td valign=center>$quo[ordno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td valign=center>$quo[chrgvat]</td></tr>
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
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td align=right>".CUR." $quo[discount]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td align=right>".CUR." $quo[delivery]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><b>VAT $vat14</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Write'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# Details
function write($HTTP_POST_VARS)
{

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<li class=err>Quote Not Found</li>";
	}
	$quo = pg_fetch_array($quoRslt);

/* - Start Copying - */
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();
		# Insert invoice to DB
		$sql = "INSERT INTO invoices(deptid, cusnum, deptname, cusacc, cusname, surname, 
					cusaddr, cusvatno, cordno, ordno, chrgvat, terms, traddisc, salespn, 
					odate, delchrg, subtot, vat, discount, delivery, total, balance, comm, 
					printed, done, prd, serd, div,delvat)";
		$sql .= " VALUES('$quo[deptid]', '$quo[cusnum]', '$quo[deptname]', '$quo[cusacc]', 
					'$quo[cusname]', '$quo[surname]', '$quo[cusaddr]', '$quo[cusvatno]', 
					'$quo[cordno]', '$quo[ordno]', '$quo[chrgvat]', '$quo[terms]', 
					'$quo[traddisc]', '$quo[salespn]', '$quo[odate]', '$quo[delchrg]', 
					'$quo[subtot]', '$quo[vat]' , '$quo[discount]', '$quo[delivery]', 
					'$quo[total]', '$quo[total]', '$quo[comm]', 'n', 'y', '".PRD_DB."', 
					'n', '".USER_DIV."', '$quo[delvat]')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

		# get next ordnum
		$invid = lastinvid();

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$serd = "y";
		while($stkd = pg_fetch_array($stkdRslt)){
			# Insert one by one per quantity
			if(ext_isSerial("stock", "stkid", $stkd['stkid'])){
				$stkd['amt'] = sprint($stkd['amt']/$stkd['qty']);
				$serd = "n";
				for($i = 0; $i < $stkd['qty']; $i++){
					# insert invoice items
					$stkd['vatcode']+=0;
					$stkd['account']+=0;
					$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div,vatcode,description,account) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '1', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]','$stkd[description]','$stkd[account]')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
			}else{
				$stkd['vatcode']+=0;
				$stkd['account']+=0;
				# insert invoice items
				$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div,vatcode,description,account) VALUES('$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]','$stkd[description]','$stkd[account]')";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
			}

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_data  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$dataRslt = db_exec($sql);
		$data = pg_fetch_array($dataRslt);

		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$data[dept]', '$data[customer]', '$data[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);


		# set to accepted
		$sql = "UPDATE quotes SET accepted = 'y' WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		# set to not serialised
		$sql = "UPDATE invoices SET serd = '$serd' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		/* remove access data
		$sql = "DELETE FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		$sql = "DELETE FROM quote_items WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);

		$sql = "DELETE FROM quote_data WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);
		*/

/* - End Copying - */
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	header("Location: cust-credit-stockinv.php?invid=$invid&cont=1&letters=");
	exit;


	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quote accepted</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Quote for customer <b>$quo[cusname] $quo[surname]</b> has been accepted</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

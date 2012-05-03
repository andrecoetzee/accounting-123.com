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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "update":
			$OUTPUT = write($_POST);
			break;

		default:
			if (isset($_GET["invid"])) {
				$_GET["done"] = "";
				$_GET["stkerr"] = '0,0';
				$OUTPUT = details($_GET);
			}else{
				$OUTPUT = "<li class=err> Invalid use of module";
			}
	}
}else{
	if (isset($_GET["invid"])) {
		$_GET["done"] = "";
		$_GET["stkerr"] = '0,0';
		$OUTPUT = details($_GET);
	}else{
		$OUTPUT = "<li class=err> Invalid use of module";
	}
}


# get templete
require("template.php");


# details
function details($_POST, $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Error : Client not Found";
	}else{
		$cust = pg_fetch_array($custRslt);
		# moarn if customer account has been blocked
		if($cust['blocked'] == 'yes'){
			return "<li class=err>Error : Selected customer account has been blocked.";
		}
		$customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
		$cusnum = $cust['cusnum'];
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class=err> There are no Stores found in Cubit.";
	}else{
			$whs .= "<option value='-S' disabled selected>Select Store</option>";
			while($wh = pg_fetch_array($whRslt)){
					$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
	}
	$whs .="</select>";

	# get sales people
	db_conn("exten");
	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class=err> There are no Sales People found in Cubit.";
	}else{
		$salesps = "<select name='salespn'>";
		while($salesp = pg_fetch_array($salespRslt)){
			if($salesp['salesp'] == $inv['salespn']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$salesps .= "<option value='$salesp[salesp]' $sel>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}

	# days drop downs
	$days = array("0"=>"0","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($inv['chrgvat'] == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}

	# format date
	list($oyear, $omon, $oday) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>STORE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><th>Remove</th><tr>";

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

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
		$products .="<tr class='bg-odd'><td><input type=hidden name=whids[] value='$stkd[whid]'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td><input type=text size=4 name=qtys[] value='$stkd[qty]'></td><td><input type=hidden size=8 name=unitcost[] value='$stkd[unitcost]'>$stkd[unitcost]</td><td><input type=text size=4 name=disc[] value='$stkd[disc]'> OR <input type=text size=4 name=discp[] value='$stkd[discp]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$stkd[amt]'> ".CUR." $stkd[amt]</td><td><input type=checkbox name=remprod[] value='$key'><input type=hidden name=SCROLL value=yes></td></tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}else{
		$SCROLL = "yes";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = pg_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# get price from price list if it is set
				if(isset($cust['pricelist'])){
					# get selected stock in this warehouse
					db_conn("exten");
					$sql = "SELECT price FROM plist_prices WHERE listid = '$cust[pricelist]' AND stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
					$plRslt = db_exec($sql);
					if(pg_numrows($plRslt) > 0){
						$pl = pg_fetch_array($plRslt);
						$stk['selamt'] = $pl['price'];
					}
				}

				/* -- Start Some Checks -- */
				# check if they are selling too much
				if(($stk['units'] - $stk['alloc']) < $qtyss[$key]){
					if(!in_array($stk['stkid'], explode(",", $stkerr))){
						if($stk['type'] != 'lab'){
							$stkerr .= ",$stk[stkid]";
							$error .= "<li class=err>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}
				/* -- End Some Checks -- */

				# Calculate the Discount discount
				if($discs[$key] < 1){
					if($discps[$key] > 0){
						$discs[$key] = round((($discps[$key]/100) * $stk['selamt']), 2);
					}
				}else{
					$discps[$key] = round((($discs[$key] * 100) / $stk['selamt']), 2);
				}

				# Calculate amount
				# $amt[$key] = (($qtyss[$key] * $stk['selamt']) - $discs[$key]);
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				# Put in selected warehouse and stock
				$products .="<tr class='bg-odd'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td><input type=text size=4 name=qtys[] value='$qtyss[$key]'></td><td><input type=hidden size=8 name='unitcost[]'  value='$stk[selamt]'>$stk[selamt]</td><td><input type=text size=4 name=disc[] value='$discs[$key]'> OR <input type=text size=4 name=discp[] value='$discps[$key]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$amt[$key]'> ".CUR." $amt[$key]</td><td><input type=checkbox name=remprod[] value='$keyy'></td></tr>";
				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					 # skip if not selected
					if($whid == "-S"){
						continue;
					}

					# get warehouse name
					db_conn("exten");
					$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					# get stock on this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class=err>There are no stock items in the selected warehouse.";
						continue;
					}
					$stks = "<select class='width:15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
					$stks .= "<option value='-S' disabled selected>Select Number</option>";
					$count = 0;
					while($stk = pg_fetch_array($stkRslt)){
						$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
					}
					$stks .= "</select> ";

					# put in drop down and warehouse
					$products .="<tr class='bg-odd'><td><input type=hidden name=whidss[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td><input type=text size=4 name='qtyss[]'  value='1'></td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td><input type=hidden name=amts[] value='0.00'>".CUR." 0.00</td><td></td></tr>";
				}
			}
		}
	}else{
		if(!isset($diffwhBtn)){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					$err .= "<li>There are no stock items in the selected store.";
					continue;
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
				$products .= "<tr class='bg-odd'><td><input type=hidden name=whidss[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td><input type=text size=4 name=qtyss[] value='1'></td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
			}else{
				$products .= "<tr class='bg-odd'><td>$whs</td><td></td><td> </td><td> </td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
			}
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "<tr class='bg-odd'><td>$whs</td><td></td><td> </td><td> </td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */

	# the SUBOTAL !!!!!!!!!
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

/* --- End Some calculations --- */


/*--- Start checks --- */
	# check only if the customer is selected
	if(isset($cusnum)){
		db_connect();
		# check credit limit (inclide unpaid invoices)
		$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND balance <> 0 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql);
		$bal = pg_fetch_array($rslt);
		$credbal = $bal['sum'];

		#check againg credit limit
		if(($TOTAL + $credbal) > $cust['credlimit']){
			$error .= "<li class=err>Warning : Customers Credit limit of <b>".CUR." $cust[credlimit]</b> has been exceeded";
		}
		$avcred = ($cust['credlimit'] - $credbal);
	}else{
		$avcred = "0.00";
	}
/*--- Start checks --- */

/* -- Final Layout -- */
	$details = "<center><h3>Edit Invoice</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=invid value='$invid'>
	<input type=hidden name=stkerr value='$stkerr'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$customers</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($cust['addr1'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center><input type=text size=10 name=cordno value='$inv[cordno]'></td></tr>
			<tr class='bg-odd'><td>Customer Vat Number</td><td>$cust[vatnum]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Invoice Details </th></tr>
			<tr class='bg-even'><td>Invoice No.</td><td valign=center>TI $inv[invid]</td></tr>
			<tr class='bg-odd'><td>Sales Order No.</td><td valign=center><input type=text size=5 name=ordno value='$inv[ordno]'></td></tr>
			<tr class='bg-even'><td>VAT Inclusive</td><td valign=center>Yes <input type=radio size=7 name=chrgvat value='inc' $chin> No<input type=radio size=7 name=chrgvat value='exc' $chex> No Vat<input type=radio size=7 name=chrgvat value='nov' $chno></td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$termssel Days</td></tr>
			<tr class='bg-even'><td>Sales Person</td><td valign=center>$salesps</td></tr>
			<tr class='bg-odd'><td>Invoice Date</td><td valign=center><input type=text size=2 name=oday maxlength=2 value='$oday'>-<input type=text size=2 name=omon maxlength=2 value='$omon'>-<input type=text size=4 name=oyear maxlength=4 value='$oyear'> DD-MM-YYYY</td></tr>
			<tr class='bg-even'><td>Available Credit</td><td>".CUR." $avcred</td></tr>
			<tr class='bg-odd'><td>Trade Discount</td><td valign=center><input type=text size=5 name=traddisc value='$inv[traddisc]'>%</td></tr>
			<tr class='bg-even'><td>Delivery Charge</td><td valign=center><input type=text size=7 name=delchrg value='$inv[delchrg]'></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Comments</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td class='bg-odd'><a href='cust-credit-stockinv.php'>New Invoice</a><td class='bg-odd' rowspan=4 align=center valign=top><textarea name=comm rows=4 cols=20>$inv[comm]</textarea></td></tr>
			<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=SUBTOT value='$SUBTOT'>$SUBTOT</td></tr>
			<tr class='bg-even'><td>Trade Discount</td><td align=right>".CUR." $traddiscm</td></tr>
			<tr class='bg-odd'><td>Delivery Charge</td><td align=right>".CUR." $inv[delchrg]</td></tr>
			<tr class='bg-even'><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr class='bg-odd'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Different Store'> | <input name=addprodBtn type=submit value='Add Product'> | <input type=submit name='saveBtn' value='Save'> </td><td> | <input type=submit name='upBtn' value='Update'>$done</td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($cordno, "num", 0, 20, "Invalid Customer Order Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "num", 0, 20, "Invalid order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($oday, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($omon, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($oyear, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $oyear."-".$omon."-".$oday;
	if(!checkdate($omon, $oday, $oyear)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
			if($disc[$keys] > $unitcost[$keys]){
				$v->isOk ($disc[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than the unitcost.");
			}
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
			if($discp[$keys] > 100){
				$v->isOk ($discp[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than 100 %.");
			}
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$_POST["done"] = "";
		return details($_POST, $err);
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM inv_data WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	# insert invoice to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
			# get selected stock in this invoice
			db_connect();
			$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);

			while($stkt = pg_fetch_array($stktRslt)){
				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}

			# remove old items
			$sql = "DELETE FROM inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($stkids)){
			foreach($stkids as $keys => $value){
				if(isset($remprod)){
					if(in_array($keys, $remprod)){
						# skip product (wonder if $keys still align)
						$amt[$keys] = 0;
						continue;
					}else{
						# get selamt from selected stock
						$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
						$stkRslt = db_exec($sql);
						$stk = pg_fetch_array($stkRslt);

						# Calculate the Discount discount
						if($disc[$keys] < 1){
							if($discp[$keys] > 0){
								$disc[$keys] = (($discp[$keys]/100) * $unitcost[$keys]);
							}
						}else{
							$discp[$keys] = (($disc[$keys] * 100) / $unitcost[$keys]);
						}

						# Calculate amount
						$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));

						# Check Tax Excempt
						if($stk['exvat'] == 'yes'){
							$taxex += ($amt[$keys]);
						}

						# insert invoice items
						$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."')";
						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

						# update stock(alloc + qty)
						$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
					}
				}else{
					# get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					# Calculate the Discount discount
					if($disc[$keys] < 1){
						if($discp[$keys] > 0){
							$disc[$keys] = (($discp[$keys]/100) * $unitcost[$keys]);
						}
					}else{
						$discp[$keys] = (($disc[$keys] * 100) / $unitcost[$keys]);
					}

					# Calculate amount
					# $amt[$keys] = (($qtys[$keys] * $unitcost[$keys]) - $disc[$keys]);
					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));

					if($stk['exvat'] == 'yes'){
						$taxex += ($amt[$keys]);
					}

					# insert invoice items
					$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]','$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

					# update stock(alloc + qty)
					$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = " | <input name=doneBtn type=submit value='Done'>";
			}
		}else{
			$_POST["done"] = "";
		}

		/* --- Clac --- */
		# calculate subtot
		if(isset($amt)){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}

		# Calculate tradediscm
		if($traddisc > 0){
			$traddiscm = round((($traddisc/100) * $SUBTOT), 2);
		}else{
			$traddiscm = 0;
		}

		/* Trade discount fix */

			# Calculate tradediscm
			if($traddisc > 0){
				$traddiscmt = sprint(($traddisc/100) * $taxex);
			}else{
				$traddiscmt = 0.00;
			}

			$taxex -= $traddiscmt;

		/* Trade discount fix */

		# minus discount
		# $SUBTOT -= $disc; --> already minused

		# duplicate
		$SUBTOTAL = $SUBTOT;

		# minus trade discount
		$SUBTOTAL -= $traddiscm;

		# add del charge
		$SUBTOTAL += $delchrg;

		# If vat must be charged
		if($chrgvat == "exc"){
			$VATP = TAX_VAT;
			$VAT = sprint(($VATP/100) * ($SUBTOTAL - $taxex));
		}elseif($chrgvat == "inc"){
			$VATP = TAX_VAT;
			$VAT = sprint((($SUBTOTAL - $taxex)/($VATP + 100)) * $VATP);
		}else{
			$VATP = 0;
			$VAT = "0.00";
		}

		# total
		if($chrgvat == "exc"){
			$TOTAL = sprint($SUBTOTAL + $VAT);
		}else{
			$TOTAL = sprint($SUBTOTAL);
			$SUBTOT = sprint($SUBTOT - $VAT);
		}
		/* --- End Clac --- */

		# insert invoice to DB
		$sql = "UPDATE invoices SET cusnum = '$cusnum', deptname = '$dept[deptname]', cusacc = '$cust[accno]', cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', chrgvat = '$chrgvat', terms = '$terms', salespn = '$salespn',
		odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', comm = '$comm' WHERE invid = '$invid'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM inv_data WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# pu in new data
		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* --- Start button Listeners --- */
	if(isset($doneBtn)){

		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class=err> Error : Invoice number has no items.";
			return details($_POST, $error);
		}

		# insert quote to DB
		$sql = "UPDATE invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);
		# print the invoice
		header("Location:invoice-print.php?invid=$invid");

	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>New Invoice Saved</th></tr>
			<tr class='bg-even'><td>Invoice for customer <b>$cust[cusname] $cust[surname]</b> has been saved.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $write;
	}else{
		return details($_POST);
	}
/* --- End button Listeners --- */
}
?>

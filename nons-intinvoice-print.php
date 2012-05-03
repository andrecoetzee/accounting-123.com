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

if ( isset($_GET) && isset($_POST) ) {
	array_merge($_POST, $_GET);
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "cconfirm":
			$OUTPUT = cconfirm($_POST);
			break;
		case "cprewrite":
			$OUTPUT = cprewrite();
			break;
		case "cwrite":
			$OUTPUT = cwrite($_POST);
			break;
		default:
			if (isset($_GET["invid"])) {
				$OUTPUT = cdetails($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
	}
}else{
	if (isset($_GET["invid"])) {
		$OUTPUT = cdetails($_GET);
	} else {
		$OUTPUT = "<li class=err>Invalid use of module.";
	}
}


# get templete
require("template.php");




# Customer details
function cdetails($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");

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

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);
	$currs = getSymbol($inv['fcid']);

	$details = "";
	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[tval]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cust = pg_fetch_array($custRslt);

	$details = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'> Customer Details </th>
		</tr>
		<input type='hidden' name='cusnum' value='$cust[cusnum]'>
		<tr class='".bg_class()."'>
			<td>Customer</td>
			<td valign='center'>$cust[cusname] $cust[surname]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Customer Address</td>
			<td valign='center'><pre>$cust[addr1]</pre></td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Customer VAT Number</td>
			<td valign='center'>$cust[vatnum]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Customer Order number</td>
			<td valign='center'>$inv[cordno]</td>
		</tr>
	</table>";


	$stkacc = "";
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}
			$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
	$stkacc .= "</select>";

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th width='5%'>#</th>
			<th width='55%'>DESCRIPTION</th>
			<th width='10%'>QTY</th>
			<th width='10%'>UNIT PRICE</th>
			<th width='10%'>AMOUNT</th>
			<th width='10%'>ACCOUNT</th>
		<tr>";

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		# put in product
		$products .= "
			<tr class='".bg_class()."'>
				<td align='center'>$i</td>
				<td>$stkd[description]</td>
				<td>$stkd[qty]</td>
				<td>$inv[currency] $stkd[unitcost]</td>
				<td>$inv[currency] $stkd[amt]</td>
				<td ".ass("Select the account you wish to Credit")."><select name='stkaccs[$stkd[id]]'>$stkacc</td>
			</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['sdate']);

	/* -- Final Layout -- */
	$details = "
				<center>
				<h3>Non-Stock Invoice Details</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='cconfirm'>
					<input type='hidden' name='invid' value=$invid>
				<table ".TMPL_tblDflts." width='95%'>
					<tr>
						<td valign='top'>$details</td>
						<td valign='top' align='right'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'> Non-Stock Invoice Details </th>
								</tr>
								<tr class='".bg_class()."'>
									<td>Non-Stock Invoice No.</td>
									<td valign='center'>T $inv[invid]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Proforma Inv No.</td>
									<td valign='center'>$inv[docref]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Date</td>
									<td valign='center'>$sday-$smon-$syear</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Foreign Currency</td>
									<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." $inv[xrate]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>VAT Inclusive</td>
									<td valign='center'>$inv[chrgvat]</td>
								</tr>
							</table>
						</td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='2'>$products</td>
					</tr>
					<tr>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th width='40%'>Quick Links</th>
									<th width='45%'>Remarks</th>
									<td rowspan='5' valign='top' width='15%'><br></td>
								</tr>
								<tr>
									<td class='".bg_class()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
									<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
								</tr>
								<tr class='".bg_class()."'>
									<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
								</tr>
								<script>document.write(getQuicklinkSpecial());</script>
							</table>
						</td>
						<td align='right'>
							<table ".TMPL_tblDflts." width='80%'>
								<tr class='".bg_class()."'>
									<td>SUBTOTAL</td>
									<td align='right'>$inv[currency] $inv[subtot]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>VAT @ ".TAX_VAT." %</td>
									<td align='right'>$inv[currency] $inv[vat]</td>
								</tr>
								<tr class='".bg_class()."'>
									<th>GRAND TOTAL</th>
									<td align='right'>$inv[currency] $inv[total]</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>
				</center>";
	return $details;

}



# Customer Confirm
function cconfirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");

	if(isset($stkaccs)){
		foreach($stkaccs as $key => $accid){
			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
		}
	}else{
		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "$err<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);
	$currs = getSymbol($inv['fcid']);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th width='5%'>#</th>
			<th width='40%'>DESCRIPTION</th>
			<th width='10%'>QTY</th>
			<th width='10%'>UNIT PRICE</th>
			<th width='10%'>AMOUNT</th>
			<th width='25%'>ACCOUNT</th>
		<tr>";

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$stkacc = $stkaccs[$stkd['id']];
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkacc);
		$acc = pg_fetch_array($accRs);

		$i++;
		# put in product
		$products .= "
		<tr class='".bg_class()."'>
			<td align='center'>$i</td>
			<td>$stkd[description]</td>
			<td>$stkd[qty]</td>
			<td>$inv[currency] $stkd[unitcost]</td>
			<td>$inv[currency] $stkd[amt]</td>
			<td><input type='hidden' name='stkaccs[$stkd[id]]' value='$stkacc'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
		</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['sdate']);

	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cust = pg_fetch_array($custRslt);

	$details = "
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'> Customer Details </th>
					</tr>
					<input type='hidden' name='cusnum' value='$cusnum'>
					<tr class='".bg_class()."'>
						<td>Customer</td>
						<td valign='center'>$cust[cusname] $cust[surname]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Customer Address</td>
						<td valign='center'><pre>$cust[addr1]</pre></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Customer VAT Number</td>
						<td valign='center'>$cust[vatnum]</td>
					</tr>
				</table>";

	/* -- Final Layout -- */
	$details = "
				<center>
				<h3>Non-Stock Invoice Details</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='cwrite'>
					<input type='hidden' name='invid' value='$invid'>
				<table ".TMPL_tblDflts." width='95%'>
					<tr>
						<td valign='top'>$details</td>
						<td valign='top' align='right'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'> Non-Stock Invoice Details </th>
								</tr>
								<tr class='".bg_class()."'>
									<td>Non-Stock Invoice No.</td>
									<td valign='center'>T $inv[invid]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Proforma Inv No.</td>
									<td valign='center'>$inv[docref]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Date</td>
									<td valign='center'>$sday-$smon-$syear</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Foreign Currency</td>
									<td valign='center'>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." $inv[xrate]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>VAT Inclusive</td>
									<td valign='center'>$inv[chrgvat]</td>
								</tr>
							</table>
						</td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='2'>$products</td>
					</tr>
					<tr>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th width='40%'>Quick Links</th>
									<th width='45%'>Remarks</th>
									<td rowspan='5' valign='top' width='15%'><br></td>
								</tr>
								<tr>
									<td class='".bg_class()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
									<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
								</tr>
								<tr class='".bg_class()."'>
									<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
								</tr>
								<script>document.write(getQuicklinkSpecial());</script>
							</table>
						</td>
						<td align='right'>
							<table ".TMPL_tblDflts." width='80%'>
								<tr class='".bg_class()."'>
									<td>SUBTOTAL</td>
									<td align='right'>$inv[currency] $inv[subtot]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>VAT @ ".TAX_VAT." %</td>
									<td align='right'>$inv[currency] $inv[vat]</td>
								</tr>
								<tr class='".bg_class()."'>
									<th>GRAND TOTAL</th>
									<td align='right'>$inv[currency] $inv[total]</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' value='Confirm &raquo'></td>
					</tr>
				</table>
				</form>
				</center>
			";
	return $details;

}



# Customer write
function cwrite($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");

	if(isset($stkaccs)){
		foreach($stkaccs as $key => $accid){
			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
		}
	}else{
		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
	}

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



	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($inv['odate']) >= strtotime($blocked_date_from) AND strtotime($inv['odate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$td=$inv['sdate'];

	$currs = getSymbol($inv['fcid']);

	# Update xrate
	cus_xrate_update($inv['fcid'], $inv['xrate']);
	xrate_update($inv['fcid'], $inv['xrate'], "invoices", "invid");
	xrate_update($inv['fcid'], $inv['xrate'], "custran", "id");

	db_connect();
	# cust
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cus = pg_fetch_array($custRslt);

	$details = "
	<tr><td>$cus[surname]</td></tr>
	<tr><td>".nl2br($cus['addr1'])."</td></tr>
	<tr><td>$cus[vatnum]</td></tr>";

	$na=$cus['surname'];

# Begin updates
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$refnum = getrefnum();
/*refnum*/

	/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT","non");

	/* - End Hooks - */

	$real_invid = divlastid('inv', USER_DIV);
	db_connect();
	# Put in product
	while($stk = pg_fetch_array($stkdRslt)){
		$stkacc = $stkaccs[$stk['id']];
		# keep records for transactions
// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat']);
// 		}

		$Sl="SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$stk['vatex']="y";
		}

		//print $inv['chrgvat'];exit;

		$t=$inv['chrgvat'];

		$VATP = $vd['vat_amount'];
		# keep records for transactions
		if(isset($totstkamt[$stkacc])){
			if($stk['vatex']=="y") {
				$totstkamt[$stkacc] += vats($stk['amt'], 'novat', $vd['vat_amount']);
				$va=0;
				$inv['chrgvat']="";
			} else {
				$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat']=="no") {
					$va=sprint($stk['amt']*$VATP/100);
				}
			}
		}else{
			if($stk['vatex']=="y") {
				$totstkamt[$stkacc] = $stk['amt'];
				$inv['chrgvat']="";
				$va=0;
			} else {
				$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat']=="no") {
					$va=sprint($stk['amt']*$VATP/100);
				}
			}
		}

		$f=vats($stk['amt'],$inv['chrgvat'], $vd['vat_amount']);
		$f=$f*$inv['xrate'];
		$va=$va*$inv['xrate'];


		vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"Non-Stock Sales, invoice No.$real_invid", ($f+$va),$va);

		$inv['chrgvat']=$t;


		$sql = "UPDATE nons_inv_items SET accid = '$stkacc' WHERE id = '$stk[id]'";
		$sRslt = db_exec($sql);
		$products .="<tr valign=top><td>$stk[description]</td><td>$stk[qty]</td><td>$inv[currency]  $stk[unitcost]</td><td>$inv[currency] $stk[amt]</td></tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	$LVAT = sprint($VAT * $inv['xrate']);
	$LTOTAL = sprint($TOTAL * $inv['xrate']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","non");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");



	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# record transaction  from data
	foreach($totstkamt as $stkacc => $wamt){
		# Debit Customer and Credit stock
		writetrans($dept['debtacc'], $stkacc, $td, $refnum, ($wamt * $inv['xrate']), "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
	}
	# Debit bank and credit the account involved
	writetrans($dept['debtacc'], $vatacc, $td, $refnum, $LVAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cus[surname].");

	$sdate = date("Y-m-d");

	db_connect();
	$sql = "UPDATE nons_invoices SET cusid = '$cusnum', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

	# Record the payment on the statement
	$sql = "
		INSERT INTO stmnt 
			(cusnum, invid, docref, amount, date, type, div, allocation_date) 
		VALUES 
			('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL','$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."', '$inv[odate]')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Record the payment on the statement
	$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount,  balance, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL','$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Update the customer (make balance more)
	$sql = "UPDATE customers SET balance = (balance + '$LTOTAL'::numeric(13,2)), fbalance = (fbalance + '$TOTAL'::numeric(13,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Make ledge record
	custledger($cusnum, $dept['incacc'], $td, $real_invid, "Non Stock Invoice No. $real_invid", $LTOTAL, "d");
	frecordDT($TOTAL, $cusnum, $inv['xrate'], $inv['fcid'], $inv['sdate']);

	db_connect();
	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES('$inv[sdate]', '$invid', '$real_invid', '$dept[debtacc]', '$LVAT', '$LTOTAL', 'non', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	db_conn('cubit');

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$cusnum','$na','Non-stock International Invoice $real_invid','$inv[sdate]','".sprint($LTOTAL-$LVAT)."','$LVAT','".sprint($LTOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);


# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	# $stkdRslt = db_exec($sql);

	/* -- Format the remarks boxlet -- */
	$inv["remarks"] = "<table border=1><tr><td>Remarks:<br>$inv[remarks]</td></tr></table>";

	$cc = "<script> CostCenter('dt', 'Sales', '$td', 'Non Stock Invoice No.$real_invid', '".($LTOTAL-$LVAT)."', ''); </script>";

	if($inv['chrgvat']=="yes") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="no") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	/* -- Final Layout -- */
	$details = "
				<center>
				$cc
				<h2>Tax Invoice</h2>
				<table cellpadding='0' cellspacing='4' border=0 width='750'>
					<tr>
						<td valign='top' width='30%'>
							<table ".TMPL_tblDflts.">
								$details
							</table>
						</td>
						<td valign='top' width='30%'>
							".COMP_NAME."<br>
							".COMP_ADDRESS."<br>
							".COMP_TEL."<br>
							".COMP_FAX."<br>
							Reg No. ".COMP_REGNO."<br>
							VAT No. ".COMP_VATNO."
						</td>
						<td width='20%'><img src='compinfo/getimg.php' width='230' height='47'></td>
						<td valign='bottom' align='right' width='20%'>
							<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
								<tr>
									<td><b>Invoice No.</b></td>
									<td valign='center'>$real_invid</td>
								</tr>
								<tr>
									<td><b>Proforma Inv No.</b></td>
									<td valign='center'>$inv[docref]</td>
								</tr>
								<tr>
									<td><b>Invoice Date</b></td>
									<td valign='center'>$inv[sdate]</td>
								</tr>
								<tr>
									<td><b>VAT</b></td>
									<td valign='center'>$inv[chrgvat]</td>
								</tr>
							</table>
						</td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='4'>
							<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
								<tr>
									<th width='65%'>DESCRIPTION</th>
									<th width='10%'>QTY</th>
									<th width='10%'>UNIT PRICE</th>
									<th width='10%'>AMOUNT</th>
								<tr>
								$products
							</table>
						</td>
					</tr>
					<tr>
						<td>$inv[remarks]</td>
						<td>".BNK_BANKDET."</td>
						<td align='right' colspan='2'>
							<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
								<tr>
									<td><b>SUBTOTAL</b></td>
									<td align='right'>$inv[currency] $SUBTOT</td>
								</tr>
								<tr>
									<td><b>VAT @ ".TAX_VAT."%</b></td>
									<td align='right'>$inv[currency] $VAT</td>
								</tr>
								<tr>
									<th><b>GRAND TOTAL<b></th>
									<td align='right'>$inv[currency] $TOTAL</td>
								</tr>
							</table>
						</td>
					</tr>
					".TBL_BR."
				</table>
				</center>
			";

	$OUTPUT = $details;
	require("tmpl-print.php");

}

# records for DT
function frecordDT($amount, $cusnum, $rate, $fcid, $odate = "")
{

	if($odate == "")
		$odate = date("Y-m-d");

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND fbalance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			$lamount = ($amount * $rate);
			if(floatval($amount) > 0){
				if($dat['fbalance'] < $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET fbalance = (fbalance + '$amount'::numeric(13,2)), balance = (balance + '$lamount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0 ;
				}else{
					# remove small ones
					//if($dat['fbalance'] > $amount){
						$amount -= $dat['fbalance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					//}
				}
			}
		}
		if($amount > 0){
			$lamount = ($amount * $rate);

			/* Make transaction record for age analysis */
			$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$lamount = ($amount * $rate);

		/* Make transaction record for age analysis */
		$sql = "INSERT INTO custran(cusnum, odate, fcid, balance, fbalance, div) VALUES('$cusnum', '$odate', '$fcid', '$lamount', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE fbalance = 0::numeric(13,2) AND balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




# vats
function vats($amt, $inc, $VATP)
{

	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "yes"){
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}else{
		$ret = ($amt);
	}
	return $ret;

}


?>

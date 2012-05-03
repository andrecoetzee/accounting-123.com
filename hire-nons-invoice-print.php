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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

if ( isset($_GET) && isset($_POST) ) {
	array_merge($_POST, $_GET);
}

if (isset($_GET["post"]) && $_GET["post"] == "true") {
	foreach ($_GET as $key=>$value) {
		$_POST[$key] = $value;
	}
}

# decide what to do
if (isset($_GET["invid"]) && !isset($_GET["key"])) {
	$OUTPUT = slct($_GET);
} else {
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "recvpayment_write":
				$OUTPUT = recvpayment_write();
				break;
			case "slct":
				$OUTPUT = cdetails($_POST);
				break;
			case "confirm":
				$OUTPUT = confirm($_POST);
				break;
			case "cconfirm":
				$OUTPUT = cconfirm($_POST);
				break;
			case "prewrite":
				$OUTPUT = prewrite();
				break;
			case "cprewrite":
				$OUTPUT = cprewrite();
				break;
			case "write":
				$OUTPUT = write($_POST);
				break;
			case "cwrite":
				$OUTPUT = cwrite($_POST);
				break;

			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	}else{
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# get templete
require("../template.php");

# Details
function slct($_GET)
{

	# Get vars
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

	if(isset($letters)) {
		$letters=remval($letters);
		$whe="AND lower(surname) LIKE lower('%$letters%')";
	} else {
		$letters="";
		$whe="";
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if($inv['ctyp'] == 's'){
		$VARS['invid'] = $invid;
		$VARS['ctyp'] = 's';
		$VARS['cusnum'] = $inv['tval'];
		return cdetails($VARS);
	}elseif($inv['ctyp'] == 'c'){
		$VARS['invid'] = $invid;
		$VARS['ctyp'] = 'c';
		$VARS['deptid'] = $inv['tval'];
		return cdetails($VARS);
	}elseif($inv['ctyp'] == 'cb'){
		$VARS['invid'] = $invid;
		$VARS['ctyp'] = 'cb';
		$VARS['deptid'] = 0;
		return cdetails($VARS);
	}elseif($inv['ctyp'] == 'ac'){
		$VARS['invid'] = $invid;
		$VARS['ctyp'] = 'ac';
		$VARS['deptid'] = $inv['tval'];
		return acdetails($VARS);
	}

	db_connect();
	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' $whe ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		$custs="No customers
		<input type='hidden' name='cusnum' value='#'>";
	} else {
		$custs = "<select name='cusnum'>";
		while($cus = pg_fetch_array($cusRslt)){
			$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
		}
		$custs .= "</select>";
	}

	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name='deptid'>";
	if(pg_numrows($deptRslt) < 1) $depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	$details = "
					<center>
					<h3>Print Non-Stock Invoices</h3>
					<h4>Customer Details</h4>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='slct'>
						<input type='hidden' name='invid' value='$invid'>
						<input type='hidden' name='starting' value=''>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr class='".bg_class()."' ".ass("Select when selling non stock goods to your customers").">
							<td><input type='radio' name='ctyp' value='s' checked='yes'> Select Customer</td>
							<td>$custs</td>
						</tr>
						<tr class='".bg_class()."' ".ass("Select when the sale of non stock goods is a cash sale").">
							<td><input type='radio' name='ctyp' value='c'>Cash Sale</td>
							<td>$depts</td>
						</tr>
						<tr class='".bg_class()."' ".ass("Select when the sale of non stock goods is not a cash sale").">
							<td><input type='radio' name='ctyp' value='ac'>Ledger Accounts Sale</td>
							<td></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>Search by surname</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='text' size='10' name='letters' value='$letters'></td>
							<td><input type='submit' value='Search &raquo;'></td>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td></td>
							<td align='center'><input type='submit' value='Continue &raquo;' name='button'></td>
						</tr>
					</table>
					</form>";
	return $details;

}


# Customer details
function cdetails($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	if(!isset($button)&&(isset($starting))) {
		return slct($_GET);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>$err";
		return $confirm;
	}

	if($ctyp=="ac") {
		return acdetails($_GET);
	}

	# Get Invoice info
	db_connect();
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$details = "";

	if($ctyp == 's'){
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
							<tr class='".bg_class()."'>
								<td>Customer Order number</td>
								<td valign='center'>$inv[cordno]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Balance (Excl this Invoice)</td>
								<td>".CUR.sprint($cust["balance"])."</td>
							</tr>
						</table>";
	}elseif($ctyp == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Customer Details </th>
							</tr>
							<input type='hidden' name='deptid' value='$deptid'>
							<tr class='".bg_class()."'>
								<td>Customer</td>
								<td valign='center'>$inv[cusname] </td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Address</td>
								<td valign='center'><pre>$inv[cusaddr]</pre></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer VAT Number</td>
								<td valign='center'>$inv[cusvatno]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Order number</td>
								<td valign='center'>$inv[cordno]</td>
							</tr>
						</table>";
	}elseif($ctyp == 'cb'){
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		} else {
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Customer Details </th>
							</tr>
							<input type='hidden' name='bankid' value='$inv[accid]'>
							<tr class='".bg_class()."'>
								<td>Customer</td>
								<td valign='center'>$inv[cusname] </td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Address</td>
								<td valign='center'><pre>$inv[cusaddr]</pre></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer VAT Number</td>
								<td valign='center'>$inv[cusvatno]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Customer Order number</td>
								<td valign='center'>$inv[cordno]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Account</td>
								<td>$supacc</td>
							</tr>
						</table>";
	}

	$stkacc = "";
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
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
	$sql = "SELECT * FROM hire.hire_nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatex]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
						<tr class='".bg_class()."'>
							<td align='center'>$i</td>
							<td>$ex $stkd[description]</td>
							<td>$stkd[qty]</td>
							<td>$stkd[unitcost]</td>
							<td>".CUR." $stkd[amt]</td>
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
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "
	<center>
	<h3>Non-Stock Invoice Details</h3>
	<form action='".SELF."' method='POST' name='form'>
	<input type='hidden' name='key' value='cconfirm'>
	<input type='hidden' name='invid' value=$invid>
	<input type='hidden' name='ctyp' value=$ctyp>
	<table ".TMPL_tblDflts." width=95%>
	<tr><td valign='top'>
		$details
	</td><td valign='top' align='right'>
		<table ".TMPL_tblDflts.">
			<tr><th colspan='2'> Non-Stock Invoice Details </th></tr>
			<tr class='bg-odd'><td>Non-Stock Invoice No.</td><td valign='center'>T $inv[invid]</td></tr>
			<tr class='bg-even'><td>Hire No.</td><td valign='center'>H".getHirenum($inv["hire_invid"], 1)."</td></tr>
			<tr class='bg-odd'><td>Date</td><td valign='center'>$sday-$smon-$syear</td></tr>
			<tr class='bg-even'><td>VAT Inclusive</td><td valign='center'>$inv[chrgvat]</td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign='center'>$inv[terms] Days</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan='2'>
	$products
	</td></tr>
	<tr><td>
		<table ".TMPL_tblDflts.">
			<tr>
				<th width='40%'>Quick Links</th>
				<th width='45%'>Remarks</th>
				<td rowspan='5' valign='top' width='15%'><br></td>
			</tr>
			<tr class='bg-odd'>
				<td align='center'>
					<a href='javascript:popupOpen(\"../nons-invoice-new.php\")'>New Non-Stock Invoices</a>
				</td>
				<td class='bg-odd' rowspan=4 align=center valign=top>".nl2br($inv['remarks'])."</td></tr>
			<tr class='bg-odd'><td align='center'><a href='javascript:popupOpen(\"nons-invoice-view.php\")'>View Non-Stock Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align='right'>
		<table ".TMPL_tblDflts." width='80%'>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align='right'>".CUR." $inv[subtot]</td></tr>
			<tr class='bg-odd'><td>VAT $vat14</td><td align='right'>".CUR." $inv[vat]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align='right'>".CUR." $inv[total]</td></tr>
		</table>
	</td></tr>
	<tr><td align='right'><input type='submit' value='Confirm &raquo'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# Customer details
function acdetails($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
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

	$details = "";

	if($ctyp == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cust = pg_fetch_array($custRslt);

		$details = "
		<table ".TMPL_tblDflts.">
			<tr><th colspan='2'> Customer Details </th></tr>
			<input type='hidden' name='cusnum' value=$cusnum>
			<tr class='bg-odd'><td>Customer</td><td valign='center'>$cust[cusname] $cust[surname]</td></tr>
			<tr class='bg-even'><td>Customer Address</td><td valign='center'><pre>$cust[addr1]</pre></td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td valign='center'>$cust[vatnum]</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign='center'>$inv[cordno]</td></tr>
			<tr class='".bg_class()."'>
				<td>Customer Balance (Excl this Invoice)</td>
				<td>".CUR.sprint($cust["balance"])."</td>
			</tr>
		</table>";
	}elseif($ctyp == 'ac'){
// 		db_conn("exten");
// 		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
// 		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
// 		$dept = pg_fetch_array($deptRslt);

		$details = "
		<table ".TMPL_tblDflts.">
			<tr><th colspan='2'> Customer Details </th></tr>
			<input type='hidden' name='deptid' value='$deptid'>
			<tr class='bg-odd'><td>Customer</td><td valign='center'>$inv[cusname] </td></tr>
			<tr class='bg-even'><td>Customer Address</td><td valign='center'><pre>$inv[cusaddr]</pre></td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td valign='center'>$inv[cusvatno]</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign='center'>$inv[cordno]</td></tr>
		";
	}

	$stkacc = "";
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
		}
		while($acc = pg_fetch_array($accRslt)){
			$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
	$stkacc .= "</select>";

	$details.="<tr class='bg-even'><td>Select Account</td><td valign='center'><select name='accountc'>$stkacc</select></td></tr>
	</table>";

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
	$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
		<tr class='bg-odd'>
			<td align='center'>$i</td>
			<td>$stkd[description]</td>
			<td>$stkd[qty]</td>
			<td>$stkd[unitcost]</td>
			<td>".CUR." $stkd[amt]</td>
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
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Invoice Details</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=cconfirm>
	<input type=hidden name=invid value=$invid>
	<input type=hidden name=ctyp value=$ctyp>
	<table ".TMPL_tblDflts." width='95%'>
	<tr><td valign=top>
		$details
	</td><td valign=top align=right>
		<table ".TMPL_tblDflts.">
			<tr><th colspan=2> Non-Stock Invoice Details </th></tr>
			<tr class='bg-odd'><td>Non-Stock Invoice No.</td><td valign=center>T $inv[invid]</td></tr>
			<tr class='bg-even'><td>Hire No.</td><td valign=center>H".getHirenum($inv["hire_invid"], 1)."</td></tr>
			<tr class='bg-odd'><td>Date</td><td valign=center>$sday-$smon-$syear</td></tr>
			<tr class='bg-even'><td>VAT Inclusive</td><td valign=center>$inv[chrgvat]</td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$inv[terms] Days</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table ".TMPL_tblDflts.">
			<tr><th width=40%>Quick Links</th><th width=45%>Remarks</th><td rowspan=5 valign=top width=15%><br></td></tr>
			<tr class='bg-odd'><td align='center'><a href='javascript:popupOpen(\"../nons-invoice-new.php\")'>New Non-Stock Invoices</a></td><td class='bg-odd' rowspan=4 align=center valign=top>".nl2br($inv['remarks'])."</td></tr>
			<tr class='bg-odd'><td align='center'><a href='javascript:popupOpen(\"nons-invoice-view.php\")'>View Non-Stock Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table ".TMPL_tblDflts." width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." $inv[subtot]</td></tr>
			<tr class='bg-odd'><td>VAT $vat14</td><td align=right>".CUR." $inv[vat]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." $inv[total]</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=submit value='Confirm &raquo'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# Customer Confirm
function cconfirm($_POST)
{

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>$e[msg]</li>";
		}
		$confirm = "$err<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	if (!isset($monthly)) $monthly = 0;

	$pcash = $pcredit = $pcc = $pcheque = "0.00";

	# Get Invoice info
	db_connect();
	$sql = "SELECT *, extract('epoch' FROM odate) AS e_date
			FROM cubit.nons_invoices
			WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

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
	<tr>";

	$sql = "SELECT EXTRACT('epoch' FROM from_time) AS e_from FROM hire.hires
			WHERE inv_id='$inv[hire_invid]'";
	$time_rslt = db_exec($sql) or errDie("Unable to retrieve times.");
	$e_time = pg_fetch_result($time_rslt, 0);

	$sql = "UPDATE hire.hires SET to_time=current_timestamp WHERE inv_id='$invid'";
	db_exec($sql) or errDie("Unable to update to time.");

	# get selected stock in this Invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	$totamt = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		$totamt += $stkd["amt"];

		// Retrieve hire sales accounts
		$sql = "SELECT * FROM core.accounts WHERE topacc='1050' AND accnum='000'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
		$acc = pg_fetch_array($acc_rslt);

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$sql = "SELECT * FROM hire.hire_invitems WHERE id='$stkd[item_id]'";
		$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
		$hinv_data = pg_fetch_array($hinv_rslt);

		$from_date = $hinv_data["from_date"];
		$from_date = explode ("-", $from_date);
		$from_year = $from_date[0];
		$from_month = $from_date[1];
		$from_day = $from_date[2];

		$sql = "SELECT extract('epoch' FROM hired_time) AS e_time
				FROM hire.assets_hired WHERE item_id='$stkd[item_id]'";
		$et_rslt = db_exec($sql) or errDie("Unable to retrieve hired time.");
		$e_time = pg_fetch_result($et_rslt, 0);

		$i++;
		# put in product
		$products .="
		<input type=hidden name=stkaccs[$stkd[id]] value='$acc[accid]'>
		<tr class='bg-odd'>
			<td align=center>$i</td>
			<td>
				<input type='hidden' name='description[$stkd[id]]' value='$stkd[description]' />
				$stkd[description]
			</td>
			<td>$stkd[qty]</td>
			<td>".sprint($stkd["unitcost"])."</td>
			<td>".CUR.sprint($stkd["amt"])."</td>
		</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($totamt+$inv["delivery"]-$inv["discount"]);

	# Get vat
	$VAT = sprint($SUBTOT/100*14);

	# Get Total
	$TOTAL = $inv["total"];
	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	db_connect();
	# cust % bank
	if($ctyp == 's'){
		$cust = qryCustomer($cusnum);

		$details = "
		<table ".TMPL_tblDflts.">
			<tr><th colspan=2> Customer Details </th></tr>
			<input type=hidden name=cusnum value=$cusnum>
			<tr class='bg-odd'><td>Customer</td><td valign=center>$cust[cusname] $cust[surname]</td></tr>
			<tr class='bg-even'><td>Customer Address</td><td valign=center><pre>$cust[addr1]</pre></td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td valign=center>$cust[vatnum]</td></tr>
			<tr class='".bg_class()."'>
				<td>Customer Balance (Excl this Invoice)</td>
				<td>".CUR.sprint($cust["balance"])."</td>
			</tr>
		</table>";
	}elseif($ctyp == 'c'){
		$dept = qryDepartment($deptid);

		$details = "
		<table ".TMPL_tblDflts.">
		<tr><th colspan=2> Customer Details </th></tr>
		<input type=hidden name=deptid value='$deptid'>
		<tr class='bg-odd'><td>Customer</td><td valign=center>$inv[cusname] </td></tr>
		<tr class='bg-even'><td>Customer Address</td><td valign=center><pre>$inv[cusaddr]</pre></td></tr>
		<tr class='bg-odd'><td>Customer VAT Number</td><td valign=center>$inv[cusvatno]</td></tr>
		</table>";
	}elseif($ctyp == 'cb'){

		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err> Bank not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
		<table ".TMPL_tblDflts.">
		<tr><th colspan=2> Customer Details </th></tr>
		<input type=hidden name=bankid value='$inv[accid]'>
		<tr class='bg-odd'><td>Customer</td><td valign=center>$inv[cusname] </td></tr>
		<tr class='bg-even'><td>Customer Address</td><td valign=center><pre>$inv[cusaddr]</pre></td></tr>
		<tr class='bg-odd'><td>Customer VAT Number</td><td valign=center>$inv[cusvatno]</td></tr>
		<tr class='bg-even'><td>Account</td><td>$supacc</td></tr>
		</table>";
	}elseif($ctyp == 'ac'){
		$accountc+=0;
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $accountc);

		$accd = pg_fetch_array($accRs);

		$details = "
		<table ".TMPL_tblDflts.">
		<tr><th colspan=2>Customer Details </th></tr>
		<input type=hidden name=accountc value='$accountc'>
		<tr class='bg-odd'><td>Customer</td><td valign=center>$inv[cusname] </td></tr>
		<tr class='bg-even'><td>Customer Address</td><td valign=center><pre>$inv[cusaddr]</pre></td></tr>
		<tr class='bg-odd'><td>Customer VAT Number</td><td valign=center>$inv[cusvatno]</td></tr>
		<tr class='bg-even'><td>Account</td><td valign=center>$accd[accname]</td></tr>
		</table>";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if ($monthly) {
		$correction = "<input type='button' value='&laquo Correction' onclick='javascript:move(\"hire-invoice-new.php?invid=$inv[hire_invid]\");' />";
	} else {
		$correction = "";
	}

	if (!isset($recvpay)) $recvpay = "";
	if (!isset($pc)) $pc = "";
	if (!isset($inv["pcash"])) $inv["pcash"] = "";
	if (!isset($inv["pcheque"])) $inv["pcheque"] = "";
	if (!isset($inv["pcc"])) $inv["pcc"] = "";
	if (!isset($inv["pcredit"])) $inv["pcredit"] = "";

	$hirenum = "H".getHirenum($inv["hire_invid"], 1);

	/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Invoice Details</h3>
	<script>
	function ptot_recvpay() {
		if (ptot_amt() > 0) {
			return true;
		} else {
			alert('Enter amounts received by customer above.');
			return false;
		}
	}

	function pfld_num(fn) {
		i = getObject(fn).value;

		if (i) {
			return parseFloat(i);
		} else {
			return 0;
		}
	}
	function ptot_amt(nocredit) {
		i = pfld_num('pcash');
		i += pfld_num('pcc');
		i += pfld_num('pcheque');

		if (!nocredit && getObject('pcredit')) {
			i += pfld_num('pcredit');
		}

		return i.toFixed(2);
	}

	function ptot_update() {
		getObject('ptot').innerHTML = '".CUR." ' + ptot_amt();

		if (o = getObject('recvpay')) {
			o.value = 'Receive Payment: ".CUR." ' + ptot_amt(true);
		}
	}

	function paytotal(id) {
		if (getObject('pcredit')) getObject('pcredit').value = '0.00';

		ptot_update();
	}
	</script>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=cwrite>
	<input type=hidden name=invid value=$invid>
	<input type=hidden name=ctyp value=$ctyp>
	<input type='hidden' name='monthly' value='$monthly' />
	<input type='hidden' name='hirenum' value='$hirenum' />
	<table ".TMPL_tblDflts." width=95%>
	<tr><td valign=top>
		$details
	</td><td valign=top align=right>
		<table ".TMPL_tblDflts.">
			<tr><th colspan=2> Non-Stock Invoice Details </th></tr>
			<tr class='".bg_class()."'>
				<td>Non-Stock Invoice No.</td>
				<td valign=center>T $inv[invid]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Hire No.</td>
				<td valign=center>$hirenum</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td valign=center>".date("d-m-Y", $inv["e_date"])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Inclusive</td>
				<td valign=center>$inv[chrgvat]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Terms</td>
				<td valign=center>$inv[terms] Days</td>
			</tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table ".TMPL_tblDflts.">
			<tr><th colspan='2'>Payment Details </th></tr>
			<tr class='".bg_class()."'>
				<td>User</td>
				<td><input type='hidden' name='user' value='".USER_NAME."'>".USER_NAME."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap='t'>Amount Paid Cash</td>
				<td nowrap='t'>
					<input size='12' type='text' name='pcash' id='pcash' value='$pcash' onchange='ptot_update();'>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap='t'>Amount Paid Cheque</td>
				<td nowrap='t'>
					<input size='12' type='text' name='pcheque' id='pcheque' value='$pcheque' onchange='ptot_update();'>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap='t'>Amount Paid Credit Card</td>
				<td nowrap='t'>
					<input size='12' type='text' name='pcc' id='pcc' value='$pcc' onchange='ptot_update();'>
				</td>
			</tr>
			$recvpay
			$pc
			<!--<tr class='bg-even'>
				<td nowrap='t'>Total Covered</td>
				<td nowrap='t' id='ptot'>".CUR." ".sprint($inv["pcash"] + $inv["pcheque"] + $inv["pcc"] + $inv["pcredit"])."</td>
			</tr>-->
			<tr>
				<th width=40%>Quick Links</th>
				<th width=45%>Remarks</th>
				<td rowspan=5 valign=top width=15%><br></td>
			</tr>
			<tr class='bg-odd'>
				<td align='center'><a href='javascript:popupOpen(\"../nons-invoice-new.php\")'>New Non-Stock Invoices</a></td>
				<td class='bg-odd' rowspan=4 align=center valign=top>".nl2br($inv['remarks'])."</td>
			</tr>
			<tr class='bg-odd'>
				<td align='center'><a href='javascript:popupOpen(\"../nons-invoice-view.php\")'>View Non-Stock Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table ".TMPL_tblDflts." width='80%'>
			<tr class='".bg_class()."'>
				<td>Delivery Charge</td>
				<td align='right'>".CUR."$inv[delivery]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Trade Discount</td>
				<td align='right'>".CUR."$inv[discount]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>SUBTOTAL</td>
				<td align=right>".CUR." $SUBTOT</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT $vat14</td>
				<td align=right>".CUR." $VAT</td>
			</tr>
			<tr class='".bg_class()."'>
				<th>GRAND TOTAL</th>
				<td align=right>".CUR." $TOTAL</td>
			</tr>
		</table>
	</td></tr>
	<tr>
		<td align=right>
			$correction
			<input type=submit value='Write &raquo'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# Customer write
function cwrite($_GET) {
	$showvat = TRUE;

	extract($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

// 	if(isset($stkaccs)){
// 		foreach($stkaccs as $key => $accid){
// 			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
// 		}
// 	}else{
// 		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
// 	}

	if (!isset($description) && !count($description)) {
		$v->addError(0, "No items selected.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	// Update descriptions
	foreach ($description as $key=>$value) {
		$sql = "UPDATE hire.hire_nons_inv_items SET description='$value' WHERE id='$key'";
		db_exec($sql) or errDie("Unable to update descriptions.");
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$td = $inv['odate'];

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($td) >= strtotime($blocked_date_from) AND strtotime($td) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	db_connect();
	# cust % bank
	if($ctyp == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);

		$details = "
		<tr><td>$cus[surname]</td></tr>
		<tr><td>".nl2br($cus['addr1'])."</td></tr>
		<tr><td>VAT No. $cus[vatnum]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na=$cus['surname'];
	} elseif($ctyp == 'c') {
		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na=$inv['cusname'];
	} else {
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na=$inv['cusname'];
	}
# Begin updates
	$refnum = getrefnum();

	/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT","NO VAT");
	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

	/* - End Hooks - */
	//lock(2);

	$real_invid = divlastid('inv', USER_DIV);

	//unlock(2);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.");

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

        # Put in product
	$i = 0;
	$page = 0;
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$sql = "SELECT basis, hours, weeks,
					extract('epoch' from from_date) AS e_from,
					extract('epoch' from to_date) AS e_to
				FROM hire.hire_invitems
				WHERE id='$stk[item_id]'";
		$hired_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$hired_data = pg_fetch_array($hired_rslt);

		// --------------------------------------------------------------------
		$sql = "SELECT * FROM cubit.assets WHERE id='$stk[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		$sql = "SELECT traddisc FROM hire.hire_invoices WHERE invid='$inv[hire_invid]'";
		$disc_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
		$traddisc = pg_fetch_result($disc_rslt, 0);

		$sql = "UPDATE hire.assets_hired SET return_time=CURRENT_TIMESTAMP
				WHERE item_id='$stk[item_id]'";
		db_exec($sql) or errDie("Unable to update hired assets.");
		if (isset($monthly) && !$monthly) {
			if (!isSerialized($asset_data["id"])) {
				$new_qty = $asset_data["serial2"] + $stk["qty"];

				$sql = "UPDATE cubit.assets SET serial2='$new_qty' WHERE id='$stk[asset_id]'";
				db_exec($sql) or errDie("Unable to update assets.");
			}

			$hire_num = getHirenum($inv["hire_invid"]);
			if ($hire_num) {
				$sql = "SELECT * FROM hire.hire_invoices WHERE invnum='$hire_num'";
				$hi_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
				
				while ($hi_data = pg_fetch_array($hi_rslt)) {
					$sql = "DELETE FROM hire.hire_invitems
							WHERE invid='$hi_data[invid]'";
					db_exec($sql) or errDie("Unable to remove old items.");
				}
				
				$sql = "DELETE FROM hire.hire_invoices WHERE invnum='$hire_num'";
				db_exec($sql) or errDie("Unable to remove invoices.");
				
				$sql = "DELETE FROM hire.monthly_invoices WHERE invnum='$hire_num'";
				db_exec($sql) or errDie("Unable to remove invoices.");
				
				$sql = "UPDATE hire.assets_hired SET return_time=current_timestamp
						WHERE invnum='$hire_num'";
				db_exec($sql) or errDie("Unable to update return time.");
			}

			$sql = "DELETE FROM hire.hire_invitems WHERE id='$stk[item_id]'";
			db_exec($sql) or errDie("Unable to remove returned item.");
			
			$sql = "DELETE FROM hire.monthly_invitems WHERE item_id='$stk[item_id]'";
			db_exec($sql) or errDie("Unable to remove old items.");
			
			$sql = "UPDATE hire.assets_hired SET return_time=current_timestamp
					WHERE item_id='$stk[item_id]'";
			db_exec($sql) or errDie("Unable to remove old items.");
			
			$sql = "DELETE FROM hire.monthly_invoices
					WHERE invid='$inv[hire_invid]'";
			db_exec($sql) or errDie("Unable to remove monthly.");
			
			$sql = "DELETE FROM hire.hire_invitems
					WHERE invid='$inv[hire_invid]'";
			db_exec($sql) or errDie("Unable to remove monthly.");
		}
		$stkacc = $stkaccs[$stk['id']];

		$Sl="SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$stk['vatex']="y";
		}

		//print $inv['chrgvat'];exit;

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$t=$inv['chrgvat'];

		$VATP = TAX_VAT;
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
					$va=sprint($stk['amt']*$vd['vat_amount']/100);
				}
			}
		}else{
			if($stk['vatex']=="y") {
				$totstkamt[$stkacc] = $stk['amt'];
				$inv['chrgvat']="";
				$va=0;
			} else {
				// Seems only this one is used for our hiring purposes
				$totstkamt[$stkacc] = $stk['amt'];
				$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat']=="no") {
					$va=sprint($stk['amt']*$vd['vat_amount']/100);
				}
			}
		}

// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat']);
// 		}
		$sql = "UPDATE hire.hire_nons_inv_items SET accid = '$stkacc' WHERE id = '$stk[id]'";
		$sRslt = db_exec($sql);

		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

// 		$time_from = "$from_day-$from_month-$from_year $from_hour:$from_minute";
// 		$time_to = "$to_day-$to_month-$to_year $to_hour:$to_minute";

		if ($hired_data["weeks"]) {
			$hired_days = sprint($hired_data["weeks"] * 7);
		} elseif ($hired_data["e_from"] > 0) {
			$secs = $hired_data["e_to"] - $hired_data["e_from"];
			$hired_days = sprint(($secs / (60 * 60 * 24)) + 1);
		} elseif ($hired_data["hours"]) {
			$secs = $hired_data["hours"] / 24;
			$hired_days = sprint($secs);
		} else {
			$hired_days = 0;
		}

		$hired_days = floor($hired_days);

		switch ($hired_data["basis"]) {
			case "per_hour":
				$basis = "Hourly";
				$basis_s = "hour";
				$basis_p = "per_hour";
				break;
			case "per_day":
				$basis = "Daily";
				$basis_s = "day";
				$basis_p = "per_day";
				break;
			case "per_week":
				$basis = "Weekly";
				$basis_s = "week";
				$basis_p = "per_week";
		}

		$rate = basisPrice($inv["cusnum"], $stk["asset_id"], $basis_p);
		if (empty($rate)) $rate = "0.00";

		$sql = "UPDATE hire.hire_nons_inv_items SET hired_days='$hired_days',
			rate='$rate' WHERE id='$stk[id]'";
		db_exec($sql) or errDie("Unable to save to items.");

		$products[$page][] = "<tr valign=top>
			<td style='border-right: 2px solid #000'>$ex $stk[description]&nbsp;</td>
			<td style='border-right: 2px solid #000'>$stk[qty]&nbsp;</td>
			<td style='border-right: 2px solid #000'>$hired_days&nbsp;</td>
			<td align='right' style='border-right: 2px solid #000'>($basis) ".sprint($rate)." &nbsp;</td>
			<td align='right'>".CUR.sprint($stk["amt"])."&nbsp;</td>
		</tr>";

		$i++;
	}

	$inv['chrgvat']=$t;

 	$blank_lines = 25;
 	foreach ($products as $key=>$val) {
 		$bl = $blank_lines - count($products[$key]);
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "<tr>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
  				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td>&nbsp;</td>
 			</tr>";
 		}
 	}

	$sql = "INSERT INTO hire.hires (inv_id, user_id, cust_id, from_time)
			VALUES ('$inv[invid]', ".USER_ID.", '$inv[cusnum]', CURRENT_TIMESTAMP)";
	db_exec($sql) or errDie("Unable to create new hire.");

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","novat");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");


	if(isset($bankid)) {
		$bankid+=0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err>Bank not Found.</li>";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$deptd = pg_fetch_array($deptRslt);
		}

		db_conn('core');

		$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
		$rd=db_exec($Sl) or errDie("Unable to get data.");
		$data=pg_fetch_array($rd);

		$BA = $data['accnum'];
	}

	$tot_post=0;
	# bank  % cust
	if($ctyp == 's'){
		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.</li>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp=0;

		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt) {

			$wamt += $inv["delivery"] / count($totstkamt);
			$wamt -= $inv["discount"] / count($totstkamt);

			# Debit Customer and Credit stock
			$tot_post+=$wamt;

			writetrans($dept['debtacc'], $stkacc, $td, $refnum, $SUBTOT, "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post+=$VAT;
			writetrans($dept['debtacc'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cus[surname].");
		}

		$sdate = date("Y-m-d");
	}else{

		if(!isset($accountc)) {
			$accountc=0;
		}

		if(!isset($dept['pca'])) {
			$accountc+=0;
			$dept['pca']=$accountc;
			$dept['debtacc']=$accountc;
		}

		if(isset($bankid)) {
			$dept['pca']=$BA;

		}

		$tpp=0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			if(!(isset($cust['surname']))) {
				$cust['surname']=$inv['cusname'];
				$cust['addr1']=$inv['cusaddr'];
			}

			# Debit Customer and Credit stock
			$wamt += $inv["delivery"] / count($totstkamt);
			$tot_post += $wamt;

			writetrans($dept['pca'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cust[surname].");
		}

		if(isset($bankid)) {
			db_connect();
			$bankid+=0;
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div) VALUES ('$bankid', 'deposit', '$td', '$inv[cusname]', 'Non-Stock Sales on invoice No.$real_invid customer $inv[cusname]', '0', '$TOTAL', '$VAT', '$inv[chrgvat]', 'no', '$stkacc', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$sql = "UPDATE cubit.hire_nons_invoices SET jobid='$bankid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post+=$VAT;
			writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cust[surname].");
		}

		$sdate = date("Y-m-d");
	}

	$tot_post=sprint($tot_post);

	db_connect();
	if($ctyp == 's'){
		$sql = "UPDATE cubit.nons_invoices SET cusid = '$cusnum', ctyp = '$ctyp', cusname = '$cus[surname]', cusaddr = '$cus[addr1]', cusvatno = '$cus[vatnum]', done = 'y', invnum = '$real_invid', balance = total WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
//
// 		# Record the payment on the statement
// 		$sql = "INSERT INTO stmnt(cusnum, invid, docref, amount, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL','$inv[odate]', 'Non-Stock Invoice', '".USER_DIV."')";
// 		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
//
// 		# Record the payment on the statement
// 		$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL','$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."')";
// 		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
//
// 		# Update the customer (make balance more)
// 		$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
// 		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
//
// 		# Make ledge record
// 		custledger($cusnum,$stkacc , $td, $real_invid, "Non Stock Invoice No. $real_invid", $TOTAL, "d");
// 		custDT($TOTAL, $cusnum, $td);
//
// 		$tot_dif=sprint($tot_post-$TOTAL);

		if (!isset($tot_dif)) $tot_dif = 0;

		if($tot_dif>0) {
			writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif<0) {
			$tot_dif=$tot_dif*-1;
			writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	} else {
		$date = date("Y-m-d");

		$sql = "UPDATE cubit.nons_invoices SET balance=total, cusname = '$cust[surname]', accid = '$dept[pca]', ctyp = '$ctyp', cusaddr = '$cust[addr1]', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

		$tot_dif=sprint($tot_post-$TOTAL);

		if($tot_dif>0) {
			writetrans($varacc,$dept['pca'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif<0) {
			$tot_dif=$tot_dif*-1;
			writetrans($dept['pca'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	}

	db_connect();
	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES('$inv[odate]', '$invid', '$real_invid', '$dept[debtacc]', '$VAT', '$TOTAL', 'non', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	com_invoice($inv['salespn'],($TOTAL-$VAT),0,$real_invid,$inv["odate"]);

	db_conn('cubit');

	if(!isset($cusnum))
		$cusnum = 0;

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$cusnum','$na','Non stock Invoice $real_invid','$inv[sdate]','".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);

	// Customer Statement -----------------------------------------------------
	# Record the payment on the statement
	$sql = "INSERT INTO stmnt(cusnum, invid, docref, amount, date, type, div)
			VALUES('$inv[cusnum]', '$inv[invid]', '$inv[invnum]', '$TOTAL',
				'$inv[odate]', 'Hire Invoice H$real_invid', '".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record");

	# Record the payment on the statement
	$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance,
				date, type, div)
			VALUES ('$inv[cusnum]', '$inv[invid]', '$inv[invnum]', '$TOTAL',
				'$TOTAL', '$inv[odate]', 'Hire Invoice no H$real_invid', '".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record.");

	# Update the customer (make balance more)
	$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2))
			WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$stkacc = qryAccountsName("Hire Sales");
	$stkacc = $stkacc["accid"];

	# Make ledger record
	custledger($inv["cusnum"], $stkacc, $inv["odate"], $inv["invid"],
		"Hire Invoice No. H$real_invid", $TOTAL, "d");
	custDT($TOTAL, $inv["cusnum"], $inv["odate"]);
	// ------------------------------------------------------------------------

	# Get selected stock in this invoice
	$sql = "SELECT * FROM hire.hire_nons_inv_items
			WHERE invid='$invid' AND div='".USER_DIV."'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$item_count = pg_num_rows($item_rslt);
	
	$totamt = 0;
	while ($item_data = pg_fetch_array($item_rslt)) {
		$totamt += $item_data["amt"];

		$sql = "SELECT * FROM cubit.assets WHERE id='$item_data[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		$discount = $item_data["amt"] / 100 * $traddisc;

		// Add up revenue
		$sql = "INSERT INTO hire.revenue (group_id, asset_id, total, discount,
					hire_invnum, inv_invnum, cusname)
				VALUES ('$asset_data[grpid]', '$item_data[asset_id]',
					'$item_data[amt]', '$discount', '$hirenum',
					'$real_invid', '$inv[cusname]')";
		db_exec($sql) or errDie("Unable to update revenue");

		$sql = "INSERT INTO cubit.nons_inv_items (invid, qty, description,
			div, amt, unitcost, vatex, accid, asset_id)
		VALUES ('$invid', '$item_data[qty]',
			'$item_data[description]', '$item_data[div]', '$item_data[amt]',
			'$item_data[amt]',  '2', '$item_data[accid]', '$item_data[asset_id]')";
		db_exec($sql) or errDie("Unable to add non stock items.");

		$sql = "UPDATE hire.assets_hired SET return_time=CURRENT_TIMESTAMP,
					inv_invnum='$real_invid', value='$item_data[amt]'
				WHERE item_id='$item_data[item_id]'";
		db_exec($sql) or errDie("Unable to record asset return time.");
	}

	// Add the delivery discount to the total revenue
	if ($inv["delivery"]) {
		$discount = $inv["delivery"] / 100 * $traddisc;

		$sql = "INSERT INTO hire.revenue (discount)
				VALUES ('$discount')";
		db_exec($sql) or errDie("Unable to update revenue");
	}

	$cc = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', ''); </script>";


	 db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='SALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sp="<tr><td><b>Sales Person:</b> $inv[salespn]</td></tr>";
	} else {
		$sp="";
	}
	if($inv['chrgvat']=="yes") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="no") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	if ($inv["remarks"] == "") {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve the default comments from Cubit.");
		$inv["remarks"] = pg_fetch_result($commRslt, 0);
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Retrieve the company information
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='2' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;
	";

// 	$nolr_borders = "
// 		border-top: 2px solid #000;
// 		border-left: none;
// 		border-right: none;
// 		border-bottom: none;
// 	";

	$sql = "UPDATE hire.hire_invoices SET done='y', delivery='0.00'
			WHERE invnum='".getHirenum($inv["hire_invid"])."'";
	db_exec($sql) or errDie("Unable to update invoices.");

	vatr($vd['id'], $td, "OUTPUT", $vd['code'], $refnum,
		"Non-Stock Sales, invoice No.$real_invid", $TOTAL, $inv["vat"]);

	$details = "";

	$SUBTOT = sprint($totamt);

	for ($i = 0; $i <= $page; $i++) {
		if ($monthly) {
			$monthly_out = "
			<tr>
				<td style='border-right: 2px solid #000'>Invoiced to date ".date("d-m-Y")."</td>
			</tr>";
		} else {
			$monthly_out = "";
		}

		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$details .= "<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left' rowspan='2'><img src='../compinfo/getimg.php' width='230' height='47'></td>
					<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Invoice</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr1]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr1]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr2]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr2]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr3]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr3]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr4]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[postcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>REG:</b> $comp_data[regnum]</b>&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>$bank_data[bankname]</b>&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT REG:</b> $comp_data[vatnum]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch</b> $bank_data[branchname]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Tel:</b> $comp_data[tel]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch Code:</b> $bank_data[branchcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Fax:</b> $comp_data[fax]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Acc Num:</b> $bank_data[accnum]&nbsp;</td>
				</tr>
			</table>
			</td><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date</b></td>
					<td><b>Page Number</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$inv[odate]</td>
					<td>".($i + 1)."</td>
				</tr>

				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr><td>&nbsp</td></tr>
				<tr><td>&nbsp</td></tr>

				<tr>
					<td colspan='2'><b>Invoice No:</b> $real_invid</td>
				</tr>
				<tr>
					<td colspan='2'><b>Hire No:</b> $hirenum</td>
				</tr>
				$sp
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Tax Invoice To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$cus[surname]</b>&nbsp;</td>
					<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
					<td width='33%'><b>Delivery Address</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>".nl2br($cus["addr1"])."&nbsp;</td>
					<td style='border-right: 2px solid #000'>".nl2br($cus["paddr1"])."&nbsp;</td>
					<td>".nl2br($cus["del_addr1"])."&nbsp;</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $inv[cusvatno]</td>
					<td width='33%'><b>Customer Order No:</b> $inv[cordno]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>No of Days</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Rate</b></td>
					<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
				</tr>
				$products_out
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$inv[remarks]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms:</b> $inv[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td><b>".CUR."$SUBTOT</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Delivery</b></td>
					<td><b>".CUR."$inv[delivery]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Discount</b></td>
					<td><b>".CUR."$inv[discount]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td><b>".CUR."$inv[vat]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td><b>".CUR."$inv[total]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
				</tr>
				$monthly_out
			</table>
		</table>
		";
	}

	$amt = $pcash + $pcheque + $pcc;
	$_POST["amt"] = $amt;
	$_POST["date"] = $inv["odate"];

	recvpayment_write();

	$sql = "UPDATE cubit.nons_invoices SET cash='$pcash' WHERE invid='$inv[invid]'";
	db_exec($sql) or errDie("Unable to update cash value.");

	pglib_transaction("COMMIT");
	
	// Retrieve the template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$OUTPUT = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');</script>
			$details";
		require("../tmpl-print.php");
	} else {
		$OUTPUT = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');
		move (\"../$template?invid=$inv[invid]&type=nons\");
		</script>";
		require ("template.php");
	}
}

function recvpayment_write() {
	if (isset($_POST["btn_back"])) {
		return details($_POST);
	}
	extract($_POST);

	$bank_acc = qryAccountsName("Cash on Hand");
	$bank_acc = $bank_acc["accid"];

	$cred_acc = qryAccountsName("POS Credit Card Control");
	$cred_acc = $cred_acc["accid"];

	$v = new validate();
	$v->isOk($cusnum, "num", 1, 10, "Invalid customer id.");
	$v->isOk($bank_acc, "num", 1, 10, "Invalid cash account selected.");
	$v->isOk($pcc, "float", 1, 40, "Invalid credit card amount.");
	$v->isOk($pcash, "float", 1, 40, "Invalid cash amount.");
	$v->isOk($pcheque, "float", 1, 40, "Invalid cheque amount.");
	$v->isOk($amt, "float", 1, 40, "Invalid total received amount.");
	$v->isOk($date, "date", 1, 1, "Invalid invoice date.");

	if ($v->isError()) {
		return details($_POST, $v->genErrors());
	}

	$sdate = $date;

	$cus = qryCustomer($cusnum);
	$dept = qryDepartment($cus["deptid"], "debtacc");
	$refnum = getrefnum();

	pglib_transaction("BEGIN");

	/* do the calculations/recordings */
	# update the customer (make balance less)
	$sql = "UPDATE cubit.customers SET balance = (balance - '$amt'::numeric(13,2))
			WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$sql = "SELECT prd,invnum,descrip,age FROM cubit.nons_invoices
			WHERE invid ='$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}

	$inv = pg_fetch_array($invRslt);

	$inv['invnum'] += 0;

	# reduce the money that has been paid
	if ($amt) {
		$sql = "UPDATE cubit.nons_invoices
				SET balance = (balance - $amt::numeric(13,2))
				WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		$sql = "UPDATE cubit.open_stmnt
				SET balance = (balance - $amt::numeric(13,2))
				WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# record the payment on the statement
		$sql = "INSERT INTO cubit.stmnt(cusnum, invid, amount, date, type, div)
				VALUES('$cus[cusnum]','$inv[invnum]',
				'".($amt - ($amt * 2))."','$sdate',
				'Payment for Hire Invoice No. $inv[invnum]',
				'".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		$cash_amt = $pcash + $pcheque;
		$cred_amt = $pcc;

		custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Hire Invoice No. $inv[invnum]", $cash_amt, "c");
		custledger($cus["cusnum"], $cred_acc, $sdate, $inv["invnum"], "Payment for Hire Invoice No. $inv[invnum]", $cred_amt, "c");

		custCT($amt, $cus["cusnum"], $sdate);
		//recordCT($amt, $cus['cusnum'],$inv['age'],$sdate);
	}

	if (!isset($invids[$key])) $invids[$key] = 0;
	if (!isset($rinvids)) $rinvids = 0;
	if (!isset($amounts)) $amounts = 0;
	if (!isset($invprds)) $invprds = 0;
	if (!isset($rages)) $rages = 0;
	if (!isset($invidsers)) $invidsers = 0;

	$rinvids .= "|$invids[$key]";
	$amounts .= "|$amt";
	$invprds .= "|0";
	$rages .= "|$inv[age]";
	$invidsers .= " - $inv[invnum]";

	$sql = "SELECT * FROM core.accounts WHERE topacc='6400' AND accnum='000'";
	$acc_rslt = db_exec($sql);
	$deptacc = pg_fetch_result($acc_rslt, 0);

	if ((float)$pcash) {
		writetrans($bank_acc, $deptacc, $sdate, $refnum, $pcash,
			"Payment for Invoice $inv[invnum] from customer $cus[cusname] $cus[surname]");
	}
	if ((float)$pcc) {
		$sql = "SELECT accid FROM core.accounts WHERE topacc='7300' AND accnum='000'";
		$acc_rslt = db_exec($sql);
		$accid = pg_fetch_result($acc_rslt, 0);

		writetrans($accid, $deptacc, $sdate, $refnum, $pcc,
			"Payment for Invoice $invidsers from customer $cus[cusname] $cus[surname]");
	}
	if ((float)$pcheque) {
		$sql = "SELECT accid FROM core.accounts WHERE topacc='7200' AND accnum='000'";
		$acc_rslt = db_exec($sql);
		$accid = pg_fetch_result($acc_rslt, 0);

		writetrans($accid, $deptacc, $sdate, $refnum, $pcheque,
			"Payment for Invoice $invidsers from customer $cus[cusname] $cus[surname]");
	}

	db_conn('cubit');

	pglib_transaction("COMMIT");

	$_POST["pcc"] = $_POST["pcheque"] = $_POST["pcash"] = "0.00";

	return cdetails($_POST, "<li class='err'>Payment received successfully</li>");
}

function recordCT($amount, $cusnum, $age, $date="", $changemon = false) {
	db_connect();
	if ($date=="") {
		$date = date("Y-m-d");
	}

	$amount = ($amount * (-1));
	$date_ins = "'$date'";

	$sql = "INSERT INTO custran(cusnum, odate, balance, div, age)
			VALUES('$cusnum', $date_ins, '$amount', '".USER_DIV."', '$age')";
	$purcRslt = db_exec($sql)
		or errDie("Unable to update int purchases information in Cubit.");
}

function allocamt(&$tot, $invbal) {
	if ($tot >= $invbal) {
		$val = $invbal;
		$tot -= $invbal;
	} else {
		$val = $tot;
		$tot = 0;
	}

	return sprint($val);
}

# vats
function vats($amt, $inc, $VATP){
	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	} elseif($inc == "yes") {
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	} else {
		$ret = ($amt);
	}

	return $ret;
}
?>

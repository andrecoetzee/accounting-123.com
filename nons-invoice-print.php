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

if ( isset($HTTP_GET_VARS) && isset($HTTP_POST_VARS) ) {
	array_merge($HTTP_POST_VARS, $HTTP_GET_VARS);
}

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = slct($HTTP_GET_VARS);
} else {
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "slct":
				$OUTPUT = cdetails($HTTP_POST_VARS);
				break;
			case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;
			case "cconfirm":
				$OUTPUT = cconfirm($HTTP_POST_VARS);
				break;
			case "prewrite":
				$OUTPUT = prewrite();
				break;
			case "cprewrite":
				$OUTPUT = cprewrite();
				break;
			case "write":
				$OUTPUT = write($HTTP_POST_VARS);
				break;
			case "cwrite":
				$OUTPUT = cwrite($HTTP_POST_VARS);
				break;
			case "":
				$OUTPUT = cdetails($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	}else{
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# get templete
require("template.php");




# Details
function slct($HTTP_GET_VARS)
{

	# Get vars
	extract ($HTTP_GET_VARS);

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
		$letters = remval($letters);
		$whe = "AND lower(surname) LIKE lower('%$letters%')";
	} else {
		$letters = "";
		$whe = "";
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$custs = "No customers
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
			<tr bgcolor='".bgcolorg()."' ".ass("Select when selling non stock goods to your customers").">
				<td><input type='radio' name='ctyp' value='s' checked='yes'> Select Customer</td>
				<td>$custs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when the sale of non stock goods is a cash sale").">
				<td><input type='radio' name='ctyp' value='c'>Cash Sale</td>
				<td>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when the sale of non stock goods is not a cash sale").">
				<td><input type='radio' name='ctyp' value='ac'>Ledger Accounts Sale</td>
				<td></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Search by surname</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
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
function cdetails($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_GET_VARS);

	if(!isset($button) && (isset($starting))) {
		return slct($HTTP_GET_VARS);
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

	if($ctyp == "ac") {
		return acdetails($HTTP_GET_VARS);
	}

	# Get Invoice info
	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
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
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$cust[addr1]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$cust[vatnum]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
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
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
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
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='bankid' value='$inv[accid]'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td>$supacc</td>
				</tr>
			</table>";
	}

	$stkacc = "";

	$useaccdrop = getCSetting ("USE_NON_STOCK_ACCOUNTS");
	if (isset ($useaccdrop) AND $useaccdrop == "yes"){
		$acc_sql = "SELECT * FROM non_stock_account_list ORDER BY accname";
		$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
		if (pg_numrows ($run_acc) > 0){
			while($acc = pg_fetch_array($run_acc)){
				$stkacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
			}
			$stkacc .= "</select>";
		}
	}else {
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
		}
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid']))
				continue;
			$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
		$stkacc .= "</select>";
	}

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

	$sql = "SELECT * FROM nons_inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td nowrap>$ex $stkd[description]</td>
				<td>$stkd[qty]</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap>".CUR." $stkd[amt]</td>
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
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>$details</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>T $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Inv No.</td>
							<td valign='center'>$inv[docref]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$sday-$smon-$syear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
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
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}


# Customer details
function acdetails($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

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

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($inv['odate']) >= strtotime($blocked_date_from) AND strtotime($inv['odate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

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
				<input type='hidden' name='cusnum' value=$cusnum>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$cust[addr1]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$cust[vatnum]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>
			</table>";
	}elseif($ctyp == 'ac'){
// 		db_conn("exten");
// 		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
// 		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
// 		$dept = pg_fetch_array($deptRslt);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='deptid' value='$deptid'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Order number</td>
					<td valign='center'>$inv[cordno]</td>
				</tr>";
	}

	$stkacc = "";
	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}
	while($acc = pg_fetch_array($accRslt)){
		$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$stkacc .= "</select>";

	$details .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Account <input align='right' type='button' onClick=\"window.open('core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td valign='center'><select name='accountc'>$stkacc</select></td>
			</tr>
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
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td nowrap>$stkd[description]</td>
				<td>$stkd[qty]</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap>".CUR." $stkd[amt]</td>
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
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='ctyp' value='$ctyp'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>$details</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>T $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Inv No.</td>
							<td valign='center'>$inv[docref]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$sday-$smon-$syear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
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
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}


# Customer Confirm
function cconfirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

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

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($inv['odate']) >= strtotime($blocked_date_from) AND strtotime($inv['odate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

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

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$i++;
		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td nowrap>$stkd[description]</td>
				<td>$stkd[qty]</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap>".CUR." $stkd[amt]</td>
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
	list($syear, $smon, $sday) = explode("-", $inv['odate']);

	db_connect();
	# cust % bank
	if($ctyp == 's'){
		$cust = qryCustomer($cusnum);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$cust[addr1]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$cust[vatnum]</td>
				</tr>
			</table>";
	}elseif($ctyp == 'c'){
		$dept = qryDepartment($deptid);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='deptid' value='$deptid'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
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
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
		}

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<input type='hidden' name='bankid' value='$inv[accid]'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td>$supacc</td>
				</tr>
			</table>";
	}elseif($ctyp == 'ac'){
		$accountc += 0;
		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $accountc);

		$accd = pg_fetch_array($accRs);

		$details = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Customer Details </th>
				</tr>
				<input type='hidden' name='accountc' value='$accountc'>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$inv[cusname] </td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer Address</td>
					<td valign='center'><pre>$inv[cusaddr]</pre></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td valign='center'>$inv[cusvatno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account</td>
					<td valign='center'>$accd[accname]</td>
				</tr>
			</table>";
	}

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
			<input type='hidden' name='key' value='cwrite'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='ctyp' value='$ctyp'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>$details</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>T $inv[invid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Inv No.</td>
							<td valign='center'>$inv[docref]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$sday-$smon-$syear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
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
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right' nowrap>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}


# Customer write
function cwrite($HTTP_GET_VARS)
{

	$showvat = TRUE;

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	if(isset($stkaccs)){
		foreach($stkaccs as $key => $accid){
			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
		}
	}else{
		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$td = $inv['odate'];

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

		$na = $cus['surname'];
	}elseif($ctyp == 'c'){
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

		$na = $inv['cusname'];
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

		$na = $inv['cusname'];
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

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

    # Put in product
	$i = 0;
	$page = 0;
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$stkacc = $stkaccs[$stk['id']];

		$Sl = "SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$stk['vatex'] = "y";
		}

		//print $inv['chrgvat'];exit;

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$t = $inv['chrgvat'];

		$VATP = TAX_VAT;
		# keep records for transactions
		if(isset($totstkamt[$stkacc])){
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] += vats($stk['amt'], 'novat', $vd['vat_amount']);
				$va = 0;
				$inv['chrgvat'] = "";
			} else {
				$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va = sprint($stk['amt'] - vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount']/100);
				}
			}
		}else{
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] = $stk['amt'];
				$inv['chrgvat'] = "";
				$va = 0;
			} else {
				$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va = sprint($stk['amt'] - vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount'] / 100);
				}
			}
		}

		vatr($vd['id'], $td, "OUTPUT", $vd['code'], $refnum, "Non-Stock Sales, invoice No.$real_invid", (vats($stk['amt'],$inv['chrgvat'], $vd['vat_amount'])+$va), $va);

		$inv['chrgvat']=$t;

// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat']);
// 		}
		$sql = "UPDATE nons_inv_items SET accid = '$stkacc' WHERE id = '$stk[id]'";
		$sRslt = db_exec($sql);

		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
//			$ex = "&nbsp;&nbsp;";
			$ex = "";
		}

		$products[$page][] = "
			<tr valign='top'>
				<td style='border-right: 2px solid #000'>$ex $stk[description]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$stk[qty]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000' nowrap>".CUR." $stk[unitcost]&nbsp;</td>
				<td align='right' nowrap>".CUR." $stk[amt]&nbsp;</td>
			</tr>";

		$i++;
	}

 	$blank_lines = 25;
 	foreach ($products as $key=>$val) {
 		$bl = $blank_lines - count($products[$key]);
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "<tr>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td>&nbsp;</td>
 			</tr>";
 		}
 	}

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
		$bankid += 0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$deptd = pg_fetch_array($deptRslt);
		}

		db_conn('core');

		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$rd = db_exec($Sl) or errDie("Unable to get data.");
		$data = pg_fetch_array($rd);

		$BA = $data['accnum'];
	}

	$tot_post = 0;
	# bank  % cust
	if($ctyp == 's'){
		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class='err'>Department not Found.</li>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp = 0;
		# record transaction from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Customer and Credit stock
			$tot_post += $wamt;
			writetrans($dept['debtacc'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($dept['debtacc'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cus[surname].");
		}

		$sdate = date("Y-m-d");
	}else{

		if(!isset($accountc)) {
			$accountc = 0;
		}

		if(!isset($dept['pca'])) {
			$accountc += 0;
			$dept['pca'] = $accountc;
			$dept['debtacc'] = $accountc;
		}

		if(isset($bankid)) {
			$dept['pca'] = $BA;

		}

		$tpp = 0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			if(!(isset($cust['surname']))) {
				$cust['surname'] = $inv['cusname'];
				$cust['addr1'] = $inv['cusaddr'];
			}
			# Debit Customer and Credit stock
			$tot_post += $wamt;
			writetrans($dept['pca'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cust[surname].");
		}

		if(isset($bankid)) {
			db_connect();
			$bankid += 0;
			$sql = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, 
					descript, cheqnum, 
					amount, vat, chrgvat, banked, accinv, div
				) VALUES (
					'$bankid', 'deposit', '$td', '$inv[cusname]', 
					'Non-Stock Sales on invoice No.$real_invid customer $inv[cusname]', '0', 
					'$TOTAL', '$VAT', '$inv[chrgvat]', 'no', '$stkacc', '".USER_DIV."'
				)";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$sql = "UPDATE nons_invoices SET jobid='$bankid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cust[surname].");
		}

		$sdate = date("Y-m-d");
	}

	$tot_post = sprint($tot_post);

	db_connect();
	if($ctyp == 's'){
		$sql = "UPDATE nons_invoices SET balance = total, cusid = '$cusnum', ctyp = '$ctyp', cusname = '$cus[surname]', cusaddr = '$cus[addr1]', cusvatno = '$cus[vatnum]', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt (
				cusnum, invid, docref, amount, date, 
				type, div, allocation_date, allocation_balance
			) VALUES (
				'$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$inv[odate]', 
				'Non-Stock Invoice', '".USER_DIV."', '$inv[odate]', '".abs($TOTAL)."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Record the payment on the statement

		$sql = "
			INSERT INTO open_stmnt (
				cusnum, invid, docref, amount, balance, 
				date, type, div
			) VALUES (
				'$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL', 
				'$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(16,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Make ledge record
		custledger($cusnum,$stkacc , $td, $real_invid, "Non Stock Invoice No. $real_invid", $TOTAL, "d");
		custDT($TOTAL, $cusnum, $td, $invid, "nons");

		$tot_dif = sprint($tot_post-$TOTAL);

		if($tot_dif > 0) {
			writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif < 0) {
			$tot_dif = $tot_dif * -1;
			writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	} else {
		$date = date("Y-m-d");

		$sql = "UPDATE nons_invoices SET balance=total, cusname = '$cust[surname]', accid = '$dept[pca]', ctyp = '$ctyp', cusaddr = '$cust[addr1]', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

		$tot_dif = sprint($tot_post - $TOTAL);

		if($tot_dif > 0) {
			writetrans($varacc,$dept['pca'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		} elseif($tot_dif < 0) {
			$tot_dif = $tot_dif * -1;
			writetrans($dept['pca'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
		}
	}

	db_connect();

	$sql = "
		INSERT INTO salesrec (
			edate, invid, invnum, debtacc, 
			vat, total, typ, div
		) VALUES (
			'$inv[odate]', '$invid', '$real_invid', '$dept[debtacc]', '$VAT', 
			'$TOTAL', 'non', '".USER_DIV."'
		)";
	$recRslt = db_exec($sql);

	com_invoice($inv['salespn'],($TOTAL-$VAT),0,$real_invid,$inv["odate"]);

	db_conn('cubit');

	if(!isset($cusnum))
		$cusnum = 0;

	$Sl = "
		INSERT INTO sj (
			cid, name, des, date, 
			exl, vat, inc, div
		) VALUES (
			'$cusnum', '$na', 'Non stock Invoice $real_invid', '$inv[sdate]', 
			'".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."'
		)";
	$Ri = db_exec($Sl);

# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	# $stkdRslt = db_exec($sql);

	$cc = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', ''); </script>";


	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<tr><td><b>Sales Person:</b> $inv[salespn]</td></tr>";
	} else {
		$sp = "";
	}
	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
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

	#make sure we have a valid bank id for customer
	if (!isset($inv['bankid']) OR strlen ($inv['bankid']) < 1){
		$inv['bankid'] = '2';
	}

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='$inv[bankid]' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;";

// 	$nolr_borders = "
// 		border-top: 2px solid #000;
// 		border-left: none;
// 		border-right: none;
// 		border-bottom: none;
// 	";

	$details = "";

	for ($i = 0; $i <= $page; $i++) {
		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$details .= "
			<center>
			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr><td>
				<table border='0' cellpadding='2' cellspacing='2' width='100%'>
					<tr>
						<td align='left' rowspan='2'><img src='compinfo/getimg.php' width=230 height=47></td>
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
						<td colspan='2'><b>Proforma Inv No:</b> $inv[docref]</td>
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
						<td style='border-bottom: 2px solid #000; border-right: 2px solid #000' align='right'><b>Unit Price</b></td>
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
						<td><b>".CUR."$inv[subtot]</b></td>
					</tr>
					<tr>
						<td style='border-right: 2px solid #000'>&nbsp;</td>
						<td><b>VAT $vat14:</b></td>
						<td nowrap><b>".CUR."$inv[vat]</b></td>
					</tr>
					<tr>
						<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
						<td><b>Total Incl VAT:</b></td>
						<td nowrap><b>".CUR."$inv[total]</b></td>
					</tr>
					<tr>
						<td style='border-right: 2px solid #000'>&nbsp;</td>
					<tr>
					<tr>
						<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
					</tr>
					</tr>
				</table>
			</table>";
	}



	// Retrieve the template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$OUTPUT = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');</script>
			$details";
		require("tmpl-print.php");
	} else {
		$OUTPUT = "
			<script>
				CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', '');
				move (\"$template?invid=$inv[invid]&type=nons\");
			</script>";
		require ("template.php");
	}
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

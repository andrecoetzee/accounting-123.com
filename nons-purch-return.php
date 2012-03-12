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

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "update":
				$OUTPUT = write($_POST);
				break;
			case "confirm":
				$OUTPUT = confirm($_POST);
				break;
			case "write":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
			}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");



# Details
function details($_POST, $error="")
{

	$showvat = TRUE;

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err>".$e["msg"]."</li>";
		}
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Order info
	db_conn($prd);

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Drop Downs --- */

	# format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

	# Get selected supplier info
	db_connect();

	$hide = "";
	if($pur['ctyp'] == 's'){

		if($pur['supid'] == 0) {
			return "
				You can only return a non stock purchase made after the update.<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='nons-purchase-view.php'>View Orders</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
		}

		$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
		if (pg_numrows ($supRslt) < 1) {
			$error = "<li class='err'> Supplier not Found.";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$sup = pg_fetch_array($supRslt);
			$pur['supplier'] = $sup['supname'];
			$pur['supaddr'] = $sup['supaddr'];
			$supacc = $sup['supno'];
		}

	}elseif($pur['ctyp'] == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$pur[typeid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err> Department not Found.";
			$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[deptname] - Cash on Hand";
		}
	}elseif($pur['ctyp'] == 'p'){

		core_connect();
		# Get Petty cash account
		$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $cashacc);
		if(pg_numrows($accRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.";
		}
		$acc = pg_fetch_array($accRslt);

		$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";

	} elseif($pur['ctyp'] == 'ac'){

		core_connect();
        # Get Petty cash account
		//$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $pur['mpurid']);
		if(pg_numrows($accRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.";
		}
		$acc = pg_fetch_array($accRslt);

		$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";

	} elseif($pur['ctyp'] == 'cb'){

		core_connect();
		# Get Petty cash account
		//$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$pur[supid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
			$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
			//$hide = "<input type=hidden name=bankid value='$pur[supid]'><input type=hidden name=ctyp value='$ctyp'>";
		}

	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>VAT</th>
				<th>ITEM ACCOUNT</th>
			<tr>";

	# get selected stock in this Order
	db_conn($prd);

	$sql = "SELECT *, (qty - rqty) as qty FROM nons_pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		db_connect();

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$stkd['accid'] += 0;
		core_connect();
		# Get selected stock line
		$sql = "SELECT accname,accid,topacc,accnum FROM accounts WHERE accid = '$stkd[accid]' AND div = '".USER_DIV."'";
		if(!$accRslt = @db_exec($sql)) {
			return "
				You can only return non stock purchases made after the update.<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='nons-purchase-view.php'>View Orders</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
		}
		$acc = pg_fetch_array($accRslt);
		$stkacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='ids[]' value='$stkd[id]'>$stkd[cod]</td>
				<td>$stkd[des]</td>
				<td><input type='hidden' name='qts[]' value='$stkd[qty]'><input type='text' size='5' name='qtys[]' value='$stkd[qty]'></td>
				<td><input type='hidden' name='unitcost[]' value='$stkd[unitcost]'>".CUR."$stkd[unitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>".CUR." ".sprint($stkd['amt'])."</td>
				<td nowrap>".CUR." $stkd[svat]</td>
				<td>$stkacc</td>
			</tr>";
		$key++;
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

	$pur['spurnum']+=0;

	if(!isset($supacc)) {
		if($pur['spurnum'] == 0) {

			return "
				You cannot return a linked non-stock purchase made before the update.
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='nons-purchase-view.php'>View Orders</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
		}
		//header("Location: lnons-purch-ret.php?purid=$purid&prd=$prd");
		//exit;
	}

	if($pur['spurnum']>0) {
		$sup="";
	} else {
		$sup="<tr bgcolor='".bgcolorg()."'><td>Account</td><td>$supacc</td></tr>";
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
		<h3>Non-Stock Order return</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$pur[supplier]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Address</td>
							<td valign='center'><pre>$pur[supaddr]</pre></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value='$pur[refno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("p",$p_year,$p_month,$p_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td valign='center' nowrap>".CUR." <input type='hidden' name='shipchrg' size='10' value='$pur[shipchrg]'>$pur[shipchrg]</td>
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
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-purchase-new.php'>New Order</a></td>
							<td bgcolor='".bgcolorg()."' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-purchase-view.php'>View Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right' nowrap>".CUR." $pur[shipping]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right' nowrap>".CUR." $pur[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='upBtn' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}


# Confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
	}

	/*
	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Quantity for product number : <b>".($keys+1)."</b> is more that Qty Orderd");
			}
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}
	*/

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pdate) >= strtotime($blocked_date_from) AND strtotime($pdate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get Order info
	db_conn($prd);

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Drop Downs --- */

	# format date
	//list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

	# Get selected supplier info
	db_connect();

	$hide = "";
	if($pur['ctyp'] == 's'){
		$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
		if (pg_numrows ($supRslt) < 1) {
			$error = "<li class='err'> Supplier not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$sup = pg_fetch_array($supRslt);
			$pur['supplier'] = $sup['supname'];
			$pur['supaddr'] = $sup['supaddr'];
			$supacc = $sup['supno'];
		}
	}elseif($pur['ctyp'] == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$pur[typeid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Department not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[deptname] - Cash on Hand";
		}
	}elseif($pur['ctyp'] == 'p'){
		core_connect();
        # Get Petty cash account
		$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $cashacc);
		if(pg_numrows($accRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.</li>";
		}
		$acc = pg_fetch_array($accRslt);

		$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";
	} elseif($pur['ctyp'] == 'ac'){
		core_connect();
        # Get Petty cash account
		//$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $pur['mpurid']);
		if(pg_numrows($accRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.</li>";
		}
		$acc = pg_fetch_array($accRslt);

		$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";
	} elseif($pur['ctyp'] == 'cb'){
		core_connect();
        # Get Petty cash account
		//$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$pur[supid]'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[bankname] - $dept[accname]($dept[acctype])";
			//$hide = "<input type=hidden name=bankid value='$pur[supid]'><input type=hidden name=ctyp value='$ctyp'>";
		}
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>VAT</th>
				<th>ITEM ACCOUNT</th>
			<tr>";

	# amount of stock in
	$totstkamt = array();
	$resub = 0;
	# Get subtotal

	if(!isset($qtys)||!is_array($qtys)) {
		return "
			All the items on this purchase have already been returned<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-purchase-view.php'>View Orders</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

	foreach($qtys as $keys => $value){
		# Skip zeros
		if($qtys[$keys] < 1){
			continue;
		}
		$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
	}
	$SUBTOTAL = array_sum($amt);
	$revat = 0;
	foreach($qtys as $keys => $value){
		if($qtys[$keys] < 1)
			continue;

		db_conn($prd);

		# get selected stock line
		$sql = "SELECT * FROM nons_pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		# Calculate cost amount bought
		$amt[$keys] = ($qtys[$keys] * $stkd['unitcost']);

		/* delivery charge */

			# Calculate percentage from subtotal
			$perc[$keys] = (($amt[$keys]/$SUBTOTAL) * 100);

			# Get percentage from shipping charges
			$shipc[$keys] = (($perc[$keys] / 100) * $shipchrg);

			# add delivery charges
			$amt[$keys] += $shipc[$keys];

		/* end delivery charge */

		# calculate vat
		$svat[$keys] = svat($amt[$keys], $stkd['amt'], $stkd['svat']);

		# received vat
		$revat += $svat[$keys];

		# make amount vat free
		if($pur['vatinc'] == "yes"){
			$amt[$keys] = ($amt[$keys] - $svat[$keys]);
		}

		# the subtotal + delivery charges
		$resub += $amt[$keys];

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$stkd['accid']+=0;

		core_connect();

		# Get selected stock line
		$sql = "SELECT accname,accid,topacc,accnum FROM accounts WHERE accid = '$stkd[accid]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acc = pg_fetch_array($accRslt);
		$stkacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='ids[]' value='$stkd[id]'>$stkd[cod]</td>
				<td>$stkd[des]</td>
				<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
				<td nowrap><input type='hidden' name='unitcost[]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>".CUR." ".sprint($amt[$keys])."</td>
				<td nowrap>".CUR." $svat[$keys]</td>
				<td>$stkacc</td>
			</tr>";
		$key++;
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($resub);

	# Get vat
	$VAT = sprint($revat);

	# Get Total
	$TOTAL = sprint($SUBTOT + $VAT);

	if($pur['spurnum'] > 0) {
		$sup = "";
	} else {
		$sup = "<tr bgcolor='".bgcolorg()."'><td>Account</td><td>$supacc</td></tr>";
	}

/* --- End Some calculations --- */
// for some reason the date could be changed on the confirm screen ... rather just pass on the vars ...
// ".mkDateSelect("p",$p_year,$p_month,$p_day)."

/* -- Final Layout -- */
	$confirm = "
		<center>
		<h3>Non-Stock Order return</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='TOTAL' value='$TOTAL'>
			<input type='hidden' name='SUBTOTAL' value='$SUBTOT'>
			<input type='hidden' name='VAT' value='$VAT'>
			<input type='hidden' name='p_year' value='$p_year'>
			<input type='hidden' name='p_month' value='$p_month'>
			<input type='hidden' name='p_day' value='$p_day'>
			<input type='hidden' name='remarks' value='$remarks'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$pur[supplier]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Address</td>
							<td valign='center'><pre>$pur[supaddr]</pre></td>
						</tr>
						$sup
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value='$pur[refno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$p_day-$p_month-$p_year</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td valign='center'>".CUR." <input type='hidden' name='shipchrg' size='10' value='$pur[shipchrg]'>$pur[shipchrg]</td>
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
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-purchase-new.php'>New Order</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$remarks</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-purchase-view.php'>View Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='upBtn' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $confirm;

}


# Details
function write($_GET)
{

	# get vars
	extract ($_GET);
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$prd+=0;
	$v->isOk ($purid, "num", 1, 20, "Invalid Invoice number.");
	$sndate = $p_year."-".$p_month."-".$p_day;
	if( !checkdate($p_month, $p_day, $p_year) ){
		$v->addError($sdate, "Invalid Date.");
	}

	$td=$sndate;

	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
		$v->isOk ($qtys[$key], "num", 1, 20, "Invalid Item quantity.");
		//$v->isOk ($amts[$key], "float", 1, 20, "Invalid Item amount.");
	}
	//$v->isOk ($subtot, "float", 1, 20, "Invalid sub-total amount.");
	//$v->isOk ($vat, "float", 1, 20, "Invalid vat amount.");
	//$v->isOk ($total, "float", 1, 20, "Invalid total amount.");

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

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($sndate) >= strtotime($blocked_date_from) AND strtotime($sndate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

	db_conn($prd);

	# Get invoice info
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found$sql</i>";
	}
	$inv = pg_fetch_array($invRslt);

	db_conn("cubit");

	$sql = "SELECT * FROM nons_purchasesn";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Non-Stock Order information");
	if (pg_numrows ($purRslt) < 1) {
		$noteid = 2;
	} else {
		$noteid = pglib_lastid("nons_purchasesn", "id");
		$noteid++;
	}

	$refnum = getrefnum();

	db_conn("cubit");
	if($inv['spurnum'] > 0) {
		if($inv['ctyp'] == "sup") {
			$inv['ctyp'] = 's';
			$inv['supid'] = $inv['typeid'];
		} elseif($inv['ctyp'] == "led") {
			$inv['ctyp'] = 'c';
			$inv['deptid'] = $inv['typeid'];
		} else{
			$inv['ctyp'] = 'p';
		}
	}

# Begin updates
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	if($inv['ctyp'] == 's'){
			$sql = "SELECT * FROM suppliers WHERE supid = '$inv[supid]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);

		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql) or errDie("Unable to get details.");
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
	}elseif($inv['ctyp'] == 'c'){

		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql) or errDie("Unable to get details.");
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}

		$dept['credacc'] = $dept['pca'];
	}elseif($inv['ctyp'] == 'cb'){
		$bankid = $inv['supid'];
		$bankid += 0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err> Bank not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$deptd = pg_fetch_array($deptRslt);
		}

		db_conn('core');

		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$rd = db_exec($Sl) or errDie("Unable to get data.");
		$data = pg_fetch_array($rd);

		$BA = $data['accnum'];

		$dept['credacc'] = $BA;
	}elseif($inv['ctyp'] == 'p'){
		core_connect();
        # Get Petty cash account
		$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $cashacc);
		if(pg_numrows($accRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.</li>";
		}
		$acc = pg_fetch_array($accRslt);

		$dept['credacc'] = $cashacc;

		//$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";
		//$hide = "<input type=hidden name=supacc value='$cashacc'><input type=hidden name=ctyp value='$ctyp'>";
	}


	db_conn($prd);

	/* --- Start Products Display --- */
	$tot_post = 0;
	# Products layout
	$products = "";
	$resub = 0;
	$revat = 0;

	foreach($ids as $key => $id){
		db_conn($prd);
		$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		$stkacc = $stkd['accid'];
		# keep records for transactions

		$sql = "UPDATE nons_pur_items SET rqty = (rqty + '$qtys[$key]') WHERE id = '$stkd[id]'";
		$sRslt = db_exec($sql);

		# Calculate cost amount bought
		$amt[$key] = ($qtys[$key] * $unitcost[$key]);

		/* delivery charge */

			# Calculate percentage from subtotal
			$perc[$key] = (($amt[$key]/$SUBTOTAL) * 100);

			# Get percentage from shipping charges
			$shipc[$key] = (($perc[$key] / 100) * $shipchrg);

			# add delivery charges
			$amt[$key] += $shipc[$key];

		/* end delivery charge */

		# the subtotal + delivery charges
		$resub += $amt[$key];

		# calculate vat
		$svat[$key] = svat($amt[$key], $stkd['amt'], $stkd['svat']);

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		vatr($vd['id'],$td,"INPUT",$vd['code'],$refnum,"VAT for Non-Stock purchase No. $inv[purnum] returned",$amt[$key],$svat[$key]);

		# received vat
		$revat += $svat[$key];

		# make amount vat free
// 		if($inv['vatinc'] == "yes"){
// 			$amt[$key] = ($amt[$key] - $svat[$key]);
// 		}

		$amts[$key] = $stkd['unitcost']*$qtys[$key];

		$nv = sprint(($stkd['svat']/$stkd['qty'])*$qtys[$key]);

		if($inv['vatinc'] != "no") {
			$aev = $amts[$key]-$nv;
		} else {
			$aev = $amts[$key];
		}

		$stkd['accid'] += 0;

		db_conn('cubit');

		$sql = "
			INSERT INTO nons_pur_itemsn (
				noteid, qty, description, amt, unitcost, svat, div, 
				cod, des, ddate, accid
			) VALUES (
				'$noteid', '$qtys[$key]', '$stkd[des]', '$amts[$key]', '$stkd[unitcost]', '$stkd[svat]', '".USER_DIV."', 
				'$stkd[cod]', '$stkd[des]', '$stkd[ddate]', '$stkd[accid]'
			)";
		$stkdRslt = db_exec($sql) or errDie("Unable to insert note items.");

		if($inv['ctyp'] == 'ac'){
			$dept['credacc'] = $inv['mpurid'];
		}

		//$sql = "INSERT INTO nons_pur_itemsn(noteid, cod, des, qty, unitcost, amt, svat, ddate, accid, div) VALUES('$nid', '$stktc[cod]', '$stktc[des]', '$qtys[$key]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '$stktc[accid]', '".USER_DIV."')";

		if($inv['spurprd'] == 0) {

			$tot_post += $aev;
			writetrans($dept['credacc'],$stkacc, $td, $refnum, $aev, "Non-Stock purchase No. $inv[purnum] returned, Supplier $inv[supplier].");
		}
	}



	$supacc = $dept['credacc'];


	$tot_post = 0;

	$pur = $inv;

	if($inv['spurprd'] > 0) {

		$retot = $TOTAL;
		$vatamt = $VAT;

		db_conn($pur['spurprd']);

		# Get purchase info
		$sql = "SELECT * FROM purchases WHERE purnum = '$pur[spurnum]' AND div = '".USER_DIV."'";
		$spurRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
		if (pg_numrows ($spurRslt) < 1) {
			return "<li> - purchase Not Found</li>";
		}
		$spur = pg_fetch_array($spurRslt);

		db_conn($pur['spurprd']);

		# Get selected stock
		$sql = "SELECT * FROM pur_items WHERE purid = '$spur[purid]' AND div = '".USER_DIV."'";
		$sstkdRslt = db_exec($sql);
		while($sstk = pg_fetch_array($sstkdRslt)){

			if($spur['vatinc'] == "yes"){
				$csamt = sprint((($sstk['amt'] - $sstk['svat'])/$spur['subtot']) * ($retot - $vatamt));
			}else{
				$csamt = sprint((($sstk['amt'])/$spur['subtot']) * ($retot - $vatamt));
			}

			db_connect();

			# get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);
			$stkt = pg_fetch_array($stktRslt);

			/* Code insert */
				# get warehouse name
				db_conn("exten");
				$sql = "SELECT * FROM warehouses WHERE whid = '$stkt[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				$tot_post += $csamt;
				writetrans($supacc, $wh['stkacc'], date("d-m-Y"), $refnum, $csamt, "Non-Stock Purchase No. $pur[purnum] Returned.");
			/* End code insert */

			db_connect();
			if($stkt['units'] <> 0){
				$sql = "
					UPDATE stock 
					SET csamt = (csamt - '$csamt'), csprice = (csamt/units) 
					WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}else{
				$sql = "UPDATE stock SET csamt = (csamt - '$csamt') WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}
			$sdate = $pur['pdate'];
			stockrec($stkt['stkid'], $stkt['stkcod'], $stkt['stkdes'], 'ct', $sdate, 0, $csamt, "Cost decreased with Non Stock Purchase No. $pur[purnum], returned");

			# Just wanted to fix the xxx.xxxxxxe-x value
			# get selected stock
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);
			$stkt = pg_fetch_array($stktRslt);

			# $csprice = round(($stk['csamt']/$stk['units']), 2);
			if($stkt['units'] > 0){
				$csprice = round(($stkt['csamt']/$stkt['units']), 2);
			}else{
				$csprice = round($stkt['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

	}

	/* --- Start Some calculations --- */

	# Subtotal
	//$SUBTOT = sprint($subtot);
//	$VAT = sprint($vat);
	//$TOTAL = sprint($total);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	// print $inv['ctyp']; exit;



	//$real_noteid = divlastid('note', USER_DIV);



	db_conn('cubit');

	# bank  % cust
	if($inv['ctyp'] == 's'){
		$sql = "SELECT * FROM suppliers WHERE supid = '$inv[supid]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);

		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql) or errDie("Unable to get details.");
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp = 0;
		# record transaction  from data
		//foreach($totstkamt as $stkacc => $wamt){

		//}


		//$tot_dif=sprint($tot_post-$TOTAL);


	}elseif($inv['ctyp'] == 'b'){
		$dept['debtacc'] = getbankaccid($inv['accid']);
		$amounts = "";
		$accids = "";
		$vats = "";
		$chrgvats = "";
		$gamt = 0;

		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Cook vars
			$amounts .= "|$wamt";
			$accids .= "|$stkacc";
			$vats .= "|0";
			$chrgvats .= "|no";

			# Debit Customer and Credit stock
			//$tot_post+=$wamt;
			//writetrans($stkacc, $dept['debtacc'], date("d-m-Y"), $refnum, $wamt, "Non-Stock purchase No. $inv[invnum] Credit note No.$real_noteid.");
		}

		# Debit bank and credit the account involved


	}else{
		//$cusacc = $inv['accid'];
		$sdate = date("Y-m-d");
		# record transaction  from data
		//foreach($totstkamt as $stkacc => $wamt){
			# Debit Customer and Credit stock
//			$tot_post+=$wamt;
			//writetrans($stkacc, $cusacc,  date("d-m-Y"), $refnum, $wamt, "Non-Stock Purchase No. $inv[invnum] Credit note No.$real_noteid.");
			//pettyrec($cusacc, $sdate, "dt", "Non-Stock Purchase No. $inv[invnum] Credit note No.$real_noteid.", $wamt, "Account Sale Credit note");
		//}

		# Debit bank and credit the account involved
		//$tot_post+=$VAT;
		//writetrans($vatacc, $cusacc, date("d-m-Y"), $refnum, $VAT, "Non-Stock Purchase No. $inv[invnum] Credit note No.$real_noteid VAT.");
		//pettyrec($cusacc, $sdate, "dt", "Non-Stock Purchase No. $inv[invnum] Credit note No.$real_noteid VAT.", $VAT, "Account Sale Credit note VAT");


	}
	if($VAT <> 0){
		$tot_post += $VAT;
		writetrans($dept['credacc'],$vatacc, $td, $refnum, $VAT, "Non-Stock purchase No. $inv[purnum] Returned. Supplier $inv[supplier].");
	}

	$sdate = date("Y-m-d");

	if($inv['spurprd'] > 0) {
		$stkacc = $wh['stkacc'];

		$diff = sprint($TOTAL-$tot_post);


		if($diff > 0) {
			writetrans($dept['credacc'],$cvacc,$td , $refnum, $diff, "Cost Variance for Non stock Purchase No. $pur[purnum] Returned");
		} elseif($diff<0) {
			writetrans($cvacc,$dept['credacc'], $td , $refnum, -$diff, "Cost Variance for Non stock Purchase No. $pur[purnum] Returned");
		}
	}
	/*
	if($tot_dif>0) {
		writetrans($stkacc, $varacc, date("d-m-Y"), $refnum, $tot_dif, "Purchase Variance on invoice $real_invid");
	} elseif($tot_post<0) {
		writetrans($varacc, $stkacc, date("d-m-Y"), $refnum, $tot_dif, "Purchase Variance on invoice $real_invid");
	}*/

	$reff = $refnum;

	db_connect();
	if($inv['ctyp'] == 's'){
		# Record the payment on the statement
		$sql = "
			INSERT INTO sup_stmnt (
				supid, ref, amount, edate, descript, div, cacc
			) VALUES (
				'$inv[supid]', '$reff', '-$TOTAL', '$td', 'Non Stock purchase $inv[purnum] returned', '".USER_DIV."', '$stkacc'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$TOTAL'::numeric(13,2)) WHERE supid = '$inv[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Make ledge record
		suppledger($inv['supid'],$stkacc , $td, $reff, "Non Stock purchase $inv[purnum] returned", $TOTAL, "d");

		$mt=$TOTAL*-1;

		db_connect();

		$sql = "INSERT INTO suppurch (supid, purid, pdate, balance, div) VALUES ('$inv[supid]', '$inv[purnum]', '$td', '$mt', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.$sql",SELF);

		//custfCT($TOTAL, $inv['cusid'], $sndate);
	}elseif($inv['ctyp'] == 'cb'){
		$date = date("Y-m-d");

		# Record the Receipt record
		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, 
				banked, accids, amounts,  chrgvats, vats, div, accinv
			) VALUES (
				'$inv[supid]', 'deposit', '$td', '$inv[supplier]', 'Nons Stock purchase $inv[purnum] returned', '0', '$TOTAL', 
				'no', '', '$TOTAL', '$inv[vatinc]', '$VAT', '".USER_DIV."','$stkacc'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank Receipt to database.",SELF);
	}

	db_connect();

	$sql = "UPDATE \"$prd\".nons_purchases SET balance = (balance - '$TOTAL'::numeric(13,2)) WHERE purid = '$inv[purid]' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

	$sql = "UPDATE cubit.suppurch SET balance=(balance - '$TOTAL'::numeric(13,2)) WHERE purid='$inv[purid]'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice balance.");

	# write note
	$sql = "
		INSERT INTO cubit.nons_purchasesn (
			purid, purnum, supplier, supaddr, vatinc, pdate, subtot, vat, total, 
			prd, notenum, ctyp, remarks, div
		) VALUES (
			'$inv[purid]', '$inv[purnum]', '$inv[supplier]', '$inv[supaddr]', '$inv[vatinc]', '$td', '$SUBTOTAL', '$VAT', '$TOTAL', 
			'".PRD_DB."', '$noteid', '$inv[ctyp]', '$remarks', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.$sql",SELF);

	$nid = pglib_lastid ("nons_purchasesn", "id");
	$nid++;

	# write note items
	foreach($ids as $key => $id){
		db_conn($prd);
		$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND id = '$id' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql) or errDie("Unable to get data.$sql");
		$stktc = pg_fetch_array($stkdRslt);

		db_conn("cubit");

		$sql = "
			INSERT INTO nons_pur_itemsn (
				noteid, cod, des, qty, unitcost, amt, svat, 
				ddate, accid, div
			) VALUES (
				'$nid', '$stktc[cod]', '$stktc[des]', '$qtys[$key]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[svat]', 
				'$stktc[ddate]', '$stktc[accid]', '".USER_DIV."'
			)";
		//$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
	}

# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* -- Format the remarks boxlet -- */
	$inv["remarks"] = "<table border=1><tr><td>Remarks:<br>$inv[remarks]</td><ble>";

	$cc = "<script> CostCenter('dt', 'Credit Note', '$inv[pdate]', 'Non Stock Credit Note No.$noteid', '".($TOTAL-$VAT)."', ''); </script>";

	/* -- Final Layout -- */
	$details =  "
		$cc
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase Return</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Purchase return has been recorded.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-view.php'>View purchases</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $details;

}

# details
function bwrite($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Quantity for product number : <b>".($keys+1)."</b> is more that Qty Orderd");
			}
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	# Get Order info
	db_conn($prd);

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# Get selected supplier info
	db_connect();
	if($pur['ctyp'] == 's'){
		$supid = $pur['typeid'];
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
		if (pg_numrows ($supRslt) < 1) {
			$error = "<li class='err'> Supplier not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$sup = pg_fetch_array($supRslt);
			$pur['supplier'] = $sup['supname'];
			$pur['supaddr'] = $sup['supaddr'];

			# Get department info
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$sup[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}
			$supacc = $dept['credacc'];
		}
	}elseif($pur['ctyp'] == 'c'){
		$deptid = $pur['typeid'];
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Department not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = $dept['pca'];
		}
	}

	# Insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		if(isset($qtys)){
			# amount of stock in
			$totstkamt = array();
			$resub = 0;
			# Get subtotal
			foreach($qtys as $keys => $value){
				# Skip zeros
				if($qtys[$keys] < 1){
					continue;
				}
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
			}
			$SUBTOTAL = array_sum($amt);
			$revat = 0;
			foreach($qtys as $keys => $value){
				# Get selected stock line
				$sql = "SELECT * FROM nons_pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				# Calculate cost amount bought
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

				/* delivery charge */

					# Calculate percentage from subtotal
					$perc[$keys] = (($amt[$keys]/$SUBTOTAL) * 100);

					# Get percentage from shipping charges
					$shipc[$keys] = (($perc[$keys] / 100) * $shipchrg);

					# add delivery charges
					$amt[$keys] += $shipc[$keys];

				/* end delivery charge */

				# the subtotal + delivery charges
				$resub += $amt[$keys];

				# calculate vat
				$svat[$keys] = svat($amt[$keys], $stkd['amt'], $stkd['svat']);

				# received vat
				$revat += $svat[$keys];

				# make amount vat free
				if($pur['vatinc'] == "yes"){
					$amt[$keys] = ($amt[$keys] - $svat[$keys]);
				}

				# Update Order items
				$sql = "
					UPDATE nons_pur_items 
					SET rqty = (rqty + '$qtys[$keys]'), accid = '$stkacc[$keys]' 
					WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

				# keep records for transactions
				if(isset($totstkamt[$stkacc[$keys]])){
					$totstkamt[$stkacc[$keys]] += $amt[$keys];
				}else{
					$totstkamt[$stkacc[$keys]] = $amt[$keys];
				}

				# check if there are any outstanding items
				$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				# if none the set to received
				if(pg_numrows($stkdRslt) < 1){
					# update surch_int(received = 'y')
					$sql = "
						UPDATE nons_purchases 
						SET received = 'y', supplier = '$pur[supplier]', supaddr = '$pur[supaddr]' 
						WHERE purid = '$purid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);
				}
			}
		}

		# Update Order on the DB
		if($pur['part'] == 'y'){
			# Update Order on the DB
			$sql = "
				UPDATE nons_purchases 
				SET ctyp = '$ctyp', typeid = '$typeid', refno = '$refno', remarks = '$remarks' 
				WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}else{
			# Update Order on the DB
			$sql = "
				UPDATE nons_purchases 
				SET ctyp = '$ctyp', typeid = '$typeid', refno = '$refno', remarks = '$remarks' 
				WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}

/* Transactions */

	$refnum = getrefnum(date("d-m-Y"));

/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

/* - End Hooks - */
		$detadd = "";
		if(isset($supid)){
			$detadd = " from Supplier $sup[supname]";
		}

		$sdate = $pur['pdate'];
		$tpp=0;
		$ccamt = 0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Stock and Credit Suppliers control
			writetrans($stkacc, $supacc, date("d-m-Y"), $refnum, $wamt, "Non-Stock Purchase No. $pur[purnum] Received $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $pur[purnum] Received $detadd.", $wamt, "Cash Order");
		}

		# vat
 		$vatamt = $revat;

		# Add vat if not included
		if($pur['vatinc'] == 'no'){
			$retot = ($resub + $vatamt);
		}elseif($pur['vatinc'] == "novat") {
			$retot = ($resub);
			$vatamt = 0;
		}else{
			$retot = ($resub);
		}

		if(isset($supid)){
			# Ledger Records
			$DAte = $pur['pdate'];
			suppledger($sup['supid'], $stkacc, $DAte, $pur['purid'], "Non-Stock Purchase No. $pur[purnum] received.", $retot, 'c');
		}

		if($vatamt <> 0){
			# Debit bank and credit the account involved
			writetrans($vatacc, $supacc, date("d-m-Y"), $refnum, $vatamt, "Non-Stock Purchase VAT paid on Non-Stock Order No. $pur[purnum] $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $pur[purnum] Received $detadd.", $vatamt, "Cash Order VAT");

			# Record the payment on the statement
			db_connect();
			$sdate = $pur['pdate'];
		}

		if(isset($supid)){
			$DAte = $pur['pdate'];

			db_connect();
			# update the supplier (make balance more)
			$sql = "UPDATE suppliers SET balance = (balance + '$retot') WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$sql = "
				INSERT INTO sup_stmnt (
					supid, edate, cacc, amount, descript, ref, ex, div
				) VALUES (
					'$sup[supid]','$DAte', '$dept[credacc]', '$retot', 'Non Stock Purchase No. $pur[purnum] Received', '$refnum', '$pur[purnum]', '".USER_DIV."'
				)";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			db_connect();

			# update the supplier age analysis (make balance less)
			/* Make transaction record for age analysis */
			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$sup[supid]', '$pur[purnum]', '$DAte', '$retot', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* End Transactions */

/* Start moving if Order received */

		# Get Order info
		db_connect();

		$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li>- Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y"){
			# copy Order
			db_conn($pur['prd']);
			$sql = "
				INSERT INTO nons_purchases (
					purid, deptid, supplier, supaddr, terms, pdate, ddate, 
					shipchrg, shipping, subtot, total, balance, vatinc, vat, 
					remarks, refno, received, done, ctyp, typeid, div, purnum
				) VALUES (
					'$purid', '$pur[deptid]', '$pur[supplier]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', 
					'$pur[shipchrg]', '$pur[shipping]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', 
					'$pur[remarks]', '$pur[refno]', 'y', 'y', '$pur[ctyp]', '$pur[typeid]', '".USER_DIV."', '$pur[purnum]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);


			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert Order items
				db_conn($pur['prd']);
				$sql = "
					INSERT INTO nons_pur_items (
						purid, cod, des, qty, unitcost, amt, 
						svat, ddate, accid, div
					) VALUES (
						'$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[amt]', 
						'$stktc[svat]', '$stktc[ddate]', '$stktc[accid]', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();
			# Remove the Order from running DB
			$sql = "DELETE FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Remove those Order items from running DB
			$sql = "DELETE FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

/* End moving Order received */
	$cc = "<script> CostCenter('ct', 'Non-Stock Purchase', '$pur[pdate]', 'Non Stock Purchase No.$pur[purnum]', '".($retot-$vatamt)."', ''); </script>";

	// Final Layout
	$write = "
		$cc
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Non-Stock Order received</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Non-Stock Order receipt has been recorded.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-purchase-view.php'>View Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $write;
}

function vats($amt, $inc){
	# If vat is not included
	$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "novat") {
		$ret = ($amt);
	}else{
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}
	return $ret;
}

function svat($amt, $samt, $svat){
	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;
}

function vat($amt, $inc){
	# If vat is not included
	$VATP = TAX_VAT;
	if($inc == "no"){
		$VAT = sprint(($VATP/100) * $amt);
	}else{
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
	}
	return $VAT;
}

?>

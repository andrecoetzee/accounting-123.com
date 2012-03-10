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
if (isset($HTTP_GET_VARS["purid"])) {
	$OUTPUT = slct($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;
			case "slct":
				$OUTPUT = details($HTTP_POST_VARS);
				break;
			case "":
				$OUTPUT = details ($HTTP_POST_VARS);
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
function slct($HTTP_GET_VARS, $err = "")
{

	# Get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Invoice number.");

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

	# get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	if($pur['typeid'] > 0){
		if($pur['ctyp'] == "s"){
			$VARS['supid'] = $pur['supplier'];
		}elseif($pur['ctyp'] == "c"){
			$VARS['deptid'] = $pur['typeid'];
		}
		$VARS['ctyp'] = $pur['ctyp'];
		$VARS['purid'] = $purid;
		$VARS['accounts'] = 0x0ff;
		return details($VARS);
	}

	db_connect();

	$sql = "SELECT * FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);
	$sups = "<select name='supid'>";
	if(pg_numrows($supRslt) < 1) 
		$sups .= "<option value='-S'></option>";
	while($sup = pg_fetch_array($supRslt)){
		$sups .= "<option value='$sup[supid]'>$sup[supno] $sup[supname]</option>";
	}
	$sups .= "</select>";

	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name='deptid'>";
	if(pg_numrows($deptRslt) < 1)
		$depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	//Option removed
	//<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select when tranferring goods between Departments or Stores")."><td colspan=2><input type=radio name=ctyp value='c' checked=yes>Accounts Order</td></tr>

	$details = "
		<center>
		<h3>Non-Stock Order received</h3>
		<h4>Supplier Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slct'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'> Order Details </th>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when purchasing non stock goods from your suppliers").">
				<td><input type='radio' name='ctyp' value='s' checked='yes'> Select Supplier</td>
				<td>$sups</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("Select when the Order of non stock goods is a cash Order").">
				<td><input type='radio' name='ctyp' value='c'>Cash Order</td>
				<td>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><input type='radio' name='ctyp' value='p'>Petty Cash Order</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='center'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		</form>";
	return $details;

}


# Details
function details($HTTP_POST_VARS, $error="")
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($supid, "num", 1, 20, "Invalid supplier account number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slct($HTTP_POST_VARS, $error);
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);


	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}
	$supacc = "<select name='supacc'>";
	while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
		$supacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$supacc .= "</select>";

	$pur['grpid'] += 0;

	if($pur['grpid'] > 0) {
		db_conn('cubit');
		$Sl = "SELECT * FROM assetgrp WHERE grpid='$pur[grpid]'";
		$Ro = db_exec($Sl) or errDie("Unable to get data.");

		$gd = pg_fetch_array($Ro);

		$def = $gd['costacc'];
	} else {
		$def = 0;
	}

	$stkacc = "<select name='stkacc[]'>";

	$useaccdrop = getCSetting ("USE_NON_PURCHASES_ACCOUNTS");
	if (isset ($useaccdrop) AND $useaccdrop == "yes"){
		db_connect ();
		$acc_sql = "SELECT * FROM non_purchases_account_list ORDER BY accname";
		$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
		if (pg_numrows ($run_acc) > 0){
			while($acc = pg_fetch_array($run_acc)){
				if ($acc['accid'] == $def) {
					$stkacc .= "<option value='$acc[accid]' selected>$acc[accname]</option>";
				}else {
					$stkacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
			}
			$stkacc .= "</select>";
		}
	}else {
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;
			if($def == $acc['accid']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$stkacc .= "<option value='$acc[accid]' $sel>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
		$stkacc .= "</select>";
	}

	# Get selected supplier info
	db_connect();

	$hide = "";
	if(isset($ctyp) && $ctyp == 's'){
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
			$supacc = $sup['supno'];
			$hide = "<input type='hidden' name='supid' value='$supid'><input type='hidden' name='ctyp' value='$ctyp'>";
		}
	}elseif(isset($ctyp) && $ctyp == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Department not Found.</li>";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[deptname] - Cash on Hand";
			$hide = "<input type='hidden' name='deptid' value='$deptid'><input type='hidden' name='ctyp' value='$ctyp'>";
		}
	}elseif(isset($ctyp) && $ctyp == 'cb'){
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
			$hide = "<input type='hidden' name='bankid' value='$pur[supid]'><input type='hidden' name='ctyp' value='$ctyp'>";
		}
	} elseif(isset($ctyp) && $ctyp == 'p'){
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
		$hide = "<input type='hidden' name='supacc' value='$cashacc'><input type='hidden' name='ctyp' value='$ctyp'>";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RECEIVED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>ITEM ACCOUNT</th>
			<tr>";

	# get selected stock in this Order
	db_connect();

	$sql = "SELECT *, (qty - rqty) as qty FROM nons_pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		db_connect();

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		if(isset($accounts)){
			core_connect();
			# Get selected stock line
			$stkd['accid'] += 0;
			$sql = "SELECT accname,accid,topacc,accnum FROM accounts WHERE accid = '$stkd[accid]' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			if(pg_num_rows($accRslt)>1) {
				$acc = pg_fetch_array($accRslt);
				$stkacc = "<input type='hidden' name='stkacc[]' value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]";
			}
		}

		# put in product
		$products .= "
			<input type='hidden' name='ids[]' value='$stkd[id]'>
			<input type='hidden' size='4' name='cod[]' value='$stkd[cod]'>
			<input type='hidden' name='qts[]' value='$stkd[qty]'>
			<input type='hidden' size='4' name='unitcost[]' value='$stkd[unitcost]'>
			<tr bgcolor='".bgcolorg()."'>
				<td>$stkd[cod]</td>
				<td>$stkd[des]</td>
				<td><input type='text' size='5' name='qtys[]' value='$stkd[qty]'></td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>".CUR." $stkd[amt]</td>
				<td ".ass("Select the account you wish to Debit").">$stkacc</td>
			</tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
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
		<h3>Non-Stock Order received</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			$hide
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
						<tr bgcolor='".bgcolorg()."' ".ass("Select the account you wish to Credit").">
							<td>Account <input align='right' type='button' onClick=\"window.open('core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
							<td>$supacc</td>
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
							<td>Supplier Invoice Number</td>
							<td valign='center'><input type='text' name='supinv' size='10' value='$pur[supinv]'></td>
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
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
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



# details
function write($HTTP_POST_VARS)
{

	#get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	if(!isset($supid) && !isset($deptid)){
		if(!isset($supacc)) {
			$supacc = 0;
		}
		$v->isOk ($supacc, "num", 1, 10, "Invalid Supplier Account number.");
	}
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($supinv, "string",0,50,"Invalid supplier inv num.");

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
			$v->isOk ($stkacc[$keys], "num", 1, 10, "Invalid Item Account number : <b>".($keys+1)."</b>");
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
		return details($HTTP_POST_VARS, $err);
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pdate) >= strtotime($blocked_date_from) AND strtotime($pdate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$sid = $pur['supplier'];

	$pur['pdate'] = $p_year."-".$p_month."-".$p_day;

	$td = $pur['pdate'];

	# Get selected supplier info
	db_connect();

	if(isset($supid)){
		$typeid = $supid;
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
	}elseif(isset($deptid)){
		$typeid = $deptid;
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
	}elseif(isset($bankid)) {
		$bankid += 0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$pur[supid]'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.</li>";
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

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$refnum = getrefnum();
/*refnum*/

	# Insert Order to DB
	db_connect();

# begin updating

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

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri = db_exec($Sl) or errDie("Unable to get data.");

				$vd = pg_fetch_array($Ri);

				vatr($vd['id'],$pur['pdate'],"INPUT",$vd['code'],$refnum,"VAT for Non-Stock Purchase No. $pur[purnum]",-$amt[$keys],-$svat[$keys]);

				# received vat
				$revat += $svat[$keys];

				# make amount vat free
				if($pur['vatinc'] == "yes"){
					$amt[$keys] = ($amt[$keys] - $svat[$keys]);
				}

				# Update Order items
				$sql = "UPDATE nons_pur_items SET rqty = (rqty + '$qtys[$keys]'), accid = '$stkacc[$keys]' WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
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
					$sql = "UPDATE nons_purchases SET received = 'y', supplier = '$pur[supplier]', supaddr = '$pur[supaddr]' WHERE purid = '$purid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);
				}
			}
		}

		if(!isset($ctyp)) {
			$ctyp = "ac";
		}

		# Update Order on the DB
		if($pur['part'] == 'y'){
			# Update Order on the DB
			$sql = "UPDATE nons_purchases SET supinv = '$supinv', ctyp = '$ctyp', typeid = '$typeid', refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}else{
			# Update Order on the DB
			$sql = "UPDATE nons_purchases SET supinv = '$supinv', ctyp = '$ctyp', typeid = '2', refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}

/* Transactions */


/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

/* - End Hooks - */
		$detadd = "";
		if(isset($supid)){
			$detadd = " from Supplier $sup[supname]";
		}

		$sdate = $pur['pdate'];
		$tpp = 0;
		$ccamt = 0;
		# record transaction  from data

		if(isset($BA)) {
			$supacc = $BA;
		}

		foreach($totstkamt as $stkacc => $wamt){
			writetrans($stkacc, $supacc, $td, $refnum, $wamt, "Non-Stock Purchase No. $pur[purnum] Received $detadd.");
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
			db_connect();
			# update the supplier (make balance more)
			$sql = "UPDATE suppliers SET balance = (balance + '$retot') WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		}

		if(isset($supid)){
			# Ledger Records
			$DAte = $pur['pdate'];
			suppledger($sup['supid'], $stkacc, $DAte, $pur['purid'], "Non-Stock Purchase No. $pur[purnum] received.", $retot, 'c');
		}

		if($vatamt <> 0){
			# Debit bank and credit the account involved
			writetrans($vatacc, $supacc, $td, $refnum, $vatamt, "Non-Stock Purchase VAT paid on Non-Stock Order No. $pur[purnum] $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $pur[purnum] Received $detadd.", $vatamt, "Cash Order VAT");

			# Record the payment on the statement
			db_connect();
			$sdate = $pur['pdate'];
		}

		if(isset($bankid)) {

			db_connect();
			$sql = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, descript, 
					cheqnum, amount, vat, chrgvat, banked, accinv, div
				) VALUES (
					'$bankid', 'withdrawal', '$sdate', '$pur[supplier]', 'Non-Stock Purchase No. $pur[purnum] received', 
					'0', '$retot', '$vatamt', '$pur[vatinc]', 'no', '$stkacc', '".USER_DIV."'
				)";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		}

		if(isset($supid)){
			db_connect();

			$DAte = $pur['pdate'];

			$sql = "
				INSERT INTO sup_stmnt (
					supid, edate, cacc, amount, 
					descript, ref, ex, div
				) VALUES (
					'$sup[supid]', '$DAte', '$stkacc', '$retot', 
					'Non Stock Purchase No. $pur[purnum] Received', '$refnum', '$pur[purnum]','".USER_DIV."'
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

	$sid += 0;

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	if($pur['received'] == "y"){

		if(isset($bankid)) {
			$sid = $bankid;
			$sid += 0;
		}
		# copy Order
		db_conn($pur['prd']);
		$sql = "
			INSERT INTO nons_purchases (
				purid, deptid, supplier, supaddr, terms, pdate, ddate, 
				shipchrg, shipping, subtot, total, balance, vatinc, vat, 
				remarks, refno, received, done, ctyp, typeid, div, purnum, 
				supid, mpurid, is_asset, supinv
			) VALUES (
				'$purid', '$pur[deptid]', '$pur[supplier]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', 
				'$pur[shipchrg]', '$pur[shipping]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', 
				'$pur[remarks]', '$pur[refno]', 'y', 'y', '$pur[ctyp]', '$pur[typeid]', '".USER_DIV."', '$pur[purnum]', 
				'$sid', '$supacc', '$pur[is_asset]', '$pur[supinv]'
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
					svat, ddate, accid, div, vatcode
				) VALUES (
					'$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[amt]', 
					'$stktc[svat]', '$stktc[ddate]', '$stktc[accid]', '".USER_DIV."', '$stktc[vatcode]'
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


function vats($amt, $inc)
{

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


function svat($amt, $samt, $svat)
{

	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;

}


function vat($amt, $inc)
{

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

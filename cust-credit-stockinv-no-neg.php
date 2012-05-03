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
require_lib("customers");

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$OUTPUT = details($_GET);
} else if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "update":
				$OUTPUT = write($_POST);
				break;
			case "details":
			default:
				if(isset($_GET["ctyp"]) && $_GET["ctyp"] == 'int')
					header("Location: intinvoice-new.php?deptid=$_POST[deptid]&letters=$_POST[letters]");
				$OUTPUT = details($_POST);
				break;
		}
	} else {
		$OUTPUT = details($_POST);
	}
}

# get templete
require("template.php");




# Default view
function view()
{

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br /><br />
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Invoice</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='bg-even'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer type</td>
				<td valign='center'>
					<input type='radio' name='ctyp' value='loc' checked='yes'> Local |
					<input type='radio' name='ctyp' value='int'> International
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}


# Default view
function view_err($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if(isset($deptid) && $dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Invoice</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' value='$letters' maxlength='5'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer type</td>
				<td valign='center'><input type='radio' size='7' name='ctyp' value='loc' checked='yes'> Local | <input type='radio' size='7' name='ctyp' value='int'> International</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}



function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
//	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;
	$branch = 0;
	$del_addr = "";
	$bankid = cust_bank_id($cusnum);

	//lock(1);

	$fcid = getDef_fcid();

	$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
	if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
		$trans_date_value = getCSetting ("TRANSACTION_DATE");
		$date_arr = explode ("-", $trans_date_value);
		$date_year = $date_arr[0];
		$date_month = $date_arr[1];
		$date_day = $date_arr[2];
	}else {
		$date_year = date("Y");
		$date_month = date("m");
		$date_day = date("d");
	}
	$odate = "$date_year-$date_month-$date_day";

	# Insert invoice to DB
	$sql = "
		INSERT INTO invoices (
			deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, 
			delchrg, subtot, vat, total, balance, comm, username, printed, done, prd, 
			branch, fcid, del_addr, bankid, div
		) VALUES (
			'$deptid', '$cusnum', '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', 
			'$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '".PRD_DB."', 
			'$branch', '$fcid', '$del_addr', '$bankid', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);
	return lastinvid();

}



# Details
function details($_POST, $error="")
{

	# Get vars
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}

	if (isset($letter)) {
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}
	if (isset($deptid)) {
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	}
	if (isset($sel_frm)) {
		$v->isOk($sel_frm, "string", 6, 6, "Invalid select from selection.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$error="";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $error.view_err($_POST);
	}

	if(isset($deptid) && isset($letters)){

		db_connect();

		if ($deptid == "0"){
			$searchdept = "";
		}else {
			$searchdept = "deptid = '$deptid' AND ";
		}

		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE $searchdept location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$ajax_err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
			//return view_err($_POST, $err);
		}
	}

	if (!isset($deptid)) {
		$deptid = 2;
	} else if (isset($invid)) {
		db_conn("cubit");
		$sql = "UPDATE invoices SET deptid='$deptid' WHERE invid='$invid' AND deptid<>'$deptid'";
		db_exec($sql) or errDie("Error updating invoice department.");
	}

	if (!isset($invid)) {
		$invid = create_dummy($deptid);
	}

	if (!isset($stkerr)) {
		$stkerr = "0,0";
	}

	if(!isset($done)){
		$done = "";
	}

	if(!isset($cust_del_addr))
		$cust_del_addr = "";

	if (!isset($sel_frm)) {
		$sel_frm = "stkcod";
	}

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if(!isset($branch)){
		$branch = $inv['branch'];
	}

	# Check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected Customer info
	if (isset($letters)) {
		db_connect();
		$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND location != 'int' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		if (pg_numrows ($custRslt) < 1) {

			db_connect();

			if ($inv['deptid'] == 0){
				$searchdept = "";
			}else {
				$searchdept = "deptid = '$inv[deptid]' AND ";
			}

			# Query server for customer info
			$sql = "SELECT cusnum,cusname,surname FROM customers WHERE $searchdept location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
			if (pg_numrows ($custRslt) < 1) {
				$ajax_err = "<li class=err>No customer names starting with <b>$letters</b> in database.</li>";
				//return view_err($_POST, $err);
			}else{
				$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				$customers .= "<option value='-S' selected>Select Customer</option>";
				while($cust = pg_fetch_array($custRslt)){
					$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
				}
				$customers .= "</select>";
			}
			# Take care of the unset vars
			$cust['addr1'] = "";
			$cust['cusnum'] = "";
			$cust['vatnum'] = "";
			$cust['accno'] = "";
			$cust_del_addr = "";
			$branchdrop = "<input type='hidden' name='branch' value='0'>";
		}else{
			$cust = pg_fetch_array($custRslt);

                      #override address
			if($branch != 0){
				$get_addr = "SELECT branch_descrip FROM customer_branches WHERE id = '$branch' AND div = '".USER_DIV."' LIMIT 1";
				$run_addr = db_exec($get_addr);
				if(pg_numrows($run_addr) < 1){
					#address missing ... do nothing
				}else {
					$arr = pg_fetch_array($run_addr);
					$cust['addr1'] = $arr['branch_descrip'];

					if($inv['del_addr'] != $arr['branch_descrip']){
						$update_addr = "UPDATE invoices SET del_addr  = '$arr[branch_descrip]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
					}
				}
			}

			#if the del_addr of invoice if empty, add the customer's entry and display button so user can edit anyway
			if(strlen($inv['del_addr']) < 1){
				$cust_del_addr = $cust['del_addr1'];

				#we need to write this to the db ... now
				$update_addr = "UPDATE invoices SET del_addr  = '$cust_del_addr' WHERE invid = '$invid' AND div = '".USER_DIV."'";
//				$run_update = db_exec($update_addr) or errDie("Unable to update invoice information");
			}else {
				$cust_del_addr = $inv['del_addr'];
			}

			$sql = "SELECT cusnum, cusname, surname FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
			$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
			# Moarn if customer account has been blocked
			if($cust['blocked'] == 'yes'){
				$error .= "<li class='err'>Error : Selected customer account has been blocked.</li>";
			}

			// $customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
			$cusnum = $cust['cusnum'];

			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				// $customers .= "<option value='-S' selected>Select Customer</option>";
				while($cus = pg_fetch_array($cusRslt)){
					$sel = "";
					if($cust['cusnum'] == $cus['cusnum']){
						$sel = "selected";
					}
					$customers .= "<option value='$cus[cusnum]' $sel>$cus[cusname] $cus[surname]</option>";
				}
			$customers .= "</select>";

			#get list of branches
			$get_branches = "SELECT * FROM customer_branches WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
			$run_branches = db_exec($get_branches);
			if(pg_numrows($run_branches) < 1){
				$branchdrop = "<input type='hidden' name=branch value='0'>No Branches For This Customer";
			}else {
				$branchdrop = "<select name='branch' onChange='javascript:document.form.submit();'>";
				$branchdrop .= "<option value='0'>Head Office</option>";
				while($barr = pg_fetch_array($run_branches)){
					$sel2 = "";
					if($barr['id'] == $branch){
						$sel2 = "selected";
					}
					$branchdrop .= "<option $sel2 value='$barr[id]'>$barr[branch_name]</option>";
				}
				$branchdrop .= "</select>";
			}

/*			#override address
			if($branch != 0){
				$get_addr = "SELECT branch_descrip FROM customer_branches WHERE id = '$branch' AND div = '".USER_DIV."' LIMIT 1";
				$run_addr = db_exec($get_addr);
				if(pg_numrows($run_addr) < 1){
					#address missing ... do nothing
				}else {
					$arr = pg_fetch_array($run_addr);
					$cust['addr1'] = $arr['branch_descrip'];
				}
			}*/
		}
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
//old
//$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
//fixes broken store function
	$whs = "<select name='whidss[]'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			if (!user_in_store_team($wh["whid"], USER_ID)) continue;
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	// Is the customer linked to a sales rep
	if (!empty($cust["sales_rep"])) {
		db_conn("exten");
		$sql = "SELECT salesp FROM salespeople WHERE salespid='$cust[sales_rep]'";
		$sr_rslt = db_exec($sql) or errDie("Unable to retrieve sales rep from Cubit.");

		$salespname = pg_fetch_result($sr_rslt, 0);
		$salesps = "<input type='hidden' name='salespn' value='$salespname'><b>[$salespname]</b>";
	} else {
		db_conn("exten");
		$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
		$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
		if (pg_numrows ($salespRslt) < 1) {
			return "<li class='err'> There are no Sales People found in Cubit.</li>";
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
	}

	# Days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	$sql = "SELECT value FROM cubit.settings WHERE constant='VAT_INC'";

// 2007-05-09 New VAT setting in admin menu
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


	# Format date
	list($inv_date_year, $inv_date_month, $inv_date_day) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts."' width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>VAT CODE</th>
				<th>SERIAL NO.</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	$ai = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account'] += 0;
		if($stkd['account'] != 0) {
			# Keep track of selected stock amounts
			$stkd["amt"] = sprint($stkd["amt"]);
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');

			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accounts[$ai]'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if($ad['accid'] == $stkd['account']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				if(isb($ad['accid'])) {
					continue;
				}
				$Accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}
			$Accounts .= "</select>";

			$sernos = "
				<input type='hidden' name='sernos[$ai]' value='$stkd[serno]'>
				<input type='hidden' name='sernos_ss[$ai]' value='$stkd[serno]'>";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[$ai]' value='$stkd[qty]'>";

			$viewcost = "<input type='text' size='8' name='unitcost[$ai]' value='$stkd[unitcost]'>";

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[$ai]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($stkd['vatcode'] == $vd['id']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			# Put in product
			$products .= "
				<input type='hidden' name='stkids[$ai]' value='$stkd[stkid]'>
				<input type='hidden' name='discp[$ai]' value='$stkd[discp]'>
				<input type='hidden' name='whids[$ai]' value='$stkd[whid]'>
				<input type='hidden' name='amt[$ai]' value='$stkd[amt]'>
				<input type='hidden' name='disc[$ai]' value='$stkd[disc]'>
				<tr class='".bg_class()."'>
					<td colspan='2'>$Accounts</td>
					<td>$Vatcodes</td>
					<td></td>
					<td><input type='text' size='20' name='descriptions[$ai]' value='$stkd[description]'> $sernos</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td></td>
					<td nowrap> ".CUR." $stkd[amt]</td>
					<td><input type='checkbox' name='remprod[$ai]' value='$key'></td>
				</tr>";
			$key++;
			++$ai;
		}else{
			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];

			$i++;

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);


			# Get selected stock in this warehouse
			db_connect();

			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# Serial number
			if($stk['serd'] == 'yes' && ($inv['serd'] == 'n' || $stkd["serno"] == "")){
				$sers = ext_getavserials($stkd['stkid']);
				$sernos = "<select name='sernos[$ai]'>";
				foreach($sers as $skey => $ser){
					$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
				}
				$sernos .= "</select>
							<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
			}else{
				$sernos = "
					<input type='hidden' name='sernos_ss[]' value='$stkd[ss]' />
					<input type='hidden' name='sernos[$ai]' value='$stkd[serno]'>$stkd[ss]";
			}

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[$ai]' value='$stkd[qty]'>";
			if($stk['serd'] == 'yes'){
				$qtyin = "<input type='hidden' size='3' name='qtys[$ai]' value='$stkd[qty]'>$stkd[qty]";
			}

			# check permissions
			if(perm("invoice-unitcost-edit.php")){
				$viewcost = "<input type='text' size='8' name='unitcost[$ai]' value='$stkd[unitcost]'>";
			}else{
				$viewcost = "<input type='hidden' size='8' name='unitcost[$ai]' value='$stkd[unitcost]'>$stkd[unitcost]";
			}

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[$ai]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($stkd['vatcode'] == $vd['id']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			$products .= "
				<input type='hidden' name='accounts[$ai]' value='0'>
				<input type='hidden' name='descriptions[$ai]' value=''>
				<input type='hidden' name='whids[$ai]' value='$stkd[whid]'>
				<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
				<input type='hidden' name='amt[$ai]' value='$stkd[amt]'>
				<tr class='".bg_class()."'>
					<td>$wh[whname]</td>
					<td><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$Vatcodes</td>
					<td>$sernos</td>
					<td>".extlib_rstr($stk['stkdes'], 30)."</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td><input type='text' size='4' name='disc[$ai]' value='$stkd[disc]'> OR <input type='text' size='4' name='discp[$ai]' value='$stkd[discp]' maxlength='5'>%</td>
					<td nowrap>".CUR." $stkd[amt]</td>
					<td><input type='checkbox' name='remprod[$ai]' value='$key'></td>
				</tr>";
			$key++;
			++$ai;
		}
	}

	# Look above(remprod keys)
	$keyy = $key;

	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}else{
		$SCROLL = "yes";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S" && isset($cust['pricelist'])){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# Get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				if($stk['serd'] == 'yes'){
					$sers = ext_getavserials($stkidss[$key]);
					$sernos = "<select name='sernos[]' onChange='javascript:document.form.submit();'>";
					foreach($sers as $skey => $ser){
						$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
					}
					$sernos .= "</select>
						<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
				}else{
					$sernos = "
						<input type='hidden' name='sernos_ss[]' value=''>
						<input type='hidden' name='sernos[$ai]' value=''>";
				}

				# Get price from price list if it is set
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
							$error .= "<li class='err'>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}

				if($stk['units'] <= $stk['minlvl'] && $stk['minlvl']!=0) {
					$error.="<li class='err'>$stk[stkcod] is below minimum level, please notify stock controller.</li>";
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
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				# Input qty if not serialised
				$qtyin = "<input type='text' size='3' name='qtemp' value='$qtyss[$key]'>";
				if($stk['serd'] == 'yes'){
					$qtyin = "<input type='hidden' size='3' name='qtemp' value='$qtyss[$key]'>$qtyss[$key]";
				}

				#clean some vars
				$stk['selamt'] = sprint ($stk['selamt']);
				$amt[$key] = sprint ($amt[$key]);

				# Check permissions
				if(perm("invoice-unitcost-edit.php")){
					$viewcost = "<input type='text' size='8' name='unitcost[$ai]' value='$stk[selamt]'>";
				}else{
					$viewcost = "<input type='hidden' size='8' name='unitcost[$ai]' value='$stk[selamt]'>$stk[selamt]";
				}

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[$ai]'>
						<option value='0'>Select</option>";
				while($vd = pg_fetch_array($Ri)) {
					if($stk['vatcode'] == $vd['id']) {
						$sel = "selected";
					} else {
						$sel = "";
					}
					$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
				}
				$Vatcodes .= "</select>";

				# Put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[$ai]' value='0'>
					<input type='hidden' name='descriptions[$ai]' value=''>
					<input type='hidden' name='whids[]' value='$whid'>
					<input type='hidden' name='stkids[$ai]' value='$stk[stkid]'>
					<input type='hidden' name='amt[$ai]' value='$amt[$key]'>
					<tr class='".bg_class()."'>
						<td>$wh[whname]</td>
						<td><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$Vatcodes</td>
						<td>$sernos</td>
						<td>".extlib_rstr($stk['stkdes'], 30)."</td>
						<td>$qtyin</td>
						<td>$viewcost</td>
						<td><input type='text' size='4' name='disc[$ai]' value='$discs[$key]'> OR <input type='text' size='4' name='discp[$ai]' value='$discps[$key]' maxlength='5'>%</td>
						<td nowrap>".CUR." $amt[$key]</td>
						<td><input type='checkbox' name='remprod[$ai]' value='$keyy'></td>
					</tr>";
				$keyy++;
				++$ai;
			}elseif(isset($accountss[$key]) && $accountss[$key] != "0" && isset($cust['pricelist'])){

				db_conn('core');

				$Sl = "SELECT * FROM accounts WHERE accid='$accountss[$key]'";
				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

				if(pg_num_rows($Ri) < 1) {
					return "invalid.";
				}

				$ad = pg_fetch_array($Ri);

				# Calculate amount
				$amt[$key] = sprint($qtyss[$key] * ($unitcosts[$key]));

				# Input qty if not serialised
				$qtyin = "<input type='text' size='3' name='qtemp' value='$qtyss[$key]'>";

				# Check permissions
				$viewcost = "<input type='text' size='8' name=unitcost[$ai] value='$unitcosts[$key]'>";

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[$ai]'>
						<option value='0'>Select</option>";
				while($vd = pg_fetch_array($Ri)) {
					if($vatcodess[$key] == $vd['id']) {
						$sel = "selected";
					} else {
						$sel = "";
					}
					$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
				}
				$Vatcodes .= "</select>";

				# Put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[$ai]' value='$accountss[$key]'>
					<input type='hidden' name='whids[$ai]' value='0'>
					<input type='hidden' name='stkids[$ai]' value='0'>
					<input type='hidden' name='disc[$ai]' value='0'>
					<input type='hidden' name='discp[$ai]' value='0'>
					<input type='hidden' name='amt[$ai]' value='$amt[$key]'>
					<tr class='".bg_class()."'>
						<td colspan='2'>$ad[accname]</td>
						<td>$Vatcodes</td>
						<td></td>
						<td><input type='text' size='20' name='descriptions[$ai]' value='$descriptionss[$key]'></td>
						<td>$qtyin</td>
						<td>$viewcost</td>
						<td></td>
						<td nowrap>".CUR." $amt[$key]</td>
						<td><input type='checkbox' name=remprod[$ai] value='$keyy'></td>
					</tr>";
				$keyy++;
				++$ai;
			}else{
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				if (!isset($addnon)) {
					if (isset ($filter_store) AND $filter_store != "0"){
						# get warehouse name
						db_conn("exten");
						$sql = "SELECT whname FROM warehouses WHERE whid = '$filter_store' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);
					}

					if(isset($ria) AND $ria != "") {
						$len = strlen($ria);
						if($ria == "Show All"){
							$Wh = "";
							$ria = "";
						}else {
							$Wh = "AND (lower(stkdes) LIKE lower('%$ria%')) OR (lower(stkcod) LIKE lower('%$ria%'))";
							$ria = "";
						}
					} else {
						$Wh = "AND FALSE";
						$ria = "";
					}

					$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

					if (isset ($check_setting) AND $check_setting == "yes"){
						if (isset ($filter_class) AND $filter_class != "0"){
							$Wh .= " AND prdcls = '$filter_class'";
						}
						if (isset ($filter_cat) AND $filter_cat != "0"){
							$Wh .= " AND catid = '$filter_cat'";
						}
					}

					if (isset($filter_store) AND $filter_store != "0"){
						$Wh .= " AND whid = '$filter_store'";
					}

					# get stock on this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY $sel_frm ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");

					if (pg_numrows ($stkRslt) < 1) {
						// May 3 2007 - Don't think this is needed any more
						// as this now gets checked on each update.
						// $error .= "<li class='err'>There are no stock items in the selected warehouse.";
						continue;
					}

					if (pg_numrows ($stkRslt) == 1) {
						$ex = "selected";
					} else {$ex = "";}

					if ($sel_frm == "stkcod") {
						$cods = "<select name='stkidss[$ai]' onChange='javascript:document.form.submit();'>";
						$cods .= "<option value='-S' disabled selected>Select Number</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							// Check if this stock item has been blocked
							if (stock_is_blocked($stk["stkid"])) {
								continue;
							}

							$units = sprint3($stk["units"] - $stk_alloc["alloc"]);
							if ($units <= 0) continue;

							$cods .= "<option value='$stk[stkid]'>$stk[stkcod] ($units)</option>";
						}
						$cods .= "</select> ";

						$descs = "";
					} else {
						$descs = "<select style='width:250px' name='stkidss[$ai]' onChange='javascript:document.form.submit();'>";
						$descs .= "<option value='-S' disabled selected>Select Description</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							// Check if this stock item has been blocked
							if (stock_is_blocked($stk["stkid"])) {
								continue;
							}

							$units = $stk['units'] - $stk['alloc'];
							if ($units <= 0) continue;
							$descs .= "<option value='$stk[stkid]'>$stk[stkdes] ($units)</option>";
						}
						$descs .= "</select> ";

						$cods = "";
					}

					db_conn('cubit');

					$Sl = "SELECT * FROM vatcodes ORDER BY code";
					$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

					$Vatcodes = "
						<select name='vatcodess[$ai]'>
							<option value='0'>Select</option>";
					while($vd = pg_fetch_array($Ri)) {
						if($vd['del'] == "Yes") {
							$sel = "selected";
						} else {
							$sel = "";
						}
						$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
					}
					$Vatcodes .= "</select>";

					# put in drop down and warehouse
					$products .= "
						<input type='hidden' name='accountss[$ai]' value='0'>
						<input type='hidden' name='descriptionss[$ai]' value=''>
						<input type='hidden' name='whidss[$ai]' value='$filter_store'>
						<input type='hidden' name='vatcodess' value='0'>
						<input type='hidden' size='3' name='qtyss[$ai]' value='1'>
						<input type='hidden' name='amts[$ai]' value='0.00'>
						<tr class='".bg_class()."'>
							<td></td>
							<td>$cods</td>
							<td></td>
							<td> </td>
							<td>$descs</td>
							<td>1</td>
							<td> </td>
							<td><input type='text' size='4' name=discs[$ai] value='0'> OR <input type='text' size='4' name=discps[$ai] value='0' maxlength='5'>%</td>
							<td nowrap>".CUR." 0.00</td>
							<td></td>
						</tr>";
					++$ai;
				} else{

					db_conn('core');

					$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
					$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

					$Accounts = "
						<select name='accountss[$ai]' onChange='javascript:document.form.submit();'>
							<option value='0'>Select Account</option>";
					while($ad = pg_fetch_array($Ri)) {
						if(isb($ad['accid'])) {
							continue;
						}
						$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
					}
					$Accounts .= "</select>";

					db_conn('cubit');

					$Sl = "SELECT * FROM vatcodes ORDER BY code";
					$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

					$Vatcodes = "
						<select name='vatcodess[$ai]'>
							<option value='0'>Select</option>";
					while($vd = pg_fetch_array($Ri)) {
						if($vd['del'] == "Yes") {
							$sel = "selected";
						} else {
							$sel = "";
						}
						$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
					}
					$Vatcodes .= "</select>";

					$products .= "
						<input type='hidden' name='whidss[$ai]' value='1'>
						<inpu type='hidden' name='stkidss[$ai]' value=''>
						<input type='hidden' name='discs[$ai]' value='0'>
						<input type='hidden' name='discps[$ai]' value='0'>
						<tr class='".bg_class()."'>
							<td colspan='2'>$Accounts</td>
							<td>$Vatcodes</td>
							<td></td>
							<td><input type='text' size='20' name='descriptionss[$ai]'></td>
							<td><input type='text' size='3' name='qtyss[$ai]' value='1'></td>
							<td><input type='text' name='unitcosts[$ai]' size='7'></td>
							<td></td>
							<td nowrap>".CUR." 0.00</td>
							<td></td>
						</tr>";
					++$ai;
				}
			}
		}
	}else{
		if(!isset($addnon)){

			if (isset ($filter_store) AND $filter_store != "0"){
				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$filter_store' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
			}

			if(isset($ria) and $ria!="") {
				$len = strlen($ria);
				if($ria == "Show All"){
					$Wh = "";
					$ria = "";
				}else {
					$Wh = "AND (lower(substr(stkdes,1,'$len'))=lower('$ria') OR lower(substr(stkcod,1,'$len'))=lower('$ria'))";
					$ria = "";
				}
			} else {
				$Wh = "AND FALSE";
				$ria = "";
			}

			$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

			if (isset ($check_setting) AND $check_setting == "yes"){
				if (isset ($filter_class) AND $filter_class != "0"){
					$Wh .= " AND prdcls = '$filter_class'";
				}
				if (isset ($filter_cat) AND $filter_cat != "0"){
					$Wh .= " AND catid = '$filter_cat'";
				}
			}

			if (isset($filter_store) AND $filter_store != "0"){
				$Wh .= " AND whid = '$filter_store'";
			}

			# get stock on this warehouse
			db_connect();

			$sql = "SELECT * FROM stock WHERE blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY $sel_frm ASC";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				if(!isset($err)){$err="";}
				$err .= "<li>There are no stock items in the selected store.";
				//ontinue;
			}
			if ($sel_frm == "stkcod") {
				$cods = "<select name='stkidss[$ai]' onChange='javascript:document.form.submit();'>";
				$cods .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){

					// Check if this stock item has been blocked
					if (stock_is_blocked($stk["stkid"])) {
						continue;
					}

					$units = sprint3($stk['units'] - $stk['alloc']);
					if ($units <= 0) continue;

					$cods .= "<option value='$stk[stkid]'>$stk[stkcod] ($units)</option>";
				}
				$cods .= "</select> ";

				$descs = "";
			} else {
				$descs = "<select style='width:250px' name='stkidss[$ai]' onChange='javascript:document.form.submit();'>";
				$descs .= "<option value='-S' disabled selected>Select Description</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					// Check if this stock item has been blocked
					if (stock_is_blocked($stk["stkid"])) {
						continue;
					}

					$units = $stk['units'] - $stk['alloc'];
					if ($units <= 0) continue;

					$descs .= "<option value='$stk[stkid]'>$stk[stkdes] ($units)</option>";
				}
				$descs .= "</select> ";

				$cods = "";
			}

			$products .= "
				<input type='hidden' name='accountss[$ai]' value='0'>
				<input type='hidden' name='descriptionss[$ai]' value=''>
				<input type='hidden' name='vatcodess[$ai]' value=''>
				<input type='hidden' name='whidss[$ai]' value='$filter_store'>
				<input type='hidden' size='3' name='qtyss[$ai]' value='1'>
				<tr class='".bg_class()."'>
					<td></td>
					<td>$cods</td>
					<td> </td>
					<td></td>
					<td>$descs</td>
					<td>1</td>
					<td> </td>
					<td><input type='text' size='4' name=discs[$ai] value='0'> OR <input type='text' size='4' name=discps[$ai] value='0' maxlength='5'>%</td>
					<td nowrap>".CUR." 0.00</td>
					<td></td>
				</tr>";
			++$ai;
		}elseif(isset($addnon)){

			db_conn('core');

			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accountss[$ai]'  onChange='javascript:document.form.submit();'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				$Accounts .= "<option value=$ad[accid]>$ad[accname]</option>";
			}
			$Accounts .= "</select>";

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodess[$ai]'>
					<option value='0'>Select</option>";
			while($vd = pg_fetch_array($Ri)) {
				if($vd['del'] == "Yes") {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

			$products .= "
				<input type='hidden' name=whidss[$ai] value='1'>
				<inpu type='hidden' name='stkidss[$ai]' value=''>
				<input type='hidden' name='discs[$ai]' value='0'>
				<input type='hidden' name='discps[$ai]' value='0' >
				<tr class='".bg_class()."'>
					<td colspan='2'>$Accounts</td>
					<td>$Vatcodes</td>
					<td></td>
					<td><input type='text' size='20' name='descriptionss[$ai]'></td>
					<td><input type='text' size='3' name='qtyss[$ai]' value='1'></td>
					<td><input type='text' name='unitcosts[$ai]' size='7'></td>
					<td></td>
					<td nowrap>".CUR." 0.00</td>
					<td></td>
				</tr>";
			++$ai;
		}
	}

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = sprint(($inv['traddisc']/100) * ($inv['total']));
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
	if(isset($cusnum) && $cusnum != "-S" && is_numeric($cusnum)){

		db_connect ();

		#check for any unpaid inv before term limit
		$check_date = date("Y-m-d",mktime(0,0,0,date("m"),date("d")-$cust['credterm'],date("Y")));
		$get_check2 = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cusnum' AND date < '$check_date'";
		$run_check2 = db_exec($get_check2) or errDie ("Unable to check credit term information.");
		if(pg_numrows($run_check2) < 1){
			#no entries found ...
		}else {
			$sum_amount = sprint (pg_fetch_result ($run_check2,0,0));
			if ($sum_amount > sprint (0)){
				$error .= "<li class='err'>Warning : Customers Credit Term of <b>$cust[credterm] days</b> has been exceeded.";
				$get_check = "SELECT value FROM set WHERE label = 'CUST_INV_WARN' LIMIT 1";
				$run_check = db_exec($get_check) or errDie("Unable to get credit limit response setting");
				if(pg_numrows($run_check) < 1){
					#no setting ? do nothing ....
				}else {
					$sarr = pg_fetch_array($run_check);
					if($sarr['value'] == "block"){
						#block account ...
						$done = "";
					}
				}
			}
		}

		#check againg credit limit
		if(($TOTAL + $cust['balance']) > $cust['credlimit']){
			$error .= "<li class='err'>Warning : Customers Credit limit of <b>".CUR." ".sprint($cust["credlimit"])."</b> has been exceeded.";

			#limit reached ... check for warn/block
			db_conn("cubit");
			$get_check = "SELECT value FROM set WHERE label = 'CUST_INV_WARN' LIMIT 1";
			$run_check = db_exec($get_check) or errDie("Unable to get credit limit response setting");
			if(pg_numrows($run_check) < 1){
				#no setting ? do nothing ....
			}else {
				$sarr = pg_fetch_array($run_check);
				if($sarr['value'] == "block"){
					#block account ...
					$done = "";
				}
			}

			# Check permissions
			if(!perm("invoice-limit-override.php")){
				$done = "";
			}
		}
		$avcred = ($cust['credlimit'] - $cust['balance']);
	}else{
		$avcred = "0.00";
	}
/*--- Start checks --- */

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sc = "checked";
	} else {
		$sc = "";
	}

	$sales = "
		<td>
			<table ".TMPL_tblDflts.">
				<tr>
					<td>$salesps</td>
					<td>Print</td>
					<td><input type='checkbox' name='printsales' $sc></td>
				</tr>
			</table>
		</td>";

	$inv['delvat'] += 0;

	if($inv['delvat'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$inv['delvat'] = $vd['id'];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $inv['delvat']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$chp = "checked";
	} else {
		$chp = "";
	}

	if (empty($inv["comm"])) {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$cmntRslt = db_exec($sql) or errDie("Unable to retrieve the default comment from Cubit.");
		$comm = base64_decode(pg_fetch_result($cmntRslt, 0));
	} else {
		$comm = $inv["comm"];
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Which display method was selected
	if (isset($sel_frm) && $sel_frm == "stkdes") {
		$sel_frm_cod = "";
		$sel_frm_des = "checked";
	} else {
		$sel_frm_cod = "checked";
		$sel_frm_des = "";
	}

	// Delivery Date
	if (!empty($inv["deldate"])) {
		$deldate = explode("-", $inv["deldate"]);
	} else {
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$deldate[0] = $date_arr[0];
			$deldate[1] = $date_arr[1];
			$deldate[2] = $date_arr[2];
		}else {
			$deldate[0] = date("Y");
			$deldate[1] = date("m");
			$deldate[2] = date("d");
		}
	}

	// Retrieve VAT Setting
	$sql = "SELECT value FROM cubit.settings WHERE constant='VAT_INC'";
	$vatinc_rslt = db_exec($sql) or errDie("Unable to retrieve vat setting.");
	$vatinc = pg_fetch_result($vatinc_rslt, 0);

	if ($vatinc != "yes" && $vatinc != "no") {
		$vatinc = "no";
	}

	if ($vatinc == 'yes') {
		$chrgvat = "inc";
	} else {
		$chrgvat = "exc";
	}

/* -- Final Layout --  onkeyup='javasript:predict()'   Exempt From Vat<input type=radio size=7 name=chrgvat value='nov' $chno>       */
	$details_begin = "
		<center>
		<h3>New Invoice</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='SCROLL' value='yes'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='stkerr' value='$stkerr'>
			<input type='hidden' name='chrgvat' value='$chrgvat' />
		<table ".TMPL_tblDflts." width='95%'>
		 	<tr>
		 		<td valign='top' width='50%'>
		 			<div id='cust_selection'>";

	if (empty($ajax_err) && (isset($cusnum) || AJAX)) {
		if (isset($cusnum)) {
			$OTS_OPT = onthespot_encode(
				SELF,
				"cust_selection",
				"deptid=$inv[deptid]&letters=$letters&cusnum=$cusnum&invid=$invid"
			);
			//	<a href='javascript: popupSized(\"cust-edit.php?cusnum=$cusnum&onthespot=$OTS_OPT\", \"edit_cust\", 700, 630);'>
			$custedit = "<td nowrap><a href='javascript: popupSized(\"customers-new.php?cusnum=$cusnum&onthespot=$OTS_OPT\", \"edit_cust\", 700, 630);'>Edit Customer Details</a></td>";
		} else {
			$custedit = "";
		}

		$optional_filter_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

		if (isset ($optional_filter_setting) AND $optional_filter_setting == "yes"){

			db_connect ();

			$catsql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
			$catRslt = db_exec($catsql);
			if(pg_numrows($catRslt) < 1){
				$cat_drop = "<input type='hidden' name='filter_cat' value='0'>";
			}else{
				$cat_drop = "<select name='filter_cat'>";
				$cat_drop .= "<option value='0'>All Categories</option>";
				while($cat = pg_fetch_array($catRslt)){
					if (isset ($filter_cat) AND $filter_cat == $cat['catid']){
						$cat_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
					}else {
						$cat_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
					}
				}
				$cat_drop .= "</select>";
			}

			# Select classification
			$classsql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
			$clasRslt = db_exec($classsql);
			if(pg_numrows($clasRslt) < 1){
				$class_drop = "<input type='hidden' name='filter_class' value='0'>";
			}else{
				$class_drop = "<select name='filter_class' style='width: 167'>";
				$class_drop .= "<option value='0'>All Classifications</option>";
				while($clas = pg_fetch_array($clasRslt)){
					if (isset ($filter_class) AND $filter_class == $clas['clasid']){
						$class_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
					}else {
						$class_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
					}
				}
				$class_drop .= "</select>";
			}

			$display_optional_filters = "
				<tr class='".bg_class()."'>
					<td>Select Category</td>
					<td>$cat_drop</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Classification</td>
					<td>$class_drop</td>
				</tr>";
		}

		db_conn("exten");

		$sql = "SELECT whid, whname, whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
		$whRslt = db_exec($sql);
		if(pg_numrows($whRslt) < 1){
			$store_drop = "<input type='hidden' name='filter_store' value='0'>";
		}else{
			if (!isset ($filter_store)){
				# check if setting exists
				db_connect();
				$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
				$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
				if (pg_numrows ($Rslt) > 0) {
					$set = pg_fetch_array($Rslt);
					$filter_store = $set['value'];
				}
			}
			$store_drop = "<select name='filter_store'>";
			$store_drop .= "<option value='0'>All Stores</option>";
			while($wh = pg_fetch_array($whRslt)){
				if (isset ($filter_store) AND $filter_store == $wh['whid']){
					$store_drop .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
				}else {
					$store_drop .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
				}
			}
			$store_drop .= "</select>";
		}

		$ajaxOut = "
			<input type='hidden' name='letters' value='$letters'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Department</td>
					<td valign='center'>$dept[deptname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Account No.</td>
					<td valign='center'>$cust[accno]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer</td>
					<td valign='center'>$customers</td>
					$custedit
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Customer Branch</td>
					<td valign='center'>$branchdrop</td>
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Customer Address</td>
					<td valign='center'>".nl2br($cust['addr1'])."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Current Delivery Address</td>
					<td valign='center'>".nl2br($cust_del_addr)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Delivery Address</td>
					<td valign='center'>
						<input type='button' onClick=\"
							var windowReference;

							function openPopup() {
								windowReference = window.open('cust-credit-stockinv-deladdr.php?invid=$invid','windowName','width=500,height=400,status=1');
								if (!windowReference.opener)
									windowReference.opener = self;
							}
							openPopup();\" value='Change Delivery Address'>
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer Order number</td>
					<td valign='center'><input type='text' size='10' name='cordno' value='$inv[cordno]'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer VAT Number</td>
					<td>$cust[vatnum]</td>
				</tr>
				<tr>
					<th colspan='2'>Point of Sale</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Barcode</td>
					<td><input type='text' size='13' name='bar' value=''></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Print Delivery Note</td>
					<td><input type='checkbox' name='printdel' $chp></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Using</td>
					<td>Stock Code<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod><br>Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th colspan='2'>Additional Filters</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Store</td>
					<td>$store_drop</td>
				</tr>
				$display_optional_filters
				<tr class='".bg_class()."' ".ass("Type the first letters of the stock code you are looking for.").">
					<td>Stock Filter</td>
					<td nowrap><input type='text' size='13' name='ria' value='$ria'> <input type='submit' value='Search'> <input type='submit' name='ria' value='Show All'></td>
				</tr>
			</table>";
	} else {
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			return "<li class='err'>There are no Departments found in Cubit.</li>";
		}else{
			$depts = "<select name='deptid' id='deptid'>";
			$depts .= "<option value='0'>All Departments</option>";
			while($dept = pg_fetch_array($deptRslt)){
				$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
			}
			$depts .= "</select>";
		}

		if (!isset($ajax_err)) $ajax_err = "";

		$ajaxOut = "
			<script>
			function updateCustSelection() {
				deptid = getObject('deptid').value;
				letters = getObject('letters').value;
				ctyp = getObject('ctypintl').checked;

				if (ctyp) {
					document.location.href='intinvoice-new.php?' + 'letters=' + letters + '&deptid=' + deptid + '&ctyp=' + ctyp + '&invid=$invid';
				} else {
					ajaxRequest('".SELF."', 'cust_selection', AJAX_SET, 'letters='+letters+'&deptid='+deptid+'&ctyp='+ctyp+'&invid=$invid');
				}
			}
			</script>
			$ajax_err
			<form name='cusselfrm'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Customer Selection</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Department</td>
					<td valign='center'>$depts</td>
				</tr>
				<tr class='bg-even'>
					<td>First Letters of customer</td>
					<td valign='center'><input type='text' size='5' id='letters' maxlength='5'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customer is International</td>
					<td valign='center'><input type='checkbox' id='ctypintl'></td>
				</tr>
				<tr>
					<td><br /></td>
				</tr>
				<tr>
					<td></td>
					<td valign='center'><input type='button' value='Update &raquo' onClick='updateCustSelection();'></td>
				</tr>
			</table>
			</form>";
	}

	$avcred = sprint ($avcred);

	if (isset ($addprodBtn) OR isset ($addnon) OR isset ($saveBtn) OR isset ($upBtn) OR isset ($ria)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details_end = "
						</div>
					</td>
					<td valign='top' align='right' width='50%'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Invoice Details </th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Invoice No.</td>
								<td valign='center'>TI $inv[invid]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Proforma Inv No.</td>
								<td valign='center'><input type='text' size='5' name='docref' value='$inv[docref]'></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Sales Order No.</td>
								<td valign='center'><input type='text' size='5' name='ordno' value='$inv[ordno]'></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Terms</td>
								<td valign='center'>$termssel Days</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Sales Person</td>
								$sales
							</tr>
							<tr class='".bg_class()."'>
								<td>Invoice Date</td>
								<td valign='center'>".mkDateSelect("inv_date",$inv_date_year,$inv_date_month,$inv_date_day)."</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT Inclusive</td>
								<td>Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin> No<input type='radio' size='7' name='chrgvat' value='exc' $chex></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Available Credit</td>
								<td>".CUR." $avcred</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Trade Discount</td>
								<td valign='center'><input type='text' size='5' name='traddisc' value='$inv[traddisc]'>%</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Delivery Charge</td>
								<td valign='center'><input type='text' size='7' name='delchrg' value='$inv[delchrg]'>$Vatcodes</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Delivery Date</td>
								<td valign='center'>".mkDateSelect("del_date",$deldate[0],$deldate[1],$deldate[2])."</td>
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
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th width='25%'>Quick Links</th>
								<th width='25%'>Comments</th>
								<td rowspan='5' valign='top' width='50%'>$error</td>
							</tr>
							<tr>
								<td class='".bg_class()."'><a href='customers-new.php?re=$inv[invid]'>New Customer</a></td>
								<td class='".bg_class()."' rowspan='5' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$comm</textarea></td>
							</tr>
							<tr>
								<td class='".bg_class()."'><a href='cust-credit-stockinv-no-neg.php'>New Invoice</a></td>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='invoice-view.php'>View Invoices</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
					<td align='right' valign='top'>
						<table ".TMPL_tblDflts." width='50%'>
							<tr class='".bg_class()."'>
								<td>SUBTOTAL</td>
								<td align='right' nowrap>".CUR." <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Trade Discount</td>
								<td align='right' nowrap>".CUR." $inv[discount]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Delivery Charge</td>
								<td align='right' nowrap>".CUR." $inv[delivery]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td><b>VAT $vat14</b></td>
								<td align='right' nowrap>".CUR." $VAT</td>
							</tr>
							<tr class='".bg_class()."'>
								<th>GRAND TOTAL</th>
								<td align='right' nowrap>".CUR." $TOTAL</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align='right'><input name='addprodBtn' type='submit' value='Add Product'>| <input name='addnon' type='submit' value='Add Non stock Product'> | <input type='submit' name='saveBtn' value='Save'> </td>
					<td>| <input type='submit' name='upBtn' value='Update'>$done</td>
				</tr>
			</table>
			<a name='bottom'>
			</form>
			</center>
			$jump_bot";

	if (AJAX) {
		return $ajaxOut;
	} else {
		return "$details_begin$ajaxOut$details_end";
	}

}



# details
function write($_POST)
{

	# Get vars
	extract ($_POST);



	if (!isset($cusnum)) {
		return details($_POST, "<li class='err'>Please select customer/department first.</li>");
	}

	if (isset($cusnum) && customer_overdue($cusnum)) {
		return details($_POST, "<li class='err'>Customer is overdue, account blocked!</li>");
	}

	$delvat += 0;

	db_conn('cubit');

	if(isset($printsales)) {

		$Sl = "SELECT * FROM settings WHERE constant='SALES'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri) < 1) {
			$Sl = "INSERT INTO settings (constant,value,div) VALUES ('SALES','Yes','".USER_DIV."')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE settings SET value='Yes' WHERE constant='SALES' AND div='".USER_DIV."'";
			$Ri = db_exec($Sl);
		}
	} else {
		$Sl = "UPDATE settings SET value='No' WHERE constant='SALES' AND div='".USER_DIV."'";
		$Ri = db_exec($Sl);
	}

	if(isset($printdel)) {

		$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri) < 1) {
			$Sl = "INSERT INTO settings (constant,value,div) VALUES ('Delivery Note','Yes','".USER_DIV."')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE settings SET value='Yes' WHERE constant='Delivery Note' AND div='".USER_DIV."'";
			$Ri = db_exec($Sl);
		}
	} else {
		$Sl = "UPDATE settings SET value='No' WHERE constant='Delivery Note' AND div='".USER_DIV."'";
		$Ri = db_exec($Sl);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($branch, "num", 1, 20, "Invalid Branch, Please select a branch.");
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	if (!isset($ria)) {$ria = "";}
	$v->isOk ($ria, "string", 0, 20, "Invalid stock code(fist letters).");

	$v->isOk ($comm, "string", 0, 1024, "Invalid Comments.");
	$v->isOk ($docref, "string", 0, 20, "Invalid Document Reference No.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid sales order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($inv_date_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($inv_date_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($inv_date_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $inv_date_year."-".$inv_date_month."-".$inv_date_day;
	if(!checkdate($inv_date_month, $inv_date_day, $inv_date_year)){
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

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		$tmp_sernos = $sernos; // only check for uniqueness among items not selected for removal
		foreach ($sernos as $k => $serno_val) {
			if (isset($remprod) && in_array($k, $remprod)) {
				unset($tmp_sernos[$k]);
			}
		}
		if(!ext_isUnique(ext_remBlnk($tmp_sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check is serai no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			if (is_numeric($stkid) AND $stkid != 0) {
				$sql = "SELECT units, stkcod FROM cubit.stock WHERE stkid='$stkid'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
				list($stock_units, $stock_code) = pg_fetch_array($stock_rslt);
				
				if ((isset($qtys[$keys]) && $qtys[$keys] > $stock_units) || (isset($qtemp) && $qtemp > $stock_units)) {
					$v->addError(0, "Not enough stock available for $stock_code");
				}
			}

			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && !(strlen($sernos[$keys]) > 0)){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}
		}
	}

	if(!isset($qtys)&&isset($qtemp)) {
		$qtys[] = $qtemp;
	}elseif(isset($qtys)&&isset($qtemp)) {
		//array_unshift ($qtys,$qtemp);
		$qtys[] = $qtemp;
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

			$v->isOk ($qty, "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
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
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
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

	$inv['traddisc'] = $traddisc;
	$inv['chrgvat'] = $chrgvat;

	if(($cusnum != $inv['cusnum']) OR ($branch != $inv['branch'])){

		$get_addr = "SELECT branch_descrip FROM customer_branches WHERE id = '$branch' AND div = '".USER_DIV."' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if(pg_numrows($run_addr) < 1){
			#no branch addres ? since we NEED to update the address, add the customer's here
			$get_cadd = "SELECT del_addr1 FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
			$run_cadd = db_exec($get_cadd) or errDie("Unable to get customer delivery address");
			if(pg_numrows($run_cadd) < 1){
				#no customer ??
				return details ($_POST,"<li class='err'>Invalid customer selected.</li>");
			}else {
				$carr = pg_fetch_array($run_cadd);
				$update_addr = "UPDATE invoices SET del_addr  = '$carr[del_addr1]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$run_update = db_exec($update_addr) or errDie("Unable to update invoice information");
			}
		}else {
			$arr = pg_fetch_array($run_addr);
			$cust['addr1'] = $arr['branch_descrip'];
			if($inv['del_addr'] != $arr['branch_descrip']){
				$update_addr = "UPDATE invoices SET del_addr = '$arr[branch_descrip]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$run_update = db_exec($update_addr) or errDie("Unable to update invoice information");
			}
		}
	}


	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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

		$inv['deptid'] = $cust['deptid'];

		# If customer was just selected, get the following
		if($inv['cusnum'] == 0){
			$traddisc = $cust['traddisc'];
			$terms = $cust['credterm'];
		}
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	$vatamount = 0;
	$showvat = TRUE;

	# insert invoice to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* -- Start remove old items -- */
	# get selected stock in this invoice
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);

	while($stkt = pg_fetch_array($stktRslt)){
		# update stock(alloc + qty)
		$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		if(strlen($stkt['serno']) > 0)
			ext_unresvSer($stkt['serno'], $stkt['stkid']);
	}

	# remove old items
	$sql = "DELETE FROM inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
	/* -- End remove old items -- */

	$newvat = 0;

	$taxex = 0;
	if(isset($qtys)){
		foreach($qtys as $keys => $value){
			/* set the serial ss field for serials selected from list */
			if ($sernos_ss[$keys] == "*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*") {
				$sernos_ss[$keys] = $sernos[$keys];
			}

			if(isset($remprod)&&in_array($keys, $remprod)){
				$amt[$keys] = 0;

				if ($sernos[$keys] == $sernos_ss[$keys] && $sernos_ss[$keys] != "") {
					$chr = substr($sernos[$keys], strlen($sernos[$keys])-1, 1);

					$tab = "ss$chr";

					/* mark barcoded item as unavailable */
					$sql = "UPDATE ".$tab." SET active='yes' WHERE code = '$sernos[$keys]' AND div = '".USER_DIV."'";
					db_exec($sql);
				}
			}elseif(isset($accounts[$keys])&&$accounts[$keys]!=0){
				$accounts[$keys] += 0;
				# Get selamt from selected stock
				db_conn('core');
				$Sl = "SELECT * FROM accounts WHERE accid='$accounts[$keys]'";
				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

				$ad = pg_fetch_array($Ri);

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));

				db_conn('cubit');
				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
				}

				$vd = pg_fetch_array($Ri);

				if($vd['zero'] == "Yes") {
					$excluding = "y";
				} else {
					$excluding = "";
				}

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs = explode("|",$vr);
				$ivat = $vrs[0];
				$iamount = $vrs[1];

				$vatamount += $ivat;

				# Check Tax Excempt
				if($vd['zero'] == "Yes"){
					$taxex += $amt[$keys];
					$exvat = "y";
				} else {
					$exvat = "n";
				}

				//$newvat+=vatcalc($amt[$keys],$chrgvat,$exvat,$traddisc);
				$vatcodes[$keys] += 0;
				$accounts[$keys] += 0;
				$descriptions[$keys] = remval($descriptions[$keys]);
				$wtd = $whids[$keys];
				# insert invoice items
				$sql = "
					INSERT INTO inv_items (
						invid, whid, stkid, qty, unitcost, amt, 
						disc, discp, serno, div, vatcode, description, 
						account, del
					) VALUES (
						'$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
						'$disc[$keys]', '$discp[$keys]', '', '".USER_DIV."', '$vatcodes[$keys]', '$descriptions[$keys]', 
						'$accounts[$keys]', '0'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			}else{
				# Get selamt from selected stock
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

				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
				}
				$vd = pg_fetch_array($Ri);

				if($vd['zero'] == "Yes") {
					$excluding = "y";
				} else {
					$excluding = "";
				}

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs = explode("|",$vr);
				$ivat = $vrs[0];
				$iamount = $vrs[1];

				$vatamount += $ivat;

				# Check Tax Excempt
				if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes"){
					$taxex += $amt[$keys];
					$exvat = "y";
				} else {
					$exvat = "n";
				}

				//$newvat+=vatcalc($amt[$keys],$chrgvat,$exvat,$traddisc);

				$wtd = $whids[$keys];
				# insert invoice items
				$sql = "
					INSERT INTO inv_items (
						invid, whid, stkid, qty, unitcost, amt, 
						disc, discp, ss, serno, div, 
						vatcode, del
					) VALUES (
						'$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
						'$disc[$keys]', '$discp[$keys]', '$sernos_ss[$keys]', '$sernos[$keys]', '".USER_DIV."', 
						'$vatcodes[$keys]', '0'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

				if(strlen($sernos[$keys]) > 0)
					ext_resvSer($sernos[$keys], $stk['stkid']);

				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}

			# everything is set place done button
			$_POST["done"] = "
				| <input name='doneBtn' type='submit' value='Process'>";

			//if ($cust["email"] != "") {
			$_POST["done"] .= "
				| <input name='emailBtn' type='submit' value='Process and Email to Customer'>";
			//}
		}
	}else{
		$_POST["done"] = "";
	}

	//$newvat+=vatcalc($delchrg,$chrgvat,"no",$traddisc);

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$delvat'";
	$Ri = db_exec($Sl);

	$vd = pg_fetch_array($Ri);


	// 		if(pg_num_rows($Ri)>0) {
	// 			$taxex += $delchrg;
	// 		}

	if($vd['zero'] == "Yes") {
		$excluding = "y";
	} else {
		$excluding = "";
	}

	if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
		$showvat = FALSE;
	}

	$_POST['showvat'] = $showvat;

	$vr = vatcalc($delchrg,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs = explode("|",$vr);
	$ivat = $vrs[0];
	$iamount = $vrs[1];

	$vatamount += $ivat;

	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$sub = 0.00;
	if(isset($amt)) {
		$sub = sprint(array_sum($amt));
	}

	$VATP = TAX_VAT;

	if($chrgvat == "exc"){
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = $vatamount;
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);

	}elseif($chrgvat == "inc"){
		$ot = $taxex;
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc / 100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = $vatamount;
		$SUBTOT = sprint($sub);
		$TOTAL = sprint($subtotal);
		$delexvat = sprint(($delchrg));
		$traddiscmt = sprint($traddiscmt);

	} else {
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc / 100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(0);
		$SUBTOT = $sub;
		$TOTAL = $subtotal;
		$delexvat = sprint($delchrg);
	}

	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------










/* --- ----------- Clac ---------------------
----------------------OLD----------------------
		# calculate subtot
		$SUBTOT = 0.00;
		if(isset($amt))
			$SUBTOT = array_sum($amt);

		$SUBTOT -= $taxex;

		# duplicate
		$SUBTOTAL = $SUBTOT;

		$VATP = TAX_VAT;
		if($chrgvat == "exc"){
			$SUBTOTAL = $SUBTOTAL;
			$delexvat= ($delchrg);
		}elseif($chrgvat == "inc"){
			$SUBTOTAL = sprint(($SUBTOTAL * 100)/(100 + $VATP));
			$delexvat = sprint(($delchrg * 100)/($VATP + 100));
		}else{
			$SUBTOTAL = ($SUBTOTAL);
			$delexvat = ($delchrg);
		}

		$SUBTOT = $SUBTOTAL;
		$EXVATTOT = $SUBTOT;
		$EXVATTOT += $delexvat;

		# Minus trade discount from taxex
		if($traddisc > 0){
			$traddiscmtt = (($traddisc/100) * $taxex);
		}else{
			$traddiscmtt = 0;
		}
		$taxext = ($taxex - $traddiscmtt);

		if($traddisc > 0) {
			$traddiscmt = ($EXVATTOT * ($traddisc/100));
		}else{
			$traddiscmt = 0;
		}
		$EXVATTOT -= $traddiscmt;
		// $EXVATTOT -= $taxex;

		$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

		if($chrgvat != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;
 */
	#override address
	if($branch != 0){
		$get_addr = "SELECT branch_descrip FROM customer_branches WHERE id = '$branch' AND div = '".USER_DIV."' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if(pg_numrows($run_addr) < 1){
			#address missing ... do nothing
		}else {
			$arr = pg_fetch_array($run_addr);
			$cust['addr1'] = $arr['branch_descrip'];
		}
	}

	// Delivery Date
	$deldate = "$del_date_year-$del_date_month-$del_date_day";

	/* --- ----------- Clac --------------------- */

	if (!isset($bankid)) {
		$bankid = cust_bank_id($cusnum);
	}

	# insert invoice to DB
	$sql = "
		UPDATE invoices 
		SET delvat='$delvat', cusnum = '$cusnum', deptid = '$dept[deptid]', deptname = '$dept[deptname]', 
			cusacc = '$cust[accno]', cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', 
			cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', docref = '$docref', chrgvat = '$chrgvat', 
			terms = '$terms', salespn = '$salespn', odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', 
			subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', comm = '$comm', serd = 'y', 
			discount='$traddiscmt', delivery='$delexvat', branch = '$branch', deldate = '$deldate', bankid = '$bankid' 
		WHERE invid = '$invid'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# remove old data
	$sql = "DELETE FROM inv_data WHERE invid='$invid'  AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

	# pu in new data
	$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	if (strlen($bar)>0)
	{
		/* check if there a stock item with global barcode matching input barcode */
		$sql = "SELECT * FROM stock WHERE bar='$bar' AND div = '".USER_DIV."'";
		$barRslt = db_exec($sql);

		if (pg_num_rows($barRslt) <= 0) {
			/* fetch last character of barcode */
			$chr = substr($bar, strlen($bar)-1, 1);

			/* invalid barcode */
			if (!is_numeric($chr)) {
				return details($_POST,"The code you selected is invalid");
			}

			/* which barcode table to scan for stock id */
			$tab = "ss$chr";
			$stid = barext_dbget($tab, 'code', $bar, 'stock');

			$stab = "serial$chr";
			$sstid = serext_dbget($stab, 'serno', $bar, 'stkid');

			/* non-existing barcode, check for serial number */
			if ($stid <= 0) {
				if ($sstid <= 0) {
					return details($_POST,"<li class='err'>The serial number/bar code you selected is not in the system or is not available.</li>");
				}

				if (serext_dbnum($stab, 'serno', $bar, 'stkid') > 1) {
					return details($_POST,"<li class='err'>Duplicate serial numbers found, please scan barcode or select stock item.</li>");
				}

				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$stab." SET rsvd='y' WHERE serno='$bar'";
				db_exec($sql);

				$serno_bar = "$bar";

				$stid = $sstid;
			} else {
				if ($sstid > 0) {
					return details($_POST,"<li class='err'>A serial and barcode with same value, please scan other value or select product manually.</li>");
				}

				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$tab." SET active='no' WHERE code='$bar' AND div='".USER_DIV."'";
				db_exec($sql);

				$serno_bar = "$bar";
			}

			/* fetch stock row for selected item */
			$sql = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$barRslt = db_exec($sql);
		} else {
			$serno_bar = "";
		}

		$s = pg_fetch_array($barRslt);

		/* allocate stock item */
		$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$s[stkid]' AND div = '".USER_DIV."'";
		db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		$sql = "
			INSERT INTO inv_items (
				invid, whid, stkid, qty, unitcost, amt, disc, discp, ss, serno, 
				div, vatcode
			) VALUES (
				'$invid', '$s[whid]', '$s[stkid]', '1', '$s[selamt]', '$s[selamt]', '0', '0', '$bar', '$serno_bar', 
				'".USER_DIV."', (SELECT id FROM cubit.vatcodes LIMIT 1)
			)";
		db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* --- Start button Listeners --- */
	if(isset($doneBtn) || isset($emailBtn)){
		# Check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class='err'> Error : Invoice number has no items.";
			return details($_POST, $error);
		}

		# Insert quote to DB
		$sql = "UPDATE invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);

		$Sl = "SELECT * FROM settings WHERE constant='Delivery Note'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		$data = pg_fetch_array($Ri);

		if (isset($emailBtn)) {
			$email = "email=true";
		} else {
			$email = "";
		}

		if($data['value'] == "Yes") {
			$OUTPUT = "
				<script>
					nhprinter('invoice-delnote.php?invid=$invid','Delivery Note');
					printer('invoice-print.php?invid=$invid&type=inv&salespn=$salespn&$email');
					move('cust-credit-stockinv-no-neg.php');
			</script>";
		} else {
			$OUTPUT = "
				<script>
					printer('invoice-print.php?invid=$invid&type=inv&$email');
					move('cust-credit-stockinv-no-neg.php');
				</script>";
		}

		# Print the invoice

		require("template.php");


	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Invoice Saved</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Invoice for customer <b>$cust[cusname] $cust[surname]</b> has been saved. To view it go to 'View incomplete invoices'</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='invoice-view.php'>View Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}else{
		if(isset($wtd)){$_POST['wtd'] = $wtd;}
		if(strlen($ria) > 0){$_POST['ria'] = $ria;}
		return details($_POST);
	}

}



?>

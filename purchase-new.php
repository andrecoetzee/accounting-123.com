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
if (isset($_GET["purid"]) && isset($_GET["cont"])) {
	$_GET['letters'] = "";
	$_GET['done'] = "";
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "details":
				$OUTPUT = details($_POST);
				break;
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = view();
		}
	} else {
		$OUTPUT = view();
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
		$depts .= "<option value='0'>All Departments</option>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<tr>
				<th colspan='2'>New Order</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of Supplier</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order Type</td>
				<td valign='center'><input type='radio' name='cash' value='no' checked='yes'>Normal Order | <input type='radio' name='cash' value='yes'>Petty Cash Order</td>
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
				<td><a href='purchase-view.php'>View Orders</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='supp-new.php'>New Supplier</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
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
		$depts .= "<option value='0'>All Departments</option>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	//layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Order</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>First Letters of Supplier</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order Type</td>
				<td valign='center'><input type='radio' name='cash' value='no' checked='yes'>Normal Order | <input type='radio' name='cash' value='yes'>Cash Order</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
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
				<td><a href='purchase-view.php'>View Orders</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}



# Starting dummy
function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$supid = 0;
	$remarks = "";
	$supname = "";
	$supaddr = "";
	$supno = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
//	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";

	$purnum = divlastid('pur', USER_DIV);

	if(getSetting("PURCH_APPRV") == 'napprv' || getSetting("PURCH_APPRV") == ''){
		$apprv = 'y';
	}else{
		$apprv = 'n';
	}

	$def_vat = getCSetting ("PURCH_DEFAULT_VAT_SETTING");
	if (!isset ($def_vat) OR $def_vat == "yes"){
		$vatsetting = "yes";
	}else {
		$vatsetting = "no";
	}

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
	$pdate = "$date_year-$date_month-$date_day";

	# Insert Order to DB
	$sql = "
		INSERT INTO purchases (
			deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, 
			total, balance, vatinc, vat, remarks, received, done, prd, div, purnum, apprv
		) VALUES (
			'$deptid', '$supid', '$supname', '$supaddr', '$supno', '$terms', '$pdate', '$ddate', '$shipchrg', '$subtot', 
			'$total', '$total', '$vatsetting', '0', '$remarks', 'n', 'n', '".PRD_DB."', '".USER_DIV."', '$purnum', '$apprv'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

	# Get next ordnum
	$purid = lastpurid();
	return $purid;

}



# details
function details($_POST, $error="")
{

	# get vars
	extract ($_POST);

	# Redirect, vars?
	if(isset($cash) && $cash == 'yes'){
		header("Location: purchase-new-cash.php?deptid=$deptid");
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($purid)){
		$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	}else{
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if(!isset($purid)){
		$purid = create_dummy($deptid);
	}

	$supprice = 0;

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$deptid = $pur['deptid'];

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	if(!(isset($ordernum))) {$ordernum = '';}
	if(!(isset($supinv))) {$supinv = '';}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($supRslt) < 1) {

		db_connect();

		if ($inv['deptid'] == 0){
			$searchdept = "";
		}else {
			$searchdept = "deptid = '$deptid' AND ";
		}

		# Query server for supplier info
		$sql = "SELECT * FROM suppliers WHERE $searchdept location != 'int' AND lower(supname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier names starting with <b>$letters</b> in database.</li>";
			return view_err($_POST, $err);
		}else{
			$suppliers = "<select name='supid' onChange='javascript:document.form.submit();'>";
			$suppliers .= "<option value='-S' selected>Select Supplier</option>";
			while($sup = pg_fetch_array($supRslt)){
				$suppliers .= "<option value='$sup[supid]'>$sup[supname]</option>";
			}
			$suppliers .= "</select>";
		}

		# take care of the uset vars
		$supaddr = "";
		$accno = "";
	}else{
		db_connect();
		# Query server for supplier info
		$sql = "SELECT * FROM suppliers WHERE deptid = '$deptid' AND location != 'int' AND lower(supname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier names starting with <b>$letters</b> in database.</li>";
			return view_err($_POST, $err);
		}else{
			$suppliers = "<select name='supid' onChange='javascript:document.form.submit();'>";
			$supaddr = "";
			$accno = "";
			while($sup = pg_fetch_array($supRslt)){
				if($sup['supid'] == $pur['supid']){
					$sel = "selected";
					$supaddr = $sup['supaddr'];
					$accno = $sup['supno'];
					$supprice = $sup['listid'];
				}else {
					$sel = "";
				}
				$suppliers .= "<option value='$sup[supid]' $sel>$sup[supname]</option>";
			}
			$suppliers .= "</select>";
		}

		$get_codes = "SELECT * FROM suppstock WHERE suppid = '$pur[supid]' ORDER BY stkid";
		$run_codes = db_exec ($get_codes) or errDie ("Unable to get supplier stock code information");
		if (pg_numrows ($run_codes) > 0){
			while ($codarr = pg_fetch_array ($run_codes)){
				if (strlen ($codarr['stkcod']) > 0) 
					$stockcodes[$codarr['stkid']]['stkcod'] = $codarr['stkcod'];
				if (strlen ($codarr['stkdes']) > 0) 
					$stockcodes[$codarr['stkid']]['stkdes'] = $codarr['stkdes'];
			}
		}

	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> No Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# days drop downs
	$days = array("0" => "0","7" => "7","14" => "14","30" => "30","60" => "60","90" => "90","120" => "120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pur_year, $pur_month, $pur_day) = explode("-", $pur['pdate']);

	# keep the charge vat option stable
	if($pur['vatinc'] == "yes"){
		$chy = "checked='yes'";
		$chn = "";
		$chv = "";
	}elseif($pur['vatinc'] == "no"){
		$chy = "";
		$chn = "checked='yes'";
		$chv = "";
	}else{
		$chy = "";
		$chn = "";
		$chv = "checked='yes'";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>VAT CODE</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PER PACK</th>
				<th>PRICE PER PACK</th>
				<th>UNITS</th>
				<th>PRICE PER UNIT</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>VAT</th>
				<th>REM</th>
			</tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account'] += 0;
		if($stkd['account'] != 0) {

			if(!isset($stk['exvat']))
				$stk['exvat'] = "";

			# keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');

			$Sl = "SELECT * FROM accounts WHERE accid='$stkd[account]'";
			$Rk = db_exec($Sl);

			$ad = pg_fetch_array($Rk);

			list($syear, $smonth, $sday) = explode("-", $stkd['ddate']);

			$stkd['amt'] = sprint($stkd['amt']);

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
//				return "Please select the vatcode for all your stock.";
				$_POST['done'] = "";
				return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
			}

			$vd = pg_fetch_array($Ri);

			if($pur['vatinc'] == 'no' && $stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
				$vunitamt = sprint($stkd['unitcost'] + ($stkd['svat']/$stkd['qty']));
			}else{
				$vunitamt = sprint($stkd['unitcost']);
			}

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[]'>
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

			$tip = "&nbsp;&nbsp;&nbsp;";
			if(isset($vatc[$key])){
				$tip = "<font color='red'>#</font>";
				$error = "<div class='err'> $tip&nbsp;&nbsp;=&nbsp;&nbsp; VAT amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
			}

			if($stkd['udiscount'] > 0){
				$discps = round((($stkd['udiscount']/100) * $stkd['unitcost']), 2);
			}
			$stkd['amt'] = sprint($stkd['qty'] * ($stkd['unitcost'] - $discps));

			# put in product
			$products .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[]' value='0'></td>
					<td>
						<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
						$ad[accname]
						<input type='hidden' name='accounts[]' value='$stkd[account]'>
					</td>
					<td>$Vatcodes</td>
					<td><input type='text' name='descriptions[]' value='$stkd[description]'></td>
					<td><input type='text' size='4' name='qtys[]' value='$stkd[qpack]'></td>
					<td><input type='text' size='5' name='upack[]' value='$stkd[upack]'></td>
					<td><input type='text' size='8' name='ppack[]' value='".sprint($stkd['ppack'])."'></td>
					<td>".sprint3($stkd['qty'])."</td>
					<td align='right' nowrap>".CUR." $vunitamt</td>
					<td><input type='text' size='5' name='udiscount[]' value='$stkd[udiscount]'></td>
					<td>".mkDateSelecta("d",$key,$syear,$smonth,$sday)."</td>
					<td align='right' nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
					<td>$tip <input type='text' name='svat[]' size='7' value='$stkd[svat]'></td>
					<td><input type='checkbox' name='remprod[]' value='$key'></td>
				</tr>";
			$key++;
		}else {

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

			list($syear, $smonth, $sday) = explode("-", $stkd['ddate']);

			$stkd['amt'] = sprint($stkd['amt']);

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
				<select name='vatcodes[]'>
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

			if($pur['vatinc'] == 'no' && $stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
				$vunitamt = sprint($stkd['unitcost'] + ($stkd['svat']/$stkd['qty']));
			}else{
				$vunitamt = sprint($stkd['unitcost']);
			}

			$tip = "&nbsp;&nbsp;&nbsp;";
			if(isset($vatc[$key])){
				$tip = "<font color='red'>#</font>";
				$error = "<div class='err'> $tip&nbsp;&nbsp;=&nbsp;&nbsp; VAT amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
			}

			$discps = 0;
			if($stkd['udiscount'] > 0){
				$discps = round((($stkd['udiscount']/100) * $stkd['unitcost']), 2);
			}
			$stkd['amt'] = sprint($stkd['qty'] * ($stkd['unitcost'] - $discps));

			if (isset ($stockcodes[$stk['stkid']]['stkcod']))
				$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
			if (isset ($stockcodes[$stk['stkid']]['stkdes']))
				$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

			# put in product
			$products .= "
				<input type='hidden' name='accounts[]' value='0'>
				<input type='hidden' name='descriptions[]' value=''>
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$Vatcodes</td>
					<td>$stk[stkdes]</td>
					<td><input type='text' size='4' name='qtys[]' value='$stkd[qpack]'></td>
					<td><input type='text' size='5' name='upack[]' value='$stkd[upack]'></td>
					<td><input type='text' size='8' name='ppack[]' value='".sprint($stkd['ppack'])."'></td>
					<td>".sprint3($stkd['qty'])."</td>
					<td align='right' nowrap>".CUR." $vunitamt</td>
					<td><input type='text' size='5' name='udiscount[]' value='$stkd[udiscount]'></td>
					<td>".mkDateSelecta("d",$key,$syear,$smonth,$sday)."</td>
					<td align='right' nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
					<td>$tip <input type='text' name='svat[]' size='7' value='$stkd[svat]'></td>
					<td><input type='checkbox' name='remprod[]' value='$key'></td>
				</tr>";
			$key++;
		}
	}

	// select using selection
	if (!isset($sel_frm)) {
		$sel_frm = "stkcod";
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# Get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				$Sl = "SELECT * FROM splist_prices WHERE listid='$supprice' AND div='".USER_DIV."' AND stkid='$stkidss[$key]'";
				$Ry = db_exec($Sl) or errDie("Unable to get price.");
				$listdata = pg_fetch_array($Ry);
				$newprice = $listdata['price'];

				# Get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Calculate amount
				$amt[$key] = sprint($qtyss[$key] * 0);
				$newprice += 0;

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[]'>
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

				$discps = 0;
				if($udiscount[$key] > 0){
					$discps = round((($udiscount[$key]/100) * $newprice), 2);
				}
				$amt[$key] = sprint($qtyss[$key] * ($newprice - $discps));

				if (isset ($stockcodes[$stk['stkid']]['stkcod']))
					$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
				if (isset ($stockcodes[$stk['stkid']]['stkdes']))
					$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

				# Put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[]' value='0'>
					<input type='hidden' name='descriptions[]' value=''>
					<tr class='".bg_class()."'>
						<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
						<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$Vatcodes</td>
						<td>$stk[stkdes]</td>
						<td><input type='text' size='4' name='qtys[]' value='$qtyss[$key]'></td>
						<td><input type='hidden' name='novat[$keyy]' value='1'><input type='text' size='5' name='upack[]' value='$stk[rate]'></td>
						<td><input type='text' size='8' name='ppack[]' value='".sprint($newprice)."'></td>
						<td>$stk[rate]</td>
						<td align='right' nowrap>".CUR." 0.00</td>
						<td><input type='text' size='5' name='udiscount[]' value='".sprint($udiscounts[$key])."'></td>
						<td>".mkDateSelecta("d",$keyy,$d_year[$keyy],$d_month[$keyy],$d_day[$keyy])."</td>
						<td align='right' nowrap><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
						<td> </td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
			}elseif(isset($accountss[$key]) && $accountss[$key] != "0"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				db_conn('core');

				$Sl = "SELECT * FROM accounts WHERE accid='$accountss[$key]'";
				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

				if(pg_num_rows($Ri) < 1) {
					return "invalid.";
				}

				$ad = pg_fetch_array($Ri);

				# Calculate amount
				$amt[$key] = sprint($qtyss[$keyy] * 0);
				//$newprice+=0;

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes ORDER BY code";
				$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
					<select name='vatcodes[]'>
						<option value='0'>Select</option>";
				while($vd = pg_fetch_array($Ri)) {
					if($vatcodess[$keyy] == $vd['id']) {
						$sel = "selected";
					} else {
						$sel = "";
					}
					$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
				}
				$Vatcodes .= "</select>";

				#we want to open the cost center popup here and now if this is and expense ....

/*				$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' LIMIT 1";
				$banks = db_exec($sql);
				if(pg_numrows($banks) < 1){
					return "<li class=err> There are no accounts held at the selected Bank.
					<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
				}
				$barr = pg_fetch_array($banks);
				$bankid = $barr['bankid'];

				core_connect();
				$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
				# Check if link exists
				if(pg_numrows($rslt) <1){
					return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
				}
				$banklnk = pg_fetch_array($rslt);*/

/*				if(cc_TranTypeAcc($accountss[$key], $banklnk['accnum']) != false){
					$cc_trantype = cc_TranTypeAcc($accountss[$key], $banklnk['accnum']);
					$date = date("d-m-Y");
					$amount = $ppack[$key] * $qtyss[$key];
					$cc = "<script> CostCenter('$cc_trantype', 'Bank Transaction', '$date', '$descriptionss[$key]', '$amount', ''); </script>";
				}else{
					$cc = "";
				}*/

				# THIS PRODUCT DISPLAYS DIRECTLY AFTER A NON STOCK ACCOUNT HAS BEEN SUBMITTED
				# Put in selected warehouse and stock

				$discps = 0;
				if($udiscount[$key] > 0){
					$discps = round((($udiscount[$key]/100) * $newprice), 2);
				}
				$amt[$key] = sprint($qtyss[$key] * ($newprice - $discps));

				$products .= "
					<tr class='".bg_class()."'>
						<td colspan='2'>
							<input type='hidden' name='accounts[]' value='$accountss[$key]'>
							<input type='hidden' name='whids[]' value='0'>$ad[accname]
						</td>
						<td><input type='hidden' name='stkids[]' value='0'>$Vatcodes</td>
						<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$keyy]'></td>
						<td><input type='text' size='4' name='qtys[]' value='$qtyss[$keyy]'></td>
						<td><input type='hidden' name='novat[$keyy]' value='1'><input type='text' size='5' name='upack[]' value='$upack[$key]'></td>
						<td><input type='text' size='8' name='ppack[$keyy]' value='".sprint($ppack[$keyy])."'></td>
						<td></td>
						<td align='right' nowrap>".CUR." 0.00</td>
						<td><input type='text' size='5' name='udiscount[$keyy]' value='".sprint($udiscount[$keyy])."'></td>
						<td>".mkDateSelecta("d",$keyy,$d_year[$key],$d_month[$key],$d_day[$key])."</td>
						<td align='right' nowrap><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
						<td> </td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
			}else{
				# Skip if not selected
				if($whid == "-S"){
					continue;
				}

				if(!isset($addnon)) {

					if (isset ($filter_store) AND $filter_store != "0"){
						# Get warehouse name
						db_conn("exten");
						$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);
					}

					if(isset($des) AND $des != "") {
						$len = strlen($des);
						if($des == "Show All"){
							$Wh = "";
							$des = "";
						}else {
							$Wh = "AND (lower(stkdes) LIKE lower('%$des%')) OR (lower(stkcod) LIKE lower('%$des%'))";
						}
					} else {
						$Wh = "AND FALSE";
						$des = "";
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

					# Get stock on this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' $Wh ORDER BY stkcod ASC LIMIT 200";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						//$error .= "<li class='err'>There are no stock items in the selected warehouse.";
						continue;
					}

					if ($sel_frm == "stkcod") {
						$stks = "<select name='stkidss[]' style='width:200px' onChange='javascript:document.form.submit();'>";
						$stks .= "<option value='-S' disabled selected>Select Number</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							if (isset ($stockcodes[$stk['stkid']]['stkcod']))
								$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
							if (isset ($stockcodes[$stkd['stkid']]['stkdes']))
								$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];
							$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
						}
						$stks .= "</select> ";
					} else {
						$stks = "<select name='stkidss[]' style='width:200px' onChange='javascript:document.form.submit();'>";
						$stks .= "<option value='-S' disabled selected>Select Description</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){

							if (isset ($stockcodes[$stk['stkid']]['stkcod']))
								$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
							if (isset ($stockcodes[$stkd['stkid']]['stkdes']))
								$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

							$stks .= "<option value='$stk[stkid]'>$stk[stkdes] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
						}
						$stks .= "</select> ";
					}

					# Put in drop down and warehouse
					$products .= "
						<input type='hidden' name='accountss[]' value='0'>
						<input type='hidden' name='descriptionss[]' value=''>
						<input type='hidden' name='udiscounts[]' value='0.00'>
						<tr class='".bg_class()."'>
							<td><input type='hidden' name='whidss[]' value='$filter_store'></td>
							<td>$stks</td>
							<td> </td>
							<td> </td>
							<td><input type='text' size='4' name='qtyss[]'  value='1'></td>
							<td> </td>
							<td> </td>
							<td> </td>
							<td> </td>
							<td></td>
							<td>".mkDateSelecta("d",$keyy,$d_year[$keyy],$d_month[$keyy],$d_day[$keyy])."</td>
							<td align='right' nowrap><input type='hidden' name='amts[]' value='0.00'>".CUR." 0.00</td>
							<td> </td>
							<td> </td>
						</tr>
						<input type='hidden' name='accountss[]' value='0'>
						<input type='hidden' name='descriptionss[]' value=''>";
				} else {

					$Accounts = "
						<select name='accountss[]'>
							<option value='0'>Select Account</option>";

					$useaccdrop = getCSetting ("USE_NON_PURCHASES_ACCOUNTS");
					if (isset ($useaccdrop) AND $useaccdrop == "yes"){
						db_connect ();
						$acc_sql = "SELECT * FROM non_purchases_account_list ORDER BY accname";
						$run_acc = db_exec ($acc_sql) or errDie ("Unable to get account information.");
						if (pg_numrows ($run_acc) > 0){
							while($acc = pg_fetch_array($run_acc)){
								$Accounts .= "<option value='$acc[accid]'>$acc[accname]</option>";
							}
							$Accounts .= "</select>";
						}
					}else {
						db_conn('core');
						$Sl = "SELECT accid,topacc,accnum,accname FROM accounts ORDER BY accname";
						$Ri = db_exec($Sl) or errDie("Unable to get accounts.");// WHERE acctype='I'
						while($ad = pg_fetch_array($Ri)) {
							if(isDisabled($ad['accid'])) {
								continue;
							}
	
							$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
						}
						$Accounts .= "</select>";
					}

					db_conn('cubit');
					$Sl = "SELECT * FROM vatcodes ORDER BY code";
					$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

					$Vatcodes = "
						<select name='vatcodess[$keyy]'>
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

					# Put in drop down and warehouse
					$products .= "
						<tr class='".bg_class()."'>
							<td colspan='2'><input type='hidden' name='whidss[]' value='0'>$Accounts</td>
							<td>$Vatcodes</td>
							<td><input type='text' size='20' name='descriptionss[$keyy]' value=''></td>
							<td><input type='text' size='4' name='qtyss[$keyy]' value='1'></td>
							<td><input type='hidden' name='upack[$keyy]' value='1'></td>
							<td><input type='text' size='8' name='ppack[$keyy]' value=''></td>
							<td> </td>
							<td> </td>
							<td><input type='hidden' name='udiscount[$keyy]' value='0.00'></td>
							<td>".mkDateSelecta("d",$keyy,$d_year[$key],$d_month[$key],$d_day[$key])."</td>
							<td align='right' nowrap><input type='hidden' name='amts[$keyy]' value='0.00'>".CUR." 0.00</td>
							<td> </td>
							<td> </td>
						</tr>";
				}
			}
			$keyy++;
		}
	}else{
		$ckey = $keyy;
		# take todays date
		list($year, $month, $day) = explode("-", $pur['pdate']);

			if (isset ($filter_store) AND $filter_store != "0"){
				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$filter_store' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
			}

			if(isset($des) AND $des != "") {
				$len = strlen($des);
				if($des == "Show All"){
					$Wh = "";
					$des = "";
				}else {
					$Wh = "AND (lower(stkdes) LIKE lower('%$des%')) OR (lower(stkcod) LIKE lower('%$des%'))";
				}
			} else {
				$Wh = "AND FALSE";
				$des = "";
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
			$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' $Wh ORDER BY stkcod ASC LIMIT 200";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				if(!(isset($err))) {$err="";}
				$err .= "<li>There are no stock items in the selected warehouse.</li>";
			}

			if ($sel_frm == "stkcod") {
				$stks = "<select name='stkidss[]' style='width:200px' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					if (isset ($stockcodes[$stk['stkid']]['stkcod']))
						$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
					if (isset ($stockcodes[$stkd['stkid']]['stkdes']))
						$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
			} else {
				$stks = "<select name='stkidss[]' style='width:200px' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Description</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					if (isset ($stockcodes[$stk['stkid']]['stkcod']))
						$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
					if (isset ($stockcodes[$stkd['stkid']]['stkdes']))
						$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];
					$stks .= "<option value='$stk[stkid]'>$stk[stkdes] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
			}

			$products .= "
				<tr class='".bg_class()."'>
					<input type='hidden' name='accountss[]' value='0'>
					<input type='hidden' name='descriptionss[]' value=''>
					<input type='hidden' name='vatcodess[]' value=''>
					<input type='hidden' name='udiscounts[]' value='0.00'>
					<td><input type='hidden' name='whidss[]' value='$filter_store'></td>
					<td>$stks</td>
					<td> </td>
					<td> </td>
					<td><input type='text' size='4' name='qtyss[]' value='1'></td>
					<td> </td>
					<td> </td>
					<td> </td>
					<td> </td>
					<td> </td>
					<td>".mkDateSelecta("d",$ckey,$year,$month,$day)."</td>
					<td nowrap>".CUR." 0.00</td>
					<td> </td>
					<td></td>
				</tr>";
		$ckey++;
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

	# Shipping Charges
	$pur['shipchrg'] = sprint($pur['shipchrg']);

	$pur['delvat'] += 0;

	if($pur['delvat'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$pur['delvat'] = $vd['id'];
	}


	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $pur['delvat']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

/* --- End Some calculations --- */

	$ex = "";

	if(strlen($supinv)) {

		db_conn('cubit');

		$Sl = "SELECT purnum,pdate FROM purchases WHERE supid='$supid' AND supinv='$supinv' AND purid!='$purid'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) > 0) {
			$pd = pg_fetch_array($Ri);

			$ex .= "<li class='err'>Purchase $pd[purnum] on $pd[pdate] has the same supplier invoice number.</li>";

		}

		for($i = 1;$i < 13;$i++) {

			db_conn($i);

			$Sl = "SELECT purnum,pdate FROM purchases WHERE supid='$supid' AND supinv='$supinv'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {
				$pd = pg_fetch_array($Ri);

				$ex .= "<li class='err'>Purchase $pd[purnum] on $pd[pdate] has same the supplier invoice number.</li>";
			}
		}

		db_conn('cubit');

		$Sl = "SELECT purnum,pdate FROM nons_purchases WHERE supplier='$supid' AND supinv='$supinv'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) > 0) {
			$pd = pg_fetch_array($Ri);

			$ex .= "<li class='err'>Non Stock Purchase $pd[purnum] on $pd[pdate] has the same supplier invoice number.</li>";

		}

		for($i = 1;$i < 13;$i++) {

			db_conn($i);

			$Sl = "SELECT purnum,pdate FROM nons_purchases WHERE supplier='$supid' AND supinv='$supinv'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {
				$pd = pg_fetch_array($Ri);

				$ex .= "<li class='err'>Non Stock Purchase $pd[purnum] on $pd[pdate] has same the supplier invoice number.</li>";
			}
		}
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

	if (isset ($addnon) OR isset ($upBtn) OR isset ($doneBtn) OR isset ($invoice) OR isset ($donePrnt)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
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
				$whid = $set['value'];
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

	$details = "
		<center>
		<h3>New Order</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='SCROLL' value='1' />
			<input type='hidden' name='key' value='update' />
			<input type='hidden' name='purid' value='$purid' />
			<input type='hidden' name='deptid' value='$deptid' />
			<input type='hidden' name='letters' value='$letters' />
			<input type='hidden' name='total' value='$TOTAL' />
			<input type='hidden' name='subtot' value='$SUBTOT'>
		<table ".TMPL_tblDflts." width='97%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier</td>
							<td valign='center'>$suppliers</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account number</td>
							<td valign='center'>$accno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Select Using</td>
							<td>
								Stock Code
								<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod> Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des />
							</td>
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
						<tr class='".bg_class()."'>
							<td>Stock Filter</td>
							<td>
								<input type='text' size='13' name='des' value='$des'> 
								<input type='submit' value='Search'> 
								<input type='submit' name='des' value='Show All'>
							</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right' width='35%'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Order Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Order No.</td>
							<td valign='center'><input type='text' size='10' name='ordernum' value='$pur[ordernum]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Inv</td>
							<td><input type='text' size='10' name='supinv' value='$pur[supinv]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center' nowrap='t'>".mkDateSelect("pur",$pur_year,$pur_month,$pur_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>
								Yes <input type='radio' size='7' name='vatinc' value='yes' $chy> 
								No <input type='radio' size='7' name='vatinc' value='no' $chn></td></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges</td>
							<td valign='center'><input type=text size=7 name=shipchrg value='$pur[shipchrg]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges VAT Code</td>
							<td valign='center'>$Vatcodes</td>
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
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'>$ex $error</td>
						</tr>
						<tr>
							<td class='".bg_class()."'>
								<a href='supp-new.php?re=$pur[purnum]'>New Supplier</a>
							</td>
							<td class='".bg_class()."' rowspan='5' align='center' valign='top'>
								<textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea>
							</td>
						</tr>
						<tr>
							<td class='".bg_class()."'><a href='purchase-view.php'>View Orders</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr class='".bg_class()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $SUBTOT</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges</td>
							<td align='right' nowrap>".CUR." $pur[shipping]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT $vat14</td>
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
				<td colspan='2' align='center'><input name='addnon' type='submit' value='Add Non stock Product'> | <input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";
	return $details;

}




# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 9, "Invalid Order ID");
	$v->isOk ($supinv, "string",0,50,"Invalid supplier inv num.");
	$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	$v->isOk ($ordernum, "string", 0, 20, "Invalid order number.");
	$v->isOk ($supid, "num", 1, 20, "Please Select Supplier.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($pur_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($pur_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($pur_year, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$pdate = $pur_year."-".$pur_month."-".$pur_day;
	$pur_year += 0;
	$pur_month += 0;
	$pur_day += 0;
	if(!checkdate($pur_month, $pur_day, $pur_year)){
		$v->isOk ($pdate, "num", 1, 1, "Invalid Date.");
	}

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($upack[$keys], "num", 1, 20, "Invalid Units Per Pack for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($ppack[$keys], "float", 1, 20, "Invalid Price Per Pack for product number : <b>".($keys+1)."</b>.");
			// $v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");

			if($qty <= 0){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be more than zero. Product number : <b>".($keys+1)."</b>");
			}
			if($upack[$keys] < 1){
				$v->isOk ($upack[$keys], "num", 0, 0, "Error : Units Per Pack must be at least one. Product number : <b>".($keys+1)."</b>");
			}

			if(!isset($novat[$keys])){
				$v->isOk ($svat[$keys], "float", 0, 20, "Invalid vat amount. Product number : <b>".($keys+1)."</b>");
			}

//			if(($vatcodes[$keys] == "0") OR ($vatcodes[$keys] == "00")){
//				$v->addError($vatcodes[$keys],"Invalid Vat Percentage");
//			}

			# Validate ddate[]
			$v->isOk ($d_day[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($d_month[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($d_year[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $d_year[$keys]."-".$d_month[$keys]."-".$d_day[$keys];
			if(!checkdate($d_month[$keys], $d_day[$keys], $d_year[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
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
		$_POST['done'] = "";
		return details($_POST, $err);
	}

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		// code here
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	
	$pur['deptid'] = $sup['deptid'];

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Vat percantege
	$VATP = TAX_VAT;

	# Fix those nasty zeros
	$shipchrg += 0;

	# insert Order to DB
	db_connect();

	$showvat = TRUE;

	# Begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		# get selected stock in this purchase
		db_connect();
		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		while($stkt = pg_fetch_array($stktRslt)){
			# update stock(ordered - qty)
			$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# remove old items
		$sql = "DELETE FROM pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod) && in_array($keys, $remprod)){

				} elseif(isset($accounts[$keys]) && $accounts[$keys] != 0) {
					# get selamt from selected stock
					db_conn('core');
					$Sl = "SELECT * FROM accounts WHERE accid='$accounts[$keys]'";
					$Ri = db_exec($Sl) or errDie("Unable to get account data.");

					$ad = pg_fetch_array($Ri);

					db_conn('cubit');
					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
//						return "Please select the vatcode for all your stock.";
						$_POST['done'] = "";
						return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
					}

					$vd = pg_fetch_array($Ri);
					$VATP = $vd['vat_amount'];

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					# Calculate amount
					//$amt[$keys] = ($qtys[$keys] * $ppack[$keys]);

					if($udiscount[$keys] > 0){
						$discps = round((($udiscount[$keys]/100) * $ppack[$keys]), 2);
					}else {
						$discps = 0;
					}
					$amt[$keys] = sprint($qtys[$keys] * ($ppack[$keys] - $discps));

					# auto
					$qpack[$keys] = $qtys[$keys];
					$unitcost[$keys] = sprint($ppack[$keys]/$upack[$keys]);
					$qtys[$keys] = ($upack[$keys] * $qpack[$keys]);

					$stk['exvat'] = "";

					if(isset($novat[$keys])){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}elseif($vatinc == "yes"){
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}else{
								$vat[$keys] = 0;
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif(isset($svat[$keys]) && strlen($svat[$keys]) < 1){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes'&&$vd['zero']!="Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}elseif($vatinc == "yes"){
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/(100 + $VATP)) * $VATP));
							}else{
								$vat[$keys] = 0;
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif($vatinc == "novat"){
						$vat[$keys] = 0;
					}else{
						if($stk['exvat'] != 'yes'&&$vd['zero']!="Yes"){
							$vat[$keys] = $svat[$keys];
						}else{
							$vat[$keys] = 0;
						}
					}

					if($vatinc != "novat"){
						# Track Vat Changes
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP +  100)) * $VATP));
							}
						}else{
							$vatc[$keys] = 0;
						}
						if($vat[$keys] <> $vatc[$keys]){
							$_POST["vatc"][$keys] = "yes";
						}
					}

					# ddate
					$ddate[$keys] = "$d_year[$keys]-$d_month[$keys]-$d_day[$keys]";

					$wtd = $whids[$keys];
					# insert Order items
					$sql = "
						INSERT INTO pur_items (
							purid, whid, stkid, qty, iqty, unitcost, 
							amt, ddate, qpack, upack, ppack, svat, 
							div, vatcode, description, account, 
							udiscount
						) VALUES (
							'$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
							'$amt[$keys]', '$ddate[$keys]', '$qpack[$keys]', '$upack[$keys]', '$ppack[$keys]', '$vat[$keys]', 
							'".USER_DIV."', '$vatcodes[$keys]', '$descriptions[$keys]', '$accounts[$keys]', 
							'$udiscount[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

					# update stock(ordered + qty)
					//$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					//$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				} else {
					# get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
//						return "Please select the vatcode for all your stock.";
						$_POST['done'] = "";
						return details($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
					}

					$vd = pg_fetch_array($Ri);
					$VATP = $vd['vat_amount'];

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					# Calculate amount
					//$amt[$keys] = ($qtys[$keys] * $ppack[$keys]);

					if($udiscount[$keys] > 0){
						$discps = round((($udiscount[$keys]/100) * $ppack[$keys]), 2);
					}else {
						$discps = 0;
					}
					$amt[$keys] = sprint($qtys[$keys] * ($ppack[$keys] - $discps));

					# auto
					$qpack[$keys] = $qtys[$keys];
					$unitcost[$keys] = sprint($ppack[$keys]/$upack[$keys]);
					$qtys[$keys] = ($upack[$keys] * $qpack[$keys]);

					if(isset($novat[$keys])){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}elseif($vatinc == "yes"){
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}else{
								$vat[$keys] = 0;
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif(isset($svat[$keys]) && strlen($svat[$keys]) < 1){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}elseif($vatinc == "yes"){
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/(100 + $VATP)) * $VATP));
							}else{
								$vat[$keys] = 0;
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif($vatinc == "novat"){
						$vat[$keys] = 0;
					}else{
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							$vat[$keys] = $svat[$keys];
						}else{
							$vat[$keys] = 0;
						}
					}

					if($vatinc != "novat"){
						# Track Vat Changes
						if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
							# If vat is not included
							if($vatinc == "no"){
								$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP +  100)) * $VATP));
							}
						}else{
							$vatc[$keys] = 0;
						}
						if($vat[$keys] <> $vatc[$keys]){
							$_POST["vatc"][$keys] = "yes";
						}
					}

					# ddate
					$ddate[$keys] = "$d_year[$keys]-$d_month[$keys]-$d_day[$keys]";

					$wtd=$whids[$keys];
					# insert Order items
					$sql = "
						INSERT INTO pur_items (
							purid, whid, stkid, qty, iqty, unitcost, 
							amt, ddate, qpack, upack, ppack, svat, 
							div, vatcode, sup_stkcod, 
							udiscount
						) VALUES (
							'$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
							'$amt[$keys]', '$ddate[$keys]', '$qpack[$keys]', '$upack[$keys]', '$ppack[$keys]', '$vat[$keys]', 
							'".USER_DIV."','$vatcodes[$keys]', '".suppStkcod($supid, $stkids[$keys])."', 
							'$udiscount[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

					# update stock(ordered + qty)
					$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
                if (trhSupplierEnabled($supid)) {
                    $trh_done = "<input name='trhSend' type='submit' value='Send with Transheks' />";
                } else {
                    $trh_done = "";
                }

				$_POST["done"] = "&nbsp; $trh_done | &nbsp;<input name='doneBtn' type='submit' value='Done'> 
					 | <input name='invoice' type='submit' value='Receive & Record Invoice'> | <input type='button' onClick=\"window.open('purch-print.php?purid=$purid', 'popup_purch_print','scrollbars=yes, statusbar=no, width=800, height= 600');\" value='Print'> | <input type='submit' name='donePrnt' value='Done, Print and make another'>";
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

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$delvat'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			$Sl = "SELECT * FROM vatcodes";
			$Ri = db_exec($Sl);
		}

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$_POST['showvat'] = $showvat;

		if($vd['zero'] != "Yes") {

			# If vat is not included (delchrg)
			//$VATP = TAX_VAT;
			if($vatinc == "no"){
				$svat = sprint(($VATP/100) * $shipchrg);
				$shipexvat = $shipchrg;
			}elseif($vatinc == "yes"){
				$svat = sprint(($shipchrg/($VATP+100)) * $VATP);
				$shipexvat = ($shipchrg - $svat);
			}else{
				$svat = 0;
				$shipexvat  = $shipchrg;
			}
		} else {
			$svat = 0;
			$shipexvat  = $shipchrg;
		}

		# If there vatable items
		if(isset($vat)){
			$VAT = array_sum($vat);
		}else{
			$VAT = 0;
		}

		# Total
		$TOTAL = ($SUBTOT + $shipexvat);

		# If vat is not included
		if($vatinc == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}else{
			$TOTAL = ($TOTAL + $svat);
			$SUBTOT -= ($VAT);
		}

		$VAT += $svat;

	/* --- End Clac --- */

	# Insert Order to DB
	$sql = "
		UPDATE purchases 
		SET delvat='$delvat', supid='$supid', supinv='$supinv', supname='$sup[supname]', supaddr='$sup[supaddr]', 
			supno='$sup[supno]', terms='$terms', pdate='$pdate', shipchrg='$shipchrg', subtot='$SUBTOT', total='$TOTAL', 
			balance='$TOTAL', vatinc='$vatinc', vat='$VAT', shipping='$shipexvat', ordernum='$ordernum', 
			remarks='$remarks', deptid = '$dept[deptid]' 
		WHERE purid = '$purid' ";
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(isset($invoice)) {
		header("Location: purch-recv.php?purid=$purid&invoice=no");
		exit;
	}

	// Was the Done, Print and make another button pressed?
	if (isset($donePrnt)) {
		$sql = "UPDATE purchases SET done='y' WHERE purid='$purid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit");

		$OUTPUT = "
			<script>
				printer('purch-print.php?purid=$purid');move('purchase-new.php');
			</script>";
		return $OUTPUT;
	}

	if(!(isset($doneBtn) || isset($trhSend))){
		if(isset($wtd)){$_POST['wtd']=$wtd;}
		return details($_POST);
	}else{
		# insert Order to DB
		$sql = "UPDATE purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);
		
		if (isset($trhSend)) {
			header("Location: transheks/order_send.php?key=send&id=$purid");
			exit;
		}

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New Order</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Order from Supplier <b>$sup[supname]</b> has been recorded.</td>
					<td><a href='javascript: printer(\"purch-print.php?purid=$purid\");'>Print Order</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='purchase-view.php'>View Orders</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
		return $write;

	}
}


?>

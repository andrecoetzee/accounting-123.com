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
if (isset($HTTP_GET_VARS["invid"]) && isset($HTTP_GET_VARS["cont"])) {
	$HTTP_GET_VARS["stkerr"] = '0,0';
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
            case "details":
				if(isset($HTTP_POST_VARS["ctyp"]) && $HTTP_POST_VARS["ctyp"] == 'int')
					header("Location: intinvoice-new.php?deptid=$HTTP_POST_VARS[deptid]&letters=$HTTP_POST_VARS[letters]");
				$OUTPUT = details($HTTP_POST_VARS);
				break;

			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
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
	if(PRD_STATE == 'p')
		return "<br><li class=err> - Error : You cannot acces this function when you are using a closed period.<br><br></li>";

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}


	// Layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=details>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>New Invoice</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters maxlength=5></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer type</td><td valign=center><input type=radio size=7 name=ctyp value='loc' checked=yes> Local | <input type=radio size=7 name=ctyp value='int'> International</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $view;
}

# Default view
function view_err($HTTP_POST_VARS, $err = "")
{
	if(PRD_STATE == 'p')
		return "<br><li class=err> - Error : You cannot acces this function when you are using a closed period.<br><br></li>";

	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
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
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=details>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>New Invoice</th></tr>
		<tr><td colspan=2>$err</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters value='$letters' maxlength=5></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer type</td><td valign=center><input type=radio size=7 name=ctyp value='loc' checked=yes> Local | <input type=radio size=7 name=ctyp value='int'> International</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $view;
}

# create a dummy invoice
function create_dummy($deptid){

	db_connect();
	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;

	# Insert invoice to DB
	$sql = "INSERT INTO invoices(deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, username, printed, done, prd, div)";
	$sql .= " VALUES('$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '".PRD_DB."', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = lastinvid();

	return $invid;
}

# Details
function details($HTTP_POST_VARS, $error="")
{
	if(PRD_STATE == 'p')
		return "<br><li class=err> - Error : You cannot acces this function when you are using a closed period.<br><br></li>";

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}else{
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if(isset($deptid)){
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$deptid' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($HTTP_POST_VARS, $err);
		}
	}

	if(!isset($invid)){
		$invid = create_dummy($deptid);
		$stkerr = "0,0";
	}

	if(!isset($done)){
		$done = "";
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# Check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected Customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND location != 'int' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($HTTP_POST_VARS, $err);
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
	}else{
		$cust = pg_fetch_array($custRslt);

		$sql = "SELECT cusnum, cusname, surname FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
		# Moarn if customer account has been blocked
		if($cust['blocked'] == 'yes'){
			$error .= "<li class=err>Error : Selected customer account has been blocked.";
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

	# Get sales people
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

	# Days drop downs
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

	# Format date
	list($oyear, $omon, $oday) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>STORE</th><th>ITEM NUMBER</th><th>SERIAL NO.</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><th>Remove</th><tr>";

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

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
		if($stk['serd'] == 'yes' && $inv['serd'] == 'n'){
			$sers = ext_getavserials($stkd['stkid']);
			$sernos = "<select class='width : 15' name='sernos[]'>";
			foreach($sers as $skey => $ser){
				$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
			}
			$sernos .= "</select>";
		}else{
			$sernos = "<input type=hidden name=sernos[] value='$stkd[serno]'>$stkd[serno]";
		}

		# Input qty if not serialised
		$qtyin = "<input type=text size=3 name=qtys[] value='$stkd[qty]'>";
		if($stk['serd'] == 'yes'){
			$qtyin = "<input type=hidden size=3 name=qtys[] value='$stkd[qty]'>$stkd[qty]";
		}

		# check permissions
		if(perm("invoice-unitcost-edit.php")){
			$viewcost = "<input type=text size=8 name=unitcost[] value='$stkd[unitcost]'>";
		}else{
			$viewcost = "<input type=hidden size=8 name=unitcost[] value='$stkd[unitcost]'>$stkd[unitcost]";
		}

		# Put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$stkd[whid]'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$sernos</td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td>$qtyin</td><td>$viewcost</td><td><input type=text size=4 name=disc[] value='$stkd[disc]'> OR <input type=text size=4 name=discp[] value='$stkd[discp]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$stkd[amt]'> ".CUR." $stkd[amt]</td><td><input type=checkbox name=remprod[] value='$key'><input type=hidden name=SCROLL value=yes></td></tr>";
		$key++;
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
					$sernos = "<select class='width : 15'name='sernos[]' onChange='javascript:document.form.submit();'>";
					foreach($sers as $skey => $ser){
						$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
					}
					$sernos .= "</select>";
				}else{
					$sernos = "<input type=hidden name=sernos[] value=''>";
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
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				# Input qty if not serialised
				$qtyin = "<input type=text size=3 name=qtys[] value='$qtyss[$key]'>";
				if($stk['serd'] == 'yes'){
					$qtyin = "<input type=hidden size=3 name=qtys[] value='$qtyss[$key]'>$qtyss[$key]";
				}

				# Check permissions
				if(perm("invoice-unitcost-edit.php")){
					$viewcost = "<input type=text size=8 name=unitcost[] value='$stk[selamt]'>";
				}else{
					$viewcost = "<input type=hidden size=8 name=unitcost[] value='$stk[selamt]'>$stk[selamt]";
				}

				# Put in selected warehouse and stock
				$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stk[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$sernos</td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td>$qtyin</td><td>$viewcost</td><td><input type=text size=4 name=disc[] value='$discs[$key]'> OR <input type=text size=4 name=discp[] value='$discps[$key]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$amt[$key]'> ".CUR." $amt[$key]</td><td><input type=checkbox name=remprod[] value='$keyy'></td></tr>";
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

					if(isset($ria)and $ria!="") {$len=strlen($ria);$Wh="AND lower(substr(stkcod,1,'$len'))=lower('$ria')";} else {$Wh="";$ria="";}

					# get stock on this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class=err>There are no stock items in the selected warehouse.";
						continue;
					}
					if (pg_numrows ($stkRslt) == 1) {
						$ex="selected";
					} else {$ex="";}
					$stks = "<select class='width : 15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
					$stks .= "<option value='-S' disabled selected>Select Number</option>";
					$count = 0;
					while($stk = pg_fetch_array($stkRslt)){
						$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
					}
					$stks .= "</select> ";

					# put in drop down and warehouse
					$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whidss[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td> </td><td><input type=hidden size=3 name='qtyss[]'  value='1'>1</td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td><input type=hidden name=amts[] value='0.00'>".CUR." 0.00</td><td></td></tr>";
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
				if(isset($wtd)){$whid=$wtd;}

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				if(isset($ria)and $ria!="") {$len=strlen($ria);$Wh="AND lower(substr(stkcod,1,'$len'))=lower('$ria')";} else {$Wh="";$ria="";}

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $Wh ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!isset($err)){$err="";}
					$err .= "<li>There are no stock items in the selected store.";
					//ontinue;
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
				$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whidss[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td></td><td><input type=hidden size=3 name=qtyss[] value='1'>1</td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
			}else{
				$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$whs</td><td> </td><td></td><td> </td><td> </td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
			}
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$whs</td><td> </td><td></td><td> </td><td> </td><td> </td><td><input type=text size=4 name=discs[] value='0'> OR <input type=text size=4 name=discps[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td><td></td></tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);

	//print "<br>TOTAL:$inv[total]  VAT:$inv[vat]<br>";
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
	if(isset($cusnum) && $cusnum != "-S"){
		#check againg credit limit
		if(($TOTAL + $cust['balance']) > $cust['credlimit']){
			$error .= "<li class=err>Warning : Customers Credit limit of <b>".CUR." $cust[credlimit]</b> has been exceeded";
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

/* -- Final Layout -- */
	$details = "<center><h3>New Invoice</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=invid value='$invid'>
	<input type=hidden name=letters value='$letters'>
	<input type=hidden name=stkerr value='$stkerr'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top width=50%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Account No.</td><td valign=center>$cust[accno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer</td><td valign=center>$customers</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Customer Address</td><td valign=center>".nl2br($cust['addr1'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Order number</td><td valign=center><input type=text size=10 name=cordno value='$inv[cordno]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Vat Number</td><td>$cust[vatnum]</td></tr>
			<tr><th colspan=2>Point of Sale</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Barcode</td><td><input type=text size=13 name=bar value=''></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Type the first letters of the stock code you are looking for.")."><td>Stock Filter</td><td><input type=text size=13 name=ria value='$ria' onkeyup='javasript:predict()'></td></tr>
		</table>
	</td><td valign=top align=right width=50%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Invoice Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Invoice No.</td><td valign=center>TI $inv[invid]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Proforma Inv No.</td><td valign=center><input type=text size=5 name=docref value='$inv[docref]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Sales Order No.</td><td valign=center><input type=text size=5 name=ordno value='$inv[ordno]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td valign=center>Yes <input type=radio size=7 name=chrgvat value='inc' $chin> No<input type=radio size=7 name=chrgvat value='exc' $chex> Excempt From Vat<input type=radio size=7 name=chrgvat value='nov' $chno></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$termssel Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Person</td><td valign=center>$salesps</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Invoice Date</td><td valign=center><table><tr><td><input type=text size=2 name=oday maxlength=2 value='$oday'></td><td>-</td><td><input type=text size=2 name=omon maxlength=2 value='$omon'></td><td>-</td><td><input type=text size=4 name=oyear maxlength=4 value='$oyear'></td><td></tr></table></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Available Credit</td><td>".CUR." $avcred</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Trade Discount</td><td valign=center><input type=text size=5 name=traddisc value='$inv[traddisc]'>%</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charge</td><td valign=center><input type=text size=7 name=delchrg value='$inv[delchrg]'></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100%>
			<tr><th width=25%>Quick Links</th><th width=25%>Comments</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='cust-credit-stockinv.php'>New Invoice</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top><textarea name=comm rows=4 cols=20>$inv[comm]</textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=50%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=SUBTOT value='$SUBTOT'>$SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Different Store'> | <input name=addprodBtn type=submit value='Add Product'> | <input type=submit name='saveBtn' value='Save'> </td><td>| <input type=submit name='upBtn' value='Update'>$done</td></tr>
	</table><a name=bottom>
	</form></center>";

	return $details;
}

# details
function write($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	if (!isset($ria)) {$ria="";}
	$v->isOk ($ria, "string", 0, 20, "Invalid stock code(fist letters).");

	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($docref, "num", 0, 20, "Invalid Document Reference No.");
	$v->isOk ($ordno, "num", 0, 20, "Invalid sales order number.");
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

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check is serai no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && !(strlen($sernos[$keys]) > 0)){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

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
		return details($HTTP_POST_VARS, $err);
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

		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
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
						# $amt[$keys] = (($qtys[$keys] * $unitcost[$keys]) - $disc[$keys]);
						$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));

						# Check Tax Excempt
						if($stk['exvat'] == 'yes'){
							$taxex += $amt[$keys];
						}

						$wtd = $whids[$keys];

						# insert invoice items
						$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, serno, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '$sernos[$keys]','".USER_DIV."')";
						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

						if(strlen($stkt['serno']) > 0)
							ext_resvSer($stkt['serno'], $stk['stkid']);

						# update stock(alloc + qty)
						$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
					}
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

					# Check Tax Excempt
					if($stk['exvat'] == 'yes'){
						$taxex += $amt[$keys];
					}

					$wtd = $whids[$keys];
					# insert invoice items
					$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, serno, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '$sernos[$keys]','".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

					if(strlen($sernos[$keys]) > 0)
						ext_resvSer($sernos[$keys], $stk['stkid']);

					# update stock(alloc + qty)
					$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
				$HTTP_POST_VARS["done"] = " | <input name=doneBtn type=submit value='Print'>";
			}
		}else{
			$HTTP_POST_VARS["done"] = "";
		}

/* --- ----------- Clac --------------------- */

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
		$taxex -= $traddiscmtt;

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

		$TOTAL = sprint($EXVATTOT + $VAT + $taxex);
		$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

		# insert invoice to DB
		$sql = "UPDATE invoices SET cusnum = '$cusnum', deptname = '$dept[deptname]', cusacc = '$cust[accno]', cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', cusvatno = '$cust[vatnum]', cordno = '$cordno', ordno = '$ordno', docref = '$docref',
		chrgvat = '$chrgvat', terms = '$terms', salespn = '$salespn', odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', comm = '$comm', serd = 'y', discount='$traddiscmt', delivery='$delexvat' WHERE invid = '$invid'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM inv_data WHERE invid='$invid'  AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# pu in new data
		$sql = "INSERT INTO inv_data(invid, dept, customer, addr1, div) VALUES('$invid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	if (strlen($bar)>0)
	{

		$Sl = "SELECT * FROM possets WHERE div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

		if (pg_numrows ($Rs) < 1)
		{
			return details($HTTP_POST_VARS,"Please go set the point of sale settings under the stock settings");
		}
		$Dets = pg_fetch_array($Rs);
		if($Dets['opt']=="No")
		{

			switch (substr($bar,(strlen($bar)-1),1)) {
					case "0":
						$tab="ss0";
						break;
					case "1":
						$tab="ss1";
						break;
					case "2":
						$tab="ss2";
						break;
					case "3":
						$tab="ss3";
						break;
					case "4":
						$tab="ss4";
						break;
					case "5":
						$tab="ss5";
						break;
					case "6":
						$tab="ss6";
						break;
					case "7":
						$tab="ss7";
						break;
					case "8":
						$tab="ss8";
						break;
					case "9":
						$tab="ss9";
						break;
					default:
						return details($HTTP_POST_VARS,"The code you selected is invalid");

				}
			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = barext_dbget($tab,'code',$bar,'stock');

			if(!($stid>0)){return details($HTTP_POST_VARS,"The bar code you selected is not in the system or is not available.");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp,ss, div) VALUES('$invid', '$s[whid]', '$stid', '1','$s[selamt]','$s[selamt]','0','0','$bar', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			$Sl = "DELETE FROM ".$tab." WHERE code = '$bar' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}else{
			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = ext_dbget('stock','bar',$bar,'stkid');

			if(!($stid>0)){return details($HTTP_POST_VARS,"The bar code you selected is not in the system or is not available.");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp,ss, div) VALUES('$invid', '$s[whid]', '$stid', '1','$s[selamt]','$s[selamt]','0','0','$bar', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}

	}

/* --- Start button Listeners --- */
	if(isset($doneBtn)){

		# Check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class=err> Error : Invoice number has no items.";
			return details($HTTP_POST_VARS, $error);
		}

		# Insert quote to DB
		$sql = "UPDATE invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);

		# Print the invoice
		$OUTPUT = "<script>printer('invoice-print.php?invid=$invid');move('main.php');</script>";
		require("template.php");


	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>New Invoice Saved</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Invoice for customer <b>$cust[cusname] $cust[surname]</b> has been saved.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
		return $write;
	}else{
		if(isset($wtd)){$HTTP_POST_VARS['wtd']=$wtd;}
		if(strlen($ria)>0){$HTTP_POST_VARS['ria']=$ria;}
		return details($HTTP_POST_VARS);
	}
/* --- End button Listeners --- */
}
?>

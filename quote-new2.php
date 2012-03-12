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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "details":
				if(isset($_POST["doneBtn"])){
					$OUTPUT = write($_POST);
				}else{
					$OUTPUT = details($_POST);
				}
				break;

            default:
				$OUTPUT = view();
			}
} else {
	$OUTPUT = view();
}

# get templete
require("template.php");

# Default view
function view()
{

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments ORDER BY deptname ASC";
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


	//layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=details>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>New Quote</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters maxlength=5></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        return $view;
}

# Default view
function view_err($_POST, $err = "")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
	}else{
		$depts = "<select name='deptid'>";
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
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=details>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>New Quote</th></tr>
		<tr><td colspan=2>$err</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters value='$letters' maxlength=5></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        return $view;
}

# details
function details($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	if(!isset($cussel)){
		$v->isOk ($cusnum, "num", 1, 20, "Please Select Customer.");
		if(isset($selected)){
			$v->isOk ($ordno, "num", 0, 20, "Invalid order number.");
			$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
			$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
			$v->isOk ($oday, "num", 1, 2, "Invalid Quote Date day.");
			$v->isOk ($omon, "num", 1, 2, "Invalid Quote Date month.");
			$v->isOk ($oyear, "num", 1, 5, "Invalid Quote Date year.");
			# $v->isOk ($disc, "float", 0, 20, "Invalid Discount.");
			# $v->isOk ($discp, "float", 0, 20, "Invalid Discount percentage.");
			$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
			$v->isOk ($cordno, "num", 0, 20, "Invalid Customer Order Number.");
			$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
			$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
			$odate = $oyear."-".$omon."-".$oday;
	        if(!checkdate($omon, $oday, $oyear)){
    	    	$v->isOk ($odate, "num", 1, 1, "Invalid Quote Date.");
        	}


			# check quantities and discounts
			if(isset($qtys)){
				foreach($qtys as $keys => $qty){
					$v->isOk ($qty, "num", 0, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
					$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
					$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
					$v->isOk ($unitcost[$keys], "float", 0, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
					if($qty < 1){
						$v->isOk ($error, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
					}
				}

				# everything is set place done button
				$done = " | <input name=doneBtn type=submit value='Done'>";
			}else{
				# no done button
				$done = "";
			}
		}else{
			# no done button
			$done = "";
		}

		# display errors, if any
		$err = "";
		if ($v->isError ()) {
			# theres an error?? remove done button
			$done = "";

			$errors = $v->getErrors();

			foreach ($errors as $e) {
				$err .= "<li class=err>".$e["msg"];
			}
			# $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			# return $confirm;
		}
	}else{
		$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");

		# no done button
		$done = "";

		# display errors, if any
		$err = "";
		if ($v->isError ()) {
			$errors = $v->getErrors();

			foreach ($errors as $e) {
				$err .= "<li class=err>".$e["msg"];
			}
			return view_err($_POST, $err);
		}
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	# Check if must select customer (is yes, make some vars void)
	if(isset($cussel)){
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$deptid' AND lower(surname) LIKE lower('$letters%') ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($_POST, $err);
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='-S' disabled selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}

		# take care of the uset vars
		$cust['addr1'] = "";
		$cust['addr2'] = "";
		$cust['addr3'] = "";
		$cust['cusnum'] = "";
		$cust['vatnum'] = "";
		$cust['traddisc'] = "";
		$terms = "0";
		$ordno = "";
		$chrgvat = "yes";
		$salespn = "";
		$oday = date("d");
		$omon = date("m");
		$oyear = date("Y");
		$disc = "0.00";
		$discp = "0.00";
		$delchrg = "0.00";
		$cordno = "";
		$comm = "";
		$traddisc = 0;
	}elseif($cusnum == "-S" || strlen($cusnum) < 1){
		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$deptid' AND lower(cusname) LIKE lower('$letters%') ORDER BY cusname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($_POST, $err);
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='-S' disabled selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}

		# take care of the uset vars
		$cust['addr1'] = "";
		$cust['addr2'] = "";
		$cust['addr3'] = "";
		$cust['cusnum'] = "";
		$cust['vatnum'] = "";
		$cust['traddisc'] = "";
		$terms = "0";
		$ordno = "";
		$chrgvat = "yes";
		$salespn = "";
		$oday = date("d");
		$omon = date("m");
		$oyear = date("Y");
		$disc = "0.00";
		$discp = "0.00";
		$delchrg = "0.00";
		$cordno = "";
		$terms = "";
		$traddisc = 0;
	}else{
		# Get selected customer info
		db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		if (pg_numrows ($custRslt) < 1) {
			return "<li class=err>Invalid Customer Number.";
		}
		$cust = pg_fetch_array($custRslt);
		# moarn if customer account has been blocked
		if($cust['blocked'] == 'yes'){
			return "<li class=err>Error : Selected customer account has been blocked.";
		}
		$customers = "<input type=hidden name=selected value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
	}

	/* --- Start Drop Downs --- */
	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whids[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class=err> There are no Warehouses found in Cubit.";
	}else{
			$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
			while($wh = pg_fetch_array($whRslt)){
					$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
	}
	$whs .="</select>";

	# get sales people
	db_conn("exten");
	$sql = "SELECT * FROM salespeople ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class=err> There are no Sales People found in Cubit.";
	}else{
		$salesps = "<select name='salespn'>";
		while($salesp = pg_fetch_array($salespRslt)){
			if($salesp['salesp'] == $salespn){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$salesps .= "<option value='$salesp[salesp]' $sel>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $terms);

	# keep the charge vat option stable
	if($chrgvat == "yes"){
		$chy = "checked=yes";
		$chn = "";
	}else{
		$chy = "";
		$chn = "checked=yes";
	}

	/* --- End Drop Downs --- */

	/*--- Start My Event Listeners ---*/

	#if the Add button was clicked add one more product
	if(isset($addprodBtn)){
		# check if setting exists
		db_connect();
		$sql = "SELECT value FROM set WHERE label = 'DEF_WH'";
		$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
		if (pg_numrows ($Rslt) > 0) {
			$set = pg_fetch_array($Rslt);
			$whid = $set['value'];

			# get selected warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$whid'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get stock on this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE whid = '$whid' ORDER BY stkcod ASC";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				$err .= "<li>There are no stock items in the selected warehouse.";
				continue;
			}
			$stks = "<select name='stkids[]' onChange='javascript:document.form.submit();'>";
			$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
			$count = 0;
			while($stk = pg_fetch_array($stkRslt)){
				$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
			}
			$stks .= "</select> ";

			$moreprod = "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td><input type=text size=4 name=qtys[] value='1'></td><td><input type=text size=4 name=unitcost[] value='0'></td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td></tr>";
			$done = "";
		}else{
			$done = "";
			$moreprod = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$whs</td><td> </td><td> </td><td> </td><td> </td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td></tr>";
		}
	}else{
		$moreprod ="";
	}

	# if different warehouse is pressed
	if(isset($diffwhBtn)){
		$moreprod = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$whs</td><td> </td><td> </td><td> </td><td> </td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td></tr>";
		$done = "";
	}

	# if the customer was just selected
	if(!isset($selected)){
		$traddisc = $cust['traddisc'];
	}
	/* --- End My Event Listeners --- */

	/* --- Start Products Display --- */

	# check if stock warehouse was selected
	if(isset($whids)){
		$products = "
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
		<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><th>Remove</th><tr>";

		foreach($whids as $key => $whid){
			# skip if not selected
			if($whid == "-S"){
				continue;
			}

			if(isset($stkids[$key]) && $stkids[$key] != "-S"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$key]' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				/* -- Start Some Checks -- */
					# check stock availability
					if(($stk['units'] - $stk['alloc']) < 1){
					}

					# check if they are selling too much
					if(($stk['units'] - $stk['alloc']) < $qtys[$key]){
						$err .= "<li class=err>Warnign :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
					}
				/* -- End Some Checks -- */

				# check the unitcost
				if($unitcost[$key] < 1){
					$unitcost[$key] = $stk['selamt'];
				}

				# Calculate the Discount discount
				if($disc[$key] < 1){
					if($discp[$key] > 0){
						$disc[$key] = round((($discp[$key]/100) * $unitcost[$key]), 2);
					}
				}else{
					$discp[$key] = round((($disc[$key] * 100) / $unitcost[$key]), 2);
				}

				# Calculate amount
				$amt[$key] = (($qtys[$key] * $unitcost[$key]) - $disc[$key]);

				# if product must be removed skip it
				if(isset($remprod)){
					if(in_array($key, $remprod)){
						# skip product (wonder if $keys still align)
						$products .="";
					}else{
						# put in selected warehouse and stock
						$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a> (".($stk['units'] - $stk['alloc']).")</td><td>$stk[stkdes]</td><td><input type=text size=4 name=qtys[] value='$qtys[$key]'></td><td><input type=text size=4 name=unitcost[] value='$unitcost[$key]'></td><td><input type=text size=4 name=disc[] value='$disc[$key]'> OR <input type=text size=4 name=discp[] value='$discp[$key]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$amt[$key]'> ".CUR." $amt[$key]</td><td><input type=checkbox name=remprod[] value='$key'></td></tr>";
					}
				}else{
					# put in selected warehouse and stock
					$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a> (".($stk['units'] - $stk['alloc']).")</td><td>$stk[stkdes]</td><td><input type=text size=4 name=qtys[] value='$qtys[$key]'></td><td><input type=text size=5 name=unitcost[] value='$unitcost[$key]'></td><td><input type=text size=4 name=disc[] value='$disc[$key]'> OR <input type=text size=4 name=discp[] value='$discp[$key]' maxlength=5>%</td><td><input type=hidden name=amt[] value='$amt[$key]'> ".CUR." $amt[$key]</td><td><input type=checkbox name=remprod[] value='$key'></td></tr>";
				}
			}else{
				# get warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					$err .= "<li>There are no stock items in the selected warehouse.";
					continue;
				}
				$stks = "<select name='stkids[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
					$count++;
				}
				$stks .= "</select> ";
				$done = "";
				# put in drop down and warehouse
				$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td><input type=text size=4 name='qtys[]'  value='1'></td><td><input type=text size=4 name=unitcost[] value='0'></td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td><input type=hidden name=amt[] value='0.00'>".CUR." 0.00</td><td></td></tr>";
			}
		}
		$products .= "$moreprod</table>";
	}else{
		# check if setting exists
		db_connect();
		$sql = "SELECT value FROM set WHERE label = 'DEF_WH'";
		$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
		if (pg_numrows ($Rslt) > 0) {
			$set = pg_fetch_array($Rslt);
			$whid = $set['value'];

			# get selected warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$whid'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get stock on this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE whid = '$whid' ORDER BY stkcod ASC";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				$err .= "<li>There are no stock items in the selected warehouse.";
				continue;
			}
			$stks = "<select name='stkids[]' onChange='javascript:document.form.submit();'>";
			$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
			$count = 0;
			while($stk = pg_fetch_array($stkRslt)){
				$stks .= "<option value='$stk[stkid]'>$stk[stkcod]</option>";
   }
			$stks .= "</select> ";

			$products ="
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
				<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$whid'>$wh[whname]</td><td>$stks</td><td> </td><td><input type=text size=4 name=qtys[] value='1'></td><td><input type=text size=4 name=unitcost[] value='0'></td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td></tr>
			</table>";
		}else{
			$products ="
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
				<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>UNIT DISCOUNT</th><th>AMOUNT</th><tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td>$whs</td><td> </td><td> </td><td> </td><td> </td><td><input type=text size=4 name=disc[] value='0'> OR <input type=text size=4 name=discp[] value='0' maxlength=5>%</td><td>".CUR." 0.00</td></tr>
			</table>";
		}
		$done = "";
	}
	/* --- End Products Display --- */

	/* --- Start Some calculations --- */

	# Calculate subtotal
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

	# minus discount
	# $SUBTOT -= $disc; --> already minused

	# duplicate
	$SUBTOTAL = $SUBTOT;

	# minus trade discount
	$SUBTOTAL -= $traddiscm;

	# add del charge
	$SUBTOTAL += $delchrg;


	# if vat must be charged
	if($chrgvat == "yes"){
		$VATP = TAX_VAT;
		$VAT = sprintf("%01.2f", (($VATP/100) * $SUBTOTAL));
	}else{
		$VATP = 0;
		$VAT = "0.00";
	}

	# total
	$TOTAL = $SUBTOTAL + $VAT;

	/* --- End Some calculations --- */

	# quote number
	$quono = pglib_lastid("quotes","quoid");
	$quono++;

	/* -- Final Layout -- */
	$details = "<center>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details>
	<input type=hidden name=deptid value='$deptid'>
	<input type=hidden name=cusnum value='$cust[cusnum]'>
	<input type=hidden name=letters value='$letters'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td>$err</td></tr>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$deptname</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer</td><td valign=center>$customers</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Customer Address</td><td valign=center>".nl2br($cust['addr1'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Vat Number</td><td>$cust[vatnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Order number</td><td valign=center><input type=text size=10 name=cordno value='$cordno'></td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center><textarea name=comm rows=4 cols=20>$comm</textarea></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Quote Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Quote No.</td><td valign=center>$quono</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order No.</td><td valign=center><input type=text size=5 name=ordno value='$ordno'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Charge VAT</td><td valign=center>Yes <input type=radio size=7 name=chrgvat value='yes' $chy> No<input type=radio size=7 name=chrgvat value='no' $chn></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$termssel Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Person</td><td valign=center>$salesps</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Quote Date</td><td valign=center><input type=text size=2 name=oday maxlength=2 value='$oday'>-<input type=text size=2 name=omon maxlength=2 value='$omon'>-<input type=text size=4 name=oyear maxlength=4 value='$oyear'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td valign=center><input type=text size=7 name=traddisc value='$traddisc'>%</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td valign=center><input type=text size=7 name=delchrg value='$delchrg'></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=SUBTOT value='$SUBTOT'>$SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Trade Discount</td><td align=right>".CUR." $traddiscm</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charge</td><td align=right>".CUR." $delchrg</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT @ $VATP%</td><td align=right>".CUR." <input type=hidden name=vat value='$VAT'>$VAT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><th>GRAND TOTAL</th><td align=right>".CUR." <input type=hidden name=total value='$TOTAL'>$TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Different Warehouse'> | <input name=addprodBtn type=submit value='Add Product'></td><td><input type=submit name='updateBtn' value='Update'>$done</td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	#get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	$v->isOk ($cordno, "num", 0, 20, "Invalid Customer Order Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "num", 0, 20, "Invalid order number.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($oday, "num", 1, 2, "Invalid Quote Date day.");
	$v->isOk ($omon, "num", 1, 2, "Invalid Quote Date month.");
	$v->isOk ($oyear, "num", 1, 5, "Invalid Quote Date year.");
	$odate = $oyear."-".$omon."-".$oday;
	if(!checkdate($omon, $oday, $oyear)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Quote Date.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($vat, "float", 0, 20, "Invalid Vat Amount.");
	$v->isOk ($total, "float", 0, 20, "Invalid Grand Total.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($error, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Quantity.");
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Warehouse number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Warehouse number, please enter all details.");
	}
	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Amount, please enter all details.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($_POST);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		return details($_POST);
	}
	$cust = pg_fetch_array($custRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return details($_POST);
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# insert quote to DB
	db_connect();

	# begin inserting
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# insert quote to DB
		$sql = "INSERT INTO quotes(deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, accepted)";
		$sql .= " VALUES('$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', 'n')";
		$rslt = db_exec($sql) or errDie("Unable to insert quote to Cubit.",SELF);

		# get next ordnum
		$quoid = pglib_lastid ("quotes", "quoid");

		foreach($qtys as $keys => $value){
			# Zeros
			$disc[$keys] += 0;
			$discp[$keys] += 0;

			# insert quote items
			$sql = "INSERT INTO quote_items(quoid, whid, stkid, qty, unitcost, amt, disc, discp) VALUES('$quoid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$disc[$keys]', '$discp[$keys]')";
			$rslt = db_exec($sql) or errDie("Unable to insert quote items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		$sql = "INSERT INTO quote_data(quoid, dept, customer, addr1, addr2, addr3) VALUES('$quoid', '$dept[deptname]', '$cust[cusname] $cust[surname]', '$cust[addr1]', '$cust[addr2]', '$cust[addr3]')";
		$rslt = db_exec($sql) or errDie("Unable to insert quote data to Cubit.",SELF);

	# commit inserting
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>New Quote</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Quote for customer <b>$cust[cusname] $cust[surname]</b> has been recorded.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='quote-view.php'>View Quotes</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

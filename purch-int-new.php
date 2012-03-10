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
if (isset($HTTP_GET_VARS["purid"]) && isset($HTTP_GET_VARS["cont"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "details":
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

	global $HTTP_GET_VARS;

	//$HTTP_GET_VARS["noduty"]="y";

	if(isset($HTTP_GET_VARS["noduty"])) {
		$ex = "<input type='hidden' name='noduty' value='yes'>";
	} else {
		$ex = "";
	}

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

	//layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			$ex
			<tr>
				<th colspan='2'>New International Order</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of Supplier</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='Order-view.php'>View Orders</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}



# Default view
function view_err($HTTP_POST_VARS, $err = "")
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(isset($noduty)) {
		$ex = "<input type='hidden' name='noduty' value='yes'>";
	} else {
		$ex = "";
	}

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
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

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			$ex
			<tr>
				<th colspan='2'>New International Order</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of Supplier</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='Order-view.php'>View Orders</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
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
	$supaddr = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
//	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";
	$fcid = getDef_fcid();
	$curr = "R";
	$xrate = 1;
	$tax = 0;
	$duty = 0;

	$purnum = divlastid ("pur", USER_DIV);

	$fcid = getDef_fcid();
	$curr = getSymbol($fcid);
	$xrate = getRate($fcid);

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
		INSERT INTO purch_int (
			deptid, supid, supaddr, terms, pdate, ddate, xrate, fcid, curr, tax, 
			shipchrg, duty, subtot, total, balance, remarks, received, done, prd, div, 
			purnum
		) VALUES (
			'$deptid', '$supid',  '$supaddr', '$terms', '$pdate', '$ddate', '$xrate', '$fcid', '$curr[symbol]', '$tax', 
			'$shipchrg', '$duty', '$subtot', '$total', '$total', '$remarks', 'n', 'n', '".PRD_DB."', '".USER_DIV."', 
			'$purnum'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

	# Get next ordnum
	$purid = pglib_lastid ("purch_int", "purid");
	return $purid;

}



# details
function details($HTTP_POST_VARS, $error="")
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($noduty)) {
		$exd = "<input type='hidden' name='noduty' value='yes'>";
	} else {
		$exd = "";
	}

	# Validate input
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

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$deptid = $pur['deptid'];
	$supid = $pur['supid'];

	# Get selected supplier info
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
		$sql = "SELECT * FROM suppliers WHERE $searchdept location = 'int' AND lower(supname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier names starting with <b>$letters</b> in database.</li>";
			return view_err($HTTP_POST_VARS, $err);
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
		$fcid = $pur['fcid'];
	}else{
		db_connect();
		# Query server for supplier info
		$sql = "SELECT * FROM suppliers WHERE deptid = '$deptid' AND location = 'int' AND lower(supname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier names starting with <b>$letters</b> in database.</li>";
			return view_err($HTTP_POST_VARS, $err);
		}else{
			$suppliers = "<select name='supid' onChange='javascript:document.form.submit();'>";
			$sel = "";
			$fcid = $pur['fcid'];
			while($sup = pg_fetch_array($supRslt)){
				if($sup['supid'] == $supid){
					$sel = "selected";
					$supaddr = "$sup[supaddr]";
					$accno = $sup['supno'];
					$fcid = $sup['fcid'];
					$listid = $sup['listid'];
				}else{
					$sel = "";
					$supaddr = "";
					$accno = "";
				}
				$suppliers .= "<option value='$sup[supid]' $sel>$sup[supname]</option>";
			}
			$suppliers .= "</select>";
		}
	}

	# this is a quick fix for pricelist product avaibility
	$listids = array();
	if(isset($listid) && $listid > 0){
		# Get jobs stkids
		db_conn("exten");
		$sql = "SELECT stkid FROM splist_prices WHERE listid = '$listid' AND div = '".USER_DIV."'";
		$lstkRslt = db_exec($sql);
		while($lstk = pg_fetch_array($lstkRslt)){
			$listids[] = $lstk['stkid'];
		}
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# Days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# Currency
	$currs = getSymbol($fcid);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	# Format date
	list($ipur_year, $ipur_month, $ipur_day) = explode("-", $pur['pdate']);
	list($del_year, $del_month, $del_day) = explode("-", $pur['ddate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	if(isset($noduty)) {
		$dd = "";
	} else {
		$dd = "<th colspan='2'>DUTY</th>";
	}

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan=2>UNIT PRICE</th>
				$dd
				<th>LINE TOTAL</th>
				<th>COST PER UNIT</th>
				<th>DEL</th>
			<tr>";

	# Get selected stock in this Order
	db_connect();

	$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
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

	/* -- Calculations -- */

		# Calculate cost amount bought
		$totamt = ($stkd['qty'] * $stkd['cunitcost']);
		$unittot = $totamt;

		# Calculate percentage from subtotal
		if($pur['subtot'] <> 0){
			$perc = ((($totamt + $stkd['duty']) / $pur['subtot']) * 100);
		}else{
			$perc = 0;
		}

		# Get percentage from shipping charges
		$shipchrg = sprint(($perc / 100) * $pur['shipchrg']);

		# Add shipping charges to amt
		$totamt = sprint($totamt + $shipchrg + $stkd['duty']);
		$unittot = sprint($unittot + $stkd["duty"]);

		$lineamt = sprint($totamt / $stkd["qty"]);
		$unitamt = sprint($unittot / $stkd['qty']);

	/* -- End Calculations --*/

		$stkd['amt'] = sprint($stkd['amt']);

		if(isset($noduty)) {
			$dd = "
				<input type='hidden' name='duty[]' value='$stkd[duty]'>
				<input type='hidden' name='dutyp[]' value='$stkd[dutyp]'>";
		} else {
			$dd = "
				<td nowrap>$pur[curr] <input type='text' size='6' name='duty[]' value='$stkd[duty]'> or </td>
				<td><input type='text' size='3' name='dutyp[]' value='$stkd[dutyp]'>%</td>";
		}

		# Put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
				<td>
					<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
					<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
				</td>
				<td>$stk[stkdes]</td>
				<td><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td nowrap>$pur[curr] <input type='text' size='6' name='cunitcost[]' value='$stkd[cunitcost]'><b> OR </b></td>
				<td nowrap>".CUR." <input type='text' size='6' name='unitcost[]' value='$stkd[unitcost]'></td>
				$dd
				<td nowrap><input type='hidden' name='amt[]' value='$stkd[amt]'> $pur[curr] $lineamt</td>
				<td align='right' nowrap>$pur[curr] $unitamt</td>
				<td>
					<input type='checkbox' name='remprod[]' value='$key'>
					<input type='hidden' name='SCROLL' value='yes'>
				</td>
			</tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	if($pur['xrate'] == 0)
		$pur['xrate'] = 1;


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
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Calculate amount
				$amt[$key] = sprint(($qtyss[$key] * $stk['lcsprice'])/$pur['xrate']);
				$cunitcost[$key] = sprint($pur['xrate'] * $stk['lcsprice']);

				if(isset($noduty)) {
					$dd = "
						<input type='hidden' name='duty[]' value='0'>
						<input type='hidden' name='dutyp[]' value='0'>";
				} else {
					$dd = "
						<td>$pur[curr] <input type='text' size='6' name='duty[]' value='0'> or </td>
						<td><input type='text' size='3' name='dutyp[]' value='0'>%</td>";
				}

				# put in selected warehouse and stock
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
						<td>
							<input type='hidden' name='stkids[]' value='$stk[stkid]'>
							<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
						</td>
						<td>$stk[stkdes]</td>
						<td><input type='text' size='3' name='qtys[]' value='$qtyss[$key]'></td>
						<td nowrap>$pur[curr] <input type='text' size='6' name='cunitcost[]' value='$stkd[cunitcost]'> or </td>
						<td nowrap>".CUR." <input type='text' size='6' name='unitcost[]'  value='$stk[lcsprice]'></td>
						$dd
						<td nowrap><input type='hidden' name='amt[]' value='$amt[$key]'> $pur[curr] $amt[$key]</td>
						<td>&nbsp;</td>
						<td>
							<input type='checkbox' name='remprod[]' value='$keyy'>
							<input type='hidden' name='SCROLL' value='yes'>
						</td>
					</tr>";
				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					# Skip if not selected
					if($whid == "-S"){
						continue;
					}

					# Get warehouse name
					db_conn("exten");

					$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					# Get stock on this warehouse
					db_connect();

					$sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class='err'>There are no stock items in the selected warehouse.</li>";
						continue;
					}
					$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
					$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
					$count = 0;
					while($stk = pg_fetch_array($stkRslt)){
						$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
					}
					$stks .= "</select> ";

					if(isset($noduty)) {
						$dd = "";
					} else {
						$dd = "<td>&nbsp</td><td>&nbsp;</td>";
					}

					# Put in drop down and warehouse
					$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
							<td>$stks</td>
							<td>&nbsp;</td>
							<td><input type='text' size='3' name='qtyss[]' value='1'></td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							$dd
							<td nowrap><input type='hidden' name='amts[]' value='0.00'>$pur[curr] 0.00</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>";
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

				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!(isset($err))) {$err="";}
					$err .= "<li>There are no stock items in the selected warehouse.</li>";
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";

				if(isset($noduty)) {
					$dd = "";
				} else {
					$dd = "<td>$pur[curr] 0.00</td><td></td>";
				}

				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
						<td>$stks</td>
						<td>&nbsp;</td>
						<td><input type='text' size='3' name='qtyss[]' value='1'></td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						$dd
						<td>&nbsp;</td>
					</tr>";
			}else{
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$whs</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>$pur[curr] 0.00</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>";
			}
		}
	}

/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		# take todays date
		list($year, $mon, $day) = explode("-", $pur['pdate']);
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$whs</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>$pur[curr] 0.00</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>";
	}

/* -- End Listeners -- */


	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	$pur['tax'] = sprint($pur['tax']);

	$pur['shipchrg'] = sprint($pur['shipchrg']);

/* --- End Some calculations --- */

	$pur['jobnum'] += 0;

	if($pur['jobnum'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$pur['jobnum'] = $vd['id'];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $pur['jobnum']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	if (isset ($diffwhBtn) OR isset ($upBtn) OR isset ($doneBtn) OR isset ($donePrnt)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details = "
		<center>
		<h3>New International Order</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='deptid' value='$deptid'>
			<input type='hidden' name='letters' value='$letters'>
			$exd
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$suppliers</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$accno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Purchase No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("ipur",$ipur_year,$ipur_month,$ipur_day)." </td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Foreign Currency</td>
							<td valign='center'>$currsel &nbsp;&nbsp;Exchange rate ".CUR." <input type='text' size='7' name='xrate' value='$pur[xrate]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax</td>
							<td valign='center'>$pur[curr] <input type='text' size='7' name='tax' value='$pur[tax]'>$Vatcodes</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Shipping Charges</td>
							<td valign='center' nowrap>$pur[curr] <input type='text' size='7' name='shipchrg' value='$pur[fshipchrg]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Date</td>
							<td valign=center>".mkDateSelect("del",$del_year,$del_month,$del_day)."</td>
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
							<td bgcolor='".bgcolorg()."'><a href='purch-int-view.php'>View International Orders</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>$pur[curr] <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Shipping Charges</td>
							<td align='right' nowrap>$pur[curr] $pur[shipchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax </td>
							<td align='right' nowrap>$pur[curr] $pur[tax]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>$pur[curr] <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input name='diffwhBtn' type='submit' value='Different Store'> |</td>
				<td nowrap><input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";
	return $details;

}



# details
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($noduty)) {
		$HTTP_POST_VARS["noduty"]="yes";
	} else {
		$exd = "";
	}

	# Validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");
	$v->isOk ($supid, "num", 1, 20, "Please Select Supplier.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($ipur_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($ipur_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($ipur_year, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($del_day, "num", 1, 2, "Invalid Delivery Date day.");
	$v->isOk ($del_month, "num", 1, 2, "Invalid Delivery Date month.");
	$v->isOk ($del_year, "num", 1, 5, "Invalid Delivery Date year.");
	//$v->isOk ($curr, "string", 1, 20, "Invalid Foreign currency.");
	$v->isOk ($xrate, "float", 1, 20, "Invalid Exchange Rate.");
	$v->isOk ($tax, "float", 0, 20, "Invalid Tax.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Shipping Charges.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$pdate = mkdate($ipur_year, $ipur_month, $ipur_day);
	$v->isOk ($pdate, "date", 1, 1, "Invalid purchase date.");
	$ddate = mkdate($del_year, $del_month, $del_day);
	$v->isOk ($ddate, "date", 1, 1, "Invalid delivery date.");

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 0, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($cunitcost[$keys], "float", 0, 20, "Invalid Foreign currency Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($duty[$keys], "float", 0, 20, "Invalid Duty Charges for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($dutyp[$keys], "float", 0, 20, "Invalid Duty Charges Percentage for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}

			# Nasty Zeros
			$unitcost[$keys] += 0;
			$cunitcost[$keys] += 0;
			$duty[$keys] += 0;
			$dutyp[$keys] += 0;
		}
	}

	# Check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	# Check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# Check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	# Display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$HTTP_POST_VARS['done'] = "";
		return details($HTTP_POST_VARS, $err);
	}



	# Get Order info
	db_connect();

	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

	# If supplier was just selected/changed, get the following
	if($pur['supid'] != $supid){
		$xrate = getRate($sup['fcid']);
	}

	# currency
	$currs = getSymbol($sup['fcid']);
	$curr = $currs['symbol'];

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$xrate += 0;
	if($xrate == 0) $xrate = 1;
	$shipchrg += 0;
	$tax += 0;

	# insert Order to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* -- Start remove old items -- */
	# get selected stock in this Order
	$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);

	while($stkt = pg_fetch_array($stktRslt)){
		# update stock(ordered - qty)
		$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
	}

	# remove old items
	$sql = "DELETE FROM purint_items WHERE purid='$purid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

	/* -- End remove old items -- */

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

					# Calculate the unitcost
					if($cunitcost[$keys] > 0){
						$unitcost[$keys] = round(($cunitcost[$keys] * $xrate), 2);
					}else{
						$cunitcost[$keys] = round(($unitcost[$keys]/$xrate), 2);
					}

					# Calculate the duty amount
					if($duty[$keys] < 1){
						if($dutyp[$keys] > 0){
							$duty[$keys] = round(((($dutyp[$keys]/100) * $cunitcost[$keys])/$xrate), 2);
						}
					}else{
						if($unitcost[$keys] > 0){
							$dutyp[$keys] = round(((($duty[$keys] * 100) / $cunitcost[$keys]) * $xrate), 2);
						}else{
							$dutyp[$keys] = 0;
						}
					}

					# Calculate amount
					$amt[$keys] = (($qtys[$keys] * $cunitcost[$keys]) + $duty[$keys]);

					# insert Order items
					$sql = "INSERT INTO purint_items(purid, whid, stkid, qty, unitcost, cunitcost, duty, dutyp, amt, ddate, recved, div) VALUES('$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$cunitcost[$keys]', '$duty[$keys]', '$dutyp[$keys]', '$amt[$keys]', '$ddate', 'n', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

					# update stock(ordered + qty)
					$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
			}else{
				# Get csprice from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Calculate the unitcost
				if($cunitcost[$keys] > 0){
					$unitcost[$keys] = round(($cunitcost[$keys] * $xrate), 2);
				}else{
					$cunitcost[$keys] = round(($unitcost[$keys]/$xrate), 2);
				}

				# Calculate the duty amount
				if($duty[$keys] < 1){
					if($dutyp[$keys] > 0){
						$duty[$keys] = round(((($dutyp[$keys]/100) * $unitcost[$keys])/$xrate), 2);
					}
				}else{
					if($unitcost[$keys] > 0){
						$dutyp[$keys] = round(((($duty[$keys] * 100) / $unitcost[$keys])*$xrate), 2);
					}else{
						$dutyp[$keys] = 0;
					}
				}

				# Calculate amount
				$amt[$keys] = (($qtys[$keys] * $cunitcost[$keys]) + $duty[$keys]);

				# Insert Order items
				$sql = "
					INSERT INTO purint_items (
						purid, whid, stkid, qty, unitcost, 
						cunitcost, duty, dutyp, amt, ddate, recved, div
					) VALUES (
						'$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', 
						'$cunitcost[$keys]', '$duty[$keys]', '$dutyp[$keys]', '$amt[$keys]', '$ddate', 'n', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

				# Update stock(ordered + qty)
				$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}
			# Everything is set place done button
			$HTTP_POST_VARS["done"] = "&nbsp; | &nbsp;<input name='doneBtn' type='submit' value='Done'>
			&nbsp; | &nbsp;<input type='submit' name='donePrnt' value='Done, Print and make another'>";
		}
	}else{
		$HTTP_POST_VARS["done"] = "";
	}

	/* --- Clac --- */
	# Calculate subtot
	if(isset($amt)){
		$SUBTOT = array_sum($amt);
	}else{
		$SUBTOT = 0.00;
	}

	# shipchrg is in for curr
	$fshipchrg = $shipchrg;
		// $shipchrg = ($shipchrg * $xrate);

	# total
	$TOTAL = sprint($SUBTOT + $shipchrg + $tax);

	# total Duty
	if(isset($duty)){
		$dutytot = sprint(array_sum($duty));
	}else{
		$dutytot = "0.00";
	}

	# Local Totals
	$LTOTAL = sprint($TOTAL * $xrate);
	$LSUBTOT = sprint($SUBTOT * $xrate);

	/* --- End Clac --- */


	# Insert Order to DB
	$sql = "
		UPDATE purch_int 
		SET supid = '$supid', supaddr = '$sup[supaddr]', terms = '$terms', pdate = '$pdate', ddate = '$ddate',
			fcid = '$sup[fcid]', currency = '$curr', curr = '$curr', tax = '$tax', xrate = '$xrate', 
			fshipchrg = '$fshipchrg', shipchrg = '$shipchrg', duty = '$dutytot', subtot = '$SUBTOT',
			total = '$TOTAL', balance = '$TOTAL', fsubtot = '$LSUBTOT', fbalance = '$LTOTAL', remarks = '$remarks',
			jobnum='$delvat', deptid = '$dept[deptid]' 
		WHERE purid = '$purid'";
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	# Commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$lastid = pglib_lastid("purch_int", "purid");

	// Was Done, Print and make another selected
	if (isset($donePrnt)) {
		$sql = "UPDATE purch_int SET done='y' WHERE purid='$purid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.");

		$OUTPUT = "<script>printer('purch-int-det.php?purid=$lastid');move('purch-int-new.php');</script>";
		return $OUTPUT;
	}

	if(!isset($doneBtn)){
		return details($HTTP_POST_VARS);
	}else{
		# Insert Order to DB
		$sql = "UPDATE purch_int SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New International Order</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Order from Supplier <b>$sup[supname]</b> has been recorded.</td>
					<td><a href='purch-int-det.php?purid=$lastid'>Print Order</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purch-int-view.php'>View International Orders</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}

}


?>
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
	$HTTP_GET_VARS["done"] = "";
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
				$OUTPUT = slct();
		}
	} else {
		$OUTPUT = slct();
	}
}

# get templete
require("template.php");



function slct($err = "")
{

	db_connect();

	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND location = 'int' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve supplier s Information from the Database.",SELF);
	$supps = "<select name='supid'>";
	if(pg_numrows($supRslt) < 1) $supps .= "<option value='-S'></option>";
	while($sup = pg_fetch_array($supRslt)){
		$supps .= "<option value='$sup[supid]'>$sup[supno] - $sup[supname]</option>";
	}
	$supps .= "</select>";

	$details = "
		<center>
		<h3>New International Non-Stock Order</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'> Supplier Details </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Supplier</td>
				<td>$supps</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'></td>
				<td align='center'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}


function create_dummy($deptid, $supid)
{

	# Get selected supplier  info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get supplier  information");
	$sup = pg_fetch_array($supRslt);

	# Dummy Vars
	$remarks = "";
	$supaddr = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
//	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";

	$fcid = $sup['fcid'];
	$curr = getSymbol($fcid);
	$xrate = getRate($fcid);

	$purnum = divlastid ("pur", USER_DIV);

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
		INSERT INTO nons_purch_int (
			deptid, supid, supplier, supaddr, terms, pdate, ddate, shipchrg, xrate, fcid, 
			curr, subtot, total, balance, tax, remarks, received, done, prd, 
			div, purnum
		) VALUES (
			'$deptid', '$supid', '',  '$supaddr', '$terms', '$pdate', '$ddate', '$shipchrg', '$xrate', '$fcid', 
			'$curr[symbol]', '$subtot', '$total', '$total', '0', '$remarks', 'n', 'n', '".PRD_DB."', 
			'".USER_DIV."', '$purnum'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);

	# Get next ordnum
	$purid = pglib_lastid ("nons_purch_int", "purid");
	return $purid;

}



function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($purid)){
		$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	}else{
		$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = $error.slct();
		return $confirm;
	}


	if(!isset($purid)){
		$purid = create_dummy(0, $supid);
	}

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

	if($pur['xrate'] == 0)
		$pur['xrate'] = 1;

	# Get selected supplier info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($supRslt) < 1) {
		db_connect();
		# Query server for supplier info
		$sql = "SELECT * FROM suppliers WHERE location = 'int' AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier found in database.</li>";
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
		$sql = "SELECT * FROM suppliers WHERE location = 'int' AND div = '".USER_DIV."' ORDER BY supname ASC";
		$supRslt = db_exec ($sql) or errDie ("Unable to view suppliers");
		if (pg_numrows ($supRslt) < 1) {
			$err = "<li class='err'>No Supplier found in database.</li>";
			return view_err($HTTP_POST_VARS, $err);
		}else{
			$supid=$pur['supid'];
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

	$currs = getSymbol($fcid);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	if(!(isset($ordernum))) {$ordernum='';}

/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($npuri_year, $npuri_month, $npuri_day) = explode("-", $pur['pdate']);
	list($del_year, $del_month, $del_day) = explode("-", $pur['ddate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan='2'>UNIT PRICE</th>
				<th colspan='2'>DUTY</th>
				<th>LINE TOTAL</th>
				<th>COST PER UNIT</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this Order
	db_connect();

	$sql = "SELECT * FROM nons_purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

	/* -- Calculations -- */

		# Calculate cost amount bought
		$totamt = ($stkd['qty'] * $stkd['cunitcost']);

		# Calculate percentage from subtotal
		if($pur['subtot'] <> 0){
			$perc = ((($totamt+$stkd['duty'])/$pur['subtot']) * 100);
		}else{
			$perc = 0;
		}

		# Get percentage from shipping charges
		$shipchrg = sprint(($perc / 100) * $pur['shipchrg']);

		# Add shipping charges to amt
		$totamt = sprint($totamt + $shipchrg +$stkd['duty']);

		$unitamt = sprint($totamt / $stkd['qty']);

	/* -- End Calculations --*/

		$stkd['amt'] = sprint($stkd['amt']);

		$tip = "&nbsp;&nbsp;&nbsp;";
		if(isset($vatc[$key])){
			$tip = "<font color='red'>#</font>";
			$error = "<div class='err'> $tip&nbsp;&nbsp;=&nbsp;&nbsp; Vat amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value='$stkd[cod]'></td>
				<td align='center'><input type='text' size='20' name='des[]' value='$stkd[des]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td>$pur[curr] <input type='text' size='6' name='cunitcost[]' value='$stkd[cunitcost]'> or </td>
				<td>".CUR." <input type='text' size='6' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td>$pur[curr] <input type='text' size='6' name='duty[]' value='$stkd[duty]'> or </td>
				<td><input type='text' size='3' name='dutyp[]' value='$stkd[dutyp]'>%</td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> $pur[curr] $stkd[amt]</td>
				<td align='right'>$pur[curr] $unitamt</td>
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
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		# add one
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value=''></td>
				<td align='center'><input type='text' size='20' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td>$pur[curr] <input type='text' size='6' name='cunitcost[]'> or </td>
				<td>".CUR." <input type='text' size='6' name='unitcost[]'></td>
				<td>$pur[curr] <input type='text' size='6' name='duty[]'> or </td>
				<td><input type='text' size='3' name='dutyp[]'>%</td>
				<td>$pur[curr] 0.00</td>
				<td align='right'>$pur[curr] 0.00</td>
				<td> </td>
			</tr>";
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value=''></td>
				<td align='center'><input type='text' size='20' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td>$pur[curr] <input type='text' size='6' name='cunitcost[]'> or </td>
				<td>".CUR." <input type='text' size='6' name='unitcost[]'></td>
				<td>$pur[curr] <input type='text' size='6' name='duty[]'> or </td>
				<td><input type='text' size='3' name='dutyp[]'>%</td>
				<td>$pur[curr] 0.00</td>
				<td align='right'>$pur[curr] 0.00</td>
				<td> </td>
			</tr>";
		$key++;
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

	$pur['cusid'] += 0;

	if($pur['cusid'] == 0) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$pur['cusid'] = $vd['id'];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='delvat'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $pur['cusid']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

/* --- End Some calculations --- */

	if (isset ($diffwhBtn) OR isset ($upBtn) OR isset ($doneBtn) OR isset ($recv) OR isset ($donePrnt)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details = "
		<center>
		<h3>New International Non-Stock Order</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
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
							<th colspan='2'> Non-Stock Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'><input type='text' size='10' name='ordernum' value='$ordernum'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center' nowrap='t'>".mkDateSelect("npuri",$npuri_year,$npuri_month,$npuri_day)."</td>
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
							<td valign='center'>$pur[curr] <input type='text' size='7' name='shipchrg' value='$pur[fshipchrg]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Date</td>
							<td valign='center'>".mkDateSelect("del",$del_year,$del_month,$del_day)."</td>
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
							<td bgcolor='".bgcolorg()."'><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>$pur[curr] <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Shipping Charges</td>
							<td align='right'>$pur[curr] $pur[shipchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax </td>
							<td align='right'>$pur[curr] $pur[tax]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>$pur[curr] <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='2' align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input name='diffwhBtn' type='submit' value='Add Item'> | <input type='submit' name='upBtn' value='Update'>$done</td>
			</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";
	return $details;

}



function write($HTTP_POST_VARS)
{

	#get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 9, "Invalid Order ID");
	$v->isOk ($ordernum, "string", 0, 20, "Invalid order number.");
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($npuri_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($npuri_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($npuri_year, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($xrate, "float", 1, 20, "Invalid Exchange Rate.");
	$v->isOk ($tax, "float", 0, 20, "Invalid Tax.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$pdate = $npuri_year."-".$npuri_month."-".$npuri_day;
	if(!checkdate($npuri_month, $npuri_day, $npuri_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
	}
	$ddate = $del_year."-".$del_month."-".$del_day;
	if(!checkdate($del_month, $del_day, $del_year)){
		$v->isOk ($ddate, "num", 1, 1, "Invalid Date.");
	}
	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			# Nasty Zeros
			$unitcost[$keys] += 0;
			$cunitcost[$keys] += 0;
			$duty[$keys] += 0;
			$dutyp[$keys] += 0;
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 0, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($cunitcost[$keys], "float", 0, 20, "Invalid Foreign currency Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($duty[$keys], "float", 0, 20, "Invalid Duty Charges for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($dutyp[$keys], "float", 0, 20, "Invalid Duty Charges Percentage for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($des[$keys], "string", 1, 255, "Invalid Description.");
			$v->isOk ($cod[$keys], "string", 0, 255, "Invalid Item Code.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
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
		$HTTP_POST_VARS['done'] = "";
		return details($HTTP_POST_VARS, $err);
	}

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# Get selected supplier  info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get supplier  information");
	$sup = pg_fetch_array($supRslt);

	# Currency
	$currs = getSymbol($sup['fcid']);
	$curr = $currs['symbol'];

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
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
	# remove old items
	$sql = "DELETE FROM nons_purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

	/* -- End remove old items -- */
	$VATP = TAX_VAT;
	if(isset($qtys)){
		foreach($qtys as $keys => $value){
			if(isset($remprod)){
				if(in_array($keys, $remprod)){
					# skip product (wonder if $keys still align)
					$amt[$keys] = 0;
					continue;
				}else{

					# Calculate the unitcost
					if($cunitcost[$keys] > 0){
						$unitcost[$keys] = round(($cunitcost[$keys] * $xrate), 2);
					}else{
						$cunitcost[$keys] = round(($unitcost[$keys]/$xrate), 2);
					}

					# Calculate the duty amount
					if($duty[$keys] < 1){
						if($dutyp[$keys] > 0){
							$duty[$keys] = round((($dutyp[$keys]/100) * $unitcost[$keys]), 2);
						}
					}else{
						if($unitcost[$keys] > 0){
							$dutyp[$keys] = round((($duty[$keys] * 100) / $unitcost[$keys]), 2);
						}else{
							$dutyp[$keys] = 0;
						}
					}

					# Calculate amount
					$amt[$keys] = (($qtys[$keys] * $cunitcost[$keys]) + $duty[$keys]);

					# insert Order items
					$sql = "
						INSERT INTO nons_purint_items (
							purid, cod, des, qty, unitcost, cunitcost, duty, dutyp, amt, div
						) VALUES (
							'$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$cunitcost[$keys]', '$duty[$keys]', '$dutyp[$keys]', '$amt[$keys]', '".USER_DIV."'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
				}
			}else{
				# Calculate the unitcost
				if($cunitcost[$keys] > 0){
					$unitcost[$keys] = round(($cunitcost[$keys] * $xrate), 2);
				}else{
					$cunitcost[$keys] = round(($unitcost[$keys]/$xrate), 2);
				}

				# Calculate the duty amount
				if($duty[$keys] < 1){
					if($dutyp[$keys] > 0){
						$duty[$keys] = round((($dutyp[$keys]/100) * $unitcost[$keys]), 2);
					}
				}else{
					if($unitcost[$keys] > 0){
						$dutyp[$keys] = round((($duty[$keys] * 100) / $unitcost[$keys]), 2);
					}else{
						$dutyp[$keys] = 0;
					}
				}

				# Calculate amount
				$amt[$keys] = (($qtys[$keys] * $cunitcost[$keys]) + $duty[$keys]);

				# insert Order items
				$sql = "
					INSERT INTO nons_purint_items (
						purid, cod, des, qty, unitcost, cunitcost, 
						duty, dutyp, amt, div
					) VALUES (
						'$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$cunitcost[$keys]', 
						'$duty[$keys]', '$dutyp[$keys]', '$amt[$keys]', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}
			# everything is set place done button
			$HTTP_POST_VARS["done"] = "&nbsp; | &nbsp;<input name='doneBtn' type='submit' value='Done'>
			&nbsp; | &nbsp;<input name='recv' type='submit' value='Receive'>
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

	# insert Order to DB
	$sql = "
		UPDATE nons_purch_int 
		SET supid = '$supid',cusid = '$delvat', supplier = '$sup[supname]', supaddr = '$sup[supaddr]', terms = '$terms', 
			pdate = '$pdate', ddate = '$ddate', fcid = '$sup[fcid]', currency = '$curr', curr = '$curr', tax = '$tax', 
			xrate = '$xrate', fshipchrg = '$fshipchrg', shipchrg = '$shipchrg', duty = '$dutytot', subtot = '$SUBTOT', 
			total = '$TOTAL', balance = '$TOTAL', fsubtot = '$LSUBTOT', fbalance = '$LTOTAL', remarks = '$remarks' 
		WHERE purid = '$purid'";
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$lastid = pglib_lastid("nons_purch_int", "purid");

	if (isset($donePrnt)) {
		$sql = "UPDATE nons_purch_int SET done='y' WHERE purid='$purid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.");

		$OUTPUT = "
			<script>
				printer('nons-purch-int-print.php?purid=$lastid');
				move('nons-purch-int-new.php');
			</script>";
		return $OUTPUT;
	}

	if(isset($recv)) {
		header("Location: nons-purch-int-recv.php?purid=$purid");
		exit;
	}elseif(!isset($doneBtn)){
		return details($HTTP_POST_VARS);
	}else{
		# insert Order to DB
		$sql = "UPDATE nons_purch_int SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New International Non-Stock Order</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>International Non-Stock Order from Supplier <b>$sup[supname]</b> has been recorded.</td>
					<td><a href='nons-purch-int-print.php?purid=$lastid'>Print Order</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}

}


?>
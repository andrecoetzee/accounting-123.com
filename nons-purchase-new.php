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
				$HTTP_GET_VARS["done"] = "";
				$OUTPUT = details($HTTP_GET_VARS);
		}
	} else {
		$HTTP_GET_VARS["done"] = "";
		$OUTPUT = slct($HTTP_GET_VARS);
	}
}

# get templete
require("template.php");




# Details
function slct($HTTP_GET_VARS, $err = "")
{

	db_connect();

	$sql = "SELECT * FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);
	$sups = "<select name='supid'>";
	if(pg_numrows($supRslt) < 1) $sups .= "<option value='-S'></option>";
	while($sup = pg_fetch_array($supRslt)){
		$sups .= "<option value='$sup[supid]'>$sup[supno] $sup[supname]</option>";
	}
	$sups .= "</select>";

	$banks = "<select name='bankid'>";

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$Rs = db_exec($sql);
	$numrows = pg_numrows($Rs);

	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = pg_fetch_array($Rs)){
		$banks .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$banks.="</select>";

	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name='deptid'>";
	if(pg_numrows($deptRslt) < 1) $depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value=$dept[deptid]>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	//Option removed
	//<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select when tranferring goods between Departments or Stores")."><td colspan=2><input type=radio name=ctyp value='c' checked=yes>Accounts Order</td></tr>
	//<tr bgcolor='".TMPL_tblDataColor2."' ".ass("Select when the Order of non stock goods is a bank Order")."><td><input type=radio name=ctyp value='cb'>Bank Order</td><td>$banks</td></tr>

	$details = "
		<center>
		<h3>Non-Stock Order received</h3>
		<h4>Supplier Details</h4>
		<form action='".SELF."' method='post' name='form'>
			<input type='hidden' name='key' value='details'>
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
			<tr bgcolor='".bgcolorg()."' ".ass("Select when tranferring goods between Departments or Stores").">
				<td colspan='2'><input type='radio' name='ctyp' value='ac'>Ledger Accounts Order</td>
				<td class='err'>This selection will credit the amount of the invoice<br /> to a General Ledger account instead of Creditors Control.</td>
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




# Starting dummy
function create_dummy($deptid)
{

	global $HTTP_POST_VARS;

	extract($HTTP_POST_VARS);

	db_connect();
	# Dummy Vars
	$remarks = "";
	$supaddr = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
//	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";

	$purnum = divlastid("pur", USER_DIV);

	if(isset($supid)) {
		$supid += 0;
	} else {
		$supid = 0;
	}

	if(isset($deptid)) {
		$typeid = $deptid;
		$typeid += 0;
	} else {
		$typeid = 0;
	}

	if($ctyp == "cb" && isset($bankid)) {
		$bankid += 0;
		$supplierid = $bankid;
		$supid = "";
	} else {
		$supplierid = 0;
	}

	if($ctyp == "c") {
		$supid = "Cash Order";
	}  elseif($ctyp == "p") {
		$supid = "Petty Cash Order";
	} elseif($ctyp == "ac") {
		$supid = "Ledger Account Order";
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
		INSERT INTO nons_purchases (
			supid, deptid, supplier, supaddr, terms, pdate, ddate, shipchrg, subtot, total, 
			balance, vatinc, vat, remarks, received, done, prd, div, purnum, ctyp, typeid
		) VALUES (
			'$supplierid', '$deptid', '$supid',  '$supaddr', '$terms', '$pdate', '$ddate', '$shipchrg', '$subtot', '$total', 
			'$total', 'yes', '0', '$remarks', 'n', 'n', '".PRD_DB."', '".USER_DIV."', '$purnum', '$ctyp', '$typeid'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);

	# Get next ordnum
	$purid = lastpurid();
	return $purid;

}




# details
function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($purid)){
		$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	} else {
		$v->isOk ($ctyp, "string", 0, 20, "Invalid purchase type.");
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
		$purid = create_dummy(0);
	}

	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	if(!(isset($ordernum))) {$ordernum='';}

/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($npur_year, $npur_month, $npur_day) = explode("-", $pur['pdate']);

	# keep the charge vat option stable
	if($pur['vatinc'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
		$chnv = "";
	} else if ($pur['vatinc'] == 'novat') {
		$chy = "";
		$chn = "";
		$chnv = "checked=yes";
	}else{
		$chy = "";
		$chn = "checked=yes";
		$chnv = "";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>VAT CODE</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>VAT</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this Order
	db_connect();

	$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$stkd['amt'] = round($stkd['amt'], 2);

		$tip = "&nbsp;&nbsp;&nbsp;";
		if(isset($vatc[$key])){
			$tip = "<font color='red'>#</font>";
			$error = "<div class='err'> $tip&nbsp;&nbsp;=&nbsp;&nbsp; Vat amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
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

		$stkd['amt'] = sprint ($stkd['amt']);

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value='$stkd[cod]'></td>
				<td>$Vatcodes</td>
				<td align='center'><input type='text' size='20' name='des[]' value='$stkd[des]'></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
				<td align='center'>".mkDateSelecta("d",array($i),$syear,$smon,$sday)."</td>
				<td><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
				<td>$tip <input type='text' name='vat[]' size='9' value='$stkd[svat]'></td>
				<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
			</tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$Vatcodes = "
			<select name='vatcodes[]'>
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

		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$item_year = $date_arr[0];
			$item_month = $date_arr[1];
			$item_day = $date_arr[2];
		}else {
			$item_year = date("Y");
			$item_month = date("m");
			$item_day = date("d");
		}

		# add one
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value=''></td>
				<td>$Vatcodes</td>
				<td align='center'><input type='text' size='20' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]'></td>
				<td align='center'>".mkDateSelecta("d",array($i), $item_year, $item_month, $item_day)."</td>
				<td>".CUR." 0.00</td>
				<td><input type='hidden' name='novat[]' value='1'></td>
				<td> </td>
			</tr>";
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

		$Vatcodes = "
			<select name='vatcodes[]'>
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

$j = $i + 1;
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='10' name='cod[]' value=''></td>
				<td>$Vatcodes</td>
				</td><td align='center'><input type='text' size='20' name='des[]' value=''></td>
				<td align='center'><input type='text' size='3' name='qtys[]' value='1'></td>
				<td align='center'><input type='text' size='8' name='unitcost[]'></td>
				<td align='center'>".mkDateSelecta("d",array($j))."</td>
				<td>".CUR." 0.00</td>
				<td><input type='hidden' name='novat[$j]' value='1'></td>
				<td> </td>
			</tr>";
		$key++;
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $pur['subtot'];

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

	if($pur['ctyp']=="s") {

		db_connect();

		$sql = "SELECT * FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supno ASC";
		$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);
		$sups = "<select name='supplier'>";
		if(pg_numrows($supRslt) < 1)
			$sups .= "<option value='-S'></option>";
		while($sup = pg_fetch_array($supRslt)){
			if($sup['supid'] == $pur['supplier']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$sups .= "<option value='$sup[supid]' $sel>$sup[supno] $sup[supname]</option>";
		}
		$sups .= "</select>";

		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td>$sups</td>
			</tr>
			<input type='hidden' name='supaddr' value=''>";

	} elseif($pur['ctyp']=="cb") {
		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td valign='center'><input type='text' name='supplier' value='$pur[supplier]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Supplier Address</td>
				<td valign='center'><textarea name='supaddr' cols='18' rows='3'>$pur[supaddr]</textarea></td>
			</tr>";
	} elseif($pur['ctyp']=="c") {
		if(strlen($pur['supplier']) < 1) 
			$pur['supplier'] = "Cash Order";
		//Cash Order
		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td valign='center'><input type='text' name='supplier' value='$pur[supplier]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Supplier Address</td>
				<td valign='center'><textarea name='supaddr' cols='18' rows='3'>$pur[supaddr]</textarea></td>
			</tr>";
	}  elseif($pur['ctyp']=="p") {
		if(strlen($pur['supplier']) < 1)
			$pur['supplier'] = "Petty Cash Order";
		//Petty Cash Order
		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td valign='center'><input type='text' name='supplier' value='$pur[supplier]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Supplier Address</td>
				<td valign='center'><textarea name='supaddr' cols='18' rows='3'>$pur[supaddr]</textarea></td>
			</tr>";
	} elseif($pur['ctyp'] == "ac") {
//<input type='text' name='supplier' value='$pur[supplier]'>
		if(strlen($pur['supplier']) < 1) 
			$pur['supplier'] = "Ledger Account Order";
		//Ledger Account Order
		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td valign='center'><input type='text' name='supplier' value='$pur[supplier]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Supplier Address</td>
				<td valign='center'><textarea name='supaddr' cols='18' rows='3'>$pur[supaddr]</textarea></td>
			</tr>";
	} elseif($pur['ctyp'] == "c") {
		$sdata = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier</td>
				<td valign='center'><input type='text' name='supplier' value='$pur[supplier]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Supplier Address</td>
				<td valign='center'><textarea name='supaddr' cols='18' rows='3'>$pur[supaddr]</textarea></td>
			</tr>";
	} else {
		return slct($HTTP_POST_VARS);
	}

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

	$ex = "";

	if(strlen($pur['supinv']) AND ($pur['ctyp'] == "s")) {

		db_conn('cubit');

		$Sl = "SELECT purnum,pdate FROM nons_purchases WHERE supplier='$pur[supplier]' AND supinv='$pur[supinv]' AND purid != '$purid'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) > 0) {
			$pd = pg_fetch_array($Ri);

			$ex .= "<li class='err'>Non Stock Purchase $pd[purnum] on $pd[pdate] has the same supplier invoice number.</li>";

		}

		for($i=1;$i<13;$i++) {

			db_conn($i);

			$Sl = "SELECT purnum,pdate FROM nons_purchases WHERE supplier='$pur[supplier]' AND supinv='$pur[supinv]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {
				$pd = pg_fetch_array($Ri);

				$ex .= "<li class='err'>Non Stock Purchase $pd[purnum] on $pd[pdate] has same the supplier invoice number.</li>";
			}
		}

		db_conn ('cubit');

		$Sl = "SELECT purnum,pdate FROM purchases WHERE supid='$pur[supplier]' AND supinv='$pur[supinv]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) > 0) {
			$pd = pg_fetch_array($Ri);

			$ex .= "<li class='err'>Purchase $pd[purnum] on $pd[pdate] has the same supplier invoice number.</li>";

		}

		for($i=1;$i<13;$i++) {

			db_conn($i);

			$Sl = "SELECT purnum,pdate FROM purchases WHERE supid='$pur[supplier]' AND supinv='$pur[supinv]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {
				$pd = pg_fetch_array($Ri);

				$ex .= "<li class='err'>Purchase $pd[purnum] on $pd[pdate] has same the supplier invoice number.</li>";
			}
		}

	}

	$Vatcodes .= "</select>";

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if(!isset($supinv)) 
		$supinv = $pur['supinv'];
		
/* -- Final Layout -- */
	$details = "
		<center>
		<h3>New Non-Stock Order</h3>
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
						$sdata
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan=2> Non-Stock Order Details </th>
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
							<td>Supplier Inv No</td>
							<td valign='center'><input type='text' size='10' name='supinv' value='$supinv'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("npur",$npur_year,$npur_month,$npur_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>Yes <input type='radio' size='7' name='vatinc' value='yes' $chy> No<input type='radio' size='7' name='vatinc' value='no' $chn></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td valign='center'><input type='text' size='7' name='shipchrg' value='$pur[shipchrg]'>$Vatcodes</td>
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
							<td rowspan='5' valign='top' width='50%'>$ex $error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='subtot' value='$SUBTOT'>$SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>".CUR." $pur[shipping]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." $pur[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='2' align='center'><input name='diffwhBtn' type='submit' value='Add Item'> | <input type='submit' name='upBtn' value='Update'>$done</td>
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
	$v->isOk ($purid, "num", 1, 9, "Invalid Order ID");
	$v->isOk ($ordernum, "string", 0, 20, "Invalid order number.");
	$v->isOk ($supinv, "string", 0, 20, "Invalid supplier invoice number.");
	$v->isOk ($supplier, "string", 1, 255, "Invalid Supplier name.");
	$v->isOk ($supaddr, "string", 0, 255, "Invalid Supplier address.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($npur_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($npur_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($npur_year, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($vatinc, "string", 1, 5, "Invalid VAT Inclusion Option.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$pdate = $npur_year."-".$npur_month."-".$npur_day;
	if(!checkdate($npur_month, $npur_day, $npur_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
	}

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($des[$keys], "string", 1, 255, "Invalid Description.");
			$v->isOk ($cod[$keys], "string", 0, 255, "Invalid Item Code.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}

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

	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# fix those nasty zeros
	$shipchrg += 0;

	# insert Order to DB
	db_connect();

	$showvat = TRUE;

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		# remove old items
		$sql = "DELETE FROM nons_pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		/* -- End remove old items -- */
		$VATP = TAX_VAT;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)&&in_array($keys, $remprod)){
					//if(in_array($keys, $remprod)&&in_array($keys, $remprod)){
// 						# skip product (wonder if $keys still align)
// 						$amt[$keys] = 0;
// 						continue;
// 					}else{
//
// 						# Calculate amount
// 						$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
//
// 						if(isset($novat[$keys]) || strlen($vat[$keys]) < 1){
// 							# If vat is not included
// 							if($vatinc == "no"){
// 								$vat[$keys] = sprint(($VATP/100) * $amt[$keys]);
// 							}elseif($vatinc == "yes"){
// 								$vat[$keys] = sprint(($amt[$keys]/(100 + $VATP)) * $VATP);
// 							}else{
// 								$vat[$keys] = 0;
// 							}
// 						}
// 						if($vatinc == "novat"){
// 							$vat[$keys] = 0;
// 						}
//
// 						if($vatinc != "novat"){
// 							# If vat is not included
// 							if($vatinc == "no"){
// 								$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
// 							}else{
// 								$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
// 							}
// 							if($vat[$keys] <> $vatc[$keys]){
// 								$HTTP_POST_VARS["vatc"][$keys] = "yes";
// 							}
// 						}
//
// 						# format ddate
// 						$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";
//
// 						# insert Order items
// 						$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$vat[$keys]', '$ddate[$keys]', '".USER_DIV."')";
// 						$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
// 					}
				}else{
					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

					$tv = $vatinc;
					db_conn('cubit');
					$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri = db_exec($Sl);

					if(pg_num_rows($Ri) < 1) {
						return details($HTTP_POST_VARS, "<li class='err'>Please select the vatcode for all your items.</li>");
					}

					$vd = pg_fetch_array($Ri);

					$VATP = $vd['vat_amount'];

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					# Check Tax Excempt
					if($vd['zero']=="Yes"){
						$vat[$keys] = 0;
						$vatinc= "novat";
					}

					if(!isset($vat[$keys]))
						$vat[$keys] = "";

					if(isset($novat[$keys]) || strlen($vat[$keys]) < 1){
						# If vat is not included
						if($vatinc == "no"){
							$vat[$keys] = sprint(($VATP/100) * $amt[$keys]);
						}elseif($vatinc == "yes"){
							$vat[$keys] = sprint(($amt[$keys]/(100 + $VATP)) * $VATP);
						}else{
							$vat[$keys] = 0;
						}
					}
					if($vatinc == "novat"){
						$vat[$keys] = 0;
					}

					if($vatinc != "novat"){
						# If vat is not included
						if($vatinc == "no"){
							$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
						}else{
							$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
						}
						if($vat[$keys] <> $vatc[$keys]){
							$HTTP_POST_VARS["vatc"][$keys] = "yes";
						}
					}

					$vatinc=$tv;

					# ddate
					$ddate[$keys] = "$d_year[$keys]-$d_month[$keys]-$d_day[$keys]";

					# insert Order items
					$sql = "
						INSERT INTO nons_pur_items (
							purid, cod, des, qty, unitcost, amt, 
							svat, ddate, div, vatcode
						) VALUES (
							'$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
							'$vat[$keys]', '$ddate[$keys]', '".USER_DIV."','$vatcodes[$keys]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
				}
				# everything is set place done button
				$HTTP_POST_VARS["done"] = "&nbsp; | &nbsp;<input name='doneBtn' type='submit' value='Done'>
				&nbsp; | &nbsp;<input name='print'  type='submit' value='Receive'>
				&nbsp; | &nbsp;<input type='submit' name='donePrnt' value='Done, Print and make another'>";
			}
		}else{
			$HTTP_POST_VARS["done"] = "";
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

		$HTTP_POST_VARS['showvat'] = $showvat;

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

		/* --- Clac ---
		# calculate subtot
		if(isset($amt)){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}

		# If vat is not included (delchrg)
		$VATP = TAX_VAT;
		if($vatinc == "no"){
			$svat = sprint(($VATP/100) * $shipchrg);
		}else{
			$svat = sprint(($shipchrg/($VATP+100)) * $VATP);
		}

		# Total
		$TOTAL = ($SUBTOT + $shipchrg);

		# If there vatable items
		if(isset($vat)){
			$VAT = array_sum($vat);
		}else{
			$VAT = 0;
		}

		# If vat is not included
		if($vatinc == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}elseif($vatinc == "novat"){
			$VAT = 0;
			$svat = 0;
		}else{
			$SUBTOT -= $VAT;
		}

		$VAT += $svat;

		/* --- End Clac --- */

		$VAT += 0;

		# insert Order to DB
		$sql = "
			UPDATE nons_purchases 
			SET delvat='$delvat', supplier = '$supplier', supaddr = '$supaddr', terms = '$terms', pdate = '$pdate', 
				shipchrg = '$shipchrg', subtot = '$SUBTOT', total = '$TOTAL', 
				balance = '$TOTAL', vatinc = '$vatinc', vat = '$VAT',ordernum='$ordernum', 
				remarks = '$remarks', shipping = '$shipexvat', supinv='$supinv' 
			WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if (isset($donePrnt)) {
		$sql = "UPDATE nons_purchases SET done='y' WHERE purid='$purid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.");

		// LASTID
		$OUTPUT = "
			<script>
				printer('nons-purch-print.php?purid=$purid');
				move('nons-purchase-new.php');
			</script>";
		return $OUTPUT;
	}

	if(isset($print)) {
		$sql = "UPDATE nons_purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		# Print the invoice
		header("Location: nons-purch-recv.php?purid=$purid");
		exit;
	}elseif(!isset($doneBtn)){
		return details($HTTP_POST_VARS);
	}else{
		# insert Order to DB
		$sql = "UPDATE nons_purchases SET done='y' WHERE purid='$purid' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New Non-Stock Order</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Non-Stock Order from Supplier <b>$supplier</b> has been recorded.</td>
					<td><a href='javascript: printer(\"nons-purch-print.php?purid=$purid\");'>Print Order</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
		return $write;

	}
}


?>

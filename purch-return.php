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
if (isset($_GET["purid"])  && isset($_GET["prd"])) {
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
			default:
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
		}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");




# details
function details($_POST, $error="")
{

	$showvat = TRUE;

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_conn($prd);

	$sql = "SELECT *,(shipchrg - rshipchrg) as shipchrg FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	db_connect ();

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

	# check if purchase has been printed
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Warehouses found in Cubit.";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
		while($wh = pg_fetch_array($whRslt)){
				$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<TH>SERIAL NO.</TH>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>RETURNED</th>
			<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT *,(qty - tqty) as qty FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
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

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		if($stkd['udiscount'] > 0){
			$discps = round((($stkd['udiscount']/100) * $stkd['unitcost']), 2);
		}else {
			$discps = 0;
		}
		$amt[$keys] = sprint($qty[$stkd['id']] * ($stkd['unitcost'] - $discps));
		$stkd['amt'] = sprint(($stkd['unitcost'] - $discps) * $stkd['qty']);

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		if($stk['serd'] == 'yes'){
			$sers = ext_getPurSerStk($pur['purnum'], $stkd['stkid']);
			for($j = 0; $j < $stkd['qty']; $j++){
				$serial = $sers[$j]['serno'];
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$wh[whname]</td>
						<td>
							<input type='hidden' name='ids[$key]' value='$stkd[id]'>
							<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
							<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
						</td>
						<td>$stk[stkdes]</td>
						<td align='center'><input type='hidden' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'>$serial</td>
						<td>
							<input type='hidden' name='qt[$key]' value='1'>
							<input type='hidden' size='5' name='qtys[$key]' value='1'>1
						</td>
						<td nowrap>$stkd[unitcost]</td>
						<td>$stkd[udiscount]</td>
						<td>$sday-$smon-$syear</td>
						<td nowrap>".CUR." $stkd[amt]</td>
						<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
					</tr>";
				$key++;
			}
		}else{

			if($stkd['stkid'] == 0) {
				$stk['stkdes'] = $stkd['description'];
				$stk['stkid'] = "";
				$stk['stkcod'] = "";
			}

			$stkd['qty'] = sprint3($stkd['qty']);

			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td>
						<input type='hidden' name='ids[$key]' value='$stkd[id]'>
						<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
						<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
					</td>
					<td>$stk[stkdes]</td>
					<td><br></td>
					<td><input type='hidden' name='qt[$key]' value='$stkd[qty]'><input type='text' size='5' name='qtys[$key]' value='$stkd[qty]'></td>
					<td nowrap>$stkd[unitcost]</td>
					<td>$stkd[udiscount]</td>
					<td>$sday-$smon-$syear</td>
					<td nowrap>".CUR." $stkd[amt]</td>
					<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
				</tr>";
			$key++;
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get vat
	$VAT = sprint($pur['vat']);

	# Get Total
	// $TOTAL = sprint($pur['total']);
	$TOTAL = sprint($pur['total']);

/* --- End Some calculations --- */

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if (!isset($del)) {
		$del = $pur["shipchrg"];
	}

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Stock Return</h3>
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
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
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
							<th colspan='2'> Purchase Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Purchase No.</td>
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
							<td valign='center'>".CUR." <input type='hidden' size='10' value='0' name='del'>$del</td>
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
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New purchase</a></td>
							<td bgcolor='".TMPL_tblDataColor1."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
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
				<td align='right'><input type='submit' name='upBtn' value='Confirm'></td>
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
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
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

	# Get purchase info
	db_conn($prd);

	$sql = "SELECT *,(shipchrg - rshipchrg) as shipchrg FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	db_connect ();

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

	# check if purchase has been printed
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.</li>";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Warehouses found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
		while($wh = pg_fetch_array($whRslt)){
				$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	// list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>SERIAL NO.</th>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	$vatinc = $pur['vatinc'];
	$cost2 = 0;

	$key = 0;
	foreach($recvd as $sk => $keys){
		# Skip zeros
		if($qtys[$keys] <= 0){
			continue;
		}
		# Update purchase items
		db_conn($prd);
		$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);
		$stkd = pg_fetch_array($rslt);

		# get selamt from selected stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		$stkd['vatcode'] += 0;
		if($stkd['vatcode'] == 0) {
			$stkd['vatcode']++;
		}

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];
		$unitcost[$keys] = $stkd['unitcost'];

		# get warehouse name
		db_conn("exten");

		$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		if($stkd['udiscount'] > 0){
			$discps = round((($stkd['udiscount']/100) * $unitcost[$keys]), 2);
		}else {
			$discps = 0;
		}
		$amt[$keys] = sprint(($unitcost[$keys] - $discps) * $qtys[$keys]);

		$cunit = sprint ($unitcost[$keys] - $discps);

		if($stk['serd'] == 'yes'){
			$serial = $sers[$stkd['stkid']][$keys];
			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><input type='hidden' name='ids[$key]' value='$stkd[id]'><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td align='center'><input type='hidden' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'>$serial</td>
					<td><input type='hidden' size='5' name=qtys[$key] value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $unitcost[$keys]</td>
					<td>$stkd[udiscount]</td>
					<td>$sday-$smon-$syear</td>
					<td nowrap>".CUR." $amt[$keys] <input type='hidden' name='recvd[]' value='$key'></td>
				</tr>";
		}else{
			if($stkd['account'] > 0) {
				$stk['stkdes'] = $stkd['description'];
				$stk['stkid'] = "";
				$stk['stkcod'] = "";
				$stk['exvat'] = "";
			}

			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><input type='hidden' name='ids[$key]' value='$stkd[id]'><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td align='center'></td>
					<td><input type='hidden' size='5' name='qtys[$key]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $unitcost[$keys]</td>
					<td>$stkd[udiscount]</td>
					<td>$sday-$smon-$syear</td>
					<td nowrap>".CUR." $amt[$keys] <input type='hidden' name='recvd[]' value='$key'></td>
				</tr>";
		}

		$svat[$keys] = "";
		//$VATP=TAX_VAT;
		if(isset($novat[$keys])){

			# Check Tax Excempt
			if($stk['exvat'] != 'yes' && $vd['zero'] != "Yes"){
				# If vat is not included
				if($vatinc == "no"){
					$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
					$cunit = sprint($cunit-sprintf("%01.2f", (($VATP/100) * $cunit)));
				}elseif($vatinc == "yes"){
					$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
					$cunit = sprint($cunit-sprintf("%01.2f", (($cunit/($VATP + 100)) * $VATP)));
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
					$cunit = sprint($cunit-sprintf("%01.2f", (($VATP/100) * $cunit)));
				}elseif($vatinc == "yes"){

					$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/(100 + $VATP)) * $VATP));
					$cunit = sprint($cunit-sprintf("%01.2f", (($cunit/($VATP + 100)) * $VATP)));
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

		$vat[$keys] = sprint($amt[$keys] - sprint($cunit*$qtys[$keys]));

		$cost2 += ($cunit*$qtys[$keys]);

		$products .= "<input type='hidden' name='vat[$key]' value='$vat[$keys]'>";

		$key++;
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	$shipchrg = $del;//$pur['shipchrg'];

	# Get subtotal
	//$SUBTOT = sprint(array_sum($amt));

		/* --- Clac --- */
		# calculate subtot
		if(isset($amt)){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}
		$pur['delvat'] += 0;

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$pur[delvat]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			$Sl = "SELECT * FROM vatcodes";
			$Ri = db_exec($Sl);
		}

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];
		if($vd['zero'] != "Yes") {

			# If vat is not included (delchrg)
			//$VATP = TAX_VAT;
			if($pur['vatinc'] == "no"){
				$svat = sprint(($VATP/100) * $shipchrg);
				$shipexvat = $shipchrg;
			}elseif($pur['vatinc'] == "yes"){
				$svat = sprint(($shipchrg/($VATP+100)) * $VATP);
				$shipexvat = ($shipchrg - $svat);
			}else{
				$svat = 0;
				$shipexvat = $shipchrg;
			}
		} else{
			$svat = 0;
			$shipexvat = $shipchrg;
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
		if($pur['vatinc'] == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}else{
			$TOTAL = ($TOTAL + $svat);
			$SUBTOT -= ($VAT);
		}

		$VAT += $svat;

		$cost = $TOTAL-$VAT;
		$showcost = sprint($cost);
//		$cost=$cost2;


/* --- End Some calculations --- */

/* -- Final Layout -- */
	$confirm = "
		<center>
		<h3>Stock Return</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='cost' value='$cost'>
			<input type='hidden' name='TOTALs' value='$TOTAL'>
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
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr><th colspan='2'> Purchase Details </th></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Purchase No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value='$refno'></td>
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
							<td valign='center'>".CUR." <input type='hidden' size='10' value='$del' name='de'l>$del</td>
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
							<td rowspan='5' valign='top' width='50%'><br></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New purchase</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$remarks</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<th>Total Cost Returned</th>
							<td align='right' nowrap>".CUR." $showcost</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' name='upBtn' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $confirm;

}


function write($_POST)
{

	# Get vars
	extract ($_POST);

	$TOTALs += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
	}

	# Used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qtys[$keys] <= 0){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity must more than zero. Product number : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				foreach($recvd as $sk => $keys){
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) < 1){
						$v->isOk ("#", "string", 1, 20, "Error : Invalid Serial number.");
					}
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) > 0 && (ext_findSer($sernos[$keys]) == false)){
						$v->isOk ("#", "string", 1, 20, "Error : Serial <b>$sernos[$keys]</b> does not exists.");
					}
				}
			}
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : Items Not Selected.");
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



	if(!isset($del))
		$del = "";

	$del += 0;

	# Get purchase info
	db_conn($prd);

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pur['pdate']) >= strtotime($blocked_date_from) AND strtotime($pur['pdate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# check if purchase has been received
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has not been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		// code here
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	# Get department info
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$pur['delvat'] += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$pur[delvat]'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes";
		$Ri = db_exec($Sl);
	}

	$vd = pg_fetch_array($Ri);
	$VATP = $vd['vat_amount'];

	if($vd['zero'] != "Yes") {

	//	$VATP = TAX_VAT;
		if($pur['vatinc'] == "no"){
			$scvat = sprint(($VATP/100) * $del);
			$delexvat = $del;
			$scvat = sprint(($VATP/100) * $cost);
			$costexvat = $cost;
		}elseif($pur['vatinc'] == "yes"){
			$scvat = sprint(($del/($VATP+100)) * $VATP);
			$delexvat = ($del - $scvat);
			$scvat = sprint(($cost/($VATP+100)) * $VATP);
			$costexvat = ($cost - $scvat);
		}else{
			$scvat = 0;
			$costexvat = $cost;
		}
	} else{

		//$VATP = TAX_VAT;
		if($pur['vatinc'] == "no"){
			$scvat = sprint(($VATP/100) * $del);
			$delexvat = $del;
			$scvat = sprint(($VATP/100) * $cost);
			$costexvat = $cost;
		}elseif($pur['vatinc'] == "yes"){
			$scvat = sprint(($del/($VATP+100)) * $VATP);
			$delexvat = $del;
			$scvat = sprint(($cost/($VATP+100)) * $VATP);
			$costexvat = ($cost - $scvat);
		}else{
			$scvat = 0;
			$costexvat  = $cost;
		}
	}

	//$cost=$cost+$delexvat;

	//2631.58

	# Insert purchase to DB
	db_connect();

	# Begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$refnum = getrefnum();
		db_connect();
		$taxex = 0;
		# amount of stock in
		$totstkamt = array();
		foreach($recvd as $sk => $keys){

			# Skip zeros
			if($qtys[$keys] <= 0){
				continue;
			}

			db_conn($prd);
			$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);
			$stkd = pg_fetch_array($rslt);

			if($stkd['stkid'] < 1){
				$stk['whid'] = $stkd['account'];

				$unitcost[$keys] = $stkd['unitcost'];

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					$Sl = "SELECT * FROM vatcodes";
					$Ri = db_exec($Sl);
				}

				$vd = pg_fetch_array($Ri);
				$VATP = $vd['vat_amount'];

			//	$VATP = TAX_VAT;
				if($pur['vatinc'] == "yes"){
					$uc = sprint(($stkd['amt'] - $stkd['svat'])/$stkd['qty']);
				} else {
					$uc = sprint(($stkd['amt'])/$stkd['qty']);
				}

				$perc[$keys] = sprint(($uc/$cost) * 100);
				$shipc[$keys] = sprint(($perc[$keys] / 100) * $delexvat);

				$unitcost[$keys] += $shipc[$keys];

				# including shipchrg, exluding vat
				$amts[$keys] = sprint($qtys[$keys] * $uc);
				//$amts[$keys] -= $vat[$keys];

				if(isset($totstkamt[$stk['whid']])){
					$totstkamt[$stk['whid']] += $amts[$keys];
				}else{
					$totstkamt[$stk['whid']] = $amts[$keys];// + $vat[$keys];
				}

				$totstkamt[$stk['whid']] = sprint ($totstkamt[$stk['whid']]);

				db_conn($prd);

				$sql = "
					UPDATE pur_items 
					SET tqty = (tqty + '$qtys[$keys]'), ctqty = (ctqty + $qtys[$keys]) 
					WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);

			} else {

				db_connect();

				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				$unitcost[$keys] = $stkd['unitcost'];

				db_conn('cubit');

				$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					$Sl = "SELECT * FROM vatcodes";
					$Ri = db_exec($Sl);
				}

				$vd = pg_fetch_array($Ri);
				$VATP = $vd['vat_amount'];

				//$VATP = TAX_VAT;
				if($pur['vatinc'] == "yes"){
					$unitcost[$keys] = sprint(($stkd['amt'] - $stkd['svat'])/$stkd['qty']);
				}else{
					$unitcost[$keys] = sprint(($stkd['amt'])/$stkd['qty']);
				}

				$cost=$cost-$delexvat;
				//print "cost$cost/unicost $unitcost[$keys]";

				$perc[$keys] = sprint((($unitcost[$keys]*$qtys[$keys])/$cost) * 100);
				$shipc[$keys] = sprint(($perc[$keys] / 100) * $delexvat);

				$cost = $cost + $delexvat;

				# including shipchrg, exluding vat
				$amts[$keys] = sprint($qtys[$keys] * $unitcost[$keys]);
				$amts[$keys] += $shipc[$keys];
				//$amts[$keys]-=$vat[$keys];

				//print "Per$perc[$keys]  Amount$amts[$keys]<br>";

				$Sl = "SELECT * FROM pcost WHERE purnum='$pur[purnum]' AND stkid='$stk[stkid]'";
				$Ri = db_exec($Sl);

				if (pg_num_rows($Ri) > 0) {
					$pd = pg_fetch_array($Ri);

					db_conn("exten");

					$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);
					$stockacc = $wh['stkacc'];
					$cosacc = $wh['cosacc'];

					if (($pd['qty'] - $pd['rqty']) < ($qtys[$keys] - $stk['units'])) {
						$qt = $pd['qty'] - $pd['rqty'];
					} else {
						$qt = $qtys[$keys] - $stk['units'];
					}

					$cost = $pd['cost']*$qt;

					writetrans ($stockacc,$cosacc,date("d-m-Y"), $refnum, $cost, "Reverse Cost Of Sales for stock sold before purchase $pur[purnum]");

					db_conn('cubit');

					$Sl = "UPDATE pcost SET rqty=rqty+'$qt' WHERE purnum='$pur[purnum]'";
					$Ri = db_exec($Sl);

					$lc = ($amts[$keys]-$cost);

				} else {
					$lc = $amts[$keys];
				}

				# Update purchase items
				db_conn($prd);

				$sql = "
					UPDATE pur_items 
					SET tqty = (tqty + '$qtys[$keys]'), ctqty = (ctqty + $qtys[$keys]) 
					WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);

				# Update stock(units - qty), csamt = (csamt - amt)
				db_connect();

				$sql = "
					UPDATE stock 
					SET units = (units - '$qtys[$keys]'), csamt = (csamt - '$lc') 
					WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

				if(isset($sers[$stkids[$keys]][$keys])){
					ext_OutSer($sers[$stkids[$keys]][$keys], $stkids[$keys], $pur['supname'], $pur['purnum'], "ret");

					$serial = $sers[$stkids[$keys]][$keys];

					db_connect();
					$sql = "DELETE FROM pserec WHERE purid = '$purid' AND  stkid = '$stkids[$keys]' AND serno = '$serial'";
					$rslt = db_exec($sql) or errDie("Unable to update stock serials in Cubit.",SELF);
				}

				# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
				$sdate = date("Y-m-d");
			//	stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $qtys[$keys], $lc,"Stock returned to Supplier : $sup[supname] - Purchase No. $pur[purnum].");
			//$amts[$keys] = $lc
				stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $pur['pdate'], $qtys[$keys], $amts[$keys],"Stock returned to Supplier : $sup[supname] - Purchase No. $pur[purnum].");

				db_connect();
				$cspric = sprint($amts[$keys]/$qtys[$keys]);
				$sql = "
					INSERT INTO stockrec (
						edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div
					) VALUES (
						'$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'purchase', '-$qtys[$keys]', '$lc', '$cspric', 'Stock Returned to Supplier : $sup[supname] - Order No. $pur[purnum]', '".USER_DIV."'
					)";
				$recRslt = db_exec($sql);


				# Keep records for transactions
				if(isset($totstkamt[$stk['whid']])){
					$totstkamt[$stk['whid']] += $amts[$keys];
				}else{
					$totstkamt[$stk['whid']] = $amts[$keys];
				}
				$totstkamt[$stk['whid']] = sprint ($totstkamt[$stk['whid']]);

				# Get selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Just wanted to fix the xxx.xxxxxxe-x value
				if($stk['units'] > 0){
					$csprice = sprint($stk['csamt']/$stk['units']);
				}else{
					$csprice = 0;
				}

				# Update stock(csprice = (csamt/units))
				$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			}
		}

		/* --- Clac --- */

		# calculate subtot exluding vat
		$SUBTOT = sprint(array_sum($amts)); // Excluding shipping charges

		/* --- End Clac --- */

		# Update purchase on the DB
		db_conn($prd);

		$sql = "UPDATE purchases SET rsubtot = (rsubtot + $SUBTOT) WHERE purid = '$purid'";
		# we dont want to change the old remark ???? just add the new 1 to the returned table data
		// , remarks = '$remarks'
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

		# Insert returned purchase
		$sql = "
			INSERT INTO purch_ret (
				purid, purnum, supname, rdate, subtot, remarks, div, supinv
			) VALUES (
				'$purid', '$pur[purnum]', '$sup[supname]', '$pdate', '$SUBTOT', '$remarks', '".USER_DIV."', '$pur[supinv]'
			)";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

		$rpurid = pglib_lastid ("purch_ret", "rpurid");

		# Insert returned items
		foreach($recvd as $sk => $keys){

			# Skip zeros
			if($qtys[$keys]< 1){
				continue;
			}

			db_connect();

			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			if(!isset($stk['whid'])) {
				$stk['whid'] = 0;
				$stk['stkid'] = 0;
			}

			db_conn($prd);

			$sql = "
				INSERT INTO retpur_items (
					rpurid, whid, stkid, qty, unitcost, itemid
				) VALUES (
					'$rpurid', '$stk[whid]', '$stk[stkid]', '$qtys[$keys]', '$unitcost[$keys]', '$ids[$keys]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);
		}

	/* Transactions */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	# Record transaction from data
	foreach($totstkamt as $whid => $wamt){
		# get whouse info
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);

		if(pg_num_rows($whRslt) < 1) {
			$sql = "SELECT stkacc,conacc FROM warehouses";
			$whRslt = db_exec($sql);

			$wh = pg_fetch_array($whRslt);

			$wh['stkacc'] = $whid;
		} else {
			$wh = pg_fetch_array($whRslt);
		}

		# Debit Suppliers control and Credit Stock
		//writetrans($wh['conacc'], $wh['stkacc'], date("d-m-Y"), $refnum, $wamt, "Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname]");
		writetrans($wh['conacc'], $wh['stkacc'], $pdate, $refnum, $wamt, "Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname]");
	}

	/* End Transactions */
	db_conn($prd);

	# check if there are any outstanding items
	$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND (qty - tqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	# if none the set to received
	if(pg_numrows($stkdRslt) < 1){
		# update surch_int(received = 'y')
		$sql = "UPDATE purchases SET returned = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Orders in Cubit.",SELF);
	}

	# Commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
		<script>
			printer ('purch-return-print.php?purid=$purid');
		</script>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Return</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Return to Supplier <b>$sup[supname]</b> has been recorded.</td>
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
	return $write;

}


?>

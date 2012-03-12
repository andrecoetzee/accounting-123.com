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
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
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
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.</li>";
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
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
			<tr>";

	# get selected stock in this purchase
	db_conn($prd);

	$sql = "SELECT *,ctqty as qty FROM pur_items  WHERE purid = '$purid' AND tqty > 0 AND div = '".USER_DIV."'";
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

		$stkd['amt'] = sprint($stkd['unitcost'] * $stkd['qty']);

		# put in product
		if($stk['serd'] == 'yes'){
			$sers = ext_getPurSerStk($pur['purnum'], $stkd['stkid']);

			for($j = 0; $j < $stkd['qty']; $j++){
				//$serial = $sers[$j]['serno'];$serial
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$wh[whname]</td>
						<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$stk[stkdes]</td>
						<td>1</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td>$sday-$smon-$syear</td>
					</tr>";
				$key++;
			}
		}else{

			if($stkd['account'] > 0) {
				$stk['stkdes'] = $stkd['description'];
				$stk['stkid'] = "";
				$stk['stkcod'] = "";
			}

			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td>$stkd[qty]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td>$sday-$smon-$syear</td>
				</tr>";
			$key++;
		}
	}
	$products .= "</table>";

	$total = sprint($pur['rsubtot'] * 1.14);
	$vat = sprint($total - $pur['rsubtot']);

/* --- End Products Display --- */

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl);

	$vd = "<table border='0' cellpadding='0' cellspacing='0'>";

	while($vc = pg_fetch_array($Ri)) {
		if($vc['del'] == "Yes") {
			$rvat = $vat;
		} else {
			$rvat = "";
		}
		$vid = $vc['id'];
		$vd .= "
			<tr>
				<td>$vc[code]</td>
				<td><input type='text' size='7' name='rvat[$vid]' value='$rvat'></td>
			</tr>";
	}

	$vd .= "</table>";

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Record Purchase Credit Note</h3>
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
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='text' name='subtot' size='10' value='$pur[rsubtot]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR."$vd</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='text' name='total' size='10' value='$total'></td>
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
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid Date.");
	}
	$v->isOk ($subtot, "float", 1, 20, "Invalid Subtotal.");
	$vat=array_sum($rvat);
	foreach ($rvat as $rvat_k => $rvat_v) {
		$v->isOk ($rvat_v, "float", 0, 40, "Invalid vat ($rvat_v).");
	}
	$v->isOk ($total, "float", 1, 20, "Invalid total.");

	$error = "";
	$confirm = "";
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
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.</li>";
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
	$whs .= "</select>";

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>DELIVERY DATE</th>
			<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT *,ctqty as qty FROM pur_items  WHERE purid = '$purid' AND tqty > 0 AND div = '".USER_DIV."'";
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

		$stkd['amt'] = sprint($stkd['unitcost'] * $stkd['qty']);

		# put in product
		if($stk['serd'] == 'yes'){
			$sers = ext_getPurSerStk($pur['purnum'], $stkd['stkid']);
			for($j = 0; $j < $stkd['qty']; $j++){
				//$serial = $sers[$j]['serno'];
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$wh[whname]</td>
						<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$stk[stkdes]</td>
						<td>1</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td>$sday-$smon-$syear</td>
					</tr>";
				$key++;
			}
		}else{

			if($stkd['account']>0) {
				$stk['stkdes']=$stkd['description'];
				$stk['stkid']="";
				$stk['stkcod']="";
			}


			$products .="
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td>$stkd[qty]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td>$sday-$smon-$syear</td>
				</tr>";
			$key++;
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

	$subtot = sprint($subtot);
	$vat = sprint($vat);
	$total = sprint($subtot + $vat);


	db_conn('cubit');
	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl);

	$vd = "<table border='0' cellpadding='0' cellspacing='0'>";

	while($vc = pg_fetch_array($Ri)) {
		$vid = $vc['id'];
		$vd .= "
			<tr>
				<td><input type='hidden' name='rvat[$vid]' value='$rvat[$vid]'></td>
			</tr>";
	}

	$vd.="</table>";



/* -- Final Layout -- */

	$confirm = "
		<center>
		<h3>Confirm Purchase Credit Note</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
			$vd
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
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." <input type='hidden' name='subtot' size='10' value='$subtot'>$subtot</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." <input type='hidden' name='vat' size='10' value='$vat'>$vat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." <input type='hidden' name='total' size='10' value='$total'>$total</td>
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


# details
function write($_POST)
{

	# Get vars
	extract ($_POST);

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
	$v->isOk ($subtot, "float", 1, 20, "Invalid Subtotal.");
	foreach ($rvat as $rvat_k => $rvat_v) {
		$v->isOk ($rvat_v, "float", 0, 40, "Invalid vat ($rvat_v).");
	}
	$v->isOk ($total, "float", 1, 20, "Invalid total.");

	# Used to generate errors
	$error = "asa@";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : purchase number <b>$pur[purnum]</b> has not been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	$sup = pg_fetch_array($supRslt);

	# Get department info
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Insert purchase to DB
	db_connect();

	$d=date("Y-m-d");

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_conn('cubit');

		$Sl="INSERT INTO dnotes (purid,date,sub,vat,tot) VALUES ('$purid','$d','$subtot','$vat','$total')";
		$Ri=db_exec($Sl) or errDie("Unable insert data.");

		# Get warehouse name
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# Update purchase on the DB
		db_conn($prd);
		$sql = "UPDATE purchases SET noted = 'y', rsubtot = 0, remarks = '$remarks' WHERE purid = '$purid'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

	/* - Start Hooks - */
		$refnum = getrefnum();
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");
	/* - End Hooks - */



		$retot = sprint($subtot + $vat);
		$sdate = $pdate;//date("Y-m-d");

		# Debit Supplier control, credit inv control
	//	writetrans($dept['credacc'], $vatacc, date("d-m-Y"), $refnum, $vat, "Credit Note for VAT return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
	//	writetrans($dept['credacc'], $wh['conacc'], date("d-m-Y"), $refnum, $subtot, "Credit Note for Stock return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");

		writetrans($dept['credacc'], $vatacc, $pur['pdate'], $refnum, $vat, "Credit Note for VAT return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		writetrans($dept['credacc'], $wh['conacc'], $pur['pdate'], $refnum, $subtot, "Credit Note for Stock return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes ORDER BY code";
		$Ri = db_exec($Sl);

		$f = 0;

		while($vd = pg_fetch_array($Ri)) {
			$vid = $vd['id'];
			if($rvat[$vid] < 0.01) {
				continue;
			}
			if($f == 0) {
				$AM = sprint($subtot+$vat);
			} else {
				$AM = 0;
			}
			//$vd.="<tr><td><input type=hidden name='rvat[$vid]' value='$rvat[$vid]'></td></tr>";
			vatr($vd['id'],date("Y-m-d"),"INPUT",$vd['code'],$refnum,"Return VAT for Purchase No. $pur[purnum]",$AM,$rvat[$vid]);
			$f++;
		}

		db_connect();

		# Update the supplier (make balance less)
		$sql = "UPDATE suppliers SET balance = (balance - '$retot') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Update the supplier age analysis (make balance less)
		if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
			# Found? Make amount less
			$sql = "
				UPDATE suppurch 
				SET balance = (balance - '$retot') 
				WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		}else{
			# Make transaction record for age analysis
			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$pur[supid]', '$pur[purnum]', '$pdate', '-$retot', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}

		# Remove all empty entries
		$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
		$rs = db_exec($sql);

		$sql = "
			INSERT INTO sup_stmnt (
				supid, edate, cacc, amount, descript, ref, ex, div
			) VALUES (
				'$pur[supid]', '$pdate', '$dept[credacc]', '-$retot', 'Stock Returned', '$refnum', '$pur[purnum]', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Ledger Records
		suppledger($pur['supid'], $wh['conacc'], $sdate, $pur['purid'], "Stock Purchase No. $pur[purnum] returned.", $retot, 'd');

	/*-- Cost varience -- */
		if($pur['rsubtot'] > $subtot){
			$diff = sprint($pur['rsubtot'] - $subtot);
			# Debit Stock Control and Credit Creditors control
			writetrans($cvacc, $wh['conacc'], $sdate, $refnum, $diff, "Cost Variance for Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}elseif($subtot > $pur['rsubtot']){
			$diff = sprint($subtot - $pur['rsubtot']);
			# Debit Stock Control and Credit Creditors control
			writetrans($wh['conacc'], $cvacc, $sdate, $refnum, $diff, "Cost Variance for Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}
	/*-- End Cost varience -- */

	/* End Transactions */
	db_conn($prd);
	$sql = "UPDATE pur_items SET ctqty = '0' WHERE purid = '$purid' AND ctqty > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

# Commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Return Credit Note</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Return Credit note from Supplier <b>$sup[supname]</b> has been recorded.</td>
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

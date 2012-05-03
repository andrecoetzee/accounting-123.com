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

if (!isset($_REQUEST["purid"]) || !is_numeric($_REQUEST["purid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("template.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "details":
		$OUTPUT = details();
		break;
	case "update":
		$OUTPUT = write();
		break;
	case "confirm":
		$OUTPUT = confirm();
		break;
	case "recv_print":
		$OUTPUT = recv_print();
		break;
	}
} else {
	$OUTPUT = details();
}

# Get templete
require("template.php");




# Details
function details ($err="")
{

	$showvat = TRUE;

	# get vars
	extract ($_REQUEST);

	# Validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");

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
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

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
	if($pur['apprv'] != "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has not yet been approved.";
		return $error;
	}

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.";
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
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<TH>SERIAL NO.</TH>
				<th>QTY RECEIVED</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
				<th>RECEIVED</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT *,(qty - rqty) as qty FROM pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
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

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		if($stk['serd'] == 'yes'){
			for($j = 0; $j < $stkd['qty']; $j++){
				$serial = "";
				if(isset($sers[$stkd['stkid']][$key]))
					 $serial = $sers[$stkd['stkid']][$key];
				$products .= "
					<tr class='".bg_class()."'>
						<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
						<td>
							<input type='hidden' name='ids[$key]' value='$stkd[id]'>
							<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
							<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
						</td>
						<td>$stk[stkdes]</td>
						<td align='center'><input type='text' tabindex='".($key+1)."' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'></td>
						<td>
							<input type='hidden' size='5' name='qts[$key]' value='1'>
							<input type='hidden' size='5' name='qtys[$key]' value='1'>1
						</td>
						<td nowrap><input type='hidden' size='4' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
						<td nowrap><input type='hidden' size='4' name='udiscount[$key]' value='$stkd[udiscount]'>".CUR." $stkd[udiscount]</td>
						<td>".mkDateSelecta("d",$key,$syear,$smon,$sday)."</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
					</tr>";
				$key++;
			}
		}else{
			if($stkd['account']>0) {
				$stk['stkdes'] = $stkd['description'];
				$stk['stkid'] = "";
				$stk['stkcod'] = "";
			}

			$stkd['qty'] = sprint3($stkd['qty']);

			$products .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
					<td>
						<input type='hidden' name='ids[$key]' value='$stkd[id]'>
						<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
						<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
					</td>
					<td>$stk[stkdes]</td>
					<td><br></td>
					<td><input type='hidden' size='5' name='qts[$key]' value='$stkd[qty]'><input type='text' size='7' name='qtys[$key]' value='$stkd[qty]'></td>
					<td nowrap><input type='hidden' size='4' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' size='4' name='udiscount[$key]' value='$stkd[udiscount]'>$stkd[udiscount]</td>
					<td>".mkDateSelecta("d",$key,$syear,$smon,$sday)."</td>
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
	$TOTAL = sprint($pur['total']);
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

	global $_GET;

	if(isset($_GET['invoice']) OR isset($_POST['invoice'])) {
		$fex = "<input type='hidden' name='invoice' value='invoice'>";
	} else {
		$fex = "";
	}

	if($TOTAL == 0) {
		return "<li class='err'>The total purchase amount is zero. You cannot receive a R0 purchase. If it doesnt cost anything, its not a purchase.</li>";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if (isset($gds_note) AND strlen($gds_note) > 0)
		$gdssel = "checked='yes'";
	else 
		$gdssel = "";

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Stock Order received</h3>
		<form action='".SELF."' method='POST' name='form'>
			$err
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
			$fex
		<table ".TMPL_tblDflts." width='95%'>
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
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign=top>Supplier Address</td>
							<td valign='center'>".nl2br($supaddr)."</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr><th colspan='2'> Purchase Details </th></tr>
						<tr class='".bg_class()."'>
							<td>Purchase No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Inv.</td>
							<td valign='center'><input type='text' name='supinv' size='10' value='$pur[supinv]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value=''></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges</td>
							<td valign='center'>".CUR." <input type='hidden' name='shipchrg' size='10' value='$pur[shipchrg]'>$pur[shipchrg]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Print Goods Received Note</td>
							<td><input type='checkbox' name='gds_note' value='yes' $gdssel></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td colspan='2'>$products</td></tr>
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
							<td class='".bg_class()."'><a href='purchase-new.php'>New Stock Order</a></td>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='purchase-view.php'>View Stock Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
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
							<td align='right' nowrap>".CUR." $pur[vat]</td>
						</tr>
						<tr class='".bg_class()."'>
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



# details
function confirm()
{

	# Get vars
	extract($_REQUEST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($supinv, "string", 0, 80, "Invalid supplier invoice number.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qtys[$keys] <= 0){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity must be more than zero. Product number : <b>".($keys+1)."</b>");
			}
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");
			# Validate ddate[]
			$v->isOk ($d_day[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($d_month[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($d_year[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $d_year[$keys]."-".$d_month[$keys]."-".$d_day[$keys];
			if(!checkdate($d_month[$keys], $d_day[$keys], $d_year[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
			}
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				if(!ext_isUnique(ext_remBlnk($sernos))){
					$v->isOk ("error", "num", 1, 1, "Error : Serial numbers must be unique per Stock Item.");
				}else{
					foreach($recvd as $sk => $keys){
						if(isset($sernos[$keys]) && $v->isOk ($sernos[$keys], "string", 1, 20, "Error : Invalid Serial number.")){
							if((ext_findSer($sernos[$keys]) != false)){
								$v->isOk ("#", "string", 1, 20, "Error : Serial number already exists.");
							}
						}
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




	db_conn("cubit");

	$sql = "UPDATE purchases SET supinv='$supinv' WHERE purid='$purid'";
	$sinv_rslt = db_exec($sql) or errDie("Unable to store the supplier invoice number to Cubit.");

	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pur['pdate']) >= strtotime($blocked_date_from) AND strtotime($pur['pdate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
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

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.";
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

	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	/* --- Start Products Display --- */

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

		# If vat is not included (shipchrg)
		//$VATP = TAX_VAT;
		if($pur['vatinc'] == "no"){
			$scvat = sprint(($VATP/100) * $shipchrg);
			$shipexvat = sprint($shipchrg);
		}elseif($pur['vatinc'] == "yes"){
			$scvat = sprint(($shipchrg/($VATP+100)) * $VATP);
			$shipexvat = sprint($shipchrg - $scvat);
		}else{
			$scvat = 0;
			$shipexvat  = sprint($shipchrg);
		}
	}else{
		$scvat = 0;
		$shipexvat  = sprint($shipchrg);
	}

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<TH>SERIAL NO.</TH>
				<th>QTY RECEIVED</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# amount of stock in
	$totstkamt = 0;
	$resub = 0;
	$revat = 0;

	foreach($recvd as $sk => $keys){

		# Skip zeros
		if($qtys[$keys] <= 0){
			continue;
		}

		db_connect();

		# Get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		if(!isset($stk['whid'])) {
			$stk['whid'] = 0;
			$stk['serd'] = "";
			$stk['stkid'] = "";
			$stk['stkcod'] = "";
		}

		# get warehouse name
		db_conn("exten");

		$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
		$whRslt = @db_exec($sql);
		$wh = @pg_fetch_array($whRslt);

		db_connect();

		# Get selected stock line
		$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if($pur['vatinc'] == "yes"){
			$unitcost[$keys] = sprint(($stkd['amt'] - $stkd['svat'])/$stkd['qty']);
		}else{
			$unitcost[$keys] = sprint(($stkd['amt'])/$stkd['qty']);
		}

		$perc[$keys] = sprint(($unitcost[$keys]/$pur['subtot']) * 100);

		# Get percentage from shipping charges excluding vat
		$shipc[$keys] = sprint($perc[$keys] / 100 * $shipexvat);

		if($stkd['udiscount'] > 0){
			$discps = round((($stkd['udiscount']/100) * $stkd['unitcost']), 2);
		}else {
			$discps = 0;
		}

		# add delivery charges = amt + del chrg excluding vat
		$unitcost[$keys] += $shipc[$keys];

		$amt[$keys] = sprint($qtys[$keys] * $unitcost[$keys]);

		$tot = ($stkd['unitcost']-$discps) * $qtys[$keys];

		$revat += ($stkd['svat']/$stkd['qty']) * $qtys[$keys];
		$totstkamt += $amt[$keys];

		if ( $pur["vatinc"] == "yes" ) {
			$amt[$keys] = $tot-(($stkd['svat']/$stkd['qty']) * $qtys[$keys]);
		} else {
			$amt[$keys] = $tot;
		}

		$resub += $amt[$keys];

		// list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

	//	$amts = sprint($stkd['unitcost'] * $qtys[$keys]);

		$amts = sprint($qtys[$keys] * ($stkd['unitcost'] - $discps));

		if($stk['serd'] == 'yes'){
			$serial = $sers[$stkd['stkid']][$keys];
			$products .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[$keys]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='ids[$keys]' value='$stkd[id]'><input type='hidden' name='stkids[$keys]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td align='center'><input type='hidden' name='sers[$stkd[stkid]][$keys]' size='20' value='$serial'>$serial</td>
					<td><input type='hidden' size='5' name='qts[$keys]' value='1'><input type='hidden' size='5' name='qtys[$keys]' value='1'>1</td>
					<td nowrap><input type='hidden' size='4' name='unitcost[$keys]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' size='4' name='udiscount[$keys]' value='$stkd[udiscount]'>$stkd[udiscount]</td>
					<td>
						<input type='hidden' size='2' name='d_day[$keys]' maxlength='2' value='$d_day[$keys]'>$d_day[$keys]-
						<input type='hidden' size='2' name='d_month[$keys]' maxlength='2' value='$d_month[$keys]'>$d_month[$keys]-
						<input type='hidden' size='4' name='d_year[$keys]' maxlength='4' value='$d_year[$keys]'>$d_year[$keys]
					</td>
					<td nowrap>".CUR." $stkd[unitcost]<input type='hidden' name='recvd[]' value='$keys'></td>
				</tr>";
		}elseif($stkd['account']>0){
			$products .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[$keys]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='ids[$keys]' value='$stkd[id]'><input type='hidden' name='stkids[$keys]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stkd[description]</td>
					<td align='center'></td>
					<td><input type='hidden' size='5' name='qts[$keys]' value='$qts[$keys]'><input type='hidden' size='5' name='qtys[$keys]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap><input type='hidden' size='4' name='unitcost[$keys]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' size='4' name='udiscount[$keys]' value='$stkd[udiscount]'>$stkd[udiscount]</td>
					<td>
						<input type='hidden' size='2' name='d_day[$keys]' maxlength='2' value='$d_day[$keys]'>$d_day[$keys]-
						<input type='hidden' size='2' name='d_month[$keys]' maxlength='2' value='$d_month[$keys]'>$d_month[$keys]-
						<input type='hidden' size='4' name='d_year[$keys]' maxlength='4' value='$d_year[$keys]'>$d_year[$keys]
					</td>
					<td nowrap>".CUR." $amts<input type='hidden' name='recvd[]' value='$keys'></td>
				</tr>";
		}else{
			$products .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='whids[$keys]' value='$stkd[whid]'>$wh[whname]</td>
					<td><input type='hidden' name='ids[$keys]' value='$stkd[id]'><input type='hidden' name='stkids[$keys]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>$stk[stkdes]</td>
					<td align='center'></td>
					<td><input type='hidden' size='5' name='qts[$keys]' value='$qts[$keys]'><input type='hidden' size='5' name='qtys[$keys]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap><input type='hidden' size='4' name='unitcost[$keys]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' size='4' name='udiscount[$keys]' value='$stkd[udiscount]'>$stkd[udiscount]</td>
					<td>
						<input type='hidden' size='2' name='d_day[$keys]' maxlength='2' value='$d_day[$keys]'>$d_day[$keys]-
						<input type='hidden' size='2' name='d_month[$keys]' maxlength='2' value='$d_month[$keys]'>$d_month[$keys]-
						<input type='hidden' size='4' name='d_year[$keys]' maxlength='4' value='$d_year[$keys]'>$d_year[$keys]
					</td>
					<td nowrap>".CUR." $amts<input type='hidden' name='recvd[]' value='$keys'></td>
				</tr>";
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

	$totstkamt = sprint($totstkamt);
	$SUBTOT = sprint($resub);
	$VAT = sprint($scvat + $revat);
	$TOTAL = sprint($SUBTOT + $VAT + $shipexvat);

	if(isset($invoice)) {
		$fex = "<input type='hidden' name='invoice' value='invoice'>";
	} else {
		$fex = "";
	}

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

		# If vat is not included (shipchrg)
	//	$VATP = TAX_VAT;
		if($pur['vatinc'] == "no"){
			$scvat = sprint(($VATP/100) * $shipchrg);
			$shipexvat = $shipchrg;
		}elseif($pur['vatinc'] == "yes"){
			$scvat = sprint(($shipchrg/($VATP+100)) * $VATP);
			$shipexvat = ($shipchrg - $scvat);
		}else{
			$scvat = 0;
			$shipexvat  = $shipchrg;
		}
	}else{
		$scvat = 0;
		$shipexvat  = $shipchrg;
	}

	$SUBTOT += $shipexvat;

	if (isset($gds_note) AND strlen($gds_note) > 1){
		$show_gds_note = "Yes <input type='hidden' name='gds_note' value='yes'>";
	}else {
		$show_gds_note = "No";
	}

	$displaytotstkamt = "<input type='text' size='10' name='totstkamt' value='$totstkamt'>";

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Confirm Stock Order received</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='isubtot' value='$SUBTOT'>
			<input type='hidden' name='ivat' value='$VAT'>
			<input type='hidden' name='itotal' value='$TOTAL'>
			$fex
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
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
							<td valign='center'>$sup[supname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account number</td>
							<td valign='center'>$sup[supno]</td>
						</tr>
						<tr class='".bg_class()."'>
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
						<tr class='".bg_class()."'>
							<td>Purchase No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Inv</td>
							<td valign='center'>$pur[supinv]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='hidden' name='refno' value='$refno'>$refno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Delivery Charges</td>
							<td valign='center'>".CUR." <input type='hidden' name='shipchrg' size='10' value='$pur[shipchrg]'>$pur[shipchrg]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Print Goods Received Note</td>
							<td>$show_gds_note</td>
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
							<td rowspan='5' valign='top' width='50%'></td>
						</tr>
						<tr>
							<td class='".bg_class()."'><a href='purchase-new.php'>New Stock Order</a></td>
							<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$remarks</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='purchase-view.php'>View Stock Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
					<table ".TMPL_tblDflts." width='80%'>
						<tr class='".bg_class()."'>
							<th>Received Stock Cost</th>
							<td align='right' nowrap>".CUR." $totstkamt</td>
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

	/*<tr class='bg-even'><td>Delivery Charges</td><td align=right>".CUR." $shipexvat</td></tr>
	<tr class='bg-odd'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $VAT</td></tr>
	<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>*/
	return $details;

}


# details
function write()
{

	# Get vars
	extract($_REQUEST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qtys[$keys] <= 0){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity must be more than zero. Product number : <b>".($keys+1)."</b>");
			}
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");

			# Validate ddate[]
			$v->isOk ($d_day[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($d_month[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($d_year[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $d_year[$keys]."-".$d_month[$keys]."-".$d_day[$keys];
			if(!checkdate($d_month[$keys], $d_day[$keys], $d_year[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
			}
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				if(!ext_isUnique(ext_remBlnk($sernos))){
					$v->isOk ("error", "num", 1, 1, "Error : Serial numbers must be unique per Stock Item.");
				}else{
					foreach($recvd as $sk => $keys){
						if(isset($sernos[$keys]) && $v->isOk ($sernos[$keys], "string", 1, 20, "Error : Invalid Serial number.")){
							if((ext_findSer($sernos[$keys]) != false)){
								$v->isOk ("#", "string", 1, 20, "Error : Serial number already exists.");
							}
						}
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



	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li> - Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$td = $pur['pdate'];

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
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
		$dept['deptname'] = "<i class='err'> - Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

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

		# If vat is not included (shipchrg)
		if($pur['vatinc'] == "no"){
			$scvat = sprint(($VATP/100) * $shipchrg);
			$shipexvat = $shipchrg;
		}elseif($pur['vatinc'] == "yes"){
			$scvat = sprint(($shipchrg/($VATP+100)) * $VATP);
			$shipexvat = ($shipchrg - $scvat);
		}else{
			$scvat = 0;
			$shipexvat  = $shipchrg;
		}
	} else{
		$scvat = 0;
		$shipexvat  = $shipchrg;
	}

	db_conn("exten");

	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_conn(PRD_DB);

		# get last ref number
		$refnum = getrefnum();

		db_connect();

		# amount of stock in
		$totstkamt = array();
		$resub = 0;

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

	$flag = TRUE;
	$checkid = 0;
	$nonstot = 0;
	foreach($recvd as $sk => $keys){

		if($checkid == $ids[$keys]){
			$flag = FALSE;
		}else {
			$flag = TRUE;
		}

		$checkid = $ids[$keys];

		# Skip zeros
		if($qtys[$keys] <= 0){
			continue;
		}

		db_connect();

		# Get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		if($stk['units'] < 0) {
			$min_stock = abs($stk['units']);

			if ( $qtys[$keys] < $min_stock ) {
				$min_stock = $qtys[$keys];
			}
		} else {
			$min_stock = 0;
		}

		# Get selected stock line
		$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if($pur['vatinc'] == "yes"){
			$unitcost[$keys] = sprint(($stkd['amt'] - $stkd['svat'])/$stkd['qty']);
		}else{
			$unitcost[$keys] = sprint(($stkd['amt'])/$stkd['qty']);
		}

		//$perc[$keys] = sprint((($unitcost[$keys]*$qtys[$keys])/$pur['subtot']) * 100);

		$perc[$keys] = sprint(($unitcost[$keys] / $pur['subtot']) * 100);



		$ffs = $perc[$keys] * $qtys[$keys];


		# Get percentage from shipping charges excluding vat
		$shipc[$keys] = sprint(($perc[$keys] / 100) * $shipexvat);

		//print "cost: percent:$ffs ship: part1".($unitcost[$keys]*$qtys[$keys])."part2".($shipc[$keys]*$qtys[$keys])."<br>";

		# add delivery charges = amt + del chrg excluding vat
		$unitcost[$keys] += $shipc[$keys];

		if($stkd['udiscount'] > 0){
			$discps = round((($stkd['udiscount']/100) * $unitcost[$keys]), 2);
		}else {
			$discps = 0;
		}
		$amt[$keys] = sprint($qtys[$keys] * $unitcost[$keys]);

		#serialized items are broken into multiples .... we only want to process the first ... so FLAG is used


		if(isset($invoice)) {
			$iq = $qtys[$keys];

			$iq += 0;

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl) or errDie("Unable to get data.");

			$vd = pg_fetch_array($Ri);

			if($pur['vatinc'] == "yes") {
				$iamount = $stkd['amt'];
			} else {
				$iamount = sprint($stkd['amt'] + $stkd['svat']);
			}

			if($flag)
				vatr($vd['id'],$pur['pdate'],"INPUT",$vd['code'],$refnum,"VAT for Purchase No. $pur[purnum]",-$iamount,-$stkd['svat']);

			$Sl = "UPDATE pur_items SET iqty=iqty-'$iq' WHERE id='$stkd[id]'";
			$Ri = db_exec($Sl) or errDie("Unable to update invoice qty.");
		}

		$resub += $amt[$keys];

		# Update purchase items
		$sql = "
			UPDATE pur_items 
			SET rqty = (rqty + '$qtys[$keys]'), ddate = '$ddate[$keys]' 
			WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

		$cc = "";

		if($stkd['account'] > 0) {

			if($pur['vatinc'] == "yes"){
				#calculate the vat of this amount as we dont store it !!
				$vatcod = $stkd['vatcode'] + 0;
				$get_v = "SELECT vat_amount FROM vatcodes WHERE id = '$vatcod' LIMIT 1";
				$run_v = db_exec($get_v) or errDie("Unable to get vatcode information.");
				$varr = pg_fetch_array($run_v);
				$clearvat = $varr['vat_amount'] + 0;
				$remvat = sprint(($stkd['amt']/($clearvat+100)) * $clearvat);
				$nonstot = $nonstot + $stkd['amt'] - $remvat;
			}else {
				$nonstot = $nonstot + $stkd['amt'];
			}

			$stk['whid'] = $stkd['account'];

			$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' LIMIT 1";
			$banks = db_exec($sql);
			if(pg_numrows($banks) < 1){
				return "<li class='err'> There are no accounts held at the selected Bank.
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
			}
			$barr = pg_fetch_array($banks);
			$bankid = $barr['bankid'];

			core_connect();

			$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
			# Check if link exists
			if(pg_numrows($rslt) < 1){
				return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
			}

			$banklnk = pg_fetch_array($rslt);

			$cc_trantype = cc_TranTypeAcc($stkd['account'], $banklnk['accnum']);
		} else {
			# Update stock(ordered + qty, units + qty, csamt + (csamt + amt))
			$sql = "
				UPDATE stock 
				SET ordered = (ordered - '$qtys[$keys]'), units = (units + '$qtys[$keys]' +'$min_stock'), csamt = (csamt + '$amt[$keys]') 
				WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			if(isset($sers[$stkids[$keys]][$keys])){
				ext_InSer($sers[$stkids[$keys]][$keys], $stkids[$keys], $pur['supname'], $pur['purnum'], "pur",$td);

				$serial = $sers[$stkids[$keys]][$keys];

				db_connect();
				$sql = "
					INSERT INTO pserec (
						purid, purnum, stkid, serno, div
					) VALUES (
						'$purid', '$pur[purnum]', '$stkids[$keys]', '$serial', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to update stock serials in Cubit.",SELF);
			}


			# Get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			//$sdate = date("Y-m-d");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $td, $qtys[$keys], $amt[$keys], "Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]");
			db_connect();
			$cspric = sprint($amt[$keys]/$qtys[$keys]);
			$sql = "
				INSERT INTO stockrec (
					edate, stkid, stkcod, stkdes, trantype, qty, csprice, 
					csamt, details, div
				) VALUES (
					'$td', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'purchase', '$qtys[$keys]', '$amt[$keys]', 
					'$cspric', 'Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);

			# Just wanted to fix the xxx.xxxxxxe-x value
			if($stk['units'] > 0){
				$csprice = round(($stk['csamt']/$stk['units']), 2);
			}else{
				$csprice = round($stk['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "
				UPDATE stock 
				SET csprice = '$csprice', lcsprice = '$cspric' 
				WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		}

		# Keep records for transactions
		if(isset($totstkamt[$stk['whid']])){
			$totstkamt[$stk['whid']] += $amt[$keys];
		}else{
			$totstkamt[$stk['whid']] = $amt[$keys];
		}

		db_connect ();

		# check if there are any outstanding items
		$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		# if none the set to received
		if(pg_numrows($stkdRslt) < 1){
			# update surch_int(received = 'y')
			$sql = "UPDATE purchases SET received = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Orders in Cubit.",SELF);
		}

		if($min_stock > 0) {
			$cost = sprint($unitcost[$keys]*$min_stock);

			db_conn("exten");

			$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);
			$stockacc = $wh['stkacc'];
			$cosacc = $wh['cosacc'];

			db_connect();

			$Sl = "UPDATE stock SET csamt = (csamt - '$cost'),units=(units-'$min_stock') WHERE stkid='$stkids[$keys]'";
			$Ri = db_exec($Sl);

			writetrans($cosacc, $stockacc,$td, $refnum, $cost, "Cost Of Sales for stock sold before purchase $pur[purnum]");

			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $td, 0,$cost , "Cost Of Sales for stock sold before purchase $pur[purnum]");

			db_connect();

			$Sl = "
				INSERT INTO pcost (
					purnum, cost, qty, rqty, stkid
				) VALUES (
					'$pur[purnum]', '$unitcost[$keys]', '$min_stock', '0', '$stk[stkid]'
				)";
			$Ri = db_exec($Sl);

		}

	}



//	$darr = explode ("-",$date);
//	$cdate = "$darr[2]-$darr[1]-$darr[0]";

	#if non stock total is set, process the cost center
	if($nonstot != "0"){
		$nonstot = sprint ($nonstot);
		if($cc_trantype != false){
			$date = date("Y-m-d");
			$cc .= "
				<script>
					CostCenter('$cc_trantype', 'Non Stock Purchase', '$date', '$stkd[description]', $nonstot, '');
				</script>";
		}else{
			$cc .= "";
		}
	}





	if(isset($invoice)) {
		###################VAT CALCS#######################
		$pur['delvat'] += 0;
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$pur[delvat]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			$Sl = "SELECT * FROM vatcodes";
			$Ri = db_exec($Sl);
		}

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$excluding = "y";
		} else {
			$excluding = "";
		}

		$vr = vatcalc($shipchrg,$pur['vatinc'],$excluding,0,$vd['vat_amount']);

		$vrs = explode("|",$vr);
		$ivat_tmp = $vrs[0];
		$iamount_tmp = $vrs[1];

		vatr($vd['id'],$td,"INPUT",$vd['code'],$refnum,"VAT Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].",sprint(-$iamount_tmp),-$scvat);

		####################################################

		db_conn("exten");

		$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		db_connect();
		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$itotal') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$sql = "
			INSERT INTO sup_stmnt (
				supid, edate, cacc, amount, descript, ref, ex, div
			) VALUES (
				'$pur[supid]', '$pur[pdate]', '$wh[conacc]', '$itotal', 
				'Stock Received - Purchase $pur[purnum] Inv:$pur[supinv]', '$refnum', '$pur[purnum]', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $dept['credacc'], $td, $refnum, $isubtot, "Invoice Received for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Transfer vat
		writetrans($vatacc, $dept['credacc'], $td, $refnum, $ivat, "VAT Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Ledger Records
		suppledger($pur['supid'], $wh['conacc'], $td, $pur['purid'], "Purchase No. $pur[purnum] received.", $itotal, 'c');
		db_connect();

		/* End Transactions */

		/* Make transaction record  for age analysis */
			db_connect();
			# update the supplier age analysis (make balance less)
			if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
				# Found? Make amount less
				$sql = "UPDATE suppurch SET balance = (balance + '$itotal') WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}else{
				/* Make transaction record for age analysis */
				$sql = "
					INSERT INTO suppurch (
						supid, purid, pdate, balance, div
					) VALUES (
						'$pur[supid]', '$pur[purnum]', '$pur[pdate]', '$itotal', '".USER_DIV."'
					)";
				$purcRslt = db_exec($sql) or errDie("Unable to update Order information in Cubit.",SELF);
			}

		/* Make transaction record  for age analysis */

		# commit updating
		$sql = "UPDATE purchases SET iamount = iamount+'$itotal',ivat=ivat+'$ivat' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		$sql = "SELECT SUM(iqty) FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		$data = pg_fetch_array($stktRslt);

		$left = $data['sum'];

		if($left == 0) {

		/* Start moving if purchase */
			if($pur['received'] == "y"){

				if(strlen($pur['appdate']) < 8) {
					$pur['appdate'] = date("Y-m-d");
				}

				# copy purchase
				db_conn($pur['prd']);

				$sql = "
					INSERT INTO purchases (
						purid, deptid, supid, supname, supaddr, supno, 
						terms, pdate, ddate, shipchrg, subtot, total, 
						balance, vatinc, vat, shipping, remarks, refno, received, done, 
						div, purnum, supinv, ordernum, appname, appdate
					) VALUES (
						'$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', 
						'$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', 
						'0', '$pur[vatinc]', '$pur[vat]', '$pur[shipping]', '$remarks', '$pur[refno]', 'y', 'y', 
						'".USER_DIV."', '$pur[purnum]', '$supinv', '$pur[ordernum]', '$pur[appname]', '$pur[appdate]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

				/*-- Cost varience -- */
				//$nsubtot = sprint($pur['total'] - $pur['vat']);
				$nsubtot = sprint($p['iamount'] - $p['ivat']);

				if($p['rsubtot'] > $nsubtot){
					$diff = sprint($p['rsubtot'] - $nsubtot);
					# Debit Stock Control and Credit Creditors control
					writetrans($wh['conacc'], $cvacc, $td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
				}elseif($nsubtot > $p['rsubtot']){
					$diff = sprint($nsubtot - $pur['rsubtot']);
					# Debit Stock Control and Credit Creditors control
					writetrans($cvacc, $wh['conacc'],$td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
				}
				/*-- End Cost varience -- */

				db_connect();
				# Get selected stock
				$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
				$stktcRslt = db_exec($sql);

				while($stktc = pg_fetch_array($stktcRslt)){
					# Insert purchase items
					db_conn($pur['prd']);
					$sql = "
						INSERT INTO pur_items (
							purid, whid, stkid, qty, rqty, unitcost, 
							amt, svat, ddate, div
						) VALUES (
							'$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', 
							'$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '".USER_DIV."'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
				}

				# begin updating
				//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

					db_connect();

					# Remove the purchase from running DB
					$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
					$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

					# Record where purchase is
					$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
					$movRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

					# Remove those purchase items from running DB
					$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
					$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

				/* End moving purchase received */

				# commit updating
				//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

			}else{
				# insert Order to DB
				$sql = "UPDATE purchases SET invcd = 'y',supinv='$pur[supinv]' WHERE purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);
			}
		}
	}

	if(strlen($refno) > 0){
		if(strlen($pur['refno']) > 0)
			$refno = "$pur[refno]-$refno";
		else
			$refno = $refno;
	}else{
		$refno = $pur['refno'];
	}

	db_connect ();

	# Update purchase on the DB
	if($pur['part'] == 'y'){
		$sql = "
			UPDATE purchases 
			SET rsubtot = (rsubtot + '$resub'), refno = '$refno', remarks = '$remarks', edit = 1 
			WHERE purid = '$purid'";
	}else{
		$sql = "
			UPDATE purchases 
			SET part = 'y', rsubtot = (rsubtot + '$resub'), refno = '$refno', remarks = '$remarks', edit = 1 
			WHERE purid = '$purid'";
	}
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	/* Transactions */

		db_conn(PRD_DB);
		# get last ref number
		//$refnum = getrefnum();

	/* - Start Hooks - */


	/* - End Hooks - */

		# Record transaction  from data
		foreach($totstkamt as $whid => $wamt){
			# get whouse info
			db_conn("exten");
			$sql = "SELECT stkacc,conacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);

			if(pg_num_rows($whRslt) < 1) {
				$sql = "SELECT stkacc,conacc FROM warehouses";
				$whRslt = db_exec($sql);

				$wh = pg_fetch_array($whRslt);

				$wh['stkacc'] = $whid;
			} else {
				$wh = pg_fetch_array($whRslt);
			}

			# Debit Stock and Credit Stock control
			writetrans($wh['stkacc'], $wh['conacc'], $td, $refnum, $wamt, "Stock Received for Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}

# commit updating

/*** pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* Start moving if Order received and invoiced */

		# Get purchase info
		db_connect();

		$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li> - Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y" && $pur['invcd'] == 'y'){

			if(strlen($pur['appdate']) < 8) {
				$pur['appdate'] = date("Y-m-d");
			}

			# copy purchase
			db_conn(PRD_DB);

			$sql = "
				INSERT INTO purchases (
					purid, deptid, supid, supname, supaddr, supno, 
					terms, pdate, ddate, shipchrg, subtot, total, balance, 
					vatinc, vat, shipping, remarks, refno, received, done, div, 
					purnum, supinv, ordernum, appname, appdate, delvat
				) VALUES (
					'$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', 
					'$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', 
					'$pur[vatinc]', '$pur[vat]', '$pur[shipping]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', 
					'$pur[purnum]', '$pur[supinv]', '$pur[ordernum]', '$pur[appname]', '$pur[appdate]', '$pur[delvat]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

			/*-- Cost varience -- */
			$nsubtot = sprint($pur['total'] - $pur['vat']);
			if($pur['rsubtot'] > $nsubtot){
				$diff = sprint($pur['rsubtot'] - $nsubtot);
				# Debit Stock Control and Credit Creditors control
				writetrans($wh['conacc'], $cvacc, $td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}elseif($nsubtot > $pur['rsubtot']){
				$diff = sprint($nsubtot - $pur['rsubtot']);
				# Debit Stock Control and Credit Creditors control
				writetrans($cvacc, $wh['conacc'], $td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}
			/*-- End Cost varience -- */

			db_connect();

			# Get selected stock
			$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert purchase items
				db_conn(PRD_DB);
				$sql = "
					INSERT INTO pur_items (
						purid, whid, stkid, qty, rqty, unitcost, 
						amt, svat, ddate, div, vatcode, 
						account, description, udiscount
					) VALUES (
						'$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', 
						'$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '".USER_DIV."','$stktc[vatcode]', 
						'$stktc[account]', '$stktc[description]', '$stktc[udiscount]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();

			# Remove the purchase from running DB
			$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Record where purchase is
			$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


/* End moving purchase received */

	if (isset($gds_note) AND strlen($gds_note) > 0){
		$cc .= "
			<script>
				printer(\"".SELF."?key=recv_print&purid=$purid\");
			</script>";
	}

	// Final Layout
	$write = "
		$cc
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Order received</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order receipt from Supplier <b>$sup[supname]</b> has been recorded.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='purchase-new.php'>New Purchase</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='purchase-view.php'>View Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



function recv_print()
{

	extract ($_REQUEST);

	$union_pur = array();
	$union_items = array();

	$union_pur[] = "
		SELECT deptname, supname, supno, supaddr, purnum, supinv, refno, terms, 
			pdate, vatinc, rshipchrg 
		FROM cubit.purchases 
			LEFT JOIN exten.departments ON purchases.deptid=departments.deptid 
		WHERE purid = '$purid'";

	$union_items[] = "
		SELECT whname, stkcod, vatcodes.description AS vatdesc, vatcodes.code, 
			pur_items.description AS item_desc, qty, rqty, unitcost, ddate, 
			amt, svat, stock.stkdes AS stock_desc, stock.stkid, udiscount 
		FROM cubit.pur_items 
			LEFT JOIN exten.warehouses ON pur_items.whid=warehouses.whid 
			LEFT JOIN cubit.stock ON pur_items.stkid=stock.stkid 
			LEFT JOIN cubit.vatcodes ON pur_items.vatcode=vatcodes.id 
		WHERE purid = '$purid'";


	for ($i = 1; $i <= 14; $i++) {

		$union_pur[] = "
			SELECT deptname, supname, supno, supaddr, purnum, supinv, refno, terms,
				pdate, vatinc, rshipchrg 
			FROM \"$i\".purchases
				LEFT JOIN exten.departments ON purchases.deptid=departments.deptid
			WHERE purid='$purid'";

		$union_items[] = "
			SELECT whname, stkcod, vatcodes.description AS vatdesc, vatcodes.code,
				pur_items.description AS item_desc, qty, rqty, unitcost, ddate,
				amt, svat, stock.stkdes AS stock_desc, stock.stkid, udiscount
			FROM \"$i\".pur_items
				LEFT JOIN exten.warehouses ON pur_items.whid=warehouses.whid
				LEFT JOIN cubit.stock ON pur_items.stkid=stock.stkid
				LEFT JOIN cubit.vatcodes ON pur_items.vatcode=vatcodes.id
			WHERE purid='$purid'";

	}

	$sql = implode(" UNION ", $union_items);
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve purchase items.");

	$sql = implode(" UNION ", $union_pur);
	$pur_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");
	$pur_data = pg_fetch_array($pur_rslt);

	$total_amount = 0;
	$total_vat = 0;
	$item_out = "";
	while ($item_data = pg_fetch_array($item_rslt)) {

		if (isset($item_data['stkid']) AND $item_data['stkid'] != "0")
			$item_data['item_desc'] = $item_data['stock_desc'];

		$item_out .= "
			<tr>
				<td>$item_data[whname]&nbsp;</td>
				<td>$item_data[stkcod]&nbsp;</td>
				<td>$item_data[vatdesc]&nbsp;</td>
				<td>$item_data[item_desc]&nbsp;</td>
				<td>".sprint3($item_data['rqty'])."&nbsp;</td>
				<td>$item_data[unitcost]&nbsp;</td>
				<td>$item_data[udiscount]&nbsp;</td>
				<td>$item_data[ddate]&nbsp;</td>
				<td>".CUR." $item_data[amt]&nbsp;</td>
				<td>".CUR." $item_data[svat]&nbsp;</td>
			</tr>";

		$total_amount += $item_data['amt'];
		$total_vat += $item_data['svat'];

	}

	$OUTPUT = "
		<table ".TMPL_tblDflts." border='1'>
			<tr>
				<td colspan='10'><h3>Goods Received</h3><td>
			</tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td width='50%'>
								<table width='100%'>
									<tr>
										<td colspan='2'><strong>Supplier Details</strong></td>
									</tr>
									<tr>
										<td>Department</td>
										<td>$pur_data[deptname]</td>
									</tr>
									<tr>
										<td>Supplier</td>
										<td>$pur_data[supname]</td>
									</tr>
									<tr>
										<td>Account Number</td>
										<td>$pur_data[supno]</td>
									</tr>
									<tr>
										<td>Supplier Address</td>
										<td>".nl2br($pur_data["supaddr"])."</td>
									</tr>
								</table>
							</td>
							<td width='50%'>
								<table width='100%'>
									<tr>
										<td colspan='2'><strong>Purchase Details</strong></td>
									</tr>
									<tr>
										<td>Purchase No.</td>
										<td>$pur_data[purnum]</td>
									</tr>
									<tr>
										<td>Supplier Inv</td>
										<td>$pur_data[supinv]</td>
									</tr>
									<tr>
										<td>Delivery Ref No</td>
										<td>$pur_data[refno]</td>
									</tr>
									<tr>
										<td>Terms</td>
										<td>$pur_data[terms] Days</td>
									</tr>
									<tr>
										<td>Date</td>
										<td>$pur_data[pdate]</td>
									</tr>
									<tr>
										<td>VAT Inclusive</td>
										<td>$pur_data[vatinc]</td>
									</tr>
									<tr>
										<td>Delivery Charges</td>
										<td>$pur_data[rshipchrg]</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td><b>Store</b></td>
				<td><b>Stock</b></td>
				<td><b>VAT Code</b></td>
				<td><b>Description</b></td>
				<td><b>Qty Received</b></td>
				<td><b>Price per Unit</b></td>
				<td><b>Discount</b></td>
				<td><b>Delivery Date</b></td>
				<td><b>Amount</b></td>
				<td><b>VAT</b></td>
			</tr>
			$item_out
			<tr>
				<td colspan='8' align='right'><b>Total:&nbsp</b></td>
				<td>".CUR." ".sprint($total_amount)."</td>
				<td>".CUR." ".sprint($total_vat)."</td>
			</tr>
		</table>";
	require ("tmpl-print.php");

}


?>

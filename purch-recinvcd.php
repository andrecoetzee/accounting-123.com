<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# Decide what to do
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "confirm":
				$OUTPUT = confirm($_POST);
				break;
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# Get templete
require("template.php");




function details($_POST, $error="")
{

	$showvat = TRUE;

	# get vars
	extract ($_POST);

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

	# check if purchase has been received
	if($pur['invcd'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.";
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

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$i++;

		if($stkd['account'] > 0) {
			$stk['stkdes'] = $stkd['description'];
			$stk['stkid'] = 0;
			$stk['stkcod'] = "";
		}

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td><input type='text' name='qty[$stkd[id]]' size='3' value='$stkd[iqty]'></td>
				<td>$stkd[unitcost]</td>
				<td>$stkd[udiscount]</td>
				<td>$sday-$smon-$syear</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Record Purchase Invoice</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
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
							<td>Supp Inv No.</td>
							<td valign='center'><input type='text' name='supinv' size='10' value='$pur[supinv]'></td>
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
							<td valign='center'>".CUR." $pur[shipchrg]</td>
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
							<td rowspan='5' valign='top' width=50%>$error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='purchase-new.php'>New purchase</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='purchase-view.php'>View purchases</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width=80%>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." ".sprint($pur['subtot'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>".CUR." $pur[shipping]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right'>".CUR." ".sprint($pur['vat'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $pur[total]</td>
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



# Details
function confirm($_POST, $error="")
{

	# get vars
	extract ($_POST);

	#make sure the date vars are all cozy
	$p_day += 0;
	$p_month += 0;
	$p_year += 0;

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase Number.");

	#check the date ...
	$v->isOk ($p_day, "num", 1, 2, "Invalid Invoice Day.");
	$v->isOk ($p_month, "num", 1, 2, "Invalid Invoice Month.");
	$v->isOk ($p_year, "num", 1, 4, "Invalid Invoice Year.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
		$v->isOk ($pdate, "num", 1, 1, "Invalid Invoice Date.");
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
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['invcd'] == "y"){
		$error = "<li class='err'> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.</li>";
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

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
//	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */
	$vatinc=$pur['vatinc'];
/* --- Start Products Display --- */
	$VATP = TAX_VAT;
	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>UNIT PRICE</th>
				<th>DISCOUNT</th>
				<th>DELIVERY DATE</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	$tot = 0;
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$i++;
		$qty[$stkd['id']] += 0;

		$amt[] = $stkd['amt'];

		# put in product
		$keys = $key;
		$amt[$keys] = $stkd['amt'];

		if($stkd['udiscount'] > 0){
			$discps = round((($stkd['udiscount']/100) * $stkd['unitcost']), 2);
		}else {
			$discps = 0;
		}
		$amt[$keys] = sprint($qty[$stkd['id']] * ($stkd['unitcost'] - $discps));
		$stkd['amt'] = sprint(($stkd['unitcost'] - $discps)*$qty[$stkd['id']]);

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];

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

		if($qty[$stkd['id']] > $stkd['iqty']) {
			return "You cannot invoice more than you ordered.";
		}

 		$tot += $qty[$stkd['id']];

		if($stkd['account'] > 0) {
			$stk['stkdes'] = $stkd['description'];
			$stk['stkid'] = 0;
			$stk['stkcod'] = "";
		}

		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td><input type='hidden' name=qty[$stkd[id]] size='3' value='".$qty[$stkd['id']]."'>".sprint3($qty[$stkd['id']])."</td>
				<td>$stkd[unitcost]</td>
				<td>$stkd[udiscount]</td>
				<td>$sday-$smon-$syear</td>
				<td>".CUR." $stkd[amt]</td>
			</tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

	if(isset($amt)){
		$SUBTOT = array_sum($amt);
	}else{
		$SUBTOT = 0.00;
	}

	if($tot == 0) {
		return "You cannot invoice zero items";
	}

	# If vat is not included (delchrg)

 	$shipchrg = $pur['shipchrg'];

	if($pur['iamount'] > 0) {
		$shipchrg = 0;
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
		$TOTAL = sprint($TOTAL + $VAT + $svat);
	}else{
		$TOTAL = sprint($TOTAL + $svat);
		$SUBTOT -= ($VAT);
	}

	$VAT += $svat;

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Record Purchase Invoice</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='TOTAL' value='$TOTAL'>
			<input type='hidden' name='SUBTOT' value='$SUBTOT'>
			<input type='hidden' name='VAT' value='$VAT'>
			<input type='hidden' name='del' value='$shipexvat'>
			<input type='hidden' name='remarks' value='$remarks'>
			<input type='hidden' name='supinv' value='$supinv'>
			<input type='hidden' name='p_day' value='$p_day'>
			<input type='hidden' name='p_month' value='$p_month'>
			<input type='hidden' name='p_year' value='$p_year'>
		<table ".TMPL_tblDflts." width=95%>
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
							<td>Supp Inv No.</td>
							<td valign='center'>$supinv</td>
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
							<td valign='center'>$p_day-$p_month-$p_year</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$pur[vatinc]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td valign='center'>".CUR." $pur[shipchrg]</td>
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
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($remarks)."</td>
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
							<td align='right'>".CUR." ".sprint($SUBTOT)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>".CUR." $shipexvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." ".sprint($VAT)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $TOTAL</td>
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
	return $details;

}



# details
function write($_POST)
{

	# Get vars
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($supinv, "string", 0, 255, "Invalid supp inv.");

	# used to generate errors
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
	db_connect();

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

//	$td = $pur['pdate'];
	$td = "$p_year-$p_month-$p_day";

	# check if purchase has been received
	if($pur['invcd'] == "y"){
		$error = "<li class='err'> Error : Purchase number <b>$pur[purnum]</b> has already been invoiced.</li>";
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

	# Get warehouse name
	db_conn("exten");

	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$refnum = getrefnum();

		# get selected stock in this purchase
		db_connect();

		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stktRslt)){

			$iq = $qty[$stkd['id']];

			$iq += 0;

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl) or errDie("Unable to get data.");

			$vd = pg_fetch_array($Ri);

			if($pur['vatinc'] == "yes") {
				$iamount = $stkd['amt'];
			} else {
				$iamount = sprint($stkd['amt']+$stkd['svat']);
			}
//$pur['pdate'] -> $td
			vatr($vd['id'],$td,"INPUT",$vd['code'],$refnum,"VAT for Purchase No. $pur[purnum]",-$iamount,-$stkd['svat']);

			$Sl = "UPDATE pur_items SET iqty=iqty-'$iq' WHERE id='$stkd[id]'";
			$Ri = db_exec($Sl) or errDie("Unable to update invoice qty.");
		}

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

		$vr = vatcalc($pur['shipchrg'],$pur['vatinc'],$excluding,0,$vd['vat_amount']);

		$vrs = explode("|",$vr);
		$ivat_tmp = $vrs[0];
		$iamount_tmp = $vrs[1];

		vatr($vd['id'],$td,"INPUT",$vd['code'],$refnum,"VAT Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].",sprint(-$iamount_tmp),-$ivat_tmp);

		####################################################
		/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

		/* - End Hooks - */

		# Record the payment on the statement
		db_connect();

		$sdate = date("Y-m-d");
		$DAte = date("Y-m-d");

		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$TOTAL') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
//$pur['pdate'] -> $td
		$sql = "
			INSERT INTO sup_stmnt (
				supid, edate, cacc, amount, descript, 
				ref, ex, div
			) VALUES (
				'$pur[supid]', '$td', '$wh[conacc]', '$TOTAL', 'Stock Received - Purchase $pur[purnum] Inv:$supinv', 
				'$refnum', '$pur[purnum]', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $dept['credacc'], $td, $refnum, sprint($SUBTOT+$del), "Invoice Received for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Transfer vat
		writetrans($vatacc, $dept['credacc'], $td, $refnum, $VAT, "VAT Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Ledger Records
		suppledger($pur['supid'], $wh['conacc'], $td, $pur['purid'], "Purchase No. $pur[purnum] received.", $TOTAL, 'c');

		/* End Transactions */

		/* Make transaction record  for age analysis */
			db_connect();
			# update the supplier age analysis (make balance less)
			if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
				# Found? Make amount less
				$sql = "
					UPDATE suppurch 
					SET balance = (balance + '$TOTAL') 
					WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}else{
//$pur['pdate'] -> $td
				/* Make transaction record for age analysis */
				$sql = "
					INSERT INTO suppurch (
						supid, purid, pdate, balance, div
					) VALUES (
						'$pur[supid]', '$pur[purnum]', '$td', '$TOTAL', '".USER_DIV."'
					)";
				$purcRslt = db_exec($sql) or errDie("Unable to update Order information in Cubit.",SELF);
			}

		/* Make transaction record  for age analysis */

	# commit updating
	$sql = "UPDATE purchases SET iamount = iamount+'$TOTAL',ivat=ivat+'$VAT' WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");

	$p=pg_fetch_array($purRslt);

	//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

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

//$pur['pdate'] -> $td

			$sql = "
				INSERT INTO purchases (
					purid, deptid, supid, supname, supaddr, supno, terms, 
					pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, 
					shipping, remarks, refno, received, done, div, purnum, supinv, 
					ordernum, appname, appdate, delvat
				) VALUES (
					'$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', '$pur[terms]', 
					'$td', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', 
					'$pur[shipping]', '$remarks', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]', '$supinv', 
					'$pur[ordernum]', '$pur[appname]', '$pur[appdate]', '$pur[delvat]'
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

				db_conn($pur['prd']);

				$sql = "
					INSERT INTO pur_items (
						purid, whid, stkid, qty, rqty, unitcost, 
						amt, svat, ddate, div, vatcode, 
						account, description, udiscount
					) VALUES (
						'$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', 
						'$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '".USER_DIV."', '$stktc[vatcode]', 
						'$stktc[account]', '$stktc[description]', '$stktc[udiscount]'
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
			$sql = "INSERT INTO movpurch (purtype, purnum, prd, div) VALUES ('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

			/* End moving purchase received */

			# commit updating
			//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}else{
			# insert Order to DB
			$sql = "UPDATE purchases SET invcd = 'y', supinv='$supinv', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);
		}
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase Invoiced</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Purchase Invoice from Supplier <b>$pur[supname]</b> has been recorded.</td>
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

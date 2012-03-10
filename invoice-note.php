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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = details($HTTP_POST_VARS);
		}
} else {
	$OUTPUT = details($HTTP_GET_VARS);
}

require("template.php");




function details($HTTP_GET_VARS,$err="")
{

	$showvat = TRUE;

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$inv['chrgvat'] = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$inv['chrgvat'] = "No";
	}else{
		$inv['chrgvat'] = "Non VAT";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		$err
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>WAREHOUSE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>SERIAL NO.</th>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
			<tr>";

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT *, (qty - noted) as qty FROM inv_items  WHERE invid = '$invid' AND qty > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	if(pg_numrows($stkdRslt) < 1){
		return "<li> The are not items on the invoice, all items have been returned.</li>";
	}

	$tcosamt = 0;
	$taxex = 0;
	$ai = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		if($stkd['qty'] == 0){
			continue;
		}

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

		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$taxex += ($stkd['amt']);
		}

		if($stkd['account'] > 0) {
			$stk['stkid'] = 0;
			$stk['stkcod'] = "";
			$stk['stkdes'] = $stkd['description'];
		}

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);
		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		#clear a var
		$stkd['amt'] = sprint ($stkd['amt']);

		if($stkd['account'] > 0){
			if($inv['chrgvat'] == "exc"){
				#get vat amt
				$get_v = "SELECT vat_amount FROM vatcodes WHERE id = '$stkd[vatcode]' LIMIT 1";
				$run_v = db_exec($get_v) or errDie("Unable to get vatcode information.");
				if(pg_numrows($run_v) < 1){
					$vat = 0;
				}else {
					$varr = pg_fetch_array($run_v);
					$vat = (($stkd['amt']/100)*$varr['vat_amount']);
				}
				$cosamt = round(($stkd['qty'] * $stkd['amt']) + $vat, 2);
			}else {
                #get vat amt
				$get_v = "SELECT vat_amount FROM vatcodes WHERE id = '$stkd[vatcode]' LIMIT 1";
				$run_v = db_exec($get_v) or errDie("Unable to get vatcode information.");
				if(pg_numrows($run_v) < 1){
					$vat = 0;
				}else {
					$varr = pg_fetch_array($run_v);
					$vat = (($stkd['amt']/(100+$varr['vat_amount']))*$varr['vat_amount']);
				}
				$cosamt = round(($stkd['qty'] * $stkd['amt']), 2);
			}
		}else {
			$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
			$tcosamt += $cosamt;
		}

		$stkd['qty'] = sprint3($stkd['qty']);

		# Put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td>
					<input type='hidden' name='ids[$ai]' value='$stkd[id]'>
					<input type='hidden' name='stkids[$ai]' value='$stk[stkid]'>$stk[stkcod]
				</td>
				<td>$stk[stkdes]</td>
				<td><input type='hidden' name='sers[$stkd[stkid]][$ai]' value='$stkd[serno]'>$stkd[serno]</td>
				<td>
					<input type='hidden' size='4' name='qts[$ai]' value='$stkd[qty]'>
					<input type='text' size='4' name='qtys[$ai]' value='$stkd[qty]'>
				</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td>
					<input type='hidden' size='4' name='disc[$ai]' value='$stkd[disc]'>$stkd[disc]<b> OR </b>
					<input type='hidden' size='4' name='discp[$ai]' value='$stkd[discp]' maxlength='5'>$stkd[discp]%
				</td>
				<td nowrap>".CUR." ".sprint($stkd["amt"])."</td>
			</tr>";
		++$ai;
	}
	$products .= "</table>";

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = round((($inv['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);
	$traddiscm = sprint($traddiscm);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

	// Retrieve the default comments
	if (!isset($comm)) {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$cmntRslt = db_exec($sql) or errDie("Unable to retrieve default comment from Cubit.");
		if (empty($inv["comm"])) {
			$comm = base64_decode(pg_fetch_result($cmntRslt, 0));
		} else {
			$comm = $inv["comm"];
		}
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$Sl = "SELECT * FROM cubit.settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");
	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Sales Person</td>
				<td valign='center'>$inv[salespn]</td>
			</tr>";
	} else {
		$sp = "";
	}

	if (!isset($o_year)) {
		list($o_year, $o_month, $o_day) = explode("-", $inv['odate']);
		//list($o_year, $o_month, $o_day) = explode("-", date("Y-m-d"));
	}

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Credit Note</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='tcosamt' value='$tcosamt'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer VAT Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'><textarea name='comm' rows='4' cols='20'>$comm</textarea></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Number</td>
							<td valign='center'>$inv[invnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$termssel Days</td>
						</tr>
						$sp
						<tr bgcolor='".bgcolorg()."'>
							<td>Credit Note Date</td>
							<td valign='center'>".mkDateSelect("o", $o_year, $o_month, $o_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='hidden' size='7' name='traddisc' value='$inv[traddisc]'>$inv[traddisc]%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='hidden' name='dct' value='$dct'><input type='text' size='7' name='delchrg' value='$dct'></td>
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
					<table ".TMPL_tblDflts.">
						<p>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
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
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $traddiscm</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right' nowrap>".CUR." $inv[delchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right' nowrap>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



# details
function confirm($HTTP_POST_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($o_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($o_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($o_year, "num", 1, 5, "Invalid Invoice Date year.");

	$odate = mkdate($o_year, $o_month, $o_day);
	$v->isOk ($odate, "date", 1, 1, "Invalid Invoice Date.");

	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	if($delchrg > $dct){
		$v->isOk ($delchrg, "float", 0, 0, "Error : Delivery Charge amount must not be more than the amount in the Invoice.");
	}

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ($qty, "float", 0, 0, "The Returned Quantity cannot be more than the quantity sold.");
			}
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		# $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return details($HTTP_POST_VARS, $err);
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($odate) >= strtotime($blocked_date_from) AND strtotime($odate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$vchrgvat = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$vchrgvat = "No";
	}else{
		$vchrgvat = "Non VAT";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>WAREHOUSE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<TH>SERIAL NO.</TH>
				<th>QTY RETURNED</th>
				<th>UNIT PRICE</th>
				<th>UNIT DISCOUNT</th>
				<th>AMOUNT</th>
			<tr>";

	$vatamount = 0;

	$c = 0;
	$taxex = 0;
	$ai = 0;
	foreach($qtys as $keys => $value){
		if ($qtys[$keys] > 0) {
			db_connect();
			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# get selected stock in this invoice
			$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			if($stkd['account'] == 0) {

				# get warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Calculate the Discount discount
				if($disc[$keys] < 1){
					if($discp[$keys] > 0){
						$disc[$keys] = (($discp[$keys]/100) * $stkd['unitcost']);
					}
				}else{
					$discp[$keys] = (($disc[$keys] * 100) / $stkd['unitcost']);
				}

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

				db_connect();
				$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					return "Please select the vatcode for all your stock.";
				}
				$vd = pg_fetch_array($Ri);

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				if($stk['exvat'] == 'yes'||$vd['zero'] == "Yes") {
					$excluding = "y";
				} else {
					$excluding = "";
				}

				$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs = explode("|",$vr);
				$ivat = $vrs[0];
				$iamount = $vrs[1];

				$vatamount += $ivat;

				# Check Tax Excempt
				if($stk['exvat'] == 'yes'||$vd['zero'] == "Yes"){
					$taxex += $amt[$keys];
				}

				if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

				$serial = $sers[$stk['stkid']][$keys];

				if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}

				$amt[$keys] = sprint ($amt[$keys]);

				vsprint($discp[$keys]);

				# Put in product
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$wh[whname]</td>
						<td><input type='hidden' name='ids[]' value='$ids[$keys]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
						<td>$stk[stkdes]</td>
						<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
						<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>".sprint3($qtys[$keys])."</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
						<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." ".sprint($amt[$keys])."</td>
					</tr>";
				$c++;

			} else {

				db_connect();

				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# get warehouse name
				db_conn("core");
				$sql = "SELECT accname FROM accounts WHERE accid = '$stkd[account]'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				$disc[$keys]=0;

				# Calculate amount
				$amt[$keys] = sprint($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

				db_connect();

				$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd = pg_fetch_array($Ri);

				if($vd['zero'] == "Yes") {
					$excluding = "y";
				} else {
					$excluding = "";
				}

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs = explode("|",$vr);
				$ivat = $vrs[0];
				$iamount = $vrs[1];

				$vatamount += $ivat;

				if($stkd['account'] > 0) {
					$wh['whname'] = "";
					$stk['stkid'] = 0;
					$stk['stkcod'] = $wh['accname'];
					$stk['stkdes'] = $stkd['description'];
				}


				# Check Tax Excempt
				if($vd['zero'] == "Yes"){
					$taxex += $amt[$keys];
				}

				if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys] = "";}

				$serial = $sers[$stk['stkid']][$keys];

				if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}
				# Put in product
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$wh[whname]</td>
						<td><input type='hidden' name='ids[]' value='$ids[$keys]'><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod]</td>
						<td>$stk[stkdes]</td>
						<td><input type='hidden' name='sers[$stkd[stkid]][]' value='$serial'>$serial</td>
						<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>".sprint3($qtys[$keys])."</td>
						<td nowrap>".CUR." $stkd[unitcost]</td>
						<td><input type='hidden' size='4' name='disc[]' value='$disc[$keys]'>$disc[$keys] OR <input type='hidden' size='4' name='discp[]' value='$discp[$keys]' maxlength='5'>$discp[$keys]%</td>
						<td nowrap><input type='hidden' name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
					</tr>";
				$c++;

			}
		}
	}
	$products .= "</table>";

	if($c < 1){
		$err = "<li class='err'>Please enter quantity.</li>";
		return details($HTTP_POST_VARS, $err);
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri = db_exec($Sl);

// 		if(pg_num_rows($Ri)>0) {
// 			$taxex += $delchrg;
// 		}

	$vd = pg_fetch_array($Ri);

	if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
		$showvat = FALSE;
	}

	if($vd['zero'] == "Yes") {
		$excluding = "y";
	} else {
		$excluding = "";
	}

	$vr = vatcalc($delchrg,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs = explode("|",$vr);
	$ivat = $vrs[0];
	$iamount = $vrs[1];

	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$sub = 0.00;
	if(isset($amt)) {
		$sub = sprint(array_sum($amt));
	}

	$VATP = TAX_VAT;

	if($inv['chrgvat'] == "exc"){
		$taxex = sprint($taxex - ($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
	//	$VAT=sprint(($subtotal-$taxex)*$VATP/100);
		$VAT = sprint($vatamount + $ivat);
		$SUBTOT = $sub;
		$delexvat = sprint($delchrg);
		$TOTAL = sprint($subtotal + $VAT);
	}elseif($inv['chrgvat'] == "inc"){
		$ot = $taxex;
		$taxex = sprint($taxex-($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
		$VAT = sprint($vatamount + $ivat);
		$SUBTOT = sprint($sub);
		$TOTAL = sprint($subtotal);
		$delexvat = sprint(($delchrg));
		$traddiscmt = sprint($traddiscmt);
	} else {
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(0);
		$SUBTOT = $sub;
		$TOTAL = $subtotal;
		$delexvat = sprint($delchrg);
	}

	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------

/* --- ----------- Clac ---------------------
	# calculate subtot
	$SUBTOT = 0.00;
	if(isset($amt))
		$SUBTOT = array_sum($amt);

	$SUBTOT -= $taxex;

	# duplicate
	$SUBTOTAL = $SUBTOT;

	$VATP = TAX_VAT;
	if($inv['chrgvat'] == "exc"){
		$SUBTOTAL = $SUBTOTAL;
		$delexvat= ($delchrg);
	}elseif($inv['chrgvat'] == "inc"){
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
	$taxext = ($taxex - $traddiscmtt);

	if($traddisc > 0) {
		$traddiscmt = ($EXVATTOT * ($traddisc/100));
	}else{
		$traddiscmt = 0;
	}
	$EXVATTOT -= $traddiscmt;
	// $EXVATTOT -= $taxex;

	$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

	if($inv['chrgvat'] != "nov"){
		$VAT = sprint($EXVATTOT * ($VATP/100));
	}else{
		$VAT = 0;
	}

	$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
	$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

	$traddiscmt = sprint($traddiscmt);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$Sl = "SELECT * FROM cubit.settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");
	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Sales Person</td>
				<td valign='center'>$inv[salespn]</td>
			</tr>";
	} else {
		$sp = "";
	}

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Credit Note</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='invid' value='$invid' />
			<input type='hidden' name='o_day' value='$o_day' />
			<input type='hidden' name='o_month' value='$o_month' />
			<input type='hidden' name='o_year' value='$o_year' />
			<input type='hidden' name='tcosamt' value='$tcosamt' />
			<input type='hidden' name='odate' value='$odate' />
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$inv[deptname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname] $inv[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Customer Address</td>
							<td valign='center'>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer VAT Number</td>
							<td>$inv[cusvatno]</td>
						</tr>
						<tr>
							<th colspan='2' valign='top'>Comments</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'><input type='hidden' name='comm' value='$comm'>".nl2br($comm)."</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Number</td>
							<td valign='center'>$inv[invnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order No.</td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$vchrgvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'><input type='hidden' size='7' name='terms' value='$terms'>$terms Days</td>
						</tr>
						$sp
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice Date</td>
							<td valign='center'>$odate</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td valign='center'><input type='hidden' size='7' name='traddisc' value='$traddisc'>$traddisc%</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td valign='center'><input type='hidden' size='7' name='delchrg' value='$delchrg'>$delchrg</td>
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
					<table ".TMPL_tblDflts.">
						<p>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='invoice-view.php'>View Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right' nowrap><input type='hidden' name='SUBTOT' value='$SUBTOT'>".CUR." $SUBTOT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $traddiscmt</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charge</td>
							<td align='right' nowrap>".CUR." $delexvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><b>VAT $vat14</b></td>
							<td align='right' nowrap>".CUR." $VAT</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



# details
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		return details($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($odate, "date", 1, 14, "Invalid Invoice note date.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "float", 1, 15, "Invalid Returned Quantity.");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids[]
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# check amt[]
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
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($HTTP_POST_VARS, $err);
	}

	
	
/* -------------------------------- */
	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$notenum = divlastid('note', USER_DIV);
/* --- Start Products Display --- */

	$vatamount = 0;

	# Products layout
	$products = "";
	$taxex = 0;
	$ai = 0;
	$amt = array();
	foreach($qtys as $keys => $value){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if ($stkd['account'] == 0) {
			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Calculate the Discount discount
			if($disc[$keys] < 1){
				if($discp[$keys] > 0){
					$disc[$keys] = (($discp[$keys]/100) * $stkd['unitcost']);
				}
			}else{
				$discp[$keys] = (($disc[$keys] * 100) / $stkd['unitcost']);
			}

			# Calculate amount
			$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

			db_conn('cubit');
			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			# Check Tax Excempt
			if($stk['exvat'] == 'yes'||$vd['zero'] == "Yes"){
				$taxex += $amt[$keys];
			}

			if($vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			$vatamount += $ivat;

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			db_conn("exten");

			# put in product
			$products .= "
				<tr>
					<td><input type='hidden' name='stkids[$ai]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' size='5' name='qtys[$ai]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' name='amt[$ai]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
				</tr>";
			++$ai;

		} else {


			# get warehouse name
			db_conn("core");

			$sql = "SELECT accname FROM accounts WHERE accid = '$stkd[account]'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			$discp[$keys] = 0;

			# Calculate amount
			$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));
			
			$nons_amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

			# Check Tax Excempt
			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes"){
				$taxex += $amt[$keys];
			}

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			$vatamount += $ivat;

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			db_conn("exten");

			$wh['whname'] = "";
			$stk['stkid'] = 0;
			$stk['stkcod'] = $wh['accname'];
			$stk['stkdes'] = $stkd['description'];

			# put in product
			$products .= "
				<tr>
					<td><input type='hidden' name='stkids[$ai]' value='$stk[stkid]'>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td><input type='hidden' size='5' name='qtys[$ai]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td nowrap><input type='hidden' name='amt[$ai]' value='$amt[$keys]'>".CUR." $amt[$keys]</td>
				</tr>";
			++$ai;

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

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri = db_exec($Sl);
	$vd = pg_fetch_array($Ri);
	
	if($vd['zero'] == "Yes") {
		$excluding = "y";
	} else {
		$excluding = "";
	}

	$vr = vatcalc($delchrg,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs = explode("|",$vr);
	$DELVAT = $vrs[0];

	if($vd['zero'] == "Yes") {
		$DELVAT = 0;
	}

// 		db_conn('cubit');
// 		$Sl="SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
// 		$Ri=db_exec($Sl);
//
// 		$vd=pg_fetch_array($Ri);
//
// 		$vr=vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
// 		$vrs=explode("|",$vr);
// 		$ivat=$vrs[0];
// 		$iamount=$vrs[1];
//
// 		$vatamount += $ivat;


// 		if(pg_num_rows($Ri)>0) {
// 			$taxex += $delchrg;
// 		}


/* --- ----------- Clac ---------------------

	# calculate subtot
	$SUBTOT = 0.00;
	if(isset($amt))
		$SUBTOT = array_sum($amt);

	$SUBTOT -= $taxex;

	# duplicate
	$SUBTOTAL = $SUBTOT;

	$VATP = TAX_VAT;
	if($inv['chrgvat'] == "exc"){
		$SUBTOTAL = $SUBTOTAL;
		$delexvat= ($delchrg);
	}elseif($inv['chrgvat'] == "inc"){
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
	$taxext = ($taxex - $traddiscmtt);

	if($traddisc > 0) {
		$traddiscmt = ($EXVATTOT * ($traddisc/100));
	}else{
		$traddiscmt = 0;
	}
	$EXVATTOT -= $traddiscmt;
	// $EXVATTOT -= $taxex;

	$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

	if($inv['chrgvat'] != "nov"){
		$VAT = sprint($EXVATTOT * ($VATP/100));
	}else{
		$VAT = 0;
	}

	$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
	$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

/* A quick fix by jupiter
	$allnoted = true;
	foreach($qtys as $keys => $value) {
		$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);
		if($stkd['qty'] != $qtys[$keys]){
			$allnoted = false;
		}
	}

	if($allnoted){
		$SUBTOT = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);
		$delchrg = sprint($inv['delivery']);
		$traddiscmt = sprint($inv['discount']);
		$SUBTOTAL = sprint($TOTAL - $VAT);
	}
/* End A quick fix by jupiter */

	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$sub = 0.00;
	if(isset($amt)) {
		$sub = sprint(array_sum($amt));
	}
	$nons_total = 0.00;
	if(isset($nons_amt)) {
		$nons_total = sprint(array_sum($nons_amt));
	}

	$VATP = TAX_VAT;

	if($inv['chrgvat'] == "exc"){
		$taxex = sprint($taxex - ($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
	//	$VAT=sprint(($subtotal-$taxex)*$VATP/100);
		$VAT = sprint($vatamount + $DELVAT);
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);
	}elseif($inv['chrgvat'] == "inc"){
		$ot = $taxex;
		$taxex = sprint($taxex - ($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		//$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
		$VAT = sprint($vatamount + $DELVAT);
		$TOTAL = sprint($subtotal);
		//$SUBTOT=sprint($TOTAL - $VAT - ($delchrg - $DELVAT));
		$SUBTOT = sprint($sub);
		$delexvat = sprint(($delchrg));
		$traddiscmt = sprint($traddiscmt);
	} else {
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(0);
		$SUBTOT = $sub;
		$TOTAL = $subtotal;
		$delexvat = sprint($delchrg);
	}

	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------

	if($inv['balance'] >= $TOTAL) {
		$invpay = $TOTAL;
		$examt = 0;
	} else {
		$invpay = $inv['balance'];
		$examt = ($TOTAL - $invpay);
	}

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","z");
	/* - End Hooks - */

	# Todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");
	$td = $odate;

	$refnum = getrefnum();
/*refnum*/

	# Insert invoice to period DB
	db_conn($inv['prd']);

	# Insert invoice credit note to DB
	$sql = "
		INSERT INTO inv_notes (
			deptid, notenum, invnum, invid, cusnum, cordno, ordno, 
			chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, 
			total, comm, username, div, surname, cusaddr, cusvatno, 
			deptname, branch, bankid, prd
		) VALUES (
			'$inv[deptid]', '$notenum', '$inv[invnum]', '$inv[invid]', '$inv[cusnum]', '$inv[cordno]', '$inv[ordno]', 
			'$inv[chrgvat]', '$terms', '$traddiscmt', '$inv[salespn]', '$odate', '$delexvat', '$SUBTOT', '$VAT' , 
			'$TOTAL', '$comm', '".USER_NAME."', '".USER_DIV."', '$inv[surname]', '$inv[cusaddr]', '$inv[cusvatno]', 
			'$inv[deptname]', '$inv[branch]', '$inv[bankid]', '$inv[prd]'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# Get next ordnum
	$noteid = pglib_lastid ("inv_notes", "noteid");

	db_connect();

	# Begin updating

	$nbal = ($inv['nbal'] + $TOTAL);

	# Update the invoice (make balance less)
	$sql = "UPDATE invoices SET nbal = '$nbal', rdelchrg = (rdelchrg + '$delchrg'), balance = balance - '$invpay' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Update the invoice (make balance less)
	$sql = "UPDATE open_stmnt SET balance = balance-'$TOTAL' WHERE invid = '$inv[invnum]'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Update the customer (make balance less)
	$sql = "UPDATE customers SET balance = (balance - '$TOTAL') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Update invoice's discounts
	# $sql = "UPDATE inv_discs SET traddisc = (traddisc - '$traddiscm'), itemdisc = (itemdisc - '$discs') WHERE cusnum = '$inv[cusnum]' AND invid = '$invid'";
	# $stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# record the payment on the statement
	$sql = "
		INSERT INTO stmnt (
			cusnum, invid, amount, date, 
			type, div, allocation_date
		) VALUES (
			'$inv[cusnum]', '$notenum', '".($TOTAL - ($TOTAL * 2))."', '$odate', 
			'Credit Note for invoice No. $inv[invnum]', '".USER_DIV."', '$odate'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	$disc = 0;
	$commision = 0;
	# Commit updating
	$nsp = 0;

	# Make ledge record
	custledger($inv['cusnum'], $dept['incacc'], $td, $notenum, "Credit Note No. $notenum for invoice No. $inv[invnum]", $TOTAL, "c");
	
	$salesp = qrySalesPersonN($inv["salespn"]);

	if($examt > 0) {
		# Make record for age analisys
		custCTP($examt, $inv['cusnum'],$td);
	}

	#recalculate the total cost amount for ONLY THE CREDITED ITEMS
	$ntcosamt = 0;

	foreach($qtys as $keys => $value){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if ($stkd['account'] == 0) {
			# Keep track of discounts
			$disc += ($stkd['disc'] * $stkd['qty']);

			db_connect();
			
			$Sl = "SELECT * FROM scr WHERE inv='$inv[invnum]' AND stkid='$stkd[stkid]' AND invid = '$stkd[id]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) > 0) {
				$cd = pg_fetch_array($Ri);

				$stk['csprice'] = $cd['amount'];
			} else {
				$stk['csprice'] = 0;
			}

			# cost amount
			if ($stk['csprice'] == "0.00") {
				$cosamt = sprint($qtys[$keys] * $stk['lcsprice']);
			} else {
				$cosamt = sprint($qtys[$keys] * $stk['csprice']);
			}

			#add this cost amount to the new total
			$ntcosamt += $cosamt;

			db_connect();

			# Update stock(onhand + qty)
			$sql = "UPDATE stock SET csamt = (csamt + '$cosamt'), units = (units + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# fix stock cost amount
			$Sl = "UPDATE stock set csprice=csamt/units WHERE stkid = '$stkids[$keys]' AND units>0";
			$Ri = db_exec($Sl) or errDie("Unable to update stock cost price in Cubit.",SELF);

			if ($stk['serd'] == 'yes') {
				ext_InSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $notenum, 'note',$td);
			}

			# negetive values to minus profit
			$nqty = ($qtys[$keys] * (1));
			$namt = ($amt[$keys] * (-1));
			$ncsprice = ($cosamt * (-1));

			$noted = ($stkd['noted'] + $qtys[$keys]);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			stockrec($stkd['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $td, $nqty, $cosamt, "Credit note for Customer : $inv[surname] - Credit note No. $notenum");

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $amt[$keys];

			###################VAT CALCS#######################

			db_conn('cubit');
			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if (pg_num_rows($Ri) < 1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			$vatamount += $ivat;

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);
			
			if ($excluding == "y") {
				$exvatamt = $iamount;
			} else {
				$exvatamt = $iamount - $ivat;
			}

			####################################################
			$sql = "
				INSERT INTO stockrec (
					edate, stkid, stkcod, stkdes, trantype, qty, csprice, 
					csamt, details, div
				) VALUES (
					'$td', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'note', '$qtys[$keys]', '$amtexvat', 
					'$cosamt', 'Credit note for Customer : $inv[surname] - Credit note No. $notenum', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);

			# Get selected stock in this invoice
			$sql = "UPDATE inv_items SET noted = '$noted' WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);
			$stkds = pg_fetch_array($stkdsRslt);

			# get accounts
			db_conn("exten");

			$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);
			$stockacc = $wh['stkacc'];
			$cosacc = $wh['cosacc'];
		
			# sales rep commission
			if ($salesp["com"] > 0) {
				$itemcommission = $salesp['com'];
			} else {
				$itemcommission = $stk["com"];
			}
			
			$commision = $commision + coms($inv['salespn'], $exvatamt, $itemcommission);

			writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum for Customer: $inv[cusname] $inv[surname]");

			db_conn($inv['prd']);

			# insert invoice items
			$sql = "
				INSERT INTO inv_note_items (
					noteid, whid, stkid, qty, amt, div, vatcode
				) VALUES (
					'$noteid', '$stkd[whid]', '$stkids[$keys]', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."', '$stkd[vatcode]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();

			$sql = "
				INSERT INTO salesrec (
					edate, invid, invnum, debtacc, vat, total, typ, div
				) VALUES (
					'$odate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nstk', '".USER_DIV."'
				)";
			$recRslt = db_exec($sql);
		} else {
			# Keep track of discounts
			//$disc += ($stkd['disc'] * $stkd['qty']);

			# negetive values to minus profit
			$nqty = ($qtys[$keys] * (1));
			$namt = ($amt[$keys] * (-1));
			//$ncsprice = ($cosamt * (-1));

			$noted = ($stkd['noted'] + $qtys[$keys]);

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = $amt[$keys];

			###################VAT CALCS#######################

			$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd = pg_fetch_array($Ri);

			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes") {
				$excluding = "y";
			} else {
				$excluding = "";
			}

			$vr = vatcalc($amt[$keys],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
			$vrs = explode("|",$vr);
			$ivat = $vrs[0];
			$iamount = $vrs[1];

			$vatamount += $ivat;

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note: $notenum Customer : $inv[cusname] $inv[surname]",-$iamount,-$ivat);
		
			if ($excluding == "y") {
				$exvatamt = $iamount;
			} else {
				$exvatamt = $iamount - $ivat;
			}

			####################################################

			if($inv['chrgvat'] == "exc"){
				$nvat = (($stkd['amt']/100) * $vd['vat_amount']);
				$ncosamt = round(($stkd['qty'] * $stkd['amt'])+$nvat, 2);
			}else {
				$nvat = (($stkd['amt'] / (100 + $vd['vat_amount'])) * $vd['vat_amount']);
				$ncosamt = round(($stkd['qty'] * $stkd['amt']), 2);
			}
			$ntcosamt += $ncosamt;


			####################################################

			# Get selected stock in this invoice
			$sql = "UPDATE inv_items SET noted = '$noted' WHERE id = '$ids[$keys]' AND invid ='$invid' AND div = '".USER_DIV."'";
			$stkdsRslt = db_exec($sql);
			$stkds = pg_fetch_array($stkdsRslt);

			$nsp += sprint($iamount-$ivat);

			//writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
			writetrans($stkd['account'],$dept['debtacc'],$td, $refnum, ($iamount-$ivat), "Debtors control for Credit note: $notenum Customer : $inv[cusname] $inv[surname]");

			//# dt(stock) ct(cos)
		//	writetrans($stockacc, $cosacc, $td, $refnum, $cosamt, "Cost Of Sales for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");
		
			if ($salesp["com"] > 0) {
				$itemcommission = $salesp['com'];
			} else {
				$itemcommission = $stk["com"];
			}
			
			$commision = $commision + coms($inv['salespn'], $exvatamt, $itemcommission);

			db_conn($inv['prd']);
			# insert invoice items
			$sql = "
				INSERT INTO inv_note_items (
					noteid, whid, stkid, qty, amt, div, description, vatcode
				) VALUES (
					'$noteid', '$stkd[account]', '0', '$qtys[$keys]', '$amt[$keys]', '".USER_DIV."', '$stkd[description]', '$stkd[vatcode]'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			db_connect();
		
			$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
			VALUES('$odate', '$noteid', '$notenum', '$dept[debtacc]', '$ivat', '$iamount', 'nnon', '".USER_DIV."')";
			$recRslt = db_exec($sql);
		}
	}

	db_connect();

	# save invoice discount
	$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div) VALUES('$inv[cusnum]', '$invid', '0', '-$disc', '$inv[odate]', '0', '".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	/* - Start Transactoins - */

//	###################VAT CALCS#######################
//	db_conn('cubit');
	$Sl = "SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes";
		$Ri = db_exec($Sl);
	}
//
	$vd = pg_fetch_array($Ri);
//
	$excluding = "";
//
	$vr = vatcalc($delexvat,$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs = explode("|",$vr);
	$ivat = $vrs[0];
	$iamount = $vrs[1];
//
//	if($vd['zero']=="Yes") {
//		$ivat=0;
//	}
//
//	$vatamount += $ivat;
//
	vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"VAT for Credit note No. $notenum, Customer : $inv[cusname] $inv[surname]",sprint(-$iamount-$ivat),-$ivat);
//
//	####################################################



	com_invoice($inv['salespn'],-($TOTAL-$VAT),-$commision,$inv['invnum'],$td,true);

	if(($TOTAL-$VAT-$nsp)>0) {

		# dt(income) ct(debtors)
		writetrans($dept['incacc'], $dept['debtacc'], $td, $refnum, ($TOTAL-$VAT-$nsp), "Debtors Control for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	}

	# dt(vat) ct(debtors)
	writetrans($vatacc, $dept['debtacc'], $td, $refnum, $VAT, "VAT Return for Credit note No. $notenum for Customer : $inv[cusname] $inv[surname]");

	db_connect();
//	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
//	VALUES('$odate', '$noteid', '$notenum', '$dept[debtacc]', '$VAT', '$TOTAL', 'nstk', '".USER_DIV."')";
//	$recRslt = db_exec($sql);

	$Sl = "INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusnum]','$inv[surname]','Credit Note:$notenum, Invoice $inv[invnum]','$odate','".-sprint($TOTAL-$VAT)."','-$VAT','".-sprint($TOTAL)."','".USER_DIV."')";
	$Ri = db_exec($Sl);

	pglib_transaction("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Transactoins - */

	$OUTPUT = "
		<script>
			sCostCenter('ct', 'Credit Note', '$odate', 'Credit Note for Invoice No.$inv[invnum] for Customer $inv[cusname] $inv[surname]', '".($TOTAL-$VAT-$nons_total)."', 'Cost Of Sales for Credit Note for Invoice No.$inv[invnum]', '$ntcosamt', '');
			printer('invoice-note-reprint.php?noteid=$noteid&prd=$inv[prd]&cccc=yes&reprint=no');
			move('main.php');
		</script>";
	require ("template.php");

}


?>

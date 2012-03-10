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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;
			case "write":
				$OUTPUT = write($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# Get templete
require("../template.php");




# details
function details($HTTP_GET_VARS, $errata = "")
{

	$showvat = TRUE;

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");

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



	db_connect();

	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='5%'>#</th>
				<th width='40%'>DESCRIPTION</th>
				<th width='10%'>QTY</th>
				<th width='10%'>UNIT PRICE</th>
				<th width='10%'>AMOUNT</th>
				<th width='20%'>ACCOUNT</th>
			<tr>";

	# get selected stock in this Invoice
	db_connect();

	$sql = "SELECT *,(qty - rqty) as qty FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		$accRs = get("core", "accname,topacc,accnum", "accounts", "accid", $stkd['accid']);
		$acc = pg_fetch_array($accRs);

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i<input type='hidden' name=ids[] value='$stkd[id]'></td>
				<td>$stkd[description]</td>
				<td>
					<input type='hidden' name=oqtys[] value='$stkd[qty]'>
					<input type='hidden' name='qtys[]' value='$stkd[qty]'>
					$stkd[qty]
				</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap>".CUR." $stkd[amt]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}

	db_conn ('hire');

	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

	/* --- End Some calculations --- */

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	# format date
	list($ninv_year, $ninv_month, $ninv_day) = explode("-", $inv['odate']);

	if(!isset($ninv_year) OR strlen($ninv_year) < 1){
		$ninv_year = date("Y");
	}

	if(!isset($ninv_month) OR strlen($ninv_month) < 1){
		$ninv_month = date("m");
	}

	if(!isset($ninv_day) OR strlen($ninv_day) < 1){
		$ninv_day = date("d");
	}

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Non-Stock Credit Note Details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='invid' value=$invid>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Address</td>
							<td valign='center'><pre>$inv[cusaddr]</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer VAT Number</td>
							<td valign='center'>$inv[cusvatno]</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>$inv[invnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>".mkDateSelect("ninv",$ninv_year,$ninv_month,$ninv_day)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td>$errata</td>
			</tr>
			<tr>
				<td colspan='2'>$products</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width=40%>Quick Links</th>
							<th width=45%>Remarks</th>
							<td rowspan='5' valign='top' width=15%><br></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan=4 align=center valign=top><textarea name='remarks' cols='20' rows='5'>".nl2br($inv['remarks'])."</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>".CUR." $inv[discount]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Subtotal</td>
							<td align='right' nowrap>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right' nowrap>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}


# confirm
function confirm($HTTP_POST_VARS)
{

	$showvat = TRUE;

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid remarks.");
	$sdate = $ninv_year."-".$ninv_month."-".$ninv_day;
	if( !checkdate($ninv_month, $ninv_day, $ninv_year) ){
		$v->addError($sdate, "Invalid Date.");
	}

	foreach($ids as $key => $id){
		$v->isOk ($id, "num", 1, 20, "Invalid Item number.");
		$v->isOk ($qtys[$key], "float", 1, 20, "Invalid Item quantity.");
		if($qtys[$key] > $oqtys[$key]){
			$v->isOk ("##", "num", 1, 1, "Error: Item quantity cannot be more than invoiced quantity.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$confirm = "$err<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return details($HTTP_POST_VARS, $err);
		return $confirm;
	}



	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='5%'>#</th>
				<th width='40%'>DESCRIPTION</th>
				<th width='10%'>QTY</th>
				<th width='10%'>UNIT PRICE</th>
				<th width='10%'>AMOUNT</th>
				<th width='20%'>ACCOUNT</th>
			<tr>";

	// Retrieve invoice items
	db_connect();

	$sql = "SELECT *,(qty - rqty) as qty FROM nons_inv_items WHERE invid='$invid' AND div='".USER_DIV."'";
	$item_rslt = db_exec($sql);

	$i = 0;
	while ($item_data = pg_fetch_array($item_rslt)) {
		++$i;

		$accRs = get("core", "accname, topacc, accnum", "accounts", "accid", $item_data['accid']);
		$acc = pg_fetch_array($accRs);

// 					<tr bgcolor='".bgcolorg()."'>
// 						<td align=center>$i<input type='hidden' name=ids[] value='$stkd[id]'></td>
// 						<td>$stkd[description]</td>
// 						<td><input type='hidden' name='qtys[]' value='$qtys[$key]'>$qtys[$key]</td>
// 						<td nowrap>".CUR." $stkd[unitcost]</td>
// 						<td nowrap><input type='hidden' name='amts[]' value='$amt[$key]'>".CUR." $amt[$key]</td>
// 						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
// 					</tr>";

		$products .= "
			<input type='hidden' name='ids[]' value='$item_data[id]' />
			<input type='hidden' name='qtys[]' value='$item_data[qty]' />
			<input type='hidden' name='amts[]' value='$item_data[amt]' />
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td>$item_data[description]</td>
				<td>$item_data[qty]</td>
				<td nowrap>".CUR." $item_data[unitcost]</td>
				<td nowrap>".CUR." $item_data[amt]</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>";
	}
	$products .= "</table>";

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve non stock invoice.");
	$inv = pg_fetch_array($inv_rslt);

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Non-Stock Credit Note</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='invid' value=$invid>
			<input type='hidden' name='remarks' value='$remarks'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Address</td>
							<td valign='center'><pre>$inv[cusaddr]</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer VAT Number</td>
							<td valign='center'>$inv[cusvatno]</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>$inv[invnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>
								<input type='hidden' size='2' name='ninv_day' maxlength='2' value='$ninv_day'>$ninv_day-
								<input type='hidden' size='2' name='ninv_month' maxlength='2' value='$ninv_month'>$ninv_month-
								<input type='hidden' size='4' name='ninv_year' maxlength='4' value='$ninv_year'>$ninv_year
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
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
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($remarks)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>Trade Discount</td>
							<td align='right' nowrap>
								<input type='hidden' name='discount' value='$inv[discount]' />
								".CUR." $inv[discount]
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Subtotal</td>
							<td align='right' nowrap><input type='hidden' name='subtot' value='$inv[subtot]'>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT $vat14</td>
							<td align='right' nowrap><input type='hidden' name='vat' value='$inv[vat]'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap><input type='hidden' name='total' value='$inv[total]'>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



function write()
{

	extract ($_REQUEST);

	require_lib("validate");

	$v = new validate;
	$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");
	$sndate = "$ninv_year-$ninv_month-$ninv_day";
	if (!checkdate($ninv_month, $ninv_day, $ninv_year)) {
		$v->addError($sdate, "Invalid Date.");
	}

	pglib_transaction("BEGIN");

	// Get invoice info
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid='$invid' AND div='".USER_DIV."'";
	$inv_rslt = db_exec ($sql) or errDie("Unable to get invoice information");
	if (pg_numrows($inv_rslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($inv_rslt);
	$TOTAL = $inv["subtot"] + $inv["vat"];

	$notenum = pglib_lastid("cubit.nons_inv_notes", "noteid");
	$notenum++;

	// Add to the non stock credit notes
	$sql = "
		INSERT INTO cubit.nons_inv_notes (
			invid, invnum, cusname, cusaddr, cusvatno, chrgvat, 
			date, subtot, vat, total, username, prd, notenum, ctyp, 
			remarks, div
		) VALUES (
			'$inv[invid]', '$inv[invnum]', '$inv[cusname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[chrgvat]', 
			'$sndate', '$inv[subtot]', '$inv[vat]', '$TOTAL', '".USER_NAME."', '".PRD_DB."', '$notenum', '$inv[ctyp]', 
			'$inv[remarks]', '".USER_DIV."'
		)";
	db_exec($sql) or errDie("Unable to save credit note.");
	$noteid = pglib_lastid("cubit.nons_inv_notes", "noteid");

	$sql = "SELECT count(id) FROM cubit.nons_inv_items WHERE invid='$invid'";
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve amount of items.");
	$item_count = pg_fetch_result($count_rslt, 0);

	$i = 0;
	$page = 0;
	foreach ($ids as $key=>$id) {
		$sql = "SELECT * FROM cubit.nons_inv_items WHERE invid='$invid' AND id='$id'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve item.");
		$item_data = pg_fetch_array($item_rslt);
		
		if($item_data['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		// Time for a new page ??
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$products[$page][] = "
			<tr valign='top'>
				<td style='border-right: 2px solid #000'>
					$ex $item_data[description]&nbsp;
				</td>
				<td style='border-right: 2px solid #000'>
					$item_data[qty]&nbsp;
				</td>
				<td style='border-right: 2px solid #000' align='right' nowrap>
					".CUR." $item_data[unitcost]&nbsp;
				</td>
				<td align='right' nowrap>".CUR." $item_data[amt]&nbsp;</td>
			</tr>";

		$i++;

		// Create credit note item
		$sql = "
			INSERT INTO cubit.nons_note_items (
				noteid, qty, description, amt, unitcost, 
				vatcode
			) VALUES (
				'$noteid', '$qtys[$key]', '$item_data[description]', '$amts[$key]', '$item_data[unitcost]', 
				'$item_data[vatex]'
			)";
		db_exec($sql) or errDie("Unable to create credit note item.");

		$sql = "SELECT grpid FROM cubit.assets WHERE id='$item_data[asset_id]'";
		$group_rslt = db_exec($sql) or errDie("Unable to retrieve group.");
		$group_id = pg_fetch_result($group_rslt, 0);

		$discount = ($inv["discount"] / $item_count);
		$amt = $item_data["amt"];

		// Update royalty report and detail report
		$sql = "
			INSERT INTO hire.revenue (
				group_id, asset_id, total, discount, credit
			) VALUES (
				'$group_id', '$item_data[asset_id]', '-$amt', '-$discount', '1'
			)";
		db_exec($sql) or errDie("Unable to update revenue.");
		$i++;
	}

 	$blank_lines = 25;
 	foreach ($products as $key=>$val) {
 		$bl = $blank_lines - count($products[$key]);
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>";
 		}
 	}

	// Retrieve customer debt account
	$sql = "
		SELECT debtacc FROM exten.departments 
			LEFT JOIN cubit.customers ON departments.deptid=customers.deptid
		WHERE cusnum='$inv[cusid]'";
	$dept_rslt = db_exec($sql) or errDie("Unable to retrieve departments.");
	$debtacc = pg_fetch_result($dept_rslt, 0);

	$hireacc = $inv["accid"];
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","vat");

	$refnum = getrefnum();

	writetrans($hireacc, $debtacc, $sndate, $refnum, $inv["subtot"],
		"Non-Stock Invoice No. $inv[invnum] Credit Note No. $noteid Customer
		$inv[cusname]");

	if ($inv["vat"] != 0) {
		writetrans($vatacc, $debtacc, $sndate, $refnum, $inv["vat"],
		"Non-Stock Invoice No. $inv[invnum] Credit Note No. $noteid VAT.
		Customer $inv[cusname]");
	}

	// Record on the statement
	$sql = "
		INSERT INTO cubit.stmnt (
			cusnum, invid, amount, date, type, 
			div
		) VALUES (
			'$inv[cusid]', '$noteid', '-$TOTAL', '$sndate', 'Non-Stock Credit Note, for invoice $inv[invnum]', 
			'".USER_DIV."'
		)";
	db_exec($sql) or errDie("Unable to insert to customer statement.");

	// Update the customer (Make the balance less)
	$sql = "UPDATE cubit.customers SET balance=(balance-'$TOTAL') WHERE cusnum='$inv[cusid]'";
	db_exec($sql) or errDie("Unable to update customer balance.");

	// Update the customer (Make the balance less)
	$sql = "UPDATE cubit.open_stmnt SET balance=(balance-'$TOTAL') WHERE cusnum='$inv[cusid]'";
	db_exec($sql) or errDie("Unable to update customer balance.");

	// Create ledger record
	custledger($inv["cusid"], $hireacc, $sndate, $noteid, "Non-Stock Credit Note $noteid", $TOTAL, "c");
	custCT($inv["total"], $inv["cusid"], $inv["odate"]);

	// Update non-stock invoice
	$sql = "UPDATE cubit.nons_invoices SET balance=(balance-'$TOTAL') WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update non-stock invoice.");

	$sql = "
		INSERT INTO cubit.salesrec (
			edate, invid, invnum, debtacc, vat, total, typ, div
		) VALUES (
			'$sndate', '$noteid', '$notenum', '0', '$inv[vat]', '$TOTAL', 'nnon', '".USER_DIV."'
		)";
	db_exec($sql) or errDie("Unable to record in sales.");

	$sql = "
		INSERT INTO cubit.sj (
			cid, name, des, date, 
			exl, vat, inc, div
		) VALUES (
			'$inv[cusid]', '$inv[cusname]', 'Credit Note: $noteid Invoice $inv[invnum]', '$sndate', 
			'-".($TOTAL - $inv["vat"])."', '$inv[vat]', '".-sprint($TOTAL)."', '".USER_DIV."'
		)";
	db_exec($sql) or errDie("Unable to record in sj.");

	$sql = "UPDATE cubit.nons_invoices SET accepted='note' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update invoice.");

	com_invoice($inv["salespn"], -($TOTAL - $inv["vat"]), 0, $inv["invnum"], $sndate);

	$cc = "
		<script>
			CostCenter('ct', 'Credit Note', '$sndate',
			'Non Stock Credit Note No.$noteid', '".($TOTAL-$inv["vat"])."', '');
	   </script>";

	// Reverse the amounts on the coastal reports -----------------------------
	$sql = "UPDATE hire.assets_hired SET value=0 WHERE invid='$inv[hire_invid]'";
	db_exec($sql) or errDie("Unable to update asset hired records.");

	// Vat
	$sql = "SELECT id FROM cubit.vatcodes WHERE code='01'";
	$vd_rslt = db_exec($sql) or errDie("Unable to retrieve vatcodes.");
	$vd_id = pg_fetch_result($vd_rslt, 0);

	vatr($vd_id, $sndate, "OUTPUT", "01", $refnum,
		"Non-Stock Sales, invoice No.$inv[invnum]", $TOTAL, $inv["vat"]);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Retrieve the company information
	db_conn("cubit");

	$sql = "SELECT * FROM compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Retrieve the banking information
	$sql = "SELECT * FROM bankacct WHERE bankid='2' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank.");
	$bank_data = pg_fetch_array($bank_rslt);

	// Retrieve customer information
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusid]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_data = pg_fetch_array($cust_rslt);

	if($inv['cusid'] == "0"){
		$cust_data['surname'] = $inv['cusname'];
		$cust_data['addr1'] = $inv['cusaddr'];
		$cust_data['paddr1'] = $inv['cusaddr'];
	}

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;";

	$details = "";

	for ($i = 0; $i <= $page; $i++) {
		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$details .= "
			<center>
			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table border='0' cellpadding='2' cellspacing='2' width='100%'>
							<tr>
								<td align='left' rowspan='2'><img src='../compinfo/getimg.php' width='230' height='47'></td>
								<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
								<td align='right'><font size='5'><b>Tax Credit Note</b></font></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td valign='top'>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='border-right: 2px solid #000'>$comp_data[addr1]&nbsp;</td>
								<td style='border-right: 2px solid #000'>$comp_data[paddr1]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>$comp_data[addr2]&nbsp;</td>
								<td style='border-right: 2px solid #000'>$comp_data[paddr2]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>$comp_data[addr3]&nbsp;</td>
								<td style='border-right: 2px solid #000'>$comp_data[paddr3]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>$comp_data[addr4]&nbsp;</td>
								<td style='border-right: 2px solid #000'>$comp_data[postcode]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>REG:</b> $comp_data[regnum]</b>&nbsp;</td>
								<td style='border-right: 2px solid #000'><b>$bank_data[bankname]</b>&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>VAT REG:</b> $comp_data[vatnum]&nbsp;</td>
								<td style='border-right: 2px solid #000'><b>Branch</b> $bank_data[branchname]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Tel:</b> $comp_data[tel]&nbsp;</td>
								<td style='border-right: 2px solid #000'><b>Branch Code:</b> $bank_data[branchcode]&nbsp;</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Fax:</b> $comp_data[fax]&nbsp;</td>
								<td style='border-right: 2px solid #000'><b>Acc Num:</b> $bank_data[accnum]&nbsp;</td>
							</tr>
						</table>
					</td>
					<td valign='top'>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='border-right: 2px solid #000'><b>Date</b></td>
								<td><b>Page Number</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>$inv[odate]</td>
								<td>".($i + 1)."</td>
							</tr>
							<tr>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
								<td style='border-bottom: 2px solid #000'>&nbsp</td>
							</tr>
							<tr><td>&nbsp</td></tr>
							<tr>
								<td colspan='2'><b>Credit Note No:</b> $noteid</td>
							</tr>
							<tr>
								<td colspan='2'><b>Invoice No:</b> $inv[invnum]</td>
							</tr>
							<tr>
								<td colspan='2'><b>Proforma Inv No:</b> $inv[docref]</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td align='center'><font size='4'><b>Credit Note To:</b></font></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td width='33%' style='border-right: 2px solid #000'><b>$cust_data[surname]</b></td>
								<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
								<td width='33%'><b>Delivery Address</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
								<td>&nbsp</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $inv[cusvatno]</td>
								<td width='33%'><b>Customer Order No:</b> $inv[cordno]</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Description</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Qty</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000' align='right'><b>Unit Price</b></td>
								<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
							</tr>
							$products_out
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td><i>VAT Exempt Indicator: #</i></td>
							</tr>
							<tr>
								<td>$remarks</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='border-right: 2px solid #000'><b>Terms:</b> $inv[terms] days</b></td>
								<td><b>Trade Discount:</b></td>
								<td nowrap><b>".CUR." $inv[discount]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>Subtotal:</b></td>
								<td nowrap><b>".CUR." $inv[subtot]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
								<td><b>VAT $vat14:</b></td>
								<td nowrap><b>".CUR." $inv[vat]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>Total Incl VAT:</b></td>
								<td nowrap><b>".CUR." ".sprint($TOTAL)."</b></td>
							<tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>";
	}

	pglib_transaction("COMMIT");

	$OUTPUT = $details;
	require ("../tmpl-print.php");

}



function custfCT($amount, $cusnum,$age)
{

	$odate = date("Y-m-d");

	db_connect();

	$amount = ($amount * (-1));

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' AND age='$age' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) < 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			$sql = "INSERT INTO custran(cusnum, odate, balance,div,age) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."','$age')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		$sql = "INSERT INTO custran(cusnum, odate, balance, div,age) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."','$age')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}


# vats
function vats($amt, $inc, $VATP)
{

	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "yes"){
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}else{
		$ret = ($amt);
	}
	return $ret;

}


?>
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
require("picking_slips/picking_slip.lib.php");

// Merge get vars with post vars
foreach ($HTTP_GET_VARS as $key=>$val) {
	$HTTP_POST_VARS[$key] = $val;
}

// We need the invid
if (!isset($HTTP_POST_VARS["invid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

// Decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "details":
			$OUTPUT = details($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = details($HTTP_POST_VARS);
}
require("template.php");




function details($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	$showvat = TRUE;

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

	# Check if invoice has been printed
	if($inv['printed'] != "y"){
		$error = "<li class='err'>Invoice number <b>$invid</b> has not been printed yet.</li>";
		return $error;
	}


	# Check if stock was selected(yes = put  button)
	db_connect();
	$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'>Invoice number <b>$invid</b> has no items.</li>";
		return $error;
	}

	# Products layout
	$products = "";
	$disc = 0;
	$taxex = 0;

	# Get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$i = 0;
	$page = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		if($stkd['account'] == 0) {

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
		} else {
			$wh['whname'] = "";
			$stk['exvat'] = "";
			$stk['stkcod'] = "";
			$stk['stkdes'] = $stkd['description'];
		}

		$stkd['vatcode'] += 0;
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if(pg_num_rows($Ri) > 0) {
			if($vd['zero'] == "Yes")
				$stk['exvat'] = "yes";
		} else {
			$stk['exvat'] = "";
		}

		# Check Tax Excempt
		if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes"){
			$taxex += ($stkd['amt']);
			$ex = "#";
		}else{
			$ex = "";
		}

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# Keep track of discounts
		$disc += $stkd['disc'];

		# Put in product
		if (!isset($products[$page])) {
			$products[$page] = "";
		}

		if (strlen($stkd['serno']) > 0){
			$showser = "<br>".$stkd['serno'];
		}else {
			$showser = "";
		}

		$products[$page][] = "
			<tr valign='top'>
				<td style='border-right: 2px solid #000'>$stk[stkcod]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$ex $stk[stkdes]&nbsp;$showser</td>
				<td style='border-right: 2px solid #000'>$stkd[qty]&nbsp;</td>
				<td style='border-right: 2px solid #000' align='right' nowrap>".CUR." $stkd[unitcost]&nbsp;</td>
				<td style='border-right: 2px solid #000' align='right'>$stkd[disc]&nbsp;</td>
				<td align='right' nowrap>".CUR." $stkd[amt]&nbsp;</td>
			</tr>";

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
	 				<td style='border-right: 2px solid #000'>&nbsp;</td>
	 				<td style='border-right: 2px solid #000'>&nbsp;</td>
	 				<td>&nbsp;</td>
	 			</tr>";
 		}
 	}


	# Subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if(strlen($inv['traddisc']) > 0){
		$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "
			<table border=1 cellspacing='0' bordercolor='#000000'>
				<tr>
					<td>".nl2br($inv['comm'])."</td>
				</tr>
			</table>";
	}

	# Update number of prints
	$inv['prints']++;
	db_connect();
	$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($inv['chrgvat'] == "inc") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "exc") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}

	if($data['value'] == "Yes") {
		$sp = "
			<tr>
				<td><b>Sales Person:</b> $inv[salespn]</td>
			</tr>";
	} else {
		$sp = "";
	}

	if (!isset($comment)) {
		// Retrieve the comment from Cubit.
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$cmntRslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
		$comment = pg_fetch_result($cmntRslt, 0);
	}

	if($inv['branch'] == 0){
		$branchname = "Head Office";
	}else {
		$get_bname = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_bname = db_exec($get_bname);
		if(pg_numrows($run_bname) < 1){
			$branchname = "";
		}else {
			$arr = pg_fetch_array($run_bname);
			$branchname = $arr['branch_name'];
		}
	}

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
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='$inv[bankid]' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cust_data = pg_fetch_array($cust_rslt);

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

        if ($inv["pslip_sordid"] > 0) {
			$barcode = "<img src='manufact/".pick_slip_barcode($inv["pslip_sordid"], 1)."' />";
        } else {
            $barcode = "";
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
								<td align='left' rowspan='2'><img src='compinfo/getimg.php' width='230' height='47'>$barcode</td>
								<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
								<td align='right'><font size='5'><b>Tax Invoice</b></font></td>
							</tr>
							<tr>
								<!-- Rowspan -->
								<!-- Rowspan -->
								<td align='right'><font size='4'><b>Reprint</b></font></td>
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
								<td colspan='2'><b>Invoice No:</b> $inv[invnum]</td>
							</tr>
							<tr>
								<td colspan='2'><b>Proforma Inv No:</b> $inv[docref]</td>
							</tr>
							<tr>
								<td colspan='2'><b>Sales Order No:</b> $inv[ordno]</td>
							</tr>
							<tr>
								<td colspan='2'><b>Account No:</b> $cust_data[accno]</td>
							</tr>
							$sp
						</table>
					</td>
				</tr>
			</table>
		
			<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td align='center'><font size='4'><b>Tax Invoice To:</b></font></td>
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
								<td width='33%' style='border-right: 2px solid #000'><b>$inv[surname]</b></td>
								<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
								<td width='33%'><b>Delivery Address</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
								<td>$branchname<br>".nl2br($inv["del_addr"])."</td>
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
								<td width='33%' style='border-right: 2px solid #000'><b>Customer Order No:</b> $inv[cordno]</td>
								<td width='33%'><b>Delivery Date:</b> $inv[deldate]</td>
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
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Code</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Description</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Qty</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Price</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Discount</b></td>
								<td style='border-bottom: 2px solid #000' align='right'><b>Amount</b></td>
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
								<td>$inv[comm]</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		
			<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000'>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='border-right: 2px solid #000'><b>Terms:</b> $inv[terms] days</b></td>
								<td><b>Subtotal:</b></td>
								<td nowrap><b>".CUR." $inv[subtot]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>Trade Discount:</b></td>
								<td nowrap><b>".CUR." $inv[discount]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
								<td><b>Delivery Charge</b></td>
								<td nowrap><b>".CUR." $inv[delivery]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>VAT $vat14:</b></td>
								<td nowrap><b>".CUR." $inv[vat]</b></td>
							<tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
								<td><b>Total Incl VAT:</b></td>
								<td nowrap><b>".CUR." $inv[total]</b></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>";
	}

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);
	
	if ($template == "invoice-print.php") {
		$OUTPUT = $details;
		require("tmpl-print.php");
	} else {
		header ("Location: $template?invid=$inv[invid]&type=invreprint");
	}

}



?>
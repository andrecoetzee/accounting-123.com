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
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module</li>";
}

require ("../template.php");




function details($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

// 	# display errors, if any
// 	if ($v->isError ()) {
// 		$err = "";
// 		$errors = $v->getErrors();
// 		foreach ($errors as $e) {
// 			$err .= "<li class=err>".$e["msg"];
// 		}
// 		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
// 		return $confirm;
// 	}


	# Get invoice info
	$sql = "SELECT * FROM cubit.nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	// are we working with an international invoice?
	if ($inv["currency"] != "") {
		$currency = $inv["currency"];
	} else {
		$currency = CUR;
	}

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = array();
	$disc = 0;

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$i = 0;
	$page = 0;
	$total = 0;
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$sql = "
			SELECT *, extract('epoch' FROM hired_time) AS e_from, extract('epoch' FROM return_time) AS e_to 
			FROM hire.assets_hired 
			WHERE item_id='$stk[item_id]'";
		$hires_rslt = db_exec($sql) or errDie("Unable to retrieve hires.");
		$hires_data = pg_fetch_array($hires_rslt);

		// Check Tax Excempt
		db_conn("cubit");

		$sql = "SELECT zero FROM vatcodes WHERE id='$stk[vatex]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$products[$page][] = "
			<tr valign=top>
				<td style='border-right: 2px solid #000'>$ex $stk[description]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$stk[qty]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$stk[hired_days]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000'>$stk[rate]&nbsp;</td>
				<td align='right'>$currency ".sprint($stk["amt"])."&nbsp;</td>
			</tr>";

		$i++;
		$total += $stk["amt"];
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
	 				<td>&nbsp;</td>
	 			</tr>";
 		}
 	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/

	/* --- Updates ---- */
	db_connect();

	# get selected stock in this invoice
	$sql = "SELECT * FROM hire.hire_nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<tr><td><b>Sales Person:</b> $inv[salespn]</td></tr>";
	} else {
		$sp = "";
	}

	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
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
	$sql = "SELECT * FROM bankacct WHERE bankid='2' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusid]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cust_data = pg_fetch_array($cust_rslt);

	if($inv['ctyp'] != "s"){
		$cust_data['addr1'] = $inv['cusaddr'];
		$cust_data['surname'] = $inv['cusname'];
		$cust_data['paddr1'] = "";
		$cust_data['del_addr1'] = "";
	}


	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;";

// 	$nolr_borders = "
// 		border-top: 2px solid #000;
// 		border-left: none;
// 		border-right: none;
// 		border-bottom: none;
		// 	";




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

		$sql = "
			SELECT count(id) 
			FROM hire.hire_stock_items_reprint 
			WHERE invnum='$inv[invnum]'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve stock count.");
		$count = pg_fetch_result($count_rslt, 0);

		$stock_out = "";
		if ($i == $page && $count > 0) {
			$stock_out = "
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							<tr>
								<td style='
									border-bottom: 2px solid #000;
									border-top: 2px solid #000;
									border-right: 2px solid #000'>
									<b>Stock</b>
								</td>
								<td style='
									border-bottom: 2px solid #000;
									border-top: 2px solid #000;
									border-right: 2px solid #000'>
									<b>Qty</b>
								</td>
								<td style='
									border-bottom: 2px solid #000;
									border-top: 2px solid #000;
									border-right: 2px solid #000'>
									<b>Unit Price</b>
								</td>
								<td style='
									border-bottom: 2px solid #000;
									border-top: 2px solid #000;
									boreder-right: 2px solid #000;' 
									align='right'>
									<b>Amount</b>
								</td>
							</tr>";
			$sql = "
				SELECT whname, stkcod, stkdes, qty, unitcost, amount FROM hire.hire_stock_items_reprint
					LEFT JOIN exten.warehouses ON hire_stock_items_reprint.whid=warehouses.whid
					LEFT JOIN cubit.stock ON hire_stock_items_reprint.stkid=stock.stkid
				WHERE invnum='$inv[invnum]'";
			$stock_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
			while ($stock_data = pg_fetch_array($stock_rslt)) {
				$stock_out .= "
					<tr>
						<td style='border-right: 2px solid #000'>
							$stock_data[stkcod] - $stock_data[stkdes]
						</td>
						<td style='border-right: 2px solid #000'>
							$stock_data[qty]
						</td>
						<td style='border-right: 2px solid #000' align='right'>$stock_data[unitcost]</td>
						<td align='right'>$stock_data[amount]</td>
					</tr>";
				$SUBTOT += $stock_data["amount"];
			}
			$stock_out .= "
						</table>
					</td>
				</tr>";
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
								<td colspan='2'><b>Hire No:</b> H$inv[hire_invnum]</td>
							</tr>
							<tr>
								<td colspan='2'>&nbsp;</td>
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
								<td width='33%' style='border-right: 2px solid #000'><b>$cust_data[surname]</b>&nbsp;</td>
								<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
								<td width='33%'><b>Delivery Address</td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."&nbsp;</td>
								<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."&nbsp;</td>
								<td>".nl2br($cust_data["del_addr1"])."&nbsp;</td>
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
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>No of Days</b></td>
								<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Daily Rate</b></td>
								<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
							</tr>
							$products_out
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table cellpadding='2' cellspacing='0' border='0' width='100%'>
							$stock_out
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
								<td>$inv[remarks]</td>
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
								<td><b>Subtotal:</b></td>
								<td><b>".CUR.sprint($total)."</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>Delivery</b></td>
								<td><b>".CUR."$inv[delivery]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>Discount</b></td>
								<td><b>".CUR."$inv[discount]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
								<td><b>VAT $vat14:</b></td>
								<td><b>".CUR."$inv[vat]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
								<td><b>Total Incl VAT:</b></td>
								<td><b>".CUR."$inv[total]</b></td>
							</tr>
							<tr>
								<td style='border-right: 2px solid #000'>&nbsp;</td>
							<tr>
							<tr>
								<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>";
	}

	// Retrieve template settings
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	$OUTPUT = $details;
	require("../tmpl-print.php");

}


?>
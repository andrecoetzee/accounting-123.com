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
if (isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module</li>";
}

# get templete
require("template.php");




# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid Credit note number.");

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
	db_connect ();
	$sql = "SELECT * FROM credit_notes WHERE id = '$id'";
	$noteRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($noteRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$note = pg_fetch_array($noteRslt);


	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	$sql = "SELECT stkid FROM credit_notes_stock WHERE creditnote_id = '$note[id]'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
//		$error = "<li class='err'> Error : Credit note number <b>$note[notenum]</b> has no items.</li>";
//		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
//		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = array();

	# Get selected stock in this invoice note
	$sql = "SELECT * FROM credit_notes_stock WHERE creditnote_id = '$id'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
	$nsub = 0;

	$i = 0;
	$page = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		#get warehouse id
		$get_stock = "SELECT whid FROM stock WHERE stkid = '$stkd[stkid]' LIMIT 1";
		$run_stock = db_exec($get_stock) or errDie ("Unable to get stock information.");
		if(pg_numrows($run_stock) < 1){
			return "Stock Information Not Found.";
		}else {
			$whid = pg_fetch_result ($run_stock,0,0);
		}

		# get warehouse name
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# get selected stock in this warehouse
		if ( $stkd["stkid"] != "0" ) {
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$tcosamt += sprint(($stkd['stkunits'] * $stk['csprice']), 2);
		} else {
			$stk = array(
				'stkcod' => "",
				'stkdes' => $stkd['description'],
				'exvat' => ""
			);
		}

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$note[vatcode]'";
		$Ri = db_exec($Sl);

// 		if(pg_num_rows($Ri)>0) {
// 			$stk['exvat']="yes";
// 		} else {
// 			$stk['exvat']="";
// 		}

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$note[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$selamt = sprint($stkd['stkcosts']);
		$selamt2 = sprint($stkd['stkcosts']*$stkd['stkunits']);

		$nsub += $selamt2;

		# put in product
		$products[$page][] = "
		<tr valign='top'>
			<td style='border-right: 2px solid #000'>$stk[stkcod]&nbsp;</td>
			<td style='border-right: 2px solid #000'>$ex $stk[stkdes]&nbsp;</td>
			<td style='border-right: 2px solid #000'>$stkd[stkunits]&nbsp;</td>
			<td style='border-right: 2px solid #000' align='right' nowrap>".CUR." $selamt&nbsp;</td>
			<td align='right' nowrap>".CUR." $selamt2&nbsp;</td>
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
 				<td>&nbsp;</td>
 			</tr>";
 		}
 	}

 	$note['comm'] = "";
	# Avoid little box
	if(strlen($note['comm']) > 0){
		$note['comm'] = "
		<table border=1 cellspacing='0' bordercolor='#000000'>
			<tr><td>".nl2br($note['comm'])."</td></tr>
		</table>";
	}

	# VAT perc
	$VATP = TAX_VAT;

	# format date
	$cc = "";
	if(isset($cccc))
		$cc = "<script> nCostCenter('ct', 'Credit Note', '$note[odate]', 'Credit Note No.$note[notenum] for Customer $note[cusname] $note[surname]', '".($note['total']-$note['vat'])."', 'Credit Note No.$note[notenum]', '$tcosamt', ''); </script>";
	$nsub = sprint($nsub);

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

	$note['bankid'] = 0;

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='$note[bankid]' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$note[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cust_data = pg_fetch_array($cust_rslt);

	// Find out if we should display the sales person
	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "
		<tr>
			<td><b>Sales Person:</b> $note[salespn]</td>
		</tr>";
	} else {
		$sp = "";
	}

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;
	";

	$nolr_borders = "
		border-top: 2px solid #000;
		border-left: none;
		border-right: none;
		border-bottom: none;
	";

	$details = "";

	for ($i = 0; $i <= $page; $i++) {
		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		if(is_array($products)){
			foreach ($products[$i] as $string) {
				$products_out .= $string;
			}
		}
/*
	<tr>
		<td colspan='3' align='right'><font size='3'><b>Reprint</b></font></td>
	</tr>
*/
		$details .= "
		<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr>
				<td>
					<table border='0' cellpadding='2' cellspacing='2' width='100%'>
						<tr>
							<td align='left' rowspan='2'><img src='compinfo/getimg.php' width=230 height=47></td>
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
							<td style='border-right: 2px solid #000'>$note[tdate]</td>
							<td>".($i + 1)."</td>
						</tr>
						<tr>
							<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
							<td style='border-bottom: 2px solid #000'>&nbsp</td>
						</tr>
						<tr>
							<td colspan='2'><b>General Credit Note No:</b> g$note[creditnote_num]</td>
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
							<td width='50%' style='border-right: 2px solid #000'><b>$cust_data[surname]</b></td>
							<td width='50%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
						</tr>
						<tr>
							<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
							<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
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
							<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $cust_data[vatnum]</td>
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
							<td>$note[comm]</td>
						</tr>
					</table>
				</tr>
			</tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr>
				<td>
					<table cellpadding='2' cellspacing='0' border='0' width='100%'>
						<tr>
							<td style='border-right: 2px solid #000'>&nbsp;</td>
							<td><b>Subtotal:</b></td>
							<td nowrap><b>".CUR." $note[amount]</b></td>
						</tr>
						<tr>
							<td style='border-right: 2px solid #000'>&nbsp;</td>
							<td><b>VAT $vat14:</b></td>
							<td nowrap><b>".CUR." $note[vatamt]</b></td>
						</tr>
						<tr>
							<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
							<td><b>Total Incl VAT:</b></td>
							<td nowrap><b>".CUR." $note[totamt]</b></td>
						</tr>
						<tr>
							<td style='border-right: 2px solid #000'>&nbsp;</td>
							<td></td>
							<td nowrap></td>
						<tr>
						<tr>
							<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
							<td></td>
							<td nowrap></td>
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
		if (isset($cccc)) {
			$costcentr = "nCostCenter('ct', 'Credit Note', '$note[odate]', 'Credit Note No.$note[notenum] for Customer $note[cusname] $note[surname]', '".($note['total']-$note['vat'])."', 'Credit Note No.$note[notenum]', '$tcosamt', '');";
		} else {
			$costcentr = "";
		}
		if (!isset($reprint) || $reprint == "no") {
			$type = "invnote";
		} else {
			$type = "invnotereprint";
		}

		$OUTPUT = "<script>$costcentr move(\"$template?invid=$note[noteid]&type=$type&prd=$prd\");</script>";
		require("template.php");
	}

}


?>
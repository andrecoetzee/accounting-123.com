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
if (isset($HTTP_GET_VARS["noteid"])) {
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
	$v->isOk ($noteid, "num", 1, 20, "Invalid Credit note number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");

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
	db_conn($prd);
	$sql = "SELECT * FROM inv_notes WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$noteRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($noteRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$note = pg_fetch_array($noteRslt);

	if($note['branch'] != 0){

		db_connect ();

		#get branch
		$get_branch = "SELECT * FROM customer_branches WHERE id = '$note[branch]' LIMIT 1";
		$run_branch = db_exec($get_branch) or errDie("Unable to get branch information.");
		if(pg_numrows($run_branch) < 1){
			$branchname = "";
		}else {
			$barr = pg_fetch_array($run_branch);
			$branchname = $barr['branch_name']."<br>";
		}
	}else {
		$branchname = "";
	}

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_conn($prd);
	$sql = "SELECT stkid FROM inv_note_items WHERE noteid = '$note[noteid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Credit note number <b>$note[notenum]</b> has no items.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = array();

	# Get selected stock in this invoice note
	db_conn($prd);
	$sql = "SELECT * FROM inv_note_items WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
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

		# get warehouse name
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# get selected stock in this warehouse
		if ( $stkd["stkid"] != "0" ) {
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$tcosamt += sprint(($stkd['qty'] * $stk['csprice']), 2);
		} else {
			$stk = array(
				'stkcod' => "",
				'stkdes' => $stkd['description'],
				'exvat' => ""
			);
		}

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
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
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$selamt = sprint($stkd['amt']/$stkd['qty']);
		$selamt2 = sprint($stkd['amt']);

		$nsub += $selamt2;

		# put in product
		$products[$page][] = "
		<tr valign='top'>
			<td style='border-right: 2px solid #000'>$stk[stkcod]&nbsp;</td>
			<td style='border-right: 2px solid #000'>$ex $stk[stkdes]&nbsp;</td>
			<td style='border-right: 2px solid #000'>".sprint3($stkd['qty'])."&nbsp;</td>
			<td style='border-right: 2px solid #000' align='right' nowrap>".CUR." $selamt&nbsp;</td>
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
 				<td>&nbsp;</td>
 			</tr>";
 		}
 	}

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
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		if (!isset($reprint) || $reprint == "no") {
			$show_reprint = "";
		}else {
			$show_reprint = "
				<tr>
					<td colspan='3' align='right'><font size='3'><b>Reprint</b></font></td>
				</tr>";
		}

		$details .= "
		<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left' rowspan='2'><img src='compinfo/getimg.php' width=230 height=47></td>
					<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Credit Note</b></font></td>
				</tr>
				$show_reprint
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td valign='top'>
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
			</td><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date</b></td>
					<td><b>Page Number</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$note[odate]</td>
					<td>".($i + 1)."</td>
				</tr>

				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>

				<tr>
					<td colspan='2'><b>Credit Note No:</b> $note[notenum]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Invoice No:</b> $note[invnum]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Sales Order No:</b> $note[ordno]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Credit Note To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$note[surname]</b></td>
					<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
					<td width='33%'><b>Delivery Address</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
					<td>$branchname".nl2br($note["cusaddr"])."</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $note[cusvatno]</td>
					<td width='33%'><b>Customer Order No:</b> $note[cordno]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
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
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$note[comm]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms:</b> $note[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td nowrap><b>".CUR." $note[subtot]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Trade Discount:</b></td>
					<td nowrap><b>".CUR." $note[traddisc]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
					<td><b>Delivery Charge</b></td>
					<td nowrap><b>".CUR." $note[delchrg]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td nowrap><b>".CUR." $note[vat]</b></td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td nowrap><b>".CUR." $note[total]</b></td>
				</tr>
				</tr>
			</table>
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

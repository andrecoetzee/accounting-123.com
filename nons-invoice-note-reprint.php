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
if (isset($_GET["noteid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");




# details
function details($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($noteid, "num", 1, 20, "Invalid credit note number.");

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

	# Get credit note info
	$sql = "SELECT * FROM nons_inv_notes WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get credit note information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$cur = CUR;
	if($inv['location'] == 'int')
		$cur = $inv['currency'];

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = array();
	$disc = 0;
	# Get selected stock in this credit note
	db_connect();
	$sql = "SELECT * FROM nons_note_items  WHERE noteid = '$noteid'";
	$stkdRslt = db_exec($sql);

	$i = 0;
	$page = 0;
	# Put in product
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$stk[vatcode]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stk[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$products[$page][] = "
						<tr valign='top'>
							<td style='border-right: 2px solid #000'>$ex $stk[description]&nbsp;</td>
							<td align='right' style='border-right: 2px solid #000'>$stk[qty]&nbsp;</td>
							<td align='right' style='border-right: 2px solid #000' nowrap>".CUR." $stk[unitcost]&nbsp;</td>
							<td align='right' nowrap>$cur $stk[amt]&nbsp;</td>
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

	#make sure we have a valid bank id for customer
	if (!isset($inv['bankid']) OR strlen ($inv['bankid']) < 1){
		$inv['bankid'] = '2';
	}

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='$inv[bankid]' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);
	
	db_conn("cubit");

	$sql = "SELECT * FROM nons_invoices WHERE invid='$inv[invid]'";
	$ni_rslt = db_exec($sql) or errDie("Unable to retrieve customer id from Cubit.");
	$ni_data = pg_fetch_array($ni_rslt);

	if($ni_data['cusid'] != "0"){
		// Retrieve customer information
		db_conn("cubit");
		$sql = "SELECT * FROM customers WHERE cusnum='$ni_data[cusid]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$cust_data = pg_fetch_array($cust_rslt);
	}else {
		$cust_data['surname'] = $inv['cusname'];
		$cust_data['addr1'] = $inv['cusaddr'];
		$cust_data['paddr1'] = $inv['cusaddr'];
	}

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;
	";
	
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
			
		$details .= "<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left' rowspan='2'><img src='compinfo/getimg.php' width=230 height=47></td>
					<td align='left' rowspan='2'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Credit Note</b></font></td>
				</tr>
				<tr>
					<!-- Rowspan -->
					<!-- Rowspan -->
					<td align='right'><font size='4'><b>Reprint</b></font></td>
				</tr>
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
					<td style='border-right: 2px solid #000'>$inv[date]</td>
					<td>".($i + 1)."</td>
				</tr>
				
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr><td>&nbsp</td></tr>
				
				<tr>
					<td colspan='2'><b>Credit Note No:</b> $inv[notenum]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Invoice No:</b> $inv[invnum]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Proforma Inv No:</b> $ni_data[docref]</td>
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
			</td></tr>
		</table>
		
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $inv[cusvatno]</td>
					<td width='33%'><b>Customer Order No:</b> $ni_data[cordno]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000' align='right'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000' align='right'><b>Unit Price</b></td>
					<td style='border-bottom: 2px solid #000;' align='right'><b>Amount</b></td>
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
					<td>$inv[remarks]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms:</b> $ni_data[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td nowrap><b>$cur $inv[subtot]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td nowrap><b>$cur $inv[vat]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td nowrap><b>$cur $inv[total]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
				</tr>
				</tr>
			</table>
		</table>
		";
	}

// 	/* -- Final Layout -- */
// 	$details = "<center><h2>Credit Note<br>Reprint</h2>
// 	<table cellpadding='0' cellspacing='4' border=0 width=750>
// 	<tr><td valign=top width='30%'>
// 		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
// 			<tr><td>$inv[cusname]</td></tr>
// 			<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
// 			<tr><td>(Vat No. $inv[cusvatno])</td></tr>
// 		</table>
// 	</td><td valign=top width='30%'>
// 		".COMP_NAME."<br>
// 		".COMP_ADDRESS."<br>
// 		".COMP_PADDR."<br>
// 		".COMP_TEL."<br>
// 		".COMP_FAX."<br>
// 		Reg No. ".COMP_REGNO."<br>
// 		Vat No. ".COMP_VATNO."
// 	</td><td width='15%'>
// 		<img src='compinfo/getimg.php' width='230' height='47'>
// 	</td><td valign='bottom' align='right' width='25%'>
// 		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
// 			<tr><td><b>Credit Note No.</b></td><td valign=center>$inv[notenum]</td></tr>
// 			<tr><td><b>Credit Note Date</b></td><td valign=center>$inv[date]</td></tr>
// 		</table>
// 	</td></tr>
// 	<tr><td><br></td></tr>
// 	<tr><td colspan=4>
// 	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
// 		<tr>
// 			<td width='65%'>DESCRIPTION</td>
// 			<td width='10%'>QTY</td>
// 			<td width='10%'>UNIT PRICE</td>
// 			<td width='10%'>AMOUNT</td>
// 		<tr>
// 		$products
// 	</table>
// 	</td></tr>
// 	<tr><td>
// 	</td><td>
// 	</td><td align=right colspan=3>
// 		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
// 			<tr><td><b>SUBTOTAL</b></td><td align=right>$cur $SUBTOT</td></tr>
// 			<tr><td><b>VAT $vat14</b></td><td align=right>$cur $VAT</td></tr>
// 			<tr><td><b>GRAND TOTAL<b></td><td align=right>$cur $TOTAL</td></tr>
// 		</table>
// 	</td></tr>
// 	<tr><td>
// 		<table cellpadding='5' cellspacing='0' border='1' bordercolor='#000000'>
// 			<tr><td>$comment</td></tr>
// 		</table>
// 	</td></tr>
// 	<tr><td><br></td></tr>
// 	</table></center>";

	// Retrieve the template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$OUTPUT = $details;
		require("tmpl-print.php");
	} else {
		header ("Location: $template?noteid=$inv[noteid]&type=nonsnote&reprint=t");
	}

}



?>
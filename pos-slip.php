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

// Required for the pdf_lstr function
require ("pdf-settings.php");

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");



# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

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
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* --- Start some checks --- */

	# check if invoice has been printed
	if($inv['printed'] == "n"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has not been printed.</li>";
		return $error;
	}

	# check if stock was selected(yes = put done button)
	db_conn($prd);
	$sql = "SELECT stkid FROM pinv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has no items.</li>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
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

		$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
		$tcosamt += $cosamt;

		$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		# keep track of discounts
		$disc += $stkd['disc'];

		if($stkd['account']!=0) {
			$stk['stkcod']=$stkd['description'];
			$stk['stkdes']="";
		}

		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$stkd['unitcost']=$stkd['unitcost']-$stkd['disc'];
		$products .= "
		<tr>
			<td><font size='1'>&nbsp;&nbsp;$stk[stkcod]</font></td>
			<td><font size='1'>".sprint($stkd["unitcost"])."</font></td>
			<td><font size='1'>$stkd[qty]</font></td>
			<td align=right><font size='1'>".sprint($stkd["amt"])."</font></td>
		</tr>
		<tr>
			<td colspan='4'><font size='1'>$stk[stkdes]</font></td>
		</tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
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

	pglib_transaction("BEGIN");

	# Update number of prints
	$inv['prints']++;
	db_conn($prd);
	$Sql = "UPDATE pinvoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	if(strlen($inv['comm'])>0){
		$Com = "
				<table>
					<tr>
						<td>".nl2br($inv['comm'])."</td>
					</tr>
				</table>";
	} else {
		$Com="";
	}


	$time=date("H:i");

	if(isset($cccc)) {
		$cc = "<script> sCostCenter('dt', 'Sales', '$inv[odate]', 'Invoice No.$inv[invnum] for Customer $inv[cusname] $inv[surname]', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$inv[invnum]', '$tcosamt', ''); </script>";
	} else {
		$cc="";
	}
	 db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='PSALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sp="
		<tr>
			<td width='50%' align='right'><font size='1'>SALES PERSON:</font></td>
			<td width='50%'><font size='1'>$inv[salespn]</font></td>
		</tr>";
	} else {
		$sp="";
	}

	$Sl="SELECT * FROM pc WHERE inv='$inv[invnum]'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pc = "
				<tr>
					<td>Change</td>
					<td align='right'><b>".CUR." $pd[amount]</b></td>
				</tr>";

		$change=$pd['amount'];
	} else {
		$pc="";
		$change=0;
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cash'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pd['amount']=sprint($pd['amount']+$change);

		$pcash = "
					<tr>
						<td>Paid Cash</td>
						<td align='right'><b>".CUR." $pd[amount]</b></td>
					</tr>";
	} else {
		$pcash = "";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cheque'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcheque = "
						<tr>
							<td>Paid Cheque</td>
							<td align='right'><b>".CUR." $pd[amount]</b></td>
						</tr>";
	} else {
		$pcheque = "";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit Card'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcc = "
					<tr>
						<td>Paid Credit Card</td>
						<td align='right'><b>".CUR." $pd[amount]</b></td>
					</tr>";
	} else {
		$pcc = "";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcc .= "
					<tr>
						<td>On Credit</td>
						<td align='right'><b>".CUR." $pd[amount]</b></td>
					</tr>
				";
	} else {
		$pcc.="";
	}


// 	$Sl="SELECT * FROM varrec WHERE inv='$inv[invnum]'";
// 	$Ri=db_exec($Sl);
//
// 	if(pg_num_rows($Ri)>0) {
// 		$rd=pg_fetch_array($Ri);
//
// 		$rounding="<tr><td>Rounding</td><td align=right>".CUR." $rd[amount]</td></tr>";
// 	} else {
// 		$rounding="";
// 	}

	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rounding = "
				<tr>
					<td>Rounding</td>
					<td align='right'>".CUR." $inv[rounding]</td>
				</tr>
				<tr>
					<td>Amount Due</td>
					<td align='right'>".CUR." $due</td>
				</tr>";
	} else {
		$rounding="";
	}


	$cusinfo = "";
	if($inv['cusnum']>0) {
		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$cd=pg_fetch_array($Ri);

		$inv['cusname'] = $cd['surname']." (VAT No. $cd[vatnum])<br>";
		$cusinfo .= "Tel: $inv[telno]<br>";
		$cusinfo .= "Order No: $inv[cordno]";
	}else {
		if(strlen($inv['vatnum']) > 1){
			$inv['cusname'] = "$inv[cusname] (VAT No. $inv[vatnum])<br>";
			$cusinfo .= "Order No: $inv[cordno]";
		}
	}

	db_conn('cubit');

	$Sl="SELECT img2 FROM compinfo";
	$Ri=db_exec($Sl);

	$id=pg_fetch_array($Ri);

	if(strlen($id['img2'])>0) {
		$logo = "
					<tr>
						<td valign='top' width='100%' align='center'><img src='compinfo/getimg2.php' width='230' height='47'></td>
					</tr>
				";
	} else {
		$logo="";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}
	
	if (($posmsg = nl2br(getCSetting("POSMSG"))) === false) {
		$posmsg = "THANK YOU FOR YOUR PURCHASE";
	}

	if ($inv["pslip_sordid"] > 0) {
		$barcode = "<img src='manufact/".pick_slip_barcode($inv["pslip_sordid"], 1)."' />";
	} else {
		$barcode = "";
	}

	$nb_top = "border-top: none;";
	$nb_left = "border-left: none;";
	$nb_right = "border-right: none;";
	$nb_bot = "border-bottom: none;";

	$details = "$cc
	<table cellpadding='0' cellspacing='1' border=0 width='220'>
	<tr><td><hr style='border: 1px solid black; $nb_bot'></td></tr>
	<tr><td align='center'><font size='1'>TAX INVOICE</font></td></tr>
	<tr><td align='center'>$barcode</td></tr>
	<tr><td><hr style='border: 1px solid black; $nb_top'></td></tr>
	$logo
	<tr><td valign=top width='100%'>
		<font size='1'>".COMP_NAME."</font><br>
		<font size='1'>".COMP_ADDRESS."</font><br>
		<br>
		<font size='1'>TEL: ".COMP_TEL."</font><br>
		<font size='1'>FAX: ".COMP_FAX."</font><br>
		<br>
		<font size='1'>Registration Number: ".COMP_REGNO."</font><br>
		<font size='1'>VAT Registration Number: ".COMP_VATNO."</font><br>
	</td></tr>
	<tr><td><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td></tr>
	<tr><td>
		<table ".TMPL_tblDflts." width='100%'>
			<tr><td align='center'><font size='1'>$inv[cusname]</font></td></tr>
			<tr><td align='left'><font size='1'>$cusinfo</font></td></tr>
		</table>
	</td></tr>
	<tr><td>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td align='left' width='33.33%'><font size='1'>Inv: $inv[invnum]</font></td>
				<td width='33.33%'><font size='1'>$time</font></td>
				<td width='33.33%' align='right'><font size='1'>$inv[odate]</font></td>
			</tr>
		</table>
	</td></tr>
	<tr><td><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td></tr>
	<tr><td>
	<table cellpadding='4' cellspacing='0' border='0' width='100%' bordercolor='#000000'>
		<tr>
			<td><font size='1'>CODE</font></td>
			<td><font size='1'>UNIT PRICE</font></td>
			<td><font size='1'>QTY</font></td>
			<td><font size='1'>TOTAL</font></td>
		<tr>
		$products
	</table>
	</td></tr>
	<tr><td align=right>
		<table cellpadding='2' cellspacing='0' border=0 width='100%' bordercolor='#000000'>
			<tr><td colspan='2'><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td></tr>
			<tr><td><font size='1'>SUBTOTAL</font></td><td align='right'><font size='1'>".CUR." $SUBTOT</font></td></tr>
			<tr><td><font size='1'>Trade Discount</font></td><td align='right'><font size='1'>".CUR." $traddiscm</font></td></tr>
			<tr><td><font size='1'>Delivery Charge</font></td><td align='right'><font size='1'>".CUR." $inv[delchrg]</font></td></tr>
			<tr><td><font size='1'>VAT $vat14</font></td><td align='right'><font size='1'>".CUR." $VAT</font></td></tr>
			<tr><td><font size='1'>GRAND TOTAL</font></td><td align='right'><b><font size='1'>".CUR." $TOTAL</font></b></td></tr>
			<font size='1'>$rounding</font>
			<font size='1'>$pcash</font>
			<font size='1'>$pcheque</font>
			<font size='1'>$pcc</font>
			<font size='1'>$pc</font>
			<tr><td colspan='2'><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td></tr>
			<tr><td colspan='2'><hr style='border: 1px solid black; $nb_bot'></td></tr>
			<tr>
				<td colspan='2' align='center'>
				<table cellpadding='2' cellspacing='0'>
				<tr>
					<td width='50%' align='right'><font size='1'>CASHIER:</font></td>
					<td width='50%'><font size='1'>$inv[username]</font></td>
				</tr>
				$sp
				</table>
				</td>
			</tr>
			<tr><td colspan='2'><hr style='border: 1px solid black; $nb_top'></td></tr>
			<tr><td colspan='2'><font size='1'>$posmsg</font></td></tr>
			<tr><td>
				<font size='1'>$Com</font>
			</td></tr>
		</table>
	</td></tr>
	</table>";

	$OUTPUT = $details;
	require("tmpl-print.php");

}

?>

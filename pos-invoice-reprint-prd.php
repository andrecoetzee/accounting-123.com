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

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.</li>";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
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
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* --- Start some checks --- */

	# check if invoice has been printed
	if($inv['printed'] == "n"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has not been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# check if stock was selected(yes = put done button)
	db_conn($prd);
	$sql = "SELECT stkid FROM pinv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has no items.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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

	while($stkd = pg_fetch_array($stkdRslt)){

		if($stkd['account']==0) {
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
		
		} else {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
				$stk['exvat'] ="";
			}

		$sp = "";
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$ex = "#";
		}else{
			$ex = "";
		}

		# keep track of discounts
		$disc += $stkd['disc'];

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .="<tr valign=top><td>$stk[stkcod]</td><td>$ex $sp $stk[stkdes]</td><td>$stkd[qty]</td><td>".sprint($stkd["unitcost"])."</td><td>$stkd[disc]</td><td>".CUR. sprint($stkd["amt"])."</td></tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	# Update number of prints
	$inv['prints']++;
	db_conn($prd);
	$Sql = "UPDATE pinvoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/

	if(strlen($inv['comm'])>0){
		$Com="<table><tr><td>".nl2br($inv['comm'])."</td></tr></table>";
				} else {$Com="";}

	db_conn('cubit');
	
	$Sl="SELECT * FROM settings WHERE constant='SALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");
	
	$data=pg_fetch_array($Ri);
	
	if($data['value']=="Yes") {
		$sp="<tr><td><b>Sales Person</b></td><td>$inv[salespn]</td></tr>";
	} else {
		$sp="";
	}

	if($inv['cusnum']>0) {
		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$cd=pg_fetch_array($Ri);

		$inv['cusname']=$cd['surname']." (VAT No. $cd[vatnum])";

	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}
	
	$Sl="SELECT * FROM pc WHERE inv='$inv[invnum]'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);
	
		$pc="<tr><td><b>Change</b></td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
		
		$change=$pd['amount'];
	} else {
		$pc="";
		$change=0;
	}

	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rounding="<tr><td>Rounding</td><td align=right>".CUR." $inv[rounding]</td></tr>
		<tr><td>Amount Due</td><td align=right>".CUR." $due</td></tr>";
	} else {
		$rounding="";
	}
	
	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cash'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);
		
		$pd['amount']=sprint($pd['amount']+$change);
	
		$pcash="<tr><td><b>Paid Cash</b></td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcash="";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cheque'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);
	
		$pcheque="<tr><td><b>Paid Cheque</b></td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcheque="";
	}
	
	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit Card'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);
	
		$pcc="<tr><td><b>Paid Credit Card</b></td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcc="";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);
	
		$pcc.="<tr><td><b>On Credit</b></td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcc.="";
	}

	/* -- Final Layout -- */
	$details = "<center><h2>Tax Invoice<br>Reprint $inv[prints]</h2>
	<table cellpadding='0' cellspacing='1' border=0 width=750>
	<tr><td valign=top width=40%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$inv[cusname]</td></tr>
		</table>
	</td><td valign=top width=35%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
	</td><td valign=bottom align=right width=25%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invnum]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>Cash</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
			<tr><td><b>Cashier</b></td><td>$inv[username]</td></tr>
			$sp
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=3>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr>
			<td><b>ITEM NUMBER</b></th>
			<td width=45%><b>DESCRIPTION</b></th>
			<td><b>QTY</b></th>
			<td><b>UNIT PRICE</b></th>
			<td><b>UNIT DISCOUNT</b></th>
			<td><b>AMOUNT</b></th>
		<tr>
		$products
	</table>
	</td></tr>
	<tr><td>
		$Com
	</td><td align=right colspan=2>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr><td><b>VAT $vat14</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><td><b>GRAND TOTAL<b></td><td align=right>".CUR." $TOTAL</td></tr>
			$rounding
			$pcash
			$pcheque
			$pcc
			$pc
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='0' border=1>
		<tr><td colspan=2>VAT Exempt indicator = #</td></tr>
        	<tr><td><b>VAT No.</b></td><td align=center>".COMP_VATNO."</td></tr>
		</table>
	</td><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}

?>

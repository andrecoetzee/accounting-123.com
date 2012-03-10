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
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
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
	db_connect();
	$sql = "SELECT * FROM cancelled_sord WHERE sordid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);


	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM sorders_items WHERE sordid = '$inv[sordid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Consignment number <b>$invid</b> has no items.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM sorders_items  WHERE sordid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

		if($stkd['account']==0) {

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

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			$vd=pg_fetch_array($Ri);

			if($vd['zero']=="Yes") {
				$excluding="y";
			} else {
				$excluding="";
			}

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

// 			$vr=vatcalc($stkd['unitcost'],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
// 			$vrs=explode("|",$vr);
// 			$ivat=$vrs[0];
// 			$iamount=$vrs[1];
// 
// 			$vatamount += $ivat;


		} else {
			$wh['whname']="";
			$stk['stkcod']="";
			$stk['stkdes']=$stkd['description'];
		}

		# Keep track of discounts
		$disc += $stkd['disc'];

		// Should we display the costs
		if ($inv["display_costs"] == "yes") {
			$products_costs = "<td>$stkd[unitcost]</td><td>$stkd[disc]</td><td>".CUR." $stkd[amt]</td></tr>";
		} else {
			$products_costs = "";
		}
				
		# Put in product
		$products .="<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td>$products_costs";
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

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	/*
	# minus discount
	# $SUBTOT -= $disc; --> already minused

	# duplicate
	$SUBTOTAL = $SUBTOT;

	# minus trade discount
	$SUBTOTAL -= $traddiscm;

	# add del charge
	$SUBTOTAL += $inv['delchrg'];


	# if vat must be charged
	if($inv['chrgvat'] == "yes"){
		$VATP = TAX_VAT;
		$VAT = sprintf("%01.2f", (($VATP/100) * $SUBTOTAL));
	}else{
		$VATP = 0;
		$VAT = "0.00";
	}

	# total
	$TOTAL = sprint($SUBTOTAL + $VAT);
	*/
	
	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "<table border=1 cellspacing='0' bordercolor='#000000'>
			<tr><td>".nl2br($inv['comm'])."</td></tr>
		</table>";
	}

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Should we display the costs
	if ($inv["display_costs"] == "yes") {
		$headings_costs = "<td><b>UNIT PRICE</b></td><td><b>UNIT DISCOUNT</b></td><td><b>AMOUNT</b></td>";
		$totals_costs = "</td><td align=right colspan=3>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr><td><b>VAT $vat14</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><td><b>GRAND TOTAL<b></td><td align=right>".CUR." $TOTAL</td></tr>
		</table>";
	} else {
		$headings_costs = "";
		$totals_costs = "";
	}

	if(isset($inv['proforma']) AND ($inv['proforma'] == "yes")){
		$header = "Cancelled Proforma Invoice";
	}else {
		$header = "Cancelled Sales Order";
	}

	/* -- Final Layout -- */
	$details = "<center><h2>$header</h2>
	<table cellpadding='0' cellspacing='4' border=0 width=770>
	<tr><td valign=top width=30%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$inv[surname]</td></tr>
			<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
			<tr><td>(Vat No. $inv[cusvatno])</td></tr>
		</table>
	</td><td valign=top width=30%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
                VAT No. ".COMP_VATNO."<br>
	</td><td align=left width=20%>
		<img src='compinfo/getimg.php' width=230 height=47>
	</td><td valign=bottom align=right width=20%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Sales Order No.</b></td><td valign=center>$inv[sordid]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>$inv[terms] Days</td></tr>
			<tr><td><b>Date</b></td><td valign=center>$inv[odate]</td></tr>
			<tr><td><b>Vat</b></td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><td><b>ITEM NUMBER</b></td><td width=45%><b>DESCRIPTION</b></td><td><b>QTY</b></td>$headings_costs<tr>
		$products
	</table>
	</td></tr>
	<tr><td>
	$inv[comm]
	$totals_costs
	</td></tr>
	<tr><td colspan='5'>
		<table border=0>
			<tr><td>&nbsp</td></tr>
			<tr><td>Signature: __________________________________________</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}
?>

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
if (isset($HTTP_GET_VARS["sordid"])) {
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
	$v->isOk ($sordid, "num", 1, 20, "Invalid invoice number.");

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
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$cordRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($cordRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$cord = pg_fetch_array($cordRslt);


	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM corders_items WHERE sordid = '$cord[sordid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Consignment number <b>$sordid</b> has no items.";
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
	$sql = "SELECT * FROM corders_items  WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){
		if ($stkd['account'] == 0) {
			// determine warehouse name
			$wh = qryWarehouse($stkd["whid"], "whname");

			// get selected stock in this warehouse
			$stk = qryStock($stkd["stkid"]);

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}
		} else {
			$wh['whname'] = "";
			$stk['stkcod'] = "";
			$stk['stkdes'] = $stkd['description'];
		}

		// totals of discounts
		$disc += $stkd['disc'];

		# put in product
		$products .= "
		<tr valign=top>
			<td>$stk[stkcod]</td>
			<td>$stk[stkdes]</td>
			<td>$stkd[qty]</td>
			<td>".sprint($stk["selamt"])."</td>
			<td>".CUR."$stkd[disc]</td>
			<td>".CUR." ".sprint($stkd["amt"])."</td>
		</tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($cord['subtot']);

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($cord['subtot']);
 	$VAT = sprint($cord['vat']);
	$TOTAL = sprint($cord['total']);
	$cord['delchrg'] = sprint($cord['delchrg']);

	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
	if(strlen($cord['comm']) > 0){
		$cord['comm'] = "<table border=1 cellspacing='0' bordercolor='#000000'>
			<tr><td>".nl2br($cord['comm'])."</td></tr>
		</table>";
	}

	if($cord['chrgvat']=="inc") {
		$cord['chrgvat']="Inclusive";
	} elseif($cord['chrgvat']=="exc") {
		$cord['chrgvat']="Exclusive";
	} else {
		$cord['chrgvat']="No vat";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "<center><h2>Consignment Order</h2>
	<table cellpadding='0' cellspacing='4' border=0 width=770>
	<tr><td valign=top width=30%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$cord[surname]</td></tr>
			<tr><td>".nl2br($cord['cusaddr'])."</td></tr>
			<tr><td>(VAT No. $cord[cusvatno])</td></tr>
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
			<tr><td><b>Consignment Order No.</b></td><td valign=center>$cord[sordid]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$cord[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>$cord[terms] Days</td></tr>
			<tr><td><b>Date</b></td><td valign=center>$cord[odate]</td></tr>
			<tr><td><b>VAT</b></td><td valign=center>$cord[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr>
			<th>ITEM NUMBER</th>
			<th width=45%>DESCRIPTION</th>
			<th>QTY</th>
			<th>UNIT PRICE</th>
			<th>UNIT DISCOUNT</th>
			<th>AMOUNT</th>
		<tr>
		$products
	</table>
	</td></tr>
	<tr><td>
	$cord[comm]
	</td><td align=right colspan=3>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $cord[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $cord[delivery]</td></tr>
			<tr><td><b>VAT $vat14</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><td><b>GRAND TOTAL<b></td><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}
?>

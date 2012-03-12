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

# Decide what to do
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# Get templete
require("template.php");

# Details
function details($_GET)
{
	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	$prd+=0;
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
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Check if invoice has been printed
	if($inv['printed'] != "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has not been printed yet.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_conn($prd);
	$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
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
	$taxex = 0;

	# Get selected stock in this invoice
	db_conn($prd);
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

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

		$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$taxex += ($stkd['amt']);
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		# Keep track of discounts
		$disc += $stkd['disc'];

		# Put in product
		$products .="<tr valign=top><td>$stk[stkcod]</td><td>$ex $sp $stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>".CUR." $stkd[disc]</td><td>$inv[currency] $stkd[amt]</td></tr>";
	}

	/* --- Start Some calculations --- */

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

	# Update number of prints
	$inv['prints']++;
	db_conn($prd);
	$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	/* -- Final Layout -- */
	$details = "<center><h2>Tax Invoice<br>Reprint $inv[prints]</h2>
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
		".COMP_PADDR."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
		VAT No. ".COMP_VATNO."<br>
	</td><td align=left width=20%>
		<img src='compinfo/getimg.php' width=230 height=47>
	</td><td valign=bottom align=right width=20%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invnum]</td></tr>
			<tr><td><b>Proforma Inv No.</b></td><td>$inv[docref]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>$inv[terms] Days</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><td>ITEM NUMBER</td><td width=45%>DESCRIPTION</td><td>QTY</td><td>UNIT PRICE</td><td>UNIT DISCOUNT</td><td>AMOUNT</td><tr>
		$products
	</table>
	</td></tr>
	<tr><td>
	$inv[comm]
	</td><td>
		".BNK_BANKDET."
	</td><td align=right colspan=2>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>$inv[currency] $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>$inv[currency] $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>$inv[currency] $inv[delivery]</td></tr>
			<tr><td><b>VAT @ $VATP%</b></td><td align=right>$inv[currency] $VAT</td></tr>
			<tr><td><b>GRAND TOTAL<b></td><td align=right>$inv[currency] $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='2' cellspacing='0' border=1>
			<tr><td colspan=2>VAT Exempt indicator = #</td></tr>
		</table>
	</td><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}
?>

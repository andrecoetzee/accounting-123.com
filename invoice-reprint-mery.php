<?
// Haal Die ../cubitpro/ En Onnodige Comments Uit In Alles !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# Decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# Get templete
require("template.php");

# Details
function details($HTTP_GET_VARS)
{

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
	db_connect();
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
	db_connect();
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
		$products .="<tr valign=top><td>$stk[stkcod]</td><td>$ex $sp $stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>$stkd[disc]</td><td>".CUR." $stkd[amt]</td></tr>";
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
	db_connect();
	$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='SALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sp="<tr><td><b>Sales Person</b></td><td>$inv[salespn]</td></tr>";
	} else {
		$sp="";
	}


$cusinf = "<td colspan=5 valign=right width=40%>
		<table cellpadding='4' cellspacing='".TMPL_tblCellSpacing."' border=1>
			<td>$inv[surname]<br>
			(Vat No. $inv[cusvatno])<br>
			Customer Order Number: $inv[cordno]<br></td>
			<td>".nl2br($inv['cusaddr'])."<br></td>
		</table>
	</td>";
$comp1 = "<td valign=left width=40%>
		<table cellpadding='5' cellspacing='0' border=1>
			<td>
			".COMP_NAME."
			Reg No. ".COMP_REGNO."<br>
			VAT No. ".COMP_VATNO."<br>
			Tel No. ".COMP_TEL."<br>
			Fax No. ".COMP_FAX."<br>
			</td>
			<td>
			".COMP_ADDRESS."<br>
			".COMP_PADDR."<br>
			</table></td>";

$compinf = "
<td valign=left width=40%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_PADDR."<br>
		Tel No. ".COMP_TEL."<br>
		Fax No. ".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
		VAT No. ".COMP_VATNO."<br>
	</td>
";

// ../cubitpro/!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//<img src='../cubitpro/compinfo/getimg.php' width=230 height=47>
$logo = "
<td align=left width=100%><table width=100%><tr><td>
		<img src='../testing/corne.png' width=230 height=47>
	</td></tr></table></td>
";
//
$invinf = "<td valign=bottom align=right width=200 colspan=5>
		<table cellpadding='4' cellspacing='1' border=1>
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invnum]</td></tr>
			<tr><td><b>Proforma Inv No.</b></td><td>$inv[docref]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>$inv[terms] Days</td></tr>
			$sp
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
		</table>
	</td>";

$sale = "<td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100%>
		<tr><td><b>ITEM NUMBER</b></td><td width=45%><b>DESCRIPTION</b></td><td><b>QTY</b></td><td><b>UNIT 						PRICE</b></td><td><b>DISCOUNT</b></td><td><b>AMOUNT</b></td><tr>
		$products
	</table>
	</td>";
$sale1 = "<td align=right colspan=5>
		<table cellpadding='4' cellspacing='1' border=1 width=200 bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><td><b>GRAND TOTAL<b></td><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td> ";
$comm = "<td><table>
		
		<tr valign='top'><b>Thank you for your support</b></tr>
		<p>
		<br>
		<tr><b>Received in good order by : _________________________________</b></tr></p></br>
		<p>
		<tr><b>Date : _________________________</b></tr></p>
		<td>
	$inv[comm]
		</td>
		</table>
		</td>";


$bankdet = "<td><table border=1><tr><td>
		".BNK_BANKDET."
	</td></tr></table></td>";

$bankv = "<td>
		<table cellpadding='2' cellspacing='0' border=1>
			<tr><td colspan=2>VAT Excempt indicator = #</td></tr>
		</table>
	</td>";


/* -- Final Layout -- */
	$details = "<center><table align=center border=1><tr><td><h2 align=center>Tax Invoice<br>Reprint $inv[prints]</h2>
		<table cellpadding='1' cellspacing='1' border=1 width=770>
		<tr><td><tr><td>$logo</td></tr>
		<tr><td>$comp1

			$cusinf</td>
		</tr>
		<tr>
			<td>$bankdet</td>
			<td>$invinf</td>
		</tr>
		<tr><br></tr>
		<tr>
			<td>$sale</td>
		</tr>
		<tr><br></tr>
		<tr>
			<td>$comm</td>
			<td>$sale1</td>
		</tr>
		</td></tr></table></td></tr></table></center>";
	
	$OUTPUT = $details;
	require("pdfprint.php");
}
?>

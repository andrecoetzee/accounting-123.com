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
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
}

# get templete
require("template.php");

# details
function details($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
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
			$err .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_connect();
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
	db_connect();
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
	db_connect();
	$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

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

		# keep track of discounts
		$disc += $stkd['disc'];

		# put in product
		$products .="<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>".sprint($stk["selamt"])."</td><td>".CUR. sprint($stkd["amt"])."</td></tr>";
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

	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/
	
	if(strlen($inv['comm'])>0){
		$Com="<table><tr><td>".nl2br($inv['comm'])."</td></tr></table>";
				} else {$Com="";}

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}
	
	
	/* -- Final Layout -- */
	$details = "<center><h2>Tax Invoice</h2>
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
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invid]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>Cash</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
			<tr><td><b>Vat</b></td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=3>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><th>ITEM NUMBER</th><th width=45%>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>AMOUNT</th><tr>
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
			<tr><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><th><b>GRAND TOTAL<b></th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
        	<tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>
	</td><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}

?>

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
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid purchase number.");

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

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);
	$currs = getSymbol($inv['fcid']);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr>
		<th width='5%'>#</th>
		<th width='65%'>DESCRIPTION</th>
		<th width='10%'>QTY</th>
		<th width='10%' colspan=2>UNIT PRICE</th>
		<th width='10%'>AMOUNT</th>
	<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'>
			<td align=center>$i</td>
			<td>$stkd[description]</td>
			<td>$stkd[qty]</td>
			<td>".CUR." $stkd[cunitcost]</td>
			<td>$inv[currency] $stkd[unitcost]</td>
			<td>$inv[currency] $stkd[amt]</td>
		</tr>";
	}
	$products .= "</table>";

 	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

	/* --- End Some calculations --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['sdate']);

	if($inv['invnum']==0) {
		$inv['invnum']=$inv['invid'];
	}

	/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Invoice Details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer</td><td valign=center>$inv[cusname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Address</td><td valign=center><pre>$inv[cusaddr]</pre></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Vat Number</td><td valign=center>$inv[cusvatno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Order number</td><td valign=center>$inv[cordno]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Invoice Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Non-Stock Invoice No.</td><td valign=center>$inv[invnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Proforma Inv No.</td><td valign=center>$inv[docref]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$sday-$smon-$syear</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Foreign Currency</td><td valign=center>$currs[symbol] - $currs[name] &nbsp;&nbsp;Exchange rate ".CUR." $inv[xrate]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Inclusive</td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=40%>Quick Links</th><th width=45%>Remarks</th><td rowspan=5 valign=top width=15%><br></td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top>".nl2br($inv['remarks'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>$inv[currency] $inv[subtot]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT @ ".TAX_VAT." %</td><td align=right>$inv[currency] $inv[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>$inv[currency] $inv[total]</td></tr>
		</table>
	</td></tr>
	</table></form>
	</center>";

	return $details;
}
?>

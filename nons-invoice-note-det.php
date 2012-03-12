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
if (isset($_GET["noteid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($_GET)
{

	$showvat = TRUE;

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($noteid, "num", 1, 20, "Invalid credit note number.");

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
	$sql = "SELECT * FROM nons_inv_notes WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get credit notes information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$cur = CUR;
	if($inv['location'] == 'int')
		$cur = $inv['currency'];

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr>
		<th width='5%'>#</th>
		<th width='65%'>DESCRIPTION</th>
		<th width='10%'>QTY</th>
		<th width='10%'>UNIT PRICE</th>
		<th width='10%'>AMOUNT</th>
	<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM nons_note_items  WHERE noteid = '$noteid'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'>
			<td align=center>$i</td>
			<td>$stkd[description]</td>
			<td>$stkd[qty]</td>
			<td>$stkd[unitcost]</td>
			<td>$cur $stkd[amt]</td>
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
	list($syear, $smon, $sday) = explode("-", $inv['date']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Credit Note Details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer</td><td valign=center>$inv[cusname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer Address</td><td valign=center><pre>$inv[cusaddr]</pre></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Vat Number</td><td valign=center>$inv[cusvatno]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Credit note Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Credit note No.</td><td valign=center>$inv[notenum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$sday-$smon-$syear</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-invoice-note-view.php'>View Non-Stock credit notes</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>$cur $inv[subtot]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT $vat14</td><td align=right>$cur $inv[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>$cur $inv[total]</td></tr>
		</table>
	</td></tr>
	</table></form>
	</center>";

	return $details;
}
?>

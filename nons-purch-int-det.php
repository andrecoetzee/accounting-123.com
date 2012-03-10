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
if (isset($HTTP_GET_VARS["purid"])) {
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
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

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

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	# Currency
	$currs = getSymbol($pur['fcid']);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th colspan=2>UNIT PRICE</th><th colspan=2>DUTY</th><th>LINE TOTAL</th><tr>";
		# get selected stock in this Order
		db_connect();
		$sql = "SELECT * FROM nons_purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# put in product
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$stkd[cod]</td><td>$stkd[des]</td><td>$stkd[qty]</td><td>$pur[curr] $stkd[cunitcost] or </td><td>".CUR." $stkd[unitcost]</td><td>$pur[curr] $stkd[duty] or </td><td>$stkd[dutyp]%</td><td>$pur[curr] $stkd[amt]</td></tr>";
		}

	$products .= "</table>";

	/* --- End Products Display --- */

 	/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get tax
	$tax = sprint($pur['tax']);

	/* --- End Some calculations --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

	/* -- Final Layout -- */
	$details = "<center><h3>International Non-Stock Order Details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td valign=center>$pur[supplier]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Supplier Address</td><td valign=center><pre>$pur[supaddr]</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Order Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Non-Stock Order No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Order No.</td><td valign=center>$pur[ordernum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td valign=center>$pday-$pmon-$pyear</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Foreign Currency</td><td valign=center>$currsel &nbsp;&nbsp;Exchange rate $pur[curr] $pur[xrate]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Tax</td><td valign=center>$pur[curr] $pur[tax]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Shipping Charges</td><td valign=center>$pur[curr] $pur[shipchrg]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Date</td><td valign=center>$dday-$dmon-$dyear</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=40%>Quick Links</th><th width=45%>Remarks</th><td rowspan=5 valign=top width=15%><br></td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='nons-purch-int-new.php'>New International Non-Stock Order</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top>".nl2br($pur['remarks'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>$pur[curr] $pur[subtot]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td align=right>$pur[curr] $pur[shipping]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Tax </td><td align=right>$pur[curr] $pur[tax]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>$pur[curr] $pur[total]</td></tr>
		</table>
	</td></tr>
	</table></form>
	</center>";

	return $details;
}
?>

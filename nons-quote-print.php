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
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");




# details
function details($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# format date
	$inv['odate'] = explode("-", $inv['odate']);
	$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];


	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

        # Put in product
	while($stk = pg_fetch_array($stkdRslt)){

		$Sl = "SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$stk['vatex'] = "y";
		}
	
		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		$products .= "
			<tr valign='top'>
				<td style='border-right: 2px solid #000' nowrap>$ex $stk[description]</td>
				<td style='border-right: 2px solid #000' align='right'>$stk[qty]</td>
				<td style='border-right: 2px solid #000' align='right'>$stk[unitcost]</td>
				<td align='right'>".CUR." $stk[amt]</td>
			</tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/
	

	/* --- Updates ---- */
	db_connect();

	# Get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	/* -- Format the remarks boxlet -- */
//	$inv["remarks"] = "<table border='1'><tr><td>Remarks:<br>$inv[remarks]</td></tr></table>";
	
	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;";

	/* -- Final Layout -- */
	$details = "
		<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left'><img src='compinfo/getimg.php' width='230' height='47'>$barcode</td>
					<td align='left'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Quote</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'>".COMP_ADDRESS."&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>REG:</b> ".COMP_REGNO."</b>&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT REG:</b> ".COMP_VATNO."&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Tel:</b> ".COMP_TEL."&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Fax:</b> ".COMP_FAX."&nbsp;</td>
				</tr>
			</table>
			</td><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><b>Date</b></td>
				</tr>
				<tr>
					<td>$inv[odate]</td>
				</tr>
				<tr>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr>
					<td colspan='2'><b>Quote No:</b> $inv[invid]</td>
				</tr>
				$sp
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Quote To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$inv[cusname]</b></td>
					<td width='33%'><b>Address</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000' valign='bottom'><b>Customer VAT No:</b> $inv[cusvatno]</td>
					<td>".nl2br($inv["cusaddr"])."</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT:</b> $inv[chrgvat]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Price</b></td>
					<td style='border-bottom: 2px solid #000' align='right'><b>Amount</b></td>
				</tr>
				$products
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$inv[remarks]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000' width='60%'>&nbsp;</td>
					<td><b>Subtotal:</b></td>
					<td align='right' nowrap><b>".CUR." $inv[subtot]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT @ ".TAX_VAT."%:</b></td>
					<td align='right' nowrap><b>".CUR." $inv[vat]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Total Incl VAT:</b></td>
					<td align='right' nowrap><b>".CUR." $inv[total]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td></td>
					<td nowrap></td>
			</table>
		</table>";

	$OUTPUT = $details;
	require("tmpl-print.php");

}


?>
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
if (isset($HTTP_GET_VARS["quoid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");



# details
function details($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

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


	# Get quote info
	db_connect();

	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	# format date
	$quo['odate'] = explode("-", $quo['odate']);
	$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];

	
	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_connect();

	$sql = "SELECT stkid FROM quote_items WHERE quoid = '$quo[quoid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Quote number <b>$quoid</b> has no items.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this quote
	db_connect();

	$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

		if($stkd['account'] == 0) {

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
		} else {
			$wh['whname'] = "&nbsp;";
			$stk['stkcod'] = "&nbsp;";
			$stk['stkdes'] = $stkd['description'];
		}

		# Keep track of discounts
		$disc += $stkd['disc'];
	
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd = pg_fetch_array($Ri);

		// No need to check if it is ONLY one non stock
		if (!empty($stk['exvat'])) {
			# Check Tax Excempt
			if($stk['exvat'] == 'yes' || $vd['zero'] == "Yes"){
				//$taxex += ($stkd['amt']);
				$ex = "#";
			}else{
				$ex = "";
			}

			# all must be excempted
			if($quo['chrgvat'] == 'nov'){
				$ex = "#";
			}
		} else {
			$ex = "";
		}

		# Put in product
		$products .= "
			<tr valign='top'>
				<td style='border-right: 2px solid #000' nowrap>$stk[stkcod]</td>
				<td style='border-right: 2px solid #000' nowrap>$ex $sp $stk[stkdes]</td>
				<td style='border-right: 2px solid #000' align='right'>$stkd[qty]</td>
				<td style='border-right: 2px solid #000' align='right'>$stkd[unitcost]</td>
				<td style='border-right: 2px solid #000' align='right'>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
				<td align='right'>".CUR." $stkd[amt]</td>
			</tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($quo['subtot']);

	# Calculate tradediscm
	if(strlen($quo['traddisc']) > 0){
		$traddiscm = sprint((($quo['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);
	$quo['delchrg'] = sprint($quo['delchrg']);
	$expl = "VAT Exempt indicator";
	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
// 	if(strlen($quo['comm']) > 0){
// 		$quo['comm'] = "<table border=1 cellspacing='0' bordercolor='#000000'>
// 			<tr><td>".nl2br($quo['comm'])."</td></tr>
// 		</table>";
// 	}

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;";

	if (!isset ($quo['cusname']) OR strlen ($quo['cusname']) < 1) {
		if (isset ($quo['surname']) AND strlen ($quo['surname']) > 0){
			$quo['cusname'] = $quo['surname'];
		}else {
			$quo['cusname'] = "&nbsp;";
		}
	}

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
					<td>$quo[odate]</td>
				</tr>
				<tr>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr>
					<td colspan='2'><b>Quote No:</b> $quo[quoid]</td>
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
					<td width='33%' style='border-right: 2px solid #000'><b>$quo[cusname]</b></td>
					<td width='33%'><b>Address</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000' valign='bottom'><b>Customer VAT No:</b> $quo[cusvatno]</td>
					<td>".nl2br($quo["cusaddr"])."</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT:</b> $quo[chrgvat]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Code</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Price</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Discount</b></td>
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
					<td>$quo[comm]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000' width='60%'>&nbsp;</td>
					<td><b>Subtotal:</b></td>
					<td align='right' nowrap><b>".CUR." $quo[subtot]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT @ ".TAX_VAT."%:</b></td>
					<td align='right' nowrap><b>".CUR." $quo[vat]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Total Incl VAT:</b></td>
					<td align='right' nowrap><b>".CUR." $quo[total]</b></td>
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
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
if (isset($HTTP_GET_VARS["noteid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'> Invalid use of module.</li>";
}

# get templete
require("template.php");



# details
function details($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($noteid, "num", 1, 20, "Invalid Credit note number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period number.");

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

	# Get invoice info
	db_conn($prd);

	$sql = "SELECT * FROM inv_notes WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$noteRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($noteRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$note = pg_fetch_array($noteRslt);

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_conn($prd);

	$sql = "SELECT stkid FROM inv_note_items WHERE noteid = '$note[noteid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Credit note number <b>$note[notenum]</b> has no items.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";

	# Get selected stock in this invoice note
	db_conn($prd);

	$sql = "SELECT * FROM inv_note_items WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
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
		
		$stkd['vatcode'] += 0;

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]' AND zero='Yes'";
		$Ri = db_exec($Sl);
		
		if(pg_num_rows($Ri) > 0) {
			$stk['exvat'] = "yes";
		} else {
			$stk['exvat'] = "";
		}
		
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			//$taxex += ($stkd['amt']);
			$ex = "#";
		}else{
			$ex = "";
		}

		# cost amount
		$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
		$tcosamt += $cosamt;

		$selamt = sprint($stkd['amt']/$stkd['qty']);

		# put in product
		$products .= "
			<tr valign='top'>
				<td>$stk[stkcod]</td>
				<td>$ex $stk[stkdes]</td>
				<td>".sprint3($stkd['qty'])."</td>
				<td>$note[currency] ".sprint($selamt)."</td>
				<td>$note[currency] $stkd[amt]</td>
			</tr>";
	}

	# Avoid little box
	if(strlen($note['comm']) > 0){
		$note['comm'] = "
			<table border='1' cellspacing='0' bordercolor='#000000'>
				<tr>
					<td>".nl2br($note['comm'])."</td>
				</tr>
			</table>";
	}

	# Vat perc
	$VATP = TAX_VAT;

	# format date
	$date = $note["odate"];
	$note['odate'] = explode("-", $note['odate']);
	$note['odate'] = $note['odate'][2]."-".$note['odate'][1]."-".$note['odate'][0];

	$cc = "";
	if(isset($cccc))
		$cc = "<script> nCostCenter('ct', 'Credit Note', '$date', 'Credit Note No.$note[notenum] for Customer $note[cusname] $note[surname]', '".(($note['total']-$note['vat']) * $note['xrate'])."', 'Credit Note No.$note[notenum]', '$tcosamt', ''); </script>";

	/* -- Final Layout -- */
	$details = "
		$cc
		<center>
		<h2>Credit Note</h2>
		<table cellpadding='0' cellspacing='4' border='0' width='750'>
			<tr>
				<td valign='top' width='30%'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td>$note[surname]</td>
						</tr>
						<tr>
							<td>".nl2br($note['cusaddr'])."</td>
						</tr>
						<tr>
							<td>(Vat No. $note[cusvatno])</td>
						</tr>
					</table>
				</td>
				<td valign='top' width='25%'>
					".COMP_NAME."<br>
					".COMP_ADDRESS."<br>
					".COMP_TEL."<br>
					".COMP_FAX."<br>
					Reg No. ".COMP_REGNO."<br>
	                VAT No. ".COMP_VATNO."<br>
				</td>
				<td width='20%'><img src='compinfo/getimg.php' width='230' height='47'></td>
				<td valign='bottom' align='right' width='25%'>
					<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
						<tr>
							<td><b>Credit Note No.</b></td>
							<td valign='center'>$note[notenum]</td>
						</tr>
						<tr>
							<td><b>Invoice No.</b></td>
							<td valign='center'>$note[invnum]</td>
						</tr>
						<tr>
							<td><b>Order No.</b></td>
							<td valign='center'>$note[ordno]</td>
						</tr>
						<tr>
							<td><b>Terms</b></td>
							<td valign='center'>$note[terms] Days</td>
						</tr>
						<tr>
							<td><b>Credit note Date</b></td>
							<td valign='center'>$note[odate]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='4'>
					<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
						<tr>
							<td><b>ITEM NUMBER</b></td>
							<td width='45%'><b>DESCRIPTION</b></td>
							<td><b>QTY RETURNED</b></td>
							<td><b>UNIT PRICE</b></td>
							<td><b>AMOUNT</b></td>
						<tr>
						$products
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table border='0' cellspacing='0' bordercolor='#000000'>
						<tr>
							<td>".nl2br($note['comm'])."</td>
						</tr>
					</table>
				</td>
				<td align='right' colspan='3'>
					<table cellpadding='5' cellspacing='0' border='1' width='50%' bordercolor='#000000'>
						<tr>
							<td><b>SUBTOTAL</b></td>
							<td align='right'>$note[currency] $note[subtot]</td>
						</tr>
						<tr>
							<td><b>Trade Discount</b></td>
							<td align='right'>$note[currency] $note[traddisc]</td>
						</tr>
						<tr>
							<td><b>Delivery Charge</b></td>
							<td align='right'>$note[currency] $note[delchrg]</td>
						</tr>
						<tr>
							<td><b>VAT @ $VATP%</b></td>
							<td align='right'>$note[currency] $note[vat]</td>
						</tr>
						<tr>
							<td><b>GRAND TOTAL<b></td>
							<td align='right'>$note[currency] $note[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table cellpadding='2' cellspacing='0' border='1'>
						<tr>
							<td colspan=2>VAT Exempt indicator = #</td>
						</tr>
					</table>
				</td>
				<td><br></td>
			</tr>
			<tr><td><br></td></tr>
		</table>
		</center>";
	$OUTPUT = $details;
	require("tmpl-print.php");

}


?>
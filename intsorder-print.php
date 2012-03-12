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


	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM sorders WHERE sordid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);


	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_connect();

	$sql = "SELECT stkid FROM sorders_items WHERE sordid = '$inv[sordid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Consignment number <b>$invid</b> has no items.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM sorders_items  WHERE sordid = '$invid' AND div = '".USER_DIV."'";
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

		# Keep track of discounts
		$disc += $stkd['disc'];

		# Put in product
		$products .= "
			<tr valign=top>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>".sprint3($stkd['qty'])."</td>
				<td>".sprint($stkd['unitcost'])."</td>
				<td>$inv[currency] $stkd[amt]</td>
			</tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($inv['subtot']);

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "
			<table border='1' cellspacing='0' bordercolor='#000000'>
				<tr>
					<td>".nl2br($inv['comm'])."</td>
				</tr>
			</table>";
	}
	
	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}


	/* -- Final Layout -- */
	$details = "
		<center>
		<h2>Sales Order</h2>
		<table cellpadding='0' cellspacing='4' border='0' width='770'>
			<tr>
				<td valign='top' width='30%'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td>$inv[surname]</td>
						</tr>
						<tr>
							<td>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr>
							<td>(Vat No. $inv[cusvatno])</td>
						</tr>
					</table>
				</td>
				<td valign='top' width='30%'>
					".COMP_NAME."<br>
					".COMP_ADDRESS."<br>
					".COMP_TEL."<br>
					".COMP_FAX."<br>
					Reg No. ".COMP_REGNO."<br>
	                VAT No. ".COMP_VATNO."<br>
				</td>
				<td align='left' width='20%'><img src='compinfo/getimg.php' width='230' height='47'></td>
				<td valign='bottom' align='right' width='20%'>
					<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
						<tr>
							<td><b>Sales Order No.</b></td>
							<td valign='center'>$inv[sordid]</td></tr>
						<tr>
							<td><b>Order No.</b></td>
							<td valign='center'>$inv[ordno]</td></tr>
						<tr>
							<td><b>Terms</b></td>
							<td valign='center'>$inv[terms] Days</td></tr>
						<tr>
							<td><b>Date</b></td>
							<td valign='center'>$inv[odate]</td></tr>
						<tr>
							<td><b>Vat</b></td>
							<td valign='center'>$inv[chrgvat]</td></tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='4'>
					<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
						<tr>
							<th>ITEM NUMBER</th>
							<th width='45%'>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>AMOUNT</th>
						<tr>
						$products
					</table>
				</td>
			</tr>
			<tr>
				<td>$inv[comm]</td>
				<td align='right' colspan='3'>
					<table cellpadding='5' cellspacing='0' border='1' width='50%' bordercolor='#000000'>
						<tr>
							<td><b>SUBTOTAL</b></td>
							<td align='right'>$inv[currency] $SUBTOT</td>
						</tr>
						<tr>
							<td><b>Trade Discount</b></td>
							<td align='right'>$inv[currency] $inv[discount]</td>
						</tr>
						<tr>
							<td><b>Delivery Charge</b></td>
							<td align='right'>$inv[currency] $inv[delivery]</td>
						</tr>
						<tr>
							<td><b>VAT @ $VATP%</b></td>
							<td align='right'>$inv[currency] $VAT</td>
						</tr>
						<tr>
							<th><b>GRAND TOTAL<b></th>
							<td align='right'>$inv[currency] $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
		</table>
		</center>";
	$OUTPUT = $details;
	require("tmpl-print.php");

}


?>
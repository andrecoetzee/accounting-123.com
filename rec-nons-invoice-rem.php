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
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
}  elseif(isset($_POST["id"])) {
	$OUTPUT = remove($_POST);
}else {
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
	$v->isOk ($invid, "num", 1, 20, "Invalid purchase number.");

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

	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM rnons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='5%'>#</th>
				<th width='65%'>DESCRIPTION</th>
				<th width='10%'>QTY</th>
				<th width='10%'>UNIT PRICE</th>
				<th width='10%'>AMOUNT</th>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	$sql = "SELECT * FROM rnons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		# put in product
		$products .="
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$i</td>
				<td>$stkd[description]</td>
				<td>$stkd[qty]</td>
				<td>$stkd[unitcost]</td>
				<td>".CUR." $stkd[amt]</td>
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
	$details = "
		<center>
		<h3>Delete Recurring Non-Stock Invoice</h3>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Customer Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$inv[cusname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Address</td>
							<td valign='center'><pre>$inv[cusaddr]</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Vat Number</td>
							<td valign='center'>$inv[cusvatno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>$inv[cordno]</td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Invoice Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Invoice No.</td>
							<td valign='center'>$inv[invnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Proforma Inv No.</td>
							<td valign='center'>$inv[docref]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$sday-$smon-$syear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Inclusive</td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$inv[terms] Days</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'>$products</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='40%'>Quick Links</th>
							<th width='45%'>Remarks</th>
							<td rowspan='5' valign='top' width='15%'><br></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='rec-nons-invoice-new.php'>New Recurring Non-Stock Invoices</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='rec-nons-invoice-view.php'>View Recurring Non-Stock Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>".CUR." $inv[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT @ ".TAX_VAT." %</td>
							<td align='right'>".CUR." $inv[vat]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>".CUR." $inv[total]</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='id' value='$invid'>
			<input type='submit' value='Delete &raquo;'>
		</form>
		</center>";
	return $details;

}




function remove($_POST)
{

	extract($_POST);

	$id += 0;

	db_connect();

	$sql = "SELECT * FROM rnons_invoices WHERE invid = '$id' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoices information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}

	$Sl = "DELETE FROM rnons_invoices WHERE invid='$id'";
	$Ri = db_exec($Sl);

	$Sl = "DELETE FROM rnons_inv_items WHERE invid='$id'";
	$Ri = db_exec($Sl);

	return "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Invoice deleted</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Non-stock recurring invoice deleted.</td>
			</tr>
		</table><p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th >Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-nons-invoice-new.php'>New Recurring Non-Stock Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-nons-invoice-view.php'>View Recurring Non-Stock Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

}



?>
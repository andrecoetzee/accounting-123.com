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
} else {
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "accept":
				$OUTPUT = accept($_POST);
				break;
			default:
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
		}
	}else{
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# Get templete
require("template.php");




# Details
function details($_GET)
{

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Quote number.");

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



	# Get Quote info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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

	# Get selected stock in this Quote
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while($stkd = pg_fetch_array($stkdRslt)){
		$i++;

		# put in product
		$products .= "
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

	if($inv['invnum']==0) {
		$inv['invnum']=$inv['invid'];
	}

	/* -- Final Layout -- */
	$details = "
				<center>
				<h3>Non-Stock Quote Details</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='accept'>
					<input type='hidden' name='invid' value='$invid'>
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
							</table>
						</td>
						<td valign='top' align='right'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'> Non-Stock Quote Details </th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Non-Stock Quote No.</td>
									<td valign='center'>$inv[invnum]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Date</td>
									<td valign='center'>$inv[odate]</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>VAT Inclusive</td>
									<td valign='center'>$inv[chrgvat]</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr><td><br></td></tr>
					<tr><td colspan='2'>$products</td></tr>
					<tr>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th width='40%'>Quick Links</th>
									<th width='45%'>Remarks</th>
									<td rowspan='5' valign='top' width='15%'><br></td>
								</tr>
								<tr>
									<td bgcolor='".bgcolorg()."'><a href='nons-quote-new.php'>New Non-Stock Quotes</a></td>
									<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($inv['remarks'])."</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
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
					<tr>
						<td align='right'></td>
						<td><input type='submit' value='Accept'></td>
					</tr>
				</table>
				</form>
				</center>";
	return $details;

}




function accept($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid Quote number.");

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

	# Get Quote info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	db_connect();

/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$sql = "
		INSERT INTO nons_invoices (
			cusname, cusaddr, cusvatno, chrgvat, sdate, 
			odate, done, username, prd, invnum, 
			div, remarks, cusid, age, typ, 
			subtot, balance, vat, total, descrip, 
			ctyp, accid, tval, docref, jobid, 
			jobnum, labid, location, fcid, currency, 
			xrate, fbalance, fsubtot, multiline 
		) VALUES (
			'$inv[cusname]', '$inv[cusaddr]', '$inv[cusvatno]', '$inv[chrgvat]', '$inv[sdate]',
			'$inv[odate]', '$inv[done]', '$inv[username]', '$inv[prd]', '$inv[invnum]', 
			'$inv[div]', '$inv[remarks]', '$inv[cusid]', '$inv[age]', 'inv', 
			'$inv[subtot]', '$inv[balance]', '$inv[vat]', '$inv[total]', '$inv[descrip]', 
			'$inv[ctyp]', '$inv[accid]', '$inv[tval]', '$inv[docref]', '$inv[jobid]', 
			'$inv[jobnum]', '$inv[labid]', '$inv[location]', '$inv[fcid]', '$inv[currency]', 
			'$inv[xrate]', '$inv[fbalance]', '$inv[fsubtot]', 'yes'
		)";
	$upRslt = db_exec ($sql) or errDie ("Unable to update quote information");

	# get next ordnum
	$ninvid = lastinvid();

	# Get selected stock in this Quote
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){
		$stkd['cunitcost'] += 0;
		$sql = "
			INSERT INTO nons_inv_items (
				invid, qty, description, div, amt, 
				unitcost, accid, rqty, vatex, cunitcost
			) VALUES (
				'$ninvid', '$stkd[qty]', '$stkd[description]', '$stkd[div]', '$stkd[amt]', 
				'$stkd[unitcost]', '$stkd[accid]', '$stkd[rqty]', '$stkd[vatex]', '$stkd[cunitcost]'
			)";
		$upRslt = db_exec ($sql) or errDie ("Unable to update quote information");
	}

	# Set to not serialised
	$sql = "UPDATE nons_invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);


	/* - End Copying - */
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if (isset($inv['multiline']) AND ($inv['multiline'] == "yes"))
		header("Location: nons-multiline-invoice-new.php?invid=$ninvid&cont=1");
	else 
		header("Location: nons-invoice-new.php?invid=$ninvid&cont=1");
	exit;

	# Final Laytout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Non-Stock Quotes accepted</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Non-Stock Quotes for Customer <b>$inv[cusname]</b> has been accepted.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-quote-view.php'>View Non-Stock Quotes</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
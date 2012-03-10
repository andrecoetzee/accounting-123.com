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
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
            case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;

            default:
				$OUTPUT = "<li class='err'> Ivalid use of module.</li>";
			}
	} else {
		$OUTPUT = "<li class='err'> Ivalid use of module.</li>";
	}
}

# get templete
require("template.php");

# details
function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}


/* --- Start Drop Downs --- */


	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
						<tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$stkd[cod]</td>
							<td>$stkd[des]</td>
							<td>$stkd[qty]</td>
							<td nowrap>".CUR." $stkd[unitcost]</td>
							<td>$sday-$smon-$syear</td>
							<td nowrap>".CUR." $stkd[amt]</td>
						</tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $pur['subtot'];

	# Get Total
	$TOTAL = $pur['total'];

	# Get vat
	$VAT = $pur['vat'];

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Non-Stock Order Cancel</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='purid' value='$purid'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Account number</td>
										<td valign='center'><pre>$pur[supaddr]</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Non-Stock Order Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Non-Stock Order No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Ref No.</td>
										<td valign='center'>$pur[refno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$pur[vatinc]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charges</td>
										<td valign='center' nowrap>".CUR." $pur[shipchrg]</td>
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
										<td bgcolor='".bgcolorg()."'><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
										<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align=right>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $pur[subtot]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charges</td>
										<td align='right' nowrap>".CUR." $pur[shipchrg]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right' nowrap>".CUR." $pur[vat]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>".CUR." $pur[total]</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input type='submit' name='upBtn' value='Write'></td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}


# details
function write($HTTP_POST_VARS)
{

	#get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($HTTP_POST_VARS, $err);
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Insert Order to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# remove items
		$sql = "DELETE FROM nons_pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		# remove Order
		$sql = "DELETE FROM nons_purchases WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

		# Insert record
		$sql = "INSERT INTO cancelled_purch(purnum, pdate, username, div) VALUES('$pur[purnum]', '$pur[pdate]', '".USER_NAME."', '$pur[div]')";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Non-Stock Order Cancel</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Non-Stock Order from Supplier <b>$pur[supplier]</b> has been cancelled.</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='nons-purchase-view.php'>View Orders</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";
	return $write;

}


?>
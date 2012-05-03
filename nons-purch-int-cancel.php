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
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "update":
				$OUTPUT = write($_POST);
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
function details($_POST, $error="")
{


	# Get vars
	extract ($_POST);

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
	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th colspan='2'>UNIT PRICE</th>
							<th colspan='2'>DUTY</th>
							<th>LINE TOTAL</th>
						<tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT * FROM nons_purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){
		# put in product
		$products .= "
						<tr class='".bg_class()."'>
							<td>$stkd[cod]</td>
							<td>$stkd[des]</td>
							<td>$stkd[qty]</td>
							<td nowrap>$pur[curr] $stkd[cunitcost] or </td>
							<td nowrap>".CUR." $stkd[unitcost]</td>
							<td nowrap>$pur[curr] $stkd[duty] or </td>
							<td>$stkd[dutyp]%</td>
							<td nowrap>$pur[curr] $stkd[amt]</td>
						</tr>";
		//$products .="<tr class='bg-odd'><td>$stkd[cod]</td><td>$stkd[des]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>$dday-$dmon-$dyear</td><td>$pur[curr] $stkd[amt]</td></tr>";
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

	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip",
	"No currencies were found in cubit, please add a currency first.", null);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>International Non-Stock Order Cancel</h3>
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
									<tr class='".bg_class()."'>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier Address</td>
										<td valign='center'><pre>$pur[supaddr]</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Non-Stock Order Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Non-Stock Order No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Order No.</td>
										<td valign='center'>$pur[ordernum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Foreign Currency</td>
										<td valign='center'>$currsel &nbsp;&nbsp;Exchange rate $pur[curr] $pur[xrate]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Shipping Charges</td>
										<td valign='center'>$pur[curr] $pur[shipchrg]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Date</td>
										<td valign='center'>$dday-$dmon-$dyear</td>
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
										<td class='".bg_class()."'><a href='nons-purch-int-new.php'>New International Non-Stock Order</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>$pur[curr] $pur[subtot]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td align='right'>$pur[curr] $pur[shipping]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax </td>
										<td align='right'>$pur[curr] $pur[tax]</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>$pur[curr] $pur[total]</td>
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
function write($_POST)
{

	#get vars
	extract ($_POST);

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
		return details($_POST, $err);
	}



	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Insert Order to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Remove items
		$sql = "DELETE FROM nons_purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		# Remove Order
		$sql = "DELETE FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
				<table ".TMPL_tblDflts.">
					<tr>
						<th>International Non-Stock Order Cancel</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>International Non-Stock Order from Supplier <b>$pur[supplier]</b> has been cancelled.</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $write;

}


?>
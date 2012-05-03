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
if (isset($_GET["purid"])  && isset($_GET["prd"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "update":
				$OUTPUT = write($_POST);
				break;

            default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
		}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");

# details
function details($_POST, $error="")
{

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	# Get purchase info
	db_conn($prd);
	$sql = "SELECT *, (shipchrg - rshipchrg) as shipchrg, (fshipchrg - rfshipchrg) as fshipchrg FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.</li>";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# Format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>STORE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<TH>SERIAL NO.</TH>
							<th>QTY RETURNED</th>
							<th colspan='2'>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
						<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT *,tqty as qty FROM purint_items  WHERE purid = '$purid' AND tqty > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$key = 0;
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

		// $stkd['amt'] = ($stkd['unitcost'] * $stkd['qty']);

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		if($stk['serd'] == 'yes'){
			$sers = ext_getPurSerStk($pur['purnum'], $stkd['stkid']);
			for($j = 0; $j < $stkd['qty']; $j++){
				//$serial = $sers[$j]['serno'];
				$serial="";
				$products .= "
								<tr class='".bg_class()."'>
									<td>$wh[whname]</td>
									<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
									<td>$stk[stkdes]</td>
									<td align='center'>$serial</td>
									<td>1</td>
									<td nowrap>".CUR." $stkd[unitcost]</td>
									<td nowrap>$pur[curr] $stkd[cunitcost]</td>
									<td>$sday-$smon-$syear</td>
									<td nowrap>$pur[curr] $stkd[cunitcost]</td>
								</tr>";
				$key++;
			}
		}else{
			$products .= "
							<tr class='".bg_class()."'>
								<td>$wh[whname]</td>
								<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
								<td>$stk[stkdes]</td>
								<td><br></td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td nowrap>$pur[curr] $stkd[cunitcost]</td>
								<td>$sday-$smon-$syear</td>
								<td nowrap>$pur[curr] $stkd[amt]</td>
							</tr>";
			$key++;
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	$total = sprint($pur['rsubtot'] + $pur['rtax']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Stock Return</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='purid' value='$purid'>
						<input type='hidden' name='prd' value='$prd'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'>Supplier Details</th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier</td>
										<td valign='center'>$sup[supname]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Account number</td>
										<td valign='center'>$sup[supno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($supaddr)."</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'>Purchase Details</th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>".mkDateSelect("p",$p_year,$p_month,$p_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Foreign Currency</td>
										<td valign='center'>$pur[curr] &nbsp;&nbsp;Exchange rate ".CUR." $pur[xrate]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $pur[rtax]</td>
									</tr>
									<!--<tr class='".bg_class()."'>
										<td>Shipping Charges</td>
										<td valign='center' nowrap>$pur[curr] $pur[rfshipchrg]</td>
									</tr>-->
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2'>$products</td>
						</tr>
						<tr>
							<td>
								<p>
								<table ".TMPL_tblDflts.">
									<tr>
										<th width='25%'>Quick Links</th>
										<th width='25%'>Remarks</th>
										<td rowspan='5' valign='top' width='50%'>$error</td>
									</tr>
									<tr>
										<td class='".bg_class()."'><a href='purch-int-view.php'>View International Orders</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>$pur[curr] <input type='text' name='subtot' size='10' value='$pur[rsubtot]'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td align='right' nowrap>$pur[curr] <input type='text' name='tax' size='10' value='$pur[rtax]'></td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>$pur[curr] <input type='text' name='total' size='10' value='$total'></td>
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


# confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	// $v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }
	$v->isOk ($subtot, "float", 1, 20, "Invalid Subtotal.");
	$v->isOk ($tax, "float", 0, 20, "Invalid Tax.");
	$v->isOk ($total, "float", 1, 20, "Invalid total.");

	# Used to generate errors
	$error = "asa@";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}



	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "n"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.</li>";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

	# Format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>STORE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<TH>SERIAL NO.</TH>
							<th>QTY RETURNED</th>
							<th colspan=2>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
						<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT *,tqty as qty FROM purint_items  WHERE purid = '$purid' AND tqty > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$key = 0;
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

		// $stkd['amt'] = ($stkd['unitcost'] * $stkd['qty']);

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		if($stk['serd'] == 'yes'){
			$sers = ext_getPurSerStk($pur['purnum'], $stkd['stkid']);
			for($j = 0; $j < $stkd['qty']; $j++){
				$serial="";
				//$serial = $sers[$j]['serno'];
				$products .= "
								<tr class='".bg_class()."'>
									<td>$wh[whname]</td>
									<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
									<td>$stk[stkdes]</td>
									<td align='center'>$serial</td>
									<td>1</td>
									<td nowrap>".CUR." $stkd[unitcost]</td>
									<td nowrap>$pur[curr] $stkd[cunitcost]</td>
									<td>$sday-$smon-$syear</td>
									<td nowrap>$pur[curr] $stkd[cunitcost]</td>
								</tr>";
				$key++;
			}
		}else{
			$products .= "
							<tr class='".bg_class()."'>
								<td>$wh[whname]</td>
								<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
								<td>$stk[stkdes]</td>
								<td><br></td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td nowrap>$pur[curr] $stkd[cunitcost]</td>
								<td>$sday-$smon-$syear</td>
								<td nowrap>$pur[curr] $stkd[amt]</td>
							</tr>";
			$key++;
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */
		$subtot = sprint($subtot);
		$tax = sprint($tax);
		$total = sprint($subtot + $tax);
/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Stock Return</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='purid' value='$purid'>
						<input type='hidden' name='prd' value='$prd'>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td>
									</tr>
						   			<tr class='".bg_class()."'>
						   				<td>Supplier</td>
						   				<td valign='center'>$sup[supname]</td>
						   			</tr>
									<tr class='".bg_class()."'>
										<td>Account number</td>
										<td valign='center'>$sup[supno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($supaddr)."</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Purchase Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>".mkDateSelect("p",$p_year,$p_month,$p_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Foreign Currency</td>
										<td valign='center'>$pur[curr] &nbsp;&nbsp;Exchange rate ".CUR." $pur[xrate]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td valign='center'>$pur[curr] $tax</td>
									</tr>
								<!--<tr class='".bg_class()."'>
										<td>Shipping Charges</td>
										<td valign='center' nowrap>$pur[curr] $pur[fshipchrg]</td>
									</tr>-->
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2'>$products</td>
						</tr>
						<tr>
							<td>
								<p>
								<table ".TMPL_tblDflts.">
									<tr>
										<th width='25%'>Quick Links</th>
										<th width='25%'>Remarks</th>
										<td rowspan='5' valign='top' width='50%'></td>
									</tr>
									<tr>
										<td class='".bg_class()."'><a href='purch-int-view.php'>View International Orders</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align=right>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>$pur[curr] <input type='hidden' name='subtot' size='10' value='$subtot'>$subtot</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Tax</td>
										<td align='right' nowrap>$pur[curr] <input type='hidden' name='tax' size='10' value='$tax'>$tax</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>$pur[curr] <input type='hidden' name='total' size='10' value='$total'>$total</td>
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

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	// $v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }
	$v->isOk ($subtot, "float", 1, 20, "Invalid Subtotal.");
	$v->isOk ($tax, "float", 0, 20, "Invalid Tax.");
	$v->isOk ($total, "float", 1, 20, "Invalid total.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}

	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	$sup = pg_fetch_array($supRslt);

	# Get department info
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Insert purchase to DB
	db_connect();

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Get warehouse name
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# Update purchase on the DB
		db_conn($prd);
		$sql = "UPDATE purch_int SET noted = 'y', rsubtot = 0, rtax = 0, remarks = '$remarks' WHERE purid = '$purid'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

	/* - Start Hooks - */
		$refnum = getrefnum();
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");
	/* - End Hooks - */

		$retot = sprint($subtot + $tax);
		$sdate = date("Y-m-d");
		$lsubtot = sprint($subtot * $pur['xrate']);
		$ltax = sprint($tax * $pur['xrate']);
		$lretot = sprint($retot * $pur['xrate']);

		# Debit Supplier control, credit inv control
		//date("d-m-Y")
		writetrans($dept['credacc'], $vatacc,$pdate, $refnum, $ltax, "Credit Note for Vat return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		writetrans($dept['credacc'], $wh['conacc'], $pdate, $refnum, $lsubtot, "Credit Note for Stock return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");

		db_connect();
		# update the supplier (make balance less)
		$sql = "UPDATE suppliers SET fbalance = (fbalance - '$retot'), balance = (balance - '$lretot') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# update the supplier age analysis (make balance less)
		if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
			# Found? Make amount less
			$sql = "UPDATE suppurch SET fbalance = (fbalance - '$retot'), balance = (balance - '$lretot') WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		}else{
			/* Make transaction record for age analysis */
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fbalance, div) VALUES('$pur[supid]', '$pur[purnum]', '$sdate', '-$lretot', '-$retot', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}

		$Date = date("Y-m-d");

		$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, amount, descript, ref, ex, div) VALUES('$pur[supid]','$Date', '$dept[credacc]', '-$retot', 'Stock Returned', '$refnum', '$pur[purnum]', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Ledger Records
		suppledger($pur['supid'], $wh['stkacc'], $Date, $pur['purid'], "Stock Purchase No. $pur[purnum] returned.", $lretot, 'd');

	/*-- Cost varience -- */
	//date("d-m-Y")
		if($pur['rsubtot'] > $subtot){
			$diff = sprint($pur['rsubtot'] - $subtot);
			# Debit Stock Control and Credit Creditors control
			writetrans($cvacc, $wh['conacc'], $pdate, $refnum, $diff, "Cost Variance for Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}elseif($subtot > $pur['rsubtot']){
			$diff = sprint($subtot - $pur['rsubtot']);
			# Debit Stock Control and Credit Creditors control
			writetrans($wh['conacc'], $cvacc, $pdate, $refnum, $diff, "Cost Variance for Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}
	/*-- End Cost varience -- */

	/* End Transactions */

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Stock Return</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Stock Return to Supplier <b>$sup[supname]</b> has been recorded.</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='purchase-view.php'>View purchases</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $write;

}

?>
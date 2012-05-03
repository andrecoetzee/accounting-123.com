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
			<th>SERIAL NO.</th>
			<th>QTY RETURNED</th>
			<th colspan='2'>UNIT PRICE</th>
			<th>DELIVERY DATE</th>
			<th>AMOUNT</th>
			<th>RETURNED</th>
		<tr>";

	# get selected stock in this purchase
	db_conn($prd);
	$sql = "SELECT *,(qty - tqty) as qty FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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
				$serial = $sers[$j]['serno'];
				$products .= "
						<tr class='".bg_class()."'>
							<td>$wh[whname]</td>
							<td>
								<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
								<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
							</td>
							<td>$stk[stkdes]</td>
							<td align='center'>
								<input type='hidden' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'>$serial
							</td>
							<td>
								<input type='hidden' name='qt[$key]' value='1'>
								<input type='hidden' size='5' name='qtys[$key]' value='1'>1
							</td>
							<td nowrap>".CUR." $stkd[unitcost]</td>
							<td nowrap>$pur[curr] $stkd[cunitcost]</td>
							<td>$sday-$smon-$syear</td>
							<td nowrap>$pur[curr] $stkd[cunitcost]</td>
							<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
						</tr>";
				$key++;
			}
		}else{
			$products .="<tr class='".bg_class()."'>
				<td>$wh[whname]</td>
				<td>
					<input type='hidden' name='stkids[$key]' value='$stkd[stkid]'>
					<a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a>
				</td>
				<td>$stk[stkdes]</td>
				<td>&nbsp;</td>
				<td>
					<input type='hidden' name='qt[$key]' value='$stkd[qty]'>
					<input type='text' size='5' name='qtys[$key]' value='$stkd[qty]'>
				</td>
				<td nowrap>".CUR." $stkd[unitcost]</td>
				<td nowrap>$pur[curr] $stkd[cunitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>$pur[curr] $stkd[amt]</td>
				<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
			</tr>";
			$key++;
		}
		# Put in product
		# $products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$stk[stkdes]</td><td><input type=hidden name=qt[] value='$stkd[qty]'><input type=text size=5 name=qtys[] value='$stkd[qty]'></td><td>".CUR." $stkd[unitcost]</td><td>$pur[curr] $stkd[cunitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Shipping charges
	$SHIPCHRG = sprint($pur['shipchrg']);

	# Get tax
	$TAX = sprint($pur['tax']);

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Stock Return</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='sct' value='$pur[fshipchrg]'>
			<input type='hidden' name='otax' value='$pur[tax]'>
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
							<th colspan=2> Purchase Details </th>
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
							<td valign='center'>$pur[curr] <input type='text' size='7' name='tax' value='$pur[tax]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Shipping Charges</td>
							<td valign='center' nowrap>$pur[curr] <input type='text' size='7' name='shipchrg' value='$pur[fshipchrg]'></td>
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
							<td align='right' nowrap>$pur[curr] $SUBTOT</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Shipping Charges</td>
							<td align='right' nowrap>$pur[curr] $SHIPCHRG</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Tax</td>
							<td align='right' nowrap>$pur[curr] $TAX</td>
						</tr>
						<tr class='".bg_class()."'>
							<th>GRAND TOTAL</th>
							<td align='right' nowrap>$pur[curr] $TOTAL</td>
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
	$v->isOk ($tax, "float", 0, 20, "Invalid Tax.");
	if($tax > $otax){
		$v->isOk ($tax, "float", 0, 0, "Error : Tax amount must not be more than the amount paid on receipt of items.");
	}
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Shipping Charges.");
	if($shipchrg > $sct){
		$v->isOk ($shipchrg, "float", 0, 0, "Error : Shipping Charges amount must not be more than the amount paid on receipt of items.");
	}

	$tax += 0;
	$shipchrg += 0;

	# Used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qtys[$keys] < 1){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
			if($qtys[$keys] > $qt[$keys]){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				foreach($recvd as $sk => $keys){
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) < 1){
						$v->isOk ("#", "string", 1, 20, "Error : Invalid Serial number.");
					}
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) > 0 && (ext_findSer($sernos[$keys]) == false)){
						$v->isOk ("#", "string", 1, 20, "Error : Serial <b>$sernos[$keys]</b> does not exists.");
					}
				}
			}
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : Items Not Selected.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"];
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
		$error = "<li class='err'> Error : Order number <b>$purid</b> has not been received.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class='err'> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

	# Format date
	//list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>STORE</th>
					<th>ITEM NUMBER</th>
					<th>DESCRIPTION</th>
					<th>SERIAL NO.</th>
					<th>QTY RETURNED</th>
					<th colspan='2'>UNIT PRICE</th>
					<th>DELIVERY DATE</th>
					<th>AMOUNT</th>
				<tr>";

	foreach($recvd as $sk => $keys){
		if($qtys[$keys] < 1){
			continue;
		}

		db_connect();
		# Get csprice from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		db_conn($prd);
		# Get selected stock
		$sql = "SELECT * FROM purint_items WHERE stkid = '$stkids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		# get selamt from selected stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# Calculate the unitcost
		$unitcost[$keys] = $stk['csprice'];
		$cunitcost[$keys] = ($stk['csprice']/$pur['xrate']);
		 $cunitcost[$keys] = $stkd['cunitcost'];
		 $unitcost[$keys] = $stkd['unitcost'];

		# Calculate amount
		$amt[$keys] = ($qtys[$keys] * $cunitcost[$keys]);



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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# Put in product
		if($stk['serd'] == 'yes'){
			$serial = $sers[$stkd['stkid']][$keys];
			$products .="
			<tr class='".bg_class()."'>
				<td>$wh[whname]</td>
				<td><input type='hidden' name='stkids[$keys]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td align='center'><input type='hidden' name='sers[$stkd[stkid]][$keys]' size='20' value='$serial'>$serial</td>
				<td><input type='hidden' size='5' name='qtys[$keys]' value='$qtys[$keys]'>$qtys[$keys]</td>
				<td nowrap><input type='hidden' name='unitcost[$keys]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
				<td nowrap>$pur[curr] $stkd[cunitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>$pur[curr] ".sprint($cunitcost[$keys])."<input type='hidden' name='recvd[]' value='$keys'></td>
			</tr>";
		}else{
			$products .="
			<tr class='".bg_class()."'>
				<td>$wh[whname]</td>
				<td><input type='hidden' name='stkids[$keys]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
				<td>$stk[stkdes]</td>
				<td><br></td>
				<td><input type='hidden' size='5' name=qtys[$keys] value='$qtys[$keys]'>$qtys[$keys]</td>
				<td nowrap><input type='hidden' name='unitcost[$keys]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
				<td nowrap>$pur[curr] $stkd[cunitcost]</td>
				<td>$sday-$smon-$syear</td>
				<td nowrap>$pur[curr] ".sprint($amt[$keys])."<input type='hidden' name='recvd[]' value='$keys'></td>
			</tr>";
		}
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

		# Calculate subtot
		$SUBTOT = array_sum($amt);



/* --- End Some calculations --- */
// no good reason to get the date on the confirm screen .... just pass the vars ...
/*
<input type='text' size='2' name='p_day' maxlength='2' value='$p_day'>-
<input type='text' size='2' name='p_month' maxlength='2' value='$p_month'>-
<input type='text' size='4' name='p_year' maxlength='4' value='$p_year'> DD-MM-YYYY
*/

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Stock Return</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='shipchrg' value='$shipchrg'>
			<input type='hidden' name='p_year' value='$p_year'>
			<input type='hidden' name='p_month' value='$p_month'>
			<input type='hidden' name='p_day' value='$p_day'>
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
							<td valign='center'>$p_day-$p_month-$p_year</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Foreign Currency</td>
							<td valign='center'>$pur[curr] &nbsp;&nbsp;Exchange rate ".CUR." $pur[xrate]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Tax</td>
							<td valign='center'>$pur[curr] $tax</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Shipping Charges</td>
							<td valign='center' nowrap>$pur[curr] $shipchrg</td>
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
				<td align='right'>
					<table ".TMPL_tblDflts." width='80%'>
						<tr class='".bg_class()."'>
							<th>Total Cost Returned</th>
							<td align='right' nowrap>$pur[curr] ".sprint($SUBTOT)."</td>
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

// 	print "<pre>";
// 	print_r ($_POST);
// 	print "</pre>";

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($prd, "num", 1, 20, "Invalid period Database number.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }

	# Used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qtys[$keys] < 1){
				$v->isOk ("#", "float", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				foreach($recvd as $sk => $keys){
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) < 1){
						$v->isOk ("#", "string", 1, 20, "Error : Invalid Serial number.");
					}
					if(isset($sernos[$keys]) && strlen($sernos[$keys]) > 0 && (ext_findSer($sernos[$keys]) == false)){
						$v->isOk ("#", "string", 1, 20, "Error : Serial <b>$sernos[$keys]</b> does not exists.");
					}
				}
			}
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : Items Not Selected.");
	}

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
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Insert purchase to DB
	db_connect();

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();
		$taxex = 0;
		# amount of stock in
		$totstkamt = array();
		foreach($recvd as $sk => $keys){
			if($qtys[$keys] < 1){
				continue;
			}
			# Get csprice from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			db_conn($prd);
			# Get selected stock
			$sql = "SELECT * FROM purint_items WHERE stkid = '$stkids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			# Calculate the unitcost
			$unitcost[$keys] = $stk['csprice'];
			$cunitcost[$keys] = ($stk['csprice']/$pur['xrate']);
			$cunitcost[$keys] = $stkd['cunitcost'];
			$unitcost[$keys] = $stkd['unitcost'];

			# Calculate amount
			$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
			$famt[$keys] = ($qtys[$keys] * $cunitcost[$keys]);

// 			db_conn($prd);
// 			# Get selected stock
// 			$sql = "SELECT * FROM purint_items WHERE stkid = '$stkids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
// 			$stkdRslt = db_exec($sql);
// 			$stkd = pg_fetch_array($stkdRslt);

			db_conn($prd);
			# Update order items
			$sql = "UPDATE purint_items SET tqty = (tqty + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

			# Keep records for transactions
			if(isset($totstkamt[$stk['whid']])){
				$totstkamt[$stk['whid']] += $amt[$keys];
			}else{
				$totstkamt[$stk['whid']] = $amt[$keys];
			}

			# Update stock(units - qty), csamt = (csamt - amt)
			db_connect();
			$sql = "UPDATE stock SET units = (units - '$qtys[$keys]'), csamt = (csamt - '$amt[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			if(isset($sers[$stkids[$keys]][$keys])){
				ext_OutSer($sers[$stkids[$keys]][$keys], $stkids[$keys], $sup['supname'], $pur['purnum'], "ret");

				$serial = $sers[$stkids[$keys]][$keys];

				db_connect();
				$sql = "DELETE FROM pserec WHERE purid = '$purid' AND  stkid = '$stkids[$keys]' AND serno = '$serial'";
				$rslt = db_exec($sql) or errDie("Unable to update stock serials in Cubit.",SELF);
			}

			# Get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# Just wanted to fix the xxx.xxxxxxe-x value
			if($stk['units'] > 0){
				$csprice = round(($stk['csamt']/$stk['units']), 2);
			}else{
				$csprice = 0;
			}

			# Update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			$sdate = date("Y-m-d");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $pdate, $qtys[$keys], $amt[$keys], "Stock returned to Supplier : $sup[supname] - Purchase No. $pur[purnum].");
			db_connect();
		}

	/* --- Clac --- */
		$xrate = $pur['xrate'];

		# Calculate subtot
		if(isset($famt)){
			$SUBTOT = array_sum($famt);
		}else{
			$SUBTOT = 0.00;
		}
		$tax = 0;

	/* --- End Clac --- */

		# Update purchase on the DB
		db_conn($prd);
		$sql = "UPDATE purch_int SET rsubtot = (rsubtot + '$SUBTOT'), rtax = (rtax + '$tax'), remarks = '$remarks' WHERE purid = '$pur[purid]'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

		# Insert returned purchase
		$sql = "INSERT INTO purchint_ret(purid, purnum, supname, rdate, subtot, remarks, div)
		VALUES('$purid', '$pur[purnum]', '$sup[supname]', '$pdate', '$SUBTOT', '$remarks', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

		$rpurid = pglib_lastid ("purchint_ret", "rpurid");

		# Insert returned items
		foreach($recvd as $sk => $keys){
			# Skip zeros
			if($qtys[$keys]< 1){
				continue;
			}
			db_connect();
			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			db_conn($prd);
			$sql = "INSERT INTO retpurint_items(rpurid, whid, stkid, qty, unitcost)
			VALUES('$rpurid', '$stk[whid]', '$stk[stkid]', '$qtys[$keys]', '$unitcost[$keys]')";
			$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);
		}

	/* Transactions */

		$refnum = getrefnum();
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		# Record transaction from data
		foreach($totstkamt as $whid => $wamt){
			# get whouse info
			db_conn("exten");
			$sql = "SELECT conacc,stkacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Debit Suppliers control and Credit Stock
			writetrans($wh['conacc'], $wh['stkacc'],$pdate, $refnum, $wamt, "Stock Return on Purchase No. $pur[purnum] from Supplier : $sup[supname]");
		}

	/* End Transactions */

	/* End Transactions */
	db_conn($prd);
	# check if there are any outstanding items
	$sql = "SELECT * FROM purint_items WHERE purid = '$purid' AND (qty - tqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	# if none the set to received
	if(pg_numrows($stkdRslt) < 1){
		# update surch_int(received = 'y')
		$sql = "UPDATE purch_int SET returned = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Orders in Cubit.",SELF);
	}

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

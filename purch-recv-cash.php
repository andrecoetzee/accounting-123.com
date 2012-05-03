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

# Decide what to do
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
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

# Get templete
require("template.php");




# Details
function details($_POST, $error="")
{

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

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
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class='err'> There are no Warehouses found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

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
							<th>STORE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>SERIAL NO.</th>
							<th>QTY RECEIVED</th>
							<th>UNIT PRICE</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th><th>RECEIVED</th>
						<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT *,(qty - rqty) as qty FROM pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		if($stk['serd'] == 'yes'){
			for($j = 0; $j < $stkd['qty']; $j++){
				$serial = ""; if(isset($sers[$stkd['stkid']][$key])) $serial = $sers[$stkd['stkid']][$key];
				$products .= "
								<tr class='".bg_class()."'>
									<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
									<td><input type='hidden' name='ids[$key]' value='$stkd[id]'><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
									<td>$stk[stkdes]</td>
									<td align='center'><input type='text' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'></td>
									<td><input type='hidden' size='5' name='qts[$key]' value='1'><input type='hidden' size='5' name='qtys[$key]' value='1'>1</td>
									<td><input type='hidden' size='4' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
									<td>".mkDateSelecta("d",$key,"$syear","$smon","$sday")."</td>
									<td>".CUR." $stkd[unitcost]</td>
									<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
								</tr>";
				$key++;
			}
		}else{
			$products .= "
							<tr class='".bg_class()."'>
								<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
								<td><input type='hidden' name='ids[$key]' value='$stkd[id]'><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
								<td>$stk[stkdes]</td>
								<td><br></td>
								<td><input type='hidden' size='5' name='qts[$key]' value='$stkd[qty]'><input type='text' size='5' name='qtys[$key]' value='$stkd[qty]'></td>
								<td><input type='hidden' size='4' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
								<td>".mkDateSelecta("d",$key,"$syear","$smon","$sday")."</td>
								<td>".CUR." $stkd[amt]</td>
								<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
							</tr>";
			$key++;
		}
		# put in product
		// $products .="<tr class='bg-odd'><td><input type=hidden name=whids[] value='$stkd[whid]'>$wh[whname]</td><td><input type=hidden name=ids[] value='$stkd[id]'><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$stk[stkdes]</td><td><input type=hidden size=5 name=qts[] value='$stkd[qty]'><input type=hidden size=5 name=qtys[] value='$stkd[qty]'>$stkd[qty]</td><td><input type=hidden size=4 name=unitcost[] value='$stkd[unitcost]'>$stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
		// $key++;
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Order received</h3>
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
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td>
									</tr>
						   			<tr class='".bg_class()."'>
						   				<td>Supplier</td>
						   				<td valign='center'>$pur[supname]</td>
						   			</tr>
									<tr class='".bg_class()."'>
										<td>Supplier No.</td>
										<td valign='center'>$pur[supno]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($pur['supaddr'])."</td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Order Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Ref No.</td>
										<td valign='center'><input type='text' name='refno' size='10' value=''></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>$pyear-$pmon-$pday</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$pur[vatinc]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td valign='center'>".CUR." <input type='hidden' name='shipchrg' size='10' value='$pur[shipchrg]'>$pur[shipchrg]</td>
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
										<td class='".bg_class()."'><a href='purchase-new.php'>New Order</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='purchase-view.php'>View Orders</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td align='right'>".CUR." $pur[shipping]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT @ ".TAX_VAT." %</td>
										<td align='right'>".CUR." $pur[vat]</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." $TOTAL</td>
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
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qtys[$keys] < 1){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ("#", "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");

			# Validate ddate[]
			$v->isOk ($d_day[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($d_month[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($d_year[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $d_year[$keys]."-".$d_month[$keys]."-".$d_day[$keys];
			if(!checkdate($d_month[$keys], $d_day[$keys], $d_year[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
			}
		}
		if(isset($sers)){
			foreach($sers as $stkid => $sernos){
				if(!ext_isUnique(ext_remBlnk($sernos))){
					$v->isOk ("error", "num", 1, 1, "Error : Serial numbers must be unique per Stock Item.");
				}else{
					foreach($recvd as $sk => $keys){
						if(isset($sernos[$keys]) && $v->isOk ($sernos[$keys], "string", 1, 20, "Error : Invalid Serial number.")){
							if((ext_findSer($sernos[$keys]) != false)){
								$v->isOk ("#", "string", 1, 20, "Error : Serial number already exists.");
							}
						}
					}
				}
			}
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : Items Not Selected.");
	}

	/* check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
		}
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}*/

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
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li> - Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get department info
	db_conn("exten");
	$sql = "SELECT deptname FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	core_connect();
    # Get Petty cash account
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");


	# Insert purchase to DB
	db_connect();

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# amount of stock in
		$totstkamt = array();
		$resub = 0;
		$taxex = 0;
		$revat = 0;

		# Get subtotal
		foreach($recvd as $sk => $keys){
			# Skip zeros
			if($qtys[$keys] < 1){
				continue;
			}
			$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
		}
		$SUBTOTAL = array_sum($amt);

		foreach($recvd as $sk => $keys){
			# Skip zeros
			if($qtys[$keys]< 1){
				continue;
			}

			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# Get selected stock line
			$sql = "SELECT * FROM pur_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			# Calculate cost amount bought
			$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

			/* delivery charge */

				# Calculate percentage from subtotal
				$perc[$keys] = (($amt[$keys]/$SUBTOTAL) * 100);

				# Get percentage from shipping charges
				$shipc[$keys] = (($perc[$keys] / 100) * $shipchrg);

				# Add delivery charges
				$amt[$keys] += $shipc[$keys];

			/* end delivery charge */

			# the subtotal + delivery charges
			$resub += $amt[$keys];

			# Check Tax Excempt
			if($stk['exvat'] == 'yes'){
				# how much is not vatable?
				$taxex += ($amt[$keys]);
			}else{
				# Line vat
				$svat[$keys] = svat($amt[$keys], $stkd['amt'], $stkd['svat']);

				# received vat
				$revat += $svat[$keys];

				# make amount vat free
				if($pur['vatinc'] == "yes"){
					$amt[$keys] = ($amt[$keys] - $svat[$keys]);
				}
			}

			# Update purchase items
			$sql = "UPDATE pur_items SET rqty = (rqty + '$qtys[$keys]'), ddate = '$ddate[$keys]' WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

			# Update stock(ordered + qty, units + qty, csamt + (csamt + amt))
			$sql = "UPDATE stock SET ordered = (ordered - '$qtys[$keys]'), units = (units + '$qtys[$keys]'), csamt = (csamt + '$amt[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			if(isset($sers[$stkids[$keys]][$keys])){
				ext_InSer($sers[$stkids[$keys]][$keys], $stkids[$keys], $pur['supname'], $pur['purnum'], "pur",$pur['pdate']);

				$serial = $sers[$stkids[$keys]][$keys];

				db_connect();
				$sql = "INSERT INTO pserec(purid, purnum, stkid, serno, div)
				VALUES('$purid', '$pur[purnum]', '$stkids[$keys]', '$serial', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to update stock serials in Cubit.",SELF);
			}

			# Keep records for transactions
			if(isset($totstkamt[$stk['whid']])){
				$totstkamt[$stk['whid']] += $amt[$keys];
			}else{
				$totstkamt[$stk['whid']] = $amt[$keys];
			}


			# Get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			$sdate = date("Y-m-d");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $sdate, $qtys[$keys], $amt[$keys], "Stock Received from Supplier : $pur[supname] - Order No. $pur[purnum]");
			db_connect();
			$cspric = sprint($amt[$keys]/$qtys[$keys]);
			$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
			VALUES('$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'purchase', '$qtys[$keys]', '$amt[$keys]', '$cspric', 'Stock Received from Supplier : $pur[supname] - Order No. $pur[purnum]', '".USER_DIV."')";
			$recRslt = db_exec($sql);

			# Just wanted to fix the xxx.xxxxxxe-x value
			if($stk['units'] > 0){
				$csprice = round(($stk['csamt']/$stk['units']), 2);
			}else{
				$csprice = round($stk['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice', lcsprice = '$cspric' WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# check if there are any outstanding items
			$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			# if none the set to received
			if(pg_numrows($stkdRslt) < 1){
				# update surch_int(received = 'y')
				$sql = "UPDATE purchases SET received = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update Orders in Cubit.",SELF);
			}
		}

		if(strlen($refno) > 0){
			if(strlen($pur['refno']) > 0)
				$refno = "$pur[refno]-$refno";
			else
				$refno = $refno;
		}else{
			$refno = $pur['refno'];
		}

		# Update purchase on the DB
		if($pur['part'] == 'y'){
			$sql = "UPDATE purchases SET shipchrg = (shipchrg + '$shipchrg'), refno = '$refno', remarks = '$remarks', edit = 1 WHERE purid = '$purid'";
		}else{
			$sql = "UPDATE purchases SET shipchrg = '$shipchrg', part = 'y', refno = '$refno', remarks = '$remarks', edit = 1 WHERE purid = '$purid'";
		}
		$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	/* Transactions */

	$refnum = getrefnum(date("d-m-Y"));

	/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	/* - End Hooks - */
			$tpp = 0;
			# Record transaction  from data
			foreach($totstkamt as $whid => $wamt){
				# Get whouse info
				db_conn("exten");
				$sql = "SELECT stkacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Debit Stock and Petty Cash acc
				writetrans($wh['stkacc'], $cashacc, date("d-m-Y"), $refnum, $wamt, "Stock Received for Purchase No. $pur[purid] from Supplier : $pur[supname].");
			}

			# Calc Vat amount on (subtot + delchrg)
			# $vatamt = vat(($resub - $taxex), $pur['vatinc']);
			$vatamt = $revat;

			# Add vat if not included
			if($pur['vatinc'] == 'no'){
				$retot = ($resub + $vatamt);
			}else{
				$retot = ($resub);
			}

			# Transfer vat
			writetrans($vatacc, $cashacc, date("d-m-Y"), $refnum, $vatamt, "Vat Paid for Purchase No. $pur[purid] from Supplier : $pur[supname].");

			db_connect();
			# Record tranfer for patty cash report
			$sdate = date("Y-m-d");
			$sql = "INSERT INTO pettyrec(date, type, det, amount, name, div) VALUES ('$sdate', 'Req', 'Cash Payment for Stock Received on Purchase No. $pur[purid] from Supplier : $pur[supname].', '-$retot', 'Petty Cash Purchase', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	/* Start moving if purchase received */

		# Get purchase info
		db_connect();
		$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li> - Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y"){
			# copy purchase
			db_conn($pur['prd']);
			$sql = "INSERT INTO purchases(purid, deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, refno, received, done, div, purnum)";
			$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]')";
			$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert purchase items
				db_conn($pur['prd']);
				$sql = "INSERT INTO pur_items(purid, whid, stkid, qty, rqty, unitcost, amt, ddate, div) VALUES('$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[ddate]', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();
			# Remove the purchase from running DB
			$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Order information in Cubit.",SELF);

			# Record where purchase is
			$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int Order information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update Order information in Cubit.",SELF);
		}

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* End moving purchase received */

	// Final Layout
	$write = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Order received</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Order receipt from Supplier <b>$pur[supname]</b> has been recorded.</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='purchase-view.php'>View Orders</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}



function svat($amt, $samt, $svat){
	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;
}


?>
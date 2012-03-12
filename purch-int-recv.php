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
	
	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# Display errors, if any
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
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# Check if Order has been printed
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

	# Days drop downs
	$days = array("0"=>"0", "30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# currency drop downs
	$currs = array("R"=>"Rand","USD"=>"US Dollar","EU"=>"Euro","UKP"=>"UK Pound");
	$currsel = extlib_cpsel("curr", $currs, $pur['curr']);

	# Format dates
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($d_year, $d_month, $d_day) = explode("-", $pur['ddate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th>WAREHOUSE</th>
					<th>ITEM NUMBER</th>
					<th>DESCRIPTION</th>
					<th>SERIAL NO.</th>
					<th>QTY</th>
					<th>UNIT PRICE</th>
					<th>DUTY</th>
					<th>LINE TOTAL</th>
					<th>COST PER UNIT</th>
					<th>RECEIVED</th>
				<tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT *,(qty - rqty) as qty FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
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

	/* -- Calculations -- */

		# Calculate cost amount bought
		$totamt = ($stkd['qty'] * $stkd['cunitcost']);

		# Calculate percentage from subtotal
		$perc = ((($totamt+$stkd['duty'])/$pur['subtot']) * 100);

		# Get percentage from shipping charges
		$shipchrg = (($perc / 100) * $pur['shipchrg']);

		# add shipping charges to amt
		$totamt = round(($totamt + $shipchrg+$stkd['duty']), 2);

		#check for nasty zero
		if($stkd['qty'] != 0){
			$totunit = sprint($totamt/$stkd['qty']);
		}else {
			$totunit = 0;
		}

	/* -- End Calculations --*/

		# put in product
		# put in product
		if($stk['serd'] == 'yes'){
			$stkd['duty'] = sprint($stkd['duty']/$stkd['qty']);
			for($j = 0; $j < $stkd['qty']; $j++){
				$serial = "";
				if(isset($sers[$stkd['stkid']][$key])) $serial = $sers[$stkd['stkid']][$key];
				$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
							<td><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
							<td>$stk[stkdes]</td>
							<td align='center'><input type='text' name='sers[$stkd[stkid]][$key]' size='20' value='$serial'></td>
							<td><input type='hidden' size='5' name='qts[$key]' value='1'><input type='hidden' size='5' name='qtys[$key]' value='1'>1</td>
							<td>$pur[curr] <input type='hidden' size='8' name='cunitcost[$key]' value='$stkd[cunitcost]'>$stkd[cunitcost] &nbsp;&nbsp;or &nbsp;&nbsp;$pur[curr] <input type='hidden' size='8' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
							<td>$pur[curr] <input type='hidden' size='7' name='duty[$key]' value='$stkd[duty]'>$stkd[duty]&nbsp;&nbsp; or &nbsp;&nbsp;<input type='hidden' size='7' name='dutyp[$key]' value='$stkd[dutyp]'>$stkd[dutyp]%</td>
							<td nowrap>$pur[curr] $stkd[cunitcost]</td>
							<td align='right' nowrap>$pur[curr] $totunit</td>
							<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
						</tr>";
				$key++;
			}
		}else{
			
			#check for nasty zero
			if($stkd['qty'] != 0){
				$uc = sprint($stkd['amt']/$stkd['qty']);
			}else {
				$uc = 0;
			}
			
			$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whids[$key]' value='$stkd[whid]'>$wh[whname]</td>
						<td><input type='hidden' name='stkids[$key]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>$stk[stkdes]</td>
						<td><br></td>
						<td><input type='hidden' size='5' name='qts[$key]' value='$stkd[qty]'><input type='text' size='5' name='qtys[$key]' value='$stkd[qty]'></td>
						<td>$pur[curr] <input type='hidden' size='8' name='cunitcost[$key]' value='$stkd[cunitcost]'>$stkd[cunitcost] &nbsp;&nbsp;or &nbsp;&nbsp;<input type='hidden' size='8' name='unitcost[$key]' value='$stkd[unitcost]'>".CUR." $stkd[unitcost]</td>
						<td>$pur[curr] <input type='hidden' size='7' name='duty[$key]' value='$stkd[duty]'>$stkd[duty]&nbsp;&nbsp; or &nbsp;&nbsp;<input type='hidden' size='7' name='dutyp[$key]' value='$stkd[dutyp]'>$stkd[dutyp]%</td>
						<td nowrap>$pur[curr] $stkd[amt]</td>
						<td align='right' nowrap>$pur[curr] $uc</td>
						<td><input type='checkbox' name='recvd[]' value='$key' checked='yes'></td>
					</tr>";
			$key++;
		}
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);


/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "
			<center>
			<h3>New International Order receive</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='update'>
				<input type='hidden' name='purid' value='$purid'>
			<table ".TMPL_tblDflts." width='95%'>
				<tr>
					<td valign=top>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Supplier Details </th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Department</td>
								<td valign='center'>$dept[deptname]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Supplier</td>
								<td valign='center'>$sup[supname]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Account number</td>
								<td valign='center'>$sup[supno]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td valign='top'>Supplier Address</td>
								<td valign='center'>".nl2br($supaddr)."</td>
							</tr>
						</table>
					</td>
					<td valign='top' align='right'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'> Order Details </th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Order No.</td>
								<td valign='center'>$pur[purnum]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Delivery Ref No.</td>
								<td valign='center'><input type='text' name='refno' size='10' value=''></td>
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
								<td>Foreign Currency</td>
								<td valign='center'>$pur[curr] &nbsp;&nbsp;Exchange rate ".CUR." $pur[xrate]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Tax</td>
								<td valign='center'>$pur[curr] $pur[tax]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Shipping Charges</td>
								<td valign='center' nowrap>$pur[curr] $pur[fshipchrg]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Delivery Date</td>
								<td valign='center'>".mkDateSelect("d",$d_year,$d_month,$d_day)."</td>
							</tr>
						</table>
					</td>
				</tr>
				".TBL_BR."
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
								<td bgcolor='".bgcolorg()."'><a href='purch-int-view.php'>View International Orders</a></td>
								<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
					</td>
					<td align='right'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr bgcolor='".bgcolorg()."'>
								<td>SUBTOTAL</td>
								<td align='right' nowrap>$pur[curr] $SUBTOT</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Shipping Charges</td>
								<td align='right' nowrap>$pur[curr] $pur[shipchrg]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Tax </td>
								<td align='right' nowrap>$pur[curr] $pur[tax]</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<th>GRAND TOTAL</th>
								<td align='right' nowrap>$pur[curr] $TOTAL</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align='right'></td>
					<td><input type='submit' name='upBtn' value='Write'></td>
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
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference number.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");

	$ddate = $d_year."-".$d_month."-".$d_day;
	if(!checkdate($d_month, $d_day, $d_year)){
    	$v->isOk ($ddate, "num", 1, 1, "Invalid Date.");
    }

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($recvd)){
		foreach($recvd as $sk => $keys){
			$v->isOk ($qtys[$keys], "float", 1, 15, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 0, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($cunitcost[$keys], "float", 0, 20, "Invalid Foreign currency Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($duty[$keys], "float", 0, 20, "Invalid Duty Charges for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($dutyp[$keys], "float", 0, 20, "Invalid Duty Charges Percentage for product number : <b>".($keys+1)."</b>.");
			if($qtys[$keys] < 1){
				$v->isOk ($qtys[$keys], "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ($qtys[$keys], "num", 0, 0, "Error : Item Quantity returned is more than the bought quantity : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkids[$keys], "num", 1, 10, "Invalid Stock number, please enter all details.");

			# Nasty Zeros
			$unitcost[$keys] += 0;
			$cunitcost[$keys] += 0;
			$duty[$keys] += 0;
			$dutyp[$keys] += 0;
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

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$_POST['done'] = "";
		return details($_POST, $err);
	}

	//print $td; exit;


	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$TD = $pur["pdate"];

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($TD) >= strtotime($blocked_date_from) AND strtotime($TD) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	$sup = pg_fetch_array($supRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_conn(PRD_DB);
		# get last ref number
		$refnum = getrefnum();

		db_connect ();

		# amount of stock in
		$totstkamt = array();
		$resub = 0;
		foreach($recvd as $sk => $keys){
			if($qtys[$keys] < 1)
				continue;

		/* -- Calculations -- */

			# Calculate cost amount bought
			$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
			$amt[$keys] += ($duty[$keys]* $pur['xrate']);

			# Calculate percentage from subtotal
			$perc[$keys] = (($amt[$keys]/($pur['subtot'] * $pur['xrate'])) * 100);



			# Get percentage from shipping charges
			$shipchrg[$keys] = (($perc[$keys] / 100) * ($pur['shipchrg'] * $pur['xrate']));

			# add shipping charges to amt
			$amt[$keys] = round(($amt[$keys] + $shipchrg[$keys]), 2);

			$resub += $amt[$keys];

		/* -- End Calculations --*/

	               # Get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);
			if($stk['units']<0) {
				$min_stock = abs($stk['units']);
				if ( $qtys[$keys] < $min_stock ) {
					$min_stock = $qtys[$keys];
				}
			} else {
				$min_stock=0;
			}

			# Update Order items
			$sql = "UPDATE purint_items SET rqty = (rqty + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

			# update stock(ordered + qty, units + qty, csamt + (csamt + amt))
			$sql = "UPDATE stock SET ordered = (ordered - '$qtys[$keys]'), units = (units + '$qtys[$keys]' +'$min_stock'), csamt = (csamt + '$amt[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			if(isset($sers[$stkids[$keys]][$keys])){
				ext_InSer($sers[$stkids[$keys]][$keys], $stkids[$keys], $sup['supname'], $pur['purnum'], "pur",$TD);

				$serial = $sers[$stkids[$keys]][$keys];

				db_connect();
				$sql = "INSERT INTO pserec(purid, purnum, stkid, serno, div)
				VALUES('$purid', '$pur[purnum]', '$stkids[$keys]', '$serial', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to update stock serials in Cubit.",SELF);
			}

			# get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			$sdate = date("Y-m-d");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $TD, $qtys[$keys], $amt[$keys], "Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]");
			db_connect();

			$cspric = sprint($amt[$keys]/$qtys[$keys]);
			$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
			VALUES('$TD', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'purchase', '$qtys[$keys]', '$amt[$keys]', '$cspric', 'Stock Received from Supplier : $sup[supname] - Order No. $pur[purnum]', '".USER_DIV."')";
			$recRslt = db_exec($sql);

			# keep records for transactions
			if(isset($totstkamt[$stk['whid']])){
				$totstkamt[$stk['whid']] += $amt[$keys];
			}else{
				$totstkamt[$stk['whid']] = $amt[$keys];
			}

			# Just wanted to fix the xxx.xxxxxxe-x value
			# $csprice = round(($stk['csamt']/$stk['units']), 2);
			if($stk['units'] > 0){
				$csprice = round(($stk['csamt']/$stk['units']), 2);
			}else{
				$csprice = round($stk['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice', lcsprice = '$cspric' WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			# check if there are any outstanding items
			$sql = "SELECT * FROM purint_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			# if none the set to received
			if(pg_numrows($stkdRslt) < 1){
				# update surch_int(received = 'y')
				$sql = "UPDATE purch_int SET received = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);
			}
		}



		if($min_stock > 0) {
			$cost = sprint($unitcost[$keys] * $min_stock);
			$td = "$d_year-$d_month-$d_day";

			db_conn("exten");
			$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);
			$stockacc = $wh['stkacc'];
			$cosacc = $wh['cosacc'];

			db_connect();
			$Sl = "UPDATE stock SET csamt = (csamt - '$cost'),units=(units-'$min_stock') WHERE stkid='$stkids[$keys]'";
			$Ri = db_exec($Sl);
			writetrans($cosacc, $stockacc,$TD, $refnum, $cost, "Cost Of Sales for stock sold before international purchase $pur[purnum]");
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $td, 0,$cost , "Cost Of Sales for stock sold before international purchase $pur[purnum]");

			db_connect();
			$Sl="INSERT INTO pcost(purnum,cost,qty,rqty,stkid) VALUES ('$pur[purnum]','$unitcost[$keys]','$min_stock','0','$stk[stkid]')";
			$Ri=db_exec($Sl);
		}

		if(strlen($refno) > 0){
			if(strlen($pur['refno']) > 0)
				$refno = "$pur[refno]-$refno";
			else
				$refno = $refno;
		}else{
			$refno = $pur['refno'];
		}

		# Update Order on the DB
		$fresub = sprint($resub/$pur['xrate']);
		$sql = "UPDATE purch_int SET rsubtot = (rsubtot + '$fresub'), rlsubtot = (rlsubtot + '$resub'), refno = '$refno', remarks = '$remarks', ddate = '$ddate' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

	/* --- Transactions --- */

		db_conn(PRD_DB);
		$refnum = getrefnum();

	/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

	/* - End Hooks - */

		# record transaction  from data
		foreach($totstkamt as $whid => $wamt){
			# Get whouse info
			db_conn("exten");
			$sql = "SELECT stkacc,conacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Debit Stock and Credit Suppliers control
			writetrans($wh['stkacc'], $wh['conacc'], $TD, $refnum, $wamt, "International Stock Order No. $pur[purnum] Received from Supplier $sup[supname].");
		}

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* Start moving if Order received and invoiced */

		# Get Order info
		db_connect();
		$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li>- Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y" && $pur['invcd'] == 'y'){

			# Copy Order
			db_conn($pur['prd']);
			$sql = "INSERT INTO purch_int(purid, deptid, supid, supaddr, terms, pdate, ddate, xrate, fcid, curr, tax, shipchrg, fshipchrg, duty, subtot, total, balance, fbalance, remarks, refno, received, done, div, purnum)";
			$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[xrate]', '$pur[fcid]', '$pur[curr]', '$pur[tax]', '$pur[shipchrg]', '$pur[fshipchrg]', '$pur[duty]', '$pur[subtot]', '$pur[total]', '0', '$pur[fbalance]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]')";
			$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

		/*-- Cost varience -- */
			$nsubtot = sprint($pur['total'] - $pur['tax']);
			$nsubtot = sprint($nsubtot * $pur['xrate']);

			if($pur['rlsubtot'] > $nsubtot){
				$diff = sprint(($pur['rlsubtot'] - $nsubtot));
				# Debit Stock Control and Credit Creditors control
				writetrans($wh['conacc'], $cvacc, $TD, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}elseif($nsubtot > $pur['rlsubtot']){
				$diff = sprint(($nsubtot - $pur['rlsubtot']));
				# Debit Stock Control and Credit Creditors control
				writetrans($cvacc, $wh['conacc'], $TD, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
			}
		/*-- End Cost varience -- */

			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert Order items
				db_conn($pur['prd']);
				$sql = "INSERT INTO purint_items(purid, whid, stkid, qty, unitcost, cunitcost, duty, dutyp, amt, ddate, recved, div) VALUES('$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[cunitcost]', '$stktc[duty]', '$stktc[dutyp]', '$stktc[amt]', '$stktc[ddate]', 'y', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();
			# Remove the Order from running DB
			$sql = "DELETE FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Record where Order is
			$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('int', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Remove those Order items from running DB
			$sql = "DELETE FROM purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

/* End moving Order received */

	// Final Layout
	$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>International Order received</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Order receipt from Supplier <b>$sup[supname]</b> has been recorded.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purch-int-view.php'>View International Orders</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $write;
	
}


?>

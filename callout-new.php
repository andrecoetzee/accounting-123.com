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
if (isset($_GET["calloutid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$OUTPUT = details($_GET);
} else if (isset($_GET["calloutid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "update":
				$OUTPUT = write($_POST);
				break;
            default:
            case "details":
				$OUTPUT = details($_POST);
				break;
			}
	} else {
		$OUTPUT = details($_POST);
	}
}

# get templete
require("template.php");




# Default view
function view()
{

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		$depts .= "<option value='0'>All Departments</option>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Workshop Callout Document</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Select Department</td>
				<td valign='center'>$depts</td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of customer</td>
				<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td></tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='callout-view.php'>View Callout Documents</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}




# Default view
function view_err($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if(isset($deptid) && $dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	//layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<input type='hidden' name='cussel' value='cussel'>
			<tr>
				<th colspan='2'>New Callout Document</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign=center>$depts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First Letters of customer</td>
				<td valign=center><input type='text' size='5' name='letters' value='$letters' maxlength=5></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='callout-view.php'>View Callout Documents</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-new.php'>New Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}




# create a dummy callout document
function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$cusnum = 0;
	$calloutp = "";
	$comm = "";
	$calloutdescrip = "";
	$sign = "Hereby I acknowledge that I have received the above goods and agree with the details contained in this document";
	$odate = date("Y-m-d");
	$def_travel = "";
	$def_labour = "";
	$SUBTOT = 0;

	// $calloutid = divlastid('doc', USER_DIV);

	# insert callout document to DB
	$sql = "
		INSERT INTO callout_docs (
			deptid, cusnum, calloutp, odate, subtot, comm, calloutdescrip, username, accepted, done, 
			sign, def_travel, def_labour, div
		) VALUES (
			'$deptid', '$cusnum', '$calloutp', '$odate', '$SUBTOT', '$comm', '$calloutdescrip', '".USER_NAME."', 'n', 'n', 
			'$sign', '$def_travel', '$def_labour', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert callout document to Cubit.",SELF);

	# get next ordnum
	$calloutid = pglib_lastid ("callout_docs", "calloutid");

	return $calloutid;

}




# details
function details($_POST, $error="")
{

	extract($_POST);

	# validate input
	include("libs/validate.lib.php");
	$v = new validate();
	if (isset($calloutid)) {
		$v->isOk($calloutid, "num", 1, 20, "Invalid callout document number.");
	}

	if (isset($deptid)) {
		$v->isOk($deptid, "num", 1, 20, "Invalid department number.");
	}

	if (isset($letters)) {
		$v->isOk($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	if (!isset($deptid)) {
		$deptid = 0;
	} else if (isset($calloutid)) {
		db_conn("cubit");
		$sql = "UPDATE callout_docs SET deptid='$deptid' WHERE calloutid='$calloutid' AND deptid<>'$deptid'";
		db_exec($sql) or errDie("Error updating invoice department.");
	}

	if(!isset($calloutid)){

		db_conn ("exten");
		#only create dummy if needed resources are available ...
		$get_cpeople = "SELECT * FROM calloutpeople";
		$run_cpeople = db_exec($get_cpeople) or errDie("Unable to get call out person information");
		if(pg_numrows($run_cpeople) < 1){
			return "
				<li class='err'>No Call out People Found. Please add at least one.</li>
				<br>
				<table border=0 cellpadding='2' cellspacing='1' width=15%>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='toms/calloutp-add.php'>Add Call Out Person</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
		}

		db_connect ();
		$calloutid = create_dummy($deptid);
	}

	if (!isset($stkerr)) {
		$stkerr = "0,0";
	}

	if(!isset($done)){
		$done = "";
	}

	# Get callout document info
	db_connect();
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$docRslt = db_exec ($sql) or errDie ("Unable to get callout document information");
	if (pg_numrows ($docRslt) < 1) {
		return "<li class='err'>Callout Document Not Found</li>";
	}
	$doc = pg_fetch_array($docRslt);

	# check if callout document has been printed
	if($doc['accepted'] == "y"){
		$error = "<li class='err'> Error : Callout Document number <b>$calloutid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$doc[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected customer info
	if (isset($letters)) {

		db_connect();

		$sql = "SELECT * FROM customers WHERE cusnum = '$doc[cusnum]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		if (pg_numrows ($custRslt) < 1) {

			db_connect();
	
			if ($inv['deptid'] == 0){
				$searchdept = "";
			}else {
				$searchdept = "deptid = '$doc[deptid]' AND ";
			}

			# Query server for customer info
			$sql = "SELECT cusnum,cusname,surname FROM customers WHERE $searchdept location != 'int' AND lower(surname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY surname";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
			if (pg_numrows ($custRslt) < 1) {
				$ajax_err = "<li class='err'>No customer names starting with <b>$letters</b> in database.</li>";
				//return view_err($_POST, $err);
			}else{
				$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				$customers .= "<option value='-S' selected>Select Customer</option>";
				while($cust = pg_fetch_array($custRslt)){
					$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
				}
				$customers .= "</select>";
			}
			# take care of the unset vars
			$cust['addr1'] = "";
			$cust['cusnum'] = "";
			$cust['accno'] = "";
		}else{
			$cust = pg_fetch_array($custRslt);
			# moarn if customer account has been blocked
			if($cust['blocked'] == 'yes'){
				return "<li class='err'>Error : Selected customer account has been blocked.</li>";
			}
			$customers = "<input type='hidden' name='cusnum' value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
			$cusnum = $cust['cusnum'];
		}
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# get callout people
	db_conn("exten");

	$sql = "SELECT * FROM calloutpeople WHERE div = '".USER_DIV."' ORDER BY calloutp ASC";
	$run_sql = db_exec($sql) or errDie("Unable to get call out people from system.");
	if(pg_numrows($run_sql) < 1){
		return "<li class='err'> There were no Call Out People found.</li>";
	}else {
		$calloutps = "<select name='calloutp'>";
		while ($arr = pg_fetch_array($run_sql)){
			if($doc['calloutp'] == $arr['calloutp']){
				$calloutps .= "<option value='$arr[calloutp]' selected>$arr[calloutp]</option>";
			}else {
				$calloutps .= "<option value='$arr[calloutp]'>$arr[calloutp]</option>";
			}
		}
		$calloutps .= "</select>";
	}

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");


	# format date
	list($call_year, $call_month, $call_day) = explode("-", $doc['odate']);

/* --- End Drop Downs --- */
	// get the ID of the first warehouse
	db_conn("exten");

	$sql = "SELECT whid FROM warehouses ORDER BY whid ASC LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading warehouses (FWH).");

	if ( pg_num_rows($rslt) > 0 ) {
		$FIRST_WH = pg_fetch_result($rslt, 0, 0);
	} else {
		$FIRST_WH = "-S";
	}
/* --- Start Products Display --- */

	# select all products
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>STORE</th>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th>ITEM PRICE</th>
				<th>Remove</th>
			<tr>";

	# get selected stock in this callout document
	db_connect();

	$sql = "SELECT * FROM cubit.callout_docs_items  WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		$stkd['account'] += 0;
		$stkd['unitcost'] = sprint ($stkd['unitcost']);
		if($stkd['account'] != 0) {
			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');

			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accounts[]'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				if($ad['accid'] == $stkd['account']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$Accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}
			$Accounts .= "</select>";

			$sernos = "";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";
			$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'>";

			# Put in product
			$products .= "
				<input type='hidden' name='whids[]' value='$stkd[whid]'>
				<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
				<input type='hidden' name='SCROLL' value='yes'>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>$Accounts</td>
					<td><input type='text' size='20' name='descriptions[]' value='$stkd[description]'> $sernos</td>
					<td>$qtyin</td>
					<td>$viewcost</td>
					<td><input type='checkbox' name='remprod[]' value='$key'></td>
				</tr>";
			$key++;
		} else {
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

			# put in product
			$products .= "
				<input type='hidden' name='accounts[]' value='0'>
				<input type='hidden' name='descriptions[]' value=''>
				<input type='hidden' name='stkids[]' value='$stkd[stkid]'>
				<input type='hidden' name='whids[]' value='$stkd[whid]'>
				<input type='hidden' name='SCROLL' value='yes'>
				<tr bgcolor='".bgcolorg()."'>
					<td>$wh[whname]</td>
					<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
					<td>".extlib_rstr($stk['stkdes'], 30)."</td>
					<td><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
					<td><input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'></td>
					<td><input type='checkbox' name='remprod[]' value='$key'></td>
				</tr>";
			$key++;
		}
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S" && isset($cust['pricelist'])){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# get selected warehouse name
				db_conn("exten");

				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get selected stock in this warehouse
				db_connect();

				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# get price from price list if it is set
				if(isset($cust['pricelist'])){
					# get selected stock in this warehouse
					db_conn("exten");
					$sql = "SELECT price FROM plist_prices WHERE listid = '$cust[pricelist]' AND stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
					$plRslt = db_exec($sql);
					if(pg_numrows($plRslt) > 0){
						$pl = pg_fetch_array($plRslt);
						$stk['selamt'] = $pl['price'];
					}
				}

				/* -- Start Some Checks -- */
				# check if they are selling too much
				if(($stk['units'] - $stk['alloc']) < $qtyss[$key]){
					if(!in_array($stk['stkid'], explode(",", $stkerr))){
						if($stk['type'] != 'lab'){
							$stkerr .= ",$stk[stkid]";
							$error .= "<li class=err>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}
				/* -- End Some Checks -- */

				# Calculate amount
				$amt[$key] = ($qtyss[$key] * ($stk['selamt']));

				$stk['selamt'] = sprint ($stk['selamt']);
				# put in selected warehouse and stock
				$products .= "
					<input type='hidden' name='accounts[]' value='0'>
					<input type='hidden' name='descriptions[]' value=''>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
						<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
						<td>".extlib_rstr($stk['stkdes'], 30)."</td>
						<td><input type='text' size='3' name='qtys[]' value='$qtyss[$key]'></td>
						<td><input type='text' size='8' name='unitcost[]'  value='$stk[selamt]'></td>
						<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
					</tr>";
				$keyy++;
			}elseif(isset($accountss[$key]) && $accountss[$key] != "0" && isset($cust['pricelist'])){
 				db_conn('core');
 				$Sl = "SELECT * FROM accounts WHERE accid='$accountss[$key]'";
 				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

 				if(pg_num_rows($Ri)<1) {
 					return "invalid.";
 				}

 				$ad = pg_fetch_array($Ri);

 				# Calculate amount
 				$amt[$key] = sprint($qtyss[$key] * ($unitcosts[$key]));

 				# Input qty if not serialised
 				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";

 				# Check permissions
 				$viewcost = "<input type='text' size='8' name='unitcost[]' value='$unitcosts[$key]'>";

 				# Put in selected warehouse and stock
 				$products .= "
	 				<input type='hidden' name='accounts[]' value='$accountss[$key]'>
	 				<input type='hidden' name='whids[]' value='0'>
	 				<input type='hidden' name='stkids[]' value='0'>
	 				<input type='hidden' name='disc[]' value='0'>
	 				<input type='hidden' name='discp[]' value='0'>
	 				<tr bgcolor='".bgcolorg()."'>
	 					<td colspan='2'>$ad[accname]</td>
	 					<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$key]'></td>
	 					<td>$qtyin</td>
	 					<td>$viewcost</td>
	 					<td><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
	 					<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
	 				</tr>";
 				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					# skip if not selected
					if($whid == "-S"){
						continue;
					}

					if(!isset($addnon)) {

						# get warehouse name
						db_conn("exten");
						$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);

						# get stock on this warehouse
						db_connect();
						$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
						$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
						if (pg_numrows ($stkRslt) < 1) {
							$error .= "<li class='err'>There are no stock items in the selected store.</li>";
							continue;
						}
						if ($sel_frm == "stkcod") {
							$cods = "
								<select class='width:15' name='stkidss[]' onChange='javascript:document.form.submit();'>
									<option value='-S' disabled selected>Select Number</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								$cods .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
							}
							$cods .= "</select> ";

							$descs = "";
						} else {
							$descs = "<select class='width:15' name='stkidss[]' onChange='javascript:document.form.submit();'>";
							$descs .= "<option value='-S' disabled selected>Select Description</option>";
							$count = 0;
							while($stk = pg_fetch_array($stkRslt)){
								$descs .= "<option value='$stk[stkid]'>$stk[stkdes] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
							}
							$descs .= "</select> ";

							$cods = "";
						}


						# put in drop down and warehouse
						$products .= "
							<input type='hidden' name='accountss[]' value='0'>
							<input type='hidden' name='descriptionss[]' value=''>
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
								<td>$cods</td>
								<td>$descs</td>
								<td><input type='text' size='3' name='qtyss[]'  value='1'></td>
								<td><input type='hidden' name='amts[]' value='0.00'></td>
								<td></td>
							</tr>";
					}
				}
			}
		}
	}else{
		if(!(isset($diffwhBtn) || isset($addnon))){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd) && $wtd != 0){$whid = $wtd;}
				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!(isset($err))) {$err="";}
					$err .= "<li>There are no stock items in the selected warehouse.</li>";

				}
				$stks = "
					<select class='width:15' name='stkidss[]' onChange='javascript:document.form.submit();'>
						<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".sprint3($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";

				$products .= "
					<input type='hidden' name='descriptionss[]' value=''>
					<input type='hidden' name='accountss[]' value='0'>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
						<td>$stks</td>
						<td></td>
						<td><input type='text' size='3' name='qtyss[]' value='1'></td>
						<td>".CUR." 0.00</td>
						<td></td>
					</tr>";
			}else{
				$products .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$whs</td>
						<td></td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td>".CUR." 0.00</td>
						<td></td>
					</tr>";
			}
		}else if ( isset($addnon) ) {
			db_conn('core');
			$Sl = "SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
				<select name='accountss[]'>
					<option value='0'>Select Account</option>";
			while($ad = pg_fetch_array($Ri)) {
				if(isb($ad['accid'])) {
					continue;
				}
				$Accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
			}

			$Accounts .= "</select>";

			$products .= "
				<input type='hidden' name='whidss[]' value='$FIRST_WH'>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>$Accounts</td>
					<td><input type='text' size='20' name='descriptionss[]'></td>
					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					<td><input type='text' name='unitcosts[]' size='7'></td>
					<td>".CUR." 0.00</td>
				</tr>";
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$whs</td>
				<td></td>
				<td></td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td>".CUR." 0.00</td>
				<td></td>
			</tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($doc['subtot']);

	# Calculate subtotal
	$SUBTOT = sprint($doc['subtot']);

/* --- End Some calculations --- */

/*--- Start checks --- */

	// Which display method was selected
	if (isset($sel_frm) && $sel_frm == "stkdes") {
		$sel_frm_cod = "";
		$sel_frm_des = "checked";
	} else {
		$sel_frm_cod = "checked";
		$sel_frm_des = "";
	}

/*--- Start checks --- */


	$details_begin = "
		<center>
		<h3>New CallOut </h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='calloutid' value='$calloutid'>
		<table ".TMPL_tblDflts." width='95%'>
 			<tr>
 				<td valign='top'>
 					<div id='cust_selection'>";

	if (empty($ajax_err) && (isset($cusnum) || AJAX)) {
		if (isset($cusnum)) {
			$OTS_OPT = onthespot_encode(
				SELF,
				"cust_selection",
				"deptid=$doc[deptid]&letters=$letters&cusnum=$cusnum&calloutid=$calloutid"
			);
			$custedit = "
				<td nowrap>
					<a href='javascript: popupSized(\"cust-edit.php?cusnum=$cusnum&onthespot=$OTS_OPT\", \"edit_cust\", 700, 630);'>
						Edit Customer Details
					</a>
				</td>";
		} else {
			$custedit = "";
		}

		$ajaxOut = "
			<input type='hidden' name='letters' value='$letters'>
			<input type='hidden' name='stkerr' value='$stkerr'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'> Customer Details </th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Department</td>
					<td valign='center'>$dept[deptname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$customers</td>
					$custedit
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td valign='top'>Customer Address</td>
					<td valign='center'>".nl2br($cust['addr1'])."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td>$cust[vatnum]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Using</td>
					<td>Stock Code<input type='radio' name='sel_frm' value='stkcod' onChange='javascript:document.form.submit();' $sel_frm_cod><br>Stock Description<input type='radio' name='sel_frm' value='stkdes' onChange='javascript:document.form.submit();' $sel_frm_des></td>
				</tr>
			</table>";
	} else {
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
		$deptRslt = db_exec($sql) or errDie("Unable to view customers");
		if (pg_numrows($deptRslt) < 1) {
			return "<li class='err'>There are no Departments found in Cubit.";
		} else {
			$depts = "<select id='deptid'>";
			$depts .= "<option value='0'>All Departments</option>";
			while ($dept = pg_fetch_array($deptRslt)){
				$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
			}
			$depts .= "</select>";
		}

		if (!isset($ajax_err)) $ajax_err = "";

		$ajaxOut = "
			<script>
				function updateCustSelection() {
					deptid = getObject('deptid').value;
					letters = getObject('letters').value;
					ajaxRequest('".SELF."', 'cust_selection', AJAX_SET, 'letters='+letters+'&deptid='+deptid+'&calloutid=$calloutid');
				}
			</script>

			$ajax_err
			<table ".TMPL_tblDflts." width='400'>
				<tr>
					<th colspan='2'>New Callout Document</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>".REQ."Select Department</td>
					<td valign='center'>$depts</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>First Letters of customer</td>
					<td valign='center'><input type='text' size='5' id='letters' maxlength='5'></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td valign='center'><input type='button' value='Update &raquo' onClick='updateCustSelection();'></td>
				</tr>
			</table>";
	}

	if (isset ($diffwhBtn) OR isset ($addprodBtn) OR isset ($addnon) OR isset ($saveBtn) OR isset ($upBtn) OR isset ($doneBtn)){
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
	}else {
		$jump_bot = "";
	}

	$details_end = "
				</div>
			</td>
			<td valign='top' align='right'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Callout Document Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Job Number</td>
						<td valign='center'>$doc[calloutid]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Call Out Person</td>
						<td valign='center'>$calloutps</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date Service Required</td>
						<td valign='center'>".mkDateSelect("call",$call_year,$call_month,$call_day)."</td>
					</tr>
				<tr bgcolor='".bgcolorg()."'>
						<td>Call Out Rate</td>
						<td valign='center' nowrap>".CUR." <input type='text' name='def_travel' value='$doc[def_travel]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Labour Rate/Hour</td>
						<td valign='center' nowrap>".CUR." <input type='text' name='def_labour' value='$doc[def_labour]'></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='2'>$products</td>
		</tr>
		<tr>
			<td colspan='4'><textarea name='sign' cols='80' rows='2'>$doc[sign]</textarea></td>
		</tr>
		<tr>
			<td>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th width='25%'>Quick Links</th>
					<th width='25%'>Description Of Callout</th>
					<th width='25%'>Comments</th>
					<td rowspan='5' valign='top' width='50%'>$error</td>
				</tr>
				<tr>
					<td bgcolor='".bgcolorg()."'><a href='callout-new.php'>New Callout Document</a></td>
					<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='calloutdescrip' rows='4' cols='20'>$doc[calloutdescrip]</textarea></td>
					<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'><textarea name='comm' rows='4' cols='20'>$doc[comm]</textarea></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='callout-view.php'>View Callout Documents</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
			</td>
			<td align='right'></td>
		</tr>
		<tr>
			<td align='right'>
				<input name='diffwhBtn' type='submit' value='Different Store'> |
				<input name='addprodBtn' type='submit' value='Add Product'> |
				<input name='addnon' type='submit' value='Add Non stock Product'> |
				<input type='submit' name='saveBtn' value='Save'></td><td> |
				<input type='submit' name='upBtn' value='Update'>$done
			</td>
		</tr>
		</table>
		<a name='bottom'>
		</form>
		</center>
		$jump_bot";

	if (AJAX) {
		return $ajaxOut;
	} else {
		return "$details_begin$ajaxOut$details_end";
	}

}




# write
function write($_POST)
{

	#get vars
	extract ($_POST);

	if(!isset($cusnum)){
		return details (array(),"<li class='err'>Invalid Customer</li>");
		//$cusnum = "";
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer, Please select a customer.");
	$v->isOk ($calloutid, "num", 1, 20, "Invalid Callout Document Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($def_travel, "string", 0, 255, "Invalid Default Travel.");
	$v->isOk ($def_labour, "string", 0, 255, "Invalid Default Labour.");
	$v->isOk ($calloutdescrip, "string", 0, 255, "Invalid Callout Description.");
	$v->isOk ($sign, "string", 0, 255, "Invalid Sign Data.");
	$v->isOk ($calloutp, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($call_day, "num", 1, 2, "Invalid Service Date day.");
	$v->isOk ($call_month, "num", 1, 2, "Invalid Service Date month.");
	$v->isOk ($call_year, "num", 1, 5, "Invalid Service Date year.");
	$odate = $call_year."-".$call_month."-".$call_day;
	if(!checkdate($call_month, $call_day, $call_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid callout Document Date.");
	}
//	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";

	# check quantities
//	if(isset($qtys)){
//		foreach($qtys as $keys => $qty){
//
//			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
//			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
//			if($qty < 1){
//				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
//			}
//		}
//	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		return details($_POST, $err);
	}

	# Get callout document info
	db_connect();

	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$docRslt = db_exec ($sql) or errDie ("Unable to get callout document information");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>- Callout Document Not Found</li>";
	}

	$doc = pg_fetch_array($docRslt);



	# check if callout document has been printed
	if($doc['accepted'] == "y"){
		$error = "<li class='err'>Error : Callout Document number <b>$calloutid</b> has already been printed.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected customer info
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM callout_docs_data WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);

		$doc['deptid'] = $cust['deptid'];
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$doc[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# insert callout document to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* -- Start remove old items -- */

	# get selected stock in this callout document
	$sql = "SELECT * FROM cubit.callout_docs_items WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$stktRslt = db_exec($sql);

	#while($stkt = pg_fetch_array($stktRslt)){
	#	update stock(alloc + qty)
	#	$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]'";
	#	$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
	#}

	# remove old items
	$sql = "DELETE FROM cubit.callout_docs_items WHERE calloutid='$calloutid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update callout document items in Cubit.",SELF);

	/* -- End remove old items -- */
	$taxex = 0;
	if(isset($qtys)){
		foreach($qtys as $keys => $value){
			if(isset($remprod) && in_array($keys, $remprod)){

			}elseif(isset($accounts[$keys]) && $accounts[$keys]!=0){
				$accounts[$keys] += 0;
				# Get selamt from selected stock
				$Sl = "SELECT * FROM core.accounts WHERE accid='$accounts[$keys]'";
				$Ri = db_exec($Sl) or errDie("Unable to get account data.");

				$ad = pg_fetch_array($Ri);

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));

				$accounts[$keys] += 0;
				$descriptions[$keys] = remval($descriptions[$keys]);
				$wtd = $whids[$keys];

				# insert invoice items
				$sql = "
					INSERT INTO cubit.callout_docs_items (
						calloutid, whid, stkid, qty, 
						unitcost, amt, div, 
						description, account
					) VALUES (
						'$calloutid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', 
						'$unitcost[$keys]', '$amt[$keys]', '".USER_DIV."', 
						'$descriptions[$keys]','$accounts[$keys]'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			}else{
				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Calculate amount
				$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));


				$wtd=$whids[$keys];
				# insert callout document items
				$sql = "
					INSERT INTO cubit.callout_docs_items (
						calloutid, whid, stkid, qty, 
						unitcost, amt, div
					) VALUES (
						'$calloutid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', 
						'$unitcost[$keys]','$amt[$keys]', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert callout document items to Cubit.",SELF);

				# update stock(alloc + qty)
				# $sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]'";
				# $rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}
			# everything is set place done button
			$_POST["done"] = " | <input name='doneBtn' type='submit' value='Done'>";
		}
	}else{
		$_POST["done"] = "";
	}

	/* --- ----------- Clac --------------------- */
	##----------------------NEW----------------------

	$sub = 0.00;
	if(isset($amt)) {
		$sub = sprint(array_sum($amt));
	}

	$subtotal = sprint($sub);
	$SUBTOT = $sub;
	$TOTAL = $subtotal;


	/* --- ----------- Clac --------------------- */
	##----------------------END----------------------

	# insert callout documents to DB
	$sql = "
		UPDATE callout_docs 
		SET cusnum = '$cusnum', deptid = '$dept[deptid]', deptname = '$dept[deptname]', cusacc = '$cust[accno]', 
			cusname = '$cust[cusname]', surname = '$cust[surname]', cusaddr = '$cust[addr1]', 
			cusvatno = '$cust[vatnum]', calloutp = '$calloutp', odate = '$odate', subtot = '$SUBTOT', comm = '$comm', 
			calloutdescrip = '$calloutdescrip', sign = '$sign', def_travel = '$def_travel', def_labour = '$def_labour' 
		WHERE calloutid = '$calloutid'";
	$rslt = db_exec($sql) or errDie("Unable to update callout document in Cubit.",SELF);

	# remove old data
	$sql = "DELETE FROM callout_docs_data WHERE calloutid='$calloutid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update callout document data in Cubit.",SELF);

	# pu in new data
	$sql = "
		INSERT INTO callout_docs_data (
			calloutid, dept, customer, 
			addr1, div
		) VALUES (
			'$calloutid', '$dept[deptname]', '$cust[cusname] $cust[surname]', 
			'$cust[addr1]', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert callout document data to Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* --- Start button Listeners --- */
	if(isset($doneBtn)){

		# insert callout document to DB
		$sql = "UPDATE callout_docs SET done = 'y' WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update callout document status in Cubit.",SELF);

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>New Callout Document</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Callout Document for client <b>$cust[cusname] $cust[surname]</b> has been recorded.</td>
					<td><a target='_blank' href='callout-print.php?calloutid=$calloutid'>Print Callout Document</a></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;

	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Callout Document Saved</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Callout Document for client <b>$cust[cusname] $cust[surname]</b> has been saved.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='callout-view.php'>View Callout Documents</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}else{
		if(isset($wtd)){$_POST['wtd']=$wtd;}
		return details($_POST);
	}

}



?>
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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_REQUEST['stkid'])){
				$OUTPUT = edit($_REQUEST);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
		if (isset($_REQUEST['stkid'])){
			$OUTPUT = edit($_REQUEST);
		} else {
			$OUTPUT = get_stock ();
		}
}

require("../template.php");



function get_stock ()
{

	db_connect ();

	$get_stock = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod";
	$run_stock = db_exec($get_stock) or errDie("Unable to get stock information.");
	if(pg_numrows($run_stock) < 1){
		return "
					<li class='err'>No Stock Items Could Be Found.</li>"
					.mkQuickLinks(
								ql("../core/trans-new.php", "Journal Transactions"),
								ql("../stock-add.php", "New Stock"),
								ql("../stock-view.php", "View Stock")
							);
	}else {
		$stock_drop .= "<select name='stkid'>";
		while ($sarr = pg_fetch_array($run_stock)){
			$stock_drop .= "<option value='$sarr[stkid]'>($sarr[stkcod]) $sarr[stkdes]</option>";
		}
		$stock_drop .= "</select>";
	}


	$display = "
					<h2>Select Stock</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='GET'>
						<tr>
							<th>Stock</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>$stock_drop</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Next'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



 # confirm
function edit($_GET)
{

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	if(!isset($unitnum)) {
		# Get stock vars
		extract ($stk);
		$unitnum=1;
		$cost=1;
		$det="";
	} else {
		$entry=$tipo;
	}

	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# connect to db
	core_connect ();
	$caccdrop= "<select name='cacc'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
			return "<li class='err'>There are no Balance accounts yet in Cubit.</li>";
	}else{
		while($acc = pg_fetch_array($accRslt)){
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;

			$sel = "";
			if(isset($cacc) && $cacc == $acc['accid']){
				$sel = "selected";
			}
			$caccdrop .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
		}
	}
	$caccdrop .="</select>";

	$tinc = "";
	$tdec = "checked=yes";
	if(isset($entry) && ($entry == "inc"||$entry == "Increase")){
		$tinc = "checked=yes";
		$tdec = "";
	}

	if(!isset($date_day)) {
		$date_day=date("d");
	}

	if(!isset($date_month)) {
		$date_month=date("m");
	}

	if(!isset($date_year)) {
		$date_year=date("Y");
	}

	// Layout
	$edit = "
			<h3>Stock Balance Transaction</h3>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='stkid' value='$stkid'>
				<input type='hidden' name='whid' value='$whid'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Store</td>
					<td>$wh[whname]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Stock code</td>
					<td>$stk[stkcod]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Stock description</td>
					<td>".nl2br($stk['stkdes'])."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Type</td>
					<td valign='center'><input type='radio' name='tipo' value='Increase' $tinc>Increase Stock | <input type='radio' name='tipo' value='Decrease' $tdec>Decrease Stock</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Number of Units</td>
					<td><input type='text' size='5' name='unitnum' value='$unitnum'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td valign='top'>Details</td>
					<td><textarea name='det' rows='3' cols='18'>$det</textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Cost Amount per Unit (Stock Increase Only)</td>
					<td>".CUR." <input type='text' size='10' name='cost' value='$cost'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Contra Account</td>
					<td>$caccdrop</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td></td>
					<td align='right'><input type='submit' value='Confirm &raquo'></td>
				</tr>
			</form>
			</table>"
			.mkQuickLinks(
				ql("stock-add.php", "Add Stock"),
				ql("stock-view.php", "View Stock")
			);
	return $edit;

}



# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($whid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($tipo, "string", 1, 50, "Invalid type.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($unitnum, "num", 1, 50, "Invalid number of units.");
	if($tipo == 'Increase'){
		$v->isOk ($cost, "float", 1, 50, "Invalid cost amount per unit.");
	}else{
		$v->isOk ($cost, "float", 0, 50, "Invalid cost amount per unit.");
	}
	$v->isOk ($cacc, "num", 1, 50, "Invalid contra account.");

	$date = $date_year."-".$date_month."-".$date_day;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# Get stock vars
	extract($stk);

	if($tipo == 'Decrease'){
		$cost = sprint($csprice);
	}

	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$cacc' AND div = '".USER_DIV."'";
	$caccRslt = db_exec($sql);
	$caccd = pg_fetch_array($caccRslt);

	$serials = "";
	if($serd == 'yes' && $tipo == 'Decrease'){
		$sers = ext_getavserials($stkid);

		$serials = "<tr><th colspan='2'>Units Serial Numbers</th></tr>";

		$sernos = "<select name='sernos[]'>";

		foreach($sers as $skey => $ser){
			$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
		}
		$sernos .= "</select>";

		for($i = 0; $i < $unitnum; $i++){
			$serials .= "
							<tr class='".bg_class()."'>
								<td colspan='2' align='center'>$sernos</td>
							</tr>
						";
		}
	}

	$totcost = sprint($cost * $unitnum);

	// Layout
	$confirm = "
				<h3>Stock Balance Transaction</h3>
				<h4>Confirm entry</h4>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='whid' value='$whid'>
					<input type='hidden' name='stkid' value='$stkid'>
					<input type='hidden' name='tipo' value='$tipo'>
					<input type='hidden' name='det' value='$det'>
					<input type='hidden' name='unitnum' value='$unitnum'>
					<input type='hidden' name='cost' value='$cost'>
					<input type='hidden' name='cacc' value='$cacc'>
					<input type='hidden' name='date' value='$date'>
					<input type='hidden' name='date_day' value='$date_day'>
					<input type='hidden' name='date_month' value='$date_month'>
					<input type='hidden' name='date_year' value='$date_year'>
					<tr>
						<th width='40%'>Field</th>
						<th width='60%'>Value</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Store</td>
						<td>$wh[whname]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Stock code</td>
						<td>$stkcod</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Stock description</td>
						<td>".nl2br($stkdes)."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Type</td>
						<td>$tipo</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Date</td>
						<td>$date</td>
					</tr>
					<tr class='".bg_class()."'>
						<td valign='top'>Details</td>
						<td>".nl2br($det)."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Number of Units</td>
						<td>$unitnum</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Cost Amount per Unit</td>
						<td>".CUR." $cost</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Total Cost Amount</td>
						<td>".CUR." $totcost</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Contra Account</td>
						<td>$caccd[accname]</td>
					</tr>
					$serials
					<tr><td><br></td></tr>
					<tr>
						<td><input type='submit' name='back' value='&laquo; Correction'></td>
						<td align='right'><input type='submit' value='Write &raquo'></td>
					</tr>
				</form>
				</table>"
				.mkQuickLinks(
					ql("stock-add.php", "Add Stock"),
					ql("stock-view.php", "View Stock")
				);
	return $confirm;

}



# write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return edit($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($whid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($unitnum, "num", 1, 50, "Invalid number of units.");
	$v->isOk ($cost, "float", 1, 50, "Invalid cost amount per unit.");
	$v->isOk ($cacc, "num", 1, 50, "Invalid contra account.");
	$v->isOk ($tipo, "string", 1, 50, "Invalid type.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($date, "string", 4, 14, "Invalid date.");

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ("##", "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	if($stk['units']<0) {
		$min_stock = abs($stk['units']);

		if ( $unitnum < $min_stock ) {
			$min_stock = $unitnum;
		}
	} else {
		$min_stock=0;
	}

	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$refnum = getrefnum();

	# calculate actual cost amount
	$temp = $cost;
	$cost = sprint($cost * $unitnum);

	if($tipo == 'Increase'){

		# Update Stock
		db_connect();
		$sql = "UPDATE stock
				SET units = (units + '$unitnum'),
					lcsprice = '$temp',
					csamt = (csamt + $cost),
					csprice = (
						SELECT
							CASE WHEN (units != -$unitnum) THEN (csamt+$cost)/(units+$unitnum)
							ELSE 0
							END
						FROM cubit.stock
						WHERE stkid='$stkid' AND div='".USER_DIV."'
					)
				WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
//		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$sdate = $date;

		recordtrans ('stock', '1', '1', $sdate, $unitnum, $cost, $temp, '', $stkid);



		db_connect();
		if ($unitnum == 0) {
			$csprice = 0;
		} else {
			$csprice = sprint($cost/$unitnum);
		}

		recordtrans ('stock', '1', '0', $sdate, $unitnum, $cost, $csprice, $det, $stk['stkid']);
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		//stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $sdate, $unitnum, $cost, $det);

		$sql = "INSERT INTO stockrec (edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
				VALUES('$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'inc', '$unitnum', '$cost', '$csprice', '$det', '".USER_DIV."')";
//		$recRslt = db_exec($sql);

		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		# balance transaction
		# Debit STock account and Credit Contra Account
		recordtrans('journal', $wh['stkacc'], $cacc, $date, $refnum, $cost, '0', $det);
		//writetrans($wh['stkacc'], $cacc, $date, $refnum, $cost, $det);

		$cc_trantype = cc_TranTypeAcc($wh['stkacc'], $cacc);
	} else if($tipo == 'Decrease') {
		# Update Stock
		db_connect();
		$sql = "UPDATE stock SET csamt = (csamt - $cost), units = (units - '$unitnum') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
//		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		//$sdate = date("Y-m-d");
		$sdate = $date;
		recordtrans ('stock', '0', '0', $sdate, $unitnum, $cost, '0', $det, $stkid);




		recordtrans ('stock', '0', '1', $sdate, $unitnum, $cost, $csprice, $det, $stk['stkid']);
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		//stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $unitnum, $cost, $det);
		db_connect();
		if ($unitnum == 0) {
			$csprice = 0;
		} else {
			$csprice = sprint($cost/$unitnum);
		}
		$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
				VALUES('$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'dec', '-$unitnum', '$cost', '$csprice', '$det', '".USER_DIV."')";
		//$recRslt = db_exec($sql);

		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.</li>";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		# Units
		if($stk['units'] <> 0){
			$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
//			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}else{
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
//			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}

		if(isset($sernos)){
			foreach($sernos as $skey => $serno){
				ext_OutSer($serno, $stkid, $det, $refnum, "tran");
			}
		}

		recordtrans('journal', $cacc, $wh['stkacc'], $date, $refnum, $cost, '0', $det);
		#  Debit Contra Account and Credit Stock account
		//writetrans($cacc, $wh['stkacc'], $date, $refnum, $cost, $det);

		$cc_trantype = cc_TranTypeAcc($cacc, $wh['stkacc']);
	}

	if($cc_trantype != false){
		$cc = "<script> CostCenter('$cc_trantype', 'Stock Transaction', '$date', '$det', '$cost', ''); </script>";
	}else{
		$cc = "";
	}

	$write = "
			$cc
			<table ".TMPL_tblDflts." width='50%'>
				<tr>
					<th>Stock Balance Transaction</th>
				</tr>
				<tr class='datacell'>
					<td>Stock Balance Transaction for stock, $stk[stkdes] ($stk[stkcod]) has been successfully recorded.</td>
				</tr>
			</table>"
			.mkQuickLinks(
				ql("stock-add.php", "Add Stock"),
				ql("stock-view.php", "View Stock")
			);
	return $write;

}


?>

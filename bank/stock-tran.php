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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_GET['stkid'])){
				$OUTPUT = edit ($_GET);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['stkid'])){
		$OUTPUT = edit ($_GET);
	} elseif (isset($_GET['account'])){
		$OUTPUT = edit ($_GET);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# get template
require("../template.php");



# confirm
function edit($_GET,$errs="")
{

	# Get vars
	extract ($_GET);

	if(!isset($stkid))
		$stkid = $account;

//	if(!isset($type))
//		$type = $ttype;

	if(!isset($ttype))
		$ttype = $type;
		
	if(!isset($unitnum))
		$unitnum = "";
		
	if(!isset($det))
		$det = "";

	if(!isset($cost))
		$cost = "";

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

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$Ri = db_exec($sql);
	if(pg_numrows($Ri) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
                <p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$banks = "<select name='bankid'>";
	while($acc = pg_fetch_array($Ri)){
		$banks .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# connect to db
	core_connect ();
	$cacc= "<select name='cacc'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li class='error'>There are no Balance accounts yet in Cubit.</li>";
	}else{
		while($acc = pg_fetch_array($accRslt)){
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;

			$sel = "";
			if(isset($caccid) && $caccid == $acc['accid']){
				$sel = "selected";
			}
			$cacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
		}
	}
	$cacc .= "</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='vatcode'>
			<option value='0'>Select</option>";

	while($vd = pg_fetch_array($Ri)) {
		if($vd['del'] == "Yes") {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes .= "</select>";

	if (!isset ($date_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}
	}

	// Layout
	$edit = "
		<h3>Stock Balance Transaction</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='ttype' value='$ttype'>
			$errs
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td valign='center'>$banks</td>
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
				<td>$ttype</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
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
				<td>Amount per Unit</td>
				<td>".CUR." <input type='text' size='10' name='cost' value='$cost'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Inclusive</td>
				<td valign='center'>Yes <input type='radio' size='7' name='chrgvat' value='inc' checked='yes'> No<input type='radio' size='7' name='chrgvat' value='exc'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Code</td>
				<td>$Vatcodes</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='../stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='../stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
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
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($unitnum, "float", 1, 50, "Invalid number of units.");
	$v->isOk ($cost, "float", 1, 50, "Invalid amount per unit.");

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
//		$confirm .= "<br><input type='button' onClick='javascript:history.back();' value='&laquo Correction'>";
		return edit($_POST,$confirm."<br>");
	}

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
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

	$origvatcode = $vatcode;

	# Get stock vars
	extract ($stk);


	$totcost = sprint($cost * $unitnum);

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$origvatcode'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");
	$vd = pg_fetch_array($Ri);
	$vatp = $vd['vat_amount'];

	if($chrgvat == "exc"){
		$vat = sprint(($vatp/100) * $totcost);
		$totcost += $vat;
		$showvat = "<input type='hidden' name='vat' value='$vat'>$vat";
	} elseif($chrgvat == "inc"){
		$vat = sprint($totcost*$vatp/($vatp+100));
		$showvat = "<input type='hidden' name='vat' value='$vat'>$vat";
	}else{
		$vat = 0;
		$showvat = "<input type='hidden' name='vat' value='0'>No VAT";
	}

	/*	#get vat perc to calc vat amount

	db_connect ();

	$get_vat = "SELECT * FROM vatcodes WHERE id = '$vatcode' LIMIT 1";
	$run_vat = db_exec($get_vat);

	if(pg_numrows($run_vat) < 1){
	//		$vat = 0;
	}else {

	$vd = pg_fetch_array($run_vat);

	$vatamt = ($totcost/$vd['vat_amount']) * 100;

	if($chrgvat == "excl"){
	$totcost = $totcost + $vatamp;
	}
	}
	//*/

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$origvatcode' AND zero='Yes'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	if(pg_num_rows($Ri) > 0) {
		$chrgvat = "no";
	}

	// Layout
	$confirm = "
		<h3>Stock Balance Transaction</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='ttype' value='$ttype'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='account' value='$stkid'>
			<input type='hidden' name='det' value='$det'>
			<input type='hidden' name='unitnum' value='$unitnum'>
			<input type='hidden' name='cost' value='$cost'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='vatcode' value='$origvatcode'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
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
				<td>$type</td>
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
				<td>Amount per Unit</td>
				<td>".CUR." $cost</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Inclusive</td>
				<td valign='center'>$chrgvat</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT</td>
				<td>$showvat</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total Amount</td>
				<td>".CUR." ".sprint($totcost)."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
			<td><input type='submit' name='back' value='&laquo; Correction'></td>
			<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../stock-add.php'>Add Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
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
	$v->isOk ($unitnum, "float", 1, 50, "Invalid number of units.");
	$v->isOk ($cost, "float", 1, 50, "Invalid cost amount per unit.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($date, "string", 4, 14, "Invalid date.");

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

	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# Get warehouse name
	db_conn("exten");

	$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$refnum = getrefnum();
	/*refnum*/
	# calculate actual cost amount
	$cost = sprint($cost * $unitnum);

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	$cacc=$banklnk['accnum'];


	############## PROCESS VAT + AMOUNTS ################

	$totamt = $cost;

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");
	$vd = pg_fetch_array($Ri);
	$vatp = $vd['vat_amount'];
	if($chrgvat == "exc"){
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		$cost = sprint($totamt-$vat);
	}else{
		//		$vat = "No VAT";
	}
	################# DONE ###################

	pglib_transaction("BEGIN");


	if($ttype == 'payment'){
		# Update Stock
		db_connect();
		$sql = "UPDATE stock SET csamt = (csamt + '$cost'), units = (units + '$unitnum') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$sdate = $date;
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $sdate, $unitnum, $cost, $det);
		db_connect();
		$cspric = sprint($cost/$unitnum);
		$sql = "
			INSERT INTO stockrec (
				edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div
			) VALUES (
				'$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'inc', '$unitnum', '$cost', '$cspric', '$det', '".USER_DIV."'
			)";
		$recRslt = db_exec($sql);

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
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}else{
			$csprice = sprint($cost/$unitnum);
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}

		/*		$totamt = $cost;

		db_conn('cubit');

		$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
		$Ri=db_exec($Sl) or errDie("Unable to get vat codes");
		$vd=pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];
		if($chrgvat == "exc"){
		$totamt += $vat;
		} elseif($chrgvat == "inc"){
		$cost=sprint($totamt-$vat);
		}else{
		$vat = "No VAT";
		}*/

		# Debit STock account and Credit Contra Account
		writetrans($wh['stkacc'], $cacc, $date, $refnum, $cost, $det);

		if($vat <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $det);
		}


		vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$det,-($cost+$vat),-$vat);

		$cc_trantype = cc_TranTypeAcc($wh['stkacc'], $cacc);

		$temp = $cost + $vat;

		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, 
				div, vatcode, stkinfo
			) VALUES (
				'$bankid', 'withdrawal', '$date', '$det', '$det', '0', '$temp', '$vat', '$chrgvat', 'no', '$wh[stkacc]', 
				'".USER_DIV."', '$vatcode', '$stk[stkid]|$unitnum|$cost|$vat'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	}elseif($ttype == 'receipt'){
		$vatacc = gethook("accnum", "salesacc", "name", "VAT", "VAT");

		# Update Stock
		db_connect();
		$sql = "UPDATE stock SET csamt = (csamt - $cost), units = (units - '$unitnum') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$sdate = $date;
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $unitnum, $cost, $det);
		db_connect();
		$cspric = sprint($cost/$unitnum);
		$sql = "
			INSERT INTO stockrec (
				edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div
			) VALUES (
				'$sdate', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'dec', '-$unitnum', '$cost', '$cspric', '$det', '".USER_DIV."'
			)";
		$recRslt = db_exec($sql);

		db_connect();

		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		
		if (pg_numrows($stkRslt) < 1) {
			return "<li> Invalid Stock ID. </li>";
		} else {
			$stk = pg_fetch_array($stkRslt);
		}

		if($stk['units'] <> 0){
			$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}else{
			$csprice = sprint($cost/$unitnum);
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
		}

		if(isset($sernos)){
			foreach($sernos as $skey => $serno){
				// ext_invSer($serno, $stkid);
				ext_OutSer($serno, $stkid, $det, $refnum, "tran");
			}
		}

		#  Debit Contra Account and Credit Stock account
		writetrans($cacc, $wh['stkacc'], $date, $refnum, $cost, $det);

		if($vat <> 0){
			# DT(VAT), CT(Bank)
			writetrans($banklnk['accnum'],$vatacc, $date, $refnum, $vat, $det);
		}

		vatr($vd['id'],$date,"OUTPUT",$vd['code'],$refnum,$det,-($cost+$vat),-$vat);

		$cc_trantype = cc_TranTypeAcc($cacc, $wh['stkacc']);

		$temp = $cost + $vat;

		db_connect();

		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, vatcode, 
				stkinfo
			) VALUES (
				'$bankid', 'deposit', '$date', '$det', '$det', '0', '$temp', '0', 'no', 'no', '$wh[stkacc]', '".USER_DIV."','1',
				'$stk[stkid]|$unitnum|$cost|$vat'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}

	pglib_transaction("COMMIT");

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Stock Bank Transaction</th>
			</tr>
			<tr class='datacell'>
				<td>Stock Bank Transaction for: $stk[stkdes] ($stk[stkcod]) has been successfully recorded.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../stock-add.php'>Add Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>

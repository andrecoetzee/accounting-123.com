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
require ("../libs/ext.lib.php");
require ("bank-pay-supp-write.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "method":
			# redirect if not local supplier
			if(!is_local("suppliers", "supid", $_POST["supid"])){
				// print "SpaceBar";
				header("Location: bank-pay-supp-int.php?supid=$_POST[supid]");
				exit;
			}
			$OUTPUT = method ($_POST["supid"]);
			break;
		case "alloc":
			$OUTPUT = alloc ($_POST);
			break;
		case "confirm":
			if(isset($_POST["confirm"]))
				$OUTPUT = confirm ($_POST);
			else 
				$OUTPUT = alloc ($_POST);
			break;
		case "write":
			$OUTPUT = write_cheque ($_POST);
			break;
		default:
			$OUTPUT = sel_sup ();
	}
} elseif(isset($_GET["account"])) {
	$OUTPUT =  alloc ($_GET);
}elseif(isset($_GET["supid"])) {
	$OUTPUT =  alloc ($_GET);
}else {
	$OUTPUT = sel_sup ();
}

$OUTPUT .= "<br><br>"
	.mkQuickLinks(
		ql("bank-pay-supp.php", "Add Supplier Payment"),
		ql("bank-pay-add.php","Add Bank Payment"),
		ql("bank-recpt-add.php","Add Bank Receipt"),
		ql("../recon_reason_view.php", "Add Recon Reasons"),
		ql("cashbook-view.php","View Cash Book")
	);

require("../template.php");




# Insert details
function sel_sup()
{

	global $_POST;

	extract($_POST);

	if(!isset($supid))
		$supid = 0;

	db_connect();

	$sql = "SELECT supid,supno,supname FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supname,supno";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no Creditors in Cubit.</li>";
	}

	$supp = "<select name='supid'>";
	while($sup = pg_fetch_array($supRslt)){
		if($sup['supid'] == $supid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$supp .= "<option $sel value='$sup[supid]'>$sup[supname] ($sup[supno])</option>";
	}
	$supp .= "</select>";

	// layout
	$add = "
		<h3>New Bank Payment</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='alloc'>
			<tr>
				<th colspan='2'>Select Supplier</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Suppliers</td>
				<td>$supp</td>
			</tr>
			<input type='hidden' size='5' name='pur' value=''><input type='hidden' size='5' name='inv' value=''>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Enter Details &raquo;'></td>
			</tr>
		</form>
		</table>";
    return $add;

}



# confirm
function alloc($_POST,$err="")
{

	extract ($_POST);

	#if quick ... redirect here
	if (isset($quickpay)){
		$date = "$date_year-$date_month-$date_day";
		header ("Location: bank-pay-supp-quick.php?supid=$supid&amt=$amt&cheqnum=$cheqnum&reference=$reference&descript=$descript&bankid=$bankid&tdate=$date&pur=&inv=&process_type=$process_type");
		exit;
	}

	if (isset($midupdate))
		$navigation = "
			<script>
				gotoName('midupdate');
			</script>";
	if (isset($botupdate))
		$navigation = "
			<script>
				gotoName('botupdate');
			</script>";

#######set all missing vars ...
	$out = 0;
	$doset = TRUE;
	$settext = "Do not use this settlement discount function if the supplier is going to issue a tax credit note. If the supplier tax invoice contains the percentage and terms of settlement or any additional post transactional discount, then the supplier does not have to issue a tax credit note and you can use this function.";

	if(!isset($all))
		$all = 2;
	if(!isset($bankid))
		$bankid = "";
	if(!isset($descript))
		$descript = "";
	if(!isset($reference))
		$reference = "";
	if(!isset($cheqnum))
		$cheqnum = "";
	if(!isset($paidamt) OR !is_array($paidamt))
		$paidamt = array (0);
	$amt = sprint (array_sum($paidamt));
	if(!isset($stock_setamt))
		$stock_setamt = array (0);
	if(!isset($setamt))
		$setamt = 0;
	if(!isset($setvat))
		$setvat = "";
	if(!isset($setvatcode))
		$setvatcode = "";
	if(!isset($overpay))
		$overpay = 0;
	if (!isset($process_type))
		$process_type = getCSetting("SUPP_PROCESS_TYPE");
	if (!isset($process_type))
		$process_type = "";
	if (!isset($supid) OR strlen($supid) < 1)
		return sel_sup();

	$amt = sprint ($amt + $overpay);
	$setamt = sprint ($setamt);

	if(!isset($date_day)){
		#get the last used one ...
		$date = getCSetting("SUPP_PAY_DATE");
		if(isset($date) AND strlen($date) > 0){
			$date_arr = explode ("-",$date);
			$date_day = $date_arr[2];
			$date_month = $date_arr[1];
			$date_year = $date_arr[0];
		}else {
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}
	}

	if (!isset($date_day) OR strlen($date_day) < 1 OR $date_day > 31 OR $date_day < 1) 
		$date_day = date ("d");
	if (!isset($date_month) OR strlen ($date_month) < 1 OR $date_month > 12 or $date_month < 1) 
		$date_month = date("m");
	if (!isset($date_year) OR strlen($date_year) < 1 OR $date_year < 1980 OR $date_year > 2020) 
		$date_year = date ("Y");

//print "$date_year-$date_month-$date_day";
####### validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 0, 30, "Invalid Bank Account.");
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($setamt, "float", 0, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setvat, "string", 0, 10, "Invalid Settlement VAT Option.");
	$v->isOk ($setvatcode, "string", 0, 40, "Invalid Settlement VAT code");
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");

	$date = mkdate($date_year, $date_month, $date_day);
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.alloc($_POST,$confirm."<br>");
	}






	db_connect();

	#check if this supplier is blocked ...
	$sql = "SELECT blocked FROM suppliers WHERE supid = '$supid'";
	$run_sql = db_exec($sql) or errDie ("Unable to get supplier information.");
	if (pg_numrows($run_sql) < 1){
		return "<li class='err'>Supplier information not found.</li>";
	}else {
		$res = pg_fetch_array ($run_sql);
		if ($res['blocked'] == "yes")
			return "<li class='err'>Supplier has been blocked. Please unblock supplier before continuing. <a href='../supp-view.php'>View Suppliers.</a></li>";
	}


####### Get supplier details
	$sql = "SELECT supid,supno,supname,setdisc,setdays,balance FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

####### Get Bank Dropdown
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);
	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	$bank_drop = "<select name='bankid'>";
	while($acc = pg_fetch_array($banks)){
		if($bankid == $acc['bankid']){
			$bank_drop .= "<option value='$acc[bankid]' selected>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}else {
			$bank_drop .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		}
	}
	$bank_drop .= "</select>";

####### Get vat codes for dropdown
	$get_vatc = "SELECT * FROM vatcodes ORDER BY code";
	$run_vatc = db_exec($get_vatc) or errDie ("Unable to get vat codes information.");
	if(pg_numrows($run_vatc) < 1){
		$vatcode_drop = "<input type='hidden' name='setvatcode' value=''>";
	}else {
		$vatcode_drop = "<select name='setvatcode'>";
		while ($varr = pg_fetch_array ($run_vatc)){
			if(isset($setvatcode) AND $setvatcode == $varr['id']){
				$vatcode_drop .= "<option value='$varr[id]' selected>$varr[code] $varr[description]</option>";
			}else {
				$vatcode_drop .= "<option value='$varr[id]'>$varr[code] $varr[description]</option>";
			}
		}
		$vatcode_drop .= "</select>";
	}

	$setvatsel1 = "";
	$setvatsel2 = "";
	if($setvat == "novat")
		$setvatsel2 = "checked='yes'";
	else 
		$setvatsel1 = "checked='yes'";

//				<input type='hidden' name='bankid' value='$bankid'>
//				<input type='hidden' name='date' value='$date'>
//				<input type='hidden' name='descript' value='$descript'>
//				<input type='hidden' name='reference' value='$reference'>
//				<input type='hidden' name='cheqnum' value='$cheqnum'>
//				<input type='hidden' name='amt' value='$amt'>
//				<input type='hidden' name='setvat' value='$setvat'>
//				<input type='hidden' name='setvatcode' value='$setvatcode'>
//				<input type='hidden' name='setamt' value='".sprint (array_sum($stock_setamt))."'>

	$prsel1 = "";
	$prsel2 = "";
	if ($process_type == "batch")
		$prsel2 = "checked='yes'";
	else 
		$prsel1 = "checked='yes'";

	if (perm("allow-user-change-supp-process-type.php")){
		$show_process_type = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Payment Process Type</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><input type='radio' name='process_type' value='now' $prsel1>Pay creditor immediately and add to cashbook</td>
					<td><input type='radio' name='process_type' value='batch' $prsel2>Add to creditor payment batch</td>
				</tr>
				".TBL_BR."
			</table>";
	}else {
		$show_process_type = "<input type='hidden' name='process_type' value='$process_type'>";
	}
	$confirm = "
		<script>
			function showText() {
				XPopupShow('$settext', getObject('phonetic_show'));
			}
			function updateStockTotal (counter){
				var total_val = getObj('total_id'+counter);
				var htotal_val = getObj('total_hid'+counter);
				var set_val = getObj('set_id'+counter);
				var hset_val = getObj('set_hid'+counter);
	
				var button_val = getObj('button'+counter);
	
				if (total_val.value == '0.00'){
					total_val.value = htotal_val.value - hset_val.value;
				}else {
					total_val.value = '0.00';
				}
				if (set_val.value == '0.00'){
					set_val.value = hset_val.value;
				}else {
					set_val.value = '0.00';
				}
				button_val.blur();
			}
			function pageXY(el){
				var XY={x:0, y:0};
				for( var node = el; node; node=node.offsetParent)
				{ XY.x += node.offsetLeft;
					XY.y += node.offsetTop;
				}
				return XY;
			}
			function gotoName(name){
				var anchors, anchor, XY;
				anchors=document.anchors;
				anchor=anchors[name];
				if(!anchor) // IE sucks
				{
					for( var i = 0; i < anchors.length; ++i){
						if(anchors[i].name==name){
							anchor = anchors[i];
							break;
						}
					}
				}
				if(!anchor){
					if( document.getElementById)
						anchor=document.getElementById(name);
					else if( document.all) // untested
						anchor=document.all[name];
				}
				if(anchor){
					XY = pageXY(anchor);
					window.scrollTo(XY.x, XY.y);
				}
			}
		</script>
		<h3>New Bank Payment</h3>
		<form action='".SELF."' method='POST' name='form1'>
		$show_process_type
		<table ".TMPL_tblDflts.">
			$err
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='pur' value=''>
			<input type='hidden' name='inv' value=''>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account</td>
							<td>$bank_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Payment Date</td>
							<td valign='center'>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Paid To</td>
							<td valign='center'>($sup[supno]) $sup[supname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Description</td>
							<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reference</td>
							<td valign='center'><input type='text' size='25' name='reference' value='$reference'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Total Amount</td>
							<td>".CUR." $amt</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Settlement Discount</td>
							<td valign='center'>".CUR." ".sprint (array_sum($stock_setamt))." <input type='button' onClick='showText();' value='Tax Credit Note ?'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td nowrap>Settlement Discount VAT</td>
							<td nowrap>
								$vatcode_drop 
								<input type='radio' name='setvat' value='inc' $setvatsel1> VAT Inclusive 
								<input type='radio' name='setvat' value='novat' $setvatsel2> No VAT
							</td>
						</tr>
					</table>
				</td>
				<td width='5%'></td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'><font style='color:red'>Quick</font> Payment</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cheque Number</td>
							<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." <input type='text' size='6' name='amt' value='$amt'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td colspan='2' align='right'><input type='submit' name='quickpay' value='Allocate the above payment automatically'></td>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='submit' name='confirm' value='Allocate the payments below &raquo'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			".TBL_BR."
			<tr class='".bg_class()."'>
				<td colspan='4'>Add unallocated payment to supplier statement &nbsp;</td>
				<td>&nbsp;".CUR." <input type='text' size='10' name='overpay' value='$overpay'></td>
			</tr>
			".TBL_BR;

	db_conn('cubit');

	$Sl = "SELECT purnum,supinv FROM purchases";
	$Ri = db_exec($Sl);

	while($pd = pg_fetch_array($Ri)) {
		$pid = $pd['purnum'];
		$supinv[$pid] = $pd['supinv'];
	}

	for($i = 1; $i < 13; $i++) {
		db_conn($i);

		$Sl = "SELECT purnum, supinv FROM purchases";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {
			$pid = $pd['purnum'];
			$supinv[$pid] = $pd['supinv'];
		}

		$Sl = "SELECT purnum, supinv FROM nons_purchases";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {
			$pid = $pd['purnum'];
			$supinv[$pid] = $pd['supinv'];
		}

	}


	if($all == 2) {

		if(!isset($paidamt))
			$paidamt = array (0);
		if(!isset($stock_setamt))
			$stock_setamt = array (0);

	#user has to use auto allocation ... we cant just die here anymore ...
		db_conn("cubit");

		$sql = "
			SELECT purid as invid, intpurid as invid2, SUM(balance) AS balance, pdate as odate 
			FROM suppurch WHERE supid = '$supid' AND div = '".USER_DIV."' 
			GROUP BY purid, intpurid, pdate 
			HAVING SUM(balance) > 0 
			ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
//		if(pg_numrows($prnInvRslt) < 1) {return "The selected supplier has no outstanding purchases<br>
//				To make a payment in advance please select Auto Allocation";}

		$i = 0;
		$counter = 0;
		$total = 0;
		while(($inv = pg_fetch_array($prnInvRslt))) {
			if ($inv['invid'] == 0) {continue;}
			if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}

			if($doset)
				$showsethead = "<th>Settlement</th><th>Potential Settlement Discount</th>";
			else 
				$showsethead = "";

			if($i == 0) {
				$confirm .= "
					".TBL_BR."
					<tr>
						<td colspan='3'><h3>Allocate Payments To Supplier Invoices:</h3></td>
					</tr>
					".TBL_BR."
					<tr>
						<th>Purchase</th>
						<th>Supplier Invoice No.</th>
						<th>Outstanding Amount</th>
						<th>Date</th>
						<th>Amount</th>
						$showsethead
					</tr>";
			}

			$invid = $inv['invid'];
			$val = '';
			if(pg_numrows($prnInvRslt) == 1) {$val = $amt;}

			if(isset($paidamt[$i])) {
				$val = sprint ($paidamt[$i]);
			}

			$val = sprint ($val);

			if(isset($supinv[$invid])) {
				$sinv = $supinv[$invid];
			} else {
				$sinv = "";
			}

			if($doset) {
				#check if we can find a recommended settlement amt ...
				if ($sup['setdisc'] != "0"){

					#generate the dates ...
					if ($sup['setdays'] == 0)
						$month = date ("m") + 1;
					else 
						$month = date ("m");
					$startmonth = $month - 1;

					$firstdate = date("Y-m-d",mktime(0,0,0,$startmonth,$sup['setdays'],date("Y")));
					$discdate = date("Y-m-d",mktime (0,0,0,$month,$sup['setdays'],date("Y")));
					$lastdate = date("Y-m-d",mktime(0,0,0,date("m")+1,-1,date("Y")));

//print "$firstdate -> $discdate -> $lastdate =====> $date ($inv[odate])<br>";

					if (($inv['odate'] > $firstdate) AND ($inv['odate'] < $lastdate) AND ($inv['odate'] <= $date)){
						#discount applies ...
						$setrec = sprint (($inv['balance'] / 100) * $sup['setdisc']);
					}else {
						#no discount
						$setrec = sprint (0);
					}
				}else {
					$setrec = sprint (0);
				}
				if (!isset($stock_setamt[$i])){
					$stock_setamt[$i] = sprint (0);
				}
				$stock_setamt[$i] = sprint ($stock_setamt[$i]);
				$showset = "
					<td><input id='set_id$counter' type='text' size='10' name='stock_setamt[$i]' value='$stock_setamt[$i]'></td>
					<td><input id='set_hid$counter' type='hidden' name='stock_setamt_val' value='$setrec'>".CUR." $setrec</td>";
			}else {
				$showset = "
					<input id='set_id$counter' type='hidden' name='stock_setamt[$i]' value='0'>
					<input id='set_hid$counter' type='hidden' name='stock_setamt_val' value='0'>";
			}

			$confirm .= "
				<input type='hidden' size='20' name='invids[$i]' value='$inv[invid]'>
				<input id='total_hid$counter' type='hidden' name='totamt[$i]' value='$inv[balance]'>
				<tr class='".bg_class()."'>
					<td>$inv[invid]</td>
					<td>$sinv</td>
					<td>".CUR." $inv[balance]</td>
					<td>$inv[odate]</td>
					<td><input id='total_id$counter' type='text' name='paidamt[$i]' size='10' value='$val'></td>
					$showset
					<td><input id='button$counter' type='checkbox' onClick=\"updateStockTotal($counter);\"></td>
				</tr>";

			$i++;

			$total = $total + $inv['balance'];

			if($counter == 15){
				$confirm .= "
					<tr>
						<td colspan='2'></td>
						<td class='".bg_class()."' nowrap colspan='2'>".CUR." ".sprint ($total)."</td>
						<td class='".bg_class()."' nowrap>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
						<td class='".bg_class()."' nowrap>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
						<td class='".bg_class()."' nowrap align='left'><input type='submit' name='midupdate' value='Update'></td>
					</tr>
					<a name='midupdate'></a>";
				$counter = 0;
			}
			$counter++;
		}

		if ($counter != 0){
			$confirm .= "
				<tr>
					<td colspan='2'></td>
					<td class='".bg_class()."' nowrap colspan='2'>".CUR." ".sprint ($total)."</td>
					<td class='".bg_class()."' nowrap>Total: ".CUR." ".sprint (array_sum($paidamt))." </td>
					<td class='".bg_class()."' nowrap>Total: ".CUR." ".sprint (array_sum($stock_setamt))." </td>
					<td class='".bg_class()."' nowrap align='left'><input type='submit' name='botupdate' value='Update'></td>
				</tr>
				<a name='botupdate'></a>";
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
		if ($out >= 0.01) {
			$confirm .= "
				<tr class='".bg_class()."'>
					<td colspan='5'><b>A general transaction will debit the supplier's account with ".CUR." ".sprint($out)." </b></td>
				</tr>";
		}
	}

	vsprint($out);



###########################[ RECONCILIATION ]#############################

	#if we adding a new reason ... add it
	if((isset($newreason) AND strlen($newreason) > 0) AND (isset($newreasonamt) AND strlen($newreasonamt) > 0)){
		if (!isset($newreasondescr))
			$newreasondescr = "";
		$ins_sql = "
			INSERT INTO recon_balance_ct (
				supid, date, reason_id, amount,descr
			) VALUES (
				'$supid', 'now', '$newreason', '$newreasonamt', '$newreasondescr'
			)";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record new reson information.");
		$navigation = "<script>gotoName('bottom');</script>";
	}

	#if we adding a new comment ... add it
	if(isset($newcomment) AND strlen($newcomment) > 0){
		$ins_sql = "
			INSERT INTO recon_comments_ct (
				supid, comment, date
			) VALUES (
				'$supid', '".base64_encode($newcomment)."', 'now'
			)";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record new comment information.");
		$navigation = "<script>gotoName('bottom');</script>";
	}

	#if we removing a reason ... remove it
	if(isset($remreason)){
		$rem_sql = "DELETE FROM recon_balance_ct WHERE id = '$remreason'";
		$run_rem = db_exec($rem_sql) or errDie ("Unable to remove selected reason.");
	}

	#if we removing a comment ... remove it
	if(isset($remcomment)){
		$rem_com = "DELETE FROM recon_comments_ct WHERE id = '$remcomment'";
		$run_rem = db_exec($rem_com) or errDie ("Unable to remove selected comment.");
	}


	#get balances ...
	if(!isset($creditor_balance) OR strlen($creditor_balance) < 1){
		$sql = "SELECT balance FROM cubit.recon_creditor_balances WHERE supid='$supid'";
		$cbalance_rslt = db_exec($sql) or errDie("Unable to retrieve creditor balance.");
		if(pg_numrows($cbalance_rslt) < 1){
			$creditor_balance = 0;
			$ins_sql = "INSERT INTO cubit.recon_creditor_balances (supid,balance) VALUES ('$supid','0')";
			$run_ins = db_exec($ins_sql) or errDie ("Unable to record creditor balance.");
		}
		$creditor_balance = pg_fetch_result($cbalance_rslt, 0);
	}else {
		#update the db one now ..
		$upd_sql = "UPDATE recon_creditor_balances SET balance = '$creditor_balance' WHERE supid = '$supid'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update creditor balance information.");
	}
	$total_balance = sprint($sup["balance"] + $creditor_balance);
//	$diff_balance = sprint($sup["balance"] - $creditor_balance);
	$diff_balance = sprint (($amt + array_sum($stock_setamt)) - $creditor_balance);

	#get reasons for supplier ...
	$sql = "SELECT recon_balance_ct.id, date, reason, amount, descr FROM cubit.recon_balance_ct
				LEFT JOIN cubit.recon_reasons
					ON recon_balance_ct.reason_id=recon_reasons.id
			WHERE supid='$supid' AND date>='$date'";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");
	$balance_out = "";
	$reason_total = 0;
	while (list($id, $date, $reason, $amount, $reasondescr) = pg_fetch_array($balance_rslt)) {
		$balance_out .= "
			<tr class='".bg_class()."'>
				<td>$date</td>
				<td>$reason</td>
				<td align='right'>".CUR." $amount</td>
				<td>$reasondescr</td>
				<td><input type='checkbox' name='remreason' value='$id' onClick='document.form1.submit();'></td>
			</tr>";
		$reason_total = $reason_total + $amount;
	}
	if($reason_total != 0){
		$balance_out .= "
			<tr class='".bg_class()."'>
				<td colspan='2'><b>Total:</b></td>
				<td align='right'><b>".CUR." ".sprint($reason_total)."</b></td>
				<td colspan='2'></td>
			</tr>";
	}

	if (!isset($sup['supid']))
		$sup['supid'] = "0";

	#get comments for supplier ...
	$sql = "SELECT id, date, comment FROM cubit.recon_comments_ct WHERE supid='$sup[supid]' ORDER BY id DESC";
	$comments_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
	$comments_out = "";
	while ($comments_data = pg_fetch_array($comments_rslt)) {
		$comments_out .= "
			<tr class='".bg_class()."'>
				<td>$comments_data[date]</td>
				<td>".base64_decode(nl2br($comments_data["comment"]))."</td>
				<td><input type='checkbox' name='remcomment' value='$comments_data[id]' onClick='document.form1.submit();'></td>
			</tr>";
	}

	$get_reasons = "SELECT * FROM recon_reasons ORDER BY reason";
	$run_reasons = db_exec($get_reasons) or errDie ("Unable to get reasons information");
	if(pg_numrows($run_reasons) < 1){
		$newreason_drop = "<a target='_blank' href='../recon_reason_view.php'>No Reasons Found.</a>";
	}else {
		#get list of available reasons
		$newreason_drop = "<select name='newreason'>";
		while ($rarr = pg_fetch_array ($run_reasons)){
			$newreason_drop .= "<option value='$rarr[id]'>$rarr[reason]</option>";
		}
		$newreason_drop .= "</select>";
	}

	if (!isset($navigation))
		$navigation = "";

	$confirm .= "
			<input type='hidden' name='out' value='$out'>
			<tr>
				<td colspan='4'></td>
				<td align='right'><input type='submit' name='confirm' value='Confirm &raquo'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			".TBL_BR."
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='5'>Reconciliation to statement supplied by supplier:</th>
						</tr>
						<tr>
							<th>Date</th>
							<th>Reason</th>
							<th>Amount</th>
							<th>Ref No</th>
							<th>Remove</th>
						</tr>
						$balance_out
						<tr class='".bg_class()."'>
							<td colspan='2'>$newreason_drop</td>
							<td><input type='text' size='10' name='newreasonamt'></td>
							<td><input type='text' size='14' name='newreasondescr'></td>
							<td><input type='submit' value='Add'></td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='3'>Comments</th>
						</tr>
						<tr>
							<th>Date</th>
							<th>Comment</th>
							<th>Remove</th>
						</tr>
						$comments_out
						<tr class='".bg_class()."'>
							<td colspan='2'><input type='text' size='30' name='newcomment'></td>
							<td><input type='submit' value='Add'></td>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr class='".bg_class()."'>
				<td>Balance According to Cubit</td>
				<td align='right'>".CUR." $sup[balance]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total Of This Payment</td>
				<td align='right'>".CUR." ".sprint ($amt + array_sum ($stock_setamt))."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Balance According to Supplier</td>
				<td align='right'><input type='text' name='creditor_balance' value='$creditor_balance'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Difference in amount paid now and supplier statement</td>
				<td align='right'><li class='err'>".CUR." $diff_balance</li></td>
			</tr>
		</table>
		</form>
		<a name='bottom'></a>
		$navigation";
	return $confirm;

}



# confirm
function confirm($_POST)
{
	
	# get vars
	extract ($_POST);

	if(isset($back)) {
		return alloc($_POST);
	}


	if(!isset($out1)) {$out1 = '';}
	if(!isset($out2)) {$out2 = '';}
	if(!isset($out3)) {$out3 = '';}
	if(!isset($out4)) {$out4 = '';}
	if(!isset($out5)) {$out5 = '';}

	$OUT1 = $out1;
	$OUT2 = $out2;
	$OUT3 = $out3;
	$OUT4 = $out4;
	$OUT5 = $out5;

	if(!isset($overpay))
		$overpay = 0;
	if (!isset($stock_setamt) OR !is_array ($stock_setamt)){
		$setamt = 0;
		$stock_setamt = array (0);
	}else {
		$setamt = array_sum($stock_setamt);
	}

	if (!isset($paidamt) OR !is_array($paidamt)){
		$amt = 0;
		$paidamt = array(0);
	}else {
		$amt = array_sum ($paidamt);
	}

	#add the overpay amount to the total ?
//	$amt += $overpay;
	
	#handle missing description
	if (!isset($descript) OR strlen($descript) < 1)
		$descript = $reference;

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 1, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 15, "Invalid amount.");
	$v->isOk ($overpay, "float", 1, 15, "Invalid unallocated payment amount.");
	$v->isOk ($setamt, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk ($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(currant).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");
	$v->isOk ($process_type, "string", 1, 6, "Invalid Payment Process Type.");

	$v->isOk ($supid, "num", 1, 10, "Invalid Supplier number.");
	if(isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}
			if (!isset ($stock_setamt[$key]) OR strlen ($stock_setamt[$key]) < 1)
				$stock_setamt[$key] = 0;
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No. [$key]");
			$v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
			$v->isOk ($stock_setamt[$key], "float", 1, 20, "Invalid settlement discount amount.");

			if (sprint ($paidamt[$key] + $stock_setamt[$key]) > sprint ($totamt[$key]))
				$v->addError($paidamt[$key], "Total Paid Amount For Purchase: $invids[$key] Is More Than Total Outstanding Amount. (".sprint ($totamt[$key]).")");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
	//	$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return alloc ($_POST, $confirm."<br>");
	}

	#set some vars, after validation of course ...
	$overpay = sprint ($overpay + 0);
	$setamt = array_sum ($stock_setamt);

	$date = "$date_year-$date_month-$date_day";


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$out1 += 0;
	$out2 += 0;
	$out3 += 0;
	$out4 += 0;
	$out5 += 0;

	# check invoice payments
	$tot = 0;
	if(isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}
			$tot += $paidamt[$key];
		}
	}


	if(sprint($tot + $out + $out1 + $out2 + $out3 + $out4 + $out5 - $amt) > sprint (0)){
		return "<li class='err'>The total amount for invoices is greater than the amount received.
		Please check the details.</li>".alloc($_POST);
	}

	if (sprint ($setamt) > 0){
		if (array_sum ($stock_setamt) != $setamt){
			return "<li class='err'>The total settlement amount for invoices is not equal to the amount received.
			Please check the details.</li>".alloc($_POST);
		}
	}

	vsprint($out);

	$confirm = "
		<h3>New Bank Payment</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='out' value='$out'>
			<input type='hidden' name='amt' value='$amt'>
			<input type='hidden' name='overpay' value='$overpay'>
			<input type='hidden' name='setamt' value='$setamt'>
			<input type='hidden' name='setvat' value='$setvat'>
			<input type='hidden' name='setvatcode' value='$setvatcode'>
			<input type='hidden' name='process_type' value='$process_type'>";

	# Get bank account name
	db_connect();

	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	if(pg_num_rows($bankRslt) < 1) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	# Supplier name
	$sql = "SELECT supno,supname FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	$amt = sprint ($amt);

	if($setvat == "inc")
		$showsetvat = "VAT Inclusive";
	else 
		$showsetvat = "No VAT";

//	$overpay = sprint ($amt - array_sum($paidamt));
	if($overpay < 0)
		$overpay = 0.00;

	$confirm .= "
		<tr>
			<th colspan='2'>Payment Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Account</td>
			<td>$bank[accname] - $bank[bankname]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Payment Date</td>
			<td valign='center'>$date</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Paid To</td>
			<td valign='center'>($sup[supno]) $sup[supname]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Description</td>
			<td valign='center'>$descript</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Reference</td>
			<td valign='center'>$reference</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Cheque Number</td>
			<td valign='center'>$cheqnum</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Amount</td>
			<td valign='center'>".CUR." $amt</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Settlement Discount</td>
			<td valign='center'>".CUR." ".sprint ($setamt)." $showsetvat</td>
		</tr>
		".TBL_BR."
		<tr class='".bg_class()."'>
			<td colspan='5'><b>A general transaction will credit the client's account with ".CUR." $overpay </b></td>
		</tr>";



	if(sprint ($setamt) > 0)
		$doset = TRUE;
	else 
		$doset = FALSE;

	if($all == 2) {

		if ($doset) 
			$showsethead = "<th>Settlement</th>";
		else 
			$showsethead = "";

		// Layout
		$confirm .= "
			".TBL_BR."
			<tr>
				<td colspan='2'><h3>Outstanding Purchases</h3></td>
			</tr>
			<!--<table ".TMPL_tblDflts." width='90%'>-->
			<tr>
				<th>Purchase</th>
				<th>Outstanding amount</th>
				<th>Date</th>
				<th>Amount</th>
				$showsethead
			</tr>";

		$i = 0; // for bgcolor
		if(isset($invids)) {
			foreach($invids as $key => $value){

				if($paidamt[$key] < 0.01){
					continue;
				}

				$paidamt[$key] = sprint ($paidamt[$key]);

				db_conn("cubit");
				# Get all the details
				$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE purid='$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1)
				{
					$sql = "SELECT purid as invid,intpurid as invid2,balance,pdate as odate FROM suppurch WHERE intpurid='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1)
					{
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}
				}
				$inv = pg_fetch_array($invRslt);
				if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}
				$invid = $inv['invid'];

				#handle warnings ...
				if (($paidamt[$key] + $stock_setamt[$key]) < sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying Less Than Total Amount.</li></td>";
				}elseif (($paidamt[$key] + $stock_setamt[$key]) > sprint ($inv['balance'])){
					$warning = "<td><li class='err'>Paying More Than Total Amount Outstanding.</li></td>";
				}else {
					$warning = "";
				}

				if($doset) {
					if(!isset($stock_setamt[$invid]))
						$stock_setamt[$invid] = "";
					$showset = "<td><input type='hidden' name='stock_setamt[$key]' value='$stock_setamt[$key]'>".CUR." ".sprint ($stock_setamt[$key])."</td>";
				}else {
					$showset = "<td><input type='hidden' name='stock_setamt[$key]' value='0'></td>";
				}

				$confirm .= "
					<input type='hidden' size='20' name='invids[$key]' value='$inv[invid]'>
					<input type='hidden' name='paidamt[$key]' size='7' value='$paidamt[$key]'>
					<tr class='".bg_class()."'>
						<td>$inv[invid]</td>
						<td>".CUR." $inv[balance]</td>
						<td>$inv[odate]</td>
						<td>".CUR." $paidamt[$key]</td>
						$showset
						$warning
					</tr>";
				$i++;

			}
		}else {
			$confirm .= "
				<tr class='".bg_class()."'>
					<td colspan='4'>No Payments Made</td>
				</tr>";
		}

		// 0.01 because of high precisions like 0.0000000001234 still matching
//		if ($out >= 0.01) {
//			$confirm .= "
//			<tr class='".bg_class()."'>
//				<td colspan='5'><b>A general transaction will debit the supplier's account
//					with ".CUR." ".sprint($out)." </b>
//				</td>
//			</tr>";
//		}
	}

	vsprint($out1);
	vsprint($out2);
	vsprint($out3);
	vsprint($out4);
	vsprint($out5);
	vsprint($OUT1);
	vsprint($OUT2);
	vsprint($OUT3);
	vsprint($OUT4);
	vsprint($OUT5);

	# Supplier name
	$sql = "SELECT supid,supno,supname,setdisc,setdays,balance FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	$sup = pg_fetch_array($supRslt);

	#get balances ...
	if(!isset($creditor_balance) OR strlen($creditor_balance) < 1){
		$sql = "SELECT balance FROM cubit.recon_creditor_balances WHERE supid='$supid'";
		$cbalance_rslt = db_exec($sql) or errDie("Unable to retrieve creditor balance.");
		$creditor_balance = pg_fetch_result($cbalance_rslt, 0);
	}else {
		#update the db one now ..
		$upd_sql = "UPDATE recon_creditor_balances SET balance = '$creditor_balance' WHERE supid = '$supid'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update creditor balance information.");
	}
	$total_balance = sprint($sup["balance"] + $creditor_balance);
//	$diff_balance = sprint($sup["balance"] - $creditor_balance);
	$diff_balance = sprint (($amt + array_sum($stock_setamt)) - $creditor_balance);

	#get reasons for supplier ...
	$sql = "SELECT recon_balance_ct.id, date, reason, amount FROM cubit.recon_balance_ct
				LEFT JOIN cubit.recon_reasons
					ON recon_balance_ct.reason_id=recon_reasons.id
			WHERE supid='$supid' AND date>='$date'";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");
	$balance_out = "";
	$reason_total = 0;
	while (list($id, $date, $reason, $amount) = pg_fetch_array($balance_rslt)) {
		$balance_out .= "
			<tr class='".bg_class()."'>
				<td>$date</td>
				<td>$reason</td>
				<td align='right'>".CUR." $amount</td>
			</tr>";
		$reason_total = $reason_total + $amount;
	}
	if($reason_total != 0){
		$balance_out .= "
			<tr class='".bg_class()."'>
				<td colspan='2'><b>Total:</b></td>
				<td align='right'><b>".CUR." ".sprint($reason_total)."</b></td>
			</tr>";
	}

	#get comments for supplier ...
	$sql = "SELECT id, date, comment FROM cubit.recon_comments_ct WHERE supid='$sup[supid]' ORDER BY id DESC";
	$comments_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
	$comments_out = "";
	while ($comments_data = pg_fetch_array($comments_rslt)) {
		$comments_out .= "
			<tr class='".bg_class()."'>
				<td>$comments_data[date]</td>
				<td>".base64_decode(nl2br($comments_data["comment"]))."</td>
			</tr>";
	}

	$get_reasons = "SELECT * FROM recon_reasons ORDER BY reason";
	$run_reasons = db_exec($get_reasons) or errDie ("Unable to get reasons information");
	if(pg_numrows($run_reasons) < 1){
		$newreason_drop = "<a target='_blank' href=''></a>";
	}else {
		#get list of available reasons
		$newreason_drop = "<select name='newreason'>";
		while ($rarr = pg_fetch_array ($run_reasons)){
			$newreason_drop .= "<option value='$rarr[id]'>$rarr[reason]</option>";
		}
		$newreason_drop .= "</select>";
	}

	$confirm .= "
				<input type='hidden' name='out1' value='$out1'>
				<input type='hidden' name='out2' value='$out2'>
				<input type='hidden' name='out3' value='$out3'>
				<input type='hidden' name='out4' value='$out4'>
				<input type='hidden' name='out5' value='$out5'>
				<input type='hidden' name='OUT1' value='$OUT1'>
				<input type='hidden' name='OUT2' value='$OUT2'>
				<input type='hidden' name='OUT3' value='$OUT3'>
				<input type='hidden' name='OUT4' value='$OUT4'>
				<input type='hidden' name='OUT5' value='$OUT5'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
			</table>
			<table ".TMPL_tblDflts.">
				".TBL_BR."
				<tr>
					<td valign='top'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='3'>Reconciliation to statement supplied by supplier:</th>
							</tr>
							<tr>
								<th>Date</th>
								<th>Reason</th>
								<th>Amount</th>
							</tr>
							$balance_out
						</table>
					</td>
					<td valign='top'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'>Comments</th>
							</tr>
							<tr>
								<th>Date</th>
								<th>Comment</th>
							</tr>
							$comments_out
						</table>
					</td>
				</tr>
				".TBL_BR."
			</table>
			<table ".TMPL_tblDflts.">
				<tr class='".bg_class()."'>
					<td>Balance According to Cubit</td>
					<td align='right'>".CUR." $sup[balance]</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Of This Payment</td>
					<td align='right'>".CUR." ".sprint ($amt + array_sum ($stock_setamt))."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Balance According to Supplier</td>
					<td align='right'>".CUR." ".sprint ($creditor_balance)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Difference in amount</td>
					<td align='right'><li class='err'>".CUR." $diff_balance</li></td>
				</tr>
			</table>
			<table ".TMPL_tblDflts." width='40%'>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right'><input type='submit' value='Write &raquo'></td>
				</tr>
			</table>
			</form>";
	return $confirm;

}




function write_cheque ($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		unset($_POST["back"]);
		return alloc($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($overpay, "float", 1, 15, "Invalid unallocated payment amount.");
	$v->isOk ($setamt, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setvat, "string", 1, 10, "Invalid Settlement VAT Option.");
	$v->isOk ($setvatcode, "string", 1, 40, "Invalid Settlement VAT code");
	$v->isOk ($supid, "num", 1, 10, "Invalid supplier number.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(current).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");
	$v->isOk ($process_type, "string", 1, 6, "Invalid Payment Process Type.");

	if(isset($invids)) {
		foreach($invids as $key => $value){
   			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No.");
			$v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	db_connect ();

	$get_sup = "SELECT supname FROM suppliers WHERE supid = '$supid' LIMIT 1";
	$run_sup = db_exec($get_sup) or errDie ("Unable to get supplier information.");
	if (pg_numrows($run_sup) < 1){
		return "<li class='err'>Supplier information not found.</li>";
	}

	unset ($_POST["process_type"]);
	$suparr = pg_fetch_array($run_sup);

	$sqlkey = "";
	$sqlval = "";
	$send_post_vars = "";
	foreach ($_POST AS $key => $value){
		if(($key == "all") OR ($key == "OUT1") OR ($key == "OUT2") OR ($key == "OUT3") OR ($key == "OUT4") OR ($key == "OUT5")){
			$newval = strtolower($key)."_val";
			$_POST[$newval] = $value;
		}
		if (!is_array ($value)){
			$send_post_vars .= "<input type='hidden' name='$key' value='$value'>\n";

			if(($key == "all") OR ($key == "OUT1") OR ($key == "OUT2") OR ($key == "OUT3") OR ($key == "OUT4") OR ($key == "OUT5"))
				$key = $key ."_val";

			$sqlkey .= "$key,";
			$sqlval .= "'$value',";
		}else {
			$sqlkey .= "$key,";
			$sqlval .= "'";
			foreach ($value AS $valkey => $valvalue){
				$send_post_vars .= "<input type='hidden' name='"."$key"."[$valkey]' value='$valvalue'>\n";
				$sqlval .= "$valvalue|";
			}
			$sqlval .= "',";
		}
	}

	$sqlkey = substr($sqlkey,0,-1) . ",printed,done,supname";
	$sqlval = substr($sqlval,0,-1) . ",'yes','no','$suparr[supname]'";


	$do_auto = FALSE;
	$do_manu = FALSE;
	$do_expo = FALSE;

	$pay_type = getCSetting("SUPP_PAY_TYPE");

	if (!isset($pay_type) OR strlen ($pay_type) < 1)
		$pay_type = "cheq_man";

	if ($pay_type == "cheq_man"){
		$do_manu = TRUE;
	}elseif ($pay_type == "export"){
		$do_expo = TRUE;
	}else {
		$do_auto = TRUE;
	}


	if ($process_type == "batch"){
		$write_sql = "INSERT INTO supp_payment_cheques ($sqlkey) VALUES ($sqlval)";
		$run_sql = db_exec($write_sql) or errDie ("Unable to record payment details.");
	}else {
		write ($_POST);
	}

	$checkdate = getCSetting("SUPP_PAY_DATE");
	if(!isset($checkdate) OR strlen($checkdate) < 1){
		#no date ... insert
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'SUPP_PAY_DATE', 'Last Supplier Payment Date Used', '$date', 'general', 'string', '10', '10', '0', 'n'
			);";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record supplier payment date information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$date' WHERE constant = 'SUPP_PAY_DATE'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update supplier payment date setting.");
	}

	header ("Location: ../supp-view.php");

//	$supname = "some name";


			#we have all the vars ... write to cheque table ...
			$write_sql = "INSERT INTO supp_payment_cheques ($sqlkey) VALUES ($sqlval)";
//			$run_sql = db_exec($write_sql) or errDie ("Unable to record payment details.");


}


function sage($supid, $days)
{

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current outstanding
	db_conn("cubit");
	$sql = "SELECT sum(balance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] += 0);

}



function recordDT($amount, $supid, $edate, $age="0")
{

	db_connect();

	$py = array();
	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid > 0 AND balance > 0 OR supid = '$supid' AND intpurid > 0 AND balance > 0 ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					if($dat['purid'] > 0){
						$py[] = "$dat[id]|$dat[purid]|$amount|$dat[pdate]";
					}else{
						$py[] = "$dat[id]|$dat[intpurid]|$amount|$dat[pdate]";
					}
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
						if($dat['purid'] > 0){
							$py[] = "$dat[id]|$dat[purid]|$dat[balance]|$dat[pdate]";
						}else{
							$py[] = "$dat[id]|$dat[intpurid]|$dat[balance]|$dat[pdate]";
						}
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d", mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div $extra1
				) VALUES (
					'$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

		$sql = "
			INSERT INTO suppurch (
				supid, purid, pdate, balance, div $extra1
			) VALUES (
				'$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2
			)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	return $py;

}


?>
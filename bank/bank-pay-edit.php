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

// Merge get vars and post vars
foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

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
			$OUTPUT = add();
	}
} else {
        # Display default output
        $OUTPUT = add();
}

require("../template.php");



# Insert details
function add()
{

	extract($_REQUEST);

	$id+=0;

	$Sl="SELECT * FROM cubit.batch_cashbook WHERE cashid='$id'";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)<1) {
		return "Invalid";
	}

	$bcb = pg_fetch_array($Ri);

	if ($bcb["chrgvat"] == "exc") {
		$bcb["amount"] -= $bcb["vat"];
	}

	extract($bcb, EXTR_SKIP);

	# Accounts Drop down selections
	core_connect();

	# Income accounts ($inc)
	$glacc = "<select name='accinv'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	$numrows = pg_numrows($accRslt);
	if(empty($numrows)){
		$glacc = "<li class='err'>There are no Income accounts yet in Cubit.</li>";
	}

	$account=$accinv;
    while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
		if(isset($account)&&$account==$acc['accid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
    }
    $glacc .="</select>";

	db_connect();
    $sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
    $banks = db_exec($sql);
    if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
    }
	$bank = "<select name='bankid'>";
    while($acc = pg_fetch_array($banks)){
		if(isset($bankid)&&$bankid==$acc['bankid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$bank .= "<option value=$acc[bankid] $sel>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
    }
	$bank .= "</select>";

	if(!isset($name)) {
		$name="";
		$descript="";
		$reference="";
		$cheqnum="";
		$amount="";
		$vatcodes="2";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name=vatcode>
	<option value='0'>Select</option>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['id'] == $vatcode) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes.="</select>";

	explodeDate($date, $o_year, $o_month, $o_day);

	$sel1 = "";
	$sel2 = "";
	$sel3 = "";

	if ($chrgvat == "inc") {
		$sel1 = "checked=yes";
	} else if ($chrgvat == "exc") {
		$sel2 = "checked=yes";
	} else if ($chrgvat == "nov") {
		$sel3 = "checked=yes";
	} else {
		$sel1 = "checked=yes";
	}

	# layout
	$add = "
				<h3>Edit Bank Payment</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm' />
					<input type='hidden' name='id' value='$id' />
					<input type='hidden' name='vat' value='$bcb[vat]' />
					<input type='hidden' name='orig_vatcode' value='$bcb[vatcode]' />
					<input type='hidden' name='orig_chrgvat' value='$bcb[chrgvat]' />
					<input type='hidden' name='orig_amount' value='$bcb[amount]' />
				<table ".TMPL_tblDflts." width='80%'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Bank Account</td>
						<td valign='center'>$bank</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Date</td>
						<td>".mkDateSelect("o", $o_year, $o_month, $o_day)."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Paid to</td>
						<td valign='center'><input size='20' name='name' value='$name'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td valign='top'>Description</td>
						<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
					</tr>
					<tr class='".bg_class()."'>
						<td valign='top'>Reference</td>
						<td valign='center'><input size='20' name='reference' value='$reference'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Cheque Number</td>
						<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Amount</td>
						<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='".sprint($amount)."'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>VAT </td>
						<td>
							<input type='radio' name='chrgvat' value='inc' $sel1>Inclusive &nbsp;
							<input type='radio' name='chrgvat' value='exc' $sel2>Exclusive &nbsp;
							<input type='radio' name='chrgvat' value='nov' $sel3>No VAT
						</td>
					<tr class='".bg_class()."'>
						<td>VAT Code</td>
						<td>$Vatcodes</td>
					</tr>
					<tr class='".bg_class()."'>
						<td valign=top>Select Contra Account</td>
						<td>$glacc</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td><input type='button' onClick='javascript:history.back();' value='&laquo Correction'>&nbsp</td>
						<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
					</tr>
				</table>";

        # main table (layout with menu)
        $OUTPUT = "
					<center>
					<table width=100%>
						<tr>
							<td width=65% align='left'>$add</td>
							<td valign='top' align='center'>"
								.mkQuickLinks()."
							</td>
						</tr>
					</table>";
		return $OUTPUT;

}

# confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		header("Location: cashbook-entry.php");
		exit;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to.");
	$v->isOk ($reference, "string", 0, 255, "Invalid Reference.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 40, "Invalid amount.");
	$v->isOk ($vat, "float", 1, 40, "Invalid vat amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Account type (account involved).");

	$v->isOk ($o_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($o_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($o_year, "num", 1,4, "Invalid Date Year.");

	$date = mkdate($o_year, $o_month, $o_day);
	$v->isOk ($date, "date", 1, 1, "Invalid date.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}

		return $confirm.add($_POST);
	}



	# Get bank account name
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='".getPRDDB($date)."' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);

	# Get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv);
	$accnt = pg_fetch_array($accRslt);

	$totamt = $amount;

	vsprint($vat);

	if ($vatcode != $orig_vatcode || $amount != $orig_amount || $chrgvat != $orig_chrgvat) {
		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
		$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

		$vd = pg_fetch_array($Ri);
		$vatp = $vd['vat_amount'];

		if ($chrgvat == "exc") {
			$vat = sprint(($vatp / 100) * $amount);
		} else if ($chrgvat == "inc") {
			$vat = sprint($amount * $vatp / ($vatp + 100));
		} else {
			$vat = 0;
		}
	}

	if ($chrgvat == "exc") {
		$totamt += $vat;
		$vatin = CUR."<input type='text' name='vat' value='$vat' />";
	} else if ($chrgvat == "inc") {
		$vatin = CUR."<input type='text' name='vat' value='$vat' />";
	} else {
		$vatin = "No VAT";
	}

	$confirm = "
					<center>
					<h3>Edit Bank Payment</h3>
					<h4>Confirm entry (Please check the details)</h4>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write' />
						<input type='hidden' name='bankid' value='$bankid' />
						<input type='hidden' name='date' value='$date' />
						<input type='hidden' name='name' value='$name' />
						<input type='hidden' name='reference' value='$reference' />
						<input type='hidden' name='descript' value='$descript' />
						<input type='hidden' name='cheqnum' value='$cheqnum' />
						<input type='hidden' name='amount' value='$amount' />
						<input type='hidden' name='chrgvat' value='$chrgvat' />
						<input type='hidden' name='accinv' value='$accinv' />
						<input type='hidden' name='vat' value='$vat' />
						<input type='hidden' name='vatcode' value='$vatcode' />
						<input type='hidden' name='id' value='$id' />
					<table ".TMPL_tblDflts." width='60%'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account</td>
							<td>$bank[accname] - $bank[bankname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Balance</td>
							<td>".CUR." $bal[bal]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Paid to</td>
							<td valign='center'>$name</td>
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
							<td valign='center'>".CUR." ".sprint($totamt)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT </td>
							<td>$vatin</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Ledger Account Paid To</td>
							<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
						</tr>
						".TBL_BR."
						<tr>
							<td>&nbsp;</td>
							<td align='right'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' name='back' value='&laquo Correction'></td>
							<td align='right'><input type='submit' name='batch' value='Edit Batch &raquo'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Write &raquo'></td>
						</tr>
					</table>
					</form>"
					.mkQuickLinks();
	return $confirm;

}



# write
function write($_POST)
{

	# processes
	db_connect();

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return add($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to/received from.");
	$v->isOk ($reference, "string", 0, 255, "Invalid Reference.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 40, "Invalid amount.");
	$v->isOk ($vat, "float", 1, 10, "Invalid vat amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "string", 1, 255, "Invalid account number (account involved).");

	$vatcode+=0;

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



	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

	#refnum
	$refnum = getrefnum();

	# Start rattling vat
//	$vatp = TAX_VAT;
	$totamt = $amount;

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$vatcode' AND zero='Yes'";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	if(pg_num_rows($Ri)>0) {
		$chrgvat="no";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$vd=pg_fetch_array($Ri);
	$vatp = $vd['vat_amount'];

	if($chrgvat == "exc"){
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		//$vat=sprint((sprint($amount*100/(100+$vatp)))*$vatp/100);
		$amount=sprint($totamt-$vat);
		//$vat = sprint(($amount/(100 + $vatp)) * $vatp);
	}else{
		$vat = "No VAT";
	}

	/*if($chrgvat == "exc"){
		$vat = sprint(($vatp/100) * $amount);
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		$vat=sprint((sprint($amount*100/(100+$vatp)))*$vatp/100);
		$amount=sprint($amount*100/(100+$vatp));
		//$vat = sprint(($amount/(100 + $vatp)) * $vatp);
	}else{
		$vat = "No VAT";
	}

// 	if($chrgvat == "exc"){
// 		$vat = sprint(($vatp/100) * $amount);
// 		$totamt += $vat;
// 	} elseif($chrgvat == "inc"){
// 		//$vat = sprint((sprint($amount/(100 + $vatp))) * $vatp);
// 		$vat = sprint((sprint($amount/114*100)*$vatp/100));
// 		$amount = sprint($amount/114*100);
// 	}else{
// 		$vat = 0;
// 	}

	/* -- Start Hooks -- */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

		# Get hook account number
		core_connect();
		$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
		# Check if link exists
		if(pg_numrows($rslt) <1){
			return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
		}
		$banklnk = pg_fetch_array($rslt);

	/* -- End Hooks -- */

	$cheqnum = 0 + $cheqnum;
	$vat+=0;
	$vatcode+=0;

	if(isset($batch)) {

		db_conn('cubit');

		$Sl="UPDATE batch_cashbook SET bankid='$bankid', date='$date', name='$name', descript='$descript', reference = '$reference', cheqnum='$cheqnum', amount='$totamt', vat='$vat', chrgvat='$chrgvat', accinv='$accinv', vatcode='$vatcode' WHERE cashid = '$id'";
		$Ri=db_exec($Sl) or errDie("Unable to update cashbook");

		# Status report
		$write = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>Bank Payment</th>
						</tr>
						<tr class='datacell'>
							<td>Bank Payment edited.</td>
						</tr>
					</table>";

	}else {

		# Record the payment record

		db_connect();

		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, reference, vatcode) VALUES ('$bankid', 'withdrawal', '$date', '$name', '$descript', '$cheqnum', '$totamt', '$vat', '$chrgvat', 'no', '$accinv', '".USER_DIV."', '$reference', '$vatcode')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

		vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descript,-($amount+$vat),-$vat);

		# DT(account involved), CT(bank)
		writetrans($accinv, $banklnk['accnum'], $date, $refnum, $amount, $descript);

		if($vat <> 0){
			# DT(VAT), CT(Bank)
			writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $descript);
		}

		if(cc_TranTypeAcc($accinv, $banklnk['accnum']) != false){
			$cc_trantype = cc_TranTypeAcc($accinv, $banklnk['accnum']);
			$cc = "<script> CostCenter('$cc_trantype', 'Bank Transaction', '$date', '$descript', '$amount', '../'); </script>";
		}else{
			$cc = "";
		}

		$dif=sprint(($amount+$vat)-$totamt);

		if($dif>0) {
			writetrans($banklnk['accnum'], $varacc, $date, $refnum, $dif, "Variance on bank payment Ref: $refnum");
		} elseif($dif<0) {
			$dif=$dif*-1;
			writetrans($varacc, $banklnk['accnum'], $date, $refnum, $dif, "Variance on bank payment Ref: $refnum");
		}

		# Status report
		$write = "
					$cc
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>Bank Payment</th>
						</tr>
						<tr class='datacell'>
							<td>Bank Payment added to cash book.</td>
						</tr>
					</table>";

	}

	# Main table (layout with menu)
	$OUTPUT = "
				<center>
				<table width='90%'>
					<tr valign='top'>
						<td width='50%'>$write</td>
						<td align='center'>"
							.mkQuickLinks(
								ql("bank-pay-add.php", "Add Bank Payment"),
								ql("bank-recpt-add.php", "Add Bank Receipt"),
								ql("cashbook-view.php", "View Cash Book"),
								ql("batch-cashbook-view.php", "View Batch Cashbook")
							)."
						</td>
					</tr>
				</table>";
	return $OUTPUT;

}


?>
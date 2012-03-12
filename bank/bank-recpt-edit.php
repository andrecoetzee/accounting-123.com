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
			$OUTPUT = add();
	}
} else {
	# Display default output
	$OUTPUT = add();
}

require("../template.php");



function add()
{

	extract($_REQUEST);

	$qry = new dbSelect("batch_cashbook", "cubit", grp(
		m("where", wgrp(
			m("cashid", "$id")
		))
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("Invalid batch cashbook entry.");
	}

	$bcb = $qry->fetch_array();

	if ($bcb["chrgvat"] == "exc") {
		$bcb["amount"] -= $bcb["vat"];
	}

	extract($bcb, EXTR_SKIP);

	core_connect();
	$accs = qryAccounts();

	if ($accs->num_rows() <= 0) {
		 $glacc = "There are no Income accounts in Cubit.";
	}

	$glacc = "<select name='accinv'>";
	while ($acc = $accs->fetch_array()){
		if (isDisabled($acc['accid'])) {
			continue;
		}

		if ($accinv == $acc['accid']) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$glacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
	}

	$glacc .= "</select>";

	$OUT = "
				<h3>Edit Bank Receipt</h3>
				<table ".TMPL_tblDflts." width='100%'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
					<input type='hidden' name='vat' value='$bcb[vat]' />
					<input type='hidden' name='orig_vatcode' value='$bcb[vatcode]' />
					<input type='hidden' name='orig_chrgvat' value='$bcb[chrgvat]' />
					<input type='hidden' name='orig_amount' value='$bcb[amount]' />
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank Account</td>
						<td valign='center'>
							<select name='bankid'>";

	db_connect();
	$qry->setTable("bankacct", "cubit");
	$qry->setOpt(grp(
		m("where", "btype!='int' AND div='".USER_DIV."'")
	));
	$qry->run();

	if($qry->num_rows() <= 0){
			return "<li class='err'> There are no accounts held at the selected Bank.
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = $qry->fetch_array()){
		if (isset($bankid) && $bankid == $acc['bankid']) {
			$sel="selected";
		} else {
			$sel="";
		}

		$OUT .= "<option value='$acc[bankid]' $sel>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	if (!isset($name)) {
		$name = "";
		$descript = "";
		$cheqnum = "";
		$amount = "";
		$chrgvat = "";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
			<select name='vatcode'>
				<option value='0'>Select</option>";

	$vacs = qryVatcode();
	$Vatcodes = db_mksel($vacs, "vatcode", $vatcode, "#id", "#code", "0:Select");

	list($o_year, $o_month, $o_day) = explode('-', $date);

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

	$OUT .= "
		</select>
		</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Date</td>
		<td>
			".mkDateSelect("o", $o_year, $o_month, $o_day)."
		</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Received from</td>
		<td valign='center'><input size='20' name='name' value='$name'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Description</td>
		<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Reference</td>
		<td valign='center'><input size='20' name='reference' value='$reference'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Cheque Number</td>
		<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Amount</td>
		<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='".sprint($amount)."'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>VAT </td>
		<td>
			<input type='radio' name='chrgvat' value='inc' $sel1>Inclusive &nbsp;&nbsp;
			<input type='radio' name='chrgvat' value='exc' $sel2>Exclusive &nbsp;&nbsp;
			<input type='radio' name='chrgvat' value='nov' $sel3>No VAT
		</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>VAT Code</td>
		<td>$Vatcodes</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td valign='top'>Select Contra Account</td>
		<td>$glacc</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
	</tr>
	</table>";

	# main table (layout with menu)
	$OUT .= mkQuickLinks();
	return $OUT;

}



# Confirm
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
	$v->isOk ($o_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($o_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($o_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business paid to/received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Account involved.");
	$date = mkdate($o_year, $o_month, $o_day);

	$v->isOk ($date, "date", 1, 1, "Invalid date.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return $err.add($_POST);
	}



	# Start rattling vat

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

	$OUT = "
			<center>
			<h3>Edit Bank Receipt</h3>
			<h4>Confirm entry (Please check the details)</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write' />
				<input type='hidden' name='id' value='$id' />
				<input type='hidden' name='bankid' value='$bankid' />
				<input type='hidden' name='date' value='$date' />
				<input type='hidden' name='name' value='$name' />
				<input type='hidden' name='descript' value='$descript' />
				<input type='hidden' name='reference' value='$reference' />
				<input type='hidden' name='cheqnum' value='$cheqnum' />
				<input type='hidden' name='amount' value='$amount' />
				<input type='hidden' name='chrgvat' value='$chrgvat' />
				<input type='hidden' name='accinv' value='$accinv' />
				<input type='hidden' name='vatcode' value='$vatcode' />";

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
		return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='".getPRDDB($date)."' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);

	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv);
	$accnt = pg_fetch_array($accRslt);

	$OUT .= "
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account</td>
						<td>$bank[accname] - $bank[bankname]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Balance</td>
						<td>".CUR." $bal[bal]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td valign='center'>$date</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Received from</td>
						<td valign='center'>$name</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Description</td>
						<td valign='center'>$descript</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Reference</td>
						<td valign='center'>$reference</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Cheque Number</td>
						<td valign='center'>$cheqnum</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Amount</td>
						<td valign='center'>".CUR." ".sprint($totamt)."</td>
					</tr>
					<tr bgcolor='".TMPL_tblDataColor1."'>
						<td>VAT </td>
						<td>$vatin</td>
					</tr>
					<tr bgcolor='".TMPL_tblDataColor2."'>
						<td>Ledger Account Received from</td>
						<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
					</tr>
				 	".TBL_BR."
					<tr>
						<td>&nbsp;</td>
						<td align='right'><input type='submit' value='Write &raquo'></td>
					</tr>
				</form>
				</table>".
	mkQuickLinks();
	return $OUT;

}



# write
function write($_POST)
{

	# Processes
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
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($reference, "string", 0, 255, "Invalid Reference.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid vat option.");
	$v->isOk ($accinv, "string", 1, 255, "Invalid account number (account involved).");

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


	# Date format

	# Refnum
	$refnum = getrefnum($date);
// AND zero='Yes'

	# Start rattling vat
//	$vatp = TAX_VAT;
	$totamt = $amount;
	if($chrgvat == "exc"){
		$totamt += $vat;
	} elseif($chrgvat == "inc"){
		$amount -= $vat;
	}else{
		$vat = 0;
	}

	/* -- Start Hooks -- */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT", "VAT");

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

	db_connect();

	$sql = "UPDATE batch_cashbook SET bankid='$bankid',date='$date',name='$name',
				descript='$descript',cheqnum='$cheqnum',amount='$totamt',vat='$vat',
				chrgvat='$chrgvat',accinv='$accinv',reference='$reference'
			WHERE cashid='$id'";
	db_exec($sql);

	$write = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Bank Receipt</th>
					</tr>
					<tr class='datacell'>
						<td>Bank Receipt edited.</td>
					</tr>
				</table>";


	# main table (layout with menu)
	$OUTPUT = "
				<center>
				<table width = 90%>
					<tr valign='top'>
						<td width='50%'>$write</td>
						<td align='center'>"
							.mkQuickLinks(
								ql("bank-pay-add.php", "Add Bank Payment"),
								ql("bank-recpt-add.php", "Add Bank Receipt"),
								ql("cashbook-view.php", "View Cash Book"),
								ql("batch-cashbook-view.php", "View Batch Cashbook")
							)."
						</td></tr>
				</table>";
	return $OUTPUT;

}


?>
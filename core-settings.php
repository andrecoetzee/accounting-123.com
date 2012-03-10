<?
/**
 * Generally used functions/constants, login logic also
 * @package Cubit
 * @subpackage CoreSettings
 */

# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "core-settings.php") {
	exit;
}

require("uselog.php");

$allowed = array("yr-close.php", "set-bal-sheet-edit.php", "set-bal-sheet.php");

# Get all diabled accounts
db_conn("exten");
$sql = "SELECT debtacc,credacc FROM departments";
$rs = db_exec($sql);
while($dis = pg_fetch_array($rs)){
	$DISABLE[] = $dis['credacc'];
	$DISABLE[] = $dis['debtacc'];
}
$sql = "SELECT stkacc FROM warehouses";
$disRslt = db_exec($sql) or errDie("Could not retrieve warehouses Information from the Database.",SELF);
while($dis = pg_fetch_array($disRslt)){
	$DISABLE[] = $dis['stkacc'];
}

db_conn('cubit');
$sql = "SELECT value FROM set WHERE label = 'BLOCK'";
$Ri=db_exec($sql);

$data=pg_fetch_array($Ri);

$blocked = array();

if ( $data['value'] == "use" ) {
	db_conn('core');
	$Sl = "SELECT accid FROM trial_bal WHERE vat='t'";
	$Ri = db_exec($Sl);

	while ( $data = pg_fetch_array($Ri) ) {
		$blocked[] = $data['accid'];
	}
}

/**
 * account types: all accounts
 *
 */
define("ACCTYPE_ALL", false);

/**
 * account types: balance accounts
 *
 */
define("ACCTYPE_B", 0x01);

/**
 * account types: expense accounts
 *
 */
define("ACCTYPE_E", 0x02);

/**
 * account types: income accounts
 *
 */
define("ACCTYPE_I", 0x04);


/**
 * account types: income/expense accounts
 *
 */
define("ACCTYPE_IE", 0x08);

/**
 * functions
 */

/**
 * returns current year net profit/loss
 *
 * @param int $div branch id
 * @param year $year optionally a previous year schema name
 * @return unknown
 */
function getNetProfit($div = USER_DIV, $year = "core") {
	$sql = "SELECT SUM(tb.credit)-SUM(tb.debit) AS netprofit
			FROM core.accounts acc LEFT JOIN $year.trial_bal tb
				ON acc.accid=tb.accid AND acc.div=tb.div
			WHERE (acc.acctype='I' OR acc.acctype='E') AND acc.div='$div'
				AND tb.period='12'";

	$rslt = db_exec($sql) or errDie("Error calculating current year profit or loss (QR).");

	return sprint(pg_fetch_result($rslt, 0, 0));
}

/**
 * returns a previous year net profit/loss
 * 
 * function can be ignored and getNetProfit() can be used instead.
 *
 * @param int $div branch id
 * @ignore
 * @param year $year optionally a previous year schema name
 * @return unknown
 */
function getNetProfit_py($div = USER_DIV, $year = YR_DB) {
	$sql = "SELECT SUM(yb.credit)-SUM(yb.debit) AS netprofit
			FROM core.accounts acc LEFT JOIN $year.year_balance yb
				ON acc.accid=yb.accid AND acc.div=yb.div
			WHERE (acc.acctype='I' OR acc.acctype='E') AND acc.div='$div'";
	$rslt = db_exec($sql) or errDie("Error calculating previous year profit or loss (QR).");

	$p = sprint(pg_fetch_result($rslt, 0, 0));
	return $p;
}

/**
 * records a vat report entry
 *
 * @param int $cid
 * @param string $date
 * @param string $type
 * @param string $code
 * @param int $ref
 * @param string $description
 * @param float $amount
 * @param float $vat
 */
function vatr($cid, $date, $type, $code, $ref, $description, $amount, $vat)
{

	$amount += 0;
	$cid += 0;

	if ($amount != 0 && $cid != 0) {
		db_conn('cubit');

		$sdate = date("Y-m-d");

		$cid += 0;

		$Sl = "
			INSERT INTO vatreport (
				cid, date, sdate, type, code, ref, description, amount, vat
			) VALUES (
				'$cid', '$date', '$sdate', '$type', '$code', '$ref', '$description', '$amount', '$vat'
			)";
		$Ri = db_exec($Sl) or errDie("unable to isert vat record.$Sl");
	}

}

/**
 * returns vatcode id from vatcode number
 *
 * @param int $codenum vatcode number
 * @return int
 */
function vatcode($codenum) {
	if (($vc = qryVatcodeC($codenum)) === false) {
		errDie("Vatcode $codenum not found.");
	} else {
		return $vc["id"];
	}
}

/**
 * returns vat amount from vatcode number
 *
 * @param int $id vatcode id
 * @return int
 */
function vatamountID($id) {
	if (($vc = qryVatcode($id)) === false) {
		return TAX_VAT;
	} else {
		return $vc["vat_amount"];
	}
}

/**
 * returns vat amount from vatcode number
 *
 * @param int $codenum vatcode number
 * @return int
 */
function vatamountCODE($codenum) {
	if (($vc = qryVatcodeC($codenum)) === false) {
		return TAX_VAT;
	} else {
		return $vc["vat_amount"];
	}
}

/**
 * alias for getMonthName()
 *
 * @param int $month
 * @return string
 */
function prdname($prd){
	return getMonthName($prd);
}

/**
 * returns next available reference number
 *
 * @return int
 */
function getrefnum(){
	$rn = new dbSelect("max_refnum", "core", grp(
		m("cols", "max(refnum)")
	));
	$rn->run();

	return $rn->fetch_result() + 1;
}

/**
 * returns bank ledger account id from bank account
 *
 * @param int $bankid
 * @return int
 */
function getbankaccid($bankid){
	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) < 1){
		errDie("<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.");
	}

	if (($banklnk = pg_fetch_array($rslt)) !== false) {
		return $banklnk['accnum'];
	} else {
		return false;
	}
}

/**
 * returns a bank account id for bank account linked to specified ledger account
 *
 * @param int $accid
 * @return int
 */
function getbankid($accid){
	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accnum = '$accid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) < 1){
		errDie("<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.");
	}
	$banklnk = pg_fetch_array($rslt);

	return $banklnk['accid'];
}

/**
 * checks if account is linked to bank account
 *
 * @param int $accid
 * @return bool
 */
function isbank($accid){
	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accnum = '$accid' AND accid != 0 AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve check bank account link.",SELF);

	# Check if link exists
	if(pg_numrows($rslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function isBankRec($accid, $trantype, $date, $name, $descript, $cheqnum, $totamt, $accinv, $chrgvat = "nov", $vat = 0){
	# Date format
	$date = explode("-", $date);
	$date = "$date[2]-$date[1]-$date[0]";

	if(isbank($accid)){
		$bankid = getbankid($accid);
		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, 
				amount, vat, chrgvat, banked, accinv, div
			) VALUES (
				'$bankid', '$trantype', '$date', '$name', '$descript', '$cheqnum', 
				'$totamt', '$vat', '$chrgvat', 'no', '$accinv', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}
}

/**
 * @ignore
 */
function isBankmRec($accid, $trantype, $date, $name, $descript, $cheqnum, $totamt, $accinv, $amounts, $accids, $vats, $chrgvats){
	# Date format
	$date = explode("-", $date);
	$date = "$date[2]-$date[1]-$date[0]";

	if(isbank($accid)){
		$bankid = getbankid($accid);
		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO cashbook (
				bankid, trantype, date, name, descript, cheqnum, 
				amount, vat, chrgvat, banked, accids, amounts, 
				chrgvats, vats, div
			) VALUES (
				'$bankid', '$trantype', '$date', '$name', '$descript', '$cheqnum', 
				'$totamt', '0', 'nov', 'no', '$accids', '$amounts', 
				'$chrgvats', '$vats', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}
}


/**
 * checks if account is linked to petty cash account
 *
 * @param int $accid
 * @return bool
 */
function ispetty($accid){
	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accnum = '$accid' AND name = 'Petty Cash' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve check bank account link.",SELF);

	# Check if link exists
	if(pg_numrows($rslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function pettyrec($accid, $date, $type, $descript, $amount, $details){
	if($type == 'dt'){
		$typedef = 'Transfer';
	}else{
		$typedef = 'Req';
		$amount = -$amount;
	}

	if(ispetty($accid)){
		db_connect();
		# Record tranfer for patty cash report
		$sql = "
			INSERT INTO pettyrec (
				date, type, det, amount, name, div
			) VALUES (
				'$date', '$typedef', '$descript', '$amount', '$details', '".USER_DIV."'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}
}

/**
 * @ignore
 */
function intInvoice($cusnum, $total,$account)
{
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cus = pg_fetch_array($custRslt);

	# Default data
	$sdate = date("Y-m-d");
	$invnum = divlastid('inv', USER_DIV);

	db_connect();
	# Insert purchase to DB
	$sql = "
		INSERT INTO nons_invoices (
			cusid, cusname, cusaddr, cusvatno, chrgvat, 
			sdate, subtot, balance, vat, total, 
			done, username, prd, invnum, typ, 
			descrip, div, ctyp, odate
		) VALUES (
			'$cus[cusnum]', '$cus[surname]', '$cus[addr1]', '$cus[vatnum]', 'none', 
			'$sdate', '$total', '$total', 0, '$total', 
			'y', '".USER_NAME."', '".PRD_DB."', '$invnum', 'inv', 
			'Interest Charged', '".USER_DIV."', 's', '$sdate'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create Interest Invoice.",SELF);

	# Get next ordnum
	$invid = lastinvid();

	$Sl = "SELECT * FROM vatcodes WHERE zero='Yes'";
	$Ri = db_exec($Sl);

	$vd = pg_fetch_array($Ri);

	# Insert purchase items
	$sql = "
		INSERT INTO nons_inv_items (
			invid, qty, amt, unitcost, description, 
			div, vatex, accid
		) VALUES (
			'$invid', '1', '$total', '$total', 'Interest on Outstanding Invoices.', 
			'".USER_DIV."','$vd[id]','$account'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

	return $invnum;
}

/**
 * @ignore
 */
function fintInvoice($cusnum, $total, $rate)
{

	$ftotal = sprint($total * $rate);

	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	$cus = pg_fetch_array($custRslt);

	$curr = getSymbol($cus['fcid']);
	$xrate = getRate ($cus['fcid']);

	# Default data
	$sdate = date("Y-m-d");
	$invnum = divlastid('inv', USER_DIV);

	db_connect();
	# Insert purchase to DB
	$sql = "
		INSERT INTO nons_invoices (
			cusid, cusname, cusaddr, cusvatno, chrgvat, 
			fcid, currency, xrate, sdate, subtot, 
			balance, fbalance, vat, total, done, 
			username, prd, invnum, typ, ctyp, 
			tval, location, descrip, div, odate
		) VALUES (
			'$cus[cusnum]', '$cus[cusname] $cus[surname]', '$cus[addr1]', '$cus[vatnum]', 'none', 
			'$cus[fcid]', '$curr[symbol]', '$xrate', '$sdate', '$total', 
			'$ftotal', '$total', 0, '$total', 'y', 
			'".USER_NAME."', '".PRD_DB."', '$invnum', 'inv', 's', 
			'$cusnum', 'int', 'Interest Charged', '".USER_DIV."', '$sdate'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create Interest Invoice.",SELF);

	# Get next ordnum
	$invid = lastinvid();

	# Insert purchase items
	$sql = "
		INSERT INTO nons_inv_items (
			invid, qty, amt, cunitcost, unitcost, 
			description, div
		) VALUES (
			'$invid', '1', '$total', '$ftotal', '$total', 
			'Interest on Outstanding Invoices.', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

	return $invnum;
}

/**
 * @ignore
 */
function core_createcat($catname, $div, $type) {
	core_connect();

	# In case no upper case
	$type = strtoupper($type);

	switch($type){
		case "I":
			$tab = "income";
			break;

		case "B":
			$tab = "balance";
			break;

		case "E":
			$tab = "expenditure";
			break;

		default:
			return "<li> Invalid Category type";
	}

	# Make seq
	$seq = $tab."_seq";

	# Insert Category
	$sql = "INSERT INTO $tab (catid, catname, div) VALUES ('$type' || nextval('$seq'), '$catname', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("$seq");
	$catid = $type.$catid;

	return $catid;
}

/**
 * @ignore
 */
function core_createacc($topacc, $accnum, $accname, $catid, $acctype, $vat, $div)
{
	global $PRDMON;

	# In case no upper case
	$acctype = strtoupper($acctype);

	core_connect();

	# Check account number on selected branch
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '$div'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 1;
	}

	# Check account name on selected branch
	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '$div'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 2;
	}

	# Write to DB
	$Sql = "
		INSERT INTO accounts (
			topacc, accnum, accname, acctype, catid, div
		) VALUES (
			'$topacc', '$accnum', '$accname', '$acctype', '$catid', '$div'
		)";
	$accRslt = db_exec ($Sql) or errDie ("Unable to add Account to Database.", SELF);

	# Get last inserted id for new acc
	$accid = pglib_lastid ("accounts", "accid");

	# Insert account into trial Balance
	foreach ($PRDMON as $map_prd => $map_mon) {
		$query = "
			INSERT INTO trial_bal (
				accid, topacc, accnum, accname, div, month, period
			) VALUES (
				'$accid', '$topacc', '$accnum', '$accname', '$div', '$map_mon', '$map_prd'
			)";
		$trialRslt = db_exec($query) or errDie ("Unable to add Account to Database.", SELF);
	}

	# Return Zero on success
	return 0;
}

/**
 * checks if an account exists
 *
 * @param int $accid
 * @return bool
 */
function accExist($accid){
	$tranRs = get("core", "accid", "accounts", "accid", $accid);
	if(pg_numrows($tranRs) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function getSetDes($label){
	$label = strtoupper($label);
	$setRs = undget("cubit", "descript", "set", "upper(label)", $label);
	if(pg_numrows($setRs) > 0){
		$set = pg_fetch_array($setRs);
		return $set['descript'];
	}
}

/**
 * @ignore
 */
function getErrAcc(){
	# Get error account
	$errRs = undget("core", "*", "accounts", "topacc", "999' AND accnum = '998' AND div = '".USER_DIV);
	if(pg_numrows($errRs) < 1){
		$catid = core_createcat("ERROR ACCOUNTS", USER_DIV, "B");
		if(core_createacc("999", "998", "Error Transactions", $catid, "B", "f", USER_DIV) != 0){
			errDie ("Unable to create Error Trnsactions account.");
		}
		$errRs = undget("core", "*", "accounts", "topacc", "999' AND accnum = '998' AND div = '".USER_DIV);
	}
	$err = pg_fetch_array($errRs);

	return $err['accid'];
}

/**
 * @ignore
 */
function getAcc($accid){
	# Get Error account
	$accRs = get("core", "accname, topacc, accnum, acctype", "accounts", "accid", $accid);
	$acc = pg_fetch_array($accRs);
	return $acc;
}

/**
 * @ignore
 */
function getAccn($topacc, $accnum){
	# Get account
	$accRs = undget("core", "*", "accounts", "topacc", "$topacc' AND accnum = '$accnum' AND div = '".USER_DIV);
	if(pg_numrows($accRs) < 1){
		return false;
	}
	$acc = pg_fetch_array($accRs);
	return $acc;
}

/**
 * returns array with PRD_DB and PRD_NAME to use determined by date 
 * 
 * @param string $data
 * @return array
 */
function getPRD($date = false) {
	if ($date === false) $date = date("Y-m-d");
	list($year,$month,$day) = explode("-", $date);

	$PRD_DB = date("n", mktime(0, 0, 0, $month, $day, $year));
	$PRD_NAME = date("F", mktime(0, 0, 0, $month, $day, $year));

	return array($PRD_DB, $PRD_NAME);
}

/**
 * returns which period schema to use for a date
 *
 * @param string $date
 * @return int
 */
function getPRDDB($date = false) {
	if ($date === false) $date = date("Y-m-d");
	return extractMonth($date);
}

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
/**
 * writes a transaction into the general ledger
 *
 * @param int $dtacc debit account id
 * @param int $ctacc credit account id
 * @param string $date
 * @param int $refnum
 * @param float $amount
 * @param string $details description for ledger entry
 */
function writetrans($dtacc, $ctacc, $date, $refnum, $amount, $details) {
	global $uselog;
	global $MONPRD, $PRDMON;
	$amount = sprint($amount);
	if($amount < 0.01) {
		return;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited ($ctacc) for transaction \"$details\".");
	$v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited ($dtacc) for transaction \"$details\".");
	$v->isOk ($date, "date", 1, 14, "Invalid date for transaction \"$details\".");
	$v->isOk ($refnum, "string", 1, 50, "Invalid reference number for transaction \"$details\".");
	$v->isOk ($amount, "float", 1, 40, "Invalid amount $amount for transaction \"$details\".");
	$details = str_replace(":", " ", $details);
	$v->isOk ($details, "string", 0, 2048, "Invalid Details [$details].");
	# Date format
	$date = explode("-", $date);
	// $day ,$mon , $year
	if(checkdate($date[1], $date[0], $date[2])){
		$mon = $date[1];
		$yr = $date[2];
		$date = "$date[2]-$date[1]-$date[0]";
	// $year, $mon, $day
	}elseif(checkdate($date[1], $date[2], $date[0])){
		$mon = $date[1];
		$yr = $date[0];
		$date = "$date[0]-$date[1]-$date[2]";
	}else{
		$v->addError("", "Invalid date for transaction. ".(DEBUG>0?"\"$details\"":""));
	}

	if (isset($yr)) {
		$curyr = getActiveFinYear();
		if ($yr > $curyr || ($yr == $curyr && $mon > $PRDMON[12])) {
			$v->addError("", "Cannot do transaction in future financial year. ".(DEBUG>0?"\"$details\"":""));
		}
	}

	/* start usage log */
	if (empty($uselog["firsttrans"]["date"])) {
		setUsage("firsttrans", "");
	}

	setUsage("lasttrans", "");
	/* end usage log */

	if(floatval($amount) == floatval(0)){
		return;
	}
	
	if ($v->isError()) {
		$OUTPUT = $v->genErrors();
		$OUTPUT .= "<p><input type='button' onclick='javascript:history.back();' value='&laquo; Correct submission'></p>";
		pglib_transaction("ROLLBACK");
		require("template.php");
	}

	if(!accExist($dtacc)){
		$dtacc = getErrAcc();
		$details = $details." - Debit account was not found, transaction has been posted to error account.";
		db_connect();
		$Sql = "INSERT INTO req (sender, recipient, message, timesent, viewed) VALUES ('SYSTEM', '', 'Debit account was not found for transaction with Ref No. $refnum, transaction has been posted to error account.', CURRENT_TIMESTAMP, 0)";
		$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);
	}
	if(!accExist($ctacc)){
		$ctacc = getErrAcc();
		$details = $details." - Credit account was not found, transaction has been posted to error account.";
		db_connect();
		$Sql = "INSERT INTO req (sender, recipient, message, timesent, viewed) VALUES ('SYSTEM', '', 'Credit account was not found for transaction with Ref No. $refnum, transaction has been posted to error account.', CURRENT_TIMESTAMP, 0)";
		$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);
	}

	# Get account information
	$dacc = getAcc($dtacc);
	$cacc = getAcc($ctacc);

	$sdate=date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	# Insert the records into the transect table
	//if (PRD_STATE != "py") {
		db_conn($PRD_DB);
		$sql = "
			INSERT INTO transect (
				debit, daccname, dtopacc, daccnum , credit,
				caccname, ctopacc, caccnum, date, refnum, 
				amount, author, details, div, sdate, custom_refnum
			) VALUES (
				'$dtacc', '$dacc[accname]', '$dacc[topacc]', '$dacc[accnum]', '$ctacc', 
				'$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]', '$date', '".getrefnum()."', 
				'$amount', '".USER_NAME."', '$details', '".USER_DIV."', '$sdate', '$refnum'
			)";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	//}

	# Update the balances by adding appropriate values to the trial_bal Table
	core_connect();

	# Begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	if (PRD_STATE != "py") {
		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount')
				WHERE accid = '$ctacc' AND period>='$MONPRD[$PRD_DB]' AND div = '".USER_DIV."'";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount')
				WHERE accid  = '$dtacc' AND period>='$MONPRD[$PRD_DB]' AND div = '".USER_DIV."'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);
	} else {
		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount')
				WHERE accid = '$ctacc' AND period<'1' AND div = '".USER_DIV."'";
//FP MOVEMENT FIX
//					AND (acctype='I' OR acctype='E')";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount')
				WHERE accid  = '$dtacc' AND period<'1' AND div = '".USER_DIV."'";
//FP MOVEMENT FIX
//					AND (acctype='I' OR acctype='E')";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);
	}

	if (PRD_STATE == "py") {
		db_conn(YR_DB);
		$ctbal = "UPDATE year_balance SET credit=(credit+'$amount') WHERE accid='$ctacc'";
		$dtbal = "UPDATE year_balance SET debit=(debit+'$amount') WHERE accid='$dtacc'";
		//db_exec($ctbal) or errDie("Error updating previous year balance (CT).");
		//db_exec($dtbal) or errDie("Error updating previous year balance (DT).");
	}

	# commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	// insert into audit db
	if (PRD_STATE == "py") {
		$audit_db = YR_NAME . "_audit";
//		$start_prd = 1;
//FP WHY UPDATE WHOLE OF PREVIOUS YEAR ????
		$start_prd = $MONPRD[$PRD_DB];
		$actyear = PYR_NAME;
	} else {
		$audit_db = "audit";
		$start_prd = $MONPRD[$PRD_DB];
		$actyear = YR_NAME;
	}

	db_conn($audit_db);
	$sql = "
		INSERT INTO ".$PRD_NAME." (
			debit, credit, date, refnum, amount, 
			author, details, div, actyear, custom_refnum
		) VALUES (
			'$dtacc', '$ctacc', '$date', '".getrefnum()."', '$amount', 
			'".USER_NAME."', '$details', '".USER_DIV."', '$actyear', '$refnum'
		)";
	$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

	if (true || $MONPRD[$PRD_DB] < $MONPRD[$CUR_PRD_DB]) {
		for ($iPRD = $start_prd; $iPRD <= 12; ++$iPRD) {

			//print "wt: $iPRD - $PRDMON[$iPRD] - $PRD_DB<Br>";
			$iPRD_NAME = date("F", mktime(0, 0, 0, $PRDMON[$iPRD], 1, 2000));

			db_conn(YR_DB);
			$ctbal = "UPDATE ".$iPRD_NAME." SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '".USER_DIV."'";
			$dtbal = "UPDATE ".$iPRD_NAME." SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '".USER_DIV."'";

			$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
			$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);
		}

		# Update opening balances for current period
		if (PRD_STATE != "py") {
			db_conn($CUR_PRD_DB);
			$ctbal = "UPDATE openbal SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '".USER_DIV."'";
			$dtbal = "UPDATE openbal SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '".USER_DIV."'";
			$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
			$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);
		}
	}

	//errDie("done");

	if (PRD_STATE != "py") {
		# Record vat transactions
		core_connect();
		$sql = "SELECT accnum FROM salesacc WHERE name = 'VATIN' AND div = '".USER_DIV."'";
		$vatRslt = db_exec($sql);
		if(pg_numrows($vatRslt) > 0){
			$vat = pg_fetch_array($vatRslt);
			$vatacc = $vat['accnum'];

			if($vatacc == $ctacc){
				db_connect();
				$sql = "INSERT INTO vatrec (edate, ref, amount, descript, div,chrgvat) VALUES ('$date', '$refnum', '$amount', 'VAT : $details', '".USER_DIV."', 'VATIN')";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			}elseif($vatacc == $dtacc){
				db_connect();
				$sql = "INSERT INTO vatrec(edate, ref, amount, descript, div,chrgvat) VALUES('$date', '$refnum', '-$amount', 'VAT : $details', '".USER_DIV."','VATIN')";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			}
		}

		core_connect();
		$sql = "SELECT accnum FROM salesacc WHERE name = 'VATOUT' AND div = '".USER_DIV."'";
		$vatRslt = db_exec($sql);
		if(pg_numrows($vatRslt) > 0){
			$vat = pg_fetch_array($vatRslt);
			$vatacc = $vat['accnum'];

			if($vatacc == $ctacc){
				db_connect();
				$sql = "INSERT INTO vatrec(edate, ref, amount, descript, div,chrgvat) VALUES('$date', '$refnum', '$amount', 'VAT : $details', '".USER_DIV."','VATOUT')";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			}elseif($vatacc == $dtacc){
				db_connect();
				$sql = "INSERT INTO vatrec(edate, ref, amount, descript, div,chrgvat) VALUES('$date', '$refnum', '-$amount', 'VAT : $details', '".USER_DIV."','VATOUT')";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			}
		}
	}

	if (PRD_STATE == "py") {
		ledgerCT_py($ctacc, $dtacc, $date, $refnum, $details, $amount);
		ledgerDT_py($dtacc, $ctacc, $date, $refnum, $details, $amount);
		writetrans_update_py($dtacc, $ctacc, $amount,$PRD_DB);
	}

	ledgerCT($ctacc, $dtacc, $date, $refnum, $details, $amount);
	ledgerDT($dtacc, $ctacc, $date, $refnum, $details, $amount);

	$vatacc1 = gethook("accnum", "salesacc", "name", "VAT","VAT");
	
}


# Record Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
/**
 * writes a transaction into the general ledger
 *
 * @param string $ttype transaction type to record
 * @param int $dtacc debit account id
 * @param int $ctacc credit account id
 * @param string $date
 * @param int $refnum
 * @param float $amount
 * @param float $vat vat amount
 * @param string $details description for ledger entry
 */
function recordtrans($ttype, $dtacc, $ctacc, $date, $refnum, $amount,$vat, $details,$iid='0') {
	global $uselog;
	global $MONPRD, $PRDMON;
	$amount=sprint($amount);
//	if($amount<0.01) {
//		return;
//	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited ($ctacc) for transaction \"$details\".");
	$v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited ($dtacc) for transaction \"$details\".");
	$v->isOk ($date, "date", 1, 14, "Invalid date for transaction \"$details\".");
	$v->isOk ($refnum, "num", 1, 50, "Invalid reference number for transaction \"$details\".");
	$v->isOk ($amount, "float", 1, 40, "Invalid amount $amount for transaction \"$details\".");
	$details = str_replace(":", " ", $details);
	$detailscheck = str_replace ("|","",$details);
	$v->isOk ($detailscheck, "string", 0, 2048, "Invalid Details [$details].");
	# Date format
	$date = explode("-", $date);
	// $day ,$mon , $year
	if(checkdate($date[1], $date[0], $date[2])){
		$mon = $date[1];
		$yr = $date[2];
		$date = "$date[2]-$date[1]-$date[0]";
	// $year, $mon, $day
	}elseif(checkdate($date[1], $date[2], $date[0])){
		$mon = $date[1];
		$yr = $date[0];
		$date = "$date[0]-$date[1]-$date[2]";
	}else{
		$v->addError("", "Invalid date for transaction. ".(DEBUG>0?"\"$details\"":""));
	}

	if (isset($yr)) {
		$curyr = getActiveFinYear();
		if ($yr > $curyr || ($yr == $curyr && $mon > $PRDMON[12])) {
			$v->addError("", "Cannot do transaction in future financial year. ".(DEBUG>0?"\"$details\"":""));
		}
	}

	/* start usage log */
	if (empty($uselog["firsttrans"]["date"])) {
		setUsage("firsttrans", "");
	}

	setUsage("lasttrans", "");
	/* end usage log */

	if(floatval($amount) == floatval(0)){
		return;
	}
	
	if ($v->isError()) {
		$OUTPUT = $v->genErrors();
		$OUTPUT .= "<p><input type='button' onclick='javascript:history.back();' value='&laquo; Correct submission'></p>";
		pglib_transaction("ROLLBACK");
		require("template.php");
	}


	if(!accExist($dtacc) AND ($dtacc != "0") AND ($dtacc != "1") AND ($dtacc != "9999")){
		$dtacc = getErrAcc();
		$details = $details." - Debit account was not found, transaction has been posted to error account.";
	}
	if(!accExist($ctacc) AND ($ctacc != "0") AND ($ctacc != "1") AND ($ctacc != "9999")){
		$ctacc = getErrAcc();
		$details = $details." - Credit account was not found, transaction has been posted to error account.";
	}

	# Get account information
	$dacc = getAcc($dtacc);
	$cacc = getAcc($ctacc);

	$sdate=date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	if($vat != "0")
		$vat = sprint ($vat);

	# Insert the records into the transect table
	//if (PRD_STATE != "py") {
		db_conn("exten");
		$sql = "INSERT INTO tranreplay(ttype,debitacc,creditacc,tdate,refno,amount,vat,details,iid)
			VALUES('$ttype', '$dtacc', '$ctacc','$date', '$refnum', '$amount','$vat', '$details','$iid')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	//}
}

/**
 * updates the opening balances when doing a previous year ledger 
 * 
 * @param int $dt_acc debit account id
 * @param int $ct_acc credit account id
 * @param float $amount
 */
function writetrans_update_py($dt_acc, $ct_acc, $amount,$PRD_DB=false) {
	$netProfit_before = getNetProfit_py();

	/* update the year balances */
	db_conn(YR_DB);
	$sql = "UPDATE year_balance SET debit=(debit+'$amount') WHERE accid='$dt_acc'";
	db_exec($sql) or errDie("Error updating previous year balance (DT).");

	$sql = "UPDATE year_balance SET credit=(credit+'$amount') WHERE accid='$ct_acc'";
	db_exec($sql) or errDie("Error updating previous year balance (CT).");

	/* get the retained income account id */
	# Transfer from to Profit/Loss account (9999/999)
	$sql = "SELECT * FROM core.accounts WHERE topacc='5200' AND accnum='000' AND div='".USER_DIV."'";
	$plRs = db_exec($sql) or errDie("Error retrieving retained income account details.");
	if(pg_numrows($plRs) < 1){
		errDie("<li> Retained Income / Accumulated Loss Account number : 5200/000 does not exist");
	}

	$pl = pg_fetch_array($plRs);


	$netProfit = getNetProfit_py();

	/* calculate retained income change */
	$ri_inc = $netProfit_before - $netProfit;

	/* setup debits/credits for transect transaction */
	if ($netProfit > 0) {
		$ri_dtacc = 0;
		$ri_ctacc = $pl["accid"];
	} else {
		$netProfit *= -1;
		$ri_dtacc = $pl["accid"];
		$ri_ctacc = 0;
	}

	# Get account information
	if ($ri_dtacc != 0) {
		$dacc = getAcc($ri_dtacc);
	} else {
		$dacc['accname']=0;
		$dacc['topacc']=0;
		$dacc['accnum']=0;
	}

	if ($ri_ctacc != 0) {
		$cacc = getAcc($ri_ctacc);
	} else {
		$cacc['accname']=0;
		$cacc['topacc']=0;
		$cacc['accnum']=0;
	}

	global $MONPRD, $PRDMON;
//print "--" . YR_DB . "==" . "$PRDMON[1]";
	db_conn($PRDMON[1]);
	$sql = "UPDATE transect
			SET debit='$ri_dtacc', daccname='$dacc[accname]', dtopacc='$dacc[topacc]',
				daccnum='$dacc[accnum]', credit='$ri_ctacc', caccname='$cacc[accname]',
				ctopacc='$cacc[topacc]', caccnum='$cacc[accnum]', amount='$netProfit'
			WHERE refnum='0' AND details LIKE 'Year End, Net %.' AND div='".USER_DIV."'";
	db_exec($sql) or errDie("Error updating net profit/loss year open transaction.");

//FP

//	db_conn(YR_DB);
	db_conn("core");

	/* update ret income account */
	$sql = "UPDATE trial_bal SET debit=(debit + '$ri_inc') WHERE accid='$ri_dtacc' AND period>0";
	db_exec($sql) or errDie("Error updating retained income with previous year transaction (DT).");
	/* if income increased, the ri_inc will be negative, which is by we negate in this query */
	$sql = "UPDATE trial_bal SET credit=(credit - '$ri_inc') WHERE accid='$ri_ctacc' AND period>0";
	db_exec($sql) or errDie("Error updating retained income with previous year transaction (CT).");

	/* now just make sure all negative debit/credit values get's transferred to oposing column */
	$sql = "UPDATE trial_bal SET debit=(debit-credit), credit=0 WHERE credit<0 AND accid='$ri_dtacc'";
	db_exec($sql) or errDie("Error balancing columns for previous year retained income (DT).");
	$sql = "UPDATE trial_bal SET credit=(credit-debit), debit=0 WHERE debit<0 AND accid='$ri_ctacc'";
	db_exec($sql) or errDie("Error balancing columns for previous year retained income (CT).");

	/* update the involved accounts in current year IF they are BALANCE accounts*/
	$sql = "UPDATE trial_bal SET credit=(credit+'$amount')
			WHERE accid='$ct_acc' AND period>'0' AND acctype = 'B'";
	db_exec($sql) or errDie("Error updating trial balance with previous year transaction (CT).");
	$sql = "UPDATE trial_bal SET debit=(debit+'$amount')
			WHERE accid='$dt_acc'AND period>'0' AND acctype = 'B'";
	db_exec($sql) or errDie("Error updating trial balance with previous year transaction (DT).");

	/* update the involved accounts */
	/* we do this in the actual period ??? */
	/* we've already updated the period 0 entries in the core db */
	db_conn (YR_DB);
	$sql = "UPDATE trial_bal SET credit=(credit+'$amount')
			WHERE accid='$ct_acc' AND period>='$MONPRD[$PRD_DB]'";
	db_exec($sql) or errDie("Error updating trial balance with previous year transaction (CT).");
	$sql = "UPDATE trial_bal SET debit=(debit+'$amount')
			WHERE accid='$dt_acc'AND period>='$MONPRD[$PRD_DB]'";
	db_exec($sql) or errDie("Error updating trial balance with previous year transaction (DT).");
}

/**
 * @ignore
 */
function writetransdiv($dtacc, $ctacc, $date, $refnum, $amount, $details, $div)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($date, "date", 1, 14, "Invalid date.");
	$v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount $amount.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	if(floatval($amount) == floatval(0)){
		return;
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	/*
	# Insert the records into the transect table
	db_conn(PRD_DB);
	$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details, div) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details', '$div')";
	$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	*/

	# Get account information
	$dacc = getAcc($dtacc);
	$cacc = getAcc($ctacc);

	# Insert the records into the transect table
	db_conn(PRD_DB);
	$sql = "INSERT INTO transect(debit, daccname, dtopacc, daccnum , credit, caccname, ctopacc, caccnum, date, refnum, amount, author, details, div) VALUES('$dtacc', '$dacc[accname]','$dacc[topacc]','$dacc[accnum]', '$ctacc', '$cacc[accname]','$cacc[topacc]','$cacc[accnum]', '$date', '$refnum', '$amount', '".USER_NAME."', '$details', '$div')";
	$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

	# Update the balances by adding appropriate values to the trial_bal Table
	core_connect();

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc'";
	$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc'";
	$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
	$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

	# commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	# Record vat transactions
	core_connect();
	$sql = "SELECT accnum FROM salesacc WHERE name = 'VAT' AND div = '$div'";
	$vatRslt = db_exec($sql);
	if(pg_numrows($vatRslt) > 0){
		$vat = pg_fetch_array($vatRslt);
		$vatacc = $vat['accnum'];

		if($vatacc == $ctacc){
			db_connect();
			$sql = "INSERT INTO vatrec(edate, ref, amount, descript, div) VALUES('$date', '$refnum', '$amount', 'VAT account transaction', '$div')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		}elseif($vatacc == $dtacc){
			db_connect();
			$sql = "INSERT INTO vatrec(edate, ref, amount, descript, div) VALUES('$date', '$refnum', '-$amount', 'VAT account transaction', '$div')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		}
	}
}

/**
 * @ignore
 */
function writetransdivy($dtacc, $ctacc, $date, $refnum, $amount, $details, $div)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($date, "date", 1, 14, "Invalid date.");
	$v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
	$v->isOk ($amount, "float", 1, 50, "Invalid Amount $amount.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	if(floatval($amount) == floatval(0)){
		return;
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	/*
	# Insert the records into the transect table
	db_conn(PRD_DB);
	$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details, div) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details', '$div')";
	$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	*/

	# Get account information
	if($dtacc!=0) {
		$dacc = getAcc($dtacc);
	} else {
		$dacc['accname']=0;
		$dacc['topacc']=0;
		$dacc['accnum']=0;
	}

	if($ctacc!=0) {
		$cacc = getAcc($ctacc);
	} else {
		$cacc['accname']=0;
		$cacc['topacc']=0;
		$cacc['accnum']=0;
	}

	# Insert the records into the transect table
	global $PRDMON;
	db_conn($PRDMON[1]);
	$sql = "INSERT INTO transect(debit, daccname, dtopacc, daccnum , credit,
				caccname, ctopacc, caccnum, date, refnum, amount, author, details, div)
			VALUES('$dtacc', '$dacc[accname]','$dacc[topacc]','$dacc[accnum]',
				'$ctacc', '$cacc[accname]','$cacc[topacc]','$cacc[accnum]',
				'$date', '$refnum', '$amount', '".USER_NAME."', '$details', '$div')";
	$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

	# Update the balances by adding appropriate values to the trial_bal Table
	core_connect();

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc'";
	$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc'";
	$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
	$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

	# commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

}

/**
 * debits the ledgers in the previous year audit schema's
 * 
 * @param int $acc account id
 * @param int $contra contra account
 * @param string $date
 * @param int $ref
 * @param string $details
 * @param float $amount
 */
function ledgerDT_py($acc, $contra, $date, $ref, $details, $amount) {
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount 3.");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	$tdate=date("Y-m-d");

	$audit_db = YR_NAME . "_audit";
	$actyear = PYR_NAME;

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	# Get balances
	$idRs = get($audit_db, "max(id)", "${PRD_NAME}_ledger", "acc", $acc);
	$id = pg_fetch_array($idRs);

	if($id['max'] <> 0){
		$balRs = get($audit_db, "cbalance,dbalance", "${PRD_NAME}_ledger", "id", $id['max']);
		$bal = pg_fetch_array($balRs);
		$bal['cbalance'] += 0;
		$bal['dbalance'] += 0;
		$bal['dbalance'] += $amount;
	}else{
		db_conn(YR_DB);
		$balSql = "SELECT credit as cbalance, debit as dbalance FROM year_balance WHERE accid='$acc'";
		$balRs = db_exec($balSql) or errDie("Error reading trial balance.");
		$bal = pg_fetch_array($balRs);
	}

	# Total balance changes
	if($bal['dbalance'] > $bal['cbalance']){
		$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
		$bal['cbalance'] = 0;
	}elseif($bal['cbalance'] > $bal['dbalance']){
		$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
		$bal['dbalance'] = 0;
	}else{
		$bal['cbalance'] = 0;
		$bal['dbalance'] = 0;
	}

	$sdate=date("Y-m-d");

	$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $contra);
	$cacc = pg_fetch_array($caccRs);

	db_conn($audit_db);
	$sql = "INSERT INTO ${PRD_NAME}_ledger(acc, contra, caccname, ctopacc, caccnum, edate,
			sdate, eref, descript, debit, credit, dbalance, cbalance, div, actyear)
		VALUES('$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]',
			'$date','$sdate', '$ref', '$details', '$amount', '0', '$bal[dbalance]',
			'$bal[cbalance]', '".USER_DIV."', '$actyear')";
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");

	global $PRDMON, $MONPRD;

	for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
		$iPRD_NAME = date("F", mktime(0, 0, 0, $PRDMON[$iPRD], 1, 2000));

		//print "dt: $iPRD - $PRDMON[$iPRD] - $PRD_DB<Br>";
		$sql = "UPDATE ${iPRD_NAME}_ledger SET dbalance = (dbalance + '$amount') WHERE acc = '$acc'";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}
}

/**
 * credits the ledgers in the previous year audit schema's
 * 
 * @param int $acc account id
 * @param int $contra contra account
 * @param string $date
 * @param int $ref
 * @param string $details
 * @param float $amount
 */
function ledgerCT_py($acc, $contra, $date, $ref, $details, $amount) {
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount 4.");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	$audit_db = YR_NAME . "_audit";
	$actyear = PYR_NAME;

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	# Get balances
	$idRs = get($audit_db, "max(id)", "${PRD_NAME}_ledger", "acc", $acc);
	$id = pg_fetch_array($idRs);

	if($id['max'] <> 0){
		$balRs = get($audit_db, "cbalance,dbalance", "${PRD_NAME}_ledger", "id", $id['max']);
		$bal = pg_fetch_array($balRs);
		$bal['cbalance'] += 0;
		$bal['dbalance'] += 0;
		$bal['cbalance'] += $amount;
	}else{
		db_conn(YR_DB);
		$balSql = "SELECT credit as cbalance, debit as dbalance FROM year_balance WHERE accid='$acc'";
		/* CODE_MARK, this and sister function
			if no entry was done in July ledger (PrevYear), then this part is gonna be used
			to retrieve current balance. deadly. because it gonna return end year balance
		*/
		$balRs = db_exec($balSql) or errDie("Error reading trial balance.");
		$bal = pg_fetch_array($balRs);
	}

	# Total balance changes
	if($bal['dbalance'] > $bal['cbalance']){
		$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
		$bal['cbalance'] = 0;
	}elseif($bal['cbalance'] > $bal['dbalance']){
		$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
		$bal['dbalance'] = 0;
	}else{
		$bal['cbalance'] = 0;
		$bal['dbalance'] = 0;
	}

	$sdate=date("Y-m-d");

	$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $contra);
	$cacc = pg_fetch_array($caccRs);

	db_conn($audit_db);
	$sql = "INSERT INTO ${PRD_NAME}_ledger(acc, contra, caccname, ctopacc, caccnum, edate,sdate,
			eref, descript, debit, credit, dbalance, cbalance, div, actyear)
		VALUES('$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]',
			'$date','$sdate', '$ref', '$details', '0', '$amount', '$bal[dbalance]',
			'$bal[cbalance]', '".USER_DIV."', '$actyear')";
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");

	global $PRDMON, $MONPRD;

	for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
		$iPRD_NAME = date("F", mktime(0, 0, 0, $PRDMON[$iPRD], 1, 2000));

		//print "ct: $iPRD - $PRDMON[$iPRD] - $PRD_DB<Br>";

		$sql = "UPDATE ${iPRD_NAME}_ledger SET cbalance = (cbalance + '$amount') WHERE acc = '$acc'";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}
}

/**
 * debits the ledgers in the current year audit schema's
 * 
 * @param int $acc account id
 * @param int $contra contra account
 * @param string $date
 * @param int $ref
 * @param string $details
 * @param float $amount
 */
function ledgerDT($acc, $contra, $date, $ref, $details, $amount) {
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount 3.");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	$tdate=date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	# Get balances
	$idRs = get($PRD_DB, "max(id)", "ledger", "acc", $acc);
	$id = pg_fetch_array($idRs);

	if($id['max'] <> 0){
		$balRs = get($PRD_DB, "cbalance,dbalance", "ledger", "id", $id['max']);
		$bal = pg_fetch_array($balRs);
		$bal['cbalance'] += 0;
		$bal['dbalance'] += 0;
		$bal['dbalance'] += $amount;
	}else{
		db_conn("core");
		$balSql = "SELECT credit as cbalance, debit as dbalance FROM trial_bal WHERE accid='$acc' AND period='$PRD_DB'";
		$balRs = db_exec($balSql) or errDie("Error reading trial balance.");
		$bal = pg_fetch_array($balRs);
	}

	# Total balance changes
	if($bal['dbalance'] > $bal['cbalance']){
		$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
		$bal['cbalance'] = 0;
	}elseif($bal['cbalance'] > $bal['dbalance']){
		$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
		$bal['dbalance'] = 0;
	}else{
		$bal['cbalance'] = 0;
		$bal['dbalance'] = 0;
	}

	$sdate=date("Y-m-d");

	$caccRs = get("core", "accname, accid, topacc, accnum, acctype", "accounts", "accid", $contra);
	$cacc = pg_fetch_array($caccRs);

	if (PRD_STATE != "py") {
		db_conn($PRD_DB);
		$sql = "INSERT INTO ledger(refnum, acc, contra, caccname, ctopacc, caccnum, edate,sdate,
				eref, descript, debit, credit, dbalance, cbalance, div)
			VALUES(0, '$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]',
				'$date','$sdate', '$ref', '$details', '$amount', '0', '$bal[dbalance]',
				'$bal[cbalance]', '".USER_DIV."')";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}

	global $PRDMON, $MONPRD;

	if (PRD_STATE == "py") {
		$start_prd = 1;
	} else {
		$start_prd = $MONPRD[$PRD_DB] + 1;
	}

/*
FP
Added this because we need to allocate E and I amounts to the retained
income account if we are dealing with previous year transactions.
*/

	/* get the retained income account id */
	# Transfer from to Profit/Loss account (9999/999)
	$sql = "SELECT * FROM core.accounts WHERE topacc='5200' AND accnum='000' AND div='".USER_DIV."'";
	$plRs = db_exec($sql) or errDie("Error retrieving retained income account details.");
	if(pg_numrows($plRs) < 1){
		errDie("<li> Retained Income / Accumulated Loss Account number : 5200/000 does not exist");
	}
	$pl = pg_fetch_array($plRs);

	$netProfit_before = getNetProfit_py();
	$netProfit = getNetProfit_py();

	/* calculate retained income change */
	$ri_inc = $netProfit_before - $netProfit;
	/* setup debits/credits for transect transaction */
//	if ($netProfit > 0) {
//		$ri_dtacc = 0;
//		$ri_ctacc = $pl["accid"];
//	} else {
//		$netProfit *= -1;
//		$ri_dtacc = $pl["accid"];
//		$ri_ctacc = 0;
//	}

	$actyear = YR_NAME;
//print "++$acc ++ $contra<br>";

	if (true || $MONPRD[$PRD_DB] < $MONPRD[$CUR_PRD_DB]) {

		$act_accRs = get("core", "accname, accid, topacc, accnum, acctype", "accounts", "accid", $acc);
		$ant_acc = pg_fetch_array($act_accRs);


		#both accounts are the same ??? swap ??
		$check = TRUE;
		if($ant_acc['acctype'] == $cacc['acctype']){
			$check = FALSE;
//			$temp = $ri_dtacc;
//			$ri_dtacc = $ri_ctacc;
//			$ri_ctacc = $temp;
		}else {
			if(($ant_acc['acctype'] == "I") OR ($ant_acc['acctype'] == "E")){
				$ri_dtacc = $pl["accid"];
			}else {
				$ri_dtacc = 0;
			}
		}

//		if ($cacc['acctype'] == "")
		for ($iPRD = $start_prd; $iPRD <= 12; ++$iPRD) {
			//print "dt: $iPRD - $PRDMON[$iPRD] - $PRD_DB<Br>";
			db_conn($PRDMON[$iPRD]);
			if (PRD_STATE == "py"){

				if ($ant_acc['acctype'] == "B"){
					#we only update the current dbs if the account is BALANCE
					$sql = "UPDATE ledger SET dbalance = (dbalance + '$amount') WHERE acc = '$acc'";
					$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
				}else {
					#else we update the retained income account

					if ($check){
					/* update ret income account */
					$sql = "UPDATE ledger SET dbalance=(dbalance + '$amount') WHERE acc='$ri_dtacc'";
					db_exec($sql) or errDie("Error updating retained income with previous year ledger transaction (DT).");
					/* if income increased, the ri_inc will be negative, which is by we negate in this query */
//					$sql = "UPDATE ledger SET cbalance=(cbalance + '$amount') WHERE acc='$ri_ctacc'";
//					db_exec($sql) or errDie("Error updating retained income with previous year ledger transaction (CT).");
					}
				}
			}else {
				$sql = "UPDATE ledger SET dbalance = (dbalance + '$amount') WHERE acc = '$acc'";
				$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
			}
		}
	}

	if (PRD_STATE != "py") {
		db_conn("audit");
		$sql = "INSERT INTO ".$PRD_NAME."_ledger(acc, contra, caccname, ctopacc, caccnum, edate,sdate,
				eref, descript, debit, credit, dbalance, cbalance, div, actyear)
			VALUES('$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]', '$date','$sdate',
				'$ref', '$details', '$amount', '0', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear')";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}
}

/**
 * credits the ledgers in the current year audit schema's
 * 
 * @param int $acc account id
 * @param int $contra contra account
 * @param string $date
 * @param int $ref
 * @param string $details
 * @param float $amount
 */
function ledgerCT($acc, $contra, $date, $ref, $details, $amount) {
	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 2048, "Invalid Details. ($details)");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount 4.");

	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT =  $write;
		require("template.php");
	}

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD(date("Y-m-d"));

	# Get balances
	$idRs = get($PRD_DB, "max(id)", "ledger", "acc", $acc);
	$id = pg_fetch_array($idRs);

	if($id['max'] <> 0){
		$balRs = get($PRD_DB, "cbalance,dbalance", "ledger", "id", $id['max']);
		$bal = pg_fetch_array($balRs);
		$bal['cbalance'] += 0;
		$bal['dbalance'] += 0;
		$bal['cbalance'] += $amount;
	}else{
		db_conn("core");
		$balSql = "SELECT credit as cbalance, debit as dbalance FROM trial_bal WHERE accid='$acc' AND period='$PRD_DB'";
		$balRs = db_exec($balSql) or errDie("Error reading trial balance.");
		$bal = pg_fetch_array($balRs);
	}

	# Total balance changes
	if($bal['dbalance'] > $bal['cbalance']){
		$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
		$bal['cbalance'] = 0;
	}elseif($bal['cbalance'] > $bal['dbalance']){
		$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
		$bal['dbalance'] = 0;
	}else{
		$bal['cbalance'] = 0;
		$bal['dbalance'] = 0;
	}

	$sdate=date("Y-m-d");

	$caccRs = get("core", "accname, accid, topacc, accnum, acctype", "accounts", "accid", $contra);
	$cacc = pg_fetch_array($caccRs);

	if (PRD_STATE != "py") {
		db_conn($PRD_DB);
		$sql = "INSERT INTO ledger(refnum, acc, contra, caccname, ctopacc, caccnum, edate,sdate,
				eref, descript, debit, credit, dbalance, cbalance, div)
			VALUES(0, '$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]',
				'$date','$sdate', '$ref', '$details', '0', '$amount', '$bal[dbalance]',
				'$bal[cbalance]', '".USER_DIV."')";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}

	global $PRDMON, $MONPRD;

	if (PRD_STATE == "py") {
		$start_prd = 1;
	} else {
		$start_prd = $MONPRD[$PRD_DB] + 1;
	}

/*
FP
Added this because we need to allocate E and I amounts to the retained
income account if we are dealing with previous year transactions.
*/

	/* get the retained income account id */
	# Transfer from to Profit/Loss account (9999/999)
	$sql = "SELECT * FROM core.accounts WHERE topacc='5200' AND accnum='000' AND div='".USER_DIV."'";
	$plRs = db_exec($sql) or errDie("Error retrieving retained income account details.");
	if(pg_numrows($plRs) < 1){
		errDie("<li> Retained Income / Accumulated Loss Account number : 5200/000 does not exist");
	}
	$pl = pg_fetch_array($plRs);

	$netProfit_before = getNetProfit_py();
	$netProfit = getNetProfit_py();

	/* calculate retained income change */
	$ri_inc = $netProfit_before - $netProfit;
	/* setup debits/credits for transect transaction */
//	if ($netProfit > 0) {
//		$ri_dtacc = 0;
//		$ri_ctacc = $pl["accid"];
//	} else {
//		$netProfit *= -1;
//		$ri_dtacc = $pl["accid"];
//		$ri_ctacc = 0;
//	}


	$actyear = YR_NAME;
	if (true || $MONPRD[$PRD_DB] < $MONPRD[$CUR_PRD_DB]) {

		$act_accRs = get("core", "accname, accid, topacc, accnum, acctype", "accounts", "accid", $acc);
		$act_acc = pg_fetch_array($act_accRs);

		
		#both accounts are the same ??? swap ??
		$check = TRUE;
		if($act_acc['acctype'] == $cacc['acctype']){
			$check = FALSE;
		}else {
			if(($act_acc['acctype'] == "I") OR ($act_acc['acctype'] == "E")){
				$ri_ctacc = $pl["accid"];
			}else {
				$ri_ctacc = 0;
			}
		}

		for ($iPRD = $start_prd; $iPRD <= 12; ++$iPRD) {

			db_conn($PRDMON[$iPRD]);
			if (PRD_STATE == "py"){
				if ($act_acc['acctype'] == "B"){
					#we only update the current dbs if the account is BALANCE
					$sql = "UPDATE ledger SET cbalance = (cbalance + '$amount') WHERE acc = '$acc'";
					$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
				}else {
					#else we update the retained income account

					if($check){
					#for credit, we switch the retained income update accounts around ???
					/* update ret income account */
//					$sql = "UPDATE ledger SET dbalance=(dbalance + '$amount') WHERE acc='$ri_dtacc'";
//					db_exec($sql) or errDie("Error updating retained income with previous year ledger transaction (DT).");
					/* if income increased, the ri_inc will be negative, which is by we negate in this query */
					$sql = "UPDATE ledger SET cbalance=(cbalance + '$amount') WHERE acc='$ri_ctacc'";
					db_exec($sql) or errDie("Error updating retained income with previous year ledger transaction (CT).");
					}
				}
			}else {
				#working in current year ... update everything
				$sql = "UPDATE ledger SET cbalance = (cbalance + '$amount') WHERE acc = '$acc'";
				$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
			}
		}
	}

	if (PRD_STATE != "py") {
		db_conn("audit");
		$sql = "INSERT INTO ".$PRD_NAME."_ledger(acc, contra, caccname, ctopacc, caccnum, edate,
				eref, descript, debit, credit, dbalance, cbalance,sdate, div, actyear)
			VALUES('$acc', '$contra', '$cacc[accname]', '$cacc[topacc]', '$cacc[accnum]',
				'$date', '$ref', '$details', '0', '$amount', '$bal[dbalance]',
				'$bal[cbalance]','$sdate', '".USER_DIV."', '$actyear')";
		$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
	}
}

/**
 * @ignore
 */
function lbalance($id) {
	$balRs = get(PRD_DB, "cbalance, dbalance", "ledger", "id", $id);
	$bal = pg_fetch_array($balRs);

	# Total balance changes
	if($bal['dbalance'] > $bal['cbalance']){
		$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
		$bal['cbalance'] = 0;
	}elseif($bal['cbalance'] > $bal['dbalance']){
		$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
		$bal['dbalance'] = 0;
	}else{
		$bal['cbalance'] = 0;
		$bal['dbalance'] = 0;
	}

	$sql = "UPDATE ledger SET dbalance = '$bal[dbalance]', cbalance = '$bal[cbalance]' WHERE id = '$id'";
	$rs = db_exec($sql) or errdie("Unable to update ledger entry on the Database.");
}

/**
 * @ignore
 */
function isDebtors($accid) {
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE debtacc = '$accid'";
	$deptRslt = db_exec($sql) or errDie("Could not retrieve departments Information from the Database.",SELF);
	if(pg_numrows($deptRslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function isCreditors($accid) {
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE credacc = '$accid'";
	$deptRslt = db_exec($sql) or errDie("Could not retrieve departments Information from the Database.",SELF);
	if(pg_numrows($deptRslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function isStock($accid) {
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE stkacc = '$accid'";
	$whRslt = db_exec($sql) or errDie("Could not retrieve warehouses Information from the Database.",SELF);
	if(pg_numrows($whRslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * @ignore
 */
function Control($dtaccid, $ctaccid, $refnum, $day, $mon, $year) {
	# Check if its not customer control
	if(isDebtors($dtaccid)){
		return debtors("dt", $ctaccid, $refnum, $day, $mon, $year);
	}
	if(isDebtors($ctaccid)){
		return debtors("ct", $dtaccid, $refnum, $day, $mon, $year);
	}
	if(isStock($ctaccid)){
		return stock("ct", $dtaccid, $refnum, $day, $mon, $year);
	}

	# Check if its not creditors control
	if(isCreditors($dtaccid)){
		return creditors("dt", $ctaccid, $refnum, $day, $mon, $year);
	}
	if(isCreditors($ctaccid)){
		return creditors("ct", $dtaccid, $refnum, $day, $mon, $year);
	}
	if(isStock($dtaccid)){
		return stock("dt", $ctaccid, $refnum, $day, $mon, $year);
	}
}

/**
 * returns whether an account is disabled
 *
 * @param int $accid
 * @return bool
 */
function isDisabled($accid){
	global $DISABLE;

	# If accid is in the disabled list
	if(in_array($accid, $DISABLE)){
		return true;
	}else{
		return isb($accid);
	}
}

/**
 * returns whether an account is blocked
 *
 * @param int $accid
 * @return bool
 */
function isb($accid){
	global $blocked;

	if(is_array($blocked)) {
		# If accid is in the disabled list
		if(in_array($accid, $blocked)){
			return true;
		}else{
			return false;
		}
	} else {
		return false;
	}
}

/**
 * @ignore
 */
function debtors($tran, $cacc, $refnum, $day, $mon, $year){
	db_connect();
	$sql = "SELECT * FROM customers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class=err> There are no Customers in Cubit.";
	}
	$custs = "<select name=cusnum>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	if($tran == "dt"){
		$entry = "DT";
	}else{
		$entry = "CT";
	}

	$debtors = "
					<h3>You Selected a Debtors Control account</h3>
					<h4>Select Customer</h4>
					<form action='cust-trans.php' method='POST'>
						<input type='hidden' name='key' value='details'>
						<input type='hidden' name='details' value='details'>
						<input type='hidden' name='entry' value='$entry'>
						<input type='hidden' name='refnum' value='$refnum'>
						<input type='hidden' name='day' value='$day'>
						<input type='hidden' name='mon' value='$mon'>
						<input type='hidden' name='year' value='$year'>
						<input type='hidden' name='ct_day' value='$day'>
						<input type='hidden' name='ct_month' value='$mon'>
						<input type='hidden' name='ct_year' value='$year'>
						<input type='hidden' name=accid value='$cacc'>
					<table ".TMPL_tblDflts." width='300'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Select Customer</td>
							<td>$custs</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='center'><input type='submit' value='Continue &raquo;'></td>
						</tr>
					</table>";
	return $debtors;

}

/**
 * @ignore
 */
function creditors($tran, $cacc, $refnum, $day, $mon, $year){
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class=err> There are no suppliers in Cubit.";
	}
	$sups = "<select name=supid>";
	while($sup = pg_fetch_array($supRslt)){
		$sups .= "<option value='$sup[supid]'>$sup[supname]</option>";
	}
	$sups .= "</select>";

	if($tran == "dt"){
		$entry = "DT";
	}else{
		$entry = "CT";
	}

	$creditors = "
					<h3>You Selected a Creditors Control account</h3>
					<h4>Select Supplier</h4>
					<form action='supp-trans.php' method='POST'>
						<input type='hidden' name='tran' value='$tran'>
						<input type='hidden' name='cacc' value='$cacc'>
						<input type='hidden' name='key' value='details'>
						<input type='hidden' name='details' value='details'>
						<input type='hidden' name='entry' value='$entry'>
						<input type='hidden' name='refnum' value='$refnum'>
						<input type='hidden' name='day' value='$day'>
						<input type='hidden' name='mon' value='$mon'>
						<input type='hidden' name='year' value='$year'>
						<input type='hidden' name='date_day' value='$day'>
						<input type='hidden' name='date_month' value='$mon'>
						<input type='hidden' name='date_year' value='$year'>
						<input type='hidden' name='accid' value='$cacc'>
					<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
						<tr><th>Field</th><th>Value</th></tr>
						<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select Supplier</td><td>$sups</td></tr>
						<tr><td><br></td></tr>
						<tr><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=center><input type=submit value='Continue &raquo;'></td></tr>
					</table>";

	return $creditors;
}

/**
 * @ignore
 */
function stock($tran, $cacc, $refnum, $day, $mon, $year){
	$stk = qryStock();
	$stks = db_mksel($stk, "stkid", false, "#stkid", "(#stkcod) #stkdes");

	if ($tran == "dt"){
		$dtct = "inc";
	} else {
		$dtct = "dec";
	}

	$OUT = "
	<h3>You Selected a Stock Control account</h3>
	<h4>Select Stock Item</h4>
	<form action='".relpath("stock-balance.php")."' method='post'>
	<input type='hidden' name='entry' value='$dtct' />
	<input type='hidden' name='caccid' value='$cacc' />
	<table ".TMPL_tblDflts." width='300'>
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td valign='top'>Select Stock Item</td>
		<td>$stks</td>
	</tr>
	<tr>
		<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()' /></td>
		<td align='center'><input type='submit' value='Continue &raquo;' /></td>
	</tr>
	</table>
	</form>";

	return $OUT;
}

/**
 * @ignore
 */
function custCT($amount, $cusnum, $odate = "", $invid=0, $invtype="")
{
	if($odate == "")
	$odate = date("Y-m-d");

	db_connect();

	$amount = ($amount * (-1));

	# Check for previous transactions
	$sql = "SELECT * FROM custran 
			WHERE cusnum='$cusnum' AND balance>0 AND odate='$odate' AND div='".USER_DIV."' 
			ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			$sql = "
			INSERT INTO custran(cusnum, odate, balance,div, invid, invtype)
			VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."', '$invid', '$invtype')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		$sql = "
		INSERT INTO custran(cusnum, odate, balance, div, invid, invtype)
		VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."', '$invid', '$invtype')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * @ignore
 */
function custCTP($amount, $cusnum, $odate = "")
{
	if($odate == "")
	$odate = date("Y-m-d");

	db_connect();

	$amount = ($amount * (-1));

	/* Make transaction record for age analysis */
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * @ignore
 */
function custDT($amount, $cusnum, $odate = "", $invid=0, $invtype="")
{
	if($odate == "")
	$odate = date("Y-m-d");

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance < 0 AND div = '1000' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			$sql = "
			INSERT INTO custran(cusnum, odate, balance, div, invid, invtype)
			VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."', '$invid', '$invtype')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		$sql = "
		INSERT INTO custran(cusnum, odate, balance, div, invid, invtype)
		VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."', '$invid', '$invtype')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * @ignore
 */
function custDTA($amount, $cusnum, $age,$date="")
{
	db_connect();
	if($date=="") {
		$date=date("Y-m-d");
	}

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND balance < 0 AND div = '".USER_DIV."' AND div=1000 ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, age, div) VALUES('$cusnum', '$odate', '$amount', '$age', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, age, div) VALUES('$cusnum', '$date', '$amount', '$age', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * @ignore
 */
function suppDT($amount, $supid, $edate = "")
{
	if($edate == "")
	$edate = date("Y-m-d");

	// $amount = ($amount - ($amount * 2));

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance > 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * @ignore
 */
function suppCT($amount, $supid, $edate = "")
{
	if($edate == "")
	$edate = date("Y-m-d");

	$amount = ($amount - ($amount * 2));

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance < 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			$amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND fbalance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

/**
 * creates an inventory ledger entry
 *
 * @param int $stkid stock id
 * @param string $stkcod stock code
 * @param string $stkdes stock description
 * @param char $trantype value must be "d" | "c" - debit/credit
 * @param string $edate 
 * @param int $qty
 * @param float $csamt cost amount
 * @param string $details entry details
 */
function stockrec($stkid, $stkcod, $stkdes, $trantype, $edate, $qty, $csamt, $details, $dobal=TRUE){
	list($PRD_DB, $PRD_NAME) = getPRD($edate);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD();
	
	if($trantype != 'dt'){
		$csamt = ($csamt * (-1));
		$qty = ($qty * (-1));
	}

	# Get balances
	$idRs = get($PRD_DB, "max(id)", "stkledger", "stkid", $stkid);
	$id = pg_fetch_array($idRs);

	if($id['max'] <> 0){
		$balRs = get($PRD_DB, "balance,bqty", "stkledger", "id", $id['max']);
		$bal = pg_fetch_array($balRs);
		$bal['balance'] += $csamt;
		$bal['bqty'] += $qty;
	}else{
		$balRs = get("cubit", "csamt as balance, units as bqty", "stock", "stkid", $stkid);
		$bal = pg_fetch_array($balRs);
	}

	db_conn($PRD_DB);
	$sql = "INSERT INTO stkledger(stkid, stkcod, stkdes, trantype, edate, qty, csamt, balance,
			bqty, details, div,yrdb)
		VALUES('$stkid', '$stkcod', '$stkdes', '$trantype', '$edate', '$qty', '$csamt',
			'$bal[balance]', '$bal[bqty]', '$details', '".USER_DIV."', '".YR_DB."')";
	$recRslt = db_exec($sql);

	global $PRDMON, $MONPRD;

	for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
		db_conn($PRDMON[$iPRD]);
		$sql = "UPDATE stkledger SET balance=balance+'$csamt',bqty=bqty+'$qty'
		 	 	WHERE stkid='$stkid' AND yrdb='".YR_DB."'";
		$Ri = db_exec($sql) or errDie("Unable to update stockledeger.");

		if ($dobal) {
			if (pg_affected_rows($Ri) <= 0) {
				$sql = "INSERT INTO stkledger(stkid, stkcod, stkdes, trantype, edate, qty,
						csamt, balance, bqty, details, div,yrdb)
					VALUES('$stkid', '$stkcod', 'Balance', '$trantype', '$edate', '$qty',
						'$csamt', '$bal[balance]', '$bal[bqty]', '$details',
						'".USER_DIV."', '".YR_DB."')";
				$recRslt = db_exec($sql);
			}
		}
	}
}
?>

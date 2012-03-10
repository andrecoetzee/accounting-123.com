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


/*
# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "core-settings.php") {
	exit;
}
core_connect("core");
$sql = "SELECT * FROM active";
$rslt = db_exec($sql);
$rows = pg_numrows($rslt);
if(empty($rows)){
$OUTPUT = "<center>ERROR : There Current Period is not Selected Yet. You Cannot continue without Selecting a period";
require("template.php");
}
$act = Pg_fetch_array($rslt);
define ("PRD_DB", $act['prddb']);
define ("YR_DB", $act['yrdb']);
define ("PRD_NAME", $act['prdname']);
define ("YR_NAME", $act['yrname']);

function prdname($prd){
	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prddb = '$prd'";
	$rs = db_exec($sql);
	if(pg_numrows($rs) > 0){
		$pr = pg_fetch_array($rs);
		return $pr['prdname'];
	}else{
		return "<li class=err>Perion name not found</li>";
	}
}

# get last entered ID
function getlastid ($db, $table, $col)
{
	# new connection if none exists
        db_conn($db);
	# get last inserted id value, die if fails
	if (!$lastidRslt = db_exec ("SELECT last_value FROM ".$table."_".$col."_seq")) {
		return 0;
	}
	# die if no result
	if (pg_numrows ($lastidRslt) < 1) {
		return 0;
	}
	$myId = pg_fetch_row ($lastidRslt, 0);
	return $myId[0];
}

function ledgerDT($acc, $contra, $date, $ref, $details, $amount){
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");


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

	db_conn(PRD_DB);

	$sql = "INSERT INTO ledger(acc, contra, edate, eref, descript, debit, div) VALUES('$acc', '$contra', '$date', '$ref', '$details', '$amount', '".USER_DIV."')";
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
}

function ledgerCT($acc, $contra, $date, $ref, $details, $amount){
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acc, "num", 1, 50, "Invalid Ledger Entry Account.");
	$v->isOk ($contra, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($ref, "string", 1, 30, "Invalid Ledger reference.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");


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

	db_conn(PRD_DB);

	$sql = "INSERT INTO ledger(acc, contra, edate, eref, descript, credit, div) VALUES('$acc', '$contra', '$date', '$ref', '$details', '$amount', '".USER_DIV."')";
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
}
*/

	require("../core-settings.php");
?>

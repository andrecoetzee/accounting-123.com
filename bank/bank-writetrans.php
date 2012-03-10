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

// self explainatory
function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account number.");
	$v->isOk ($trantype, "string", 1, 50, "Invalid Transaction type.");
	$v->isOk ($date, "date", 1, 14, "Invalid Bank Transaction date.");
	$v->isOk ($name, "string", 1, 50, "Invalid Name.");
	$v->isOk ($details, "string", 0, 255, "Invalid Bank Transacton details.");
	$v->isOk ($cheqnum, "num", 0, 50, "Invalid Bank Transacton cheque number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Bank Transacton Amount.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Bank Transaction account involved.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$OUTPUT = $write."<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		require("../template.php");
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];


	# record the payment record
    db_connect();
	$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv) VALUES ('$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
}
?>

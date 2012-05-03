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

require("../settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "report":
			$OUTPUT=report($_POST);
			break;
		default:
			$OUTPUT="Invalid use.";
	}
} else {
	$OUTPUT=seluse();
}

require("../template.php");

function seluse()
{
	db_conn("cubit");
	$Sl="SELECT DISTINCT username,userid FROM posrec ORDER BY username";
	$Ry=db_exec($Sl) or errDie("Unable to get users from pos rec.");

	$users="<select name=user>
	<option value=0>All</option>";

	while($data=pg_fetch_array($Ry)) {
		$users.="<option value='$data[userid]'>$data[username]</option>";
	}

	$users.="</select>";

	$Out="<h3>POS Report</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='report'>
	<tr><th colspan=2>Report Criteria</th></tr>
	<tr class='bg-odd'><td>User</td><td>$users</td></tr>
	<tr class='bg-even'><td>Date</td><td>
		<table cellpadding=1 cellspacing=2><tr>
			<td><input type=text size=3 value='".date("d")."' name=day></td>
			<td>-</td>
			<td><input type=text size=3 value='".date("m")."' name=mon></td>
			<td>-</td>
			<td><input type=text size=5 value='".date("Y")."' name=year></td>
		</tr></table>
	</td></tr>
	<tr class='bg-odd'><td>Starting Amount</td><td><input type=text size=20 name=amount></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='View Report &raquo;'></td></tr>
	</form>
	</table><p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $Out;
}

function report($_POST)
{
	extract($_POST);

	$date = $year."-".$mon."-".$day;
        $amount+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($user, "string", 1, 50, "Invalid user.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

	if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid order date.");
        }

	$met=remval($met);

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>$e[msg]</li>";
		}
		return $confirm;
	}

	if($user!="0") {
		$whe=" AND by='$user'";
	} else {
		$whe="";
	}

	if($met!="all") {
		$whe.=" AND method='$met'";
	} else {
		$whe.="";
	}

	db_conn("cubit");
	$sql = "SELECT * FROM payrec WHERE date='$date' $whe";
	$rslt = db_exec($sql) or errDie("Unable to retrieve pos report from Cubit.");

	$cash = $cheque = $credit_card = $credit = $sales = 0;
	while ($rec_data = pg_fetch_array($rslt)) {
		switch (strtolower($rec_data["method"])) {
			case "cash":
				$cash += $rec_data["amount"];
				break;
			case "cheque":
				$cheque += $rec_data["amount"];
				break;
			case "credit card":
				$credit_card += $rec_data["amount"];
				break;
			case "credit":$credit += $rec_data["amount"];
				break;
		}
		$sales += $rec_data["amount"];
	}

	db_conn('cubit');
	$Sl="SELECT sum(amount) FROM payrec WHERE date='$date' $whe";
	$Ry=db_exec($Sl) or errDie("Unable to get pos rec.");
	$data=pg_fetch_array($Ry);

	$amount=sprint($amount);
	$expected=sprint($amount+$sales);

	$Report="<h3>POS Report: $date</h3>
	<table ".TMPL_tblDflts." style='width: 100%'>
	<tr>
		<th colspan=2>Report</th>
	</tr>
	<tr class='bg-odd'>
		<td>Starting Amount</td>
		<td align='right'>".CUR." $amount</td>
	</tr>
	<tr class='bg-even'>
		<td>Cash</td>
		<td align='right'>".sprint($cash)."</td>
	</tr>
	<tr class='bg-odd'>
		<td>Cheque</td>
		<td align='right'>".sprint($cheque)."</td>
	</tr>
	<tr class='bg-even'>
		<td>Credit Card</td>
		<td align='right'>".sprint($credit_card)."</td>
	</tr>
	<tr class='bg-odd'>
		<td>Credit</td>
		<td align='right'>".sprint($credit)."</td>
	</td>
	<tr class='bg-even'>
		<td>Expected Amount</td>
		<td align='right'>".CUR." $expected</td>
	</tr>
	</table>";

	include("temp.xls.php");

	Stream("Report", $Report);

	return $Report;
}
?>
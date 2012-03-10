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

$OUTPUT=report($HTTP_POST_VARS);

require("../template.php");



function report($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	$date = $year."-".$mon."-".$day;
	$tdate = $tyear."-".$tmon."-".$tday;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid order date.");
        }

	if(!checkdate($tmon, $tday, $tyear)){
                $v->isOk ($tdate, "num", 1, 1, "Invalid order date.");
        }

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unabel to get data.");

	$total1 = 0;
	$totvat1 = 0;
	$out = "
		<tr>
			<th colspan='5'>OUTPUT</th>
		</tr>
		<tr>
			<th>Code</th>
			<th>VAT Code Name</th>
			<th>Tax %</th>
			<th>Base Amount</th>
			<th>Tax Amount</th>
		</tr>";
	$i = 1;

	while($vd = pg_fetch_array($Ri)) {

		$Sl = "SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' AND type = 'OUTPUT' AND cid='$vd[id]'";
		$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
		$data = pg_fetch_array($Ry);

		$amount = sprint($data['amount']);
		$vat = sprint($data['vat']);

		$total1 += $amount;
		$totvat1 += $vat;

		$out .= "
			<tr>
				<td>$vd[code]</td>
				<td>$vd[description]</td>
				<td>$vd[vat_amount]</td>
				<td align='right'>".CUR." $amount</td>
				<td align='right'>".CUR." $vat</td>
			</tr>";
		$i++;
	}

	$total1 = sprint($total1);
	$totvat1 = sprint($totvat1);

	$out .= "
		<tr>
			<td colspan='2' align='right'>Total:</td>
			<td></td>
			<td align='right'>".CUR." $total1</td>
			<td align='right'>".CUR." $totvat1</td>
		</tr>";


	$total2 = 0;
	$totvat2 = 0;
	$out .= "
		".TBL_BR."
		".TBL_BR."
		<tr>
			<th colspan='5'>INPUT</th>
		</tr>
		<tr>
			<th>Code</th>
			<th>VAT Code Name</th>
			<th>Tax %</th>
			<th>Base Amount</th>
			<th>Tax Amount</th>
		</tr>";
	$i = 1;

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unabel to get data.");

	while($vd = pg_fetch_array($Ri)) {

		$Sl = "SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' AND type = 'INPUT' AND cid='$vd[id]'";
		$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
		$data = pg_fetch_array($Ry);

		$amount = sprint($data['amount']);
		$vat = sprint($data['vat']);

		$total2 += $amount;
		$totvat2 += $vat;

		$out .= "
			<tr>
				<td>$vd[code]</td>
				<td>$vd[description]</td>
				<td>$vd[vat_amount]</td>
				<td align='right'>".CUR." $amount</td>
				<td align='right'>".CUR." $vat</td>
			</tr>";
		$i++;
	}

	$total2 = sprint($total2);
	$totvat2 = sprint($totvat2);

	$out .= "
		<tr>
			<td colspan='2' align='right'>Total:</td>
			<td></td>
			<td align='right'>".CUR." $total2</td>
			<td align='right'>".CUR." $totvat2</td>
		</tr>";

	$out .= "
		<tr><td><br></td></tr>
		<tr>
			<td colspan='2' align='right'>Total:</td>
			<td></td>
			<td>".CUR." ".($total1-abs($total2))."</td>
			<td>".CUR." ".($totvat1-abs($totvat2))."</td>
		</tr>";

	$Report = "
		<h3>VAT Report: $date TO $tdate</h3>
		<table ".TMPL_tblDflts.">
			$out
		</table>";

	include("temp.xls.php");
	Stream("VAT_REPORT", $Report);
	return $Report;

}


?>
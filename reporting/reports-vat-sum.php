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

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "report":
			$OUTPUT=report($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT="Invalid use.";
	}
} else {
	$OUTPUT = seluse();
}

require("../template.php");



function seluse()
{

	$Out = "
		<h3>VAT Report</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='report'>
			<tr>
				<th colspan='2'>Report Criteria</th>
			</tr>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From</td>
				<td>".mkDateSelect("from")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To</td>
				<td>".mkDateSelect("to")."</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='View Report &raquo;'></td>
			</tr>
		</form>
		</table><p>
		<table ".TMPL_tblDflts.">
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='index-reports.php'>Financials</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='index-reports-other.php'>Other Reports</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	return $Out;

}



function report($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	$date = $from_year."-".$from_month."-".$from_day;
	$tdate = $to_year."-".$to_month."-".$to_day;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(!checkdate($from_month, $from_day, $from_year)){
                $v->isOk ($date, "num", 1, 1, "Invalid order date.");
        }

	if(!checkdate($to_month, $to_day, $to_year)){
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
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

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

		$Sl = "SELECT sum(amount) AS amount, sum(vat) AS vat FROM vatreport WHERE date >= '$date' AND date <= '$tdate' AND type = 'OUTPUT' AND cid='$vd[id]'";
		$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
		$data = pg_fetch_array($Ry);

		$amount = sprint($data['amount']);
		$vat = sprint($data['vat']);

		$total1 += $amount;
		$totvat1 += $vat;

		$out .= "
			<tr bgcolor='".bgcolorg()."'>
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
		<tr bgcolor='".bgcolorg()."'>
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
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	while($vd = pg_fetch_array($Ri)) {
		$bgcolor = ($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$Sl = "SELECT sum(amount) AS amount, sum(vat) AS vat FROM vatreport WHERE date >= '$date' AND date <= '$tdate' AND type = 'INPUT' AND cid='$vd[id]'";
		$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
		$data = pg_fetch_array($Ry);

		$amount = sprint($data['amount']);
		$vat = sprint($data['vat']);

		$total2 += $amount;
		$totvat2 += $vat;

		$out .= "
			<tr bgcolor='".bgcolorg()."'>
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
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='right'>Total:</td>
			<td></td>
			<td align='right'>".CUR." $total2</td>
			<td align='right'>".CUR." $totvat2</td>
		</tr>";

	$out .= "
		<tr><td><br></td></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='right'>Total:</td>
			<td></td>
			<td align='right'>".CUR." ".sprint($total1-abs($total2))."</td>
			<td align='right'>".CUR." ".sprint($totvat1-abs($totvat2))."</td>
		</tr>";

// 	<form action='xls/pos-report-user-xls.php' method=post name=form>
// 	<input type=hidden name=key value=report>
// 	<input type=hidden name=cid value='$cid'>
// 	<input type=hidden name=day value='$day'>
// 	<input type=hidden name=mon value='$mon'>
// 	<input type=hidden name=year value='$year'>
// 	<input type=submit name=xls value='Export to spreadsheet'>
// 	</form>

	$Report = "
		<h3>VAT Report: $date TO $tdate</h3>
		<table ".TMPL_tblDflts.">
			$out
			<tr><td><br></td></tr>
		<form action='../xls/reports-vat-sum-xls.php' method='POST' name='form'>
			<input type='hidden' name='key' value='report'>
			<input type='hidden' name='day' value='$from_day'>
			<input type='hidden' name='mon' value='$from_month'>
			<input type='hidden' name='year' value='$from_year'>
			<input type='hidden' name='tday' value='$to_day'>
			<input type='hidden' name='tmon' value='$to_month'>
			<input type='hidden' name='tyear' value='$to_year'>
			<tr>
				<td colspan='4'><input type='submit' name='xls' value='Export to spreadsheet'></td>
			</tr>
		</form>
		</table>
		<p>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index-reports-other.php'>Other Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $Report;

}


?>

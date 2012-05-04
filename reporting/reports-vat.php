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
	$OUTPUT = seluse();
}

require("../template.php");

function seluse()
{

	$types = "
		<select name='type'>
			<option value=0>All</option>
			<option value='INPUT'>INPUT</option>
			<option value='OUTPUT'>OUTPUT</option>
		</select>";

	$reports = "
		<select name='report'>
			<option value='sum'>Total</option>
			<option value='det'>Detailed</option>
		</select>";

	db_conn("cubit");

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ry = db_exec($Sl) or errDie("Unable to vat codes.");

	$users = "
		<select name='cid'>
			<option value='0'>All</option>";
	while($data = pg_fetch_array($Ry)) {
		$users .= "<option value='$data[id]'>$data[code] - $data[description]</option>";
	}
	$users .= "</select>";

	$Out = "
		<h3>VAT Report</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='report'>
			<tr>
				<th colspan='2'>Report Criteria</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Type</td>
				<td>$types</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Report</td>
				<td>$reports</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>VAT Code</td>
				<td>$users</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>From</td>
				<td>".mkDateSelect("from")."</td>
			</tr>
			<tr class='".bg_class()."'>
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
			<tr class='".bg_class()."'><td><a href='index-reports.php'>Financials</a></td></tr>
			<tr class='".bg_class()."'><td><a href='index-reports-other.php'>Other Reports</a></td></tr>
			<tr class='".bg_class()."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	return $Out;

}



function report($_POST)
{

	extract($_POST);

	$date = $from_year."-".$from_month."-".$from_day;
	$tdate = $to_year."-".$to_month."-".$to_day;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cid, "num", 1, 50, "Invalid id.");

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

	$Whe = "";
	if($cid != "0") {
		$whe .= " AND cid='$cid' ";
	} else {
		$whe .= "";
	}

	if($type!="0") {
		$whe .= " AND type='$type' ";
	}


	if($cid!="0") {
		if($report == "sum") {

			$Sl = "SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
			$data = pg_fetch_array($Ry);

			$amount = sprint($data['amount']);
			$vat = sprint($data['vat']);

			$out = "
				<tr>
					<th>Description</th>
					<th>Amount inc VAT</th>
					<th>VAT</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total</td>
					<td align='right'>".CUR." $amount</td>
					<td align='right'>".CUR." $vat</td>
				</tr>";
		} else {
			$Sl = "SELECT * FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");

			$out = "
				<tr>
					<th>Date</th>
					<th>Code</th>
					<th>Ref</th>
					<th>Description</th>
					<th>Amount inc VAT</th>
					<th>VAT</th>
				</tr>";

			$i = 0;

			$total = 0;
			$totvat = 0;

			while($vd = pg_fetch_array($Ry)) {

				$out .= "
					<tr class='".bg_class()."'>
						<td>$vd[date]</td>
						<td>$vd[code]</td>
						<td>$vd[ref]</td>
						<td>$vd[description]</td>
						<td align='right'>".CUR." $vd[amount]</td>
						<td align='right'>".CUR." $vd[vat]</td>
					</tr>";

				$i++;

				$total += $vd['amount'];
				$totvat += $vd['vat'];

			}

			$total = sprint($total);
			$totvat = sprint($totvat);

			$out .= "
				<tr class='".bg_class()."'>
					<td colspan='4'>Total</td>
					<td align='right'>".CUR." $total</td>
					<td align='right'>".CUR." $totvat</td>
				</tr>";
		}

	} else {
		if($report == "sum") {

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unabel to get data.");

			$total = 0;
			$totvat = 0;
			$out = "
				<tr>
					<th>Description</th>
					<th>Amount inc VAT</th>
					<th>VAT</th>
				</tr>";
			$i=1;

			while($vd = pg_fetch_array($Ri)) {
				$Sl = "SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe AND cid='$vd[id]'";
				$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");
				$data = pg_fetch_array($Ry);

				$amount = sprint($data['amount']);
				$vat = sprint($data['vat']);

				$total += $amount;
				$totvat += $vat;

				$out .= "
					<tr class='".bg_class()."'>
						<td>$vd[code]</td>
						<td align='right'>".CUR." $amount</td>
						<td align='right'>".CUR." $vat</td>
					</tr>";
				$i++;
			}

			$total=sprint($total);
			$totvat=sprint($totvat);

			$out .= "
				<tr class='".bg_class()."'>
					<td>Total</td>
					<td align='right'>".CUR." $total</td>
					<td align='right'>".CUR." $totvat</td>
				</tr>";

		} else {
			$Sl = "SELECT * FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry = db_exec($Sl) or errDie("Unable to get vat rec.");

			$out = "
				<tr>
					<th>Code</th>
					<th>Date</th>
					<th>Ref</th>
					<th>Description</th>
					<th>Amount inc VAT</th>
					<th>VAT</th>
				</tr>";

			$i = 0;

			$total = 0;
			$totvat = 0;

			while($vd = pg_fetch_array($Ry)) {

				$amount = sprint($vd['amount']);
				$vat = sprint($vd['vat']);

				$out .= "
					<tr class='".bg_class()."'>
						<td>$vd[code]</td>
						<td>$vd[date]</td>
						<td>$vd[ref]</td>
						<td>$vd[description]</td>
						<td align='right'>".CUR." $amount</td>
						<td align='right'>".CUR." $vat</td>
					</tr>";

				$i++;

				$total+=$vd['amount'];
				$totvat+=$vd['vat'];

			}

			$total=sprint($total);
			$totvat=sprint($totvat);

			$out .= "
					<tr class='".bg_class()."'>
						<td colspan='4'>Total</td>
						<td align='right'>".CUR." $total</td>
						<td align='right'>".CUR." $totvat</td>
					</tr>";
		}
	}

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
			<form action='../xls/reports-vat-xls.php' method='POST' name='form'>
				<input type='hidden' name='key' value='report'>
				<input type='hidden' name='cid' value='$cid'>
				<input type='hidden' name='type' value='$type'>
				<input type='hidden' name='report' value='$report'>
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
				<tr class='".bg_class()."'>
					<td><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports-other.php'>Other Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $Report;

}


?>

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
	$OUTPUT=seluse();
}

require("../template.php");

function seluse()
{

	$types="<select name=type>
	<option value=0>All</option>
	<option value='INPUT'>INPUT</option>
	<option value='OUTPUT'>OUTPUT</option>
	</select>";

	$reports="<select name=report>
	<option value='sum'>Total</option>
	<option value='det'>Detailed</option>
	</select>";

	db_conn("cubit");
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ry=db_exec($Sl) or errDie("Unable to vat codes.");

	$users="<select name=cid>
	<option value=0>All</option>";

	while($data=pg_fetch_array($Ry)) {
		$users.="<option value='$data[id]'>$data[code] - $data[description]</option>";
	}

	$users.="</select>";

	$Out="<h3>VAT Report</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='report'>
	<tr><th colspan=2>Report Criteria</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Type</td><td>$types</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Report</td><td>$reports</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Code</td><td>$users</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>From</td><td>
		<table cellpadding=1 cellspacing=2><tr>
			<td><input type=text size=3 value='".date("d")."' name=day></td>
			<td>-</td>
			<td><input type=text size=3 value='".date("m")."' name=mon></td>
			<td>-</td>
			<td><input type=text size=5 value='".date("Y")."' name=year></td>
		</tr></table>
	</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>To</td><td>
		<table cellpadding=1 cellspacing=2><tr>
			<td><input type=text size=3 value='".date("d")."' name=tday></td>
			<td>-</td>
			<td><input type=text size=3 value='".date("m")."' name=tmon></td>
			<td>-</td>
			<td><input type=text size=5 value='".date("Y")."' name=tyear></td>
		</tr></table>
	</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='View Report &raquo;'></td></tr>
	</form>
	</table><p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $Out;
}

function report($HTTP_POST_VARS)
{
	extract($HTTP_POST_VARS);

	$date = $year."-".$mon."-".$day;
	$tdate = $tyear."-".$tmon."-".$tday;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cid, "num", 1, 50, "Invalid id.");

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
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn('cubit');

	if($cid!="0") {
		$whe=" AND cid='$cid'";
	} else {
		$whe="";
	}

	if($type!="0") {
		$whe=" AND type='$type'";
	}

	if($cid!="0") {
		if($report=="sum") {

			$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
			$data=pg_fetch_array($Ry);

			$amount=sprint($data['amount']);
			$vat=sprint($data['vat']);

			$out="<tr><th>Description</th><th>Amount inc VAT</th><th>VAT</th></tr>
			<tr><td>Total</td><td align=right>".CUR." $amount</td><td align=right>".CUR." $vat</td></tr>";
		} else {
			$Sl="SELECT * FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");

			$out="<tr><th>Date</th><th>Code</th><th>Ref</th><th>Description</th><th>Amount inc VAT</th><th>VAT</th></tr>";

			$i=0;

			$total=0;
			$totvat=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				//$amount=sprint($data['amount']);
				//$vat=sprint($data['vat']);

				$out.="<tr><td>$vd[date]</td><td>$vd[code]</td><td>$vd[ref]</td><td>$vd[description]</td>
				<td align=right>".CUR." $vd[amount]</td><td align=right>".CUR." $vd[vat]</td></tr>";

				$i++;

				$total+=$vd['amount'];
				$totvat+=$vd['vat'];

			}

			$total=sprint($total);
			$totvat=sprint($totvat);

			$out.="<tr><td colspan=4>Total</td><td align=right>".CUR." $total</td><td align=right>".CUR." $totvat</td></tr>";
		}

	} else {
		if($report=="sum") {

			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unabel to get data.");

			$total=0;
			$totvat=0;
			$out="<tr><th>Description</th><th>Amount inc VAT</th><th>VAT</th></tr>";
			$i=1;

			while($vd=pg_fetch_array($Ri)) {
				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe AND cid='$vd[id]'";
				$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
				$data=pg_fetch_array($Ry);

				$amount=sprint($data['amount']);
				$vat=sprint($data['vat']);

				$total+=$amount;
				$totvat+=$vat;

				$out.="<tr><td>$vd[code]</td><td align=right>".CUR." $amount</td><td align=right>".CUR." $vat</td></tr>";
				$i++;
			}

			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$total=sprint($total);
			$totvat=sprint($totvat);

			$out.="<tr><td>Total</td><td align=right>".CUR." $total</td><td align=right>".CUR." $totvat</td></tr>";

		} else {
			$Sl="SELECT * FROM vatreport WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");

			$out="<tr><th>Date</th><th>Code</th><th>Ref</th><th>Description</th><th>Amount inc VAT</th><th>VAT</th></tr>";

			$i=0;

			$total=0;
			$totvat=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$amount=sprint($vd['amount']);
				$vat=sprint($vd['vat']);

				$out.="<tr><td>$vd[date]</td><td>$vd[code]</td><td>$vd[ref]</td><td>$vd[description]</td>
				<td align=right>".CUR." $amount</td><td align=right>".CUR." $vat</td></tr>";

				$i++;

				$total+=$vd['amount'];
				$totvat+=$vd['vat'];

			}

			$total=sprint($total);
			$totvat=sprint($totvat);

			$out.="<tr><td colspan=4>Total</td><td align=right>".CUR." $total</td><td align=right>".CUR." $totvat</td></tr>";
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
	$Report="<h3>VAT Report: $date TO $tdate</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>

	$out
	</table>";

	include("temp.xls.php");
	Stream("VAT_REPORT", $Report);

	return $Report;
}
?>

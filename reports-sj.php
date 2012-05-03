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

require("settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "report":
			$OUTPUT=report($_POST);
			break;
		case "export":
			$OUTPUT=export($_POST);
			break;
		default:
			$OUTPUT="Invalid use.";
	}
} else {
	$OUTPUT=seluse();
}

require("template.php");

function seluse()
{

	$reports="<select name=report>
	<option value='sum'>Total</option>
	<option value='det'>Detailed</option>
	</select>";

	db_conn('cubit');

	$Sl="SELECT cusnum,surname FROM customers ORDER BY surname";
	$Ri=db_exec($Sl) or errDie("Unable to get customer data.");

	$customers="<select name=cid>
	<option value='all'>All</option>
	<option value='0'>Cash</option>";

	while($cd=pg_fetch_array($Ri)) {
		$customers.="<option value='$cd[cusnum]'>$cd[surname]</option>";
	}

	$customers.="</option>";

	$Out="<h3>Sales Journal</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='report'>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Customer</td><td>$customers</td></tr>
	<tr class='bg-even'><td>Type</td><td>$reports</td></tr>
	<tr class='bg-odd'><td>From</td><td>
		<table cellpadding=1 cellspacing=2><tr>
			<td><input type=text size=3 value='1' name=day></td>
			<td>-</td>
			<td><input type=text size=3 value='".date("m")."' name=mon></td>
			<td>-</td>
			<td><input type=text size=5 value='".date("Y")."' name=year></td>
		</tr></table>
	</td></tr>
	<tr class='bg-even'><td>To</td><td>
		<table cellpadding=1 cellspacing=2><tr>
			<td><input type=text size=3 value='".date("d")."' name=tday></td>
			<td>-</td>
			<td><input type=text size=3 value='".date("m")."' name=tmon></td>
			<td>-</td>
			<td><input type=text size=5 value='".date("Y")."' name=tyear></td>
		</tr></table>
	</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='View Journal &raquo;'></td></tr>
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
	$tdate = $tyear."-".$tmon."-".$tday;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cid, "string", 1, 50, "Invalid cid.");

	if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid from date.");
        }

	if(!checkdate($tmon, $tday, $tyear)){
                $v->isOk ($tdate, "num", 1, 1, "Invalid to date.");
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

	if($cid!="all") {
		$whe=" AND cid='$cid'";
	} else {
		$whe="";
	}


	if($cid!="all") {
		if($report=="sum") {

			$Sl="SELECT sum(exl) AS exl,sum(vat) AS vat,sum(inc) AS inc FROM sj WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");
			$data=pg_fetch_array($Ry);

			$exl=sprint($data['exl']);
			$vat=sprint($data['vat']);
			$inc=sprint($data['inc']);

			$out="<tr><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>
			<tr class='bg-odd'><td>Total</td><td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";
		} else {
			$Sl="SELECT * FROM sj WHERE date>='$date' AND date<='$tdate' $whe ORDER BY id";
			$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");

			$out="<tr><th>Date</th><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";

			$i=0;

			$totexl=0;
			$totvat=0;
			$totinc=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$out.="<tr class='bg-odd'><td>$vd[date]</td><td>$vd[des]</td>
				<td align=right>".CUR." $vd[exl]</td><td align=right>".CUR." $vd[vat]</td><td align=right>".CUR." $vd[inc]</td></tr>";

				$i++;

				$totexl+=$vd['exl'];
				$totvat+=$vd['vat'];
				$totinc+=$vd['inc'];
			}

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr class='bg-odd'><td colspan=2>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";
		}

	} else {
		if($report=="sum") {

			$Sl="SELECT DISTINCT cid,name FROM sj ORDER BY name";
			$Ri=db_exec($Sl) or errDie("Unable to get sales journal.");

			$totexl=0;
			$totvat=0;
			$totinc=0;
			$out="<tr><th>Customer</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";
			$i=1;

			while($vd=pg_fetch_array($Ri)) {
				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$Sl="SELECT sum(exl) AS exl,sum(vat) AS vat, sum(inc) AS inc FROM sj WHERE date>='$date' AND date<='$tdate' $whe AND cid='$vd[cid]'";
				$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");
				$data=pg_fetch_array($Ry);

				$exl=sprint($data['exl']);
				$vat=sprint($data['vat']);
				$inc=sprint($data['inc']);

				$totexl+=$exl;
				$totvat+=$vat;
				$totinc+=$inc;

				$out.="<tr bgcolor='$bgcolor'><td>$vd[name]</td><td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";
				$i++;
			}

			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr bgcolor='$bgcolor'><td>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";

		} else {
			$Sl="SELECT * FROM sj WHERE date>='$date' AND date<='$tdate' $whe  ORDER BY id";
			$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");

			$out="<tr><th>Date</th><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";

			$i=0;

			$totexl=0;
			$totvat=0;
			$totinc=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$exl=sprint($vd['exl']);
				$vat=sprint($vd['vat']);
				$inc=sprint($vd['inc']);

				$out.="<tr class='bg-odd'><td>$vd[date]</td><td>$vd[des]</td>
				<td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";

				$i++;

				$totexl+=$vd['exl'];
				$totvat+=$vd['vat'];
				$totinc+=$vd['inc'];

			}

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr class='bg-odd'><td colspan=2>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";
		}
	}

	$Report="<h3>Sales Journal: $date TO $tdate</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	$out
	<tr><td><br></td></tr>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=export>
	<input type=hidden name=cid value='$cid'>
	<input type=hidden name=report value='$report'>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=mon value='$mon'>
	<input type=hidden name=year value='$year'>
	<input type=hidden name=tday value='$tday'>
	<input type=hidden name=tmon value='$tmon'>
	<input type=hidden name=tyear value='$tyear'>
	<tr><td colspan=4><input type=submit name=xls value='Export to spreadsheet'></td></tr>
	</form>
	</table>
	<p>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $Report;
}

function export($_POST)
{
	extract($_POST);

	$date = $year."-".$mon."-".$day;
	$tdate = $tyear."-".$tmon."-".$tday;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($cid, "string", 1, 50, "Invalid cid.");

	if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid from date.");
        }

	if(!checkdate($tmon, $tday, $tyear)){
                $v->isOk ($tdate, "num", 1, 1, "Invalid to date.");
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

	if($cid!="all") {
		$whe=" AND cid='$cid'";
	} else {
		$whe="";
	}


	if($cid!="all") {
		if($report=="sum") {

			$Sl="SELECT sum(exl) AS exl,sum(vat) AS vat,sum(inc) AS inc FROM sj WHERE date>='$date' AND date<='$tdate' $whe";
			$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");
			$data=pg_fetch_array($Ry);

			$exl=sprint($data['exl']);
			$vat=sprint($data['vat']);
			$inc=sprint($data['inc']);

			$out="<tr><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>
			<tr><td>Total</td><td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";
		} else {
			$Sl="SELECT * FROM sj WHERE date>='$date' AND date<='$tdate' $whe ORDER BY id";
			$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");

			$out="<tr><th>Date</th><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";

			$i=0;

			$totexl=0;
			$totvat=0;
			$totinc=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$out.="<tr><td>$vd[date]</td><td>$vd[des]</td>
				<td align=right>".CUR." $vd[exl]</td><td align=right>".CUR." $vd[vat]</td><td align=right>".CUR." $vd[inc]</td></tr>";

				$i++;

				$totexl+=$vd['exl'];
				$totvat+=$vd['vat'];
				$totinc+=$vd['inc'];
			}

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr><td colspan=2>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";
		}

	} else {
		if($report=="sum") {

			$Sl="SELECT DISTINCT cid,name FROM sj ORDER BY name";
			$Ri=db_exec($Sl) or errDie("Unable to get sales journal.");

			$totexl=0;
			$totvat=0;
			$totinc=0;
			$out="<tr><th>Customer</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";
			$i=1;

			while($vd=pg_fetch_array($Ri)) {
				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$Sl="SELECT sum(exl) AS exl,sum(vat) AS vat, sum(inc) AS inc FROM sj WHERE date>='$date' AND date<='$tdate' $whe AND cid='$vd[cid]'";
				$Ry=db_exec($Sl) or errDie("Unable to get sales journal.");
				$data=pg_fetch_array($Ry);

				$exl=sprint($data['exl']);
				$vat=sprint($data['vat']);
				$inc=sprint($data['inc']);

				$totexl+=$exl;
				$totvat+=$vat;
				$totinc+=$inc;

				$out.="<tr><td>$vd[name]</td><td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";
				$i++;
			}

			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr><td>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";

		} else {
			$Sl="SELECT * FROM sj WHERE date>='$date' AND date<='$tdate' $whe  ORDER BY id";
			$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");

			$out="<tr><th>Date</th><th>Description</th><th>Amount exl VAT</th><th>VAT</th><th>Amount inc VAT</th></tr>";

			$i=0;

			$totexl=0;
			$totvat=0;
			$totinc=0;

			while($vd=pg_fetch_array($Ry)) {

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$exl=sprint($vd['exl']);
				$vat=sprint($vd['vat']);
				$inc=sprint($vd['inc']);

				$out.="<tr><td>$vd[date]</td><td>$vd[des]</td>
				<td align=right>".CUR." $exl</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $inc</td></tr>";

				$i++;

				$totexl+=$vd['exl'];
				$totvat+=$vd['vat'];
				$totinc+=$vd['inc'];

			}

			$totexl=sprint($totexl);
			$totvat=sprint($totvat);
			$totinc=sprint($totinc);

			$out.="<tr><td colspan=2>Total</td><td align=right>".CUR." $totexl</td><td align=right>".CUR." $totvat</td><td align=right>".CUR." $totinc</td></tr>";
		}
	}

	$Report="<h3>Sales Journal: $date TO $tdate</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	$out
	</table>";

	$OUTPUT=$Report;

	include("xls/temp.xls.php");
	Stream("Report", $OUTPUT);
}
?>

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

require ("settings.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "report":
			$OUTPUT = report_display($HTTP_POST_VARS);
			break;
		case "Export to Spreadsheet":
			$OUTPUT = export_display($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = report_options();
	}
} else {
	$OUTPUT = report_options();
}

require ("template.php");



function report_options()
{

	$rslt = qrySalesPerson(false, "salesp");
	if ($rslt->num_rows() < 1) {
		return "<li class='err'> There are no Sales People found in Cubit.</li>";
	}else{
		$salesps = "<select name='sp'>";
		$salesps .= "<option value='0'>All</option>";
		while ($salesp = $rslt->fetch_array()) {
			$salesps .= "<option value='$salesp[salesp]'>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}

	$repops = "
		<h3>Commission Report</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='report'>
			<tr>
				<th colspan='3'>Select Sales Rep</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Sales Rep</td>
				<td colspan='2'>$salesps</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='3'>Select Report Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
				<td>To</td>
				<td>".mkDateSelect("to")."</td>
			</tr>
			<tr>
				<td valign='bottom' align='right' colspan='3'><input type='submit' value='Search &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/salesp-add.php'>Add Sales Person</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sales-reports.php'>Sales Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $repops;

}



# produce report
function report_display ($HTTP_POST_VARS)
{
	
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}
	if($fromdate>$todate){
		$v->isOk ($todate, "num", 1, 1, "The From date cannot be bigger than the To date!");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm.report_options();
	}

	if($sp != "0") {$Whe = " WHERE salesp = '$sp'";} else {$Whe = "WHERE 1 = 1";}

	$ComRep = "
		<h3>Commission Report $fromdate TO $todate</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Inv</th>
				<th>Date</th>
				<th>VAT Excl. Invoice Amount</th>
				<th>Commission</th>
			</tr>";

	db_conn ("exten");

	$i = 0;
	$total_amount = 0;
	$total_com = 0;

	$Sl = "SELECT salespid,salesp FROM salespeople $Whe AND div = '".USER_DIV."' ORDER BY salesp ASC";
	$RsSr = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
	if (pg_numrows ($RsSr) < 1) {
		return "<li>There are no Sales People in Cubit.</li>";
	}

	db_conn ("cubit");

	while ($SalesReps = pg_fetch_array ($RsSr)) {
// 		$Sl = "SELECT SUM(com) as amount FROM coms_invoices WHERE invdate >= '$fromdate' AND invdate <= '$todate' AND rep='$SalesReps[salesp]' ";
// 		$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
// 		$Tp = pg_fetch_array($Rs);
// 		$Tp['amount'] = sprint($Tp['amount']);

		$ComRep .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><b>$SalesReps[salesp]</b></td>
				<td colspan='2'></td>
				<td></td>
			</tr>";

		$sql = "SELECT * FROM coms_invoices WHERE invdate >= '$fromdate' AND invdate <= '$todate' AND rep='$SalesReps[salesp]'";
		$Rx = db_exec($sql) or errDie(" Unable to get invoices from db.");

		$rep_com = 0;
		$rep_amount = 0;
		while ($inv = pg_fetch_array($Rx)) {
			$ComRep .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[inv]</td>
					<td>$inv[invdate]</td>
					<td align='right'>".CUR." $inv[amount]</td>
					<td align='right'>".CUR." $inv[com]</td>
				</tr>";
			$rep_com += sprint($inv['com']);
			$rep_amount += sprint($inv['amount']);
		}

		$ComRep .= "
			<tr bgcolor='".bgcolorg()."'>
				<td></td>
				<td><b>Total:</b></td>
				<td align='right'><b>".CUR." ".sprint ($rep_amount)."</b></td>
				<td align='right'><b>".CUR." ".sprint ($rep_com)."</b></td>
			</tr>";

		$ComRep .= TBL_BR;
		$total_amount += $rep_amount;
		$total_com += $rep_com;
		$i++;
	}
	$total = sprint($total);

	$ComRep .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>Total Reps: $i</td>
			<td align='right' colspan='2'><b>".CUR." $total_amount</b></td>
			<td align='right'><b>".CUR." $total_com</b></td>
		</tr>
		<tr><td><br></td></tr>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='sp' value='$sp'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>
			<tr><td><input type='submit' name='key' value='Export to Spreadsheet'>
		</form>";

	$ComRep .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/salesp-add.php'>Add Sales Person</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sales-reports.php'>Sales Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $ComRep;

}


# produce report
function export_display ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}
	if($fromdate > $todate){
		$v->isOk ($todate, "num", 1, 1, "The From date cannot be bigger than the To date!");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm.report_options();
	}



	if($sp != "0") {$Whe = " WHERE salesp='$sp'";} else {$Whe = "WHERE 1 = 1";}

	$ComRep = "
		<h3>Commission Report $fromdate TO $todate</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Inv</th>
				<th>Invoice Date</th>
				<th>VAT Excl. Invoice Amount</th>
				<th>Commission</th>
			</tr>";

	db_conn ("exten");

	$i = 0;
	$total_amount = 0;
	$total_com = 0;
	$Sl = "SELECT salespid,salesp FROM salespeople $Whe AND div = '".USER_DIV."' ORDER BY salesp ASC";
	$RsSr = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
	if (pg_numrows ($RsSr) < 1) {
		return "<li>There are no Sales People in Cubit.</li>";
	}

	db_conn ("cubit");

	while ($SalesReps = pg_fetch_array ($RsSr)) {
// 		$Sl = "SELECT SUM(com) as amount FROM coms_invoices WHERE invdate >= '$fromdate' AND invdate <= '$todate' AND rep='$SalesReps[salesp]' ";
// 		$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
// 		$Tp = pg_fetch_array($Rs);
// 		$Tp['amount'] =sprint($Tp['amount']);

		$ComRep .= "
			<tr>
				<td><b>$SalesReps[salesp]</b></td>
				<td colspan='2'></td>
				<td align='right'></td>
			</tr>";

		$Sl = "SELECT * FROM coms_invoices WHERE invdate >= '$fromdate' AND invdate <= '$todate' AND rep='$SalesReps[salesp]'";
		$Rx = db_exec($Sl) or errDie(" Unable to get invoices from db.");

		$rep_com = 0;
		$rep_amount = 0;
		while ($inv = pg_fetch_array($Rx)) {
			$ComRep .= "
				<tr>
					<td>$inv[inv]</td>
					<td>$inv[invdate]</td>
					<td align='right'>".CUR." $inv[amount]</td>
					<td align='right'>".CUR." $inv[com]</td>
				</tr>";
			$rep_com += sprint($inv['com']);
			$rep_amount += sprint($inv['amount']);
		}

		$ComRep .= "
			<tr>
				<td></td>
				<td><b>Total:</b></td>
				<td align='right'><b>".CUR." ".sprint ($rep_amount)."</b></td>
				<td align='right'><b>".CUR." ".sprint ($rep_com)."</b></td>
			</tr>";

		$ComRep .= "<tr><td><br></td></tr>";
		$i++;
		$total_amount += $rep_amount;
		$total_com += $rep_com;
	}
	$total = sprint($total);

	$ComRep .= "
		<tr>
			<td>Total Reps: $i</td>
			<td align='right' colspan='2'>".CUR." $total_amount</td>
			<td align='right'>".CUR." $total_com</td>
		</tr>";

	$ComRep .= "</table>";

	$OUTPUT = $ComRep;

	include("xls/temp.xls.php");
	Stream("Report", $OUTPUT);

	return $ComRep;

}


?>
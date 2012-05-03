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

if(isset($_POST["sum"])){
	$OUTPUT = sum($_POST);
} elseif(isset($_POST["all"])) {
	$OUTPUT = all($_POST);
} else {
	$OUTPUT = sel();
}

$OUTPUT .= "
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='reporting/index-reports.php'>Financials</a></td>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='reporting/index-reports-other.php'>Other Reports</a></td>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";

require("template.php");




function sel()
{

	$out = "
		<h3>Sales Report (POS Invoices Only)</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<tr>
				<th colspan='2'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center' colspan='2'>
					".mkDateSelect("from")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='submit' name='sum' value='Summary'></td>
				<td align='right'><input type='submit' name='all' value='All Pos Sales'></td>
			</tr>
		</form>
		</table>";
	return $out;

}




function sum($_POST)
{

	extract($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	db_conn('cubit');

	$Sl = "SELECT distinct(cust) FROM pr WHERE pdate>='$fromdate' AND pdate<='$todate' ORDER BY cust";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$out = "
		<h3>Sales Report (POS Invoices Only)</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Customer</th>
				<th>Amount</th>
			</tr>";

	$i = 0;
	$tot = 0;

	while($pd = pg_fetch_array($Ri)) {

		$Sl = "SELECT sum(amount) AS amount FROM pr WHERE pdate>='$fromdate' AND pdate<='$todate' AND cust='$pd[cust]'";
		$Rd = db_exec($Sl) or errDie("Unable to get data.");

		$sd = pg_fetch_array($Rd);

		$out .= "
			<tr class='".bg_class()."'>
				<td>$pd[cust]</td>
				<td align='right'>".CUR." $sd[amount]</td>
			</tr>";

		$i++;

		$tot += $sd['amount'];

	}

	$tot = sprint($tot);

	$out .= "
			<tr class='".bg_class()."'>
				<td>Total</td>
				<td align='right'>".CUR." $tot</td>
			</tr>
		</table><p>
		<form action='xls/pos-report-sales-xls.php' method='POST' name='form'>
			<input type='hidden' name='key' value='report'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>
			<input type='hidden' name='sum' value=''>
			<input type='submit' name='xls' value='Export to spreadsheet'>
		</form>";
	return $out;

}



function all($_POST)
{

	extract($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	db_conn('cubit');

	$Sl = "SELECT * FROM pr WHERE pdate >= '$fromdate' AND pdate <= '$todate' ORDER BY cust";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$out = "
		<h3>Pos Sales Report</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Customer</th>
				<th>Date</th>
				<th>Inv</th>
				<th>Amount</th>
			</tr>";

	$i = 0;
	$tot = 0;

	while($pd = pg_fetch_array($Ri)) {

		$out .= "
			<tr class='".bg_class()."'>
				<td>$pd[cust]</td>
				<td>$pd[pdate]</td>
				<td>$pd[inv]</td>
				<td align='right'>".CUR." $pd[amount]</td>
			</tr>";
		$i++;
		$tot += $pd['amount'];

	}

	$tot = sprint($tot);

	$out .= "
			<tr class='".bg_class()."'>
				<td>Total</td>
				<td></td>
				<td></td>
				<td align='right'>".CUR." $tot</td>
			</tr>
		</table>
		<p>
		<form action='xls/pos-report-sales-xls.php' method='POST' name='form'>
			<input type='hidden' name='key' value='report'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>
			<input type='hidden' name='all' value=''>
			<input type='submit' name='xls' value='Export to spreadsheet'>
		</form>";
	return $out;

}


?>
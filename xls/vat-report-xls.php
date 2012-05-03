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

# get settings
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "view":
			if(isset($_POST["amt"])){
				$OUTPUT = viewRepAmt($_POST);
				break;
			}else{
				$OUTPUT = viewRep($_POST);
				break;
			}
			break;
		default:
			$OUTPUT = view();
	}
} else {
	$OUTPUT = view();
}

# get templete
require("../template.php");



# Default view
function view()
{

	// Layout
	$view = "
				<h3>Vat Report<h3>
				<table ".TMPL_tblDflts." width='400'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th>Transactions By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'>
							".mkDateSelect("from",date("Y"),date("m"),"01")."
							&nbsp;TO&nbsp;
							".mkDateSelect("to")."
						</td>
						<td valign='bottom'><input type='submit' name='srch' value='Search'></td>
					</tr>
					".TBL_BR."
					<tr>
						<th>Vat Balance</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><input type='submit' name='amt' value='View Amount'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>
			";
	return $view;

}



# Default view
function viewRep($_POST)
{

	# Get vars
	extract ($_POST);

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

	# Set up table to display in
	$printRep = "
				    <table>
						<tr>
							<th colspan='2'><h3>Vat Report: $fromdate TO $todate</h3></th>
						</tr>
						".TBL_BR."
					    <tr>
					    	<th><u>Date</u></th>
					    	<th><u>Reference</u></th>
					    	<th><u>Description</u></th>
					    	<th><u>Amount</u></th>
					    </tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' ORDER BY edate DESC";
        $vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
		if (pg_numrows ($vatRslt) < 1) {
			$vattot = 0;
			$printRep .= "
							<tr>
								<td colspan='4'><li>No previous vat Transactions.</li></td>
							</tr>";
		}else{
			# connect to database
			db_connect ();
			$vatout = 0;
			$vatin = 0;
			$vattot = 0;
			while($vat = pg_fetch_array($vatRslt)){

				# format date
				$vat['edate'] = explode("-", $vat['edate']);
				$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

				$printRep .= "
								<tr>
									<td>$vat[edate]</td>
									<td>$vat[ref]</td>
									<td>$vat[descript]</td>
									<td>".CUR." $vat[amount]</td>
								</tr>";
				$vattot += $vat['amount'];
				$i++;
			}
		}

	// Layout
	$printRep .= "<tr><td colspan=3><b>Total vat balance</b></td><td align=right><b>".CUR." $vattot</b></td></tr>
	</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("VatReport", $printRep);

}



# Default view
function viewRepAmt($_POST)
{

	# Get vars
	extract ($_POST);

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

	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate'  AND chrgvat='VATIN' AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND chrgvat='VATOUT' AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);
	$pvat['sum'] = sprint($pvat['sum'] * (-1));

	$totbal = sprint($rvat['sum'] - $pvat['sum']);

	# Set up table to display in
	$printRep = "
				    <table>
						<tr>
							<th colspan='2'><h3>Vat Report: $fromdate TO $todate</h3></th>
						</tr>
						".TBL_BR."
						<tr>
							<td>Total Input Vat</td>
							<td>".CUR." $pvat[sum]</td>
						</tr>
						<tr>
							<td>Total Output Vat</td>
							<td>".CUR." $rvat[sum]</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><b>Total Vat Balance</b></td>
							<td><b>".CUR." $totbal</b></td>
						</tr>
					</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("VatReport", $printRep);

}


?>
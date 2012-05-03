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
require ("core-settings.php");


if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			require_lib("docman");
			$OUTPUT = printPurch ($_POST);
			break;
	case "export":
			$OUTPUT = export ($_POST);
			break;

		default:
			$OUTPUT = slct ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct ();
}

require ("template.php");

# Default view
function slct()
{
	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class=err>ERROR : There are no periods set for the current year";
	}
	$Prds = "<select name=prd>";
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$Prds .= "</select>";

	//layout
	$slct = "
	<h3>View Returned International Non Stock Orders</h3>
	<table ".TMPL_tblDflts." width=460>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=view>
		<tr>
			<th colspan=2>By Date Range</th>
		</tr>
		<tr class='bg-odd'>
			<td align=center colspan=2>
				".mkDateSelect("from",date("Y"),date("m"),"01")."
				&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
				".mkDateSelect("to")."
			</td>
		</tr>
		<tr>
			<td colspan='2' align='right'><input type='submit' value='Search'></td>
		</tr>
	</form>
	</table>
	<p>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'>
			<td><a href='purchase-new.php'>New Purchase</a></td>
		</tr>
		<tr class='bg-odd'>
			<td><a href='purchase-view.php'>View Purchases</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'>
		</tr>
	</table>";

	return $slct;
}

# show stock
function printPurch ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
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

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "<center>
	<h3>Returned International Non Stock Orders</h3>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Purchase No.</th>
		<th>Return Date</th>
		<th>Supplier</th>
		<th>Total Cost Returned</th>
		<th>Options</th>
	</tr>";

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = $from_month; $i <= $to_month; $i++) {
		$schema = (int)$i;

		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".rnons_purch_int WHERE rdate >= '$fromdate' AND rdate <= '$todate' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY rdate DESC";

	# Query server
	$i = 0;
	$tot = 0;
    $stkpRslt = db_exec ($query) or errDie ("Unable to retrieve stock Order from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li> There are no Returned Non Stock Orders found.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'>
			<td><a href='purchase-new.php'>New Purchase</a></td>
		</tr>
		<tr class='bg-odd'>
			<td><a href='purchase-view.php'>View Purchases</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'>
		</tr>
		</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)){
		$prd = $stkp["query_schema"];

		# date format
        $date = explode("-", $stkp['rdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		db_conn($prd);
		$sql = "SELECT * FROM rnons_purch_int WHERE purid='$stkp[purid]'";
		$pi_rslt = db_exec($sql) or errDie("Unable to retrieve order information from Cubit.");
		$pi_data = pg_fetch_array($pi_rslt);

		db_conn("cubit");
		$sql = "SELECT symbol FROM currency WHERE fcid='$pi_data[fcid]'";
		$fc_rslt = db_exec($sql) or errDie("Unable to retrieve currency information from Cubit.");
		$symbol = pg_fetch_result($fc_rslt, 0);

		$tot += $pi_data['subtot'];

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printOrd .= "<tr bgcolor='$bgColor'>
			<td>$stkp[purnum]</td>
			<td>$date</td>
			<td>$stkp[supplier]</td>
			<td align=right>".CUR." $stkp[subtot]</td>
			<td><a href='nons-purch-int-det-ret.php?rpurid=$stkp[purid]&prd=$prd'>Details</a></td>
		</tr>";
		$i++;
	}
	$tot = sprint($tot);

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	$printOrd .= "<tr bgcolor='$bgColor'><td colspan=3>Totals</td><td align=right>".CUR." $tot</td><td><br></td></tr>

	<tr><td><br></td></tr>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='export'>
	<input type=hidden name=prd value='$prd'>
	<input type=hidden name=fday value='$from_day'>
	<input type=hidden name=fmon value='$from_month'>
	<input type=hidden name=fyear value='$from_year'>
	<input type=hidden name=today value='$to_day'>
	<input type=hidden name=tomon value='$to_month'>
	<input type=hidden name=toyear value='$to_year'>
	<tr><td colspan=4><input type=submit value='Export to Spreadsheet'></td></tr>
	</form>

	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='purch-int-new.php'>New International Order</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='stock-report.php'>Stock Control Reports</a></td></tr>
		<tr class='bg-even'><td><a href='stock-view.php'>View Stock</a></td></tr>
	</table>";

	return $printOrd;
}

function export ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
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

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "<center>
	<h3>Returned International Non Stock Orders</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Purchase No.</th><th>Return Date</th><th>Supplier</th><th>Total Cost Returned</th></tr>";

	# Connect to database
	db_conn ($prd);
	# Query server
	$i = 0;
	$tot = 0;
    $sql = "SELECT * FROM purchint_ret WHERE rdate >= '$fromdate' AND rdate <= '$todate' AND div = '".USER_DIV."' ORDER BY rdate DESC";
    $stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Order from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li> There are no Returned Non Stock Orders found.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='purchase-new.php'>New Purchase</a></td></tr>
		<tr class='bg-odd'><td><a href='purchase-view.php'>View Purchases</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)){
		# date format
        $date = explode("-", $stkp['rdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		$tot += $stkp['subtot'];

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printOrd .= "<tr><td>$stkp[purnum]</td><td>$date</td><td>$stkp[supname]</td><td align=right>".CUR." $stkp[subtot]</td></tr>";
		$i++;
	}
	$tot = sprint($tot);

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	$printOrd .= "<tr><td colspan=3>Totals</td><td align=right>".CUR." $tot</td><td><br></td></tr>
	</table>";

	include("xls/temp.xls.php");
	Stream("Purchases", $printOrd);

	return $printOrd;
}

?>

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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "view":
			require_lib("docman");
			$OUTPUT = printPurch ($HTTP_POST_VARS);
			break;
		case "export":
			$OUTPUT = export ($HTTP_POST_VARS);
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
		return "<li class='err'>ERROR : There are no periods set for the current year.</li>";
	}
	$Prds = "<select name='prd'>";
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$Prds .= "</select>";

	db_connect ();

	$supplier_drop = "<select name='supplier'>";
	$supplier_drop .= "<option value='0'>All Suppliers</option>";
	$get_supps = "SELECT * FROM suppliers WHERE blocked IS NULL or blocked != 'yes' ORDER BY supname";
	$run_supps = db_exec ($get_supps) or errDie ("Unable to get supplier information");
	if (pg_numrows ($run_supps) > 0){
		while ($sarr = pg_fetch_array ($run_supps)){
			$supplier_drop .= "<option value='$sarr[supid]'>$sarr[supname]</option>";
		}
	}
	$supplier_drop .= "</select>";

	//layout
	$slct = "
		<h3>View Returned Stock Orders</h3>
		<table ".TMPL_tblDflts." width='500'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr>
				<th>Supplier(s)</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$supplier_drop</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-new.php'>New Purchase</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-view.php'>View Purchases</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



# show stock
function printPurch ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

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
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
		<center>
		<h3>Returned Stock Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase No.</th>
				<th>Supp Inv No.</th>
				<th>Return Date</th>
				<th>Supplier</th>
				<th>Total Cost Returned</th>
				<th>Options</th>
			</tr>";

	$supsql = "";
	if (isset ($supplier) AND $supplier != 0){
		$supsql = " AND supid = '$supplier'";
	}

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = $from_month; $i <= $to_month; $i++) {
		$schema = (int)$i;

		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".purch_ret WHERE rdate >= '$fromdate' AND rdate <= '$todate' AND div = '".USER_DIV."' $supsql";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY rdate DESC";

	$i = 0;
	$tot = 0;
	$stkpRslt = db_exec ($query) or errDie ("Unable to retrieve stock Order from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li class='err'> No Returned Stock Orders found.</li>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purchase-new.php'>New Purchase</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purchase-view.php'>View Purchases</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)){
		$prd = $stkp["query_schema"];

		# date format
		$date = explode("-", $stkp['rdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		$tot += $stkp['subtot'];

		$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stkp[purnum]</td>
				<td>$stkp[supinv]</td>
				<td>$date</td>
				<td>$stkp[supname]</td>
				<td align='right'>".CUR." $stkp[subtot]</td>
				<td><a href='purch-det-ret.php?rpurid=$stkp[rpurid]&prd=$prd'>Details</a></td>
			</tr>";
		$i++;
	}
	$tot = sprint($tot);

	$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>Totals</td>
				<td align='right'>".CUR." $tot</td>
				<td><br></td>
			</tr>
			<tr><td><br></td></tr>
			<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>
			<tr>
				<td colspan='2'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
			</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-new.php'>New Order</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
		</table>";
	return $printOrd;

}



function export ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

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
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# Set up table to display in
	$printOrd = "
		<center>
		<h3>Returned Stock Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase No.</th>
				<th>Supp Inv No.</th>
				<th>Return Date</th>
				<th>Supplier</th>
				<th>Total Cost Returned</th>
			</tr>";

	# Connect to database
	db_conn ($prd);

	# Query server
	$i = 0;
	$tot = 0;
	$sql = "SELECT * FROM purch_ret WHERE rdate >= '$fromdate' AND rdate <= '$todate' AND div = '".USER_DIV."' ORDER BY rdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Order from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li> No Returned Stock Orders found.</li>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purchase-new.php'>New Purchase</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='purchase-view.php'>View Purchases</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)){

		# date format
		$date = explode("-", $stkp['rdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		$tot += $stkp['subtot'];

		$printOrd .= "
			<tr>
				<td>$stkp[purnum]</td>
				<td>$stkp[supinv]</td>
				<td>$date</td>
				<td>$stkp[supname]</td>
				<td align='right'>".CUR." $stkp[subtot]</td>
			</tr>";
		$i++;
	}
	$tot = sprint($tot);

	$printOrd .= "
			<tr>
				<td colspan='4'>Totals</td>
				<td align='right'>".CUR." $tot</td>
			</tr>
		</table>";

	include("xls/temp.xls.php");
	Stream("Purchases", $printOrd);
	return $printOrd;

}


?>

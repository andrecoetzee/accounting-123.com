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
		case "view":
			$OUTPUT = printInv($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct();
	}
} else {
	$OUTPUT = slct();
}

require ("template.php");



# Default view
function slct()
{

	//layout
	$slct = "
		<h3>View Canceled Invoices<h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;TO&nbsp;
					".mkDateSelect("to")."
				</td>
				<td><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-canc-view.php'>View Canceled Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
	        <script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



# Show invoices
function printInv ($HTTP_POST_VARS)
{

	# get vars
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
	$printInv = "
		<h3>View Canceled Invoices</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Username</th>
				<th>Department</th>
				<th>Invoice No.</th>
			</tr>";

	# Connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM cancelled_inv WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY invid DESC";
    $invRslt = db_exec ($sql) or errDie ("Unable to retrieve canceled invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li class='err'>No Canceled Invoices Found.</li>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {
			# format date
			$inv['date'] = explode("-", $inv['date']);
			$inv['date'] = $inv['date'][2]."-".$inv['date'][1]."-".$inv['date'][0];

			$printInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'>$inv[date]</td>
					<td>$inv[username]</td>
					<td>$inv[deptname]</td>
					<td>$inv[invid]</td>
				</tr>";
			$i++;
		}
	}

	// Layout
	$printInv .= "
		</table>
		<p>
        <table ".TMPL_tblDflts.">
        	".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-canc-view.php'>View Canceled Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}


?>
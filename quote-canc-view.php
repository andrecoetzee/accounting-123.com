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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "view":
			$OUTPUT = printQuo ($_POST);
			break;
		default:
			$OUTPUT = slct ();
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
				<h3>View Cancelled Quotes<h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th colspan='2'>By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
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
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='quote-canc-view.php'>View Canceled Quotes</a></td>
					<tr class='".bg_class()."'>
						<td><a href='quote-unf-view.php'>View Incomplete Quotes</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='quote-new.php'>New Quote</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>
			";
	return $slct;

}



# Show invoices
function printQuo ($_POST)
{

	# get vars
	extract ($_POST);

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
	$printQuo = "
					<h3>View Cancelled Quotes</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Date</th>
							<th>Username</th>
							<th>Department</th>
							<th>Quote No.</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM cancelled_quo WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY quoid DESC";
	$quoRslt = db_exec ($sql) or errDie ("Unable to retrieve cancelled Quotes from database.");
	if (pg_numrows ($quoRslt) < 1) {
		$printQuo = "<li class='err'>No Canceled Quotes Found.</li><br>";
	}else{
		while ($quo = pg_fetch_array ($quoRslt)) {
			# format date
			$quo['date'] = explode("-", $quo['date']);
			$quo['date'] = $quo['date'][2]."-".$quo['date'][1]."-".$quo['date'][0];

			$printQuo .= "
							<tr class='".bg_class()."'>
								<td align='center'>$quo[date]</td>
								<td>$quo[username]</td>
								<td>$quo[deptname]</td>
								<td>$quo[quoid]</td>
							</tr>";
			$i++;
		}
	}

	// Layout
	$printQuo .= "</table>"
		.mkQuickLinks(
			ql("quote-canc-view.php","View Canceled Quotes"),
			ql("quote-unf-view.php", "View Incomplete Quotes"),
			ql("quote-new.php", "New Quote"),
			ql("customers-new.php", "New Customer")
		);
	return $printQuo;

}


?>
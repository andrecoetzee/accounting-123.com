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
			$OUTPUT = printCallout ($_POST);
			break;

		default:
			$OUTPUT = slct ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

require ("template.php");



# Default view
function slct()
{

	//layout
	$slct = "
				<h3>View Canceled Call Out Documents<h3>
				<table ".TMPL_tblDflts." width='580'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th>By Date Range</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td align='center'>
							".mkDateSelect("from",date("Y"),date("m"),"01")."
							&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
							".mkDateSelect("to")."
			
						</td>
						<td valign='bottom'><input type='submit' value='Search'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td><a href='callout-unf-view.php'>View Incomplete Call Out Documents</td>
					</tr>
					<tr class='datacell'>
						<td><a href='callout-new.php'>New Call Out Document</td>
					</tr>
					<tr class='datacell'>
						<td><a href='main.php'>Main Menu</td>
					</tr>
				</table>";
			return $slct;

}



# Show invoices
function printCallout ($_POST)
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
	$printCallout = "
						<h3>View Canceled Call Out Documents</h3>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Date</th>
								<th>Username</th>
								<th>Department</th>
								<th>Job No.</th>
							</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM cancelled_callout WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY calloutid DESC";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to retrieve canceled Quotes from database.");
	if (pg_numrows ($calloutRslt) < 1) {
		$printCallout = "<li>There are no previous canceled Call Out Documents.</li>";
	}else{
		while ($callout = pg_fetch_array ($calloutRslt)) {

			# format date
			$callout['date'] = explode("-", $callout['date']);
			$callout['date'] = $callout['date'][2]."-".$callout['date'][1]."-".$callout['date'][0];

			$printCallout .= "
								<tr bgcolor='".bgcolorg()."'>
									<td align='center'>$callout[date]</td>
									<td>$callout[username]</td>
									<td>$callout[deptname]</td>
									<td>$callout[calloutid]</td>
								</tr>";
			$i++;
		}
	}

	// Layout
	$printCallout .= "
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr><td><br></td></tr>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='datacell'>
			<td><a href='callout-unf-view.php'>View Incomplete Quotes</td>
		</tr>
		<tr class='datacell'>
			<td><a href='cust-credit-stockinv.php'>New Quote</td>
		</tr>
		<tr class='datacell'>
			<td><a href='main.php'>Main Menu</td>
		</tr>
	</table>";
	return $printCallout;

}


?>
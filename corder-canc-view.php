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
			$OUTPUT = printCord ($HTTP_POST_VARS);
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
					<h3>View Cancelled Consignment Orders<h3>
					<table ".TMPL_tblDflts." width='460'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='view'>
						<tr>
							<th colspan='2'>By Date Range</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center' nowrap>
								".mkDateSelect("from",date("Y"),date("m"),"01")."
								&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
								".mkDateSelect("to")."
							</td>
							<td valign='bottom'><input type='submit' value='Search'></td>
						</tr>
					</form>
					</table>
					<p>
					<table border='0' cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-unf-view.php'>View Incomplete Consignment Orders</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-view.php'>View Consignment Orders</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</td>
						</tr>
					</table>";
		return $slct;

}




# Show invoices
function printCord ($HTTP_POST_VARS)
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
	$printSord = "
					<h3>View Cancelled Consignment Orders</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Date</th>
							<th>Username</th>
							<th>Department</th>
							<th>Order No.</th>
						</tr>
				";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM cancelled_cord WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY sordid DESC";
	$sordRslt = db_exec ($sql) or errDie ("Unable to retrieve cancelled Sales Order from database.");
	if (pg_numrows ($sordRslt) < 1) {
		$printSord = "<li>There are no cancelled Consignment Orders.</li>";
	}else{
		while ($sord = pg_fetch_array ($sordRslt)) {

			# format date
			$sord['date'] = explode("-", $sord['date']);
			$sord['date'] = $sord['date'][2]."-".$sord['date'][1]."-".$sord['date'][0];

			$printSord .= "
							<tr bgcolor='".bgcolorg()."'>
								<td align='center'>$sord[date]</td>
								<td>$sord[username]</td>
								<td>$sord[deptname]</td>
								<td>$sord[sordid]</td>
							</tr>
						";
			$i++;
		}
	}

	// Layout
	$printSord .= "
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-unf-view.php'>View Incomplete Consignment Orders</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='corder-view.php'>View Consignment Orders</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</td>
						</tr>
					</table>";
	return $printSord;

}



?>
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
require_lib("docman");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "view":
			$OUTPUT = printPurch ($HTTP_POST_VARS);
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
	//layout
	$slct = "
				<h3>View International Non-Stock Orders</h3>
				<table ".TMPL_tblDflts." width='550'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th colspan='2'>By Date Range</th>
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
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='nons-purch-int-new.php'>New International Non Stock Order</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $slct;

}



# show
function printPurch ($HTTP_POST_VARS)
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
	$printOrd = "
					<center>
					<h3>View International Non-Stock Orders</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Purchase No.</th>
							<th>Purchase Date</th>
							<th>Supplier</th>
							<th>Sub Total</th>
							<th>Delivery Charges</th>
							<th>Vat</th>
							<th>Total</th>
							<th>Documents</th>
							<th colspan='5'>Options</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$sql = "SELECT * FROM nons_purch_int WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
					<li> There are no Orders found.</li><p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-purch-int-new.php'>New International Non-Stock Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {
		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# Get documents
		$docs = doclib_getdocs("npur", $stkp['purnum']);

		$edit = "nons-purch-int-new.php";
		$det = "nons-purch-int-print.php";
		$recv = "nons-purch-int-recv.php";

		$printOrd .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$stkp[purnum]</td>
								<td>$date</td>
								<td>$stkp[supplier]</td>
								<td align='right'>$stkp[curr] $stkp[subtot]</td>
								<td align='right'>$stkp[curr] $stkp[shipchrg]</td>
								<td align='right'>$stkp[curr] $stkp[tax]</td>
								<td align='right'>$stkp[curr] $stkp[total]</td><td>$docs</td>
								<td><a href='$det?purid=$stkp[purid]'>Details</a></td>
								<td><a target='_blank' href='$det?purid=$stkp[purid]'>Print</a></td>";

		if($stkp['received'] != "y" && $stkp['subtot'] == 0){
			$printOrd .= "
								<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
								<td><a href='nons-purch-int-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
							</tr>";
		}elseif($stkp['received'] != "y"){
			$printOrd .= "
								<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
								<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
								<td><a href='nons-purch-int-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
							</tr>";
		}else{
			$printOrd .= "
								<td colspan='3'><br></td>
							</tr>";
		}
		$i++;
	}

	$printOrd .= "
						</table>
					    <p>
						<table ".TMPL_tblDflts.">
					        <tr><td><br></td></tr>
					        <tr>
					        	<th>Quick Links</th>
					        </tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='nons-purch-int-new.php'>New International Non-Stock Order</a></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='main.php'>Main Menu</a></td>
							</tr>
						</table>";
	return $printOrd;

}


?>
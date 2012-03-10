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
			$OUTPUT = printOrd($HTTP_POST_VARS);
			break;
	case "export":
			
			$OUTPUT = export($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = slct();
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
				<h3>View International Stock Purchases</h3>
				<table ".TMPL_tblDflts." width='580'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th colspan='2'>By Date Range</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td align='center'>
							".mkDateSelect("from",date("Y"),date("m"),"01")."
							&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
							".mkDateSelect("to")."
						</td>
						<td valign='bottom'><input type='submit' value='Search'></td>
					</tr>
				</form>
				</table>"
				.mkQuickLinks(
					ql("purchase-new.php", "New Order"),
					ql("stock-report.php", "Stock Control Reports"),
					ql("stock-view.php", "View Stock")
				);
	return $slct;

}


# show stock
function printOrd ($HTTP_POST_VARS, $pure = false)
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
	
	require_lib("docman");

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
	$printOrd = "";

	if (!$pure) {
		$printOrd .= "
						<center>
						<h3>View International Stock Purchases</h3>";
	}

	$printOrd .= "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Purchase No.</th>
							<th>Purchase Date</th>
							<th>Received Date</th>
							<th>Supplier</th>
							<th>Sub Total</th>
							<th>Shipping Charges</th>
							<th>Tax</th>
							<th colspan='2'>Total Cost Amount</th>
							<th>Documents</th>
							<th colspan='5'>Options</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM purch_int WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
    $stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock purchases from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
				<li class='err'> There are no previous stock purchases.</li>"
				.mkQuickLinks(
					ql("purch-int-new.php", "New International Order"),
					ql("purch-int-view.php", "View International Orders"),
					ql("stock-view.php", "View Stock")
				);
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {

		# Date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		$ddate = explode("-", $stkp["ddate"]);
		$ddate = "$ddate[2]-$ddate[1]-$ddate[0]";

		# Get supplier
		db_connect();
		$sql = "SELECT supname,currency FROM suppliers WHERE supid = '$stkp[supid]' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		if(pg_numrows($supRslt) < 1){
			$sup['supname'] = "<li class='err'>Supplier not found";
			$sup['currency'] = "";
		}else{
			$sup = pg_fetch_array($supRslt);
		}

		# Calculate the Sub-Total
		$subtot = sprint($stkp['total'] - $stkp['shipchrg']);
		$stkp['tax'] = sprint($stkp['tax']);
		$stkp['shipchrg'] = sprint($stkp['shipchrg']);

		# Get documents
		$docs = doclib_getdocs("ipur", $stkp['purnum']);

		$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

		$printOrd .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$stkp[purnum]</td>
					<td>$date</td>
					<td>$ddate</td>
					<td>$sup[supname]</td>
					<td align='right' nowrap>$stkp[curr] $subtot</td>
					<td align='right' nowrap>$stkp[curr] $stkp[shipchrg]</td>
					<td align='right' nowrap>$stkp[curr] $stkp[tax]</td>
					<td align='right' nowrap>$sp4 $stkp[curr] $stkp[total]</td>
					<td align='right' nowrap>$sp4 ".CUR." $stkp[fbalance]</td>
					<td>$docs</td>
					<td><a href='javascript: printer(\"purch-int-det.php?purid=$stkp[purid]\");'>Print</a></td>";

		if($stkp['received'] != "y" && $subtot == 0){
			$printOrd .= "
					<td colspan='2' align='center'><a href='purch-int-new.php?purid=$stkp[purid]&cont=1&letters=&done='>Edit</a></td>
					<td><br></td>
					<td><a href='purch-int-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
				</tr>";
		}elseif($stkp['received'] != 'y'){
			$recinv = "<br>";
			if($stkp['invcd'] != 'y'){
				$recinv = "<a href='purch-int-recinvcd.php?purid=$stkp[purid]'>Record Invoice</a>";
			}
			$printOrd .= "
					<td><a href='purch-int-new.php?purid=$stkp[purid]&cont=1&letters=&done='>Edit</a></td>
					<td><a href='purch-int-recv.php?purid=$stkp[purid]'>Received</a></td>
					<td>$recinv</td>
					<td><a href='purch-int-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
				</tr>";
		}else{
			$recinv = "<br>";
			if($stkp['invcd'] != 'y'){
				$recinv = "<a href='purch-int-recinvcd.php?purid=$stkp[purid]'>Record Invoice</a>";
			}
			$printOrd .= "
					<td colspan='2'>Received</td>
					<td>$recinv</td>
					<td><br></td>
				</tr>";
		}
		$i++;
	}

	if (!$pure) {
		$printOrd .= "
		".TBL_BR."
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
			<tr>
				<td colspan='3'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</form>";
	}
	
	if (!$pure) {
		$printOrd .= "
		</table>";
	}
	
	if (!$pure) {
		$printOrd .= 
				mkQuickLinks(
					ql("purch-int-new.php", "New International Purchase"),
					ql("stock-report.php", "Stock Control Reports"),
					ql("stock-view.php", "View Stock")
				);
	}
	
	return $printOrd;

}


function export ($HTTP_POST_VARS) {
	$OUT = printOrd($HTTP_POST_VARS, true);
	$OUT = clean_html($OUT);

	require_lib("xls");
	Stream("Orders Received", $OUT);
}


?>
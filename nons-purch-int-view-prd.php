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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
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

	//layout
	$slct = "
				<h3>View Received International Non-Stock Orders</h3>
				<table ".TMPL_tblDflts." width='580'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th colspan='2'>By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center' colspan='2'>
							".mkDateSelect("from",date("Y"),date("m"),"01")."
							&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
							".mkDateSelect("to")."
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Search'></td>
					</tr>
				</form>
				</table>"
				.mkQuickLinks(
					ql("nons-purchase-new.php", "New Order"),
					ql("stock-view.php", "View Stock")
				);
	return $slct;

}


# show stock
function printPurch ($_POST,$flag=TRUE)
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
	$printOrd = "
					<center>
					<h3>Received International Non-Stock Orders</h3>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='export'>
						<input type='hidden' name='from_day' value='$from_day'>
						<input type='hidden' name='from_month' value='$from_month'>
						<input type='hidden' name='from_year' value='$from_year'>
						<input type='hidden' name='to_day' value='$to_day'>
						<input type='hidden' name='to_month' value='$to_month'>
						<input type='hidden' name='to_year' value='$to_year'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Purchase No.</th>
							<th>Purchase Date</th>
							<th>Supplier</th>
							<th>Sub Total</th>
							<th>Delivery Charges</th>
							<th>Tax</th>
							<th>Total</th>
							<th>Delevery Reference No.</th>
							<th>Documents</th>
							<th colspan='5'>Options</th>
						</tr>";

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = $from_month; $i <= $to_month; $i++) {
		$schema = (int)$i;
		
		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".nons_purch_int WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND done = 'y' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY pdate DESC";

	# Query server
	$i = 0;

    $stkpRslt = db_exec ($query) or errDie ("Unable to retrieve stock Non-Stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li>No International Non-Stock Orders found.</li>"
				.mkQuickLinks(
					ql("nons-purch-int-new.php", "New International Non-Stock Order"),
					ql("stock-view.php", "View Stock")
				);
	}

	while ($stkp = pg_fetch_array ($stkpRslt)) {
		$prd = $stkp["query_schema"];
		
		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# Get documents
		$docs = doclib_getdocs("npur", $stkp['purnum']);

		$printOrd .= "
						<tr class='".bg_class()."'>
							<td>$stkp[purnum]</td>
							<td>$date</td>
							<td>$stkp[supplier]</td>
							<td align='right'>$stkp[curr] $stkp[subtot]</td>
							<td align='right'>$stkp[curr] $stkp[shipchrg]</td>
							<td align='right'>$stkp[curr] $stkp[tax]</td>
							<td align='right'>$stkp[curr] $stkp[total]</td>
							<td>$stkp[refno]</td>
							<td>$docs</td>";

		if($flag){
			if($stkp['subtot'] > 0){
				$printOrd .= "
								<td><a href='nons-purch-int-return.php?purid=$stkp[purid]&prd=$prd'>Return</a></td>
								<td><a href='nons-purch-int-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td>
							</tr>";
			}else{
				$printOrd .= "
								<td><a href='nons-purch-int-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td>
							</tr>";
			}
		}else {
			$printOrd .= "</tr>";
		}

		$i++;
	}

	if($flag){
		$printOrd .= "
							".TBL_BR."
							<tr>
								<td align='center' colspan='11'><input type='submit' value='Export to Spreadsheet'></td>
							</tr>
						</form>
						</table>
						<br>"
					.mkQuickLinks(
						ql("nons-purch-int-new.php", "New International Non-Stock Order")
					);
	}else {
		$printOrd .= "</form></table>";
	}
	return $printOrd;

}


function export ($_POST) {
	$OUT = printPurch($_POST, FALSE);
	$OUT = clean_html($OUT);

	require_lib("xls");
	Stream("Non Stock Orders Received", $OUT);
}

?>
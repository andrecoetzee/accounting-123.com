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
			$OUTPUT = printOrd($_POST);
			break;
	 case "export":
			$OUTPUT = export($_POST);
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
				<h3>View Received International Stock Orders</h3>
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
						<td colspan='2' align='right'>
							<input type='submit' value='Search &raquo'>
						</td>
					</tr>
				</form>
				</table>"
				.mkQuickLinks(
					ql("stock-view.php", "View Stock")
				);
	return $slct;

}


# show stock
function printOrd ($_POST)
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
			$confirm .= "<li class='err'>-".$e["msg"]."</li";
		}
        return $confirm;
	}



	# Set up table to display in
	$printOrd = "
					<center>
			        <h3>Received International Stock Orders</h3>
			        <table ".TMPL_tblDflts.">
			       	 <tr>
			       	 	<th>Order No.</th>
			       	 	<th>Order Date</th>
			       	 	<th>Supplier</th>
			       	 	<th>Sub Total</th>
			       	 	<th>Shipping Charges</th>
			       	 	<th>Tax</th>
			       	 	<th colspan='2'>Total</th>
			       	 	<th>Delivery Reference No.</th>
			       	 	<th>Documents</th>
			       	 	<th colspan='5'>Options</th>
			       	 </tr>";

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = $from_month; $i <= $to_month; $i++) {
		$schema = (int)$i;
		
		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".purch_int WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND done = 'y' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY pdate DESC";

	# Query server
	$i = 0;
    $stkpRslt = db_exec ($query) or errDie ("Unable to retrieve stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li> There are no stock Orders found.</li>"
				.mkQuickLinks(
					ql("purch-int-new.php", "New International Order"),
					ql("stock-view.php", "View Stock")
				);
	}

	while ($stkp = pg_fetch_array ($stkpRslt)) {

		# date format
        $date = explode("-", $stkp['pdate']);
        $prd = $stkp["query_schema"];
        $date = $date[2]."-".$date[1]."-".$date[0];

		# Get supplier
		db_connect();
		$sql = "SELECT supname FROM suppliers WHERE supid = '$stkp[supid]' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		if(pg_numrows($supRslt) < 1){
			$sup['supname'] = "<li class='err'>Supplier not found.</li>";
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

		#clear var
		$stkp['fbalance'] = sprint($stkp['fbalance']);
		$stkp['total'] = sprint(sprint($stkp['total']));
		$printOrd .= "
						<tr class='".bg_class()."'>
							<td>$stkp[purnum]</td>
							<td>$date</td>
							<td>$sup[supname]</td>
							<td align='right'>$stkp[curr] $subtot</td>
							<td align='right'>$stkp[curr] $stkp[shipchrg]</td>
							<td align='right'>$stkp[curr] $stkp[tax]</td>
							<td align='right'>$stkp[curr] $stkp[total]</td>
							<td align='right'>$sp4 ".CUR." $stkp[fbalance]</td>
							<td>$stkp[refno]</td>
							<td>$docs</td>";

		if($stkp['returned'] != 'y' && returnable($stkp['purnum'])){
			$printOrd .= "<td><a href='purch-int-return.php?purid=$stkp[purid]&prd=$prd'>Return</a></td>";
		}else{
			$printOrd .= "<td><br></td>";
		}

		if($stkp['rsubtot'] > 0){
			$printOrd .= "<td><a href='purch-int-recnote.php?purid=$stkp[purid]&prd=$prd'>Record Credit Note</a></td>";
		}else{
			$printOrd .= "<td><br></td>";
		}

		$printOrd .= "<td><a href='purch-int-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td></tr>";

		$i++;
	}

	$printOrd .= "
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
							<td colspan='3'><input type='submit' value='Export to Spreadsheet'></td>
						</tr>
					</form>
					</table>"
					.mkQuickLinks(
						ql("purch-int-new.php", "New International Order"),
						ql("stock-view.php", "View Stock")
					);

	return $printOrd;
}


# show stock
function export ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
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
					<h3>Received International Stock Orders</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Order No.</th>
							<th>Order Date</th>
							<th>Supplier</th>
							<th>Sub Total</th>
							<th>Shipping Charges</th>
							<th>Tax</th>
							<th colspan='2'>Total</th>
							<th>Delivery Reference No.</th>
						</tr>";

	# connect to database
	db_conn($prd);

	# Query server
	$i = 0;
    $sql = "SELECT * FROM purch_int WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND done = 'y' AND div = '".USER_DIV."' ORDER BY pdate DESC";
    $stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li> There are no stock Orders found.</li>"
				.mkQuickLinks(
					ql("purch-int-int-new.php", "New International Order"),
					ql("stock-view.php", "View Stock")
				);
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {

		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# Get supplier
		db_connect();
		$sql = "SELECT supname FROM suppliers WHERE supid = '$stkp[supid]' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		if(pg_numrows($supRslt) < 1){
			$sup['supname'] = "<li class=err>Supplier not found";
		}else{
			$sup = pg_fetch_array($supRslt);
		}

		# Calculate the Sub-Total
		$subtot = sprint($stkp['total'] - $stkp['shipchrg']);
		$stkp['tax'] = sprint($stkp['tax']);
		$stkp['shipchrg'] = sprint($stkp['shipchrg']);

		# Get documents
		$docs = "";

		$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

		$printOrd .= "
						<tr>
							<td>$stkp[purnum]</td>
							<td>$date</td>
							<td>$sup[supname]</td>
							<td align='right'>$stkp[curr] $subtot</td>
							<td align='right'>$stkp[curr] $stkp[shipchrg]</td>
							<td align='right'>$stkp[curr] $stkp[tax]</td>
							<td align='right'>$stkp[curr] $stkp[total]</td>
							<td align='right'>$sp4 ".CUR." $stkp[fbalance]</td>
							<td>$stkp[refno]</td>
						</tr>";

		$i++;
	}

	$printOrd .= "</table>";

	include("xls/temp.xls.php");
	Stream("Report", $printOrd);

	return $printOrd;
}


function returnable($purnum){
	$sers = ext_getPurSerials($purnum);
	# innocent until proven guilty
	$ret = true;
	if(count($sers) > 0){
		foreach($sers as $key => $ser){
			if(ext_findAvSer($ser['serno'], $ser['stkid']) == false)
				return false;
		}
	}
	return $ret;
}


?>
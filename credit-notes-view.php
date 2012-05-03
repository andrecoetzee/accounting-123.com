<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = show_notes ($_POST);
			break;
		default:
			$OUTPUT = get_filter ($_POST);
	}
}else {
	$OUTPUT = get_filter ($_POST);
}

$OUTPUT .= "<br>" . 
			mkQuickLinks(
				ql ("general-creditnote.php","Generate General Credit Note"),
				ql ("credit-notes-view.php","View Credit Notes"),
				ql ("cust-credit-stockinv.php","New Stock Invoice"),
				ql ("invoices-view.php","View Invoices")
			);

require ("template.php");



function get_filter ($_POST,$err="")
{

	extract ($_POST);

	$display = "
		<h2>View Credit Notes</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Select Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp To &nbsp
					".mkDateSelect("to")."
				</td>
				<td><input type='submit' value='View'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function show_notes ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($from_day, "num", 1, 2, "Invalid Invoice From Date day.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid Invoice From Date month.");
	$v->isOk ($from_year, "num", 1, 5, "Invalid Invoice From Date year.");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Invoice To Date day.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid Invoice To Date month.");
	$v->isOk ($to_year, "num", 1, 5, "Invalid Invoice To Date year.");

	$fromdate = mkdate($from_year, $from_month, $from_day);
	$todate = mkdate($to_year, $to_month, $to_day);

	$v->isOk ($fromdate, "date", 1, 1, "Invalid From Date.");
	$v->isOk ($todate, "date", 1, 1, "Invalid To Date.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return get_filter($_POST, $err);
	}


	$listing = "
		<tr>
			<th>Customer</th>
			<th>Credit Note No.</th>
			<th>Ref.</th>
			<th>Date</th>
			<th>Amount</th>
			<th>Stock Returned</th>
			<th>Options</th>
		</tr>";

	db_connect ();

	$get_sql = "SELECT * FROM credit_notes WHERE tdate >= '$fromdate' AND tdate <= '$todate'";
	$run_get = db_exec($get_sql) or errDie ("Unable to get credit note information.");
	if(pg_numrows($run_get) < 1){
		$listing .= "
			<tr class='".bg_class()."'>
				<td colspan='7'>No Credit Notes Found.</td>
			</tr>";
	}else {
		while ($carr = pg_fetch_array($run_get)){

			$get_cust = "SELECT surname FROM customers WHERE cusnum = '$carr[cusnum]'";
			$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
			if(pg_numrows($run_cust) < 1){
				$cusname = "";
			}else {
				$cusname = pg_fetch_result($run_cust,0,0);
			}

			if (isset($carr['used_stock']) AND $carr['used_stock'] == "1") 
				$showstockused = "Yes";
			else 
				$showstockused = "No";

			$listing .= "
				<tr class='".bg_class()."'>
					<td>$cusname</td>
					<td>g$carr[creditnote_num]</td>
					<td>$carr[refnum]</td>
					<td>$carr[tdate]</td>
					<td>".CUR." $carr[totamt]</td>
					<td>$showstockused</td>
					<td><a href='credit-note-print.php?id=$carr[id]'>Print</a></td>
				</tr>";
		}
	}

	$display = "
		<h2>Listing Of Credit Notes</h2>
		<table ".TMPL_tblDflts.">
			$listing
		</table>";
	return $display;

}



?>
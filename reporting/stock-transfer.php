<?

require ("../settings.php");

$OUTPUT = get_report ();

require ("../template.php");


function get_report ()
{

	extract ($_REQUEST);

	db_conn ("exten");

	$get_wh = "SELECT * FROM warehouses ORDER BY whname";
	$run_wh = db_exec ($get_wh) or errDie ("Unable to get warehouses information.");
	if (pg_numrows ($run_wh) > 0){
		$store_from_drop = "<select name='store_from'>";
		$store_from_drop .= "<option value='0'>All Stores</option>";
		$store_to_drop = "<select name='store_to'>";
		$store_to_drop .= "<option value='0'>All Stores</option>";
		while ($sarr = pg_fetch_array ($run_wh)){
			if (isset ($store_from) AND $store_from == $sarr['whid']){
				$store_from_drop .= "<option value='$sarr[whid]' selected>($sarr[whno]) $sarr[whname]</option>";
			}else {
				$store_from_drop .= "<option value='$sarr[whid]'>($sarr[whno]) $sarr[whname]</option>";
			}
			if (isset ($store_to) AND $store_to == $sarr['whid']){
				$store_to_drop .= "<option value='$sarr[whid]' selected>($sarr[whno]) $sarr[whname]</option>";
			}else {
				$store_to_drop .= "<option value='$sarr[whid]'>($sarr[whno]) $sarr[whname]</option>";
			}
		}
		$store_from_drop .= "</select>";
		$store_to_drop .= "</select>";
	}else {
		$store_from_drop = "<input type='hidden' name='store_from' value='0'>No Stores Found.";
		$store_to_drop = "<input type='hidden' name='store_to' value='0'>No Stores Found.";
	}

	if (isset ($search) AND strlen ($search)){

		$fromdate = "$from_year-$from_month-$from_day";
		$todate = "$to_year-$to_month-$to_day";

		if (!isset ($from_year) OR strlen ($from_year) < 4){
			$fromdate = date ("Y-m-d");
		}
		if (!isset ($from_month) OR strlen ($from_month) < 1){
			$fromdate = date ("Y-m-d");
		}
		if (!isset ($from_day) OR strlen ($from_day) < 1){
			$fromdate = date ("Y-m-d");
		}

		if (!isset ($to_year) OR strlen ($to_year) < 4){
			$todate = date ("Y-m-d");
		}
		if (!isset ($to_month) OR strlen ($to_month) < 1){
			$todate = date ("Y-m-d");
		}
		if (!isset ($to_day) OR strlen ($to_day) < 1){
			$todate = date ("Y-m-d");
		}


		if (isset($store_from) AND strlen ($store_from) > 0 AND $store_from != "0"){
			$fromsql = "AND whid_from = '$store_from'";
		}else {
			$fromsql = "";
		}
		if (isset($store_to) AND strlen ($store_to) > 0 AND $store_to != "0"){
			$tosql = "AND whid_to = '$store_to'";
		}else {
			$tosql = "";
		}

		db_connect ();

		$get_entries = "SELECT * FROM stock_transfer WHERE transfer_date >= '$fromdate' AND transfer_date <= '$todate' $fromsql $tosql ORDER BY transfer_date";
		$run_entries = db_exec ($get_entries) or errDie ("Unable to get report information.");
		if (pg_numrows ($run_entries) > 0){
			$listing = "
				<tr>
					<th>Transfer Date</th>
					<th>Reference</th>
					<th>Stock Item</th>
					<th>Store From</th>
					<th>Store To</th>
					<th>Units</th>
					<th>Remarks</th>
				</tr>";

			db_conn ("exten");

			$get_whs = "SELECT whid, whname FROM warehouses";
			$run_whs = db_exec ($get_whs) or errDie ("Unable to get store information.");
			if (pg_numrows ($run_whs) > 0){
				$stores = array ();
				while ($warr = pg_fetch_array ($run_whs)){
					$whid = $warr['whid'];
					$stores[$whid] = $warr['whname'];
				}
			}else {
				$stores = array ();
			}

			db_connect ();

			while ($earr = pg_fetch_array ($run_entries)){

				$whid_from = $earr['whid_from'];
				$whid_to = $earr['whid_to'];

				$get_stock = "SELECT stkcod, stkdes FROM stock WHERE stkid = '$earr[stkid]' LIMIT 1";
				$run_stock = db_exec ($get_stock) or errDie ("Unable to get stock information.");
				if (pg_numrows ($run_stock) > 0){
					$stkarr = pg_fetch_array ($run_stock);
					$stock = "($stkarr[stkcod]) $stkarr[stkdes]";
				}else {
					$stock = "";
				}

				$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$earr[transfer_date]</td>
						<td>$earr[reference]</td>
						<td>$stock</td>
						<td>$stores[$whid_from]</td>
						<td>$stores[$whid_to]</td>
						<td>$earr[units]</td>
						<td>$earr[remark]</td>
					</tr>";
			}
		}
	}

	$display = "
		<h4>Stock Transfer Report</h4>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Filter</th>
			</tr>
			<tr>
				<th>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect ("from",date("Y"),date("m"),"01")." To ".mkDateSelect ("to",date("Y"),date("m"),date("d"))."</td>
			</tr>
			<tr>
				<th>Store From</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$store_from_drop</td>
			</tr>
			<tr>
				<th>Store To</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$store_to_drop</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='search' value='Search'></td>
			</tr>
		</table>
		</form>
		<br>
		<table ".TMPL_tblDflts.">
			$listing
		</table>";
	return $display;

}

?>
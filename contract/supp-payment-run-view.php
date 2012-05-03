<?

require ("../settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "search":
			$OUTPUT = show_runs ($_POST);
			break;
		default:
			$OUTPUT = get_runs ();
	}
}else {
	$OUTPUT = get_runs ();
}

$OUTPUT .= "<br>".
	mkQuickLinks(
		ql("../supp-payment-run.php","Creditor Payment Run"),
		ql("supp-payment-list.php","Creditor Payment Run Listing")
	);

require ("../template.php");



function get_runs ()
{

	$display = "
		<h2>View Credit Runs</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='search'>
			<tr>
				<th>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from",date("Y"),date("m"),"01")." To ".mkDateSelect("to")." <input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function show_runs ($_POST)
{

	extract ($_POST);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	db_conn('contract');

	$get_dist = "SELECT distinct(run_id) FROM credit_runs";
	$run_dist = db_exec($get_dist) or errDie ("Unable to get creditor run information.");
	if(pg_numrows($run_dist) < 1){
		return "<li class='err'>No Previous Creditor Runs Found.</li>";
	}else {
		$listing = "";
		while ($darr = pg_fetch_array ($run_dist)){

			db_conn('contract');

			#get this run for this id ...
			$get_run = "SELECT * FROM credit_runs WHERE run_id = '$darr[run_id]' AND proc_date >= '$from_date' AND proc_date <= '$to_date' ORDER BY id";
			$run_run = db_exec($get_run) or errDie ("Unable to get creditor run information.");
			if(pg_numrows($run_run) < 1){
				return "<li class='err'>Could not get creditor run information.</li>";
			}else {
				while ($rarr = pg_fetch_array ($run_run)){

					db_connect ();

					#get supplier info for this entry
					$get_supp = "SELECT supname FROM suppliers WHERE supid = '$rarr[supid]' LIMIT 1";
					$run_supp = db_exec($get_supp) or errDie ("Unable to get supplier information.");
					if(pg_numrows($run_supp) < 1){
						return "<li class='err'>Could not get supplier information for credit run: $rarr[run_id]</li>";
					}

					$supplier_name = pg_fetch_result($run_supp,0,0);

					$listing .= "
									<tr class='".bg_class()."'>
										<td>$supplier_name</td>
										<td>$rarr[proc_date]</td>
										<td>$rarr[cheq_num]</td>
										<td>".sprint($rarr['amount'])."</td>
										<td>".nl2br($rarr['remarks'])."</td>
									</tr>
								";
				}
			}
			$listing .= TBL_BR;
		}
	}
	
	if(strlen($listing) < 1){
		$listing = "
						<tr>
							<td>No Entries Found In Period.</td>
						</tr>
					";
	}

	$display = "
					<h2>View Processed Creditor Runs</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<tr>
							<th>Supplier</th>
							<th>Date</th>
							<th>Cheque Number</th>
							<th>Amount</th>
							<th>Remarks</th>
						</tr>
						$listing
					</form>
					</table>
				";
	return $display;

}


?>
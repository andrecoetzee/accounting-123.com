<?

	require ("settings.php");

	$OUTPUT = show_listing ();

	require ("template.php");


function show_listing ()
{

	db_connect ();

	$get_loans = "SELECT * FROM loan_types ORDER BY loan_type";
	$run_loans = db_exec($get_loans) or errDie("Unable to get loan types information");
	if(pg_numrows($run_loans) < 1){
		$listing = "
				<tr class='".bg_class()."'>
					<td colspan='2'>No Entries Were Found.</td>
				</tr>
			";
	}else {
		$listing = "";
		while ($larr = pg_fetch_array($run_loans)){
			$listing .= "
					<tr class='".bg_class()."'>
						<td>$larr[loan_type]</td>
						<td><a href='loan_type_rem.php?id=$larr[id]'>Remove</a></td>
					</tr>
				";
		}
	}

	$display = "
			<h2>Loan Types Listing</h2>
			<table ".TMPL_tblDflts.">
				<tr><td><br></td></tr>
				<tr>
					<th>Loan Type</th>
					<th>Options</th>
				</tr>
				$listing
			</table><br>"
			.mkQuickLinks(
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);
	return $display;


}





?>

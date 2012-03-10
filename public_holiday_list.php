<?

	require ("settings.php");

	$OUTPUT = show_listing ();

	require ("template.php");



function show_listing ()
{

	db_connect ();

	$get_list = "SELECT * FROM public_holidays ORDER BY holiday_date";
	$run_list = db_exec($get_list) or errDie("Unable to get public holiday list.");
	if(pg_numrows($run_list) < 1){
		$listing = "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Public Holidays Have Been Added.</td></tr>";
	}else {
		$listing = "";
		while ($parr = pg_fetch_array($run_list)){
			$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$parr[holiday_name]</td>
						<td>$parr[holiday_type]</td>
						<td>$parr[holiday_date]</td>
						<td><a href='public_holiday_rem.php?id=$parr[id]'>Remove</a></td>
					</tr>
				";
		}
	}


	$display = "
				<h2>Public Holiday Listing</h2>
				<table ".TMPL_tblDflts.">
					<tr><td><br></td></tr>
					<tr>
						<th>Holiday Name</th>
						<th>Holiday Type</th>
						<th>Holiday Date</th>
						<th>Options</th>
					</tr>
					$listing
				</table><br>"
			.mkQuickLinks(
				ql("public_holiday_add.php", "Add Public Holiday")
			);
	return $display;

}




?>
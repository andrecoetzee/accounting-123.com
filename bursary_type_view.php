<?

	require ("settings.php");

	$OUTPUT = show_listing ();

	require ("template.php");


function show_listing ()
{

	db_connect ();

	$get_burs = "SELECT * FROM bursaries ORDER BY bursary_name";
	$run_burs = db_exec($get_burs) or errDie("Unable to get bursaries information.");
	if(pg_numrows($run_burs) < 1){
		return "No bursaries found.";
	}else {
		$listing = "";
		while ($barr = pg_fetch_array($run_burs)){
			$listing .= "
					<tr class='".bg_class()."'>
						<td>$barr[bursary_name]</td>
						<td>".nl2br($barr['bursary_details'])."</td>
						<td><a href='bursary_type_rem.php?id=$barr[id]'>Remove</a></td>
					</tr>
				";
		}
	}

	$display = "
			<h2>Bursaries View</h2>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Bursary Name</th>
					<th>Bursary Details</th>
					<th>Options</th>
				</tr>
				$listing
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);
	return $display;

}

?>
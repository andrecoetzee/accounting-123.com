<?

	require ("settings.php");

	$OUTPUT = show_list ();

	require ("template.php");


function show_list ()
{

	db_connect ();

	$get_burs = "SELECT * FROM active_bursaries ORDER BY rec_name";
	$run_burs = db_exec($get_burs) or errDie("Unable to get bursaries information.");
	if(pg_numrows($run_burs) < 1){
		$listing .= "<tr class='".bg_class()."'><td colspan='8'>No bursaries found.</td></tr>";
	}else {
		$listing = "";
		while ($barr = pg_fetch_array($run_burs)){

			$get_bur = "SELECT * FROM bursaries WHERE id = '$barr[bursary]' LIMIT 1";
			$run_bur = db_exec($get_bur) or errDie("Unable to get bursary information.");
			if(pg_numrows($run_bur) < 1){
				return "<li class='err'>Invalid Use Of Module. Invalid Bursary.</li>";
			}
			$burarr = pg_fetch_array($run_bur);
			$showburs = $burarr['bursary_name'];

			$listing .= "
					<tr class='".bg_class()."'>
						<td>$barr[rec_name]</td>
						<td>$showburs</td>
						<td>$barr[rec_idnum]</td>
						<td>$barr[rec_telephone]</td>
						<td>$barr[from_date]</td>
						<td>$barr[to_date]</td>
						<td>".nl2br($barr['notes'])."</td>
						<td><a href='bursary_give_rem.php?id=$barr[id]'>Remove</a></td>
					</tr>
				";
		}
	}

	$display = "
			<h2>Given Bursaries</h2>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Name</th>
					<th>Bursary</th>
					<th>ID Number</th>
					<th>Telephone Number</th>
					<th>From Date</th>
					<th>To Date</th>
					<th>Notes</th>
					<th>Options</th>
				</tr>
				$listing
			</table><br>"
			.mkQuickLinks(
				ql("bursary_give.php", "Give Bursary"),
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);
	return $display;

}


?>
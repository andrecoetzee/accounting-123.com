<?

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");



function display()
{

	$sql = "SELECT * FROM project.positions";
	$positions_rslt = db_exec($sql) or errDie("Unable to retrieve positions.");

	$positions_out = "";
	while ($positions_data = pg_fetch_array($positions_rslt)) {
		$positions_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$positions_data[name]</td>
				<td>$positions_data[description]</td>
				<td><a href='position_save.php?id=$positions_data[id]&page_option=Edit'>Edit</a></td>
			</tr>";
	}

	if (empty($positions_out)) {
		$positions_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='3'><li>No positions found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>View Positions</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>Options</th>
			</tr>
			$positions_out
		</table>";
	return $OUTPUT;

}

?>
<?php

require ("settings.php");

$OUTPUT = view();

$OUTPUT .= mkQuickLinks(
	ql("asset-new.php", "Add Asset"),
	ql("asset-view.php", "View Assets"),
	ql("asset_type_save.php", "Add Asset Type")
);

require ("template.php");



function view()
{

	// Retrieve asset types
	$sql = "SELECT * FROM cubit.asset_types ORDER BY name ASC";
	$at_rslt = db_exec($sql) or errDie("Unable to retrieve asset types.");

	$at_out = "";
	while ($at_data = pg_fetch_array($at_rslt)) {
		$at_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$at_data[name]</td>
				<td>".nl2br($at_data["description"])."</td>
				<td><a href='asset_type_save.php?page_option=Edit&id=$at_data[id]'>Edit</a></td>
				<td><a href='asset_type_rem.php?id=$at_data[id]'>Remove</a></td>
			</tr>";
	}

	if (empty($at_out)) {
		$at_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'>No asset types found</td>
			</tr>";
	}

	$OUTPUT = "
		<h3>View Asset Types</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th colspan='2'>Options</th>
			</tr>
			$at_out
		</table>";
	return $OUTPUT;

}



?>
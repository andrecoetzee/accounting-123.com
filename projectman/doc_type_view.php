<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= "<p>".mkQuickLinks(
	ql("doc_type_save.php","Add Project Document Type"),
	ql ("doc_type_view.php","View Project Document Types")
);

require ("../template.php");



function display()
{

	extract ($_REQUEST);

	$sql = "SELECT * FROM project.doc_types";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

	$types_out = "";
	while ($dt_data = pg_fetch_array($dt_rslt)) {
		$types_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td>$dt_data[name]</td>
				<td>$dt_data[description]</td>
				<td>$dt_data[extension]</td>
				<td><a href='doc_type_save.php?id=$dt_data[id]&page_option=Edit'>Edit</a></td>
			</tr>";
	}

	if (empty($types_out)) {
		$types_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='10'><li>No document types found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>View Project Document Types</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>Extension</th>
				<th>Options</th>
			</tr>
			$types_out
		</table>";
	return $OUTPUT;

}


?>
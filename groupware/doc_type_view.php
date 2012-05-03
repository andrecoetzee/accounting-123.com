<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("doc_type_save.php", "Add Document Type"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.document_types";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

	$types_out = "";
	while ($dt_data = pg_fetch_array($dt_rslt)) {
		$types_out .= "<tr class='".bg_class()."'>
			<td>$dt_data[type_name]</td>
			<td><a href='doc_type_remove.php?id=$dt_data[id]'>Remove</a></td>
		</tr>";
	}

	if (empty($types_out)) {
		$types_out = "<tr class='".bg_class()."'>
			<td colspan='20'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<h3>View Document Types</h3>
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr class='".bg_class()."'>
			<th>Type Name</th>
			<th>Options</th>
		</tr>
		$types_out
	</table>";

	return $OUTPUT;
}

?>

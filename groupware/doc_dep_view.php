<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("doc_dep_save.php", "Add Document Deparment"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.document_departments";
	$dd_rslt = db_exec($sql) or errDie("Unable to retrieve document department.");

	$dd_out = "";
	while ($dd_data = pg_fetch_array($dd_rslt)) {
		$dd_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$dd_data[dep_name]</td>
			<td><a href='doc_dep_save.php?id=$dd_data[id]&mode=edit'>Edit</a></td>
			<td><a href='doc_dep_remove.php?id=$dd_data[id]'>Remove</a></td>
		</tr>";
	}

	if (empty($dd_out)) {
		$dd_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='20'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<h3>View Document Departments</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Deparment Name</th>
			<th colspan='2'>Options</th>
		</tr>
		$dd_out
	</table>";

	return $OUTPUT;
}

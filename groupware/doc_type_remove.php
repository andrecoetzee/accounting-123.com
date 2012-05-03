<?php

require ("../settings.php");

db_conn("cubit");
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = confirm();
}

$OUTPUT .= mkQuickLinks(
	ql("doc_type_save.php", "Add Document Type"),
	ql("doc_type_view.php", "View Document Types"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");

function confirm()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.document_types WHERE id='$id'";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");
	$dt_data = pg_fetch_array($dt_rslt);

	$OUTPUT = "<h3>Remove Document Type</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='id' value='$dt_data[id]' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Type Name</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>$dt_data[type_name]</td>
			<td><input type='submit' value='Remove &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "DELETE FROM cubit.document_types WHERE id='$id'";
	$dt_rslt = db_exec($sql) or errDie("Unable to remove document type.");

	$OUTPUT = "<h3>Remove Document Type</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Remove</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Successfully Removed Document Type</td>
		</tr>
	</table>";

	return $OUTPUT;
}

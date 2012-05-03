<?php

require ("../settings.php");

db_conn("cubit");
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .= mkQuickLinks(
	ql("doc_type_save.php", "Add Another Document Type"),
	ql("doc_type_view.php", "View Document Types"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["type_name"] = "";

	extract ($fields, EXTR_SKIP);

	if (isset($mode) && $mode == "edit") {
		$title = "Edit";
	} else {
		$title = "Add";
	}

	$OUTPUT = "<h3>$title Document Type</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='title' value='$title' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Type</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='type_name' value='$type_name' /></td>
			<td><input type='submit' value='Confirm &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<h3>$title Document Type</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='title' value='$title' />
	<input type='hidden' name='type_name' value='$type_name' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Type</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>$type_name</td>
			<td><input type='submit' value='Write &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "INSERT INTO cubit.document_types (type_name) VALUES ('$type_name')";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

	$OUTPUT = "<h3>$title Document Type</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Successfully Saved The Document Type</td>
		</tr>
	</table>";

	return $OUTPUT;
}

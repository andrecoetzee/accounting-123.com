<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
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

$OUTPUT .= "<p>".mkQuickLinks(
	ql("doc_type_save.php","Add Project Document Type"),
	ql ("doc_type_view.php","View Project Document Types")
);

require ("../template.php");



function enter($errors="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["id"] = 0;
	$fields["page_option"] = "Add";
	$fields["name"] = "";
	$fields["description"] = "";
	$fields["extension"] = "";

	extract ($fields, EXTR_SKIP);

	if (strtolower($page_option) == "edit") {
		$sql = "SELECT * FROM project.doc_types WHERE id='$id'";
		$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

		if (pg_num_rows($dt_rslt)) {
			extract(pg_fetch_array($dt_rslt));
		} else {
			$page_option = "Add";
		}
	}

	$OUTPUT = "
		<h3>$page_option Project Document Type</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='key' value='confirm' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Document Type Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Name</td>
				<td><input type='text' name='name' value='$name' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td><input type='text' name='description' value='$description' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>File Extension</td>
				<td><input type='text' name='extension' value='$extension' /></td>
			</tr>
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' value='Confirm &raquo' />
				</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function confirm()
{

	extract ($_REQUEST);

	require_lib("validate");

	$v = new validate;
	$v->isOk($name, "string", 1, 255, "Invalid name.");
	$v->isOk($description, "string", 0, 255, "Invalid description.");
	$v->isOk($extension, "string", 0, 65, "Invalid extension.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}


	$OUTPUT = "
		<h3>$page_option Project Document Type</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='name' value='$name' />
			<input type='hidden' name='description' value='$description' />
			<input type='hidden' name='extension' value='$extension' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name</td>
				<td>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td>$description</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Extension</td>
				<td>$extension</td>
			</tr>
			<tr>
				<td><input type='submit' name='key' value='&laquo Correction' /></td>
				<td align='right'><input type='submit' value='Write &raquo' /></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function write()
{

	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($name, "string", 1, 255, "Invalid name.");
	$v->isOk($description, "string", 0, 255, "Invalid description.");
	$v->isOk($extension, "string", 0, 65, "Invalid extension.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if (strtolower($page_option) == "edit") {
		$sql = "UPDATE project.doc_types SET name='$name', description='$description', extension='$extension' WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update document type.");
	} else {
		$sql = "INSERT INTO project.doc_types (name, description, extension) VALUES ('$name', '$description', '$extension')";
		db_exec($sql) or errDie("Unable to add document type.");
	}

	$OUTPUT = "
		<h3>$page_option Project Document Type</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><li>Successfully added the document type.</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>
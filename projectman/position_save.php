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

$OUTPUT .= mkQuickLinks(
	ql("position_save.php", "Add Position"),
	ql("position_view.php", "View Positions"),
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects")
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

	extract ($fields, EXTR_SKIP);

	if (strtolower($page_option) == "edit") {
		$sql = "SELECT * FROM project.positions WHERE id='$id'";
		$position_rslt = db_exec($sql) or errDie("Unable to retrieve position.");

		if (pg_num_rows($position_rslt)) {
			extract (pg_fetch_array($position_rslt));
		} else {
			$page_option = "Add";
		}
	}

	$OUTPUT = "<h3>$page_option Position</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='page_option' value='$page_option' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Position Details</h3>
		</tr>
		<tr class='".bg_class()."'>
			<td>Name</td>
			<td><input type='text' name='name' value='$name' /></td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Description</td>
			<td><input type='text' name='description' value='$description' /></td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm &raquo' />
			</td.
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
	$v->isOk($name, "string", 1, 255, "Invalid position name.");
	$v->isOk($description, "string", 0, 255, "Invalid position description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$OUTPUT = "<h3>$page_option Position</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='name' value='$name' />
	<input type='hidden' name='description' value='$description' />
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
	$v->isOk($name, "string", 1, 255, "Invalid position name.");
	$v->isOk($description, "string", 0, 255, "Invalid position description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if (strtolower($page_option) == "edit") {
		$sql = "
		UPDATE project.positions SET name='$name', description='$description'
		WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update position.");
	} else {
		$sql = "
		INSERT INTO project.positions (name, description)
		VALUES ('$name', '$description')";
		db_exec($sql) or errDie("Unable to add position.");
	}

	$OUTPUT = "<h3>$page_option Position</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved the position.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

?>
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
	ql("task_save.php", "Add Task"),
	ql("task_view.php", "View Tasks"),
	ql("task_type_save.php", "Add Task Type"),
	ql("task_type_view.php", "View Task Types"),
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
		$sql = "SELECT * FROM project.task_types WHERE id='$id'";
		$tt_rslt = db_exec($sql) or errDie("Unable to retrieve task type.");

		if (pg_num_rows($tt_rslt)) {
			extract (pg_fetch_array($tt_rslt));
		} else {
			$page_option = "Add";
		}
	}

	$OUTPUT = "<h3>$page_option Task Type</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='page_option' value='$page_option' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Task Type Details</th>
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
	$v->isOk($name, "string", 1, 255, "Invalid task type name.");
	$v->isOk($description, "string", 0, 255, "Invalid task type description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$OUTPUT = "<h3>$page_option Task Type</h3>
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
	</table>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($name, "string", 1, 255, "Invalid task type name.");
	$v->isOk($description, "string", 0, 255, "Invalid task type description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if (strtolower($page_option) == "edit") {
		$sql = "
		UPDATE project.task_types SET name='$name', description='$description'
		WHERE id='$id'";
		db_exec($sql) or errDie("Unable to update task type.");
	} else {
		$sql = "
		INSERT INTO project.task_types (name, description)
		VALUES ('$name', '$description')";
		db_exec($sql) or errDie("Unable to add task type.");
	}

	$OUTPUT = "<h3>$page_option Task Type</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved the task type.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
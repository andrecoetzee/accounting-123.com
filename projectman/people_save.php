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
	ql("people_save.php", "Add People"),
	ql("people_view.php", "View People"),
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects")
);


require ("../template.php");

function enter($errors="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["page_option"] = "Add";
	$fields["id"] = 0;
	$fields["user_id"] = 0;
	$fields["description"] = "";

	extract ($fields, EXTR_SKIP);

	if (strtolower($page_option) == "edit") {
		$sql = "SELECT * FROM project.people WHERE id='$id'";
		$edit_rslt = db_exec($sql) or errDie("Unable to retrieve people.");

		if (pg_num_rows($edit_rslt)) {
			extract (pg_fetch_array($edit_rslt));
		} else {
			$page_option = "Add";
		}
	}

	// Cubit users dropdown ---------------------------------------------------
	$sql = "SELECT * FROM cubit.users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve cubit users.");

	$user_sel = "<select name='user_id'>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$user_sel .= "<option value='$user_data[userid]' $sel>
			$user_data[username]
		</option>";
	}
	$user_sel .= "</select>";

	$OUTPUT = "<h3>$page_option Person</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='page_option' value='$page_option' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Person Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Cubit User</td>
			<td>$user_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Person Description</td>
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
	$v->isOk($user_id, "num", 1, 20, "Invalid cubit user selection.");
	$v->isOk($description, "string", 0, 255, "Invalid person description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	// Retrieve username
	$sql = "SELECT username FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve username.");
	$username = pg_fetch_result($user_rslt, 0);

	$OUTPUT = "<h3>$page_option Person</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='user_id' value='$user_id' />
	<input type='hidden' name='description' value='$description' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Cubit User</td>
			<td>$username</td>
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
	$v->isOk($user_id, "num", 1, 20, "Invalid cubit user selection.");
	$v->isOk($description, "string", 0, 255, "Invalid person description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if (strtolower($page_option) == "edit") {
		$sql = "
		UPDATE project.people SET user_id='$user_id', description='$description'
		WHERE id='$id'";
		db_exec($sql) or errDie("Unable to add person");
	} else {
		$sql = "
		INSERT INTO project.people (user_id, description)
		VALUES ('$user_id', '$description')";
		db_exec($sql) or errDie("Unable to add person.");
	}

	$OUTPUT = "<h3>$page_option Person</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved the person to cubit.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
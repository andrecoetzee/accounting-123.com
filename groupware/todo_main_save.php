<?php

require ("../settings.php");
require ("gw-common.php");

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

require ("gw-tmpl.php");

function enter($errors="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["title"] = "";
	$fields["page_option"] = "Add";
	$fields["id"] = 0;
	$fields["team_id"] = 0;

	extract ($fields, EXTR_SKIP);

	// Create team permissions dropdown
	$sql = "SELECT * FROM crm.teams WHERE div='".USER_DIV."'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='team_id' style='width: 150px'>
	<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}
		$team_sel .= "<option value='$team_data[id]' $sel>$team_data[name]</option>";
	}
	$team_sel .= "</select>";

	$OUTPUT = "<h3>$page_option Main Todo</h3>
	<form method='post' action='".SELF."'>
	".frmupdate_passon()."
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='id' value='$id' />
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='odd'>
			<td>Title</td>
			<td>
				<input type='text' name='title' value='$title'
				style='width: 150px' />
			</td>
		</tr>
		<tr class='even'>
			<td>Team Permissions</td>
			<td>$team_sel</td>
		</tr>
	</table>

	<p></p>

	<input type='submit' value='Confirm &raquo' />
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($title, "string", 1, 255, "Invalid title.");
	$v->isOk($team_id, "num", 1, 9, "Invalid team selection.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	// Retrieve team name
	$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
	$team_name = pg_fetch_result($team_rslt, 0);

	if (empty($team_name)) {
		$team_name = "[None]";
	}

	$OUTPUT = "<h3>$page_option Main Todo</h3>
	<form method='post' action='".SELF."'>
	".frmupdate_passon()."
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='title' value='$title' />
	<input type='hidden' name='team_id' value='$team_id' />
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='odd'>
			<td>Title</td>
			<td>$title</td>
		</tr>
		<tr class='even'>
			<td>Team Permissions</td>
			<td>$team_name</td>
		</tr>
	</table>
	<input type='submit' value='Write &raquo' />
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	if ($page_option == "Edit") {
		$sql = "UPDATE cubit.todo_main SET title='$title', team_id='$team_id'
		WHERE id='$id' AND user_id='".USER_ID."'";
	} else {
		$sql = "INSERT INTO cubit.todo_main (title, user_id, team_id)
		VALUES ('$title', '".USER_ID."', '$team_id')";
	}
	db_exec($sql) or errDie("Unable to save main todo.");

	if (frmupdate_passon()) {
		$newlist = new dbSelect("todo_main", "cubit");
		$newlist->run();

		// are we an admin?
		$sql = "SELECT admin FROM cubit.users WHERE userid='".USER_ID."'";
		$admin_rslt = db_exec($sql) or errDie("Unable to check for admin.");
		$admin = pg_fetch_result($admin_rslt, 0);

		$a = array();
		if ($newlist->num_rows() > 0) {
			$a[0] = "[None]";
			while ($row = $newlist->fetch_array()) {
				if (in_team(USER_ID, $row["team_id"])) {
					$sql = "SELECT * FROM cubit.todo_main WHERE id='$row[id]'";
					$tm_rslt = db_exec($sql) or errDie("Unable to retrieve todo.");

					$count = pg_num_rows($tm_rslt);

					$a[$row["id"]] = "$row[title] ($count)";
				} else {
					continue;
				}
			}
		}
		$js = frmupdate_exec(array($a), true);
	} else {
		$js = "";
	}

	$OUTPUT = "$js
	<h3>$page_option Main Todo</h3>
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th>Write</th>
		</tr>
		<tr class='odd'><td>Successfully saved the main todo.</td></tr>
	</table>";

	return $OUTPUT;
}

<?php

require ("../settings.php");

if (!isset($_REQUEST["id"])) {
	$OUTPUT = slct();
} else {
	if (isset($_REQUEST["key"])) {
		switch ($_REQUEST["key"]) {
			default:
			case "display":
				$OUTPUT = display();
				break;
			case "slct":
				$OUTPUT = slct();
				break;
			case "update":
				$OUTPUT = update();
				break;
			case "remove":
				$OUTPUT = remove();
				break;
		}
	} else {
		$OUTPUT = display();
	}
}

require ("../template.php");



function slct()
{

	$sql = "SELECT * FROM project.teams";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='id'>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		$team_sel .= "<option value='$team_data[id]'>$team_data[name]</option>";
	}
	$team_sel .= "</select>";

	$OUTPUT = "
				<h3>Allocate People to Project Management Teams</h3>
				<form method='post' action='".SELF."'>
					<input type='hidden' name='key' value='display' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Team</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>$team_sel</td>
						<td><input type='submit' value='Select &raquo' /></td>
					</tr>
				</table>
				</form>";
	return $OUTPUT;

}



function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["person_id"] = 0;

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT * FROM project.teams WHERE id='$id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team details.");
	$team_data = pg_fetch_array($team_rslt);

	// People dropdown --------------------------------------------------------
	$sql = "SELECT * FROM project.people";
	$people_rslt = db_exec($sql) or errDie("Unable to retrieve people.");

	$people_sel = "<select name='person_id'>";
	while ($people_data = pg_fetch_array($people_rslt)) {
		if ($person_id == $people_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		// Retrieve username
		$sql = "SELECT username FROM cubit.users WHERE userid='$people_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve username.");
		$username = pg_fetch_result($user_rslt, 0);

		$people_sel .= "<option value='$people_data[id]'>$username</option>";
	}
	$people_sel .= "</select>";

	// Create a list of people already added
	$sql = "SELECT * FROM project.teams_people WHERE team_id='$id'";
	$tp_rslt = db_exec($sql) or errDie("Unable to retrieve team people.");

	$tp_out = "";
	while ($tp_data = pg_fetch_array($tp_rslt)) {
		// Retrieve the person
		$sql = "SELECT * FROM project.people WHERE id='$tp_data[person_id]'";
		$person_rslt = db_exec($sql) or errDie("Unable to retrieve person.");
		$person_data = pg_fetch_array($person_rslt);

		// Retrieve username
		$sql = "SELECT username FROM cubit.users WHERE userid='$person_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve username.");
		$username = pg_fetch_result($user_rslt, 0);

		$tp_out .= "
		<tr class='".bg_class()."'>
			<td>$username</td>
			<td><a href='".SELF."?key=remove&id=$id&tp_id=$tp_data[id]'>Remove</a></td>
		</tr>";
	}

	if (empty($tp_out)) {
		$tp_out = "
		<tr class='".bg_class()."'>
			<td colspan='2'><li>No people found</li></td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Allocate People to Project Management Teams</h3>
	<form method='post' action='".SELF."'>
		<input type='hidden' name='key' value='update' />
		<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Project Management Team Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Name</td>
			<td>$team_data[name]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Description</td>
			<td>$team_data[description]</td>
		</tr>
		<tr>
			<th colspan='2'>Add Person</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>$people_sel</td>
			<td><input type='submit' value='Add &raquo' /></td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Username</th>
			<th>Options</th>
		</tr>
		$tp_out
	</table>
	</form>
	</center>";
	return $OUTPUT;

}




function update()
{

	extract ($_REQUEST);

	$sql = "
	SELECT * FROM project.teams_people
	WHERE team_id='$id' AND person_id='$person_id'";
	$tp_rslt = db_exec($sql) or errDie("Unable to retrieve team people.");

	if (!pg_num_rows($tp_rslt)) {
		$sql = "
		INSERT INTO project.teams_people (team_id, person_id)
		VALUES ('$id', '$person_id')";
		db_exec($sql) or errDie("Unable to update team allocation.");
	}
	return display();

}



function remove()
{

	extract ($_REQUEST);

	$sql = "DELETE FROM project.teams_people WHERE id='$tp_id'";
	db_exec($sql) or errDie("Unable to remove person from team.");
	return display();

}
<?php

require_once(relpath("settings.php"));

function addTodayEntry($section_name, $id, $date, $info="Brought Forward")
{
	$section_name = strtolower($section_name);
	$section_name = ucfirst($section_name);

	// Retrieve today section
	$sql = "SELECT * FROM cubit.today_sections WHERE name='$section_name'";
	$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
	$section_data = pg_fetch_array($section_rslt);

	if (pg_num_rows($section_rslt)) {
		// URL
		$link = $section_data["title_link"].$id;

		// Retrieve details
		$sql = "
		SELECT * FROM $section_data[table_name]
		WHERE $section_data[id_column]='$id'";
		$row_rslt = db_exec($sql) or errDie("Unable to retrieve details.");
		$row = pg_fetch_array($row_rslt);

		$title = $row[$section_data["title_column"]];

		if (isset($row["team_id"])) {
			$team_id = $row["team_id"];
		} else {
			$team_id = 0;
		}

		$sql = "
		INSERT INTO cubit.today (date, section_id, info, link, title,
			user_id, link_id, team_id)
		VALUES ('$date', '$section_data[id]', '$info', '$link', '$title',
			'".USER_ID."', '$id', '$team_id')";
		db_exec($sql) or errDie("Unable to add to today.");
	} else {
		return false;
	}

	return true;
}

function removeTodayEntry($section_name, $id)
{
	$section_name = strtolower($section_name);
	$section_name = ucfirst($section_name);

	// Retrieve today section
	$sql = "SELECT * FROM cubit.today_sections WHERE name='$section_name'";
	$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
	$section_data = pg_fetch_array($section_rslt);

	if (pg_num_rows($section_rslt)) {
		// Remove the entry
		$sql = "DELETE FROM cubit.today
		WHERE link_id='$id' AND section_id='$section_data[id]'";
		db_exec($sql) or errDie("Unable to remove today entry.");
	} else {
		return false;
	}

	return true;
}

function in_team($team_id, $user_id)
{
	if (empty($team_id) || empty($user_id)) {
		return true;
	}

	// Retrieve user
	$sql = "SELECT * FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
	$user_data = pg_fetch_array($user_rslt);

	// Retrieve teams
	$sql = "SELECT * FROM crm.team_owners
	WHERE team_id='$team_id' AND user_id='$user_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	if ($user_data["admin"] || pg_num_rows($team_rslt) || !$team_id)
	{
		return true;
	}

	return false;
}

?>
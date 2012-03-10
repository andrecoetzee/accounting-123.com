<?php

require_once("../settings.php");

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
	// Retrieve user
	$sql = "SELECT * FROM cubit.users WHERE user_id='$user_id'";
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
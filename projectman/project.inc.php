<?php

class Project
{
	public project_id;
	public name;
	public champion_id;
	public sponsor_id;
	public leader_id;
	public start_date;
	public complete_date;
	public priority;
	public access_people;
	public access_teams;

	function __construct($project_id)
	{
		$sql = "SELECT * FROM projects WHERE id='$project_id'";
		$project_rslt = db_exec($sql) or errDie("Unable to retrieve project.");
		$project_data = pg_fetch_array($project_rslt);

		$this->project_id = $project_data["id"];
		$this->name = $project_data["name"];
		$this->champion_id = $project_data["champion_id"];
		$this->sponsor_id = $project_data["sponsor_id"];
		$this->leader_id = $project_data["leader_id"];
		$this->start_date = $project_data["start_date"];
		$this->complete_date = $project_data["complete_date"];
		$this->priority = $project_data["priority"];
	}

	function add_team_access($team)
	{
		$sql = "
		INSERT INTO project_team_access (project_id, team_id)
		VALUES ('".$this->project_id."', '".$team->team_id."')";
		$pta_rslt = db_exec($sql) or errDie("Unable to add team access.");

		if (pg_affected_rows($pta_rslt)) {
			$this->access_teams[] = $team->team_id;
			return true;
		} else {
			return false;
		}
	}

	function add_person_access($person)
	{
		$sql = "
		INSERT INTO project_person_access (project_id, person_id)
		VALUES ('".$this->project_id."', '".$person->person_id."')";
		$ppa_rslt = db_exec($sql) or errDie("Unable to add person access.");

		if (pg_affected_rows($ppa_rslt)) {
			$this->access_people[] = $person->person_id;
			return true;
		} else {
			return false;
		}
	}

	function have_team_access($team)
	{
		$sql = "
		SELECT count(id) FROM project_team_access
		WHERE project_id='".$this->project_id."' AND team_id='".$team->team_id."'";
		$pta_rslt = db_exec($sql) or errDie("Unable to retrieve team access.");

		if (pg_num_rows($pta_rslt)) {
			return true;
		} else {
			return false;
		}
	}

	function have_person_access($person)
	{
		$sql = "
		SELECT count(id) FROM project_person_access
		WHERE project_id='".$this->project_id."'
		AND person_id='".$person->person_id."'";
		$ppa_rslt = db_exec($sql) or errDie("Unable to retrieve person access.");

		if (pg_num_rows($ppa_rslt)) {
			return true;
		} else {
			return false;
		}
	}

	function set_priority($priority)
	{
		$sql = "
		UPDATE projects SET priority='$priority' WHERE id='".$this->project_id."'";
		$priority_rslt = db_exec($sql) or errDie("Unable to update priority.");

		if (pg_affected_rows($priority_rslt)) {
			$this->priority = $priority;
			return true;
		} else {
			return false;
		}
	}
}
?>
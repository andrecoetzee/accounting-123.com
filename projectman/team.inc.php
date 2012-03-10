<?php

class Team
{
	public id;
	public name;
	public description;
	public people;

	function __construct($team_id)
	{
		$sql = "SELECT * FROM teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
		$team_data = pg_fetch_array($team_rslt);

		$this->team_id = $team_data["id"];
		$this->name = $team_data["name"];
		$this->description = $team_data["description"];
	}
}
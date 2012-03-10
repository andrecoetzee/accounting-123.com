<?php

class Task
{
	public task_id;
	public name;
	public leader_id;
	public start_date;
	public complete_date;
	public notes;
	public priority;
	public access_team;
	public access_person;

	function __construct($task_id)
	{
		$sql = "SELECT * FROM tasks WHERE id='$task_id'";
		$task_rslt = db_exec($sql) or errDie("Unable to retrieve task.");
		$task_data = pg_fetch_array($task_rslt);

		$this->task_id = $task_id;
		$this->name = $task_data["name"];
		$this->leader_id = $task_data["leader_id"];
		$this->start_date = $task_data["start_date"];
		$this->complete_date = $task_data["complete_date"];
		$this->priority = $task_data["priority"];
	}

	function add_team_access($team)
	{
		$sql = "INSERT INTO
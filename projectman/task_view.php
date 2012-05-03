<?php

require ("../settings.php");

if (isset($_REQUEST["project_id"]) && is_numeric($_REQUEST["project_id"]) &&
	$_REQUEST["project_id"]) {
	$OUTPUT = display();
} else {
	$OUTPUT = slct();
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




function slct()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["project_id"] = 0;

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT * FROM project.projects";
	$proj_rslt = db_exec($sql) or errDie("Unable to retrieve projects.");

	$proj_sel = "<select name='project_id'>";
	while ($proj_data = pg_fetch_array($proj_rslt)) {
		if ($project_id == $proj_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$proj_sel .= "<option value='$proj_data[id]' $sel>$proj_data[name]</option>";
	}

	$OUTPUT = "
		<h3>View Tasks</h3>
		<form method='post' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Select Project</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$proj_sel</td>
				<td><input type='submit' value='Select'></td>
			</tr>
		</table>
		</form>
		<p>";
	return $OUTPUT;

}



function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["project_id"] = 0;

	extract ($fields, EXTR_SKIP);

	if ($project_id) {
		$where = "WHERE project_id='$project_id'";
	} else {
		$where = "";
	}

	$sql = "SELECT * FROM project.tasks $where";
	$task_rslt = db_exec($sql) or errDie("Unable to retrieve tasks.");

	$tasks_out = "";
	while ($task_data = pg_fetch_array($task_rslt)) {
		// Retrieve project
		$sql = "SELECT name FROM project.projects WHERE id='$task_data[project_id]'";
		$proj_rslt = db_exec($sql) or errDie("Unable to retrieve project.");
		$project = pg_fetch_result($proj_rslt, 0);

		// Retrieve person
		$sql = "SELECT user_id FROM project.people WHERE id='$task_data[leader_id]'";
		$person_rslt = db_exec($sql) or errDie("Unable to retrieve leader person.");
		$user_id = pg_fetch_result($person_rslt, 0);

		// Retrieve user
		$sql = "SELECT * FROM cubit.users WHERE userid='$user_id'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
		$user_data = pg_fetch_array($user_rslt);

		if(!isset($task_data['start_date']))
			$task_data['start_date'] = "";
		if(!isset($task_data['end_date']))
			$task_data['end_date'] = "";

		$tasks_out .= "
			<tr class='".bg_class()."'>
				<td>$project</td>
				<td>$task_data[name]</td>
				<td>$user_data[username]</td>
				<td>".substr($task_data['start_time'],0,10)."</td>
				<td>".substr($task_data['end_time'],0,10)."</td>
				<td>$task_data[priority]</td>
				<td><a href='task_save.php?id=$task_data[id]&project_id=$task_data[project_id]&page_option=Edit'>Edit</a></td>
			</tr>";
	}

	if (empty($tasks_out)) {
		$tasks_out = "
			<tr class='".bg_class()."'>
				<td colspan='10'><li>No tasks found</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>View Tasks</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Project</th>
				<th>Name</th>
				<th>Leader</th>
				<th>Start Date</th>
				<th>Complete Date</th>
				<th>Priority</th>
				<th>Options</th>
			</tr>
			$tasks_out
		</table>";
	return $OUTPUT;

}


?>
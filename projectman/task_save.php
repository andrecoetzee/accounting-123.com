<?php

require ("../settings.php");

if (!isset($_REQUEST["project_id"])) {
	$OUTPUT = slct();
} else {
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
			case "slct":
				$OUTPUT = slct();
				break;
		}
	} else {
		$OUTPUT = enter();
	}
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
	$fields["page_option"] = "Add";

	// Retrieve list of projects
	$sql = "SELECT * FROM project.projects";
	$proj_rslt = db_exec($sql) or errDie("Unable to retrieve projects.");

	$proj_sel = "<select name='project_id'>";
	while ($proj_data = pg_fetch_array($proj_rslt)) {
		$proj_sel .= "<option value='$proj_data[id]'>$proj_data[name]</option>";
	}

	if(!isset($page_option)) 
		$page_option = "Add";

	$OUTPUT = "
		<h3>$page_option Task</h3>
		<form method='POST' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Select Project</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$proj_sel</td>
				<td><input type='submit' value='Select'></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function enter($errors="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["id"] = 0;
	$fields["page_option"] = "Add";
	$fields["name"] = "";
	$fields["leader_id"] = 0;
	$fields["start_day"] = date("d");
	$fields["start_month"] = date("m");
	$fields["start_year"] = date("Y");
	$fields["end_day"] = date("d");
	$fields["end_month"] = date("m");
	$fields["end_year"] = date("Y");
	$fields["notes"] = "";
	$fields["priority"] = "";

	extract ($fields, EXTR_SKIP);

	if (strtolower($page_option) == "edit") {
		$sql = "SELECT * FROM project.tasks WHERE id='$id'";
		$task_rslt = db_exec($sql) or errDie("Unable to retrieve task.");

		if (pg_num_rows($task_rslt)) {
			extract(pg_fetch_array($task_rslt));
		} else {
			$page_option = "Add";
		}
	}

	$sql = "SELECT name FROM project.projects WHERE id='$project_id'";
	$proj_rslt = db_exec($sql) or errDie("Unable to retrieve project.");
	$project = pg_fetch_result($proj_rslt, 0);

	// Leader dropdown --------------------------------------------------------
	$sql = "SELECT * FROM project.people";
	$leader_rslt = db_exec($sql) or errDie("Unable to retrieve leader.");

	$leader_sel = "<select name='leader_id' style='width: 100%'>";
	while ($leader_data = pg_fetch_array($leader_rslt)) {
		$sql = "SELECT * FROM cubit.users WHERE userid='$leader_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve leader user.");
		$user_data = pg_fetch_array($user_rslt);

		if ($leader_id == $leader_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$leader_sel .= "<option value='$leader_data[id]' $sel>$user_data[username]</option>";
	}
	$leader_sel .= "</select>";

	// Priority dropdown ------------------------------------------------------
	$priority_sel = "<select name='priority'>";
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $priority) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$priority_sel .= "<option value='$i' $sel>$i</option>";
	}
	$priority_sel .= "</select>";

	$style = "style='width: 100%;'";

	if(!isset($ts_no))
		$ts_no = "";
	if(!isset($ts_yes))
		$ts_yes = "";

	$OUTPUT = "
		<h3>$page_option Task</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='project_id' value='$project_id' />
			<input type='hidden' name='page_option' value='$page_option' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Task Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Project</td>
				<td>$project</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name</td>
				<td><input type='text' name='name' value='$name' $style /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Leader</td>
				<td>$leader_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Time - Start Task</td>
				<td>".mkDateSelect("start", $start_year, $start_month, $start_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Time - Complete Task</td>
				<td>".mkDateSelect("end", $end_year, $end_month, $end_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Notes</td>
				<td><textarea name='notes' rows='5' $style>$notes</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Priority</td>
				<td>$priority_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Sub Task</td>
				<td>
					Yes <input type='radio' name='task_sub' value='yes' $ts_yes />
					No <input type='radio' name='task_sub' value='no' $ts_no />
				</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo' /></td>
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
	$v->isOk($name, "string", 1, 255, "Invalid task name.");
	$v->isOk($leader_id, "num", 1, 20, "Invalid leader.");
	$v->isOk($start_day, "num", 1, 2, "Invalid date start (day).");
	$v->isOk($start_month, "num", 1, 2, "Invalid date start (month).");
	$v->isOk($start_year, "num", 4, 4, "Invalid date start (year).");
	$v->isOk($end_day, "num", 1, 2, "Invalid date end (day).");
	$v->isOk($end_month, "num", 1, 2, "Invalid date end (month).");
	$v->isOk($end_year, "num", 4, 4, "Invalid date end (year).");
	$v->isOk($priority, "num", 1, 9, "Invalid priority.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$sql = "SELECT name FROM project.projects WHERE id='$project_id'";
	$proj_rslt = db_exec($sql) or errDie("Unable to retrieve project.");
	$project = pg_fetch_result($proj_rslt, 0);

	$sql = "SELECT username FROM cubit.users WHERE userid='$leader_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve leader.");
	$leader_name = pg_fetch_result($user_rslt, 0);

	$start_date = "$start_day-$start_month-$start_year";
	$end_date = "$end_day-$end_month-$end_year";

	if(!isset($page_option))
		$page_option = "Confirm";

	if(!isset($id))
		$id = "";

	$OUTPUT = "
		<h3>$page_option Task</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='project_id' value='$project_id' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='name' value='$name' />
			<input type='hidden' name='leader_id' value='$leader_id' />
			<input type='hidden' name='start_day' value='$start_day' />
			<input type='hidden' name='start_month' value='$start_month' />
			<input type='hidden' name='start_year' value='$start_year' />
			<input type='hidden' name='end_day' value='$end_day' />
			<input type='hidden' name='end_month' value='$end_month' />
			<input type='hidden' name='end_year' value='$end_year' />
			<input type='hidden' name='notes' value='$notes' />
			<input type='hidden' name='priority' value='$priority' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Task Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Project</td>
				<td>$project</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name</td>
				<td>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Leader</td>
				<td>$leader_name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Date - Start Task</td>
				<td>$start_date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Date - Complete Task</td>
				<td>$end_date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Notes</td>
				<td>$notes</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Priority</td>
				<td>$priority</td>
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
	$v->isOk($name, "string", 1, 255, "Invalid task name.");
	$v->isOk($leader_id, "num", 1, 20, "Invalid leader.");
	$v->isOk($start_day, "num", 1, 2, "Invalid date start (day).");
	$v->isOk($start_month, "num", 1, 2, "Invalid date start (month).");
	$v->isOk($start_year, "num", 4, 4, "Invalid date start (year).");
	$v->isOk($end_day, "num", 1, 2, "Invalid date end (day).");
	$v->isOk($end_month, "num", 1, 2, "Invalid date end (month).");
	$v->isOk($end_year, "num", 4, 4, "Invalid date end (year).");
	$v->isOk($priority, "num", 1, 9, "Invalid priority.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$start_date = "$start_year-$start_month-$start_day";
	$end_date = "$end_year-$end_month-$end_day";

//start_date='$start_date', end_date='$end_date',
	if (strtolower($page_option) == "edit") {
		$sql = "
			UPDATE project.tasks 
			SET name='$name', leader_id='$leader_id', notes='$notes', 
				priority='$priority', project_id='$project_id' 
			WHERE id = '$id'";
		db_exec($sql) or errDie("Unable to update task.");
	} else {
		$sql = "
			INSERT INTO project.tasks (
				name, leader_id, start_time, end_time, notes, priority, project_id
			) VALUES (
				'$name', '$leader_id', '$start_date', '$end_date', '$notes', '$priority', '$project_id'
			)";
		db_exec($sql) or errDie("Unable to add task.");
	}

	$OUTPUT = "
		<h3>$page_option Task</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><li>Successfully saved the task.</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>
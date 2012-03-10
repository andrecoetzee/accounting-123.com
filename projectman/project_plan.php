<?php

require ("../settings.php");

if (!isset($_REQUEST["project_id"])) {
	$OUTPUT = slct();
} else {
	$OUTPUT = display();
}

require ("../template.php");

function slct()
{
	extract($_REQUEST);

	$fields = array();
	$fields["project_id"] = 0;

	extract($fields, EXTR_SKIP);

	// Retrieve projects
	$sql = "SELECT * FROM project.projects";
	$project_rslt = db_exec($sql) or errDie("Unable to retrieve projects.");

	$project_sel = "<select name='project_id' style='width: 100%'
					 onchange='javascript:document.form.submit();'>";
	$project_sel.= "<option value='0'>[None]</option>";
	while ($project_data = pg_fetch_array($project_rslt)) {
		if ($project_id == $project_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$project_sel.= "<option value='$project_data[id]' $sel>
							$project_data[name]
						</option>";
	}

	$OUTPUT = "<center>
	<h3>Project Plan</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Select Project</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$project_sel</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function display()
{
	extract($_REQUEST);

	$sql = "SELECT *,
				   extract('epoch' FROM start_time) AS e_start,
				   extract('epoch' FROM end_time) AS e_end
			FROM project.tasks
			WHERE project_id='$project_id' AND sub!='yes'";
	$mtask_rslt = db_exec($sql) or errDie("Unable to retrieve main tasks.");

	$task_out .= "";
	while ($mtask_data = pg_fetch_array($mtask_rslt)) {
		$task_out .= "<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Main</td>
			<td>$mtask_data[name]</td>
			<td>$mtask_data[notes]</td>
			<td>".date("d-m-Y", $mtask_data["e_start"])."</td>
			<td>".date("d-m-Y", $mtask_data["e_end"])."</td>
		</tr>";

		$sql = "SELECT *,
					   extract('epoch' FROM start_time) AS e_start,
					   extract('epoch' FROM end_time) AS e_end
				FROM project.tasks
				WHERE project_id='$project_id' AND sub='yes'
					  AND main_id='$mtask_data[id]'";
		$stask_rslt = db_exec($sql) or errDie("Unable to retrieve sub tasks.");

		while ($stask_data = pg_fetch_array($stask_rslt)) {
			$task_out .= "<tr bgcolor='".TMPL_tblDataColor2."'>
				<td>&nbsp; &nbsp; Sub</td>
				<td>&nbsp; &nbsp; $stask_data[name]</td>
				<td>&nbsp; &nbsp; $stask_data[notes]</td>
				<td>&nbsp; &nbsp; ".date("d-m-Y", $stask_data["e_start"])."</td>
				<td>&nbsp; &nbsp; ".date("d-m-Y", $stask_data["e_end"])."</td>
			</tr>";
		}
	}

	$OUTPUT = "<h3>Project Plan</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Task Type</th>
			<th>Name</th>
			<th>Notes</th>
			<th>Starting Date</th>
			<th>Ending Date</th>
		</tr>
		$task_out
	</table>";

	return $OUTPUT;
}
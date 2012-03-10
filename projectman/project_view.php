<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("project_save.php", "Add Project"),
	ql("project_charter.php", "Project Charter"),
	ql("gantt_display.php", "Gantt Chart")
);


require ("../template.php");

function display()
{
	$sql = "SELECT * FROM project.projects WHERE sub='no'";
	$proj_rslt = db_exec($sql) or errDie("Unable to retrieve projects.");

	$proj_out = "";
	while ($proj_data = pg_fetch_array($proj_rslt)) {

		$people_types = array(
			"champion"=>"champion_id",
			"sponsor"=>"sponsor_id",
			"leader"=>"leader_id"
		);

		foreach ($people_types as $key=>$value) {
			$sql = "SELECT * FROM cubit.users WHERE userid='$proj_data[$value]'";
			$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
			$$key = pg_fetch_array($user_rslt);
		}

		$sql = "SELECT * FROM project.charters WHERE project_id='$proj_data[id]'";
		$ch_rslt = db_exec($sql) or errDie("Unable to retrieve charter.");

		if (pg_num_rows($ch_rslt)) {
			$charter = "<td><a href='charter_view.php?project_id=$proj_data[id]'>
							View Charter
						</a></td>";
		} else {
			$charter = "";
		}

		$proj_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$proj_data[name]</td>
			<td>$champion[username]</td>
			<td>$sponsor[username]</td>
			<td>$leader[username]</td>
			<td>$proj_data[edate]</td>
			<td>$proj_data[priority]</td>
			<td>
				<a href='project_save.php?id=$proj_data[id]&page_option=Edit'>
					Edit
				</a>
			</td>
			<td>
				<a href='task_save.php?project_id=$proj_data[id]'>Add Task</a>
			</td>
			<td>
				<a href='task_view.php?project_id=$proj_data[id]'>View Tasks</a>
			</td>
			$charter
		</tr>";

		$sql = "SELECT * FROM project.projects WHERE sub='yes'";
		$sproj_rslt = db_exec($sql) or errDie("Unable to retrieve sub projects.");

		while ($sproj_data = pg_fetch_array($sproj_rslt)) {
			foreach ($people_types as $key=>$value) {
				$sql = "SELECT * FROM cubit.users WHERE userid='$sproj_data[$value]'";
				$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
				$$key = pg_fetch_array($user_rslt);
			}

			$proj_out .= "<tr bgcolor='".bgcolorg()."'>
				<td><li>$sproj_data[name]</li></td>
				<td>$champion[username]</td>
				<td>$sponsor[username]</td>
				<td>$leader[username]</td>
				<td>$sproj_data[edate]</td>
				<td>$sproj_data[priority]</td>
				<td>
					<a href='project_save.php?id=$sproj_data[id]&page_option=Edit'>
						Edit
					</a>
				</td>
				<td>
					<a href='task_save.php?project_id=$sproj_data[id]'>Add Task</a>
				</td>
				<td>
					<a href='task_view.php?project_id=$sproj_data[id]'>View Tasks</a>
				</td>
			</tr>";
		}
	}

	if (empty($proj_out)) {
		$proj_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='10'><li>No projects found.</li></td>
		</tr>";
	}

	$OUTPUT = "<h3>View Projects</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Name</th>
			<th>Champion</th>
			<th>Sponsor</th>
			<th>Leader</th>
			<th>Expected Date of Completion</th>
			<th>Priority</th>
			<th colspan='3'>Options</th>
		</tr>
		$proj_out
	</table>";

	return $OUTPUT;
}

?>
<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("people_save.php", "Add People"),
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects")
);

require ("../template.php");

function display()
{
	// Retrieve people
	$sql = "SELECT * FROM project.people";
	$people_rslt = db_exec($sql) or errDie("Unable to retrieve people.");

	$people_out = "";
	while ($people_data = pg_fetch_array($people_rslt)) {
		$sql = "SELECT * FROM cubit.users WHERE userid='$people_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");
		$user_data = pg_fetch_array($user_rslt);

		$people_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$user_data[username]</td>
			<td>$people_data[description]</td>
			<td>
				<a href='people_save.php?page_option=Edit&id=$people_data[id]'>
					Edit
				</a>
			</td>
		</tr>";
	}

	if (empty($people_out)) {
		$people_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><li>No people found</li></td>
		</tr>";
	}

	$OUTPUT = "<h3>View People</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Username</th>
			<th>Description</th>
			<th>Options</th>
		</tr>
		$people_out
	</table>";

	return $OUTPUT;
}
<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql("team_view.php","View Project Management Teams")
			);

require ("../template.php");




function display()
{

	// Retrieve teams
	$sql = "SELECT * FROM project.teams";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_out = "";
	while ($team_data = pg_fetch_array($team_rslt)) {
		$team_out .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$team_data[name]</td>
							<td>$team_data[description]</td>
							<td><a href='team_save.php?page_option=Edit&id=$team_data[id]'>Edit</a></td>
						</tr>";
	}

	if (empty($team_out)) {
		$team_out = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='3'><li>No teams found</li></td>
						</tr>";
	}

	$OUTPUT = "
	<h3>View Project Management Teams</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Name</th>
			<th>Description</th>
			<th>Options</th>
		</tr>
		$team_out
	</table>";
	return $OUTPUT;

}

?>
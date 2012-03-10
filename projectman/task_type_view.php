<?

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= "<p>".mkQuickLinks(
	ql("task_save.php", "Add Task"),
	ql("task_view.php", "View Tasks"),
	ql("task_type_save.php", "Add Task Type"),
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects")
);

require ("../template.php");



function display()
{

	extract ($_REQUEST);

	$sql = "SELECT * FROM project.task_types";
	$tt_rslt = db_exec($sql) or errDie("Unable to retrieve task types.");

	$tt_out = "";
	while ($tt_data = pg_fetch_array($tt_rslt)) {
		$tt_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$tt_data[name]</td>
				<td>$tt_data[description]</td>
				<td><a href='task_type_save.php?id=$tt_data[id]&page_option=Edit'>Edit</a></td>
			</tr>";
	}

	if (empty($tt_out)) {
		$tt_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='3'><li>No task types found</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>View Task Types</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>Options</th>
			</tr>
			$tt_out
		</table>";
	return $OUTPUT;

}


?>
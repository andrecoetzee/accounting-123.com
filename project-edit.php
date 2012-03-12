<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch($_POST["key"]){
			case "confirm":
				$OUTPUT = confirm ($_POST);
				break;
			case "write":
				$OUTPUT = write ($_POST);
				break;
			default:
				$OUTPUT = edit ($_POST);
		}
	}else {
		$OUTPUT = edit ($_GET);
	}

	$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='project-add.php'>Add Project</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='projects-edit.php'>View/Edit Project Information</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-add.php'>Add Cost Center</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>
			";

	require ("template.php");





function edit ($_POST)
{

	extract ($_POST);

	if(!isset($id) OR (strlen($id) < 1)){
		return "<li class='err'>Invalid Use Of Module. Invalid Project ID.</li>";
	}
	
	db_connect ();
	
	$get_project = "SELECT * FROM projects WHERE id = '$id' LIMIT 1";
	$run_project = db_exec($get_project) or errDie ("Unable to get project information.");
	if(pg_numrows($run_project) < 1){
		return "<li class='err'>Poject Information Not Found.</li>";
	}
	
	$parr = pg_fetch_array($run_project);
	
	if(!isset($project_name))
		$project_name = $parr['project_name'];
	if(!isset($project_code))
		$project_code = $parr['code'];

	$display = "
					<h2>Edit Project Information</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='id' value='$id'>
						<tr>
							<th>Project Name</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' name='project_name' value='$project_name'></td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Project Code</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' size='8' name='project_code' value='$project_code'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Confirm'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function confirm ($_POST)
{

	extract ($_POST);


	$display = "
					<h2>Confirm Project Information</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='project_name' value='$project_name'>
						<input type='hidden' name='project_code' value='$project_code'>
						<tr>
							<th>Project Name</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$project_name</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Project Code</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$project_code</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Confirm'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function write ($_POST)
{

	extract ($_POST);

	if(!isset($id) OR (strlen($id) < 1)){
		return "<li class='err'>Invalid Use Of Module. Invalid Project ID.</li>";
	}

	db_connect ();

	$upd_sql = "UPDATE projects SET project_name = '$project_name', code = '$project_code' WHERE id = '$id'";
	$run_upd = db_exec($upd_sql) or errDie ("Unable to update project information.");

	$display = "
					<h2>Project Updated</h2>
					<table ".TMPL_tblDflts.">
					
					</table>
				";
	return $display;

}




?>
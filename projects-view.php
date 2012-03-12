<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings
require ("settings.php");

foreach ($_GET as $each => $own){
	$_POST[$each] = $own;	
}

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "add":
			$OUTPUT = add_project ($_POST);
			break;
		case "edit":
			$OUTPUT = edit ($_POST);
			break;
		case "confirmedit":
			$OUTPUT = confirmedit ($_POST);
			break;
		case "writeedit":
			$OUTPUT = writeedit ($_POST);
			break;
		case "remove":
			$OUTPUT = remove ($_POST);
			break;
		case "writeremove":
			$OUTPUT = writeremove ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
}else {
	$OUTPUT = enter ();
}

$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='projects-edit.php'>Edit Project Information</a></td>
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


# display output
require ("template.php");




# enter new data
function enter ($err="")
{

	db_connect ();

	if(!isset($project1))
		$project1 = "";
	if(!isset($project2))
		$project2 = "";

	$project_listing = "";

	$get_lev1 = "SELECT * FROM projects WHERE id != '1' ORDER BY project_name";
	$run_lev1 = db_exec($get_lev1) or errDie("Unable to get project information.");
	if(pg_numrows($run_lev1) < 1)
		print "<li class='err'>No Project Groups Found. Please add one.</li>";

	$project1_drop = "<select name='project1' onChange='javascript:document.form1.submit()'>";
	while ($larr1 = pg_fetch_array($run_lev1)){
		if($larr1["id"] == $project1){
			$project1_drop .= "<option value='$larr1[id]' selected>$larr1[project_name]</option>";
		}else {
			$project1_drop .= "<option value='$larr1[id]'>$larr1[project_name]</option>";
		}
	}
	$project1_drop .= "</select>";



	$get_lev2 = "SELECT * FROM sub_projects WHERE project_id != '1' ORDER BY sub_project_name";
	$run_lev2 = db_exec($get_lev2) or errDie("Unable to get sub-project information.");

	$project2_drop = "<select name='project2' onChange='javascript:document.form1.submit()'>";
	while ($larr2 = pg_fetch_array($run_lev2)){
		if($larr2["id"] == $project2){
			$project2_drop .= "<option value='$larr2[id]' selected>$larr2[sub_project_name]</option>";
		}else {
			$project2_drop .= "<option value='$larr2[id]'>$larr2[sub_project_name]</option>";
		}
	}
	$project2_drop .= "</select>";




	#make listing of current entries

	$get_pro1 = "SELECT * FROM projects WHERE id != '1' ORDER BY project_name";
	$run_pro1 = db_exec($get_pro1) or errDie("Unable to get project information.");
	if(pg_numrows($run_pro1) < 1){
		$project_listing .= "<tr><td><li class='err'>No Existing Projects Found.</li></td></tr>";
	}else {
		$project_listing .= "";
		while ($p1arr = pg_fetch_array($run_pro1)){
			#check if this entry has any cost centers ...
			$get_check = "SELECT * FROM sub_projects WHERE project_id = '$p1arr[id]' LIMIT 1";
			$run_check = db_exec($get_check) or errDie("Unable to get cost center check.");
			if(pg_numrows($run_check) > 0){
				$showremove = "";
			}else {
				$showremove = "<a href='projects-view.php?key=remove&project=1&id=$p1arr[id]'>Remove</a>";
			}
			$project_listing .= "
									<tr>
										<th colspan='4'>Project Information</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td colspan='2'>$p1arr[project_name]</td>
										<td><a href='projects-view.php?key=edit&project=1&id=$p1arr[id]'>Edit</a></td>
										<td>$showremove</td>
									</tr>
								";

			$get_pro2 = "SELECT * FROM sub_projects WHERE project_id = '$p1arr[id]' ORDER BY sub_project_name";
			$run_pro2 = db_exec($get_pro2) or errDie("Unable to get project information.");
			if(pg_numrows($run_pro2) < 1){
				$project_listing .= "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Sub-Sections Found.</td></tr>";
			}else {
				$project_listing .= "";
				while ($p2arr = pg_fetch_array($run_pro2)){
					#check if this entry has any cost centers ...
					$get_check = "SELECT * FROM sub_sub_projects WHERE sub_project_id = '$p2arr[id]' AND project_id != '1' LIMIT 1";
					$run_check = db_exec($get_check) or errDie("Unable to get cost center check.");
					if(pg_numrows($run_check) > 0){
						$showremove = "";
					}else {
						$showremove = "<a href='projects-view?key=remove&project=2&id=$p2arr[id]'>Remove</a>";
					}
					$project_listing .= "
											<tr bgcolor='".bgcolorg()."'>
												<td colspan='2'>$p2arr[sub_project_name]</td>
												<td><a href='projects-view.php?key=edit&project=2&id=$p2arr[id]'>Edit</a></td>
												<td>$showremove</td>
											</tr>
										";

					$get_pro3 = "SELECT * FROM sub_sub_projects WHERE sub_project_id = '$p2arr[id]' ORDER BY sub_sub_project_name";
					$run_pro3 = db_exec($get_pro3) or errDie("Unable to get project information.");
					if(pg_numrows($run_pro3) < 1){
						$project_listing .= "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Sub-Sub-Sections Found.</td></tr>";
					}else {
						$project_listing .= "";
						while ($p3arr = pg_fetch_array($run_pro3)){
							#check if this entry has any cost centers ...
							$get_check = "SELECT * FROM costcenters_links WHERE project3 = '$p3arr[id]' LIMIT 1";
							$run_check = db_exec($get_check) or errDie("Unable to get cost center check.");
							if(pg_numrows($run_check) > 0){
								$showremove = "";
							}else {
								$showremove = "<a href='projects-view?key=remove&project=3&id=$p3arr[id]'>Remove</a>";
							}
							$project_listing .= "
													<tr bgcolor='".bgcolorg()."'>
														<td width='4%'></td>
														<td>$p3arr[sub_sub_project_name]</td>
														<td><a href='projects-view.php?key=edit&project=3&id=$p3arr[id]'>Edit</a></td>
														<td>$showremove</td>
														<td><a href='costcenter-allocation-add.php?project=$p1arr[id]&subsub=$p3arr[id]'>Assign Cost Centers</a></td>
														<td><a href='costcenter-allocation-change.php?project=$p1arr[id]&subsub=$p3arr[id]'>Change Cost Centers</a></td>
														<td><a href='costcenter-allocation-rem.php?project=$p1arr[id]&subsub=$p3arr[id]'>Remove Cost Centers</a></td>
													</tr>
												";
						}
					}

				}
			}
			
			$project_listing .= TBL_BR;

		}
	}


	



	$enter = "
				<h3>Projects Add/Edit/Remove</h3>
				$err
				<table ".TMPL_tblDflts." width='65%'>
					<input type='hidden' name='key' value='confirm'>
					<tr>
						<th colspan='4'>Add</th>
					</tr>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='add'>
					<input type='hidden' name='project' value='1'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Project</td>
						<td><input type='text' size='30' name='project1_add'></td>
						<td></td>
						<td><input type='submit' value='Add'></td>
					</tr>
				</form>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='add'>
					<input type='hidden' name='project' value='2'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sub Section</th>
						<td><input type='text' size='30' name='project2_add'></td>
						<td>$project1_drop</td>
						<td><input type='submit' value='Add'></td>
					</tr>
				</form>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='add'>
					<input type='hidden' name='project' value='3'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sub Sub Section</th>
						<td><input type='text' size='30' name='project3_add'></td>
						<td>$project2_drop</td>
						<td><input type='submit' value='Add'></td>
					</tr>
				</form>
				</table>
				<table ".TMPL_tblDflts." width='80%'>
					".TBL_BR."
					$project_listing
					".TBL_BR."

				</table>
			";
	return $enter;

}






function add_project ($_POST)
{

	extract ($_POST);

	db_connect ();

	switch ($project){
		case "1":
			if(!isset($project1_add) OR (strlen($project1_add) < 1))
				return enter ("<li class='err'>Please Ensure Data To Add Is Correct</li>");
			$ins_sql = "INSERT INTO projects (project_name) VALUES ('$project1_add');";
			$run_ins = db_exec($ins_sql) or errDie("Unable to add project information.");
			break;
		case "2":
			if(!isset($project2_add) OR (strlen($project2_add) < 1))
				return enter ("<li class='err'>Please Ensure Data To Add Is Correct</li>");
			$ins_sql1 = "INSERT INTO sub_projects (sub_project_name,project_id) VALUES ('$project2_add','$project1')";
			$run_ins1 = db_exec($ins_sql1) or errDie("Unable to add project information.");
			break;
		case "3":
			if(!isset($project3_add) OR (strlen($project3_add) < 1))
				return enter ("<li class='err'>Please Ensure Data To Add Is Correct</li>");
			#get $project2's id
			$get_sql = "SELECT project_id FROM sub_projects WHERE id = '$project2' LIMIT 1";
			$run_sql = db_exec($get_sql) or errDie("Unable to get project information.");
			if(pg_numrows($run_sql) < 1){
				return enter ();
			}
			$arr = pg_fetch_array($run_sql);

			$ins_sql = "INSERT INTO sub_sub_projects (sub_sub_project_name,sub_project_id,project_id) VALUES ('$project3_add','$project2','$arr[project_id]')";
			$run_ins = db_exec($ins_sql) or errDie("Unable to add project information.");
			break;
		default:
			return enter ();
	}

	return enter ("<li class='err'>Group Added</li>");

}





function edit ($_POST)
{

	extract ($_POST);
	
	if(!isset($id) or (strlen($id) < 1)){
		return enter ("<li class='err'> Invalid Use Of Module. Invalid ID.</li>");
	}

	if(!isset($project) or (strlen($project) < 1)){
		return enter ("<li class='err'> Invalid Use Of Module. Invalid Project ID.</li>");
	}

	db_connect ();

	switch ($project){
		case "1":
			$conn_to_db = "projects";
			$conn_to_field = "project_name";
			break;
		case "2":
			$conn_to_db = "sub_projects";
			$conn_to_field = "sub_project_name";
			break;
		case "3":
			$conn_to_db = "sub_sub_projects";
			$conn_to_field = "sub_sub_project_name";
			break;
		default:
			return enter ("<li class='err'>Invalid Use Of Module. Invalid Project ID.</li>");
	}

	$get_info = "SELECT * FROM $conn_to_db WHERE id = '$id' LIMIT 1";
	$run_info = db_exec($get_info) or errDie("Unable to get project information.");
	if(pg_numrows($run_info) < 1){
		return "Invalid Use Of Module.";
	}

	$arr = pg_fetch_array($run_info);

	$display = "
					<h2>Change Value</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirmedit'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='project' value='$project'>
						<input type='hidden' name='conn_to_db' value='$conn_to_db'>
						<input type='hidden' name='conn_to_field' value='$conn_to_field'>
						<tr>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' name='value' value='$arr[$conn_to_field]'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Next'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}





# confirm new data
function confirmedit ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 10, "Invalid ID.");
	$v->isOk ($project, "num", 1, 10, "Invalid Project.");
	$v->isOk ($value, "string", 1, 255, "Invalid Project Value.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if(!isset($conn_to_db) OR (strlen($conn_to_db) < 1)){
		return "Invalid Use Of Module. Invalid Project Field 1.";
	}
	
	if(!isset($conn_to_field) OR (strlen($conn_to_db) < 1)){
		return "Invalid Use Of Module. Invalid Project Field 2.";
	}

	$display = "
					<h2>Change Value</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='writeedit'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='project' value='$project'>
						<input type='hidden' name='value' value='$value'>
						<input type='hidden' name='conn_to_db' value='$conn_to_db'>
						<input type='hidden' name='conn_to_field' value='$conn_to_field'>
						<tr>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$value</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Next'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}





# write new data
function writeedit ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 10, "Invalid ID.");
	$v->isOk ($project, "num", 1, 10, "Invalid Project.");
	$v->isOk ($value, "string", 1, 255, "Invalid Project Value.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect ();

	# write to db
	$sql = "UPDATE $conn_to_db SET $conn_to_field = '$value' WHERE id = '$id'";
	$run_sql = db_exec ($sql) or errDie ("Unable to add class to system.", SELF);
	if (pg_cmdtuples ($run_sql) < 1) {
		return "<li class='err'>Unable to update project information.";
	}

	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Project Data Updated</th>
					</tr>
					<tr class=datacell>
						<td>Project Information Has Been Successfully Updated.</td>
					</tr>
				</table>
			";
	return $write;

}






function remove ($_POST)
{

	extract ($_POST);

	if(!isset($id) or (strlen($id) < 1)){
		return enter ("<li class='err'> Invalid Use Of Module. Invalid ID.</li>");
	}

	if(!isset($project) or (strlen($project) < 1)){
		return enter ("<li class='err'> Invalid Use Of Module. Invalid Project ID.</li>");
	}

	db_connect ();

	switch ($project){
		case "1":
			$conn_to_db = "projects";
			$conn_to_field = "project_name";
			break;
		case "2":
			$conn_to_db = "sub_projects";
			$conn_to_field = "sub_project_name";
			break;
		case "3":
			$conn_to_db = "sub_sub_projects";
			$conn_to_field = "sub_sub_project_name";
			break;
		default:
			return enter ("<li class='err'>Invalid Use Of Module. Invalid Project ID.</li>");
	}

	$get_info = "SELECT * FROM $conn_to_db WHERE id = '$id' LIMIT 1";
	$run_info = db_exec($get_info) or errDie("Unable to get project information.");
	if(pg_numrows($run_info) < 1){
		return "Invalid Use Of Module.";
	}

	$arr = pg_fetch_array($run_info);

	$display = "
					<h2>Remove Entry</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='writeremove'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='project' value='$project'>
						<input type='hidden' name='conn_to_db' value='$conn_to_db'>
						<input type='hidden' name='conn_to_field' value='$conn_to_field'>
						<input type='hidden' name='value' value='$arr[$conn_to_field]'>
						<tr>
							<th>Remove Entry</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$arr[$conn_to_field]</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Next'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}






function writeremove ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 10, "Invalid ID.");
	$v->isOk ($project, "num", 1, 10, "Invalid Project.");
	$v->isOk ($value, "string", 1, 255, "Invalid Project Value.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect ();

	# write to db
	$sql = "DELETE FROM $conn_to_db WHERE id = '$id'";
	$run_sql = db_exec ($sql) or errDie ("Unable to add class to system.", SELF);
	if (pg_cmdtuples ($run_sql) < 1) {
		return "<li class='err'>Unable to update project information.";
	}

	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Project Data Removed</th>
					</tr>
					<tr class=datacell>
						<td>Project Information Has Been Successfully Removed.</td>
					</tr>
				</table>
			";
	return $write;

}


?>

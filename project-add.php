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
require_lib ("pgsql");

foreach ($_GET as $each => $own){
	$_POST[$each] = $own;	
}

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "add":
			$OUTPUT = add_project ($_POST);
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

	$get_lev1 = "SELECT * FROM projects WHERE id != '1' ORDER BY project_name";
	$run_lev1 = db_exec($get_lev1) or errDie("Unable to get project information.");
//	if(pg_numrows($run_lev1) < 1)
//		print "<li class='err'>No Project Groups Found. Please add one.</li>";

	$pro_drop = "<select name='project' onChange='javascript:document.form1.submit()'>";
	$pro_drop .= "<option value='0' disabled selected>Select Template</option>";
	while ($larr1 = pg_fetch_array($run_lev1)){
		if($larr1["id"] == $project1){
			$pro_drop .= "<option value='$larr1[id]' selected>$larr1[project_name]</option>";
		}else {
			$pro_drop .= "<option value='$larr1[id]'>$larr1[project_name]</option>";
		}
	}
	$pro_drop .= "</select>";


	$enter = "
				<h3>Add Project</h3>
				$err
				<table ".TMPL_tblDflts." width='65%'>
					<input type='hidden' name='key' value='confirm'>
					<tr>
						<th colspan='4'>Add</th>
					</tr>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='add'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Project</td>
						<td><input type='text' size='30' name='project_add'></td>
						<td><input type='text' size='8' name='project_code' value='Code' onClick=\"value=''\"></td>
						<td>$pro_drop</td>
						<td><input type='submit' value='Add'></td>
					</tr>
				</form>
				</table>
			";
	return $enter;

}






function add_project ($_POST)
{

	extract ($_POST);

	db_connect ();

	if(!isset($project_add) OR (strlen($project_add) < 1))
		return enter ("<li class='err'>Please Ensure Project Name Is Correct</li>");

	if(!isset($project))
		$project = '0';

	pglib_transaction("BEGIN") or errDie ("Unable to start database transaction.");

		#get the latest id
		$lastid = $project;//pglib_lastid("projects","id");

		$ins_sql = "INSERT INTO projects (project_name,code) VALUES ('$project_add','$project_code');";
		$run_ins = db_exec($ins_sql) or errDie("Unable to add project information.");

		$newid = pglib_lastid("projects","id");

		if($project > 0){

			#get the structure
			$get_sub = "SELECT * FROM sub_projects WHERE project_id = '$lastid'";

			$run_sub = db_exec($get_sub) or errDie ("Unable to get sub project information.");
			if (pg_numrows($run_sub) < 1){
				return enter ("<li class='err'>No Sub Project Data For Selected Template Project Found.</li>");
			}else {
				while ($subarr = pg_fetch_array ($run_sub)){
					#add this sub then get its info
					$add_sql = "INSERT INTO sub_projects (sub_project_name,project_id) VALUES ('$subarr[sub_project_name]','$newid')";
					$run_add = db_exec($add_sql) or errDie ("Unable to store sub project information.");
	
					#get info
					$lastsubid = pglib_lastid("sub_projects","id");
	
					#get subsub info
					$get_subsub = "SELECT * FROM sub_sub_projects WHERE sub_project_id = '$subarr[id]'";

					$run_subsub = db_exec($get_subsub) or errDie ("Unable to get sub sub information.");
					if (pg_numrows($run_subsub) < 1){
						return enter ("<li class='err'>No Sub Sub Project Data For Selected Template Project Found.</li>");
					}else {
						while ($subsubarr = pg_fetch_array ($run_subsub)){
							$add_subsub1 = "INSERT INTO sub_sub_projects (sub_sub_project_name,sub_project_id,project_id) VALUES ('$subsubarr[sub_sub_project_name]','$lastsubid','$newid')";
							$run_subsub1 = db_exec($add_subsub1) or errDie ("Unable to store sub sub information.");

							#get info
							$lastsubsubid = pglib_lastid("sub_sub_projects","id");

							#check if there is a  cost center linked to this subsub
							$get_cc = "SELECT * FROM costcenters_links WHERE project1 = '$lastid' AND project2 = '$subarr[id]' AND project3 = '$subsubarr[id]'";

							$run_cc = db_exec($get_cc) or errDie ("Unable to get cost center information.");
							if(pg_numrows($run_cc) > 0){
								while ($arr = pg_fetch_array ($run_cc)){
									#cost center found ... add to the new subsub
									$add_sql1 = "INSERT INTO costcenters_links (ccid,project1,project2,project3) VALUES ('$arr[ccid]','$newid','$lastsubid','$lastsubsubid')";
									$run_add1 = db_exec($add_sql1) or errDie ("Unable to store cost center information.");
								}
							}
						}
					}
				}
			}
		}
		

	pglib_transaction("COMMIT") or errDie ("Unable to commit database transaction.");


	return enter ("<li class='err'>Project Added</li><br>");

}


















?>

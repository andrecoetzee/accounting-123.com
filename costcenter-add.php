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
require("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			if(isset($_POST["done"])){
				$OUTPUT = confirm($_POST);
			}else {
				$OUTPUT = view ($_POST);
			}
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = view($_POST);
	}
} else {
        # Display default output
        $OUTPUT = view($_POST);
}

	$OUTPUT .= "
				<p>
				<table border='0' cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-add.php'>Add Cost Center</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='projects-edit.php'>Manage Project Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>
				";

# Get template
require("template.php");



# Default view
function view($_POST,$err="")
{

	extract ($_POST);

	db_connect ();
	
	if(!isset($project1))
		$project1 = "";

	if(!isset($old_project1))
		$old_project1 = "";
		
	if(!isset($project2))
		$project2 = "";
	
	if(!isset($old_project2))
		$old_project2 = "";
	
	#reset all lower levels if master changed
	if($old_project1 != $project1){
//		unset ($project2);
	//	unset ($project3);
		$project2 = "";
		$project3 = "";
	}

	if($old_project2 != $project2){
		unset ($project3);
	}

	#set the vars
	if(!isset($centercode))
		$centercode = "";
	if(!isset($centername))
		$centername = "";

	if(!isset($project1) OR (strlen($project1) < 1)){
		$project1 = "0";
		$sel1 = "selected";
	}else {
		$sel1 = "";
	}
	if(!isset($project2) OR (strlen($project2) < 1)){
		$project2 = "0";
		$sel2 = "selected";
	}else {
		$sel2 = "";
	}
	if(!isset($project3) OR (strlen($project3) < 1)){
		$project3 = "0";
		$sel3 = "selected";
	}else {
		$sel3 = "";
	}

	#get the 3 levels

	$get_lev1 = "SELECT * FROM projects WHERE project_name != 'No Project' ORDER BY project_name";
	$run_lev1 = db_exec($get_lev1) or errDie("Unable to get project information.");
	if(pg_numrows($run_lev1) < 1)
		return "<li class='err'>No Projects Found. Please add 1.</li>";

	$lev1_drop = "<select name='project1' onChange='javascript:document.form1.submit()'>";
	$lev1_drop .= "<option disabled value='' $sel1>Select A Project</option>";
	while ($larr1 = pg_fetch_array($run_lev1)){
		if($larr1["id"] == $project1){
			$lev1_drop .= "<option value='$larr1[id]' selected>$larr1[project_name]</option>";
		}else {
			$lev1_drop .= "<option value='$larr1[id]'>$larr1[project_name]</option>";
		}
	}
	$lev1_drop .= "</select>";



	$get_lev2 = "SELECT * FROM sub_projects WHERE project_id = '$project1' ORDER BY sub_project_name";
	$run_lev2 = db_exec($get_lev2) or errDie("Unable to get sub-project information.");
//	if(pg_numrows($run_lev2) < 1)
//		return "No Sub-Project Groups Found. Please add 1.";

	$lev2_drop = "<select name='project2' onChange='javascript:document.form1.submit()'>";
	$lev2_drop .= "<option disabled value='' $sel2>Select A Sub-Section</option>";
	while ($larr2 = pg_fetch_array($run_lev2)){
		if($larr2["id"] == $project2){
			$lev2_drop .= "<option value='$larr2[id]' selected>$larr2[sub_project_name]</option>";
		}else {
			$lev2_drop .= "<option value='$larr2[id]'>$larr2[sub_project_name]</option>";
		}
	}
	$lev2_drop .= "</select>";



	$get_lev3 = "SELECT * FROM sub_sub_projects WHERE sub_project_id = '$project2' ORDER BY sub_sub_project_name";
	$run_lev3 = db_exec($get_lev3) or errDie("Unable to get sub-sub-project information.");
//	if(pg_numrows($run_lev3) < 1)
//		return "No Sub-Sub-Project Groups Found. Please add 1.";

	$lev3_drop = "<select name='project3' onChange='javascript:document.form1.submit()'>";
	$lev3_drop .= "<option disabled value='' $sel3>Select A Sub-Sub-Section</option>";
	while ($larr3 = pg_fetch_array($run_lev3)){
		if($larr3["id"] == $project3){
			$lev3_drop .= "<option value='$larr3[id]' selected>$larr3[sub_sub_project_name]</option>";
		}else {
			$lev3_drop .= "<option value='$larr3[id]'>$larr3[sub_sub_project_name]</option>";
		}
	}
	$lev3_drop .= "</select>";



	//layout
	$view = "
				<h3>Add Cost Center</h3>
				$err
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form1'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='old_project1' value='$project1'>
					<input type='hidden' name='old_project2' value='$project2'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Code</td>
						<td><input type='text' size='20' name='centercode' value='$centercode'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Name</td>
						<td><input type='text' size='20' name='centername' value='$centername'></td>
					</tr>
					".TBL_BR."
					<tr>
						<th colspan='2'>Project</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>$lev1_drop</td>
					</tr>
					<tr>
						<th colspan='2'>Sub-Section</th>
					</th>
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>$lev2_drop</td>
					</tr>
					<tr>
						<th colspan='2'>Sub-Sub-Section</th>
					</th>
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>$lev3_drop</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
						<td valign='center'><input type='submit' name='done' value='Add &raquo'></td>
					</tr>
				</form>
				</table>";
	return $view;

}



# confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	if(!isset($project1))
		$project1 = "";
	if(!isset($project2))
		$project2 = "";
	if(!isset($project3))
		$project3 = "";
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");
	$v->isOk ($project1, "num", 1, 10, "Invalid Project Group.");
	$v->isOk ($project2, "num", 1, 10, "Invalid Sub-Section Group.");
	$v->isOk ($project3, "num", 1, 10, "Invalid Sub-Sub-Section Group.");
	
	
	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return view($_POST,$confirm);
	}

	# check stock code
	db_connect();

	$sql = "SELECT centercode FROM costcenters WHERE lower(centercode) = lower('$centercode') AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'>A Category with code : <b>$centercode</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Layout
	$confirm = "
					<h3>Add Cost Center</h3>
					<h4>Confirm entry</h4>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='centercode' value='$centercode'>
						<input type='hidden' name='centername' value='$centername'>
						<input type='hidden' name='old_project1' value='$project1'>
						<input type='hidden' name='old_project2' value='$project2'>
						<input type='hidden' name='project1' value='$project1'>
						<input type='hidden' name='project2' value='$project2'>
						<input type='hidden' name='project3' value='$project3'>
						<tr>
							<th width='40%'>Field</th>
							<th width='60%'>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Code</td>
							<td>$centercode</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Name</td>
							<td>$centername</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='left'><input type='submit' value='Confirm &raquo'></td>
						</tr>
					</form>
					</table>";
	return $confirm;

}



# write
function write($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");
	$v->isOk ($project1, "num", 1, 10, "Invalid Project Group.");
	$v->isOk ($project2, "num", 1, 10, "Invalid Sub-Section Group.");
	$v->isOk ($project3, "num", 1, 10, "Invalid Sub-Sub-Section Group.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "
						<p>
						<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	pglib_transaction("BEGIN") or errDie("Unable to start transaction.");

	// Insert cost centers
	db_connect();

	$sql = "INSERT INTO costcenters 
			(centercode, centername, div) 
			VALUES 
			('$centercode', '$centername', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert stock cost center to Cubit.",SELF);

	$id = pglib_lastid ("costcenters","ccid");

	#add the cost center id
	$sql2 = "INSERT INTO costcenters_links (ccid,project1,project2,project3) VALUES ('$id','$project1','$project2','$project3')";
	$run_sql2 = db_exec($sql2) or errDie("Unable to get cost center information.");

	#add this to the template project
	if($project1 != 1){
		$insert_sql = "INSERT INTO costcenters_links (ccid,project1,project2,project3) VALUES ('$id','1','1','1')";
		$run_insert = db_exec($insert_sql) or errDie("Unable to add cost center link to template data.");
	}


	pglib_transaction("COMMIT") or errDie("Unable to complete transaction.");



	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Cost Center added to database</th>
					</tr>
					<tr class='datacell'>
						<td>New Cost Center, $centername ($centercode) has been successfully added to Cubit.</td>
					</tr>
				</table>";
	return $write;

}


?>
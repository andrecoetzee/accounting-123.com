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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			if(isset($HTTP_POST_VARS["done"])){
				$OUTPUT = confirm($HTTP_POST_VARS);
			}else {
				$OUTPUT = edit($HTTP_POST_VARS);
			}
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			if (isset($HTTP_GET_VARS["ccid"])){
				$OUTPUT = edit($HTTP_GET_VARS);
			} else {
				# Display default output
				$OUTPUT = "<li class=err>Invalid use of module.</li>";
			}
	}
} else {
	if (isset($HTTP_GET_VARS["ccid"])){
		$OUTPUT = edit($HTTP_GET_VARS);
	} else {
		# Display default output
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# Get template
require("template.php");



# Default view
function edit($HTTP_GET_VARS)
{

	# Get vars
	extract ($HTTP_GET_VARS);

	# Query server
    db_connect();
	$sql = "SELECT * FROM costcenters WHERE ccid = '$ccid'";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>Invalid Cost Center.</li>";
	}
	$cc = pg_fetch_array ($ccRslt);

	#reset all lower levels if master changed
//	if($old_project1 != $project1){
//		unset ($project2);
//		unset ($project3);
//	}
//	if($old_project2 != $project2){
//		unset ($project3);
//	}
//
//	if(!isset($project1) OR (strlen($project1) < 1)){
//		$project1 = $cc["project1"];
//		$sel1 = "selected";
//	}else {
//		$sel1 = "";
//	}
//	if(!isset($project2) OR (strlen($project2) < 1)){
//		$project2 = $cc["project2"];
//		$sel2 = "selected";
//	}else {
//		$sel2 = "";
//	}
//	if(!isset($project3) OR (strlen($project3) < 1)){
//		$project3 = $cc["project3"];
//		$sel3 = "selected";
//	}else {
//		$sel3 = "";
//	}



	#get the 3 levels
//
//	$get_lev1 = "SELECT * FROM projects ORDER BY project_name";
//	$run_lev1 = db_exec($get_lev1) or errDie("Unable to get project information.");
//	if(pg_numrows($run_lev1) < 1)
//		return "No Project Groups Found. Please add 1.";
//
//	$lev1_drop = "<select name='project1' onChange='javascript:document.form1.submit()'>";
//	$lev1_drop .= "<option disabled value='0' $sel1>Select A Project</option>";
//	while ($larr1 = pg_fetch_array($run_lev1)){
//		if($larr1["id"] == $project1){
//			$lev1_drop .= "<option value='$larr1[id]' selected>$larr1[project_name]</option>";
//		}else {
//			$lev1_drop .= "<option value='$larr1[id]'>$larr1[project_name]</option>";
//		}
//	}
//	$lev1_drop .= "</select>";



//	$get_lev2 = "SELECT * FROM sub_projects WHERE project_id = '$project1' ORDER BY sub_project_name";
//	$run_lev2 = db_exec($get_lev2) or errDie("Unable to get sub-project information.");
////	if(pg_numrows($run_lev2) < 1)
////		return "No Sub-Project Groups Found. Please add 1.";
//
//	$lev2_drop = "<select name='project2' onChange='javascript:document.form1.submit()'>";
//	$lev2_drop .= "<option disabled value='0' $sel2>Select A Sub-Section</option>";
//	while ($larr2 = pg_fetch_array($run_lev2)){
//		if($larr2["id"] == $project2){
//			$lev2_drop .= "<option value='$larr2[id]' selected>$larr2[sub_project_name]</option>";
//		}else {
//			$lev2_drop .= "<option value='$larr2[id]'>$larr2[sub_project_name]</option>";
//		}
//	}
//	$lev2_drop .= "</select>";



//	$get_lev3 = "SELECT * FROM sub_sub_projects WHERE sub_project_id = '$project2' ORDER BY sub_sub_project_name";
//	$run_lev3 = db_exec($get_lev3) or errDie("Unable to get sub-sub-project information.");
////	if(pg_numrows($run_lev3) < 1)
////		return "No Sub-Sub-Project Groups Found. Please add 1.";
//
//	$lev3_drop = "<select name='project3' onChange='javascript:document.form1.submit()'>";
//	$lev3_drop .= "<option disabled value='0' $sel3>Select A Sub-Sub-Section</option>";
//	while ($larr3 = pg_fetch_array($run_lev3)){
//		if($larr3["id"] == $project3){
//			$lev3_drop .= "<option value='$larr3[id]' selected>$larr3[sub_sub_project_name]</option>";
//		}else {
//			$lev3_drop .= "<option value='$larr3[id]'>$larr3[sub_sub_project_name]</option>";
//		}
//	}
//	$lev3_drop .= "</select>";


	//layout
	$view = "
				<h3>Edit Cost Center</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form1'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='ccid' value='$ccid'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Code</td>
						<td><input type='text' size='20' name='centercode' value='$cc[centercode]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Name</td>
						<td><input type='text' size='20' name='centername' value='$cc[centername]'></td>
					</tr>
					".TBL_BR."
					<tr>
						<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
						<td valign='center'><input type='submit' name='done' value='Edit &raquo'></td>
					</tr>
				</form>
				</table>
				<P>
				<table ".TMPL_tblDflts." width='100'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $view;

}



# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ccid, "num", 1, 20, "Invalid Cost center code.");
	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");
//	$v->isOk ($project1, "num", 1, 10, "Invalid Project Group.");
//	$v->isOk ($project2, "num", 1, 10, "Invalid Sub-Section Group.");
//	$v->isOk ($project3, "num", 1, 10, "Invalid Sub-Sub-Section Group.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm."
					<p>
					<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
					<P>
					<table ".TMPL_tblDflts." width='100'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='costcenter-view.php'>View Cost Centers</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</form>
					</table>";
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM costcenters WHERE ccid = '$ccid'";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>Invalid Cost Center.</li>";
	}
	$cc = pg_fetch_array ($ccRslt);

	# check stock code
	db_connect();
	$sql = "SELECT centercode FROM costcenters WHERE lower(centercode) = lower('$centercode') AND ccid != '$ccid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Category with code : <b>$catcod</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Layout
	$confirm = "
					<h3>Edit Cost Center</h3>
					<h4>Confirm entry</h4>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='ccid' value='$ccid'>
						<input type='hidden' name='centercode' value='$centercode'>
						<input type='hidden' name='centername' value='$centername'>
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
					</table>
					<p>
					<table ".TMPL_tblDflts." width='100'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='costcenter-view.php'>View Cost Centers</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $confirm;

}



# write
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ccid, "num", 1, 20, "Invalid Cost center code.");
	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");
//	$v->isOk ($project1, "num", 1, 10, "Invalid Project Group.");
//	$v->isOk ($project2, "num", 1, 10, "Invalid Sub-Section Group.");
//	$v->isOk ($project3, "num", 1, 10, "Invalid Sub-Sub-Section Group.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]<li>";
		}
		$confirm .= "
				<p>
				<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
				<P>
				<table ".TMPL_tblDflts." width='100'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</form>
				</table>";

		return $confirm;
	}

	// Insert cost centers
	db_connect();
	$sql = "UPDATE costcenters 
			SET centercode = '$centercode', centername = '$centername' 
			WHERE ccid = '$ccid'";
	$rslt = db_exec($sql) or errDie("Unable to update stock cost center to Cubit.",SELF);

	$write = "
					<table ".TMPL_tblDflts." width='50%'>
						<tr>
							<th>Cost Center edited</th>
						</tr>
						<tr class='datacell'>
							<td>Cost Center, $centername ($centercode) has been successfully edited.</td>
						</tr>
					</table>
					<p>
					<table border='0' cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='costcenter-view.php'>View Cost Centers</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='costcenter-add.php'>Add Cost Center</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $write;

}


?>
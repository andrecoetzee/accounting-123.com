<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "allocate":
			$OUTPUT = allocate_centers ($_POST);
			break;
		default:
			$OUTPUT = get_allocation ($_POST);
	}
}else {
	$OUTPUT = get_allocation ($_GET);
}

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql ("projects-edit.php","Manage Cost Center Projects"),
				ql ("costcenter-add.php","Add Cost Center"),
				ql ("costcenter-view.php","View Cost Centers")
			);

require ("template.php");





function get_allocation ($_POST,$err="")
{

	extract($_POST);

	if (!isset($project) OR (strlen($project) < 1)){
		return "<li class='err'>Invalid Use Of Module. (Invalid Project)</li>";
	}
	
	if (!isset($subsub) OR (strlen($subsub) < 1)){
		return "<li class='err'>Invalid Use Of Module. (Invalid Sub Sub Project)</li>";
	}

	$listing = "";

	db_connect ();

	#we only show entries not already allocated to this project

	#get template links
//	$get_costs = "SELECT * FROM costcenters_links WHERE project3 = '1'";
//	$run_costs = db_exec($get_costs) or errDie("Unable to get cost center information.");
//	if(pg_numrows($run_costs) > 0){
//		while ($carr = pg_fetch_array($run_costs)){
		#get list of cost centers
		$get_cc = "SELECT * FROM costcenters ORDER BY centername";
		$run_cc = db_exec($get_cc) or errDie ("Unable to get cost center information.");
		if(pg_numrows($run_cc) < 1){
			return "<li class='err'>No Cost Centers Found. Please Add One First.</li>";
		}else {
			while ($carr = pg_fetch_array ($run_cc)){
				#now we have all the available cost center links ... filter ...
				$exist_sql = "SELECT * FROM costcenters_links WHERE project1 = '$project' AND ccid = '$carr[ccid]'";
				$run_exist = db_exec($exist_sql) or errDie("Unable to check or cost center link.");
				if(pg_numrows($run_exist) < 1){
					$get_ccinfo = "SELECT centercode,centername FROM costcenters WHERE ccid = '$carr[ccid]' LIMIT 1";
					$run_ccinfo = db_exec($get_ccinfo) or errDie("Unable to get available cost centers");
					if(pg_numrows($run_ccinfo) < 1){
						$ccode = "";
						$cname = "";
					}else {
						$ccarr = pg_fetch_array($run_ccinfo);
						$ccode = $ccarr['centercode'];
						$cname = $ccarr['centername'];
					}

					$listing .= "
									<tr class='".bg_class()."'>
										<td>$ccode</td>
										<td>$cname</td>
										<td><input type='checkbox' name='adds[]' value='$carr[ccid]'></td>
									</tr>
								";
				}
			}
		}
//		}
//	}else {
	//	return "No Allocated Cost Centers Found.";
//	}

	if(strlen($listing) < 1){
		$listing .= "
						<tr class='".bg_class()."'>
							<td colspan='3'>No Entries Found</td>
						</tr>
					";
	}

	$display = "
					<h3>Unallocated Cost Centers For This Project</h3>
					<table ".TMPL_tblDflts.">
						$err
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='allocate'>
						<input type='hidden' name='project' value='$project'>
						<input type='hidden' name='subsub' value='$subsub'>
						<tr>
							<th>Cost Center Code</th>
							<th>Cost Center Name</th>
							<th>Allocate Cost Center</th>
						</tr>
						$listing
						".TBL_BR."
						<tr>
							<td colspan='3' align='right'><input type='submit' value='Add Cost Center(s) To Project'></td>
						</tr>
					</form>
					<form action='projects-edit.php' method='POST'>
						<input type='hidden' name='key' value='showedit'>
						<input type='hidden' name='project' value='$project'>
						<tr>
							<td><input type='submit' value='Return'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}





function allocate_centers ($_POST)
{

	extract ($_POST);

	if(!isset($adds) OR !is_array($adds))
		return get_allocation($_POST,"<li class='err'>Please select at least one Cost Center to add.</li>");

	if (!isset($project) OR (strlen($project) < 1)){
		return "<li class='err'>Invalid Use Of Module. (Invalid Project)</li>";
	}
	
	if (!isset($subsub) OR (strlen($subsub) < 1)){
		return "<li class='err'>Invalid Use Of Module. (Invalid Sub Sub Project)</li>";
	}

	db_connect ();

	$get_pro2 = "SELECT sub_project_id FROM sub_sub_projects WHERE id = '$subsub' LIMIT 1";
	$run_pro2 = db_exec($get_pro2) or errDie("Unable to get sub project information.");
	if(pg_numrows($run_pro2) < 1){
		$project2 = "";
	}else {
		$parr = pg_fetch_array($run_pro2);
		$project2 = $parr['sub_project_id'];
	}

	foreach ($adds AS $each){
		$ins_sql = "INSERT INTO costcenters_links (ccid,project1,project2,project3) VALUES ('$each','$project','$project2','$subsub')";
		$run_ins = db_exec($ins_sql) or errDie("Unable to add cost center information.");
	}


//	$display = "
//					<table ".TMPL_tblDflts.">
//					
//					</table>
//				";
//	return $display;

	header("Location: costcenter-allocation-rem.php?project=$project&subsub=$subsub");
}



?>

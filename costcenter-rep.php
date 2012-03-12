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

require ("settings.php");
require ("core-settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "view":
			if(!isset($_POST["done"])){
				$OUTPUT = slct($_POST);
			}else {
				$OUTPUT = printCenter($_POST);
			}
			break;
		case "export":
			$OUTPUT = export_data ($_POST);
			break;
		default:
			$OUTPUT = slct($_POST);
			break;
	}
} else {
    # Display default output
    $OUTPUT = slct($_POST);
}

$OUTPUT .= "
	<br>
	<table border='0' cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='costcenter-view.php'>View Cost Center</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";

require ("template.php");



# Default view
function slct($_POST)
{

	extract ($_POST);

	if(!isset($prd))
		$prd = "";
	if(!isset($project1))
		$project1 = "";
	if(!isset($project2))
		$project2 = "";
		
	if(!isset($old_project1))
		$old_project1 = "";
	if(!isset($old_project2))
		$old_project2 = "";

	db_conn(YR_DB);

	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year.</li>";
	}

//	$Prds = "<select name='prd'>";
//	while($prd = pg_fetch_array($prdRslt)){
//		if($prd['prddb'] == PRD_DB){
//			$sel = "selected";
//		}else{
//			$sel= "";
//		}
//		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
//	}
//	$Prds .= "</select>";

//	print getfinyear ();
	
	$Prds = finMonList("prd", $prd);

	#reset all lower levels if master changed
	if($old_project1 != $project1){
//		unset ($project2);
		$project2 = "";
//		unset ($project3);
		$project3 = "";
	}
	if($old_project2 != $project2){
//		unset ($project3);
		$project3 = "";
	}

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


	db_connect ();

	$get_lev1 = "SELECT * FROM projects ORDER BY project_name";
	$run_lev1 = db_exec($get_lev1) or errDie("Unable to get project information.");
	if(pg_numrows($run_lev1) < 1)
		return "No Projects Found. Please add 1.";

	$lev1_drop = "<select name='project1' onChange='javascript:document.form1.submit()'>";
	$lev1_drop .= "<option disabled value='0' $sel1>Search For A Project</option>";
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
	$lev2_drop .= "<option disabled value='0' $sel2>Search For A Sub-Section</option>";
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
	$lev3_drop .= "<option disabled value='0' $sel3>Search For A Sub-Sub-Section</option>";
	while ($larr3 = pg_fetch_array($run_lev3)){
		if($larr3["id"] == $project3){
			$lev3_drop .= "<option value='$larr3[id]' selected>$larr3[sub_sub_project_name]</option>";
		}else {
			$lev3_drop .= "<option value='$larr3[id]'>$larr3[sub_sub_project_name]</option>";
		}
	}
	$lev3_drop .= "</select>";

	//layout
	$slct = "
		<br><br>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='view'>
			<input type='hidden' name='old_project1' value='$project1'>
			<input type='hidden' name='old_project2' value='$project2'>
			<tr>
				<th colspan='2'>Cost Ledger</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Period</td>
				<td>$Prds</td>
			</tr>
			".TBL_BR."
			<tr>
				<td class='err' colspan='2'>In The Following Section, Select Only The Items You Want To Include In The Report</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$lev1_drop</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$lev2_drop</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$lev3_drop</td>
			</tr>
			".TBL_BR."
			<tr>
				<td valign='bottom'><input type='submit' name='done' value='View'></td>
			</tr>
		</form>
		</table>
		<p>";
	return $slct;

}



# show stock
function printCenter ($_POST)
{

	# Get vars
	extract ($_POST);

	$search1 = "";

	if(isset($project1) AND (strlen($project1) > 0) AND ($project1 != 0))
		$search1 .= "AND project1 = '$project1' ";
	if(isset($project2) AND (strlen($project2) > 0) AND ($project2 != 0))
		$search1 .= "AND project2 = '$project2' ";
	if(isset($project3) AND (strlen($project3) > 0) AND ($project3 != 0))
		$search1 .= "AND project3 = '$project3'";

	if(!isset ($preject1))
		$project1 = "";
	if(!isset ($preject2))
		$project2 = "";


	# Set up table to display in
	$printCenter = "
		<h3>Cost Ledger</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='old_project1' value='$project1'>
			<input type='hidden' name='old_project2' value='$project2'>
			<tr>
				<th>Cost Center Code</th>
				<th>Cost Center Name</th>
				<th>Project</th>
				<th>Sub-Section</th>
				<th>Sub-Sub-Section</th>
				<th>Total Income</th>
				<th>Total Expense</th>
				<th>Total Profit / Loss</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM costcenters_links WHERE id > '0' $search1 ORDER BY id ASC";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>No Cost Centers Were Found With Selected Criteria.</li>";
	}

	#create selection date range
	$finyear = getYearOfFinMon($prd);
	$search = "edate >= '$finyear-$prd-01' AND edate <= '$finyear-$prd-".date("d",mktime(0,0,0,$prd+1,-0,$finyear))."' AND ";

	$totinc = 0;
	$totexp = 0;
	$totprof = 0;
	while ($cc = pg_fetch_array ($ccRslt)) {
		db_conn($prd);
		$sql = "SELECT sum(amount) as inc FROM cctran WHERE $search (project = '$cc[project1]' OR project = '0') AND ccid = '$cc[ccid]' AND trantype = 'dt'";
		$ibalRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
		$ibal = pg_fetch_array($ibalRslt);
		$sql = "SELECT sum(amount) as exp FROM cctran WHERE $search (project = '$cc[project1]' OR project = '0') AND ccid = '$cc[ccid]' AND trantype = 'ct'";
		$ebalRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
		$ebal = pg_fetch_array($ebalRslt);

		db_connect ();

		#get the project information
		$get_pro1 = "SELECT project_name FROM projects WHERE id = '$cc[project1]'";
		$run_pro1 = db_exec($get_pro1) or errDie ("Unable to get project information.");
		if(pg_numrows($run_pro1) < 1){
			$project1_name = "";
		}else {
			$p1arr = pg_fetch_array($run_pro1);
			$project1_name = $p1arr['project_name'];
		}

		#get the project 2 information
		$get_pro2 = "SELECT sub_project_name FROM sub_projects WHERE id = '$cc[project2]'";
		$run_pro2 = db_exec($get_pro2) or errDie ("Unable to get project information.");
		if(pg_numrows($run_pro2) < 1){
			$project2_name = "";
		}else {
			$p2arr = pg_fetch_array($run_pro2);
			$project2_name = $p2arr['sub_project_name'];
		}

		#get the project 3 information
		$get_pro3 = "SELECT sub_sub_project_name FROM sub_sub_projects WHERE id = '$cc[project3]'";
		$run_pro3 = db_exec($get_pro3) or errDie ("Unable to get project information.");
		if(pg_numrows($run_pro3) < 1){
			$project3_name = "";
		}else {
			$p3arr = pg_fetch_array($run_pro3);
			$project3_name = $p3arr['sub_sub_project_name'];
		}

		# alternate bgcolor
		$cc['inc'] = sprint($ibal['inc']);
		$cc['exp'] = sprint($ebal['exp']);
		$prof = ($ibal['inc'] - $ebal['exp']);

		$totinc += $ibal['inc'];
		$totexp += $ebal['exp'];
		$totprof += $prof;

		#get costcenter info
		$get_cc2 = "SELECT * FROM costcenters WHERE ccid = '$cc[ccid]' LIMIT 1";
		$run_cc2 = db_exec($get_cc2) or errDie ("Unable to get cost center information.");
		if(pg_numrows($run_cc2) < 1){
			$centercode = "";
			$centername = "";
		}else {
			$cc2arr = pg_fetch_array ($run_cc2);
			$centercode = $cc2arr['centercode'];
			$centername = $cc2arr['centername'];
		}

		$printCenter .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$centercode</td>
						<td>$centername</td>
						<td>$project1_name</td>
						<td>$project2_name</td>
						<td>$project3_name</td>
						<td align='right'>".CUR." ".sprint($cc['inc'])."</td>
						<td align='right'>".CUR." ".sprint($cc['exp'])."</td>
						<td align='right'>".CUR." ".sprint($prof)."</td>
						<td><a href='#bottom' onclick='openwindowbg(\"costcenter-rep-det.php?ccid=$cc[ccid]&prd=$prd\")'>Detailed</a></td>
					</tr>";
		$i++;
	}

	$totinc = sprint($totinc);
	$totexp = sprint($totexp);
	$totprof = sprint($totprof);

	$printCenter .= "
							<tr bgcolor='".bgcolorg()."'>
								<td align='right' colspan='2'><b>Total</b></td>
								<td align='right'><b>".CUR." $totinc</b></td>
								<td align='right'><b>".CUR." $totexp</b></td>
								<td align='right'><b>".CUR." $totprof</b></td>
							</tr>
							<tr><td><br></td></tr>
							<tr>
								<td colspan='6' align='center'><input type='submit' value='Export To Spreadsheet'></td>
							</tr>
						</form>
						</table>
					";
	return $printCenter;

}



function export_data ($_POST)
{

	extract ($_POST);
	require_lib ("xls");

	$data = clean_html(printCenter($_POST));

	StreamXLS ("costcenter_report","$data");

}


?>
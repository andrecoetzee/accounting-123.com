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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "view":
			if(!isset($HTTP_POST_VARS["done"])){
				$OUTPUT = slct($HTTP_POST_VARS);
			}else {
				$OUTPUT = printCenter($HTTP_POST_VARS);
			}
			break;
		case "export":
			$OUTPUT = export_data ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct($HTTP_POST_VARS);
			break;
	}
} else {
    # Display default output
    $OUTPUT = slct($HTTP_POST_VARS);
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
function slct($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_conn(YR_DB);

	if(!isset($from_prd))
		$from_prd = "";
	if(!isset($to_prd))
		$to_prd = "";
	if(!isset($project1))
		$project1 = "";
	if(!isset($project2))
		$project2 = "";
	if(!isset($old_project1))
		$old_project1 = "";
	if(!isset($old_project2))
		$old_project2 = "";

	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql) or errDie("Unable to get period information");
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year";
	}

	$from_Prds = finMonList("from_prd", $from_prd);
	$to_Prds = finMonList("to_prd", $to_prd);

	#reset all lower levels if master changed
	if($old_project1 != $project1){
		unset ($project2);
		unset ($project3);
	}
	if($old_project2 != $project2){
		unset ($project3);
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
		return "No Project Groups Found. Please add 1.";

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
		<table ".TMPL_tblDflts." width='450'>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='view'>
			<input type='hidden' name='old_project1' value='$project1'>
			<input type='hidden' name='old_project2' value='$project2'>
			<tr>
				<th colspan='2'>Cost Ledger</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Period Range</td>
				<td>$from_Prds TO $to_Prds</td>
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
function printCenter ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	$search = "";

	if(isset($project1) AND (strlen($project1) > 0) AND ($project1 != 0))
		$search .= "AND project1 = '$project1' ";
	if(isset($project2) AND (strlen($project2) > 0) AND ($project2 != 0))
		$search .= "AND project2 = '$project2' ";
	if(isset($project3) AND (strlen($project3) > 0) AND ($project3 != 0))
		$search .= "AND project3 = '$project3'";

	if(!isset($project1))
		$project1 = "";
	if(!isset($project2))
		$project2 = "";

	# Set up table to display in
	$printCenter = "
		<h3>Cost Centers Period Review</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='from_prd' value='$from_prd'>
			<input type='hidden' name='to_prd' value='$to_prd'>
			<input type='hidden' name='old_project1' value='$project1'>
			<input type='hidden' name='old_project2' value='$project2'>
			<tr>
				<th>Cost Center Code</th>
				<th>Cost Center Name</th>
				<th>Total Income</th>
				<th>Total Expense</th>
				<th>Total Profit / Loss</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM costcenters_links WHERE id > '0' $search ORDER BY id ASC";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>There are no Cost Centers in Cubit.</li>";
	}

	#create selection date range
	$from_finyear = getYearOfFinMon($from_prd);
	$to_finyear = getYearOfFinMon($to_prd);
	$search = "edate >= '$from_finyear-$from_prd-01' AND edate <= '".date("Y-m-d",mktime(0,0,0,$to_prd+1,0,$to_finyear))."' AND ";
	
	$prds = array();
	if ($to_prd < $from_prd) {
		for ($i = $from_prd; $i <= 12; ++$i) {
			$prds[] = $i;
		}
		for ($i = 1; $i <= $to_prd; ++$i) {
			$prds[] = $i;
		}
	} else {
		for ($i = $from_prd; $i <= $to_prd; ++$i) {
			$prds[] = $i;
		}
	}

	$totinc = 0;
	$totexp = 0;
	$totprof = 0;
	while ($cc = pg_fetch_array ($ccRslt)) {
		$cc['inc'] = 0;
		$cc['exp'] = 0;
		foreach ($prds as $x) {
			db_conn($x);
			$sql = "SELECT sum(amount) as inc FROM cctran WHERE $search (project = '$cc[project1]' OR project = '0') AND ccid = '$cc[ccid]' AND trantype = 'dt'";
			$ibalRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
			$ibal = pg_fetch_array($ibalRslt);
			$sql = "SELECT sum(amount) as exp FROM cctran WHERE $search (project = '$cc[project1]' OR project = '0') AND ccid = '$cc[ccid]' AND trantype = 'ct'";
			$ebalRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
			$ebal = pg_fetch_array($ebalRslt);
	
			$cc['inc'] = $cc['inc'] + sprint($ibal['inc']);
			$cc['exp'] = $cc['exp'] + sprint($ebal['exp']);
			$prof = ($ibal['inc'] - $ebal['exp']);
	
			$totinc += $ibal['inc'];
			$totexp += $ebal['exp'];
			$totprof += $prof;
		}

		$cc['inc'] = sprint ($cc['inc']);

		db_connect ();

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
							<td align='right'>".CUR." ".sprint($cc['inc'])."</td>
							<td align='right'>".CUR." ".sprint($cc['exp'])."</td>
							<td align='right'>".CUR." ".sprint($prof)."</td>
							<td><a href='#bottom' onclick='openwindowbg(\"costcenter-rep-prd-det.php?ccid=$cc[ccid]&from_prd=$from_prd&to_prd=$to_prd\")'>Detailed</a></td>
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


function export_data ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);
	require_lib ("xls");

	$data = clean_html(printCenter($HTTP_POST_VARS));

	StreamXLS ("costcenter_report","$data");

}


?>
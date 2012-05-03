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
# admin-employee-rem.php :: Remove employees from db
##

require ("settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = view_grievance ($_POST["empnum"]);
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
}elseif(isset($_GET["empnum"])){
	$OUTPUT = view_grievance ($_GET["empnum"]);
} else {
	$OUTPUT = select_emp_grievance ();
}

$OUTPUT .= "<br>".mkQuickLinks(
	ql("admin-employee-add.php", "Add Nem Employee"),
	ql("admin-employee-view.php", "View Employees")
	);

require ("template.php");

##
# Functions
##

function select_emp_grievance ()
{

	global $_GET;

	# Connect to db
	db_connect ();

	# Get employees from db
	$employees = "";
	$i = 0;
	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "
			No employees in database.<p>
	        <table ".TMPL_tblDflts.">
	        	<tr>
	        		<th>Quick Links</th>
	        	</tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}

	$emp_drop = "<select name=empnum>";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$emp_drop .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames]</option>";
	}
	$emp_drop .= "</select>";

	if(isset($_GET["mode"])){
		$sendmode = "<input type='hidden' name='mode' value='$_GET[mode]'>";
	}else {
		$sendmode = "";
	}

	# Set up table & form
	$enterEmp = "
		<h2>View Grievances</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			$sendmode
			<tr>
				<th>Select Staff Member</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$emp_drop</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>";
	return $enterEmp;

}


function view_grievance ($empnum = "")
{

	global $_POST;

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($empnum, "string", 0, 10, "Invalid staff member selected.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return get_grievance($confirmCust);
	}

	$listing = "";

	db_connect ();

	if(isset($_POST["mode"]) AND ($_POST["mode"] == "closed")){
		$get_grievs = "SELECT * FROM grievances WHERE empnum = '$empnum' AND closed = 'yes'";
	}else {
		$get_grievs = "SELECT * FROM grievances WHERE empnum = '$empnum' AND closed = 'no'";
	}
	$run_grievs = db_exec($get_grievs);
	if(pg_numrows($run_grievs) < 1){
		$listing = "
			<tr class='".bg_class()."'>
				<td colspan='4'>No grievances recorded for the staff member.</td>
			</tr>";
	}else {
		$i = 0;
		while ($garr = pg_fetch_array($run_grievs)){
			if($garr['closed'] == "yes"){
				$listing .= "
					<tr class='".bg_class()."'>
						<td>$garr[first_rec_date]</td>
						<td>$garr[griev_details]</td>
						<td>$garr[company_date]</td>
					</tr>";
			}else {
				$listing .= "
					<tr class='".bg_class()."'>
						<td>$garr[first_rec_date]</td>
						<td>$garr[griev_details]</td>
						<td>$garr[company_date]</td>
						<td><a href='grievance-det.php?grievnum=$garr[grievnum]'>Details</a></td>
						<td><a href='grievance-edit.php?grievnum=$garr[grievnum]'>Edit</a></td>
					</tr>";
			}
			$i++;
		}
	}

	$confirm = "
		<h2>Grievance Listing</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<tr>
				<th>First Recorded Date</th>
				<th>Details</th>
				<th>Company Date</th>
				<th colspan='2'>Options</th>
			</tr>
			$listing
		</form>
		</table>";
	return $confirm;

}



function write_grievance ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($griev_details, "string", 0, 255, "Invalid Grievance Details.");
	$v->isOk ($input, "string", 0, 255, "Invalid grievance input.");
	$v->isOk ($close_griev, "string", 0, 10, "Invalid close grievance option.");

	$v->isOk ($first_rec_date, "date", 1, 10, "$first_rec_date Invalid first recorded date.");
	$v->isOk ($company_date, "date", 1, 10, "$company_date Invalid company date.");
	$v->isOk ($ccma_date, "date", 1, 10, "$ccma_date Invalid ccma date.");
	$v->isOk ($ccma_app_date, "date", 1, 10, "$ccma_app_date Invalid ccma appeal date.");
	$v->isOk ($court_date, "date", 1, 10, "$court_date Invalid court date.");
	$v->isOk ($court_app_date, "date", 1, 10, "$court_app_date Invalid court appeal date.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return get_grievance($confirmCust);
	}

	db_connect ();

	$write_sql = "
		INSERT INTO grievances (
			empnum, first_rec_date, griev_details, company_date, ccma_date, ccma_app_date, 
			court_date, court_app_date, div, closed
		) VALUES (
			'$empnum', '$first_rec_date', '$griev_details', '$company_date', '$ccma_date', '$ccma_app_date', 
			'$court_date', '$court_app_date', '".USER_DIV."', '$close_griev'
		)";
	$run_sql = db_exec($write_sql);

	#now get this id and write first input entry to db
	$get_id = "SELECT grievnum FROM grievances WHERE empnum = '$empnum' AND first_rec_date = '$first_rec_date' AND griev_details = '$griev_details' AND company_date = '$company_date' AND div = '".USER_DIV."' LIMIT 1";
	$run_id = db_exec($get_id);
	if(pg_numrows($run_id) != 1){
		#cant find entry .... nothing to do ...
	}else {
		$id_arr = pg_fetch_array($run_id);

		#add the input entry
		$update_sql = "INSERT INTO grievance_items (input,grievnum,div,date_added) VALUES ('$input','$id_arr[grievnum]','".USER_DIV."','now')";
		$run_update = db_exec($update_sql);
	}

	header ("Location: grievances-view.php");

}



?>
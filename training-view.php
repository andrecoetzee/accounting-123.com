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
			$OUTPUT = show_training();
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} elseif(isset($_GET["empnum"])){
	$OUTPUT = show_training ($_GET["empnum"]);
} else {
	$OUTPUT = get_employee ();
}

require ("template.php");

##
# Functions
##

function get_employee ()
{

	db_connect ();

	# Get employees from db
	$employees = "";
	$i = 0;
	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees in database.<p>
	        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        	<tr><th>Quick Links</th></tr>
	        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}

	$emp_drop = "<select name=empnum>";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$emp_drop .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames]</option>";
	}
	$emp_drop .= "</select>";


	$enterEmp = "
			<h2>Select Staff Member</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<input type=hidden name=key value='confirm'>
				<tr class='bg-odd'>
					<td>$emp_drop</td>
				</tr>
				<tr>
					<td><input type=submit value='Next'></td>
				</tr>
			</form>
			</table>
			<br>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
		";
	return $enterEmp;

}



function show_training ($empnum = "")
{

	global $_GET,$_POST;
	extract ($_GET);
	extract ($_POST);

	if(!isset($empnum)){
		return "Employee not found";
	}

	db_connect ();

	$get_trains = "SELECT * FROM training WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
	$run_trains = db_exec($get_trains);
	if(pg_numrows($run_trains) < 1){
		$listing = "<tr class='bg-odd'><td colspan='4'>No qualifications recorded for this staff member.</td></tr>";
	}else {
		$listing = "";
		$i = 0;
		while($tarr = pg_fetch_array($run_trains)){
			$listing .= "<tr class='".bg_class()."'><td>$tarr[course_name]</td><td>$tarr[commence_date]</td><td>$tarr[completed_date]</td><td><a href='employee-training-edit.php?trainnum=$tarr[trainnum]'>Edit</a></td></tr>";
			$i++;
		}
	}

	$listing = "
			<h2>View Staff Qualifications</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr>
					<th>Course Name</th>
					<th>Course Start Date</th>
					<th>Course End Date</th>
					<th colspan='2'>Options</th>
				</tr>
				$listing
			</table>
			<br>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
		";
	return $listing;


}

?>

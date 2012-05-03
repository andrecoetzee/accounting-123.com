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
		case "next":
			$OUTPUT = get_training ();
			break;
		case "confirm":
			$OUTPUT = confirm_grievance($_POST);
			break;
		case "write":
			$OUTPUT = write_grievance($_POST);
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
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
				<input type=hidden name=key value='next'>
				<tr>
					<td>$emp_drop</td>
				</tr>
				<tr>
					<td><input type=submit value='Next'></td>
				</tr>
			</form>
			</table>
		";
	return $enterEmp;

}

function get_training ($err = "")
{

	global $_POST;
	extract($_POST);

	# Connect to db
	db_connect ();

	#get training provider list
	$get_suppliers = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND length(bee_training) > 1 ORDER BY supname";
	$run_suppliers = db_exec($get_suppliers);
	if(pg_numrows($run_suppliers) < 1){
		$provider_list = "<select name=supid>";
		$provider_list .= "<option value='0'>Self</option>";
		$provider_list .= "</select>";
	}else {
		$provider_list = "<select name=supid>";
		$provider_list .= "<option value='0'>Self</option>";
		while ($tarr = pg_fetch_array($run_suppliers)){
			$provider_list .= "<option value='$tarr[supid]'>$tarr[supname]</option>";
		}
		$provider_list .= "</select>";
	}

	# Set up table & form
	$enterEmp = "
			<h2>Add new Qualification for Staff Member:</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='empnum' value='$empnum'>
				$err
				<tr><td><br></td></tr>
				<tr><th colspan='2'>Add Date</th></tr>
				<tr class='".bg_class()."'><td colspan='2'>".mkDateSelect("date")."</td></tr>
				<tr><th colspan='2'>Add Name of Course or Qualification</th></tr>
				<tr class='".bg_class()."'>
					<td colspan='2'><textarea name='course_name' cols='40' rows='4'></textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date Commenced: </td>
					<td>".mkDateSelect("commence")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date Completed: </td>
					<td>".mkDateSelect("completed")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Training Provider: </td>
					<td>$provider_list</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Name of Assessor: </td>
					<td><input type='text' name='assessor_name'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Cost of training: </td>
					<td>R <input type='text' size='8' name='training_cost'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Individual competent date: </td>
					<td>".mkDateSelect("competent")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<th colspan='2'>Other Details: </th>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='2'><textarea name='other_details' cols='40' rows='4'></textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr><td><input type='submit' value='Add Qualification to Staff Member'></td></tr>
			</form>
			</table>";

	return $enterEmp;

}


function get_training_err ($_POST,$err = "")
{

	//global $_POST;
	extract($_POST);

	if(!isset($date_year))
		$date_year = "";
	if(!isset($date_month))
		$date_month = "";
	if(!isset($date_day))
		$date_day = "";
	if(!isset($course_name))
		$course_name = "";
	if(!isset($commence_year))
		$commence_year = "";
	if(!isset($commence_month))
		$commence_month = "";
	if(!isset($commence_day))
		$commence_day = "";
	if(!isset($completed_year))
		$completed_year = "";
	if(!isset($completed_month))
		$completed_month = "";
	if(!isset($completed_day))
		$completed_day = "";
	if(!isset($supid))
		$supid = "";
	if(!isset($assessor_name))
		$assessor_name = "";
	if(!isset($training_cost))
		$training_cost = "";
	if(!isset($competent_year))
		$competent_year = "";
	if(!isset($competent_month))
		$competent_month = "";
	if(!isset($competent_day))
		$competent_day = "";
	if(!isset($other_details))
		$other_details = "";

	# Connect to db
	db_connect ();

	#get training provider list
	$get_suppliers = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND length(bee_training) > 1 ORDER BY supname";
	$run_suppliers = db_exec($get_suppliers);
	if(pg_numrows($run_suppliers) < 1){
		$provider_list = "<select name=supid>";
		$provider_list .= "<option value='0'>Self</option>";
		$provider_list .= "</select>";
	}else {
		$provider_list = "<select name=supid>";
		$provider_list .= "<option value='0'>Self</option>";
		while ($tarr = pg_fetch_array($run_suppliers)){
			if($supid == "$tarr[supid]"){
				$provider_list .= "<option value='$tarr[supid]' selected>$tarr[supname]</option>";
			}else {
				$provider_list .= "<option value='$tarr[supid]'>$tarr[supname]</option>";
			}
		}
		$provider_list .= "</select>";
	}

	# Set up table & form
	$enterEmp = "
			<h2>Add new Qualification for Staff Member:</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<input type=hidden name=key value='confirm'>
				<input type=hidden name=empnum value='$empnum'>
				$err
				<tr><td><br></td></tr>
				<tr><th colspan='2'>Add Date</th></tr>
				<tr class='".bg_class()."'>
					<td colspan='2'>
						<input type='text' size='4' name='date_year' maxlength='4' value='$date_year'>-
						<input type='text' size='2' name='date_month' maxlength='2'  value='$date_month'>-
						<input type='text' size='2' name='date_day' maxlength='2'  value='$date_day'>
					</td>
				</tr>
				<tr><th colspan='2'>Add Name of Course or Qualification</th></tr>
				<tr class='".bg_class()."'>
					<td colspan='2'><textarea name='course_name' cols='40' rows='4'>$course_name</textarea></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date Commenced: </td>
					<td>
						<input type='text' size='4' name='commence_year' maxlength='4' value='$commence_year'>-
						<input type='text' size='2' name='commence_month' maxlength='2' value='$commence_month'>-
						<input type='text' size='2' name='commence_day' maxlength='2' value='$commence_day'>
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date Completed: </td>
					<td>
						<input type='text' size='4' name='completed_year' maxlength='4' value='$completed_year'>-
						<input type='text' size='2' name='completed_month' maxlength='2' value='$completed_month'>-
						<input type='text' size='2' name='completed_day' maxlength='2' value='$completed_day'>
					</td>
				</tr>
				<tr class='".bg_class()."'><td>Select Training Provider: </td><td>$provider_list</td></tr>
				<tr class='".bg_class()."'>
					<td>Name of Assessor: </td>
					<td><input type='text' name='assessor_name' value='$assessor_name'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Cost of training: </td>
					<td><input type='text' name='training_cost' value='$training_cost'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Individual competent date: </td>
					<td>
						<input type='text' size='4' name='competent_year' maxlength='4' value='$competent_year'>-
						<input type='text' size='2' name='competent_month' maxlength='2' value='$competent_month'>-
						<input type='text' size='2' name='competent_day' maxlength='2' value='$competent_day'>
					</td>
				</tr>
				<tr class='".bg_class()."'><th colspan='2'>Other Details: </th></tr>
				<tr class='".bg_class()."'><td colspan='2'><textarea name='other_details' cols='40' rows='4'>$other_details</textarea></td></tr>
				<tr><td><br></td></tr>
				<tr><td><input type=submit value='Add Qualification to Staff Member'></td></tr>
			</form>
			</table>";

	return $enterEmp;

}


function confirm_grievance ($_POST)
{

	extract ($_POST);

	$date_date = $date_month."-".$date_day."-".$date_year;
	$commence_date = $commence_month."-".$commence_day."-".$commence_year;
	$completed_date = $completed_month."-".$completed_day."-".$completed_year;
	$competent_date = $competent_month."-".$competent_day."-".$competent_year;


	# validate input
	require_lib("validate");
	$v = new validate ();

	$v->isOk ($date_date, "date", 10, 10, "$date_date Invalid date entered.");
	$v->isOk ($course_name, "string", 0, 255, "Invalid Course Name.");
	$v->isOk ($commence_date, "date", 1, 10, "$commence_date Invalid Commence date.");
	$v->isOk ($completed_date, "date", 1, 10, "$completed_date Invalid ccma date.");
	$v->isOk ($supid, "string", 0, 255, "Invalid Training Provider.");
	$v->isOk ($assessor_name, "string", 0, 255, "Invalid Assessor Name.");
	$v->isOk ($training_cost, "string", 0, 255, "Invalid Training Cost.");
	$v->isOk ($competent_date, "date", 1, 10, "$competent_date Invalid date entered.");
	$v->isOk ($other_details, "string", 0, 255, "Invalid Course Name.");

	$fdate = explode("-", $date_date);
	if(count($fdate) < 3){
		$v->isOk ($date_date, "date", 1, 1, "Invalid date.");
	}else{
		if($fdate[1] > 29 && $fdate[0] == 2){
				$v->isOk ($date_date, "date", 1, 1, "Invalid date.");
		}elseif($fdate[1] > 31 || $fdate[0] > 12){
				$v->isOk ($date_date, "date", 1, 1, "Invalid date.");
		}
	}

	$commdate = explode("-", $commence_date);
	if(count($commdate) < 3){
		$v->isOk ($commence_date, "date", 1, 1, "Invalid commence date.");
	}else{
		if($commdate[1] > 29 && $commdate[0] == 2){
				$v->isOk ($commence_date, "date", 1, 1, "Invalid commence date.");
		}elseif($commdate[1] > 31 || $commdate[0] > 12){
				$v->isOk ($commence_date, "date", 1, 1, "Invalid commence date.");
		}
	}

	$compdate = explode("-", $completed_date);
	if(count($compdate) < 3){
		$v->isOk ($completed_date, "date", 1, 1, "Invalid completed date.");
	}else{
		if($compdate[1] > 29 && $compdate[0] == 2){
				$v->isOk ($completed_date, "date", 1, 1, "Invalid completed date.");
		}elseif($compdate[1] > 31 || $compdate[0] > 12){
				$v->isOk ($completed_date, "date", 1, 1, "Invalid completed date.");
		}
	}

	$compedate = explode("-", $competent_date);
	if(count($compedate) < 3){
		$v->isOk ($competent_date, "date", 1, 1, "Invalid competent date.");
	}else{
		if($compedate[1] > 29 && $compedate[0] == 2){
				$v->isOk ($competent_date, "date", 1, 1, "Invalid competent date.");
		}elseif($compedate[1] > 31 || $compedate[0] > 12){
				$v->isOk ($competent_date, "date", 1, 1, "Invalid competent date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>$e[msg]</li>";
		}

		return get_training_err($_POST,$confirmCust);
	}


	$confirm = "
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<input type=hidden name=key value='write'>
				<input type=hidden name=empnum value='$empnum'>

				<input type=hidden name=date_date value='$date_date'>
				<input type=hidden name=date_year value='$date_year'>
				<input type=hidden name=date_month value='$date_month'>
				<input type=hidden name=date_day value='$date_day'>

				<input type=hidden name=course_name value='$course_name'>

				<input type=hidden name=commence_date value='$commence_date'>
				<input type=hidden name=commence_year value='$commence_year'>
				<input type=hidden name=commence_month value='$commence_month'>
				<input type=hidden name=commence_day value='$commence_day'>

				<input type=hidden name=completed_date value='$completed_date'>
				<input type=hidden name=completed_year value='$completed_year'>
				<input type=hidden name=completed_month value='$completed_month'>
				<input type=hidden name=completed_day value='$completed_day'>

				<input type=hidden name=supid value='$supid'>
				<input type=hidden name=assessor_name value='$assessor_name'>
				<input type=hidden name=training_cost value='$training_cost'>

				<input type=hidden name=competent_date value='$competent_date'>
				<input type=hidden name=competent_year value='$competent_year'>
				<input type=hidden name=competent_month value='$competent_month'>
				<input type=hidden name=competent_day value='$competent_day'>

				<input type=hidden name=other_details value='$other_details'>

				<tr><th colspan='2'>Add Date</th></tr>
				<tr class='bg-odd'><td colspan='2'>$date_date</td></tr>
				<tr><th colspan='2'>Add Name of Course or Qualification</th></tr>
				<tr class='bg-odd'><td colspan='2'>".nl2br($course_name)."</td></tr>
				<tr class='bg-odd'><td>Date Commenced: </td><td>$commence_date</td></tr>
				<tr class='bg-even'><td>Date Completed: </td><td>$completed_date</td></tr>
				<tr class='bg-odd'><td>Training Provider: </td><td>$supid</td></tr>
				<tr class='bg-even'><td>Name of Assessor: </td><td>$assessor_name</td></tr>
				<tr class='bg-odd'><td>Cost of Training: </td><td>$training_cost</td></tr>
				<tr class='bg-even'><td>Individual Competent Date: </td><td>$competent_date</td></tr>
				<tr><td><br></td></tr>
				<tr><th colspan='2'>Other Details:</th></tr>
				<tr class='bg-odd'><td colspan='2'>".nl2br($other_details)."</td></tr>
				<tr><td><br></td></tr>
				<tr><td><input type=submit value='Save'></td></tr>
			</form>
			</table>
		";
	return $confirm;

}


function write_grievance ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();

	$v->isOk ($date_date, "date", 10, 10, "$date_date Invalid date entered.");
	$v->isOk ($course_name, "string", 0, 255, "Invalid Course Name.");
	$v->isOk ($commence_date, "date", 1, 10, "$commence_date Invalid Commence date.");
	$v->isOk ($completed_date, "date", 1, 10, "$completed_date Invalid ccma date.");
	$v->isOk ($supid, "string", 0, 255, "Invalid Training Provider.");
	$v->isOk ($assessor_name, "string", 0, 255, "Invalid Assessor Name.");
	$v->isOk ($training_cost, "string", 0, 255, "Invalid Training Cost.");
	$v->isOk ($competent_date, "date", 1, 10, "$competent_date Invalid date entered.");
	$v->isOk ($other_details, "string", 0, 255, "Invalid Other Details.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>$e[msg]</li>";
		}

		return get_training($confirmCust);
	}



	#handle empty
	if(strlen($date_date) != 2){
		$date_date1 = ",date_date";
		$date_date2 = ",'$date_date'";
	}else {
		$date_date1 = "";
		$date_date2 = "";
	}
	if(strlen($commence_date) != 2){
		$commence_date1 = ",commence_date";
		$commence_date2 = ",'$commence_date'";
	}else {
		$commence_date1 = "";
		$commence_date2 = "";
	}
	if(strlen($completed_date) != 2){
		$completed_date1 = ",completed_date";
		$completed_date2 = ",'$completed_date'";
	}else {
		$completed_date1 = "";
		$completed_date2 = "";
	}
	if(strlen($competent_date) != 2){
		$competent_date1 = ",competent_date";
		$competent_date2 = ",'$competent_date'";
	}else {
		$competent_date1 = "";
		$competent_date2 = "";
	}



	db_connect ();

	$write_sql = "INSERT INTO training (empnum $date_date1 ,course_name $commence_date1 $completed_date1 $competent_date1 ,supid,assessor_name,training_cost,other_details,div)
				 VALUES ('$empnum' $date_date2 ,'$course_name' $commence_date2 $completed_date2 $competent_date2 ,'$supid','$assessor_name','$training_cost','$other_details','".USER_DIV."')";
	$run_sql = db_exec($write_sql);

	header ("Location: training-view.php?empnum=$empnum");

}

?>

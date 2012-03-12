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
			$OUTPUT = confirm_grievance($_POST);
			break;
		case "write":
			$OUTPUT = write_grievance($_POST);
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} else {
	$OUTPUT = get_grievance ();
}

require ("template.php");




function get_grievance ($err = "")
{

	# Connect to db
	db_connect ();

	# Get employees from db
	$employees = "";
	$i = 0;
	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "
			No employees found.<p>
	        <table ".TMPL_tblDflts.">
	        	<tr>
	        		<th>Quick Links</th>
	        	</tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}

	$emp_drop = "<select name='empnum'>";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$emp_drop .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames]</option>";
	}
	$emp_drop .= "</select>";

	# Set up table & form
	$enterEmp = "
		<h2>Add Grievances</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			$err
			<tr>
				<th colspan='2'>Select Staff Member</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$emp_drop</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Date First Recorded</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>".mkDateSelect("first_rec")."</td>
			</tr>
			<tr>
				<th colspan='2'>Details of grievance</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><textarea name='griev_details' cols='40' rows'4'></textarea></td>
			</tr>
			<tr>
				<th colspan='2'>Status Of Grievance</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company: </td>
				<td>".mkDateSelect("company")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>CCMA: </td>
				<td>".mkDateSelect("ccma")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>CCMA Appeal: </td>
				<td>".mkDateSelect("ccma_app")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Court: </td>
				<td>".mkDateSelect("court")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Court Appeal: </td>
				<td>".mkDateSelect("court_app")."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Input:</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><textarea name='input' cols='40' rows='4'></textarea></td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Close this grievance:</td>
				<td>
					Yes <input type='radio' name='close_griev' value='yes'> 
					No <input type='radio' name='close_griev' value='no' checked='yes'>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>";
	return $enterEmp;

}



function confirm_grievance ($_POST)
{

	extract ($_POST);

//	$first_rec_date = $first_rec_month."-".$first_rec_day."-".$first_rec_year;
//	$company_date = $company_month."-".$company_day."-".$company_year;
//	$ccma_date = $ccma_month."-".$ccma_day."-".$ccma_year;
//	$ccma_app_date = $ccma_app_month."-".$ccma_app_day."-".$ccma_app_year;
//	$court_date = $court_month."-".$court_day."-".$court_year;
//	$court_app_date = $court_app_month."-".$court_app_day."-".$court_app_year;

	$first_rec_date = $first_rec_year."-".$first_rec_month."-".$first_rec_day;
	$company_date = $company_year."-".$company_month."-".$company_day;
	$ccma_date = $ccma_year."-".$ccma_month."-".$ccma_day;
	$ccma_app_date = $ccma_app_year."-".$ccma_app_month."-".$ccma_app_day;
	$court_date = $court_year."-".$court_month."-".$court_day;
	$court_app_date = $court_app_year."-".$court_app_month."-".$court_app_day;

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

	$fdate = explode("-", $first_rec_date);
	if(count($fdate) < 3){
		$v->isOk ($first_rec_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($fdate[1] > 29 && $fdate[0] == 2){
			$v->isOk ($first_rec_date, "date", 1, 1, "Invalid termination date.");
		}elseif($fdate[1] > 31 || $fdate[0] > 12){
			$v->isOk ($first_rec_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	$compdate = explode("-", $company_date);
	if(count($compdate) < 3){
		$v->isOk ($company_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($compdate[1] > 29 && $compdate[0] == 2){
			$v->isOk ($company_date, "date", 1, 1, "Invalid termination date.");
		}elseif($compdate[1] > 31 || $compdate[0] > 12){
			$v->isOk ($company_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	$ccdate = explode("-", $ccma_date);
	if(count($ccdate) < 3){
		$v->isOk ($ccma_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($ccdate[1] > 29 && $ccdate[0] == 2){
			$v->isOk ($ccma_date, "date", 1, 1, "Invalid termination date.");
		}elseif($ccdate[1] > 31 || $ccdate[0] > 12){
			$v->isOk ($ccma_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	$ccapdate = explode("-", $ccma_app_date);
	if(count($ccapdate) < 3){
		$v->isOk ($ccma_app_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($ccapdate[1] > 29 && $ccapdate[0] == 2){
			$v->isOk ($ccma_app_date, "date", 1, 1, "Invalid termination date.");
		}elseif($ccapdate[1] > 31 || $ccapdate[0] > 12){
			$v->isOk ($ccma_app_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	$cdate = explode("-", $court_date);
	if(count($cdate) < 3){
		$v->isOk ($court_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($cdate[1] > 29 && $cdate[0] == 2){
			$v->isOk ($court_date, "date", 1, 1, "Invalid termination date.");
		}elseif($cdate[1] > 31 || $cdate[0] > 12){
			$v->isOk ($court_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	$capdate = explode("-", $court_app_date);
	if(count($capdate) < 3){
		$v->isOk ($court_app_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($capdate[1] > 29 && $capdate[0] == 2){
			$v->isOk ($court_app_date, "date", 1, 1, "Invalid termination date.");
		}elseif($capdate[1] > 31 || $capdate[0] > 12){
			$v->isOk ($court_app_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return get_grievance($confirmCust);
	}


	$confirm = "
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='griev_details' value='$griev_details'>
			<input type='hidden' name='input' value='$input'>
			<input type='hidden' name='close_griev' value='$close_griev'>
			<input type='hidden' name='first_rec_date' value='$first_rec_date'>
			<input type='hidden' name='company_date' value='$company_date'>
			<input type='hidden' name='ccma_date' value='$ccma_date'>
			<input type='hidden' name='ccma_app_date' value='$ccma_app_date'>
			<input type='hidden' name='court_date' value='$court_date'>
			<input type='hidden' name='court_app_date' value='$court_app_date'>
			<input type='hidden' name='first_rec_month' value='$first_rec_month'>
			<input type='hidden' name='first_rec_day' value='$first_rec_day'>
			<input type='hidden' name='first_rec_year' value='$first_rec_year'>
			<input type='hidden' name='company_month' value='$company_month'>
			<input type='hidden' name='company_day' value='$company_day'>
			<input type='hidden' name='company_year' value='$company_year'>
			<input type='hidden' name='ccma_month' value='$ccma_month'>
			<input type='hidden' name='ccma_day' value='$ccma_day'>
			<input type='hidden' name='ccma_year' value='$ccma_year'>
			<input type='hidden' name='ccma_app_month' value='$ccma_app_month'>
			<input type='hidden' name='ccma_app_day' value='$ccma_app_day'>
			<input type='hidden' name='ccma_app_year' value='$ccma_app_year'>
			<input type='hidden' name='court_month' value='$court_month'>
			<input type='hidden' name='court_day' value='$court_day'>
			<input type='hidden' name='court_year' value='$court_year'>
			<input type='hidden' name='court_app_month' value='$court_app_month'>
			<input type='hidden' name='court_app_day' value='$court_app_day'>
			<input type='hidden' name='court_app_year' value='$court_app_year'>
			<tr>
				<th colspan='2'>Date First Recorded</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$first_rec_date</td>
			</tr>
			<tr>
				<th colspan='2'>Details of grievance</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>".nl2br($griev_details)."</td>
			</tr>
			<tr>
				<th colspan='2'>Status Of Grievance</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company: </td>
				<td>$company_date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>CCMA: </td>
				<td>$ccma_date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>CCMA Appeal: </td>
				<td>$ccma_app_date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Court: </td>
				<td>$court_date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Court Appeal: </td>
				<td>$court_app_date</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Input:</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>".nl2br($input)."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Close this grievance:</td>
				<td>$close_griev</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
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

	#handle empty
	if(strlen($first_rec_date) != 2){
		$first_rec_date1 = ",first_rec_date";
		$first_rec_date2 = ",'$first_rec_date'";
	}else {
		$first_rec_date1 = "";
		$first_rec_date2 = "";
	}
	if(strlen($company_date) != 2){
		$company_date1 = ",company_date";
		$company_date2 = ",'$company_date'";
	}else {
		$company_date1 = "";
		$company_date2 = "";
	}
	if(strlen($ccma_date) != 2){
		$ccma_date1 = ",ccma_date";
		$ccma_date2 = ",'$ccma_date'";
	}else {
		$ccma_date1 = "";
		$ccma_date2 = "";
	}
	if(strlen($ccma_app_date) != 2){
		$ccma_app_date1 = ",ccma_app_date";
		$ccma_app_date2 = ",'$ccma_app_date'";
	}else {
		$ccma_app_date1 = "";
		$ccma_app_date2 = "";
	}
	if(strlen($court_date) != 2){
		$court_date1 = ",court_date";
		$court_date2 = ",'$court_date'";
	}else {
		$court_date1 = "";
		$court_date2 = "";
	}
	if(strlen($court_app_date) != 2){
		$court_app_date1 = ",court_app_date";
		$court_app_date2 = ",'$court_app_date'";
	}else {
		$court_app_date1 = "";
		$court_app_date2 = "";
	}

	db_connect ();

	$write_sql = "
		INSERT INTO grievances (
			empnum $first_rec_date1 , griev_details $company_date1 $ccma_date1 $ccma_app_date1 $court_date1 
			$court_app_date1 ,div, closed
		) VALUES (
			'$empnum' $first_rec_date2 ,'$griev_details' $company_date2 $ccma_date2 $ccma_app_date2 $court_date2 
			$court_app_date2 , '".USER_DIV."', '$close_griev'
		)";
	$run_sql = db_exec($write_sql);

	#now get this id and write first input entry to db
	$get_id = "SELECT grievnum FROM grievances WHERE empnum = '$empnum' AND griev_details = '$griev_details' AND div = '".USER_DIV."' LIMIT 1";
	$run_id = db_exec($get_id);
	if(pg_numrows($run_id) != 1){
		#cant find entry .... nothing to do ...
	}else {
		$id_arr = pg_fetch_array($run_id);
		if(isset($input) AND (strlen($input) > 0)){
			#add the input entry
			$update_sql = "INSERT INTO grievance_items (input,grievnum,div,date_added) VALUES ('$input','$id_arr[grievnum]','".USER_DIV."','now')";
			$run_update = db_exec($update_sql);
		}
	}
	header ("Location: grievances-view.php");

}


?>
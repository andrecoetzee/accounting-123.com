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
		case "confirm_rem":
			$OUTPUT = confirm_rem($_POST);
			break;
		case "remove":
			$OUTPUT = remove($_POST);
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} elseif(isset($_GET["empnum"])) {
	$OUTPUT = confirm_emp($_GET);
} else {
	$OUTPUT = "Invalid.";
}

require ("template.php");




##
# Functions
##

# confirm removal
function confirm_emp ($_GET,$err="")
{

	extract($_GET);

	$empnum += 0;

	db_connect ();

	# get employee info to edit
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid emp number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	if($myEmpl['resident']=="t") {$myEmpl['resident']="Yes";} else {$myEmpl['resident']="No";}
	if($myEmpl['sex']=="M") {$myEmpl['sex']="Male";} else {$myEmpl['sex']="Female";}

	# Set up table & form
	$confirmEmp = "
		<h3>Confirm Remove Employee</h3>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm_rem'>
			<input type='hidden' name='empnum' value='$myEmpl[empnum]'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$myEmpl[sname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$myEmpl[fnames]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sex</td>
							<td valign='center'>$myEmpl[sex]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Marital Status</td>
							<td valign='center'>$myEmpl[marital]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Resident</td>
							<td valign='center'>$myEmpl[resident]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hire Date</td>
							<td valign='center'>$myEmpl[hiredate]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$myEmpl[telno]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td valign='center'>$myEmpl[email]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Basic Salary</td>
							<td valign='center'>".CUR." $myEmpl[basic_sal]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pay Type</td>
							<td valign='center'>$myEmpl[paytype]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Name</td>
							<td valign='center'>$myEmpl[bankname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td valign='center'>$myEmpl[bankcode]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account Type</td>
							<td valign='center'>$myEmpl[bankacctype]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account No</td>
							<td valign='center'>$myEmpl[bankaccno]</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$myEmpl[res1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res3]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res4]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td valign='center'>$myEmpl[pos1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[pos2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Code</td>
							<td valign='center'>$myEmpl[pcode]</td>
						</tr>
						<tr>
							<th colspan='2'>Friend Not Living With Employee</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$myEmpl[contsname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$myEmpl[contfnames]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$myEmpl[contres1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[contres2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[contres3]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$myEmpl[conttelno]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reason for Leaving</td>
				<td><input type='text' size='20' name='reason'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>".mkDateSelect("date")."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Select one or more of the following</th>
			</tr>
			<tr>
				<th colspan='2'>Employee Initiated:</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='dissat_payben' value='yes'> Dissatisfaction with Pay or Benefits</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='dissat_jobcon' value='yes'> Dissatisfaction with Job Content</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='dissat_env' value='yes'> Dissatisfaction with Working Environment</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='emigration' value='yes'> Emigration</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='incom_supman' value='yes'> Incompatibility with Supervisor or Manager</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='incom_orgcul' value='yes'> Incompatibility with Organisational Culture</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='incom_collea' value='yes'> Incompatibility with Colleagues</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='lack_perdev' value='yes'> Lack of Personal Development Opportunities</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='lack_caradv' value='yes'> Lack of Career Advancement Oppertunities</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='lack_recogn' value='yes'> Lack of Recognition</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='lack_culsen' value='yes'> Lack of Cultural Sensitivity</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='self_empl' value='yes'> Self Employment</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='unsuit_locorg' value='yes'> Unsuitable Geographic Location of Organisation</td>
			</tr>
			<tr>
				<th colspan='2'>Employer Instigated:</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='redundantretrench' value='yes'> Redundancy or Retrenchment</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='dismissmisconduct' value='yes'> Dismissal or Misconduct</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='incapablepoorperc' value='yes'> Incapacity or Poor Performance</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='negosettle' value='yes'> Negotiated Settlement</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='desertion' value='yes'> Desertion</td>
			</tr>
			<tr>
				<th colspan='2'>Unavoidable Separations:</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='death' value='yes'> Death</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='retirement' value='yes'> Retirement</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='illhealth' value='yes'> Ill Health</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='pregnan' value='yes'> Pregnancy</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='famcircums' value='yes'> Family Circumstances</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='checkbox' name='intercomptrans' value='yes'> Inter Company or Group Transfer</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><textarea name='description' rows='5' cols='65'></textarea></td>
			</tr>
			<tr>
				<td><br></td>
				<td align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmEmp;

}




# confirm removal
function confirm_rem ($_POST)
{

	extract($_POST);

	require_lib("validate");
    $v = new validate ();

	$v->isOk ($reason, "string", 1, 250, "Invalid reason.");
	$v->isOk ($description, "string", 0, 5000, "Invalid description.");

	$date_year += 0;
	$date_month += 0;
	$date_day += 0;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($reason, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
        if ($v->isError ()) {
            $confirmCust = "";
            $errors = $v->getErrors();
            foreach ($errors as $e) {
                $confirmCust .= "<li class='err'>".$e["msg"]."</li>";
            }
		return confirm_emp($_POST,$confirmCust."<br>");
	}

	if (empty($description)) {
		$description = "No Description.";
	}

	$date = "$date_year-$date_month-$date_day";

	$empnum += 0;

	db_connect ();

	# get employee info to edit
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid emp number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	if($myEmpl['resident'] == "t") {$myEmpl['resident'] = "Yes";} else {$myEmpl['resident'] = "No";}
	if($myEmpl['sex'] == "M") {$myEmpl['sex'] = "Male";} else {$myEmpl['sex'] = "Female";}

	$list1 = "<tr><th colspan='2'>Employee Initiated:</th></tr>";
	$list2 = "<tr><th colspan='2'>Employer Instigated:</th></tr>";
	$list3 = "<tr><th colspan='2'>Unavoidable Separations:</th></tr>";

	if(isset($dissat_payben) AND ($dissat_payben == "yes")) {$list1 .= "<input type='hidden' name='dissat_payben' value='$dissat_payben'><tr class='".bg_class()."'><td colspan='2'>Dissatisfaction with Pay or Benefits</td></tr>";}
	if(isset($dissat_jobcon) AND ($dissat_jobcon == "yes")) {$list1 .= "<input type='hidden' name='dissat_jobcon' value='$dissat_jobcon'><tr class='".bg_class()."'><td colspan='2'>Dissatisfaction with Job Content</td></tr>";}
	if(isset($dissat_env) AND ($dissat_env == "yes")) {$list1 .= "<input type='hidden' name='dissat_env' value='$dissat_env'><tr class='".bg_class()."'><td colspan='2'>Dissatisfaction with Working Environment</td></tr>";}
	if(isset($emigration) AND ($emigration == "yes")) {$list1 .= "<input type='hidden' name='emigration' value='$emigration'><tr class='".bg_class()."'><td colspan='2'>Emigration</td></tr>";}
	if(isset($incom_supman) AND ($incom_supman == "yes")) {$list1 .= "<input type='hidden' name='incom_supman' value='$incom_supman'><tr class='".bg_class()."'><td colspan='2'>Incompatibility with Supervisor or Manager</td></tr>";}
	if(isset($incom_orgcul) AND ($incom_orgcul == "yes")) {$list1 .= "<input type='hidden' name='incom_orgcul' value='$incom_orgcul'><tr class='".bg_class()."'><td colspan='2'>Incompatibility with Organisational Culture</td></tr>";}
	if(isset($incom_collea) AND ($incom_collea == "yes")) {$list1 .= "<input type='hidden' name='incom_collea' value='$incom_collea'><tr class='".bg_class()."'><td colspan='2'>Incompatibility with Colleagues</td></tr>";}
	if(isset($lack_perdev) AND ($lack_perdev == "yes")) {$list1 .= "<input type='hidden' name='lack_perdev' value='$lack_perdev'><tr class='".bg_class()."'><td colspan='2'>Lack of Personal Development Opportunities</td></tr>";}
	if(isset($lack_caradv) AND ($lack_caradv == "yes")) {$list1 .= "<input type='hidden' name='lack_caradv' value='$lack_caradv'><tr class='".bg_class()."'><td colspan='2'>Lack of Career Advancement Oppertunities</td></tr>";}
	if(isset($lack_recogn) AND ($lack_recogn == "yes")) {$list1 .= "<input type='hidden' name='lack_recogn' value='$lack_recogn'><tr class='".bg_class()."'><td colspan='2'>Lack of Recognition</td></tr>";}
	if(isset($lack_culsen) AND ($lack_culsen == "yes")) {$list1 .= "<input type='hidden' name='lack_culsen' value='$lack_culsen'><tr class='".bg_class()."'><td colspan='2'>Lack of Cultural Sensitivity</td></tr>";}
	if(isset($self_empl) AND ($self_empl == "yes")) {$list1 .= "<input type='hidden' name='self_empl' value='$self_empl'><tr class='".bg_class()."'><td colspan='2'>Self Employment</td></tr>";}
	if(isset($unsuit_locorg) AND ($unsuit_locorg == "yes")) {$list1 .= "<input type='hidden' name='unsuit_locorg' value='$unsuit_locorg'><tr class='".bg_class()."'><td colspan='2'>Unsuitable Geographic Location of Organisation</td></tr>";}

	if(isset($redundantretrench) AND ($redundantretrench == "yes")) {$list2 .= "<input type='hidden' name='redundantretrench' value='$redundantretrench'><tr class='".bg_class()."'><td colspan='2'>Redundancy or Retrenchment</td></tr>";}
	if(isset($dismissmisconduct) AND ($dismissmisconduct == "yes")) {$list2 .= "<input type='hidden' name='dismissmisconduct' value='$dismissmisconduct'><tr class='".bg_class()."'><td colspan='2'>Dismissal or Misconduct</td></tr>";}
	if(isset($incapablepoorperc) AND ($incapablepoorperc == "yes")) {$list2 .= "<input type='hidden' name='incapablepoorperc' value='$incapablepoorperc'><tr class='".bg_class()."'><td colspan='2'>Incapacity or Poor Performance</td></tr>";}
	if(isset($negosettle) AND ($negosettle == "yes")) {$list2 .= "<input type='hidden' name='negosettle' value='$negosettle'><tr class='".bg_class()."'><td colspan='2'>Negotiated Settlement</td></tr>";}
	if(isset($desertion) AND ($desertion == "yes")) {$list2 .= "<input type='hidden' name='desertion' value='$desertion'><tr class='".bg_class()."'><td colspan='2'>Desertion</td></tr>";}

	if(isset($death) AND ($death == "yes")) {$list3 .= "<input type=hidden name='death' value='$death'><tr class='".bg_class()."'><td colspan='2'>Death</td></tr>";}
	if(isset($retirement) AND ($retirement == "yes")) {$list3 .= "<input type='hidden' name='retirement value='$retirement'><tr class='".bg_class()."'><td colspan='2'>Retirement</td></tr>";}
	if(isset($illhealth) AND ($illhealth == "yes")) {$list3 .= "<input type='hidden' name='illhealth' value='$illhealth'><tr class='".bg_class()."'><td colspan='2'>Ill Health</td></tr>";}
	if(isset($pregnan) AND ($pregnan == "yes")) {$list3 .= "<input type='hidden' name='pregnan' value='$pregnan'><tr class='".bg_class()."'><td colspan='2'>Pregnancy</td></tr>";}
	if(isset($famcircums) AND ($famcircums == "yes")) {$list3 .= "<input type='hidden' name='famcircums' value='$famcircums'><tr class='".bg_class()."'><td colspan='2'>Family Circumstances</td></tr>";}
	if(isset($intercomptrans) AND ($intercomptrans == "yes")) {$list3 .= "<input type='hidden' name='intercomptrans' value='$intercomptrans'><tr class='".bg_class()."'><td colspan='2'>Inter Company or Group Transfer</td></tr>";}

	# Set up table & form
	$confirmEmp = "
		<h3>Confirm Remove Employee</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='remove'>
			<input type='hidden' name='empnum' value='$myEmpl[empnum]'>
			<input type='hidden' name='reason' value='$reason'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='description' value='$description'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$myEmpl[sname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$myEmpl[fnames]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sex</td>
							<td valign='center'>$myEmpl[sex]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Marital Status</td>
							<td valign='center'>$myEmpl[marital]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Resident</td>
							<td valign='center'>$myEmpl[resident]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hire Date</td>
							<td valign='center'>$myEmpl[hiredate]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$myEmpl[telno]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td valign='center'>$myEmpl[email]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Basic Salary</td>
							<td valign='center'>".CUR." $myEmpl[basic_sal]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pay Type</td>
							<td valign='center'>$myEmpl[paytype]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Name</td>
							<td valign='center'>$myEmpl[bankname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td valign='center'>$myEmpl[bankcode]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account Type</td>
							<td valign='center'>$myEmpl[bankacctype]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account No</td>
							<td valign='center'>$myEmpl[bankaccno]</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$myEmpl[res1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res3]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[res4]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td valign='center'>$myEmpl[pos1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[pos2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Code</td>
							<td valign='center'>$myEmpl[pcode]</td>
						</tr>
						<tr>
							<th colspan='2'>Friend Not Living With Employee</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$myEmpl[contsname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$myEmpl[contfnames]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$myEmpl[contres1]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[contres2]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$myEmpl[contres3]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$myEmpl[conttelno]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<th colspan='2'>Details of leaving</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reason for Leaving</td>
				<td>$reason</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Select one or more of the following</th>
			</tr>
			$list1
			$list2
			$list3
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><pre>$description</pre></td>
			</tr>
			<tr>
				<td><br></td>
				<td align='right'><input type='submit' value='Remove from active Employees &raquo;'></td>
			</tr>
		</form>
		</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmEmp;

}




# Remove employee
function remove ($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate ();

	$v->isOk ($reason, "string", 1, 250, "Invalid reason.");
	$v->isOk ($date, "string", 6, 10, "Invalid date.");
	$v->isOk ($description, "string", 0, 5000, "Invalid description.");

	# display errors, if any
    if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "-".$e["msg"]."<br>";
		}
		$Errors = "<tr><td class='err' colspan='2'>$confirmCust</td></tr>
		<tr><td colspan='2'><br></td></tr>";
		return $Errors;
	}

	# remove employee
	db_connect ();

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid emp number.";
	}
	$myEmpl = pg_fetch_array ($empRslt);

	extract($myEmpl);

	#compile the list of items to insert
	$insertlist1 = "";
	$insertlist2 = "";
	if(isset($dissat_payben) AND ($dissat_payben == "yes")) {$insertlist1 .= "dissat_payben, "; $insertlist2 .= "'$dissat_payben', ";}
	if(isset($dissat_jobcon) AND ($dissat_jobcon == "yes")) {$insertlist1 .= "dissat_jobcon, "; $insertlist2 .= "'$dissat_jobcon', ";}
	if(isset($dissat_env) AND ($dissat_env == "yes")) {$insertlist1 .= "dissat_env, "; $insertlist2 .= "'$dissat_env', ";}
	if(isset($emigration) AND ($emigration == "yes")) {$insertlist1 .= "emigration, "; $insertlist2 .= "'$emigration', ";}
	if(isset($incom_supman) AND ($incom_supman == "yes")) {$insertlist1 .= "incom_supman, "; $insertlist2 .= "'$incom_supman', ";}
	if(isset($incom_orgcul) AND ($incom_orgcul == "yes")) {$insertlist1 .= "incom_orgcul, "; $insertlist2 .= "'$incom_orgcul', ";}
	if(isset($incom_collea) AND ($incom_collea == "yes")) {$insertlist1 .= "incom_collea, "; $insertlist2 .= "'$incom_collea', ";}
	if(isset($lack_perdev) AND ($lack_perdev == "yes")) {$insertlist1 .= "lack_perdev, "; $insertlist2 .= "'$lack_perdev', ";}
	if(isset($lack_caradv) AND ($lack_caradv == "yes")) {$insertlist1 .= "lack_caradv, "; $insertlist2 .= "'$lack_caradv', ";}
	if(isset($lack_recogn) AND ($lack_recogn == "yes")) {$insertlist1 .= "lack_recogn, "; $insertlist2 .= "'$lack_recogn', ";}
	if(isset($lack_culsen) AND ($lack_culsen == "yes")) {$insertlist1 .= "lack_culsen, "; $insertlist2 .= "'$lack_culsen', ";}
	if(isset($self_empl) AND ($self_empl == "yes")) {$insertlist1 .= "self_empl, "; $insertlist2 .= "'$self_empl', ";}
	if(isset($unsuit_locorg) AND ($unsuit_locorg == "yes")) {$insertlist1 .= "unsuit_locorg, "; $insertlist2 .= "'$unsuit_locorg', ";}

	if(isset($redundantretrench) AND ($redundantretrench == "yes")) {$insertlist1 .= "redundantretrench, "; $insertlist2 .= "'$redundantretrench', ";}
	if(isset($dismissmisconduct) AND ($dismissmisconduct == "yes")) {$insertlist1 .= "dismissmisconduct, "; $insertlist2 .= "'$dismissmisconduct', ";}
	if(isset($incapablepoorperc) AND ($incapablepoorperc == "yes")) {$insertlist1 .= "incapablepoorperc, "; $insertlist2 .= "'$incapablepoorperc', ";}
	if(isset($negosettle) AND ($negosettle == "yes")) {$insertlist1 .= "negosettle, "; $insertlist2 .= "'$negosettle', ";}
	if(isset($desertion) AND ($desertion == "yes")) {$insertlist1 .= "desertion, "; $insertlist2 .= "'$desertion', ";}

	if(isset($death) AND ($death == "yes"))	 {$insertlist1 .= "death, "; $insertlist2 .= "'$death', ";}
	if(isset($retirement) AND ($retirement == "yes")) {$insertlist1 .= "retirement, "; $insertlist2 .= "'$retirement', ";}
	if(isset($illhealth) AND ($illhealth == "yes")) {$insertlist1 .= "illhealth, "; $insertlist2 .= "'$illhealth', ";}
	if(isset($pregnan) AND ($pregnan == "yes")) {$insertlist1 .= "pregnan, "; $insertlist2 .= "'$pregnan', ";}
	if(isset($famcircums) AND ($famcircums == "yes")) {$insertlist1 .= "famcircums, "; $insertlist2 .= "'$famcircums', ";}
	if(isset($intercomptrans) AND ($intercomptrans == "yes")) {$insertlist1 .= "intercomptrans, "; $insertlist2 .= "'$intercomptrans', ";}

	//if ($resident=="Yes") {$resident="TRUE";} else {$resident="FALSE";}
	$Sl = "
		INSERT INTO lemployees (
			empnum, sname, fnames, sex, marital, resident, hiredate, telno, email, basic_sal, 
			saltyp, hpweek, novert, hovert, payprd, paytype, bankname, bankcode, bankacctype, 
			bankaccno, vaclea, siclea, stdlea, res1, res2, res3, res4, pos1, pos2, pcode, 
			contsname, contfnames, contres1, contres2, contres3, conttelno, div, idnum, 
			enum, leavedate, leavereason, $insertlist1 leavedescription
		) VALUES (
			'$empnum', '$sname', '$fnames', '$sex', '$marital', '$resident', '$hiredate', '$telno', '$email', '$basic_sal', 
			'$saltyp', '$hpweek', '$novert', '$hovert', '$payprd', '$paytype', '$bankname', '$bankcode', '$bankacctype', 
			'$bankaccno', '$vaclea', '$siclea', '$stdlea', '$res1', '$res2', '$res3', '$res4', '$pos1', '$pos2', '$pcode', 
			'$contsname', '$contfnames', '$contres1', '$contres2', '$contres3', '$conttelno', '".USER_DIV."', '$idnum', 
			'$enum', '$date', '$reason', $insertlist2 '$description'
		)";
	$Ry = db_exec ($Sl) or errDie ("Unable to add new employee.");

	$sql = "DELETE FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$remEmpRslt = db_exec ($sql) or errDie ("Unable to delete employee from database.");

	# Provide some info on status
	$remEmp = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee successfully deleted</th>
			</tr>
			<tr class='datacell'>
				<td>Employee, has been moved to past employees.</td>
			</tr>
		</table>
		<br>"
		.mkQuickLinks(
			ql("admin-employee-add.php", "Add Employee"),
			ql("admin-employee-view.php", "View Employees")
		);
	return $remEmp;

}



?>
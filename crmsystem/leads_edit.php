<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please lead us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.
require ("../settings.php");
require ("../libs/ext.lib.php");
require ("../groupware/gw-common.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = get_data ($_GET);
	}
} else {
	$OUTPUT = get_data ($_GET);
}

# display output
require ("../template.php");



# enter new data
function get_data ($_GET,$errs="")
{

	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($id,"num", 1,100, "Invalid num.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('crm');

	$user = USER_NAME;

	# write to db
	$Sql = "SELECT * FROM leads WHERE ((id='$id')and ((con='Yes' and by='$user' AND div = '".USER_DIV."') or(con='No' AND div = '".USER_DIV."')))";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "Lead not Found";}
	$Data = pg_fetch_array($Rslt);


	$date = $Data['date'];

	$mon = substr($date,5,2);

	if ($mon == 1){$td = 31;$M = 'January';}
	if ($mon == 2){$td = 28;$M = 'February';}
	if ($mon == 3){$td = 31;$M = 'March';}
	if ($mon == 4){$td = 30;$M = 'April';}
	if ($mon == 5){$td = 31;$M = 'May';}
	if ($mon == 6){$td = 30;$M = 'June';}
	if ($mon == 7){$td = 31;$M = 'July';}
	if ($mon == 8){$td = 31;$M = 'August';}
	if ($mon == 9){$td = 30;$M = 'September';}
	if ($mon == 10){$td = 31;$M = 'October';}
	if ($mon == 11){$td = 30;$M = 'November';}      //and substr(date,7,4)='$year'
	if ($mon == 12){$td = 31;$M = 'December';}


	$Day = substr($date,8,2);
	$Day = $Day + 0;
	$Year = substr($date,0,4);

	$Date = $Day." ".$M." "." ".$Year;



	$hadd = $Data['hadd'];
	$padd = $Data['padd'];

	if ( $Data["con"] == "No" ) {
		$Cons = "
			<select size='1' name='Con'>
				<option value='No' selected>No</option>
				<option value='Yes'>Yes</option>
			</select>";
	} else {
		$Cons = "
			<select size='1' name='Con'>
				<option value='No'>No</option>
				<option value='Yes' selected>Yes</option>
			</select>";
	}

	extract($Data);

	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);

	list($bf_year, $bf_month, $bf_day) = explode("-", $birthdate);
	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));

	$select_bfday = "<select name='bf_day'>";
	for ( $i = 1; $i <= 31; $i++ ) {
		if ( $bf_day == $i )
			$sel = "selected";
		else
			$sel = "";
		$select_bfday .= "<option $sel value='$i'>$i</option>";
	}
	$select_bfday .= "</select>";

	$select_bfmonth = "<select name='bf_month'>";
	for ( $i = 1; $i <= 12; $i++ ) {
		if ( $bf_month == $i )
			$sel = "selected";
		else
			$sel = "";
		$select_bfmonth .= "<option $sel value='$i'>".date("F", mktime(0, 0, 0, $i, 1, 2000))."</option>";
	}
	$select_bfmonth .= "</select>";

	$select_bfyear = "<select name='bf_year'>";
	for ( $i = 1971; $i <= 2027; $i++ ) {
		if ( $bf_year == $i )
			$sel = "selected";
		else
			$sel = "";
		$select_bfyear .= "<option $sel value='$i'>$i</option>";
	}
	$select_bfyear .= "</select>";

	$genders = array("Male", "Female");
	$select_gender = "<select name='gender'>";
	foreach ($genders as $val) {
		if ($val == $gender) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$select_gender .= "<option value='$val' $selected>$val</option>";
	}
	$select_gender .= "</select>";

	// Sales people
	db_conn("exten");

	$sql = "SELECT * FROM salespeople WHERE div='".USER_DIV."' ORDER BY salesp ASC";
	$rslt = db_exec($sql) or errDie("Unable to retrieve sales people from Cubit.");

	$salespn_out = "<select name='salespn'>";
	while ($salespn_data = pg_fetch_array($rslt)) {
		if ($salespid == $salespn_data["salespid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$salespn_out .= "<option value='$salespn_data[salespid]' $selected>$salespn_data[salesp]</option>";
	}
	$salespn_out .= "</select>";

	// Next Contact Date
	if (!empty($ncdate)) {
		$ncdate = explode("-", $ncdate);
	} else {
		$ncdate[0] = $ncdate[1] = $ncdate[2] = "";
	}

	// Create the teams dropdown
	$sql = "SELECT * FROM crm.teams ORDER BY name ASC";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$teams_sel = "<select name='team_id'>";
	$teams_sel .= "<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$teams_sel .= "<option value='$team_data[id]'>$team_data[name]</option>";
	}
	$teams_sel .= "</select>";


	$get_data = "
		<h3>Modify Lead</h3>
		<br>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' name='frm_con' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='id' value='$id'>
			$errs
			<tr>
				<th colspan='4'>Lead Information</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td width='120'>First Name</td>
				<td width='210'><input type='text' size='27' name='name' value='$name'></td>
				<td width='120'>Office Phone</td>
				<td width='210'><input type='text' size='27' name='tell_office' value='$tell_office'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Company/Last Name</td>
				<td><input type='text' size='27' name='surname' value='$surname'></td>
				<td>Mobile</td>
				<td><input type='text' size='27' name='cell' value='$cell'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Team Permissions</td>
				<td>$teams_sel</td>
				<td>Home Phone</td>
				<td><input type='text' size='27' name='tell' value='$tell'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Lead Source</td>
				<td>$select_source</td>
				<td>Other Phone</td>
				<td><input type='text' size='27' name='tell_other' value='$tell_other'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Title</td>
				<td><input type='text' size='27' name='title' value='$title'></td>
				<td>Fax</td>
				<td><input type='text' size='27' name='fax' value='$fax'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td><input type='text' size='27' name='department' value='$department'></td>
				<td>E-mail</td>
				<td><input type='text' size='27' name='email' value='$email'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Birthdate</td>
				<td>$select_bfday $select_bfmonth $select_bfyear</td>
				<td>Other E-mail</td>
				<td><input type='text' size='27' name='email_other' value='$email_other'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='2'>Account Name</td>
				<td rowspan='2'>
					<table>
						<tr>
							<td>
								<input type='text' readonly='yes' size='27' name='accountname' value='$accountname'>
								<input type='hidden' name='account_id' value='$account_id'>
								<input type='hidden' name='account_type' value='$account_type'>
							</td>
							<td align='center'>
								<input type='button' value='Customer' onClick='popupSized(\"../customers-view.php?action=contact_acc\", \"leadacc\", 700, 450, \"\");'><br>
								<input type='button' value='Supplier' onClick='popupSized(\"../supp-view.php?action=contact_acc\", \"leadacc\", 700, 300, \"\");'>
							</td>
						</tr>
					</table>
				</td>
				<td>Assistant</td>
				<td><input type='text' size='27' name='assistant' value='$assistant'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Assistant Phone</td>
				<td><input type='text' size='27' name='assistant_phone' value='$assistant_phone'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Religion</td>
				<td><input type='text' size='27' name='religion' value='$religion'></td>
				<td>Website</td>
				<td><input type='text' size='27' name='website' value='$website'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Race</td>
				<td><input type='text' size='27' name='race' value='$race'></td>
				<td>Next Contact Date</td>
				<td>".mkDateSelect("ncdate", $ncdate[0], $ncdate[1], $ncdate[2])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Gender</td>
				<td>$select_gender</td>
				<td>Sales Person</td>
				<td>$salespn_out</td>
				</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Physical Address</th>
				<th colspan='2'>Postal Address</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'><textarea name='hadd' rows='4' cols='35'>$hadd</textarea></td>
				<td colspan='2' align='center'><textarea name='padd' rows='4' cols='35'>$padd</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>City</td>
				<td><input type='text' size='27' name='padd_city' value='$padd_city'></td>
				<td>City</td>
				<td><input type='text' size='27' name='hadd_city' value='$hadd_city'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>State/Province</td>
				<td><input type='text' size='27' name='padd_state' value='$padd_state'></td>
				<td>State/Province</td>
				<td><input type='text' size='27' name='hadd_state' value='$hadd_state'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td><input type='text' size='27' name='padd_code' value='$padd_code'></td>
				<td>Postal Code</td>
				<td><input type='text' size='27' name='hadd_code' value='$hadd_code'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Country</td>
				<td><input type='text' size='27' name='padd_country' value='$padd_country'></td>
				<td>Country</td>
				<td><input type='text' size='27' name='hadd_country' value='$hadd_country'></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'><textarea name='description' rows='4' cols='35'>$description</textarea></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Private</td>
				<td align='center'>$Cons</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>State/Province</td>
				<td><input type='text' size='27' name='padd_state' value='$padd_state'></td>
				<td>State/Province</td>
				<td><input type='text' size='27' name='hadd_state' value='$hadd_state'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td><input type='text' size='27' name='padd_code' value='$padd_code'></td>
				<td>Postal Code</td>
				<td><input type='text' size='27' name='hadd_code' value='$hadd_code'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Country</td>
				<td><input type='text' size='27' name='padd_country' value='$padd_country'></td>
				<td>Country</td>
				<td><input type='text' size='27' name='hadd_country' value='$hadd_country'></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'><textarea name='description' rows='4' cols='35'>$description</textarea></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Private</td>
				<td align='center'>$Cons</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='leads_list.php'>List leads</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $get_data;

}



# confirm new data
function con_data ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk($id, "num", 1, 9, "ID Field (hidden)");
	$v->isOk($surname, "string", 1, 100, "Last name");
	$v->isOk($name, "string", 0, 100, "First name");
	$v->isOk($accountname, "string", 0, 100, "Account");
	$v->isOk($account_id, "num", 0, 9, "Account ID (hidden)");
	$v->isOk($account_type, "string", 0, 100, "Account type (hidden)");
	$v->isOk($lead_source, "string", 0, 100, "Lead Source");
	$v->isOk($title, "string", 0, 100, "Title");
	$v->isOk($department, "string", 0, 100, "Department");
	$v->isOk($tell, "string", 0, 100, "Home Phone");
	$v->isOk($cell, "string", 0, 100, "Mobile Phone");
	$v->isOk($fax, "string", 0, 100, "Fax");
	$v->isOk($tell_office, "string", 0, 100, "Office Phone");
	$v->isOk($tell_other, "string", 0, 100, "Other Phone");
	$v->isOk($email, "string", 0, 100, "Email");
	$v->isOk($email_other, "string", 0, 100, "Other Email");
	$v->isOk($assistant, "string", 0, 100, "Assistant");
	$v->isOk($assistant_phone, "string", 0, 100, "Assistant Phone");
	$v->isOk($padd, "string", 0, 250, "Physical Address");
	$v->isOk($padd_city, "string", 0, 100, "Physical Address: City");
	$v->isOk($padd_state, "string", 0, 100, "Physical Address: State/Province");
	$v->isOk($padd_code, "string", 0, 100, "Physical Address: Postal Code");
	$v->isOk($padd_country, "string", 0, 100, "Physical Address: Country");
	$v->isOk($hadd, "string", 0, 250, "Postal Address");
	$v->isOk($hadd_city, "string", 0, 100, "Postal Address: City");
	$v->isOk($hadd_state, "string", 0, 100, "Postal Address: State/Province");
	$v->isOk($hadd_code, "string", 0, 100, "Postal Address: Postal Code");
	$v->isOk($hadd_country, "string", 0, 100, "Postal Address: Country");
	$v->isOk($description, "string", 0, 100, "Description");
	$v->isOk($website, "string", 0, 255, "Website");
	$v->isOk($religion, "string", 0, 100, "Religion");
	$v->isOk($race, "string", 0, 100, "Race");
	$v->isOk($gender, "string", 0, 6, "Gender");
	$v->isOk($Con,"string",2 ,3, "Invalid private.");
	$v->isOk($salespn, "num", 1, 9, "Sales person.");
	$v->isOk($team_id, "num", 1, 9, "Team");

	if (!empty($ncdate_day) || !empty($ncdate_month) || !empty($ncdate_year)) {
		$v->isOk($ncdate_day, "num", 1, 2, "Next contact date (Day)");
		$v->isOk($ncdate_month, "num", 1, 2, "Next contact date (Month)");
		$v->isOk($ncdate_year, "num", 4, 4, "Next contact date (Year)");
		$ncdate = "$ncdate_day-$ncdate_month-$ncdate_year";
	} else {
		$ncdate = "";
	}

	$birthdate = "$bf_year-$bf_month-$bf_day";
	if ( $v->isOk($birthdate, "string", 1, 100, "Birthdate") ) {
		if ( ! checkdate($bf_month, $bf_day, $bf_year) ) {
			$v->addError("_OTHER", "Invalid birthdate. No such date exists.");
		}
	}

	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			if ( $e["value"] == "_OTHER" )
			$err .= "<li class='err'>$e[msg]</li>";
			else
			$err .= "<li class=err>Invalid characters: $e[msg]</li>";
		}
		return get_data($_POST,$err);
	}

	db_conn("exten");

	$sql = "SELECT salesp FROM salespeople WHERE salespid='$salespn'";
	$rslt = db_exec($sql) or errDie("Unable to retieve sales person from Cubit.");
	$salespn_out = pg_fetch_result($rslt, 0);

	// Retrieve team name
	$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
	$team_name = pg_fetch_result($team_rslt, 0);

	$con_data = "
		<h3>Confirm lead details</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key'      value='write'>
			<input type='hidden' name=id value='$id'>
			<input type='hidden' name='surname' value='$surname'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='account_id' value='$account_id'>
			<input type='hidden' name='account_type' value='$account_type'>
			<input type='hidden' name='lead_source' value='$lead_source'>
			<input type='hidden' name='title' value='$title'>
			<input type='hidden' name='department' value='$department'>
			<input type='hidden' name='bf_day' value='$bf_day'>
			<input type='hidden' name='bf_month' value='$bf_month'>
			<input type='hidden' name='bf_year' value='$bf_year'>
			<input type='hidden' name='tell' value='$tell'>
			<input type='hidden' name='cell' value='$cell'>
			<input type='hidden' name='fax' value='$fax'>
			<input type='hidden' name='tell_office' value='$tell_office'>
			<input type='hidden' name='tell_other' value='$tell_other'>
			<input type='hidden' name='email' value='$email'>
			<input type='hidden' name='email_other' value='$email_other'>
			<input type='hidden' name='assistant' value='$assistant'>
			<input type='hidden' name='assistant_phone' value='$assistant_phone'>
			<input type='hidden' name='padd' value='$padd'>
			<input type='hidden' name='padd_city' value='$padd_city'>
			<input type='hidden' name='padd_state' value='$padd_state'>
			<input type='hidden' name='padd_code' value='$padd_code'>
			<input type='hidden' name='padd_country' value='$padd_country'>
			<input type='hidden' name='hadd' value='$hadd'>
			<input type='hidden' name='hadd_city' value='$hadd_city'>
			<input type='hidden' name='hadd_state' value='$hadd_state'>
			<input type='hidden' name='hadd_code' value='$hadd_code'>
			<input type='hidden' name='hadd_country' value='$hadd_country'>
			<input type='hidden' name='description' value='$description'>
			<input type='hidden' name='website' value='$website'>
			<input type='hidden' name='religion' value='$religion'>
			<input type='hidden' name='race' value='$race'>
			<input type='hidden' name='gender' value='$gender'>
			<input type='hidden' name='Con' value='$Con'>
			<input type='hidden' name='salespn' value='$salespn'>
			<input type='hidden' name='ncdate_day' value='$ncdate_day'>
			<input type='hidden' name='ncdate_month' value='$ncdate_month'>
			<input type='hidden' name='ncdate_year' value='$ncdate_year'>
			<input type='hidden' name='team_id' value='$team_id' />
			<tr>
				<th colspan='4'>Lead Information</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td width='120'>First Name</td>
				<td width='210'>$name</td>
				<td width='120'>Office Phone</td>
				<td width='210'>$tell_office</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company/Last Name</td>
				<td>$surname</td>
				<td>Mobile</td>
				<td>$cell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>$accountname</td>
				<td>Home Phone</td>
				<td>$tell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Lead Source</td>
				<td>".crm_get_leadsrc($lead_source)."</td>
				<td>Other Phone</td>
				<td>$tell_other</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Title</td>
				<td>$title</td>
				<td>Fax</td>
				<td>$fax</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td>$department</td>
				<td>E-mail</td>
				<td>$email</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Birthdate</td>
				<td>$birthdate_description</td>
				<td>Other E-mail</td>
				<td>$email_other</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reports To</td>
				<td>$reports_to</td>
				<td>Assistant</td>
				<td>$assistant</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Team Permissions</td>
				<td>$team_name</td>
				<td>Assistant Phone</td>
				<td>$assistant_phone</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Religion</td>
				<td>$religion</td>
				<td>Website</td>
				<td>$website</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Race</td>
				<td>$race</td>
				<td>Next Contact Date</td>
				<td>$ncdate</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Gender</td>
				<td>$gender</td>
				<td>Sales Person</td>
				<td>$salespn_out</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Physical Address</th>
				<th colspan='2'>Postal Address</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='left' valign='top'><xmp>$hadd</xmp></td>
				<td colspan='2' align='left'><xmp>$padd</xmp></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>City</td>
				<td>$padd_city</td>
				<td>City</td>
				<td>$hadd_city</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>State/Province</td>
				<td>$padd_state</td>
				<td>State/Province</td>
				<td>$hadd_state</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td>$padd_code</td>
				<td>Postal Code</td>
				<td>$hadd_code</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Country</td>
				<td>$padd_country</td>
				<td>Country</td>
				<td>$hadd_country</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='left'><xmp>$description</xmp></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Private</td>
				<td align='center'>$Con</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='leads_list.php'>List leads</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $con_data;

}



# write new data
function write_data ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk($id, "num", 1, 9, "ID Field (hidden)");
	$v->isOk($surname, "string", 1, 100, "Last name");
	$v->isOk($name, "string", 0, 100, "First name");
	$v->isOk($account_id, "num", 0, 9, "Account ID (hidden)");
	$v->isOk($account_type, "string", 0, 100, "Account type (hidden)");
	$v->isOk($lead_source, "string", 0, 100, "Lead Source");
	$v->isOk($title, "string", 0, 100, "Title");
	$v->isOk($department, "string", 0, 100, "Department");
	$v->isOk($tell, "string", 0, 100, "Home Phone");
	$v->isOk($cell, "string", 0, 100, "Mobile Phone");
	$v->isOk($fax, "string", 0, 100, "Fax");
	$v->isOk($tell_office, "string", 0, 100, "Office Phone");
	$v->isOk($tell_other, "string", 0, 100, "Other Phone");
	$v->isOk($email, "string", 0, 100, "Email");
	$v->isOk($email_other, "string", 0, 100, "Other Email");
	$v->isOk($assistant, "string", 0, 100, "Assistant");
	$v->isOk($assistant_phone, "string", 0, 100, "Assistant Phone");
	$v->isOk($padd, "string", 0, 250, "Physical Address");
	$v->isOk($padd_city, "string", 0, 100, "Physical Address: City");
	$v->isOk($padd_state, "string", 0, 100, "Physical Address: State/Province");
	$v->isOk($padd_code, "string", 0, 100, "Physical Address: Postal Code");
	$v->isOk($padd_country, "string", 0, 100, "Physical Address: Country");
	$v->isOk($hadd, "string", 0, 250, "Postal Address");
	$v->isOk($hadd_city, "string", 0, 100, "Postal Address: City");
	$v->isOk($hadd_state, "string", 0, 100, "Postal Address: State/Province");
	$v->isOk($hadd_code, "string", 0, 100, "Postal Address: Postal Code");
	$v->isOk($hadd_country, "string", 0, 100, "Postal Address: Country");
	$v->isOk($description, "string", 0, 100, "Description");
	$v->isOk($website, "string", 0, 255, "Website");
	$v->isOk($religion, "string", 0, 100, "Religion");
	$v->isOk($race, "string", 0, 100, "Race");
	$v->isOk($gender, "string", 0, 6, "Gender");
	$v->isOk($Con,"string",2 ,3, "Invalid private.");
	$v->isOk($salespn, "num", 1, 9, "Sales person.");
	$v->isOk($team_id, "num", 1, 9, "Team");

	if (!empty($ncdate_day) || !empty($ncdate_month) || !empty($ncdate_year)) {
		$v->isOk($ncdate_day, "num", 1, 2, "Next contact date (Day)");
		$v->isOk($ncdate_month, "num", 1, 2, "Next contact date (Month)");
		$v->isOk($ncdate_year, "num", 4, 4, "Next contact date (Year)");
		$ncdate = ", ncdate = '$ncdate_year-$ncdate_month-$ncdate_day'";
	} else {
		$ncdate = "";
	}

	$birthdate = "$bf_year-$bf_month-$bf_day";
	if ( $v->isOk($birthdate, "string", 1, 100, "Birthdate") ) {
		if ( ! checkdate($bf_month, $bf_day, $bf_year) ) {
			$v->addError("_OTHER", "Invalid birthdate. No such date exists.");
		}
	}

	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));

	# display errors, if any
	if ($v->isError ()) {
		$err = "The following field value errors occured:<br>";

		$errors = $v->getErrors();

		foreach ($errors as $e) {
			if ( $e["value"] == "_OTHER" )
			$err .= "<li class='err'>$e[msg]</li>";
			else
			$err .= "<li class='err'>Invalid characters: $e[msg]</li>";
		}
		return get_data($_POST,$err);
	}





	db_conn('crm');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class='err'>Unable to edit lead(TB)</li>";
	}

	$Sl = "SELECT * FROM leads WHERE id='$id'";
	$Ry = db_exec($Sl) or errDie("Unable to get lead details.");

	if(pg_num_rows($Ry) < 1) {
		return "Invalid lead.";
	}

	$cdata = pg_fetch_array($Ry);

	if ( $account_type == "Customer" ) {
		db_conn("cubit");
		$sql = "SELECT surname FROM customers WHERE cusnum='$account_id'";
		$rslt = db_exec($sql) or errDie("Error reading account name (customers)");

		if ( pg_num_rows($rslt) > 0 ) {
			$accountname = pg_fetch_result($rslt, 0, 0);
		} else {
			$account_id = 0;
			$accountname = "";
			$account_type = "";
		}
	} else if ( $account_type == "Supplier" ) {
		db_conn("cubit");
		$sql = "SELECT supname FROM suppliers WHERE supid='$account_id'";
		$rslt = db_exec($sql) or errDie("Error reading account name (suppliers)");

		if ( pg_num_rows($rslt) > 0 ) {
			$accountname = pg_fetch_result($rslt, 0, 0);
		} else {
			$account_id = 0;
			$accountname = "";
			$account_type = "";
		}
	} else {
		$accountname = "";
	}

	# write to db
	db_conn("crm");

	$Sql = "
		UPDATE leads 
		SET surname='$surname', name='$name', accountname='$accountname', account_id='$account_id', 
			account_type='$account_type', lead_source='$lead_source', title='$title', department='$department', 
			birthdate='$birthdate', tell='$tell', cell='$cell', fax='$fax', tell_office='$tell_office', 
			tell_other='$tell_other', email='$email', email_other='$email_other', assistant='$assistant', 
			assistant_phone='$assistant_phone', padd='$padd', padd_city='$padd_city', padd_state='$padd_state', 
			padd_code='$padd_code', padd_country='$padd_country', hadd='$hadd', hadd_city='$hadd_city', 
			hadd_state='$hadd_state', hadd_code='$hadd_code', hadd_country='$hadd_country', description='$description', 
			website='$website', religion='$religion', race='$race', gender='$gender', con='$Con', salespid='$salespn', 
			team_id='$team_id' $ncdate
		WHERE id='$id'";

	// Add entry to today
	if (!empty($ncdate_year) && !empty($ncdate_month) && !empty($ncdate_day)) {
		$contact_date = "$ncdate_year-$ncdate_month-$ncdate_day";
		addTodayEntry("Leads", $id, $contact_date, "Contact $surname");
	}

	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Rslt);

	db_conn("cubit");

	if($cdata['supp_id'] != 0) {
		$Sl = "UPDATE suppliers SET supname='$surname',tel='$tell',fax='$fax',email='$email',supaddr='$padd \n $hadd' WHERE supid='$cdata[supp_id]'";
		$Ry = db_exec($Sl) or errDie("Unable to update supplier.");
	}

	if($cdata['cust_id'] != 0) {
		$Sl = "UPDATE customers SET surname='$surname',tel='$tell',fax='$fax',email='$email',paddr1='$padd',addr1='$hadd' WHERE cusnum='$cdata[cust_id]'";
		$Ry = db_exec($Sl) or errDie("Unable to update customers.");
	}

	if (!pglib_transaction("COMMIT")) {
		return "<li class='err'>Unable to edit lead. (TC)</li>";
	}

	$write_data = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Lead modified</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$surname has been modified.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='leads_list.php'>List leads</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write_data;

}


?>
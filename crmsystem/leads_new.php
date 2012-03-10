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

// remove all '
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = con_data ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_data ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_data ("");
	}
} else {
	$OUTPUT = get_data ("");
}

# display output
require ("../template.php");



# enter new data
function get_data ($err)
{

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	$fields["surname"] = "";
	$fields["name"] = "";
	$fields["accountname"] = "";
	$fields["account_id"] = 0;
	$fields["account_type"] = "";
	$fields["lead_source"] = 0;
	$fields["title"] = "";
	$fields["department"] = "";
	$fields["birthdate"] = date("Y-m-d");
	$fields["reports_to_id"] = 0;
	//$fields["assigned_to_id"] = "";
	$fields["tell"] = "";
	$fields["cell"] = "";
	$fields["fax"] = "";
	$fields["tell_office"] = "";
	$fields["tell_other"] = "";
	$fields["email"] = "";
	$fields["email_other"] = "";
	$fields["assistant"] = "";
	$fields["assistant_phone"] = "";
	$fields["padd"] = "";
	$fields["padd_city"] = "";
	$fields["padd_state"] = "";
	$fields["padd_code"] = "";
	$fields["padd_country" ] ="";
	$fields["hadd"] = "";
	$fields["hadd_city"] = "";
	$fields["hadd_state"] = "";
	$fields["hadd_code"] = "";
	$fields["hadd_country"] = "";
	$fields["description"] = "";
	$fields["website"] = "http://";
	$fields["religion"] = "";
	$fields["race"] = "";
	$fields["gender"] = "Male";
	$fields["ncdate_day"] = "";
	$fields["ncdate_month"] = "";
	$fields["ncdate_year"] = "";
	$fields["salespn"] = "";

	foreach ( $fields as $key => $value ) {
		if ( ! isset($$key) )
		$$key = $value;
	}

	list($bf_year, $bf_month, $bf_day) = explode("-", $birthdate);

// 	$select_bfday = "<select name=bf_day>";
// 	for ( $i = 1; $i <= 31; $i++ ) {
// 		if ( $bf_day == $i )
// 		$sel = "selected";
// 		else
// 		$sel = "";
//
// 		$select_bfday .= "<option $sel value='$i'>$i</option>";
// 	}
// 	$select_bfday .= "</select>";

// 	$select_bfmonth = "<select name=bf_month>";
// 	for ( $i = 1; $i <= 12; $i++ ) {
// 		if ( $bf_month == $i )
// 		$sel = "selected";
// 		else
// 		$sel = "";
//
// 		$select_bfmonth .= "<option $sel value='$i'>".date("F", mktime(0, 0, 0, $i, 1, 2000))."</option>";
// 	}
// 	$select_bfmonth .= "</select>";

// 	$select_bfyear = "<select name=bf_year>";
// 	for ( $i = 1971; $i <= 2027; $i++ ) {
// 		if ( $bf_year == $i )
// 		$sel = "selected";
// 		else
// 		$sel = "";
//
// 		$select_bfyear .= "<option $sel value='$i'>$i</option>";
// 	}
// 	$select_bfyear .= "</select>";

	$genders = array("Male", "Female");
	$select_gender = "<select name='gender'>";
	foreach ($genders as $val) {
		if ($gender == $val) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$select_gender .= "<option value='$val' $selected>$val</option>";
	}
	$select_gender .= "</select>";

	// Sales person
	db_conn("exten");

	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespn_rslt = db_exec($sql) or errDie("Unable to retrieve sales people from Cubit.");

	$salespn_out = "<select name='salespn'>";
	while ($salespn_data = pg_fetch_array($salespn_rslt)) {
		if ($salespn == $salespn_data["salesp"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$salespn_out .= "<option value='$salespn_data[salespid]' $selected>$salespn_data[salesp]</option>";
	}
	$salespn_out .= "</select>";

	// reports to name
	$reports_to = "";
	if ( ! empty($reports_to_id) ) {
		$reports_to_id += 0;

		db_conn("crm");
		$sql = "SELECT * FROM leads WHERE id='$reports_to_id' LIMIT 1";
		$rslt = db_exec($sql) or errDie("Error retrieving 'Reports to' value.");

		$dat = pg_fetch_array($rslt);

		if ( ! empty($dat["name"]) ) {
			$reports_to .= "$dat[name] ";
		}

		$reports_to .= "$dat[surname]";
	}

	// crm value
	if ( isset($crm) ) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	$Cons = "
		<select size='1'  name=Con>
			<option selected value='No'>No</option>
			<option value='Yes'>Yes</option>
		</select>";

	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);

	if(!isset($team_id))
		$team_id = "";

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
		<h3>New Lead</h3>
		$err
		<table ".TMPL_tblDflts."'>
		<form action='".SELF."' method='POST' name='frm_con'>
			<input type='hidden' name='key' value='confirm'>
			$ex
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
				<td>".REQ."Birthdate</td>
				<td>".mkDateSelect("bf",$bf_year,$bf_month,$bf_day)."</td>
				<td>Other E-mail</td>
				<td><input type='text' size='27' name='email_other' value='$email_other'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='2'>Account Name</td>
				<td>
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
				<td align='center'>
					Add Customer <input type='checkbox' name='cust'>
					Add Supplier <input type='checkbox' name='supp'><br>
				</td>
				<td>Assistant Phone</td>
				<td><input type='text' size='27' name='assistant_phone' value='$assistant_phone'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Religion</td>
				<td><input type='text' size='27' name='religion' value='$religion'></td></td>
				<td>Website</td>
				<td><input type='text' size='27' name='website' value='$website'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Race</td>
				<td><input type='text' size='27' name='race' value='$race'></td>
				<td>Next Contact Date (DD-MM-YYYY)</td>
				<td>".mkDateSelect("ncdate",$ncdate_year,$ncdate_month,$ncdate_day)."</td>
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
				<td>".REQ."Private</td>
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
function con_data ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();

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
		$v->isOk($ncdate_month, "num", 1, 2, "Next contact day (Month)");
		$v->isOk($ncdate_year, "num", 4, 4, "Next contact day (Year)");
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

	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_month, $bf_day, $bf_year));

	# display errors, if any
	if ($v->isError ()) {

		$errors = $v->getErrors();

		foreach ($errors as $e) {
			if ( $e["value"] == "_OTHER" )
				$err .= "<li class='err'>$e[msg]</li>";
			else
				$err .= "<li class='err'>Invalid characters: $e[msg]</li>";
		}
		return get_data($err);
	}



	db_connect();

	$lastid = pglib_lastid("customers","cusnum");

	# Get last account number
	$sql = "SELECT accno FROM customers WHERE cusnum = '$lastid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		do{
			$lastid--;
			# get last account number
			$sql = "SELECT accno FROM customers WHERE cusnum = '$lastid' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				$accno = "";
				$naccno= "";
			}else{
				$acc = pg_fetch_array($accRslt);
				$accno = $acc['accno'];
			}
		}while(strlen($accno) < 1 && $lastid > 1);
	}else{
		$acc = pg_fetch_array($accRslt);
		$accno = $acc['accno'];
	}

	# Check if we got $accno(if not skip this)
	if(strlen($accno) > 0){
		// get the next account number
		$num = preg_replace ("/[^\d]+/", "", $accno);
		$num++;
		$chars = preg_replace("/[\d]/", "", $accno);
		$naccno = $chars.$num;
	}

	db_connect();

	$lastid = pglib_lastid("suppliers","supid");

	# get last account number
	$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		do{
			$lastid--;
			# get last account number
			$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				$supno = "";
				$nsupno= "";
			}else{
				$acc = pg_fetch_array($accRslt);
				$supno = $acc['supno'];
			}
		}while(strlen($supno) < 1 && $lastid > 1);
	}else{
		$acc = pg_fetch_array($accRslt);
		$supno = $acc['supno'];
	}

	# Check if we got $supno(if not skip this)
	if(strlen($supno) > 0){
		# Get the next account number
		$num = preg_replace ("/[^\d]+/", "", $supno);
		$num++;
		$chars = preg_replace("/[\d]/", "", $supno);
		$nsupno = $chars.$num;
	}

	// Retrieve the sales person
	db_conn("exten");

	$sql = "SELECT salesp FROM salespeople WHERE salespid='$salespn'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve sales person from Cubit.");
	$salespn_out = pg_fetch_result($rslt, 0);

	if(isset($cust)) {
		$custext = "
			<tr>
				<th colspan='2'>Customer Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Acc No</td>
				<td><input type='text' size='20' name='cusacc' value='$naccno'></td>
			</tr>";
	} else {
		$custext = "";
	}

	if(isset($supp)) {
		$suptext = "
			<tr>
				<th colspan='2'>Supplier Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Sup No</td>
				<td><input type='text' size='20' name='supacc' value='$nsupno'></td>
			</tr>";
	} else {
		$suptext = "";
	}

	if ( ! empty($custext) || ! empty($suptext) ) {
		$account_id = 0;
		$displayaccountname = "
			<table width='100%' cellpadding='0' cellspacing='0'>
				<td>$custext $suptext</td>
			</table>";
	}

	if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	// Retrieve the team name
	if ($team_id) {
		$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team name.");
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "[None]";
	}


//			<input type='hidden' name='accountname' value='$accountname'>

	$con_data = "
		<h3>Confirm lead details</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='surname' value='$surname'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='account_id' value='$account_id'>
			<input type='hidden' name='accountname' value='$accountname'>
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
			<input type='hidden' name='ncdate_day' value='$ncdate_day'>
			<input type='hidden' name='ncdate_month' value='$ncdate_month'>
			<input type='hidden' name='ncdate_year' value='$ncdate_year'>
			<input type='hidden' name='salespn' value='$salespn'>
			<input type='hidden' name='team_id' value='$team_id' />
			$ex
			$displayaccountname
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
				<td>".REQ."Company/Last Name</td>
				<td>$surname</td>
				<td>Mobile</td>
				<td>$cell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Team Permissions</td>
				<td>$team_name</td>
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
				<td>".REQ."Birthdate</td>
				<td>$birthdate_description</td>
				<td>Other E-mail</td>
				<td>$email_other</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan=2>Account Name</td>
				<td rowspan=2>$accountname</td>
				<td>Assistant</td>
				<td>$assistant</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
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
			</td>
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
				<td>".REQ."Private</td>
				<td align='center'>$Con</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
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
function write_data ($HTTP_POST_VARS)
{

	$date = date("Y-m-d");
	# get vars
	extract ($HTTP_POST_VARS);

	if( isset($back) ) {
		return get_data("");
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
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
	$v->isOk($salespn, "num", 1, 9, "Sales person.");
	$v->isOK($team_id, "num", 1, 9, "Team.");

	if (!empty($ncdate_day) || !empty($ncdate_month) || !empty($ncdate_year)) {
		$v->isOk($ncdate_day, "num", 1, 2, "Next contact date (Day)");
		$v->isOk($ncdate_month, "num", 1, 2, "Next contact day (Month)");
		$v->isOk($ncdate_year, "num", 4, 4, "Next contact day (Year)");
		$ncdate_col = ", ncdate";
		$ncdate = ", '$ncdate_year-$ncdate_month-$ncdate_day'";
	} else {
		$ncdate_col = "";
		$ncdate = "";
	}

	$v->isOk($Con,"string",2 ,3, "Invalid private.");

	$birthdate = "$bf_year-$bf_month-$bf_day";
	if ( $v->isOk($birthdate, "string", 1, 100, "Birthdate") ) {
		if ( ! checkdate($bf_month, $bf_day, $bf_year) ) {
			$v->addError("_OTHER", "Invalid birthdate. No such date exists.");
		}
	}

	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));

	$assigned_to = USER_NAME;
	$assigned_to_id = USER_ID;

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
		return get_data($err);
	}

	db_conn('cubit');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class='err'>Unable to add lead to database. (TB)</li>";
	}

	if(isset($supacc)) {
		$supacc=remval($supacc);
		$sql = "
			INSERT INTO  suppliers (
				deptid, supno, supname, location, fcid, currency, vatnum, supaddr, contname, tel, fax, 
				email, url, listid, bankname, branname, brancode, bankaccno, balance, fbalance, div
			) VALUES (
				'2', '$supacc', '$surname', 'loc', '2', 'R', '', '$hadd \n $padd', '', '$tell', '$fax', 
				'$email', '', '2', '', '', '', '', 0, 0, '".USER_DIV."'
			)";
		$supRslt = db_exec ($sql) or errDie ("Unable to add supplier to the system.", SELF);
		if (pg_cmdtuples ($supRslt) < 1) {
			return "<li class='err'>Unable to add supplier to database.</li>";
		}

		if ( ($supp_id = pglib_lastid("suppliers", "supid")) == 0 ) {
			return "<li class='err'>Unable to add supplier to lead list.</li>";
		}

		$accountname = $surname;
		$account_type = "Supplier";
		$account_id = $supp_id;
	} else {
		$supp_id = 0;
	}

	if(isset($cusacc)) {
		$cusacc = remval($cusacc);
		$sql = "
			INSERT INTO customers (
				deptid, accno, surname, title, init, location, fcid, currency, category, class, addr1, paddr1, vatnum, 
				contname, bustel, tel, cellno, fax, email, url, traddisc, setdisc, pricelist, chrgint, overdue, 
				intrate, chrgvat, credterm, odate, credlimit, blocked, balance, div,deptname,classname,catname
			) VALUES (
				'2', '$cusacc', '$surname', '', '', 'loc', '2', 'R', '2', '2', '$hadd', '$padd', '', 
				'', '', '$tell', '$cell', '$fax', '$email', '', '0', '0', '2', 'yes', '0', '0', 'yes', 
				'0', '$date', '0', 'no', '0', '".USER_DIV."','Ledger 1','General','General'
			)";
		$custRslt = db_exec ($sql) or errDie ("Unable to add customer to system.", SELF);
		if (pg_cmdtuples ($custRslt) < 1) {
			return "<li class='err'>Unable to add customer to database.";
		}

		if (($cust_id = pglib_lastid("customers", "cusnum")) == 0) {
			return "<li class='err'>Unable to add customer to lead list.</li>";
		}

		$accountname = $surname;
		$account_type = "Customer";
		$account_id = $cust_id;
	} else {
		$cust_id = 0;
	}

	# write to db
	db_conn("crm");

	$sql = "
		INSERT INTO leads (
			surname, name, accountname, account_id, account_type, lead_source, title, department, 
			birthdate, tell, cell, fax, tell_office, tell_other, email, email_other, assistant, 
			assistant_phone, padd, padd_city, padd_state, padd_code, padd_country, hadd, hadd_city, 
			hadd_state, hadd_code, hadd_country, description, website, religion, race, gender, 
			ref, date, con, by, div, supp_id, cust_id, assigned_to, 
			assigned_to_id $ncdate_col, salespid, team_id
		) VALUES (
			'$surname', '$name', '$accountname', '$account_id', '$account_type', '$lead_source', '$title', '$department', 
			'$birthdate', '$tell', '$cell', '$fax', '$tell_office', '$tell_other', '$email', '$email_other', '$assistant', 
			'$assistant_phone', '$padd', '$padd_city', '$padd_state', '$padd_code', '$padd_country', '$hadd', '$hadd_city', 
			'$hadd_state', '$hadd_code', '$hadd_country', '$description', '$website', '$religion', '$race', '$gender', 
			'', CURRENT_DATE, '$Con', '".USER_NAME."', '".USER_DIV."', '$supp_id', '$cust_id', '$assigned_to', 
			'$assigned_to_id' $ncdate, '$salespn', '$team_id'
		)";
	$rslt = db_exec($sql) or errDie ("Unable to add lead to database.");
	$lead_id = pglib_lastid("leads", "id");

	// Add entry to today
	if (!empty($ncdate_year) && !empty($ncdate_month) && !empty($ncdate_day)) {
		$contact_date = "$ncdate_year-$ncdate_month-$ncdate_day";
		addTodayEntry("Leads", $lead_id, $contact_date, "Contact $surname");
	}

	if (!pglib_transaction("COMMIT")) {
		return "<li class='err'>Unable to add lead to database. (TC)</li>";
	}

	if(isset($crm)) {
		header("Location: crm/tokens-new.php?value=$surname");
		exit;
	}

	$write_data = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Lead added</th>
			</tr>
			<tr class='datacell'>
				<td>$surname has been added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='".SELF."'>Add another lead</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../crmsystem/leads_list.php'>View Leads</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write_data;

}


?>
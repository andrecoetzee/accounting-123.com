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
# file please contact us at +27834433455 or via email
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

# get settings
require("../settings.php");
require("gw-common.php");
require_lib("validate");

// remove all '
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_POST_VARS[$key] = str_replace("'", "", $value);
	}
}
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

// overwrite the get vars with the post vars
if ( isset($HTTP_POST_VARS) ) {
	foreach($HTTP_POST_VARS as $gvar => $value) {
		$HTTP_GET_VARS[$gvar]=$value;
	}
}

// create the $OUTPUT variable
$OUTPUT="";

// set the date to read to current one if not specified
if ( ! isset($HTTP_GET_VARS["year"]) )
	$HTTP_GET_VARS["year"] = date("Y");

if ( ! isset($HTTP_GET_VARS["month"]) )
	$HTTP_GET_VARS["month"] = date("m");

if ( ! isset($HTTP_GET_VARS["mday"]) )
	$HTTP_GET_VARS["mday"] = date("d");

// DECIDE WHAT TO DO
if ( isset($HTTP_GET_VARS["key"]) ) {
	switch ($HTTP_GET_VARS["key"]) {
		case "create": // create the actual entry and close the window
			$OUTPUT=createAppointment();
			break;
		case "view": // view existing appointment
			$OUTPUT=viewAppointment();
			break;
		case "delete": // delete appointment
			$OUTPUT=deleteAppointment();
			break;
		case "modify": // modify appointment
			$OUTPUT=modifyAppointment();
			break;
		case "enter": // enter the appointment info
		default:
			$OUTPUT=enterAppointment();
	}
} else { // create appointment form
	$OUTPUT=enterAppointment();
}


// get templete
require ("gw-tmpl.php");
// require("../template.php");

// creates the "create appointment" form
function enterAppointment() {
	global $HTTP_GET_VARS;

	// if a selected user was specified, but you do not have write permission to his diary, remove
	// the diary selection and update buttons
	if ( isset($HTTP_GET_VARS["ap_diaryowner"]) && $HTTP_GET_VARS["ap_diaryowner"] != USER_NAME ) {
		db_conn("cubit");
		$sql = "SELECT * FROM diary_privileges
			WHERE diary_owner='$HTTP_GET_VARS[ap_diaryowner]' AND priv_owner='".USER_NAME."' AND privilege='W'";
		$rslt = db_exec($sql) or errDie("Error checking diary permissions (REMFLD).");

		if ( pg_num_rows($rslt) > 0 ) {
			$NOT_WRITEABLE = false;
		} else {
			$NOT_WRITEABLE = true;
		}
	} else {
		$NOT_WRITEABLE = false;
	}

	// start of form
	$OUTPUT="<center>
		<table width=100% height=100%>
			<tr>
				<td valign=top align=center>";

	if ( ! $NOT_WRITEABLE ) {
		$OUTPUT .= "<form action='diary-appointment.php' method=POST name='form'>";
	}

	// generate lists for start time selections
	$select_day="";
	for ( $i=1 ; $i<=31 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_day"]) && $HTTP_GET_VARS["ap_day"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_day.="<option value=$i $selected>$i</option>";
	}

	$select_month="";
	for ( $i=1 ; $i<=12 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_month"]) && $HTTP_GET_VARS["ap_month"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_month.="<option value=$i $selected>".date("M",mktime(0,0,0,$i,1,2000))."</option>";
	}

	$select_year="";
	for ( $i=date("Y") ; $i<=2050 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_year"]) && $HTTP_GET_VARS["ap_year"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_year.="<option value=$i $selected>$i</option>";
	}

	$select_start_time="";
	for ( $i=6 ; $i<=21 ; $i++ ) {
		$selected1="";
		$selected2="";
		if ( isset($HTTP_GET_VARS["ap_start_time"]) ) {
			if ( $HTTP_GET_VARS["ap_start_time"] == "$i:00" )
				$selected1="selected";
			else if ( $HTTP_GET_VARS["ap_start_time"] == "$i:30" )
				$selected2="selected";
		}

		$select_start_time.="<option value='$i:00' $selected1>$i:00</option>";
		$select_start_time.="<option value='$i:30' $selected2>$i:30</option>";
	}

	// generate lists for end time selection
	$select_end_time="";
	for ( $i=6 ; $i<=22 ; $i++ ) {
		$selected1="";
		$selected2="";
		if ( isset($HTTP_GET_VARS["ap_end_time"]) ) {
			if ( $HTTP_GET_VARS["ap_end_time"] == "$i:00")
				$selected1="selected";
			else if ( $HTTP_GET_VARS["ap_end_time"] == "$i:30")
				$selected2="selected";
		} else if ( isset($HTTP_GET_VARS["ap_start_time"]) ) {
			if ( $HTTP_GET_VARS["ap_start_time"] == ($i-1) . ":30")
				$selected1="selected";
			else if ( $HTTP_GET_VARS["ap_start_time"] == $i.":00")
				$selected2="selected";
		}

		$select_end_time.="<option value='$i:00' $selected1>$i:00</option>";

		// only add this on if it not past 22:00
		if ( $i < 22 )
			$select_end_time.="<option value='$i:30' $selected2>$i:30</option>";
	}

	// lists for repetitions dates
	$select_repet_day="";
	for ( $i=1 ; $i<=31 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_repet_day"]) && $HTTP_GET_VARS["ap_repet_day"] == $i )
			$selected="selected";
		else if ( isset($HTTP_GET_VARS["ap_day"]) && $HTTP_GET_VARS["ap_day"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_repet_day.="<option value=$i $selected>$i</option>";
	}

	$select_repet_month="";
	for ( $i=1 ; $i<=12 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_repet_month"]) && $HTTP_GET_VARS["ap_repet_month"] == $i )
			$selected="selected";
		else if ( isset($HTTP_GET_VARS["ap_month"]) && $HTTP_GET_VARS["ap_month"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_repet_month.="<option value=$i $selected>".date("M",mktime(0,0,0,$i,1,2000))."</option>";
	}

	$select_repet_year="";
	for ( $i=date("Y") ; $i<=2050 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_repet_year"]) && $HTTP_GET_VARS["ap_repet_year"] == $i )
			$selected="selected";
		else if ( isset($HTTP_GET_VARS["ap_year"]) && $HTTP_GET_VARS["ap_year"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_repet_year.="<option value=$i $selected>$i</option>";
	}

	// list of diaries person may edit
	if ( $NOT_WRITEABLE ) {
		$diary_list = "$HTTP_GET_VARS[ap_diaryowner]";
	} else {
		db_conn("cubit");
		$sql = "SELECT '".USER_NAME."' AS diary_owner
			UNION
			SELECT diary_owner FROM diary_privileges WHERE privilege = 'W' AND priv_owner = '".USER_NAME."'";
		$rslt=db_exec($sql) or errDie("Error reading diaries you may write to.");
		$diary_list="<select name='ap_diaryowner'>";
		while ( $row=pg_fetch_array($rslt) ) {
			if ( isset($HTTP_GET_VARS["ap_diaryowner"]) && $HTTP_GET_VARS["ap_diaryowner"] == $row["diary_owner"] )
				$selected = "selected";
			elseif ( (! isset($HTTP_GET_VARS["ap_diaryowner"]) ) && $row["diary_owner"] == USER_NAME )
				$selected = "selected";
			else
				$selected="";;

			$diary_list.="<option value='$row[0]' $selected>$row[0]</option>";
		}
		$diary_list.="</select>";
	}

	// list of categories, default selection: appointments
	$rslt=db_exec("SELECT category_id,category_name FROM diary_categories");
	$category_list="";

	// check if there was any categories, if not add them, and get the results again
	if ( pg_num_rows($rslt) <= 0 ) {
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Reminder')") or errDie("Error inserting category");
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Call')") or errDie("Error inserting category");
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Meeting')") or errDie("Error inserting category");
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Birthday')") or errDie("Error inserting category");
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Training')") or errDie("Error inserting category");
		db_exec("INSERT INTO diary_categories (category_name) VALUES('Event')") or errDie("Error inserting category");

		$rslt=db_exec("SELECT category_id,category_name FROM diary_categories");
	}

	while ( $row=pg_fetch_row($rslt) ) {
		if ( isset($HTTP_GET_VARS["ap_category"]) && $HTTP_GET_VARS["ap_category"] == $row[0] )
			$selected="selected";
		else if ( ! isset($HTTP_GET_VARS["ap_category"]) && $row[1]=='Appointments')
			$selected="selected";
		else
			$selected="";

		$category_list.="<option value=$row[0] $selected>$row[1]</option>";
	}

	// notify list
	$select_notify="";
	for ( $i=0 ; $i<=14 ; $i++ ) {
		if ( isset($HTTP_GET_VARS["ap_notify"]) && $HTTP_GET_VARS["ap_notify"] == $i )
			$selected="selected";
		else if ( ! isset($HTTP_GET_VARS["ap_notify"]) && $i == 0 )
			$selected="selected";
		else
			$selected="";

		if ( $i == 0 ) { // no notify
			$select_notify.="<option value='$i' $selected>Dont Notify</option>";
		} else {
			$select_notify.="<option value='$i' $selected>$i days before</option>";
		}
	}

	// selection restore for Repetitions
	if ( isset($HTTP_GET_VARS["ap_repet"]) ) {
		$HTTP_GET_VARS["ap_repet"]=='N' ? $rep_selected0="checked" : $rep_selected0="";
		$HTTP_GET_VARS["ap_repet"]=='D' ? $rep_selected1="checked" : $rep_selected1="";
		$HTTP_GET_VARS["ap_repet"]=='W' ? $rep_selected2="checked" : $rep_selected2="";
		$HTTP_GET_VARS["ap_repet"]=='M' ? $rep_selected3="checked" : $rep_selected3="";
		$HTTP_GET_VARS["ap_repet"]=='Y' ? $rep_selected4="checked" : $rep_selected4="";
	} else {
		$rep_selected0="checked";
		$rep_selected1="";
		$rep_selected2="";
		$rep_selected3="";
		$rep_selected4="";
	}

	// format variables so they are checked or filled again
	isset($HTTP_GET_VARS["ap_entireday"]) ? $sel_entireday="checked" : $sel_entireday="";
	isset($HTTP_GET_VARS["ap_private"]) ? $sel_private="checked" : $sel_private="";
	isset($HTTP_GET_VARS["ap_repet_forever"]) ? $sel_repet_forever="checked" : $sel_repet_forever="";

	isset($HTTP_GET_VARS["ap_title"]) ? $ap_title=$HTTP_GET_VARS["ap_title"] : $ap_title="";
	isset($HTTP_GET_VARS["ap_location"]) ? $ap_location=$HTTP_GET_VARS["ap_location"] : $ap_location="";
	isset($HTTP_GET_VARS["ap_homepage"]) ? $ap_homepage=$HTTP_GET_VARS["ap_homepage"] : $ap_homepage="";
	isset($HTTP_GET_VARS["ap_description"]) ? $ap_description=htmlspecialchars($HTTP_GET_VARS["ap_description"]) : $ap_description="";
	isset($HTTP_GET_VARS["ap_required"]) ? $ap_required=$HTTP_GET_VARS["ap_required"] : $ap_required="";
	isset($HTTP_GET_VARS["ap_notrequired"]) ? $ap_notrequired=$HTTP_GET_VARS["ap_notrequired"] : $ap_notrequired="";
	isset($HTTP_GET_VARS["ap_optional"]) ? $ap_optional=$HTTP_GET_VARS["ap_optional"] : $ap_optional="";
	isset($HTTP_GET_VARS["ap_leadid"]) ? $ap_leadid=$HTTP_GET_VARS["ap_leadid"] : $ap_leadid = "";

	// start date
	$OUTPUT.="<table width=100% cellpadding='2' cellspacing='0' class='shtable'>
			<tr class='even'>
				<td>Date:</td>
				<td>
					<select name='ap_day'>$select_day</select> &nbsp; &nbsp;
					<select name='ap_month'>$select_month</select> &nbsp; &nbsp;
					<select name='ap_year'>$select_year</select> &nbsp; &nbsp;
				</td>
				<td nowrap><input type=checkbox $sel_entireday name='ap_entireday'>Entire Day</td>
			</tr>
			<tr class='odd'>
				<td>Time:</td>
				<td>
					From &nbsp; &nbsp; <select name='ap_start_time'>$select_start_time</select> &nbsp; &nbsp;
					to &nbsp; &nbsp; <select name='ap_end_time'>$select_end_time</select>
				</td>
				<td nowrap><input type=checkbox $sel_private name='ap_private'>Private</td>
			</tr>
			</table>
			<p></p>";

	// Retrieve contacts from the database
	db_conn("cubit");
	$sql = "SELECT * FROM cons WHERE by='".USER_NAME."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve leads from Cubit.");

	if (!pg_num_rows($rslt)) {
		$lead_sel = "<input type='hidden' name='lead_id' value='0'>";
		$lead_sel .= "<b>[No contacts found]</b>";
	} else {
		$lead_sel = "<select name='lead_id' style='width: 150'>";
		$lead_sel .= "<option value='0'>[None]</option>";
		while ($lead_data = pg_fetch_array($rslt)) {
			if ($lead_data["id"] == $ap_leadid) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$lead_sel .= "<option value='$lead_data[id]'>$lead_data[name] $lead_data[surname]</option>";
		}
		$lead_sel .= "</select>";
	}

	// Create the location dropdown
	$sql = "SELECT * FROM cubit.diary_locations";
	$loc_rslt = db_exec($sql) or errDie("Unable to retrieve locations.");

	$loc_sel = "<select name='loc_id'>
		<option value='0'>[None]</option>";
	while ($loc_data = pg_fetch_array($loc_rslt)) {
		if ($loc_id == $loc_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$loc_sel .= "<option value='$loc_data[id]' $sel>
			$loc_data[location]
		</option>";
	}
	$loc_sel .= "</select>";

	// information fields
	$OUTPUT.="
			<table width=100% cellpadding='2' cellspacing='0' class='shtable'>
				<tr class='even'>
					<td>Title:</td>
					<td><input type=text name=ap_title style='width: 150' value='$ap_title'>$lead_sel</td>
				</tr>
				<tr class='odd'>
					<td>Location:</td>
					<td>
						$loc_sel
						<a href='javascript:popupOpen(\"location_save.php?"
						.frmupdate_make("list", "form", "loc_id")."\")'>
							Add Location
						</a>
					</td>
				</tr>
				<tr class='even'>
					<td colspan=2>
						Description:<br>
						<textarea name=ap_description rows=5 cols=60>$ap_description</textarea>
					</td>
				</tr>
			</table>
			<p></p>";

	// repetitions
	$OUTPUT.="<table width=100% cellspacing='0' cellpadding='2' class='shtable'>
			<tr class='even'>
				<td>Repetitions:</td>
				<td><input type=radio name='ap_repet' value=N $rep_selected0>None</td>
				<td><input type=radio name='ap_repet' value=D $rep_selected1>Daily</td>
				<td><input type=radio name='ap_repet' value=W $rep_selected2>Weekly</td>
				<td><input type=radio name='ap_repet' value=M $rep_selected3>Monthly</td>
				<td><input type=radio name='ap_repet' value=Y $rep_selected4>Yearly</td>
			</tr>
			<tr class='odd'>
				<td>Until:</td>
				<td><select name='ap_repet_day'>$select_repet_day</select></td>
				<td><select name='ap_repet_month'>$select_repet_month</select></td>
				<td><select name='ap_repet_year'>$select_repet_year</select></td>
				<td colspan=2><input type=checkbox $sel_repet_forever name='ap_repet_forever'>Forever</td>
			</tr>
			</table>
			<p></p>";

	// other user info (NOT YET IMPLEMENTED)
		$OUTPUT.="
			<input type=hidden name='ap_required' value=''>
			<input type=hidden name='ap_notrequired' value=''>
			<input type=hidden name='ap_optional' value=''>
			";
	/*$OUTPUT.="<table width=100%>
				<tr>
					<td>Required</td>
					<td><input type=text name='ap_required' size=50 value='$ap_required'></td>
				</tr>
				<tr>
					<td>Not Required</td>
					<td><input type=text name='ap_notrequired' size=50 value='$ap_notrequired'></td>
				</tr>
				<tr>
					<td>Optional</td>
					<td><input type=text name='ap_optional' size=50 value='$ap_optional'></td>
				</tr>
			</table><hr>";*/

	// categories and whos diary
	$OUTPUT.="<table width=100% cellspacing='0' cellpadding='2' class='shtable'>
		<tr class='even'>
			<td valign=top>Category:</td>
			<td><select name='ap_category'>$category_list</select></td>
			<td valign=top nowrap>Who's Diary:</td>
			<td valign=top>$diary_list</td>
		</tr>
		<tr class='odd'>
			<td valign=top>Notify Time:</td>
			<td><select name='ap_notify'>$select_notify</select> days before</td>
			<td colspan=2>&nbsp;</td>
		</tr>
	</table>";

	if ( ! $NOT_WRITEABLE ) {
		// attach the appropriate buttons
		if ( isset($HTTP_GET_VARS["key"]) &&
			( $HTTP_GET_VARS["key"] == "view" || $HTTP_GET_VARS["key"] == "modify" ) ) {
			// attach modify button
			$OUTPUT.="
				<center><table><tr><td>
				<input type=hidden name=key value=modify>
				<input type=hidden name='entry_id' value='$HTTP_GET_VARS[entry_id]'>
				<input type=submit name=submit value='Modify Appointment'>
			</form></td></tr>";

			// attach delete button
			if ( isset($HTTP_GET_VARS["entry_id"]) ) {
				$OUTPUT.="<tr><td><form action='diary-appointment.php' method=post>
						<input type=hidden name=key value=delete>
						<input type=hidden name='entry_id' value='$HTTP_GET_VARS[entry_id]'>
						<input type=submit name=submit value='Delete Appointment'>
					</form></td>
				</tr></table></center>";
			}

			define("DOC_TITLE", "$ap_title");
		} else {
			$OUTPUT.="		<br>
				<input type=hidden name=key value=create>
				<input type=submit name=submit value='Create Appointment'>
			</form>";

			define("DOC_TITLE", "New Appointment");
		}
	}

	// end of form
	$OUTPUT.="</td>
			</tr>
		</table>
	</center>";

	return $OUTPUT;
}

// creates the appointment entry
function createAppointment() {
	global $HTTP_GET_VARS,$HTTP_SESSION_VARS,$user_admin;

	// create the recieved variables
	extract($HTTP_GET_VARS);

	// check for valid input
	// check if start date is before end date
	$v=new validate();

	$time_parts=explode(":",$ap_start_time);
	$start_time=mktime($time_parts[0], $time_parts[1], 0, $ap_month, $ap_day, $ap_year);
	$time_parts=explode(":",$ap_end_time);
	$end_time=mktime($time_parts[0], $time_parts[1], 0, $ap_month, $ap_day, $ap_year);

	// format variables to correct format for database
	isset($ap_entireday) ? $ap_entireday=1 : $ap_entireday=0;
	isset($ap_private) ? $ap_private=1 : $ap_private=0;
	isset($ap_repet) ? 1 : $ap_repet='N';
	isset($ap_repet_forever) ? $ap_repet_forever=1 : $ap_repet_forever=0;

	if ( $end_time < $start_time && $ap_entireday == 0)
		$errlist[]="The ending date/time for appointment is before the starting date/time.";

	if ( ! $v->isOk($ap_title,"string",1,200,"") )
		$errlist[]="No or erraneous title.";

	if ( ! $v->isOk(str_replace($ap_description,'@',''),"string",0,1000000,"") )
		$errlist[]="No or erraneous description.";

	if ( isset($ap_category) && $v->isOk($ap_category, "num", 0, 9, "") ) {
		$rslt=db_exec("SELECT * FROM diary_categories WHERE category_id='$ap_category'");

		if ( pg_num_rows($rslt) == 0 )
			$errlist[]="Invalid category chosen: $value.";
	} else {
		$errlist[]="Invalid category chosen: $value.";
	}

	// check if notify period valid
	if ( ! isset($ap_notify) ) {
		$ap_notify = 3;
	} else {
		if ( $ap_notify < 0 && $ap_notify > 14 )
			$errlist[]="Invalid notification period.";
	}

	// check if may add to this person's diary (if permissions or owner or admin)
	if ( $HTTP_SESSION_VARS["USER_NAME"] != $ap_diaryowner ) {
		// check if has permissions
		db_conn("cubit");
		$sql = "SELECT * FROM diary_privileges
			WHERE privilege = 'W' AND priv_owner = '".USER_NAME."' AND diary_owner = '$ap_diaryowner'";
		$rslt = db_exec($sql) or errDie("Error reading diary privileges.");

		if ( pg_num_rows($rslt) < 1 ) {
			$errlist[]="You have no permissions to modify $ap_diaryowner's diary.";
		}
	}

	// check to see if dates are valid
	if ( checkdate($ap_month, $ap_day, $ap_year) == FALSE ) {
		$errlist[]="Invalid entry date specified";
	}

	$rep_date="$ap_repet_year-$ap_repet_month-$ap_repet_day";
	$start_time=date("Y-m-d H:i:s",$start_time);
	$end_time=date("Y-m-d H:i:s",$end_time);

	// only do the repetition date checks if repetitions is not NONE and FOREVER is false
	if ( $ap_repet != 'N' && $ap_repet_forever == 0 ) {
		// check to see if repetition date is valid
		if ( checkdate($ap_repet_month, $ap_repet_day, $ap_repet_year) == FALSE ) {
			$errlist[]="Invalid repetition ending date specified";
		} else if ( mktime(0, 0, 0, $ap_repet_month, $ap_repet_day, $ap_repet_year)
						< mktime(0, 0, 0, $ap_month, $ap_day, $ap_year) ) {
			$errlist[]="The date the repetitions should end is before the date it should start.";
		}
	}

	// if errors was found, print them and create the appointment creation window, filling in all the values
	if ( isset($errlist) && is_array($errlist) ) {
		$OUTPUT="<p>The following errors was found:<br>";
		foreach($errlist as $key => $err)
			$OUTPUT.="<li class=err>$err</li>";
		$OUTPUT.="</p>";

		$OUTPUT.=enterAppointment();
		return $OUTPUT;
	} else {
		// create the diary entry
		pglib_transaction("BEGIN");

		// if this was a modification, delete the old one
		deleteAppointment();

		if ($ap_diaryowner != USER_NAME) {
			$ap_title = "[".USER_NAME."] $ap_title";
		}

		db_conn("cubit");
		$sql = "INSERT INTO diary_entries
				(username,time_start,time_end,time_entireday,title,location,
				homepage,description,type,repetitions,rep_date,rep_forever,
				category_id,notify,lead_id, loc_id)
			VALUES('$ap_diaryowner','$start_time','$end_time','$ap_entireday',
				'$ap_title','$ap_location', '$ap_homepage','$ap_description',
				'$ap_private','$ap_repet','$rep_date','$ap_repet_forever',
				'$ap_category','$ap_notify', '$lead_id', '$loc_id')";

		db_exec($sql) or errDie("Error inserting diary entry. Please contact Administrator");

		$entry_id=pglib_lastid("diary_entries","entry_id");

		preg_match("([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})", $start_time, $match);
		$date = $match[0];

		addTodayEntry("Diary", $entry_id, $date);

		pglib_transaction("COMMIT") or die("Error writing to database. Please contact your nearest integrator.") ;

		// create the required, not required and optional entry details
		$arr_required=explode(";",$ap_required);
		$arr_notrequired=explode(";",$ap_notrequired);
		$arr_optional=explode(";",$ap_optional);

		// insert each as a group setting or user setting (groups are departments and start with @)
		if ( is_array($arr_required) ) {
			foreach ( $arr_required as $arr => $arrval ) {
				if ( $arrval!="" ) {
					if ( $arrval[0] == '@' )
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '', '$arrval','R')");
					else
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '$arrval', '','R')");
				}
			}
		}

		if ( is_array($arr_notrequired) ) {
			foreach ( $arr_notrequired as $arr => $arrval ) {
				if ( $arrval!="" ) {
					if ( $arrval[0] == '@' )
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '', '$arrval','N')");
					else
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '$arrval', '','N')");
				}
			}
		}

		if ( is_array($arr_optional) ) {
			foreach ( $arr_optional as $arr => $arrval ) {
				if ( $arrval!="" ) {
					if ( $arrval[0] == '@' )
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '', '$arrval','O')");
					else
						db_exec("INSERT INTO diary_entries_details VALUES('$entry_id', '$arrval', '','O')");
				}
			}
		}

		// notify all on the required, not required and optional list
		//print "NOTIFY ALL ON REQUIRED, NOT REQUIRED AND OPTIONAL LIST<br>";

		$i = 0;
		$get = "";
		foreach ($_POST as $key=>$value) {
			if ($i) {
				$get .= "&";
			}
			$i++;

			$get .= "$key=$value";
		}

		// quit
		$OUTPUT="
		<script>
				obj = window.opener.location.reload();
				window.close();
				//obj = obj.contentDocument.getElementById('diary_container');
				//ajaxRequest(\"diary-index.php\", obj, AJAX_OBJ | AJAX_CLS, \"$get\");
		</script>";
	}

	return $OUTPUT;
}

// function that loads the specified appointment into GET_VARS and shows it, with the delete button
function viewAppointment() {
	global $HTTP_GET_VARS;

	if ( ! isset($HTTP_GET_VARS["entry_id"]) )
		return;

	db_conn("cubit");
	$rslt=db_exec("SELECT username,time_entireday,title,location,homepage,description,type,repetitions,rep_forever, lead_id,
				EXTRACT(day from rep_date) AS rep_day, EXTRACT(month from rep_date) AS rep_month,
				EXTRACT(year from rep_date) AS rep_year,

				EXTRACT(day from time_start) AS day, EXTRACT(month from time_start) AS month,
				EXTRACT(year from time_start) AS year,

				EXTRACT(hour from time_start) as shour, EXTRACT(minute from time_start) as smin,
				EXTRACT(hour from time_end) as ehour, EXTRACT(minute from time_end) as emin,

				category_id, notify
			FROM diary_entries
			WHERE entry_id=$HTTP_GET_VARS[entry_id]");

	if ( $sqlrow = pg_fetch_array($rslt) ) {
		// general
		$HTTP_GET_VARS["ap_diaryowner"] = $sqlrow["username"];
		$HTTP_GET_VARS["ap_title"] = $sqlrow["title"];
		$HTTP_GET_VARS["ap_location"] = $sqlrow["location"];
		$HTTP_GET_VARS["ap_homepage"] = $sqlrow["homepage"];
		$HTTP_GET_VARS["ap_description"] = $sqlrow["description"];
		$HTTP_GET_VARS["ap_repet"] = $sqlrow["repetitions"];
		$HTTP_GET_VARS["ap_category"] = $sqlrow["category_id"];
		$HTTP_GET_VARS["ap_notify"] = $sqlrow["notify"];
		$HTTP_GET_VARS["ap_leadid"] = $sqlrow["lead_id"];

		// time variables
		$HTTP_GET_VARS["ap_day"] = $sqlrow["day"];
		$HTTP_GET_VARS["ap_month"] = $sqlrow["month"];
		$HTTP_GET_VARS["ap_year"] = $sqlrow["year"];
		$HTTP_GET_VARS["ap_start_time"] = $sqlrow["shour"] .":". str_pad($sqlrow["smin"],2,"0",STR_PAD_LEFT);
		$HTTP_GET_VARS["ap_end_time"] = $sqlrow["ehour"] .":". str_pad($sqlrow["emin"],2,"0",STR_PAD_LEFT);
		$HTTP_GET_VARS["ap_repet_day"] = $sqlrow["rep_day"];
		$HTTP_GET_VARS["ap_repet_month"] = $sqlrow["rep_month"];
		$HTTP_GET_VARS["ap_repet_year"] = $sqlrow["rep_year"];

		// these variables should not be set when false
		$sqlrow["time_entireday"] == '1' ? $HTTP_GET_VARS["ap_entireday"] = 1 : 1;
		$sqlrow["type"] == '1' ? $HTTP_GET_VARS["ap_private"] = 1 : 1;
		$sqlrow["rep_forever"] == '1' ? $HTTP_GET_VARS["ap_repet_forever"] = 1 : 1;

		// generate the required, not required and optional fields

		// create the category listings
		$OUTPUT = enterAppointment();
	} else {
		return "Error reading diary entry from database. Please contact Cubit.";
	}

	return $OUTPUT;
}

// function that removes a specified entry from Cubit, and closes the window
function deleteAppointment() {
	global $HTTP_GET_VARS;

	if ( ! isset($HTTP_GET_VARS["entry_id"]) )
		return 0;

	// delete from the diary_entries table
	db_exec("DELETE FROM diary_entries WHERE entry_id='$HTTP_GET_VARS[entry_id]' ");

	// delete all diary entry details
	db_exec("DELETE FROM diary_entries_details WHERE entry_id='$HTTP_GET_VARS[entry_id]' ");

	removeTodayEntry("Diary", $HTTP_GET_VARS["entry_id"]);

	// only close the window when it is delete key, not modify, else errors wont get displayed
	if ( $HTTP_GET_VARS["key"] == "delete" ) {
		$OUTPUT="
		<script>
				obj = window.opener.location.reload();
				window.close();
				//obj = obj.contentDocument.getElementById('diary_container');
				//ajaxRequest(\"diary-index.php\", obj, AJAX_OBJ | AJAX_CLS, \"$get\");
		</script>";
	} else {
		$OUTPUT="";
	}

	return $OUTPUT;
}

// function that modifies an entry. all it does is call createApp, which will call deleteApp on success
function modifyAppointment() {
	$OUTPUT = createAppointment();

	return $OUTPUT;
}
?>

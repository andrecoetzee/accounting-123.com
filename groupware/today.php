<?php

require ("../settings.php");
require ("gw-common.php");

error_reporting(E_ALL);

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display ();
			break;
		case "future":
			$OUTPUT = future ();
			break;
		case "remove":
			$OUTPUT = remove ();
			break;
	}
} else {
	$OUTPUT = display ();
}

require ("gw-tmpl.php");



function display ()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["human_date"] = date("D jS M Y"); // eg: Mon 2nd Aug 2006

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT * FROM cubit.today WHERE date<=now()";
	$today_rslt = db_exec($sql) or errDie("Unable to retrieve today.");

	$today_out = "";
	$prev_out = "";
	while ($today_data = pg_fetch_array($today_rslt)) {
		if (!in_team($today_data["team_id"], USER_ID)) {
			continue;
		}


		$datetime = "$today_data[date] / ";
		if (!empty($today_data["time"])) {
			$datetime .= $today_data["time"];
		} else {
			$datetime .= "Entire Day";
		}

		// Retrieve the section
		$sql = "SELECT * FROM cubit.today_sections WHERE id='$today_data[section_id]'";
		$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
		$section_data = pg_fetch_array($section_rslt);

		$tmp_out = "
			<tr class='".bg_class()."'>
				<td>$datetime</td>
				<td><a href='javascript:popupOpen(\"$section_data[link]\")'>$section_data[name]</a></td>
				<td><a href='javascript:popupOpen(\"$today_data[link]\")'>$today_data[title]</a></td>
				<td>$today_data[info]</td>
				<td align='center'>
					<input type='checkbox' name='rem[$today_data[id]]' 
					value='$today_data[id]' onchange='javascript:document.form.submit()' />
				</td>
			</tr>";

		if ($today_data["date"] == date("Y-m-d")) {
			$today_out .= $tmp_out;
		} else {
			$prev_out .= $tmp_out;
		}
	}

	if (empty($today_out)) {
		$today_out = "
			<tr class='".bg_class()."'>
				<td colspan='5'><li>No items found for today</li></td>
			</tr>";
	}
	if (empty($prev_out)) {
		$prev_out = "
			<tr class='".bg_class()."'>
				<td colspan='5'><li>No previous items found</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>Today Action Display for $human_date</h3>
		".whereis_block()."
		<form method='POST' action='".SELF."' name='form'>
			<input type='hidden' name='key' value='remove' />
			<a href='".SELF."?key=future' style='font-size:12pt; font-weight: bold'>Future Action Dates</a>
		<p></p>
		<table cellpadding='5' cellspacing='0' class='shtable'>
			<tr>
				<th>Date / Time</th>
				<th>Section</th>
				<th>Title</th>
				<th>Info</th>
				<th>Remove</th>
			</tr>
			$today_out
		</table>
		<p></p>
		<h3>Previous Actions</h3>
		<table cellpadding='5' cellspacing='0' class='shtable'>
			<tr>
				<th>Date / Time</th>
				<th>Section</th>
				<th>Title</th>
				<th>Info</th>
				<th>Remove</th>
			</tr>
			$prev_out
		</table>
		</form>
		<p></p>
		<a href='today_dates.php' style='font-size:12pt; font-weight: bold'>Brought Forward Dates</a><br><br>".
		mkQuickLinks(
			ql ("../crmsystem/leads_list.php","Return To View Leads")
		);
	return $OUTPUT;

}



function future()
{

	$sql = "SELECT * FROM cubit.today WHERE date>now()";
	$today_rslt = db_exec($sql) or errDie("Unable to retrieve today.");

	$prev_out = "";
	$future_out = "";
	while ($today_data = pg_fetch_array($today_rslt)) {
		if (!in_team($today_data["team_id"], USER_ID)) {
			continue;
		}

		$datetime = "$today_data[date] / ";
		if (!empty($today_data["time"])) {
			$datetime .= $today_data["time"];
		} else {
			$datetime .= "Entire Day";
		}

		// Retrieve the section
		$sql = "SELECT * FROM cubit.today_sections WHERE id='$today_data[section_id]'";
		$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
		$section_data = pg_fetch_array($section_rslt);

		$future_out .= "
			<tr class='".bg_class()."'>
				<td>$datetime</td>
				<td><a href='javascript:popupOpen(\"$section_data[link]\")'>$section_data[name]</a></td>
				<td><a href='javascript:popupOpen(\"$today_data[link]\")'>$today_data[title]</a></td>
				<td>$today_data[info]</td>
				<td align='center'>
					<input type='checkbox' name='rem[$today_data[id]]' 
					value='$today_data[id]' onchange='javascript:document.form.submit()' />
				</td>
			</tr>";
	}

	if (empty($future_out)) {
		$future_out = "
			<tr class='".bg_class()."'>
				<td colspan='5'><li>No items found for today</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>Future Action Display</h3>
		<form method='post' action='".SELF."' name='form'>
			<input type='hidden' name='key' value='remove' />
			<a href='".SELF."' style='font-size:12pt; font-weight: bold'>Today/Previous Action Dates</a>
			<p></p>
		<table cellpadding='5' cellspacing='0' class='shtable'>
			<tr>
				<th>Date / Time</th>
				<th>Section</th>
				<th>Title</th>
				<th>Info</th>
				<th>Remove</th>
			</tr>
			$future_out
		</table><br>".
		mkQuickLinks(
			ql ("../crmsystem/leads_list.php","Return To View Leads")
		);
	return $OUTPUT;

}



function remove()
{

	extract ($_REQUEST);

	foreach ($rem as $id) {
		removeActions($id);

		$sql = "DELETE FROM cubit.today WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove today entry.");
	}
	return display();

}



function whereis_block()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["user_id"] = 0;

	extract ($fields, EXTR_SKIP);

	// Retrieve users
	$sql = "SELECT * FROM cubit.users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");

	$user_sel = "
		<select name='user_id' onchange='javascript:document.whereisfrm.submit()' style='width: 100%'>
			<option value='0'>[None]</option>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$user_sel .= "<option value='$user_data[userid]' $sel>$user_data[username]</option>";
	}
	$user_sel .= "</select>";

	$ctime = date("Y-m-d G:i:s");

	// Retrieve username
	if ($user_id) {
		$sql = "SELECT username FROM users WHERE userid='$user_id'";
		$un_rslt = db_exec($sql) or errDie("Unable to retrieve username.");
		$username = pg_fetch_result($un_rslt, 0);
	} else {
		$username = "";
	}

	// Retrieve location
	$sql = "
		SELECT loc_id 
		FROM cubit.diary_entries 
		WHERE ('$ctime' BETWEEN time_start AND time_end) AND username='$username'";
	$de_rslt = db_exec($sql) or errDie("Unable to retrieve diary location.");
	$loc_id = pg_fetch_result($de_rslt, 0);

	if ($loc_id) {
		$sql = "SELECT location FROM cubit.diary_locations WHERE id='$loc_id'";
		$loc_rslt = db_exec($sql) or errDie("Unable to retrieve diary location.");
		$location = pg_fetch_result($loc_rslt, 0);
	} else {
		$location = "No entry found in the diary.";
	}

	$OUTPUT = "
		<form method='POST' action='".SELF."' name='whereisfrm'>
		<table cellspacing='1' cellpadding='5' class='shtable' width='30%'>
			<tr>
				<th>Where is</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$user_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><b>$location</b></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function removeActions($id)
{

	// Retrieve today
	$sql = "SELECT * FROM cubit.today WHERE id='$id'";
	$today_rslt = db_exec($sql) or errDie("Unable to retrieve today entry.");
	$today_data = pg_fetch_array($today_rslt);

	// Retrieve section
	$sql = "SELECT * FROM cubit.today_sections WHERE id='$today_data[section_id]'";
	$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
	$section_data = pg_fetch_array($section_rslt);

	// Blank the contact date at leads if neccessary
	switch ($section_data["name"]) {
		case "Leads":
			$sql = "UPDATE crm.leads SET ncdate=NULL WHERE id='$today_data[link_id]'";
			db_exec($sql) or errDie("Unable to perform remove action on leads.");
			break;
	}
	return true;

}



?>
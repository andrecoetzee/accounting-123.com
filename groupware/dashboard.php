<?php

require ("../settings.php");

$OUTPUT = display();

require ("gw-tmpl.php");

function display()
{
	extract ($_REQUEST);

	define ("SECONDS_IN_DAY", 5184000);
	$yesterday = (time() - SECONDS_IN_DAY);

	$fields["hs_day"] = "01";
	$fields["hs_month"] = date("m");
	$fields["hs_year"] = date("Y");
	$fields["he_day"] = date("d", $yesterday);
	$fields["he_month"] = date("m", $yesterday);
	$fields["he_year"] = date("Y", $yesterday);

	extract ($fields, EXTR_SKIP);

	// Display the current day
	$date_start = date("Y-m-d")." 00:00:00";
	$date_end = date("Y-m-d")." 23:59:59";

	$hs_date = "$hs_year-$hs_month-$hs_day 00:00:00";
	$he_date = "$he_year-$he_month-$he_day 23:59:59";

	$date = mkDate(date("Y"), date("m"), date("d"));

	$OUTPUT = "<center>
	<h3>Today Action Display for $date</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='5' valign='top' align='center'>".whereis_block()."</td>
		<tr>
			<td align='center' valign='top'>".diary_block($date_start, $date_end)."</td>
			<td align='center' valign='top'>".email_block($date_start, $date_end)."</td>
			<td align='center' valign='top'>".cust_block($date_start, $date_end)."</td>
			<td align='center' valign='top'>".doc_block($date_start, $date_end)."</td>
			<td align='center' valign='top'>".lead_block($date_start, $date_end)."</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td colspan='5' align='center'><h3>History</h3></td>
		</tr>
		<tr>
			<td align='center' valign='top'>".diary_block($hs_date, $he_date)."</td>
			<td align='center' valign='top'>".email_block($hs_date, $he_date)."</td>
			<td align='center' valign='top'>".cust_block($hs_date, $he_date)."</td>
			<td align='center' valign='top'>".doc_block($hs_date, $he_date)."</td>
			<td align='center' valign='top'>".lead_block($hs_date, $he_date)."</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
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

	$user_sel = "<select name='user_id'
	onchange='javascript:document.form.submit()' style='width: 100%'>
	<option value='0'>[None]</option>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$user_sel .= "<option value='$user_data[userid]' $sel>
			$user_data[username]
		</option>";
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
	$sql = "SELECT loc_id FROM cubit.diary_entries
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

	$OUTPUT = "<form method='post' action='".SELF."' name='form'>
	<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th>Where is</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$user_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><b>$location</b></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function cust_block($date_start, $date_end)
{
	extract ($_REQUEST);

	if (isset($cust_rem)) {
		$sql = "DELETE FROM cubit.cust_dates WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove entry.");
	}

	// Retrieve customer items for the selected date range
	$sql = "
	SELECT *, extract('epoch' FROM date) AS e_date FROM cubit.cust_dates
	WHERE user_id='".USER_ID."' AND date BETWEEN '$date_start' AND '$date_end'";
	$cd_rslt = db_exec($sql) or errDie("Unable to retrieve customer entries.");

	$cust_out = "";
	while ($cd_data = pg_fetch_array($cd_rslt)) {
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cd_data[cust_id]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer info.");
		$cust_data = pg_fetch_array($cust_rslt);

		$cust_out .= "<tr class='odd'>
			<td>
				<a href='../cust-det.php?cusnum=$cust_data[cusnum]'>
					$cust_data[surname] $cust_data[name]
				</a>
			</td>
			<td>$cd_data[notes]</td>
			<td><a href='".SELF."?cust_rem=1&id=$cd_data[id]'>Remove</a></td>
		</tr>";
	}

	if (empty($cust_out)) {
		$cust_out .= "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>None found</td>
		</tr>";
	}

	$OUTPUT = "<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th colspan='3'>Customer Dates</th>
		</tr>
		<tr>
			<th>Name</th>
			<th>Notes</th>
			<th>Remove</th>
		</tr>
		$cust_out
	</table>";

	return $OUTPUT;
}

function email_block($date_start, $date_end)
{
	extract ($_REQUEST);

	if (isset($email_rem)) {
		$sql = "DELETE FROM email_dates WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove entry.");
	}

	// Retrieve customer items for the selected date range
	$sql = "
	SELECT *, extract('epoch' FROM date) AS e_date FROM cubit.email_dates
	WHERE user_id='".USER_ID."' AND date BETWEEN '$date_start' AND '$date_end'";
	$ed_rslt = db_exec($sql) or errDie("Unable to retrieve customer entries.");

	$eml_out = "";
	while ($ed_data = pg_fetch_array($ed_rslt)) {
		$sql = "SELECT * FROM cubit.mail_messages WHERE message_id='$ed_data[message_id]'";
		$mm_rslt = db_exec($sql) or errDie("Unable to retrieve customer info.");
		$mm_data = pg_fetch_array($mm_rslt);

		$eml_out .= "<tr class='odd'>
			<td>$mm_data[subject]</td>
			<td>$mm_data[add_to]</td>
			<td><a href='".SELF."?email_rem=1&id=$ed_data[id]'>Remove</a></td>
		</tr>";
	}

	if (empty($eml_out)) {
		$eml_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>None found</td>
		</tr>";
	}

	$OUTPUT = "<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th colspan='3'>Email Brought Forward</th>
		</tr>
		<tr>
			<th>Subject</th>
			<th>To/From</th>
			<th>Remove</th>
		</tr>
		$eml_out
	</table>";

	return $OUTPUT;
}

function doc_block($date_start, $date_end)
{
	extract ($_REQUEST);

	if (isset($doc_rem)) {
		$sql = "DELETE FROM doc_dates WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove entry.");
	}

	// Retrieve customer items for the selected date range
	$sql = "
	SELECT *, extract('epoch' FROM date) AS e_date FROM cubit.doc_dates
	WHERE user_id='".USER_ID."' AND date BETWEEN '$date_start' AND '$date_end'";
	$dd_rslt = db_exec($sql) or errDie("Unable to retrieve customer entries.");

	$doc_out = "";
	while ($dd_data = pg_fetch_array($dd_rslt)) {
		$sql = "SELECT * FROM cubit.documents WHERE id='$dd_data[doc_id]'";
		$doc_rslt = db_exec($sql) or errDie("Unable to retrieve customer info.");
		$doc_data = pg_fetch_array($doc_rslt);

		$doc_out .= "<tr class='odd'>
			<td>$doc_data[title]</td>
			<td>$dd_data[notes]</td>
			<td><a href='".SELF."?doc_rem=1&id=$id'>Remove</a></td>
		</tr>";
	}

	if (empty($doc_out)) {
		$doc_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>None found</td>
		</tr>";
	}

	$OUTPUT = "<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th colspan='3'>Documents Brought Forward</th>
		</tr>
		<tr>
			<th>Name</th>
			<th>Notes</th>
			<th>Remove</th>
		</tr>
		$doc_out
	</table>";

	return $OUTPUT;
}

function lead_block($date_start, $date_end)
{
	extract ($_REQUEST);

	if (isset($lead_rem)) {
		$sql = "DELETE FROM lead_dates WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove entry.");
	}

	// Retrieve lead items for the selected date range
	$sql = "
	SELECT *, extract('epoch' FROM date) AS e_date FROM cubit.lead_dates
	WHERE user_id='".USER_ID."' AND date BETWEEN '$date_start' AND '$date_end'";
	$dd_rslt = db_exec($sql) or errDie("Unable to retrieve lead entries.");

	$lead_out = "";
	while ($ld_data = pg_fetch_array($dd_rslt)) {
		$sql = "SELECT * FROM crm.leads WHERE id='$ld_data[lead_id]'";
		$lead_rslt = db_exec($sql) or errDie("Unable to retrieve lead info.");
		$lead_data = pg_fetch_array($lead_rslt);

		$lead_out .= "<tr class='odd'>
			<td>$lead_data[title]</td>
			<td>$ld_data[notes]</td>
			<td><a href='".SELF."?lead_rem=1&id=$id'>Remove</a></td>
		</tr>";
	}

	if (empty($lead_out)) {
		$lead_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>None found</td>
		</tr>";
	}

	$OUTPUT = "<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th colspan='3'>Leads</th>
		</tr>
		<tr>
			<th>Name</th>
			<th>Notes</th>
			<th>Remove</th>
		</tr>
		$lead_out
	</table>";

	return $OUTPUT;
}

function diary_block($date_start, $date_end)
{
	extract ($_REQUEST);

	if (isset($diary_rem)) {
		$sql = "DELETE FROM diary_entries WHERE entry_id='$id'";
		db_exec($sql) or errDie("Unable to remove entry.");
	}

	// Retrieve the diary items for the selected time range
	$sql = "
	SELECT *,
		extract('epoch' FROM time_start) AS e_start,
		extract('epoch' FROM time_end) AS e_end
	FROM cubit.diary_entries
	WHERE username='".USER_NAME."' AND (
		(time_start BETWEEN '$date_start' AND '$date_end')
	OR
		(time_end BETWEEN '$date_start' AND '$date_end')
	)";
	$de_rslt = db_exec($sql) or errDie("Unable to retrieve diary entries.");

	$diary_out = "";
	while ($de_data = pg_fetch_array($de_rslt)) {

		// Display the starting time of the event
		if ($de_data["time_entireday"]) {
			$time = "Entire Day";
		} else {
			$time = date("G:i:s", $de_data["e_start"]);
		}

		$diary_out .= "
		<tr class='odd'>
			<td>
				<a href='diary-appointment.php?entry_id=$de_data[entry_id]&key=view'>
					$de_data[title]
				</a>
			</td>
			<td>$time</td>
			<td><a href='".SELF."?diary_rem=1&id=$de_data[entry_id]'>Remove</a></td>
		</tr>";
	}

	if (empty($diary_out)) {
		$diary_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>None found</td>
		</tr>";
	}

	$OUTPUT = "
	<table cellspacing='1' cellpadding='5' class='shtable'>
		<tr>
			<th colspan='3'>Diary</th>
		</tr>
		<tr>
			<th>Title</th>
			<th>Time</th>
			<th>Remove</th>
		</tr>
		$diary_out
	</table>";

	return $OUTPUT;
}
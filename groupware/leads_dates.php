<?php

require ("../settings.php");
require ("../groupware/gw-common.php");

if (!isset($_REQUEST["lead_id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module</li>";
	require ("../template.php");
}

if (isset($_REQUEST["key"])) {
	$key = strtolower($_REQUEST["key"]);
	switch ($key) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "add":
			$OUTPUT = add();
			break;
		case "remove":
			$OUTPUT = remove();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	// Retrieve customer information
	$sql = "SELECT * FROM crm.leads WHERE id='$lead_id'";
	$lead_rslt = db_exec($sql) or errDie("Unable to retrieve customer information.");
	$lead_data = pg_fetch_array($lead_rslt);

	$OUTPUT = "
	<center>
	<h3>Lead Dates</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Lead</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Lead Name</td>
			<td>$lead_data[surname] $lead_data[name]</td>
		</tr>
	</table>
	<p></p>";

	$sql = "
	SELECT *,extract('epoch' FROM date) as e_date FROM cubit.lead_dates
	WHERE lead_id='$lead_id' AND user_id='".USER_ID."' ORDER BY date DESC";
	$dd_rslt = db_exec($sql) or errDie("Unable to retrieve customer dates.");

	$dates_out = "";
	while ($ld_data = pg_fetch_array($dd_rslt)) {
		$date_year = date("Y", $ld_data["e_date"]);
		$date_month = date("m", $ld_data["e_date"]);
		$date_day = date("d", $ld_data["e_date"]);

		$dates_out .= "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='id' value='$ld_data[id]' />
		<input type='hidden' name='lead_id' value='$lead_id' />
		<tr class='".bg_class()."'>
			<td>$date_day-$date_month-$date_year</td>
			<td>$ld_data[notes]</td>
			<td><input type='submit' name='key' value='Remove' /></td>
		</tr>
		</form>";
	}

	$OUTPUT .= "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Note</th>
			<th>Options</th>
		</tr>
		<form method='post' action='".SELF."'>
		<input type='hidden' name='lead_id' value='$lead_id' />
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("new_date")."</td>
			<td><input type='text' name='new_note' /></td>
			<td>
				<input type='submit' name='key' value='Add' style='width:100%'/>
			</td>
		</tr>
		</form>
		$dates_out
	</table>";

	return $OUTPUT;
}

function add()
{
	extract ($_REQUEST);

	$new_date = "$new_date_year-$new_date_month-$new_date_day";

	addTodayEntry("Leads", $lead_id, $new_date, $new_note);

	return display();
}

function remove()
{
	extract ($_REQUEST);

	removeTodayEntry("Leads", $id);

	return display();
}
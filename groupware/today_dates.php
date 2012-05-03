<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	$key = strtolower($_REQUEST["key"]);
	switch ($key) {
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

require ("gw-tmpl.php");



function display()
{

	extract($_REQUEST);

	$fields = array();
	$fields["section_id"] = 0;
	$fields["id"] = 0;

	extract ($fields, EXTR_SKIP);

	// Create the sections dropdown
	$sql = "SELECT * FROM cubit.today_sections ORDER BY name ASC";
	$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");

	$section_sel = "
		<select name='section_id' onchange='javascript:document.form.submit()' style='width: 100%'>
			<option value='0'>[None]</option>";

	while ($section_data = pg_fetch_array($section_rslt)) {
		if ($section_id == $section_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$section_sel .= "<option value='$section_data[id]' $sel>$section_data[name]</option>";
	}

	// Retrieve the section
	if ($section_id) {
		$sql = "SELECT * FROM cubit.today_sections WHERE id='$section_id'";
		$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
		$section_data = pg_fetch_array($section_rslt);
		$section_name = $section_data["name"];

		$sql = "SELECT * FROM $section_data[table_name] ORDER BY $section_data[title_column] ASC";
		$id_rslt = db_exec($sql) or errDie("Unable to retieve $section_data[name]");

		$id_sel = "
			<select name='id' onchange='javascript:document.form.submit()' style='width: 100%'>
				<option value='0'>[None]</option>";
		while ($id_data = pg_fetch_array($id_rslt)) {
			if ($id == $id_data[$section_data["id_column"]]) {
				$sel = "selected";
			} else {
				$sel = "";
			}

			$id_sel .= "
				<option value='".$id_data[$section_data["id_column"]]."' $sel>
					".$id_data[$section_data["title_column"]]."
				</option>";
		}
		$id_sel .= "</select>";

	} else {
		$section_name = "";
		$id_sel = "Please Select a Section";
	}

	$OUTPUT = "
		<h3>$section_name Brought Forward Dates</h3>
		<form method='POST' action='".SELF."' name='form' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Section</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$section_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>$id_sel</td>
			</tr>
		</table>
		</form>
		<p></p>";

	if ($section_id && $id) {
		// if section id already exists section_data should be available

		// retrieve the title
		$sql = "
			SELECT $section_data[title_column] 
			FROM $section_data[table_name] 
			WHERE $section_data[id_column]='$id'";
		$title_rslt = db_exec($sql) or errDie("Unable to retrieve title.");
		$title = pg_fetch_result($title_rslt, 0);

		$sql = "
			SELECT *,extract('epoch' FROM date) as e_date 
			FROM cubit.today 
			WHERE section_id='$section_id' AND title='$title' AND user_id='".USER_ID."' 
			ORDER BY id DESC";
		$today_rslt = db_exec($sql) or errDie("Unable to retrieve today entries.");

		$today_out = "";
		while ($today_data = pg_fetch_array($today_rslt)) {
			$date = date("d-m-Y", $today_data["e_date"]);

			$today_out .= "
				<tr class='".bg_class()."'>
					<td nowrap>$date</td>
					<td>$today_data[info]</td>
					<td align='center'>
						<input type='checkbox' name='rem' value='$today_data[id]'
						onchange='javascript:document.remfrm.submit()' />
					</td>
				</tr>";
		}

		$OUTPUT .= "
			<table cellpadding='5' cellspacing='0' class='shtable'>
				<tr>
					<th>Date</th>
					<th>Info</th>
					<th>Options</th>
				</tr>
			<form method='POST' action='".SELF."'>
				<input type='hidden' name='section_id' value='$section_id' />
				<input type='hidden' name='id' value='$id' />
				<tr class='".bg_class()."'>
					<td nowrap>".mkDateSelect("date")."</td>
					<td><input type='text' name='info' style='width: 100%' /></td>
					<td><input type='submit' name='key' value='Add' style='width:100%' /></td>
				</tr>
			</form>
			<form method='post' action='".SELF."' name='remfrm' />
				<input type='hidden' name='section_id' value='$section_id' />
				<input type='hidden' name='id' value='$id' />
				<input type='hidden' name='key' value='remove' />
				$today_out
			</form>
			</table>";
	}

	$OUTPUT .= "
		<p></p>
		<a href='today.php' style='font-size: 12pt; font-weight: bold;'>Today Action Display</a><br><br>".
		mkQuickLinks(
			ql ("../crmsystem/leads_list.php","Return To View Leads")
		);
	return $OUTPUT;

}



function add ()
{

	extract($_REQUEST);

	$date = "$date_year-$date_month-$date_day";

	// Retrieve the section
	$sql = "SELECT * FROM cubit.today_sections WHERE id='$section_id'";
	$section_rslt = db_exec($sql) or errDie("Unable to retrieve section.");
	$section_data = pg_fetch_array($section_rslt);

	// Retrieve the title
	$sql = "
		SELECT $section_data[title_column] 
		FROM $section_data[table_name] 
		WHERE $section_data[id_column]='$id'";
	$title_rslt = db_exec($sql) or errDie("Unable to retrieve title.");
	$title = pg_fetch_result($title_rslt, 0);

	// Retrieve team id if any
	$has_team = true;
	$sql = "SELECT team_id FROM $section_data[table_name] WHERE $section_data[id_column]='$id'";
	$team_rslt = db_exec($sql) or $has_team = false;

	if ($has_team) {
		$team_id = pg_fetch_result($team_rslt, 0);
	} else {
		$team_id = 0;
	}

	$sql = "
		INSERT INTO cubit.today (
			section_id, title, date, info, link, user_id, link_id, team_id
		) VALUES (
			'$section_id', '$title', '$date', '$info', '$section_data[title_link]$id', '".USER_ID."', '$id', '$team_id'
		)";
	db_exec($sql) or errDie("Unable to add today entry.");
	return display();

}



function remove()
{

	extract($_REQUEST);

	$sql = "DELETE FROM cubit.today WHERE id='$rem'";
	db_exec($sql) or errDie("Unable to retrieve today entry.");
	return display();

}



?>
<?php

require ("../settings.php");

if (!isset($_REQUEST["doc_id"])) {
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
	$sql = "SELECT * FROM cubit.documents WHERE id='$doc_id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve customer information.");
	$doc_data = pg_fetch_array($doc_rslt);

	$OUTPUT = "
	<center>
	<h3>Document Dates</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Document</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Document Title</td>
			<td>$doc_data[title]</td>
		</tr>
	</table>
	<p></p>";

	// Retrieve customer contact dates
	$sql = "
	SELECT *,extract('epoch' FROM date) as e_date FROM cubit.doc_dates
	WHERE doc_id='$doc_id' AND user_id='".USER_ID."' ORDER BY date DESC";
	$dd_rslt = db_exec($sql) or errDie("Unable to retrieve customer dates.");

	$dates_out = "";
	while ($dd_data = pg_fetch_array($dd_rslt)) {
		$date_year = date("Y", $dd_data["e_date"]);
		$date_month = date("m", $dd_data["e_date"]);
		$date_day = date("d", $dd_data["e_date"]);

		$dates_out .= "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='id' value='$dd_data[id]' />
		<input type='hidden' name='doc_id' value='$doc_id' />
		<tr class='".bg_class()."'>
			<td>$date_day-$date_month-$date_year</td>
			<td>$dd_data[notes]</td>
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
		<input type='hidden' name='doc_id' value='$doc_id' />
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

	$sql = "
	INSERT INTO cubit.doc_dates (user_id, doc_id, date, notes)
	VALUES ('".USER_ID."', '$doc_id', '$new_date', '$new_note')";
	$cd_rslt = db_exec($sql) or errDie("Unable to insert customer date.");


	return display();
}

function remove()
{
	extract ($_REQUEST);

	$sql = "DELETE FROM cubit.doc_dates WHERE id='$id'";
	db_exec($sql) or errDie("Unable to remove date.");

	return display();
}
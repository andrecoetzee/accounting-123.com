<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "view":
			$OUTPUT = view();
			break;
		case "update":
			$OUTPUT = update();
			break;
		case "done":
			$OUTPUT = done();
			break;
	}
} else {
	$OUTPUT = view();
}

require ("gw-tmpl.php");




function view()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["id"] = 0;

	extract ($fields, EXTR_SKIP);

	// Retrieve team
	$sql = "SELECT * FROM crm.team_owners WHERE user_id='".USER_ID."'";
	$to_rslt= db_exec($sql) or errDie("Unable to retrieve team owners.");

	$perm_ar = array();
	while ($to_data = pg_fetch_array($to_rslt)) {
		$perm_ar[] = "team_id='$to_data[team_id]'";
	}

	$perm_sql = "";
	if (count($perm_ar)) {
		$perm_sql = " OR ".implode(" OR ", $perm_ar);
	} else {
		$perm_sql = "";
	}

	// Retrieve main todo's
	$sql = "SELECT * FROM cubit.todo_main WHERE (user_id='".USER_ID."' $perm_sql)
	ORDER BY title ASC";
	$tm_rslt = db_exec($sql) or errDie("Unable to retrieve main todos.");

	// Keep track of the total amount of todo items
	$total_todo = 0;

	// Create main todo dropdown
	$tm_sel = "
		<select name='id' onchange='javascript:document.form.submit()' style='width: 100%'>
			<option value='0'>[None]</option>";
	while ($tm_data = pg_fetch_array($tm_rslt)) {
		$sql = "SELECT count(id) FROM todo_sub WHERE main_id='$tm_data[id]'
		AND done='0'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve todo count.");
		$count = pg_fetch_result($count_rslt, 0);

		// Add to the grand total
		$total_todo += $count;

		if ($id == $tm_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$tm_sel .= "
			<option value='$tm_data[id]' $sel>
				$tm_data[title] ($count)
			</option>";
	}
	$tm_sel .= "</select>";

	// Create the list of Todo items.
	if ($id) {
		// Retrieve tasks already added
		$sql = "SELECT *,extract('epoch' FROM datetime) as e_time FROM cubit.todo_sub
 		WHERE done='0' AND main_id='$id' ORDER BY datetime DESC";
		$ts_rslt = db_exec($sql) or errDie("Unable to retrieve sub todos.");

		// Inputs for new todo items
		$ts_out = "
			<tr class='odd'>
				<td>".mkDateSelect("ndate")."</td>
				<td>
					<input type='text' name='nhour' size='2'  value='".date("G")."'
					style='text-align: center'/> :
					<input type='text' name='nminute' size='2' value='".date("i")."'
					style='text-align: center' />
				</td>
				<td><input type='text' name='ndesc' /></td>
				<td>&nbsp;</td>
			</tr>";

		$i = 0;
		while ($ts_data = pg_fetch_array($ts_rslt)) {
			$i++;
			$class = ($i % 2) ? "odd" : "even";

			$date = date("d-m-Y", $ts_data["e_time"]);
			$time = date("G:i", $ts_data["e_time"]);

			$ts_out .= "
				<tr class='$class'>
					<td>$date</td>
					<td>$time</td>
					<td>$ts_data[description]</td>
					<td>
						<input type='checkbox' name='done' value='$ts_data[id]'
						onchange='javascript:document.form2.submit()'/>
					</td>
				</tr>";
		}
		$num_todo = pg_num_rows($ts_rslt);
	} else {
		$ts_out = "
			<tr class='odd'>
				<td colspan='5'><li>Please Select a Main Todo Item</li></td>
			</tr>";
		$num_todo = 0;
	}

	$long_date = date("D")." ".date("d").date("S")." ".date("M")." ".date("Y");

	$OUTPUT = "
		<h3>Todo List</h3>
		<form method='post' action='".SELF."' name='form'>
			<input type='hidden' name='key' value='update' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Main Todo ($total_todo)</th>
			</tr>
			<tr class='even'>
				<td>$tm_sel</td>
				<td>
					<a href='javascript:popupOpen(\"todo_main_save.php?"
					.frmupdate_make("list", "form", "id")."\")'>
						Add Main Todo
					</a>
				</td>
			</tr>
			<tr>
				<th colspan='2'>Todo ($num_todo) $long_date</th>
			</tr>
		</table>
		</form>

		<p></p>

		<form method='post' action='".SELF."' name='form2'>
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='key' value='update' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Date</th>
				<th>Time</th>
				<th>Description</th>
				<th>Done</th>
			</tr>
			$ts_out
		</table>

		<p></p>

		<input type='submit' value='Update &raquo' />

		</form>";
	return $OUTPUT;

}




function update()
{

	extract ($_REQUEST);

	if (isset($done)) {
		done($done);
	}

	if (!isset($ndate_year))
		$ndate_year = date("Y");
	if (!isset($ndate_month))
		$ndate_month = date("m");
	if (!isset($ndate_day))
		$ndate_day = date("d");
	if (!isset($nhour))
		$nhour = date("H");
	if (!isset($nminute))
		$nminute = date("i");


	$date = "$ndate_year-$ndate_month-$ndate_day";
	$time = "$nhour:$nminute";

	if (!empty($ndesc)) {
		$sql = "
			INSERT INTO cubit.todo_sub (
				datetime, description, done, main_id
			) VALUES (
				'$date $time', '$ndesc', '0', '$id'
			)";
		db_exec($sql) or errDie("Unable to add todo item.");
	}
	return view();

}




function done($done)
{

	extract ($_REQUEST);

	$sql = "UPDATE cubit.todo_sub SET done='1' WHERE id='$done'";
	db_exec($sql) or errDie("Unable to update todo item.");
	return view();

}
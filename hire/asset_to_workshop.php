<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .=
	mkQuickLinks (
		ql("asset_to_workshop.php", "Book Asset to Workshop"),
		ql("../workshop-view.php", "View Workshop")
	);

require ("../template.php");

function enter($errors="")
{
	extract($_REQUEST);

	$fields = array();
	$fields["asset_id"] = 0;
	$fields["ex_year"] = date("Y");
	$fields["ex_month"] = date("m");
	$fields["ex_day"] = date("d");
	$fields["description"] = "";
	$fields["notes"] = "";
	$fields["qty"] = 1;

	extract($fields, EXTR_SKIP);

	// Create asset dropdown
	$sql = "SELECT id, des, serial FROM cubit.assets ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$asset_sel = "<select name='asset_id' style='width='100%'>";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"]) ||
			isHired($asset_data["id"], date("Y-m-d"))) {

			continue;
		}

		if ($asset_id == $asset_data["id"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$asset_sel .= "
		<option value='$asset_data[id]' $sel>
			($asset_data[serial]) $asset_data[des]
		</option>";
	}
	$asset_sel.= "</select>";

	$OUTPUT = "<h3>Book Asset to Workshop</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='qty' value='$qty' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Asset</td>
			<td>$asset_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Expected Back Date</td>
			<td>".mkDateSelect("ex", $ex_year, $ex_month, $ex_day)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Description</td>
			<td><input type='text' name='description' value='$description' /></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Notes</td>
			<td><textarea name='notes' rows='5' cols='20'>$notes</textarea></td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($asset_id, "num", 1, 9, "Invalid asset id.");
	$v->isOk($ex_year, "num", 4, 4, "Invalid expected date (year)");
	$v->isOk($ex_month, "num", 1, 2, "Invalid expected date (month)");
	$v->isOk($ex_day, "num", 1, 2, "Invalid expected date (day)");
	$v->isOk($description, "string", 0, 255, "Invalid description.");

	if (isHired($asset_id, date("Y-m-d"))) {
		$v->addError(0, "Asset is currently hired out.");
	}

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	// Retrieve the name and serial of the asset
	$sql = "SELECT des, serial FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
	$asset_data = pg_fetch_array($asset_rslt);
	$asset_out = "($asset_data[serial]) $asset_data[des]";

	$ex_date = dateFmt($ex_year, $ex_month, $ex_day);

	$e_from = time();
	$e_to = getDTEpoch("$ex_date 23:59:59");

	if (!isSerialized($asset_id)) {
		$qty_out = "<tr bgcolor='".bgcolorg()."'>
			<td>Qty</td>
			<td><input type='text' name='qty' value='$qty' /></td>
		</tr>";

	} else {

		$booked = 0;
		for ($i = $e_from; $i < $e_to; $i += DAYS) {
			if (isBooked($asset_id, date("Y-m-d", $i))) {
				$booked = 1;
				break;
			}
		}

		if ($booked) {
			$msg = "<li class='err'><b>WARNING</b>: The asset has been booked</li>";
		}
	}

	$OUTPUT = "<h3>Book Asset to Workshop</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='asset_id' value='$asset_id' />
	<input type='hidden' name='ex_year' value='$ex_year' />
	<input type='hidden' name='ex_month' value='$ex_month' />
	<input type='hidden' name='ex_day' value='$ex_day' />
	<input type='hidden' name='description' value='$description' />
	<input type='hidden' name='notes' value='$notes' />
	<input type='hidden' name='qty' value='$qty' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$msg</td>
		</tr>
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Asset</td>
			<td>$asset_out</td>
		</tr>
		$qty_out
		<tr bgcolor='".bgcolorg()."'>
			<td>Expected Back Date</td>
			<td>$ex_date</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Description</td>
			<td>$description</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Notes</td>
			<td>$notes</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
				<input type='submit' name='key' value='&laquo Correction' />
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($asset_id, "num", 1, 9, "Invalid asset id.");
	$v->isOk($ex_year, "num", 4, 4, "Invalid expected date (year)");
	$v->isOk($ex_month, "num", 1, 2, "Invalid expected date (month)");
	$v->isOk($ex_day, "num", 1, 2, "Invalid expected date (day)");
	$v->isOk($description, "string", 0, 255, "Invalid description.");
	$v->isOk($qty, "num", 1, 9, "Invalid qty.");

	if (!isSerialized($asset_id) && $qty <= 0) {
		$v->addError(0, "Invalid Quantity.");
	}

	if (getUnits($asset_id) < $qty) {
		$v->addError(0, "Not enough items available.");
	}

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$ex_date = dateFmt($ex_year, $ex_month, $ex_day);
	$notes = base64_encode($notes);

	$sql = "SELECT id, des, serial, serial2 FROM cubit.assets
				WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
	$asset_data = pg_fetch_array($asset_rslt);

	pglib_transaction("BEGIN");

	$sql = "INSERT INTO cubit.workshop (stkcod, description, notes, status,
				serno, cdate, active, asset_id, e_date, qty)
				VALUES ('$asset_data[des]', '$description', '$notes', 'Present',
					'$asset_data[serial]', CURRENT_DATE, 'true',
					'$asset_data[id]', '$ex_date', '$qty')";
	$ws_rslt = db_exec($sql) or errDie("Unable to add workshop item.");

	$sql = "UPDATE cubit.assets SET remaction='Workshop' WHERE id='$asset_data[id]'";
	db_exec($sql) or errDie("Unable to update assets.");

	if (!isSerialized($asset_id)) {
		$new_qty = $asset_data["serial2"] - $qty;

		$sql = "UPDATE cubit.assets SET serial2='$new_qty'
					WHERE id='$asset_data[id]'";
		db_exec($sql) or errDie("Unable to update assets.");
	}

	pglib_transaction("COMMIT");

	$OUTPUT = "<h3>Book Asset to Workshop</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully booked asset to the workshop.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
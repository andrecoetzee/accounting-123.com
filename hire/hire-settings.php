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

require ("../template.php");

function enter($errors="")
{
	extract ($_REQUEST);

	// Create the stores dropdown
	$sql = "SELECT * FROM exten.warehouses ORDER BY whname ASC";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve warehouses.");

	$stores_sel = "<select name='wh_id'>";
	while ($wh_data = pg_fetch_array($wh_rslt)) {
		if ($wh_data["whid"] == $wh_id) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$stores_sel .= "
		<option value='$wh_data[whid]' $sel>
			$wh_data[whname]
		</option>";
	}
	$stores_sel .= "</select>";

	$OUTPUT = "<h3>Hire Settings</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr><td>$errors</td></tr>
		<tr><th colspan='2'>Settings</th></tr>
		<tr class='".bg_class()."'>
			<td>Hire Out Store</td>
			<td>$stores_sel</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($wh_id, "num", 1, 9, "Invalid store id.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	// Retrieve store name
	$sql = "SELECT whname FROM exten.warehouses WHERE whid='$wh_id'";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve store name.");
	$store_name = pg_fetch_result($wh_rslt, 0);

	$OUTPUT = "<h3>Hire Settings</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='wh_id' value='$wh_id' />
	<table ".TMPL_tblDflts.">
		<tr><th colspan='2'>Confirm</th></tr>
		<tr class='".bg_class()."'>
			<td>Hire Out Store</td>
			<td>$store_name</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($wh_id, "num", 1, 9, "Invalid store id.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$sql = "UPDATE hire.hire_settings SET value='$wh_id' WHERE field='wh_id'";
	db_exec($sql) or errDie("Unable to save store id.");

	$OUTPUT = "<h3>Hire Settings</h3>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='2'>Write</th></tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved settings.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
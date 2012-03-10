<?php

require ("settings.php");

if (!isset($_REQUEST["forecast_id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require("template.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = confirm();
}

require ("template.php");

function confirm()
{
	extract($_REQUEST);

	$OUTPUT = "<center>
	<h3>Remove Saved Sales Forecast</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='forecast_id' value='$forecast_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Remove this sales forecast</li></td>
			<td align='right'>
				<input type='submit' value='Remove &raquo' />
			</td>
		</tr>
	</table>
	</form>
	<iframe src='sales_forecast.php?forecast_id=$forecast_id'
	width='100%' height='80%' style='border: none'></iframe>";

	return $OUTPUT;
}

function write()
{
	extract($_REQUEST);

	pglib_transaction("BEGIN");

	$sql = "DELETE FROM cubit.forecasts WHERE id='$forecast_id'";
	db_exec($sql) or errDie("Unable to remove forecasts.");

	$sql = "DELETE FROM cubit.forecast_items WHERE forecast_id='$forecast_id'";
	db_exec($sql) or errDie("Unable to remove forecast items.");

	pglib_transaction("COMMIT");

	$OUTPUT = "<h3>Remove Saved Sales Forecast</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Remove</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Saved sales forecast removed successfully.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
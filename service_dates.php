<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.assets ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$service_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"])) {
			continue;
		}

		// Retrieve the service date
		$sql = "SELECT svdate, des FROM cubit.asset_svdates
				WHERE asset_id='$asset_data[id]'";
		$svdate_rslt = db_exec($sql) or errDie("Unable to retrieve service date.");
		list($svdate, $des) = pg_fetch_array($svdate_rslt);

		$service_out .= "
		<tr class='".bg_class()."'>
			<td>".getSerial($asset_data["id"])."</td>
			<td>$asset_data[des]</td>
			<td>$svdate</td>
			<td>$des</td>
		</tr>";
	}

	if (empty($service_out)) {
		$service_out = "<tr class='".bg_class()."'>
			<td colspan='3'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Plant Service Dates</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Serial</th>
			<th>Description</th>
			<th>Service Date</th>
			<th>Service Description</th>
		</tr>
		$service_out
	</table>
	</center>";

	return $OUTPUT;
}
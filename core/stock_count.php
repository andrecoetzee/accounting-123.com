<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract($_REQUEST);

	$sql = "SELECT stkid, stkcod, stkdes, units FROM cubit.stock
			ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {

		if (is_blocked($stock_data["stkid"])) {
			$blocked = "checked";
		} else {
			$blocked = "";
		}

		$stock_out = "
		<tr class='".bg_class()."'>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td>$stock_data[units]</td>
			<td>
				<input type='checkbox' name='blocked[$stock_data[stkid]]'
				value='$stock_data[stkid]' $blocked />
			</td>
		</tr>";
	}

	$OUTPUT = "<h3>Stock Count</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Current Units</th>
			<th>Block</th>
			<th>New Units</th>
		</tr>
		$stock_out
	</table>";
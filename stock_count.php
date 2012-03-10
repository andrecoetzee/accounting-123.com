<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "save":
			$OUTPUT = save();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("template.php");

function display()
{
	extract($_REQUEST);

	$sql = "SELECT stkid, stkcod, stkdes, units FROM cubit.stock
			ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {

		if (!isset($count[$stock_data["stkid"]])) {
			$count[$stock_data["stkid"]] = 0;
		}

		if (stock_is_blocked($stock_data["stkid"])) {
			$blocked = "checked";
		} else {
			$blocked = "";
		}

		$stock_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td>$stock_data[units]</td>
			<td>
				<input type='checkbox' name='blocked[]'
				value='$stock_data[stkid]' $blocked
				onchange='javascript:document.form.submit()'/>
			</td>
			<td>
				<input type='text' name='count[$stock_data[stkid]]'
				value='".$count[$stock_data["stkid"]]."' />
			</td>
		</tr>";
	}

	$OUTPUT = "<h3>Stock Count</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='save' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Current Units</th>
			<th>Block</th>
			<th>New Units</th>
		</tr>
		$stock_out
	</table>
	</form>";

	return $OUTPUT;
}

function save()
{
	extract($_REQUEST);

	print "<xmp>";
	print_r ($_REQUEST);
	print "</xmp>";

	pglib_transaction("BEGIN");

	if (isset($blocked)) {
		foreach ($blocked as $stkid) {
			$sql = "UPDATE cubit.stock SET blocked='y' WHERE stkid='$stkid'";
			db_exec($sql) or errDie("Unable to update stock.");
		}
	}

	if (isset($count)) {
		foreach ($count as $stkid=>$value) {
			$sql = "UPDATE cubit.stock SET units='$value' WHERE stkid='$stkid'";
			db_exec($sql) or errDie("Unable to update stock.");
		}
	}

	pglib_transaction("COMMIT");

	return display();
}
<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
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

$OUTPUT .= mkQuickLinks(
	ql("cust_basis.php", "Customer Basis Prices")
);

require ("../template.php");

function display($msg="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract($fields, EXTR_SKIP);

	$sql = "SELECT assets.id, des, locat, per_day, per_hour, per_week, serial
				FROM cubit.assets
					LEFT JOIN hire.basis_prices
						ON assets.id = basis_prices.assetid
				WHERE remaction IS NULL AND (assets.id ILIKE '$search%' OR
					des ILIKE '$search%' OR locat ILIKE '$search%' OR
					per_day ILIKE '%$search%' OR per_hour ILIKE '%$search%' OR
					per_week ILIKE '%$search%' OR serial ILIKE '$search%')
				ORDER BY des ASC";
	$asset_rslt = db_exec($sql);

	$basis_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (empty($asset_data["per_hour"])) $asset_data["per_hour"] = "0.00";
		if (empty($asset_data["per_day"])) $asset_data["per_day"] = "0.00";
		if (empty($asset_data["per_week"])) $asset_data["per_week"] = "0.00";

		$basis_out .= "
		<input type='hidden' name='asset[]' value='$asset_data[id]' />
		<tr bgcolor='".bgcolorg()."'>
			<td>$asset_data[des]</td>
			<td>$asset_data[serial]</td>
			<td>$asset_data[locat]</td>
			<td>
				".CUR."
				<input type='text' name='hour[$asset_data[id]]'
				value='".sprint($asset_data["per_hour"])."' size='5'
				style='text-align: right' />
			</td>
			<td>
				".CUR."
				<input type='text' name='day[$asset_data[id]]'
				value='".sprint($asset_data["per_day"])."' size='5'
				style='text-align: right' />
			</td>
			<td>
				".CUR."
				<input type='text' name='week[$asset_data[id]]'
				value='".sprint($asset_data["per_week"])."' size='5'
				style='text-align: right' />
			</td>
		</tr>";
	}

	if (empty($basis_out)) {
		$basis_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Default Basis Prices</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='save' />
	<table ".TMPL_tblDflts.">
		$msg
		<tr>
			<th>Plant</th>
			<th>Serial</th>
			<th>Location</th>
			<th>Hour</th>
			<th>Day</th>
			<th>Week</th>
		</tr>
		$basis_out
		<tr>
			<td colspan='6' align='center'>
				<input type='submit' value='Set Basis &raquo'
				style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function save()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");
	if (isset($asset)) {
		foreach ($asset as $id) {
			$sql = "SELECT id FROM hire.basis_prices WHERE assetid='$id'";
			$bp_rslt = db_exec($sql) or errDie("Unable to retrieve basis.");

			if (pg_num_rows($bp_rslt)) {
				$bp_id = pg_fetch_result($bp_rslt, 0);

				$sql = "UPDATE hire.basis_prices SET per_hour='$hour[$id]',
							per_day='$day[$id]', per_week='$week[$id]'
							WHERE id='$bp_id'";
			} else {
				$sql = "INSERT INTO hire.basis_prices (assetid, per_hour,
							per_day, per_week)
							VALUES ('$id', '$hour[$id]', '$day[$id]',
								'$week[$id]')";
			}
			db_exec($sql) or errDie("Unable to update basis prices.");
		}
	}
	pglib_transaction("COMMIT");

	$msg = "<tr bgcolor='".bgcolorg()."'>
		<td colspan='6'><li>Successfully saved the default basis prices.</li>
	</tr>";

	return display($msg);
}
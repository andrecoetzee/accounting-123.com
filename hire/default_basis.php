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
	$fields["search"] = "[_BLANK_]";

	extract($fields, EXTR_SKIP);

	$sql = "SELECT assets.id, des, locat, per_day, per_hour, per_week,
				per_month, serial, default_basis
				FROM cubit.assets
					LEFT JOIN hire.basis_prices
						ON assets.id = basis_prices.assetid
				WHERE remaction IS NULL AND (
					des ILIKE '$search%' OR serial ILIKE '%$search%')
				ORDER BY des ASC";
	$asset_rslt = db_exec($sql);

	if ($search == "[_BLANK_]") {
		$n_msg = "Please type first few letters of the asset's name or serial";
		$search = "";
	} else {
		$n_msg = "No results found";
	}

	$basis_types = array(
		"per_hour"=>"Hourly",
		"per_day"=>"Daily",
		"per_week"=>"Weekly",
		"per_month"=>"Monthly"
	);

	$basis_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (empty($asset_data["per_hour"])) $asset_data["per_hour"] = "0.00";
		if (empty($asset_data["per_day"])) $asset_data["per_day"] = "0.00";
		if (empty($asset_data["per_week"])) $asset_data["per_week"] = "0.00";
		if (empty($asset_data["per_month"])) $asset_data["per_month"] = "0.00";
	
		if (empty($asset_data["default_basis"])) {
			$asset_data["default_basis"] = "per_day";
		}

		$types_sel = "<select name='default_basis[$asset_data[id]]'>";
		foreach ($basis_types as $key=>$value) {
			if ($key == $asset_data["default_basis"]) {
				$sel = "selected='t'";
			} else {
				$sel = "";
			}
			$types_sel .= "<option value='$key' $sel>$value</option>";
		}
		$types_sel .= "</select>";
	
		$basis_out .= "
		<input type='hidden' name='asset[]' value='$asset_data[id]' />
		<tr class='".bg_class()."'>
			<td>$types_sel</td>
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
			<td>
				".CUR."
				<input type='text' name='month[$asset_data[id]]'
				value='".sprint($asset_data["per_month"])."' size='5'
				style='text-align: right' />
			</td>
		</tr>";
	}

	if (empty($basis_out)) {
		$basis_out = "
		<tr class='".bg_class()."'>
			<td colspan='8'><li>$n_msg</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Default Basis Prices</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
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
			<th>Default Basis</th>
			<th>Plant</th>
			<th>Serial</th>
			<th>Location</th>
			<th>Hour</th>
			<th>Day</th>
			<th>Week</th>
			<th>Month</th>
		</tr>
		$basis_out
		<tr>
			<td colspan='8' align='center'>
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
			$sql = "
			SELECT id, per_day, per_hour, per_week, per_month
			FROM hire.basis_prices WHERE assetid='$id'";
			$bp_rslt = db_exec($sql) or errDie("Unable to retrieve basis.");
			$bp_data = pg_fetch_array($bp_rslt);

			if (pg_num_rows($bp_rslt)) {
				$bp_id = $bp_data["id"];

				$sql = "
				UPDATE hire.basis_prices SET per_hour='$hour[$id]',
					per_day='$day[$id]', per_week='$week[$id]',
					per_month='$month[$id]', default_basis='$default_basis[$id]'
				WHERE id='$bp_id'";
			} else {
				$sql = "
				INSERT INTO hire.basis_prices (assetid, per_hour, per_day,
					per_week, per_month, default_basis)
					VALUES ('$id', '$hour[$id]', '$day[$id]', '$week[$id]',
						'$month[$id]', '$default_basis[$id]')";
			}
			db_exec($sql) or errDie("Unable to update basis prices.");
			
			$sql = "
			SELECT id, hour, day, week FROM hire.cust_basis
			WHERE asset_id='$id'";
			$cb_rslt = db_exec($sql)
				or errDie("Unable to retrieve customer basis.");
			while (list($cb_id, $cb_hour, $cb_day, $cb_week) = pg_fetch_array($cb_rslt)) {
				
				if ($cb_hour == $bp_data["per_hour"]) {
					$n_hour = $hour[$id];
				} else {
					$n_hour = $cb_hour;
				}
				
				if ($cb_day == $bp_data["per_day"]) {
					$n_day = $day[$id];
				} else {
					$n_day = $cb_day;
				}
				
				if ($cb_week == $bp_data["per_week"]) {
					$n_week = $week[$id];
				} else {
					$n_week = $cb_week;
				}
				
				$sql = "
				UPDATE hire.cust_basis SET hour='$n_hour', day='$n_day',
					week='$n_week'
				WHERE id='$cb_id'";
				db_exec($sql) or errDie("Unable to update customer basis.");
			}
		}
	}
	pglib_transaction("COMMIT");

	$msg = "<tr class='".bg_class()."'>
		<td colspan='8'><li class='yay'>Successfully saved the default basis prices.</li>
	</tr>";

	return display($msg);
}

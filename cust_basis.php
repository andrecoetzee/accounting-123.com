<?php

require ("../settings.php");

error_reporting(E_ALL);

if (isset($_REQUEST["key"])) {
	$key = $_REQUEST["key"];
} else {
	$key = "";
}

if (!isset($_REQUEST["cust_id"])) {
	$key = "cust_slct";
}

switch ($key) {
	default:
	case "cust_slct":
		$OUTPUT = cust_slct();
		break;
	case "basis":
		$OUTPUT = basis();
		break;
	case "save":
		$OUTPUT = save();
		break;
}

$OUTPUT .= mkQuickLinks(
	ql("default_basis.php", "Default Basis Prices")
);

require ("../template.php");

function cust_slct($message="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["cust_search"] = "";

	extract ($fields, EXTR_SKIP);

	$OUTPUT = "<center>
	<h3>Basis</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		$message
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='cust_search' value='$cust_search' /></td>
			<td><input type='submit' value='Search &raquo' /></td>
		</tr>
	</table>
	</form>";

	// Retrieve customers;
	$sql = "SELECT * FROM cubit.customers
			WHERE surname ILIKE '%$cust_search%' OR cusname ILIKE '%$cust_search%'
			ORDER BY surname, cusname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	$cust_out = "";
	$i = 0;
	while ($cust_data = pg_fetch_array($cust_rslt)) {
		if (!$i) {
			$cust_out .= "<tr>";
		}

		$cust_out .= "<td class='".bg_class()."' align='center'>
			<a href='".SELF."?key=basis&cust_id=$cust_data[cusnum]'>
				$cust_data[surname] $cust_data[cusname]
			</a>
		</td>";

		if ($i == 4) {
			$cust_out .= "</tr>";
			$i = 0;
		}

		$i++;
	}

	if (empty($cust_out)) {
		$cust_out = "<tr class='".bg_class()."'>
			<td><li>No customers found.</li></td>
		</tr>";
	}

	$OUTPUT .= "<br />
	<table ".TMPL_tblDflts." width='80%'>
		<tr>
			<th colspan='5'>Select Customer</th>
		</tr>
		$cust_out
	</table>";

	return $OUTPUT;
}

function basis()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["asset_search"] = "";

	extract ($fields, EXTR_SKIP);

	// Retrieve assets
	$sql = "SELECT * FROM cubit.assets
			WHERE des ILIKE '%$asset_search%' OR serial ILIKE '%$asset_search%'
				OR locat ILIKE '%$asset_search%'
			ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$asset_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"])) {
			continue;
		}

		$sql = "SELECT * FROM hire.cust_basis
				WHERE cust_id='$cust_id' AND asset_id='$asset_data[id]'";
		$basis_rslt = db_exec($sql) or errDie("Unable to retrieve basis.");
		$basis_data = pg_fetch_array($basis_rslt);

		$sql = "SELECT * FROM hire.basis_prices WHERE assetid='$asset_data[id]'";
		$bp_rslt = db_exec($sql) or errDie("Unable to retrieve default basis.");
		$bp_data = pg_fetch_array($bp_rslt);

		if (!isset($day[$asset_data["id"]])) {
			$day[$asset_data["id"]] = sprint($basis_data["day"]);

			if (!(float)$day[$asset_data["id"]] && (float)$bp_data["per_day"]) {
				$day[$asset_data["id"]] = $bp_data["per_day"];
			}
		}
		if (!isset($hour[$asset_data["id"]])) {
			$hour[$asset_data["id"]] = sprint($basis_data["hour"]);

			if (!(float)$hour[$asset_data["id"]] && (float)$bp_data["per_hour"]) {
				$hour[$asset_data["id"]] = $bp_data["per_hour"];
			}
		}
		if (!isset($week[$asset_data["id"]])) {
			$week[$asset_data["id"]] = sprint($basis_data["week"]);

			if (!(float)$week[$asset_data["id"]] && (float)$bp_data["per_week"]) {
				 $week[$asset_data["id"]] = $bp_data["per_week"];
			}
		}

		$asset_out .= "<tr class='".bg_class()."'>
			<td>$asset_data[des]</td>
			<td>".getSerial($asset_data["id"])."</td>
			<td>$asset_data[locat]</td>
			<td>
				".CUR."
				<input type='text' name='hour[$asset_data[id]]'
				value='".sprint($hour[$asset_data["id"]])."' size='5'
				style='text-align: right' />
			</td>
			<td>
				".CUR."
				<input type='text' name='day[$asset_data[id]]'
				value='".sprint($day[$asset_data["id"]])."' size='5'
				style='text-align: right' />
			</td>
			<td>
				".CUR."
				<input type='text' name='week[$asset_data[id]]'
				value='".sprint($week[$asset_data["id"]])."' size='5'
				style='text-align: right' />
			</td>
		</tr>";
	}

	// Retrieve customer details
	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cust_id'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_data = pg_fetch_array($cust_rslt);

	$OUTPUT = "<center>
	<h3>Basis</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='cust_id' value='$cust_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='asset_search' value='$asset_search' /></td>
			<td><input type='submit' value='Search &raquo' /></td>
		</tr>
		<tr>
			<th colspan='2'>Customer</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				<a href='javascript:".
				"popupOpen(\"../cust-det.php?cusnum=$cust_data[cusnum]\");'>
					$cust_data[surname] $cust_data[cusname]
				</a>
			</td>
			<td>
				<a href='".SELF."'>Change</a>
			</td>
		</tr>
	</table>
	<p></p>
	</table>
	</form>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='save' />
	<input type='hidden' name='cust_id' value='$cust_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Plant</th>
			<th>Serial</th>
			<th>Location</th>
			<th>Hour</th>
			<th>Day</th>
			<th>Week</th>
		</tr>
		$asset_out
		<tr>
			<td colspan='6' align='center'>
				<input type='submit' value='Set Basis &raquo'
				style='font-size: 1.1em; font-weight: bold;' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function save()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	foreach ($hour as $asset_id=>$value) {
		$sql = "SELECT * FROM hire.cust_basis
				WHERE cust_id='$cust_id' AND asset_id='$asset_id'";
		$cb_rslt = db_exec($sql) or errDie("Unable to check basis.");
		$cb_data = pg_fetch_array($cb_rslt);

		if (pg_num_rows($cb_rslt)) {
			$sql = "UPDATE hire.cust_basis
					SET hour='$hour[$asset_id]', day='$day[$asset_id]',
						week='$week[$asset_id]'
					WHERE id='$cb_data[id]'";
			db_exec($sql) or errDie("Unable to update basis.");
		} else {
			$sql = "INSERT INTO hire.cust_basis (cust_id, asset_id, hour, day, week)
					VALUES ('$cust_id', '$asset_id', '$hour[$asset_id]',
						'$day[$asset_id]', '$week[$asset_id]')";
			db_exec($sql) or errDie("Unable to add basis");
		}
	}

	pglib_transaction("COMMIT");

	$message = "<tr class='".bg_class()."'>
		<td colspan='2'><li>Successfully saved customer basis</li></td>
	</tr>";

	return cust_slct($message);
}
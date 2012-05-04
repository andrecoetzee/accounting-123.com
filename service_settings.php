<?php

require ("../settings.php");

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

require ("../template.php");

function display($message="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$description = array();

	$OUTPUT = "<center>
	<h3>Service Settings</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search &raquo' /></td>
		</tr>
	</table>
	</form>";

	// Retrieve assets
	$sql = "SELECT * FROM cubit.assets
			WHERE des ILIKE '%$search%' OR serial ILIKE '%$search%'
				OR locat ILIKE '%$search%'
			ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$service_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"])) {
			continue;
		}

		if (!isset($description[$asset_data["id"]])) {
			$description[$asset_data["id"]] = "";
		}

		$service_out .= "<tr class='".bg_class()."'>
			<td>$asset_data[des]</td>
			<td>".getSerial($asset_data["id"])."</td>
			<td>$asset_data[locat]</td>
			<td>
				<input type='text' name='days[".$asset_data["id"]."]'
				value='$asset_data[days]' size='3'
				style='text-align: center' />
			</td>
			<td>
				<input type='text' name='description[$asset_data[id]]'
				value='".$description[$asset_data["id"]]."' />
			</td>
		</tr>";
	}

	if (empty($service_out)) {
		$service_out = "<tr class='".bg_class()."'>
			<td colspan='5'><li>No service days found.</li></td>
		</tr>";
	}

	$OUTPUT .= "<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='save' />
	<table ".TMPL_tblDflts.">
		<tr class='".bg_class()."'>
			<td colspan='5'><li>Amount of hire days before warning.</li></td>
		</tr>
		<tr>
			<td colspan='5'>$message</td>
		</tr>
		<tr>
			<th>Description</th>
			<th>Serial</th>
			<th>Location</th>
			<th>Days</th>
			<th>Description</th>
		</tr>
		$service_out
		<tr>
			<td colspan='5' align='center'>
				<input type='submit' value='Save &raquo' />
			</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function save()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	foreach ($days as $asset_id=>$days) {
		$sql = "UPDATE cubit.assets SET days='$days' WHERE id='$asset_id'";
		db_exec($sql) or errDie("Unable to update service days.");

		$secs = $days * 60 * 60 * 24;
		$svdate = date("Y-m-d", (time() + $days));

		$sql = "SELECT * FROM cubit.asset_svdates
				WHERE asset_id='$asset_id'";
		$svdate_rslt = db_exec($sql) or errDie("Unable to retrieve days.");

		if (!pg_num_rows($svdate_rslt)) {
			$sql = "INSERT INTO cubit.asset_svdates (asset_id, svdate, des)
					VALUES ('$asset_id', '$svdate', '$description[$asset_id]')";
		} else {
			$sv_data = pg_fetch_array($svdate_rslt);

			$sql = "UPDATE cubit.asset_svdates SET svdate='$svdate',
						description='$description[$asset_id]'
					WHERE id='$sv_data[id]'";
		}
	}
	db_exec($sql) or errDie("Unable to update service history.");

	pglib_transaction("COMMIT");

	$message = "<li class='yay'>
					Successfully saved service days.
				</li>";

	return display($message);
}

?>

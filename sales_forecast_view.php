<?php

require ("settings.php");

$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("sales_forecast.php", "Sales Forecast"),
	ql("sales_forecast_pit.php", "Sales Forecast - Point in Time")
);

require ("template.php");

function display()
{
	extract($_REQUEST);

	$sql = "SELECT forecasts.id, prd, prd_val, inc_perc, dec_perc, username,
				extract('epoch' FROM timestamp) AS e_time
			FROM cubit.forecasts
				LEFT JOIN cubit.users ON forecasts.user_id = users.userid
			ORDER BY timestamp DESC";
	$fc_rslt = db_exec($sql) or errDie("Unable to retrieve sales forecast.");

	$fc_out = "";
	while ($fc_data = pg_fetch_array($fc_rslt)) {
		if ($fc_data["prd"] == "monthly") {
			$fc_prd = "Month";
		} else {
			$fc_prd = "Week";
		}

		$fc_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>".date("d-m-Y G:i:s", $fc_data["e_time"])."</td>
			<td>$fc_data[username]</td>
			<td>$fc_prd: $fc_data[prd_val]</td>
			<td>$fc_data[inc_perc]%</td>
			<td>$fc_data[dec_perc]%</td>
			<td>
				<a href='sales_forecast.php?forecast_id=$fc_data[id]'>View</a>
			</td>
			<td>Compare</td>
			<td>
				<a href='sales_forecast_remove.php?forecast_id=$fc_data[id]'>
					Remove
				</a>
			</td>
		</tr>";
	}

	if (empty($fc_out)) {
		$fc_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'><li>No saved sales forecasts found</li></td>";
	}

	$OUTPUT = "<center>
	<h3>View Saved Sales Forecasts</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Created</th>
			<th>User</th>
			<th>Period</th>
			<th>Increase</th>
			<th>Decrease</th>
			<th colspan='3'>Options</th>
		</tr>
		$fc_out
	</table>";

	return $OUTPUT;
}
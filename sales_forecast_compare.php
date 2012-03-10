<?php

require ("settings.php");
require ("sales_forecast.lib.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "compare":
			$OUTPUT = compare();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("template.php");

function enter()
{
	extract($_REQUEST);

	$fields = array();
	$fields["prd"] = "month";

	extract ($fields, EXTR_SKIP);

	if ($prd == "month") {
		$prd_month = "checked";
		$prd_week = "";
	} else {
		$prd_month = "";
		$prd_week = "checked";
	}

	// Retrieve list of sales forecasts
	$sql = "SELECT forecasts.id, prd, prd_val, inc_perc, dec_perc, timestamp,
				username, extract('epoch' FROM timestamp) AS e_time
			FROM cubit.forecasts
				LEFT JOIN cubit.users ON forecasts.user_id = users.userid
			ORDER BY timestamp DESC";
	$fc_rslt = db_exec($sql) or errDie("Unable to retrieve sales forecasts.");

	$fc_sel = "<select name='forecast_id'
				onchange='javascript:document.form.submit()'>
					<option value='0'>[None]</option>";
	while ($fc_data = pg_fetch_array($fc_rslt)) {
		if ($fc_data["prd"] == "monthly") {
			$fc_prd = "Month";
		} else {
			$fc_prd = "Week";
		}

		$fc_sel .= "<option value='$fc_data[id]'>
			[Created: ".date("d-m-Y G:i:s", $fc_data["e_time"])."]
			[$fc_prd: $fc_data[prd_val]]
			[Increase: $fc_data[inc_perc]%]
			[Decrease: $fc_data[dec_perc]%]
		</option>";
	}
	$fc_sel .= "</select>";

	$OUTPUT = "<center>
	<h3>Compare Sales Forecasts</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='compare' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Saved Sales Forecast</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>$fc_sel</td>

			<td rowspan='3'>
				<input type='submit' value='Compare &raquo'
				style='width: 100%; height: 100%; font-weight: bold' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Compare With</td>
			<td>
				Last 7 Days
				<input type='radio' name='prd' value='week' $prd_week />
			</td>
			<td>
				Last 30 Days
				<input type='radio' name='prd' value='month' $prd_month />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><table ".TMPL_tblDflts." width='100%'>
				<tr>
					<td><b>Increase</b></td>
					<td>
						+<input type='text' name='inc_perc' value='0' size='5'>%
					</td>
					<td><b>Decrease</b></td>
					<td>
						-<input type='text' name='dec_perc' value='0' size='5'>%
					</td>
				</tr>
			</td></table>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}

function compare()
{
	extract($_REQUEST);

	if ($prd == "week") {
		$dates = lastWeekDates();
	} else {
		$dates = lastMonthDates();
	}

	$start_date = $dates["start_date"];
	$end_date = $dates["end_date"];

	// Store headings
	$sql = "SELECT whid, whname FROM exten.warehouses ORDER BY whname ASC";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");

	$stores_th_lv1 = $stores_th_lv2 = "";
	while ($wh_data = pg_fetch_array($wh_rslt)) {
		$stores_th_lv1 .= "<th colspan='2'>$wh_data[whname]</th>";
		$stores_th_lv2 .= "<th>Actual</th><th>Projected</th>";
	}

	// Retrieve unique stock
	$sql = "SELECT DISTINCT(stkcod) FROM cubit.stock ORDER BY stkcod ASC";
	$stkcod_rslt = db_exec($sql) or errDie("Unable to retrieve stock codes.");

	$stock_out = "";
	while ($stkcod = pg_fetch_array($stkcod_rslt)) {
		$stkcod = $stkcod["stkcod"];

		$sql = "SELECT stkdes FROM cubit.stock
				WHERE stkcod='$stkcod'";
		$stkdes_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stkdes = pg_fetch_result($stkdes_rslt, 0);

		$stock_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$stkcod</td>
			<td>$stkdes</td>
			<td>
				".totalActual($stkcod, $start_date, $end_date, $forecast_id)."
			</td>
			<td>
				".totalProjected($stkcod, $inc_perc, $dec_perc, $start_date,
								 $end_date, $forecast_id)."
			</td>";

		pg_result_seek($wh_rslt, 0);
		while ($wh_data = pg_fetch_array($wh_rslt)) {
			$sql = "SELECT stkid, units FROM cubit.stock
					WHERE stkcod='$stkcod' AND whid='$wh_data[whid]'";
			$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
			$stock_data = pg_fetch_array($stock_rslt);

			$stkid = $stock_data["stkid"];

			// Don't go beyond this point unless the stock exists in this store
			if (empty($stkid)) {
				$stock_out .= "<td>0.00</td><td>0.00</td>";
				continue;
			}

			$sql = "SELECT actual, projected FROM cubit.forecast_items
					WHERE stkid='$stkid' AND forecast_id='$forecast_id'";
			$fci_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
			$fci_data = pg_fetch_array($fci_rslt);

			// Total sales for the selected period
			$current_actual = actualSales($stkid, $start_date, $end_date,
										  $forecast_id);
			$current_projected = projectedSales($stkid, $inc_perc, $dec_perc,
												$start_date, $end_date,
												$forecast_id);

			if (empty($current_actual)) $current_actual = "0.00";
			if (empty($current_projected)) $current_projected = "0.00";
			if (empty($fci_data["actual"])) $fci_data["actual"] = "0.00";
			if (empty($fci_data["projected"])) $fci_data["projected"] = "0.00";

			$actual_sales = sprint($current_actual - $fci_data["actual"]);
			$projected_sales = sprint($current_projected - $fci_data["projected"]);

			if ($actual_sales > 0) {
				$actual_color = "green";
			} elseif ($actual_sales < 0) {
				$actual_color = "red";
			} else {
				$actual_color = "black";
			}

			if ($projected_sales > 0) {
				$projected_color = "green";
			} elseif ($projected_sales < 0) {
				$projected_color = "red";
			} else {
				$projected_color = "black";
			}

			$stock_out .= "
				<input type='hidden' name='stkid[]' value='$stkid' />
				<td>
					<input type='hidden' name='actual_sales[$stkid]'
					value='$actual_sales' />
					<span style='color: $actual_color'>$actual_sales</span>
				</td>
				<td>
					<input type='hidden' name='projected_sales[$stkid]'
					value='$projected_sales' />
					<span style='color: $projected_color'>$projected_sales</span>
				</td>";
		}
	}

	$OUTPUT = "
	<center>
	<h3>Compare Sales Forecast</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th rowspan='2'>Stock Code</th>
			<th rowspan='2'>Stock Item</th>
			<th colspan='2'>Total</th>
			$stores_th_lv1
		</tr>
		<tr>
			<th>Actual</th>
			<th>Projected</th>
			$stores_th_lv2
		</tr>
		$stock_out
		<tr>
	</table>
	</center>";

	return $OUTPUT;
}

function lastWeekDates()
{
	$days = 60 * 60 * 24;

	$start_time = time() - ($days * 7);
	$end_time = time();

	$start_date = date("Y-m-d", $start_time);
	$end_date = date("Y-m-d", $end_time);

	$dates = array("start_date"=>$start_date, "end_date"=>$end_date);

	return $dates;
}

function lastMonthDates()
{
	$days = 60 * 60 * 24;

	$start_time = time() - ($days * 30);
	$end_time = time();

	$start_date = date("Y-m-d", $start_time);
	$end_date = date("Y-m-d", $end_time);

	$dates = array("start_date"=>$start_date, "end_date"=>$end_date);

	return $dates;
}
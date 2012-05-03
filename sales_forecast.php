<?php

require ("settings.php");
require ("sales_forecast.lib.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "save":
			$OUTPUT = saveReport();
			break;
	}
} else {
	$OUTPUT = display();
}

$OUTPUT .=
	mkQuickLinks(
		ql("sales_forecast_pit.php", "Point in Time - Sales Forecast"),
		ql("sales_forecast_view.php", "View Saved Sales Forecasts"),
		ql("stock-view.php", "View Stock"),
		ql("stock-search.php", "Search Stock")
	);

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["prd"] = "monthly";
	$fields["prd_val"] = date("m");
	$fields["inc_perc"] = "0";
	$fields["dec_perc"] = "0";
	$fields["forecast_id"] = "0";

	extract($fields, EXTR_SKIP);

	// Report settings --------------------------------------------------------

	$sql = "SELECT *, extract('epoch' FROM timestamp) AS e_time FROM cubit.forecasts ORDER BY timestamp DESC";
	$fc_rslt = db_exec($sql) or errDie("Unable to retrieve forecast.");

	$forecast_sel = "<select name='forecast_id'
					 onchange='javascript:document.form.submit()'
					 style='width: 100%'>
						<option value='0'>[None]</option>";
	while ($fc_data = pg_fetch_array($fc_rslt)) {
		if ($fc_data["id"] == $forecast_id) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		if ($fc_data["prd"] == "monthly") {
			$fc_prd = "Month";
		} else {
			$fc_prd = "Week";
		}

		$forecast_sel .= "
			<option value='$fc_data[id]' $sel>
				[Created: ".date("d-m-Y G:i:s", $fc_data["e_time"])."]
				[$fc_prd: $fc_data[prd_val]]
				[Increase: $fc_data[inc_perc]%]
				[Decrease: $fc_data[dec_perc]%]
			</option>";
	}

	if (!$forecast_id) {
		$forecast_id = "new";

		// Months
		if ($prd == "monthly") {
			$prd_monthly = "checked='checked'";
			$prd_weekly = "";

			// Months dropdown
			$prd_sel = "Month:
			<select name='prd_val' onchange='javascript:document.form.submit()'>";
			for ($i = 1; $i <= 12; $i++) {
				if ($prd_val == $i) {
					$sel = "selected='selected'";
				} else {
					$sel = "";
				}

				$prd_sel .= "<option value='$i' $sel>$i</option>";
			}
			$prd_sel .= "</select>";

			$dates = monthToDates($prd_val);

		// Weeks
		} else {
			$prd_monthly = "";
			$prd_weekly = "checked='checked'";

			// Weeks dropdown
			$prd_sel = "Week:
			<select name='prd_val' onchange='javascript:document.form.submit()'>";
			for ($i = 1; $i <= date("W", mktime(0,0,0,12,31,date("Y"))); $i++) {
				if ($prd_val == $i) {
					$sel = "selected='selected'";
				} else {
					$sel = "";
				}

				$prd_sel .= "<option value='$i' $sel>$i</option>";
			}
			$prd_sel .= "</select>";

			$dates = weekToDates($prd_val);
		}

	//	$start_date = lastYear($dates["start"]);
	//	$end_date = lastYear($dates["end"]);

		$start_date = $dates["start"];
		$end_date = $dates["end"];

		$OUTPUT = "<center>
		<h3>Sales Forecast</h3>
		<form method='post' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='7'>Report Settings</th>
			</tr>
			<tr class='".bg_class()."'>
				<!-- COL 1 -->
				<td>
					<input type='radio' name='prd' value='weekly' $prd_weekly
					onchange='javascript:document.form.submit()' />
				</td>
				<td>Weekly</td>

				<td>
					<input type='radio' name='prd' value='monthly' $prd_monthly
					onchange='javascript:document.form.submit()' />
				</td>
				<td>Monthly</td>

				<!-- COL 2 -->
				<td class='".bg_class()."'>&nbsp;</td>

				<!-- COL 3 -->
				<th>Increase</th>
				<th>Decrease</th>
			</tr>
			<tr class='".bg_class()."'>
				<!-- COL 1 -->
				<td colspan='4' align='center'>$prd_sel</td>

				<!-- COL 2 -->
				<td class='".bg_class()."'>&nbsp;</td>

				<!-- COL 3 -->
				<td>
					<span style='font-weight: bold'>+</span>
					<input type='text' name='inc_perc' value='$inc_perc' size='4'
					style='text-align: center' />
					<span style='font-weight: bold'>%</span>
				</td>
				<td>
					<span style='font-weight: bold'>-</span>
					<input type='text' name='dec_perc' value='$dec_perc' size='4'
					style='text-align: center' />
					<span style='font-weight: bold'>%</span>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<!-- COL 1 -->
				<td colspan='4'>$start_date <b> To </b> $end_date</td>

				<!-- COL 2 -->
				<td class='".bg_class()."'>&nbsp;</td>

				<!-- COL 3 -->
				<td colspan='2' align='center'>
					<input type='submit' value='Apply &raquo'
					style='width: 100%' />
				</td>
			</tr>
			<tr><th colspan='7'>Saved Sales Forecast</th></tr>
			<tr class='".bg_class()."'>
				<td colspan='7'>
					If a saved sales forecast has been chosen, all other<br />
					settings for this report will be ignored.
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='7'>$forecast_sel</td>
			</tr>
		</table>
		</form>";

		$save_btn = "<input type='submit' value='Save' />";
	} else {
		$start_date = NULL;
		$end_date = NULL;

		$OUTPUT = "<center>";
		$save_btn = "";
	}

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

		$stock_out .= "<tr class='".bg_class()."'>
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

			// Total sales for the selected period
			$actual_sales = actualSales($stkid, $start_date, $end_date,
										$forecast_id);
			$projected_sales = projectedSales($stkid, $inc_perc, $dec_perc,
											  $start_date, $end_date,
											  $forecast_id);

			$stock_out .= "
				<input type='hidden' name='stkid[]' value='$stkid' />
				<td>
					<input type='hidden' name='actual_sales[$stkid]'
					value='$actual_sales' />
					$actual_sales
				</td>
				<td>
					<input type='hidden' name='projected_sales[$stkid]'
					value='$projected_sales' />
					$projected_sales
				</td>";
		}
	}

	$OUTPUT .= "
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='save' />
	<input type='hidden' name='prd' value='$prd' />
	<input type='hidden' name='prd_val' value='$prd_val' />
	<input type='hidden' name='inc_perc' value='$inc_perc' />
	<input type='hidden' name='dec_perc' value='$dec_perc' />
	$save_btn
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
	$save_btn
	</form>";

	return $OUTPUT;
}

function saveReport()
{
	extract($_REQUEST);

	pglib_transaction("BEGIN");

	$sql = "INSERT INTO cubit.forecasts (prd, prd_val, inc_perc, dec_perc,
				user_id)
			VALUES ('$prd', '$prd_val', '$inc_perc', '$dec_perc', '".USER_ID."')";
	db_exec($sql) or errDie("Unable to save sales forecast.");
	$forecast_id = pglib_lastid("cubit.forecasts", "id");

	foreach ($stkid as $value) {
		$sql = "INSERT INTO cubit.forecast_items (forecast_id, stkid, actual,
					projected)
				VALUES ('$forecast_id', '$value', '$actual_sales[$value]',
					'$projected_sales[$value]')";
		db_exec($sql) or errDie("Unable to save sales forecast.");
	}

	pglib_transaction("COMMIT");

	return display();
}

// ext.lib.php candidates -----------------------------------------------------
function stkidToWhid($stkid)
{
	$sql = "SELECT whid FROM cubit.stock WHERE stkid='$stkid'";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$whid = pg_fetch_result($wh_rslt);

	return $whid;
}

function weekToDates($week_num)
{
	$days = 60 * 60 * 24;
	$weeks = $days * 7;

	$start_week = mktime(0, 0, 0, 1, 1, date("Y"));
	$end_week = mktime(0, 0, 0, 12, 31, date("Y"));

	for ($i = $start_week, $j = 0; $i <= $end_week; $i += $weeks, $j++) {
		if ($j == $week_num) {
			$day_of_week = date("w", $i);

			$start_time = $i;
			$start_time -= ($days * 7);

			$end_time = $i;
			$end_time += ($weeks * $day_of_week);

			$start_date = date("Y-m-d", $start_time);
			$end_date = date("Y-m-d", $end_time);

			$dates = array("start"=>$start_date, "end"=>$end_date);

			return $dates;
		}
	}

	return false;
}

function lastYear($date)
{
	$date = explode("-", $date);
	$date[0]--;

	$date = "$date[0]-$date[1]-$date[2]";

	return $date;
}

function monthToDates($month_num)
{
	$start_time = mktime(0, 0, 0, $month_num, 1, date("Y"));
	$end_time = mktime(0, 0, 0, $month_num, date("t", $start_time), date("Y"));

	$start_date = date("Y-m-d", $start_time);
	$end_date = date("Y-m-d", $end_time);

	$dates = array("start"=>$start_date, "end"=>$end_date);
	return $dates;

}


?>
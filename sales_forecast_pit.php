<?php

require ("settings.php");
require ("sales_forecast.lib.php");

$OUTPUT = display();

$OUTPUT .=
	mkQuickLinks(
		ql("sales_forecast.php", "Sales Forecast"),
		ql("sales_forecast_view.php", "View Saved Sales Forecasts"),
		ql("stock-view.php", "View Stock"),
		ql("stock-search.php", "Search Stock")
	);

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["inc_perc"] = 0;
	$fields["dec_perc"] = 0;

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$OUTPUT = "<center>
	<h3>Point in Time Sales Forecast</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='3'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
		</tr>
		<tr>
			<th>Increase</th>
			<th>&nbsp;</th>
			<th>Decrease</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>
				<span style='font-weight: bold'>+</span>
				<input type='text' name='inc_perc' value='$inc_perc' size='4'
				style='text-align: center' />
				<span style='font-weight: bold'>%</span>
			</td>
			<td>&nbsp;</td>
			<td align='center'>
				<span style='font-weight: bold'>-</span>
				<input type='text' name='dec_perc' value='$dec_perc' size='4'
				style='text-align: center' />
				<span style='font-weight: bold'>%</span>
			</td>
		</tr>
		<tr>
			<td colspan='3' align='center'>
				<input type='submit' value='Apply' />
			</td>
		</tr>
	</table>
	</form>";

	$sql = "SELECT whid, whname FROM exten.warehouses ORDER BY whname ASC";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");

	$stores_th_lv1 = $stores_th_lv2 = "";
	while ($wh_data = pg_fetch_array($wh_rslt)) {
		$stores_th_lv1 .= "<th colspan='2'>$wh_data[whname]</th>";
		$stores_th_lv2 .= "<th>Actual</th><th>Average<br>per Week</th>";
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
				".totalActual($stkcod, $from_date, $to_date)."
			</td>
			<td>
				<!--".totalProjected($stkcod, $inc_perc, $dec_perc, $from_date,
								 $to_date)."-->
				".totalWeekAverages($stkcod, $from_date, $to_date)."
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
			$actual_sales = actualSales($stkid, $from_date, $to_date);
			$projected_sales = projectedSales($stkid, $inc_perc, $dec_perc,
											  $from_date, $to_date);

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
					<!--$projected_sales-->
					".weekAverages($stkid, $from_date, $to_date)."
				</td>";
		}
	}

	$OUTPUT .= "
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th rowspan='2'>Stock Code</th>
			<th rowspan='2'>Stock Item</th>
			<th colspan='2'>Total</th>
			$stores_th_lv1
		</tr>
		<tr>
			<th>Actual</th>
			<th>Average<br>per Week</th>
			$stores_th_lv2
		</tr>
		$stock_out
		<tr>
	</table>
	</form>";

	return $OUTPUT;
}

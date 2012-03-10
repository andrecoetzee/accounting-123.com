<?php
function actualSales($stkid, $from_date, $to_date, $forecast="new")
{
	$inv_total = 0;
	if (!is_numeric($forecast)) {
			$sql = "SELECT sum(amt)
					FROM cubit.inv_items
						LEFT JOIN cubit.invoices
							ON inv_items.invid=invoices.invid
					WHERE printed='y' AND done='y' AND stkid='$stkid' AND
						odate BETWEEN '$from_date' AND '$to_date'";
			$inv_rslt = db_exec($sql)
				or errDie("Unable to retrieve invoice items.");
			$inv_total += pg_fetch_result($inv_rslt, 0);

			for ($i = 1; $i <= 12; $i++) {
				$sql = "SELECT sum(amt)
						FROM \"$i\".pinv_items
							LEFT JOIN \"$i\".pinvoices
								ON pinv_items.invid=pinvoices.invid
						WHERE printed='y' AND done='y' AND stkid='$stkid' AND
							odate BETWEEN '$from_date' AND '$to_date'";
				$pinv_rslt = db_exec($sql)
					or errDie("Unable to retrieve invoice items.");
				$inv_total += pg_fetch_result($pinv_rslt, 0);
			}
	} else {
		// Load a saved forecast
		$sql = "SELECT actual FROM cubit.forecast_items
				WHERE stkid='$stkid' AND forecast_id='$forecast'";
		$actual_rslt = db_exec($sql) or errDie("Cannot retrieve forecast.");
		$inv_total = pg_fetch_result($actual_rslt, 0);
	}
	if (empty($inv_total)) {
		$inv_total = 0.00;
	}

	return sprint($inv_total);
}

function projectedSales($stkid, $inc_perc, $dec_perc, $from_date, $to_date,
						$forecast="new")
{
	$actual = actualSales($stkid, $from_date, $to_date, $forecast);
	$projected = $actual;

	if ($dec_perc) $projected -= (($dec_perc / 100) * $actual);
	if ($inc_perc) $projected += (($inc_perc / 100) * $actual);

	return sprint($projected);
}

function totalActual($stkcod, $from_date, $to_date, $forecast="new")
{
	$sql = "SELECT stkid FROM cubit.stock WHERE stkcod='$stkcod'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock");
	$total = 0;
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$total += actualSales($stock_data["stkid"], $from_date, $to_date,
							  $forecast);
	}

	return sprint($total);
}

function totalProjected($stkcod, $inc_perc, $dep_perc, $from_date, $to_date,
						$forecast="new")
{
	$sql = "SELECT stkid FROM cubit.stock WHERE stkcod='$stkcod'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock");
	$total = 0;
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$total += projectedSales($stock_data["stkid"], $inc_perc, $dep_perc,
								 $from_date, $to_date, $forecast);
	}

	return sprint($total);
}

function trimToWeek($from_date, $to_date)
{
	$days = 60 * 60 * 24;

	$from_arr = explode("-", $from_date);
	$from_time = mktime(0, 0, 0, $from_arr[1], $from_arr[2], $from_arr[0]);

	$to_arr = explode("-", $to_date);
	$to_time = mktime(0, 0, 0, $to_arr[1], $to_arr[2], $to_arr[0]);

	while (date("w", $from_time) > 0) {
		$from_time -= $days;
	}

	while (date("w", $to_time) < 6) {
		$to_time += $days;
	}

	$from_date = date("Y-m-d", $from_time);
	$to_date = date("Y-m-d", $to_time);
	$dates = array("start_date"=>$from_date, "end_date"=>$to_date);

	return $dates;
}

function weekCount($from_date, $to_date)
{
	$days = 60 * 60 * 24;
	$weeks = $days * 7;

	$from_arr = explode("-", $from_date);
	$from_time = mktime(0, 0, 0, $from_arr[1], $from_arr[2], $from_arr[0]);

	$to_arr = explode("-", $to_date);
	$to_time = mktime(0, 0, 0, $to_arr[1], $to_arr[2], $to_arr[0]);

	$time = $to_time - $from_time;

	return $time / $weeks;
}

function weekAverages($stkid, $from_date, $to_date)
{
	$amount = actualSales($stkid, $from_date, $to_date);

	$weeks = weekCount($from_date, $to_date);
	if ($amount && $weeks > 1) {
		$average = $amount / $weeks;
	} else {
		$average = $amount;
	}

	return sprint($average);
}

function totalWeekAverages($stkcod, $from_date, $to_date)
{
	$amount = totalActual($stkcod, $from_date, $to_date);

	$weeks = weekCount($from_date, $to_date);
	if ($amount && $weeks > 1) {
		$average = $amount / $weeks;
	} else {
		$average = $amount;
	}

	return sprint($average);
}
?>

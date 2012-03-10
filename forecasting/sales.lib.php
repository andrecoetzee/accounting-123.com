<?php

require("../settings.php");

define ("DAY_IN_SECS", 60 * 60 * 24);
define ("WEEK_IN_SECS", DAY_IN_SECS * 7);
define ("MONTH_IN_SECS", DAY_IN_SECS * 30);

$sql = "SELECT stkid, stkcod FROM cubit.stock ORDER BY stkcod ASC";
$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

while (list($stkid, $stkcod) = pg_fetch_array($stock_rslt)) {
	$avg = avgPerWeek($stkid, "2007-03-01", "2007-03-30");
	if (!$avg) continue;
	print "
	<table>
		<td>$stkcod</td>
		<td>$avg</td>
	</table>";
}

function avgPerWeek($stkid, $from_date, $to_date)
{
	$from_time = strtotime($from_date);
	$to_time = strtotime($to_date);

	$total_weeks = ($to_time - $from_time) / WEEK_IN_SECS;

	$total_sales = 0;
	$union = array();
	for ($i = 1; $i <= 14; $i++) {
		$union[] = "
		SELECT sum(qty) AS qty FROM \"$i\".inv_items
			LEFT JOIN \"$i\".invoices ON inv_items.invid=invoices.invid
		WHERE odate BETWEEN '$from_date' AND '$to_date' AND stkid='$stkid'
			AND done='y' AND printed='y'";
	}
	$union[] = "
	SELECT sum(qty) AS qty FROM cubit.inv_items
		LEFT JOIN cubit.invoices ON inv_items.invid=invoices.invid
	WHERE odate BETWEEN '$from_date' AND '$to_date' AND stkid='$stkid'
		AND done='y' AND printed='y'";
	$sql = implode(" UNION ", $union);
	$qty_rslt = db_exec($sql) or errDie("Unable to retrieve invoice items.");
	while ($qty_data = pg_fetch_array($qty_rslt)) {
		$total_sales += $qty_data["qty"];
	}

	if ($total_sales != 0 && $total_weeks != 0) {
		return $total_sales / $total_weeks;
	} else {
		return false;
	}
}

<?php

require ("settings.php");
require ("sales_forecast.lib.php");

require_lib("manufact");

$OUTPUT = display();

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

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "SELECT stkid, stkcod, stkdes, units, minlvl FROM cubit.stock";
	$stock_rslt = db_exec($sql) or errdie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		// Gather all the data we need to perform the calculations
		$qty_units = $stock_data["units"];
		$qty_min = $stock_data["minlvl"];
		$qty_sales = averageSalesQty($stock_data["stkid"], $from_date, $to_date, "YEARLY");
		$qty_ordered = qty_ordered($stock_data["stkid"], $from_date, $to_date);

		$total_qty = $qty_units + $qty_ordered - $qty_sales;

		if ($total_qty <= $qty_min) {
			$qty_suggest = (int)($qty_min - $total_qty);
		}

		// Nothing interesting to see here...
		if (!isset($qty_suggest) || !$qty_suggest) continue;

		$stock_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stock_data[stkcod]</td>
				<td>$stock_data[stkdes]</td>
				<td align='center'><b>$qty_suggest</b></td>
				<td>".orderDate($stock_data["stkid"], $from_date, $to_date)."</td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Recommended Order Date and Order Quantity Report</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr><th colspan='4'>Date Range</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b> To </b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td>
					<input type='submit' value='Apply' style='font-weight: bold' />
				</td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Suggested Order Qty</th>
				<th>Suggested Order Date</th>
			</tr>
			$stock_out
		</table>";
	return $OUTPUT;

}



function qty_min($stkid, $from_date, $to_date)
{

	$DAYS = 60 * 60 * 24;

	$from_ar = explode("-", $from_date);
	$from_time = mktime(0, 0, 0, $from_ar[1], $from_ar[2], $from_ar[0]);

	$to_ar = explode("-", $to_date);
	$to_time = mktime(0, 0, 0, $to_ar[1], $to_ar[2], $to_ar[0]);

	$days = ($to_time - $from_time) / $DAYS;

	if ($days >= 30) {
		$prd = "monthly";
	} elseif ($days >= 7) {
		$prd = "weekly";
	} else {
		$prd = "daily";
	}

	return averageSalesQty($stkid, $from_date, $to_date, $prd);

}




function qty_ordered($stkid, $from_date, $to_date)
{

	$from_ar = explode("-", $from_date);
	$from_time = mktime(0, 0, 0, $from_ar[1], $from_ar[2], $from_ar[0]);

	$to_ar = explode("-", $to_date);
	$to_time = mktime(0, 0, 0, $to_ar[1], $to_ar[2], $to_ar[0]);

	$sql = "SELECT id, pur_items.purid, pdate FROM cubit.pur_items
				LEFT JOIN cubit.purchases ON pur_items.purid = purchases.purid
				WHERE stkid='$stkid' AND received='n'";
	$pur_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");

	$pur_qty = 0;
	while ($pur_data = pg_fetch_array($pur_rslt)) {
		$exp_date = purExpectedDate($pur_data["purid"], "Y-m-d");
		$exp_ar = explode("-", $exp_date);
		$exp_time = mktime(0, 0, 0, $exp_ar[1], $exp_ar[2], $exp_ar[0]);

		if ($exp_time >= $from_time && $exp_time <= $to_time) {
			$sql = "SELECT iqty FROM cubit.pur_items WHERE id='$pur_data[id]'";
			$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
			$pur_qty += pg_fetch_result($qty_rslt, 0);
		}
	}

	return $pur_qty;

}




function orderDate($stkid, $from_date, $to_date)
{

	$sql = "SELECT minlvl FROM cubit.stock WHERE stkid='$stkid'";
	$min_rslt = db_exec($sql) or errDie("Unable to retrieve stock minimum stock level.");
	$min = pg_fetch_result($min_rslt, 0);
	
	if (empty($min)) $min = 0;
	
	$from_time = strtotime($from_date);
	$to_time = strtotime(date("Y-m-t", $from_time));
	
	$sales = averageSalesQty($stkid, date("Y-m-d", $from_time), date("Y-m-d", $to_time), "DAILY");
	if ($sales <= $min) {
		return date("Y-m-d");
	} else {
		$tmp_sales = $sales;
		$ret = 0;
		while ($tmp_sales > $min) {
			$to_time = $from_time * (1 * (60 * 60 * 24));
			$tmp_sales = averageSalesQty($stkid, date("Y-m-d", $from_time), date("Y-m-d", $to_time), "MONTHLY");
			
			$ret = 1;
		}
		if ($ret) {
			return date("Y-m-d", $to_time);
		}
	}
	
	return;

}



?>
<?php

require ("settings.php");

define ("SECONDS_IN_DAY", 60 * 60 * 24);

$report_start = time();

$OUTPUT = display();

$report_end = time();
$OUTPUT .= "
<span style='font-size: 0.85em'>
	Report generated in ".($report_end - $report_start)." seconds
</span>";

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["cat_id"] = 0;
	$fields["days"] = 1;
	$fields["order_for"] = 30;
	$fields["from_month"] = 1;
	$fields["to_month"] = 12;
	$fields["disp_order"] = 0;
	extract ($fields, EXTR_SKIP);

	// Create the categories dropdown
	$sql = "
	SELECT catid, cat FROM cubit.stockcat
	WHERE div='".USER_DIV."' ORDER BY cat ASC";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

	$cat_sel = "
	<select name='cat_id'>
		<option value='0'>[Please Select]</option>";
	while ($cat_data = pg_fetch_array($cat_rslt)) {
		$sel = ($cat_id == $cat_data["catid"]) ? "selected='selected'" : "";
		$cat_sel .= "
		<option value='$cat_data[catid]' $sel>
			$cat_data[cat]
		</option>";
	}
	$cat_sel .= "</select>";

	// Create the items output
	$sql = "
	SELECT stkid, stkcod, stkdes, units FROM cubit.stock
		LEFT JOIN cubit.stockcat ON stock.catname=stockcat.cat
	WHERE stockcat.catid='$cat_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");

	$forecast_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		
		$average_sales = averageSalesPerDayForDays($stock_data["stkid"], $days);
		$average_sales = sprint($average_sales);
		$average_manu_m = averageManuPerDayForDays($stock_data["stkid"], $days, "m");
		$average_manu_m = sprint($average_manu_m);
		$average_manu_s = averageManuPerDayForDays($stock_data["stkid"], $days, "s");
		$average_manu_s = sprint($average_manu_s);
		$on_order = amtOnOrder($stock_data["stkid"]);

		$average = $average_manu_s - $average_manu_m + $average_sales;

		$order_info = nextOrderDate($stock_data["stkid"], $average);
		$order_qty = orderForDays($average, $order_for);

		// Should this item be displayed
		if ($disp_order && !$order_qty) {
			continue;
		}

		$forecast_out .= "
		<tr class='".bg_class()."'>
			<td nowrap>($stock_data[stkcod]) $stock_data[stkdes]</td>
			<td align='center'>$stock_data[units]</td>
			<td align='center'>".amtOnOrder($stock_data["stkid"])."</td>
			<td align='center'>$order_info[date]</td>
			<td nowrap>$order_info[supplier]</td>
			<td align='center'>$order_qty</td>
		</tr>";
	}

	if (empty($forecast_out)) {
		$forecast_out = "
		<tr class='".bg_class()."'>
			<td colspan='10'><li>No results found</li></td>
		</tr>";
	}

	$order_days = array("7", "30", "60", "120");
	$order_sel = "<select name='order_for'>";
	foreach ($order_days as $day) {
		$sel = ($order_for == $day) ? "selected='selected'" : "";
		$order_sel .= "<option value='$day' $sel>$day Days</option>";
	}
	$order_sel .= "</select>";

	$months = array(1=>"January", "February", "March", "April", "May", "June",
					"July", "August", "September", "October", "November",
					"December");

	$frm_month_sel = "<select name='from_month'>";
	for ($i = 1; $i <= 12; $i++) {
		$sel = ($from_month == $i) ? "selected='selected'" : "";
		$frm_month_sel .= "<option value='$i' $sel>$months[$i]</option>";
	}
	$frm_month_sel .= "</option>";

	$to_month_sel = "<select name='to_month'>";
	for ($i = 1; $i <= 12; $i++) {
		$sel = ($to_month == $i) ? "selected='selected'" : "";
		$to_month_sel .= "<option value='$i' $sel>$months[$i]</option>";
	}
	$to_month_sel .= "</select>";

	$do_sel = ($disp_order) ? "checked='checked'" : "";

	$OUTPUT = "
	<style>
		td, th { font-size: .7em; }
	</style>
	<center>
	<h3>Purchases: Sales Forecasting</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr class='".bg_class()."'>
			<td>Stock Category</td>
			<td>$cat_sel</td>
			<td>Stock Days On Hand</td>
			<td><input type='text' name='days' value='$days' /></td>
		</tr>
		<tr class='".bg_class()."'>
			<td>From Month</td>
			<td>$frm_month_sel</td>
			<td>To Month</td>
			<td>$to_month_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Order for <i>n</i> days</th>
			<td>$order_sel</td>
			<td colspan='2'>
				<input type='checkbox' name='disp_order' value='1'
				onchange='javascript:document.form.submit()' $do_sel />
				Only display items to be ordered
			</td>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='4' align='center'>
				<input type='submit' value='Show' style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Stock</th>
			<th>Currently on Hand</th>
			<th>Currently on Order</th>
			<th>Recommended Order Date</th>
			<th>Recommended Supplier</th>
			<th>Recommended Order Qty</th>
		</tr>
		$forecast_out
	</table>
	</center>";

	return $OUTPUT;
}

function amtOnOrder($stkid)
{
	$sql = "
	SELECT sum(qty) FROM cubit.purchases
		LEFT JOIN cubit.pur_items ON purchases.purid=pur_items.purid
	WHERE stkid='$stkid' AND received='n'";
	$pur_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");
	$on_order = pg_fetch_result($pur_rslt, 0);

	return $on_order;
}

function orderForDays($decline_rate_per_day, $days)
{
	return ceil($decline_rate_per_day * $days);
}

function nextOrderDate($stkid, $decline_rate_per_day)
{
	// Retrieve current units and minimum units 
	$sql = "SELECT units, minlvl FROM cubit.stock WHERE stkid='$stkid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	list($units, $minlvl) = pg_fetch_array($stock_rslt);

	$units += amtOnOrder($stkid);

	$supplier_info = recommendedSupplier($stkid);

	$lead_time = $supplier_info["lead_time"];
	$supname = $supplier_info["supname"];

	$lead_time *= SECONDS_IN_DAY;
	
	$time = time();

	if (empty($minlvl)) {
		$minlvl = 0;
	}

	if ($units > $minlvl && $decline_rate_per_day > 0) {
		while ($units >= $minlvl) {
			$time += SECONDS_IN_DAY;
			$units -= $decline_rate_per_day;
		}
	}

	if (($time - $lead_time) > time()) {
		$time -= $lead_time;
	} else {
		$time = time();
	}

	if ($decline_rate_per_day > 0) {
		return array(
			"date" => date("Y-m-d", $time),
			"supplier" => $supname
		);
	} else {
		return array(
			"date" => "",
			"supplier" => ""
		);
	}
}

function recommendedSupplier($stkid)
{
	// Retrieve shortest lead time
	$sql = "
	SELECT lead_time, suppliers.supid, supname FROM cubit.lead_times
		LEFT JOIN cubit.suppliers ON lead_times.supid=suppliers.supid
	WHERE stkid='$stkid'";
	$lead_rslt = db_exec($sql) or errDie("Unable to retrieve lead times.");

	$min_lead_time = array("lead_time"=>-1, "supid"=>0, "supname"=>"");
	while (list($lead_time, $supid, $supname) = pg_fetch_array($lead_rslt)) {
		if ($lead_time < $min_lead_time["lead_time"] ||
			$min_lead_time["lead_time"] < 0) {
				
				$min_lead_time["lead_time"] = $lead_time;
				$min_lead_time["supid"] = $supid;
				$min_lead_time["supname"] = $supname;
			}
	}

	return $min_lead_time;
}

function averageManuPerDayForDays($stkid, $days, $ms)
{
	$total_manu = totalManuForDays($stkid, $days, $ms);

	return ($total_manu != 0 && $days != 0) ? $total_manu / $days : 0;
}

function averageSalesPerDayForDays($stkid, $days)
{
	$total_sales = totalSalesForDays($stkid, $days);
	
	return ($total_sales != 0 && $days != 0) ? $total_sales / $days : 0;
}

function totalManuForDays($stkid, $days, $ms)
{
	$total_manu = 0;

	$time_start = time() - ($days * SECONDS_IN_DAY);
	$time_end = time();

	$datetime_start = date("Y-m-d", $time_start) . " 0:00:00";
	$datetime_end = date("Y-m-d", $time_end) . " 23:59:59";

	$sql = "
	SELECT distinct(timestamp) FROM cubit.manu_history
	WHERE {$ms}_stock_id='$stkid' AND
		timestamp BETWEEN '$datetime_start' AND '$datetime_end'";
	$hist_rslt = db_exec($sql) or errDie("Unable to retrieve manufacture.");
	$total_manu = pg_num_rows($hist_rslt);

	return $total_manu;
}

function totalSalesForDays($stkid, $days)
{
	$total_sales = 0;

	$time_start = time() - ($days * SECONDS_IN_DAY);
	$time_end = time();

	$date_start = date("Y-m-d", $time_start);
	$date_end = date("Y-m-d", $time_end);

	// Invoice sales
	$union = array();

	// Invoice Table => Line Items Table
	$invoices = array(
		"invoices"=>"inv_items",
		"pinvoices"=>"pinv_items"
	);

	foreach ($invoices as $invoice=>$inv_items) {
		// Retrieve total quantities
		$union[] = "
			SELECT sum(qty) FROM cubit.$inv_items
			LEFT JOIN cubit.$invoice ON $inv_items.invid=$invoice.invid
			WHERE stkid='$stkid' AND done='y' AND printed='y' AND 
			odate BETWEEN '$date_start' AND '$date_end'";

		// Total quantities for periods
		for ($j = 1; $j <= 14; $j++) {
			$union[] = "
				SELECT sum(qty) FROM \"$j\".$inv_items
				LEFT JOIN \"$j\".$invoice ON $inv_items.invid=$invoice.invid
				WHERE stkid='$stkid' AND done='y' AND printed='y' AND 
				odate BETWEEN '$date_start' AND '$date_end'";
		}
	}
	$sql = implode(" UNION ", $union);
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve total qty");

	while (list($qty) = pg_fetch_array($items_rslt)) {
		$total_sales += $qty;
	}

	return $total_sales;
}

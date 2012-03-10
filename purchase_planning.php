<?php

require ("settings.php");
require ("sales_forecast.lib.php");

$OUTPUT = display();

require ("template.php");





function display()
{

	extract ($_REQUEST);

	define("TOMMOROW", time() + (60 * 60 * 24));

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y", TOMMOROW) + 5;
	$fields["to_month"] = date("m", TOMMOROW);
	$fields["to_day"] = date("d", TOMMOROW);
	$fields["stock_search"] = "";
	$fields["inc_perc"] = 0;
	$fields["dec_perc"] = 0;
	$fields["cat"] = "";
	$fields["stock_search"] = "";

	extract ($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	// Retrieve categories
	$sql = "
	SELECT catid, cat FROM cubit.stockcat
	WHERE div='".USER_DIV."'
	ORDER BY cat ASC";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

	$cat_sel = "<select name='cat' style='width: 100%'>";
	$cat_sel .= "<option value='[None]'>[None]</option>";
	while ($cat_data = pg_fetch_array($cat_rslt)) {
		$sel = ($cat == $cat_data["cat"]) ? "selected" : "";
		$cat_sel .= "<option value='$cat_data[cat]' $sel>$cat_data[cat]</option>";
	}
	$cat_sel .= "</select>";

	// Retrieve stores
	$sql = "SELECT whname FROM exten.warehouses ORDER BY whname ASC";
	$stores_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");

	$forecast_stores = "";
	while ($stores_data = pg_fetch_array($stores_rslt)) {
		$forecast_stores .= "<th>Sales Forecast - $stores_data[whname]</th>";
	}

	// Retrieve stock
	$sql = "
	SELECT stkid, stkcod, stkdes, units, alloc, minlvl, maxlvl, selamt,
		supplier1, supplier2, supplier3, leadtime_supp1, leadtime_supp2,
		leadtime_supp3
	FROM cubit.stock
	WHERE catname='$cat' AND (stkcod ILIKE '$stock_search%' OR
		stkdes ILIKE '$stock_search%')
	ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$sql = "SELECT whid FROM exten.warehouses ORDER BY whname ASC";
		$stores_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");
		$stores_out = "";
		while (list($whid) = pg_fetch_array($stores_rslt)) {
			$stores_out .= "
			<td align='center'>
				".averageDailySales($stock_data["stkid"], $whid)."
			</td>";
		}

		// Total on order
		$sql = "
		SELECT sum(qty) FROM cubit.pur_items
			LEFT JOIN cubit.purchases ON pur_items.purid=purchases.purid
		WHERE stkid='$stock_data[stkid]' AND done='y' AND received='n'";
		$pur_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");
		$total_orders = pg_fetch_result($pur_rslt, 0);

		if (empty($total_orders)) {
			$total_orders = 0;
		}

		$supplier1 = $supplier2 = $supplier3 = "";
		// Retrieve supplier1
		if ($stock_data["supplier1"] > 0) {
			$sql = "
			SELECT supname FROM cubit.suppliers
			WHERE supid='$stock_data[supplier1]'";
			$supplier1_rslt = db_exec($sql) or errDie("Unable to retrieve supplier1.");
			$supplier1 = pg_fetch_result($supplier1_rslt, 0);
		}

		// Retrieve supplier2
		if ($stock_data["supplier2"] > 0) {
			$sql = "
			SELECT supname FROM cubit.suppliers
			WHERE supid='$stock_data[supplier2]'";
			$supplier2_rslt = db_exec($sql) or errDie("Unable to retrieve supplier2.");
			$supplier2 = pg_fetch_result($supplier2_rslt, 0);
		}

		// Retrieve supplier3
		if ($stock_data["supplier3"] > 0) {
			$sql = "
			SELECT supname FROM cubit.suppliers
			WHERE supid='$stock_data[supplier3]'";
			$supplier3_rslt = db_exec($sql) or errDie("Unable to retrieve supplier3.");
			$supplier3 = pg_fetch_result($supplier3_rslt, 0);
		}

		$stock_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stock_data[stkcod] - $stock_data[stkdes]</td>
				<td nowrap>".purchaseForecast($stock_data["stkid"], $from_date, $to_date)."</td>
				<td align='center'>".totalAverageDailySales($stock_data["stkid"])."</td>
				$stores_out
				<td align='center'>$stock_data[units]</td>
				<td align='center'>$total_orders</td>
				<td align='center'>$stock_data[alloc]</td>
				<td align='center'>$supplier1 $stock_data[leadtime_supp1]</td>
				<td align='center'>$supplier2 $stock_data[leadtime_supp2]</td>
				<td align='center'>$supplier3 $stock_data[leadtime_supp3]</td>
				<td align='center'>$stock_data[minlvl]</td>
				<td align='center'>$stock_data[maxlvl]</td>
			</tr>";
	}

	if (empty($stock_out)) {
		$stock_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='20'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Purchase Resource Planning</h3>
		<form method='POST' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>From</th>
				<th>To</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$from_day-$from_month-$from_year</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
	<!--
			<tr>
				<th>Sales Forecast - Projected Increase</th>
				<th>Sales Forecast - Projected Decrease</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					<input type='text' name='inc_perc' value='$inc_perc' size='3'
					style='text-align: center' />%
				</td>
				<td align='center'>
					<input type='text' name='dec_perc' value='$dec_perc' size='3'
					style='text-align: center' />%
				</td>
			</tr>
	-->
			<tr><th colspan='2'>Stock Category</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$cat_sel</td>
			</tr>
			<tr>
				<th colspan='2'>Stock Filter</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>
					<input type='text' name='stock_search' value='$stock_search'
					style='width: 100%' />
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>
					<input type='submit' value='Apply &raquo'
					style='font-weight: bold' />
				</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>
					<li><b>Note:</b> If no lead times has been found for a stock<br />
					item, a default lead time of 30 days will be used.</li>
				</td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock</th>
				<th>Forecasted Purchase Requirement</th>
				<th>Sales Forecast - Total</th>
				$forecast_stores
				<th>Total in Stock</th>
				<th>Total on Order<br />(not yet delivered)</td>
				<th>Total Stock Allocated</th>
				<th>Lead Time - Supplier 1</th>
				<th>Lead Time - Supplier 2</th>
				<th>Lead Time - Supplier 3</th>
				<th>Stock Minnimum</th>
				<th>Stock Maximum</th>
			</tr>
			$stock_out
		</table>
		</center>";
	return $OUTPUT;

}


?>
<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	define ("SECONDS_IN_DAY", 60 * 60 * 24);

	$fields = array();
	$fields["cat_name"] = "";
	$fields["days"] = 0;
	extract ($fields, EXTR_SKIP);

	// Create categories dropdown
	$sql = "
	SELECT cat FROM cubit.stockcat
	WHERE div='".USER_DIV."'
	ORDER BY cat ASC";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");
	$cat_sel = "
	<select name='cat_name'>
		<option value='0'>[None]</option>";
	while (list($cat) = pg_fetch_array($cat_rslt)) {
		$sel = ($cat_name == $cat) ? "selected='selected'" : "";
		$cat_sel .= "<option value='$cat' $sel>$cat</option>";
	}
	$cat_sel .= "</select>";

	$seconds_ago = $days * SECONDS_IN_DAY;
	$time_start = time() - $seconds_ago;
	$time_end = time();

	$sales = array();
	for ($i = $time_start; $i < $time_end; $i += SECONDS_IN_DAY) {
		$sql = "SELECT stkid FROM cubit.stock WHERE catname='$cat_name'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		while (list($stkid) = pg_fetch_array($stock_rslt)) {
			$sales[$stkid][date("Y-m-d", "$i")] = 0;

			$sql = "
			SELECT sum(qty) FROM cubit.inv_items 
				LEFT JOIN cubit.invoices ON inv_items.invid=invoices.invid
			WHERE stkid='$stkid' AND odate='".date("Y-m-d", $i)."'";
			$count_rslt = db_exec($sql)
				or errDie("Unable to retrieve invoices.");
			$count = pg_fetch_result($count_rslt, 0);
			if (is_numeric($count)) {
				$sales[$stkid][date("Y-m-d", $i)] += $count;
			}

			// Count up the total number of invoices for $i day
			for ($j = 1; $j <= 14; $j++) {
				$sql = "
				SELECT sum(qty) FROM \"$j\".inv_items
					LEFT JOIN cubit.invoices ON inv_items.invid=invoices.invid
				WHERE stkid='$stkid' AND odate='".date("Y-m-d", "$i")."'";
				$count_rslt = db_exec($sql)
					or errDie("Unable to retrieve invoices.");
				print $sql."<br />";
								
				$count = pg_fetch_result($count_rslt, 0);
				if (is_numeric($count)) {
					$sales[$stkid][date("Y-m-d", "$i")] += $count;
				}

				if (pg_num_rows($count_rslt)) {
					break;
				}
			}
		}
	}

	$sales_out = "";
	foreach ($sales as $stkid=>$lv2) {
		$total_sold = 0;
		$total_days = 0;
		foreach ($lv2 as $date=>$sold) {
			$total_days++;
			$total_sold += $sold;
		}

		$sql = "
		SELECT stkcod, stkdes, units FROM cubit.stock
		WHERE stkid='$stkid'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_data = pg_fetch_array($stock_rslt);

		if ($total_sold != 0 && $total_days != 0) {
			$average_sold = $total_sold / $total_days;
		} else {
			$average_sold = 0;
		}

		$purchase_days = 0;
		$purchase_time = 0;
		if ($stock_data["units"] > 0) {
			for ($k = $stock_data["units"]; $k > 0; $k -= $average_sold) {
				$purchase_days++;
			}
		}
		
		
		if ($purchase_days > 0) {
			$purchase_time = time() + ($purchase_days * SECONDS_IN_DAY);
		} else {
			$purchase_time = time();
		}
		
		$sales_out .= "
		<tr class='".bg_class()."'>
			<td>$stock_data[stkcod] - $stock_data[stkdes]</td>
			<td>$total_days</td>
			<td>$total_sold</td>
			<td>$average_sold</td>
			<td>$stock_data[units]</td>
			<td>".date("Y-m-d", $purchase_time)."</td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Purchases: Sales Forecasting</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr class='".bg_class()."'>
			<td>$cat_sel</td>
			<td><input type='text' name='days' value='$days' size='2' /></td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Stock</th>
			<th>Total Days</th>
			<th>Total Sold</th>
			<th>Average Sold</th>
			<th>Units in Stock</th>
			<th>Next Purchase Date</th>
		</tr>
		$sales_out
	</form>";

	return $OUTPUT;
}

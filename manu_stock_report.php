<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_day"] = date("m");
	$fields["from_month"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	$from_time = "$from_year-$from_month-$from_day 0:00:00";
	$to_time = "$to_year-$to_month-$to_day 23:59:59";

	$sql = "
	SELECT DISTINCT timestamp FROM cubit.manu_history
	WHERE timestamp BETWEEN '$from_time' AND '$to_time'";
	$time_rslt = db_exec($sql) or errDie("Unable to retrieve history.");

	$history_ar = array();
	while ($time_data = pg_fetch_array($time_rslt)) {
		$sql = "
		SELECT id, extract('epoch' FROM timestamp) AS e_time
		FROM cubit.manu_history
		WHERE timestamp='$time_data[timestamp]' ORDER BY timestamp DESC";
		$history_rslt = db_exec($sql) or errDie("Unable to retrieve history.");

		while ($history_data = pg_fetch_array($history_rslt)) {
			$history_ar[$history_data["e_time"]][] = $history_data["id"];
		}
	}

	$history_out = "";
	foreach ($history_ar as $e_time=>$lv2) {
		$total = 0;
		$history_out .= "
		<tr><th colspan='5'>".date("d-m-Y G:i:s", $e_time)."</th></tr>";
		foreach ($history_ar[$e_time] as $id) {
			$sql = "
				SELECT m_stock_id, s_stock_id, qty, (cost * qty) AS cost
				FROM cubit.manu_history
				WHERE id='$id'";
			$history_rslt = db_exec($sql) or errDie("Unable to retrieve hist");
			list($m_stock_id, $s_stock_id, $qty, $cost) = 
				pg_fetch_array($history_rslt);

			$sql = "
				SELECT stkcod, stkdes FROM cubit.stock
				WHERE stkid='$m_stock_id'";
			$m_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock");
			$m_stock = pg_fetch_array($m_stock_rslt);

			$sql = "
				SELECT stkcod, stkdes FROM cubit.stock
				WHERE stkid='$s_stock_id'";
			$s_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock");
			$s_stock = pg_fetch_array($s_stock_rslt);

			$history_out .= "
			<tr class='".bg_class()."'>
				<td>".date("d-m-Y G:i:s", $e_time)."</td>
				<td>($m_stock[stkcod]) $m_stock[stkdes]</td>
				<td>($s_stock[stkcod]) $s_stock[stkdes]</td>
				<td>$qty</td>
				<td align='right'>".sprint($cost)."</td>
			</tr>";
			$total += $cost;
		}
		$history_out .= "
		<tr class='".bg_class()."'>
			<td colspan='4'>Total</td>
			<td align='right'>".sprint($total)."</td></tr>
		</tr>";
	}

	$OUTPUT = "
	<h3>Stock Manufactured Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Filter' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		$history_out
	</table>";

	return $OUTPUT;
}

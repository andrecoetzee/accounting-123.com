<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["frm_year"] = date("Y") - 1;
	$fields["frm_month"] = "01";
	$fields["frm_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	$frm_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "
	SELECT stkcod, stkdes, qty, page, whname, adjust_val, date
	FROM cubit.stock_take_adjustments
		LEFT JOIN cubit.stock
			ON stock_take_adjustments.stkid=stock.stkid
		LEFT JOIN exten.warehouses
			ON stock_take_adjustments.whid=warehouses.whid
	WHERE date BETWEEN '$frm_date' AND '$to_date'
	ORDER BY date, page, whname DESC";
	$adjust_rslt = db_exec($sql) or errDie("Unable to retrieve adjustments.");

	$adjust_out = "";
	while ($adjust_data = pg_fetch_array($adjust_rslt)) {
		$adjust_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$adjust_data[date]</td>
			<td>$adjust_data[whname]</td>
			<td>($adjust_data[stkcod]) $adjust_data[stkdes]</td>
			<td align='center'>$adjust_data[page]</td>
			<td align='center'>$adjust_data[qty]</td>
			<td align='right'>$adjust_data[adjust_val]</td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Stock Take Adjustment Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Store</th>
			<th>Stock</th>
			<th>Page</th>
			<th>Qty Counted</th>
			<th>Value Adjusted</th>
		</tr>
		$adjust_out
	</table>
	</center>";

	return $OUTPUT;
}

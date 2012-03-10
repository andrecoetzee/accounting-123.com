<?php

require ("settings.php");
require_lib("manufact");

$OUTPUT = display();

$OUTPUT .= "<br>".mkQuickLinks(
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
	$fields["prd"] = "daily";
	$fields["increase"] = 0;
	$fields["decrease"] = 0;

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "SELECT stkid, stkcod, stkdes, minlvl, maxlvl, units FROM cubit.stock";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	$tot_avail = 0;
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$tmp_min = $suggested_min = averageSalesQty($stock_data["stkid"], $from_date, $to_date, $prd);
		$tmp_max = $suggested_max = maxSalesQty($stock_data["stkid"], $prd);

		$suggested_min += ($tmp_min / 100) * $increase;
		$suggested_min -= ($tmp_min / 100) * $decrease;

		$suggested_max += ($tmp_max / 100) * $increase;
		$suggested_max -= ($tmp_max / 100) * $decrease;

		$stock_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stock_data[stkcod]</td>
				<td>$stock_data[stkdes]</td>
				<td align='center'><b>$stock_data[minlvl]</b></td>
				<td align='center'><b>$stock_data[maxlvl]</b></td>
				<td align='center'>
					<b>$suggested_min</b>
				</td>
				<td align='center'>
					<b>".sprint3($suggested_max)."</b>
				</td>
				<td align='center'><b>".sprint3($stock_data['units'])."</b></td>
			</tr>";

		if ($stock_data['units'] > 0)
			$tot_avail += $stock_data['units'];
	}

	$stock_out .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='6'><b>Total: (Positive amounts only)</b></td>
						<td align='center'><b>".sprint3($tot_avail)."</b></td>
					</tr>
				";

	$prds = array("daily", "weekly", "monthly");

	$prd_sel = "<select name='prd'>";
	foreach ($prds as $prd_val) {
		if ($prd_val == $prd) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$prd_sel .= "<option value='$prd_val' $sel>".ucfirst($prd_val)."</option>";
	}


	$OUTPUT = "
		<center>
		<h3>Minimum and Maximum Stock Levels</h3>
		<form method='POST' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b> To </b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td rowspan='2'>
					<input type='submit' value='Calculate' style='height: 100%; font-weight: bold' />
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='3' align='center'>$prd_sel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4' align='center'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>Increase</th>
							<th>Decrease</th>
						</tr>
						<tr>
							<td width='50%' align='center'>
								<input type='text' name='increase' value='$increase' size='3' />%
							</td>
							<td width='50%' align='center'>
								<input type='text' name='decrease' value='$decrease' size='3' />%
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Current Minimum</th>
				<th>Current Maximum</th>
				<th>Suggested Minimum</th>
				<th>Suggested Maximum</th>
				<th>Currently On Hand</th>
			</tr>
			$stock_out
		</table>
		</center>";
	return $OUTPUT;

}



?>

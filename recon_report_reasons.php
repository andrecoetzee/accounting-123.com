<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"]  = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "
	SELECT supname, date, reason_id, reason, amount
	FROM cubit.recon_balance_ct
		LEFT JOIN cubit.suppliers ON recon_balance_ct.supid=suppliers.supid
		LEFT JOIN cubit.recon_reasons ON recon_balance_ct.reason_id=recon_reasons.id
	WHERE date BETWEEN '$from_date' AND '$to_date'
	ORDER BY date ASC";
	$rows_rslt = db_exec($sql) or errDie("Unable to retrieve recon reasons.");

	$rows_ar = array();
	$totals_ar = array();
	while ($rows = pg_fetch_array($rows_rslt)) {
		$rows_ar[$rows["reason_id"]][] = "
		<tr class='".bg_class()."'>
			<td>$rows[date]</td>
			<td>$rows[supname]</td>
			<td align='right'>".sprint($rows["amount"])."</td>
		</tr>";
		
		if (!isset($totals_ar[$rows["reason_id"]])) {
			$totals_ar[$rows["reason_id"]] = 0;
		}

		$totals_ar[$rows["reason_id"]] += $rows["amount"];
	}

	$rows_out = "";
	foreach ($rows_ar as $reason_id=>$lv2) {
		$sql = "SELECT reason FROM cubit.recon_reasons WHERE id='$reason_id'";
		$reason_rslt = db_exec($sql) or errDie("Unable to retrieve recon reasons.");
		$reason = pg_fetch_result($reason_rslt, 0);

		$rows_out .= "
		<tr>
			<th colspan='4'>$reason</th>
		</tr>";
		foreach ($rows_ar[$reason_id] as $out) {
			$rows_out .= $out;
		}

		$rows_out .= "
		<tr class='".bg_class()."'>
			<td colspan='2'>Total</td>
			<td align='right'>".sprint($totals_ar[$reason_id])."</td>
		</tr>";
	}


	$OUTPUT = "
	<h3>Report on Creditors Recon</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select &raquo' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		$rows_out
	</table>";

	return $OUTPUT;
}

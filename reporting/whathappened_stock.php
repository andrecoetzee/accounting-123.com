<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");



function display()
{

	extract ($_REQUEST);

	define ("LIMIT", 100);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d") -1;
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["search_by"] = "stkcod";
	$fields["search"] = "";
	$fields["offset"] = 0;
	extract ($fields, EXTR_SKIP);

	if ($from_day == 0)
		$from_day = "01";

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";


	$search_ar = array(
		"stkcod" => "Stock Code",
		"stkdes" => "Stock Description",
		"id" => "Journal Number",
		"details" => "Journal Description",
	);

	$search_sel = "<select name='search_by' style='width: 100%'>";
	foreach ($search_ar as $db_name=>$out_name) {
		if ($search_by == $db_name) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$search_sel .= "<option value='$db_name' $sel>$out_name</option>";
	}
	$search_sel .= "</select>";

	$union = array();
	$first = 1;
	for ($i = 1; $i <= 13; $i++) {
		$union[] = "
			SELECT id, edate, stkcod, stkdes, details, qty, csamt, $i AS prd
			FROM \"$i\".stkledger
			WHERE $search_by ILIKE '$search%' AND trantype!='bal'
				AND edate BETWEEN '$from_date' AND '$to_date'";
	}

	$sql = implode(" UNION ", $union);
	
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve count.");
	$count = pg_num_rows($count_rslt);

	$sql .= " ORDER BY edate, id ASC OFFSET $offset LIMIT ".LIMIT;
	$ledger_rslt = db_exec($sql) or errDie("Unable to retrieve stock ledger.");

	$ledger_out = "";
	$total_csamt = 0;
	$total_qty = 0;
	$min_prd = 0;
	while ($ledger_data = pg_fetch_array($ledger_rslt)) {
		if ($ledger_data["prd"] < $min_prd || $min_prd == 0) {
			$min_prd = $ledger_data["prd"];
		}

		$ledger_out .= "
			<tr class='".bg_class()."'>
				<td>$ledger_data[id]</td>
				<td>$ledger_data[edate]</td>
				<td>($ledger_data[stkcod]) $ledger_data[stkdes]</td>
				<td>$ledger_data[details]</td>
				<td>".sprint3($ledger_data['qty'])."</td>
				<td>".sprint($ledger_data["csamt"])."</td>
			</tr>";

		$total_qty += $ledger_data["qty"];
		$total_csamt += $ledger_data["csamt"];
	}

	if ($min_prd > 0) {
		$sql = "
			SELECT id, edate, stkcod, stkdes, details, qty, csamt, $i AS prd
			FROM \"$i\".stkledger
			WHERE $search_by ILIKE '$search%'
				AND edate BETWEEN '$from_date' AND '$to_date' AND trantype='bal'";
		$bal_rslt = db_exec($sql) or errDie("Unable to retrieve stock ledger.");

		$bal_out = "";
		while ($bal_data = pg_fetch_array($bal_rslt)) {
			$bal_out .= "
				<tr class='".bg_class()."'>
					<td>$ledger_data[id]</td>
					<td>$ledger_data[edate]</td>
					<td>$ledger_data[stkcod] $ledger_data[stkdes]</td>
					<td>$details</td>
					<td>".sprint3($qty)."</td>
					<td>".sprint($csamt)."</td>
				</tr>";
		}
	}

	if (empty($ledger_out)) {
		$ledger_out = "
			<tr class='".bg_class()."'>
				<td colspan='7'><li>No results found</li></td>
			</tr>";
	}

	$next_offset = $offset + LIMIT;
	$prev_offset = $offset - LIMIT;
	
	$get_ar = array();
	foreach ($fields as $key => $value) {
		if ($key == "offset") continue;
		$get_ar[] = "$key={$$key}";
	}
	$get_vars = implode("&", $get_ar);
	
	$prev_ancor = ($prev_offset >= 0) ? "<a href='".SELF."?offset=$prev_offset&$get_vars'>&laquo; Previous</a>" : "";
	$next_ancor = ($next_offset < $count) ? "<a href='".SELF."?offset=$next_offset&$get_vars'>Next &raquo;</a>" : "";
	
	$OUTPUT = "
		<center>
		<h3>Stock - Transaction Detail Report</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='3'>Date Range</th>
				<td rowspan='4'>
					<input type='submit' value='Search' style='text-weight: bold; height: 100%' />
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
			<tr>
				<th colspan='3'>Search</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'>$search_sel</td>
				<td><input type='text' name='search' value='$search' style='width: 100%' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr class='".bg_class()."'>
				<td colspan='7' align='center'>
					$prev_ancor
					$next_ancor
				</td>
			</tr>
			<tr>
				<th>Journal No.</th>
				<th>Date</th>
				<th>Stock</th>
				<th>Journal Description</th>
				<th>Qty</th>
				<th>Amount</th>
			</tr>
			$ledger_out
			<tr class='".bg_class()."'>
				<td colspan='4'>Total</td>
				<td><b>".sprint3($total_qty)."</b></td>
				<td><b>".sprint($total_csamt)."</b></td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='7' align='center'>
					$prev_ancor
					$next_ancor
				</td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}

?>

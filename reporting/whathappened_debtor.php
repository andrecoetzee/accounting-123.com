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
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["search_by"] = "surname";
	$fields["search"] = "";
	$fields["offset"] = 0;
	
	extract ($fields, EXTR_SKIP);
	
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";
	
	$search_ar = array(
		"surname"=>"Customer",
		"id"=>"Journal Number",
		"descript"=>"Description",
		"amount"=>"Amount"
	);
	
	$search_sel = "<select name='search_by' style='width: 100%'>";
	foreach ($search_ar as $db_value=>$out_value) {
		if ($search_by == $db_value) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$search_sel .= "<option value='$db_value' $sel>$out_value</option>";
	}
	
	if ($search_by == "amount") {
		$search_db = "";
	} else {
		$search_db = " AND $search_by ILIKE '$search%'";
	}
	
	$union = array();
	for ($i = 1; $i <= 14; $i++) {
		$union[] = "
		SELECT surname, id, descript, edate, credit, debit, cbalance,
			dbalance, topacc, accnum, accname
		FROM \"$i\".custledger
			LEFT JOIN cubit.customers
				ON custledger.cusnum=customers.cusnum
			LEFT JOIN core.accounts
				ON custledger.contra=accounts.accid
		WHERE contra>0 AND edate BETWEEN '$from_date' AND '$to_date' $search_db";
	}
	
	$sql = implode(" UNION ", $union);
	
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve count.");
	$count = pg_num_rows($count_rslt);
	
	$sql .= " ORDER BY edate DESC OFFSET $offset LIMIT ".LIMIT;
	$ledger_rslt = db_exec($sql) or errDie("Unable to retrieve ledger.");
	
	$ledger_out = "";
	while ($ledger_data = pg_fetch_array($ledger_rslt)) {
		$credit = $ledger_data["credit"];
		$debit = $ledger_data["debit"];
		
		if ($search_by == "amount" && $search != "" &&
			(intval($debit) != intval($search) && intval($credit != intval($search)))) {
			continue;
		}
		
		$ledger_out .= "
		<tr class='".bg_class()."'>
			<td>$ledger_data[id]</td>
			<td>$ledger_data[edate]</td>
			<td>$ledger_data[surname]</td>
			<td>$ledger_data[descript]</td>
			<td align='right'>".sprint($ledger_data["debit"])."</td>
			<td align='right'>".sprint($ledger_data["credit"])."</td>
			<td>&nbsp; $ledger_data[topacc]/$ledger_data[accnum] $ledger_data[accname]</td>
		</tr>";
	}
	
	if (empty($ledger_out)) {
		$ledger_out .= "
		<tr class='".bg_class()."'>
			<td colspan='8'><li>No results found.</li></td>
		</tr>";
	}

	$next_offset = $offset + LIMIT;
	$prev_offset = $offset - LIMIT;
	
	$get_ar = array();
	foreach ($fields as $key=>$value)
	{
		if ($key == "offset") continue;
		$get_ar[] = "$key={$$key}";
	}
	$get_vars = implode("&", $get_ar);
	
	$prev_ancor = ($prev_offset >= 0) ? "<a href='".SELF."?offset=$prev_offset&$get_vars'>&laquo; Previous</a>" : "";
	$next_ancor = ($next_offset < $count) ? "<a href='".SELF."?offset=$next_offset&$get_vars'>Next &raquo;</a>" : "";

	$OUTPUT = "
	<center>
	<h3>Debtors - Transaction Detail Report</h3>
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
			<td colspan='8' align='center'>
				$prev_ancor
				$next_ancor
			</td>
		</tr>
		<tr>
			<th>Journal No.</th>
			<th>Date</th>
			<th>Customer</th>
			<th>Journal Description</th>
			<th>Debit</th>
			<th>Credit</th>
			<th>Contra</th>
		</tr>
		$ledger_out
		<tr class='".bg_class()."'>
			<td colspan='8' align='center'>
				$prev_ancor
				$next_ancor
			</td>
		</tr>
	</table>";
	
	return $OUTPUT;
}

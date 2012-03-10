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
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["reason_id"] = 0;
	
	extract ($fields, EXTR_SKIP);
	
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";
	
	if ($reason_id) {
		$reason_sql = " AND reason_id='$reason_id'";
	} else {
		$reason_sql = "";
	}
	
	$sql = "SELECT date, supno, supname, reason, amount
			FROM cubit.recon_balance_ct
				LEFT JOIN cubit.suppliers
					ON recon_balance_ct.supid=suppliers.supid
				LEFT JOIN cubit.recon_reasons
					ON recon_balance_ct.reason_id=recon_reasons.id
			WHERE date BETWEEN '$from_date' AND '$to_date' $reason_sql
			ORDER BY date DESC";
	$recon_rslt = db_exec($sql) or errDie("Unable to retrieve recon.");
	
	$sql = "SELECT id, reason FROM cubit.recon_reasons ORDER BY reason ASC";
	$reason_rslt = db_exec($sql) or errDie("Unable to retrieve reasons.");
	
	$reason_sel = "
	<select name='reason_id'>
		<option value='0'>[All]</option>";
	while ($reason_data = pg_fetch_array($reason_rslt)) {
		if ($reason_id == $reason_data["id"]) {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}
		
		$reason_sel .= "
		<option value='$reason_data[id]' $sel>
			$reason_data[reason]
		</option>";
	}
	$reason_sel .= "</select>";
	
	$total = 0;
	
	$recon_out = "";
	while ($recon_data = pg_fetch_array($recon_rslt)) {
		$recon_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$recon_data[date]</td>
			<td>$recon_data[supno]</td>
			<td>$recon_data[supname]</td>
			<td>$recon_data[reason]</td>
			<td>$recon_data[amount]</td>
		</tr>";
		
		$total += $recon_data["amount"];
	}
	
	if (empty($recon_out)) {
		$recon_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'><li>No results found</li></td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Creditor Recon Reason Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='3'>Date Range</th>
			<th>Reason</th>
			<th>&nbsp;</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td>$reason_sel</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Supplier No</th>
			<th>Supplier</th>
			<th>Reason</th>
			<th>Amount</th>
		</tr>
		$recon_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>Total</td>
			<td>".sprint($total)."</td>
		</tr>
	</table>
	</center>";
	
	return $OUTPUT;
}
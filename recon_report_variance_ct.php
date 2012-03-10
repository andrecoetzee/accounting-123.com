<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["search"] = "";
	
	extract($fields, EXTR_SKIP);
	
	$sql = "SELECT supno, supname, suppliers.balance AS sup_balance,
				recon_creditor_balances.balance AS recon_balance,
				(SELECT sum(amount) FROM cubit.recon_balance_ct 
				WHERE supid=suppliers.supid) AS reason_total
			FROM cubit.suppliers
				LEFT JOIN cubit.recon_creditor_balances
					ON suppliers.supid=recon_creditor_balances.supid
			WHERE supno ILIKE '$search%' OR supname ILIKE '$search%'
			ORDER BY supno ASC";
	$ct_rslt = db_exec($sql) or errDie("Unable to retrieve suppliers.");
	
	$ct_out = "";
	while ($ct_data = pg_fetch_array($ct_rslt)) {
		if ($ct_data["recon_balance"] > $ct_data["sup_balance"]) {
			$variance = $ct_data["recon_balance"] - $ct_data["sup_balance"];
		} else {
			$variance = $ct_data["sup_balance"] - $ct_data["recon_balance"];
		}
		
		$ct_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$ct_data[supno]</td>
			<td>$ct_data[supname]</td>
			<td>$ct_data[sup_balance]</td>
			<td>$ct_data[recon_balance]</td>
			<td>".sprint($variance)."</td>
		</tr>";
	}
	
	$OUTPUT = "
	<h3>Creditor Recon Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Supplier No.</th>
			<th>Supplier Name</th>
			<th>Balance According to Cubit</th>
			<th>Balance According to Creditor</th>
			<th>Variance</th>
		</tr>
		$ct_out
	</table>";
	
	return $OUTPUT;
}
<?php

require ("../settings.php");

$OUTPUT = report();

require ("../template.php");

// function display()
// {
// 	extract ($_REQUEST);
// 	
// 	$money_units = array(
// 				"5c"=>"0.05",
// 				"20c"=>"0.20",
// 				"50c"=>"0.50",
// 				"R1"=>"1.00",
// 				"R2"=>"2.00",
// 				"R5"=>"5.00",
// 				"R10"=>"10.00",
// 				"R20"=>"20.00",
// 				"R50"=>"50.00",
// 				"R100"=>"100.00",
// 				"R200"=>"200.00"
// 	);
// 	
// 	$items_out = "";
// 	foreach ($money_units as $h_value=>$c_value) {
// 		$items_out .= "
// 		<tr class='".bg_class()."'>
// 			<td>$h_value</td>
// 			<td><input type='text'";
// 		
// 	
// 	$OUTPUT = "<h3>Hire Cash Up</h3>
// 	<table ".TMPL_tblDflts.">
// 		<tr>
// 			<th>Money Unit</th>
// 			<th>Qty</th>
// 			<th>Total</th>
// 		</tr>
// 		$items_out
// 	</table>";

function report()
{
	extract ($_REQUEST);
	
	$sql = "SELECT invnum, hire_invid, invid, surname, discount, total, cash
			FROM cubit.nons_invoices
				LEFT JOIN cubit.customers
					ON nons_invoices.cusnum=customers.cusnum
			WHERE username='".USER_NAME."' AND 
				nons_invoices.odate='".date("Y-m-d")."'
				AND hire_invid>0 AND invnum>0";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve cash sales.");
	
	$items_out = "";
	$total = 0;
	while ($items_data = pg_fetch_array($items_rslt)) {
		$items_out .= "
		<tr class='".bg_class()."'>
			<td>".getHirenum($items_data["hire_invid"], 1)."</td>
			<td>$items_data[invnum]</td>
			<td>$items_data[surname]</td>
			<td>$items_data[discount]</td>
			<td>$items_data[total]</td>
			<td align='right'>$items_data[cash]</td>
		</tr>";
		
		$total += $items_data["cash"];
	}
	
	$sql = "SELECT hire_invoices.invid AS hire_invid, invnum, inv_invid, cash, 
				customers.surname, discount, total
			FROM hire.cash
				LEFT JOIN hire.hire_invoices ON hire_invoices.invid=cash.invid
				LEFT JOIN cubit.customers ON hire_invoices.cusnum=customers.cusnum
			WHERE date='".date("Y-m-d")."' AND username='".USER_NAME."'";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve cash.");
	
	while ($items_data = pg_fetch_array($items_rslt)) {
		$items_out .= "
		<tr class='".bg_class()."'>
			<td>".getHirenum($items_data["hire_invid"], 1)."</td>
			<td>$items_data[invnum]</td>
			<td>$items_data[surname]</td>
			<td>$items_data[discount]</td>
			<td>$items_data[total]</td>
			<td align='right'>$items_data[cash]</td>
		</tr>";
		
		$total += $items_data["cash"];
	}
	
	$OUTPUT = "<h3>Hire Cash Up</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Hire No</th>
			<th>Invoice No</th>
			<th>Customer</th>
			<th>Discount</th>
			<th>Total</th>
			<th>Paid Cash</th>
		</tr>
		$items_out
		<tr class='".bg_class()."'>
			<td colspan='5'><b>TOTAL</b>
			<td align='right'><b>".sprint($total)."</b></td>
		</tr>
	</table>";
	
	return $OUTPUT;
}
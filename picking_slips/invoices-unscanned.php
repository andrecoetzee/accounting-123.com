<?php

require ("../settings.php");
require ("picking_slip.lib.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);
	
	$sql = "SELECT pslip_sordid, invnum, surname, total FROM cubit.invoices WHERE pslip_sordid!=0 AND done='y' AND printed='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	
	$total = 0;
	
	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt))
	{
		$sql = "SELECT sordid FROM cubit.pslip_scans WHERE sordid='$inv_data[pslip_sordid]'";
		$scan_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");
		
		if (pg_num_rows($scan_rslt)) continue;
	
		$inv_out .= "
		<tr class='".bg_class()."'>
			<td><img src='".pick_slip_barcode($inv_data["pslip_sordid"])."'></td>
			<td>$inv_data[invnum]</td>
			<td>$inv_data[pslip_sordid]</td>
			<td>$inv_data[surname]</td>
			<td>$inv_data[total]</td>
		</tr>";
		
		$total += $inv_data["total"];
	}
	
	if (empty($inv_out)) {
		$inv_out = "
		<tr class='".bg_class()."'>
			<td colspan='5'><li>No results found.</li></td>
		</tr>";
	}
	
	$OUTPUT = "
	<h3>Unscanned Invoices</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Barcode</th>
			<th>Invoice No.</th>
			<th>Sales Order No.</th>
			<th>Customer</th>
			<th>Total</th>
		</tr>
		$inv_out
		<tr class='".bg_class()."'>
			<td colspan='4'>Total</td>
			<td>".sprint($total)."</td>
		</tr>
	</table>";
	
	return $OUTPUT;
	
}
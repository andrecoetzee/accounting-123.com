<?php

require ("settings.php");
require ("picking_slips/picking_slip.lib.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);
	
	$sql = "SELECT sordid, invnum, cusname, total FROM cubit.invoices WHERE sordid!=0";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	
	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt))
	{
		$sql = "SELECT sordid FROM cubit.pslip_scans WHERE sordid='$inv_data[sordid]'";
		$scan_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");
		
		if (pg_num_rows($scan_rslt)) continue;
	
		$inv_out .= "
		<tr class='".bg_class()."'>
			<td>".pick_slip_barcode($sordid)."</td>
			<td>$inv_data[invnum]</td>
			<td>$inv_data[cusname]</td>
			<td>$inv_data[total]</td>
		</tr>";
	}

	if (empty($inv_out)) {
		$inv_out = "
		<tr class='".bg_class()."'>
			<td colspan='4'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Unscanned Invoices</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Barcode</th>
			<th>Invoice No.</th>
			<th>Customer</th>
			<th>Total</th>
		</tr>
		$inv_out
	</table>";
	
	return $OUTPUT;

}

?>

<?php

require ("../settings.php");
require ("picking_slip.lib.php");

if (!isset($_REQUEST["sordid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module";
} else {
	$OUTPUT = print_slip();
}

require ("../template.php");

function print_slip()
{
	extract ($_REQUEST);
	
	$sql = "SELECT stkcod, stkdes, qty FROM cubit.sorders_items
				LEFT JOIN cubit.stock ON sorders_items.stkid=stock.stkid
			WHERE sordid=$sordid";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
	
	$items_out = "";
	while ($items_data = pg_fetch_array($items_rslt)) {
		for ($i = 0; $i < $items_data["qty"]; $i++) {
			$items_out .= "
			<tr>
				<td width='20%'>$items_data[stkcod]</td>
				<td width='40%'>$items_data[stkdes]</td>
				<td style='border-bottom: 1px solid #000' width='20%'>&nbsp;</td>
				<td style='border-bottom: 1px solid #000' width='20%'>&nbsp;</td>
			</tr>";
		}
	}
	
	$OUTPUT = "
	<center>
	<table ".TMPL_tblDflts." width='90%' style='border: 1px solid #000'>
		<tr>
			<td><h2>Picking Slip</h2><td>
			<td align='right'><img src='".pick_slip_barcode($sordid)."' /></td>
		</tr>
		<tr>
			<td colspan='3'>
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th style='text-align: left'>Code</th>
					<th style='text-align: left'>Description</th>
					<th align='center'>Serial 1</th>
					<th align='center'>Serial 2</th>
				</tr>
				$items_out
			</table>
			</td>
		</tr>
	</table>";
	
	require ("../tmpl-print.php");
}
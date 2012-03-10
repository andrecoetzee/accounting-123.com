<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "select":
		$OUTPUT = select();
		break;
	case "view":
		$OUTPUT = view();
		break;
	}
} else {
	$OUTPUT = select();
}

function select()
{
	extract ($_REQUEST);

	// Retrieve periods
	global $PRDMON;
	$from_prds = finMonList("from_prd", $PRDMON[1]);
	$to_prds = finMonList("to_prd", PRD_DB);

	$OUTPUT = "
	<h3>Inventory Ledger</h3>
	<h4>Select Options</h4>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Report Settings</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Stock Items</td>
			<td>
				<input type='radio' name='items_rad' value='sel' />
				Selected Items
				<input type='radio' name='items_rad' value='all' />
			</td>
		</tr>";
	
	return $OUTPUT;
}

function view()
{
	extract ($_REQUEST);

	$sql = "SELECT stkid, stkcod, stkdes FROM cubit.stock";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$ledger_out = "";
	while (list($stkid, $stkcod, $stkdes) = pg_fetch_array($stock_rslt)) {
		$ledger_out .= "<h3>$stkcod - $stkdes</h3>";

		for ($i = $from_prd; $i <= $to_prd; $i++) {
			$ledger_out .= "
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='4'>&nbsp;</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<th>Date</th>
					<th>Details</th>
					<th>Qty</th>
					<th>Cost Amount</th>
					<th>Balance</th>
				</tr>";




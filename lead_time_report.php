<?php

require ("settings.php");
require_lib("manufact");

$OUTPUT = display();

$OUTPUT .= "<br>".mkQuickLinks(
	ql("purchase_dateqty_report.php", "Recommended Order Date and Qty"),
	ql("stock-view.php", "View Stock"),
	ql("stock-add.php", "Add Stock")
);

require ("template.php");




function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT supid FROM cubit.suppliers";
	$supp_rslt = db_exec($sql) or errDie("Unable to retrieve suppliers");

	while ($supp_data = pg_fetch_array($supp_rslt)) {
		recalculateLeadTimes($supp_data["supid"]);
	}

	$sql = "SELECT stkcod, stkdes, supname, lead_times.lead_time
				FROM cubit.lead_times
					LEFT JOIN cubit.stock
						ON lead_times.stkid = stock.stkid
					LEFT JOIN cubit.suppliers
						ON lead_times.supid = suppliers.supid
			WHERE stkcod ILIKE '$search%' OR stkdes ILIKE '$search%' OR
				supname ILIKE '$search%'
			ORDER BY stkcod, supname ASC";
	$lead_rslt = db_exec($sql) or errDie("Unable to retrieve lead_times.");

	$lead_out = "";
	while ($lead_data = pg_fetch_array($lead_rslt)) {
		$lead_out .= "<tr class='".bg_class()."'>
			<td>$lead_data[stkcod]</td>
			<td>$lead_data[stkdes]</td>
			<td>$lead_data[supname]</td>
			<td>$lead_data[lead_time] Days</td>
		</tr>";
	}

	if (empty($lead_out)) {
		$lead_out = "
			<tr class='".bg_class()."'>
				<td colspan='4'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Stock Lead Time Buffer Level Report</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Search</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' name='search' value='$search' /></td>
				<td><input type='submit' value='Search' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Supplier</th>
				<th>Lead Time</th>
			</tr>
			$lead_out
		</table>
		</center>";
	return $OUTPUT;

}


?>
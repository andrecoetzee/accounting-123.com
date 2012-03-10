<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$sql = "SELECT assets_hired.invnum, surname, des, assets_hired.invid,
				extract('epoch' FROM hired_time) AS e_hired,
				extract('epoch' FROM return_time) AS e_return
			FROM hire.assets_hired
				LEFT JOIN hire.notes_reprint
					ON assets_hired.invid=notes_reprint.invid
				LEFT JOIN cubit.customers
					ON assets_hired.cust_id=customers.cusnum
				LEFT JOIN cubit.assets
					ON assets_hired.asset_id=assets.id
			WHERE hired_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59' OR
				return_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
			ORDER BY invnum DESC";
	$history_rslt = db_exec($sql) or errDie("Unable to retrieve history.");

	$history_out = "";
	while ($history_data = pg_fetch_array($history_rslt)) {
		if ($history_data["e_return"]) {
			$return = date("d-m-Y", $history_data["e_return"]);
		} else {
			$return = "Still on Hire";
		}

		$history_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>H".getHirenum($history_data["invid"], 1)."</td>
			<td>$history_data[surname]</td>
			<td>$history_data[des]</td>
			<td>".date("d-m-Y", $history_data["e_hired"])."</td>
			<td>$return</td>
		</tr>";
	}

	if (empty($history_out)) {
		$history_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Hire History Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td>
				<input type='submit' value='Select' style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Hire No</th>
			<th>Company / Name</th>
			<th>Product</th>
			<th>Date Hired</th>
			<th>Date Returned</th>
		</tr>
		$history_out
	</table>";

	return $OUTPUT;
}

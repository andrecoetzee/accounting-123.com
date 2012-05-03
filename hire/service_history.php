<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

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

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "SELECT assets.id, des, description, service_description,
				extract('epoch' FROM timestamp) AS e_time
			FROM hire.service_history
				LEFT JOIN cubit.assets ON service_history.asset_id=assets.id
			WHERE timestamp BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'";
	$hist_rslt = db_exec($sql) or errDie("Unable to retrieve service history.");

	$hist_out = "";
	while ($hist_data = pg_fetch_array($hist_rslt)) {
		$hist_out .= "
		<tr class='".bg_class()."'>
			<td>".date("d-m-Y G:i:s", $hist_data["e_time"])."</td>
			<td>$hist_data[des]</td>
			<td>".getSerial($hist_data["id"])."</td>
			<td>$hist_data[service_description]</td>
		</tr>";
	}

	if (empty($hist_out)) {
		$hist_out = "
		<tr class='".bg_class()."'>
			<td colspan='4'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Service History Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' style='font-weight:bold' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date/Time</th>
			<th>Asset</th>
			<th>Serial</th>
			<th>Service Description</th>
		</tr>
		$hist_out
	</table>";

	return $OUTPUT;

}
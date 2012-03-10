<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= mkQuickLinks (
	ql("service_settings.php", "Service Settings"),
	ql("../workshop-view.php", "View Workshop")
);

require ("../template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"]= date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "SELECT assets.id, assets.des AS asset_des, asset_svdates.svdate,
				asset_svdates.des AS sv_des
			FROM cubit.asset_svdates
				LEFT JOIN cubit.assets
					ON asset_svdates.asset_id=assets.id
			WHERE asset_svdates.svdate BETWEEN '$from_date' AND '$to_date'";
	$sv_rslt = db_exec($sql) or errDie("Unable to retrieve asset services.");

	$service_out = "";
	while ($sv_data = pg_fetch_array($sv_rslt)) {
		if (empty($sv_data["id"])) {
			continue;
		}

		$service_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$sv_data[asset_des]</td>
			<td>".getSerial($sv_data["id"])."</td>
			<td>$sv_data[sv_des]</td>
			<td>$sv_data[svdate]</td>
		</tr>";
	}

	if (empty($service_out)) {
		$service_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Service Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
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
			<th>Asset</th>
			<th>Serial</th>
			<th>Service Description</th>
			<th>Service Date</th>
		</tr>
		$service_out
	</table>";

	return $OUTPUT;
}
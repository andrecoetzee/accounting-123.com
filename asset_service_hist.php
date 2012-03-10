<?php

require ("settings.php");

error_reporting(E_ALL);

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["frm_year"] = date("Y") - 1;
	$fields["frm_month"] = date("m");
	$fields["frm_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["group_id"] = 0;
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$frm_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	// Create the groups dropdown
	$sql = "SELECT * FROM cubit.assetgrp";
	$group_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");

	$group_sel = "<select name='group_id' style='width: 100%'>";
	$group_sel.= "<option value='0'>All</option>";

	while ($group_data = pg_fetch_array($group_rslt)) {
		if ($group_data["grpid"] == $group_id) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$group_sel .= "
		<option value='$group_data[grpid]' $sel>
			$group_data[grpname]
		</option>";
	}

	// Retrieve the service history -------------------------------------------

	$sql = "
	SELECT * FROM cubit.asset_svdates
	WHERE (svdate BETWEEN '$frm_date' AND '$to_date') ORDER BY svdate DESC";
	$service_rslt = db_exec($sql) or errDie("Unable to retrieve service dates.");

	$service_out = "";

	while ($service_data = pg_fetch_array($service_rslt)) {
		// Should we search by groups
		if ($group_id) {
			$group_sql = "AND grpid='$group_id'";
		} else {
			$group_sql = "";
		}

		// Retrieve the asset information
		$sql = "
		SELECT * FROM cubit.assets
		WHERE id='$service_data[asset_id]' $group_sql AND (
			des ILIKE '$search%' OR serial ILIKE '$search%' OR
			serial2 ILIKE '$search%')";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset data.");

		if (!pg_num_rows($asset_rslt)) {
			continue;
		}

		$asset_data = pg_fetch_array($asset_rslt);

		// Retrieve the asset group
		$sql = "SELECT * FROM cubit.assetgrp WHERE grpid='$asset_data[grpid]'";
		$group_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");
		$group_data = pg_fetch_array($group_rslt);

		$service_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$service_data[svdate]</td>
			<td>$group_data[grpname]</td>
			<td>$asset_data[des]</td>
			<td>$asset_data[serial]</td>
			<td>$asset_data[serial2]</td>
		</tr>";
	}

	if (empty($service_out)) {
		$service_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'>No results found.</td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Assets Service History</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><b>&nbsp; From &nbsp;</b></td>
			<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
			<td><b>&nbsp; To &nbsp;</b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
		</tr>
		<tr>
			<th colspan='2'>Search</th>
			<th colspan='2'>Group</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'>
				<input type='text' name='search' value='$search'
				style='width:100%' />
			</td>
			<td colspan='2'>$group_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4' align='center'>
				<input type='submit' value='Search &raquo' />
			</td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Service Date</th>
			<th>Group</th>
			<th>Description</th>
			<th>Serial No</th>
			<th>2nd Serial No</th>
		</tr>
		$service_out
	</table>
	</center>";

	return $OUTPUT;
}
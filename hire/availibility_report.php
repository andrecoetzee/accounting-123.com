<?php

require ("../settings.php");

error_reporting(E_ALL);
define("SECONDS_IN_DAY", 86400);
define("AVAILABLE", "#00ff00");
define("HIRED_OUT", "#ff0000");
define("BOOKED", "#ffa200");
define("WORKSHOP", "#ffff00");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("t");
	$fields["group_id"] = -1;
	$fields["type_id"] = -1;

	extract ($fields, EXTR_SKIP);

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$sql = "SELECT * FROM cubit.assetgrp ORDER BY grpname ASC";
	$group_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");

	if ($group_id == -1) {
		$none_sel = "selected='selected'";
		$all_sel = "";
	} elseif ($group_id == 0) {
		$none_sel = "";
		$all_sel = "selected='selected'";
	} else {
		$none_sel = "";
		$all_sel = "";
	}

	$group_sel = "<select name='group_id' style='width: 100%'
				  onchange='javascript:document.form.submit()'>";
	$group_sel .= "<option value='-1' $none_sel>[None]</option>";
	$group_sel .= "<option value='0' $all_sel>[All]</option>";
	while ($group_data = pg_fetch_array($group_rslt)) {
		if ($group_id == $group_data["grpid"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$group_sel .= "<option value='$group_data[grpid]' $sel>
			$group_data[grpname]
		</option>";
	}
	$group_sel .= "</select>";

	$sql = "SELECT * FROM cubit.asset_types ORDER BY name ASC";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");

	if ($type_id == -1) {
		$none_sel = "selected='selected'";
		$all_sel = "";
	} elseif ($type_id == 0) {
		$none_sel = "";
		$all_sel = "selected='selected'";
	} else {
		$none_sel = "";
		$all_sel = "";
	}

	$type_sel = "<select name='type_id' style='width: 100%'
				 onchange='javascript:document.form.submit()'>";
	$type_sel .= "<option value='-1' $none_sel>[None]</option>";
	$type_sel .= "<option value='0' $all_sel>[All]</option>";
	while($type_data = pg_fetch_array($type_rslt)) {
		if ($type_id == $type_data["id"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$type_sel .= "<option value='$type_data[id]' $sel>
						$type_data[name]
					  </option>";
	}
	$type_sel .= "</select>";

	$OUTPUT = "
	<center>
	<h3>Availability Report</h3>
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td bgcolor='#ff0000'>&nbsp;</td><td>Hired Out</td>
			<td bgcolor='#00ff00'>&nbsp;</td><td>Available</td>
			<td bgcolor='#ffa200'>&nbsp;</td><td>Booked</td>
			<td bgcolor='#ffff00'>&nbsp;</td><td>In Workshop</td>
		</tr>
	</table>
	<p></p>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b> To </b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select &raquo' /></td>
		</tr>
		<tr><td colspan='4'>
		<table ".TMPL_tblDflts." width='100%'>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' width='50%'>$group_sel</td>
				<td colspan='2' width='50%'>$type_sel</td>
			</tr>
		</table>
		</td></tr>
	</table>
	</form>";

	$where = array();
	if ($group_id) {
		$where[] = "grpid='$group_id'";
	}
	if ($type_id) {
		$where[] = "type_id='$type_id'";
	}

	if (count($where)) {
		$where = "WHERE ".implode (" AND ", $where);
	} else {
		$where = "";
	}

	// Retrieve assets
	$sql = "SELECT * FROM cubit.assets $where";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$assets_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"])) {
			continue;
		}

		$assets_out .= "<tr bgcolor='".bgcolorg()."'>
			<td align='center'>
				".getSerial($asset_data["id"], 1)."<br />
				$asset_data[des]
			</td>
			".availability($from_date, $to_date, $asset_data["id"])."
		</tr>";
	}

	// Do the headers here so we can choose not to show it when no results found
	$headers = date_headers($from_date, $to_date);

	if (empty($assets_out)) {
		$headers = "";
		$assets_out .= "<tr bgcolor='".bgcolorg()."'>
			<td><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT .= "<table ".TMPL_tblDflts.">
		<tr>
			<th>Plant</th>
			$headers
		</tr>
		$assets_out
	</table>";

	return $OUTPUT;
}

function date_headers($from_date, $to_date)
{
	$e_from = getDTEpoch("$from_date 0:00:00");
	$e_to = getDTEpoch("$to_date 23:59:59");

	$headers = "";
	for ($i = $e_from; $i < $e_to; $i += SECONDS_IN_DAY) {
		$headers .= "
		<th style='font-weight: none; font-size: 9px;' colspan='5'>
			".date("d-m-Y", $i)."
		</th>";
	}

	return $headers;
}

function availability($from_date, $to_date, $asset_id)
{
	$e_from = getDTEpoch("$from_date 0:00:00");
	$e_to = getDTEpoch("$to_date 23:59:59");

	$row = "";
	for ($i = $e_from; $i < $e_to; $i += SECONDS_IN_DAY) {
		for ($j = 0; $j < 5; $j++) {
			if (inWorkshop($asset_id, date("Y-m-d", $i))) {
				$color = WORKSHOP;
			} elseif (isHired($asset_id, date("Y-m-d", $i))) {
				$color = HIRED_OUT;
			} elseif (isBooked($asset_id, date("Y-m-d", $i))) {
				$color = BOOKED;
			} else {
				$color = AVAILABLE;
			}

			$row .= "<td bgcolor='$color'></td>";
		}
	}

	return $row;
}


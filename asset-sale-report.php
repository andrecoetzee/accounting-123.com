<?php

require ("settings.php");

$OUTPUT= display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["group_id"] = 0;
	$fields["type_id"] = 0;

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT grpid, grpname FROM cubit.assetgrp ORDER BY grpname ASC";
	$group_rslt = db_exec($sql) or errDie("Unable to retrieve groups.");

	$group_sel = "
	<select name='group_id' style='width: 100%'>
		<option value='0'>[All]</option>";
	while ($group_data = pg_fetch_array($group_rslt)) {
		$sel = ($group_id == $group_data["grpid"]) ? "selected='t'" : "";
		$group_sel .= "
		<option value='$group_data[grpid]'>
			$group_data[grpname]
		</option>";
	}
	$group_sel .= "</select>";

	$sql = "SELECT id, name FROM cubit.asset_types ORDER BY name ASC";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset types.");

	$type_sel = "
	<select name='type_id' style='width: 100%'>
		<option value='0'>[All]</option>";
	while($type_data = pg_fetch_array($type_rslt)) {
		$sel = ($type_id == $type_data["id"]) ? "selected='t'" : "";
		$type_sel .= "
		<option value='$type_data[id]'>
			$type_data[name]
		</option>";
	}
	$type_sel .= "</select>";


	$OUTPUT = "
	<center>
	<h3>Asset Sale Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><b>From</b> &nbsp;</td>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
		</tr>
		<tr>
			<th colspan='2'>Group</th>
			<th colspan='2'>Type</th>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='2'>$group_sel</td>
			<td colspan='2'>$type_sel</td>
		</tr>
		<tr>
			<td colspan='4' align='center'>
				<input type='submit' value='Select' style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>";

	$where_ar = array();
	if ($group_id > 0) {
		$where_ar[] = "grpid='$group_id'";
	}
	if ($type_id > 0) {
		$where_ar[] = "type_id='$type_id'";
	}
	$where_sql = implode(" AND ", $where_ar);
	if (!empty($where_sql)) {
		$where_sql .= " AND ";
	}


	$sql = "
	SELECT id, des, serial, serial2, nonserial, saledate, saleamt
	FROM cubit.assets_prev
	WHERE $where_sql div='".USER_DIV."' AND remaction='Sale'";
	$sale_rslt = db_exec($sql) or errDie("Unable to retrieve sales.");

	$sale_out = "";
	while ($sale_data = pg_fetch_array($sale_rslt)) {
		if ($sale_data["nonserial"]) {
			$qty_sold = $sale_data["serial2"];
		} else {
			$qty_sold = 1;
		}

		$sale_out .= "
		<tr class='".bg_class()."'>
			<td>$sale_data[des]</td>
			<td>$sale_data[serial]</td>
			<td align='center'>$qty_sold</td>
			<td align='center'>$sale_data[saledate]</td>
			<td align='right'>".sprint($sale_data["saleamt"])."</td>
			<td align='right'>$sale_data[accdep]</td>
		</tr>";
	}

	if (empty($sale_out)) {
		$sale_out .= "
		<tr class='".bg_class()."'>
			<td colspan='5'>
				<li>No results found from your current selection</li>
			</td>
		</tr>";
	}

	$OUTPUT .= "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Asset</th>
			<th>Serial</th>
			<th>Qty Sold</th>
			<th>Sale Date</th>
			<th>Amount</th>
			<th>Accumulated Depreciation</th>
			<th>Profit / Loss</th>
		</tr>
		$sale_out
	</table>
	</center>";

	return $OUTPUT;
}
			

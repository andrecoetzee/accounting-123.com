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
	$fields["perc_search"] = "100";
	$fields["export"] = 0;

	extract($fields, EXTR_SKIP);

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$sql = "SELECT id, serial, des, grpname
			FROM cubit.assets
				LEFT JOIN cubit.assetgrp ON assets.grpid=assetgrp.grpid
			ORDER BY serial ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
	$asset_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		$percentage = utilisationPerc($asset_data["id"], $from_date, $to_date);
		if (!is_numeric($perc_search) || $percentage > $perc_search) {
			continue;
		}
		$asset_out .= "<tr class='".bg_class()."'>
			<td>$asset_data[grpname]</td>
			<td>".getSerial($asset_data["id"])."</td>
			<td>$asset_data[des]</td>
			<td align='center'>
				<b>".utilisationDays($asset_data["id"], $from_date, $to_date)."</b>
			</td>
			<td align='center' >
				<b>$percentage%</b>
			</td>
			<td width='2%' bgcolor='".ext_progressColor($percentage)."'>&nbsp;</td>
		</tr>";
	}

	if (empty($asset_out)) {
		$asset_out = "<tr class='".bg_class()."'>
			<td colspan='6'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Hire Utilisation Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='3'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b> To </b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
		</tr>
		<tr>
			<th colspan='3'>Utilisation Percentage Filter (Less Than or Equal)</th>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='3' align='center'>
				<input type='text' name='perc_search' value='$perc_search'
				size='2' style='text-align: center' />%
			</td>
		</tr>
		<tr>
			<td colspan='3' align='center'>
				<input type='submit' value='Apply to Report'
				style='font-weight: bold; font-size: 1.1em' />
			</td>
		</tr>
	</table>
	</form>";

	$OUTPUT .= $xls_out = "
	<h3>Hire Utilisation Report For $from_date to $to_date</h3>
	<table ".TMPL_tblDflts." width='70%'>
		<tr>
			<th>Group</th>
			<th>Serial</th>
			<th>Description</th>
			<th>Days Hired</th>
			<th>Utilisation Percentage</th>
			<th width='2%'>&nbsp;</th>
		</tr>
		$asset_out
	</table>";

	$OUTPUT .= "
	<form method='post' action='".SELF."'>
		<input type='hidden' name='export' value='1' />
		<input type='submit' value='Export to Spreadsheet' />
	</form>
	</center>";

	if ($export) {
		$xls_out = clean_html($xls_out);
		require_lib("xls");
		StreamXLS("hire_utilisation" , $xls_out);
	}

	return $OUTPUT;
}

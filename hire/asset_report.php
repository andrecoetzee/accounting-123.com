<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .=
	mkQuickLinks(
		ql("../asset-view.php", "View Assets"),
		ql("../asset-new.php", "Add Asset")
	);

require ("../template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["search"] = "";
	$fields["type_id"] = 0;
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("t");

	extract($fields, EXTR_SKIP);

	if ($type_id) {
		$type_sql = "AND assets.type_id='$type_id'";
	} else {
		$type_sql = "";
	}

	$sql = "SELECT qty, des, serial, customers.cusnum, customers.cusname,
				customers.surname, to_date, invnum, hire_invitems.invid,
				hire_invitems.id, done, printed, grpname, name
				FROM hire.hire_invitems
					LEFT JOIN cubit.assets
						ON hire_invitems.asset_id = assets.id
					LEFT JOIN hire.hire_invoices
						ON hire_invitems.invid = hire_invoices.invid
					LEFT JOIN cubit.customers
						ON hire_invoices.cusnum = customers.cusnum
					LEFT JOIN cubit.assetgrp
						ON assets.grpid=assetgrp.grpid
					LEFT JOIN cubit.asset_types
						ON assets.type_id=asset_types.id
				WHERE done='y' $type_sql AND remaction IS NULL AND
					printed='y' AND (cast(qty as text) ILIKE '%$search%' OR
					serial ILIKE '%$search%' OR
					customers.cusname ILIKE '$search%' OR
					customers.surname ILIKE '$search%' OR
					cast(to_date as text) ILIKE '%$search%' OR
					cast(invnum as text) ILIKE '%$search%'
					OR grpname ILIKE '%$search%' OR name ILIKE '%$search%')
				ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$sql = "SELECT id, name FROM cubit.asset_types";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset types.");

	$type_sel = "<select name='type_id' onchange='javascript:document.form.submit()'
				  style='width: 100%'>";
	$type_sel.= "<option value='0'>[All]</option>";
	while ($type_data = pg_fetch_array($type_rslt)) {
		if ($type_data["id"] == $type_id) {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}

		$type_sel .= "
		<option value='$type_data[id]' $sel>
			$type_data[name]
		</option>";
	}
	$type_sel.= "</select>";


	$hired_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		$hired_out .= "<tr class='".bg_class()."'>
			<td align='center'>
				<a href='javascript:popupOpen".
				"(\"hire-invoice-new.php?invid=$asset_data[invid]\")'>
					H".getHirenum($asset_data["invid"], 1)."
				</a>
			</td>
			<td>$asset_data[grpname]</td>
			<td>$asset_data[name]</td>
			<td>$asset_data[des]</td>
			<td>$asset_data[serial]</td>
			<td align='center'>$asset_data[qty]</td>
			<td>$asset_data[cusname] $asset_data[surname]</td>
			<td align='center'>".returnDate($asset_data["id"])."</td>
		</tr>";
	}

	// Display something atleast, even though we've got no results.
	if (empty($hired_out)) {
		$hired_out = "<tr class='".bg_class()."'>
			<td colspan='8'><li>No results found.</li></td>
		</tr>";
	}

	// Available assets -------------------------------------------------------
	$sql = "SELECT grpname, name, des, assets.id, serial2
			FROM cubit.assets
				LEFT JOIN cubit.assetgrp ON assets.grpid=assetgrp.grpid
				LEFT JOIN cubit.asset_types ON assets.type_id=asset_types.id
			WHERE remaction is NULL AND (grpname ILIKE '%$search%' OR
				name ILIKE '%$search%' OR des ILIKE '%$search%') $type_sql
			ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	$available_out = "";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (isHired($asset_data["id"])) continue;

		$available_out .= "
		<tr class='".bg_class()."'>
			<td>$asset_data[grpname]</td>
			<td>$asset_data[name]</td>
			<td>$asset_data[des]</td>
			<td>".getSerial($asset_data["id"])."</td>
			<td>".getUnits($asset_data["id"])."</td>
		</tr>";
	}

	if (empty($available_out)) {
		$available_out = "<tr class='".bg_class()."'>
			<td colspan='5'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Asset Report</th>
	<br /><br />
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
		<tr class='".bg_class()."'><td colspan='4' align='center'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Search</th>
				<th>Asset Type</th>
			</tr>
			<tr>
				<td><input type='text' name='search' value='$search' /></td>
				<td><input type='submit' value='Search' /></td>
				<td>$type_sel</td>
			</tr>
		</table>
		</td></tr>
	</table>
	</form>
	<h3>Hired Out</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Hire No</th>
			<th>Asset Group</th>
			<th>Asset Type</th>
			<th>Asset</th>
			<th>Serial</th>
			<th>Qty</th>
			<th>Customer</th>
			<th>Expected Return</th>
		</tr>
		$hired_out
	</table>
	<h3>Available</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Asset Group</th>
			<th>Asset Type</th>
			<th>Asset</th>
			<th>Serial</th>
			<th>Qty</th>
		</tr>
		$available_out
	</table>";

	return $OUTPUT;
}

<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	define ("HOUR", 60 * 60);
	define ("DAY", HOUR * 24);
	define ("WEEK", DAY * 7);
	define ("TOMMOROW", time() + DAY);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["total_invoiced"] = 0;
	$fields["total_not_invoiced"] = 0;

	extract($fields, EXTR_SKIP);

	$from_time = mktime(0, 0, 0, $from_month, $from_day, $from_year);
	$to_time = mktime(0, 0, 0, $to_month, $to_day, $to_year);

	$from_date = date("Y-m-d", $from_time);
	$to_date = date("Y-m-d", $to_time);

	// Make sure the date selection is not the same
	if ($from_date == $to_date) {
		$to_date = date("Y-m-d", TOMMOROW);
	}

	$OUTPUT = "
	<center>
	<h3>Hire by Period Detail Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' style='font-weight: bold' /></td>
		</tr>
	</table>
	</form>";

	$sql = "SELECT assets_hired.id, invnum, assets.id AS asset_id, des, surname,
				hired_time, return_time, inv_invnum, value, item_id, cust_id,
				assets.grpid, grpname, assets_hired.invid, qty, basis, weekends,
				extract('epoch' FROM hired_time) AS e_hired,
				extract('epoch' FROM return_time) AS e_return
			FROM hire.assets_hired
				LEFT JOIN cubit.assets ON assets_hired.asset_id=assets.id
				LEFT JOIN cubit.customers ON assets_hired.cust_id=customers.cusnum
				LEFT JOIN cubit.assetgrp ON assets.grpid=assetgrp.grpid
			WHERE
				hired_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
				OR return_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
				OR (hired_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
					AND return_time IS NULL)
			ORDER BY inv_invnum, invnum DESC";
	$details_rslt = db_exec($sql) or errDie("Unable to retrieve details.");

	$items_out = array();
	while ($details_data = pg_fetch_array($details_rslt)) {
		// Is this within one of the valid asset hire groups
		if (!preg_match("/(Equipment|Plant)/", $details_data["grpname"])) {
			continue;
		}

		if (!$details_data["inv_invnum"]) {
			$sql = "SELECT amt, basis, extract('epoch' FROM to_date) AS e_to
					FROM hire.hire_invitems
					WHERE id='$details_data[item_id]'";
			$item_rslt = db_exec($sql) or errDie("Unable to retrieve amount.");
			list($value, $basis, $e_to) = pg_fetch_array($item_rslt);

			$expected_disp = "";

			// Calculate the amounts for items not yet returned
			switch ($basis) {
				case "per_hour":
					$hours_beyond_expected = ($to_time - $e_to) / HOUR;

					if ($hours_beyond_expected) {
						$basis_per_hour = basisPrice($details_data["cust_id"],
										  $details_data["id"], $basis);

						$value_beyond = $basis_per_day * $days_beyond_expected;
						$details_data["value"] = $value + $value_beyond;
					} else {
						$details_data["value"] = $value;
					}
				case "per_day":
					$days_beyond_expected = ($to_time - $e_to);
					
					$sundays = 0;
					for ($i = $details_data["hired_time"];
						 $i <= $days_beyond_expected; $i+=60*60*24) {
						if (date("w", $i) == 0) {
							$sundays++;
						}
					}

					if ($details_data["weekends"]) {
						$show_sundays = $sundays + (0.6 * $sundays);
					} else {
						$show_sundays = 0;
					}

					$days_beyond_expected /= (DAY - $show_sundays);
					if ($days_beyond_expected) {
						$basis_per_day = basisPrice($details_data["cust_id"],
										 $details_data["id"], $basis);

						$value_beyond = $basis_per_day * $days_beyond_expected;
						$details_data["value"] = $value + $value_beyond;
					} else {
						$details_data["value"] = $value;
					}
					break;
				case "per_week":
					$weeks_beyond_expected = ($to_time - $e_to) / WEEK;

					if ($weeks_beyond_expected) {
						$basis_per_week = basis_price($details_data["cust_id"],
										  $details_data["id"], $basis);

						$value_beyond = $basis_per_day * $days_beyond_expected;
						$details_data["value"] = $value + $value_beyond;
					} else {
						$details_data["value"] = $value;
					}
				break;
			}

			$total_not_invoiced += $details_data["value"];
		} else {
			$total_invoiced += $details_data["value"];
		}

		$total_hire = $total_invoiced + $total_not_invoiced;

		if (empty($details_data["return_time"])) {
			$return_disp = "
			<span style='color: #f00'>
				(Still on Hire) ".date("d-m-Y", $to_time)."
			</span>";
		} else {
			$return_disp = date("d-m-Y G:i:s", $details_data["e_return"]);
		}
		
		// Units Days / Hours
		$days_hours = "";
		if ($details_data["basis"] == "per_day" ||
			$details_data["basis"] == "per_week") {
			
			$days_hours = "DAY";
		} else {
			$days_hours = "HOUR";
		}
		
		// Invoiced / Forecasted
		if (!isset($totals[$details_data["grpid"]]["forecast"]))
			$totals[$details_data["grpid"]]["forecast"] = 0.00;
		
		if (!isset($totals[$details_data["grpid"]]["invoiced"]))
			$totals[$details_data["grpid"]]["invoiced"] = 0.00;
		
		if (empty($details_data["return_time"])) {
			$forecast_value = $details_data["value"];
			$invoiced_value = "";
			
			$totals[$details_data["grpid"]]["forecast"] += $details_data["value"];
		} else {
			$forecast_value = "";
			$invoiced_value = $details_data["value"];
			
			$totals[$details_data["grpid"]]["invoiced"] += $details_data["value"];
		}
		
		// Weekends
		if ($details_data["weekends"]) {
			$weekends = "w/e charge";
		} else {
			$weekends = "";
		}
		
		$sql = "SELECT qty FROM hire.hire_nons_inv_items WHERE invid='$details_data[inv_invnum]' AND asset_id='$details_data[asset_id]'";
		$invqty_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$invqty = pg_fetch_result($invqty_rslt, 0);
		
		if (empty($invqty)) $invqty = 0;
			
		$items_out[$details_data["grpid"]][] = "
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>H".getHirenum($details_data["invid"], 1)."</td>
			<td align='center'>$details_data[inv_invnum]</td>
			<td align='center'>$details_data[surname]</td>
			<td>".getSerial($details_data["asset_id"], 1)." $details_data[des]</td>
			<td align='right'>$invqty</td>
			<td>&nbsp;</td>
			<td align='center'>$days_hours</td>
			<td>".date("d-m-Y", $details_data["e_hired"])."</td>
			<td>".returnDate($details_data["item_id"])."</td>
			<td align='right'>".sprint($invoiced_value)."</td>
			<td>$weekends</td>
			<td align='right'>".sprint($forecast_value)."</td>
			<!--
			<td align='center'>".detailsHoursHired($details_data["id"])."</td>
			<td align='center'>".detailsDaysHired($details_data["id"])."</td>
			<td align='center'>".detailsWeeksHired($details_data["id"])."</td>
			<td align='center'>".date("d-m-Y G:i:s", $details_data["e_hired"])."</td>
			<td align='center'>$return_disp</td>
			<td align='right'>".sprint($details_data["value"])."</td>
			-->
		</tr>";
	}

	if (!count($items_out)) {
		$details_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='7'><li>No results found.</li></td>
		</tr>";
	}

	if (!isset($total_hire)) $total_hire = 0;

	$OUTPUT .= "
	<table ".TMPL_tblDflts.">
		<tr>
			<th style='padding-right: 10px'>Total Invoiced Hire</th>
			<th style='padding-left: 10px'>Total Non Invoiced Hire</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>".sprint($total_invoiced)."</td>
			<td align='center'>".sprint($total_not_invoiced)."</td>
		</tr>
		<tr>
			<th colspan='2'>Total Hire</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center' colspan='2'><b>".sprint($total_hire)."</b></td>
		</tr>
	</table>
	<br />";

	foreach ($items_out as $grpid=>$lv2) {
		$sql = "SELECT grpname FROM cubit.assetgrp WHERE grpid='$grpid'";
		$group_rslt = db_exec($sql) or errDie("Unable to retrieve asset groups.");
		$group_name = pg_fetch_result($group_rslt, 0);

		$OUTPUT .= "<h3>$group_name Hire Revenue</h3>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<th>Hire Number</th>
				<th>Invoice Number</th>
				<th>Customer</th>
				<th>Asset</th>
				<th>Units Invoiced</th>
				<th>Units Forecast to ".date("d M")."</th>
				<th>Units Days / Hours</th>
				<th>Date Out</th>
				<th>Expect / Actual</th>
				<th>Rand (Ex VAT) Invoiced
				<th>&nbsp;</th>
				<th>Rand (Ex VAT) Forecast</th>
				<!--
				<th>Hours Hired</th>
				<th>Days Hired</th>
				<th>Weeks Hired</th>
				<th>Date Hired</th>
				<th>Date Returned</th>
				<th>Value (Ex VAT)</th>
				-->
			</tr>";

		foreach ($items_out[$grpid] as $line_disp) {
			$OUTPUT .= $line_disp;
		}

		$OUTPUT .= "</table>";
	}

	return $OUTPUT;
}

?>
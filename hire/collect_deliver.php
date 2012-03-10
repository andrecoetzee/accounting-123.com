<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["date_year"] = date("Y");
	$fields["date_month"] = date("m");
	$fields["date_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$date = dateFmt($date_year, $date_month, $date_day);

	for ($i = 0; $i < 2; $i++) {
		if ($i) {
			$collection = "collect";
		} else {
			$collection = "deliver";
		}

		$sql = "SELECT * FROM hire.collection
				WHERE $collection='1'
					AND time BETWEEN '$date 0:00:00' AND '$date 23:59:59'";
		$clct_rslt = db_exec($sql) or errDie("Unable to retrieve collection.");

		while ($clct_data = pg_fetch_array($clct_rslt)) {
			if (!$clct_data["item_id"]) {
				continue;
			}

			$sql = "SELECT * FROM cubit.assets WHERE id='$clct_data[asset_id]'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

			$sql = "SELECT * FROM hire.hire_invitems WHERE id='$clct_data[item_id]'";
			$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
			$item_data = pg_fetch_array($item_rslt);

			if (empty($item_data["invid"])) continue;

			$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$item_data[invid]'";
			$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
			$inv_data = pg_fetch_array($inv_rslt);

			while ($asset_data = pg_fetch_array($asset_rslt)) {
				$$collection .= "<tr bgcolor='".bgcolorg()."'>
					<td>H".getHirenum($inv_data["invid"], 1)."</td>
					<td>$asset_data[des]</td>
					<td>$inv_data[cusname] $inv_data[surname]</td>
					<td>".hireAddress($inv_data["invid"])."</td>
					<td>".getSerial($asset_data["id"])."</td>
				</tr>";
			}
		}

		if (empty($$collection)) {
			$$collection = "<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><li>No results found.</li></td>
			</tr>";
		}
	}

	$OUTPUT = "<center>
	<h3>Collect and Deliver Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='2'>Date</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			<td><input type='submit' value='Select' style='font-weight: bold' /></td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='5'>Deliver</th>
		</tr>
		<tr>
			<th>Hire No</th>
			<th>Asset</th>
			<th>Customer</th>
			<th>Address</th>
			<th>Serial</th>
		</tr>
		$deliver
		<tr>
			<th colspan='5'>Collect</th>
		</tr>
		<tr>
			<th>Hire No</th>
			<th>Asset</th>
			<th>Customer</th>
			<th>Address</th>
			<th>Serial</th>
		</tr>
		$collect
	</table>
	</center>";

	return $OUTPUT;
}

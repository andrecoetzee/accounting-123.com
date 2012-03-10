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
	$fields["print"] = 0;

	extract ($fields, EXTR_SKIP);

	if (!$print) {
		$OUTPUT = "<center>
		<h3>Driver Collect/Deliver</h3>
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
			<tr><td>&nbsp</td></tr>
			<tr>
				<td colspan='4' align='center'>
					<input type='submit' name='print' value='Print' />
				</td>
			</tr>
		</table>
		</form>";
	} else {
		$OUTPUT = "";
	}

	$sql = "
	SELECT hire_invoices.invid, hire_invitems.collection, customers.surname,
		invnum, branch_addr, branch_descrip, addr1, bustel, cellno
		FROM hire.hire_invitems
			LEFT JOIN hire.hire_invoices
				ON hire_invitems.invid=hire_invoices.invid
			LEFT JOIN cubit.customers
				ON hire_invoices.cusnum=customers.cusnum
			LEFT JOIN cubit.customer_branches
				ON customers.cusnum=customer_branches.cusnum";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve hire note items.");

	$item_out = "";
	while ($item_data = pg_fetch_array($item_rslt)) {
		// Parse collection
		$collection = explode(", ", $item_data["collection"]);
		foreach ($collection as $value) {
			if ($value == "Client Collect") continue;

			if ($item_data["branch_addr"]) {
				$address = nl2br($item_data["branch_descrip"]);
			} else {
				$address = nl2br($item_data["addr1"]);
			}

			$item_out .= "
			<table ".TMPL_tblDflts." width='400' style='border: 1px solid #000'>
				<tr bgcolor='".bgcolorg()."'>
					<td><b>$item_data[surname]</b></td>
					<td>".ucfirst($value)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Hire No: H".getHirenum($item_data["invid"], 1)."</td>
					<td>Date:_____________________</td>
				</td>
				<tr bgcolor='".bgcolorg()."'>
					<td>Business Tel: $item_data[bustel]</td>
					<td>Cell No: $item_data[cellno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>$address</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td style='padding-top: 10px'>Signature (Driver)</td>
					<td>___________________________</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td style='padding-top: 10px'>Signature (Recipient)</td>
					<td>___________________________</td>
				</tr>
			</table>
			<br />";
		}
	}

	$OUTPUT .= "$item_out";

	if ($print) {
		$OUTPUT = clean_html($OUTPUT);
		require ("../tmpl-print.php");
	} else {
		return $OUTPUT;
	}
}
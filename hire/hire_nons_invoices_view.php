<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);
	
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	if (is_numeric($search)) {
		$invnum_w = "OR invnum='$search'";
	} else {
		$invnum_w = "";
	}

	$sql = "
	SELECT invid, invnum, cusname, total, hire_invid, accepted, hire_invnum,
		odate
	FROM cubit.nons_invoices
	WHERE done='y' AND hire_invid>0 AND cusname ILIKE '%$search%' $invnum_w
		AND (odate BETWEEN '$from_date' AND '$to_date')
	ORDER BY invnum DESC";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve hire invoices.");

	$total = 0;
	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		if ($inv_data["accepted"] != "note") {
			$cnote = "
			<td>
				<a href='hire-invoice-note.php?invid=$inv_data[invid]'>
					Credit Note
				</a>
			</td>";
		} else {
			$cnote = "<td>&nbsp;</td>";
		}

		$inv_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[odate]</td>
			<td>
				<a href='javascript:printer(\"hire/hire_note_reprint.php?invid=$inv_data[hire_invid]\")'>
					H$inv_data[hire_invnum]
				</a>
			</td>
			<td>$inv_data[invnum]</td>
			<td>$inv_data[cusname]</td>
			<td>".CUR.sprint($inv_data["total"])."</td>
			<td><a href='javascript:popupOpen(\"nons-invoice-reprint.php?invid=$inv_data[invid]\")'>Reprint</a></td>
			$cnote
		</tr>";

		$total += $inv_data["total"];
	}

	if (empty($inv_out)) {
		$inv_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='8'><li>Please enter customer name or hire no.</li></td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>View Hire Invoices</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	<br />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Enter Hire No or Customer Name</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' style='width: 100%' /></td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Hire No</th>
			<th>Invoice No</th>
			<th>Customer</th>
			<th>Total</th>
			<th colspan='2'>Options</th>
		</tr>
		$inv_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><strong>Total</strong></td>
			<td>".CUR.sprint($total)."</td>
			<td colspan='2'>&nbsp;</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}

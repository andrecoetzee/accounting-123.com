<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT *,
				(SELECT cusname FROM cubit.customers WHERE cusnum=cusnum),
				(SELECT surname FROM cubit.customers WHERE cusnum=cusnum)
			FROM hire.hire_invoices
			WHERE done='y' AND printed='y' ORDER BY odate DESC";
	$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve hire notes.");

	$notes_out = "";
	while ($hinv_data = pg_fetch_array($hinv_rslt)) {
		// Check if we've got a signed hire note
		$sql = "SELECT * FROM hire.signed_hirenotes WHERE invid='$hinv_data[invid]'";
		$sh_rslt = db_exec($sql) or errDie("Unable to check for scanned hire note.");

		if (pg_num_rows($sh_rslt)) {
			continue;
		}

		$notes_out .= "<tr class='".bg_class()."'>
			<td>$hinv_data[odate]</td>
			<td>H".getHirenum($hinv["invid"], 1)."</td>
			<td>$hinv_data[surname] $hinv_data[cusname]</td>
			<td>
				<a href='javascript:popupOpen(\"signed_hirenote_save.php?invid=$hinv_data[invid]\")'>
					Signed Hire Note
				</a>
			</td>
		</tr>";
	}

	if (empty($notes_out)) {
		$notes_out = "<tr class='".bg_class()."'>
			<td colspan='4'><li>No unsigned hire notes found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Unsigned Hire Notes</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Hire No</th>
			<th>Customer</th>
			<th>Options</th>
		</tr>
		$notes_out
	</table>
	</center>";

	return $OUTPUT;
}

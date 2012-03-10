<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

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

	extract ($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	if (isset($id)) {
		$where = "AND doc_id='$id'";
	} else {
		$where = "";
	}

	$sql = "SELECT *,extract('epoch' FROM timestamp) AS e_time FROM cubit.document_movement
	WHERE (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59') $where ORDER BY timestamp DESC";
	$dm_rslt = db_exec($sql) or errDie("Unable to retrieve document movement");

	$dm_out = "";
	while ($dm_data = pg_fetch_array($dm_rslt)) {
		$bgcolor = bgcolorg();

		$dm_out .= "<tr bgcolor='$bgcolor'>
			<td>".date("d-m-Y G:i:s", $dm_data["e_time"])."</td>
			<td>$dm_data[doc_id]</td>
			<td>$dm_data[project]</td>
			<td>$dm_data[area]</td>
			<td>$dm_data[discipline]</td>
			<td>$dm_data[doc_type]</td>
			<td>$dm_data[revision]</td>
			<td>$dm_data[drawing_num]</td>
			<td>$dm_data[sheet_num]</td>
			<td>$dm_data[title]</td>
			<td>$dm_data[location]</td>
			<td>$dm_data[contract]</td>
			<td>$dm_data[contractor]</td>
			<td>$dm_data[code]</td>
			<td>$dm_data[issue_for]</td>
			<td>$dm_data[qs]</td>
			<td>$dm_data[status]</td>
		</tr>
		<tr bgcolor='$bgcolor'>
			<td colspan='20'>$dm_data[movement_description]</td>
		</tr>";
	}

	if (empty($dm_out)) {
		$dm_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='20'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Document Movement Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b>To</b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Time</th>
			<th>Record Number</th>
			<th>Project</th>
			<th>Area</th>
			<th>Discipline</th>
			<th>Document Type</th>
			<th>Revision</th>
			<th>Drawing Number</th>
			<th>Sheet Number</th>
			<th>Title</th>
			<th>Location</th>
			<th>Contract</th>
			<th>Contractor</th>
			<th>Code</th>
			<th>Issue For</th>
			<th>QS</th>
			<th>Status</th>
		</tr>
		$dm_out
	</table>";

	return $OUTPUT;
}

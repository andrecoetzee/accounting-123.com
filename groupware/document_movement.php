<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");



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

	$sql = "
		SELECT *,extract('epoch' FROM timestamp) AS e_time 
		FROM cubit.document_movement 
		WHERE (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59') $where 
		ORDER BY timestamp DESC";
	$dm_rslt = db_exec($sql) or errDie("Unable to retrieve document movement");

	$dm_out = "";
	while ($dm_data = pg_fetch_array($dm_rslt)) {
		// Check to see if we've actually got access to view this document
		$sql = "SELECT admin FROM cubit.users WHERE userid='".USER_ID."'";
		$admin_rslt = db_exec($sql) or errDie("Unable to check for admin.");
		$admin = pg_fetch_result($admin_rslt, 0);

		if ($dm_data["team_id"] && !$admin) {
			$sql = "SELECT * FROM crm.team_owners WHERE user_id='".USER_ID."' AND team_id='$dm_data[team_id]'";
			$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");

			// ok, no access... next document...
			if (!pg_num_rows($team_rslt)) {
				continue;
			}
		}

		if (isset($dm_data['doc_type']) AND strlen ($dm_data['doc_type']) > 0){
			$dm_data['doc_type'] += 0;
			$get_doc = "SELECT type_name FROM document_types WHERE id = '$dm_data[doc_type]' LIMIT 1";
			$run_doc = db_exec ($get_doc) or errDie ("Unable to get document type information.");
			$doc_type = pg_fetch_result ($run_doc,0,0);
		}else {
			$doc_type = "";
		}

		$dm_out .= "
			<tr class='".bg_class()."'>
				<td>".date("d-m-Y G:i:s", $dm_data["e_time"])."</td>
				<td>$dm_data[title]</td>
				<td>$dm_data[doc_id]</td>
				<td>$doc_type</td>
				<td>$dm_data[revision]</td>
				<td>$dm_data[location]</td>
				<td>$dm_data[status]</td>
			</tr>
			<tr bgcolor='$bgcolor'>
				<td colspan='20' align='center'><b>$dm_data[movement_description]</b></td>
			</tr>";
	}

	if (empty($dm_out)) {
		$dm_out = "
			<tr class='".bg_class()."'>
				<td colspan='20'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Document Movement Report</h3>
		<form method='POST' action='".SELF."'>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td><b>To</b></td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select' /></td>
			</tr>
		</table>
		</form>
		<table cellpadding='2' cellspacing='1' class='shtable'>
			<tr>
				<th>Time</th>
				<th>Title</th>
				<th>Record Number</th>
				<th>Document Type</th>
				<th>Revision</th>
				<th>Location</th>
				<th>Status</th>
			</tr>
			$dm_out
		</table>";
	return $OUTPUT;

}


?>
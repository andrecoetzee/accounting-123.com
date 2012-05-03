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

	$sql = "SELECT * FROM cubit.documents WHERE id='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
	$doc_data = pg_fetch_array($doc_rslt);

	extract ($doc_data);

	if (!empty($doc_type)) {
		$sql = "SELECT type_name FROM cubit.document_types WHERE id='$doc_type'";
		$type_rslt = db_exec($sql) or errDie("Unable to retrieve document type.");
		$type_name = pg_fetch_result($type_rslt, 0);
	} else {
		$type_name = "";
	}

	if (!empty($team_id)) {
		$sql = "SELECT team_name FROM cubit.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "";
	}


	$OUTPUT = "<table ".TMPL_tblDflts.">
	<tr><td valign='top'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Title</td>
			<td>$title</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Project</td>
			<td>$project</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Area</td>
			<td>$area</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Discipline</td>
			<td>$discipline</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Document Type</td>
			<td>$type_name</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Revision</td>
			<td>$revision</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Drawing Number</td>
			<td>$drawing_num</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Sheet Number</td>
			<td>$sheet_num</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>File Location</td>
			<td>$location</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Contract</td>
			<td>$contract</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Contractor</td>
			<td>$contractor</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Code</td>
			<td>$code</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Issue For</td>
			<td>$issue_for</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Comments</td>
			<td>$comments</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>QS</td>
			<td>$qs</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Team Permissions</td>
			<td>$team_name</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Status</td>
			<td>$status</td>
		</tr>
	</table>
	</td><td valign='top'>";

	$sql = "SELECT * FROM document_files WHERE doc_id='$id'";
	$df_rslt = db_exec($sql) or errDie("Unable to retrieve files.");

	$file_out = "";
	while ($df_data = pg_fetch_array($df_rslt)) {
		$file_out .= "<tr class='".bg_class()."'>
			<td><a href='getfile.php?key=doc&id=$df_data[id]'>$df_data[filename]</a></td>
			<td>".getFilesize($df_data["size"])."</td>
		</tr>";
	}

	if (empty($file_out)) {
		$file_out = "<tr class='".bg_class()."'>
			<td colspan='2'>No files found.</td>
		</tr>";
	}

	$OUTPUT .= "<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Files</th>
		</tr>
		<tr>
			<th>File</th>
			<th>Size</th>
		</tr>
		$file_out
	</table>
	</td></tr>
	</table>";

	return $OUTPUT;
}

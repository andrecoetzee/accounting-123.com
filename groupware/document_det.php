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

	$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
	$doc_data = pg_fetch_array($doc_rslt);

	// Check to see if we've actually got access to view this document
	$sql = "SELECT admin FROM cubit.users WHERE userid='".USER_ID."'";
	$admin_rslt = db_exec($sql) or errDie("Unable to check for admin.");
	$admin = pg_fetch_result($admin_rslt, 0);

	if ($doc_data["team_id"] && !$admin) {
		$sql = "SELECT * FROM crm.team_owners
		WHERE user_id='".USER_ID."' AND team_id='$doc_data[team_id]'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");

		// ok, no access...
		if (!pg_num_rows($team_rslt)) {
			return "<li class='err'>
				You don't have sufficient permission to view this document.
			</li>";
		}
	}

	extract ($doc_data);

	if (!empty($doc_type)) {
		$sql = "SELECT type_name FROM cubit.document_types WHERE id='$doc_type'";
		$type_rslt = db_exec($sql) or errDie("Unable to retrieve document type.");
		$type_name = pg_fetch_result($type_rslt, 0);
	} else {
		$type_name = "[None]";
	}

	if (!empty($team_id)) {
		$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "[None]";
	}


	$OUTPUT = "<table ".TMPL_tblDflts.">
	<tr><td valign='top'>
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Title</td>
			<td>$title</td>
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
			<td>File Location</td>
			<td>$location</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Comments</td>
			<td>$comments</td>
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

	$OUTPUT .= "<table cellpadding='2' cellspacing='0' class='shtable'>
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

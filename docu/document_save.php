<?php

require ("../settings.php");

db_conn("cubit");
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Another Document"),
	ql("document_view.php", "View Documents"),
	ql("doc-index.php", "Main Menu")
);

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["page_title"] = "";
	$fields["project"] = "";
	$fields["area"] = "";
	$fields["discipline"] = "";
	$fields["doc_type"] = "";
	$fields["revision"] = "";
	$fields["drawing_num"] = "";
	$fields["sheet_num"] = "";
	$fields["title"] = "";
	$fields["location"] = "";
	$fields["contract"] = "";
	$fields["contractor"] = "";
	$fields["code"] = "";
	$fields["issue_for"] = "";
	$fields["comments"] = "";
	$fields["qs"] = "";
	$fields["team_id"] = 0;
	$fields["type_id"] = 0;
	$fields["id"] = 0;
	$fields["status"] = "inactive";

	extract ($fields, EXTR_SKIP);

	if (isset($mode) && $mode == "edit") {
		$page_title = "Edit";

		$sql = "SELECT * FROM cubit.documents WHERE id='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
		extract (pg_fetch_array($doc_rslt));
	} else {
		$page_title = "Add";
		$mode = "";
	}

	if ($status == "active") {
		$status_active = "checked";
		$status_inactive = "";
	} else {
		$status_active = "";
		$status_inactive = "checked";
	}

	$sql = "SELECT * FROM cubit.document_types";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

	$types_sel = "<select name='type_id'>
		<option value='0'>[None]</option>";
	while ($dt_data = pg_fetch_array($dt_rslt)) {
		if ($type_id == $dt_data["id"]) {
			$selected = "selected";
		} else {
			$selected = "selected";
		}
		$types_sel .= "<option value='$dt_data[id]' $selected>
			$dt_data[type_name]
		<option>";
	}
	$types_sel .= "</select>";

	$sql = "SELECT * FROM crm.teams";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='team_id'>
		<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		$team_sel .= "<option value='$team_data[id]' $selected>
			$team_data[team_name]
		</option>";
	}
	$team_sel .= "</select>";

	$OUTPUT = "<h3>$page_title Document</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='mode' value='$mode' />
	<input type='hidden' name='page_title' value='$page_title' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Title</td>
			<td><input type='text' name='title' value='$title' /></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Document Type</td>
			<td>$types_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Revision</td>
			<td><input type='text' name='revision' value='$revision' /></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>File Location</td>
			<td><input type='text' name='location' value='$location' /></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Comments</td>
			<td>
				<textarea name='comments' rows='5' cols='20'>$comments</textarea>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Team Permissions</td>
			<td>$team_sel</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Status</td>
			<td>
			Active
			<input type='radio' name='status' value='active' $status_active />
			Inactive
			<input type='radio' name='status' value='inactive' $status_inactive />
			</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	if (isset($team_id) && is_numeric($team_id)) {
		$sql = "SELECT team_name FROM cubit.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "";
		$team_id = 0;
	}

	if (isset($type_id) && is_numeric($type_id)) {
		$sql = "SELECT type_name FROM cubit.document_types WHERE id='$type_id'";
		$type_rslt = db_exec($sql) or errDie("Unable to retrieve type.");
		$type_name = pg_fetch_result($type_rslt, 0);
	} else {
		$type_name = "";
		$type_id = 0;
	}

	$OUTPUT = "<h3>$page_title Document</h3>
	<form method='post' action='".SELF."' enctype='multipart/form-data'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='mode' value='$mode' />
	<input type='hidden' name='page_title' value='$page_title' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='revision' value='$revision' />
	<input type='hidden' name='title' value='$title' />
	<input type='hidden' name='location' value='$location' />
	<input type='hidden' name='comments' value='$comments' />
	<input type='hidden' name='team_id' value='$team_id' />
	<input type='hidden' name='type_id' value='$type_id' />
	<input type='hidden' name='status' value='$status' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>File</td>
			<td><input type='file' name='file'></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Title</td>
			<td>$title</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Document Type</td>
			<td>$type_name</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Revision</td>
			<td>$revision</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>File Location</td>
			<td>$location</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Team Permissions</td>
			<td>$team_name</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Status</td>
			<td>$status</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	if ($mode == "edit") {
		$sql = "UPDATE cubit.documents SET doc_type='$type_id', revision='$revision',
			title='$title',	location='$location', comments='$comments',
			status='$status', team_id='$team_id' WHERE id='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to save document.");

		$movement_description = "Edited Document Information";
		$doc_id = $id;
	} else {
		$sql = "
		INSERT INTO cubit.documents (doc_type, revision,
			title, location, comments, status, team_id)
		VALUES ('$type_id', '$revision', '$title', '$location', '$comments',
			'$status', '$team_id')";
		$doc_rslt = db_exec($sql) or errDie("Unable to save document.");

		$movement_description = "Document Added to System";
		$doc_id = pglib_lastid("documents", "id");
	}


	if ($_FILES["file"]["tmp_name"]) {
		$tmp_name = $_FILES["file"]["tmp_name"];
		$file_name = $_FILES["file"]["name"];
		$file_type = $_FILES["file"]["type"];
		$file_size = $_FILES["file"]["size"];

		$tmp_file = fopen($tmp_name, "rb");
		if (is_resource($tmp_file)) {
			$file = "";
			while (!feof($tmp_file)) {
				$file .= fread($tmp_file, 1024);
			}
			fclose($tmp_file);
			$file = base64_encode($file);

			$sql = "
			INSERT INTO cubit.document_files (doc_id, filename, file, type,	size)
			VALUES ('$doc_id', '$file_name', '$file', '$file_type', '$file_size')";
			$df_rslt = db_exec($sql) or errDie("Unable to upload document.");
		}
	}

// 	$sql = "
// 	INSERT INTO cubit.document_movement (doc_id, movement_description, project, area,
// 		discipline, doc_type, revision,	drawing_num, sheet_num, title, location,
// 		contract, contractor, code,	issue_for, comments, qs, status, team_id)
// 	VALUES ('$doc_id', '$movement_description', '$project', '$area',
// 		'$discipline', '$type_id', '$revision', '$drawing_num', '$sheet_num',
// 		'$title', '$location', '$contract', '$contractor', '$code', '$issue_for',
// 		'$comments', '$qs', '$status', '$team_id')";
// 	$dm_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");


	$OUTPUT = "<h3>$page_title Document</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully saved the document</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

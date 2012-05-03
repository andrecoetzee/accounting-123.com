<?php

require ("../settings.php");
require ("gw-common.php");

db_conn("cubit");
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
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
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");



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
	$fields["bf_true"] = "";
	$fields["bfdate_year"] = date("Y");
	$fields["bfdate_month"] = date("m");
	$fields["bfdate_day"] = date("d");
	$fields["status"] = "inactive";

	extract ($fields, EXTR_SKIP);

	if (isset($mode) && $mode == "edit") {
		$page_title = "Edit";

		$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
		$doc_data = pg_fetch_array($doc_rslt);
		extract ($doc_data);

		$type_id = $doc_type;

		// Check to see if we've actually got access to view this document
		$sql = "SELECT admin FROM cubit.users WHERE userid='".USER_ID."'";
		$admin_rslt = db_exec($sql) or errDie("Unable to check for admin.");
		$admin = pg_fetch_result($admin_rslt, 0);

		if ($doc_data["team_id"] && !$admin) {
			$sql = "SELECT * FROM crm.team_owners WHERE user_id='".USER_ID."' AND team_id='$doc_data[team_id]'";
			$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");

			// ok, no access... next document...
			if (!pg_num_rows($team_rslt)) {
				return "<li class='err'>You don't have sufficient permission to modify this document.</li>";
			}
		}

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

	$sql = "SELECT * FROM cubit.document_types ORDER BY type_name ASC";
	$dt_rslt = db_exec($sql) or errDie("Unable to retrieve document types.");

	$types_sel = "<select name='type_id' style='width: 100%'><option value='0'>[None]</option>";
	while ($dt_data = pg_fetch_array($dt_rslt)) {
		if ($type_id == $dt_data["id"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$types_sel .= "<option value='$dt_data[id]' $selected>$dt_data[type_name]</option>";
	}
	$types_sel .= "</select>";

	$sql = "SELECT * FROM crm.teams ORDER BY name ASC";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='team_id'><option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$team_sel .= "<option value='$team_data[id]' $selected>$team_data[name]</option>";
	}
	$team_sel .= "</select>";

	$OUTPUT = "
		<h3>$page_title Document</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='mode' value='$mode' />
			<input type='hidden' name='page_title' value='$page_title' />
			<input type='hidden' name='id' value='$id' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Title</td>
				<td><input type='text' name='title' value='$title' style='width: 100%' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Document Type</td>
				<td>$types_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Revision</td>
				<td><input type='text' name='revision' value='$revision' size='3' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Physical Location</td>
				<td><input type='text' name='location' value='$location' style='width: 100%' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Comments</td>
				<td><textarea name='comments' cols='20' style='width: 100%'>$comments</textarea></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Team Permissions</td>
				<td>$team_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bring Forward</td>
				<td>
					<input type='checkbox' name='bf_true' value='checked' $bf_true />
					".mkDateSelect("bfdate", $bfdate_year, $bfdate_month, $bfdate_day)."
				</td>
			<tr class='".bg_class()."'>
				<td>Status</td>
				<td>
					Active <input type='radio' name='status' value='active' $status_active />
					Inactive <input type='radio' name='status' value='inactive' $status_inactive />
				</td>
			</tr>
		</table>
		<p></p>
			<input type='submit' value='Confirm &raquo' />
		</form>";
	return $OUTPUT;

}



function confirm()
{

	extract ($_REQUEST);

	if (isset($team_id) && is_numeric($team_id)) {
		$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "[None]";
		$team_id = 0;
	}

	if (isset($type_id) && is_numeric($type_id)) {
		$sql = "SELECT type_name FROM cubit.document_types WHERE id='$type_id'";
		$type_rslt = db_exec($sql) or errDie("Unable to retrieve type.");
		$type_name = pg_fetch_result($type_rslt, 0);
	} else {
		$type_name = "[None]";
		$type_id = 0;
	}

	if ($bf_true) {
		$bf_out = "<b>Yes</b> &nbsp; $bfdate_day-$bfdate_month-$bfdate_year";
	} else {
		$bf_out = "<b>No</b>";
	}

	$OUTPUT = "
		<h3>$page_title Document</h3>
		<form method='POST' action='".SELF."' enctype='multipart/form-data'>
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
			<input type='hidden' name='bf_true' value='$bf_true' />
			<input type='hidden' name='bfdate_year' value='$bfdate_year' />
			<input type='hidden' name='bfdate_month' value='$bfdate_month' />
			<input type='hidden' name='bfdate_day' value='$bfdate_day' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>File</td>
				<td><input type='file' name='file'></td>
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
				<td>Team Permissions</td>
				<td>$team_name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bring Forward</td>
				<td>$bf_out</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Status</td>
				<td>$status</td>
			</tr>
		</table>
		<p></p>
			<input type='submit' name='key' value='&laquo Correction' />
			<input type='submit' value='Write &raquo' />
		</form>";
	return $OUTPUT;

}



function write()
{

	extract ($_REQUEST);

	if ($mode == "edit") {
		$sql = "
			UPDATE cubit.documents 
			SET doc_type='$type_id', revision='$revision', title='$title', location='$location', 
				comments='$comments', status='$status', team_id='$team_id' 
			WHERE docid='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to save document.");

		$movement_description = "Edited Document Information";
		$doc_id = $id;
	} else {
		$sql = "
			INSERT INTO cubit.documents (
				doc_type, revision, title, location, comments, status, team_id
			) VALUES (
				'$type_id', '$revision', '$title', '$location', '$comments', '$status', '$team_id'
			)";
		$doc_rslt = db_exec($sql) or errDie("Unable to save document.");

		$movement_description = "Document Added to System";
		$doc_id = pglib_lastid("documents", "docid");
	}

	// Save into today
	if ($bf_true) {
		$bfdate = "$bfdate_year-$bfdate_month-$bfdate_day";

		addTodayEntry("Documents", $doc_id, $bfdate);
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
				INSERT INTO cubit.document_files (
					doc_id, filename, file, type,	size
				) VALUES (
					'$doc_id', '$file_name', '$file', '$file_type', '$file_size'
				)";
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
// 	$sql = "
// 	INSERT INTO cubit.document_movement (doc_id, movement_description, doc_type,
// 		revision, title, location, comments, status, team_id)
// 	VALUES ('$doc_id', '$movement_description', '$type_id', '$revision',
// 		'$title', '$location', '$comments', '$status', '$team_id')";
// 	$dm_rslt = db_exec($sql) or errDie("Unable to update document movement.");


	$OUTPUT = "
		<h3>$page_title Document</h3>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><li>Successfully saved the document</li></td>
			</tr>
		</table>
		<p></p>";
	return $OUTPUT;

}


?>
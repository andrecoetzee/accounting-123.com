<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Document")
);

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";
	$fields["disp_inactive"] = "";
	$fields["offset"] = 0;

	extract ($fields, EXTR_SKIP);

	if (!empty($disp_inactive)) {
		$sql_status = "(status='active' OR status='inactive')";
	} else {
		$sql_status = "status='active'";
	}

	if (!empty($search)) {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status AND (id ILIKE '%$search%'
				OR project ILIKE '%$search%' OR area ILIKE '%$search%'
				OR title  ILIKE '%$search%' OR location ILIKE '%$search%'
				OR contract ILIKE '%$search%' OR contractor ILIKE '%$search%')";
	} else {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status";
	}
	$osRslt = db_exec($sql) or errDie("Unable to retrieve documents.");

	$offset_prev = ($offset - 20);
	$offset_next = ($offset + 20);

	if ($offset_prev < 0) {
		$prev = "";
	} else {
		$prev = "<a href='?search=$search&offset=$offset_prev'>&laquo Previous</a>";
	}
	if ($offset_next > pg_num_rows($osRslt)) {
		$next = "";
	} else {
		$next = "<a href='?search=$search&offset=$offset_next'>Next &raquo</a>";
	}

	if (!empty($search)) {
		$sql = "
		SELECT * FROM cubit.documents WHERE $sql_status AND (id ILIKE '%$search%'
			OR project ILIKE '%$search%' OR area ILIKE '%$search%'
			OR title  ILIKE '%$search%' OR location ILIKE '%$search%'
			OR contract ILIKE '%$search%' OR contractor ILIKE '%$search%')
		LIMIT 20 OFFSET $offset";
	} else {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status LIMIT 20 OFFSET $offset";
	}
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");

	$doc_out = "";
	while ($doc_data = pg_fetch_array($doc_rslt)) {
		$doc_out .= "<tr class='".bg_class()."'>
			<td>$doc_data[id]</td>
			<td>$doc_data[project]</td>
			<td>$doc_data[area]</td>
			<td>$doc_data[title]</td>
			<td>$doc_data[location]</td>
			<td>$doc_data[contract]</td>
			<td>$doc_data[contractor]</td>
			<td>
				<a href='document_det.php?id=$doc_data[id]'>Details</a>
			</td>
			<td>
				<a href='document_save.php?id=$doc_data[id]&mode=edit'>Edit</a>
			</td>
			<td>
				<a href='document_movement.php?id=$doc_data[id]'>
					Document Movement
				</a>
			</td>
			<td>
				<a href='action_save.php?id=$doc_data[id]'>
					Add Action
				</a>
			</td>
			<td>
				<a href='action_report.php?id=$doc_data[id]'>
					View Action Report
				</a>
			</td>
		</tr>";
	}

	if (empty($doc_out)) {
		$doc_out = "<tr class='".bg_class()."'>
			<td colspan='10'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>View Documents</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='2'>
				<input type='checkbox' name='disp_inactive' value='checked'
				$disp_inactive onchange='javascript:document.form.submit()'>
				Display Inactive
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Title</th>
			<th>Location</th>
			<th colspan='6'>Options</th>
		</tr>
		$doc_out
		<tr class='".bg_class()."'>
			<td colspan='7'>$prev</td>
			<td colspan='7' align='right'>$next</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}

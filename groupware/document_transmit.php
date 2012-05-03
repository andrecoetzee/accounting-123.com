<?php

require ("../settings.php");

if (!isset($_REQUEST["id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("gw-tmpl.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
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

require ("gw-tmpl.php");



function enter()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["location"] = "";

	extract ($fields, EXTR_SKIP);

	// Retrieve document
	$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve document.");
	$doc_data = pg_fetch_array($doc_rslt);

	$OUTPUT = "
		<h3>Document Transmit</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='id' value='$id' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Title</td>
				<td>$doc_data[title]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Current Location</td>
				<td>$doc_data[location]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>New Location</td>
				<td><input type='text' name='location' value='$location' /></td>
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

	// Retrieve document
	$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve document.");
	$doc_data = pg_fetch_array($doc_rslt);

	$OUTPUT = "
		<h3>Document Transmit</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='location' value='$location' />
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Title</td>
				<td>$doc_data[title]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Old Location</td>
				<td>$doc_data[location]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>New Location</td>
				<td>$location</td>
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

	$sql = "UPDATE cubit.documents SET location='$location' WHERE docid='$id'";
	db_exec($sql) or errDie("Unable to transmit document");

	$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
	$doc_data = pg_fetch_array($doc_rslt);
	$movement_description = "Document transmitted to $location";

	$sql = "
		INSERT INTO cubit.document_movement (
			doc_id, movement_description, doc_type, revision, title, 
			location, comments, status, team_id
		) VALUES (
			'$id', '$movement_description', '$doc_data[doc_type]', '$doc_data[revision]', '$doc_data[title]', 
			'$location', '$doc_data[comments]', '$doc_data[status]', '$doc_data[team_id]'
		)";
	$dm_rslt = db_exec($sql) or errDie("Unable to update document movement.");
	header("Location: document_view.php");

}


?>
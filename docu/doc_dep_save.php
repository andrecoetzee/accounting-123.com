<?php

require ("../settings.php");

db_conn("cubit");
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
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
	ql("doc_dep_save.php", "Add Another Document Department"),
	ql("doc_dep_view.php", "View Document Departments"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["dep_name"] = "";

	extract ($fields, EXTR_SKIP);

	if (isset($mode) && $mode == "edit") {
		$sql = "SELECT * FROM cubit.document_departments WHERE id='$id'";
		$dd_rslt = db_exec($sql) or errDie("Unable to retrieve department.");
		extract (pg_fetch_array($dd_rslt));

		$page_title = "Edit";
	} else {
		$page_title = "Add";
		$mode = "add";
		$id = 0;
	}

	$OUTPUT = "<h3>$page_title Document Department</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='page_title' value='$page_title' />
	<input type='hidden' name='mode' value='$mode' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Department Name</td>
			<td><input type='text' name='dep_name' value='$dep_name' /></td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<h3>$page_title Document Department</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='dep_name' value='$dep_name' />
	<input type='hidden' name='page_title' value='$page_title' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='mode' value='$mode' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Department Name</td>
			<td>$dep_name</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	if ($mode == "edit") {
		$sql = "UPDATE cubit.document_departments SET dep_name='$dep_name' WHERE id='$id'";
	} else {
		$sql = "INSERT INTO cubit.document_departments (dep_name) VALUES ('$dep_name')";
	}
	$dd_rslt = db_exec($sql) or errDie("Unable to save document department");

	$OUTPUT = "<h3>$page_title Document Department</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Successfully saved document department</td>
		</tr>
	</table>";

	return $OUTPUT;
}
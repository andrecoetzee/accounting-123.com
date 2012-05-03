<?php

require ("../settings.php");

db_conn("cubit");
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = confirm();
}

require ("gw-tmpl.php");

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<h3>Remove Document Department</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Department Name</td>
			<td>$dep_name</td>
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

function write()
{
	extract ($_REQUEST);

	$sql = "DELETE FROM cubit.document_departments WHERE id='$id'";
	$dd_rslt = db_exec($sql) or errDie("Unable to remove document department.");

	$OUTPUT = "<h3>Remove Document Department</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Successfully saved document department</td>
		</tr>
	</table>";

	return $OUTPUT;
}

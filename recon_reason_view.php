<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "add":
			$OUTPUT = add();
			break;
		case "remove":
			$OUTPUT = remove();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("template.php");

function display()
{
	$sql = "SELECT id, reason FROM cubit.recon_reasons ORDER BY id DESC";
	$reason_rslt = db_exec($sql) or errDie("Unable to retrieve reasons.");
	
	$reason_out = "";
	while (list($id, $reason) = pg_fetch_array($reason_rslt)) {

		$reason_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$reason</td>
			<td align='center'>
				<input type='checkbox' name='remove[$id]' value='$id'
				onchange='javascript:document.form.submit()' />
			</td>
		</tr>";
	}
	
	if (empty($reason_out)) {
		$reason_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'><li>No reasons found.</li></td>
		</tr>";
	}
	
	$OUTPUT = "
	<h3>Recon Reasons</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='add' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Add</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='reason' /></td>
			<td><input type='submit' value='Add' /></td>
		</tr>
	</table>
	</form>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='remove' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Reason</th>
			<th>Remove</th>
		</tr>
		$reason_out
	</table>
	</form>";
	
	return $OUTPUT;
}

function add()
{
	extract ($_REQUEST);
	
	$sql = "INSERT INTO cubit.recon_reasons (reason) VALUES ('$reason')";
	db_exec($sql) or errDie("Unable to add to reasons.");
	
	return display();
}

function remove()
{
	extract ($_REQUEST);
	
	foreach ($remove as $id) {
		$sql = "DELETE FROM cubit.recon_reasons WHERE id='$id'";
		db_exec($sql) or errDie("Unable to remove reason.");
	}
	
	return display();
}
<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "update":
			$OUTPUT = update();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$sql = "SELECT id, reason FROM cubit.pslip_reasons";
	$reason_rslt = db_exec($sql) or errDie("Unable to retrieve reasons.");

	$reason_out = "";
	while (list($id, $reason) = pg_fetch_array($reason_rslt)) {
		$reason_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$reason</td>
			<td><input type='checkbox' name='remove[$id]' value='$id' /></td>
		</tr>";
	}

	$OUTPUT = "
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='update' />
	<h3>Dispatch Reasons</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Reason</th>
			<th>Remove</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='reason' style='width: 100%' /></td>
			<td>&nbsp;</td>
		</tr>
		$reason_out
		<tr>
			<td colspan='2' align='center'>
				<input type='submit' value='Update' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function update()
{
	extract ($_REQUEST);

	if (isset($remove)) {
		foreach ($remove as $id=>$value) {
			$sql = "DELETE FROM cubit.pslip_reasons WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove reasons.");
		}
	}

	if (!empty($reason)) {
		$sql = "INSERT INTO cubit.pslip_reasons (reason) VALUES ('$reason')";
		db_exec($sql) or errDie("Unable to add reason.");
	}

	return enter();
}
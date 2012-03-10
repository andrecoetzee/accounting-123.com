<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "display":
		$OUTPUT = display();
		break;
	case "write":
		$OUTPUT = write();
		break;
	}
} else {
	$OUTPUT = display();
}

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT id, name FROM cubit.dispatch_how ORDER BY name ASC";
	$how_rslt = db_exec($sql) or errDie("Unable to retrieve how.");

	$how_out = "";
	while ($how_data = pg_fetch_array($how_rslt)) {
		$how_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$how_data[name]</td>
			<td>
				<input type='checkbox' name='remove[$how_data[id]]'
				value='$how_data[id]'
				onchange='javascript:document.form.submit();' />
			</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>How to dispatch</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='write' />
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td>How</td>
			<td><input type='text' name='how' /></td>
			<td><input type='submit' value='Add' /></td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		$how_out
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	require_lib("validate");

	$v = new validate();
	$v->isOk($how, "string", 0, 255, "Invalid how.");

	if ($v->isError()) {
		return display($v->genErrors());
	}

	$msg = "";

	if (isset($remove)) {
		foreach ($remove as $id=>$value) {
			$sql = "DELETE FROM cubit.dispatch_how WHERE id='$value'";
			db_exec($sql) or errDie("Unable to remove how.");
		}
	}

	if (!empty($how)) {
		$sql = "INSERT INTO cubit.dispatch_how (name) VALUES ('$how')";
		db_exec($sql) or errDie("Unable add how.");

		$msg .= "<li class='yay'>Successfully added $how</li>";
	}
	return display($msg);
}

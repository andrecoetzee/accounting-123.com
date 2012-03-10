<?php

require ("../settings.php");

error_reporting(E_ALL);

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

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='HIRE_COMMENTS'";
	$comm_rslt = db_exec($sql) or errDie("Unable to retrieve hire comments.");
	$comments = pg_fetch_result($comm_rslt, 0);

	$OUTPUT = "<h3>Default Hire Note Comments</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Comments</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>
				<textarea name='comments' cols='20' rows='5'>$comments</textarea>
			</td>
		</tr>
		<tr>
			<td align='center'><input type='submit' value='Confirm &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<h3>Default Hire Note Comments</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='comments' value='$comments' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".nl2br($comments)."</td>
		</tr>
		<tr>
			<td align='center'><input type='submit' value='Write &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "UPDATE cubit.settings SET value='$comments' WHERE constant='HIRE_COMMENTS'";
	db_exec($sql) or errDie("Unable to save comments.");

	$OUTPUT = "<h3>Default Hire Note Comments</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully saved default hire note comments.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
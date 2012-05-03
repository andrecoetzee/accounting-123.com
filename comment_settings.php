<?php

require ("../settings.php");

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

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["comments"] = "";

	extract ($fields, EXTR_SKIP);

	if (empty($comments)) {
		$sql = "SELECT value FROM hire.hire_settings WHERE field='comments'";
		$comm_rslt = db_exec($sql) or errDie("Unable to retrieve default comments.");
		$comments = pg_fetch_result($comm_rslt, 0);
	}

	$OUTPUT = "<center>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<h3>Default Hire Note Comments</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Comments</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				<textarea name='comments' rows='5' columns='20'>$comments</textarea>
			</td>
		</tr>
		<tr>
			<td align='center'><input type='submit' value='Confirm &raquo;' /></td>
		</tr>
	</table>
	</center>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<center>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='comments' value='$comments' />
	<h3>Default Hire Note Comments</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".nl2br($comments)."</td>
		</tr>
		<tr>
			<td align='center'>
				<input type='submit' name='key' value='&laquo Correction' />
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</center>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "UPDATE hire.hire_settings SET value='$comments' WHERE field='comments'";
	db_exec($sql) or errDie("Unable to update default comments.");

	$OUTPUT = "<center>
	<h3>Default Hire Note Comments</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved the hire note comments.</li></td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}
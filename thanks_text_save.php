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

	$sql = "SELECT text FROM hire.thanks_text";
	$thx_rslt = db_exec($sql) or errDie("Unable to retrieve text.");
	$thx = pg_fetch_result($thx_rslt, 0);

	if (!isset($thank_you)) {
		$thank_you = $thx;
	}

	$OUTPUT = "<h3>Edit Thank You Text</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Text</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='thank_you' value='$thank_you' style='width: 400px' /></td>
		</tr>
		<tr>
			<td align='right'><input type='submit' value='Confirm &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract($_REQUEST);

	$OUTPUT = "<h3>Edit Thank You Text</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='thank_you' value='$thank_you' />
	<table ".TMPL_tblDflts.">
		<tr><th>Confirm</th></tr>
		<tr class='".bg_class()."'>
			<td>$thank_you</td>
		</tr>
		<tr>
			<td align='right'><input type='submit' value='Write &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "UPDATE hire.thanks_text SET text='$thank_you'";
	db_exec($sql) or errDie("Unable to update text.");

	$OUTPUT = "<h3>Edit Thank You Text</h3>
	<table ".TMPL_tblDflts.">
		<tr><th>Write</th></tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved thank you text.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

?>
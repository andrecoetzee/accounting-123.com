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

	$sql = "SELECT value FROM cubit.settings WHERE constant='CONTRACT_TEXT'";
	$contract_rslt = db_exec($sql) or errDie("Unable to retrieve text.");
	$contract = pg_fetch_result($contract_rslt, 0);

	$contract = base64_decode($contract);

	$OUTPUT = "
	<h3>Contract Text</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr><th>Enter</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>
			<textarea name='contract' cols='100' rows='30' />$contract</textarea>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<input type='submit' value='Confirm &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract($_REQUEST);

	$contract = stripslashes($contract);

	$OUTPUT = "
	<h3>Contract Text</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='contract' value='$contract' />
	<table ".TMPL_tblDflts.">
		<tr><th>Confirm</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".nl2br($contract)."</td>
		</tr>
		<tr>
			<td align='center'>
				<input type='submit' name='key' value='&laquo Correction' />
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract($_REQUEST);

	$contract = base64_encode(stripslashes($contract));
	$sql = "UPDATE cubit.settings SET value='$contract'
			WHERE constant='CONTRACT_TEXT'";
	db_exec($sql) or errDie("Unable to save contract text.");

	$OUTPUT = "
	<h3>Contract Text</h3>
	<table ".TMPL_tblDflts.">
		<tr><th>Write</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully saved contract text</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}
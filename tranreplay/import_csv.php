<?php

require ("../settings.php");

if (!isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$OUTPUT = "
	<h3>Import Replay Transactions CSV</h3>
	<form method='post' action='".SELF."' enctype='multipart/form-data'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr><th>CSV Format</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>
				Transaction Type, Credit Account Id, Debit Account Id, Date,
				Reference No, Amount, VAT, Details, iid
			</td>
		<tr><th colspan='2'>Upload CSV</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='file' name='file' /></td>
			<td><input type='submit' value='Upload &raquo' /></td>
		</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function confirm()
{


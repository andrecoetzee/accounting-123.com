<?php

require ("settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
	case "enter":
		$OUTPUT = enter();
		break;
	}
} elseif (isset($_REQUEST["key"])) {
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

require ("template.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["vatinc"] = "no";

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT value FROM cubit.settings WHERE constant='VAT_INC'";
	$dbvatinc_rslt = db_exec($sql) or errDie("Unable to retrieve settings.");
	$dbvatinc = pg_fetch_result($dbvatinc_rslt, 0);
	if (!empty($dbvatinc)) $vatinc = $dbvatinc;

	if ($vatinc == "yes") {
		$vatinc_y = "checked";
		$vatinc_n = "";
	} else {
		$vatinc_y = "";
		$vatinc_n = "checked";
	}

	$OUTPUT = "
	<h3>VAT Setting</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='3'>Setting</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>VAT Inclusive</td>
			<td>
				<input type='radio' name='vatinc' value='yes' $vatinc_y /> Yes
				<input type='radio' name='vatinc' value='no' $vatinc_n /> No
			</td>
			<td><input type='submit' value='Confirm &raquo' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "
	<h3>VAT Setting</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='vatinc' value='$vatinc' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='3'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>VAT Inclusive</td>
			<td>".ucfirst($vatinc)."</td>
			<td>
				<input type='submit' name='button[enter]' value='&laquo Correction' />
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='VAT_INC'";
	$setting_rslt = pg_exec($sql) or errDie("Unable to retrieve settings.");
	
	if (!pg_num_rows($setting_rslt)) {
		$sql = "
		INSERT INTO cubit.settings (constant, label, value, type, datatype,
			minlen, maxlen, readonly)
		VALUES ('VAT_INC', 'Vat Inclusive', '$vatinc', 'general', 'string',
			'1', '3', 'f')";
		db_exec($sql) or errDie("Unable to update vat setting.");
	} else {
		$sql = "UPDATE cubit.settings SET value='$vatinc' WHERE constant='VAT_INC'";
		db_exec($sql) or errDie("Unable to add vat setting.");
	}

	$OUTPUT = "
	<h3>VAT Setting</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully updated VAT Setting</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

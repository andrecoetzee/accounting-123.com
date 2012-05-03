<?php

require ("settings.php");

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

require ("template.php");

function enter()
{
	extract ($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='OVERDUE_DAYS'";
	$days_rslt = db_exec($sql) or errDie("Unable to retrieve days.");
	$days = pg_fetch_result($days_rslt, 0);

	if (empty($days)) $days = 0;

	$OUTPUT = "
	<h3>Block Overdue Customers</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Settings</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				Block customers over
				<input type='text' name='days' value='$days' size='3' />
				Days (0 to disable this setting)
			</td>
		</tr>
		<tr>
			<td align='right'>
				<input type='submit' value='Confirm &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "
	<h3>Block Overdue Customers</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='days' value='$days' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				Block customers over <strong>$days</strong> days
				(0 to disable this setting)
			</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='OVERDUE_DAYS'";
	$overdue_rslt = db_exec($sql) or errDie("Unable to retrieve overdue days");

	if (!pg_num_rows($overdue_rslt)) {
		$sql = "
		INSERT INTO cubit.settings (constant, value)
		VALUES ('OVERDUE_DAYS', '$days')";
		db_exec($sql) or errDie("Unable to save overdue setting.");
	} else {
		$sql = "
		UPDATE cubit.settings SET value='$days'
		WHERE constant='OVERDUE_DAYS'";
		db_exec($sql) or errDie("Unable to save overdue setting.");
	}

	$OUTPUT = "
	<h3>Block Overdue Customers</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved overdue setting.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

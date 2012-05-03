<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "enter":
		$OUTPUT = enter();
		break;
	case "write":
		$OUTPUT = write();
		break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter($msg="")
{
	extract($_REQUEST);

	$sql = "SELECT value FROM cubit.settings WHERE constant='HD_PERC'";
	$hd_rslt = db_exec($sql) or errDie("Unable to retrieve half day rate.");
	$hd_perc = pg_fetch_result($hd_rslt, 0);

	if (empty($hd_perc)) {
		$hd_perc = 60;
	}

	$OUTPUT = "
	<h3>Half Day Setting</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$msg</td>
		</tr>
		<tr>
			<th colspan='2'>Setting</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Half Day Rate</td>
			<td>
				<input type='text' name='hd_perc' value='$hd_perc' size='3'
				style='text-align: center' /><b>%</b>
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
	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($hd_perc, "num", 1, 9, "Invalid half day rate.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$sql = "SELECT value FROM cubit.settings WHERE constant='HD_PERC'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve percentage.");

	if (pg_num_rows($rslt)) {
		$sql = "
		UPDATE cubit.settings SET value='$hd_perc'
		WHERE constant='HD_PERC'";
	} else {
		$sql = "
		INSERT INTO cubit.settings (constant, value)
			VALUES ('HD_PERC', '$hd_perc')";
	}
	db_exec($sql) or errDie("Unable to update half day rate.");

	$msg = "<li class='yay'>Successfully saved half day rate.</li>";

	return enter($msg);
}

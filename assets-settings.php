<?php

require ("settings.php");

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

$OUTPUT .= mkQuickLinks (
	ql("asset-view.php", "View Assets")
);

require ("template.php");

function enter($msg="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["cost_acc"] = 0;
	$fields["accdep_acc"] = 0;
	$fields["dep_sel"] = 0;

	extract ($fields, EXTR_SKIP);

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_COST_ACCOUNT'";
	$cost_rslt = db_exec($sql) or errDie("Unable to retrieve cost account.");
	$cost_acc = pg_fetch_result($cost_rslt, 0);

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_ACCDEP_ACCOUNT'";
	$accdep_rslt = db_exec($sql)
		or errDie("Unable to retrieve accumulated depreciation account.");
	$accdep_acc = pg_fetch_result($accdep_rslt, 0);

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_DEP_ACCOUNT'";
	$dep_rslt = db_exec($sql)
		or errDie("Unable to retrieve depreciation account.");
	$dep_acc = pg_fetch_result($dep_rslt, 0);

	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$cost_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$cost_sel = "<select name='cost_acc' style='width: 100%'>";
	while ($cost_data = pg_fetch_array($cost_rslt)) {
		$sel = ($cost_acc == $cost_data["accid"]) ? "selected='t'" : "";

		$cost_sel .= "
		<option value='$cost_data[accid]' $sel>
			$cost_data[topacc]/$cost_data[accnum] $cost_data[accname]
		</option>";
	}
	$cost_sel .= "</select>";

	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$accdep_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$accdep_sel = "<select name='accdep_acc' style='width: 100%'>";
	while ($accdep_data = pg_fetch_array($accdep_rslt)) {
		$sel = ($accdep_acc == $accdep_data["accid"]) ? "selected='t'" : "";

		$accdep_sel .= "
		<option value='$accdep_data[accid]' $sel>
			$accdep_data[topacc]/$accdep_data[accnum] $accdep_data[accname]
		</option>";
	}
	$accdep_sel .= "</select>";
	
	$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts";
	$dep_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

	$dep_sel = "<select name='dep_acc' style='width: 100%'>";
	while ($dep_data = pg_fetch_array($dep_rslt)) {
		$sel = ($dep_acc == $dep_data["accid"]) ? "selected='t'" : "";

		$dep_sel .= "
		<option value='$dep_data[accid]' $sel>
			$dep_data[topacc]/$dep_data[accnum] $dep_data[accname]
		</option>";
	}
	$dep_sel .= "</select>";

	$OUTPUT = "
	<h3>Asset Settings</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$msg</td>
		</tr>
		<tr>
			<th colspan='2'>Default Asset Accounts</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Cost Account</td>
			<td>$cost_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Accumulated Depreciation</td>
			<td>$accdep_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Depreciation</td>
			<td>$dep_sel</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
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

	require_lib("validate");
	$v = new validate;
	$v->isOk($cost_acc, "num", 1, 9, "Invalid cost account.");
	$v->isOk($accdep_acc, "num", 1, 9, "Invalid accumulated depreciation account.");
	$v->isOk($dep_acc, "num", 1, 9, "Invalid depreciation account.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_COST_ACCOUNT'";
	$cost_rslt = db_exec($sql) or errDie("Unable to retrieve cost setting.");
	
	if (pg_num_rows($cost_rslt)) {
		$sql = "
		UPDATE cubit.settings SET value='$cost_acc'
		WHERE constant='ASSET_COST_ACCOUNT'";
	} else {
		$sql = "
		INSERT INTO cubit.settings (constant, value)
			VALUES ('ASSET_COST_ACCOUNT', '$cost_acc')";
	}
	db_exec($sql) or errDie("Unable to update cost setting.");

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_ACCDEP_ACCOUNT'";
	$accdep_rslt = db_exec($sql)
		or errDie("Unable to retrieve accumulated depreciation setting.");

	if (pg_num_rows($accdep_rslt)) {
		$sql = "
		UPDATE cubit.settings SET value='$accdep_acc'
		WHERE constant='ASSET_ACCDEP_ACCOUNT'";
	} else {
		$sql = "
		INSERT INTO cubit.settings (constant, value)
			VALUES ('ASSET_ACCDEP_ACCOUNT', '$accdep_acc')";
	}
	db_exec($sql)
		or errDie("Unable to update accumulated depreciation setting.");

	$sql = "
	SELECT value FROM cubit.settings
	WHERE constant='ASSET_DEP_ACCOUNT'";
	$dep_rslt = db_exec($sql)
		or errDie("Unable to retrieve depreciation setting.");

	if (pg_num_rows($dep_rslt)) {
		$sql = "
		UPDATE cubit.settings SET value='$dep_acc'
		WHERE constant='ASSET_DEP_ACCOUNT'";
	} else {
		$sql = "
		INSERT INTO cubit.settings (constant, value)
			VALUES ('ASSET_DEP_ACCOUNT', '$dep_acc')";
	}
	db_exec($sql) or errDie("Unable to update depreciation setting.");

	$msg = "<li class='err'>Successfully updated asset settings.</li>";
	return enter($msg);
}

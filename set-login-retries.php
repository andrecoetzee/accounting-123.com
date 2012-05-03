<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

require ('settings.php');

if (isset($_POST['key'])) {
	switch ($_POST['key']) {
		case "enter":
			$OUTPUT = enter();
		case "confirm":
			$OUTPUT = confirm($_POST);
		case "write":
			$OUTPUT = write($_POST);
	}
} else {
	$OUTPUT = enter();
}
require ("template.php");

function enter($error="")
{
	$sql = "SELECT * FROM login_retries";
	$rslt = db_exec($sql) or errDie("Unable to update database");
	$data = pg_fetch_array($rslt);

	$sel_retrtries = "<select name=retrtries style='width=30'>";
	for ($i = 0; $i < 21; $i++) {
		if ($i == $data['tries']) {
			$sel_retrtries .= "<option value='$i' selected>$i</option>";
		} else {
			$sel_retrtries .= "<option value='$i'>$i</option>";
		}
	}
	$sel_retrtries .= "</select>";

	$sel_retrmins = "<select name=retrmins style='width=30'>";
	for ($i = 0; $i < 121; $i++) {
		if ($i == $data['minutes']) {
			$sel_retrmins .= "<option value='$i' selected>$i</option>";
		} else {
			$sel_retrmins .= "<option value='$i'>$i</option>";
		}
	}
	$sel_retrmins .= "</select>";

	$OUTPUT = "
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='confirm'>
	<table border=0 cellspacing='".TMPL_tblCellSpacing."' cellpadding='".TMPL_tblCellPadding."'>
		<th>Setting</th>
		<th>Value</th>
		<tr class='bg-odd'>
			<td>Login retries:</td>
			<td>$sel_retrtries Tries</td>
		</tr>
		<tr class='bg-even'>
			<td>Blocked Time:</td>
			<td>$sel_retrmins Minutes</td>
		</tr>
		<tr>
			<td align=right colspan=2><input type=submit value='Confirm &raquo'></td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>
	</form>";
	require ('template.php');
}
function confirm($_POST)
{
	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk("$retrtries", "num", 0, 3, "Invalid value for tries.");
	$v->isOk("$retrmins", "num", 0, 3, "Invalid value for minutes.");

	if ($retrtries != 0 && $retrmins == 0) {
		$v->addError('', "Tries needs a value");
	} elseif ($retrmins !=0 && $retrtries == 0) {
		$v->addError('', "Minutes needs a value");
	}

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return enter($confirm);
	}

	$OUTPUT = "<form method=post action='".SELF."'>

	<input type=hidden name=key value='write'>
	<input type=hidden name=retrtries value='$retrtries'>
	<input type=hidden name=retrminutes value='$retrmins'>

	<table border=0 cellspacing='".TMPL_tblCellSpacing."' cellpadding='".TMPL_tblCellPadding."'>
		<th>Setting</th>
		<th>Value</th>
		<tr class='bg-odd'>
			<td>Login retries:</td>
			<td>$retrtries</td>
		</tr>
		<tr class='bg-even'>
			<td>Blocked Time:</td>
			<td>$retrmins</td>
		</tr>
		<tr>
			<td align=right colspan=2><input type=submit value='Write &raquo'></td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>
	</form>";
	require ('template.php');
}
function write($_POST)
{
	extract ($_POST);

	db_conn('cubit');
	$sql = "UPDATE cubit.login_retries SET tries='$retrtries', minutes='$retrminutes'";
	$rslt = db_exec ($sql) or errDie("Unable to update login settings");

	$OUTPUT = "<li>Login retry information successfully updated</li>
	<table border=0 cellspacing='".TMPL_tblCellSpacing."' cellpadding='".TMPL_tblCellPadding."'>
		<tr><td>&nbsp;</td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	require ('template.php');
}
?>
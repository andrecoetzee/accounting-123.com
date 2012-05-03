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

require ("settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
	}
} else {
	$OUTPUT = enter();
}
require ("template.php");

function enter($errors="")
{
	global $_POST;
	extract($_POST);

	if (!isset($ws_cond)) {
		// Retrieve the display notice from Cubit
		db_conn("cubit");
		$sql = "SELECT value FROM workshop_settings WHERE div='".USER_DIV."' AND setting='workshop_conditions'";
		$wssRslt = db_exec($sql) or errDie("Unable to retrieve workshop settings from Cubit.");
		$ws_cond = pg_fetch_result($wssRslt, 0);
	}

	// Layout
	$OUTPUT = "<h3>Workshop Conditions</h3>
	$errors
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='confirm'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th colspan=2>Conditions</th>
	  </tr>
	  <tr class='bg-odd'>
	    <td>Default workshop conditions</td>
	    <td><textarea name=ws_cond rows=5 cols=20>$ws_cond</textarea></td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right><input type=submit value='Confirm &raquo'></td>
	  </tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm($_POST)
{
	extract($_POST);

	// Validate
	require_lib("validate");
	$v = new validate;
	$v->isOk($ws_cond, "string", 1, 1024, "Invalid display notice.");

	// Did we get any errors?
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return enter($confirm);
	}

	// Layout
	$OUTPUT = "<h3>Workshop Settings</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='write'>
	<input type=hidden name=ws_cond value='$ws_cond'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th colspan=2>Confirm</td>
	  </tr>
	  <tr class='bg-odd'>
	    <td>Default workshop conditions</td>
	    <td>".nl2br($ws_cond)."</td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right>
	      <input type=submit name=key value='&laquo Correction'>
	      <input type=submit value='Write &raquo'>
	    </td>
	  </tr>
	</table>";

	return $OUTPUT;
}

function write($_POST)
{
	extract($_POST);

	// Make sure the setting exists in Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM workshop_settings WHERE setting='workshop_conditions' AND div='".USER_DIV."'";
	$wssRslt = db_exec($sql) or errDie("Unable to retrieve the workshop settings from Cubit.");

	if (pg_num_rows($wssRslt) > 0) {
		// Update the settings
		$sql = "UPDATE workshop_settings SET value='$ws_cond' WHERE setting='workshop_conditions' AND div='".USER_DIV."'";
	} else {
		// Otherwise insert it
		$sql = "INSERT INTO workshop_settings (setting, value, div) VALUES ('workshop_conditions', '$ws_cond', '".USER_DIV."')";
	}

	// Commit
	db_conn("cubit");
	$dnRslt = db_exec($sql) or errDie("Unable to update the workshop default display notice setting to Cubit.");

	// We got this far...
	$OUTPUT = "<li>Successfully updated the workshop settings to Cubit.</li>";

	return $OUTPUT;
}

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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("template.php");

function enter($error="")
{
	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_POS_COMMENTS'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
	$comments = base64_decode(pg_fetch_result($rslt, 0));
	
	$OUTPUT = "<h3>Default Comments for POS Invoices</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='confirm'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th>Option</th>
	    <th>Value</th>
	  <tr>
	  <tr bgcolor='".TMPL_tblDataColor1."'>
	    <td>".REQ."Comment text</td>
	    <td><textarea rows=5 cols=20 name=comments>$comments</textarea></td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right><input type=submit value='Confirm &raquo'></td>
	  </tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);

	$OUTPUT = "<h3>Default Comments for POS Invoices</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='write'>
	<input type=hidden name=comments value='$comments'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th>Option</th>
	    <th>Value</th>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor1."'>
	    <td>Comment Text</td>
	    <td>$comments</td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right>
	      <input type=submit value='Write &raquo'>
	    </td>
	  </tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);

	$sql = "UPDATE settings SET value='".base64_encode($comments)."' WHERE constant='DEFAULT_POS_COMMENTS'";
	$rslt = db_exec($sql) or errDie("Unable to update default comments");

	$OUTPUT = "<li>Successfully updated default comments.</li>";
	return $OUTPUT;
}
?>

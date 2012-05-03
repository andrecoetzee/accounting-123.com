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




function enter($error="")
{

	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_STMNT_COMMENTS'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve default comments from Cubit.");
	$comments = base64_decode(pg_fetch_result($rslt, 0));
	
	$OUTPUT = "
				<h3>Default Comments for Customer Bank Receipts</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name=key value='confirm'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Option</th>
						<th>Value</th>
					<tr>
					<tr class='".bg_class()."'>
						<td>".REQ."Comment text</td>
						<td><textarea rows='5' cols='20' name='comments'>$comments</textarea></td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo'></td>
					</tr>
				</table>
				</form>";
	return $OUTPUT;

}




function confirm($_POST)
{

	extract ($_POST);

	$OUTPUT = "
				<h3>Default Comments for Customer Bank Receipts</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='comments' value='$comments'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Option</th>
						<th>Value</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Comment Text</td>
						<td>$comments</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
					</tr>
				</table>
				</form>";
	return $OUTPUT;

}




function write($_POST)
{

	extract ($_POST);

	$sql = "UPDATE settings SET value='".base64_encode($comments)."' WHERE constant='DEFAULT_BANK_RECPT_COMMENTS'";
	$rslt = db_exec($sql) or errDie("Unable to update default comments");

	$OUTPUT = "<li>Successfully Updated Default Customer Receipt Comments.</li>";
	return $OUTPUT;

}



?>
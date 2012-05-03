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
require ('libs/validate.lib.php');

if (isset($_POST['key'])) {
	switch ($_POST['key']) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
		case "enter":
			$OUTPUT = enter($_POST);
	}
} else {
	$OUTPUT = enter($_POST);
}

// Append quick links to each page
$OUTPUT .= "<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		<tr>
		<tr class='datacell'>
			<td><a href='supp-group-view.php'>View Supplier Groups</td>
		</tr>
		<tr class='datacell'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";

require ('template.php');



function enter($_POST,$errors="")
{

	extract($_POST);

	// Initialize variables
	if (!isset($groupname)) $groupname = "";

	require_lib("validate");
	$v = new validate;
	$v->isOk($groupname, "string", 0, 255, "Invalid group name.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($_POST, $confirm);
	}

	$OUTPUT = "
		<h3>Add Supplier Group</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm'>
			$errors
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Option</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Group Name:</td>
				<td><input type='text' name='groupname' value='$groupname'></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo'>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function confirm($_POST)
{

	extract ($_POST);
	
	$v = new validate;
	$v->isOk($groupname, "string", 1, 255, "Invalid supplier group name");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($_POST, $confirm);
	}

	$OUTPUT = "
		<h3>Add Supplier Group
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='groupname' value='$groupname'>
		<table ".TMPL_tblDflts.">
			".TBL_BR."
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Group Name:</td>
				<td>$groupname</td>
			</tr>
			<tr>
				<td colspan='2' align='right'>
				  <input type='submit' name='key' value='&laquo Correction'>
				  <input type='submit' value='Write &raquo'>
				</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}


function write($_POST)
{

	extract ($_POST);

	$v = new validate;
	$v->isOk($groupname, "string", 1, 255, "Invalid supplier group name");
	
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li>$e[msg]</li>";
		}
		$confirm .= "<li class='err'>$e[msg]</li>";
		return enter($_POST, $confirm);
	}

	$sql = "INSERT INTO supp_groups (groupname) VALUES ('$groupname')";
	db_exec($sql) or errDie("Unable to insert group into Cubit.");

	$_POST = array ();
	return enter ($_POST, "<li class='yay'>Successfully added the suppliers group <b>$groupname</b> to Cubit.</li><br>");

}	


?>
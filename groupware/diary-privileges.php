<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

# If this script is called by itself, abort

require("../settings.php");
require("../libs/ext.lib.php");

if ( isset($_POST) && is_array($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_GET[$key] = $value;
	}
}

if ( isset($_GET["key"]) ) {
	switch ( $_GET["key"] ) {
		case "write":
			$OUTPUT = write();
			break;
		default:
			$OUTPUT = enter("");
	}
} else {
	$OUTPUT = enter("");
}

require("gw-tmpl.php");

function enter($err) {
	global $_GET;
	extract($_GET);

	$fields["privilege_owner"] = "";
	$fields["privilege"] = "";

	foreach ($fields as $key => $value) {
		if ( ! isset($$key) ) $$key = $value;
	}

	db_conn("cubit");
	$sql = "SELECT username FROM users ORDER BY username";
	$rslt = db_exec($sql) or errDie("Error reading user list.");

	$users = Array();
	$users["0"] = "SELECT USER";
	while ( $row = pg_fetch_array($rslt) ) {
		$users[$row["username"]] = $row["username"];
	}

	$select_user = extlib_cpsel("privilege_owner", $users, $privilege_owner);

	$select_privilege = "
		<select name=privilege>
			<option value='R' ".($privilege=="R"?"selected":"").">Read</option>
			<option value='W' ".($privilege=="W"?"selected":"").">Write</option>
		</select>";


	$OUTPUT = "
	<h3>Diary Privileges</h3>
	$err
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=write>
	<table cellpadding='2' cellspacing='0' class='shtable'>
	<tr>
		<th colspan=3>Details</th>
	</tr>
	<tr class='bg-odd'>
		<td>$select_user</td>
		<td colspan=2>$select_privilege</td>
	</tr>
	<tr>
		<th>Username</th>
		<th>Privilege</th>
		<th>Del</th>
	</tr>";

	db_conn("cubit");
	$sql = "SELECT * FROM diary_privileges WHERE diary_owner='".USER_NAME."'";
	$rslt = db_exec($sql) or errDie("Error reading diary privileges.");

	$i = 1;
	while ( $row = pg_fetch_array($rslt) ) {
		$OUTPUT .= "
		<tr class='".bg_class()."'>
			<td>$row[priv_owner]</td>
			<td>$row[privilege]</td>
			<td><input type=checkbox name='del[$row[id]]'></td>
		</tr>";
	}

	$OUTPUT .= "
	</table>
	<p></p>
	<input type=submit value='Update'>
	</form>";

	return $OUTPUT;
}

function write() {
	global $_GET;
	extract($_GET);

	require_lib("validate");
	$v = & new Validate();
	$v->isOk($privilege_owner, "string", 0, 100, "Invalid privilege username.");
	if ( $privilege != 'R' && $privilege != 'W' )
		$v->addError("", "Invalid privilege.");

	if ( isset($del) && is_array($del) ) {
		foreach ( $del as $key => $value ) {
			$v->isOk($key, "num", 1, 9, "Invalid delete option selected.");
		}
	} else {
		$del = Array();
	}

	if ( $v->isError() ) {
		$err = "";

		foreach ( $v->getErrors() as $key => $value ) {
			$err .= "<li class=err>$value[msg]</li>";
		}

		return enter($err);
	}

	if ( $privilege_owner != "0" ) {
		db_conn("cubit");
		$sql = "INSERT INTO diary_privileges (diary_owner, priv_owner, privilege)
			VALUES('".USER_NAME."', '$privilege_owner', '$privilege')";
		$rslt = db_exec($sql) or errDie("Error creating privilege.");
	}

	foreach ( $del as $user => $value ) {
		db_conn("cubit");
		$sql = "DELETE FROM diary_privileges WHERE id='$user'";
		$rslt = db_exec($sql) or errDie("Error deleting diary privileges.");
	}

	unset($_GET["privilege_owner"]);
	$OUTPUT = enter("Successfully updated privileges.");

	return $OUTPUT;
}

?>

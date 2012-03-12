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

require ("../settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "add":
			$OUTPUT = add($_POST);
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter()
{
	global $_POST;
	extract ($_POST);
	
	$fields = array();
	
	$fields["group_id"] = 0;
	$fields["user_id"] = 0;
	
	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}
	
	// Create document groups dropdown
	db_conn("cubit");
	$sql = "SELECT * FROM document_groups";
	$group_rslt = db_exec($sql) or errDie("Unable to retrieve document groups from Cubit.");
	
	$groups_sel = "<select name='group_id' style='width: 180px'>";
	while ($group_data = pg_fetch_array($group_rslt)) {
		if ($group_id == $group_data["id"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$groups_sel .= "<option value='$group_data[id]'>$group_data[name]</option>";
	}
	$groups_sel .= "</select>";
	
	// Create users dropdown
	db_conn("cubit");
	$sql = "SELECT * FROM users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users from Cubit.");
	
	$users_sel = "<select name='user_id' style='width: 180px'>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id = $user_data["userid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$users_sel .= "<option value='$user_data[userid]'>$user_data[username]</option>";
	}
	$users_sel .= "</select>";
	
	$OUTPUT = "<h3>User Document Groups</h3>
	<form method='post' action='".SELF."'>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td valign='top'>
			<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='add'>
			<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr>
					<th colspan='2'>Add User to Group</th>
				</tr>
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td>Group Name</td>
					<td>$groups_sel</td>
				</tr>
				<tr bgcolor='".TMPL_tblDataColor2."'>
					<td>Username</td>
					<td>$users_sel</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>
						<input type='submit' value='Add &raquo'>
					</td>
				</tr>
			</table>
			</form>
		</td><td valign='top'>
			<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr>
					<th>Group</th>
					<th>Members</th>
				</tr>
			</table>
		</td></tr>
	</table>";
	
	return $OUTPUT;
}

function add($_POST)
{
	extract ($_POST);
	
	require_lib("validate");
	$v = new validate;
	$v->isOk($group_id, "num", 1, 9, "Invalid group id");
	$v->isOk($user_id, "num", 1, 9, "Invalid user id");
	
	db_conn("cubit");
	$sql = "INSERT INTO doc_group_owners (group_id, user_id) VALUES ('$group_id', '$user_id')";
	$dgo_rslt = db_exec($sql) or errDie("Unable to retrieve group owners from Cubit.");
	
	return display();
}
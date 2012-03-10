<?php

require ("../settings.php");

if (!isset($_REQUEST["id"])) {
	$OUTPUT = slct();
} else {
	if (isset($_REQUEST["key"])) {
		switch ($_REQUEST["key"]) {
			case "slct":
				$OUTPUT = slct();
				break;
			case "display":
				$OUTPUT = display();
				break;
			case "update":
				$OUTPUT = update();
				break;
			case "remove":
				$OUTPUT = remove();
				break;
		}
	} else {
		$OUTPUT = display();
	}
}

require ("../template.php");



function slct()
{

	extract ($_REQUEST);

	// Retrieve teams
	$sql = "SELECT * FROM crm.teams ORDER BY name ASC";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='id' onchange='javascript:document.form.submit()'>";
	$team_sel.= "<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		$team_sel .= "<option value='$team_data[id]'>$team_data[name]</option>";
	}
	$team_sel.= "</select>";

	$OUTPUT = "
					<center>
					<h3>Allocate Users to Cubit Teams</h3>
					<form method='post' action='".SELF."' name='form'>
					<input type='hidden' name='key' value='display' />
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Select Team</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$team_sel</td>
						</tr>
					</table>
					</form>
					</center>";
	return $OUTPUT;

}



function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["id"] = "";

	// Retrieve team information
	$sql = "SELECT * FROM crm.teams WHERE id='$id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
	$team_data = pg_fetch_array($team_rslt);

	// Retrieve users in this team
	$sql = "SELECT * FROM crm.team_owners WHERE team_id='$id' ORDER BY id DESC";
	$to_rslt = db_exec($sql) or errDie("Unable to retrieve team users.");

	$total_users = pg_num_rows($to_rslt);

	$i = 0;
	$users_out = "";
	$team_users = array();
	while ($to_data = pg_fetch_array($to_rslt)) {
		// Retrieve the actual user information
		$sql = "SELECT * FROM cubit.users WHERE userid='$to_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");
		$user_data = pg_fetch_array($user_rslt);

		$users_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$user_data[username]</td>
			<td align='center'>
				<input type='checkbox' name='rem' value='$to_data[id]'
				onchange='javascript:document.remfrm.submit()' />
			</td>
		</tr>";

		// Value to store that this user has already been added to this team
		$team_users[] = $user_data["userid"];
	}

	// Nothing to see here...
	if (empty($users_out)) {
		$users_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'>No users allocated to this team.</td>
		</tr>";
	}

	// Retrieve cubit users
	$sql = "SELECT * FROM cubit.users ORDER BY username ASC";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");

	$users_sel = "<select name='user_id'>";
	$users_sel.= "<option value='0'>[None]</option>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		// This user is already added to this team, no need to even ask
		if (in_array($user_data["userid"], $team_users)) {
			continue;
		}

		$users_sel.= "<option value='$user_data[userid]'>
			$user_data[username]
		</option>";
	}
	$users_sel.= "</select>";

	$OUTPUT = "
	<center>
	<h3>Allocate Users to Cubit Teams</h3>
	<form method='post' action='".SELF."' name='form'>
		<input type='hidden' name='key' value='update' />
		<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Cubit Team Information</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Team Name</td>
			<td>$team_data[name]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<Td>Total Users</td>
			<td>$total_users</td>
		</tr>
		<tr>
			<th colspan='2'>Allocate User</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$users_sel</td>
			<td>
				<input type='submit' value='Allocate &raquo'
				style='font-weight: bold; width: 100%' />
			</td>
		</tr>
	</table>
	</form>
	<p></p>
	<form method='post' action='".SELF."' name='remfrm'>
		<input type='hidden' name='key' value='remove' />
		<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Username</th>
			<th>Remove</th>
		</tr>
		$users_out
	</table>
	</form>
	</center>";
	return $OUTPUT;

}



function update()
{

	extract ($_REQUEST);

	if ($user_id) {
		$sql = "
		SELECT * FROM crm.team_owners
		WHERE user_id='$user_id' AND team_id='$id'";
		$to_rslt = db_exec($sql) or errDie("Unable to retrieve team allocation.");

		if (!pg_num_rows($to_rslt)) {
			$sql = "
			INSERT INTO crm.team_owners (user_id, team_id)
			VALUES ('$user_id', '$id')";
			db_exec($sql) or errDie("Unable to allocate user to team.");
		}

		$sql = "SELECT * FROM cubit.users WHERE userid='$user_id'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");
		$user_data = pg_fetch_array($user_rslt);

		$sql = "SELECT * FROM crm.crms WHERE userid='$user_id'";
		$crms_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
		$crms_data = pg_fetch_array($crms_rslt);

		$teams_ar = explode("|", $crms_data["teams"]);

		if (!in_array($id, $teams_ar)) {
			$teams_ar[] = $id;
		}

		$teams = implode("|", $teams_ar);

		$sql = "
		INSERT INTO crm.crms (name, userid, teamid, div, teams)
		VALUES ('$user_data[username]', '$user_id', '$id', ".USER_DIV.", '$teams')";
		db_exec($sql) or errDie("Unable to allocate to query teams.");
	}
	return display();

}



function remove()
{

	extract ($_REQUEST);

	$sql = "DELETE FROM crm.team_owners WHERE id='$rem'";
	db_exec($sql) or errDie("Unable to remove user from team.");

	return display();

}

?>
<?php

if (!defined("STOCK_LIB")) {
	define("STOCK_LIB", true);

	function user_in_team($team_id, $user_id)
	{
		// Retrieve teams
		$sql = "SELECT * FROM crm.team_owners
		WHERE team_id='$team_id' AND user_id='$user_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

		if ( pg_num_rows($team_rslt) || !$team_id)
		{
			return true;
		}

		return false;
	}

	function user_is_admin($user)
	{
		if (is_numeric($user)) {
			$user_col = "userid='$user'";
		} else {
			$user_col = "username='$user'";
		}

		$sql = "SELECT admin FROM cubit.users WHERE $user_col";
		$admin_rslt = db_exec($sql) or errDie("Unable to retrieve admin status.");
		$admin = pg_fetch_result($admin_rslt, 0);

		return $admin;
	}

	function user_in_store_team($whid, $user_id)
	{
		if (user_is_admin($user_id)) {
			return true;
		}

		$sql = "SELECT team_id FROM exten.warehouses WHERE whid='$whid'";
		$wh_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");
		$team_id = pg_fetch_result($wh_rslt, 0);

		return user_in_team($team_id, $user_id);
	}
}

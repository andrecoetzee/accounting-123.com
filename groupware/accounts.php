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

# get settings
require ("../settings.php");
require_lib("validate");

// remove all '
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_POST_VARS[$key] = str_replace("'", "", $value);
	}
}
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

// overwrite GET_VARS with postvars, this helps to access both from one
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $arr => $val ) {
		$HTTP_GET_VARS[$arr] = $val;
	}
}

// set key=view if not set at all
if ( ! isset($HTTP_GET_VARS["key"]) ) $HTTP_GET_VARS["key"] = "view";

switch ( $HTTP_GET_VARS["key"] ) {
case "commitedit":
	$OUTPUT = writeAccount();
	break;

case "edit":
	$OUTPUT = editAccount();
	break;

case "delete":
case "commitdelete":
	$OUTPUT = deleteAccount();
	break;

case "view":
default:
	// check if an account was specified
	if ( isset($HTTP_GET_VARS["aid"]) && $HTTP_GET_VARS["aid"] == 0 ) {
		$OUTPUT = listAccounts(TRUE); // aid=0, LIST ALL ACCOUNTS (admin only)
	} else {
		$OUTPUT = listAccounts(FALSE);
	}

	break;
}

$OUTPUT = "
<div class='sub_container'>
	$OUTPUT
</div>";

require ("gw-tmpl.php");

// lists all the accounts, the parameter determines whether ALL accounts, or only accounts belonging to you should be shown
function listAccounts($all) {
	global $user_admin;

	$OUTPUT = "";

	// create the query's filter
	if ( $all && $user_admin ) { // ALL ACCOUNTS
		$filter = "";
	} else { // ONLY THOSE BELONGING TO THIS USER
		$filter = "WHERE username='".USER_NAME."'";
	}

	$sql = "SELECT account_id, user_edit, active, account_name, server_type, server_host, server_user, leave_msgs,
						enable_smtp, smtp_from, smtp_reply, smtp_host, smtp_auth, smtp_user, signature, \"public\"
		FROM mail_accounts
		$filter";

	$rslt = db_exec($sql);

	if ( pg_num_rows($rslt) <= 0 ) {
		$OUTPUT .= "No accounts to show.";
		return $OUTPUT;
	}

	// start to list each account
	while ( $row = pg_fetch_array($rslt) ) {
		// create variables for use in the output
		$row["active"] == "1" ? $active = "Active" : $active = "Inactive";
		$row["leave_msgs"] == "1" ? $leavemsgs = "Yes" : $leavemsgs = "No";
		$row["enable_smtp"] == "1" ? $smtp_enabled = "Yes" : $smtp_enabled = "No";
		$row["smtp_auth"] == "1" ? $smtp_auth = "Yes" : $smtp_auth = "No";
		$row["public"] == "1" ? $public = "Yes" : $public = "No";

		// may this user edit this account
		if ( $user_admin || $row["user_edit"] == '1' ) {
			$editlink = "<a class='th_href' href='accounts.php?key=edit&aid=$row[account_id]'>edit</a>";
		} else {
			$editlink = "&nbsp;";
		}

		// create the output
		$OUTPUT .= "
		<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th align=center colspan=2><font size=3>$row[account_name] ($active) $editlink</font></th>
		</tr>

		<tr>
			<th colspan='2'>General</th>
		</tr>
		<tr class='even'>
			<td >
				Public:
			</td>
			<td>
				$public
			</td>
		</tr>

		<tr>
			<th colspan='2'>POP3</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr class='even'>
			<td>
				Server:
			</td>
			<td>
				$row[server_host]
			</td>
		</tr>
		<tr class='odd'>
			<td >
				User:
			</td>
			<td>
				$row[server_user]
			</td>
		</tr>
		<tr class='even'>
			<td >
				Leave Message on Server:
			</td>
			<td>
				$leavemsgs
			</td>
		</tr>

		<tr>
			<th colspan='2' >SMTP</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr class='even'>
			<td >
				Enabled:
			</td>
			<td>
				$smtp_enabled
			</td>
		</tr>
		<tr class='odd'>
			<td >
				Host:
			</td>
			<td>
				$row[smtp_host]
			</td>
		</tr>
		<tr class='even'>
			<td >
				Auth:
			</td>
			<td>
				$smtp_auth
			</td>
		</tr>
		<tr class='odd'>
			<td >
				Username:
			</td>
			<td>
				$row[smtp_user]
			</td>
		</tr>

		</table><br>
		";
	}

	return $OUTPUT;
}

// checks whether a user may edit a specified account
function checkMayEdit($account_id) {
	global $user_admin;

	// create the may_edit filter if user not admin, admin has not filter
	if ( $user_admin )
		$may_edit = "";
	else
		$may_edit = "AND username = '".USER_NAME."' AND user_edit = '1'";

	$account_id+=0;
	// do the check
	$rslt = db_exec("SELECT 1 FROM mail_accounts WHERE account_id = $account_id $may_edit ");

	if ( pg_num_rows($rslt) <= 0 )
			return FALSE;

	return TRUE;
}

// creates and handles the form that u edit the account with
function editAccount() {
	global $HTTP_GET_VARS, $user_admin;

	// check if an account was specified
	if ( ! isset($HTTP_GET_VARS["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $HTTP_GET_VARS["aid"];
	}

	if ( checkMayEdit($account_id) == FALSE )
		return "You may not edit this account.<br>";

	// get the fields from dbase
	$rslt = db_exec("
	SELECT active, username, account_name, user_edit, server_type, server_host, server_user,
		leave_msgs, enable_smtp, smtp_from, smtp_reply, smtp_host, smtp_auth,
		smtp_user, signature, \"public\"
	FROM mail_accounts
	WHERE account_id = $account_id");

	if ( pg_num_rows($rslt) <= 0 )
		return "ERROR fetching account details. Please contact Cubit.";

	$det = pg_fetch_array($rslt);

	$OUTPUT = "";

	// check if each variable is in GET_VARS, meaning it was submitted a previous TIME
	// and overwrite the one returned from dbase
	foreach ( $HTTP_GET_VARS as $arr => $val ) {
		$det[$arr] = $val;
	}

	// create/format the previous values if any, so the form get's filled in
	( $det["active"] == '1' || $det["active"] == 'on' ) ? $active = "checked" : $active = "";
	( $det["leave_msgs"] == '1' || $det["leave_msgs"] == 'on' ) ? $leave_msgs = "checked" : $leave_msgs = "";
	( $det["enable_smtp"] == '1' || $det["enable_smtp"] == 'on' ) ? $smtp_enable = "checked" : $smtp_enable = "";
	( $det["public"] == '1' || $det["public"] == 'on' ) ? $public = "checked" : $public = "";
	( $det["smtp_auth"] == '1' || $det["smtp_auth"] == 'on' ) ? $smtp_auth = "checked" : $smtp_auth = "";
	( $det["user_edit"] == '1' || $det["user_edit"] == 'on' ) ? $user_edit = "checked" : $user_edit = "";

	// user selection list
	$rslt = db_exec("SELECT username FROM users");
	if ( pg_num_rows($rslt) < 0 ) {
		return "No users in database.";
	}

	$userselect = "<select name=username>";
	while ( $row = pg_fetch_array($rslt) ) {
		if ( $row["username"] == $det["username"] )
			$selected = "selected";
		else
			$selected = "";

		$userselect .= "<option $selected value='$row[username]'>$row[username]</option>";
	}
	$userselect.="</select>";

	// create the header
	$OUTPUT .= "
	<form method='POST' action='accounts.php'>
	<input type='hidden' name='key' value='commitedit'>
	<input type='hidden' name='aid' value='$account_id'>

	<table cellpadding='2' cellspacing='0' class='shtable'>
	<tr>
		<th colspan='2' style='text-align: center'>$det[account_name]</th>
	</tr>
	<tr>
		<th colspan='2'>General</th>
	</tr>
	<tr>
		<td  class='even'>
			Account owner:
		</td>
		<td class='even'>
			$userselect
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			".REQ."Account name:
		</td>
		<td class='odd'>
			<input type='text' name='account_name' value='$det[account_name]'>
		</td>
	</tr>
	<tr>
		<td  class='even'>
			Public:
		</td>
		<td class='even'>
			<input type='checkbox' name='public' $public>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			Active:
		</td>
		<td class='odd'>
			<input type='checkbox' name='active' $active>
		</td>
	</tr>
	<tr>
		<td  class='even'>
			User may edit this account:
		</td>
		<td class='even'>
			<input type='checkbox' name='user_edit' $user_edit>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			Signature:
		</td>
		<td class='odd'>
			<textarea name='signature'>$det[signature]</textarea>
		</td>
	</tr>

	<tr>
		<th colspan='2'>POP3</th>
	</tr>
	<tr>
		<td  class='even'>
			Server:
		</td>
		<td class='even'>
			<input type=text name='server_host' value='$det[server_host]'>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			User:
		</td>
		<td class='odd'>
			<input type=text name='server_user' value='$det[server_user]'>
		</td>
	</tr>
	<tr>
		<td  class='even'>
			Password:
		</td>
		<td class='even'>
			<input type=password name='server_pass' value=''>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			Confirm:
		</td>
		<td class='odd'>
			<input type=password name='server_passconfirm' value=''>
		</td>
	</tr>
	<tr>
		<td  class='even'>
			Leave Message on Server:
		</td>
		<td class='even'>
			<input type=checkbox name='leave_msgs' $leave_msgs>
		</td>
	</tr>

	<tr>
		<th colspan='2'>SMTP</th>
	</tr>
	<tr>
		<td  class='even'>
			Enabled:
		</td>
		<td class='even'>
			<input type=checkbox name='enable_smtp' $smtp_enable>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			Host:
		</td>
		<td class='odd'>
			<input type=text name='smtp_host' value='$det[smtp_host]'>
		</td>
	</tr>
	<tr>
		<td  class='even'>
			From address:
		</td>
		<td class='even'>
			<input type=text name='smtp_from' value='$det[smtp_from]'>
		</td>
	</tr>
	<tr>
		<td  class='odd'>
			Reply address:
		</td>
		<td class='odd'>
			<input type=text name='smtp_reply' value='$det[smtp_reply]'>
		</td>
	</tr>
	</table>
	<p></p>
	<input type=submit value='Update'> &nbsp;
	<input type=reset value='Clear'>

	<input type=hidden name='smtp_auth' value='$smtp_auth'>
	<input type=hidden name='smtp_user' value='$det[smtp_user]'>
	<input type=hidden name='smtp_pass' value=''>
	<input type=hidden name='smtp_passconfirm' value=''>
	</form>
	<form method=post action=accounts.php>
		<input type=hidden name=key value=delete>
		<input type=hidden name=aid value=$account_id>
		<input type=submit value='Delete'>
	</form>";

	return $OUTPUT;
}

// checks the submitted data and if valid writes to database
function writeAccount() {
	global $HTTP_GET_VARS;

	// check if an account was specified
	if ( ! isset($HTTP_GET_VARS["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $HTTP_GET_VARS["aid"];
	}

	// may we edit?
	if ( checkMayEdit($account_id) == FALSE )
		return "You may not edit this account.";

	$OUTPUT = "";

	// verify
	extract($HTTP_GET_VARS);

	$v = & new validate;

	if ( isset($account_name) ) $v->isOK($account_name, "string", 1, 150, "Invalid account name.");
	if ( isset($server_host) ) $v->isOK($server_host, "url", 0, 255, "Invalid host for pop3 server.");
	if ( isset($server_user) ) $v->isOK($server_user,"string", 0, 255, "Invalid username.");
	if ( isset($server_pass) ) $v->isOK($server_pass,"string", 0, 255, "Invalid password.");
	if ( isset($server_pass_confirm) ) $v->pwMatch($server_pass, $server_passconfirm, "Passwords do not match");
	if ( isset($smtp_from) ) $v->isOK($smtp_from, "email", 0, 255, "Invalid email address in SMTP from field");
	if ( isset($smtp_reply) ) $v->isOK($smtp_reply, "email", 0, 255, "Invalid email address in SMTP reply field");
	if ( isset($signature) ) $v->isOK($signature,"string", 0, 1024, "Invalid signature");
	if ( isset($smtp_host) ) $v->isOK($smtp_host, "url", 0, 255, "Invalid SMTP host");
	if ( isset($smtp_user) ) $v->isOK($smtp_user, "string", 0, 255, "Invalid SMTP user.");
	if ( isset($smtp_pass) ) $v->isOK($smtp_pass, "string", 0, 255, "Invalid SMTP password.");
	if ( isset($smtp_passconfirm) ) $v->pwMatch($smtp_pass, $smtp_passconfirm, "SMTP passwords do not match");

	// was there erros
	if ( $v->isError() ) {
		$err = $v->getErrors();

		foreach ( $err as $ernum => $val ) {
			$OUTPUT .= "$val[msg]<br>";
		}

		// load the previous function
		$OUTPUT .= editAccount();

		return $OUTPUT;
	}

	// change the checkboxes to values recognized by the db
	if ( ! isset($active) ) $active = "0";
	if ( ! isset($user_edit) ) $user_edit = "0";
	if ( ! isset($leave_msgs) ) $leave_msgs = "0";
	if ( ! isset($enable_smtp) ) $enable_smtp = "0";
	//if ( ! isset($smtp_auth) ) $smtp_auth = "0";
	$smtp_auth = "0";
	if ( ! isset($public) ) $public = "0";

	if ( $active == "on" ) $active = "1";
	if ( $user_edit == "on" ) $user_edit = "1";
	if ( $leave_msgs == "on" ) $leave_msgs = "1";
	if ( $enable_smtp == "on" ) $enable_smtp = "1";
	//if ( $smtp_auth == "on" ) $smtp_auth = "1";
	if ( $public == "on" ) $public = "1";

	// only change the passwords if a new one was specified
	if ( ! empty($smtp_pass) )
		$smtp_pass_change = "smtp_pass = '$smtp_pass',";
	else
		$smtp_pass_change = "";

	if ( ! empty($server_pass) )
		$server_pass_change = "server_pass = '$server_pass',";
	else
		$server_pass_change = "";

	// ok now update
	$sql = "UPDATE mail_accounts
		SET 	active = '$active',
			username = '$username',
			user_edit = '$user_edit',
			account_name = '$account_name',
			server_host = '$server_host',
			server_user = '$server_user',
			$server_pass_change
			leave_msgs = '$leave_msgs',
			enable_smtp = '$enable_smtp',
			smtp_from = '$smtp_from',
			smtp_reply = '$smtp_reply',
			smtp_host = '$smtp_host',
			smtp_auth = '$smtp_auth',
			smtp_user = '$smtp_user',
			$smtp_pass_change
			signature = '$signature',
			\"public\" = '$public'
		WHERE account_id = $account_id";

	$rslt = db_exec($sql);

	if ( pg_cmdtuples($rslt) <= 0 ) {
		return "Error updating account. Please contact Cubit.<br>";
	}

	header("Location: messages.php?key=frameset&fid=0");

	$OUTPUT .= "Succesfully updated account.<br>";

	return $OUTPUT;
}

// delete an account
function deleteAccount() {
	global $HTTP_GET_VARS;

	// check if an account was specified
	if ( ! isset($HTTP_GET_VARS["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $HTTP_GET_VARS["aid"];
	}

	// may we edit?
	if ( checkMayEdit($account_id) == FALSE )
		return "You may not edit this account.";

	// check if this is the prompt or the real kill
	if ( $HTTP_GET_VARS["key"] && $HTTP_GET_VARS["key"] == "delete" ) {
		$OUTPUT = "
		<form method=POST action=accounts.php>
			Are you sure u want to delete this account?<br>
			<input type=hidden name=key value=commitdelete>
			<input type=hidden name=aid value=$account_id>
			<input type=submit value=yes>
			<input type=button onClick='document.location.href=\"accounts.php?key=edit&aid=$account_id\"' value=no>
		</form>";

		return $OUTPUT;
	}

	// delete it
	$rslt = db_exec("DELETE FROM mail_accounts WHERE account_id = $account_id");
	if ( pg_cmdtuples($rslt) < 0 )
		return "Error deleting account.<br>";

	$rslt = db_exec("DELETE FROM mail_folders WHERE account_id = $account_id");
	if ( pg_cmdtuples($rslt) < 0 )
		return "Error deleting account.<br>";

	$rslt = db_exec("DELETE FROM mail_account_settings WHERE account_id  = $account_id");
	if ( pg_cmdtuples($rslt) < 0 )
		return "Error deleting account.<br>";

	$OUTPUT = ""; // the following java script merely refreshes the tree view frame, so the new account get's shown
	$OUTPUT .= "<script>
		parent.tree.document.location.reload();
	</script>

	Account succesfully deleted.";

	return $OUTPUT;
}

?>

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
if ( isset($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_POST[$key] = str_replace("'", "", $value);
	}
}
if ( isset($_GET) ) {
	foreach ( $_GET as $key => $value ) {
		$_GET[$key] = str_replace("'", "", $value);
	}
}

// overwrite _GET with postvars, this helps to access both from one
if ( isset($_POST) ) {
	foreach ( $_POST as $arr => $val ) {
		$_GET[$arr] = $val;
	}
}

// set key=view if not set at all
if ( ! isset($_GET["key"]) ) $_GET["key"] = "view";

switch ( $_GET["key"] ) {
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
	if ( isset($_GET["aid"]) && $_GET["aid"] == 0 ) {
		$OUTPUT = listAccounts(TRUE); // aid=0, LIST ALL ACCOUNTS (admin only)
	} else {
		$OUTPUT = listAccounts(FALSE);
	}

	break;
}

require ("../template.php");

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
			$editlink = "<a class='maildef' href='accounts.php?key=edit&aid=$row[account_id]'>edit</a>";
		} else {
			$editlink = "&nbsp;";
		}

		// create the output
		$OUTPUT .= "
		<table width=100%>
		<tr>
			<th align=center colspan=2><font size=3>$row[account_name] ($active)</font></th>
		</tr>

		<tr>
			<th width=150>General</th>
			<td align=right>$editlink</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Public:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$public
			</td>
		</tr>

		<tr>
			<th width=150>POP3</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Server:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$row[server_host]
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				User:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				$row[server_user]
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Leave Message on Server:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$leavemsgs
			</td>
		</tr>

		<tr>
			<th width=150>SMTP</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Enabled:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$smtp_enabled
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Host:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				$row[smtp_host]
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Auth:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$smtp_auth
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Username:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
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
	global $_GET, $user_admin;

	// check if an account was specified
	if ( ! isset($_GET["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $_GET["aid"];
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

	// check if each variable is in _GET, meaning it was submitted a previous TIME
	// and overwrite the one returned from dbase
	foreach ( $_GET as $arr => $val ) {
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
	<table width='100%'>
	<tr><th><font size=2>$det[account_name]</font></th></tr>";

	// the form
	$OUTPUT .= "
	<tr><td>
	<form method='POST' action='accounts.php'>
		<input type='hidden' name='key' value='commitedit'>
		<input type='hidden' name='aid' value='$account_id'>

		<table width=100%>
		<tr>
			<th width=150>General</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Account owner:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				$userselect
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				".REQ."Account name:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type='text' name='account_name' value='$det[account_name]'>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Public:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type='checkbox' name='public' $public>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Active:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type='checkbox' name='active' $active>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				User may edit this account:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type='checkbox' name='user_edit' $user_edit>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Signature:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<textarea name='signature'>$det[signature]</textarea>
			</td>
		</tr>

		<tr>
			<th width=150>POP3</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Server:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type=text name='server_host' value='$det[server_host]'>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				User:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type=text name='server_user' value='$det[server_user]'>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Password:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type=password name='server_pass' value=''>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Confirm:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type=password name='server_passconfirm' value=''>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Leave Message on Server:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type=checkbox name='leave_msgs' $leave_msgs>
			</td>
		</tr>

		<tr>
			<th width=150>SMTP</th>
			<td align=right>&nbsp;</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				Enabled:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type=checkbox name='enable_smtp' $smtp_enable>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Host:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type=text name='smtp_host' value='$det[smtp_host]'>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor1.">
				From address:
			</td>
			<td bgcolor=".TMPL_tblDataColor1.">
				<input type=text name='smtp_from' value='$det[smtp_from]'>
			</td>
		</tr>
		<tr>
			<td width=150 bgcolor=".TMPL_tblDataColor2.">
				Reply address:
			</td>
			<td bgcolor=".TMPL_tblDataColor2.">
				<input type=text name='smtp_reply' value='$det[smtp_reply]'>
			</td>
		</tr>
		<tr>
			<td align=right>
				<input type=submit value='Update'> &nbsp;
				<input type=reset value='Clear'>

				<input type=hidden name='smtp_auth' value='$smtp_auth'>
				<input type=hidden name='smtp_user' value='$det[smtp_user]'>
				<input type=hidden name='smtp_pass' value=''>
				<input type=hidden name='smtp_passconfirm' value=''>

				</form>
			</td>
			<td align=left>
				<form method=post action=accounts.php>
					<input type=hidden name=key value=delete>
					<input type=hidden name=aid value=$account_id>
					<input type=submit value='Delete'>
				</form>
			</td>
		</tr>
		</table>
	</td></tr>
	";

	// finish the table and return
	$OUTPUT .= "</table>";

	return $OUTPUT;
}

// checks the submitted data and if valid writes to database
function writeAccount() {
	global $_GET;

	// check if an account was specified
	if ( ! isset($_GET["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $_GET["aid"];
	}

	// may we edit?
	if ( checkMayEdit($account_id) == FALSE )
		return "You may not edit this account.";

	$OUTPUT = "";

	// verify
	extract($_GET);

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

	$OUTPUT .= "<script>
		parent.tree.document.location.reload();
	</script>

	Succesfully updated account.<br>";

	return $OUTPUT;
}

// delete an account
function deleteAccount() {
	global $_GET;

	// check if an account was specified
	if ( ! isset($_GET["aid"]) ) {
		return "No account specified.";
	} else {
		$account_id = $_GET["aid"];
	}

	// may we edit?
	if ( checkMayEdit($account_id) == FALSE )
		return "You may not edit this account.";

	// check if this is the prompt or the real kill
	if ( $_GET["key"] && $_GET["key"] == "delete" ) {
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

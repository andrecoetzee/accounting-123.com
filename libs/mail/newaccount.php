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
if ( ! isset($HTTP_GET_VARS["key"]) ) $HTTP_GET_VARS["key"] = "new";

switch ( $HTTP_GET_VARS["key"] ) {
case "commitnew":
	$OUTPUT = writeAccount();
	break;

case "new":
default:
	$OUTPUT = newAccount();

	break;
}

require ("../template.php");

// creates and handles the form that u edit the account with
function newAccount() {
	global $HTTP_GET_VARS, $user_admin;

	$OUTPUT = "";

	// make a pointer to GET VARS for easier access
	$det = & $HTTP_GET_VARS;

	// set the variables to blank if not set
	if (! isset($det["active"]) ) $det["active"] = "";
	if (! isset($det["username"]) ) $det["username"] = "";
	if (! isset($det["user_edit"]) ) $det["user_edit"] = "";
	if (! isset($det["active"]) ) $det["active"] = "";
	if (! isset($det["account_name"]) ) $det["account_name"] = "";
	if (! isset($det["server_type"]) ) $det["server_type"] = "";
	if (! isset($det["server_host"]) ) $det["server_host"] = "";
	if (! isset($det["server_user"]) ) $det["server_user"] = "";
	if (! isset($det["server_pass"]) ) $det["server_pass"] = "";
	if (! isset($det["server_passconfirm"]) ) $det["server_passconfirm"] = "";
	if (! isset($det["leave_msgs"]) ) $det["leave_msgs"] = "";
	if (! isset($det["enable_smtp"]) ) $det["enable_smtp"] = "";
	if (! isset($det["smtp_from"]) ) $det["smtp_from"] = "";
	if (! isset($det["smtp_reply"]) ) $det["smtp_reply"] = "";
	if (! isset($det["smtp_host"]) ) $det["smtp_host"] = "";
	if (! isset($det["smtp_auth"]) ) $det["smtp_auth"] = "";
	if (! isset($det["smtp_user"]) ) $det["smtp_user"] = "";
	if (! isset($det["smtp_pass"]) ) $det["smtp_pass"] = "";
	if (! isset($det["smtp_passconfirm"]) ) $det["smtp_passconfirm"] = "";
	if (! isset($det["signature"]) ) $det["signature"] = "";
	if (! isset($det["public"]) ) $det["public"] = "";

	// create/format the previous values if any, so the form get's filled in
	$det["active"] == '1' ? $active = "checked" : $active = "";
	$det["leave_msgs"] == '1' ? $leave_msgs = "checked" : $leave_msgs = "";
	$det["enable_smtp"] == '1' ? $smtp_enable = "checked" : $smtp_enable = "";
	$det["public"] == '1' ? $public = "checked" : $public = "";
	$det["smtp_auth"] == '1' ? $smtp_auth = "checked" : $smtp_auth = "";
	$det["user_edit"] == '1' ? $user_edit = "checked" : $user_edit = "";
	$det["active"] == 'on' ? $active = "checked" : $active = "";
	$det["leave_msgs"] == 'on' ? $leave_msgs = "checked" : $leave_msgs = "";
	$det["enable_smtp"] == 'on' ? $smtp_enable = "checked" : $smtp_enable = "";
	$det["public"] == 'on' ? $public = "checked" : $public = "";
	$det["smtp_auth"] == 'on' ? $smtp_auth = "checked" : $smtp_auth = "";
	$det["user_edit"] == 'on' ? $user_edit = "checked" : $user_edit = "";

	// create the user selection box
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
	<table width='100%'>";

	// the form
	$OUTPUT .= "
	<tr><td>
	<form method='POST' action='newaccount.php'>
		<input type='hidden' name='key' value='commitnew'>

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
				Server:
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
			<td colspan=2 align=center>
				<input type=submit value='Create'> &nbsp;
				<input type=reset value='Clear'>
			</td>
		</tr>
		</table>

		<input type=hidden name='smtp_auth' value='$smtp_auth'>
		<input type=hidden name='smtp_user' value='$det[smtp_user]'>
		<input type=hidden name='smtp_pass' value=''>
		<input type=hidden name='smtp_passconfirm' value=''>

	</form>
	</td></tr>
	";

	// finish the table and return
	$OUTPUT .= "</table>";

	return $OUTPUT;
}

// checks the submitted data and if valid writes to database
function writeAccount() {
	global $HTTP_GET_VARS;

	$OUTPUT = "";

	// verify
	extract($HTTP_GET_VARS);

	$v = & new validate;

	if ( isset($account_name) ) $v->isOK($account_name, "string", 1, 150, "Invalid account name.");
	if ( isset($server_host) ) $v->isOK($server_host, "url", 0, 255, "Invalid host for pop3 server.");
	if ( isset($server_user) ) $v->isOK($server_user,"string", 0, 255, "Invalid username.");
	if ( isset($server_pass) ) if ( strpos($server_pass, "'") ) $v->addError("Invalid POP4 password.");
	if ( isset($server_pass_confirm) ) $v->pwMatch($server_pass, $server_passconfirm, "Passwords do not match");
	if ( isset($smtp_from) ) $v->isOK($smtp_from, "email", 0, 255, "Invalid email address in SMTP from field");
	if ( isset($smtp_reply) ) $v->isOK($smtp_reply, "email", 0, 255, "Invalid email address in SMTP reply field");
	if ( isset($signature) ) $v->isOK($signature,"string", 0, 1024, "Invalid signature");
	if ( isset($smtp_host) ) $v->isOK($smtp_host, "url", 0, 255, "Invalid SMTP host");
	if ( isset($smtp_user) ) $v->isOK($smtp_user, "string", 0, 255, "Invalid SMTP user.");
	if ( isset($smtp_pass) ) if ( strpos($smtp_pass, "'") ) $v->addError("Invalid SMTP password.");
	if ( isset($smtp_passconfirm) ) $v->pwMatch($smtp_pass, $smtp_passconfirm, "SMTP passwords do not match");

	// was there erros
	if ( $v->isError() ) {
		$err = $v->getErrors();

		foreach ( $err as $ernum => $val ) {
			$OUTPUT .= "$val[msg]<br>";
		}

		// load the previous function
		$OUTPUT .= newAccount();;

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
	if ( $smtp_auth == "on" ) $smtp_auth = "1";
	if ( $public == "on" ) $public = "1";

	pglib_transaction("BEGIN");

	$sql = "INSERT INTO mail_accounts (username, user_edit, active, account_name, server_type, server_host, server_user,
			server_pass, leave_msgs, enable_smtp, smtp_from, smtp_reply, smtp_host, smtp_auth, smtp_user,
			smtp_pass, signature, \"public\")
		VALUES ('$username', '$user_edit', '$active', '$account_name', 'POP3', '$server_host', '$server_user',
			'$server_pass', '$leave_msgs', '$enable_smtp', '$smtp_from', '$smtp_reply', '$smtp_host',
			'$smtp_auth', '$smtp_user', '$smtp_pass', '$signature', '$public')";

	$rslt = db_exec($sql);

	if ( pg_cmdtuples($rslt) <= 0 ) {
		return "Error creating account. Please contact Cubit.<br>";
	}

	$aid = pglib_lastid("mail_accounts", "account_id");

	pglib_transaction("COMMIT");

	// create the five special folders folders
	db_exec("INSERT INTO mail_folders (parent_id, account_id, icon_open, icon_closed, name, username, \"public\")
		VALUES (0, $aid, 'icon_inboxopen.gif', 'icon_inboxclosed.gif', 'Inbox', '$username', '0')");
	/*db_exec("INSERT INTO mail_folders (parent_id, account_id, icon_open, icon_closed, name, username, public)
		VALUES (0, $aid, 'icon_folderopen.gif', 'icon_folderclosed.gif', 'Outbox', '$username', '0')");
	db_exec("INSERT INTO mail_folders (parent_id, account_id, icon_open, icon_closed, name, username, public)
		VALUES (0, $aid, 'icon_draftsopen.gif', 'icon_draftsclosed.gif', 'Drafts', '$username', '0')");*/
	db_exec("INSERT INTO mail_folders (parent_id, account_id, icon_open, icon_closed, name, username, public)
		VALUES (0, $aid, 'icon_sentopen.gif', 'icon_sentclosed.gif', 'Sent Items', '$username', '0')");
	/*db_exec("INSERT INTO mail_folders (parent_id, account_id, icon_open, icon_closed, name, username, public)
		VALUES (0, $aid, 'icon_trashopen.gif', 'icon_trashclosed.gif', 'Trash', '$username', '0')");*/

	// set them as the special folders
	$rslt = db_exec("SELECT folder_id FROM mail_folders WHERE name='Inbox' AND account_id=$aid");
	$inbox = pg_fetch_result($rslt, 0, 0);
	/*$rslt = db_exec("SELECT folder_id FROM mail_folders WHERE name='Outbox' AND account_id=$aid");
	$outbox = pg_fetch_result($rslt, 0, 0);
	$rslt = db_exec("SELECT folder_id FROM mail_folders WHERE name='Drafts' AND account_id=$aid");
	$drafts = pg_fetch_result($rslt, 0, 0);*/
	$rslt = db_exec("SELECT folder_id FROM mail_folders WHERE name='Sent Items' AND account_id=$aid");
	$sentitems = pg_fetch_result($rslt, 0, 0);
	/*$rslt = db_exec("SELECT folder_id FROM mail_folders WHERE name='Trash' AND account_id=$aid");
	$trash = pg_fetch_result($rslt, 0, 0);*/

	//db_exec("INSERT INTO mail_account_settings VALUES($aid, $inbox, $drafts, $sentitems, $trash, $outbox)");
	db_exec("INSERT INTO mail_account_settings VALUES('$aid', '$inbox', 0, '$sentitems', 0, 0)");

	// the following java script merely refreshes the tree view frame, so the new account get's shown
	$OUTPUT .= "<script>
		parent.tree.document.location.reload();
	</script>Succesfully created account.<br>";

	return $OUTPUT;
}

?>


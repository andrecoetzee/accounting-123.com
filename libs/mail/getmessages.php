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

require ("object_popmail.php");
require ("object_mailmsg.php");

$OUTPUT = "";

// call the functions
//$OUTPUT .= sendMessages();
$OUTPUT .= receiveMessages();

// redirect to mail_messages
$OUTPUT .= "<script></script>";

require ("../template.php");

// gets the messages from the server
function receiveMessages() {
	$OUTPUT = "";

	// retrieve all accounts this user has access to
	$sql = "SELECT account_id, account_name, server_host, server_user, server_pass, leave_msgs
			FROM mail_accounts
			WHERE ( username = '".USER_NAME."' OR \"public\" = '1' ) AND active = '1'

		UNION
		SELECT mail_accounts.account_id, account_name, server_host, server_user, server_pass, leave_msgs
			FROM mail_accounts,mail_priv_accounts
			WHERE ( mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' ) AND active = '1'";

	$rslt = db_exec($sql);

	// go through each account and retrieve the messages
	$pop = & new clsPOPMail;
	$msg = & new clsMailMsg;
	if ( pg_num_rows($rslt) <= 0 ) {
		$OUTPUT .= "No active accounts found.";
	} else {
		while ( $account = pg_fetch_array($rslt) ) {
			$accid = $account["account_id"];
			$accname = $account["account_name"];
			$host = $account["server_host"];
			$port = 110;
			$user = $account["server_user"];
			$pass = $account["server_pass"];
			$leave_msgs = $account["leave_msgs"];

			// if the retrieveMessages returned true, it means an error has been found.
			// Print and continue with next server.
			if ( $connection = $pop->retrieveMessages($host, $port, $user, $pass, $leave_msgs) ) {
				$OUTPUT .= "($accname) $connection<br>";
				continue;
			}

			// get each received message, pass to processor, and store in database
			$msgcount = 0;
			while ( $buf = $pop->enumGetMessage() ) {
				// get the data to be inserted
				if ( $msg->processMessage($buf) == FALSE ) continue;
				$type_id = getMsgType($msg->type);

				// data and header is base64_encoded so weird characters can also be stored
				$data = base64_encode( $buf );

				// insert body into Cubit
				if ( ! pglib_transaction("BEGIN") ) continue;

				$rslt = db_exec("INSERT INTO mail_msgbodies (type_id, data)
					VALUES( $type_id, '$data' )");

				if ( pg_cmdtuples($rslt) <= 0 ) continue;

				$msgbody_id = pglib_lastid("mail_msgbodies", "msgbody_id");

				if ( ! pglib_transaction("COMMIT") ) continue;

				// get the folder this message should be inserted into
				$rslt = db_exec("SELECT fid_inbox FROM mail_account_settings WHERE account_id=$accid");

				if ( pg_num_rows($rslt) > 0 )
					$infolder = pg_fetch_result($rslt, 0, 0);
				else
					$infolder = 0; // move to no folder, but store, this way all is not lost

				// check if the user even MAY add to this folder (account of folder they have
				// privileges to, folder.username = their's, they have privileges to this folder
				// it is a public folder, public account
				$sql = "
				SELECT 1 FROM mail_folders WHERE folder_id = $infolder
					AND (\"public\" = '1' OR username='".USER_NAME."')
				UNION
				SELECT 1 FROM mail_accounts, mail_folders WHERE folder_id = $infolder
					AND mail_accounts.account_id=mail_folders.account_id
					AND (mail_accounts.username = '".USER_NAME."' OR mail_accounts.\"public\" = '1')
				UNION
				SELECT 1 FROM mail_priv_accounts, mail_folders WHERE folder_id = $infolder
					AND mail_priv_accounts.account_id = mail_folders.account_id
					AND priv_owner = '".USER_NAME."'
				UNION
				SELECT 1 FROM mail_priv_folders WHERE folder_id = $infolder
					AND priv_owner = '".USER_NAME."'";

				$rslt = db_exec($sql);

				if ( pg_num_rows($rslt) <= 0 )
					continue; // you may not add to this folder (inbox folder for account);

				// insert the message linked to body
				$sql = " INSERT INTO mail_messages ( account_id, folder_id, subject, add_from, add_to, add_cc,
							add_bcc, priority, attachments, msgbody_id, flag, date)
						VALUES ( '$accid', '$infolder', '$msg->subject', '$msg->from', '$msg->to',
							'$msg->cc', '$msg->bcc', '1', '0', '$msgbody_id', '1', CURRENT_TIMESTAMP)";

				$rslt = db_exec($sql);

				if ( pg_cmdtuples($rslt) <= 0 ) {
					continue;
				}

				$msgcount++;
			}

			$OUTPUT .= "Received $msgcount messages for $accname.<br>";
		}
	}

	return $OUTPUT;
}

// function that get's the type id out of mail_datatypes, if it doesn't exist, it creates it
function getMsgType($msg_type) {
	$rslt = db_exec("SELECT type_id FROM mail_datatypes WHERE name = '$msg_type'");

	// does it exist? return it
	if ( pg_num_rows($rslt) > 0 ) {
		return pg_fetch_result( $rslt, 0, 0 );
	}

	// it doesn't! create it and get the insert id
	if ( ! pglib_transaction("BEGIN") )
		return 1;

	if ( ! db_exec("INSERT INTO mail_datatypes (name,icon) VALUES('$msg_type', 'icon_blank.gif')") )
		return;

	$type_id = pglib_lastid("mail_datatypes","type_id");

	if ( ! pglib_transaction("COMMIT") )
		return 1;

	return $type_id;
}

?>

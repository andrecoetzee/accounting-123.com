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

require ("object_mailmsg.php");

if ( isset($HTTP_GET_VARS["msg_id"]) ) {
	$msg_id = $HTTP_GET_VARS["msg_id"];
} else {
	$msg_id = 0;

	// no msg id has been specified, get the newest message in the current folder, if any
	if ( isset($HTTP_GET_VARS["fid"]) ) {
		$v = & new Validate();
		if ($v->isOK($HTTP_GET_VARS["fid"], "num", 1, 9, "") == FALSE) {
			die("Invalid folder id specified");
		}

		$rslt = db_exec("SELECT message_id FROM mail_messages
			WHERE folder_id='$HTTP_GET_VARS[fid]' ORDER BY date DESC");

		if ( pg_num_rows($rslt) > 0 ) {
			$msg_id = pg_fetch_result($rslt, 0, 0); // fetch the first msg :>
		}
	}
}

// if msg_id is zero by here, there is no possible message to display with the current settings, output message and exit
if ( $msg_id == 0 ) {
	$OUTPUT = "No message to display.<br>";
	require ("../template.php");
	exit();
}

$OUTPUT = "";

// read the message
$rslt = db_exec("SELECT folder_id,subject,add_from,add_to,add_cc,add_bcc,priority,attachments,msgbody_id,date
		FROM mail_messages
		WHERE message_id = $msg_id;");

if ( pg_num_rows($rslt) <=0 ) {
	exit("No such message.");
}

// fetch the message data
$msg_data = pg_fetch_array($rslt);

// check to see if user has access  to this message's folder, giving him access to the message
$sql = "
SELECT folder_id FROM mail_folders
	WHERE ( username = '".USER_NAME."' OR \"public\" = '1' ) AND folder_id=$msg_data[folder_id]
UNION
SELECT folder_id FROM mail_priv_folders WHERE priv_owner='".USER_NAME."' AND folder_id = $msg_data[folder_id]
UNION
SELECT mail_priv_accounts.account_id FROM mail_priv_accounts,mail_folders
	WHERE mail_folders.account_id = mail_priv_accounts.account_id";
$rslt = db_exec($sql);

if ( pg_num_rows($rslt) <= 0 ) {
	exit("You dont have sufficient privileges to read this message.");
}

// read the message body now that we know we are safe
$rslt = db_exec("SELECT name,data FROM mail_msgbodies,mail_datatypes
	 WHERE msgbody_id=$msg_data[msgbody_id] AND mail_datatypes.type_id = mail_msgbodies.type_id");
if ( pg_num_rows($rslt) <= 0 ) {
	exit("Message body not found. Please contact Cubit.");
}

$msgbody = pg_fetch_array($rslt);

// decode
$msgbody["data"] = base64_decode($msgbody["data"]);

// process
$msg = & new clsMailMsg;
$msg->processMessage($msgbody["data"]);

// if it is a multipart message, create attachment list for all attachments, and fill the body with the rest
if ( $msg->maintype == "multipart" ) {
	if ( ! is_array($msg->parts) ) {
		$msgbody["data"] = implode("", $msg->body);
		return;
	}

	// ok start splitting body from attachment
	$pmsg = & new clsMailMsg;
	$msgbody["data"] = "";

	foreach ( $msg->parts as $pnum => $partdata ) {
		$partdata = implode ("\n", $partdata);

		$pmsg->processMessage($partdata);

                $HTTP_GET_VARS["filename"] = str_replace("\\\"","\"",$HTTP_GET_VARS["filename"]);

		if ( $pmsg->getAttachmentFilename() == $HTTP_GET_VARS["filename"] ) {
			$pb = $pmsg->body;
			foreach ( $pb as $k => $v ) {
				$pb[$k] = trim($v);
			}
                        $pmsg_data = implode("",$pb);

			// decode the message if shoudl
			if ( isset($pmsg->headers["Content-Transfer-Encoding"]) ) {
				// base64 encoding
				if ( strtolower($pmsg->headers["Content-Transfer-Encoding"]) == "base64" ) {
					$pmsg_data = base64_decode($pmsg_data);
				}
			}

			// create the output depending on the type of msg
			$msgbody["data"] = $pmsg_data;
			$content_type = $pmsg->type;
			$filename = $HTTP_GET_VARS["filename"];
			break;
		}
	}

	header("Content-Type: $content_type");
	header("Content-Disposition: inline; filename=\"".str_replace('"', '', $filename)."\"");
} else {
	$msgbody["data"] = "This message does not have any attachments";
}

print $msgbody["data"];

?>

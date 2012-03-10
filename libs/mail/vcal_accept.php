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

	$HTTP_GET_VARS["filename"] = base64_decode($HTTP_GET_VARS["filename"]);
	$HTTP_GET_VARS["filename"] = str_replace("\\\"","\"",$HTTP_GET_VARS["filename"]);

	foreach ( $msg->parts as $pnum => $partdata ) {
		$partdata = implode ("\n", $partdata);

		$pmsg->processMessage($partdata);

		if ( $pmsg->getAttachmentFilename() == $HTTP_GET_VARS["filename"] ) {
			$pb = $pmsg->body;
			foreach ( $pb as $k => $v ) {
				$pb[$k] = trim($v);
			}

			$pmsg_data = implode("", $pb);

			// decode the message if shoudl
			if ( isset($pmsg->headers["Content-Transfer-Encoding"]) ) {
				// base64 encoding
				if ( strtolower($pmsg->headers["Content-Transfer-Encoding"]) == "base64" ) {
					$pmsg_data = base64_decode($pmsg_data);
				}
			}

			// create the output depending on the type of msg
			$msgbody["data"] = explode("\n", $pmsg_data);
			$content_type = $pmsg->type;
			$filename = $HTTP_GET_VARS["filename"];
			break;
		}
	}

	header("Content-Type: text/html");
} else {
	$msgbody["data"] = Array();
}

$vcal = & $msgbody["data"];

/*
STATUS LEVELS:
0: Seeking begin of vcalendar
1: Parsing GENERAL headers
2: Parsing VTIMEZONE headers
3: Parsing VEVENT headers
4: Parsing VALARM headers
*/
$status = 0;
$finished = false;
$se = Array();

foreach ( $vcal as $lnum => $ln ) {
	$parts = explode(":", $ln);

	switch ( $status ) {
	case 1: // GENERAL
		if ( trim($parts[0]) == "END" && trim($parts[1]) == "VCALENDAR" ) $finished = true;

		if ( trim($parts[0]) == "BEGIN" ) {
			if ( trim($parts[1]) == "VTIMEZONE" ) $status = 2;
			if ( trim($parts[1]) == "VEVENT" ) $status = 3;
		}
		break;

	case 2: // VTIMEZONE
		if ( trim($parts[0]) == "END" && trim($parts[1]) == "VTIMEZONE" ) {
			$status = 1;
			break;
		}

		break;

	case 3: // VEVENT
		if ( trim($parts[0]) == "END" && trim($parts[1]) == "VEVENT" ) {
			$status = 1;
			break;
		}

		// check for VALARM
		if ( trim($parts[0]) == "BEGIN" && trim($parts[1]) == "VALARM" ) $status = 4;

		$sparts = explode(";", $parts[0]);

		// line contains the organizer information
		if ( trim($sparts[0]) == "ORGANIZER" ) {
			foreach ( $sparts as $spnum => $sp ) {
				if ( $spnum == 0 ) continue;

				$ssparts = explode("=", $sp);
				if ( trim($ssparts[0]) == "CN" ) {
					$se["organizer_name"] = trim($ssparts[1]);
					break;
				}
			}

			$mailto_found = false;
			foreach ( $parts as $pnum => $p ) {
				if ( $pnum == 0 ) continue;

				if ( $mailto_found ) {
					$se["organizer_email"] = $p;
					$mailto_found = false;
				} else {
					if ( trim($p) == "MAILTO" ) {
						$mailto_found = true;
						continue;
					}
				}
			}
		}

		// line contains location information
		if ( trim($sparts[0]) == "LOCATION" ) {
			$se["location"] = $parts[1];
		}

		// line contains description information
		if ( trim($sparts[0]) == "DESCRIPTION" ) {
			$se["description"] = $parts[1];
			$se["description"] = str_replace("\\n", "\n", $se["description"]);
			$se["description"] = str_replace("\\,", ",", $se["description"]);
		}

		// line contains the start time
		if ( trim($sparts[0]) == "DTSTART" ) {
			$se["dtstart"] = $parts[1];
		}

		// line contains the end time
		if ( trim($sparts[0]) == "DTEND" ) {
			$se["dtend"] = $parts[1];
		}

		break;

	case 4: // VALARM
		if ( trim($parts[0]) == "END" && trim($parts[1]) == "VALARM" ) {
			$status = 3;
			break;
		}

		break;

	case 0: // Seeking beginning of vcalendar
	default:
		if ( trim($parts[0]) == "BEGIN" && trim($parts[1]) == "VCALENDAR" ) $status = 1;

		break;
	}

	if ( $finished ) break;
}

if ( ! isset($se["description"]) ) $se["description"] = "";

$de_msg = trim( str_replace("\\N", "\n", $se["description"]) ) . "\n";

if ( isset($se["location"]) ) $location = "\nLocation: $se[location]";
if ( isset($se["organizer_name"]) ) {
	if ( isset($se["organizer_email"]) )
		$organizer = "\nOrganizer: $se[organizer_name] ($se[organizer_email])";
	else
		$organizer = "\nOrganizer: $se[organizer_name]";
}

if ( ! isset($se["dtstart"]) ) {
	$time_s = time();
} else {
	$x = explode("T", $se["dtstart"]);

	$time_s = mktime(substr($x[1], 0, 2), substr($x[1], 2, 2), substr($x[1], 4, 4),
		substr($x[0], 4, 2), substr($x[0], 6, 2), substr($x[0], 0, 4) );
}

if ( ! isset($se["dtend"]) ) {
	$time_e = $time_s + 3600;
} else {
	$x = explode("T", $se["dtend"]);

	$time_e = mktime(substr($x[1], 0, 2), substr($x[1], 2, 2), substr($x[1], 4, 4),
		substr($x[0], 4, 2), substr($x[0], 6, 2), substr($x[0], 0, 4) );
}

// if the end date is on a following day, change it's hour to 22:00, and day to the same
if ( strtotime(date("Y-m-d", $time_e)) > strtotime(date("Y-m-d", $time_s)) ) {
	$time_e = strtotime(date("d F Y", $time_s) . " 22:00:00");
}

$organizer = str_replace("\"", "", $organizer);
$location = str_replace("\"", "", $location);
$de_msg = str_replace("\"", "", $de_msg);

$time_s = date("Y-m-d H:i:s", $time_s);
$time_e = date("Y-m-d H:i:s", $time_e);

db_conn("cubit");
$sql = "INSERT INTO diary_entries (username, time_start, time_end, time_entireday, title, location, homepage,
		description, type, repetitions, rep_date, rep_forever, category_id, notify)
	VALUES('".USER_NAME."', '$time_s', '$time_e', 0, '$organizer', '$location', '', '$de_msg', '0', 'N',
		CURRENT_DATE, 0, 2, 0)";
$rslt = db_exec($sql) or errDie("Error creating diary entry.");

$OUTPUT = "
<h3>Calender Entry</h3>
Request Accepted.";

require("../template.php");

?>

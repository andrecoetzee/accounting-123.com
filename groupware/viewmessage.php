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
require_lib("ajax");
require_lib("mail.msg");

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

if ( isset($_GET["msg_id"]) ) {
	$msg_id = $_GET["msg_id"];
} else {
	$msg_id = 0;

	// no msg id has been specified, get the newest message in the current folder, if any
	if ( isset($_GET["fid"]) ) {
		$rslt = db_exec("SELECT message_id FROM mail_messages
			WHERE folder_id='$_GET[fid]' ORDER BY date DESC");

		if ( pg_num_rows($rslt) > 0 ) {
			$msg_id = pg_fetch_result($rslt, 0, 0); // fetch the first msg :>
		}
	}
}

$OUTPUT = "<link rel='stylesheet' href='stylesheet.css' type='text/css'>
";

// if msg_id is zero by here, there is no possible message to display with the current settings, output message and exit
if ( $msg_id == 0 ) {
	$OUTPUT .= "<center><p>
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<td class='odd'><li>No message to display.</li></td>
		</tr>
	</table>
	</p>
	<center>";
	print $OUTPUT;
	exit();
}

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
// $sql = "
// SELECT folder_id FROM mail_folders
// 	WHERE ( username = '".USER_NAME."' OR \"public\" = '1' ) AND folder_id=$msg_data[folder_id]
// UNION
// SELECT folder_id FROM mail_priv_folders WHERE priv_owner='".USER_NAME."' AND folder_id = $msg_data[folder_id]
// UNION
// SELECT mail_priv_accounts.account_id FROM mail_priv_accounts,mail_folders
// 	WHERE mail_folders.account_id = mail_priv_accounts.account_id";
// $rslt = db_exec($sql);
//
// if ( pg_num_rows($rslt) <= 0 ) {
// 	exit("You dont have sufficient privileges to read this message.");
// }

// read the message body now that we know we are safe
$rslt = db_exec("SELECT name,data FROM mail_msgbodies,mail_datatypes
	 WHERE msgbody_id=$msg_data[msgbody_id] AND mail_datatypes.type_id = mail_msgbodies.type_id");
if ( pg_num_rows($rslt) <= 0 ) {
	exit("Message body not found. Please contact Cubit.");
}

$msgbody = pg_fetch_array($rslt);

if ( $msg_data["attachments"] != 0 ) {
	$attachment_link = "<a target=_blank href='attachment.php?msg_id=$msg_id'>
		<img border=0 height=20 width=20 src='icon_attachment.gif'></a>";
} else {
	$attachment_link = "";
}

// decode
$msgbody["data"] = base64_decode($msgbody["data"]);

// process
$msg = & new clsMailMsg;
$msg->processMessage($msgbody["data"]);
$attachments = "";

// if it is a multipart message, create attachment list for all attachments, and fill the body with the rest
if ( $msg->maintype == "multipart" ) {
	if ( ! is_array($msg->parts) ) {
		$msgbody["data"] = implode("", $msg->body);
	} else {
		// ok start splitting body from attachment
		$pmsg = & new clsMailMsg;
		$msgbody["data"] = "";

		$i = 0;
		foreach ( $msg->parts as $pnum => $partdata ) {
			$partdata = implode ("\n", $partdata);
			$pmsg->processMessage($partdata);

			if ( $filename = $pmsg->getAttachmentFilename() ) {
				if ( $pmsg->maintype == "text" && $pmsg->subtype == "calendar" ) { // REAL CAL
				//if ( $pmsg->maintype == "application" && $pmsg->subtype == "octet-stream" ) {
					$attachments[] = '<a class=maildef
						href="JavaScript: popupSized(\'vcal.php?msg_id='.$msg_id.'&filename='.base64_encode($filename).'\', \'mailviewer\', 250, 250, \'\');">
						'.$filename.'</a> (vCalendar)';
				} else {
					$attachments[] = "<a target='_blank' class='maildef'
						href='getattachment.php?msg_id=$msg_id&filename=$filename'>$filename</a>";
				}
			} else {
				$pmsg_data = @implode("\n",$pmsg->body);

				// decode the message if shoudl
				if ( isset($pmsg->headers["Content-Transfer-Encoding"]) ) {
					// base64 encoding
					if ( strtolower($pmsg->headers["Content-Transfer-Encoding"]) == "base64" ) {
						$pmsg_data = base64_decode($pmsg_data);
					}
				}

				if ((count($msg->parts) > 0 && $pnum > 0) || count($msg->parts) == 0) {
					$msgbody["data"] .= "$pmsg_data";
				}
			}
		}
	}
} else {
	if ( is_array($msg->body) )
		$msgbody["data"] = implode("\n", $msg->body);
	else
		$msgbody["data"] = $msg->body;
}

// ok, now let's elliminate all html character interpretation, as it may cause security breach, this
// is only temporarily until the better method is finished coding2
//$msgbody["data"] = htmlspecialchars($msgbody["data"]);
// convert all new lines to breaks
//$msgbody["data"] = nl2br($msgbody["data"]);

// if there were attachments, create the listing
if ( is_array($attachments) ) {
	$attachments = implode("<br>", $attachments);
}

$showdoc_html_arr = explode("\n", $msgbody["data"]);
$showdoc_html = "''\n";
foreach($showdoc_html_arr as $value) {
	$value = str_replace("\r", "", $value);
	$value = str_replace("'","\\'", $value);
	if ( $msg->maintype == 'text' && $msg->subtype == 'plain' )
		$showdoc_html .= "+ '$value<br>'\n";
	else
		$showdoc_html .= "+ '$value'\n";
}

// start the output
$OUTPUT .= "
$JS_AJAX
<div id='mail_message'>
<script>
	function Init() {
		//editArea.document.designMode = 'On';
		getObj('editArea').contentDocument.body.innerHTML = $showdoc_html;
		getObj('editArea').height = getObj('editCell').height
	}

	window.onload = Init;
</script>
<table width=100% height=100% cellspacing=0 cellpadding=0>
<tr>
	<th align=center valign=middle colspan=2>
		&nbsp; &nbsp; $msg_data[subject]
	</th>
</tr>
<tr><th height=40 align=left valign=top>
	<table width='100%' height='100%'>
	<tr>
		<td width='50%' class='msgheaders'>
			From: <a class='maildef' href='$mail_sender$msg_data[add_from]' target=rightframe>$msg_data[add_from]</a>
		</td>
		<td width='50%' class='msgheaders'>
			Cc: <a class='maildef' href='$mail_sender$msg_data[add_cc]' target=rightframe>$msg_data[add_cc]</a>
		</td>
	</tr>
	<tr>
		<td width='50%' class='msgheaders'>
			To: <a class='maildef' href='$mail_sender$msg_data[add_to]' target=rightframe>$msg_data[add_to]</a>
		</td>
		<td width='50%' class='msgheaders'>
			Bcc: <a class='maildef' href='$mail_sender$msg_data[add_bcc]' target=rightframe>$msg_data[add_bcc]</a>
		</td>
	</tr>
	</table>
</th>
<th height=40 width=0% align=right valign=middle nowrap>
	$attachments
</th></tr>
<tr>
	<td align=left valign=top name=editCell id=editCell colspan=2 height=100%>
		<iframe class='message_frameset' name='editArea' id='editArea' style='width: 100%; height: 100%; background: #FFFFFF;'></iframe>
	</td>
</tr>
</table>
</div>
";

print $OUTPUT;

// create the frames
//require ("../template.php");

?>

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

// validate the key
if ( isset($_GET["key"]) ) {
	switch ( $_GET["key"] ) {
		case "view":
		case "msgalter":
			$key = $_GET["key"];
			break;
		default:
			$key = "view";
	}
} else {
	$key = "view";
}

// view the selected folder
if ( isset($_GET["fid"]) ) {
	$fid = $_GET["fid"];
} else {
	// now folder was selected, let's show the inbox folder of the first account on the list, if any
	$rslt = db_exec("SELECT fid_inbox FROM mail_accounts,mail_account_settings
		WHERE mail_accounts.account_id=mail_account_settings.account_id
			AND ( username='".USER_NAME."' OR \"public\" = '1' )");

	if ( pg_num_rows($rslt) > 0 ) {
		$fid = pg_fetch_result($rslt, 0, 0);
	} else {
		$fid = 0;
	}
}

// delete / move selected messages if we should
if ( $key == "msgalter" ) {
	if ( isset($_GET["msgselect"]) && is_array($_GET["msgselect"])) {
		// go through each selected msg
		foreach ( $_GET["msgselect"] as $msel_num => $msg_id ) {
			// check if msg may be deleted (owner of folder, account, or privileged, NOT public folders)
			$sql = "SELECT msgbody_id FROM mail_messages, mail_folders
					WHERE message_id='$msg_id' AND mail_folders.folder_id=mail_messages.folder_id
						AND mail_folders.username='".USER_NAME."'

				UNION
				SELECT msgbody_id FROM mail_messages,mail_accounts
					WHERE message_id='$msg_id' AND mail_accounts.account_id=mail_messages.account_id
						AND mail_accounts.username='".USER_NAME."'

				UNION
				SELECT msgbody_id FROM mail_messages,mail_priv_folders
					WHERE mail_priv_folders.folder_id=mail_messages.folder_id
						AND priv_owner='".USER_NAME."'

				UNION
				SELECT msgbody_id FROM mail_messages,mail_priv_accounts
					WHERE mail_priv_accounts.account_id=mail_messages.account_id
						AND priv_owner='".USER_NAME."'";

			$rslt = db_exec($sql);

                        // if we are allowed delete them
			if ( pg_num_rows($rslt) > 0 ) {
				// get the result from previous query
				$msgbody_id = pg_fetch_result($rslt, 0, 0);

				db_conn("cubit");
                                pglib_transaction("BEGIN");

				if ( isset($_GET["btn_delete"]) ) {
					db_exec("DELETE FROM mail_messages WHERE message_id='$msg_id'");
					db_exec("DELETE FROM mail_msgbodies WHERE msgbody_id='$msgbody_id'");
				} else if ( isset($_GET["btn_move"]) ) {
					$_GET["move_folderid"] += 0;
					db_exec("UPDATE mail_messages SET folder_id='$_GET[move_folderid]'
						WHERE message_id='$msg_id'");
				}

				// commit and on fail report
				if ( pglib_transaction("COMMIT") == 0 ) {
					$OUTPUT .= "Error deleting/moving message #$msg_id<br>";
				}
			} else {
				$OUTPUT .= "You are not allowed to delete/move message #$msg_id<br>";
			}
		}
	}
}

// first see if current user is allowed to view this folder
// -> mail_folders.username, mail_accounts.username, mail_priv_accounts/folders.priv_owner
$sql = "
SELECT mail_folders.account_id FROM mail_folders
	WHERE folder_id='$fid' AND ( mail_folders.username='".USER_NAME."' OR mail_folders.\"public\" = '1' )

UNION
SELECT mail_folders.account_id FROM mail_accounts,mail_folders
	WHERE folder_id='$fid'
		AND mail_accounts.account_id=mail_folders.account_id
		AND ( mail_accounts.username='".USER_NAME."' OR mail_accounts.\"public\" = '1' )

UNION
SELECT fp_id FROM mail_priv_folders WHERE folder_id='$fid' AND priv_owner='".USER_NAME."'

UNION
SELECT ap_id FROM mail_priv_accounts WHERE account_id='$fid' AND priv_owner='".USER_NAME."'";

$rslt = db_exec($sql);

if ( $user_admin == 0 && pg_num_rows($rslt) == 0 ) {
	exit ("No such folder, or you have insufficient privileges to view this folder $fid.");
}

// see if any specific (valid) order was specified, else load default
if ( isset($_GET["orderby"]) ) {
	// check if ASC or DESC was specified, else use DESC
	if ( isset($_GET["sortorder"])
		&& ($_GET["sortorder"] == "ASC" || $_GET["sortorder"] == "DESC") ) {
		$sortorder = $_GET["sortorder"];
	} else {
		$sortorder = "DESC";
	}

	// set the next load sort order (if the same column was clicked again)
	$sortorder == "ASC" ? $next_sortorder = "DESC" : $next_sortorder = "ASC";

	// check which column was selected, create $orderby var, and the column whose sortorder should be set next
	switch ($_GET["orderby"]) {
		case "subject":
			$so_subject = "sortorder=$next_sortorder";
			$so_sender = "";
			$so_date = "";
			$orderby = "subject $sortorder, date, add_from";
			break;
		case "sender":
			$so_sender = "sortorder=$next_sortorder";
			$so_subject = "";
			$so_date = "";
			$orderby = "add_from $sortorder, date, subject";
			break;
		case "date":
		default: // if invalid one was specified, we'll default to this one
			$so_date = "sortorder=$next_sortorder";
			$so_subject = "";
			$so_sender = "";
			$orderby = "date $sortorder, subject, add_from";
			break;
	}
} else {
	$orderby = "date DESC, subject, add_from";
	$so_subject = "";
	$so_sender = "";
	$so_date = "";
}

// create the headers
$OUTPUT = "<style> body { margin: 0px; background: #d6d6d6; } </style>
<table width=100% height=100%>
<tr><td valign=top>
	<table width=100%>
	<tr>
		<td width=2%>&nbsp;</td>
		<td width=2%>&nbsp;</td>
		<th width=50%><a class='maildef' href='msglist.php?fid=$fid&orderby=subject&$so_subject'>
			Subject</a></th>
		<th width=25%><a class='maildef' href='msglist.php?fid=$fid&orderby=sender&$so_sender'>
			Sender</a></th>
		<th width=14%><a class='maildef' href='msglist.php?fid=$fid&orderby=date&$so_date'>
			Date/Time</a></th>
		<th width=7% colspan=2>Options</th>
	</tr>";

// create the message entries, if any
$rslt = db_exec("SELECT message_id,subject,add_from,priority,attachments,flag,
			EXTRACT(day from date) as day, EXTRACT(month from date) as month, EXTRACT(year from date) as year,
			EXTRACT(hour from date) as hour, EXTRACT(minute from date) as minute
		FROM mail_messages WHERE folder_id='$fid'
		ORDER BY $orderby");

// create each message's entry, if any
if ( pg_num_rows($rslt) <= 0 ) {
	$OUTPUT .= "<tr>
			<td>&nbsp;</td>
			<td colspan=5>No Messages</td>
		</tr>";
} else {
	while ( $row = pg_fetch_array($rslt) ) {
		$datesent = date("D, d M Y, H:i",
			mktime( $row["hour"], $row["minute"], 0, $row["month"], $row["day"], $row["year"]) );

		if ( $row["attachments"] != 0 ) {
			$attachment = "<img src='icon_attachment.gif'>";
		} else {
			$attachment = "&nbsp;";
		}

		if ( empty($row["subject"]) ) {
			$row["subject"] = "[no subject]";
		}

		if ( empty($row["add_from"]) ) {
			$sender_link = "[no sender]";
		} else {
			$sender_link = "<a class='maildef' href='$mail_sender$row[add_from]' target=rightframe nowrap>$row[add_from]</a>";
		}


		$OUTPUT .= "
			<form method=GET action=msglist.php>
			<tr>
				<td align=center><input type=checkbox name='msgselect[]' value='$row[message_id]'></td>
				<td align=center>$attachment</td>
				<td><a class='maildef' href='viewmessage.php?msg_id=$row[message_id]'
					target='viewmessage' nowrap>$row[subject]</a></td>
				<td>$sender_link</td>
				<td align=center nowrap>$datesent</td>
				<td><a href='newmessage.php?msg_id=$row[message_id]&subject=Fw: $row[subject]' target=rightframe>Forward</a></td>
				<td><a href='newmessage.php?msg_id=$row[message_id]&subject=Re: $row[subject]&send_to=$row[add_from]' target=rightframe>Reply</a></td>
			</tr>
		";
	}
}

// create the msg options and finish the output
$OUTPUT .= "
	</table>
</td></tr>
<tr><td valign=bottom align=left>
		<input type=hidden name='fid' value='$fid'>
		<input type=hidden name='key' value='msgalter'>
		<input type=submit name=btn_delete value='Delete Selected'>
		 ---
		<select name=move_folderid>";

// create the folder tree
// go through each account and start to generate the tree
require ("object_foldertree.php");
require ("jscripts.php");
$tree = & new clsFolderTree;

db_conn("cubit");
$sql = "SELECT mail_accounts.account_id,mail_accounts.account_name
	FROM mail_folders, mail_accounts
	WHERE mail_folders.account_id=mail_accounts.account_id AND folder_id='$fid'";
$rslt = db_exec($sql) or errDie("Error fetching folder list for message move action.");

$account_count = pg_num_rows($rslt);
$account_num = 1;
if ( $account_count > 0 ) {
	while ( $row = pg_fetch_array($rslt) ) {
		$tree->reset_tree( "account", $row["account_id"] , $row["account_name"],
			$account_num++, $account_count );
		$tree->generate_tree();
	}
	foreach ( $tree->nodes as $key => $value ) {
		$node = explode("|", $value);

		if ( $node[0] == "F" ) {
			$OUTPUT .= "<option value='$node[1]'>$node[2]</option>";
		}
	}
}

$OUTPUT .= "	</select>
		<input type=submit name=btn_move value='Move Selected to Folder'>
	</form>
</td></tr>
</table>";

require ("../template.php");

?>

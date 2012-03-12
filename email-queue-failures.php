<?

	require ("settings.php");
	require_lib("ext");
	require_lib("mail.smtp");

	#read get vars into post vars
	foreach ($_GET AS $each => $own){
		$_POST[$each] = $own;
	}

	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "confirm":
				$OUTPUT = send_email_groups ($_POST);
				break;
			case "remove":
				$OUTPUT = remove_group ($_POST);
				break;
			case "confirm_remove":
				$OUTPUT = confirm_remove ($_POST);
				break;
			case "viewgroup":
				$OUTPUT = view_fails ($_POST);
				break;
			case "removefails":
				$OUTPUT = remove_fails ($_POST);
				break;
			default:
				$OUTPUT = show_email_groups ();
		}
	}else {
		if(isset($_GET["send"])){
			$OUTPUT = send_email_groups ($_GET);
		}else {
			$OUTPUT = show_email_groups ();
		}
	}

	$OUTPUT .= "<p>".
				mkQuickLinks(
					ql("email-queue-manage.php","Send Emails In Queue"),
					ql("email-queue-failures.php","Resend Failed Emails In Queue"),
					ql("email-groups.php", "Send Email To Group"),
					ql("email-group-new.php", "Add Email Group"),
					ql("email-group-view.php", "View Email Groups")
				);

	require ("template.php");



function show_email_groups ($err = "")
{

	db_connect ();

	#first set the second fail marker to active
	$upd_flag = "UPDATE email_queue SET status2 = 'active' WHERE status = 'failed'";
	$run_flag = db_exec($upd_flag) or errDie ("Unable to update email status flag.");

	$get_groups = "SELECT distinct (groupname) FROM email_queue";
	$run_groups = db_exec($get_groups) or errDie ("Unable to get email group information.");
	if(pg_numrows($run_groups) < 1){
		$listing = "
			<tr>
				<td><li class='err'>No Email Queues Found.</li></td>
			</tr>";
	}else {
		$listing = "
			<tr>
				<th>Group Name</th>
				<th>Subject</th>
				<th>Date Added</th>
				<th>Status</th>
				<th>Failed</th>
				<th>Select Group To Email</th>
				<th>View Failures</th>
				<th>Remove Group</th>
			</tr>";

		$counter = 0;
		while ($garr = pg_fetch_array ($run_groups)){
			#get some more info
			$get_entry = "SELECT * FROM email_queue WHERE groupname = '$garr[groupname]' AND status = 'failed' LIMIT 1";
			$run_entry = db_exec($get_entry) or errDie ("Unable to get email information.");
			if(pg_numrows($run_entry) < 1){
				#no failures for this group ...
				$listing .= "";
			}else {
				$counter++;
				#at least 1 failure found ...
				$garr2 = pg_fetch_array($run_entry);
				$groupname = $garr2['groupname'];
				$subject = $garr2['subject'];

				#check if any have been sent
				$get_entry2 = "SELECT * FROM email_queue WHERE groupname = '$garr[groupname]' AND status != 'sending'";
				$run_entry2 = db_exec($get_entry2) or errDie ("Unable to get email information.");
				if(pg_numrows($run_entry2) < 1){
					$get_all_count = db_exec("SELECT count(id) FROM email_queue WHERE groupname = '$garr[groupname]'") or errDie ("Unable to get amount of emails");
					$failed = 0;
					$status = "Unsent";
					$showprocess = "<input type='checkbox' name='sendgroups[]' value='$groupname'>";
					$showremove = "<a href='email-queue-manage.php?key=remove&id=$garr2[id]'>Remove</a>";
				}else {
					$get_entry3 = "SELECT * FROM email_queue WHERE groupname = '$garr[groupname]' AND status != 'sent'";
					$run_entry3 = db_exec($get_entry3) or errDie ("Unable to get email information.");
					if(pg_numrows($run_entry3) < 1){
						$get_all_count = db_exec("SELECT count(id) FROM email_queue WHERE groupname = '$garr[groupname]'") or errDie ("Unable to get amount of emails");

						$status = "All Emails Sent.";

						$failed = 0;
						$showprocess = "";
						$showremove = "<a href='email-queue-manage.php?key=remove&id=$garr2[id]'>Remove</a>";
					}else {
						$get_all_count = db_exec("SELECT count(id) FROM email_queue WHERE groupname = '$garr[groupname]'") or errDie ("Unable to get amount of emails");
						$total = pg_fetch_result($get_all_count,0,0);

						$status = "Some Emails Sent";

						$get_all_count = db_exec("SELECT count(id) FROM email_queue WHERE groupname = '$garr[groupname]' AND status = 'failed'") or errDie ("Unable to get amount of emails");
						$failed = pg_fetch_result($get_all_count,0,0);

						$showprocess = "<input type='checkbox' name='sendgroups[]' value='$groupname'>";
						$showremove = "<a href='email-queue-manage.php?key=remove&id=$garr2[id]'>Remove</a>";
					}
				}

				$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$groupname</td>
						<td>$subject</td>
						<td>$garr2[date_added]</td>
						<td>$status</td>
						<td>$failed</td>
						<td valign='center'>$showprocess</td>
						<td align='center'><a href='email-queue-failures.php?key=viewgroup&group=$garr[groupname]'>View Failures</a></td>
						<td align='center'>$showremove</td>
					</tr>";

			}

		}

		if ($counter == 0){
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='8'>No Failures Found In Any Email Groups.</td>
				</tr>";
		}

		$listing .= "
			<tr>
				<td colspan='5' align='right'><input type='submit' value='Send'></td>
			</tr>";

	}

	$display = "
		<h2>Email Groups With Send Failures</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			$listing
		</form>
		</table>";
	return $display;


}


function view_fails ($_POST, $err="")
{

	extract ($_POST);

	db_connect ();

	#get fails 
	$get_fails = "SELECT * FROM email_queue WHERE groupname = '$group' AND status = 'failed'";
	$run_fails = db_exec($get_fails) or errDie ("Unable to get failed email information");
	if (pg_numrows($run_fails) < 1){
		#no failures found ???
		$listing = "";
	}else {
		$listing = "";
		while ($farr = pg_fetch_array ($run_fails)){
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='text' name='emailaddress_name[$farr[id]]' value='$farr[emailaddress]'></td>
					<td>$farr[date_sent]</td>
					<td>$farr[failed_reason]</td>
					<td><input type='checkbox' name='remove_fails_ids[$farr[id]]' value='yes'></td>
				</tr>";
		}
	}

	$display = "
		<h2>Failed Email Addresses in group: $group</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='removefails'>
			<input type='hidden' name='group' value='$group'>
			$err
			<tr>
				<th colspan='3' align='left'><input type='checkbox' name='super_update' value='yes'> Also apply updates/removes to original email group</th>
			</tr>
			".TBL_BR."
			<tr>
				<th>Email Address</th>
				<th>Attempted Send Date</th>
				<th>Failure Reason</th>
				<th>Remove Email Address</th>
			</tr>
			$listing
			<tr>
				<td colspan='4' align='right'><input type='submit' value='Update'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function remove_fails ($_POST)
{

	extract ($_POST);

	if (!isset($remove_fails_ids) OR !is_array ($remove_fails_ids))
		$remove_fails_ids = array ();
//		return view_fails ($_POST, "<li style='color:red'>Please select at least 1 email address to remove.</li>");

	db_connect ();

	#remove selected email addresses
	foreach ($remove_fails_ids AS $key => $value){

		if (isset($super_update) AND $super_update == "yes"){
			$get_add = "SELECT emailaddress FROM email_queue WHERE id = '$key' LIMIT 1";
			$run_add = db_exec($get_add) or errDie ("Unable to get email address information.");
			if (pg_numrows($run_add) > 0){
				#found
				$orig_address = pg_fetch_result ($run_add,0,0);

				$rem_sql = "DELETE FROM email_groups WHERE emailaddress = '$orig_address'";
				$run_rem = db_exec($rem_sql) or errDie ("Unable to remove failed email address.");

			}
		}

		$rem_sql = "DELETE FROM email_queue WHERE id = '$key'";
		$run_rem = db_exec($rem_sql) or errDie ("Unable to remove failed email address.");
	}

	#update addresses in list
	foreach ($emailaddress_name AS $key => $value){

		#if super update, we need the orig address
		if (isset($super_update) AND $super_update == "yes"){
			$get_add = "SELECT emailaddress FROM email_queue WHERE id = '$key' LIMIT 1";
			$run_add = db_exec($get_add) or errDie ("Unable to get email address information.");
			if (pg_numrows($run_add) > 0){
				#found
				$orig_address = pg_fetch_result ($run_add,0,0);
				
				#update the original lists ..
				$upd_orig_sql = "UPDATE email_groups SET emailaddress = '$value' WHERE emailaddress = '$orig_address'";
				$run_orig_sql = db_exec($upd_orig_sql) or errDie ("Unable to update oiginal email addresses.");
			}
		}

		#now update just the queue ...
		$upd_sql = "UPDATE email_queue SET emailaddress = '$value' WHERE id = '$key'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update email queue.");

	}
		
//	return show_email_groups("<li class='err'>Selected Failed Email Addresses Have Been Updated.</li>");
	$_POST["key"] = "viewgroup";
	$_POST["group"] = $group;
	return view_fails($_POST,"<li class='err'>Updated.</li><br>");

}





















function send_email_groups ($_POST)
{

	extract ($_POST);

	if(!isset($sendgroups) OR !is_array ($sendgroups)){
		return show_email_groups("<li class='err'>Please Select At Least 1 Email Batch To Send.</li><br>");
	}

	db_connect ();

	$sendgroup = "";
	$listing = "";
	$groupcounter = 0;
	$ran = TRUE;

	foreach ($sendgroups AS $groupname){
		$ran2 = TRUE;

		$sendgroup .= "&sendgroups[]=$groupname";

		$listing .= "
			<tr>
				<td><h3>$groupname</h3></td>
			</tr>";

		#determine how many have been sent
		$get_sent = "SELECT count(id) FROM email_queue WHERE groupname = '$groupname' AND status = 'sent'";
		$run_sent = db_exec($get_sent) or errDie ("Unable to get sent email information.");
		if (pg_numrows($run_sent) < 1){
			$sent_items = 0;
		}else {
			$sent_items = pg_fetch_result ($run_sent,0,0);
		}

		$get_sent = "SELECT count(id) FROM email_queue WHERE groupname = '$groupname' AND status = 'failed' AND status2 = 'active'";
		$run_sent = db_exec($get_sent) or errDie ("Unable to get sent email information.");
		if (pg_numrows($run_sent) < 1){
			$unsent_items = 0;
		}else {
			$unsent_items = pg_fetch_result ($run_sent,0,0);
		}

		$listing .= "
			<tr>
				<th>$sent_items Emails Have Been Sent</th>
				<th colspan='3'>$unsent_items Emails Remain</th>
			</tr>";

		#get list of 5 queue items to display
		$get_list = "SELECT * FROM email_queue WHERE groupname = '$groupname' AND status = 'failed' AND status2 = 'active' OFFSET $groupcounter LIMIT 10";
		$run_list = db_exec($get_list) or errDie ("Unable to get list of emails to be sent.");
		if(pg_numrows($run_list) < 1){
//			$listing .= "
//				<tr bgcolor='".bgcolorg()."'>
//					<td colspan='2'>All Emails Have Been Sent.</td>
//				</tr>
//				".TBL_BR;
//			return show_email_groups("<li class='err'>Requested Email(s) Have Been Sent.</li><br>");
			print "
					<script>
						document.location='email-queue-manage.php';
					</script>";
		}else {

			$listing .= "
				<tr>
					<th>Email Address</th>
					<th>Subject</th>
					<th>Date Added</th>
					<th>Status</th>
				</tr>";
			while ($larr = pg_fetch_array ($run_list)){
				if ($ran && $ran2){
					$larr['status'] = "Sending";
					$ran2 = FALSE;
				}else {
					$larr['status'] = "Queued";
				}
				$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$larr[emailaddress]</td>
						<td>$larr[subject]</td>
						<td>$larr[date_added]</td>
						<td>$larr[status]</td>
					</tr>";
			}
		}

//		$count = 0;

		#only update 1 email message
		$get_emails = "SELECT * FROM email_queue WHERE groupname = '$groupname' AND status = 'failed' AND status2 = 'active' ORDER BY id LIMIT 1";
		$run_emails = db_exec($get_emails) or errDie ("Unable to get group information.");

		while ($larr = pg_fetch_array($run_emails)){

			if ($ran){
				$bodydata = $larr['message'];
				$subject = $larr['subject'];
	
				$es = qryEmailSettings();
	
				$body = $bodydata;

				#generate removal tail code
				$tail = "<br><br> 

If you would like to stop receiving these emails, please leave the following link intact, and reply to this email.
<a href='http://".$_SERVER['SERVER_ADDR']."/unsub-email.php?email=$larr[emailaddress]'>http://".$_SERVER['SERVER_ADDR']."/unsub-email.php?email=$larr[emailaddress]</a>";

				if ($larr['send_format'] != "html"){
					$tail = strip_tags($tail);
				}

				$body = $body . $tail;

				$send_cc = "";
				$send_bcc = "";
	
				$smtp_data['signature'] = $es['sig'];
				$smtp_data['smtp_from'] = getCSetting ("MARKET_MAIL_FROM"); //$es['fromname'];
				$smtp_data['smtp_reply'] = $es['reply'];
				$smtp_data['smtp_host'] = $es['smtp_host'];
				$smtp_data['smtp_auth'] = $es['smtp_auth'];
				$smtp_data['smtp_user'] = $es['smtp_user'];
				$smtp_data['smtp_pass'] = $es['smtp_pass'];
	
				// build msg body
				$body = "$body\n\n$smtp_data[signature]";

				// determine whether or not here is an attachment
				//$has_attachment = is_uploaded_file($attachment["tmp_name"]);
				if ($larr['attachment'] != 0){
					$has_attachment = TRUE;
				}else {
					$has_attachment = false;
				}

//				$has_attachment = false;
				// modify message and create content_type header depending on whether or not an attachment was posted
				if ( $has_attachment == false ) {
					$content_type = "text/$larr[send_format];charset=US-ASCII";
					$transfer_encoding = "8bit";
				} else { // has attachment

					$get_attach = "SELECT * FROM email_attachments WHERE id = '$larr[attachment]' LIMIT 1";
					$run_attach = db_exec($get_attach) or errDie ("Unable to get email attachment information.");
					if (pg_numrows($run_attach) < 1){
						return "Email attachment not found.";
					}

					$aarr = pg_fetch_array ($run_attach);

					$content_type = "multipart/mixed";
	
					// create the main body
					$body_text = "Content-Type: text/$larr[send_format]; charset=US-ASCII\n";
					$body_text .= "Content-Transfer-Encoding: base64\n";
					$body_text .= "\n" . chunk_split(base64_encode($body));
	
					// get the attachment data
					$attachment = Array();
//					$attachment["data"] = state($id,$fromdate,$todate,$type);
					$attachment["name"] = $aarr['attach_filename'];//"statement.pdf";
	
					// delete the temporary file
	
					$attachment["data"] = chunk_split($aarr["attach_data"]);//chunk_split(base64_encode($attachment["data"]));
	
					$attachment["headers"] = "Content-Type: $aarr[attach_mime]; name=\"$attachment[name]\"\n";
					$attachment["headers"] .= "Content-Transfer-Encoding: base64\n";
					$attachment["headers"] .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";
	
					$attachment["data"] = "$attachment[headers]\n$attachment[data]";
	
					// generate a unique boundary ( md5 of filename + ":=" + filesize )
					$boundary = md5($attachment["name"]) . "=:" . strlen($attachment["data"]);
					$content_type .= "; boundary=\"$boundary\"";
	
					// put together the body
					$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment[data]\n\n--$boundary--\n";
				}

				// build headers
				$headers = array();
				$headers[] = "From: ".getCSetting ("MARKET_MAIL_FROM");//$smtp_data[smtp_from]";
				$headers[] = "To: $larr[emailaddress]";
				$headers[] = "Reply-To: ".getCSetting ("MARKET_MAIL_FROM");//$smtp_data[smtp_reply]";
				$headers[] = "X-Mailer: Cubit Mail";
				$headers[] = "Return-Path: ".getCSetting ("MARKET_MAIL_FROM");//$smtp_data[smtp_reply]";
				$headers[] = "Content-Type: $content_type";
				$headers[] = "cc: $send_cc";
				$headers[] = "bcc: $send_bcc";
	
				// create the mime header if should
				if ( $has_attachment == TRUE ) {
					$headers[] = "MIME-Version: 1.0";
				}

				// create the header variable (it is done this way, to make management of headers easier, since there
				// may be no tabs and unnecesary whitespace in mail headers)
				//$headers[] = "\n"; // add another new line to finish the headers
				$headers = implode("\n", $headers);
	
				//return "done";
			       // send the message
				$sendmail = & new clsSMTPMail;
				$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"], $smtp_data["smtp_pass"],$larr['emailaddress'], $smtp_data["smtp_from"], "$subject", $body, $headers);

				if ($sendmail->bool_success){
					#email system reports success!

					#update this entry ..
					$upd_sql = "UPDATE email_queue SET status = 'sent' WHERE id = '$larr[id]'";
					$run_upd = db_exec($upd_sql) or errDie ("Unable to update email queue information.");

					$ran = FALSE;
				}else {
					#problem sending mail ...
					#if email system reports network problem, loop, else mark ...
					$upd_sql = "UPDATE email_queue SET status = 'failed', status2 = 'failed', failed_reason = '$OUTPUT' WHERE id = '$larr[id]'";
					$run_upd = db_exec($upd_sql) or errDie ("Unable to update email queue information.");

					$ran = FALSE;
				}



			}

//			$count++;

		}

//		print "group sent ...";
	}

	$display = "
					<script>
						window.setTimeout(' window.location=\"email-queue-failures.php?key=confirm$sendgroup\"; ',3000);
					</script>
					<h2>Email Management</h2>
					<table ".TMPL_tblDflts.">
						$listing
					</table>
				";
	return $display;

}



function remove_group ($_POST)
{

	extract ($_POST);

	if (!isset($id) OR strlen($id) < 1){
		return "Invalid Use Of Module.";
	}

	#get group info
	$get_group = "SELECT groupname FROM email_queue WHERE id = '$id' LIMIT 1";
	$run_group = db_exec($get_group) or errDie ("Unable to get email queue information.");
	if (pg_numrows($run_group) < 1){
		$groupname = "";
	}else {
		$groupname = pg_fetch_result ($run_group,0,0);
	}

	$display = "
					<h2>Confirm Removal Of This Group</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm_remove'>
						<input type='hidden' name='id' value='$id'>
						<input type='hidden' name='groupname' value='$groupname'>
						<tr>
							<th>Group name</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$groupname</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td><input type='submit' value='Remove'></td>
						</tr>
					</form>
					</table>
			";
	return $display;

}


function confirm_remove ($_POST)
{

	extract ($_POST);

	if (!isset($id) OR strlen($id) < 1){
		return "Invalid Use Of Module.";
	}
	if (!isset($groupname) OR strlen($groupname) < 1){
		return "Invalid Use Of Module.";
	}

	db_connect ();

	$get_att = "SELECT attachment FROM email_queue WHERE groupname = '$groupname' LIMIT 1";
	$run_att = db_exec($get_att) or errDie ("Unable to get email queue attachment information.");
	if (pg_numrows($run_att) > 0){

		$att = pg_fetch_result ($run_att,0,0);

		#remove the attachment
		$rem_att = "DELETE FROM email_attachments WHERE id = '$att'";
		$run_rem = db_exec($rem_att) or errDie ("Unable to remove attachment information.");

	}

	$rem_group = "DELETE FROM email_queue WHERE groupname = '$groupname'";
	$run_group = db_exec($rem_group) or errDie ("Unable to remove email group");


	return show_email_groups ();

}





?>
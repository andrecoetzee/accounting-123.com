<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings

require ("settings.php");
require_lib("validate");
require_lib("mail.smtp");

if(isset($_POST["key"])) {
	switch ( $_POST["key"] ) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "send":
			$OUTPUT = send($_POST);
			break;
		default:
			$OUTPUT = "Invalid use of script.";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT = "Invalid.";
}

require ("template.php");

// creates the form of the new message
function enter($_POST) {
	extract($_POST);

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

	$tokendata=pg_fetch_array($Ry);

	db_conn('cubit');

	$sql = "SELECT account_id,account_name,smtp_from
			 FROM mail_accounts WHERE ( username='".USER_NAME."' OR \"public\"='1' ) AND enable_smtp = '1'

		UNION
		SELECT mail_accounts.account_id,account_name,smtp_from
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' AND enable_smtp = '1'";

	$rslt = db_exec($sql);

	if ( pg_numrows($rslt) < 1 ) {
		return "You have no accounts from which you may send email.";
	}

	$select_accounts = "<select name='aid'>";
	while ( $row = pg_fetch_array($rslt) ) {
		$select_accounts .= "<option value='$row[account_id]'>$row[account_name] ($row[smtp_from])</option>";
	}
	$select_accounts .= "</select>";

	$OUTPUT = "<h3>Enter email details</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form method=POST action='".SELF."' enctype='multipart/form-data'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=tid value='$id'>
	<tr><th colspan=2 align=center>Email Details</th></tr>
	<tr class='bg-odd'><td width=25%>Account:</td><td width=75%>$select_accounts</td></tr>
	<tr class='bg-even'><td>To:</td><td><input type=text name=send_to value='$tokendata[email]'></td></tr>
	<tr class='bg-odd'><td>Cc:</td><td><input type=text name=send_cc value=''></td></tr>
	<tr class='bg-even'><td>Bcc:</td><td><input type=text name=send_bcc value=''></td></tr>
	<tr class='bg-odd'><td>Subject:</td><td><input type=text name=subject value=''></td></tr>
	<tr class='bg-even'><td>Attachment:</td><td><input type=file name=attachment></td></tr>
	<tr class='bg-odd'><td colspan=2><textarea rows=20 cols=80 name='body'></textarea></td></tr>
	<tr><td><input type=reset value='Clear'></td><td align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $OUTPUT;
}

function errors($_POST) {
	extract($_POST);

	$sql = "SELECT account_id,account_name,smtp_from
			 FROM mail_accounts WHERE ( username='".USER_NAME."' OR \"public\"='1' ) AND enable_smtp = '1'

		UNION
		SELECT mail_accounts.account_id,account_name,smtp_from
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' AND enable_smtp = '1'";

	$rslt = db_exec($sql);

	if ( pg_numrows($rslt) < 1 ) {
		return "You have no accounts from which you may send email.";
	}

	extract($_FILES);

	$select_accounts = "<select name='aid'>";
	while ( $row = pg_fetch_array($rslt) ) {
		$select_accounts .= "<option value='$row[account_id]'>$row[account_name] ($row[smtp_from])</option>";
	}
	$select_accounts .= "</select>";

	$OUTPUT = "<h3>Enter email details</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form method=POST action='".SELF."' enctype='multipart/form-data'>
	<input type=hidden name=key value=send>
	<input type=hidden name=tid value='$tid'>
	<tr><th colspan=2 align=center>Email Details</th></tr>
	<tr class='bg-odd'><td width=25%>Account:</td><td width=75%>$select_accounts</td></tr>
	<tr class='bg-even'><td>To:</td><td><input type=text name=send_to value='$send_to'></td></tr>
	<tr class='bg-odd'><td>Cc:</td><td><input type=text name=send_cc value='$send_cc'></td></tr>
	<tr class='bg-even'><td>Bcc:</td><td><input type=text name=send_bcc value='$send_bcc'></td></tr>
	<tr class='bg-odd'><td>Subject:</td><td><input type=text name=subject value='$subject'></td></tr>
	<tr class='bg-even'><td>Attachment:</td><td><input type=file name=attachment value='$attachment'></td></tr>
	<tr class='bg-odd'><td colspan=2><textarea rows=20 cols=80 name='body'>$body</textarea></td></tr>
	<tr><td><input type=reset value='Clear'></td><td align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $OUTPUT;
}

function confirm($_POST) {

	extract($_POST);
	extract($_FILES);

	$Sl = "SELECT * FROM mail_accounts WHERE (username='".USER_NAME."' OR \"public\"='1') AND enable_smtp = '1'
	AND account_id='$aid'";
	$Ry = db_exec($Sl) or errDie("Unable to get accounts from system.");

	if (pg_numrows($Ry) < 1) {
		return "You may not send mail from this account<br>";
	}

	$accdata=pg_fetch_array($Ry);

	$OUTPUT = "<h3>Enter email details</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form method=POST action='".SELF."' enctype='multipart/form-data'>
	<input type=hidden name=key value=send>
	<input type=hidden name=aid value='$aid'>
	<input type=hidden name=body value='$body'>
	<input type=hidden name=tid value='$tid'>
	<tr><th colspan=2 align=center>Email Details</th></tr>
	<tr class='bg-odd'><td width=25%>Account:</td><td width=75%>$accdata[smtp_from]</td></tr>
	<tr class='bg-even'><td>To:</td><td><input type=hidden name=send_to value='$send_to'>$send_to</td></tr>
	<tr class='bg-odd'><td>Cc:</td><td><input type=hidden name=send_cc value='$send_cc'>$send_cc</td></tr>
	<tr class='bg-even'><td>Bcc:</td><td><input type=hidden name=send_bcc value='$send_bcc'>$send_bcc</td></tr>
	<tr class='bg-odd'><td>Subject:</td><td><input type=hidden name=subject value='$subject'>$subject</td></tr>
	<tr class='bg-even'><td>Attachment:</td><td><input type=file name=attachment value='$attachment'></td></tr>
	<tr class='bg-odd'><td colspan=2><pre>$body</pre></td></tr>
	<tr><td><input type=reset value='Clear'></td><td align=right><input type=submit value='Send &raquo;'></td></tr>
	</form>
	</table>";

	return $OUTPUT;
}

// verifies the message and sends it, the store it in database under sent items
function send($_POST) {

	extract($_POST);
	extract($_FILES);

	$tid+=0;
	$OUTPUT = "";
	$v = & new validate;

	// check if account is valid
	if ( isset($_POST["aid"]) ) {
		// make sure aid is ONLY a number, (sql injection)
		if ( ! $v->isOk( $_POST["aid"], "num", 0, 9, "" ) )
			return "Invalid account number specified";

		// check if you may send mail from here

		$sql = "SELECT 1
			 FROM mail_accounts WHERE ( username='".USER_NAME."' OR \"public\"='1' )
			 	AND enable_smtp = '1' AND account_id='$aid'

		UNION
		SELECT 1
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id AND mail_accounts.account_id='$aid'
				AND priv_owner = '".USER_NAME."' AND enable_smtp = '1'";

		$rslt = db_exec($sql);

		if ( pg_num_rows($rslt) <= 0 )
			return "You may not send mail from this account<br>";
	} else {
		return "No account specified<br>";
	}



	$v->resetErrors();
	$v->isOK($subject, "string", 1, 255, "Invalid subject.");
	// $v->isOK($send_to, "email", 1, 255, "Invalid recipient.");
	if ( strlen($send_to) <= 0 ) $v->addError("", "Invalid recipient");
	// $v->isOK($send_cc, "email", 0, 255, "Invalid cc recipient.");
	// $v->isOK($send_bcc, "email", 0, 255, "Invalid bcc recipient.");
	if ( ! $v->isOK($body, "string", 1, 255, "Invalid text in body.") ) {
		$_GET["body"] = htmlspecialchars($body); // makes sure we dont get cross site scripting
	}

	// ok now print errors if any
	if ( $v->isError() ) {
		$errs = $v->getErrors();

		foreach ( $errs as $arr => $errval ) {
			$OUTPUT .= "$errval[msg]<br>";
		}

		$OUTPUT .= errors($_POST);

		return $OUTPUT;
	}

	$time=date("H:i:s");
	$date=date("Y-m-d");

	db_conn('crm');
	$Sl="INSERT INTO token_actions (token,action,donedate,donetime,doneby,donebyid)
	VALUES ('$tid','Sent Email','$date','$time','".USER_NAME."','".USER_ID."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert query action.");

	db_conn('cubit');
	// get the smtp data
	$rslt = db_exec("SELECT smtp_from, smtp_reply, signature, smtp_host, smtp_auth, smtp_user, smtp_pass
		FROM mail_accounts WHERE account_id=$aid");
	$smtp_data = pg_fetch_array($rslt);

	// build msg body
	$body = "$body\n\n$smtp_data[signature]";

	// determine whether or not here is an attachment
	$has_attachment = is_uploaded_file($attachment["tmp_name"]);

	// modify message and create content_type header depending on whether or not an attachment was posted
	if ( $has_attachment == FALSE ) {
		$content_type = "text/plain";
		$transfer_encoding = "8bit";
	} else { // has attachment
		$content_type = "multipart/mixed";

		// create the main body
		$body_text = "Content-Type: text/plain; charset=US-ASCII\n";
		$body_text .= "Content-Transfer-Encoding: base64\n";
		$body_text .= "\n" . chunk_split(base64_encode($body));

		// get the attachment data
		if ( ($fd = fopen($attachment["tmp_name"], "r")) == TRUE ) {
			$attachment_data = "";
			while ( ! feof($fd) ) {
				$attachment_data .= fgets( $fd, 4096 );
			}
			fclose($fd);

			// delete the temporary file
			unlink($attachment["tmp_name"]);

			$attachment_data = chunk_split(base64_encode($attachment_data));

			$attachment_headers = "Content-Type: $attachment[type]; name=\"$attachment[name]\"\n";
			$attachment_headers .= "Content-Transfer-Encoding: base64\n";
			$attachment_headers .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";

			$attachment_data = "$attachment_headers\n$attachment_data";
		} else { // error opening the attachment file
			$attachment_data = "";
		}

		// generate a unique boundary ( md5 of filename + ":=" + filesize )
		$boundary = md5($attachment["name"]) . "=:" . $attachment["size"];
		$content_type .= "; boundary=\"$boundary\"";

		// put together the body
		$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment_data\n\n--$boundary--\n
		";
	}

	// build headers
	$headers[] = "From: $smtp_data[smtp_from]";
	$headers[] = "Reply-To: $smtp_data[smtp_reply]";
	$headers[] = "X-Mailer: Cubit Mail";
	$headers[] = "Return-Path: $smtp_data[smtp_reply]";
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

        // send the message
	$sendmail = & new clsSMTPMail;
	$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"],
		$smtp_data["smtp_pass"],$send_to, $smtp_data["smtp_from"], $subject, $body, $headers);

	/*if ( mail($send_to, $subject, $body, $headers) == TRUE )
		$OUTPUT = "Successfully sent mail to $send_to.<br>";
	else
		$OUTPUT = "Error sending mail.<br>";*/

	return $OUTPUT;
}

?>

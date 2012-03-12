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

require ("settings.php");
require_lib("validate");
require_lib("mail.smtp");

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

// overwrite the GET VARS with POST VARS (so both can be access at any times)
if ( isset($_POST) ) {
	foreach ( $_POST as $arr => $arrval ) {
		$_GET[$arr] = $arrval;
	}
}

// make sure something is being done
if ( ! isset($_GET["key"]) ) $_GET["key"] = "create";

switch ( $_GET["key"] ) {
	case "send": // send the form
		$OUTPUT = sendMsg();
		break;

	case "create": // the new msg form
	default:
		$OUTPUT = writeMsg();
		break;
}

require ("template.php");

// creates the form of the new message
function writeMsg() {
	global $_GET;

	$OUTPUT = "";

	$es = qryEmailSettings();

	// restore the previous entries if any (on errors)
	extract($_GET);
	extract($_FILES);

	if (!isset($emails)) {
		header("Location: customers-email.php?err=Please select at least on customer to email to");
		exit;
	}

	$fields = array(
		"send_to" => implode(";", $emails),
		"send_bcc" => "",
		"send_cc" => "",
		"subject" => "",
		"attachment" => "",
		"body" => ""
	);

	foreach ($fields as $fname => $v) {
		if (!isset($$fname)) $$fname = $v;
	}

	$showdoc_html = "''";

	// start of the body
	$OUTPUT .= "<form method='post' action='".SELF."' name='editForm' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='send'>
			".(isset($msg_id)?"<input type='hidden' name='msg_id' value='$msg_id'>":"")."
		<table ".TMPL_tblDflts." width='70%'>";

	// the body
	$OUTPUT .= "
		<tr>
			<td width=25% bgcolor='".bgcolorg()."'>To:</td>
			<td width=75% bgcolor='".bgcolorg()."'><input type='text' name='send_to' size='50' value='$send_to'></td>
		</tr>
		<tr>
			<td width=25% bgcolor='".bgcolorg()."'>Subject:</td>
			<td width=75% bgcolor='".bgcolorg()."'><input type='text' name='subject' size='50' value='$subject'></td>
		</tr>
		<tr>
			<td width=25% bgcolor='".bgcolorg()."'>Attachment:</td>
			<td width=75% bgcolor='".bgcolorg()."'><input type='file' name='attachment' size='50' value='$attachment'></td>
		</tr>
		<tr>
			<td width=100% colspan=2>
				<script language='JavaScript'>

				function update() {
					document.editForm.bodydata.value = editArea.document.body.innerHTML;
					document.editForm.submit();
				}

				function Init() {
					editArea.document.designMode = 'On';
					editArea.document.body.innerHTML = $showdoc_html;
				}

				function controlSelOn(ctrl) {
					ctrl.style.borderColor = '#000000';
					ctrl.style.backgroundColor = '#B5BED6';
					ctrl.style.cursor = 'hand';
				}

				function controlSelOff(ctrl) {
					ctrl.style.borderColor = '#D6D3CE';
					ctrl.style.backgroundColor = '#D6D3CE';
				}

				function controlSelDown(ctrl) {
					ctrl.style.backgroundColor = '#8492B5';
				}

				function controlSelUp(ctrl) {
				ctrl.style.backgroundColor = '#B5BED6';
				}

				function doBold() {
					editArea.document.execCommand('bold', false, null);
				}

				function doItalic() {
					editArea.document.execCommand('italic', false, null);
				}

				function doUnderline() {
					editArea.document.execCommand('underline', false, null);
				}

				function doLeft() {
					editArea.document.execCommand('justifyleft', false, null);
				}

				function doCenter() {
					editArea.document.execCommand('justifycenter', false, null);
				}

				function doRight() {
					editArea.document.execCommand('justifyright', false, null);
				}

				function doOrdList() {
					editArea.document.execCommand('insertorderedlist', false, null);
				}

				function doBulList() {
					editArea.document.execCommand('insertunorderedlist', false, null);
				}

				function doRule() {
					editArea.document.execCommand('inserthorizontalrule', false, null);
				}

				function doSize(fSize) {
					if(fSize != '')
						editArea.document.execCommand('fontsize', false, fSize);
				}

				window.onload = Init;

				</script>

				<table id='tblCtrls' width='700px' height='30px' border='0' cellspacing='0' cellpadding='0' bgcolor='#D6D3CE'>
				<tr>
				<td class='tdClass'>
					<img alt='Bold' class='buttonClass' src='images/bold.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBold()'>

					<img alt='Italic' class='buttonClass' src='images/italic.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doItalic()'>
					<img alt='Underline' class='buttonClass' src='images/underline.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doUnderline()'>

					<img alt='Left' class='buttonClass' src='images/left.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doLeft()'>
					<img alt='Center' class='buttonClass' src='images/center.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doCenter()'>
					<img alt='Right' class='buttonClass' src='images/right.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRight()'>

					<img alt='Ordered List' class='buttonClass' src='images/ordlist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doOrdList()'>
					<img alt='Bulleted List' class='buttonClass' src='images/bullist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBulList()'>

					<img alt='Horizontal Rule' class='buttonClass' src='images/rule.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRule()'>
				</td>
				<td class='tdClass' align=right>
					<select name='selSize' onChange='doSize(this.options[this.selectedIndex].value)'>
					<option value=''>-- Font Size --</option>
					<option value='1'>Very Small</option>
					<option value='2'>Small</option>
					<option value='3'>Medium</option>
					<option value='4'>Large</option>
					<option value='5'>Larger</option>
					<option value='6'>Very Large</option>
					</select>
				</td>
				</tr>
				</table>

				<iframe name='editArea' id='editArea' style='width: 700px; height:405px; background: #FFFFFF;'></iframe>
				<input type=hidden name=bodydata value=''>
			</td>
		</tr>
		<tr>
			<td width=100% colspan=2>
				<input type=button onClick='update();' value='Send'> &nbsp; &nbsp;
				<input type=reset value='Clear'>
			</td>
		</tr>
		";

	// ends the body output
	$OUTPUT .= "</table>
	</form>";

	return $OUTPUT;
}

// verifies the message and sends it, the store it in database under sent items
function sendMsg() {
	global $_GET;

	$v = & new validate;

	$OUTPUT = "";

	// restore the variables
	extract($_GET);
	extract($_FILES);

	if ( ! isset($_GET["send_to"]) ) $send_to = "";
	if ( ! isset($_GET["send_bcc"]) ) $send_bcc = "";
	if ( ! isset($_GET["send_cc"]) ) $send_cc = "";
	if ( ! isset($_GET["subject"]) ) $subject = "";
	if ( ! isset($_FILES["attachment"]) ) $attachment = "";
	if ( ! isset($_GET["body"]) ) $body = "";

	$v->resetErrors();
	$v->isOK($subject, "string", 1, 255, "Invalid subject.");
	// $v->isOK($send_to, "email", 1, 255, "Invalid recipient.");
	if ( strlen($send_to) <= 0 ) $v->addError("", "Invalid recipient");
	// $v->isOK($send_cc, "email", 0, 255, "Invalid cc recipient.");
	// $v->isOK($send_bcc, "email", 0, 255, "Invalid bcc recipient.");
	//if ( ! $v->isOK($bodydata, "string", 1, 255, "Invalid text in body.") ) {
	//	$_GET["body"] = htmlspecialchars($body); // makes sure we dont get cross site scripting
	//}

	// ok now print errors if any
	if ( $v->isError() ) {
		$errs = $v->getErrors();

		foreach ( $errs as $arr => $errval ) {
			$OUTPUT .= "$errval[msg]<br>";
		}

		$OUTPUT .= writeMsg();

		return $OUTPUT;
	}

	$bodydata = "<html>$bodydata</html>";

	$smtp_data = qryEmailSettings();

	// build msg body
	$body = "$body\n\n$smtp_data[sig]";

	// determine whether or not here is an attachment
	$has_attachment = is_uploaded_file($attachment["tmp_name"]);

	// modify message and create content_type header depending on whether or not an attachment was posted
	if ( $has_attachment == FALSE ) {
		$msgtype = $content_type = "text/html";
		$transfer_encoding = "8bit";
	} else { // has attachment
		$msgtype = $content_type = "multipart/mixed";

		// create the main body
		$body_text = "Content-Type: text/html; charset=US-ASCII\n";
		$body_text .= "Content-Transfer-Encoding: base64\n";
		$body_text .= "\n" . chunk_split(base64_encode($bodydata));

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
		$bodydata = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment_data\n\n--$boundary--\n";
	}

	// generate the msg id
	$a = explode("@", $smtp_data["fromname"]);
	if (count($a) < 2) {
		$OUTPUT = "<li class='err'>Invalid from address. Click <a href='email-settings.php'>here</a> to change.</li>";
		require("template.php");
	}
	list($buf, $domain) = $a;

	// build headers
	$headers[] = "From: $smtp_data[fromname]";
	$headers[] = "Reply-To: $smtp_data[reply]";
	$headers[] = "X-Mailer: Cubit Mail";
	$headers[] = "Return-Path: $smtp_data[reply]";
	$headers[] = "Message-ID: <".date("YmdHi").".".md5($bodydata)."@$domain>";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-Type: $content_type; charset=UTF-8";
	$headers[] = "To: \"Cubit Clients\" <accounts@cubit.co.za>";

	// create the header variable (it is done this way, to make management of headers easier, since there
	// may be no tabs and unnecesary whitespace in mail headers)
	//$headers[] = "\n"; // add another new line to finish the headers
	$headers = implode("\n", $headers);

    // send the message
	$sendmail = & new clsSMTPMail;
	$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"],
		$smtp_data["smtp_pass"],$send_to, $smtp_data["fromname"], $subject, $bodydata, $headers);

	return $OUTPUT;
}

?>
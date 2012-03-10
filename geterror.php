<?

require("settings.php");
require_lib("mail.smtp");
require_lib("mail.msg");

if (!isset($_GET["id"])) {
	$OUTPUT = errSelect();
} else {
	if (isset($_GET["send"])) {
		$OUTPUT = errSend();
	} else {
		$OUTPUT = errStream();
	}
}

require("template.php");

function errSelect() {
}

function errStream() {
	$data = errData($_GET["id"]);
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=error$_GET[id]-$data[errtime].cer");

	print $data["errdata"];
	exit;
}

function errSend() {
	/* check for valid email settings */
	$settings = new dbSelect("esettings", "cubit");
	$settings->run();

	if ($settings->num_rows() <= 0) {
		r2sListSet("emailsettings");
		header("Location: email-settings.php");
		exit;
	}

	$settings->fetch_array();
	$server = $settings->d["smtp_host"];
	$from = $settings->d["fromname"];
	$reply = $settings->d["reply"];

	/* build the email */
	$data = errData($_GET["id"]);

	$msg = new clsMailMsg();
	$msg->newMessage($from, $reply, "Error Report: $data[errtime]",
		"Error report file attached.");
	$msg->addAttachment("application/octet-stream", "error$_GET[id]-$data[errtime].cer",
		$data["errdata"]);

	$md = $msg->getNewMessage();

	/* send the email */
	/**
	 * ok, so lets stop catching errors because if the email sending fails
	 * we are just going to go back to "an error has occured"
	 */

	disableErrorNet();
	$smtp = new clsSMTPMail();
	$smtp->sendMessages($server, 25, false, false, false, ERRORNET_EMAIL,
		$md["from"], $md["subject"], $md["body"], $md["headers"]);

	$OUTPUT = "<h3>Error Report</h3>";

	if ($smtp->bool_success !== true) {
		$OUTPUT .= "Error sending report. Please save report and email it
			to <a class='nav' href='mailto: ".ERRORNET_EMAIL."'>".ERRORNET_EMAIL."</a><br />
			<br />
			<input type='button' value='Save Error Report'
				onClick='document.location.href=\"".relpath("geterror.php")."?id=$_GET[id]\";' />";
	} else {
		$OUTPUT .= "Successfully sent report. Thank You.";
	}

	return $OUTPUT;
}

function errData($id) {
	db_con("cubit");
	$sql = "SELECT errtime::date AS errtime, errdata
			FROM errordumps WHERE id='$_GET[id]'";
	$rslt = db_exec($sql) or die("Unable to stream error dump");

	if (pg_num_rows($rslt) < 1) {
		$data = array(
			"errtime" => date("Y-m-d"),
			"errdata" => base64_encode("invalid error id.")
		);
	} else {
		$data = pg_fetch_array($rslt);
	}

	$data["errdata"] = bzcompress(base64_decode($data["errdata"]), 9);
	return $data;
}

?>

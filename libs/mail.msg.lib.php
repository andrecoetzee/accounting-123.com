<?
/**
 * interprets/creates a mail message
 *
 * @package Cubit
 * @subpackage mailMSG
 */

class clsMailMsg {
	var $message;

	// sum header vars
	var $to;
	var $from;
	var $reply_to;
	var $cc;
	var $bcc;
	var $subject;

	// type info
	var $maintype;
	var $subtype;
	var $typeinfo;

	var $headers; // array of headers
	var $body; // array of body
	var $parts; // parts of multipart message (for attachments)

	var $newmessage; // new message mode
	var $boundary = false;

	// constructor
	function clsMailMsg () {
		$this->to = "";
		$this->from = "";
		$this->reply_to = "";
		$this->cc = "";
		$this->bcc = "";
		$this->subject = "";
		$this->type = "";

		$this->headers = "";
		$this->body = "";
		$this->parts = "";

		$this->newmessage = false;

		$this->maintype = "text";
		$this->subtype = "plain";
		$this->typeinfo["charset"] = "US-ASCII";
	}

	// receives the message and start the processing
	function processMessage($msg) {
		if ($this->newmessage) return false;

		$this->clsMailMsg(); // just reset the variables

		$this->message = $msg;

		if ( $this->splitHeadersFromBody() == FALSE )
			return FALSE;

		// if this is a multipart message, split the parts by boundary
		if ( $this->maintype == "multipart" )
			if ( $this->splitBodyIntoParts() == FALSE ) return FALSE;

		// store common headers in predefined array
		if ( isset($this->headers["From"] ) ) $this->from = $this->headers["From"];
		if ( isset($this->headers["To"] ) ) $this->to = $this->headers["To"];
		if ( isset($this->headers["Subject"] ) ) $this->subject = $this->headers["Subject"];
		if ( isset($this->headers["Cc"] ) ) $this->cc = $this->headers["Cc"];
		if ( isset($this->headers["Bcc"] ) ) $this->bcc = $this->headers["Bcc"];
		if ( isset($this->headers["Reply-To"] ) ) $this->reply_to = $this->headers["Reply-To"];

		$this->type = $this->maintype . "/" . $this->subtype . "; charset=" . $this->typeinfo["charset"];

		return TRUE;
	}

	// reads the headers and stores them in $headers, and then the body and stores them in $body
	function splitHeadersFromBody() {
		if ($this->newmessage) return false;

		$tmp = explode("\n", $this->message);

		// only continue if there IS any lines
		if ( is_array($tmp) == FALSE )
			return FALSE;

		$at_part = "headers";
		foreach ( $tmp as $linenum => $line ) {
			// store in correct variable
			switch ( $at_part ) {
			case "headers":
				// if we found a blank line already found headers, move on from headers
				//if ( strlen($line) == 1 && ord($line) == 13 ) {
				$line = trim($line);
				if ( $line == "" && is_array($this->headers) ) {
					$at_part = "body";
					continue;
				}

				// split the header and store
				$cpos = strpos($line, ":");
				if ( $cpos === false ) $cpos = strpos($line, "=");
				if ( $cpos === false ) continue;
				$title = substr($line, 0, $cpos);
				$data = substr($line, $cpos + 1, strlen($line) - 1);

				$this->headers[$title] = trim($data); // remove the whitespace aswell

				// if this is the content type header, split it into the mtype, subtype and boundary
				if ( $title == "Content-Type" ) {
					$typedata = explode(";", $data);

					// check the type of description
					foreach ( $typedata as $tarr => $tfield ) {
						$tfield = strtolower($tfield);

						if ( strstr($tfield, "/") ) { // content type
							list ( $this->maintype, $this->subtype ) = explode("/", $tfield);
							$this->maintype = trim($this->maintype);
							$this->subtype = trim($this->subtype);
						} else if ( $epos = strpos($tfield, "=") ) { // other type info
							$tname = substr($tfield, 0, $epos);
							$tvalue = substr($tfield, $epos + 1, strlen($tfield) - 1);

							$tname = trim($tname);
							$tvalue = trim($tvalue);

							// if it is boundary, chop of the first and last "
							if ( $tname == "boundary" )
								$tvalue = substr($tvalue, 1, strlen($tvalue) - 2);

							$this->typeinfo[$tname] = $tvalue;
						}
					}
				} else if ($title == "Content-Disposition"
							&& preg_match("/filename=\"?([^\"]+)\"?/", $line, $m)) {
					$this->typeinfo["name"] = $m[1];
				} else if ( $title == 'filename' ) {
					$this->typeinfo["name"] = $data;
				}

				break;
			case "body":
			default:
				$this->body[] = $line;
			}
		}

		return TRUE;
	}

	// splits the body variable into parts (happens with multipart messages only)
	function splitBodyIntoParts() {
		if ($this->newmessage) return false;

		if ( ! isset($this->typeinfo["boundary"]) )
			return FALSE;

		// reset the variables for first use
		$body_part_num = 0;
		$boundary_found = FALSE; // whether we are copying a body or not (start boundary was found

		// go through each body line
		foreach ($this->body as $lnum => $line) {
			// check if this line is a boundary
			if ( trim($line) == "--" . $this->typeinfo["boundary"] ) {
				// if this boundary isn't the first one, increase the body part counter
				if ( $boundary_found == TRUE )
					$body_part_num++;

				// if it is a boundary, make sure we are starting the copies
				$boundary_found = TRUE;

				// we found a boundary, so we can continue with the next line
				continue;
			}

			// if this is the FINAL boundary, break;
			if ( trim($line) == "--" . $this->typeinfo["boundary"] . "--" )
				break;

			// ok, now that we are busy copying a body, and there is still more left to copy, lets copy it :>
			if ( $boundary_found == TRUE )
				$this->parts[$body_part_num][] = $line;
		}

		return TRUE;
	}

	// if this message is an attachment, then return the filename, else return FALSE
	function getAttachmentFilename() {
		if ($this->newmessage) return false;

		if ( isset($this->typeinfo["name"]) )
			return trim($this->typeinfo["name"]);
		else
			return FALSE;
	}

	/**
	 * starts a new message
	 *
	 * @param string $from from email address
	 * @param string $reply_to reply to email address
	 * @param string $subject
	 * @param string $body
	 * @param array $headers message main headers in array form
	 * @param bool $multipart whether it is multipart
	 */
	function newMessage($from, $reply_to, $subject, $body, $headers = false) {
		$this->newmessage = true;

		$this->boundary = md5($body) . "=:" . strlen($body);
		$this->subject = $subject;
		$this->from = $from;
		$this->reply_to = $reply_to;

		$this->headers = array();
		$this->headers[] = "From: $from";
		$this->headers[] = "Reply-To: $from";
		$this->headers[] = "Content-Type: multipart/mixed; boundary=\"$this->boundary\"";
		$this->headers[] = "MIME-Version: 1.0";
		if (is_array($headers)) {
			array_merge($this->headers, $headers);
		}

		$this->body =
			"Content-Type: text/html; charset=UTF-8\r\n"
			."Content-Transfer-Encoding: base64\r\n"
			."\r\n"
			.chunk_split(base64_encode($body));

		$this->parts = array();
	}

	/**
	 * add an attachments to an existing message
	 *
	 * @param string $mimetype content type
	 * @param string $filename content filename
	 * @param string $data content data
	 * @param bool $base64 whether or not the data is base64 encoded
	 */
	function addAttachment($mimetype, $filename, $data, $base64 = false) {
		$this->parts[] = array(
			"mimetype" => $mimetype,
			"filename" => $filename,
			"data" => ($base64 ? $data : base64_encode($data))
		);
	}

	/**
	 * returns the full message in array of form: from, reply_to, subject, body, headers
	 *
	 * @return array
	 */
	function getNewMessage() {
		$ret = array(
			"from" => $this->from,
			"reply_to" => $this->reply_to,
			"subject" => $this->subject,
			"body" => "",
			"headers" => $this->headers
		);

		$b = array();

		$b[] = "Message is multipart.\r\n\r\n";
		$b[] = "--$this->boundary\r\n";
		$b[] = "$this->body\r\n";
		$b[] = "\r\n";

		if (count($this->parts) > 0) {
			foreach ($this->parts as $ai) {
				$ad = array();
				$ad[] = "Content-Type: $ai[mimetype]; charset=UTF-8\r\n";
				$ad[] = "Content-Disposition: attachment; filename=$ai[filename]\r\n";
				$ad[] = "Content-Transfer-Encoding: base64\r\n";
				$ad[] = "\r\n";
				$ad[] = chunk_split($ai["data"])."\r\n";

				$b[] = "--$this->boundary\r\n";
				$b[] = implode("", $ad);
				$b[] = "\r\n";
			}
		}

		$b[] = "--$this->boundary--\r\n";

		$ret["body"] = implode("", $b);

		return $ret;
	}
}


?>

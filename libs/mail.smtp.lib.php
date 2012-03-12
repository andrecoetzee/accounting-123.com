<?
/**
 * handles the sending of mails by directly communicating with SMTP server
 *
 * @package Cubit
 * @subpackage mailSMTP
 */
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

/**
 * initiates a SMTP connection and sends mail
 *
 */
class clsSMTPMail {
	/**
	 * server to connect to
	 *
	 * @var string
	 */
	var $server;

	/**
	 * port to connect to
	 *
	 * @var int
	 */
	var $port;

	/**
	 * should we authorize?
	 *
	 * @var bool
	 */
	var $auth;

	/**
	 * auth: user on server
	 *
	 * @var string
	 */
	var $user;

	/**
	 * auth: password for this user
	 *
	 * @var string
	 */
	var $pass;

	/**
	 * recipient(s) of mail
	 *
	 * @var string
	 */
	var $rcpt;

	/**
	 * sender from whom message is sent
	 *
	 * @var string
	 */
	var $from;

	/**
	 * (internal usage) stores the socket descriptor
	 *
	 * @var int
	 */
	var $socket;

	/**
	 * stores the message to be sent
	 *
	 * @var string
	 */
	var $message;

	/**
	 * store the headers
	 *
	 * @var string
	 */
	var $headers;

	/**
	 * stores the subject
	 *
	 * @var string
	 */
	var $subject;

	/**
	 * (internal usage) enabled for debugging purposes
	 *
	 * @var bool
	 */
	var $debug;

	/**
	 * whether message was sent successfully or not
	 *
	 * @var bool
	 */
	var $bool_success;

	/**
	 * constructor
	 *
	 * @return clsSMTPMail
	 */
	function clsSMTPMail() {
		$this->server = "";
		$this->port = 0;
		$this->auth = 0;
		$this->user = "";
		$this->pass = "";
		$this->rcpt = "";
		$this->from = "";
		$this->bool_success = false;
	}

	/**
	 * sends a message
	 *
	 * if you created a new message with objMailMsg, pass the array returned with
	 * objMailMsg->getNewMessage() as $from and leave $subject, $messsage and $headers.
	 * they will all be automatically determined from the array.
	 *
	 * @param string $server
	 * @param string $port
	 * @param bool $auth
	 * @param string $user
	 * @param string $pass
	 * @param string $to
	 * @param string $from / objMailMsg
	 * @param string $subject / --
	 * @param string $message / --
	 * @param string $headers / --
	 * @return string
	 */
	function sendMessages($server, $port, $auth, $user, $pass, $to, $from, $subject = false, $message = false, $headers = false) {
		$this->bool_success = false;

		// set the parameters
		$this->server = gethostbyname($server);
		$this->port = $port;
		if ( $auth == 1 ) {
			$this->user = $user;
			$this->pass = $pass;
		}
		$this->rcpt = $to;

		if (is_array($from)) {
			$this->from = $from["from"];
			$this->subject = $from["subject"];
			$this->message = $from["body"];
			$from["headers"][] = "To: $to";
			$this->headers = implode("\r\n", $from["headers"]);
		} else {
			$this->from = $from;
			$this->subject = $subject;
			$this->message = $message;
			$this->headers = $headers;
		}

		// PHASE 1 : connect
		if ( $this->smtp_connect() == FALSE ) return "Error connecting to server. Cannot find smtp host.";

		// PHASE 2 : wait for connection to become active
		if ( $this->wait_active() == FALSE ) return $this->quit("Error connecting to server. Connection failed.");

		// PHASE 3 : wait for connection to become active
		if ( $this->smtp_helo() == FALSE ) return $this->quit("Error connecting to server. Server not responding to HELO request.");

		// PHASE 4 : authorization
		if ( $auth == 1 ) {
			if ( $this->smtp_user() == FALSE ) return $this->quit("Error identifying user (1).");
			if ( $this->smtp_pass() == FALSE ) return $this->quit("Error identifying user (2).");
		}

		// PHASE 5 : send the MAIL FROM command
		if ( ($msg = $this->smtp_mailfrom()) !== TRUE ) {
			return $this->quit("Error sending mail. $msg.");
		}

		// PHASE 6 : send the RCPT TO command
		if ( ($msg = $this->smtp_rcptto()) !== TRUE ) {
			return $this->quit("Error sending mail. $msg.");
		}

		// PHASE 7 : tells the server we're gonna start sending data
		if ( $this->smtp_data() == FALSE ) return $this->quit("Error sending mail (3).");

		// PHASE 8 : wait for connection to become active
		if ( $this->send_headers() == FALSE ) return $this->quit("Error sending mail (4).");
		if ( $this->send_data() == FALSE ) return $this->quit("Error sending mail (5).");

		// PHASE 9 : finish and disconnect
		$this->quit("Success");
		@fclose( $this->socket );

		// success full, return 0
		$this->bool_success = true;

		return "<li class='err'>Successfully sent message.</li>";
	}

	/**
	 * sends a quit message to server, and returns whatever text was passed to it
	 *
	 * @ignore
	 * @param string $string
	 * @return string
	 */
	function quit($string) {
		@$this->sock_write( "QUIT" );
		return $string;
	}

	/**
	 * waits for the connection to become active (until the first +OK or -ERR is received)
	 *
	 * @return bool
	 */
	function wait_active() {
		// receive the responde
		$data = ltrim( $this->sock_read() );

		// check response
		if ( substr($data,0,3) != "220" )
			return FALSE;

		// success !!
		return TRUE;
	}

	/**
	 * opens socket
	 *
	 * @ignore
	 * @return bool
	 */
	function smtp_connect() {
		// try and create the connection
		$this->socket = @fsockopen($this->server, $this->port, $errno, $errstr);

		if ( $this->socket == FALSE ) {
			return FALSE;
		}

		// success!!
		return TRUE;
	}

	/**
	 * sends the USER command, and wait's for an OK
	 *
	 * @todo still to be done
	 * @ignore
	 * @return bool
	 */
	function smtp_user() {
		// success!!
		return FALSE;
	}

	/**
	 * sends the PASS command, and wait's for an OK
	 *
	 * @todo still to be done
	 * @ignore
	 * @return bool
	 */
	function smtp_pass() {
		// success!!
		return FALSE;
	}

	/**
	 * sends the HELO command, and wait's for 250
	 *
	 * @ignore
	 * @return bool
	 */
	function smtp_helo() {
		global $_SERVER;

		// transmit data
		$this->sock_write( "EHLO fr13nd" );

		// receive the responde
		while ( 1 ) {
			$data = ltrim( $this->sock_read() );

			// check response
			if ( substr($data,0,3) != "250" ) {
				return FALSE;
			} else if ( substr($data, 0, 4) == "250 " ) {
				// success!!
				return TRUE;
			}
		}
	}

	/**
	 * sends the MAIL FROM command, and wait's for 250
	 *
	 * @ignore
	 * @return bool
	 */
	function smtp_mailfrom() {
		// transmit data
		$this->sock_write( "MAIL FROM: <$this->from>" );

		// receive the responde
		$data = ltrim( $this->sock_read() );

		// check response
		if ( substr($data,0,3) != "250" )
			return preg_replace("/^[^:]*: /", "", $data);

		// success!!
		return TRUE;
	}

	/**
	 * sends the RCPT TO command, and wait's for 250
	 *
	 * @ignore
	 * @return bool
	 */
	function smtp_rcptto() {
		$rcpt_parts = explode(";", $this->rcpt);

		foreach ( $rcpt_parts as $value ) {
			$value = trim($value);

			// transmit data
			$this->sock_write( "RCPT TO: <$value>" );

			// receive the responde
			$data = ltrim( $this->sock_read() );

			// check response
			if ( substr($data,0,3) != "250" ) {
				return preg_replace("/^[^:]*: /", "", $data);
			}
		}

		// success!!
		return TRUE;
	}

	/**
	 * sends the DATA command, and wait's for 354
	 *
	 * @ignore
	 * @return bool
	 */
	function smtp_data() {
		// transmit data
		$this->sock_write( "DATA" );

		// receive the responde
		$data = ltrim( $this->sock_read() );

		// check response
		if ( substr($data,0,3) != "354" ) {
			return FALSE;
		}

		// success!!
		return TRUE;
	}

	/**
	 * sends the headers and another NL
	 *
	 * @ignore
	 * @return bool
	 */
	function send_headers() {
		// transmit data
		$headers = $this->headers;
		$headers .= "\r\nSubject: $this->subject\r\n";
		$this->sock_write( "$headers" );

		// success!!
		return TRUE;
	}

	/**
	 * sends the data and the . , and wait's for 250
	 *
	 * @ignore
	 * @return bool
	 */
	function send_data() {
		// transmit data
		$this->sock_write( $this->message );
		$this->sock_write( "." );

		// receive the responde
		$data = ltrim( $this->sock_read() );

		// check response
		if ( substr($data,0,3) != "250" )
			return FALSE;

		// success!!
		return TRUE;
	}

	/**
	 * writes to the socket
	 *
	 * @ignore
	 * @param string $data
	 */
	function sock_write($data) {
		@fwrite( $this->socket, "$data\r\n");
	}

	/**
	 * reads from the socket 4kb
	 *
	 * @ignore
	 * @return string
	 */
	function sock_read() {
		$data = @fgets( $this->socket, 4096 );
		return $data;
	}
}



?>
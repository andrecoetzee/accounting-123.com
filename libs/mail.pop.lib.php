<?
/**
 * handles the retrieving of mails by directly communicating with POP server
 *
 * @package Cubit
 * @subpackage mailPOP
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
 * handles connection to POP server
 *
 * handles everything from the connection to storing of messages
 * and then also the retrievel of all messages by enumerating through them
 * one by one
 *
 */
class clsPOPMail {
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
	 * user on server
	 *
	 * @var string
	 */
	var $user;

	/**
	 * password for this user
	 *
	 * @var string
	 */
	var $pass;

	/**
	 * number of messages
	 *
	 * @var int
	 */
	var $msg_count;

	/**
	 * array containing all the messages
	 *
	 * @var array
	 */
	var $messages;

	/**
	 * tells us at what number message we are when returning them to creator of class
	 *
	 * @see clsPOPMail::getMessage()
	 *
	 * @var int
	 */
	var $msg_ret;

	/**
	 * (internal usage) stores the socket address
	 *
	 * @var int
	 */
	var $socket;

	/**
	 * constructor
	 *
	 * @return clsPOPMail
	 */
	function clsPOPMail() {
		$this->reset();
	}
	
	/**
	 * 
	 */
	function reset() {
		$this->server = "";
		$this->port = 0;
		$this->user = "";
		$this->pass = "";

		$this->messages = Array();
		$this->msg_count = "";
		$this->msg_ret = 1;
	}

	/**
	 * returns the next "unreturned" message
	 *
	 * increases $msg_ret,
	 * when $msg_ret == $msg_count, go back to zero, and return FALSE
	 *
	 * @return string/bool
	 */
	function enumGetMessage() {
		if ( $this->msg_ret <= $this->msg_count ) {
			return $this->messages[$this->msg_ret++];
		} else {
			$this->msg_ret = 0;
			return FALSE;
		}
	}

	/**
	 * initiates the retrieving process from the server
	 *
	 * stores the message in array. can only be returned with
	 * enumGetMessage() or $messages
	 *
	 * @see clsPOPMail::enumGetMessage()
	 * @see clsPOPMail::$messages
	 *
	 * @param string $server
	 * @param string $port
	 * @param string $user
	 * @param string $pass
	 * @param bool $leave_msgs true when messages should NOT be deleted
	 * @return bool
	 */
	function retrieveMessages($server, $port, $user, $pass, $leave_msgs) {
		// set the parameters
		$this->server = gethostbyname($server);
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;

		// PHASE 1 : connect
		if ( $this->pop3_connect() == FALSE ) return $this->quit("Error connecting to server.");

		// PHASE 2 : wait for connection to become active
		if ( $this->wait_active() == FALSE ) return $this->quit("Error connection to server.");

		// PHASE 3 : user
		if ( $this->pop3_user() == FALSE ) return $this->quit("Error identifying user.");

		// PHASE 4 : pass
		if ( $this->pop3_pass() == FALSE ) return $this->quit("Error identifying user.");

		// PHASE 5 : stat
		if ( $this->pop3_stat() == FALSE ) return 0; // no messages, just return

		// phase 6 and 7 get's repeated for every message on the server
		for ( $i = 1 ; $i <= $this->msg_count ; $i++ ) {
			// currently nothing is done when a retr or dele doesn't work... because it more important
			// that all the messages are received rather than bombing out when say
			// a random error occurs or whatever

			// PHASE 6 : retr
			if ( $this->pop3_retr($i) == FALSE ) 1; //return $this->quit("Server error on RETR. No such message.");

			// PHASE 7 : dele
			if ( $leave_msgs == FALSE )
				if ( $this->pop3_dele($i) == FALSE ) 1; // return $this->quit("Server error on DELE. No such message.");
		}

		// PHASE 8 : disconnect
		@fwrite( $this->socket, "QUIT\r\n" );
		@fclose( $this->socket );

		// success full, return 0
		return 0;
	}

	/**
	 * sends a quit message to server, and returns whatever text was passed to it
	 *
	 * @ignore
	 * @return string
	 */
	function quit($string) {
		@fwrite( $this->socket, "QUIT\r\n" );
		return $string;
	}

	/**
	 * waits for the connection to become active (until the first +OK or -ERR is received)
	 *
	 * @ignore
	 * @return bool
	 */
	function wait_active() {
		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response
		if ( substr($data,0,3) != "+OK" )
			return FALSE;

		// success !!
		return TRUE;
	}

	/**
	 * creates socket
	 *
	 * @ignore
	 * @return bool
	 */
	function pop3_connect() {
		// try and create the connection
		$this->socket = @fsockopen($this->server, $this->port);

		if ( $this->socket == FALSE )
			return FALSE;

		// success!!
		return TRUE;
	}

	/**
	 * sends the USER command, and wait's for an OK
	 *
	 * @ignore
	 * @return bool
	 */
	function pop3_user() {
		// transmit data
		@fwrite( $this->socket, "USER $this->user\r\n" );

		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response
		if ( substr($data,0,3) != "+OK" )
			return FALSE;

		// success!!
		return TRUE;
	}

	/**
	 * sends the PASS command and wait's for an OK
	 *
	 * @ignore
	 * @return bool
	 */
	function pop3_pass() {
		// transmit data
		@fwrite( $this->socket, "PASS $this->pass\r\n" );

		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response
		if ( substr($data,0,3) != "+OK" )
			return FALSE;

		// success!!
		return TRUE;
	}

	/**
	 * sends the STAT command and wait's for an OK, and on OK reads num of messages
	 *
	 * @ignore
	 * @return bool
	 */
	function pop3_stat() {
		// transmit data
		@fwrite( $this->socket, "STAT\r\n" );

		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response and if it is valid
		if ( (substr($data,0,3) != "+OK") && (strlen($data) > 4) )
			return FALSE;

		// ok now receive the first value after the +OK, this is the number of messages
		for ( $i = 4 ; $i < strlen($data) && $data[$i] != ' '; $i++ ) {
			$this->msg_count .= $data[$i];
		}

		$this->msg_count += 0;

		// success!!
		return TRUE;
	}

	/**
	 * handles the RETR commands for retrieving messages
	 *
	 * sends the RETR command and wait for an OK,
	 * then reads the message, and store it as array element
	 *
	 * @ignore
	 * @param unknown_type $msg_num
	 * @return unknown
	 */
	function pop3_retr($msg_num) {
		// transmit data
		@fwrite( $this->socket, "RETR $msg_num\r\n" );

		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response
		if ( substr($data,0,3) != "+OK" )
			return FALSE;

		// read until a . is found on it's own line
		$msg = "";
		while ( !feof( $this->socket ) ) {
			$buf = @fgets( $this->socket, 4096 );

			if ( trim($buf) == "." )
				break;

			$msg .= "$buf";
		}

		// store the message
		$this->messages[$msg_num] = $msg;

		// success!!
		return TRUE;
	}

	/**
	 * sends the DELE command, and wait's for an OK
	 *
	 * @ignore
	 * @param int $msg_num
	 * @return string
	 */
	function pop3_dele($msg_num) {
		// transmit data
		@fwrite( $this->socket, "DELE $msg_num\r\n" );

		// receive the responde
		$data = ltrim( @fgets( $this->socket, 4096 ) );

		// check response
		if ( substr($data,0,3) != "+OK" )
			return FALSE;

		// success!!
		return TRUE;
	}
}

?>

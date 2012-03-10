<?

/**
 * gets the current companies trh configuration.
 *
 * returns an array in form confname=>value
 *
 * @return array
 */
function getTrhConfig() {
	$qry = new dbSelect("config", "trh");
	$qry->run();

	$config = array();
	while ($row = $qry->fetch_array()) {
		$config[$row["name"]] = $row["value"];
	}

	$qry->free();

	return $config;
}

/**
 * returns key information for supplier
 *
 * @param int $suppid
 * @return array
 */
function trhKeySupp($suppid, $cols = "*") {
	if ($cols == "*") {
		$cols = "email, recv_key, send_key, introtime, introip, userid";
	}

	$cols = preg_replace("/(recv|send)_key/", "(key).\\1_key AS \\1_key", $cols);

	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "$cols"),
		m("where", "suppid='$suppid'")
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("This supplier isn't configured for Transheks transactioning.");
	}

	return $qry->fetch_array();
}

/**
 * returns key information for customer
 *
 * @param int $custid
 * @return array
 */
function trhKeyCust($custid, $cols = "*") {
	if ($cols == "*") {
		$cols = "email, recv_key, send_key, introtime, introip, ip, userid";
	}

	$cols = preg_replace("/((?<![^ ,])ip|(recv|send)_key)/", "(key).\\1 AS \\1", $cols);

	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "$cols"),
		m("where", "custid='$custid'")
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("This customer isn't configured for Transheks transactioning.");
	}

	return $qry->fetch_array();
}

/**
 * returns key information from key pair
 *
 * @param int $custid
 * @return array
 */
function trhKeyPair($recv_key, $send_key, $cols = "*") {
	if ($cols == "*") {
		$cols = "email, recv_key, send_key, introtime, introip, ip, userid, custid, suppid";
	}

	$cols = preg_replace("/((?<![^ ,])ip|(recv|send)_key)/", "(key).\\1 AS \\1", $cols);

	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "$cols"),
		m("where", "(key).send_key='$send_key' AND (key).recv_key='$recv_key'")
	));
	$qry->run();

	return $qry->fetch_array();
}

/**
 * sends a trh message.
 *
 * if you dont pass the trh config variable, it will be fetched automatically.
 *
 * @param string $who must be "supp" or "cust", what other side is too me
 * @param int $id customer/supplier id
 * @param string $msgtype 6 character message type
 * @param string $data what to send
 * @param array $config optionally the companies trh configuration
 * @return bool
 */
function send_trhmsg($who, $id, $email, $msgtype, $data, $config = false) {
	if (defined("CONSOLE")) {
		print "sending response:\n";
		print "\twho: $who\n";
		print "\tid: $id\n";
		print "\tmsgtype: $msgtype\n";
		print "\tdata: $data\n";
	}

	require_lib("mail.smtp");
	require_lib("mail.msg");

	/* configuration */
	if ($config === false) {
		$config = getTrhConfig();
	}

	extract($config);

	/* determines new message parameters */
	$subject = "trh_$msgtype";
	$body = array();
	$attachments = array();
	$headers = array();

	$compinfo = qryCompInfo();

	/* determine my ip address */
	if (empty($_SERVER["SERVER_NAME"])) {
		$myipaddr = "0.0.0.0";
	} else {
		$myipaddr = gethostbyname($_SERVER['SERVER_NAME']);
	}

	/* determine key request function */
	if ($who == "supp") {
		$keyFunc = "trhKeySupp";
	} else {
		$keyFunc = "trhKeyCust";
	}

	/* standard message body details */
	$body[] = "compname: $compinfo[compname]";
	$body[] = "ipaddr: $myipaddr";
	$body[] = "bustel: $compinfo[tel]";
	$body[] = "fromwho: ".($who == "supp" ? "cust" : "supp");
	$body[] = "email: $SMTP_FROM";

	switch ($msgtype) {
		/* request a new key */
		case "reqkey":
			$subject .= " $data";
			break;

		/* respond to a newly requested key */
		case "rspkey":
			/* get my key from this guy */
			$qry = new dbSelect("keys", "trh", grp(
				m("cols", "(key).send_key AS mykey"),
				m("where", "${who}id='$id'")
			));
			$qry->run();

			if ($qry->num_rows() <= 0) {
				return false;
			}

			$d = $qry->fetch_array();
			$mykey = $d["mykey"];

			$subject .= " $data";
			$body[] = "mykey: $mykey";
			break;

		/* requests a new purchase */
		case "reqpur":
			$keys = $keyFunc($id, "recv_key, send_key");

			$subject .= " $keys[send_key]";
			$body[] = "yourkey: $keys[recv_key]";

			$attachments["data.xml"] = $data;

			break;

		/* responds to a new purchase request */
		case "rsppur":
			$keys = $keyFunc($id, "recv_key, send_key");

			$subject .= " $keys[send_key]";
			$body[] = "yourkey: $keys[recv_key]";
			$body[] = "purid: $data[purid]";
			$body[] = "status: $data[status]";

			break;
	}

	/* create new mail message */
	$msg = new clsMailMsg();
	$msg->newMessage($SMTP_FROM, $SMTP_FROM, $subject, implode("\n", $body), $headers, true);

	if (count($attachments)) {
		foreach ($attachments as $fname => $contents) {
			$msg->addAttachment("text/plain", $fname, $data);
		}
	}
	
	$smtp = new clsSMTPMail();
	$ret = $smtp->sendMessages($SMTP_SERVER, 25, !empty($SMTP_USER), $SMTP_USER, $SMTP_PASS,
		 $email, $msg->getNewMessage());

	if ($smtp->bool_success !== true) {
		return $ret;
	}

	return true;
}

/**
 * generates a new non existant key
 *
 * @return string
 */
function genkey() {
	while (1) {
		srand(time());
		$r = rand();

		$nk = md5($r);

		$qry = new dbSelect("keys", "trh", grp(
			m("where", "(key).recv_key='$nk'")
		));
		$qry->run();

		if ($qry->num_rows() == 0) {
			$qry->free();
			return $nk;
		}

		$qry->free();
	}
}

/**
 * checks whether supplier is transheks configured
 *
 * @param int $suppid
 * @return bool
 */
function trhSupplierEnabled($suppid) {
	$qry = new dbSelect("config", "trh", grp(
		m("where", "value!='' AND (name='SMTP_SERVER' OR name='POP3_SERVER')")
	));
	$qry->run();

	$ret = $qry->num_rows() > 0;
	$qry->free();

	return $ret;
}

/**
 * returns suppliers stock code for stock item.
 *
 * returns false if no stkcod for supplier.
 *
 * @param int $suppid
 * @param int $stkid
 * @return string
 */
function suppStkcod($suppid, $stkid) {
	$qry = new dbSelect("suppstock", "cubit", grp(
		m("cols", "stkcod"),
		m("limit", 1),
		m("where", "suppid='$suppid' AND stkid='$stkid'")
	));
	$qry->run();

	return $qry->fetch_result();
}

/**
 * returns suppliers stock description for stock item.
 *
 * returns false if no stkdes for supplier.
 *
 * @param int $suppid
 * @param int $stkid
 * @return string
 */
function suppStkdes($suppid, $stkid) {
	$qry = new dbSelect("suppstock", "cubit", grp(
		m("cols", "stkdes"),
		m("limit", 1),
		m("where", "suppid='$suppid' AND stkid='$stkid'")
	));
	$qry->run();

	return $qry->fetch_result();
}


/**
 * alias for suppStkCod()
 * 
 * @ignore
 */
function trhSuppStkcod($suppid, $stkid) {
	return suppStkcod($suppid, $stkid);
}

/**
 * returns stkid for suppliers stock code.
 *
 * returns false if no stkcod for supplier.
 *
 * @param int $suppid
 * @param int $stkcod
 * @return string
 */
function suppStkid($suppid, $stkcod) {
	$qry = new dbSelect("suppstock", "cubit", grp(
		m("cols", "stkid"),
		m("limit", 1),
		m("where", "suppid='$suppid' AND stkcod='$stkcod'")
	));
	$qry->run();

	return $qry->fetch_result();
}

?>

<?

if (!defined("CUBIT_WD")) define("CUBIT_WD", dirname($_SERVER["SCRIPT_FILENAME"]));
$CWD = CUBIT_WD;
$CWD = preg_replace("/\\\\/", "/", $CWD);

ini_set("max_execution_time", 0);
define("CONSOLE", "trh");

require("${CWD}/../newsettings.php");
define("USER_DIV", 2);
require("${CWD}/daemon.inc.php");
require_lib("mail.pop");
require_lib("mail.msg");

if (isset($argv[1]) && preg_match("/^[a-z]{4}\$/", $argv[1])) {
	$wh = "AND c.code='$argv[1]'";
} else {
	$wh = "";
}

/* create objects */
$oPOP = new clsPOPMail();
$oMSG = new clsMailMsg();

//db_conn("cubit_aaae");
//send_trhmsg("cust", "-1", "trh2@last.za.net", "rspkey", "99887766554433221100");
//exit(1);

$smallest_wait = -1;
$ctr = 0;
while (true) {
	$companies = array();

	/* fetch the company list */
	if ($ctr == 0) {
		db_con("cubit");
		$sql = "SELECT c.code FROM companies c WHERE (true) $wh";
		$comps = new dbSql($sql);
		$comps->run();

		$companies = array();
		while ($row = $comps->fetch_array()) {
			/* read the companies configuration */
			print "Company Read: $row[code]\n";
			db_con("cubit_$row[code]");
			$cfg = getTrhConfig();

			/* not enabled */
			if ($cfg["MANAGEUSER"] <= 0 || empty($cfg["POP3_SERVER"]) || empty($cfg["SMTP_SERVER"])) {
				continue;
			}

			$companies[$row["code"]] = $cfg;
		}

		$comps->free();
	}

	/* run the routines */
	foreach ($companies as $code => $conf) {
		routine($code, $conf);
		if ($smallest_wait < 0 || $smallest_wait > $conf["INTERVAL"]) {
			$smallest_wait = $conf["INTERVAL"];
		}
	}

	if ($smallest_wait < 0) {
		print "\nSleeping for dflt 60\n";
		sleep(60);
	} else {
		print "\nSleeping for $smallest_wait\n";
		sleep($smallest_wait);
	}
}

/**
 * routine runner
 *
 * @param string $compa coda, $configa!
 */
function routine($code, $config) {
	global $oPOP, $oSMTP, $oMSG;
	extract($config);

	/* check if trh is enabled */
	if (empty($POP3_SERVER) || empty($SMTP_SERVER)) {
		return false;
	}

	print "Company Routine: $code\n";

	db_con("cubit_$code");

	/* check for requests/responses */
	$oPOP->reset();
	$oPOP->retrieveMessages($POP3_SERVER, 110, $POP3_USER, $POP3_PASS, false);

	/* loop through each message */
	while (($msg = $oPOP->enumGetMessage()) !== false) {
		/* process message, on failure continue with next message */
		if (!$oMSG->processMessage($msg)) {
			continue;
		}

		/* check subject for message type */
		if (!isset($oMSG->headers["Subject"])) {
			continue;
		}

		if (preg_match(REGEX_REQTYPE, $oMSG->headers["Subject"], $si)) {
			$key = $si[2];
			$req = $si[1];

			print "Key: $key\n";
			print "Req Type: $req\n";

			switch ($req) {
				/* new client request */
				case "reqkey":
					print "entering request_new()\n";
					$ret = request_new($key, $oMSG, $config);
					break;

				/* response to new request */
				case "rspkey":
					print "entering response_new()\n";
					$ret = response_new($key, $oMSG, $config);
					break;

				/* request order */
				case "reqpur":
					print "entering request_order()\n";
					$ret = request_order($key, $oMSG, $config);
					break;

				/* order response */
				case "rsppur":
					print "entering response_purchase()\n";
					$ret = response_order($key, $oMSG, $config);
					break;

				default:
					print "invalid request type\n";
					return false;
			}
		} else {
			print "Not matching: ".$oMSG->headers["Subject"]."\n";
		}
	}

	return true;
}

/**
 * handles a new request
 *
 * @param string $key
 * @param clsMailMsg $oMSG
 * @param array $config
 * @return bool
 */
function request_new($key, $oMSG, $config) {
	if (($stds = msg_std($oMSG)) === false) {
		return false;
	}
	list($compname, $ipaddr, $bustel, $fromwho, $email) = $stds;

	/* locate customer/supplier */
	if ($fromwho == "supp") {
		$suppid = locateSupplier($compname);
		$custid = 0;
	} else { // $fromwho == "cust"
		$custid = locateCustomer($compname);
		$suppid = 0;
	}

	print "name: $compname\n";
	print "ipaddr: $ipaddr\n";
	print "bustel: $bustel\n";
	print "fromwho: $fromwho\n";
	print "custid: $custid\n";
	print "suppid: $suppid\n";

	/* check if company name and key is in list */
	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "1"),
		m("where", "${fromwho}id='".${"${fromwho}id"}."' AND (key).send_key='$key'")
	));
	$qry->run();

	if ($qry->num_rows() > 0) {
		print "---> KEY EXISTS, ignoring\n";
		return false;
	}

	$qry->free();

	print "from email: $email\n";

	/* generate a key for receiving for client */
	$newkey = genkey();

	/* add new key to system */
	$cols = grp(
		m("userid", $config["MANAGEUSER"]),
		m("introtime", raw("CURRENT_TIMESTAMP")),
		m("introip", $ipaddr),
		m("email", $email),
		m("compname", $compname),
		m("bustel", $bustel),
		m("custid", $custid),
		m("suppid", $suppid),
		m("key", dbrow(
			"0.0.0.0/0",
			$key,
			$newkey
		))
	);

	$upd = new dbUpdate("keys", "trh", $cols);
	$upd->run(DB_INSERT);
	$upd->free();
	
	if ($custid == -1 && $suppid == -1) {
		$desc = ($fromwho == "supp") ? "supplier" : "customer";
		$userinfo = qryUsers($config["MANAGEUSER"]);
		msgSend($userinfo["username"], "Unknown $desc requested Transheks communication. 
			Click <a target='mainframe' href=\"../transheks/commapprove.php\">here</a> to view.");
		return false;
	} else {
		/* send response */
		return send_trhmsg($fromwho, ${"${fromwho}id"}, $email, "rspkey", "$newkey", $config);
	}
}

/**
 * handles a request response
 *
 * @param string $key
 * @param clsMailMsg $oMSG
 * @param array $config
 * @return bool
 */
function response_new($key, $oMSG, $config) {
	if (($stds = msg_std($oMSG)) === false) {
		return false;
	}
	list($compname, $ipaddr, $bustel, $fromwho, $email) = $stds;


	/* other side key */
	if (($mykey = getfrommmsg(REGEX_MYKEY, $oMSG)) === false) {
		return false;
	}

	print "details:\n";
	print "\tname: $compname\n";
	print "\tipaddr: $ipaddr\n";
	print "\tbustel: $bustel\n";
	print "\tfromwho: $fromwho\n";
	print "\temail: $email\n";
	print "\tmykey: $mykey\n";

	if ($fromwho == "supp") {
		$fld = "suppid";
	} else {
		$fld = "custid";
	}
	
	$whr = "(key).recv_key='$mykey' AND (key).send_key='' AND $fld>'0'";
	
	if ($key == str_pad("denied", 32, 'A', STR_PAD_RIGHT)) {
		$upd = new dbDelete("keys", "trh", $whr);
		$upd->run();
		
		$msg = "Transactioning request with $compname denied.\n";
	} else {
		$cols = grp(
			m(raw("key.send_key"), $key),
			m("introip", $ipaddr)
		);
	
		$upd = new dbUpdate("keys", "trh", $cols, $whr);
		$upd->run(DB_UPDATE);
	
		/* notify user */
		$keyinfo = trhKeyPair($mykey, $key);
		$userinfo = qryUsers($keyinfo["userid"]);

		$msg = "Transactioning with $compname successfully configured.\n"
			."You can now proceed and send orders, receive pricelist, etc.\n";
	}

	msgSend($userinfo["username"], $msg);

	return true;
}

/**
 * handles a order request
 *
 * @param string $key
 * @param clsMailMsg $oMSG
 * @param array $config
 * @return bool
 */
function request_order($key, $oMSG, $config) {
	if (($stds = msg_std($oMSG)) === false) {
		return false;
	}
	list($compname, $ipaddr, $bustel, $fromwho, $email) = $stds;

	/* other side key */
	if (($yourkey = getfrommmsg(REGEX_YOURKEY, $oMSG)) === false) {
		return false;
	}

	/* validate keys */
	if (($keyinfo = trhKeyPair($key, $yourkey)) === false) {
		return false;
	}

	$custid = $keyinfo["custid"];

	if (count($oMSG->parts) < 2) {
		print "Invalid message: count(parts) < 2\n";
		return false;
	}

	$attach = new clsMailMsg();
	$attach->processMessage(implode("\r\n", $oMSG->parts[1]));
	
	if ($attach->getAttachmentFilename() != "data.xml") {
		print "Invalid message part. Disposition name != data.xml\n";
		return false;
	}

	$XML = base64_decode(preg_replace("/[ \r\n	]/", "", implode("", $attach->body)));

	global $reqpur_activetag, $purch_info, $purch_items;
	$reqpur_activetag = $purch_info = $purch_items = array();

	$parser = xml_parser_create();
	xml_set_element_handler($parser, "stElement", "endElement");
	xml_parse($parser, $XML, true);
	xml_parser_free($parser);

	$i = grp(
		m("approved", "n"),
		m("custid", $custid),
		m(raw("trhkey"), dbrow(
			"0.0.0.0/0",
			"$keyinfo[send_key]",
			"$keyinfo[recv_key]"
		))
	);

	$purch_info = array_merge($purch_info, $i);

	foreach ($purch_info as $k => $v) {
		if (empty($v)) {
			$purch_info[$k] = raw("NULL");
		}
	}
	
	$upd = new dbUpdate("recvpurch", "trh", $purch_info);
	$upd->run(DB_INSERT);

	$recvpurch_id = pglib_lastid("trh.recvpurch", "id");

	$upd->setTable("recvpurch_items", "trh");
	foreach ($purch_items as $pi_det) {
		unset($pi_det["id"]);
		$pi_det["recvpurch_id"] = $recvpurch_id;
		$upd->setOpt($pi_det);
		$upd->run(DB_INSERT);
	}
	
	print "Purchase inserted.\n";
	
	$userinfo = qryUsers($config["MANAGEUSER"]);
	msgSend($userinfo["username"], "Purchase received via Transheks. Click <a target='mainframe' href='../transheks/order_approve.php'>here</a> to view.");
}

/**
 * handles a new purchase response
 *
 * @param string $key
 * @param clsMailMsg $oMSG
 * @param array $config
 * @return bool
 */
function response_order($key, $oMSG, $config) {
	if (($stds = msg_std($oMSG)) === false) {
		return false;
	}
	list($compname, $ipaddr, $bustel, $fromwho, $email) = $stds;

	/* other side key */
	if (($yourkey = getfrommmsg(REGEX_YOURKEY, $oMSG)) === false) {
		return false;
	}

	/* purchase id in my database */
	if (($purid = getfrommmsg(REGEX_PURID, $oMSG)) === false) {
		return false;
	}

	/* purchase accepted/invoices/denied */
	if (($purstatus = getfrommmsg(REGEX_PURSTATUS, $oMSG)) === false) {
		return false;
	}

	/* validate keys */
	if (($keyinfo = trhKeyPair($key, $yourkey)) === false) {
		return false;
	}

	$userinfo = qryUsers($keyinfo["userid"]);

	require_lib("validate");
	$v = new validate ();
	if (!$v->isOk ($purid, "num", 1, 20, "")) {
		return false;
	}
	
	$sql = "SELECT * FROM cubit.purchases WHERE purid = '$purid'";
	if (!($purRslt = db_exec($sql))) return false;

	if (pg_num_rows($purRslt) < 1) {
		return false;
	}

	$pur = pg_fetch_array($purRslt);

	if ($purstatus == "d") {
		print "Denying purchase: $purid\n";

		if ($pur['received'] == "y"){
			return false;
		}

		$sql = "SELECT * FROM cubit.pur_items  WHERE purid = '$purid'";
		if (!($stktRslt = db_exec($sql))) return false;

		while($stkt = pg_fetch_array($stktRslt)) {
			print "\tRemoving Item: $stkt[stkid]\n";
			$sql = "UPDATE cubit.stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]'";
			if (!db_exec($sql)) return false;

			$sql = "INSERT INTO cubit.pur_canc_items (purid, whid, stkid, qty, ddate, div, qpack, upack, ppack, svat, rqty, tqty, unitcost, amt, iqty, vatcode, description, account)
					VALUES ('$stkt[purid]', '$stkt[whid]', '$stkt[stkid]', '$stkt[qty]', '$stkt[ddate]', '$stkt[div]', '$stkt[qpack]', '$stkt[upack]', '$stkt[ppack]', '$stkt[svat]', '$stkt[rqty]', '$stkt[tqty]', '$stkt[unitcost]', '$stkt[amt]', '$stkt[iqty]', '$stkt[vatcode]', '$stkt[description]', '$stkt[account]')";
			if (!db_exec($sql)) return false;
		}

		$sql = "DELETE FROM cubit.purchases WHERE purid='$purid'";
		if (!db_exec($sql)) return false;

		$sql = "INSERT INTO cubit.cancelled_purch(purid, deptid, supid, supaddr, terms, pdate, ddate, remarks, received, refno, vatinc, prd, ordernum, part, div, purnum, edit, supname, supno, shipchrg, subtot, total, balance, vat, supinv, apprv, appname, rvat, rshipchrg, rsubtot, rtotal, jobid, jobnum, toggle, cash, shipping, invcd, rshipping, noted, returned, iamount, ivat, delvat, username) VALUES('$pur[purid]', '$pur[deptid]', '$pur[supid]', '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[remarks]', '$pur[received]', '$pur[refno]', '$pur[vatinc]', '$pur[prd]', '$pur[ordernum]', '$pur[part]', '$pur[div]', '$pur[purnum]', '$pur[edit]', '$pur[supname]', '$pur[supno]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '$pur[balance]', '$pur[vat]', '$pur[supinv]', '$pur[apprv]', '$pur[appname]', '$pur[rvat]', '$pur[rshipchrg]', '$pur[rsubtot]', '$pur[rtotal]', '$pur[jobid]', '$pur[jobnum]', '$pur[toggle]', '$pur[cash]', '$pur[shipping]', '$pur[invcd]', '$pur[rshipping]', '$pur[noted]', '$pur[returned]', '$pur[iamount]', '$pur[ivat]', '$pur[delvat]', '$userinfo[username]')";
		if (!db_exec($sql)) return false;

		$msg = "Purchase nr. $pur[purnum] denied.";

		print "Purchase denied\n";
	} else if ($purstatus == "a") {
		$msg = "Purchase nr. $pur[purnum] accepted. You should receive further information from supplier.";
	} else if ($purstatus == "i") {
		$msg = "Purchase nr. $pur[purnum] accepted and invoiced. You should receive further information/an invoice from supplier.";
	}

	print "Sending msg: $msg to user: $userinfo[username]\n";
	msgSend($userinfo["username"], $msg);
}

?>

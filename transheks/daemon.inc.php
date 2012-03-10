<?

/* regular expressions */
$CHRS = "a-zA-Z0-9-_ ";

/* body fields */
define("REGEX_REQTYPE", "/^trh_([a-z]{6}) ([a-zA-Z0-9]{32})/");
define("REGEX_COMPNAME", "/^compname: (.*)\$/");
define("REGEX_TELEPHONE", "/^bustel: (.*)\$/");
define("REGEX_IPADDR", "/^ipaddr: ([0-9.]*)\$/");
define("REGEX_MYKEY", "/^mykey: ([a-zA-Z0-9]{32})\$/");
define("REGEX_YOURKEY", "/^yourkey: ([a-zA-Z0-9]{32})\$/");
define("REGEX_FROMWHO", "/^fromwho: (supp|cust)\$/");
define("REGEX_PURID", "/^purid: ([0-9.]*)\$/");
define("REGEX_PURSTATUS", "/^status: ([dai])\$/");

//define("REGEX_FROMADDR", "/^(?:(?:[$CHRS]+|\"[^\"]+\") <)?([$CHRS]+\@(?:[$CHRS]+)+)>?/");
define("REGEX_FROMADDR", "/^(?:(?:[$CHRS]+|\"[^\"]+\") <)?([^>]+)>?/");

/**
 * retrieves information by regex from the messages (headers/body)
 *
 * if regex matches it will return what is in the first ()
 *
 * @param string $regex
 * @param clsMailMsg $oMSG
 * @param bool $header should be scanned from specified header rather
 * @return string
 */
function getfrommmsg($regex, $oMSG, $header = false) {
	/* header match */
	if ($header !== false) {
		if (isset($oMSG->headers[$header])) {
			if (preg_match($regex, $oMSG->headers[$header], $m)) {
				return $m[1];
			}
		}

		return false;
	}

	/* body match */
	if (!is_array($oMSG->parts)) {
		$oMSG->parts = array($oMSG->body);
	}

	$oBP = new clsMailMsg();
	$oBP->processMessage(implode("\n", $oMSG->parts[0]));
	if (isset($oBP->headers["Content-Transfer-Encoding"]) && $oBP->headers["Content-Transfer-Encoding"] == "base64") {
		$bp = explode("\n", base64_decode(implode("\n", $oBP->body)));
	} else {
		$bp = $oBP->body;
	}

	foreach ($bp as $l) {
		$l = trim($l);

		if (preg_match($regex, $l, $m)) {
			return $m[1];
		}
	}

	return false;
}

/**
 * locates a customer by name, if not found creates and returns id
 *
 * @param string $name company name
 * @return int
 */
function locateCustomer($name) {
	$qry = new dbSelect("customers", "cubit", grp(
		m("cols", "cusnum"),
		m("where", "lower(surname)=lower('$name')")
	));
	$qry->run();

	if ($qry->num_rows() > 0) {
		$id = $qry->fetch_result();
	} else {
		// insert into new custs
		$id = -1;
	}

	$qry->free();
	return $id;
}

/**
 * locates a supplier by name, if not found creates and returns id
 *
 * @param string $name company name
 * @return int
 */
function locateSupplier($name) {
	$qry = new dbSelect("suppliers", "cubit", grp(
		m("cols", "supid"),
		m("where", "lower(supname)=lower('$name')")
	));
	$qry->run();

	if ($qry->num_rows() > 0) {
		$id = $qry->fetch_result();
	} else {
		// insert into new supps
		$id = -1;
	}

	$qry->free();
	return $id;
}

/**
 * returns the standard variables from message
 *
 * @param $oMSG
 * @return array
 */
function msg_std($oMSG) {
	/* get the company name */
	if (($compname = getfrommmsg(REGEX_COMPNAME, $oMSG)) === false) {
		print "msgstd err: company name\n";
		return false;
	}

	/* get the intro ip name */
	if (($ipaddr = getfrommmsg(REGEX_IPADDR, $oMSG)) === false) {
		print "msgstd err: ip address\n";
		return false;
	}

	/* get the telephone number */
	if (($bustel = getfrommmsg(REGEX_TELEPHONE, $oMSG)) === false) {
		print "msgstd err: business telephone\n";
		return false;
	}

	/* get from who (supp/cust) request came */
	if (($fromwho = getfrommmsg(REGEX_FROMWHO, $oMSG)) === false) {
		print "msgstd err: from who\n";
		return false;
	}

	/* get from email address */
	if (($email = getfrommmsg(REGEX_FROMADDR, $oMSG, "From")) === false) {
		print "msgstd err: email\n";
		return false;
	}

	return array(
		$compname,
		$ipaddr,
		$bustel,
		$fromwho,
		$email
	);
}

/* receive order: function to process start elements */
function stElement($parser, $name, $pattrs) {
	global $reqpur_activetag, $purch_info, $purch_items;
	array_push($reqpur_activetag, $name);

	$name = strtolower($name);

	$attrs = array();
	foreach ($pattrs as $k => $v) {
		$attrs[strtolower($k)] = $v;
	}

	if ($name == "purdata") {
		$purch_info = $attrs;
	} else if ($name = "puritem") {
		if (is_null($purch_items)) {
			$purch_items = array();
		}

		$purch_items[] = $attrs;
	}
}

/* receive order: function to process end elements */
function endElement($parser, $name) {
	global $reqpur_activetag;
	array_pop($reqpur_activetag);
}

?>
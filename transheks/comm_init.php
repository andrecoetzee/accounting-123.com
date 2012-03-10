<?

require("../settings.php");

$frm = new cForm();

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "enter";
}

switch ($_REQUEST["key"]) {
	case "request":
		$OUTPUT = request($frm);
		break;

	case "confirm":
		$OUTPUT = confirm($frm);
		break;

	case "enter":
	default:
		$OUTPUT = enter($frm);
		break;
}

parse();

/**
 * entry function, gathers information
 */
function enter($frm) {
	$trhconf = getTrhConfig();
	if ($trhconf["MANAGEUSER"] <= 0) {
		r2sListSet("trh_comminit");
		header("Location: configuration.php");
		exit;
	}
	
	if (isset($_REQUEST["suppid"])) {
		$sc_desc = "Supplier";
		$sc_fld = "suppid";
	} else {
		$sc_desc = "Customer";
		$sc_fld = "custid";
	}

	$frm->setkey("confirm");
	$frm->settitle("Initialize Transheks Communications");
	$frm->add_heading("$sc_desc Information");
	$frm->add_hidden($sc_fld, $_REQUEST[$sc_fld], "int");
	$frm->add_text("$sc_desc Transheks Email Address", "email", "", "email", "1:255");

	$OUT = $frm->getfrm_input();

	return $OUT;
}

function confirm($frm) {
	if ($frm->validate("confirm")) {
		return enter($frm);
	}

	$frm->setkey("request");
	$frm->settitle("Initialize Transheks Communications");
	$OUT = $frm->getfrm_input();

	return $OUT;
}

function request($frm) {
	if (isset($_POST["btn_back"])) {
		return enter($frm);
	}

	if ($frm->validate("request")) {
		return confirm($frm);
	}

	$newkey = genkey();

	if (isset($_REQUEST["suppid"])) {
		$suppid = $_REQUEST["suppid"];
		$custid = "0";
	} else {
		$custid = $_REQUEST["custid"];
		$suppid = "0";
	}

	$cols = grp(
		m("introtime", raw("CURRENT_TIMESTAMP")),
		m("introip", "0.0.0.0"),
		m("email", $_REQUEST["email"]),
		m("custid", $custid),
		m("suppid", $suppid),
		m("key", dbrow(
			"0.0.0.0/0",
			"",
			$newkey
		)),
		m("userid", USER_ID)
	);

	$upd = new dbUpdate("keys", "trh", $cols);
	$upd->run(DB_INSERT);

	if ($upd->affected() > 0) {
		if (isset($_REQUEST["suppid"])) {
			if (($r = send_trhmsg("supp", $_REQUEST["suppid"], $_REQUEST["email"], "reqkey", $newkey)) === true) {
				$OUT = "Sent request for communication to supplier. On response you will be notified.";
			} else {
				$OUT = "Error sending request for communication: $r";
			}				
		} else {
			if (($r = send_trhmsg("cust", $_REQUEST["custid"], $_REQUEST["email"], "reqkey", $newkey)) === true) {
				$OUT = "Sent request for communication to customer. On response you will be notified.";
			} else {
				$OUT = "Error sending request for communication: $r";
			}				
		}
	} else {
		$OUT = "Error sending request for communication: Error updating database.";
	}

	return $OUT;
}
?>
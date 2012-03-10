<?

require("../settings.php");

switch ($_REQUEST["key"]) {
	case "send":
	default:
		$OUTPUT = send();
		break;
}

parse();

function send() {
	if (!isset($_REQUEST["id"])) {
		invalid_use();
	}

	/* fetch purchase information */
	$purchase = new dbSelect("purchases", "cubit", grp(
		m("where", "purid='$_REQUEST[id]'")
	));
	$purchase->run();

	if ($purchase->num_rows() <= 0) {
		invalid_use("Invalid purchase.");
	}

	$purdata = $purchase->fetch_array();

	/* fetch the transheks email address of this supplier */
	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "email, (key).send_key AS send_key"),
		m("where", "suppid='$purdata[supid]'")
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("This supplier isn't configured for Transheks transactioning.");
	}

	$keyinfo = trhKeySupp($purdata["supid"]);
	$email = $keyinfo["email"];
	$send_key = $keyinfo["send_key"];

	if (empty($send_key)) {
		invalid_use("This supplier hasn't confirmed the Transactioning request sent.");
	}

	/* fetch purchase item information */
	$puritems = array();
	$purchase->setTable("pur_items", "cubit");
	$purchase->run();

	if ($purchase->num_rows() <= 0) {
		invalid_use("Invalid purchase, purchase has no items.");
	}

	while ($row = $purchase->fetch_array()) {
		$puritems[] = $row;
	}

	/* build xml data */
	$XML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

	$attrs = array();
	foreach ($purdata as $k => $v) {
		$attrs[] = "$k=\"$v\"";
	}

	$XML .= "<purdata ".implode(" ", $attrs).">\n";

	foreach ($puritems as $puritem_data) {
		$attrs = array();
		foreach ($puritem_data as $k => $v) {
			$attrs[] = "$k=\"$v\"";
		}

		$XML .= "\t<puritem ".implode(" ", $attrs)." />\n";
	}

	$XML .= "</purdata>\n";

	$OUT = "<h3>Send Supplier Order</h3>";
	if (($ret = send_trhmsg("supp", $purdata["supid"], $email, "reqpur", $XML)) !== true) {
		if ($ret === false) {
			$OUT .= "<li class='err'>There was an unknown error sending order to supplier.</li>";
		} else {
			$OUT .= "<li class='err'>Error sending order to supplier: $ret.</li>";
		}
	} else {
		$OUT .= "Successfully sent order to supplier.";
	}

	return $OUT;
}

?>
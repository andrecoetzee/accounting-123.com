<?

require("settings.php");

$frm = new cForm();

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "enter";
}

switch ($_REQUEST["key"]) {
	case "write":
		$OUTPUT = write($frm);
		break;
	case "confirm":
		$OUTPUT = confirm($frm);
		break;
	case "enter":
	default:
		$OUTPUT = enter($frm);
}

parse();

function enter(&$frm)
{

	if (!isset($_REQUEST["id"])) {
		invalid_use();
	}

	$frm->settitle("Supplier Stock Codes");
	$frm->setmsg("Please enter the stock codes each of your suppliers use for the
		selected stock item.<br /><br />
		<li class='err'>To remove an item from supplier, simply leave field blank.</li>");

	$frm->setkey("confirm");
	$frm->add_hidden("id", $_REQUEST["id"], "num");

	$qry = new dbSelect("suppliers", "cubit");
	$qry->run();

	$frm->add_heading("Details");
	while ($si = $qry->fetch_array()) {
		$stkcod = suppStkcod($si["supid"], $_REQUEST["id"]);
		$stkdes = suppStkdes($si["supid"], $_REQUEST["id"]);
		$supdisp = "($si[supno]) $si[supname]";

		$frm->add_text($supdisp, "stkcod[$si[supid]]", $stkcod, "string", "0:50");
		$frm->add_text("Description", "stkdes[$si[supid]]", $stkdes, "string", "0:50");
	}

	return $frm->getfrm_input();
}

function confirm(&$frm) {
	if ($frm->validate("confirm")) {
		return enter($frm);
	}

	$frm->setkey("write");
	return $frm->getfrm_input();
}

function write(&$frm) {
	if (isset($_REQUEST["btn_back"])) {
		return enter($frm);
	}

	if ($frm->validate("write")) {
		return confirm($frm);
	}

	$upd = new dbUpdate("suppstock", "cubit");
	$del = new dbDelete("suppstock", "cubit");
	foreach ($_REQUEST["stkcod"] as $suppid => $stkcod) {
		if (empty($stkcod)) {
			$del->setOpt("suppid='$suppid' AND stkid='$_REQUEST[id]'");
			$del->run();
			continue;
		}

		$cols = grp(
			m("suppid", $suppid),
			m("stkid", $_REQUEST["id"]),
			m("stkcod", $stkcod)
		);

		$upd->setOpt($cols, "suppid='$suppid' AND stkid='$_REQUEST[id]'");
		$upd->run(DB_REPLACE);
	}

	foreach ($_REQUEST["stkdes"] as $suppid => $stkdes) {
		if (empty($stkcod)) {
			$del->setOpt("suppid='$suppid' AND stkid='$_REQUEST[id]'");
			$del->run();
			continue;
		}

		$cols = grp(
			m("suppid", $suppid),
			m("stkid", $_REQUEST["id"]),
			m("stkdes", $stkdes)
		);

		$upd->setOpt($cols, "suppid='$suppid' AND stkid='$_REQUEST[id]'");
		$upd->run(DB_REPLACE);
	}

	$OUT = "<script>window.close();</script>";

	return $OUT;
}

?>

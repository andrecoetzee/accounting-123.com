<?

require("settings.php");

cFramework::run("edit");
cFramework::parse();

function edit(&$frm) {
	/* @var $frm cForm */
	$frm->setkey("confirm");
	$frm->settitle("Point of Sale Settings");
	$frm->add_heading("Point of Sale Slips/Printing");
	
	/* point of sale message at the bottom of the slip */
	$posmsg = getCSetting("POSMSG");
	$frm->add_textarea("Message to Display at bottom of Slip", "posmsg", $posmsg, "1:255");
	
	return $frm->getfrm_input();
}

function confirm(&$frm) {
	/* @var $frm cForm */
	if ($frm->validate("confirm")) {
		return edit($frm);
	}
	
	$frm->setkey("write");
	return $frm->getfrm_input();
}

function write($frm) {
	/* @var $frm cForm */
	if ($frm->validate("confirm")) {
		return edit($frm);
	}
	
	/* point of sale message at the bottom of the slip */
	$cols = grp(
		m("value", $_POST["posmsg"])
	);
	$upd = new dbUpdate("settings", "cubit", $cols, "constant='POSMSG'");
	$upd->run(DB_UPDATE);
	
	$OUT = "
	<h3>Point of Sale Settings</h3>
	
	Successfully updated.<br />";
	
	return $OUT;
}
<?

require("../settings.php");

cFramework::run("edit");
cFramework::parse();

function edit(&$frm) {
	/* @var $frm cForm */
	$frm->setkey("write");
	$frm->settitle("Purchase Default VAT Setting");
	$frm->add_heading("Setting");
	
	$yn = array(
		"yes" => "VAT Inclusive",
		"no" => "VAT Exclusive"
	);
	
	$vat_setting = getCSetting("PURCH_DEFAULT_VAT_SETTING");
	$frm->add_select("Default Stock Purchase VAT Setting", "vat_setting", $vat_setting, $yn, "string", "2:3");
	$frm->add_ctrlbtn("Save", "submit", "btn_submit");

	return $frm->getfrm_input();
}

function write(&$frm) {
	if ($frm->validate("write")) {
		return edit($frm);
	}
	
	$cols = grp(
		m("value", $_POST["vat_setting"])
	);
	
	$qry = new dbUpdate("settings", "cubit", $cols, "constant='PURCH_DEFAULT_VAT_SETTING'");
	$qry->run(DB_UPDATE);
	
	$OUT = "
	<h3>Purchase VAT Setting</h3>
	Successfully saved setting.";
	
	return $OUT;
}

?>
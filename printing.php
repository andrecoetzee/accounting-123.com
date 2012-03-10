<?

require("settings.php");

cFramework::run("edit");
cFramework::parse();

function edit(&$frm) {
	/* @var $frm cForm */
	$frm->setkey("write");
	$frm->settitle("Printing Options");
	$frm->add_heading("Details");
	
	$yn = array(
		"y" => "Yes",
		"n" => "No"
	);
	
	$print_dialog = getCSetting("PRINT_DIALOG");
	$frm->add_select("Automatically Display Print Dialog", "print_dialog", $print_dialog, $yn, "string", "1:1");
	$frm->add_ctrlbtn("Save", "submit", "btn_submit");
	
	return $frm->getfrm_input();
}

function write(&$frm) {
	if ($frm->validate("write")) {
		return edit($frm);
	}
	
	$cols = grp(
		m("value", $_POST["print_dialog"])
	);
	
	$qry = new dbUpdate("settings", "cubit", $cols, "constant='PRINT_DIALOG'");
	$qry->run(DB_UPDATE);
	
	$OUT = "
	<h3>Printing Options</h3>
	Successfully saved printing options.";
	
	return $OUT;
}

?>
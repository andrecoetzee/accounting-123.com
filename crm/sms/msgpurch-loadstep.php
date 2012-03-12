<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

require("../../settings.php");
require("../https_urlsettings.php");

if ( ! isset($_GET["step"]) ) {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
	require("../../template.php");
}

$OUTPUT = choose_step();

require("../../template.php");

function choose_step() {
	global $_GET;
	extract($_GET);

	if ( isset($id) ) {
		require_lib("validate");
		$v = & new Validate();

		if ( ! $v->isOk($id, "num", 1, 9, "") )
			return "<li class=err>Invalid site entry id</li>";
	}

	$step = 1;

	switch($step) {
	case "0":
		if ( ! isset($msg) ) $msg = "";
		$OUTPUT = "$msg";
		break;

	case "1":
		$OUTPUT = "<script>document.location.href='".urler(PURCHASE_URL."?".sendhash())."';</script>";
		break;
	}

	return $OUTPUT;
}

?>

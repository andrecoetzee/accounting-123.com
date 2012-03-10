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

if ( ! isset($HTTP_GET_VARS["step"]) ) {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
	require("../../template.php");
}

$OUTPUT = choose_step();

$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
        </table>";


require("../../template.php");

function choose_step() {
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

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
		$request = @file(urler(REPORTS_URL."?".sendhash()));

		if ( $request == false ) {
			$site_msg = "<li class=err>Connection to server failed. Check you internet connection and try again.</li>";
			return $site_msg;
		}

		$OUTPUT = implode("", $request);
		break;
	}

	return $OUTPUT;
}

?>

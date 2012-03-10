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

//sendhash();

// secure
define("IDENTIFY_URL", "https://www.cubit.co.za/cubitnet/https_auth.php");
define("SETTINGS_URL", "https://www.cubit.co.za/cubitnet/web_settings.php");
define("PURCHASE_URL", "https://www.cubit.co.za/cubitnet/sms_purchase.php");
define("COMPINFO_URL", "https://www.cubit.co.za/cubitnet/compinfo_submit.php");

// insecure
define("UPDATE_URL", "http://www.cubit.co.za/cubitnet/update_cubit.php");
define("REPORTS_URL", "http://www.cubit.co.za/cubitnet/sms_reports.php");
define("GENERALMSG_URL", "http://www.cubit.co.za/cubitnet/sms_general.php");
define("READCREDITS_URL", "http://www.cubit.co.za/cubitnet/sms_readcredits.php");
define("BLACKLIST_SEARCH_URL", "http://www.cubit.co.za/cubitnet/bwlist_search.php");
define("BLACKLIST_SUBMIT_URL", "http://www.cubit.co.za/cubitnet/bwlist_submit.php");
define("BULKMSGS_URL", "http://www.cubit.co.za/cubitnet/sms_bulk.php");

function sendhash() {
	db_conn("cubit");

	$sql = "SELECT setting_value FROM cubitnet_settings WHERE setting_name='cubitnet_hash'";
	$rslt = db_exec($sql) or errDie("Error reading hash.");

	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT = "<li class=err>Cubit Internet Settings not set up yet.</li>";

		// lets do the template! bwahahahahhahahahahha->he_is_looking(hide==false?"die":"sleep");
		if ( is_file("template.php") ) require("template.php");
		if ( is_file("../template.php") ) require("../template.php");
		if ( is_file("../../template.php") ) require("../../template.php");
	} else {
		$hash = pg_fetch_result($rslt, 0, 0);
		return "fhash=" . substr( $hash, 0, 32 );
	}
}

function urler($value) {
	return str_replace(" ", "%20", $value);
}

?>

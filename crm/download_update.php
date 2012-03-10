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
require("settings.php");
require("https_urlsettings.php");

db_conn_maint("cubit");
$sql = "SELECT * FROM version";
$rslt = db_exec($sql) or errDie("Error fetching version info.");

$version = pg_fetch_result($rslt,0, 0);

// post the search request
$update_request = @file(urler(UPDATE_URL."?version=$version&".sendhash()));

if ( $update_request == false ) {
	$site_msg = "<li class=err>Connection to server failed. Check you internet connection and try again.</li>";
} else {
	$site_msg = implode("<br>", $update_request);
}

$OUTPUT = "
<h3>Cubit Update Download</h3>
$site_msg";

require("template.php");

?>

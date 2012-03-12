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

if ( isset($_POST) && is_array($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_GET[$key] = $value;
	}
}

if ( isset($_GET["key"]) ) {
	switch ( $_GET["key"] ) {
		default:
			$OUTPUT = view();
	}
} else {
	$OUTPUT = view();
}

print "<html>$OUTPUT</html>";

function view() {
	global $_GET;
	extract($_GET);

	if ( ! isset($target) ) {
		$OUTPUT = "<li class=err>Invalid use of module</li>";

		require("template.php");
	}

	$vars = "";
	foreach($_GET as $key => $value ){
		if ( $key != "target" ) $vars .= "&$key=$value";
	}

	// compute the auth url
	db_conn("cubit");
	$sql = "SELECT * FROM cubitnet_sitesettings WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading username and password for Cubit.co.za");

	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT = "<li class=err>Cubit.co.za Settings not set up yet.
			Please <a href='cubitnet_settings.php'>enter</a> the settings first</li>";

		require("template.php");
	}

	extract( pg_fetch_array($rslt) );

	db_conn("cubit");
	$sql = "SELECT setting_value FROM cubitnet_settings WHERE setting_name='cubitnet_hash'";
	$rslt = db_exec($sql) or errDie("Error reading hash value for Cubit.co.za.");

	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT = "<li class=err>Cubit.co.za Settings not set up yet.
			Please <a href='cubitnet_settings.php'>enter</a> the settings first</li>";

		require("template.php");
	}

	$cubitnet_hash = pg_fetch_result($rslt, 0, 0);

	print "
	<frameset rows=0,* border=0>
		<frame name=https_auth src='".IDENTIFY_URL."?hash=$cubitnet_hash&username=$cn_username&password=$cn_password'>
		<frame name=data_frame src='$target?$vars'>
	</frameset>";
}

?>

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

# Get settings
require("../settings.php");
require("../libs/ext.lib.php");

	global $_GET;
	extract ($_GET);

	if(!isset($calloutid) OR (strlen($calloutid) < 1)){
		return "Invalid use of module";
	}

	db_connect ();

	$sqlpic = "SELECT image,image_type FROM callout_docs_scanned WHERE calloutid = '$calloutid' AND div = '".USER_DIV."' LIMIT 1";
	$imgRsltpic = db_exec ($sqlpic) or errDie ("Unable to retrieve image from database");
	$imgBin = pg_fetch_array($imgRsltpic);

	$img = base64_decode($imgBin["image"]);
	$mime = $imgBin["image_type"];

	header ("Content-Type: ". $mime ."\n");
	header ("Content-Transfer-Encoding: binary\n");
	header ("Content-length: " . strlen ($img) . "\n");

	//send file contents
	print $img;




?>

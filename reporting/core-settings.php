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
# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "core-settings.php") {
	exit;
}
core_connect("core");
$sql = "SELECT * FROM active";
$rslt = db_exec($sql);
$rows = pg_numrows($rslt);
if(empty($rows)){
$OUTPUT = "<center>ERROR : There Current Period is not Selected Yet. You Cannot continue without Selecting a period";
require("template.php");
}
$act = Pg_fetch_array($rslt);
define ("PRD_DB", $act['prddb']);
define ("YR_DB", $act['yrdb']);
define ("PRD_NAME", $act['prdname']);
define ("YR_NAME", $act['yrname']);
?>

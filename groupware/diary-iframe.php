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

require ("../settings.php");

$get = "";
$i = 0;
foreach ($_GET as $key=>$value) {
	if ($i) {
		$get .= "&";
	}
	$i++;
	
	$get .= "$key=$value";
}

print "<iframe class='diary_frameset' src='diary-index.php?$get'></iframe>";

?>
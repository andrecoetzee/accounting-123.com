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

if (isset($_GET["script"])) {
	print iframe($_GET["script"]);
}

function iframe($script)
{
	$get = "";
	$i = 0;
	foreach ($_GET as $key=>$value) {
		if ($i) {
			$get .= "&";
		}
		$i++;

		$get .= "$key=$value";
	}

	return "<iframe class='diary_frameset' id='diary_frameset' src='$script?$get'></iframe>";
}
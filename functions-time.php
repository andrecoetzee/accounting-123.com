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

$fname = "groupware/diary-functions.php";

if (is_file($fname)) {
	include($fname);
} else if (is_file("../$fname")) {
	include("../$fname");
} else if (is_file("../../$fname")) {
	//include("../../$fname");
} else if (is_file("../../../$fname")) {
	//include("../../../$fname");
}

?>

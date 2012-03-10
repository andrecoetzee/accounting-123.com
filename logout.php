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

##
# logout.php :: User session logout.
##
session_name ("CUBIT_SESSION");
session_start ();
session_unset ();

$OUTPUT = "
<html>
<head>
<title>Logged out</title>
<style type='text/css'>
<!--
	body
	{
		font-family: sans-serif;
		font-size: 10pt;
	}
-->
</style>
</head>

<body bgcolor='#FFFFFF' text='#000000'>
<h3>Logged out</h3>
<a target='_top' href=index.php class=nav>Click here to log in as a different user</a>.
<br>(Please close Cubit to ensure that no one else logs in as you.)

</body>
</html>";

	print $OUTPUT;
?>

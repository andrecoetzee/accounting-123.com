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

require ("settings.php");

$OUTPUT = "
<br>
<center>
<h3>Accounts</h3>
<br>
<a href='acc-new.php' class=nav> Add New Account</a>
<p>
<a href='acc-view.php' class=nav> View Accounts </a>
<p>
<br>
<h3>Account Catagories</h3>
<br>
<a href='accat-new.php' class=nav> Add New Accounts Category </a>
<p>
<a href='accat-view.php' class=nav> View Accounts Catagories </a>
<p>
</center>
";

require ("template.php");
?>

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

$OUTPUT = "
<center>
<h3>Settings</h3>
<b><a href='cat-add.php' class=nav>Add Category</a></b>
<p>
<b><a href='cat-view.php' class=nav>View Categories</a></b>
<p>
<b><a href='class-add.php' class=nav>Add Classification</a></b>
<p>
<b><a href='class-view.php' class=nav>View Classifications</a></b>
<p>
<b><a href='pricelist-add.php' class=nav>Add Price List</a></b>
<p>
<b><a href='pricelist-view.php' class=nav>View Price Lists</a></b>
<p>
<p>
<b><a href='index.php' class=nav>Index</a></b>
</center>";

require ("../template.php");
?>

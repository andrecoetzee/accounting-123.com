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

db_connect ();

$OUTPUT =
	"<br>
	<center>
	<h4>Salaries Settings</h4>
	<p><a href='../core/sal-link.php?type=E&payname=salaries' class=nav>Add Salary Account Link</a>
	<p><a href='../core/sal-link.php?type=B&payname=salaries control' class=nav>Add Salaries Control Account link</a>
	<p><a href='../core/sal-link.php?type=E&payname=Commission' class=nav>Add Commission Account link</a>
	<p><a href='../core/sal-link.php?type=B&payname=PAYE' class=nav>Add PAYE Account link</a>
	<p><a href='../core/sal-link.php?type=B&payname=UIF' class=nav>Add UIF Account Link</a>
	<p><a href='../core/sal-link.php?type=B&payname=loanacc' class=nav>Add Employee Loan Account Link</a>
	<p><a href='../core/sal-link.php?type=E&payname=uifexp' class=nav>Company UIF expense Account link</a>
	<p><a href='../core/sal-link.php?type=B&payname=uifbal' class=nav>Company UIF Control Account link</a>
	<p><a href='../core/sal-link.php?type=E&payname=sdlexp' class=nav>Company SDL expense Account link</a>
        <p><a href='../core/sal-link.php?type=B&payname=sdlbal' class=nav>Company SDL Control Account link</a>


	<!--<p><a href='../core/sal-link.php?type=I&payname=fringben' class=nav>Add Fringe Benefits Account Link</a>-->
	<p>
	<br>
	<a href='settings-acc-edit.php' class=nav>General settings</a>"
	.mkQuickLinks(
		ql("settings-acc-edit.php", "General Settings"),
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

        require ("../template.php");
?>

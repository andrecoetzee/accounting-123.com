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
if (basename (getenv ("SCRIPT_NAME")) == "crm.lib.php") {
	exit;
}

//Add default teams
function dt() {
	db_conn('crm');

	$Sl="INSERT INTO teams (name,div) VALUES ('Sales','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Support','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Accounts','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Company Relations','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");
	
	$Sl="INSERT INTO teams (name,div) VALUES ('Purchasing - Supplier Relations','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");
}

//Add default token categories
function dc() {
	db_conn('crm');

	$Sl="INSERT INTO tcats (name,div) VALUES ('Product Enquiries','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Place an Order','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Complain','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Account querries','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Delivery or Installation Tracking','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Comment on good service or Remarks','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Ask about employment','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('General','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Potential Supplier','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Product Support','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

}

?>

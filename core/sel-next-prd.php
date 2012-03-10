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
if (basename (getenv ("SCRIPT_NAME")) == "sel-next-prd.php") {
	exit;
}

	// get active period and database records
	core_connect("core");
	$sql = "SELECT * FROM active";
	$rslt = db_exec($sql);
	if(pg_numrows($rslt) < 1){
		$OUTPUT = "<center>ERROR : There Current Period/Year is not Selected Yet. You Cannot continue without Selecting a period";
		require("template.php");
	}
	$active = Pg_fetch_array($rslt);

	// check if its december
	if($active['prddb'] == 12){
		$nxprd = 1;
	}else{
		$nxprd = ($active['prddb'] + 1);
	}

	// Get range
	core_connect();
	$sql = "SELECT * FROM range";
	$Rslt = db_exec($sql);
	if(pg_numrows($Rslt) < 1){
		$OUTPUT = "<center><li class=err>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.";
		require("template.php");
	}
	$range = Pg_fetch_array($Rslt);

	// the f***** year is over B***** !!
	if($nxprd == $range['start']){

		db_connect();
		$sql = "Insert into set(label) values('YRCLOSE')";
		$rslt = db_exec($sql) or errDie("Could not Set Next Period Database",SELF);

		$ERROR = "( i )<li>You have reached the end of the current Financial. you have to close a financial year before you continue.( i )";
	}else{
		// Months array
		$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

		// Update Active Period Db and name
		db_conn("core");
		$sql = "UPDATE active SET prddb = '$nxprd', prdname = '$months[$nxprd]'";
		$rslt = db_exec($sql) or errDie("Could not Set Next Period Database",SELF);
		$ERROR = "( i ) The next period has been activated ( i )";
	}
?>

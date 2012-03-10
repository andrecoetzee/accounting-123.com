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
if (basename (getenv ("SCRIPT_NAME")) == "set-settings.php") {
	exit;
}

db_connect();
// Cubit settings
$sql ="SELECT * FROM set WHERE label != 'ACCNEW_LNK' AND div = '".USER_DIV."'";
$rslt = db_exec($sql);
if(pg_numrows($rslt) > 0){
	while($set = pg_fetch_array($rslt)){
		if(!defined("$set[label]"))
			define("$set[label]", $set['value']);
	}
}

// Cubit account creation settings
db_connect();
$sql ="SELECT * FROM set WHERE label = 'ACCNEW_LNK'";
$rslt = db_exec($sql);
if(pg_numrows($rslt) > 0){
	$set = pg_fetch_array($rslt);
	define('ACCNEW_LNK', $set['value']);
}else{
	$OUTPUT = "<center><li> Please select account creation method first on admin settings.
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	require("template.php");
}
?>

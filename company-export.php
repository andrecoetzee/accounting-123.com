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
require ("psql_path.php");

$OUTPUT = "";

if ( ! isset($_GET["code"]) ) {
	# Set up table to display in
	$OUTPUT = "<h3>Select Company to Export</h3>";

	# connect to database
	db_conn_maint ("cubit");

	# Query server
	$i = 0;
	$sql = "SELECT code,name FROM companies ORDER BY name ASC";
	$compRslt = db_exec ($sql) or errDie ("Unable to retrieve companies from database.");

	if (pg_numrows ($compRslt) < 1) {
		return "<li>There are no companies in Cubit.";
	}

	$OUTPUT .= "
		<form method=GET action='".SELF."'>
		<select name=code>";

	while ( $row = pg_fetch_array($compRslt) ) {
		$OUTPUT .= "<option value=$row[code]>$row[name]</option>'";
	}

	$OUTPUT .= "</select>
		<input type=submit value='Export'>
		</form>";

	$OUTPUT .= "<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
	<tr><td><br></td></tr>
	<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='company-import.php'>Import Company</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='company-new.php'>Add Company</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	require ("template.php");
} else {
	require_lib("validate");
	$v = & new Validate();

	$code = $_GET["code"];

        if ( ! $v->isOk($code, "string", 4, 4, "") ) {
		errDie("Invalid company selected");
	}

	db_conn_maint ("cubit");

	# Query server
	$i = 0;
	$sql = "SELECT name,ver FROM companies WHERE code='$code'";
	$compRslt = db_exec ($sql) or errDie ("Unable to retrieve companies from database.");

	if (pg_numrows ($compRslt) < 1) {
		errDie("Invalid company selected");
	}

	$name = pg_fetch_result($compRslt, 0, 0);
	$ver = pg_fetch_result($compRslt, 0, 1);

	$filename = str_replace(" ", "_", $name);
	
	// print the data
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: inline; filename=\"$filename.cmp\"");

    print "-- V'e'r's'i'o'n: $ver\n";
	print "-- P'l'a't'f'o'r'm: " . PLATFORM . "\n\n";
	
	$Sl="SELECT datname FROM pg_stat_database";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	while($data=pg_fetch_array($Ri)) {
		if(substr($data['datname'],-4)!=$code)  {
			continue;
		}
		
		$db=substr($data['datname'],0,-5);
			
		print "CREATE DATABASE \"$db"."_%c'o'd'e%\";\n";
 		print "\\c \"$db"."_%c'o'd'e%\"\n";
 		system("$psql_exec/".PG_DUMP_EXE." -U postgres \"$db"."_$code\"");
 		print "\n\n";
	}
	
}
?>

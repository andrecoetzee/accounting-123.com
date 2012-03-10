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
require ("core-settings.php");

$OUTPUT = print_labels ();

function print_labels ()
{

	db_connect ();

	#get list of suppliers
	$get_supp = "SELECT * FROM suppliers WHERE div = '".USER_DIV."'";
	$run_supp = db_exec($get_supp) or errDie("Unable to get supplier information");
	if(pg_numrows($run_supp) < 1){
		return "No suppliers were found.";
	}else {
		$listing = "";
		while ($arr = pg_fetch_array($run_supp)){
			$listing .= "
$arr[supname]
$arr[supaddr]
\n
\n";
		}
	}




header("Content-Type: application/octet-stream");
header("Content-Disposition: atachment; filename=labels-suppliers.txt");
header("Pragma: no-cache");
header("Expires: 0");
print $listing;

}

?>
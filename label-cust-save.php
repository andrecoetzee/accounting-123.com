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

//require ("tmpl-print.php");

function print_labels ()
{

	db_connect ();

	#get list of customers
	$get_cust = "SELECT * FROM customers WHERE div = '".USER_DIV."'";
	$run_cust = db_exec($get_cust) or errDie("Unable to get customer information");
	if(pg_numrows($run_cust) < 1){
		return "No customers were found.";
	}else {
		$listing = "";
		while ($arr = pg_fetch_array($run_cust)){
			$listing .= "
$arr[surname]
$arr[paddr1]
\n
\n";
		}
	}




header("Content-Type: application/octet-stream");
header("Content-Disposition: atachment; filename=labels-customer.txt");
header("Pragma: no-cache");
header("Expires: 0");
print $listing;

}

?>
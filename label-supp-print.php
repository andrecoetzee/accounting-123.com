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

require ("tmpl-print.php");

function print_labels ()
{

	db_connect ();

	#get list of customers
	$get_cust = "SELECT * FROM suppliers WHERE div = '".USER_DIV."'";
	$run_cust = db_exec($get_cust) or errDie("Unable to get customer information");
	if(pg_numrows($run_cust) < 1){
		return "No suppliers were found.";
	}else {
		$listing = "";
		while ($arr = pg_fetch_array($run_cust)){
			$listing .= "
					<tr>
						<td><font size='3'><b>$arr[supname]</b></font></td>
					</tr>
					<tr>
						<td><font size='2'>".nl2br($arr['supaddr'])."</font></td>
					</tr>
					<tr><td><br></td></tr>
					<tr><td><br></td></tr>
				";
		}
	}

	$display = "
			<table ".TMPL_tblDflts.">
				$listing
			</table>
		";
	return $display;

}

?>
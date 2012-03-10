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

$OUTPUT = printList ();

require ("../template.php");



# show stock
function printList ()
{

	# Set up table to display in
	$printList = "
		<h3>Price Lists</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Price List</th>
				<th colspan='6'>Options</th>
			</tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM pricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
    $listRslt = db_exec ($sql) or errDie ("Unable to retrieve Price Lists from database.");
	if (pg_numrows ($listRslt) < 1) {
		return "<li class='err'>There are no Price Lists in Cubit.</li>";
	}
	while ($list = pg_fetch_array ($listRslt)) {
		$printList .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$list[listname]</td>
				<td><a href='pricelist-det.php?listid=$list[listid]'>Details</a></td>
				<td><a target=_blank href='pricelist-print.php?listid=$list[listid]'>Print</a></td>
				<td><a href='../xls/pricelist-xls.php?listid=$list[listid]'>Export to Spreadsheet</a></td>
				<td><a href='pricelist-edit.php?listid=$list[listid]'>Edit</a></td>
				<td><a href='pricelist-copy.php?listid=$list[listid]'>Copy</a></td>
				<td><a href='pricelist-rem.php?listid=$list[listid]'>Remove</a></td>
			</tr>";
		$i++;
	}

	$printList .= "
		</table>
	    <p>
		<table ".TMPL_tblDflts." width='15%'>
	       <tr><td><br></td></tr>
	        <tr>
	        	<th>Quick Links</th>
	        </tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pricelist-add.php'>Add Price List</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printList;

}


?>
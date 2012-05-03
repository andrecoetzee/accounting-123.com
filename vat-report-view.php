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

$OUTPUT = printCat ();

$OUTPUT .= "<br>".mkQuickLinks(
	ql ("reporting/index-reports.php","Financials")
);
require ("template.php");



function printCat ()
{

	# Set up table to display in
	$printCat = "
		<h3>View Saved VAT Returns</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>VAT 201 Name</th>
				<th>From Date</th>
				<th>To Date</th>
				<th>View</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM saved_vat201 ORDER BY from_date,id ASC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve stock categories from database.");
	if (pg_numrows ($vatRslt) < 1) {
		return "<li class='err'>There are no saved vat 201's in Cubit.</li>";
	}
	while ($vat = pg_fetch_array ($vatRslt)) {

		$printCat .= "
			<tr class='".bg_class()."'>
				<td>$vat[returnname]</td>
				<td align='center'>$vat[from_date]</td>
				<td align='center'>$vat[to_date]</td>
				<td><a href='vat-return-view-return.php?vatid=$vat[id]'>View</a></td>
			</tr>";

	}

	$printCat .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='reporting/vat_return_report.php'>VAT 201</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printCat;

}


?>
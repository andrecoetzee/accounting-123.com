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

# show current stock
$OUTPUT = printCurr ();

require ("template.php");



# show stock
function printCurr ()
{

	# Set up table to display in
	$printCurr = "
		<h3>View Currency</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Code</th>
				<th>Currency Symbol</th>
				<th>Current Rate</th>
				<th colspan='2'>Options</th>
			</tr>";

	# connect to database
	db_conn ("cubit");

	# Query server
	$i = 0;
	$sql = "SELECT * FROM currency ORDER BY fcid ASC";
	$curRslt = db_exec ($sql) or errDie ("Unable to retrieve currency from database.");
	if (pg_numrows ($curRslt) < 1) {
		return "<li class='err'>There are no currency in Cubit.</li>";
	}
	while ($cur = pg_fetch_array ($curRslt)) {

		$printCurr .= "
			<tr class='".bg_class()."'>
				<td align='center'>$cur[descrip]</td>
				<td align='center'>$cur[curcode]</td>
				<td align='center'>$cur[symbol]</td>
				<td align='right'>".CUR." $cur[rate]</td>
				<td><a href='currency-edit.php?fcid=$cur[fcid]'>Edit</a></td><td><a href='currency-rem.php?fcid=$cur[fcid]'>Remove</a></td>
			</tr>";
		$i++;
	}

	$printCurr .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='currency-add.php'>Add Currency</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printCurr;

}


?>
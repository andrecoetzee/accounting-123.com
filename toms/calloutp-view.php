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

# show current stock
$OUTPUT = printCalloutp ();

$OUTPUT .= "<p>".mkQuickLinks(
	ql ("calloutp-add.php","Add Call Out Person")
);

require ("../template.php");



# show stock
function printCalloutp ()
{

	# Set up table to display in
	$printCalloutp = "
		<h3>Call Out People</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Call Out Person</th>
				<th>Contact Number</th>
			</tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
	$sql = "SELECT * FROM calloutpeople WHERE div = '".USER_DIV."' ORDER BY calloutp ASC";
	$calloutpRslt = db_exec ($sql) or errDie ("Unable to retrieve Call Out People from database.");
	if (pg_numrows ($calloutpRslt) < 1) {
		return "<li class='err'>There are no Call Out People in Cubit.</li>";
	}
	while ($calloutp = pg_fetch_array ($calloutpRslt)) {
		$printCalloutp .= "
			<tr class='".bg_class()."'>
				<td>$calloutp[calloutp]</td>
				<td>$calloutp[telno]</td>
				<td><a href='calloutp-edit.php?calloutpid=$calloutp[calloutpid]'>Edit</a></td>
				<td><a href='calloutp-rem.php?calloutpid=$calloutp[calloutpid]'>Remove</a></td>
			</tr>";
		$i++;
	}

	$printCalloutp .= "</table>";
	return $printCalloutp;

}


?>
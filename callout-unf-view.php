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
$OUTPUT = printCallout ();

require ("template.php");

##
# Functions
##

# show callout docs
function printCallout ()
{
	# Set up table to display in
	$printCallout = "
					<h3>Incomplete Call Out Documents</h3>
					<table ".TMPL_tblDflts.">
					<tr>
						<th>Username</th>
						<th>Call Out Person</th>
						<th>Job No.</th>
						<th>Service Date</th>
						<th>Customer Name</th>
						<th colspan='3'>Options</th>
					</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM callout_docs WHERE done = 'n' AND div = '".USER_DIV."' ORDER BY calloutid DESC";
		$calloutRslt = db_exec ($sql) or errDie ("Unable to retrieve callout documents from database.");
		if (pg_numrows ($calloutRslt) < 1) {
			$printCallout = "<li>No incomplete call out documents.";
		}else{
			while ($callout = pg_fetch_array ($calloutRslt)) {
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$callout[cusnum]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$sql = "SELECT * FROM callout_docs_data WHERE calloutid = '$callout[calloutid]' AND div = '".USER_DIV."'";
					$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
					$cust = pg_fetch_array($custRslt);
					$cust['cusname'] = $cust['customer'];
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
// 				db_conn("exten");
// 				$sql = "SELECT * FROM departments WHERE deptid = '$quo[deptid]' AND div = '".USER_DIV."'";
// 				$deptRslt = db_exec($sql);
// 				if(pg_numrows($deptRslt) < 1){
// 					$dept['deptname'] = "<i class=err>Not Found</i>";
// 				}else{
// 					$dept = pg_fetch_array($deptRslt);
// 				}

				# format date
				$callout['odate'] = explode("-", $callout['odate']);
				$callout['odate'] = $callout['odate'][2]."-".$callout['odate'][1]."-".$callout['odate'][0];

				$printCallout .= "
								<tr class='".bg_class()."'>
									<td>$callout[username]</td>
									<td>$callout[calloutp]</td>
									<td>$callout[calloutid]</td>
									<td align='center'>$callout[odate]</td>
									<td>$cust[cusname] $cust[surname]</td>
									<td><a href='callout-new.php?calloutid=$callout[calloutid]&cont=true&letters=&done='>Continue</a></td>
									<td><a href='callout-unf-cancel.php?calloutid=$callout[calloutid]'>Cancel</a></td>
								</tr>";
				$i++;
			}
		}

		// Layout
		$printCallout .= "
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr><td><br></td></tr>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td><a href='callout-canc-view.php'>View Cancelled Call Out Documents</td>
					</tr>
					<tr class='datacell'>
						<td><a href='callout-new.php'>New Call Out Document</td>
					</tr>
					<tr class='datacell'>
						<td><a href='main.php'>Main Menu</td>
					</tr>
				</table>";
	return $printCallout;

}

?>
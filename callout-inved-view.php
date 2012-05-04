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

##
# callout-view.php :: Module to view & print call out docs
##

require ("settings.php");

# show current stock
$OUTPUT = printInv ();

require ("template.php");

##
# Functions
##

# show quotes
function printInv ()
{
	# Set up table to display in
	$printCallout = "
	<h3>View Invoiced Call Out Documents.</h3>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Department</th><th>Call Out Person</th><th>Job No.</th><th>Service Date</th><th>Customer Name</th><th colspan=5>Options</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM callout_docs WHERE accepted != 'c' AND done = 'y' AND div = '".USER_DIV."' AND invoiced = 'yes'  ORDER BY calloutid DESC";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to retrieve call out documents from database.");
	if (pg_numrows ($calloutRslt) < 1) {
		$printCallout = "<li>No previous call out documents.";
	}else{
		while ($callout = pg_fetch_array ($calloutRslt)) {

			# format date
			$callout['odate'] = explode("-", $callout['odate']);
			$callout['odate'] = $callout['odate'][2]."-".$callout['odate'][1]."-".$callout['odate'][0];

			#check if this doc has been uploaded
			$get_doc = "SELECT * FROM callout_docs_scanned WHERE calloutid = '$callout[calloutid]' AND div = '".USER_DIV."'";
			$run_doc = db_exec($get_doc) or errDie("Unable to get call out document information");
			if(pg_numrows($run_doc) < 1){
				$uploaddoc = "<td colspan='2'><a href='callout-uploaddoc.php?calloutid=$callout[calloutid]'>Upload Scanned Document</a></td>";
			}else {
				$uploaddoc = "<td><a href='callout-uploaddoc.php?calloutid=$callout[calloutid]'>Change Uploaded Document</a></td><td><a href='images/callout-showdoc.php?calloutid=$callout[calloutid]'>View Document</a></td>";
			}

			$printCallout .= "
				<tr class='".bg_class()."'>
					<td>$callout[deptname]</td>
					<td>$callout[calloutp]</td>
					<td>$callout[calloutid]</td>
					<td align=center>$callout[odate]</td>
					<td>$callout[cusname] $callout[surname]</td>
					<td><a href='callout-new.php?calloutid=$callout[calloutid]&cont=true&letters=&done='>Edit</a></td>
					<td><a href='callout-cancel.php?calloutid=$callout[calloutid]'>Cancel</a></td>
					<td><a href='callout-print.php?calloutid=$callout[calloutid]' target='_blank'>Print</a></td>
					$uploaddoc
					<td>Invoiced</td>
				</tr>";
			$i++;
		}
	}

	// Layout
	$printCallout .= "
			</table>
			<p>
			<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><td><br></td></tr>
				<tr><th>Quick Links</th></tr>
				<tr class='datacell'><td align='center'><a href='cust-credit-stockinv.php'>New Invoice</a></td></tr>
				<tr class='datacell'><td align='center'><a href='callout-new.php'>New Call Out Document</td></tr>
				<tr class='datacell'><td align='center'><a href='main.php'>Main Menu</td></tr>
			</table>";

	return $printCallout;
}
?>

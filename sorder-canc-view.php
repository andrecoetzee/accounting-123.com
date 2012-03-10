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
$OUTPUT = printSord ();

require ("template.php");

##
# Functions
##

# show Sales Orders
function printSord ()
{
		# Set up table to display in
		$printSord = "
        <h3>View Canceled Sales Orders</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr>
        	<th>Department</th>
        	<th>Sales Person</th>
        	<th>Sales Order No.</th>
        	<th>Sales Order Date</th>
        	<th>Customer Name</th>
        	<th>Order No</th>
        	<th>Grand Total</th>
        	<th colspan=6>Options</th>
        </tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM cancelled_sord WHERE div = '".USER_DIV."' ORDER BY sordid DESC";
        $sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders from database.");
		if (pg_numrows ($sordRslt) < 1) {
			$printSord = "<li>No Sales Orders.";
		}else{
			while ($sord = pg_fetch_array ($sordRslt)) {
				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# format date
				$sord['odate'] = explode("-", $sord['odate']);
				$sord['odate'] = $sord['odate'][2]."-".$sord['odate'][1]."-".$sord['odate'][0];
				$det = "sorder-cancel-details.php";
				$cancel = "sorder-cancel.php";
				$accept = "sorder-accept.php";
				$print = "sorder-print.php";
				$edit = "sorder-new.php";

				if($sord['location'] == 'int'){
					$det = "intsorder-details.php";
					$cancel = "intsorder-cancel.php";
					$accept = "intsorder-accept.php";
					$print = "intsorder-print.php";
					$edit = "intsorder-new.php";
				}

				$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
				$bcurr = CUR;
				if($sord['location'] == 'int'){
					$bcurr = $sord['currency'];
				}

				$printSord .= "<tr bgcolor='$bgColor'>
					<td>$sord[deptname]</td>
					<td>$sord[salespn]</td>
					<td>$sord[sordid]</td>
					<td align=center>$sord[odate]</td>
					<td>$sord[cusname] $sord[surname]</td>
					<td align=right>$sord[ordno]</td>
					<td align=right>$bcurr $sord[total]</td>
					<td><a href='$det?sordid=$sord[sordid]'>Details</a></td>
					<td><a href='sorder-cancel-print.php?invid=$sord[sordid]'>Print</a></td>";

				$i++;
			}
		}

		// Layout
		$printSord .= "</table>"
		.mkQuickLinks(
			ql("sorder-unf-view.php", "View Incomplete Sales Orders"),
			ql("sorder-new.php", "New Sales Order"),
			ql("customers-new.php", "New Customer")
		);

	return $printSord;
}
?>

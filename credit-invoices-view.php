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
$OUTPUT = printOrders ();

require ("template.php");

##
# Functions
##

# show invoices
function printOrders ()
{
	# Set up table to display in
	$printOrders = "
        <h3>View previous credit invoices</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Sales Rep</th><th>Invoice Date</th><th>Customer Name</th><th>Invoice No</th><th>Grand total</th><th>Terms</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
        $sql = "SELECT * FROM credit_invoices ORDER BY ordnum DESC";
        $invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "No previous invoices.";
	}
	while ($myInv = pg_fetch_array ($invRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

                # Date Format
                $myInv['invdate'] = explode("-", $myInv['invdate']);
                $myInv['invdate'] = $myInv['invdate'][2]."-".$myInv['invdate'][1]."-".$myInv['invdate'][0];

                $printOrders .= "<tr bgcolor='$bgColor'><td>$myInv[salesrep]</td><td align=center>$myInv[invdate]</td><td>$myInv[cusname]</td><td align=right>$myInv[ordnum]</td><td>$myInv[grdtot]</td>
                <td>$myInv[terms] days</td><td><a target='_blank' href='credit-invoices-details.php?ordnum=$myInv[ordnum]'>Print</a></td>";


		$i++;
	}
	$printOrders .= "<tr><td><input type=button value='Back' onClick='javascript:history.back();'></td><td valign=center></td></tr>
	</table>
        <p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
        </table>";

	return $printOrders;
}

?>

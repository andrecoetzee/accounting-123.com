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
$OUTPUT = printQuo ();

require ("template.php");

##
# Functions
##

# show quotes
function printQuo ()
{
	# Set up table to display in
	$printQuo = "
        <h3>Incomplete POS Quotes</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Username</th><th>Department</th><th>Sales Person</th><th>Quote No.</th><th>Quote Date</th><th>Customer Name</th><th>Order No</th><th>Grand Total</th><th>Balance</th><th colspan=3>Options</th></tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM pos_quotes WHERE done = 'n' AND div = '".USER_DIV."' ORDER BY quoid DESC";
        $quoRslt = db_exec ($sql) or errDie ("Unable to retrieve quotes from database.");
		if (pg_numrows ($quoRslt) < 1) {
			$printQuo = "<li>No previous incomplete quotes.";
		}else{
			while ($quo = pg_fetch_array ($quoRslt)) {

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$quo[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<i class=err>Not Found</i>";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}

				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# format date
				$quo['odate'] = explode("-", $quo['odate']);
				$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];

				$printQuo .= "<tr bgcolor='$bgColor'><td>$quo[username]</td><td>$dept[deptname]</td><td>$quo[salespn]</td><td>$quo[quoid]</td><td align=center>$quo[odate]</td><td>$quo[cusname]</td><td align=right>$quo[ordno]</td><td>".CUR." $quo[total]</td>
				<td>".CUR." $quo[balance]</td>
				<td><a href='pos-quote-new.php?quoid=$quo[quoid]&cont=true&done='>Continue</a></td>
				<td><a href='pos-quote-unf-cancel.php?quoid=$quo[quoid]'>Cancel</a></td></tr>";
				$i++;
			}
		}

		// Layout
		$printQuo .= "</table>
        <p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class=datacell><td><a href='pos-quote-canc-view.php'>View Cancelled POS Quotes</td></tr>
        <tr class=datacell><td><a href='pos-quote-new.php'>New POS Quote</td></tr>
        <tr class=datacell><td><a href='main.php'>Main Menu</td></tr>
        </table>";

	return $printQuo;
}
?>

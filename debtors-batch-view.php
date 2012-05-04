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

// Get global variables & functions
require ("settings.php");

// show all debtors
$OUTPUT = prnDebtors ();

require ("template.php");

/*
 * Functions
 *
 */

// Prints outstanding debtors only
function prnDebtors ()
{
	// Connect to database
        db_connect();
        $sql = "SELECT * FROM debtors_batch WHERE proc = 'no'";
        $batRslt = db_exec($sql);
        if(pg_numrows($batRslt) < 1){
                return "<li class=err> There are no outstanding debtors in Cubit.";
        }else{
                // Layout
                $view = "<center><h3>Debtors Outstanding Batch Entries</h3>
                <form action='debtors-batch-proc.php' method=post>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
                <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>";

                $i = 0; // for bgcolor
                $tot = 0;
                while($bat = pg_fetch_array($batRslt)){
                        db_connect();
                        # Get all the details
                        $sql = "SELECT * FROM credit_invoices WHERE ordnum = '$bat[ordnum]'";
                        $invRslt = db_exec($sql) or errDie("Unable to access database.");
                        if (pg_numrows ($invRslt) < 1) {
	        	        return "<li class=err> - Invalid ord number.";
        	        }
                        $inv = pg_fetch_array($invRslt);

                        # Get debt invoice info
                        $sql = "SELECT * FROM debtors WHERE ordnum ='$bat[ordnum]'";
                        $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	                if (pg_numrows ($dtRslt) < 1) {
		                return "<li class=err>Invalid Invoice Number.";
	                }
                        $dt = pg_fetch_array($dtRslt);

                        foreach($inv as $keys => $values){
                                $$keys = $values;
                        }

                        # get paid account name
                        core_connect();
                        $sql = "SELECT accname FROM accounts WHERE accid = '$bat[accpaid]'";
                        $accRslt = db_exec($sql);
                        $acc = pg_fetch_array($accRslt);

                        foreach($inv as $keys => $values){
                                $$keys = $values;
                        }

                        $view .= "<tr class='bg-odd'>
                                <td>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $bat[paidamt]</td>
                                <td align=center>$acc[accname]</td>
                                <td align=center><input type=checkbox name='ord[]' value='$bat[batchid]'></td>
                            </tr>";
                        $tot += $bat['paidamt'];
                }

                $view .= "<tr><td><br></td></tr>
                <tr class='bg-even'><td colspan=5><b>Total Amount Received</b></td><td colspan=2><b>".CUR." ".sprintf("%01.2f", round($tot, 2))."</b></td></tr>
                <tr><td><br></td></tr>
                <tr><td colspan=6><br></td><td colspan=2 align=right><input type=submit value='Process Selected' name=proc> <input type=submit value='Remove Selected' name=rem></td><tr></table>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
                        <tr><th>Quick Links</th></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                        <tr bgcolor='#88BBFF'><td><a href='reporting/debtors-age.php'>Debtors - Age</a></td></tr>
                        <script>document.write(getQuicklinkSpecial());</script>
                        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
                </table>";
	}

        return $view;
}
?>

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

// show all creditors
$OUTPUT = prnCreditors ();

require ("template.php");

/*
 *
 * Functions
 *
 */

// Prints creditors batch entries in db without any filter
function prnCreditors ()
{
	// Connect to database
        core_connect();
        $sql = "SELECT * FROM credit_batch WHERE proc='no' ORDER BY batchid ASC";
        $batRslt = db_exec($sql);
        if(pg_numrows($batRslt) < 1){
                return "<li class=err> - There are no Outstanding batched credit payments in Cubit.";
        }else{
                // Layout
                $view = "<center><h3>Outstanding Creditors Batch Entries</h3>
                <form action='credit-batch-proc.php' method=post>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>";

                $i = 0; // for bgcolor
                while($bat = pg_fetch_array($batRslt)){

                        $sql = "SELECT * FROM purchases WHERE purchid='$bat[purchid]'";
                        $ctRslt = db_exec($sql);
                        if(pg_numrows($ctRslt) < 1){
                                return "<li class=err> Invalid Purchase No.";
                        }
                        $ct = pg_fetch_array($ctRslt);

                        foreach($ct as $key => $value){
                                $$key = $value;
                        }

                        # get credit infomation
                        $sql = "SELECT * FROM credit_purch WHERE purchid = '$purchid' AND amount > 0";
                        $ctRslt = db_exec($sql);
                        $ct = pg_fetch_array($ctRslt);

                        # get used account name
                        $sql = "SELECT accname FROM accounts WHERE accid = '$usedacc'";
                        $accRslt = db_exec($sql);
                        $acc = pg_fetch_array($accRslt);
                        $usedaccname = $acc['accname'];

                        # get paid account name
                        $sql = "SELECT accname FROM accounts WHERE accid = '$paidacc'";
                        $accRslt = db_exec($sql);
                        $acc = pg_fetch_array($accRslt);
                        $paidaccname = $acc['accname'];

                        $view .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td>$retailer</td>
                                <td>$itemname</td>
                                <td>$descript</td>
                                <td>$quantity</td>
                                <td>".CUR." $ct[amount]</td>
                                <td>".CUR." $bat[amount]</td>
                                <td>$usedaccname</td>
                                <td>$paidaccname</td>
                                <td><input type=checkbox name='bat[]' value='$bat[batchid]'><!--<a href='credit-batch-proc.php?batchid=$bat[batchid]'>Process</a>--></td></tr>
                        </tr>";

                }

	}
	$view.="<tr><td colspan=6><br></td><td colspan=2 align=right><input type=submit value='Process Selected' name=proc> <input type=submit value='Cancel Selected' name=rem></td><tr>
                </table>
                <p>
                <table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
                        <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                        <script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $view;
}

?>

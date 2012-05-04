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
# creditors-view.php :: Module to view creditors
##

require ("settings.php");          // Get global variables & functions

// show all creditors
$OUTPUT = prnCreditors ();

require ("template.php");

/*
 * Functions
 *
 */

// Prints creditors in db without any filter

function prnCreditors ()
{
	// Connect to database
        core_connect();
        $sql = "SELECT * FROM purchases WHERE paytype = 'Credit' ORDER BY purchid DESC";
        $prnCrtsRslt = db_exec($sql);
        if(pg_numrows($prnCrtsRslt) < 1){
                return "<li class=err> There are no credit purchases yet in Cubit.";
        }else{
                // Layout
                $view = "<center><h3>Outstanding Credit Purchases</h3>
                <form action='credit-multi-pay.php' method=post>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
                <tr><th>Item Name</th><th>Description</th><th>Quantity</th><th>Total Cost</th><th>Outstanding amount</th><th>Terms</th><th>Item Account</th><th>Account Used</th></tr>";
                $i = 0; // for bgcolor
                while($crt = pg_fetch_array($prnCrtsRslt)){
                        foreach($crt as $key => $value){
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

                        if($ct['amount'] > 0){
                                $view .= "<tr class='".bg_class()."'><td>$itemname</td><td>$descript</td><td>$quantity</td><td>".CUR." $tlcost</td><td>".CUR." $ct[amount]</td><td>$ct[terms] $ct[period]</td><td>$paidaccname</td><td>$usedaccname</td>";
                                $view .= "<td><input type=checkbox name='pay[]' value='$purchid'><a href='credit-purch-pay.php?purchid=$purchid'> Pay</a></td></tr>";
                        }else{
                                continue;
                        }
                }

	}
	$view.="<tr><td colspan=6><br></td><td colspan=2 align=right><input type=submit value='Pay Selected' name=proc> <input type=submit value='Batch Selected' name=bat></td><tr>
                </table>
                <p>
                <table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='credit-batch-view.php'>View Creditors Batch</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
		</table>




	";
	return $view;
}

?>

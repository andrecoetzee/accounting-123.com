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

# get settings
require("settings.php");

# default view
$OUTPUT = view();

# get templete
require("template.php");

# Default view
function view()
{
        $view = "<center><table width=90%>
        <tr><td width=80%><h3>View Returned Purchases</h3></td>
        <td class='bg-even'><a href='purchase-new.php'>Add New Purchase</a><br>
        <a href='purchase-ret.php'>Return Purchases</a>
        </td></tr>
        </table><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
        <tr><th>Item Name</th><th>Description</th><th>Quantity</th><th>Total Cost</th><th>Payment Method</th><th>Item Account</th><th>Account Used</th></tr>";
        core_connect();
        $sql = "SELECT * FROM purch_ret ORDER BY purchid DESC";
        $purchRslt = db_exec($sql);
        if(pg_numrows($purchRslt) < 1){
                return "<li class=err> There are no returned purchases in Cubit yet";
        }else{
                $i = 0; // for bgcolor
                while($purch = pg_fetch_array($purchRslt)){
                        foreach($purch as $key => $value){
                                $$key = $value;
                        }
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

                        $view .= "<tr class='".bg_class()."'><td>$itemname</td><td>$descript</td><td>$quantity</td><td>".CUR." $tlcost</td><td>$paytype</td><td>$paidaccname</td><td>$usedaccname</td></tr>";
                        $i++;
                }
        }

        $view .= "</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $view;
}
?>

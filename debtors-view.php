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
# debtors-view.php :: Module to view debtors
##

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
        $sql = "SELECT * FROM debtors WHERE amount  > 0";
        $prnDtsRslt = db_exec($sql);
        if(pg_numrows($prnDtsRslt) < 1){
                return "<li class=err> There are no outstanding debtors in Cubit.";
        }else{
                // Layout
                $view = "<center><h3>Outstanding Debtors</h3>
                <form action='debtors-multi-pay.php' method=post>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
                <tr><th>Invoice Number</th><th>Customer Name</th><th>Tel No.</th><th>Fax No.</th><th>E-mail Address</th><th>Outstanding amount</th><th>Terms</th></tr>";
                $i = 0; // for bgcolor
                while($dts = pg_fetch_array($prnDtsRslt)){
                        foreach($dts as $key => $value){
                                $$key = $value;
                        }

                        $view .= "<tr class='".bg_class()."'><td>$ordnum</td><td>$cusname</td><td>$tel</td><td>$fax</td><td>$email</td><td>$amount</td><td>$terms days</td>";
                        $view .= "<td><input type=checkbox name='ord[]' value='$ordnum'><a href='debtors-pay.php?ordnum=$ordnum'>Pay</a></td></tr>";
                }

                $view .= "<tr><td colspan=6><br></td><td colspan=2 align=right><input type=submit value='Pay Selected' name=proc> <input type=submit value='Batch Selected' name=bat></td><tr></table>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
                        <tr><th>Quick Links</th></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                        <tr bgcolor='#88BBFF'><td><a href='reporting/debtors-age.php'>Debtors - Age</a></td></tr>
                        <script>document.write(getQuicklinkSpecial());</script>
                        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
                </table>";
	}

        return $view;
}
?>

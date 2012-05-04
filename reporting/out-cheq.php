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

require ("../settings.php");          // Get global variables & functions

// Show all cheques
printcheq();

/*
 * Functions
 *
 */

# print all outstanding cheques
function printcheq()
{
	// Set up table to display in

        $OUTPUT = "<h3>View Cheque Records</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='../bank/bank-bankall.php' method=post>
        <tr><th>Bank Name</th><th>Account Name</th><th>Date</th><th>Paid to/Received from</th><th>Description</th><th>Transaction Type</th><th>Amount</th><th>Account paid<br>/received from</th></tr>";

	// Connect to database
	db_Connect ();
        $sql = "SELECT * FROM cashbook WHERE cheqnum > 0 and banked='no' AND div = '".USER_DIV."' ORDER BY date DESC";
        $accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank cheqque transaction details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

        if ($numrows < 1) {
		$OUTPUT = "<li class=err> There are no outstanding bank cheque Records yet in Cubit.";
		require ("../template.php");
	}

	# display all bank cheques
        for ($i=0; $i < $numrows; $i++) {
		$accnt = pg_fetch_array ($accntRslt, $i);

                # get account name for account involved
                $accRslt = get("core", "accname", "accounts", "accid", $accnt['accinv']);
                $acc = pg_fetch_array($accRslt);

                # get account name for bank account
                db_connect();
                $sql = "SELECT accname,bankname  FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
                $bankRslt = db_exec($sql);
                $bank = pg_fetch_array($bankRslt);

                $OUTPUT .= "<tr class='".bg_class()."'><td>$bank[bankname]</td><td align=center>$bank[accname]</td><td align=center>$accnt[date]</td><td align=center>$accnt[name]</td><td>$accnt[descript]</td><td align=center>$accnt[trantype]</td><td align=center>".CUR." $accnt[amount]<td align=center>$acc[accname]</td></td>";

                if($accnt['banked'] == "no"){
                        $OUTPUT .="<td><input type=checkbox name='bank[]' value='$accnt[cashid]'>&nbsp;<a href='../bank/bank-bank.php?cashid=$accnt[cashid]'>Bank</td><td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td></tr>";
                }else{
                        $OUTPUT .= "</tr>";
                }
	}

	$OUTPUT .= "<tr><td colspan=8><br></td><td colspan=2><input type=submit value='Bank all selected'></td></tr></form></table>";


        // all template to display the info and die
	require ("../template.php");
}
?>

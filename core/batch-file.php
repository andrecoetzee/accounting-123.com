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

# Get settings
require("settings.php");
require("libs/acc.lib.php");

# Display default output
$OUTPUT = batch();

# Get templete
require("template.php");


# View the batch File
function batch()
{
        # query the DB
        core_connect();

        $sql = "SELECT * FROM batch WHERE proc ='no' ORDER BY refnum ASC";
        $rslt = db_exec($sql);

        # get records
        $trans = "";
        while($rec = pg_fetch_array($rslt)){
                foreach($rec as $key => $value){
                        $$key = $value;
                }

                # get the account names
                if(!$dtacc = new acc($debit)){
                        return "<li> - Invalid debit account";
                }
                if(!$ctacc = new acc($credit)){
                       return "<li> - Invalid credit account";
                }
                $trans .= "$date;$refnum;$dtacc->accname;$dtacc->topacc/$dtacc->accnum;$ctacc->accname;$ctacc->topacc/$ctacc->accnum;$amount;$details;$author\n<br>";
        }

        $retable = "<center>
        <h3>Batch File <br> Semi-Colon (;) Delimeted</h3>
        | Date | Ref No.| Debit Account | Debit Account No. | Credit Account | Credit Account No. | Amount | Details | Author |
        <br><br>
        <table bgcolor='#ffffff' width=90% cellpadding=5>
                <tr><td><font size=2>$trans</font></td></tr>
        </table>";

        return $retable;
}
?>

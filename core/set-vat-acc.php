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
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

                case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = slctInc();
	}
} else {
        # Display default output
        $OUTPUT = slctInc();
}

# get templete
require("template.php");


function slctInc()
{
        // Set up table to display in
        $OUTPUT = "<center>
	<h3>Select VAT Deductable Accounts</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Account Number</th><th>Account Name</th></tr>
	";

	// get accounts
        $sql = "SELECT * FROM accounts WHERE acctype='I' AND vat='t'";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);

        if (pg_numrows ($accRslt) < 1) {
		return "<li>There are no Accounts under Income.";
	}

        // get accounts
        $sql = "SELECT * FROM setvat";
        $accRs = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
        if(pg_numrows ($accRs) > 0){
                $i=0;
                while($setacc = pg_fetch_array($accRs)){
                        $sacc[$i] = $setacc['accid'];
                        $i++;
                }
        }else{
                $sacc = array("");
        }

        # print "<pre>";var_dump($sacc);

	# display all accounts
        for ($i=0; $acc = pg_fetch_array ($accRslt); $i++) {

                #get vars from acc as the are in db
                foreach ($acc as $key => $value) {
		        $$key = $value;
	        }

                if(in_array($accid, $sacc)){
                        $ch = "checked=yes";
                }else{
                        $ch = "";
                }

                # alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $OUTPUT .= "<tr bgcolor='$bgColor'><td><input type=checkbox name=inc[] value='$accid' $ch> $topacc/$accnum</td><td>$accname</td></tr>";
        }
        $OUTPUT .= "</table><br>
        <input type=button value='&laquo Cancel' onClick='javascript:history.back();'> <input type=submit value='Continue &raquo'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $OUTPUT;
}

# Confirm
function confirm($HTTP_POST_VARS)
{
        # get vars (arrays{inc,exp})
        foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # get number of accounts
        if(isset($inc)){
                $incnum = count($inc);
        }else{
                return "<li> - No account selected, please select at least one account";
        }


        # Set up table to display in
        $confirm = "
        <center><h3>Set VAT Accounts</h3>
	<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Account Number</th><th>Account Name</th></th></tr>";

        # strip inc array back to HTML
        foreach($inc as $key => $accid) {
                $bgColor = ($key % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
                $accRslt = get("core","accname,topacc,accnum","accounts","accid",$accid);
                $acc = pg_fetch_array($accRslt);
                $confirm .="<input type=hidden name='inc[]' value='$inc[$key]'>
                <tr bgcolor='$bgColor'><td> $acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td></tr>";
        }

        $confirm .= "</table>
        <input type=button value='< Cancel' onClick='javascript:history.back();'> <input type=submit value='Confirm >'>
        </form>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
                <tr><td><br><td></tr>
                <tr><th>Quick Links</th></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $confirm;
}

# write settings
function write($HTTP_POST_VARS)
{
        # get vars (arrays{inc,exp})
        foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # get number of accounts
        $incnum = count($inc);

        # Clear the previous settings
        $sql = "TRUNCATE TABLE setvat";
        $emptyRslt = db_exec($sql) or errDie("Unable to clear the previous vat settings on the Database", SELF);


        # write Income accounts settings
        foreach($inc as $key => $accid){
                $query = "INSERT INTO setvat(accid) VALUES('$accid')";
                $stRslt = db_exec($query) or errDie("Unable to insert income statement settings to database",SELF);
        }

        // Status Report
        $write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
                <tr><th>VAT Accounts Settings</th></tr>
                <tr class=datacell><td>VAT Accounts settings were successfully added to Cubit.</td></tr>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
                <tr><td><br></td></tr>
                <tr><th>Quick Links</th></tr>
                <tr class=datacell><td align=center><a href='../reporting/vat-report.php'>VAT Report</td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

       return $write;

}
?>

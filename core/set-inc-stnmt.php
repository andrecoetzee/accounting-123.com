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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "slctExp":
			$OUTPUT = slctExp($_POST);
			break;

                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
			$OUTPUT = write($_POST);
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
	<h3>Select accounts to be used under Income</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=slctExp>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Account Number</th><th>Account Name</th><th>Account Type</th></tr>
	";

	// get accounts
        $sql = "SELECT * FROM accounts WHERE acctype='I'";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);


        if ($numrows < 1) {
		$OUTPUT = "<li>There are no Accounts under Income.";
		require ("../template.php");
	}

        // get set accounts
        $sql = "SELECT accnum FROM incstmnt WHERE type='INC'";
        $accRs = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
        if(pg_numrows ($accRs) > 0){
                $i=0;
                while($setacc = pg_fetch_array($accRs)){
                        $sacc[$i] = $setacc['accnum'];
                        $i++;
                }
        }else{
                $sacc = array("");
        }

        # display all accounts
        for ($i=0; $i < $numrows; $i++) {
		$acc = pg_fetch_array ($accRslt, $i);

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
                $OUTPUT .= "<tr bgcolor='$bgColor'><td><input type=checkbox name=inc[] value='$accid' $ch> $topacc/$accnum</td><td>$accname</td><td align=right>$acctype</td></tr>";
        }
        $OUTPUT .= "</table><br>
        <input type=button value='< Cancel' onClick='javascript:history.back();'> <input type=submit value='Select Expenditure Accounts>'>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</table>

";

        return $OUTPUT;
}

# Select Expenditure Accounts
function slctExp($_POST)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        if(!isset($inc)){
                return "<li class=err>Please select at least one income account for the income statement.";
        }

        # strip inc array back to HTML hidden vars
        $income="";
        for($i = 0;$i <= (count($inc)-1);$i++) {
                $income .="<input type=hidden name='inc[]' value='$inc[$i]'>";
        }

        // Set up table to display in
        $OUTPUT = "<center>
	<h3>Select to be used under Expenditure</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        $income
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Account Number</th><th>Account Name</th><th>Account Type</th></tr>";

	// get accounts
        $sql = "SELECT * FROM accounts WHERE acctype='I'";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);


        if ($numrows < 1) {
		return "There are no Accounts under Expediture.";
	}

        // get set accounts
        $sql = "SELECT accnum FROM incstmnt WHERE type='EXP'";
        $accRs = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
        if(pg_numrows ($accRs) > 0){
                $i=0;
                while($setacc = pg_fetch_array($accRs)){
                        $sacc[$i] = $setacc['accnum'];
                        $i++;
                }
        }else{
                $sacc = array("");
        }

	# display all accounts
        for ($i=0; $i < $numrows; $i++) {
		$acc = pg_fetch_array ($accRslt, $i);

                #get vars from acc as the are in db
                foreach ($acc as $key => $value) {
		        $$key = $value;
	        }

                # check the ones that are alreaduy set
                # and skip the ones that are set as Income
                if(in_array($accid, $sacc)){
                        $ch = "checked=yes";
                }else{
                        $ch = "";
                }
                if(in_array($accid, $inc)){
                        continue;
                }

                # alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $OUTPUT .= "<tr bgcolor='$bgColor'><td><input type=checkbox name=exp[] value='$accid' $ch>$topacc/$accnum</td><td>$accname</td><td align=right>$acctype</td></tr>";
        }
        $OUTPUT .= "</table><br>
        <input type=button value='< Cancel' onClick='javascript:history.back();'> <input type=submit value='Continue >'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
                <tr><td><br></td></tr>
                <tr><th>Quick Links</th></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $OUTPUT;
}

# Confirm
function confirm($_POST)
{
        # get vars (arrays{inc,exp})
        foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        if(!isset($exp)){
                return "<li class=err>Please select at least one expenditure account for the income statement.";
        }

        # get number of accounts
        $incnum = count($inc);
        $expnum = count($exp);

        # Set up table to display in
        $confirm = "
        <center>
	<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";

        $income = "<tr><td colspan = 3 align=center><b><h3>Income</h3></td></tr>
        <tr><th>Account Number</th><th>Account Name</th><th>Account Type</th></tr>";

        # strip inc array back to HTML
        foreach($inc as $key => $accid) {
                $bgColor = ($key % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
                $accRslt = get("core","accname","accounts","accid",$accid);
                $accname = pg_fetch_array($accRslt);
                $income .="<input type=hidden name='inc[]' value='$inc[$key]'>
                <tr bgcolor='$bgColor'><td>$inc[$key]</td><td>$accname[accname]</td><td align=right>Income</td></tr>";
        }

        $expenditure = "<tr><td colspan = 3 align=center><b><h3>Expediture</h3></td></tr>
        <tr><th>Account Number</th><th>Account Name</th><th>Account Type</th></tr>";

                # strip inc array back to HTML
                foreach($exp as $key => $accid) {
                        $bgColor = ($key % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
                        $accRslt = get("core","accname","accounts","accid",$accid);
                        $accname = pg_fetch_array($accRslt);
                        $expenditure .="<input type=hidden name='exp[]' value='$exp[$key]'><tr bgcolor='$bgColor'>
                        <td>$exp[$key]</td><td>$accname[accname]</td><td align=right>Expenditure</td></tr>";
                }

        $confirm .= "$income $expenditure</table>
        <input type=button value='< Cancel' onClick='javascript:history.back();'> <input type=submit value='Confirm >'>
        </form>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>

</table>
";

        return $confirm;
}

# write settings
function write($_POST)
{
        # get vars (arrays{inc,exp})
        foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # get number of accounts
        $incnum = count($inc);
        $expnum = count($exp);

        # Clear the previous settings
        $sql = "TRUNCATE TABLE incstmnt";
        $emptyRslt = db_exec($sql) or errDie("Unable to clear the  Income statement setting on the Database", SELF);


        # write Income accounts settings
        foreach($inc as $key => $accid){
                $query = "INSERT INTO incstmnt(accnum,type) VALUES('$accid','INC')";
                $stRslt = db_exec($query) or errDie("Unable to insert income statement settings to database",SELF);
        }

        # write Expenditure accounts settings
        foreach($exp as $key => $accid){
                $query = "INSERT INTO incstmnt(accnum,type) VALUES('$accid','EXP')";
                $stRslt = db_exec($query) or errDie("Unable to insert income statement settings to database",SELF);
        }

        // Status Report
        $write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Income Statement Settings</th></tr>
        <tr class=datacell><td>Income Statement Settings were successfully added to Cubit.</td></tr>

<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>


</table>";

       return $write;

}
?>

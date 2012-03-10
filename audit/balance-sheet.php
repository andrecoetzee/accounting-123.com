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

header("Location: ../reporting/bal-sheet-view.php");
exit;

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
			case "print":
				$OUTPUT = bal_sheet($HTTP_POST_VARS);
				break;

			case "printsave":
				$OUTPUT = save_bal();
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get templete
require("../template.php");

# Default View
function view()
{

	// Select previous year database
	preg_match ("/yr(\d*)/", YR_DB, $id);
	$i = $id['1'];
	$i--;
	if(intval($i) == 0){
		return "<li class=err> Error : Your are on the first year of cubit operation, there are no previous closed years";
	}
	$yrdb ="yr".$i;

	// Get prev year name
	core_connect();
	$sql = "SELECT * FROM year WHERE yrdb ='$yrdb'";
	$rslt = db_exec($sql);
	$yr = pg_fetch_array($rslt);
	$yrname = $yr['yrname'];

	// Layout
	$view = "
	<h3>Balance Sheet for previous year : $yrname</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	<input type=hidden name=yrdb value='$yrdb'>
	<input type=hidden name=yrname value='$yrname'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period</td><td><select name=prd>";
                db_conn($yrdb);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        return "ERROR : There are no periods set for the current year";
                }
                while($prd = pg_fetch_array($prdRslt)){
                        if($prd['prddb'] == PRD_DB){
                               $sel = "selected";
                        }else{
                                $sel = "";
                        }
                        $view .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
                }
                $view .= "
    </select></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Balance Sheet Settings</td><td valign=center>
	Previous Year's <input type=radio name=balset value=l checked=yes> | This Year's<input type=radio name=balset value=t></td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

# return
function ret($OUTPUT){
	require("../template.php");
}

# Balance Sheet
function bal_sheet($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}

		if($balset == 'l'){
			$baldb = $yrdb;
		}elseif($balset == 't'){
			$baldb = "core";
		}

		# get prd name
		db_conn($yrdb);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);
		$prdname = $prds['prdname'];

		# check if can connect to prev period
		$sql = "SELECT accid FROM $prdname WHERE div = '".USER_DIV."'";
        $Rslt = @db_exec ($sql) or ret ("<li class=err>Unable to retrieve data from Cubit. Period <b>$prdname</b> was not properly close on previous year.", SELF);


        # Get Owners Equity Sub Headings
        $oesubRslt = get($baldb, "*", "bal_sheet", "type", "OESUB");
        $sheet = "<center>
        <h3> Balance Sheet for $prdname on previous year $yrname</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
        <tr><th colspan=3>Owners Equity</th></tr>";

        # get accounts
        $oebal = 0; // OE Balance
        while($oesub = pg_fetch_array($oesubRslt)){
               $sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$oesub[value]<b></td></tr>";

			   db_conn($baldb);
               $sql = "SELECT * FROM bal_sheet WHERE type ='OEACC' AND ref = $oesub[ref] AND div = '".USER_DIV."'";
               $accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);

				# get privious balances
				db_conn($yrdb);
				while($acc = pg_fetch_array($accRslt)){
                        $query = "SELECT * FROM $prdname WHERE accid = '$acc[value]' AND div = '".USER_DIV."'";
                        $balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
                        $accbal = pg_fetch_array($balRslt);
                        $balance = ($accbal['credit'] - $accbal['debit']);
                        $oebal += $balance;
                        $sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$accbal[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
                }
        }
        # print Net Income and OE Balance on last column
        $netincome = getNetIncome($yrdb, $prdname);
        $oebal += $netincome;
        $sheet .="<tr bgcolor='".TMPL_tblDataColor1."'><td>&nbsp<b>Net Income</b></td><td>".CUR." $netincome</td><td><br></td></tr>";
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $oebal</b></td></tr>";

        # Get Assets Sub Headings
        $abal = 0; // Assets Balance
        $asssubRslt = get($baldb, "*", "bal_sheet", "type", "ASSSUB");
        $sheet .= "<tr><th colspan=3>Assets</th></tr>";

        # get accounts
        while($asssub = pg_fetch_array($asssubRslt)){
               $sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$asssub[value]<b></td></tr>";

				db_conn($baldb);
				$sql = "SELECT * FROM bal_sheet WHERE type ='ASSACC' AND ref = $asssub[ref] AND div = '".USER_DIV."'";
				$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);

				# get previous account Balances
				db_conn($yrdb);
				while($acc = pg_fetch_array($accRslt)){
                        $query = "SELECT * FROM $prdname WHERE accid = '$acc[value]' AND div = '".USER_DIV."'";
                        $balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
                        $accbal = pg_fetch_array($balRslt);
                        $balance = ($accbal['debit'] - $accbal['credit']); // calc Balance
                        $abal += $balance;
                        $sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$accbal[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
                }
        }

        # print assets balance on last column
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $abal</b></td></tr>
        </table>
		<p>
		<form>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $sheet;
}

# Balance Sheet
function save_bal()
{
        # Get Owners Equity Sub Headings
        $oesubRslt = get("core", "*", "bal_sheet", "type", "OESUB");
        $sheet = "<center>
        <h3> Balance sheet for period : ".PRD_NAME."<br><br>Date: ".date("d M Y")." </h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
        <tr><th colspan=3>Owners Equity</th></tr>";

        # get accounts
        $oebal = 0; // OE Balance
        while($oesub = pg_fetch_array($oesubRslt)){
               $sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$oesub[value]<b></td></tr>";

               $sql = "SELECT * FROM bal_sheet WHERE type ='OEACC' AND ref = $oesub[ref] AND div = '".USER_DIV."'";
               $accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
               # get account BAlances
               while($acc = pg_fetch_array($accRslt)){
                        $query = "SELECT * FROM trial_bal WHERE accid = '$acc[value]' AND div = '".USER_DIV."'";
                        $balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
                        $accbal = pg_fetch_array($balRslt);
                        $balance = ($accbal['credit'] - $accbal['debit']);
                        $oebal += $balance;
                        $sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$accbal[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
                }
        }
        # print Net Income and OE Balance on last column
        $netincome = getNetIncome($yrdb, $prdname);
        $oebal += $netincome;
        $sheet .="<tr bgcolor='".TMPL_tblDataColor1."'><td>&nbsp<b>Net Income</b></td><td>".CUR." $netincome</td><td><br></td></tr>";
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $oebal</b></td></tr>";

        # Get Assets Sub Headings
        $abal = 0; // Assets Balance
        $asssubRslt = get("core", "*", "bal_sheet", "type", "ASSSUB");
        $sheet .= "<tr><th colspan=3>Assets</th></tr>";

        # get accounts
        while($asssub = pg_fetch_array($asssubRslt)){
               $sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$asssub[value]<b></td></tr>";

               $sql = "SELECT * FROM bal_sheet WHERE type ='ASSACC' AND ref = $asssub[ref] AND div = '".USER_DIV."'";
               $accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
               # get account BAlances
               while($acc = pg_fetch_array($accRslt)){
                        $query = "SELECT * FROM trial_bal WHERE accid = '$acc[value]' AND div = '".USER_DIV."'";
                        $balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
                        $accbal = pg_fetch_array($balRslt);
                        $balance = ($accbal['debit'] - $accbal['credit']); // calc Balance
                        $abal += $balance;
                        $sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$accbal[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
                }
        }

        # print assets balance on last column
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $abal</b></td></tr>
        </table><br>";

		$output = base64_encode($sheet);
		core_connect();
		$sql = "INSERT INTO save_bal_sheet(gendate, output, div) VALUES('".date("Y-m-d")."', '$output', '".USER_DIV."')";
		$Rs = db_exec($sql) or errdie("Unable to save the Balance Sheet.");

		$sheet .="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $sheet;
}

// get total income
function getNetIncome($yrdb, $prdname)
{
        # get the income statement settings
        core_connect();
        $sql = "SELECT accid FROM accounts WHERE acctype='I' AND div = '".USER_DIV."'";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center>There Are no Income in Cubit.";
        }

        # get income accounts Balances
        $tlinc = 0; // total income credit

		db_conn($yrdb);
        while($inc = pg_fetch_array($incRslt)){
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM $prdname WHERE accid = '$inc[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                $total = ($bal['credit'] - $bal['debit']);
                $tlinc += $total;
        }

		core_connect();
        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '".USER_DIV."'";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There Are no Expenditure accounts in Cubit.";
        }

        # get account Balances for Expenditure
        $tlexp = 0; // total expenditures

		db_conn($yrdb);
        while($exp = pg_fetch_array($expRslt)){

                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM $prdname WHERE accid = '$exp[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                # alternate bgcolor
                $total = ($bal['debit'] - $bal['credit']);
                $tlexp += $total;        // And increment the balance for expenditure
        }
        return sprintf("%01.2f", ($tlinc - $tlexp));
}
?>

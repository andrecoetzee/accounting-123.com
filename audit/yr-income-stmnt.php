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

header("Location: ../reporting/income-stmnt-view.php");
exit;

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "print":
				$OUTPUT = inc($_POST);
				break;

			case "printsave":
				$OUTPUT = save_inc($_POST);
				break;


			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

require ("../template.php");

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

	/*
	core_connect();
	$sql = "SELECT batchid FROM batch WHERE proc = 'no'";
	$Rs = db_exec($sql) or errdie("Batch file unreachable.");
	if(pg_numrows($Rs) > 0){
		$sum = pg_numrows($Rs);
		$out = pg_fetch_array($Rs);
		$note = "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
	}else{
		$note = "";
	}
	*/

	$view = "
	<h3>Income Statement for previous year : $yrname</h3>
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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

# return
function ret($OUTPUT){
	require("../template.php");
}

function inc($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
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

        # connect to core DB
        core_connect();

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype = 'I' AND div = '".USER_DIV."'";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center>There are no accounts under income on the income statement Settings table.<br>Please Set the Income Statement";
        }

		// Connect to previous year DB
		db_conn($yrdb);

        // Set up the Table to display in
        $income = "<center><h3>Income Statement for $prdname in previous year : $yrname</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
        <tr><th width=70%>Account Name</th><th>Amount</th></tr>
        <tr><td colspan=3><h3>Income</h3></td></tr>";

        # get account Balances

        $tlinc = 0; // total income credit
        $i =0;
        while($inc = pg_fetch_array($incRslt)){
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM $prdname WHERE accid = '$inc[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                # alternate bgcolor
                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $total = ($bal['credit'] - $bal['debit']);
				if($zero == "no"){
					if(intval($total == 0)){
						$i++;
						continue;
					}
				}
				$tlinc += $total;
                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

        # write totals for income
        $income .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td align=center><b>".CUR." $tlinc</b></td></tr>";

		# connect to core DB
        core_connect();

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '".USER_DIV."'";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement Settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There are no accounts under Expenditures on the income statement Settings table.<br>Please Set the Income Statement";
        }

        $income .= "<tr><td colspan=3><h3>Expenditure</h3></td></tr>";

		// Connect to previous year DB
		db_conn($yrdb);

		# get account Balances for Expenditure
        $tlexp = 0; // total expenditures

        $i =0;
        while($exp = pg_fetch_array($expRslt)){

                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM $prdname WHERE accid = '$exp[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                # alternate bgcolor
                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $total = ($bal['debit'] - $bal['credit']);
				if($zero == "no"){
					if(intval($total == 0)){
						$i++;
						continue;
					}
				}
				$tlexp += $total;        // And increment the balance for expenditure

                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }
        $income .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td align=center><b>".CUR." $tlexp</b></td></tr>
                    <tr><td colspan=3><br></td></tr>";

        # Calculate Profit/Loss
        $income .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Nett Profit Carried Forward<b></td><td align=center colspan=2><b>".CUR." ".($tlinc-$tlexp)."</b></td></tr>
		<tr><td><br></td></tr>
		<form></table>
		<p>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=20%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $income;
}

function save_inc($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # connect to core DB
        core_connect();

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype = 'I' AND div = '".USER_DIV."'";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center>There are no accounts under income on the income statement Settings table.<br>Please Set the Income Statement";
        }
        // Set up the Table to display in
        $income = "<center><h3>Income Statement as at : ".date("d M Y")."</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
        <tr><th width=70%>Account Name</th><th>Amount</th></tr>
        <tr><td colspan=3><h3>Income</h3></td></tr>";

        # get account Balances

        $tlinc = 0; // total income credit
        $i =0;
        while($inc = pg_fetch_array($incRslt)){
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                # alternate bgcolor
                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $total = ($bal['credit'] - $bal['debit']);
				if($zero == "no"){
					if(intval($total == 0)){
						$i++;
						continue;
					}
				}
				$tlinc += $total;
                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

        # write totals for income
        $income .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td align=center><b>".CUR." $tlinc</b></td></tr>";

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '".USER_DIV."'";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement Settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There are no accounts under Expenditures on the income statement Settings table.<br>Please Set the Income Statement";
        }

        $income .= "<tr><td colspan=3><h3>Expenditure</h3></td></tr>";

        # get account Balances for Expenditure
        $tlexp = 0; // total expenditures

        $i =0;
        while($exp = pg_fetch_array($expRslt)){

                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]' AND div = '".USER_DIV."'";
                $balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
                $bal = pg_fetch_array($balRslt);

                # alternate bgcolor
                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $total = ($bal['debit'] - $bal['credit']);
				if($zero == "no"){
					if(intval($total == 0)){
						$i++;
						continue;
					}
				}
				$tlexp += $total;        // And increment the balance for expenditure

                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }
        $income .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td align=center><b>".CUR." $tlexp</b></td></tr>
                    <tr><td colspan=3><br></td></tr>";

        # Calculate Profit/Loss
        $income .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Nett Profit Carried Forward<b></td><td align=center colspan=2><b>".CUR." ".($tlinc-$tlexp)."</b></td></tr>
		<tr><td><br></td></tr>
		</table>";

		$output = base64_encode($income);
		core_connect();
		$sql = "INSERT INTO save_income_stmnt(gendate, output, div) VALUES('".date("Y-m-d")."', '$output', '".USER_DIV."')";
		$Rs = db_exec($sql) or errdie("Unable to save the Income Statement.");

		$income .= "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=20%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $income;
}
?>




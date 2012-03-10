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
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
			case "print":
				$OUTPUT = inc($HTTP_POST_VARS);
				break;

			case "printsave":
				$OUTPUT = save_inc($HTTP_POST_VARS);
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

	core_connect();
	$sql = "SELECT batchid FROM batch WHERE proc = 'no' AND div = '".USER_DIV."'";
	$Rs = db_exec($sql) or errdie("Batch file unreachable.");
	if(pg_numrows($Rs) > 0){
		$sum = pg_numrows($Rs);
		$out = pg_fetch_array($Rs);
		$note = "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
	}else{
		$note = "";
	}


	$view = "
	<h3>Income Statement</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	$note
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

function inc($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
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
        $income = "<table>
		<tr><td colspan=2 align=center><h2>Income</h2></td></tr>
		<tr><td><b><u>Account Name</u></b></td><td><b><u>Amount</u></b></td></tr>";


        # get account Balances

        $tlinc = 0; // total income credit
        $i =0;
        while($inc = pg_fetch_array($incRslt)){
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$inc[accid]' AND div = '".USER_DIV."'";
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
                $income .= "<tr><td>$bal[accname]</td><td>".CUR." $total</td></tr>";
                $i++;
        }

        # write totals for income
        $income .= "<tr><td colspan=2><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td><b>".CUR." $tlinc</b></td></tr>";

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '".USER_DIV."'";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement Settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There are no accounts under Expenditures on the income statement Settings table.<br>Please Set the Income Statement";
        }

        $income .= "<tr><td colspan=2></td></tr>
		<tr><td colspan=2 align=center><h2>Expenditure</h2></td></tr>
		<tr><td><b><u>Account Name</u></b></td><td><b><u>Amount</u></b></td></tr>";

        # get account Balances for Expenditure
        $tlexp = 0; // total expenditures

        $i =0;
        while($exp = pg_fetch_array($expRslt)){

                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$exp[accid]' AND div = '".USER_DIV."'";
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

                $income .= "<tr><td>$bal[accname]</td><td>".CUR." $total</td></tr>";
                $i++;
        }
        $income .= "<tr><td colspan=2><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total<b></td><td><b>".CUR." $tlexp</b></td></tr>
		<tr><td colspan=2><br></td></tr>";

        # Calculate Profit/Loss
        $income .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Nett Profit Carried Forward<b></td><td><b>".CUR." ".($tlinc-$tlexp)."</b></td></tr>";

		# Send the stream
		include("temp.xls.php");
		Stream("IncomeStatement", $income);
}

function save_inc($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
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
                $sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$inc[accid]' AND div = '".USER_DIV."'";
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
                $sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$exp[accid]' AND div = '".USER_DIV."'";
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




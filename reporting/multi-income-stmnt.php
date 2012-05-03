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
require("../settings.php");
require("../core-settings.php");

# Decide what to do
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

	$view = "
	<h3>Income Statement</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

function inc($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # connect to core DB
        core_connect();

        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype = 'I' ORDER BY accname ASC";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center>There are no accounts under income on the income statement Settings table.<br>Please Set the Income Statement";
        }
        // Set up the Table to display in
        $income = "<center><h3>Income Statement</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
        <tr><th width=70%>Account Name</th><th>Amount</th></tr>
        <tr><td colspan=3><h3>Income</h3></td></tr>";

        # get account Balances

        $tlinc = 0; // total income credit
        $i =0;
        while($inc = pg_fetch_array($incRslt)){
				core_connect();
				# get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]' ORDER BY accname ASC";
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
				$branname = branname($bal['div']);
				$tlinc += $total;
                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname] - $branname</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

		$tlinc = sprint($tlinc);

        # write totals for income
        $income .= "<tr class='bg-odd'><td><b>Total<b></td><td align=center><b>".CUR." $tlinc</b></td></tr>";

		core_connect();
        # get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' ORDER BY accname ASC";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement Settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There are no accounts under Expenditures on the income statement Settings table.<br>Please Set the Income Statement";
        }

        $income .= "<tr><td colspan=3><h3>Expenditure</h3></td></tr>";

        # get account Balances for Expenditure
        $tlexp = 0; // total expenditures

        $i =0;
        while($exp = pg_fetch_array($expRslt)){
				core_connect();
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]' ORDER BY accname ASC";
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
				$branname = branname($bal['div']);
                $income .= "<tr bgcolor='$bgColor'><td>$bal[accname] - $branname</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

		$tlexp = sprint($tlexp);

        $income .= "<tr class='bg-odd'><td><b>Total<b></td><td align=center><b>".CUR." $tlexp</b></td></tr>
		<tr><td colspan=3><br></td></tr>";

        # Calculate Profit/Loss
        $income .= "<tr class='bg-even'><td><b>Nett Profit Carried Forward<b></td><td align=center colspan=2><b>".CUR." ".($tlinc-$tlexp)."</b></td></tr>
		<tr><td><br></td></tr>
		<tr><td>
			<table width=100%>
			<tr><td align=center>
				<form action='".SELF."' method=post name=form>
				<input type=hidden name=key value=printsave>
				<input type=hidden name=zero value='$zero'>
				<input type=submit value='Save'>
				</form>
			</td>

			<!--
			<td align=center>
				<form action='../pdf/income-stmnt-pdf.php' method=post name=form>
				<input type=hidden name=key value=print>
				<input type=hidden name=zero value='$zero'>
				<input type=submit value='View PDF'>
				</form>
			</td>
			-->

			</tr></table>
		</td>

		<!--
		<td>
			<form action='../xls/income-stmnt-xls.php' method=post name=form>
			<input type=hidden name=key value=print>
			<input type=hidden name=zero value='$zero'>
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
		</td>
		-->

		</tr>
		</table>
		<p>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=20%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
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
        $sql = "SELECT accid FROM accounts WHERE acctype = 'I' ORDER BY accname ASC";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center>There are no accounts under income on the income statement Settings table.<br>Please Set the Income Statement";
        }
        // Set up the Table to display in
        $income = "<center><h3>Income Statement as at : ".date("d M Y")."</h3>
		<table cellpadding='3' cellspacing='0' border=1 bordercolor='#000000' width=750>
        <tr><th width=70%>Account Name</th><th>Amount</th></tr>
        <tr><td colspan=3><h3>Income</h3></td></tr>";

        # get account Balances

        $tlinc = 0; // total income credit
        $i =0;
        while($inc = pg_fetch_array($incRslt)){
				core_connect();
				# get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]' ORDER BY accname ASC";
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
				$branname = branname($bal['div']);
                $income .= "<tr><td>$branname - $bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

		$tlinc = sprint($tlinc);

        # Write totals for income
        $income .= "<tr><td><b>Total<b></td><td align=center><b>".CUR." $tlinc</b></td></tr>";

		core_connect();
        # Get the income statement settings
        $sql = "SELECT accid FROM accounts WHERE acctype='E' ORDER BY accname ASC";
        $expRslt = db_exec($sql) or errDie("Unable to retrieve income statement Settings from the Database",SELF);
        if(pg_numrows($expRslt) < 1){
                return "<center>There are no accounts under Expenditures on the income statement Settings table.<br>Please Set the Income Statement";
        }

        $income .= "<tr><td colspan=3><h3>Expenditure</h3></td></tr>";

        # get account Balances for Expenditure
        $tlexp = 0; // total expenditures

        $i =0;
        while($exp = pg_fetch_array($expRslt)){
				core_connect();
                # get the balances (debit nad credit) from trial Balance
                $sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]' ORDER BY accname ASC";
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
				$branname = branname($bal['div']);
                $income .= "<tr><td>$branname - $bal[accname]</td><td align=center>".CUR." $total</td></tr>";
                $i++;
        }

		$tlexp = sprint($tlexp);

        $income .= "<tr><td><b>Total<b></td><td align=center><b>".CUR." $tlexp</b></td></tr>
		<tr><td colspan=3><br></td></tr>";

        # Calculate Profit/Loss
        $income .= "<tr><td><b>Nett Profit Carried Forward<b></td><td align=center colspan=2><b>".CUR." ".sprint($tlinc-$tlexp)."</b></td></tr>
		<tr><td><br></td></tr>
		</table>";

		$output = base64_encode($income);
		core_connect();
		$sql = "INSERT INTO save_income_stmnt(gendate, output, div) VALUES('".date("Y-m-d")."', '$output', '".USER_DIV."')";
		$Rs = db_exec($sql) or errdie("Unable to save the Income Statement.");

		$income .= "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=20%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $income;
}
?>




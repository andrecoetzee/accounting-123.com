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

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
			case "printsave":
				$OUTPUT = save_bal();
				break;

			default:
				$OUTPUT = bal_sheet();
	}
} else {
        # Display default output
        $OUTPUT = bal_sheet();
}

# get templete
require("../template.php");

# Balance Sheet
function bal_sheet()
{
        # Get Owners Equity Sub Headings
        $oesubRslt = undget("core", "*", "bal_sheet", "type", "OESUB' AND div = '11111111");
        $sheet = "<center>
        <h3> Balance sheet for period : ".PRD_NAME."<br><br>Date: ".date("d M Y")." </h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
        <tr><th colspan=3>Owners Equity</th></tr>";

        # get accounts
        $oebal = 0; // OE Balance
        while($oesub = pg_fetch_array($oesubRslt)){
			$sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$oesub[value]<b></td></tr>";

			$sql = "SELECT * FROM bal_sheet WHERE type ='OEACC' AND ref = $oesub[ref] AND div = 11111111";
			$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
			# get account BAlances
			while($acc = pg_fetch_array($accRslt)){
				list($topacc, $accnum) = explode("/", $acc['value']);

				# Get balance
				$query = "SELECT sum(credit) as credit,sum(debit) as debit FROM trial_bal WHERE topacc = '$topacc' AND accnum = '$accnum'";
				$balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
				$accbal = pg_fetch_array($balRslt);

				$balance = ($accbal['credit'] - $accbal['debit']);
				$oebal += $balance;
				$balacc = getaccnum($acc['value']);
				$sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$balacc[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
			}
        }
		# print Net Income and OE Balance on last column
        $netincome = getNetIncome();
        $oebal += sprint($netincome);
		$oebal = sprint($oebal);

		$sheet .="<tr bgcolor='".TMPL_tblDataColor1."'><td>&nbsp<b>Net Income</b></td><td>".CUR." $netincome</td><td><br></td></tr>";
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $oebal</b></td></tr>";

        # Get Assets Sub Headings
        $abal = 0; // Assets Balance
        $asssubRslt = undget("core", "*", "bal_sheet", "type", "ASSSUB' AND div = '11111111");
        $sheet .= "<tr><th colspan=3>Assets</th></tr>";

        # get accounts
        while($asssub = pg_fetch_array($asssubRslt)){
			$sheet .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><b>$asssub[value]<b></td></tr>";

			$sql = "SELECT * FROM bal_sheet WHERE type ='ASSACC' AND ref = $asssub[ref] AND div = 11111111";
			$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
			# get account BAlances
			while($acc = pg_fetch_array($accRslt)){
				list($topacc, $accnum) = explode("/", $acc['value']);

				# Get Balance
				$query = "SELECT sum(credit) as credit,sum(debit) as debit FROM trial_bal WHERE topacc = '$topacc' AND accnum = '$accnum'";
				$balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
				$accbal = pg_fetch_array($balRslt);

				$balance = ($accbal['debit'] - $accbal['credit']); // calc Balance
				$abal += $balance;
				$balacc = getaccnum($acc['value']);
				$sheet .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><blockquote><li>$acc[value] - $balacc[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
			}
        }

		$abal = sprint($abal);

        # print assets balance on last column
        $sheet .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td><td><b>".CUR." $abal</b></td></tr>
		<tr><td><br></td></tr>
		<tr><td align=center>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=printsave>
			<input type=submit value='Save'>
			</form>
		</td>

		<!--
		<td>
			<form action='../pdf/bal-sheet-pdf.php' method=post name=form>
			<input type=hidden name=key value=print>
			<input type=submit name=xls value='View PDF'>
			</form>
		</td>
		<td>
			<form action='../xls/bal-sheet-xls.php' method=post name=form>
			<input type=hidden name=key value=print>
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
		</td>
		-->

		</tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $sheet;
}

# Get account info by accno (XXX/XXX)
function getaccnum($accno){
	list($topacc, $accnum) = explode("/", $accno);
	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE accnum = '$accnum' AND topacc = '$topacc' LIMIT 1";
	$acccRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$acc = pg_fetch_array ($acccRslt);

	# Return array
	return $acc;
}


# Balance Sheet
function save_bal()
{
	# Get Owners Equity Sub Headings
	$oesubRslt = undget("core", "*", "bal_sheet", "type", "OESUB' AND div = '11111111");
	$sheet = "<center>
	<h3> Balance sheet for period : ".PRD_NAME."<br><br>Date: ".date("d M Y")." </h3>
	<table cellpadding='3' cellspacing='0' border=1 bordercolor='#000000' width=750>
	<tr><th colspan=3>Owners Equity</th></tr>";

	# get accounts
	$oebal = 0; // OE Balance
	while($oesub = pg_fetch_array($oesubRslt)){
		$sheet .= "<tr><td colspan=3><b>$oesub[value]<b></td></tr>";

		$sql = "SELECT * FROM bal_sheet WHERE type ='OEACC' AND ref = $oesub[ref] AND div = 11111111";
		$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
		# get account BAlances
		while($acc = pg_fetch_array($accRslt)){
			list($topacc, $accnum) = explode("/", $acc['value']);

			# Get balance
			$query = "SELECT sum(credit) as credit,sum(debit) as debit FROM trial_bal WHERE topacc = '$topacc' AND accnum = '$accnum'";
			$balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
			$accbal = pg_fetch_array($balRslt);

			$balance = ($accbal['credit'] - $accbal['debit']);
			$oebal += $balance;
			$balacc = getaccnum($acc['value']);
			$sheet .= "<tr><td><blockquote><li>$balacc[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
		}
	}
	$oebal = sprint($oebal);
	# print Net Income and OE Balance on last column
	$netincome = getNetIncome();
	$oebal += $netincome;
	$sheet .="<tr><td>&nbsp<b>Net Income</b></td><td>".CUR." $netincome</td><td><br></td></tr>";
	$sheet .="<tr><td colspan=2><b>Total</b></td><td><b>".CUR." $oebal</b></td></tr>";

	# Get Assets Sub Headings
	$abal = 0; // Assets Balance
	$asssubRslt = undget("core", "*", "bal_sheet", "type", "ASSSUB' AND div = '11111111");
	$sheet .= "<tr><th colspan=3>Assets</th></tr>";

	# get accounts
	while($asssub = pg_fetch_array($asssubRslt)){
		$sheet .= "<tr><td colspan=3><b>$asssub[value]<b></td></tr>";

		$sql = "SELECT * FROM bal_sheet WHERE type ='ASSACC' AND ref = $asssub[ref] AND div = 11111111";
		$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
		# get account BAlances
		while($acc = pg_fetch_array($accRslt)){
			list($topacc, $accnum) = explode("/", $acc['value']);

			# Get Balance
			$query = "SELECT sum(credit) as credit,sum(debit) as debit FROM trial_bal WHERE topacc = '$topacc' AND accnum = '$accnum'";
			$balRslt = db_exec($query) or errDie("Unable to retrieve Account Balances from the Database.",SELF);
			$accbal = pg_fetch_array($balRslt);

			$balance = ($accbal['debit'] - $accbal['credit']); // calc Balance
			$abal += $balance;
			$balacc = getaccnum($acc['value']);
			$sheet .= "<tr><td><blockquote><li>$acc[value] - $balacc[accname]</td><td>".CUR." $balance</td><td><br></td></tr>";
		}
	}

	$abal = sprint($abal);

	# print assets balance on last column
	$sheet .="<tr><td colspan=2><b>Total</b></td><td><b>".CUR." $abal</b></td></tr>
	</table><br>";

	$output = base64_encode($sheet);
	core_connect();
	$sql = "INSERT INTO save_bal_sheet(gendate, output, div) VALUES('".date("Y-m-d")."', '$output', '".USER_DIV."')";
	$Rs = db_exec($sql) or errdie("Unable to save the Balance Sheet.");

	$sheet .="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
	<tr><th>Quick Links</th></tr>
	<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $sheet;
}

// get total income
function getNetIncome()
{
	# get the income statement settings
	core_connect();
	$sql = "SELECT accid FROM accounts WHERE acctype='I'";
	$incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($incRslt) < 1){
			return "<center>There Are no Income in Cubit.";
	}

	# get income accounts Balances
	$tlinc = 0; // total income credit

	while($inc = pg_fetch_array($incRslt)){
		# get the balances (debit nad credit) from trial Balance
		$sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]'";
		$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
		$bal = pg_fetch_array($balRslt);

		$total = ($bal['credit'] - $bal['debit']);
		$tlinc += $total;
	}

	# get the income statement settings
	$sql = "SELECT accid FROM accounts WHERE acctype='E'";
	$expRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($expRslt) < 1){
			return "<center>There Are no Expenditure accounts in Cubit.";
	}

	# get account Balances for Expenditure
	$tlexp = 0; // total expenditures

	while($exp = pg_fetch_array($expRslt)){

			#get vars from inc (accnum, type)
			foreach($exp as $key => $value){
					$$key = $value;
			}

			# get the balances (debit nad credit) from trial Balance
			$sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]'";
			$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
			$bal = pg_fetch_array($balRslt);

			# alternate bgcolor
			$total = ($bal['debit'] - $bal['credit']);
			$tlexp += $total;        // And increment the balance for expenditure
	}
	return sprintf("%01.2f", ($tlinc - $tlexp));
}
?>

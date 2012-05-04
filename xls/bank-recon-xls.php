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
require ("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "out":
				if(isset($_POST["upBtn"])){
					$OUTPUT = update($_POST);
				}else{
					$OUTPUT = cashbook($_POST);
				}
				break;
			case "save":
				$OUTPUT = save($_POST);
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("../template.php");


# Default view
function view()
{
	// main layout
	$view = "<h3>Bank Reconciliation</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=out>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Bank Account</td>
	<td valign=center><select name=bankid>";

	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."'";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
		return "<li class=err> There are no bank accounts yet in Cubit.";
	}

	while($acc = pg_fetch_array($banks)){
		$view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname]</option>";
	}

	$view .= "</select></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

function cashbook($_POST, $err="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# keep selected items : banked[]
	if(isset($banked)){
		$recon = "0,0";
		foreach($banked as $key => $value){
			$recon .= ",$value";
		}
	}else{
		if(!isset($sbal)){
			$sbal = 0;
			$cbal = 0;
		}
		$recon = "0,0";
	}

	# Get account name for bank account
	db_connect();
	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
			return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);

	// Layout
	$cashbook = "<center>
	<h3>Bank Reconciliation</h3>
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=out>
	<input type=hidden name=bankid value='$bankid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td colspan=10>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100%>
			<tr class='bg-odd'><th>Bank Account</th><td>$bank[accname]</td><th>Opening Balance</th><td>".CUR." <input type=text name=sbal size=12 value='$sbal'><th>Closing Balance</th><td>".CUR." <input type=text name=cbal size=12 value='$cbal'></td></tr>
		</table>
	</td></tr>
	<tr><td><br>$err<br></td></tr>
	<tr><th>Reference</th><th>Date</th><th>Payments</th><th>Receipts</th><th>Reconcille</th></tr>";

		// Connect to database
		db_Connect ();
        $sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
        $cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database.", SELF);

        if (pg_numrows($cashRslt) < 1) {
			$cashbook = "<li class=err> There are no outstanding Bank transactions for the selected account.</li>";
			return $cashbook;
		}

		# display all bank trans
		$totout = 0;
		$totdep = 0;
		$totpay = 0;
		$totrecon = 0;
		for ($i=0; $tran = pg_fetch_array ($cashRslt); $i++) {

			# get account name for account involved
			$accRslt = get("core", "accname", "accounts", "accid", $tran['accinv']);
			$acc = pg_fetch_array($accRslt);

			$cashbook .= "<tr class='".bg_class()."'><td>$tran[descript]</td><td align=center>$tran[date]</td>";

			# If it is checked
			$arrecon = explode(",", $recon);

			if(!in_array($tran['cashid'], $arrecon)){
				$chk = "";
			}else{
				$chk = "checked=yes";
				if($tran['trantype'] == "deposit"){
					$totdep += $tran['amount'];
				}else{
					$totpay += $tran['amount'];
				}
			}

			if($tran['trantype'] == "deposit"){
				$totout += $tran['amount'];
				$cashbook .= "<td></td><td>".CUR." $tran[amount]</td><td><input type=checkbox name='banked[]' value='$tran[cashid]' $chk onClick='document.form1.submit()'></td></tr>";
			}else{
				$totout -= $tran['amount'];
				$cashbook .= "<td>".CUR." $tran[amount]</td><td></td><td><input type=checkbox name='banked[]' value='$tran[cashid]' $chk onClick='document.form1.submit()'></td></tr>";
			}
		}

	$reconbal = sprint($sbal + ($totdep - $totpay));
	$sysbal = sprint($bal['bal']);

	$cashbook .= "
	<tr class='bg-even'><td colspan=2><b>System Bank Balance + Oustanding Amounts</b></td><td colspan=4 align=right><b>".CUR." $sysbal</b></td></tr>
	<tr><td><br><br><td></tr>
	<input type=hidden name=recon value='$recon'>
	<tr class='bg-odd'><td colspan=2><b>Reconciled Bank Balance</b></td><td align=right colspan=4><b>".CUR." $reconbal</b></td></tr>
	<tr class='bg-odd'><td colspan=2><b>Closing Bank Balance</b></td><td align=right colspan=4><b>".CUR." $cbal</b></td></tr>
	<tr><td><br><td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit name='upBtn' value='Update'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";



	return $cashbook;
}

function update($_POST)
{
	# Get Vars ( banked[] )
	foreach($_POST as $key => $value){
			$$key = $value;
	}

	# Check if anything is selected
	if(!isset($banked)){
			$err = "<li class=err> Please Select at least one entry to update.";
			return cashbook($_POST, $err);
	}

	/* - Start Hooks - */
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	$refnum = getrefnum();
/*refnum*/

	# Record all trans
	$tot = 0;
	$totr = 0;
	$totp = 0;
	$recpts = "";
	$paymnts = "";
	foreach($banked as $key => $cashid){
		// Connect to database
		db_Connect ();
		$sql = "SELECT * FROM cashbook WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve details from database.", SELF);
		$cash = pg_fetch_array ($cashRslt);

		# Set record as banked
		db_connect();
		$sql = "UPDATE cashbook SET banked = 'yes' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to set bank deposit as banked in Cubit.",SELF);
	}

	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database.", SELF);

	$tot = 0;
	$totr = 0;
	$totp = 0;
	$recpts = "";
	$paymnts = "";
	while($cash = pg_fetch_array ($cashRslt)){
		if($cash['trantype'] == "deposit"){
			$recpts .= "<tr><td>$cash[date]</td><td>$cash[descript]</td><td align=right>".CUR." $cash[amount]</td></tr>";
			$totr += $cash['amount'];
		}else{
			$paymnts .= "<tr><td>$cash[date]</td><td>$cash[descript]</td><td align=right>".CUR." $cash[amount]</td></tr>";
			$totp += $cash['amount'];
		}
		$tot += $cash['amount'];
	}

	$reconbal = sprint($cbal + ($totr - $totp));

	# Get account name for bank account
	db_connect();
	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
			return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);

	$diff = ($reconbal - $bal['bal']);

	$derr = "";
	if($diff <> 0){
		$derr = "<tr><td colspan=2><b class=err>Bank statement and computer balance not balancing by</b></td><td align=right>".CUR." $diff</td></tr>";
	}

	// Layout
	$update = "<table border=1>
	<tr><th colspan=3><h3>Bank Reconciliation Output</h3></th></tr>
	<tr><td></td></tr>
	<tr><td colspan=3>
		<table cellpadding='2' cellspacing='0' border=0 bordercolor='#000000' width=100%>
			<tr><td><b>Bank Account : </b>$bank[accname]</td><td></td><td align=right><b>Prepared By : </b>".USER_NAME."</td></tr>
			<tr><td><b>Closing Balance As per Bank Statement : </b>".CUR." $cbal</td><td></td></tr>
		</table>
	</td></tr>
	<tr><td><br><br></td></tr>
	<tr><td colspan=3><b>Plus Outstanding Receipts :</b></td></tr>
	<tr><th>Date</th><th>Reference</th><th>Amount</th></tr>
	$recpts
	<tr><td colspan=2><br></td><td align=right>____________</td></tr>
	<tr><td colspan=2 align=right><b>Sub Total</b></td><td align=right>".CUR." $totr</td></tr>
	<tr><td><br><br></td></tr>
	<tr><td colspan=3><b>Less Outstanding Payments :</b></td></tr>
	<tr><th>Date</th><th>Reference</th><th>Amount</th></tr>
	$paymnts
	<tr><td colspan=2><br></td><td align=right>____________</td></tr>
	<tr><td colspan=2 align=right><b>Sub Total</b></td><td align=right>".CUR." $totp</td></tr>
	<tr><td><br><td></tr>
	$derr
	<tr><td><br><td></tr>
	<tr><td colspan=2><br></td><td align=right>____________</td></tr>
	<tr><td colspan=2><b>Reconciled Bank Balance</b></td><td align=right>".CUR." $reconbal</td></tr>
	<tr><td colspan=2><b>Computer Bank Balance</b></td><td align=right>".CUR." $bal[bal]</td></tr>
	<tr><td colspan=2><br></td><td align=right>____________</td></tr>
	<tr><td colspan=2><b>Diff</b></td><td align=right>".CUR." $diff</td></tr>
	<tr><td colspan=2><br></td><td align=right>____________</td></tr>
	</table>";

	# Send the stream
	include("temp.xls.php");
	return Stream("BankRecon", $update);
}

# Save
function save($_POST)
{
	# Get Vars ( banked[] )
	foreach($_POST as $key => $value){
			$$key = $value;
	}

	core_connect();
	$gendate = date("Y-m-d");
	$sql = "INSERT INTO save_bank_recon(bankid, gendate, recon, div) VALUES('$bankid', '$gendate', '$upcode', '".USER_DIV."')";
	$saveRslt = db_exec($sql) or errDie("Unable to save bank recon to database",SELF);

	$update = base64_decode($upcode);
	$update .= "<h3>Saved</h3>";

	$OUTPUT = $update;
	require("../tmpl-print.php");
}
?>

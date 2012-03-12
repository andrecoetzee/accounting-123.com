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

// Get global variables & functions
require ("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "out":
			if(isset($_POST["upBtn"])){
				$OUTPUT = confirm($_POST);
			}else{
				$OUTPUT = cashbook($_POST);
			}
			break;
		case "confirmed":
			$OUTPUT = update($_POST);
			break;
		case "save":
			$OUTPUT = save($_POST);
			break;
		default:
			# Display default output
			$OUTPUT = view();
	}
} else {
    $OUTPUT = view();
}

# get template
require("../template.php");



# Default view
function view()
{

	// main layout
	$view = "
		<h3>Bank Reconciliation</h3>
		<table ".TMPL_tblDflts." width='350'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='out'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td valign='center'>
					<select name='bankid'>";

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."'";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet in Cubit.";
	}
	while($acc = pg_fetch_array($banks)){
		$view .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$view .= "
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Output type</td>
				<td valign='center'><input type='radio' name='oput' value='no' checked='yes'>Normal </td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='View &raquo'></td>
			</tr>
		</form>
		</table>";
	return $view;

}



function cashbook($_POST, $err="")
{

	# get vars
	extract ($_POST);

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

	$sql = "SELECT * FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);
	$cur  = CUR;
	$amtd = "amount";
	if($bank['btype'] == 'int'){
		$currs = getSymbol($bank['fcid']);
		$cur = $currs['symbol'];
		$amtd = "famount";
	}

	# get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='12' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);
	if($bank['btype'] == 'int'){
		$bal['bal'] = $bank['fbalance'];
	}

	if($oput == "yes"){
		$url = "../xls/bank-recon-xls.php";
	}else{
		$url = SELF;
	}

	db_Connect ();

	// Reset the count variables
	$totout = 0;
	$totdep = 0;
	$totpay = 0;
	$totrecon = 0;



// 	if(!(isset($sort))) {
// 		$sort='Reference';
// 	}
//
// 	if($sort=="Date") {
// 		$s2="selected";
// 		$s1="";
// 		$ord="ORDER BY date";
// 	} else {
// 		$s1="selected";
// 		$s2="";
// 		$ord="ORDER BY descript";
// 	}
//
// 	$sorts="<select name=sort onChange='javascript:document.form1.submit();'>
// 	<option value='Reference' $s1>Reference</option>
// 	<option value='Date' $s2>Date</option>
// 	</select>";

	$orderby_ar = array (
		"descript ASC" => "Reference (Ascending)",
		"descript DESC" => "Reference (Descending)",
		"date ASC" => "Date (Ascending)",
		"date DESC" => "Date (Descending)",
		"amount ASC" => "Payments & Receipts (Ascending)",
		"amount DESC" => "Payments & Receipts (Descending)"
	);
	$ord = "";

	$sorts = "<select name='sort' onChange='javascript:document.form1.submit();'>";
	foreach ($orderby_ar as $key => $val) {
		if (isset($sort) && $sort == $key) {
			$selected = "selected";
			$ord = "ORDER BY $key";
		} else {
			$selected = "";
		}
		$sorts .= "<option value='$key' $selected>$val</option>";
	}
	$sorts .= "</select>";

	// Get all those received
	$sql = "
		SELECT sum($amtd),count(*) FROM cashbook 
		WHERE bankid = '$bankid' AND banked = 'no' AND trantype = 'withdrawal' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database for payments received.", SELF);

	if ( pg_num_rows($cashRslt) > 0 ) {
		$all_withdraw_amount = sprint(pg_fetch_result($cashRslt, 0, 0));
		$count_withdraw = pg_fetch_result($cashRslt, 0, 1);
	} else {
		$all_withdraw_amount = 0;
		$count_withdraw = 0;
	}
	// Get all those payed
	$sql = "
		SELECT sum($amtd),count(*) FROM cashbook 
		WHERE bankid = '$bankid' AND banked = 'no' AND trantype = 'deposit' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database for payments deposited.", SELF);

	if ( pg_num_rows($cashRslt) > 0 ) {
		$all_deposit_amount = sprint(pg_fetch_result($cashRslt, 0, 0));
		$count_deposit = pg_fetch_result($cashRslt, 0, 1);
	} else {
		$all_deposit_amount = 0;
		$count_deposit = 0;
	}

	if (isset ($updateBtn)){
		$upd_sql = "UPDATE cashbook SET bankrecon_ticked = 'no'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update cashbook information.");
		foreach ($banked AS $each => $own){
			$upd_sql = "UPDATE cashbook SET bankrecon_ticked = 'yes' WHERE cashid = '$own'";
			$run_upd = db_exec ($upd_sql) or errDie ("Unable to update bank reconciliation information.");
		}
	}

	vsprint($sbal);
	vsprint($cbal);

	// Layout
	$cashbook = "
		<center>
		<form action='$url' method='POST' name='form1'>
			<h3>Bank Reconciliation for $bank[accname] - $bank[bankname]</h3>
			<b>SORT BY:</b> $sorts
			<input type='hidden' name='key' value='out'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='oput' value='$oput'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr bgcolor='".bgcolorg()."'>
							<th>Opening Balance</th>
							<td>$cur <input type='text' name='sbal' size='12' value='$sbal'></td>
							<th>Closing Balance</th>
							<td>$cur <input type='text' name='cbal' size='12' value='$cbal'></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='6'><br>$err<br></td>
			</tr>
			<tr>
				<th colspan='2'>Description</th>
				<th>Transactions</th>
				<th>Amount</th>
				<th colspan='2'>Reconcile</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>All Payments</td>
				<td>$count_withdraw</td>
				<td>$cur $all_withdraw_amount</td>
				<td colspan='2' align='center'>
					<input type='submit' name='all_withdraw_recon_select' value='Select All'>
					<input type='submit' name='all_withdraw_recon_unselect' value='Unselect All'>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>All Deposits</td>
				<td>$count_deposit</td>
				<td>$cur $all_deposit_amount</td>
				<td colspan='2' align='center'>
					<input type='submit' name='all_deposit_recon_select' value='Select All'>
					<input type='submit' name='all_deposit_recon_unselect' value='Unselect All'>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Reference</th>
				<th>Date</th>
				<th>Cheque Number</th>
				<th>Reference</th>
				<th>Payments</th>
				<th>Receipts</th>
				<th>Reconcile</th>
			</tr>";

	// Connect to database
	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND banked = 'no' AND div = '".USER_DIV."' $ord";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database.", SELF);
	if (pg_numrows($cashRslt) < 1) {
		return "<li class='err'>There are no outstanding Bank transactions for the selected account.</li>";
	}

	# display all bank trans
	for ($i=0; $tran = pg_fetch_array ($cashRslt); $i++) {

		$update = ($i % 10) ? "" : "<tr><td colspan='7' align='right'><input type='submit' name='updateBtn' value='Update'></td></tr>";

		if(strlen($tran['accids']) > 0){
			$acc['accname'] = "Multiple Accounts";
			$acc['accnum'] = "000";
			$acc['topacc'] = "000";
		}else{
			# get account name for the account involved
			$AccRslt = get("core","accname","accounts", "accid", $tran['accinv']);
			$acc = pg_fetch_array($AccRslt);
		}

		/*
		# get account name for account involved
		$accRslt = get("core", "accname", "accounts", "accid", $tran['accinv']);
		$acc = pg_fetch_array($accRslt);
		*/

		if($tran['cheqnum'] == "0")
			$tran['cheqnum'] = "";

		$cashbook .= "
		$update
			<tr bgcolor='".bgcolorg()."'>
				<td>$tran[descript]</td>
				<td align='center'>$tran[date]</td>
				<td>$tran[cheqnum]</td>
				<td>$tran[reference]</td>";

		# If it is checked
		$arrecon = explode(",", $recon);
		if($tran['trantype'] == "deposit") {
			// only if the all deposit recon option was selected or this one was selected seperatly, do this one
			if ( ! isset($all_deposit_recon_unselect) &&
					(isset($all_deposit_recon_select) || in_array($tran['cashid'],$arrecon)) ) {
				$totdep += $tran[$amtd];
				$chk = "checked='yes'";
			}elseif (! isset($all_deposit_recon_unselect) && !isset ($updateBtn) && $tran["bankrecon_ticked"] == "yes") {
				$chk = "checked='yes'";
				$totdep += $tran[$amtd];
			}else {
				$chk = "";
			}
		} else {
			// only if the all withdraw recon option was selected or this one was selected seperatly, do this one
			if ( ! isset($all_withdraw_recon_unselect) &&
					(isset($all_withdraw_recon_select) || in_array($tran['cashid'],$arrecon)) ) {
				$totdep -= $tran[$amtd];
				$chk = "checked='yes'";
			}elseif (! isset($all_deposit_recon_unselect) && !isset ($updateBtn) && $tran["bankrecon_ticked"] == "yes") {
				$chk = "checked='yes'";
				$totdep -= $tran[$amtd];
			} else {
				$chk = "";
			}
		}

		if($tran['rid'] == 333 && !isset($busy)) {
			$chk = "checked";
		}

		if($tran['trantype'] == "deposit"){
			$totout += $tran[$amtd];
			vsprint($totout);
			vsprint($tran[$amtd]);
			$cashbook .= "
					<td>&nbsp;</td>
					<td>$cur $tran[$amtd]</td>
					<td><input type='checkbox' name='banked[]' value='$tran[cashid]' $chk ></td>
				</tr>";
		}else{
			$totout -= $tran[$amtd];
			vsprint($totout);
			vsprint($tran[$amtd]);
			$cashbook .= "
					<td>$cur $tran[$amtd]</td>
					<td valign='center'></td>
					<td><input type='checkbox' name='banked[]' value='$tran[cashid]' $chk ></td>
				</tr>";
		}
	}

	$reconbal = sprint($sbal + ($totdep - $totpay));
	$sysbal = sprint($bal['bal']);
	vsprint($cbal);

	$cashbook .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>System Bank Balance + Outstanding Amounts</b></td>
				<td colspan='4' align='right'><b>$cur $sysbal</b></td>
			</tr>
			".TBL_BR."
			<input type='hidden' name='recon' value='$recon' />
			<input type='hidden' name='oput' value='$oput' />
			<input type='hidden' name='busy' value='' />
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Reconciled Bank Balance</b></td>
				<td align='right' colspan='4'><b>$cur $reconbal</b></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Closing Bank Balance</b></td>
				<td align='right' colspan='4'><b>$cur $cbal</b></td>
			</tr>
			".TBL_BR."
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type='submit' name='upBtn' value='Reconcile'></td>
			</tr>
		</table>";
	return $cashbook;

}



function confirm($_POST)
{

	extract($_POST);

	# check if anything is selected
	if(!isset($banked)){
        $err = "<li class='err'> Please Select at least one entry to update.";
		return cashbook($_POST, $err);
	}

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	$refnum = getrefnum();
/*refnum*/

	# Record all trans
	$hide = "";
	foreach($banked as $key => $cashid){
		$hide .= "<input type='hidden' name='banked[]' value='$cashid'>";
	}

	// Connect to database
	db_Connect ();

	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database.", SELF);

	$sql = "SELECT * FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);
	$cur  = CUR;
	$amtd = "amount";
	if($bank['btype'] == 'int'){
		$currs = getSymbol($bank['fcid']);
		$cur = $currs['symbol'];
		$amtd = "famount";
	}

	$tot = 0;
	$totr = 0;
	$totp = 0;
	$recpts = "";
	$paymnts = "";
	while($cash = pg_fetch_array ($cashRslt)){
		# skip rivals
		if(in_array($cash['cashid'], $banked)){
			continue;
		}

		if($cash['cheqnum'] == "0")
			$cash['cheqnum'] = "";

		vsprint($cash[$amtd]);
		if($cash['trantype'] == "deposit"){
			$recpts .= "
				<tr>
					<td>$cash[date]</td>
					<td>$cash[descript]</td>
					<td>$cash[cheqnum]</td>
					<td>$cash[reference]</td>
					<td align='right'>$cur $cash[$amtd]</td>
				</tr>";
			$totr += $cash[$amtd];
		}else{
			$paymnts .= "
				<tr>
					<td>$cash[date]</td>
					<td>$cash[descript]</td>
					<td>$cash[cheqnum]</td>
					<td>$cash[reference]</td>
					<td align='right'>$cur $cash[$amtd]</td>
				</tr>";
			$totp += $cash[$amtd];
		}
		$tot += $cash[$amtd];
	}

	$reconbal = sprint($cbal + ($totr - $totp));
	vsprint($totr);
	vsprint($totp);

	# get hook account number
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='12' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);
	if($bank['btype'] == 'int'){
		$bal['bal'] = $bank['fbalance'];
	}

	$diff = sprint($reconbal - $bal['bal']);
	$derr = "";
	$conf = "<input type='submit' value='Write &raquo'>";
	if($diff <> 0){
		$derr = "
			<tr>
				<td colspan='4'><b class='err'>Bank statement and computer balance not balancing by</b></td>
				<td align='right'>$cur $diff</td>
			</tr>";
		$conf = "";
	}

	// Layout
	$update = "
		<center>
		<h3>Bank Reconciliation</h3>
		<h2>Confirm</h2>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='confirmed' />
			<input type='hidden' name='bankid' value='$bankid' />
			$hide
			<input type='hidden' name='cbal' value='$cbal' />
			<input type='hidden' name='sbal' value='$sbal' />
			<input type='hidden' name='oput' value='$oput' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td><b>Bank Account : </b>$bank[accname]</td>
							<td>&nbsp;</td>
							<td align='right'><b>Prepared By : </b>".USER_NAME."</td>
						</tr>
						<tr>
							<td><b>Closing Balance As per Bank Statement : </b>$cur $cbal</td>
							<td>&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><b>Plus Outstanding Receipts :</b></td>
			</tr>
			$recpts
			<tr>
				<td colspan='4'>&nbsp;</td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='4' align='right'><b>Sub Total</b></td>
				<td align='right'>$cur $totr</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><b>Less Outstanding Payments :</b></td>
			</tr>
			$paymnts
			<tr>
				<td colspan='4'>&nbsp;</td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='4' align='right'><b>Sub Total</b></td>
				<td align='right'>$cur $totp</td>
			</tr>
			".TBL_BR."
			$derr
			".TBL_BR."
			<tr>
				<td colspan='4'>&nbsp;</td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='4'><b>Reconciled Bank Balance</b></td>
				<td align='right'>$cur $reconbal</td>
			</tr>
			<tr>
				<td colspan='4'><b>Computer Bank Balance</b></td>
				<td align='right'>$cur $bal[bal]</td>
			</tr>
			<tr>
				<td colspan='4'><br></td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='4'><b>Diff</b></td>
				<td align='right'>$cur $diff</td>
			</tr>
			<tr>
				<td colspan='4'>&nbsp;</td>
				<td align='right'>____________</td>
			</tr>";

	$button = TBL_BR."
			<tr>
				<td align=right><input type='button' value='&laquo Back' onClick='javascript:history.back()' /></td>
				<td>$conf</td>
			</tr>
		</table>
		<p>
		<table class='quicklinks' ".TMPL_tblDflts.">
			<tr><th>Quick Links</th></tr>
			<tr><td><a href='index-reports.php'>Financials</a></td></tr>
			<tr><td><a href='index-reports-banking.php'>Banking Reports</a></td></tr>
			<tr><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		</p>
		</form>";

	$update .= $button;
	$OUTPUT = $update;
	require("../tmpl-print.php");

}



function update($_POST)
{

    # Get Vars ( banked[] )
    extract ($_POST);

    # check if anything is selected
	if(!isset($banked)){
		$err = "<li class='err'> Please Select at least one entry to update.";
		return cashbook($_POST, $err);
	}

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	$refnum = getrefnum();
/*refnum*/

	db_conn('core');

	$rid = pglib_lastid ("save_bank_recon", "id");
	$rid++;

	# Record all trans
	foreach($banked as $key => $cashid){
		# Connect to database
		db_Connect ();
		$sql = "SELECT * FROM cashbook WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve details from database.", SELF);
		$cash = pg_fetch_array ($cashRslt);

		# Set record as banked
		db_connect();
		$sql = "UPDATE cashbook SET banked = 'yes',rid='$rid' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
		$Rslt = db_exec ($sql) or errDie ("Unable to set bank deposit as banked in Cubit.",SELF);
	}

	// Connect to database
	db_connect ();

	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank transactions from database.", SELF);

	$sql = "SELECT * FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);
	$cur  = CUR;
	$amtd = "amount";
	if($bank['btype'] == 'int'){
		$currs = getSymbol($bank['fcid']);
		$cur = $currs['symbol'];
		$amtd = "famount";
	}

	$tot = 0;
	$totr = 0;
	$totp = 0;
	$recpts = "";
	$paymnts = "";
	while($cash = pg_fetch_array ($cashRslt)){
		if($cash['trantype'] == "deposit"){
			$recpts .= "
				<tr>
					<td>$cash[date]</td>
					<td>$cash[descript]</td>
					<td align='right'>$cur $cash[$amtd]</td>
				</tr>";
			$totr += $cash[$amtd];
		}else{
			$paymnts .= "
				<tr>
					<td>$cash[date]</td>
					<td>$cash[descript]</td>
					<td align='right'>$cur $cash[$amtd]</td>
				</tr>";
			$totp += $cash[$amtd];
		}
		$tot += $cash[$amtd];
	}

	$reconbal = sprint($cbal + ($totr - $totp));

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	# Get bank balance
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE period='12' AND accid = '$banklnk[accnum]' AND div = '".USER_DIV."'";
	$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	$bal = pg_fetch_array($brslt);
	if($bank['btype'] == 'int'){
		$bal['bal'] = $bank['fbalance'];
	}

	$diff = sprint($reconbal - $bal['bal']);
	$derr = "";
	if($diff <> 0){
		$derr = "
			<tr>
				<td colspan='2'><b class='err'>Bank statement and computer balance not balancing by</b></td>
				<td align='right'>$cur $diff</td>
			</tr>";
	}

	$totp = sprint ($totp);
	
	// Layout
	$update = "
		<center>
		<h3>Bank Reconciliation Output</h3>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='save'>
			<input type='hidden' name='bankid' value='$bankid'>
		<table cellpadding='2' cellspacing='0' border=0 bordercolor='#000000' width='80%'>
			<tr>
				<td colspan='10'>
					<table cellpadding='2' cellspacing='0' border=0 bordercolor='#000000' width=100%>
						<tr>
							<td><b>Bank Account : </b>$bank[accname]</td>
							<td></td>
							<td align='right'><b>Prepared By : </b>".USER_NAME."</td>
						</tr>
						<tr>
							<td><b>Closing Balance As per Bank Statement : </b>$cur $cbal</td>
							<td></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br><br></td></tr>
			<tr>
				<td><b>Plus Outstanding Receipts :</b></td>
			</tr>
			<!--<tr><th>Date</th><th>Reference</th><th>Amount</th></tr>-->
			$recpts
			<tr>
				<td colspan='2'><br></td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><b>Sub Total</b></td>
				<td align='right'>$cur $totr</td>
			</tr>
			<tr><td><br><br></td></tr>
			<tr>
				<td><b>Less Outstanding Payments :</b></td>
			</tr>
			<!--<tr><th>Date</th><th>Reference</th><th>Amount</th></tr>-->
			$paymnts
			<tr>
				<td colspan='2'><br></td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><b>Sub Total</b></td>
				<td align='right'>$cur $totp</td>
			</tr>
			<tr><td><br><td></tr>
			$derr
			<tr><td><br><td></tr>
			<tr>
				<td colspan='2'><br></td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='2'><b>Reconciled Bank Balance</b></td>
				<td align='right'>$cur $reconbal</td>
			</tr>
			<tr>
				<td colspan='2'><b>Computer Bank Balance</b></td>
				<td align='right'>$cur $bal[bal]</td>
			</tr>
			<tr>
				<td colspan='2'><br></td>
				<td align='right'>____________</td>
			</tr>
			<tr>
				<td colspan='2'><b>Diff</b></td>
				<td align='right'>$cur $diff</td>
			</tr>
			<tr>
				<td colspan='2'><br></td>
				<td align='right'>____________</td>
			</tr>";

	$upcode = base64_encode($update);

	$button = "</table></form>";

	core_connect();

	$gendate = date("Y-m-d");
	$sql = "
		INSERT INTO save_bank_recon (
			bankid, gendate, recon, div
		) VALUES (
			'$bankid', '$gendate', '$upcode', '".USER_DIV."'
		)";
	$saveRslt = db_exec($sql) or errDie("Unable to save bank recon to database",SELF);


	$update .= $button;

	$OUTPUT = $update;
	require("../tmpl-print.php");

}



function multi($cash, $chrgvat, $vatacc, $banklnk, $refnum){
}



function save($_POST)
{

	# Get Vars ( banked[] )
	extract ($_POST);

	core_connect();

	$gendate = date("Y-m-d");
	$sql = "
		INSERT INTO save_bank_recon (
			bankid, gendate, recon, div
		) VALUES (
			'$bankid', '$gendate', '$upcode', '".USER_DIV."'
		)";
	$saveRslt = db_exec($sql) or errDie("Unable to save bank recon to database",SELF);

	$update = base64_decode($upcode);
	$update .= "<h3>Saved</h3>";

	$OUTPUT = $update;
	require("../tmpl-print.php");

}


?>
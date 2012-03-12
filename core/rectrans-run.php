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
# rectrans-new.php :: Multiple debit-credit Transactions
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "cconfirm":
			$OUTPUT = cconfirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "writerem":
			$OUTPUT = writerem($_POST);
			break;
		default:
			if(isset($_POST['proc'])){
				# Process
				$OUTPUT = confirm($_POST);
			}elseif(isset($_POST["rem"])){
				# Remove
				$OUTPUT = confirmrem($_POST);
			}
	}
} else {
	if(isset($_POST["proc"])){
		# Process
		$OUTPUT = confirm($_POST);
	}elseif(isset($_POST["rem"])){
		# Remove
		$OUTPUT = confirmrem($_POST);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");



# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($run)){
		foreach($run as $key => $value){
			$v->isOk ($run[$key], "num", 1, 50, "Invalid Transaction number.");
		}
	}else{
		return "<li> - No Recurring Transactions Seleted. Please select at least one entry.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$refnum = getrefnum();
/*refnum*/



	$trans = "";

	# Transactions
	foreach($run as $key => $value){

		# connect to core
		core_Connect();

		# Get all the details
		$sql = "SELECT * FROM rectrans WHERE recid = '$value' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to access database.");
		$tran = pg_fetch_array($rslt);

		# get account to be debited
		$dtaccRs = get("core","accname,topacc,accnum","accounts","accid", $tran['debit']);
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$dtacc = pg_fetch_array($dtaccRs);

		# get account to be debited
		$ctaccRs = get("core","accname,topacc,accnum","accounts","accid",$tran['credit']);
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$ctacc = pg_fetch_array($ctaccRs);

		//script ignored the refnum given to it ???
		//".($refnum + $key)."
		$trans .= "
			<tr bgcolor=".bgcolorg().">
				<td nowrap>".mkDateSelecta("date",$key)."<input type='hidden' size='20' name='run[]' value='$value'></td>
				<td><input type='text' size='7' name='refnum[]' value='$tran[refnum]'></td>
				<td valign='center'><input type='hidden' name='dtaccid[]' value='$tran[debit]'>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td valign='center'><input type='hidden' name='ctaccid[]' value='$tran[credit]'>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
				<input type='hidden' name='vatcodes[]' value='$tran[vatcode]'>
				<td nowrap>".CUR." <input type='text' name='amount[]' size='8' value='$tran[amount]'></td>
				<td><input type='hidden' name='descript[]' value ='$tran[details]'>$tran[details]</td>";
		if($tran['chrgvat'] == "yes"){
			$vataccRs = get("core","*","accounts","accid",$tran['vataccid']);
			$vatacc  = pg_fetch_array($vataccRs);
			$vataccRs = get("core","*","accounts","accid",$tran['vatdedacc']);
			$vatdedacc  = pg_fetch_array($vataccRs);
			$trans .= "
				<input type='hidden' name='chrgvat[$value]' value ='yes'>
				<td align='center'><input type='hidden' name='vatinc[$value]' value ='$tran[vatinc]'>$tran[vatinc]</td>
				<td align='center'><input type='hidden' name='vataccid[$value]' value ='$tran[vataccid]'>$vatacc[accname]</td>
				<td align='center'><input type='hidden' name='vatdedacc[$value]' value ='$tran[vatdedacc]'>$vatdedacc[accname]</td>";
		}else{
			$trans .= "<td></td><td></td><td></td>";
		}
		$trans .= "</tr>";
	}

	$confirm = "
		<center>
		<h3>Process Recurring Journal Transaction(s)</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='cconfirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
				<th>VAT Inclusive</th>
				<th>VAT Account</th>
				<th>VAT Deductable Account</th>
			</tr>
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='rectrans-new.php'>Add Recurring Transaction</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}


# Confirm
function cconfirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($run)){
	foreach($run as $key => $value){
		$v->isOk ($run[$key], "num", 1, 50, "Invalid Transaction number.");
		$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
		$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		$v->isOk ($date_day[$key], "num", 1,2, "Invalid to Date day.");
		$v->isOk ($date_month[$key], "num", 1,2, "Invalid to Date month.");
		$v->isOk ($date_year[$key], "num", 1,4, "Invalid to Date Year.");
		$date[$key] = $date_day[$key]."-".$date_month[$key]."-".$date_year[$key];
		if(!checkdate($date_month[$key], $date_day[$key], $date_year[$key])){
			$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
		}
	}
	}else{
		return "<li> - No Recurring Transactions Seleted. Please select at least one entry.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# connect to core
	core_Connect();

	$trans = "";
	# Transactions
	foreach($run as $key => $value){

		# get account to be debited
		$dtaccRs = get("core","accname,topacc,accnum","accounts","accid", $dtaccid[$key]);
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$dtacc = pg_fetch_array($dtaccRs);

		# get account to be debited
		$ctaccRs = get("core","accname,topacc,accnum","accounts","accid", $ctaccid[$key]);
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$ctacc = pg_fetch_array($ctaccRs);

		$trans .= "
			<tr bgcolor=".bgcolorg().">
				<td>
					<input type='hidden' size='20' name='run[]' value='$value'>
					<input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]
				</td>
				<td><input type='hidden' size='7' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
				<td valign='center'><input type='hidden' name='dtaccid[]' value='$dtaccid[$key]'>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td valign='center'><input type='hidden' name='ctaccid[]' value='$ctaccid[$key]'>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
				<td>".CUR." <input type='hidden' name='amount[]' size='8' value='$amount[$key]'>$amount[$key]</td>
				<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
				<input type='hidden' name='vatcodes[]' value='$vatcodes[$key]'>";

		if(isset($chrgvat[$value])){
			$vataccRs = get("core","*","accounts","accid", $vataccid[$value]);
			$vatacc  = pg_fetch_array($vataccRs);
			$vataccRs = get("core","*","accounts","accid", $vatdedacc[$value]);
			$vdedacc  = pg_fetch_array($vataccRs);

// 						$VATP = TAX_VAT;

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
			$Ri = db_exec($Sl);

			$vd = pg_fetch_array($Ri);
			$VATP = $vd['vat_amount'];

			# if vat must be charged
			if($vatinc[$value] == "no"){
				$vatamt = sprint((($VATP/100) * $amount[$key]));
				$totamt = sprint($amount[$key] + $vatamt);
			}else{
				$vatamt = sprint((($amount[$key]/($VATP + 100)) * $VATP));
				$totamt = sprint($amount[$key]);
			}

			$trans .= "
				<input type='hidden' name='chrgvat[$value]' value ='yes'>
				<td align='center'><input type='hidden' name='vatinc[$value]' value ='$vatinc[$value]'>$vatinc[$value]</td>
				<td align='center'><input type='hidden' name='vatamt[$value]' value ='$vatamt'>".CUR." $vatamt</td>
				<td align='center'><input type='hidden' name='vataccid[$value]' value ='$vataccid[$value]'>$vatacc[accname]</td>
				<td align='center'><input type='hidden' name='vatdedacc[$value]' value ='$vatdedacc[$value]'>$vdedacc[accname]</td>";
		}else{
			$trans .= "<td></td><td></td><td></td><td></td>";
		}
		$trans .= "</tr>";

	}

	$confirm = "
		<center>
		<h3>Process Recurring Journal Transaction(s)</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
				<th>VAT Inclusive</th>
				<th>VAT Amount</th>
				<th>VAT Account</th>
				<th>VAT Deductable Account</th>
			</tr>
			$trans
			".TBL_BR."
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right' colspan='2'><input type='submit' value='Write Transaction &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><th>Quick Links</th></tr>
			<tr class='datacell'>
				<td align='center'><a href='rectrans-new.php'>Add Recurring Transaction</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}


# Write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	foreach($run as $key => $value){
		$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
		$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		$datea = explode("-", $date[$key]);
		if(count($datea) == 3){
			if(!checkdate($datea[1], $datea[0], $datea[2])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.[$key]");
			}
		}else{
			$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	foreach($run as $key => $value){
		# Accounts details
		$dtaccRs = get("core", "accname, topacc, accnum", "accounts", "accid", $dtaccid[$key]);
		$dtacc[$key]  = pg_fetch_array($dtaccRs);
		$ctaccRs = get("core", "accname, topacc, accnum", "accounts", "accid", $ctaccid[$key]);
		$ctacc[$key]  = pg_fetch_array($ctaccRs);

		if(isset($vataccid[$value])){
			$vatRs = get("core", "accname, topacc, accnum", "accounts", "accid", $vataccid[$value]);
			$vatacc[$key]  = pg_fetch_array($vatRs);
		}

		if(isset($chrgvat[$value])){
			if($vatinc[$value] == 'yes'){
				# Calculate amount
				$amt[$key] = sprint($amount[$key] - $vatamt[$value]);
				$totamt[$key] = sprint($amount[$key]);
			}else{
				# Calculate amount
				$amt[$key] = sprint($amount[$key]);
				$totamt[$key] = sprint($amount[$key] + $vatamt[$value]);
			}

			$datea = explode("-", $date[$key]);

			$cdate="$datea[2]-$datea[1]-$datea[0]";


			# Check VAt Deductable account
			if($vatdedacc[$value] == $dtaccid[$key]){

				db_connect();

				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					return "Please select the vatcode";
				}

				$vd = pg_fetch_array($Ri);

				vatr($vd['id'],$cdate,"INPUT",$vd['code'],$refnum[$key],"$descript[$key] VAT",-$totamt[$key],-$vatamt[$value]);

				writetrans($vataccid[$value], $ctaccid[$key], $date[$key], $refnum[$key], $vatamt[$value], $descript[$key]."  VAT");
				writetrans($dtaccid[$key], $ctaccid[$key], $date[$key], $refnum[$key], $amt[$key], $descript[$key]);
				isBankRec($dtaccid[$key], 'deposit', $date[$key], $ctacc[$key]['accname'], $descript[$key], 0, $amt[$key], $ctaccid[$key]);

				$amounts = "|$amt[$key]|$vatamt[$value]";
				$accids = "|$dtaccid[$key]|$vataccid[$value]";
				$vats = "|0|0";
				$chrgvats = "|nov|nov";
				isBankmRec($ctaccid[$key], 'withdrawal', $date[$key], $dtacc[$key]['accname'], $descript[$key], 0, $totamt[$key], $dtaccid[$key], $amounts, $accids, $vats, $chrgvats);
				//isBankRec($ctaccid[$key], 'withdrawal', $date[$key], $dtacc[$key]['accname'], $descript[$key], 0, $amt[$key], $dtaccid[$key]);

			}elseif($vatdedacc[$value] == $ctaccid[$key]){
				db_connect();
				$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
				$Ri = db_exec($Sl);

				if(pg_num_rows($Ri) < 1) {
					return "Please select the vatcode.";
				}

				$vd = pg_fetch_array($Ri);

				vatr($vd['id'],$cdate,"OUTPUT",$vd['code'],$refnum[$key],"$descript[$key] VAT",$totamt[$key],$vatamt[$value]);

				writetrans($dtaccid[$key], $vataccid[$value], $date[$key], $refnum[$key], $vatamt[$value], $descript[$key]."  VAT");
				writetrans($dtaccid[$key], $ctaccid[$key], $date[$key], $refnum[$key], $amt[$key], $descript[$key]);
				isBankRec($ctaccid[$key], 'withdrawal', $date[$key], $dtacc[$key]['accname'], $descript[$key], 0, $amt[$key], $dtaccid[$key]);

				$amounts = "|$amt[$key]|$vatamt[$value]";
				$accids = "|$ctaccid[$key]|$vataccid[$value]";
				$vats = "|0|0";
				$chrgvats = "|nov|nov";

				isBankmRec($dtaccid[$key], 'deposit', $date[$key], $ctacc[$key]['accname'], $descript[$key], 0, $totamt[$key], $ctaccid[$key], $amounts, $accids, $vats, $chrgvats);
				// isBankRec($dtaccid[$key], 'deposit', $date[$key], $ctacc[$key]['accname'], $descript[$key], 0, $amt[$key], $ctaccid[$key]);
			}
		}else{
			$totamt[$key] = sprint($amount[$key]);
			$vatamt[$value] = sprint(0);
			# Write normal transaction
			writetrans($dtaccid[$key],$ctaccid[$key], $date[$key], $refnum[$key], $totamt[$key], $descript[$key]);
			isBankRec($dtaccid[$key], 'deposit', $date[$key], $ctacc[$key]['accname'], $descript[$key], 0, $totamt[$key], $ctaccid[$key]);
			isBankRec($ctaccid[$key], 'withdrawal', $date[$key], $dtacc[$key]['accname'], $descript[$key], 0, $totamt[$key], $dtaccid[$key]);
		}
		# write transaction
		# writetrans($dtaccid[$key],$ctaccid[$key], $date[$key], $refnum[$key], $amount[$key], $descript[$key]);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Start layout
	$write = "
		<center>
		<h3>Recurring Transaction have been recorded</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>VAT Amount</th>
				<th>Total Transaction Amount</th>
				<th>Description</th>
			</tr>";

	foreach($run as $key => $value){
		$write .= "
			<tr bgcolor=".bgcolorg().">
				<td>$date[$key]</td>
				<td>$refnum[$key]</td>
				<td valign='center'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
				<td valign='center'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
				<td align='right'>".CUR." $amount[$key]</td>
				<td align='right'>".CUR." $vatamt[$value]</td>
				<td align='right'>".CUR." $totamt[$key]</td>
				<td>$descript[$key]</td>
			</tr>";
	}

	$write .= "
		</table>
		<br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


# Confirm
function confirmrem($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($run)){
		foreach($run as $key => $value){
			$v->isOk ($run[$key], "num", 1, 50, "Invalid Transaction number.");
		}
	}else{
		return "<li> - No Recurring Transactions Seleted. Please select at least one entry.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	core_connect();
    $trans = "";

	# Batches
	foreach($run as $key => $value){
		# Get all the details
		$sql = "SELECT * FROM rectrans WHERE recid = '$value' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to access database.");
		$tran = pg_fetch_array($rslt);

		# get account to be debited
		$dtaccRs = get("core","accname","accounts","accid",$tran['debit']);
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts to be debited does not exist";
		}
		$dtacc = pg_fetch_array($dtaccRs);

		# get account to be debited
		$ctaccRs = get("core","accname","accounts","accid",$tran['credit']);
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$ctacc = pg_fetch_array($ctaccRs);

		$trans .= "
			<tr bgcolor=".bgcolorg().">
				<td><input type='hidden' size='20' name='run[]' value='$value'>$tran[date]</td>
				<td>$tran[refnum]</td>
				<td valign='center'>$dtacc[accname]</td>
				<td valign='center'>$ctacc[accname]</td>
				<td>".CUR." $tran[amount]</td>
				<td>$tran[details]</td>
			</tr>";
	}

	$confirm = "
		<center>
		<h3>Remove Recurring Transactions</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='writerem'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>
			$trans
			".TBL_BR."
			<tr>
				<td align='right' colspan='2'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right' colspan='3'><input type='submit' value='Remove Transactions &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='rectrans-new.php'>Recurring Transaction</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}


# Write
function writerem($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($run)){
		foreach($run as $key => $value){
			$v->isOk ($run[$key], "num", 1, 50, "Invalid Transaction number.");
		}
	}else{
		return "<li> - No Recurring Transactions Seleted. Please select at least one entry.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	core_Connect();

	foreach($run as $key => $value){
		# Get all the details
		$sql = "SELECT * FROM rectrans WHERE recid = '$value' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to access database.");
		$tran = pg_fetch_array($rslt);

		// Accounts details
		$dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$tran['debit']);
		$dtacc[$key]  = pg_fetch_array($dtaccRs);
		$ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$tran['credit']);
		$ctacc[$key]  = pg_fetch_array($ctaccRs);

		$date[$key] = $tran['date'];
		$refnum[$key] = $tran['refnum'];
		$amount[$key] = $tran['amount'];
		$descript[$key] = $tran['details'];

		# Remove the entries one by one
		core_Connect();
		$query = "DELETE FROM rectrans WHERE recid = '$run[$key]'";
		$Ex = db_exec($query) or errDie("Unable to delete batch file entries.",SELF);
	}

	// Start layout
	$write = "
		<center>
		<h3>Recurring Transactions have been removed</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	foreach($run as $key => $value){
		$write .= "
			<tr bgcolor=".bgcolorg().">
				<td>$date[$key]</td>
				<td>$refnum[$key]</td>
				<td valign='center'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
				<td valign='center'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
				<td>".CUR." $amount[$key]</td>
				<td>$descript[$key]</td>
			</tr>";
	}

	$write .= "
		</table>
		<br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='rectrans-new.php'>Add Recurring Transaction</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='rectrans-view.php'>View Recurring Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>

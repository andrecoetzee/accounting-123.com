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
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			if(isset($HTTP_GET_VARS['ledgid'])){
				$OUTPUT = run($HTTP_GET_VARS);
			}else{
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
			}
	}
} else {
	if(isset($HTTP_GET_VARS['ledgid'])){
		$OUTPUT = run($HTTP_GET_VARS);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("../template.php");



# Select Accounts
function run($HTTP_GET_VARS)
{

	# Get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ledgid, "num", 1, 20, "Invalid Input Ledger Number.");

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

	# Get ledger settings
	core_connect();

	$sql = "SELECT * FROM in_ledgers WHERE ledgid='$ledgid' AND div = '".USER_DIV."'";
	$ledRslt = db_exec($sql);
	if(pg_numrows($ledRslt) < 1){
		return "<li>Invalid Input Ledger Number.</li>";
	}
	$led = pg_fetch_array($ledRslt);

	# account numbers
	$dtaccRs = get("core","*","accounts","accid",$led['dtaccid']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$led['ctaccid']);
	$ctacc  = pg_fetch_array($ctaccRs);

	$sysrefnum = getrefnum();
/*refnum*/

	/* Options generated vars */

	# check refnum option
	switch($led['refopt']){
		case "num":
			$refopt = "<input type='hidden' size='5' name='refnum[]' value='$sysrefnum'>$sysrefnum";
			break;
		case "emp":
			$refopt = "<input type='text' size='5' name='refnum[]' value=''>";
			break;
		case "once":
			$refopt = "<input type='hidden' size='5' name='refnum[]' value='$led[refnum]'>$led[refnum]";
			break;
		case "edit":
			$refopt = "<input type='text' size='5' name='refnum[]' value='$led[refnum]'>";
			break;
		default :
			$refopt = "<input type='hidden' size='5' name='refnum[]' value='$sysrefnum'>$sysrefnum";
	}

	# check descript option
	switch($led['desopt']){
		case "emp":
			$desopt = "<input type='text' size='30' name='descript[]' value=''>";
			break;
		case "once":
			$desopt = "<input type='hidden' size='30' name='descript[]' value='$led[descript]'>$led[descript]";
			break;
		case "edit":
			$desopt = "<input type='text' size='30' name='descript[]' value='$led[descript]'>";
			break;
		default :
			$desopt = "<input type='text' size='30' name='descript[]' value=''>";
	}

	# Vat account option
	if($led['chrgvat'] == 'yes'){
		# get vat account
		$vataccRs = get("core","*","accounts","accid",$led['vataccid']);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatopt = "
			<th> Vat Account: </th>
			<td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>";
	}else{
		$vatopt ="";
	}

	/* End Options generated vars */


	// Details
	$details = "
		<h3>Enter Details</h3>
		<table ".TMPL_tblDflts.">
			<tr bgcolor='".bgcolorg()."'>
				<th>High Speed Input Ledger :</th>
				<td align='center'>&nbsp;&nbsp;&nbsp;&nbsp;$led[lname]&nbsp;&nbsp;&nbsp;&nbsp;</td>
				$vatopt
			</tr>
		</table>
		<p>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='ledgid' value='$ledgid'>
			<input type='hidden' name='ctaccid' value='$led[ctaccid]'>
			<input type='hidden' name='dtaccid' value='$led[dtaccid]'>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<th>Date</th>
				<th>Ref No.</th>
				<th>Transaction Details</th>
				<th>Amount</th>
			</tr>";

	for($i = 0; $i < $led['numtran']; $i++){

		# check date option
		if($led['dateopt'] == 'system'){
			# hidden sys gen date
			$date = date("d-m-Y");
			$dateopt = "
				<input type='hidden' name='date_day[$i]' value='".date("d")."'>
				<input type='hidden' name='date_month[$i]' value='".date("m")."'>
				<input type='hidden' name='date_year[$i]' value='".date("Y")."'>
				$date";
		}else{
			# input for date
			$dateopt = mkDateSelectA("date",$i);
		}

		$details .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$dateopt</td>
				<td align='center'>$refopt</td>
				<td>$desopt</td>
				<td align='center'>".CUR." <input type='text' size='12' name='amount[]'></td>
			</tr>";
	}

	$details .= "
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td align='center' colspan='3'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledgers</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $details;

}


# Error
function error($HTTP_POST_VARS, $error)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ledgid, "num", 1, 20, "Invalid Input Ledger Number.");

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

	# Get ledger settings
	core_connect();

	$sql = "SELECT * FROM in_ledgers WHERE ledgid='$ledgid' AND div = '".USER_DIV."'";
	$ledRslt = db_exec($sql);
	if(pg_numrows($ledRslt) < 1){
		return "<li>Invalid Input Ledger Number.</li>";
	}
	$led = pg_fetch_array($ledRslt);

	# account numbers
	$dtaccRs = get("core","*","accounts","accid",$led['dtaccid']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$led['ctaccid']);
	$ctacc  = pg_fetch_array($ctaccRs);

	$sysrefnum = getrefnum();
/*refnum*/

	/* Options generated vars */
	for($i = 0; $i < $led['numtran']; $i++){
		# check date option
		if($led['dateopt'] == 'system'){
			# hidden sys gen date
			$date = date("d-m-Y");
			$dateopt[$i] = "
				<input type='hidden' name='date_day[]' value='".date("d")."'>
				<input type='hidden' name='date_month[]' value='".date("m")."'>
				<input type='hidden' name='date_year[]' value='".date("Y")."'>
				$date";
		}else{
			# input for date
			$dateopt[$i] = mkDateSelectA("date",$i,$date_year[$i],$date_month[$i],$date_day[$i]);
		}

		# check refnum option
		switch($led['refopt']){
			case "num":
				$refopt[$i] = "<input type='hidden' size='5' name='refnum[]' value='$sysrefnum'>$sysrefnum";
				break;
			case "emp":
				$refopt[$i] = "<input type='text' size='5' name='refnum[]' value='$refnum[$i]'>";
				break;
			case "once":
				$refopt[$i] = "<input type='hidden' size='5' name='refnum[]' value='$refnum[$i]'>$refnum[$i]";
				break;
			case "edit":
				$refopt[$i] = "<input type='text' size='5' name='refnum[]' value='$refnum[$i]'>";
				break;
			default :
				$refopt[$i] = "<input type='hidden' size='5' name='refnum[]' value='$sysrefnum'>$sysrefnum";
		}

		# check descript option
		switch($led['desopt']){
			case "emp":
				$desopt[$i] = "<input type='text' size='30' name='descript[]' value='$descript[$i]'>";
				break;
			case "once":
				$desopt[$i] = "<input type='hidden' size='30' name='descript[]' value='$descript[$i]'>$descript[$i]";
				break;
			case "edit":
				$desopt[$i] = "<input type='text' size='30' name='descript[]' value='$descript[$i]'>";
				break;
			default :
				$desopt[$i] = "<input type='text' size='30' name='descript[]' value='$descript[$i]'>";
		}
	}

	# vat account option
	if($led['chrgvat'] == 'yes'){
		# get vat account
		$vataccRs = get("core","*","accounts","accid",$led['vataccid']);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatopt = "
			<th> Vat Account: </th>
			<td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>";
	}else{
		$vatopt = "";
	}


	/* End Options generated vars */


	// Details
	$details = "
		<h3>Enter Details</h3>
		<table ".TMPL_tblDflts.">
			<tr bgcolor='".bgcolorg()."'>
				<th>High Speed Input Ledger : </th>
				<td align='center'>&nbsp;&nbsp;&nbsp;&nbsp;$led[lname]&nbsp;&nbsp;&nbsp;&nbsp;</td>
				$vatopt
			</tr>
		</table>
		<p>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='ledgid' value='$ledgid'>
			<input type='hidden' name='ctaccid' value='$led[ctaccid]'>
			<input type='hidden' name='dtaccid' value='$led[dtaccid]'>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<td colspan='10'>$error</td>
			</tr>
			<tr>
				<th>Date</th>
				<th>Ref No.</th>
				<th>Transaction Details</th>
				<th>Amount</th>
			</tr>";

	for($i = 0; $i < $led['numtran']; $i++){
		$details .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$dateopt[$i]</td>
				<td align='center'>$refopt[$i]</td>
				<td>$desopt[$i]</td>
				<td align='center'>".CUR." <input type='text' size='12' name='amount[]' value='$amount[$i]'></td>
			</tr>";
	}

	$details .= "
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td align='center' colspan='3'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledgers</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $details;

}


# Confirm
function confirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");


	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ledgid, "num", 1, 20, "Invalid Input Ledger Number.");
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");

	foreach($amount as $key => $value){
		$v->isOk ($amount[$key], "float", 0, 20, "Invalid Amount.[".($key+1)."]");
		if(floatval($amount[$key]) != 0){
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[".($key+1)."]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[".($key+1)."]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[".($key+1)."]");
			$date[$key] = $date_day[$key]."-".$date_month[$key]."-".$date_year[$key];
			if(!checkdate($date_month[$key], $date_day[$key], $date_year[$key])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.[".($key+1)."]");
			}

			if (strtotime($date[$key]) >= strtotime($blocked_date_from) AND strtotime($date[$key]) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
				return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
			}

		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return error($HTTP_POST_VARS, $confirm);
		# $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		# return $confirm;
	}


	# Get ledger settings
	core_connect();

	$sql = "SELECT * FROM in_ledgers WHERE ledgid='$ledgid' AND div = '".USER_DIV."'";
	$ledRslt = db_exec($sql);
	if(pg_numrows($ledRslt) < 1){
		return "<li>Invalid Input Ledger Number.</li>";
	}
	$led = pg_fetch_array($ledRslt);

	# account numbers
	$dtaccRs = get("core","*","accounts","accid",$led['dtaccid']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$led['ctaccid']);
	$ctacc  = pg_fetch_array($ctaccRs);

	# vat account option
	if($led['chrgvat'] == 'yes'){
		# get vat account
		$vataccRs = get("core","*","accounts","accid",$led['vataccid']);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatopt = "
			<th> Vat Account: </th>
			<td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>";
	}else{
		$vatopt = "";
	}

	$trans = "";
	foreach($amount as $key => $value){
		if(floatval($amount[$key]) != 0){
			$amount[$key] = sprint($amount[$key]);
			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><input type='hidden' name='date[]' value='$date[$key]'>$date[$key]</td>
					<td align='center'><input type='hidden' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
					<td><input type='hidden' name='descript[]' value='$descript[$key]'>$descript[$key]</td>
					<td>".CUR." <input type='hidden' name='amount[]' value='$amount[$key]'>$amount[$key]</td>
				</tr>";
		}
	}

	# if there are no trans
	if(strlen($trans) < 5){
		$confirm = "<li class='err'> Please enter full transaction details. </li>";
		return error($HTTP_POST_VARS, $confirm);
	}

	// Confirm Details
	$confirm = "
		<h3>Confirm Details</h3>
		<table ".TMPL_tblDflts.">
			<tr bgcolor='".bgcolorg()."'>
				<th>High Speed Input Ledger :</th>
				<td align='center'>&nbsp;&nbsp;&nbsp;&nbsp;$led[lname]&nbsp;&nbsp;&nbsp;&nbsp;</td>
				$vatopt
			</tr>
		</table>
		<p>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='ledgid' value='$ledgid'>
			<input type='hidden' name='ctaccid' value='$led[ctaccid]'>
			<input type='hidden' name='dtaccid' value='$led[dtaccid]'>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<th>Date</th>
				<th>Ref No.</th>
				<th>Transaction Details</th>
				<th>Amount</th>
			</tr>
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td align='center' colspan='3'><input type='submit' value='Record Transactions &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledgers</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $confirm;

}


function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ledgid, "num", 1, 20, "Invalid Input Ledger Number.");
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");

	foreach($amount as $key => $value){
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[".($key+1)."]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[".($key+1)."]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[".($key+1)."]");
		$v->isOk ($date[$key], "date", 1, 14, "Invalid date.[".($key+1)."]");
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

	# Get ledger settings
	core_connect();

	$sql = "SELECT * FROM in_ledgers WHERE ledgid='$ledgid' AND div = '".USER_DIV."'";
	$ledRslt = db_exec($sql);
	if(pg_numrows($ledRslt) < 1){
		return "<li>Invalid Input Ledger Number.</li>";
	}
	$led = pg_fetch_array($ledRslt);

	# Accounts details
	$dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	# vat account option
	if($led['chrgvat'] == 'yes'){
		# The percantage
		$VATP = TAX_VAT;

		# get vat account
		$vataccRs = get("core","*","accounts","accid",$led['vataccid']);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatopt = "
			<tr bgcolor='".bgcolorg()."'>
				<td> Vat Account: </td>
				<td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT </td>
				<td>$VATP %</td>
			</tr>";
	}else{
		$vatopt = "";
	}

	# write the transaction with the aid of the writetrans functions
	$tot = 0;
	foreach($amount as $key => $value){
		# Calculate vat
		if($led['chrgvat'] == 'yes'){
			# The percantage
			$VATP = TAX_VAT;

			if($led['vatinc'] == 'yes'){
				# Calc VAT
				$VAT = sprint(($amount[$key] - (($amount[$key]/(100+$VATP)) * 100)));

				# Calculate amount
				$amt = ($amount[$key] - $VAT);

				# Check VAt Deductable account
				if($led['vatdedacc'] == $led['dtaccid']){
					writetrans($led['vataccid'], $ctaccid, $date[$key], $refnum[$key], $VAT, $descript[$key]."  Vat");
					writetrans($dtaccid, $ctaccid, $date[$key], $refnum[$key], $amt, $descript[$key]);
					isBankRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amt, $ctaccid);

					$amounts = "|$amt|$VAT";
					$accids = "|$dtaccid|$led[vataccid]";
					$vats = "|0|0";
					$chrgvats = "|nov|nov";
					isBankmRec($ctaccid, 'withdrawal', $date[$key], $dtacc['accname'], $descript[$key], 0, $amount[$key], $dtaccid, $amounts, $accids, $vats, $chrgvats);

				}elseif($led['vatdedacc'] == $led['ctaccid']){
					writetrans($dtaccid, $led['vataccid'], $date[$key], $refnum[$key], $VAT, $descript[$key]."  Vat");
					writetrans($dtaccid, $ctaccid, $date[$key], $refnum[$key], $amt, $descript[$key]);
					isBankRec($ctaccid, 'withdrawal', $date[$key], $dtacc['accname'], $descript[$key], 0, $amt, $dtaccid);

					$amounts = "|$amt|$VAT";
					$accids = "|$ctaccid|$led[vataccid]";
					$vats = "|0|0";
					$chrgvats = "|nov|nov";
					isBankmRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amount[$key], $ctaccid, $amounts, $accids, $vats, $chrgvats);
					// isBankRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amt, $ctaccid);


				}
			}elseif($led['vatinc'] == 'no'){
				# Calc VAT
				$VAT = sprint((($VATP/100) * $amount[$key]));

				# Calculate amount
				$amt = $amount[$key];

				# Check VAt Deductable account
				if($led['vatdedacc'] == $led['dtaccid']){
					writetrans($led['vataccid'], $ctaccid, $date[$key], $refnum[$key], $VAT, $descript[$key]."  Vat");
					writetrans($dtaccid, $ctaccid, $date[$key], $refnum[$key], $amt, $descript[$key]);
					isBankRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amt, $ctaccid);
					
					$amounts = "|$amt|$VAT";
					$accids = "|$dtaccid|$led[vataccid]";
					$vats = "|0|0";
					$chrgvats = "|nov|nov";
					isBankmRec($ctaccid, 'withdrawal', $date[$key], $dtacc['accname'], $descript[$key], 0, $amount[$key], $dtaccid, $amounts, $accids, $vats, $chrgvats);

				}elseif($led['vatdedacc'] == $led['ctaccid']){
					writetrans($dtaccid, $led['vataccid'], $date[$key], $refnum[$key], $VAT, $descript[$key]."  Vat");
					writetrans($dtaccid, $ctaccid, $date[$key], $refnum[$key], $amt, $descript[$key]);
					isBankRec($ctaccid, 'withdrawal', $date[$key], $dtacc['accname'], $descript[$key], 0, $amt, $dtaccid);

					$amounts = "|$amt|$VAT";
					$accids = "|$ctaccid|$led[vataccid]";
					$vats = "|0|0";
					$chrgvats = "|nov|nov";
					isBankmRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amount[$key], $ctaccid, $amounts, $accids, $vats, $chrgvats);
					// isBankRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amt, $ctaccid);
				}
			}
			$tot += $amount[$key];
		}else{
			# Write normal transactions
			writetrans($dtaccid, $ctaccid, $date[$key], $refnum[$key], $amount[$key], $descript[$key]);
			isBankRec($dtaccid, 'deposit', $date[$key], $ctacc['accname'], $descript[$key], 0, $amount[$key], $ctaccid);
			isBankRec($ctaccid, 'withdrawal', $date[$key], $dtacc['accname'], $descript[$key], 0, $amount[$key], $dtaccid);

			$tot += $amount[$key];
		}
	}

	# Hmmmm ?
	$ret = $key+1;
	$tot = sprint($tot);
	
	// Start layout
	$write = "
		<center>
		<h3>Journal Transactions have been recorded</h3>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Details</td>
			</tr>
			$vatopt
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of transactions</td>
				<td>$ret</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><b>Total amount</b></td>
				<td><b>".CUR." $tot</b></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledgers</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-new.php'>New High Speed Input Ledgers</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $write;

}


?>

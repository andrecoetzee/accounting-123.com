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
# trans-new.php :: debit-credit Transaction
##

require("settings.php");
require("core-settings.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "details":
			if(isset($HTTP_POST_VARS['details'])){
				$OUTPUT = details($HTTP_POST_VARS);
			}else{
				$OUTPUT = details2($HTTP_POST_VARS);
			}
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = slctacc();
}

require("template.php");



function slctacc($err = "")
{

	extract($_POST);
	db_conn(PRD_DB);

	if (!isset($refnum))
		$refnum = getrefnum();

// 	if (empty($jr_year)) {
// 		explodeDate(false, $jr_year, $jr_month, $jr_day);
// 	}

	if (empty($dtaccid)) {
		$dtaccid = false;
	}

	if (empty($ctaccid)) {
		$ctaccid = false;
	}

	/** REFNUM **/

	//Select Account <input align='right' type='button' onClick=\"popupSized('acc-new2.php?update_parent=yes','accounts','700','400');\" value='Add Account'>

	if (!isset ($yr_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$jr_year = $date_arr[0];
			$jr_month = $date_arr[1];
			$jr_day = $date_arr[2];
		}else {
			$jr_year = date("Y");
			$jr_month = date("m");
			$jr_day = date("d");
		}
	}

	$view = "
		<center>
		<h3> Journal transaction </h3>
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("jr", $jr_year, $jr_month, $jr_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference Number</td>
				<td><input type='text' size='10' name='refnum' value='".($refnum++)."'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Debit</h4></td>
						</tr>
						<tr>
							<th>Select Account <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='center'>".mkAccSelect("dtaccid", $dtaccid)."</td>
						</tr>
					</table>
				</td>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Credit</h4></td>
						</tr>
						<tr>
							<th>Select Account <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
						</tr>
						<tr bgcolor='".bgcolorc(0)."'>
							<td valign='center'>".mkAccSelect("ctaccid", $ctaccid)."</td>
							<td><input name='details' type='submit' value='Enter Details >'></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br />
		<table ".TMPL_tblDflts.">
			<tr>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Debit</h4></td>
						</tr>
						<tr>
							<th>Account number</th>
						</tr>
						<tr bgcolor='".bgcolorc(0)."'>
							<td valign='center'><input type='text' name='dtaccnum' size='20'></td>
						</tr>
					</table>
				</td>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Credit</h4></td>
						</tr>
						<tr>
							<th>Account number</th>
						</tr>
						<tr bgcolor='".bgcolorc(0)."'>
							<td valign='center'><input type='text' name='ctaccnum' size='20'></td>
							<td><input type='submit' value='Enter Details >'></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br />
		</form>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("../core/acc-new2.php", "Add New Account"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $view;

}



function details($HTTP_POST_VARS, $err = "")
{

	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "string", 1, 10, "Invalid Reference number.");
	$v->isOk ($jr_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($jr_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($jr_year, "num", 1,4, "Invalid to Date Year.");
	$date = mkdate($jr_year, $jr_month, $jr_day);
	$v->isOk ($date, "date", 1, 1, "Invalid date.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return slctacc($err);
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}


	if (!isset($amount)) {
		$amount = "";
		$details = "";
		$chrgvat = "";
	}

	if ($chrgvat == "yes") {
		$c1 = "checked=yes";
		$c2 = "";
	} else {
		$c1 = "";
		$c2 = "checked=yes";
	}

	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	# Check for Creditors and Debtors Control
	if(($o = Control($dtaccid, $ctaccid, $refnum, $jr_day, $jr_month, $jr_year))) {
		return $o;
	}

	// Deatils
	$details = "
		<h3> Journal transaction details</h3>
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='ctaccid' value='$ctaccid' />
			<input type='hidden' name='dtaccid' value='$dtaccid' />
			<input type='hidden' name='details' value='' />
			<input type='hidden' name='jr_day' value='$jr_day' />
			<input type='hidden' name='jr_month' value='$jr_month' />
			<input type='hidden' name='jr_year' value='$jr_year' />
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			".TBL_BR."
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference No.</td>
				<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR."<input type='text' size='20' name='amount' value='$amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td>
					<input type='radio' name='chrgvat' value='yes' $c1 />Yes &nbsp;&nbsp;
					<input type='radio' name='chrgvat' value='no' $c2 />No
				</td>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$details</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value='".USER_NAME."' />".USER_NAME."</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $details;

}



function details2($HTTP_POST_VARS, $err = "")
{

	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "string", 1, 15, "Invalid Reference number.");
	$v->isOk ($jr_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($jr_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($jr_year, "num", 1,4, "Invalid to Date Year.");
	$date = mkdate($jr_year, $jr_month, $jr_day);
	$v->isOk ($date, "date", 1, 1, "Invalid date.");
	$v->isOk ($dtaccnum, "string", 1, 50, "Invalid Account number  to be Debited.");
	$v->isOk ($ctaccnum, "string", 1, 50, "Invalid Account number to be Credited.");

	if ($v->isError()) {
		$err = $v->genErrors();
		return slctacc($err);
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$dtaccnum = explode("/", rtrim($dtaccnum));
	$ctaccnum = explode("/", rtrim($ctaccnum));

	if (count($dtaccnum) < 2) {
		$dtacc = qryAccountsNum($dtaccnum[0], "000");
	} else {
		$dtacc = qryAccountsNum($dtaccnum[0], $dtaccnum[1]);
	}

	if (count($ctaccnum) < 2) {
		$ctacc = qryAccountsNum($ctaccnum[0], "000");
	} else {
		$ctacc = qryAccountsNum($ctaccnum[0], $ctaccnum[1]);
	}

	if (Control($dtacc['accid'], $ctacc['accid'], $refnum, $jr_day, $jr_month, $jr_year)) {
		return Control($dtacc['accid'], $ctacc['accid'], $refnum, $jr_day, $jr_month, $jr_year);
	}
	
	if (!isset($amount)) {
		$amount = "";
		$details = "";
		$chrgvat = "";
	}

	if ($chrgvat == "yes") {
		$c1 = "checked=yes";
		$c2 = "";
	} else {
		$c1 = "";
		$c2 = "checked=yes";
	}

	$OUT = "
		<h3>Journal transaction details</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='ctaccid' value='$ctacc[accid]' />
			<input type='hidden' name='dtaccid' value='$dtacc[accid]' />
			<input type='hidden' name='jr_day' value='$jr_day' />
			<input type='hidden' name='jr_month' value='$jr_month' />
			<input type='hidden' name='jr_year' value='$jr_year' />
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			".TBL_BR."
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference No.</td>
				<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR."<input type='text' size='20' name='amount' value='$amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td><input type='radio' name='chrgvat' value='yes' $c1>Yes &nbsp;&nbsp; <input type='radio' name='chrgvat' value='no' $c2>No</td>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$details</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td valign='center'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $OUT;

}



function slctVatacc($HTTP_POST_VARS, $err="")
{

	extract($HTTP_POST_VARS);

	if(isset($back)) {
		if(isset($details)) {
			return $confirm."</li>".details($HTTP_POST_VARS);
		} else {
			return $confirm."</li>".details2($HTTP_POST_VARS);
		}
	}

	require_lib("validate");
	$v = new validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "string", 1, 15, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($date, "date", 1, 1, "Invalid date.");

	if ($v->isError()) {
		$err = $v->genErrors();
		if(isset($details)) {
			return details($HTTP_POST_VARS, $err);
		} else {
			return details2($HTTP_POST_VARS, $err);
		}
	}



	if(!isset($vatinc)) {
		$vatinc = "";
		$vatdedacc = "";
		$vataccid = 0;
	}

	if($vatdedacc == "$ctaccid") {
		$dsel1 = "";
		$dsel2 = "checked=yes";
	} else {
		$dsel1 = "checked=yes";
		$dsel2 = "";
	}

	if($vatinc == "no") {
		$vatsel1 = "";
		$vatsel2 = "checked=yes";
	} else {
		$vatsel2 = "";
		$vatsel1 = "checked=yes";
	}

	# Account numbers
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	db_conn('core');
	$vatacc = "<select name='vataccid'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.";
	}
	while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
		if($vataccid==$acc['accid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$vatacc .= "<option value='$acc[accid]' $sel>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$vatacc .= "</select>";

	db_conn('cubit');

	if(!isset($vatcode)) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$vatcode = $vd['id'];
	}

	if(!isset($vatcode)) {
		$vatcode = 0;
	}

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "<select name='vatcode'>";

	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $vatcode) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes .= "</select>";

	// Details
	$slctacc = "
		<center>
		<h3>Journal Transaction VAT Details</h3>
		<h4>Select VAT Accounts</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='dtaccid' value='$dtaccid' />
			<input type='hidden' name='ctaccid' value='$ctaccid' />
			<input type='hidden' name='dtaccname' value='$dtacc[accname]' />
			<input type='hidden' name='ctaccname' value='$ctacc[accname]' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='refnum' value='$refnum' />
			<input type='hidden' name='amount' value='$amount' />
			<input type='hidden' name='details' value='$details' />
			<input type='hidden' name='author' value='$author' />
			<input type='hidden' name='chrgvat' value='$chrgvat' />
			<input type='hidden' name='jr_day' value='$jr_day' />
			<input type='hidden' name='jr_month' value='$jr_month' />
			<input type='hidden' name='jr_year' value='$jr_year' />
			<input type='hidden' name='vat' value='' />
	 	<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>VAT Deductable Account</td>
				<td>
					<input type='radio' name='vatdedacc' value='$dtaccid' $dsel1 />$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]<br />
					<input type='radio' name='vatdedacc' value='$ctaccid' $dsel2 />$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Account</td>
				<td>$vatacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive </td>
				<td>
					<input type='radio' size='20' name='vatinc' value='yes' $vatsel1 />Yes (Amount Includes VAT) &nbsp;&nbsp;
					<input type='radio' size='20' name='vatinc' value='no' $vatsel2 />No(Add VAT to Amount)</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$Vatcodes</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type=submit name=back value='&laquo; Correction'></td>
				<td align=right><input type=submit value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $slctacc;

}



function confirm($HTTP_POST_VARS, $err = "")
{

	extract($HTTP_POST_VARS);

	if(isset($back)) {
		if (isset($vat)) {
			return details($HTTP_POST_VARS);
		} else {
			return slctacc();
		}
	}

	# Redirect if must chrgvat
	if($chrgvat == 'yes' && !isset($vataccid)){
		return slctVatacc($HTTP_POST_VARS);
	}

	if(isset($vatcode)) {
		$vatcode += 0;
	} else {
		$vatcode = 0;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "string", 1, 15, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	if($chrgvat == 'yes'){
		$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
		$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
		$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
	}
	$v->isOk($date, "date", 1, 1, "Invalid date.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		if(isset($details)) {
			return details($HTTP_POST_VARS, $err);
		} else {
			return details2($HTTP_POST_VARS, $err);
		}
	}


	$amount = sprint($amount);

	if ($amount <= 0){
		return details($HTTP_POST_VARS,"<li class='err'>Invalid Amount To Process.</li>");
	}

	if($chrgvat == 'yes'){
		$vataccRs = get("core", "*", "accounts", "accid", $vataccid);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatin = ucwords($vatinc);
		//$VATP = TAX_VAT;

		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];

		# if vat must be charged
		if($vatinc == "no"){
			$vatamt = sprint((($VATP/100) * $amount));
			$totamt = sprint($amount + $vatamt);
		}else{
			$vatamt = sprint((($amount/($VATP + 100)) * $VATP));
			$totamt = sprint($amount);
		}

		$vataccnum = "
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Amount</td>
				<td><input type='hidden' name='vatinc' value='$vatinc'><input type='hidden' name='vatamt' value='$vatamt'>$vatamt</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total Transaction Amount</td>
				<td>$totamt</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Account</td>
				<td><input type='hidden' name='vataccid' value='$vataccid'><input type='hidden' name='vatdedacc' value='$vatdedacc'>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>
			</tr>";
	}else{
		$vataccnum = "";
	}

	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	if(isb($dtaccid)) {
		return slctacc("<li class='err'>You selected a main account for debit account.</li>");
	}

	if(isb($ctaccid)) {
		return slctacc("<li class='err'>You selected a main account for credit account.</li>");
	}

	if($vatcode > 0) {
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
		$Ri = db_exec($Sl) or errDie("unable to get data.");

		$va = pg_fetch_array($Ri);

		$vd = "
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$va[code]</td>
			</tr>";
	} else {
		$vd = "";
	}

	$confirm = "
		<h3>Record Journal transaction</h3>
		$err
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='vatcode' value='$vatcode' />
			<input type='hidden' name='dtaccid' value='$dtaccid' />
			<input type='hidden' name='ctaccid' value='$ctaccid' />
			<input type='hidden' name='dtaccname' value='$dtacc[accname]' />
			<input type='hidden' name='ctaccname' value='$ctacc[accname]' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='refnum' value='$refnum' />
			<input type='hidden' name='amount' value='$amount' />
			<input type='hidden' name='chrgvat' value='$chrgvat' />
			<input type='hidden' name='details' value='$details' />
			<input type='hidden' name='author' value='$author' />
			<input type='hidden' name='jr_day' value='$jr_day' />
			<input type='hidden' name='jr_month' value='$jr_month' />
			<input type='hidden' name='jr_year' value='$jr_year' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			".TBL_BR."
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference number</td>
				<td>$refnum</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td>$amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td>$chrgvat</td>
			</tr>
			$vataccnum
			$vd
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td>$details</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Authorising Person</td>
				<td>$author</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $confirm;

}



function write($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	$vatcode += 0;

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		if($chrgvat == "yes") {
			return slctVatacc($HTTP_POST_VARS);
		}elseif (isset($details)) {
			return details($HTTP_POST_VARS);
		} else {
			return details2($HTTP_POST_VARS);
		}
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "string", 1, 15, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	if($chrgvat == 'yes'){
		$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
		$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
		$v->isOk ($vatamt, "float", 1, 11, "Invalid VAT Amount.");
		$v->isOk ($vatinc, "string", 1, 3, "Invalid VAT inclusive selection.");
	}

	if ($v->isError ()) {
		$err = $v->genErrors();
		return confirm($HTTP_POST_VARS, $err);
	}



	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Accounts details
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	if($chrgvat == 'yes'){
		if($vatinc == 'yes'){
			# Calculate amount
			$amt = sprint($amount - $vatamt);
			$totamt = sprint($amount);
		}else{
			# Calculate amount
			$amt = sprint($amount);
			$totamt = sprint($amount + $vatamt);
		}

		$datea = explode("-", $date);

//		$cdate="$datea[2]-$datea[1]-$datea[0]";

		$cdate = $date;	

		# Check VAt Deductable account
		if($vatdedacc == $dtaccid){
			db_connect();
			$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
				return "Please select the vatcode";
			}

			$vd = pg_fetch_array($Ri);

			vatr($vd['id'], $cdate, "INPUT", $vd['code'], $refnum, "$details VAT", -$totamt, -$vatamt);

			writetrans($vataccid, $ctaccid, $date, $refnum, $vatamt, $details."  VAT");
			writetrans($dtaccid, $ctaccid, $date, $refnum, $amt, $details);
		}elseif($vatdedacc == $ctaccid){

			db_connect();
			$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
			$Ri = db_exec($Sl);

			if(pg_num_rows($Ri) < 1) {
				return "Please select the vatcode";
			}

			$vd = pg_fetch_array($Ri);

			vatr($vd['id'],$cdate,"OUTPUT",$vd['code'],$refnum,"$details.  VAT",$totamt,$vatamt);

			writetrans($dtaccid, $vataccid, $date, $refnum, $vatamt, $details."  VAT");
			writetrans($dtaccid, $ctaccid, $date, $refnum, $amt, $details);
		}
	}else{
		$totamt = sprint($amount);
		# Write normal transaction
		writetrans($dtaccid,$ctaccid, $date, $refnum, $totamt, $details);
	}

	if($chrgvat == 'yes'){
		$vataccRs = get("core", "*", "accounts", "accid", $vataccid);
		$vatacc  = pg_fetch_array($vataccRs);

		$vataccnum = "
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Amount</td>
				<td>$vatamt</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total Transaction Amount</td>
				<td><b>$totamt</b></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Account</td>
				<td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>
			</tr>";
		$amt = ($totamt - $vatamt);
	}else{
		$vataccnum = "";
		$amt = ($amount);
	}

	if(cc_TranTypeAcc($dtaccid, $ctaccid) != false){
		$cc_trantype = cc_TranTypeAcc($dtaccid, $ctaccid);
		$cc = "<script> CostCenter('$cc_trantype', 'Journal Entry', '$date', '$details', '$amt', '../'); </script>";
	}else{
		$cc = "";
	}


	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Start layout
	$write = "
		<center>$cc
		<h3>Journal transaction has been recorded</h3>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td><b>$amount</b></td>
			</tr>
			$vataccnum
		</table>"
		.mkQuickLinks(
			ql("../reporting/index-reports.php", "Financials"),
			ql("trans-new.php", "Journal Transactions")
		);
	return $write;

}


?>

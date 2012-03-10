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
require("settings.php");
require("core-settings.php");

# decide what to do
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
			if (isset($HTTP_GET_VARS['supid'])){
				$OUTPUT = slctacc ($HTTP_GET_VARS);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($HTTP_GET_VARS['supid'])){
		$OUTPUT = slctacc ($HTTP_GET_VARS);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# get templete
require("template.php");

# Select Accounts
function slctacc($HTTP_GET_VARS)
{
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	$refnum = getrefnum();
/*refnum*/
	# Select supplier
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid supplier ID.";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Accounts drop down
	core_connect();
	$accounts = "<select name=accid>";
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li>There are No accounts in Cubit.";
		}
		while($acc = pg_fetch_array($accRslt)){
			$sel = "";
			if(isset($cacc)){
				if($cacc == $acc['accid'])
					$sel = "selected";
			}
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;
			$accounts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
		}
	$accounts .="</select>";

	# get entry type
	$entd = "";
	$entc = "checked=yes";
	if(isset($tran)){
		if($tran == "dt"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

	// Accounts (debit)
	$view = "<h3> Journal transaction </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details>
	<input type=hidden name=supid value='$supid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier Number</td><td>$supp[supno]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Supplier</td><td>$supp[supname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>".mkDateSelect("date")."</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Reference Number</td><td><input type=text size=10 name=refnum value='".($refnum++)."'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Entry Type</td><td><input type=radio name=entry value=DT $entd> Debit | <input type=radio name=entry value=CT $entc>Credit</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=2>Contra Account</td><td>$accounts <input name=details type=submit value='Enter Details'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><!--       Rowspan      --><td><input type=text name=accnum size=20> <input type=submit value='Enter Details'></td></tr>
	</table>
	<p>
	<input type=button value='Go Back' onClick='javascript:history.back();'>
	</form>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../supp-view.php'>View Suppliers</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# Enter Details of Transaction
function details($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

		# get contra account details
		$accRs = get("core","*","accounts","accid",$accid);
		$acc  = pg_fetch_array($accRs);

		# Select supplier
		db_connect();
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($suppRslt) < 1){
			return "<li> Invalid supplier ID.";
		}else{
			$supp = pg_fetch_array($suppRslt);
		}

		# Probe tran type
		if($entry == "CT"){
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td>$supp[supno] - $supp[supname]</td></tr>";
		}else{
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$supp[supno] - $supp[supname]</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>";
		}

        // Layout Details
        $details = "<h3> Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name='date' value='$date'>
		<input type=hidden name='supid' value='$supid'>
        <input type=hidden name='accid' value='$accid'>
		<input type=hidden name='entry' value='$entry'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
			$tran
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$date</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>$supp[currency]<input type=text size=20 name=amount></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Exchange rate</td><td valign=center>".CUR." / $supp[currency] <input type=text size=8 name=rate value='1'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
			<tr><td><br></td></tr>
			<tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../supp-view.php'>View Suppliers</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $details;
}

# Enter Details of Transaction
function details2($HTTP_POST_VARS)
{

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        $accnum = explode("/", rtrim($accnum));

        if(count($accnum) < 2){
			// account numbers
			$accRs = get("core","*","accounts","topacc",$accnum[0]."' AND accnum = '000");
			if(pg_numrows($accRs) < 1){
					return "<li> Accounts number : $accnum[0] does not exist";
			}
			$acc  = pg_fetch_array($accRs);
        }else{
			// account numbers
			$accRs = get("core","*","accounts","topacc","$accnum[0]' AND accnum = '$accnum[1]");
			if(pg_numrows($accRs) < 1){
					return "<li> Accounts number : $accnum[0]/$accnum[1] does not exist";
			}
			$acc  = pg_fetch_array($accRs);
        }

		# Select supplier
		db_connect();
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($suppRslt) < 1){
			return "<li> Invalid Supplier ID.";
		}else{
			$supp = pg_fetch_array($suppRslt);
		}

		# probe tran type
		if($entry == "CT"){
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td>$supp[supno] - $supp[supname]</td></tr>";
		}else{
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$supp[supno] - $supp[supname]</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>";
		}

		// Layout Details
        $details = "<h3>Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name='date' value='$date'>
		<input type=hidden name='supid' value='$supid'>
        <input type=hidden name='accid' value='$accid'>
		<input type=hidden name='entry' value='$entry'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
			$tran
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$date</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>$supp[currency] <input type=text size=20 name=amount></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Exchange rate</td><td valign=center>".CUR." / $supp[currency] <input type=text size=8 name=rate value='1'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
			<tr><td><br></td></tr>
			<tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../supp-view.php'>View Suppliers</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $details;
}

# Confirm
function confirm($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($rate, "float", 1, 10, "Invalid exchange rate.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[2], $datea[0])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

		# Get contra account details
		$accRs = get("core","*","accounts","accid",$accid);
        $acc  = pg_fetch_array($accRs);

		# Select supplier
		db_connect();
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($suppRslt) < 1){
			return "<li> Invalid supplier ID.";
		}else{
			$supp = pg_fetch_array($suppRslt);
		}

		# Probe tran type
		if($entry == "CT"){
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td>$supp[supno] - $supp[supname]</td></tr>";
		}else{
			$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$supp[supno] - $supp[supname]</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>";
		}

		$lamt = sprint($amount * $rate);
		$amount = sprint($amount);

        // Layout
        $confirm = "<h3>Record Journal transaction</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name='supid' value='$supid'>
        <input type=hidden name='accid' value='$accid'>
        <input type=hidden name=accname value='$acc[accname]'>
        <input type=hidden name=date value='$date'>
        <input type=hidden name=refnum value='$refnum'>
		<input type=hidden name=entry value='$entry'>
		<input type=hidden name=amount value='$amount'>
		<input type=hidden name=rate value='$rate'>
        <input type=hidden name=details value='$details'>
        <input type=hidden name=author value='$author'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
			$tran
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$date</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Reference number</td><td>$refnum</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>$supp[currency] $amount | ".CUR." $lamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Details</td><td>$details</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Authorising Person</td><td>$author</td></tr>
			<tr><td><br></td></tr>
			<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm Transaction &raquo'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
        	<tr><th>Quick Links</th></tr>
        	<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../supp-view.php'>View Suppliers</a></td></tr>
        	<script>document.write(getQuicklinkSpecial());</script>

        </table>";

	return $confirm;
}

# Write
function write($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier number.");
	$v->isOk ($accid, "num", 1, 50, "Invalid Contra Account.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);
	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[2], $datea[0])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	$td=$date;

	# Accounts details
	$accRs = get("core","*","accounts","accid",$accid);
	$acc  = pg_fetch_array($accRs);

	# Select supplier
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid Supplier ID.";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class=err>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$famt = sprint($amount);
	$amount = sprint($amount * $rate);

	# update all supplies xchange rate first
	xrate_update($supp['fcid'], $rate, "suppurch", "id");
	sup_xrate_update($supp['fcid'], $rate);

	$supp['supname'] = remval($supp['supname']);

	# Probe tran type
	if($entry == "CT"){
		# Write transaction  (debit contra account, credit debtors control)
		writetrans($accid, $dept['credacc'], $td, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td>$supp[supno] - $supp[supname]</td></tr>";
		$samount = $amount;
		$sfamt = $famt;
		// recordCT(-$amount, $supp['supid']);
		frecordCT($famt, $amount, $supp['supid'], $supp['fcid'],$td);
		$type = 'c';
	}else{
		# Write transaction  (debit debtors control, credit contra account)
		writetrans($dept['credacc'], $accid, $td, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "<tr bgcolor='".TMPL_tblDataColor1."'><td>$supp[supno] - $supp[supname]</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>";
		$samount = sprint($amount - ($amount * 2));
		$sfamt = sprint($famt - ($famt * 2));
		// recordDT($amount, $supp['supid']);
		frecordDT($famt, $amount, $supp['supid'], $supp['fcid'],$td);
		$type = 'd';
	}

	db_connect();
	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$edate = date("Y-m-d");
		# record the payment on the statement
		$sql = "INSERT INTO sup_stmnt(supid, edate, ref, cacc, descript, amount, div) VALUES('$supp[supid]', '$td', '0', '$accid', '$details', '$sfamt', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$samount'),fbalance = (fbalance + '$sfamt') WHERE supid = '$supp[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Ledger Records
	suppledger($supp['supid'], $accid, $td, $refnum, $details, $amount, $type);
	db_connect();

	// Start layout
	$write ="<h3>Journal transaction has been recorded</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
		$tran
		<tr><td><br></td></tr>
		<tr colspan=2><td><h4>Amount</h4></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>".CUR." $famt</b></td></tr>
	</table>
	<P>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../supp-view.php'>View Suppliers</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}

# psuedo functions
function frecordDT($amount, $lamount, $supid, $fcid,$pdate){
	db_connect();
	//$pdate = date("Y-m-d");
	$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fcid, fbalance, div) VALUES('$supid', '0', '$pdate', '-$lamount', '$fcid', '-$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
}

function frecordCT($amount, $lamount, $supid, $fcid,$pdate){
	db_connect();
	//$pdate = date("Y-m-d");
	$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, fcid, fbalance, div) VALUES('$supid', '0', '$pdate', '$lamount', '$fcid', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
}
?>

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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "details":
			if(isset($_POST['details'])){
				$OUTPUT = details($_POST);
			}else{
				$OUTPUT = details2($_POST);
			}
			break;
		default:
			if (isset($_GET['supid'])){
				$OUTPUT = slctacc ($_GET);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['supid'])){
		$OUTPUT = slctacc ($_GET);
	} else {
		$OUTPUT = get_supplier ();
	}
}

# get templete
require("../template.php");




function get_supplier ()
{

	db_connect ();

	$get_supp = "SELECT * FROM suppliers ORDER BY supname";
	$run_supp = db_exec($get_supp) or errDie("Unable to get suppliers information.");
	if(pg_numrows($run_supp) < 1){
		return "
					<li class='err'>No Suppliers Could Be Found.</li>"
					.mkQuickLinks(
						ql("../core/trans-new.php", "Journal Transactions"),
						ql("../cupp-new.php", "New Supplier"),
						ql("../supp-view.php", "View Suppliers")
					);
	}else {
		$supplier_drop = "<select name='supid'>";
		while ($sarr = pg_fetch_array($run_supp)){
			$supplier_drop .= "<option value='$sarr[supid]'>$sarr[supname]</option>";
		}
		$supplier_drop .= "</select>";
	}

	$display = "
					<h2>Select Creditor</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='GET'>
						<tr>
							<th>Supplier</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$supplier_drop</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Next'></td>
						</tr>
					</form>
					</table>
				";
	return $display;
	
}



# Select Accounts
function slctacc($_GET)
{

	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select supplier
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Accounts drop down
	core_connect();
	$accounts = "<select name='accid'>";
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li>There are No accounts in Cubit.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			$sel = "";
			if(isset($cacc)){
				if($cacc == $acc['accid'])
					$sel = "selected";
			} else {
				if(isset($accid)&&$accid==$acc['accid']) {
					$sel=" selected";
				}
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
	if(isset($entry)){
		if($entry == "DT"){
			$entd = "checked=yes";
			$entc = "";
		}
	}

	if(!isset($date_year)){
		$date_year = date("Y");
	}
	if(!isset($date_month)){
		$date_month = date("m");
	}
	if(!isset($date_day)){
		$date_day = date("d");
	}
	if(!isset($refnum)){
		# get last ref number
		$refnum = getrefnum();
		/*refnum*/
		$refnum++;
	}

	// Accounts (debit)
	$view = "
			<h3> Journal transaction </h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='details'>
				<input type='hidden' name='supid' value='$supid'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Supplier Number</td>
					<td>$supp[supno]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Supplier</td>
					<td>$supp[supname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date</td>
					<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Reference Number</td>
					<td><input type='text' size='10' name='refnum' value='$refnum'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Entry Type</td>
					<td>
						<li class='err'>This will debit/credit the supplier account selected</li>
						<input type='radio' name='entry' value='DT' $entd> Debit | 
						<input type='radio' name='entry' value='CT' $entc>Credit
					</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td rowspan='2'>Contra Account</td>
					<td>$accounts <input name='details' type='submit' value='Enter Details'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<!--       Rowspan      -->
					<td><input type='text' name='accnum' size='20'> <input type='submit' value='Enter Details'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' class='err'>This journal entry does not take VAT into consideration.<br />
						VAT will have to be Journalised as an additional entry.</td>
				</tr>
			</table>
			<p>
			</form>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../core/trans-new.php'>Journal Transactions</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $view;

}



# Enter Details of Transaction
function details($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Probe tran type
	if($entry == "CT"){
		$tran = "
					<tr bgcolor='".bgcolorg()."'>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						<td>$supp[supno] - $supp[supname]</td>
					</tr>";
	}else{
		$tran = "
					<tr bgcolor='".bgcolorg()."'>
						<td>$supp[supno] - $supp[supname]</td>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					</tr>";
	}

	if(!isset($amount)) {
		$amount="";
		$details="";
	}

	// Layout Details
	$details = "
			<h3> Journal transaction details</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name='entry' value='$entry'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
			<table ".TMPL_tblDflts.">
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				<tr><td><br></td></tr>
				<tr><td><br></td></tr>
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
					<td>Transaction Details</td>
					<td valign='center'><textarea cols='20' rows='5' name='details'>$details</textarea></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Person Authorising</td>
					<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
        	</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../core/trans-new.php'>Journal Transactions</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $details;

}



# Enter Details of Transaction
function details2($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
			return "<li>Supplier has been blocked</li>.";
		}else{
			$supp = pg_fetch_array($suppRslt);
		}

		# probe tran type
		if($entry == "CT"){
			$tran = "
						<tr bgcolor='".bgcolorg()."'>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
							<td>$supp[supno] - $supp[supname]</td>
						</tr>";
		}else{
			$tran = "
						<tr bgcolor='".bgcolorg()."'>
							<td>$supp[supno] - $supp[supname]</td>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						</tr>";
		}

		// Layout Details
        $details = "
			<h3>Journal transaction details</h3>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name='entry' value='$entry'>
				<input type='hidden' name=date_day value='$date_day'>
				<input type='hidden' name=date_month value='$date_month'>
				<input type='hidden' name=date_year value='$date_year'>
			<table ".TMPL_tblDflts.">
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				<tr><td><br></td></tr>
				<tr><td><br></td></tr>
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
					<td valign='center'>".CUR."<input type='text' size='20' name='amount'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Transaction Details</td>
					<td valign='center'><textarea cols='20' rows='5' name='details'></textarea></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Person Authorising</td>
					<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td valign='center' align='right'><input type='submit' value='Write &raquo;'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../core/trans-new.php'>Journal Transactions</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align=center><a href='../supp-view.php'>View Suppliers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $details;

}



# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return slctacc($_POST);
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
		if(!checkdate($datea[1], $datea[0], $datea[2])){
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
			return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
		}else{
			$supp = pg_fetch_array($suppRslt);
		}

		# Probe tran type
		if($entry == "CT"){
			$tran = "
						<tr bgcolor='".bgcolorg()."'>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
							<td>$supp[supno] - $supp[supname]</td>
						</tr>";
		}else{
			$tran = "
						<tr bgcolor='".bgcolorg()."'>
							<td>$supp[supno] - $supp[supname]</td>
							<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						</tr>";
		}


        // Layout
        $confirm = "
			<h3>Record Journal transaction</h3>
			<h4>Confirm entry</h4>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='supid' value='$supid'>
				<input type='hidden' name='accid' value='$accid'>
				<input type='hidden' name='accname' value='$acc[accname]'>
				<input type='hidden' name='date' value='$date'>
				<input type='hidden' name='refnum' value='$refnum'>
				<input type='hidden' name='entry' value='$entry'>
				<input type='hidden' name='amount' value='$amount'>
				<input type='hidden' name='details' value='$details'>
				<input type='hidden' name='author' value='$author'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
			<table ".TMPL_tblDflts.">
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				<tr><td><br></td></tr>
				<tr><td><br></td></tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date</td>
					<td>$date</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Referance number</td>
					<td>$refnum</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount</td>
					<td>".CUR." $amount</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Details</td>
					<td>$details</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Authorising Person</td>
					<td>$author</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right'><input type='submit' value='Write &raquo'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1' width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../core/trans-new.php'>Journal Transactions</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
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

	if(isset($back)) {
		unset($_POST["back"]);
		return details($_POST);
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
		if(!checkdate($datea[1], $datea[0], $datea[2])){
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
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}



	$date="$datea[2]-$datea[1]-$datea[0]";

	# Accounts details
	$accRs = get("core","*","accounts","accid",$accid);
	$acc  = pg_fetch_array($accRs);

	# Select supplier
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li class='err'>Invalid supplier ID, or supplier has been blocked</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<i class='err'>Department Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$supp['supname'] = remval($supp['supname']);

	# Probe tran type
	if($entry == "CT"){
		# Write transaction  (debit contra account, credit debtors control)
		recordtrans('journal',$accid, $dept['credacc'], $date, $refnum, $amount, '0', $details." - Supplier $supp[supname]");
		//PROCESS THIS WRITETRANS
		//writetrans($accid, $dept['credacc'], $date, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "
					<tr bgcolor='".bgcolorg()."'>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
						<td>$supp[supno] - $supp[supname]</td>
					</tr>";
		$samount = $amount;
		recordtrans('creditor', '0', '1', $date, '0', -$samount, '0', $details." - Customer $cust[cusname] $cust[surname]", $supp['supid']);
		//PROCESS THIS ENTRY
		//recordCT(-$amount, $supp['supid'],$date);
		$type = 'c';
	}else{
		# Write transaction  (debit debtors control, credit contra account)
		recordtrans('journal',$dept['credacc'], $accid, $date, $refnum, $amount, '0', $details." - Supplier $supp[supname]");
		//PROCESS THIS WRITETRANS
		//writetrans($dept['credacc'], $accid, $date, $refnum, $amount, $details." - Supplier $supp[supname]");
		$tran = "
					<tr bgcolor='".bgcolorg()."'>
						<td>$supp[supno] - $supp[supname]</td>
						<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
					</tr>";
		$samount = ($amount - ($amount * 2));
		recordtrans('creditor', '1', '0', $date, '0', $samount, '0', $details." - Customer $cust[cusname] $cust[surname]", $supp['supid']);
		//PROCESS THIS ENTRY
		//recordDT($amount, $supp['supid'],$date);
		$type = 'd';
	}

	db_connect();
	# Begin updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$edate = date("Y-m-d");
		# record the payment on the statement
		$sql = "INSERT INTO sup_stmnt(supid, edate, ref, cacc, descript, amount, div) VALUES('$supp[supid]', '$date', '0', '$accid', '$details', '$samount', '".USER_DIV."')";
//		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$samount') WHERE supid = '$supp[supid]' AND div = '".USER_DIV."'";
//		$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

		recordtrans('creditor','9999','9999',$date,$accid,$samount,'',$details,$supp['supid']);

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	switch ($type){
		case "d":
			recordtrans('creditor',$accid,'0',$date,$refnum,$amount,'0',$details,$supp['supid']);
			break;
		case "c":
			recordtrans('creditor','0',$accid,$date,$refnum,$amount,'0',$details,$supp['supid']);
			break;
		default:
	}

	# Ledger Records
	//suppledger($supp['supid'], $accid, $date, $refnum, $details, $amount, $type);

	db_connect();

	// Start layout
	$write = "
			<h3>Journal transaction has been recorded</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<td width='50%'><h3>Debit</h3></td>
					<td width='50%'><h3>Credit</h3></td>
				</tr>
				$tran
				<tr><td><br></td></tr>
				<tr colspan='2'>
					<td><h4>Amount</h4></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'><b>".CUR." $amount</b></td>
				</tr>
			</table>
			<P>
			<table ".TMPL_tblDflts." width='25%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../core/trans-new.php'>Journal Transactions</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><a href='../supp-view.php'>View Suppliers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $write;

}



function recordDT($amount, $supid,$edate)
{
	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance > 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

# records for CT
function recordCT($amount, $supid,$edate)
{
	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance < 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			$amount = ($amount * (-1));

  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}


?>

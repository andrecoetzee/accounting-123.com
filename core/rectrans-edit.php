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
#
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if(isset($_GET['recid'])){
					$OUTPUT = edit($_GET['recid']);
			}else{
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if(isset($_GET['recid'])){
			$OUTPUT = edit($_GET['recid']);
	}else{
			$OUTPUT = "<li> - Invalid use of module";
	}
}

# get templete
require("template.php");



# Enter Details of Transaction
function edit($recid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($recid, "num", 1, 20, "Invalid transaction number.");

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

	# Connect
	core_connect();

	# Get all the details
	$sql = "SELECT * FROM rectrans WHERE recid = '$recid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to access database.");
	$tran = pg_fetch_array($rslt);

	# Get account to be debited
	$dtaccRs = get("core","*","accounts","accid",$tran['debit']);
	if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
	}
	$dtacc = pg_fetch_array($dtaccRs);

	# Get account to be debited
	$ctaccRs = get("core","*","accounts","accid",$tran['credit']);
	if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
	}
	$ctacc = pg_fetch_array($ctaccRs);

	# Explode date
	$date = explode("-", $tran['date']);

	if(!isset($vatcode)) {
		$vatcode=0;
	}

	db_connect ();
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name='vatcode'>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['id']==$tran['vatcode']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes.="</select>";

	if($tran['chrgvat'] == "no"){
		$sel1 = "";
		$sel2 = "";
		$sel3 = "checked";
	}elseif(($tran['chrgvat'] == "yes") AND ($tran['vatinc'] == "yes")){
		$sel1 = "checked";
		$sel2 = "";
		$sel3 = "";
	}elseif(($tran['chrgvat'] == "yes") AND ($tran['vatinc'] == "no")){
		$sel1 = "";
		$sel2 = "checked";
		$sel3 = "";
	}


	// Deatils
	$edit = "
				<h3>Edit Recurring Transaction</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='recid' value='$recid'>
				<table ".TMPL_tblDflts." width='500'>
					<tr>
						<td width='50%'><h3>Debit</h3></td>
						<td width='50%'><h3>Credit</h3></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
						<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
					</tr>
					<tr><td><br></td></tr>
					<tr><td><br></td></tr>
					<tr class='".bg_class()."'>
						<td>Date</td>
						<td>".mkDateSelect("date",$date[0],$date[1],$date[2])."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Reference No.</td>
						<td valign='center'><input type='text' size='20' name='refnum' value='$tran[refnum]'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Amount</td>
						<td valign='center'>".CUR."<input type='text' size='20' name='amount' value='$tran[amount]'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Transaction Details</td>
						<td valign='center'><textarea cols='20' rows='5' name='details'>$tran[details]</textarea></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Person Authorising</td>
						<td valign='center'><input type='hidden' size='20' name='author' value='$tran[author]'>$tran[author]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>VAT Code</td>
						<td>$Vatcodes</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>VAT</td>
						<td><input type='radio' name='chrgvat' $sel1 value='inclusive'>Inclusive <input type='radio' name='chrgvat' $sel2 value='exclusive'>Exclusive <input type='radio' name='chrgvat' $sel3 value='novat'>No vat</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
						<td valign='center'><input type='submit' value='Edit Entry'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border='0' cellpadding='2' cellspacing='1' width='15%'>
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
	return $edit;

}


# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($recid, "num", 1, 20, "Invalid transaction number.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");

	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
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

	# Get all the details
	core_connect();
	$sql = "SELECT * FROM rectrans WHERE recid = '$recid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to access database.");
	$tran = pg_fetch_array($rslt);

	$dtaccRs = get("core","*","accounts","accid",$tran['debit']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$tran['credit']);
	$ctacc  = pg_fetch_array($ctaccRs);

	if(isset($vatcode)) {
		$vatcode+=0;
	} else {
		$vatcode=0;
	}

	db_connect ();
	$Sl="SELECT * FROM vatcodes WHERE id = '$vatcode' LIMIT 1";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$varr = pg_fetch_array($Ri);

	$vatcodes = $varr['code'];

	$confirm = "
					<h3>Edit Recurring Transaction</h3>
					<h4>Confirm entry</h4>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='recid' value='$recid'>
						<input type='hidden' name='dtaccname' value='$dtacc[accname]'>
						<input type='hidden' name='ctaccname' value='$ctacc[accname]'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='refnum' value='$refnum'>
						<input type='hidden' name='amount' value='$amount'>
						<input type='hidden' name='details' value='$details'>
						<input type='hidden' name='author' value='$author'>
						<input type='hidden' name='vatcode' value='$vatcode'>
						<input type='hidden' name='chrgvat' value='$chrgvat'>
					<table ".TMPL_tblDflts." width='500'>
						<tr>
							<td width='50%'><h3>Debit</h3></td>
							<td width='50%'><h3>Credit</h3></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
							<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
						</tr>
						<tr><td><br></td></tr>
						<tr><td><br></td></tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td>$date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reference number</td>
							<td>$refnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Amount</td>
							<td>".CUR." $amount</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Details</td>
							<td>$details</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Authorising Person</td>
							<td>$author</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Code</td>
							<td>$vatcodes</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT</td>
							<td>$chrgvat</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='right'><input type='submit' value='Edit Transaction &raquo'></td>
						</tr>
					</form>
					</table>
					<p>
					<table border='0' cellpadding='2' cellspacing='1' width='15%'>
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
	return $confirm;

}


# Write
function write($_POST)
{

// Sanity Checking and get vars(Respectively)
        # Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($recid, "num", 1, 20, "Invalid transaction number.");
	$v->isOk ($ctaccname, "string", 1, 255, "Invalid Account name to be Credited.");
	$v->isOk ($dtaccname, "string", 1, 255, "Invalid Account name to be Debited.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

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


	core_connect();
	# Get all the details
	$sql = "SELECT * FROM rectrans WHERE recid = '$recid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to access database.");
	$tran = pg_fetch_array($rslt);

	// Accounts details
	$dtaccRs = get("core", "*", "accounts", "accid", $tran['debit']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core", "*", "accounts", "accid", $tran['credit']);
	$ctacc  = pg_fetch_array($ctaccRs);

	# Format date
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	if(!isset($chrgvat)){
		$chrgvat = "no";
		$vatinc = "";
	}elseif ($chrgvat == "inclusive") {
		$chrgvat = "yes";
		$vatinc = "yes";
	}elseif ($chrgvat == "exclusive"){
		$chrgvat = "yes";
		$vatinc = "no";
	}elseif($chrgvat == "novat"){
		$chrgvat = "no";
		$vatinc = "";
	}

	db_conn('core');

	# update
	$sql = "UPDATE rectrans SET date='$date', refnum='$refnum', amount='$amount', details='$details', chrgvat = '$chrgvat', vatinc = '$vatinc', vatcode = '$vatcode' WHERE recid = '$recid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie("Unable to update transaction entry details.",SELF);


	// Start layout
	$write = "
				<center>
				<h3>Recurring Transaction has been edited</h3>
				<table ".TMPL_tblDflts." width='500'>
					<tr>
						<td width='50%'><h3>Debit</h3></td>
						<td width='50%'><h3>Credit</h3></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
						<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
					/tr>
					<tr><td><br></td></tr>
					<tr colspan='2'>
						<td><h4>Amount</h4></td>
					</tr>
					<tr class='".bg_class()."'>
						<td colspan='2'><b>".CUR." $amount</b></td>
					</tr>
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
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
			if(isset($_GET['batchid'])){
				$OUTPUT = edit($_GET['batchid']);
			}else{
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if(isset($_GET['batchid'])){
		$OUTPUT = edit($_GET['batchid']);
	}else{
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# get templete
require("template.php");



# Enter Details of Transaction
function edit($batchid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($batchid, "num", 1, 20, "Invalid batch number.");
	
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
	$sql = "SELECT * FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
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

	// Deatils
	$edit = "
		<h3>Edit Batch Entry</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='batchid' value='$batchid'>
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
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date[0],$date[1],$date[2])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference No.</td>
				<td valign='center'><input type='text' size='20' name='refnum' value='$tran[refnum]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR."<input type='text' size='20' name='amount' value='$tran[amount]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$tran[details]</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value='$tran[author]'>$tran[author]</td>
			</tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td valign='center'><input type='submit' value='Edit Entry'></td>
			</tr>
		</form>
		</table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
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
	$v->isOk ($batchid, "num", 1, 20, "Invalid batch number.");
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

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	db_conn("core");

	# Get all the details
	$sql = "SELECT * FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to access database.");
	$tran = pg_fetch_array($rslt);

	//processes
	db_conn(PRD_DB);

	$confirm = "
		<h3>Edit Batch Entry</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>";

	$dtaccRs = get("core","*","accounts","accid",$tran['debit']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$tran['credit']);
	$ctacc  = pg_fetch_array($ctaccRs);

	$confirm .= "
		<input type='hidden' name='batchid' value='$batchid'>
		<input type='hidden' name='dtaccname' value='$dtacc[accname]'>
		<input type='hidden' name='ctaccname' value='$ctacc[accname]'>
		<input type='hidden' name='date' value='$date'>
		<input type='hidden' name='refnum' value='$refnum'>
		<input type='hidden' name='amount' value='$amount'>
		<input type='hidden' name='details' value='$details'>
		<input type='hidden' name='author' value='$author'>
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
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference number</td>
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
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Confirm Transaction &raquo'></td>
			</tr>
		</form>
		</table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
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
	$v->isOk ($batchid, "num", 1, 20, "Invalid batch number.");
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


	db_conn("core");

	# Get all the details
	$sql = "SELECT * FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to access database.");
	$tran = pg_fetch_array($rslt);

	// Accounts details
	$dtaccRs = get("core","*","accounts","accid",$tran['debit']);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$tran['credit']);
	$ctacc  = pg_fetch_array($ctaccRs);

	// Insert the records into the transaction table
	db_conn("core");

	# Format date
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$sql = "UPDATE batch SET date='$date', refnum='$refnum', amount='$amount', details='$details' WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie("Unable to update batch entry details.",SELF);

	// Start layout
	$write = "
		<center>
		<h3>Batch Entry has been edited</h3>
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
			<tr colspan='2'>
				<td><h4>Amount</h4></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>".CUR." $amount</b></td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='batch-view.php'>View batch Entries</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>

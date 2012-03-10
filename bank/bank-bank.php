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
		case "bank":
			$OUTPUT = bank($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = confirm($HTTP_GET_VARS['cashid']);
	}
} else {
	# Display default output
	if(isset($HTTP_GET_VARS['cashid'])){
		$OUTPUT = confirm($HTTP_GET_VARS['cashid']);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of mudule.</li>";
	}
}

# get template
require("../template.php");



function confirm($cashid)
{

    # validate input
    require_lib("validate");
    $v = new  validate ();
    $v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

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
    // Connect to database
    Db_Connect ();
    $sql = "SELECT * FROM cashbook WHERE cashid = '$cashid'";
    $accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

    if ($numrows < 1) {
		$OUTPUT = "<li clss='err'>The deposit with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}


    $accnt = pg_fetch_array($accntRslt);

    $confirm = "
    				<h3>Confirm Entry</h3>
				    <table ".TMPL_tblDflts.">
				    <form action='".SELF."' method='POST'>
					    <input type='hidden' name='key' value='bank'>
					    <input type='hidden' name='cashid' value='$accnt[cashid]'>";

    # get account name for the account involved
    $AccRslt = get("core","accname","accounts", "accid", $accnt['accinv']);
    $accinv = pg_fetch_array($AccRslt);

    $AccRslt = get("cubit","*","bankacct", "bankid", $accnt['bankid']);
    $bank = pg_fetch_array($AccRslt);

    $confirm .= "
					    <tr>
					    	<th>Field</th>
					    	<th>Value</th>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Bank Name</td>
					    	<td>$bank[bankname]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Account Number</td>
					    	<td>$bank[accnum]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Transaction Type</td>
					    	<td>$accnt[trantype]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Date of Transaction</td>
					    	<td>$accnt[date]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Paid to/Received from</td>
					    	<td>$accnt[name]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Date</td>
					    	<td>
					    		<input type='text' size='2' name='day' maxlength='2'>-
					    		<input type='text' size='2' name='mon' maxlength='2' value='".date("m")."'>-
					    		<input type='text' size='4' name='year' maxlength='4' value='".date("Y")."'> DD-MM-YYYY
					    	</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Journal Reference No.</td>
					    	<td valign='center'><input type='text' size='7' name='refnum' value='$refnum'></td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Description</td>
					    	<td>$accnt[descript]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Amount</td>
					    	<td>".CUR." $accnt[amount]</td>
					    </tr>
					    <tr bgcolor='".bgcolorg()."'>
					    	<td>Transaction Contra Account</td>
					    	<td>$accinv[accname]</td>
					    </tr>
					    <tr>
					    	<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
					    	<td align='right'><input type='submit' value='Bank Deposit Record &raquo'></td>
					    </tr>
				    </form>
				    </table>";
	return $confirm;

}



# write
function bank($HTTP_POST_VARS)
{

	//processes
	db_connect();

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Reference number.");
	$v->isOk ($day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($mon, "num", 1,2, "Invalid Date month.");
	$v->isOk ($year, "num", 1,4, "Invalid Date Year.");
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
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

    // Connect to database
    Db_Connect ();
    $sql = "SELECT * FROM cashbook WHERE cashid = '$cashid'";
    $cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
    if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li clss='err'>The cashbook record with reference number, <b>$cashid</b> was not found in Cubit.</li>";
		return $OUTPUT;
	}
    $cash = pg_fetch_array($cashRslt);

	# get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$cash[bankid]'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# write the transaction
	if($cash['trantype'] == "deposit"){
		# debit bank and credit the account involved
		writetrans($banklnk['accnum'], $cash['accinv'], $date, $refnum, $cash['amount'], $cash['descript']);
	}else{
		# credit bank and debit the account involved
		writetrans($cash['accinv'], $banklnk['accnum'], $date, $refnum, $cash['amount'], $cash['descript']);
	}

	# set records as banked
	db_connect();
	$sql = "UPDATE cashbook SET banked = 'yes' WHERE cashid='$cashid'";
	$Rslt = db_exec ($sql) or errDie ("Unable to set bank deposit as banked in Cubit.",SELF);

	# status report
	$bank = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Cash Book</th>
					</tr>
					<tr class='datacell'>
						<td>Cash Book Entry was successfully processed.</td>
					</tr>
				</table>";

        # main table (layout with menu)
        $OUTPUT = "
					<center>
					<table width='90%'>
						<tr valign='top'>
							<td width='60%'>$bank</td>
							<td align='center'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr>
										<th>Quick Navigation</th>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='cashbook-view.php'>View Cash Book</td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
									<tr class='datacell'>
										<td align='center'><a href='../reporting/not-banked.php'>View Outstanding Cash Book Entries</td>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='bank-pay-add.php'>Add bank Payment</td>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='bank-recpt-add.php'>Add Bank Receipt</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>";
		return $OUTPUT;

}


?>
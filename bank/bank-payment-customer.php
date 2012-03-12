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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = add();
	}
} else {
	$OUTPUT = add();
}

# get templete
require("../template.php");




# Insert details
function add()
{

		# Suppliers Drop down selections
        db_connect();
       	$cust = "<select name='cusid'>";
        $sql = "SELECT cusnum,cusname,surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname";
        $cusRslt = db_exec($sql);
        $numrows = pg_numrows($cusRslt);
        if(empty($numrows)){
                return "<li class='err'> There are no customers in Cubit.</li>";
        }
		while($cus = pg_fetch_array($cusRslt)){
			$cust .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
		}
        $cust .= "</select>";

        // layout
        $add = "
					<h3>New Bank Payment</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='confirm'>
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
        $numrows = pg_numrows($banks);

        if(empty($numrows)){
                return "
							<li class='err'> There are no accounts held at the selected Bank.</li>
							<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }

        while($acc = pg_fetch_array($banks)){
                $add .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname]</option>";
        }

        $add .= "
							</select>
			        	</td>
			        </tr>
			        <tr bgcolor='".bgcolorg()."'>
			        	<td>Date</td>
			        	<td>".mkDateSelect("date")."</td>
			        </tr>
			        <tr bgcolor='".bgcolorg()."'>
			        	<td>Customer paid to</td>
			        	<td valign='center'>$cust</td>
			        </tr>
			        <tr bgcolor='".bgcolorg()."'>
			        	<td>Description</td>
			        	<td valign='center'><textarea col='20' rows='5' name='descript'></textarea></td>
			        </tr>
			        <tr bgcolor='".bgcolorg()."'>
			        	<td>Cheque Number</td>
			        	<td valign='center'><input size='20' name='cheqnum'></td>
			        </tr>
			        <tr bgcolor='".bgcolorg()."'>
			        	<td>Amount</td>
			        	<td valign='center'>".CUR." <input type='text' size='18' name='amount'></td>
			        </tr>
					".TBL_BR."
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Add &raquo;'></td>
					</tr>
		        </table>";

		# main table (layout with menu)
		$OUTPUT = "
					<center>
					<table ".TMPL_tblDflts.">
						<tr>
							<td width='65%' align='left'>$add</td>
							<td valign='top' align='center'>
								<table ".TMPL_tblDflts." width='65%'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
						</tr>
					</table>";
		return $OUTPUT;

}



# confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
    $v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
    $v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
    $v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
    if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
    }
    $v->isOk ($descript, "string", 0, 255, "Invalid Description.");
    $v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
    $v->isOk ($amount, "float", 1, 10, "Invalid amount.");
    $v->isOk ($cusid, "num", 1, 20, "Invalid customer account.");
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



	# Get bank account name
    db_connect();
    $sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
    $bankRslt = db_exec($sql);
    $bank = pg_fetch_array($bankRslt);

	# get account name
    $supRslt = get("cubit", "*", "customers", "cusnum", $cusid);
    $cus = pg_fetch_array($supRslt);

	$confirm = "
					<center>
					<h3>New Bank Payment</h3>
					<h4>Confirm entry (Please check the details)</h4>
					<table ".TMPL_tblDflts." width='60%'>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='bankid' value='$bankid'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='descript' value='$descript'>
						<input type='hidden' name='cheqnum' value='$cheqnum'>
						<input type='hidden' name='amount' value='$amount'>
						<input type='hidden' name='cusid' value='$cusid'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account</td>
							<td>$bank[accname] - $bank[bankname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Paid to</td>
							<td valign='center'>$cus[accno] - $cus[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td valign='center'>$descript</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cheque Number</td>
							<td valign='center'>$cheqnum</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." $amount</td>
						</tr>
						".TBL_BR."
						<tr>
							<td align='right'></td>
							<td align='right'><input type='submit' value='Confirm &raquo'></td>
						</tr>
					</form>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				";
	return $confirm;

}



# write
function write($_POST)
{

	# processes
	db_connect();

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusid, "num", 1, 20, "Invalid customer account.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$cheqnum = 0 + $cheqnum;

	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$bank = pg_fetch_array($rslt);

	# get account name
	$supRslt = get("cubit", "*", "customers", "cusnum", $cusid);
	$cus = pg_fetch_array($supRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT debtacc FROM departments WHERE deptid ='$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	db_connect();
	$Sl = "
		INSERT INTO stmnt 
			(cusnum, invid, amount, date, type, div, allocation_date) 
		VALUES 
			('$cusid','0','$amount', '$date','$descript','".USER_DIV."', '$date')";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	$Sl = "INSERT INTO open_stmnt (cusnum, invid, amount, date, type, div,balance) VALUES ('$cusid','0','$amount', '$date','$descript','".USER_DIV."','$amount')";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	$sql = "UPDATE customers SET balance = (balance + '$amount') WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	custledger($cusid, $bank['accnum'], $date, '0', "Payment to Customer", $amount, "d");

	custDT($amount, $cus['cusnum']);

	# record the payment record
	db_connect();
	$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, suprec, div) VALUES ('$bankid', 'withdrawal', '$date', '$cus[cusname] $cus[surname]', '$descript', '$cheqnum', '$amount', 'no', '$dept[debtacc]', '$cusid', '".USER_DIV."')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	$refnum = getrefnum();

	writetrans($dept['debtacc'], $bank['accnum'], $date, $refnum, $amount, $descript);

	# status report
	$write = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Bank Payment</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank Payment to customer : $cus[surname] added to cash book.</td>
					</tr>
				</table>
			";

	# main table (layout with menu)
	$OUTPUT = "
				<center>
				<table ".TMPL_tblDflts.">
					<tr valign='top'>
						<td width='50%'>$write</td>
						<td align='center'>
							<table ".TMPL_tblDflts." width='80%'>
								<tr>
									<th>Quick Links</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='bank-pay-add.php'>Add Bank Payment</a></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='cashbook-view.php'>View Cash Book</a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			";
	return $OUTPUT;

}


?>
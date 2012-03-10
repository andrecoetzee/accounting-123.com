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
			$OUTPUT = add($HTTP_POST_VARS);
	}
} else {
	# Display default output
	$OUTPUT = add($HTTP_POST_VARS);
}

# get templete
require("../template.php");



# Insert details
function add($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	global $HTTP_GET_VARS;

	extract($HTTP_GET_VARS);

	$id+=0;

	db_conn('cubit');

	$Sl="SELECT * FROM batch_cashbook WHERE cashid='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	extract(pg_fetch_array($Ri));

	$fbankid=$bankid;
	$tbankid=$rid;

	$date=explode('-',$date);

	$date_day = $date[2];
	$date_month = $date[1];
	$date_year = $date[0];


	if(!isset($fbankid)) {
		$fbankid=0;
		$tbankid=0;
		$day=date("d");
		$mon=date("m");
		$year=date("Y");
		$descript="";
		$cheqnum="";
		$amount="";
	}



	$frombanksel = "<select name='fbankid'>";
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet on the Database.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	while($bacc = pg_fetch_array($banks)){
		if($fbankid==$bacc['bankid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$frombanksel .= "<option value=$bacc[bankid] $sel>$bacc[accname] - $bacc[bankname] ($bacc[acctype])</option>";
	}
	$frombanksel .= "</select>";

	$tobanksel = "<select name='tbankid'>";
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
	$banks = db_exec($sql);
	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet on the Database.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}
	while($bacc = pg_fetch_array($banks)){
		if($tbankid==$bacc['bankid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$tobanksel .= "<option value='$bacc[bankid]' $sel>$bacc[accname] - $bacc[bankname] ($bacc[acctype])</option>";
	}
	$tobanksel .= "</select>";

	// Layout
	$add = "
				<h3>Bank transfer</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
					<tr>
						<th width='40%'>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>From</td>
						<td>$frombanksel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>To</td>
						<td>$tobanksel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Description</td>
						<td valign='center'><textarea cols='18' rows='2' name='descript'>$descript</textarea></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Cheque Number</td>
						<td valign='center'><input size='10' name='cheqnum' value='$cheqnum'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Amount</td>
						<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Confirm >'></td>
					</tr>
				</table>";

	# main table (layout with menu)
	$OUTPUT = "
					<center>
					<table width='100%'>
						<tr>
							<td width='65%' align='left'>$add</td>
							<td valign='top' align='center'>
								<table ".TMPL_tblDflts." width='65%'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr class='datacell'>
										<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fbankid, "num", 1, 30, "Invalid From Bank Account.");
	$v->isOk ($tbankid, "num", 1, 30, "Invalid To Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ("#", "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
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
		return $confirm.add($HTTP_POST_VARS);
	}


	# Get bank account name
	db_connect();
	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$tbankid' AND div = '".USER_DIV."'";
	$tbankRslt = db_exec($sql);
	$tbank = pg_fetch_array($tbankRslt);

	// Layout
	$confirm = "
					<h3>Bank transfer</h3>
					<h4>Confirm edit</h4>
					<table ".TMPL_tblDflts." width='300'>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='fbankid' value='$fbankid'>
						<input type='hidden' name='tbankid' value='$tbankid'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='descript' value='$descript'>
						<input type='hidden' name='cheqnum' value='$cheqnum'>
						<input type='hidden' name='amount' value='$amount'>
						<input type='hidden' name='date_day' value='$date_day'>
						<input type='hidden' name='date_month' value='$date_month'>
						<input type='hidden' name='date_year' value='$date_year'>
						<input type='hidden' name='id' value='$id'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>From</td>
							<td>$fbank[accname] - $fbank[bankname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>To</td>
							<td>$tbank[accname] - $tbank[bankname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
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
						<tr><td><br></td></tr>
						<tr>
							<td></td>
							<td align='right'><input type='submit' value='Write &raquo'></td>
						</tr>
					</form>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $confirm;

}



# write
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		return add($HTTP_POST_VARS);
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fbankid, "num", 1, 30, "Invalid From Bank Account.");
	$v->isOk ($tbankid, "num", 1, 30, "Invalid To Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

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
	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$fbankid' AND div = '".USER_DIV."'";
	$fbankRslt = db_exec($sql);
	$fbank = pg_fetch_array($fbankRslt);

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$tbankid' AND div = '".USER_DIV."'";
	$tbankRslt = db_exec($sql);
	$tbank = pg_fetch_array($tbankRslt);

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# nasty zero
	$cheqnum += 0;

	$faccid = getbankaccid($fbankid);
	$taccid = getbankaccid($tbankid);

	# Some info
	$refnum = getrefnum();

	db_conn('cubit');

	$id+=0;

	$Sl="UPDATE batch_cashbook SET bankid='$fbankid',date='$date',name='$tbank[accname] - $tbank[bankname]',descript='$descript',cheqnum='$cheqnum',amount='$amount',accinv='$taccid',rid='$tbankid' WHERE cashid='$id'";
	$Ri=db_exec($Sl) or errDie("unable to update cashbook.");

// 			# Record the payment record
// 			db_connect();
// 			$sql = "INSERT INTO batch_cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div,bt,rid) VALUES ('$fbankid', 'withdrawal', '$date', '$tbank[accname] - $tbank[bankname]', '$descript', '$cheqnum', '$amount', 'no', '$taccid', '".USER_DIV."','transfer','$tbankid')";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

// 			$lcashid = pglib_lastid("cashbook", "cashid");
//
// 			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div) VALUES ('$tbankid', 'deposit', '$date', '$fbank[accname] - $fbank[bankname]', '$descript', '$cheqnum', '$amount', 'no', '$faccid', '".USER_DIV."')";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//
// 			$lcashid2 = pglib_lastid("cashbook", "cashid");
//
// 			# restore link
// 			$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//
// 			$sql = "UPDATE cashbook SET lcashid = '$lcashid' WHERE cashid = '$lcashid2'";
// 			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//




	# Status report
	$write = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Bank transfer</th>
					</tr>
					<tr class='datacell'>
						<td>Bank transfer has been updated.</td>
					</tr>
				</table>";

	# Main table (layout with menu)
	$OUTPUT = "
					<center>
					<table width='90%'>
						<tr valign='top'>
							<td width='50%'>$write</td>
							<td align='center'>
								<table ".TMPL_tblDflts." width='80%'>
								<tr>
									<th>Quick Links</th>
								</tr>
								<tr class='datacell'>
									<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='cashbook-view.php'>View Cash Book</a></td>
								</tr>
								<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}


?>
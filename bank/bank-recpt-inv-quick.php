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
require ("../libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "method":
			if(strlen($_POST["accnum"])==0) {
				# redirect if not local supplier
				if(!is_local("customers", "cusnum", $_POST["cusid"])){
					// print "SpaceBar";
					header("Location: bank-recpt-inv-int.php?cusid=$_POST[cusid]");
					exit;
				}
			}
			$OUTPUT = method($_POST["cusid"]);
			break;
		case "alloc":
			$OUTPUT = alloc($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = sel_cus($_POST);
	}
} elseif(isset($_GET["cusid"])) {
	# Display default output
	$OUTPUT = alloc($_GET);
} else {
	# Display default output
	$OUTPUT = sel_cus($_POST);
}

# get templete
require("../template.php");




// allocation
function alloc($_POST,$err="")
{

	extract($_POST);

	if (isset($back)) {
		if(isset($e)) {
			header("Location: cashbook-entry.php");
			exit;
		}
		return sel_cus($_POST);
	}

	if (isset($print_recpt) AND strlen($print_recpt) > 0)
		$send_print = "<input type='hidden' name='print_recpt' value='$print_recpt'>";
	else 
		$send_print = "";

	if (isset($bulk_pay) AND strlen($bulk_pay) > 0)
		$send_bulk = "<input type='hidden' name='bulk_pay' value='yes'>";
	else 
		$send_bulk = "";

	$all = 0;

	$date_arr = explode ("-",$tdate);
	$date_year = $date_arr[0];
	$date_month = $date_arr[1];
	$date_day = $date_arr[2];

	require_lib("validate");
	$v = new validate();
	$v->isOk($bankid, "num", 1, 30, "Select Bank Account.");
	$v->isOk($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
	$v->isOk($cusid, "num", 1, 10, "Invalid customer number.");

	if (strlen($date_year) != 4){
		$v->isOk($bankname, "num", 1, 1, "Invalid Date year.");
	}

	if ($amt < 0.01) {
		$v->addError($amt, "Amount too small.");
	}

	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	if ($v->isError()) {
		$confirm = $v->genErrors();
		$confirm .= "<br>"."<input type='button' onClick='history.back();' value='&laquo Correction'>";
		return $confirm;//.alloc($_POST);
	}


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	// bank account name
	if (($bankid == "0") OR (($bank = qryBankAcct($bankid, "accname, bankname")) === false)) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	// customer name
	$cus = qryCustomer($cusid, "cusname, surname");

	if ($print_recpt == "yes")
		$show_print_recpt = "Yes";
	else 
		$show_print_recpt = "No";

	$confirm = "
		<h3>New Bank Receipt</h3>
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='accnum' value=''>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='cusid' value='$cusid'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amt' value='$amt'>
			$send_bulk
			$send_print
		<table ".TMPL_tblDflts.">
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
				<td>Received from</td>
				<td valign='center'>$cus[cusname] $cus[surname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>".nl2br($descript)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference</td>
				<td valign='center'>$reference</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amt</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td>Print Receipt</td>
				<td>$show_print_recpt</td>
			</tr>";


	/* OPTION 1 : AUTO ALLOCATE (allocate) */
	#we need a new why of allocating this ... stock,nonstock,pos order is counter productive
	#so, we get them all into an array, and sort that ...
	if ($all == 0) {
		$out = $amt;
		$invs_arr = array();

		// Connect to database
		db_connect();

		#####################[ GET OUTSTANDING INVOICES ]######################
		$sql = "SELECT invnum, invid, balance, terms, odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		while (($inv = pg_fetch_array($prnInvRslt)) && ($out > 0)) {
			$invs_arr[] = array ("s",$inv['odate'],"$inv[invid]","$inv[balance]");
		}


		#####################[ GET OUTSTANDING NON STOCK INVOICES ]######################
		$sql = "SELECT invnum, invid, balance, odate FROM nons_invoices WHERE cusid='$cusid' AND done='y' AND balance>0 AND div='".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		while(($inv = pg_fetch_array($prnInvRslt)) && ($out > 0)) {
			$invs_arr[] = array ("n",$inv['odate'],"$inv[invid]","$inv[balance]");
		}

		$out = sprint($out);


		#####################[ GET OUTSTANDING POS INVOICES ]######################
		$sqls = array();
		for ($i = 1; $i <= 12; ++$i) {
			$sqls[] = "
				SELECT invnum, invid, balance, odate 
				FROM \"$i\".pinvoices 
				WHERE cusnum='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."'";
		}
		$sql = implode(" UNION ", $sqls);
		$prnInvRslt = db_exec($sql);
		while($inv = pg_fetch_array($prnInvRslt)){
			$invs_arr[] = array ("p",$inv['odate'],"$inv[invid]","$inv[balance]");
		}

		if (isset($invs_arr) AND is_array ($invs_arr)){
			$confirm .= "
				<tr><td><br></td></tr>
				<tr>
					<th>Type</th>
					<th>Invoice</th>
					<th>Outstanding Amount</th>
					<th></th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";
		}


		#compile results into an array we can sort by date
		$search_arr = array ();
		foreach ($invs_arr AS $key => $array){
			$search_arr[$key] = $array[1];
		}

		#sort array by date
		asort ($search_arr);

		#add sorted invoices to payment listing
		foreach ($search_arr AS $key => $date){

			$arr = $invs_arr[$key];

			if ($arr[0] == "s"){
				$get_sql = "SELECT invnum, invid, balance, terms, odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' AND invid = '$arr[2]'  LIMIT 1";
				$run_sql = db_exec($get_sql) or errDie ("Unable to get stock invoice information.");
				if (pg_numrows($run_sql) > 0){

					$inv = pg_fetch_array ($run_sql);
					$invid = $inv['invid'];

					$val = allocamt($out, $inv["balance"]);

					$confirm .= "
						<input type='hidden' name='paidamt[$invid]' size='10' value='$val'>
						<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Stock Invoice</td>
							<td>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td>$inv[terms] days</td>
							<td>$inv[odate]</td>
							<td>".CUR." $val</td>
						</tr>";
				}

			}elseif ($arr[0] == "n"){
//sdate as 
				$get_sql = "SELECT invnum, invid, balance, odate FROM nons_invoices WHERE cusid='$cusid' AND done='y' AND balance>0 AND div='".USER_DIV."' AND invid = '$arr[2]' LIMIT 1";
				$run_sql = db_exec($get_sql) or errDie ("Unable to get non stock information.");
				if (pg_numrows($run_sql) > 0){

					$inv = pg_fetch_array ($run_sql);
					$invid = $inv['invid'];

					$val = allocamt($out, $inv["balance"]);

					$confirm .= "
						<input type='hidden' name='paidamt[$invid]' value='$val'>
						<input type='hidden' name='itype[$invid]' value='Yes'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Non Stock Invoice</td>
							<td><input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td></td>
							<td>$inv[odate]</td>
							<td>".CUR." $val</td>
						</tr>";
				}

			}else {

				$sqls = array();
				for ($i = 1; $i <= 12; ++$i) {
					$sqls[] = "
						SELECT invnum, invid, balance, odate 
						FROM \"$i\".pinvoices 
						WHERE cusnum='$cusid' AND done='y' AND balance > 0 AND div='".USER_DIV."' AND invid = '$arr[2]'";
				}
				$get_sql = implode(" UNION ", $sqls);
				$run_sql = db_exec($get_sql) or errDie ("Unable to get pos invoice information.");
				if (pg_numrows($run_sql) > 0){

					$inv = pg_fetch_array ($run_sql);
					$invid = $inv['invid'];

					$val = allocamt($out, $inv["balance"]);

					$confirm .= "
						<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
						<input type='hidden' name='paidamt[$invid]' size='10' value='$val'>
						<input type='hidden' name='ptype[$invid]' value='YnYn'>
						<tr bgcolor='".bgcolor($i)."'>
							<td>POS Invoice</td>
							<td>$inv[invnum]</td>
							<td>".CUR." $inv[balance]</td>
							<td></td>
							<td>$inv[odate]</td>
							<td>".CUR." $val</td>
						</tr>";
				}

			}
		}

		#if there is any amount unallocated, it goes to general transaction
		$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6'><b>A general transaction will credit the client's account with ".CUR." $out </b></td>
			</tr>";

	}

	vsprint($out);

	$confirm .= "
			<input type='hidden' name='out' value='$out'>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../core/trans-new.php", "Journal Transactions"),
			ql("../customers-view.php", "View Customers")
		);
	return $confirm;

}


/* confirm function */
function confirm($_POST)
{

	extract($_POST);

	if (isset($back)) {
		header ("Location: bank-recpt-inv.php?cusnum=$cusid&descript=$descript&reference=$reference&amt=$amt");
		die;
	}

	if (!isset($print_recpt))
		$print_recpt = "";
	if (isset($bulk_pay) AND strlen($bulk_pay) > 0){
		$send_bulk = "<input type='hidden' name='bulk_pay' value='yes'>";
	}else {
		$send_bulk = "";
	}

	if (!isset($out1)) $out1 = '';
	if (!isset($out2)) $out2 = '';
	if (!isset($out3)) $out3 = '';
	if (!isset($out4)) $out4 = '';
	if (!isset($out5)) $out5 = '';

	require_lib("validate");
	$v = new  validate ();
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($date, "date", 1, 14, "Invalid Date.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
//	$v->isOk($out, "float", 1, 40, "Invalid out amount.");
	$v->isOk($out1, "float", 0, 40, "Invalid paid amount(currant).");
	$v->isOk($out2, "float", 0, 40, "Invalid paid amount(30).");
	$v->isOk($out3, "float", 0, 40, "Invalid paid amount(60).");
	$v->isOk($out4, "float", 0, 40, "Invalid paid amount(90).");
	$v->isOk($out5, "float", 0, 40, "Invalid paid amount(120).");
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$v->isOk($print_recpt, "string", 0, 10, "Invalid Print Receipt Setting.");

	if (isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}

			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No. [$key]");
			$v->isOk ($paidamt[$key], "float", 1, 40, "Invalid amount to be paid. [$key]");
		}
	}

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}

		$_POST['OUT1'] = $out1 + 0;
		$_POST['OUT2'] = $out2 + 0;
		$_POST['OUT3'] = $out3 + 0;
		$_POST['OUT4'] = $out4 + 0;
		$_POST['OUT5'] = $out5 + 0;

		return $confirm.alloc($_POST);
	}

	$out += 0;
	$OUT1 = $out1 + 0;
	$OUT2 = $out2 + 0;
	$OUT3 = $out3 + 0;
	$OUT4 = $out4 + 0;
	$OUT5 = $out5 + 0;

	$tot = 0;
	if (isset($invids)) {
		foreach($invids as $key => $value){
			if($paidamt[$key] < 0.01){
				continue;
			}

			$tot += $paidamt[$key];
		}
	}

	if (isset($open_amount)) {
		$tot += array_sum($open_amount);
	}

	$tot = sprint($tot);
	$amt = sprint($amt);
	$out = sprint($out);

	if (sprint(($tot + $out + $out1 + $out2 + $out3 + $out4 + $out5) - $amt) != sprint(0)) {
		$_POST['OUT1'] = $OUT1;
		$_POST['OUT2'] = $OUT2;
		$_POST['OUT3'] = $OUT3;
		$_POST['OUT4'] = $OUT4;
		$_POST['OUT5'] = $OUT5;

		return "<li class='err'>The total amount for invoices not equal to the amount received.
			Please check the details.</li>".alloc($_POST);
	}

	if (isset($bout)) {
		$out = $bout;
	}

	$confirm = "
		<h3>New Bank Receipt</h3>
		<h4>Confirm entry (Please check the details)</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='cusid' value='$cusid'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='reference' value='$reference'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='out' value='$out'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='OUT1' value='$OUT1'>
			<input type='hidden' name='OUT2' value='$OUT2'>
			<input type='hidden' name='OUT3' value='$OUT3'>
			<input type='hidden' name='OUT4' value='$OUT4'>
			<input type='hidden' name='OUT5' value='$OUT5'>
			<input type='hidden' name='amt' value='$amt'>
			<input type='hidden' name='print_recpt' value='$print_recpt'>
			$send_bulk
		<table ".TMPL_tblDflts.">";

	/* bank account name */
	if (($bankid == "0") OR (($bank = qryBankAcct($bankid, "accname, bankname")) === false)) {
		$bank['accname'] = "Cash";
		$bank['bankname'] = "";
	}

	/* customer name */
	$cus = qryCustomer($cusid, "cusname, surname");

	if ($print_recpt == "yes")
		$show_print_recpt = "Yes";
	else 
		$show_print_recpt = "No";

	$confirm .= "
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
		<td>Received from</td>
		<td valign='center'>$cus[cusname] $cus[surname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Description</td>
		<td valign='center'>$descript</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Reference</td>
		<td valign='center'>$reference</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Cheque Number</td>
		<td valign='center'>$cheqnum</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Amount</td>
		<td valign='center'>".CUR." $amt</td>
	</tr>
	".TBL_BR."
	<tr bgcolor='".bgcolorg()."'>
		<td>Print Receipt</td>
		<td>$show_print_recpt</td>
	</tr>";

	/* OPTION 1 : AUTO ALLOCATE (confirm) */
	if ($all == 0) {
		// Layout
		$confirm .= "
		".TBL_BR."
		<tr>
			<td colspan='2'><h3>Invoices</h3></td>
		</tr>
		<tr>
			<th>Invoice Number</th>
			<th>Outstanding amount</th>
			<th>Terms</th>
			<th>Date</th>
			<th>Amount</th>
		</tr>";

		$i = 0;
		if (isset($invids)) {
			foreach($invids as $key => $value){
				if($paidamt[$invids[$key]] < 0.01){
					continue;
				}

				db_connect();
				$ii = $invids[$key];
				if (!isset($itype[$ii]) && !isset($ptype[$ii])) {
					# Get all the details
					$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'> - Invalid ord number $invids[$key].";
					}

					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					$confirm .= "
					<input type='hidden' name='paidamt[$invid]' size='7' value='$paidamt[$invid]'>
					<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td>$inv[terms] days</td>
						<td>$inv[odate]</td>
						<td>".CUR." $paidamt[$invid]</td>
					</tr>";
				} else if (!isset($ptype[$ii])) {
					$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'> - Invalid ord number $invids[$key].</li>";
					}

					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					$confirm .= "
					<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
					<input type='hidden' name='paidamt[$invid]' size='7' value='$paidamt[$invid]'>
					<input type='hidden' name='itype[$invid]' value='y'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td>".CUR." $paidamt[$invid]</td>
					</tr>";
				} else {
					$sqls = array();
					for ($i = 1; $i <=12; ++$i) {
						$sqls[] = "SELECT invnum,invid,balance,odate FROM \"$i\".pinvoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					}
					$sql = implode(" UNION ", $sqls);

					$prnInvRslt = db_exec($sql);

					$inv = pg_fetch_array($prnInvRslt);

					$invid = $inv['invid'];

					$paidamt[$invid] = sprint ($paidamt[$invid]);
					
					$confirm .= "
					<input type='hidden' size='20' name='invids[$invid]' value='$inv[invid]'>
					<input type='hidden' name='paidamt[$invid]' size='7' value='$paidamt[$invid]'>
					<input type='hidden' name='ptype[$invid]' value='y'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$inv[invnum]</td>
						<td>".CUR." $inv[balance]</td>
						<td></td>
						<td>$inv[odate]</td>
						<td>".CUR." $paidamt[$invid]</td>
					</tr>";
				}
			}
		}

		if ($out > 0) {
			/* START OPEN ITEMS */
			$ox="";

			db_conn('cubit');
			$sql = "SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$rslt = db_exec($sql) or errDie("Unable to get open items.");

			$open_out = $out;

			$i = 0;

			while ($od = pg_fetch_array($rslt)) {
				if($open_out == 0) {
					continue;
				}

				$oid = $od['id'];

				if ($open_out >= $od['balance']) {
					$open_amount[$oid] = $od['balance'];
					$open_out = sprint($open_out - $od['balance']);
					$ox .= "
						<input type='hidden' size='20' name='open[$oid]' value='$oid'>
						<input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>
						<tr bgcolor='".bgcolorg()."'>
							<td>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td>".CUR." $open_amount[$oid]</td>
						</tr>";
				} else if ($open_out < $od['balance']) {
					$open_amount[$oid] = $open_out;
					$open_out = 0;

					$ox .= "
						<input type='hidden' size='20' name='open[$oid]' value='$od[id]'>
						<input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>
						<tr bgcolor='".bgcolorg()."'>
							<td>$od[type]</td>
							<td>".CUR." $od[balance]</td>
							<td>$od[date]</td>
							<td>".CUR." $open_amount[$oid]</td>
						</tr>";
				}
			}

			if (open()) {
				$confirm .= "
				".TBL_BR."
				<tr>
					<td colspan='2'><h3>Outstanding Transactions</h3></td>
				</tr>
				<tr>
					<th>Description</th>
					<th>Outstanding Amount</th>
					<th>Date</th>
					<th>Amount</th>
				</tr>";

				$confirm .= $ox;
				$bout = $out;
				$out = $open_out;

				if ($out > 0) {
					$confirm .="
					<tr bgcolor='".TMPL_tblDataColor2."'>
						<td colspan='4'><b>A general transaction will credit the
							client's account with ".CUR." $out </b></td>
					</tr>";
				}

				$out = $bout;
			} else {
				$confirm .= "
				<tr bgcolor='".TMPL_tblDataColor2."'>
					<td colspan='5'><b>A general transaction will credit the
						client's account with ".CUR." $out </b></td>
				</tr>";
			}
		}
	}

	vsprint($out);
	vsprint($out1);
	vsprint($out2);
	vsprint($out3);
	vsprint($out4);
	vsprint($out5);
/*
	<tr>
		<td colspan='5' align='right'><input type='submit' name='batch' value='Add To Batch'></td>
	</tr>
*/
	$confirm .= "
			<input type='hidden' name='out1' value='$out1'>
			<input type='hidden' name='out2' value='$out2'>
			<input type='hidden' name='out3' value='$out3'>
			<input type='hidden' name='out4' value='$out4'>
			<input type='hidden' name='out5' value='$out5'>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='4'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>"
	.mkQuickLinks(
		ql("../core/trans-new.php", "Journal Transactions"),
		ql("../customers-view.php", "View Customers")
	);
	return $confirm;

}



/* write function */
function write($_POST)
{

	extract($_POST);

	if (isset($back)) {
		unset($_POST["back"]);
		return alloc($_POST);
	}

	require_lib("validate");
	$v = new  validate ();
	$v->isOk($all, "num", 1,1, "Invalid allocation.");
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($date, "date", 1, 14, "Invalid Date.");
	$v->isOk($out, "float", 1, 40, "Invalid out amount.");
	$v->isOk($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk($reference, "string", 0, 50, "Invalid Reference Name/Number.");
	$v->isOk($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk($amt, "float", 1, 40, "Invalid amount.");
	$v->isOk($cusid, "num", 1, 40, "Invalid customer number.");
	$v->isOk($out1, "float", 0, 40, "Invalid paid amount(currant).");
	$v->isOk($out2, "float", 0, 40, "Invalid paid amount(30).");
	$v->isOk($out3, "float", 0, 40, "Invalid paid amount(60).");
	$v->isOk($out4, "float", 0, 40, "Invalid paid amount(90).");
	$v->isOk($out5, "float", 0, 40, "Invalid paid amount(120).");

	if (isset($invids)) {
		foreach($invids as $key => $value){
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No.");
			$v->isOk ($paidamt[$key], "float", 1, 40, "Invalid amount to be paid.");
		}
	}

	if ($v->isError ()) {
		$confirm = $v->genErrors();
		return $confirm.confirm($_POST);
	}




	/* get bank account id of cash on hand account IF this entry is cash */
	if ((($bank_acc = getbankaccid($bankid)) === false) OR ($bankid == "0")) {
		//old function didnt check if cash is selected ... if(($bank_acc = getbankaccid($bankid)) === false) {
		$sql = "SELECT accid FROM core.accounts WHERE accname='Cash on Hand'";
		$rslt = db_exec($sql);

		if (pg_num_rows($rslt) < 1) {
			if ($bankid == 0) {
				return "There is no 'Cash on Hand' account, there was one, but
						its not there now, you must have deleted it, if you want
						to use cash functionality please create a 'Cash on Hand' account.";
			} else {
				return "Invalid bank acc.";
			}
		}

		$bank_acc = pg_fetch_result($rslt, 0);

	}

	$cus = qryCustomer($cusid, "cusnum, deptid, cusname, surname");
	$dept = qryDepartment($cus["deptid"], "debtacc");
	$refnum = getrefnum();

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# date format
	$sdate = explode("-", $date);
	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
	$cheqnum = 0 + $cheqnum;
	$pay = "";
	$accdate = $sdate;

	/* Paid invoices */
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";

	/* OPTION 1 : AUTO ALLOCATE (write) */
	if ($all == 0) {

		# update the customer (make balance less)
		$sql = "UPDATE cubit.customers SET balance = (balance - '$amt'::numeric(13,2))
				WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$reverse_allocation_dates = "";
		$reverse_allocation_amounts = "";
		if (isset($invids)) {
			foreach($invids as $key => $value) {
				$ii = $invids[$key];
				/* OPTION 1: STOCK INVOICES */
				if (!isset($itype[$ii]) && !isset($ptype[$ii])) {
					$sql = "
						SELECT prd,invnum,odate 
						FROM cubit.invoices
						WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.</li>";
					}
					$inv = pg_fetch_array($invRslt);

					$inv['invnum'] += 0;

					// reduce invoice balance
					$sql = "UPDATE cubit.invoices
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, date, 
							type, div, allocation_date
						) VALUES (
							'$cus[cusnum]','$inv[invnum]', 
							'".($paidamt[$key] - ($paidamt[$key] * 2))."', '$sdate', 
							'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
						)";
					if (!(isset($bulk_pay) AND strlen($bulk_pay) > 0)){
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}else {
						$reverse_allocation_dates .= "$inv[odate]|";
						$reverse_allocation_amounts .= sprint($paidamt[$key] - ($paidamt[$key] * 2))."|";
					}
					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";

					if ($inv['prd'] == "0") {
						$inv['prd'] = PRD_DB;
					}

					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				/* OPTION 1: NONS STOCK INVOICES */
				} else if (!isset($ptype[$ii])) {
					$sql = "
						SELECT prd,invnum,descrip,age,odate 
						FROM cubit.nons_invoices 
						WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.";
					}

					$inv = pg_fetch_array($invRslt);

					$inv['invnum'] += 0;

					# reduce the money that has been paid
					$sql = "UPDATE cubit.nons_invoices
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, date, 
							type, 
							div, allocation_date
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', 
							'".($paidamt[$key] - ($paidamt[$key] * 2))."', '$sdate', 
							'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', 
							'".USER_DIV."', '$inv[odate]'
						)";
					if (!(isset($bulk_pay) AND strlen($bulk_pay) > 0)){
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}else {
						$reverse_allocation_dates .= "$inv[odate]|";
						$reverse_allocation_amounts .= sprint($paidamt[$key] - ($paidamt[$key] * 2))."|";
					}
					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");

					//recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$accdate);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|0";
					$rages .= "|$inv[age]";
					$invidsers .= " - $inv[invnum]";
				} else {
					/* pos invoices */
					$sqls = array();
					for ($i = 1; $i <=12; ++$i) {
						$sqls[] = "
							SELECT '$i' AS prd,invid,invnum,odate 
							FROM \"$i\".pinvoices 
							WHERE invid='$invids[$key]' AND div='".USER_DIV."'";
					}
					$sql = implode(" UNION ", $sqls);

					$invRslt = db_exec($sql) or errDie ("Unable to retrieve invoice details from database.");

					if (pg_numrows ($invRslt) < 1) {
						return "<li class='err'>Invalid Invoice Number.";
					}

					$inv = pg_fetch_array($invRslt);

					// reduce the invoice balance
					$sql = "UPDATE \"$inv[prd]\".pinvoices
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE cubit.open_stmnt
							SET balance = (balance - $paidamt[$key]::numeric(13,2))
							WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, date, 
							type, div, 
							allocation_date
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', 
							'".($paidamt[$key] - ($paidamt[$key] * 2))."', '$sdate', 
							'Payment for Non Stock Invoice No. $inv[invnum]', '".USER_DIV."', 
							'$inv[odate]'
						)";
					if (!(isset($bulk_pay) AND strlen($bulk_pay) > 0)){
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
					}else {
						$reverse_allocation_dates .= "$inv[odate]|";
						$reverse_allocation_amounts .= sprint($paidamt[$key] - ($paidamt[$key] * 2))."|";
					}
					custledger($cus['cusnum'], $bank_acc, $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum]", $paidamt[$key], "c");

					//recordCT($paidamt[$key], $cus['cusnum'],0,$accdate);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				}
			}

			#record the total for the statement if bulk is selected
			if (isset($bulk_pay) AND strlen($bulk_pay) > 0){
				$arrtotal = sprint(array_sum($paidamt));
				$sql = "
						INSERT INTO cubit.stmnt (
							cusnum, invid, 
							amount, date, 
							type, div, 
							allocation_date, reverse_allocation_dates, reverse_allocation_amounts
						) VALUES (
							'$cus[cusnum]', '$inv[invnum]', 
							'".($arrtotal - ($arrtotal * 2))."', '$sdate', 
							'Payment Received (Ref:$reference)', '".USER_DIV."', 
							'1500-01-01', '$reverse_allocation_dates', '$reverse_allocation_amounts'
						)";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);				
			}
		}

		$cols = grp(
			m("bankid", $bankid),
			m("trantype", "deposit"),
			m("date", $sdate),
			m("name", "$cus[cusname] $cus[surname]"),
			m("descript", "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]"),
			m("cheqnum", $cheqnum),
			m("amount", $amt),
			m("banked", "no"),
			m("accinv", $dept["debtacc"]),
			m("cusnum", $cus["cusnum"]),
			m("rinvids", $rinvids),
			m("amounts", $amounts),
			m("invprds", $invprds),
			m("rages", $rages),
			m("reference", $reference),
			m("div", USER_DIV)
		);

		$dbobj = new dbUpdate("cashbook", "cubit", $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		/*
		$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript,
					cheqnum, amount, banked, accinv, cusnum, rinvids, amounts,
					invprds, rages, reference, div)
				VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]',
					'',
					'$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]',
					'$rinvids', '$amounts', '$invprds', '$rages', '$reference',
					'".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		*/

		writetrans($bank_acc, $dept['debtacc'], $accdate, $refnum, $amt,
			"Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

		db_conn('cubit');
		if ($out > 0) {
			/* START OPEN ITEMS */
			$openstmnt = new dbSelect("open_stmnt", "cubit", grp(
				m("where", "balance>0 AND cusnum='$cusid'"),
				m("order", "date")
			));
			$openstmnt->run();

			$open_out = $out;
			$i = 0;
			$ox = "";

			while ($od = $openstmnt->fetch_array()) {
				if ($open_out == 0) {
					continue;
				}

				$oid = $od['id'];
				if ($open_out >= $od['balance']) {
					$open_amount[$oid] = $od['balance'];
					$open_out = sprint($open_out - $od['balance']);
					$ox.= "
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' size='20' name='open[$oid]' value='$oid'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
							</tr>";

					$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
					$Ri = db_exec($Sl) or errDie("Unable to update statement.");

				} elseif($open_out < $od['balance']) {
					$open_amount[$oid] = $open_out;
					$open_out = 0;
					$ox .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' size='20' name='open[$oid]' value='$od[id]'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type='hidden' name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
							</tr>";

					$Sl = "UPDATE cubit.open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
					$Ri = db_exec($Sl)or errDie("Unable to update statement.");
				}
				$i++;
			}

			if(open()) {
				$bout = $out;
				$out = $open_out;
				if($out > 0) {
					$sql = "INSERT INTO cubit.open_stmnt(cusnum, invid, amount, balance, date, type, st, div) VALUES('$cus[cusnum]', '0', '-$out', '-$out', '$sdate', 'Payment Received', 'n', '".USER_DIV."')";
					$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
					//$confirm .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
				}

				$out = $bout;
			} else  {//$confirm .="<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
			}

		}

		if ($out > 0) {
			recordCT($out, $cus['cusnum'],0,$accdate);

			$cols = grp(
				m("cusnum", $cus["cusnum"]),
				m("invid", 0),
				m("amount", -$out),
				m("date", $sdate),
				m("type", "Payment Received"),
				m("div", USER_DIV),
				m("allocation_date", $accdate)
			);

			$dbobj = new dbUpdate("stmnt", "cubit", $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();

			custledger($cus['cusnum'], $bank_acc, $sdate, "PAYMENT", "Payment received.", $out, "c");
		}
	}



	/* start moving invoices */
	// move invoices that are fully paid
	$sql = "SELECT * FROM cubit.invoices WHERE balance=0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	while ($x = pg_fetch_array($invbRslt)) {
		if (($prd = $x['prd']) == "0") {
			$prd = PRD_DB;
		}

		// move invoice
		$cols = grp(
			m("invid", $x["invid"]),
			m("invnum", $x["invnum"]),
			m("deptid", $x["deptid"]),
			m("cusnum", $x["cusnum"]),
			m("deptname", $x["deptname"]),
			m("cusacc", $x["cusacc"]),
			m("cusname", $x["cusname"]),
			m("surname", $x["surname"]),
			m("cusaddr", $x["cusaddr"]),
			m("cusvatno", $x["cusvatno"]),
			m("cordno", $x["cordno"]),
			m("ordno", $x["ordno"]),
			m("chrgvat", $x["chrgvat"]),
			m("terms", $x["terms"]),
			m("traddisc", $x["traddisc"]),
			m("salespn", $x["salespn"]),
			m("odate", $x["odate"]),
			m("delchrg", $x["delchrg"]),
			m("subtot", $x["subtot"]),
			m("vat", $x["vat"]),
			m("total", $x["total"]),
			m("age", $x["age"]),
			m("comm", $x["comm"]),
			m("discount", $x["discount"]),
			m("delivery", $x["delivery"]),
			m("docref", $x["docref"]),
			m("prd", $x["prd"]),
			m("delvat", $x["delvat"]),
			m("balance", 0),
			m("printed", "y"),
			m("done", "y"),
			m("username", USER_NAME),
			m("div", USER_DIV)
		);

		$dbobj = new dbUpdate("invoices", $prd, $cols);
		$dbobj->run(DB_INSERT);
		$dbobj->free();

		// record movement
		$cols = grp(
			m("invtype", "inv"),
			m("invnum", $x["invnum"]),
			m("prd", $x["prd"]),
			m("docref", $x["docref"]),
			m("div", USER_DIV)
		);

		$dbobj->setTable("movinv", "cubit");
		$dbobj->setOpt($cols);
		$dbobj->run();
		$dbobj->free();

		// move invoice items
		$inv_items = new dbSelect("inv_items", "cubit", grp(
			m("where", wgrp(
				m("invid", $x["invid"]),
				m("div", USER_DIV)
			))
		));
		$inv_items->run();

		while ($xi = $inv_items->fetch_array()){
			$xi['vatcode'] += 0;
			$xi['account'] += 0;
			$xi['del'] += 0;

			$cols = grp(
				m("invid", $x["invid"]),
				m("whid", $xi["whid"]),
				m("stkid", $xi["stkid"]),
				m("qty", $xi["qty"]),
				m("unitcost", $xi["unitcost"]),
				m("amt", $xi["amt"]),
				m("disc", $xi["disc"]),
				m("discp", $xi["discp"]),
				m("vatcode", $xi["vatcode"]),
				m("account", $xi["account"]),
				m("description", $xi["description"]),
				m("del", $xi["del"]),
				m("noted", $xi["noted"]),
				m("serno", $xi["serno"]),
				m("div", USER_DIV)
			);

			$dbobj->setTable("inv_items", $prd);
			$dbobj->setOpt($cols);
			$dbobj->run();
			$dbobj->free();
		}

		/* remove invoice from cubit schema */
		$dbobj = new dbDelete("invoices", "cubit", wgrp(
			m("invid", $x["invid"]),
			m("div", USER_DIV)
		));
		$dbobj->run();

		$dbobj->setTable("inv_items", "cubit");
		$dbobj->run();
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$cashbook_id = pglib_lastid("cashbook","cashid");

	if (isset($print_recpt) AND $print_recpt == "yes")
		$showreceipt = "<script>printer ('bank/bank-recpt-inv-print.php?recid=$cashbook_id');</script>";
	else 
		$showreceipt = "";

	// status report
	$write = "
		$showreceipt
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Bank Receipt</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Receipt added to cash book.</td>
			</tr>
		</table>";

	$OUTPUT = "
		<center>
		<table width='90%'>
			<tr valign='top'>
				<td width='50%'>$write</td>
				<td align='center'>"
					.mkQuickLinks(
						ql("bank-pay-add.php", "Add Bank Payment"),
						ql("bank-recpt-add.php", "Add Bank Receipt"),
						ql("bank-recpt-inv.php", "Add Customer Payment"),
						ql("cashbook-view.php", "View Cash Book")
					)."
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}




function age($cusnum, $days)
{

	$ldays  = $days;
	if($days == 149)
	$ldays = (365 * 10);

	if(div_isset("DEBT_AGE", "mon")){
		switch($days){
			case 29:
				return ageage($cusnum, 0);
			case 59:
				return ageage($cusnum, 1);
			case 89:
				return ageage($cusnum, 2);
			case 119:
				return ageage($cusnum, 3);
			case 149:
				return ageage($cusnum, 4);
		}
	}

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y'
				AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."'
				AND odate <'".extlib_ago($days-30)."'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}



function ageage($cusnum, $age)
{

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM cubit.invoices
			WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age'
				AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM cubit.custran
			WHERE cusnum = '$cusnum'
				AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;

}


# records for CT
function recordCT($amount, $cusnum, $age, $date="", $changemon = false)
{

	/*
	db_connect();

	if($date=="") {
	$date=date("Y-m-d");
	}

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
	while($dat = pg_fetch_array($rs)){
	if(floatval($amount) > 0){
	if($dat['balance'] > $amount){
	# Remove make amount less
	$sql = "UPDATE custran SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	$amount =0 ;
	}else{
	# remove small ones
	//if($dat['balance'] > $amount){
	$amount -= $dat['balance'];
	$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
	$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
	//}
	}
	}
	}
	if($amount > 0){
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}
	}else{
	$amount = ($amount * (-1));

	/* Make transaction record for age analysis
	//$odate = date("Y-m-d");
	$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	*/

	db_connect();

	if($date == "") {
		$date = date("Y-m-d");
	}

	$amount = ($amount * (-1));

	/*if ($changemon === false) {
	$date_ins = "'$date'";
	} else {
	$prd = $age * 30;
	$date_ins = "('$date'::date - '$prd days'::interval)::date";
	}*/

	$date_ins = "$date";

	if($age != "0"){
		switch ($age){
			case "1":
				$days = 30;
				break;
			case "2":
				$days = 60;
				break;
			case "3":
				$days = 90;
				break;
			case "4":
				$days = 120;
				break;
			default:
				$days = 30;
		}
		$date_ins = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
		$extra1 = ",actual_date";
		$extra2 = ",'$date'";
	}else {
		$extra1 = "";
		$extra2 = "";
	}

	$sql = "
		INSERT INTO custran (
			cusnum, odate, balance, div, age $extra1
		) VALUES (
			'$cusnum', '$date_ins', '$amount', '".USER_DIV."', '$age' $extra2
		)";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
}

function allocamt(&$tot, $invbal)
{

	if ($tot >= $invbal) {
		$val = $invbal;
		$tot -= $invbal;
	} else {
		$val = $tot;
		$tot = 0;
	}

	return sprint($val);

}



?>

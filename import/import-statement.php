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
require ("../settings.php");
require("../core-settings.php");
require ("../libs/ext.lib.php");

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "write1":
			$OUTPUT = write1($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "write2":
			$OUTPUT = write2($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "write3":
			$OUTPUT = write3($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "write4":
			$OUTPUT = write4($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "write5":
			$OUTPUT = write5($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "enter_actions":
			$OUTPUT = enter_actions($HTTP_POST_VARS);
			break;
		case "confirm_actions":
			$OUTPUT = confirm_actions($HTTP_POST_VARS);
			break;
		case "confirm_actions2":
			$OUTPUT = confirm_actions2($HTTP_POST_VARS);
			break;
		case "write_actions":
			$OUTPUT = write_actions($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($HTTP_GET_VARS["enter"])) {
	$OUTPUT = enter_actions($HTTP_POST_VARS);
} else {
	$OUTPUT = select_file();
}

$OUTPUT.= "
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='import-settings.php'>Statement Import Settings</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

require("../template.php");




function select_file ()
{

	global $HTTP_POST_VARS;

	$banks = "
		<select name='key'>
			<option value='write4'>ABSA (ASCII), * seperated</option>
			<option value='write3'>FNB (CSV), Comma seperated statement</option>
			<option value='write2'>Nedbank (CSV), Comma seperated</option>
			<option value='write5'>Nedbank (CSV), Comma seperated (Alternative)</option>
			<option value='write1'>Standard Bank (CSV), Comma seperated history statement</option>
		</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_data";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$ex = "<li class='err'>Notice: You have ".pg_num_rows($Ri)." unallocated transactions, click  <a href='import-statement.php?enter=yes'>HERE</a> to view them</li></td></tr>";
	} else {
		$ex = "";
	}

	db_connect();

	$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$Ri= db_exec($Sl) or errDie("Unable to get accounts.");
	$numrows = pg_num_rows($Ri);
	if(($numrows) < 1){
		return "<li class='err'> There are no bank accounts.";
	}

	$accounts = "<select name='bankid'>";
	while($acc = pg_fetch_array($Ri)){
		$accounts .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}
	$accounts .= "</select>";

	$OUTPUT = "
		<h3>Import Bank Statement</h3>
			<li class='err'>Before allocating to general ledger accounts please ensure that you create new accounts where necessary.</li>
			$ex
		<form method='POST' enctype='multipart/form-data' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Statement details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$accounts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank:</td>
				<td>$banks</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Please select your statement</td>
				<td><input type='file' name='compfile'></td>
			</tr>
			".TBL_BR."
			<tr><td colspan='2' align='right'><input type='submit' value='Import &raquo;'></td></tr>
		</form>
		</table>";
	return $OUTPUT;

}



//comma seperated(Standard Bank)
function write1($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	$bankid += 0;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[1]) || !w1vdate($datas[1])) {
			continue;
		}

		$date = w1vdate($datas[1]);

		$amount = $datas[3];
		$amount += 0;
		$amount = sprint($amount);

		$description = safe($datas[4]);

		$from = safe($datas[5]);

		$code = safe($datas[6]);

		$ex1 = safe($datas[0]);
		$ex2 = safe($datas[2]);
		$ex3 = safe($datas[7]);

		$Sl = "SELECT * FROM statement_history WHERE date='$date' AND amount='$amount' AND description='$description' AND contra='$from' AND code='$code' AND ex1='$ex1' AND ex2='$ex2' AND ex3='$ex3'";
		$Ri=db_exec($Sl) or errDie("unable to check data.");

		if(pg_num_rows($Ri) < 1) {

			$Sl = "
				INSERT INTO statement_data (
					date, amount, description, contra, code, ex1, ex2, ex3, by, 
					bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 
					'Standard Bank', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

			$Sl = "
				INSERT INTO statement_history (
					date, amount, description, contra, code, ex1, ex2, ex3, by, 
					bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 
					'Standard Bank', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

		}
	}

	fclose($file);
	return enter_actions($HTTP_POST_VARS);

}



//comma seperated(nedbank Bank)
function write2($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	$bankid += 0;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");
//	$file = fopen($importfile, "r");
	
	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	$out = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Amount</th>
				<th>Description</th>
				<th>Ex1</th>
			</tr>";

	while (!feof($file) ) {

		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[4]) || !w2vdate($datas[0])) {
			continue;
		}

		$date = w2vdate($datas[0]);

		$amount = $datas[2];
		$amount += 0;
		$amount = sprint($amount);

		if($amount == 0) {
			continue;
		}

		$description = safe($datas[1]);

		//$from=$datas[5];

		//$code=$datas[6];

		$ex1 = safe($datas[3]);
		//$ex2=$datas[2];
		//$ex3=$datas[7];

		//$out.="<tr><td>$date</td><td>$amount</td><td>$description</td><td>$ex1</td><td></td></tr>";

		$Sl = "SELECT * FROM statement_history WHERE date='$date' AND amount='$amount' AND description='$description' AND ex1='$ex1'";
		$Ri = db_exec($Sl) or errDie("unable to check data.");

		 if(pg_num_rows($Ri) < 1) {

			$Sl = "
				INSERT INTO statement_data (
					date, amount, description, contra, code, ex1, ex2, ex3, by, 
					bank, account
				) VALUES (
					'$date', '$amount', '$description', '$description', '', '$ex1', '', '', '".USER_DIV."', 
					'Nebbank', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

			$Sl = "
				INSERT INTO statement_history (
					date, amount, description, contra, code, ex1, ex2, ex3, by, 
					bank, account
				) VALUES (
					'$date', '$amount', '$description', '$description', '', '$ex1', '', '', '".USER_DIV."', 
					'Nebbank', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

		}
	}

	fclose($file);

	//return $out."</table>";
	return enter_actions($HTTP_POST_VARS);

}



//comma seperated(FNB)
function write3($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	$bankid += 0;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");

	if ($file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[3]) || !($date = w3vdate($datas[0], "dmY"))) {
			continue;
		}

		$amount = $datas[1];
		$amount += 0;
		$amount = sprint($amount);

		$description = safe($datas[3]);

		$from = safe($datas[3]);

		$code = "";

		$ex1 = safe($datas[2]);
		$ex2 = "";
		$ex3 = "";

		$Sl = "
			SELECT * FROM statement_history 
			WHERE date='$date' AND amount='$amount' AND description='$description' AND contra='$from' AND code='$code' AND ex1='$ex1' AND ex2='$ex2' AND ex3='$ex3'";
		$Ri = db_exec($Sl) or errDie("unable to check data.");

		 if(pg_num_rows($Ri)<1) {
			$sql = "
				INSERT INTO statement_data (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'Standard Bank', '$bankid'
				)";
			$Ri = db_exec($sql) or errDie("error importing statement.");

			$Sl = "
				INSERT INTO statement_history (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'Standard Bank', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

		}
	}

	fclose($file);
	return enter_actions($HTTP_POST_VARS);

}



//* seperated(Absa Bank)
function write4($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	$bankid += 0;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode("*",$data);

		if(!isset($datas[3]) || !w4vdate($datas[0])) {
			continue;
		}

		$date = w4vdate($datas[0]);

		$amount = $datas[2];
		$amount += 0;
		$amount = sprint($amount);

		$description = safe($datas[1]);

		$from = safe($datas[1]);

		$code = "";

		$ex1 = safe($datas[3]);
		$ex2 ="";
		$ex3 = "";

		$Sl = "SELECT * FROM statement_history WHERE date='$date' AND amount='$amount' AND description='$description' AND contra='$from' AND code='$code' AND ex1='$ex1' AND ex2='$ex2' AND ex3='$ex3'";
		$Ri = db_exec($Sl) or errDie("unable to check data.");

		if(pg_num_rows($Ri) < 1) {

			$Sl = "
				INSERT INTO statement_data (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'ABSA', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

			$Sl = "
				INSERT INTO statement_history (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'ABSA', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

		}
	}

	fclose($file);
	return enter_actions($HTTP_POST_VARS);

}


function write5($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	$bankid += 0;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	$linecount = 0;

	while (!feof($file) ) {

		$linecount++;

		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

 		if(!isset($datas[0]) OR strlen ($data) < 1) {// || !custdate($datas[0])) {
 			continue;
 		}

		if ($linecount < 5)
			continue;

		$date = custdate($datas[0]);

		if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
			return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
		}

		$amount = $datas[2];
		$amount += 0;
		$amount = sprint($amount);

		$description = safe($datas[1]);

		$from = safe($datas[1]);

		$code = "";

		$ex1 = safe($datas[3]);
		$ex2 ="";
		$ex3 = "";

		$Sl = "SELECT * FROM statement_history WHERE date='$date' AND amount='$amount' AND description='$description' AND contra='$from' AND code='$code' AND ex1='$ex1' AND ex2='$ex2' AND ex3='$ex3'";
		$Ri = db_exec($Sl) or errDie("unable to check data.");

		if(pg_num_rows($Ri) < 1) {

			$Sl = "
				INSERT INTO statement_data (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'ABSA', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

			$Sl = "
				INSERT INTO statement_history (
					date, amount, description, contra, code, ex1, ex2, ex3, by, bank, account
				) VALUES (
					'$date', '$amount', '$description', '$from', '$code', '$ex1', '$ex2', '$ex3', '".USER_DIV."', 'ABSA', '$bankid'
				)";
			$Ri = db_exec($Sl) or errDie("error importing statement.");

		}
	}

	fclose($file);
	return enter_actions($HTTP_POST_VARS);

}


function enter_actions($HTTP_POST_VARS,$err="")
{

	extract($HTTP_POST_VARS);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	$i = 0;

	db_connect();

	$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
	$Ri= db_exec($Sl) or errDie("Unable to get accounts.");
	$numrows = pg_num_rows($Ri	);

	if(($numrows) < 1){
		return "<li class='err'> There are no bank accounts.";
	}

	$accounts = "<select name='bankid' onChange='javascript:document.form.submit()'>";
	while($acc = pg_fetch_array($Ri)){
		if(isset($bankid) && $acc['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$accounts .= "<option $sel value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
		if(!isset($bid)) {
			$bid = $acc['bankid'];
		}
	}
	$accounts .= "</select>";

	if(!isset($bankid)) {
		$bankid = $bid;
	}

	$bankid += 0;

	$Sl = "SELECT * FROM statement_data WHERE account='$bankid' ORDER BY date";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return "
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='enter_actions'>
				<br><br>
				<h4>Bank Account:</h4>$accounts
			</form>
			<li class='err'>There are no unallocated transactions for this account</li>";
	}

	$out = "
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm_actions'>
			<h3>Please select the action you want to take.</h3>
			$err
			<h4>Bank Account:</h4>$accounts
			<br><br>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Amount</th>
				<th>Description</th>
				<th>From/To</th>
				<th>Action</th>
			</tr>";

	while($data = pg_fetch_array($Ri)) {

		extract($data);

		if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
			return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
		}

		$s1 = "";
		$s2 = "";
		$s3 = "";
		$s4 = "";
		$sel = "";

		if(isset($actions[$id])) {
			if($actions[$id] == "c") {
				$s1 = "selected";
			} elseif($actions[$id] == "cr") {
				$s2 = "selected";
			} elseif($actions[$id] == "d") {
				$s3 = "selected";
			} elseif($actions[$id] == "i") {
				$s4 = "selected";
			} elseif($actions[$id] == "cp" || $actions[$id] == "sp") {
				$sel = "selected";
			}
		}

		if($amount > 0) {
			$trantype = "deposit";

 			$Sl = "SELECT * FROM customers WHERE div = '".USER_DIV."' AND ((position(lower(surname) IN lower('$description'))>0)OR(position(lower(surname) IN lower('$contra'))>0))";
			$Rl = db_exec($Sl) or errDie("unable to get customer list.");

			if(pg_num_rows($Rl) > 0) {
				$sel = "selected";
			}

			$test = isrefcp($description,$contra);

			if($test > 0) {
				$sel = "selected";
			}

			$exp = "<option value='cp' $sel>Payment from customer</option>";
		} else {
			$trantype = "withdrawal";

			$Sl = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND ((position(lower(supname) IN lower('$description'))>0)OR(position(lower(supname) IN lower('$contra'))>0))";
			$Rl = db_exec($Sl) or errDie("unable to get supplier list.");

			if(pg_num_rows($Rl) > 0) {
				$sel = "selected";
			}

			$test = isrefsp($description,$contra);

			if($test > 0) {
				$sel = "selected";
			}

			$exp = "<option value='sp' $sel>Payment to supplier</option>";
		}

		$pamount = abs($amount);

		$cheqnum = $contra;
		$cheqnum += 0;

		$Sl = "SELECT * FROM cubit.cashbook WHERE trantype='$trantype' AND amount='$pamount' AND banked='no' AND bankid = '$bankid' AND rid!=333;";
		$Rl = db_exec($Sl) or errDie("Unable to get cashbook data.");

		$n = pg_num_rows($Rl);

		if($n > 0) {
			$ropt = "<option value='r'>Reconcile cashbook ($n found)</option>";
			$ex = "<td><a href='#' onClick=\"popupSized('entries.php?trantype=$trantype&amount=$pamount','Cashbook Entries',700,400,'');\">View Entries</a></td>";
		} else {
			$ropt = "";
			$ex = "";
		}

		$Actions = "
			<select name='actions[$id]'>
				<option value='0' selected>Please Select An Option</option>
				$ropt
				<option value='c' $s1>Insert into cashbook</option>
				<option value='cr' $s2>Insert into cashbook(Reconciled)</option>
				$exp
				<option value='d' $s3>Delete</option>
				<option value='i' $s4>Ignore</option>
			</select>";

		if($amount < 0) {
			$amountd = "<li class='err'>$amount</li>";
		} else {
			$amountd = "<li>$amount</li>";
		}

		$out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$date</td>
				<td>$amountd</td>
				<td>$description</td>
				<td>$contra</td>
				<td>$Actions</td>
				$ex
			</tr>";

		$i++;

	}

	$out .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='5' align='right'><input type='submit' name='next' value='Enter Details &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}



function confirm_actions($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	if(!isset($bankid)) {
		return enter_actions($HTTP_POST_VARS);
	}

	if(!isset($next)) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bankid += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM bankacct WHERE bankid='$bankid'";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bd = pg_fetch_array($Ri);

	/*
	db_conn('core');

	$Sl="SELECT accid FROM accounts WHERE accname='Bank Charges' AND div = '".USER_DIV."' AND acctype='E'";
	$Rl=db_exec($Sl) or errDie("Unable to get account data.");
	if(pg_numrows($Rl) > 0){
		$ad=pg_fetch_array($Rl);
		$bc=$ad['accid'];
	} else {
		$bc=0;
	}

	$Sl="SELECT accid FROM accounts WHERE accname='Interest Paid' AND div = '".USER_DIV."' AND acctype='E'";
	$Rl=db_exec($Sl) or errDie("Unable to get account data.");
	if(pg_numrows($Rl) > 0){
		$ad=pg_fetch_array($Rl);
		$ip=$ad['accid'];
	} else {
		$ip=0;
	}

	$Sl="SELECT accid FROM accounts WHERE accname='Travel Expenses' AND div = '".USER_DIV."' AND acctype='E'";
	$Rl=db_exec($Sl) or errDie("Unable to get account data.");
	if(pg_numrows($Rl) > 0){
		$ad=pg_fetch_array($Rl);
		$te=$ad['accid'];
	} else {
		$te=0;
	}
	*/

	$i = 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_settings";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$sd = pg_fetch_array($Ri);

	if($sd['ad'] == "num") {
		$num = true;
		$ord = "ORDER BY topacc,accnum";
	} else {
		$num = false;
		$ord = "ORDER BY accname";
	}

	$Sl = "SELECT * FROM statement_data WHERE account='$bankid' ORDER BY date";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	$out = "
		<h3>Please select the action you want to take.($bd[bankname] - $bd[accname])</h3>
		<li class='err'>Please note: Payments are in red.</li>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm_actions2'>
			<input type='hidden' name='bankid' value='$bankid'>
			<tr>
				<th>Date</th>
				<th>Amount</th>
				<th>Description</th>
				<th>From/To</th>
				<th>VAT Inc</th>
				<th>Vat Code</th>
				<th>Contra Account/Action</th>
				<th>Cheque</th>
			</tr>";

	while($data = pg_fetch_array($Ri)) {

		$vatcode_drop = "<input type='hidden' name='vatcode[]' value=''>";

		extract($data);

		$vch = "";

		if($amount > 0) {
			$trantype = "deposit";
		} else {
			$trantype = "withdrawal";
		}

		$pamount = sprint(abs($amount));

		$cheqnum = $contra;
		$cheqnum += 0;

		$action = $actions[$id];

		if($action == "0")
			return enter_actions ($HTTP_POST_VARS,"<li class='err'>Please Select An Option For All Accounts</li>");

		if($action == "c" || $action == "cr") {

			if(($bank == "Standard Bank") && (($ex2 == "##") || ($description == "OVERDRAFT LEDGER FEE"))) {
				$vch = "checked";
			}

			db_conn('core');

			$Accounts = "
				<select name='accounts[$id]'>
					<option value='0'>Select Account</option>";
			$Sl = "SELECT * FROM accounts WHERE div = '".USER_DIV."' $ord";
			$Rl = db_exec($Sl) or errDie("Unable to get account data.");
			if(pg_numrows($Rl) < 1){
				return "<li>There are No accounts in Cubit.</li>";
			}

			$hook = isrefa($description,$contra);

			if($hook == 0) {
				$hook = isaref($pamount,$description,$contra,$trantype);
			}

			if(isset($accounts[$id])) {
				$hook = $accounts[$id];
			}

			while($acc = pg_fetch_array($Rl)){

				if(isDisabled($acc['accid'])) {
					continue;
				}

				if($acc['accid'] == $hook) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				if($num) {
					$acc['accname'] = $acc['topacc']."/".$acc['accnum'];
				}

				$Accounts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
			}
			$Accounts .= "</select>";

			$details = $Accounts;


			#make vat code drop
			$vatcode_drop = "<select name='vatcode[$id]'>";
			$get_vats = "SELECT * FROM vatcodes ORDER BY code";
			$run_vats = db_exec($get_vats) or errDie("Unable to get vat code information.");
			if(pg_numrows($run_vats) < 1){
				$vatcode_drop .= "<option value='01'>Select Vatcode</option>";
			}else {
				while ($varr = pg_fetch_array($run_vats)){
					$vatcode_drop .= "<option value='$varr[id]'>$varr[code]</option>";
				}
			}
			$vatcode_drop .= "</select>";

		} elseif($action == "r") {

			db_conn('cubit');

			$Sl = "SELECT * FROM cashbook WHERE trantype='$trantype' AND amount='$pamount' AND banked='no' AND bankid='$bankid' AND rid!=333";
			$Rl = db_exec($Sl) or errDie("Unable to get cashbook data.");

			if(pg_num_rows($Rl) > 0) {

				$car = "<select name='recon[$id]'>";

				if(pg_num_rows($Rl) > 1) {
					$car .= "<option value='0'>Select Entry</option>";
				}

				while($cd = pg_fetch_array($Rl)) {
					$car .= "<option value='$cd[cashid]'>Entry on $cd[date], $cd[descript]</option>";

				}
				$car .= "</select>";

				$details = "$car";
			} else {
				$details = "";
			}
		} elseif($action == "d") {
			$details = "Delete";
		} elseif($action == "i") {
			$details = "Ignore";
		} elseif($action == "cp") {

			db_conn('cubit');

			$Sl = "SELECT cusnum,surname FROM customers WHERE div='".USER_DIV."' AND location='loc' ORDER BY surname";
			$Rl = db_exec($Sl) or errDie("Unable to get customers.");

			$details = "
				<select name='customers[$id]'>
					<option value='0'>Select Customer</option>";

			$clientid = isrefcp($description,$contra);

			while($cd = pg_fetch_array($Rl)) {

				$sel = "";

				if($clientid == 0) {

					$find = strpos(strtolower($description),strtolower($cd['surname']));

					if(!($find === false)) {
						$sel = "selected";
					}

					$find = strpos(strtolower($contra),strtolower($cd['surname']));

					if(!($find === false)) {
						$sel = "selected";
					}

				} else{
					if($cd['cusnum'] == $clientid) {
						$sel = "selected";
					}
				}

				if(isset($customers[$id]) && $customers[$id] == $cd['cusnum']) {
					$sel = "selected";
				}

				$details .= "<option value='$cd[cusnum]' $sel>$cd[surname]</option>";
			}
			$details .= "</select>";


		} elseif($action == "sp") {

			db_conn('cubit');

			$Sl = "SELECT supid,supname FROM suppliers WHERE div='".USER_DIV."' AND location='loc'  ORDER BY supname";
			$Rl = db_exec($Sl) or errDie("Unable to get customers.");

			$details = "
				<select name='suppliers[$id]'>
					<option value='0'>Select Supplier</option>";

			$supid = isrefsp($description,$contra);

			while($cd = pg_fetch_array($Rl)) {

				$sel = "";

				if($supid == 0) {

					$find = strpos(strtolower($description),strtolower($cd['supname']));

					if(!($find === false)) {
						$sel = "selected";
					}

					$find = strpos(strtolower($contra),strtolower($cd['supname']));

					if(!($find === false)) {
						$sel = "selected";
					}
				} else {
					if($cd['supid'] == $supid) {
						$sel = "selected";
					}
				}

				if(isset($suppliers[$id]) && $suppliers[$id] == $cd['supid']) {
					$sel = "selected";
				}

				$details .= "<option value='$cd[supid]' $sel>$cd[supname]</option>";
			}
			$details .= "</select>";
		}

		if(!isset($day[$id])) {
			$dates = explode("-",$date);
		} else {
			$dates[2] = $day[$id];
			$dates[1] = $mon[$id];
			$dates[0] = $year[$id];
		}

		if($amount < 0) {
			$e1 = "<li class='err'></li>";
		} else {
			$e1 = "<li></li>";
		}

		if(isset($descriptions[$id])) {
			$description = $descriptions[$id];
			if(isset($vats[$id]) && $vats[$id] != "No") {
				$vch = "checked";
			} else {
				$vch = "";
			}
		}

		if(isset($contras[$id])) {
			$contra = $contras[$id];
		}

		if($action == "d" || $action == "i") {

			$out.= "
				<tr bgcolor='".bgcolorg()."'>
					<td>
						<table border='0' cellpadding='0' cellspacing='0'>
							<tr>
								<input type='hidden' name='actions[$id]' value='$action'>
								<td>$dates[2]</td>
								<td>-</td>
								<td>$dates[1]</td>
								<td>-</td>
								<td>$dates[0]</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td>$e1</td>
								<td>$pamount</td>
							</tr>
						</table>
					</td>
					<td>$description</td>
					<td>$contra</td>
					<td></td>
					<td>$details</td>
					<td></td>
				</tr>";
		} else {

			$out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>
						<table border='0' cellpadding='0' cellspacing='0'>
							<tr>
								<input type='hidden' name='actions[$id]' value='$action'>
								<td><input type='text' size='2' name='day[$id]' value='$dates[2]'></td>
								<td>-</td>
								<td><input type='text' size='2' name='mon[$id]' value='$dates[1]'></td>
								<td>-</td>
								<td><input type='text' size='4' name='year[$id]' value='$dates[0]'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td>$e1</td>
								<td><input type='hidden' size='8' name='amounts[$id]' value='$pamount'>$pamount</td>
							</tr>
						</table>
					</td>
					<td><input type='text' size='30' name='descriptions[$id]' value='$description'></td>
					<td><input type='text' size='30' name='contras[$id]' value='$contra'></td>
					<td><input type='checkbox' name='vats[$id]' $vch></td>
					<td>$vatcode_drop</td>
					<td>$details</td>
					<td><input type='text' size='5' name='cheque[$id]' value=''></td>
				</tr>";
		}
		$i++;

	}

	$out.= "
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td colspan='4' align='right'><input type='submit' name='next' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}



function confirm_actions2($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	if(!isset($bankid)) {
		return enter_actions($HTTP_POST_VARS);
	}

	if(!isset($next)) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bankid += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM bankacct WHERE bankid='$bankid'";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bd = pg_fetch_array($Ri);

	$i = 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_data WHERE account='$bankid' ORDER BY date";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	$out = "
		<h3>Please Confirm the actions you want to take.($bd[bankname] - $bd[accname])</h3>
		<li class='err'>Please note: Payments are in red.</li>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write_actions'>
			<input type='hidden' name='bankid' value='$bankid'>
			<tr>
				<th>Date</th>
				<th>Amount</th>
				<th>Description</th>
				<th>From/To</th>
				<th>VAT Inc</th>
				<th>Vat Code</th>
				<th>Contra Account/Action</th>
				<th>Cheque</th>
			</tr>";

	while($data = pg_fetch_array($Ri)) {
		
		$showvat = "";

		extract($data);

		if($amount > 0) {
			$trantype = "deposit";
		} else {
			$trantype = "withdrawal";
		}

		$pamount = abs($amount);

		$cheqnum = $contra;
		$cheqnum += 0;

		$action = $actions[$id];

		if($action == "c" || $action == "cr") {

			db_conn('core');

			$Sl = "SELECT * FROM accounts WHERE accid='$accounts[$id]'";
			$Rl = db_exec($Sl) or errDie("Unable to get account data.");
			if(pg_numrows($Rl) < 1){
				return "<li class='err'>Please select all the contra accounts</li>".confirm_actions($HTTP_POST_VARS);
			}

			$data = pg_fetch_array($Rl);

			$details = $data['accname'];
			
			db_connect ();

			$get_vats = "SELECT * FROM vatcodes WHERE id = '$vatcode[$id]' LIMIT 1";
			$run_vats = db_exec($get_vats) or errDie("Unable to get vat code information.");
			if(pg_numrows($run_vats) < 1){
				$showvat = "";
			}else {
				$varr = pg_fetch_array($run_vats);
				$showvat = $varr['code'];
			}

		} elseif($action == "r") {

			db_conn('cubit');

			$recon[$id] += 0;

			$Sl = "SELECT * FROM cashbook WHERE cashid='$recon[$id]'";
			$Rl = db_exec($Sl) or errDie("Unable to get cashbook data.");

			if(pg_num_rows($Rl) == 1) {

				$cd = pg_fetch_array($Rl);

				$details = "
					Reconcile cashbook entry on $cd[date], $cd[descript]
					<input type='hidden' name='recon[$id]' value='$recon[$id]'>";

			} else {
				$details = "<li class='err'>Not Found</li>";
			}
		} elseif($action == "d") {
			$details = "Delete";
		} elseif($action == "i") {
			$details = "Ignore";
		} elseif($action == "cp") {

			if(isset($vats[$id])) {
				return "<li class='err'>You cannot select vat for a client payment.</li>".confirm_actions($HTTP_POST_VARS);
			}

			db_conn('cubit');

			$cid = $customers[$id];

			$cid += 0;

			$Sl = "SELECT * FROM customers WHERE cusnum='$cid'";
			$Rl = db_exec($Sl) or errDie("Unable to get customer info.");

			if(pg_numrows($Rl) < 1){
				return "<li class='err'>Please select all the contra accounts(Client Accounts)</li>".confirm_actions($HTTP_POST_VARS);
			}

			$cd = pg_fetch_array($Rl);

			$details = $cd['surname']."<input type='hidden' name='customers[$id]' value='$cid'>";

		} elseif($action == "sp") {

			if(isset($vats[$id])) {
				return "<li class='err'>You cannot select vat for a supplier payment.</li>".confirm_actions($HTTP_POST_VARS);
			}

			db_conn('cubit');

			$sid = $suppliers[$id];

			$sid += 0;

			$Sl = "SELECT * FROM suppliers WHERE supid='$sid'";
			$Rl = db_exec($Sl) or errDie("Unable to get customer info.");

			if(pg_numrows($Rl) < 1){
				return "<li class='err'>Please select all the contra accounts(Supplier Accounts)</lI>".confirm_actions($HTTP_POST_VARS);
			}

			$cd = pg_fetch_array($Rl);

			$details = $cd['supname']."<input type='hidden' name='suppliers[$id]' value='$sid'>";
		}

		$dates = explode("-",$date);

		if($amount < 0) {
			$e1 = "<li class='err'></li>";
		} else {
			$e1 = "";
		}

		if(!isset($accounts[$id])) {
			$accounts[$id] = 0;
		}

		if(isset($vats[$id])) {
			$vch = "Yes<input type='hidden' name='vats[$id]' value='Yes'>";
		} else {
			$vch = "No<input type='hidden' name='vats[$id]' value='No'>";
		}

		if(isset($mon[$id])) {
			$testd = "$year[$id]-$mon[$id]-$day[$id]";

			if(!(gd($testd))) {
				return "<li class='err'>Invalid date $testd.</li>".confirm_actions($HTTP_POST_VARS);
			}
		}

		if($action == "d" || $action == "i") {
			$pamount = sprint ($pamount);
			$out.= "
				<tr bgcolor='".bgcolorg()."'>
					<td>
						<table border=0 cellpadding=0 cellspacing=0>
							<tr>
								<input type='hidden' name='actions[$id]' value='$action'>
								<td>$dates[2]</td>
								<td>-</td>
								<td>$dates[1]</td>
								<td>-</td>
								<td>$dates[0]</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td>$e1</td>
								<td>$pamount</td>
							</tr>
						</table>
					</td>
					<td>$description</td>
					<td>$contra</td>
					<td><input type='hidden' name='vats[$id]' value='No'></td>
					<td>$showvat <input type='hidden' name='vatcode[$id]' value='$vatcode[$id]'></td>
					<td>$details</td>
					<td></td>
				</tr>";

		} else {
			$amounts[$id] = sprint ($amounts[$id]);
			$out.= "
				<tr bgcolor='".bgcolorg()."'>
					<td>
						<table border='0' cellpadding='0' cellspacing='0'>
							<tr>
								<input type='hidden' name='actions[$id]' value='$action'>
								<input type='hidden' name='accounts[$id]' value='$accounts[$id]'>
								<td><input type='hidden' name='day[$id]' value='$day[$id]'>$day[$id]</td>
								<td>-</td>
								<td><input type='hidden' name='mon[$id]' value='$mon[$id]'>$mon[$id]</td>
								<td>-</td>
								<td><input type='hidden' name='year[$id]' value='$year[$id]'>$year[$id]</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td>$e1</td>
								<td><input type='hidden' name='amounts[$id]' value='$amounts[$id]'>$amounts[$id]</td>
							</tr>
						</table>
					</td>
					<td><input type='hidden' name='descriptions[$id]' value='$descriptions[$id]'>$descriptions[$id]</td>
					<td><input type='hidden' name='contras[$id]' value='$contras[$id]'>$contras[$id]</td>
					<td>$vch</td>
					<td>$showvat <input type='hidden' name='vatcode[$id]' value='$vatcode[$id]'></td>
					<td>$details</td>
					<td><input type='hidden' name='cheque[$id]' value='$cheque[$id]'>$cheque[$id]</td>
				</tr>";
		}

		$i++;

	}

	$out.= "
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td colspan='4' align='right'><input type='submit' name='next' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}




function write_actions($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		$HTTP_POST_VARS["next"] = "";
		return confirm_actions($HTTP_POST_VARS);
	}

	if(!isset($bankid)) {
		return enter_actions($HTTP_POST_VARS);
	}

	if(!isset($next)) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bankid += 0;

	$vatr = TAX_VAT;
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	db_conn('cubit');

	$Sl = "SELECT * FROM bankacct WHERE bankid='$bankid'";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	if(pg_num_rows($Ri) < 1) {
		return enter_actions($HTTP_POST_VARS);
	}

	$bd = pg_fetch_array($Ri);

	$i = 0;

	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) < 1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$banklnk = pg_fetch_array($rslt);



	db_conn('cubit');

	$Sl = "SELECT * FROM statement_data WHERE account='$bankid' ORDER BY date";
	$Ri = db_exec($Sl) or errDie("unable to get data.");

	$out = "
		<h3>Please Confirm the actions you want to take.($bd[bankname] - $bd[accname])</h3>
		<li class='err'>Please note: Payments are in red.</li>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm_actions2'>
			<tr>
				<th>Date</th>
				<th>Amount</th>
				<th>Description</th>
				<th>From/To</th>
				<th>Cheque</th>
				<th>Contra Account/Action</th>
			</tr>";

	while($data = pg_fetch_array($Ri)) {

		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$refnum = getrefnum();

		extract($data);

		if(isset($contras[$id])) {
			$contras[$id] = safe($contras[$id]);
		}
		if(isset($descriptions[$id])) {
			$descriptions[$id] = safe($descriptions[$id]);
		}
		if(isset($accounts[$id])) {
			$accounts[$id] += 0;
		}

		if($amount > 0) {
			$trantype = "deposit";
		} else {
			$trantype = "withdrawal";
		}

		$pamount = abs($amount);

		$cheqnum = $contra;
		$cheqnum += 0;

		$action = $actions[$id];

		if(isset($year[$id])) {
			$date = "$year[$id]-$mon[$id]-$day[$id]";
		} else {
			$date = date("Y-m-d");
		}

		if(!gd($date)) {
			return "<li class='err'>Invalid date $testd.</li>".confirm_actions($HTTP_POST_VARS);
		}

		if($action == "c" || $action == "cr") {

			db_conn('core');

			$Sl = "SELECT * FROM accounts WHERE accid='$accounts[$id]'";
			$Rl = db_exec($Sl) or errDie("Unable to get account data.");
			if(pg_numrows($Rl) < 1){
				return "<li>Account not found.</li>";
			}

			$data = pg_fetch_array($Rl);

			$details = $data['accname'];

			if($action == "cr") {
				$rid = 333;
			} else {
				$rid = 0;
			}

			$cheque[$id] += 0;

			db_connect();
			$vat = 0;
			$chrgvat = "no";
			$Sl = "
				INSERT INTO cashbook (
					bankid, trantype, date, name, descript, cheqnum, 
					amount, vat, chrgvat, banked, accinv, div,rid
				) VALUES (
					'$bankid', '$trantype', '$date', '$contras[$id]', '$descriptions[$id]', '$cheque[$id]', 
					'$amounts[$id]', '$vat', '$chrgvat', 'no', '$accounts[$id]', '".USER_DIV."','$rid'
				)";
			$Rl = db_exec ($Sl) or errDie ("Unable to add bank payment to database.",SELF);

			if($trantype == "deposit") {

				db_conn('core');

				$Sl = "SELECT * FROM bankacc WHERE accnum='$accounts[$id]'";
				$Rg = db_exec($Sl) or errDie("Unable to get accnum");

				if(pg_num_rows($Rg) > 0) {
					$bd = pg_fetch_array($Rg);

					db_conn('cubit');

					//$Sl="SELECT * FROM bankacct WHERE

					$sql = "
						INSERT INTO cashbook (
							bankid, trantype, date, name, descript, cheqnum, 
							amount, vat, chrgvat, banked, accinv, div
						) VALUES (
							'$bd[accid]', 'withdrawal', '$date', '$descriptions[$id]', '$descriptions[$id]', '0', 
							'$amounts[$id]', '0', '', 'no', '$banklnk[accnum]', '".USER_DIV."'
						)";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
				}

				if($vats[$id] == "No") {
					writetrans($banklnk['accnum'], $accounts[$id], $date, $refnum, $amounts[$id], $descriptions[$id]);
				} else {

					$vat = sprint($amounts[$id] * $vatr / (100 + $vatr));
					$vatex = sprint($amounts[$id] - $vat);

					writetrans($banklnk['accnum'], $accounts[$id], $date, $refnum, $vatex, $descriptions[$id]);
					writetrans($banklnk['accnum'], $vatacc, $date, $refnum, $vat, "VAT ".$descriptions[$id]);

					db_connect ();

					$get_vats = "SELECT * FROM vatcodes WHERE id = '$vatcode[$id]' LIMIT 1";
					$run_vats = db_exec($get_vats) or errDie("Unable to get vat code information.");
					if(pg_numrows($run_vats) > 0){
						$vd = pg_fetch_array($run_vats);
						vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descriptions[$id],$amounts[$id],$vat);
					}

				}
			} else {

				db_conn('core');

				$Sl = "SELECT * FROM bankacc WHERE accnum='$accounts[$id]'";
				$Rg = db_exec($Sl) or errDie("Unable to get accnum");

				if(pg_num_rows($Rg) > 0) {
					$bd = pg_fetch_array($Rg);

					db_conn('cubit');

					//$Sl="SELECT * FROM bankacct WHERE

					$sql = "
						INSERT INTO cashbook (
							bankid, trantype, date, name, descript, cheqnum, 
							amount, vat, chrgvat, banked, accinv, div
						) VALUES (
							'$bd[accid]', 'deposit', '$date', '$descriptions[$id]', '$descriptions[$id]', '0', 
							'$amounts[$id]', '0', '', 'no', '$banklnk[accnum]', '".USER_DIV."'
						)";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
				}

				if($vats[$id] == "No") {
					writetrans($accounts[$id], $banklnk['accnum'], $date, $refnum, $amounts[$id], $descriptions[$id]);
				} else {

					$vat = sprint($amounts[$id]*$vatr/(100+$vatr));
					$vatex = sprint($amounts[$id]-$vat);

					writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, "VAT ".$descriptions[$id]);
					writetrans($accounts[$id], $banklnk['accnum'], $date, $refnum, $vatex, $descriptions[$id]);

					db_connect ();
	
					$get_vats = "SELECT * FROM vatcodes WHERE id = '$vatcode[$id]' LIMIT 1";
					$run_vats = db_exec($get_vats) or errDie("Unable to get vat code information.");
					if(pg_numrows($run_vats) > 0){
						$vd = pg_fetch_array($run_vats);
						vatr($vd['id'],$date,"INPUT",$vd['code'],$refnum,$descriptions[$id],-$amounts[$id],-$vat);
					}

				}
			}

			db_conn('cubit');

			$Sl = "DELETE FROM statement_arefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			$Sl = "SELECT * FROM statement_irefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			if(pg_num_rows($Rl) == 0) {
				$Sl = "INSERT INTO statement_arefs (amount, des1, des2, pn, action, account, by) VALUES ('$pamount','$description','$contra','$trantype','c','$accounts[$id]','".USER_NAME."')";
				$Rl = db_exec($Sl) or errDie("Unable to insert statement data.");
			}

			$Sl = "DELETE FROM statement_data WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to remove data.");

		} elseif($action == "r") {

			db_conn('cubit');

			$Sl = "SELECT * FROM cashbook WHERE cashid='$recon[$id]'";
			$Rl = db_exec($Sl) or errDie("Unable to get cashbook data.");

			if(pg_num_rows($Rl) == 1) {

				$cd = pg_fetch_array($Rl);

				$Sl = "UPDATE cashbook SET rid='333' WHERE cashid='$cd[cashid]'";
				$Rl = db_exec($Sl) or errDie("Unable to update cashbook.");

				$Sl = "DELETE FROM statement_data WHERE id='$id'";
				$Rl = db_exec($Sl) or errDie("Unable to remove data.");

			} else {
				$details = "";
			}
		} elseif($action == "d") {

			db_conn('cubit');

			$Sl = "DELETE FROM statement_data WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to remove data.");
		} elseif($action == "cp") {

			cp2($customers[$id],abs($amounts[$id]),$descriptions[$id],$contras[$id],$refnum,$date,$cheque[$id],$bankid);

			db_conn('cubit');

			$Sl = "DELETE FROM statement_arefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			$Sl = "SELECT * FROM statement_irefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			if(pg_num_rows($RI) == 0) {
				$Sl = "INSERT INTO statement_arefs (amount,des1,des2,pn,action,account,by) VALUES ('$pamount','$description','$contra','$trantype','cp','$customers[$id]','".USER_NAME."')";
				$Rl = db_exec($Sl) or errDie("Unable to insert statement data.");
			}

			$Sl = "DELETE FROM statement_data WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to remove data.");

		} elseif($action == "sp") {

			sp($suppliers[$id],abs($amounts[$id]),$descriptions[$id],$contras[$id],$refnum,$date,$cheque[$id],$bankid);

			db_conn('cubit');

			$Sl = "DELETE FROM statement_arefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			$Sl = "SELECT * FROM statement_irefs WHERE des1='$description' AND des2='$contra' AND pn='$trantype' AND amount='$pamount'";
			$RI = db_exec($Sl) or errDie("Unable to get data.");

			if(pg_num_rows($Rl) == 0) {
				$Sl = "INSERT INTO statement_arefs (amount,des1,des2,pn,action,account,by) VALUES ('$pamount','$description','$contra','$trantype','sp','$suppliers[$id]','".USER_NAME."')";
				$Rl = db_exec($Sl) or errDie("Unable to insert statement data.");
			}


			$Sl = "DELETE FROM statement_data WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to remove data.");
		}

		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

		$i++;

	}

	$out.= "
			<tr><td><br></td></tr>
			<tr></td></tr>
		</form>
		</table>";

	$out = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Done</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Your transactions have been processed</th>
			</tr>
		</table>";
	return $out;

}



function safe($value)
{

	$value = str_replace("!","",$value);
	$value = str_replace("=","",$value);
	//$value = str_replace("#","",$value);
	$value = str_replace("%","",$value);
	$value = str_replace("$","",$value);
	//$value = str_replace("*","",$value);
	$value = str_replace("^","",$value);
	$value = str_replace("?","",$value);
	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace(":","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_replace("$","",$value);
	return $value;

}



function w1vdate($date)
{

	if(checkdate(substr($date,4,2),substr($date,6,2) , substr($date,0,4))) {
		return substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
	} else {
		return false;
	}

}




function w2vdate($date)
{

	$months = array("1","Jan","Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

	$ex = "";

	if(isset($date[1])) {
		$c = $date[1];
	} else {
		$c = 0;
	}
	$c += 0;

	/*if(($c>0)==1) {
		$day=substr($date,0,2);
		$mon=substr($date,2,3);
		$year=substr($date,5,4);

	} else {
		$day=substr($date,0,1);
		$mon=substr($date,1,3);
		$year=substr($date,4,4);
	}*/

	if(strlen($date) == 9) {
		$day = substr($date,0,2);
		$mon = substr($date,2,3);
		$year = substr($date,5,4);
	} else {
		$day = substr($date,0,1);
		$mon = substr($date,1,3);
		$year = substr($date,4,4);
	}

	$mon = array_search($mon,$months);

	//return "$day-$mon-$year";
	$year += 0;
	$mon += 0;
	$day += 0;

	if(checkdate($mon,$day , $year)) {
		return "$year-$mon-$day";
	} else {
		return false;
	}

}




function w3vdate($date)
{

	$dates = explode("/",$date);

	if(!isset($dates[2])) {
		return false;
	}

	$day = $dates[0];
	$mon = $dates[1];
	$year = $dates[2];

	$year += 0;
	$mon += 0;
	$day += 0;

	if(checkdate($mon, $day, $year)) {
		return mkdate($year, $mon, $day);
	} else {
		return false;
	}

}



function gd($date)
{

	$dates = explode("-",$date);

	if(!isset($dates[2])) {
		return false;
	}

	$day = $dates[2];
	$mon = $dates[1];
	$year = $dates[0];

	$year += 0;
	$mon += 0;
	$day += 0;

	if(checkdate($mon,$day , $year)) {
		return true;
	} else {
		return false;
	}

}
	//if(!checkdate(substr($date,4,4),substr($date,4,4) , $oyear)){



// nedbank
function w4vdate($date)
{

	$year = substr($date,0,4);
	$mon = substr($date,4,2);
	$day = substr($date,6,2);
	$year += 0;
	$mon += 0;
	$day += 0;
	if(checkdate($mon,$day,$year)) {
		return substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
	} else {
		return false;
	}

}

function custdate ($date)
{

	$months_arr = array (
		"Jan" => "01",
		"Feb" => "02",
		"Mar" => "03",
		"Apr" => "04",
		"May" => "05",
		"Jun" => "06",
		"Jul" => "07",
		"Aug" => "08",
		"Sept" => "09",
		"Oct" => "10",
		"Nov" => "11",
		"Dec" => "12"
	);

	foreach ($months_arr AS $each => $own){
		$date = str_replace ($each, $own, $date);
	}

	$year = substr($date,4,4);
 	$mon = substr($date,2,2);
	$day = substr($date,0,2);

	$year += 0;
	$mon += 0;
	$day += 0;
	if(checkdate($mon,$day,$year)) {
		//return substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
		return "$year-$mon-$day";
	} else {

		return false;
	}

}

//cp($customers[$id],abs($amounts[$id]),$descriptions[$id],$contras[$id],$refnum,$date,$cheque[$id],$bankid);
function cp ($id,$amount,$description,$contra,$refnum,$date,$cheque,$bankid)
{

	$id += 0;
	$amount += 0;

	$out = $amount;

	db_connect();

	$Sl = "SELECT cusnum,deptid,cusname,surname FROM customers WHERE cusnum = '$id' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");
	$cus = pg_fetch_array($Ri);

	$confirm = "";
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";
	$cheque += 0;

	db_conn('core');

	$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
	$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
	if(pg_numrows($Rx) < 1) {
		return "Invalid bank acc.";
	}
	$link = pg_fetch_array($Rx);

	db_conn("exten");

	$Sl = "SELECT debtacc FROM departments WHERE deptid ='$cus[deptid]' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get department data.");
	$dept = pg_fetch_array($Ri);

	db_connect();

	$Sl = "SELECT invnum,invid,balance,prd FROM invoices WHERE cusnum = '$id' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$Ri = db_exec($Sl) or errDie("Unable to get invoices");
	$i = 0;
	while(($inv = pg_fetch_array($Ri))and($out>0)) {
		$invid = $inv['invid'];

		if($out >= $inv['balance']) {
			$val = $inv['balance'];
			$out = $out - $inv['balance'];
		}else {
			$val = $out;
			$out = 0;
		}
		$i++;
		$val = sprint($val);

		db_conn('cubit');

		$Sl = "UPDATE invoices SET balance = (balance - $val::numeric(13,2)) WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$Rl = db_exec($Sl) or errDie("Unable to update Invoice information in Cubit.",SELF);

		$inv['invnum'] += 0;

		$Sl = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, type, div, allocation_date
			) VALUES (
				'$cus[cusnum]', '$inv[invnum]', '".($val - ($val * 2))."', '$date', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$date'
			)";
		$Rl = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		$sql = "UPDATE  open_stmnt SET balance = (balance - $val::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		custledger($cus['cusnum'], $link['accnum'], $date, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $val, "c");

		$rinvids .= "|$invid";
		$amounts .= "|$val";
		if($inv['prd'] == 0) {
			$inv['prd'] = PRD_DB;
		}
		$invprds .= "|$inv[prd]";
		$rages .= "|0";
		$invidsers .= " - $inv[invnum]";

	}

	db_conn('cubit');

	$Sl = "SELECT invnum, invid, balance, descrip, age, prd FROM nons_invoices WHERE cusid = '$id' AND done = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY sdate ASC";
	$Ri = db_exec($Sl) or errDie("Unable to get nons invoices.");
	while(($inv = pg_fetch_array($Ri))and($out>0)) {

		$invid = $inv['invid'];

		if($out >= $inv['balance']) {
			$val = $inv['balance'];
			$out = $out - $inv['balance'];
		}else {
			$val = $out;
			$out = 0;
		}
		$i++;
		$val = sprint($val);

		db_conn('cubit');

		$Sl = "UPDATE nons_invoices SET balance = (balance - $val::numeric(13,2)) WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$Rl = db_exec($Sl) or errDie("Unable to update Invoice information in Cubit.",SELF);

		$inv['invnum'] += 0;
		$Sl = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, 
				type, div, allocation_date
			) VALUES (
				'$cus[cusnum]', '$inv[invnum]', '".($val - ($val * 2))."', '$date', 
				'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."', '$date'
			)";
		$Rl = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		$sql = "UPDATE  open_stmnt SET balance = (balance - $val::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
		$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		custledger($cus['cusnum'], $link['accnum'], $date, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $val, "c");

		db_connect();

		recordCT($val, $cus['cusnum'],$date);

		$rinvids .= "|$invid";
		$amounts .= "|$val";
		$invprds .= "|0";
		$rages .= "|$inv[age]";
		$invidsers .= " - $inv[invnum]";

	}
	$out = sprint($out);

	db_conn('cubit');

	$Sl = "
		INSERT INTO cashbook (
			bankid, trantype, date, name, 
			descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div
		) VALUES (
			'$bankid', 'deposit', '$date', '$cus[cusname] $cus[surname]', 
			'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheque', '$amount', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."'
		)";
	$Ri = db_exec($Sl) or errDie ("Unable to add bank payment to database.",SELF);

	writetrans($link['accnum'],$dept['debtacc'], $date, $refnum, $amount, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");
	db_conn('cubit');

	if($out > 0) {
		recordCT($out, $cus['cusnum'],$date);
		$Sl = "
			INSERT INTO stmnt (
				cusnum, invid, amount, date, type, div, allocation_date
			) VALUES (
				'$cus[cusnum]', '0', '".($out*(-1))."', '$date', 'Payment Received.', '".USER_DIV."', '$date'
			)";
		$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

		$sql = "
			INSERT INTO open_stmnt (
				cusnum, invid, amount, balance, date, type, st, div
			) VALUES (
				'$cus[cusnum]', '0', '-$out', '-$out', '$date', 'Payment Received', 'n', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

		custledger($cus['cusnum'],$link['accnum'], $date, "PAYMENT", "Payment received.", $out, "c");

	}

	db_conn('cubit');

	$Sl = "UPDATE customers SET balance=(balance-'$amount'::numeric(13,2)) WHERE cusnum='$cus[cusnum]' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to update invoice in Cubit.",SELF);

}



function sp ($id,$amount,$description,$contra,$refnum,$date,$cheque,$bankid)
{

	$id += 0;
	$amount += 0;
	$cheque += 0;

	db_connect();

	$Sl = "SELECT supid,supno,supname,deptid FROM suppliers WHERE supid = '$id' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get supplier data.");
	$sup = pg_fetch_array($Ri);

	core_connect();

	$Sl = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to retrieve bank account link from Cubit",SELF);

	 if(pg_numrows($Ri) < 1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}

	$bank = pg_fetch_array($Ri);

	db_conn("exten");

	$Sl = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unable to get department data.");
	$dept = pg_fetch_array($Ri);

	$out = $amount;
	$confirm = "";
	$ids = "";
	$purids = "";
	$pamounts = "";
	$pdates = "";

	db_connect();

	$Sl = "SELECT id,purid AS invid,intpurid AS invid2,balance,pdate FROM suppurch WHERE supid='$id' AND balance > 0 AND div='".USER_DIV."' ORDER BY pdate ASC";
	$Ri = db_exec($Sl) or errDie("unable to get invoices.");
	$i = 0;
	while(($inv = pg_fetch_array($Ri)) AND ($out > 0)) {
		if($inv['invid2'] > 0) {$inv['invid'] = $inv['invid2'];}

		$invid = $inv['invid'];
		if($out >= $inv['balance']) {
			$val = $inv['balance'];
			$out = $out - $inv['balance'];
		}else {
			$val = $out;
			$out = 0;
		}

		$Sl = "UPDATE suppurch SET balance = (balance - '$val'::numeric(13,2)) WHERE id='$inv[id]'";
		$Rl = db_exec($Sl) or errDie("Unable to update Invoice information in Cubit.",SELF);

		$ids .= "|$inv[id]";
		$purids .= "|$invid";
		$pamounts .= "|$val";
		$pdates .= "|$inv[pdate]";
	}

	$samount = ($amount - ($amount * 2));

	if($out > 0) {recordDT($out, $sup['supid'],$date);}

	$Sl = "INSERT INTO sup_stmnt (supid, amount, edate, descript,ref,cacc, div) VALUES ('$sup[supid]','$samount','$date', 'Payment','$cheque','$bank[accnum]', '".USER_DIV."')";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	db_connect();

	$Sl = "UPDATE suppliers SET balance = (balance - '$amount'::numeric(13,2)) WHERE supid = '$sup[supid]'";
	$Ri = db_exec($Sl) or errDie("Unable to update invoice in Cubit.",SELF);

	suppledger($sup['supid'], $bank['accnum'], $date, $cheque, "Payment for purchases", $amount, "d");

	db_conn('cubit');

	$Sl = "
		INSERT INTO cashbook (
			bankid, trantype, date, name, descript, 
			cheqnum, amount, banked, accinv, supid, ids, 
			purids, pamounts, pdates, div
		) VALUES (
			'$bankid', 'withdrawal', '$date', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', 
			'$cheque', '$amount', 'no', '$dept[credacc]', '$sup[supid]', '$ids', 
			'$purids', '$pamounts', '$pdates', '".USER_DIV."'
		)";
	$Ri = db_exec($Sl) or errDie ("Unable to add bank payment to database.",SELF);

	db_conn('core');

	$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
	$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
	if(pg_numrows($Rx)<1) {
		return "Invalid bank acc.";
	}
	$link = pg_fetch_array($Rx);

	writetrans($dept['credacc'],$bank['accnum'], $date, $refnum, $amount, "Supplier Payment to $sup[supname]");

}



function cp2($id,$amount,$description,$contra,$refnum,$date,$cheque=0,$bankid)
{

	$cheque += 0;
	$sdate = date("Y-m-d");
	$accdate = $date;

	if ($accdate == 0){
		$accdate = date("Y-m-d");
	}

	$cus = qryCustomer($id, "cusnum, deptid, cusname, surname");
	$dept = qryDepartment($cus["deptid"], "debtacc");

// 	db_connect();
// 
// 	$Sl = "SELECT cusnum,deptid,cusname,surname FROM customers WHERE cusnum = '$id' AND div = '".USER_DIV."'";
// 	$Ri = db_exec($Sl) or errDie("Unable to get data.");
// 	$cus = pg_fetch_array($Ri);

	db_conn('core');

	$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
	$Rx = db_exec($Sl) or errDie("Uanble to get bank acc.");
	if(pg_numrows($Rx) < 1) {
		return "Invalid bank acc.";
	}
	$link = pg_fetch_array($Rx);

#######################################################################################################
########################################### COMPILE ###################################################
#######################################################################################################


	$out = $amount;
	$invs_arr = array();

	// Connect to database
	db_connect();

	#####################[ GET OUTSTANDING INVOICES ]######################
	$sql = "
		SELECT invnum, invid, balance, terms, odate 
		FROM invoices 
		WHERE cusnum = '$id' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$prnInvRslt = db_exec($sql);
	while (($inv = pg_fetch_array($prnInvRslt)) && ($out > 0)) {
		$invs_arr[] = array ("s",$inv['odate'],"$inv[invid]","$inv[balance]");
	}

	#####################[ GET OUTSTANDING NON STOCK INVOICES ]######################
	$sql = "
		SELECT invnum, invid, balance, odate 
		FROM nons_invoices 
		WHERE cusid='$id' AND done='y' AND balance>0 AND div='".USER_DIV."' ORDER BY odate ASC";
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
			WHERE cusnum='$id' AND done='y' AND balance > 0 AND div='".USER_DIV."'";
	}
	$sql = implode(" UNION ", $sqls);
	$prnInvRslt = db_exec($sql);
	while($inv = pg_fetch_array($prnInvRslt)){
		$invs_arr[] = array ("p",$inv['odate'],"$inv[invid]","$inv[balance]");
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

			db_connect ();

			$get_sql = "
				SELECT invnum, invid, balance, terms, odate 
				FROM invoices 
				WHERE cusnum = '$id' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' AND invid = '$arr[2]'  LIMIT 1";
			$run_sql = db_exec($get_sql) or errDie ("Unable to get stock invoice information.");
			if (pg_numrows($run_sql) > 0){

				$inv = pg_fetch_array ($run_sql);
				$invid = $inv['invid'];

				$val = allocamt($out, $inv["balance"]);

				if ($val == 0.00) 
					continue;

				$inv['invnum'] += 0;

				// reduce invoice balance
				$sql = "
					UPDATE cubit.invoices 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				$sql = "
					UPDATE cubit.open_stmnt 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				# record the payment on the statement
				$sql = "
					INSERT INTO cubit.stmnt (
						cusnum, invid, amount, date, 
						type, div, allocation_date
					) VALUES (
						'$id','$inv[invnum]', '".($val - ($val * 2))."', '$accdate', 
						'Payment for Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $link['accnum'], $accdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $val, "c");

				$rinvids .= "|$invids[$key]";
				$amounts .= "|$paidamt[$key]";

				if ($inv['prd'] == "0") {
					$inv['prd'] = PRD_DB;
				}

				$invprds .= "|$inv[prd]";
				$rages .= "|0";
				$invidsers .= " - $inv[invnum]";

			}

		}elseif ($arr[0] == "n"){

			db_connect ();

			$get_sql = "
				SELECT invnum, invid, balance, odate 
				FROM nons_invoices 
				WHERE cusid='$id' AND done='y' AND balance>0 AND div='".USER_DIV."' AND invid = '$arr[2]' LIMIT 1";
			$run_sql = db_exec($get_sql) or errDie ("Unable to get non stock information.");
			if (pg_numrows($run_sql) > 0){

				$inv = pg_fetch_array ($run_sql);
				$invid = $inv['invid'];

				$val = allocamt($out, $inv["balance"]);

				if ($val == 0.00) 
					continue;

				$inv['invnum'] += 0;

				# reduce the money that has been paid
				$sql = "
					UPDATE cubit.nons_invoices 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				$sql = "
					UPDATE cubit.open_stmnt 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				# record the payment on the statement
				$sql = "
					INSERT INTO cubit.stmnt (
						cusnum, invid, amount, date, 
						type, 
						div, allocation_date
					) VALUES (
						'$id', '$inv[invnum]', '".($val - ($val * 2))."', '$accdate', 
						'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', 
						'".USER_DIV."', '$inv[odate]'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $link['accnum'], $accdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $val, "c");

				$rinvids .= "|$invids[$key]";
				$amounts .= "|$paidamt[$key]";
				$invprds .= "|0";
				$rages .= "|$inv[age]";
				$invidsers .= " - $inv[invnum]";
			}

		}else {

			db_connect ();

			$sqls = array();
			for ($i = 1; $i <= 12; ++$i) {
				$sqls[] = "
					SELECT invnum, invid, balance, odate, '$i' AS prd  
					FROM \"$i\".pinvoices 
					WHERE cusnum='$id' AND done='y' AND balance > 0 AND div='".USER_DIV."' AND invid = '$arr[2]'";
			}
			$get_sql = implode(" UNION ", $sqls);
			$run_sql = db_exec($get_sql) or errDie ("Unable to get pos invoice information.");
			if (pg_numrows($run_sql) > 0){
	
				$inv = pg_fetch_array ($run_sql);
				$invid = $inv['invid'];
	
				$val = allocamt($out, $inv["balance"]);
	
				if ($val == 0.00) 
					continue;

				// reduce the invoice balance
				$sql = "
					UPDATE \"$inv[prd]\".pinvoices 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	
				$sql = "
					UPDATE cubit.open_stmnt 
					SET balance = (balance - $val::numeric(13,2)) 
					WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
				$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

				# record the payment on the statement
				$sql = "
					INSERT INTO cubit.stmnt (
						cusnum, invid, amount, date, 
						type, div, allocation_date
					) VALUES (
						'$cus[cusnum]', '$inv[invnum]', '".($val - ($val * 2))."', '$accdate', 
						'Payment for Non Stock Invoice No. $inv[invnum]', '".USER_DIV."', '$inv[odate]'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $link['accnum'], $accdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum]", $val, "c");

				$rinvids .= "|$invids[$key]";
				$amounts .= "|$paidamt[$key]";
				$invprds .= "|$inv[prd]";
				$rages .= "|0";
				$invidsers .= " - $inv[invnum]";
			}

		}
	}

	#if there is any amount unallocated, it goes to general transaction
	$confirm .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'><b>A general transaction will credit the client's account with ".CUR." $out </b></td>
		</tr>";


	vsprint($out);

	$confirm .= "<input type='hidden' name='out' value='$out'>";


###############################################################################################################################
###############################################################################################################################
###############################################################################################################################

#######################################################################################################
########################################### PROCESS ###################################################
#######################################################################################################

	# update the customer (make balance less)
	$sql = "
		UPDATE cubit.customers 
		SET balance = (balance - '$amount'::numeric(13,2)) 
		WHERE cusnum = '$id' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	$cols = grp(
		m("bankid", $bankid),
		m("trantype", "deposit"),
		m("date", $accdate),
		m("name", "$cus[cusname] $cus[surname]"),
		m("descript", "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]"),
		m("cheqnum", $cheque),
		m("amount", $amount),
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

	writetrans($link['accnum'], $dept['debtacc'], $accdate, $refnum, $amount,
		"Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

	db_conn('cubit');

	if ($out > 0) {
		/* START OPEN ITEMS */
		$openstmnt = new dbSelect("open_stmnt", "cubit", grp(
			m("where", "balance>0 AND cusnum='$id'"),
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
				$sql = "
					INSERT INTO cubit.open_stmnt (
						cusnum, invid, amount, balance, date, type, st, div
					) VALUES (
						'$cus[cusnum]', '0', '-$out', '-$out', '$accdate', 'Payment Received', 'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
			}

			$out = $bout;
		}


		if ($out > 0) {
			recordCT($out, $cus['cusnum'],$accdate,0);

			$cols = grp(
				m("cusnum", $cus["cusnum"]),
				m("invid", 0),
				m("amount", -$out),
				m("date", $accdate),
				m("type", "Payment Received"),
				m("div", USER_DIV),
				m("allocation_date", $accdate)
			);

			$dbobj = new dbUpdate("stmnt", "cubit", $cols);
			$dbobj->run(DB_INSERT);
			$dbobj->free();

			custledger($cus['cusnum'], $link['accnum'], $accdate, "PAYMENT", "Payment received.", $out, "c");
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


}


function recordDT($amount, $supid, $edate)
{

	db_connect();

	$py = array();
	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid > 0 AND balance > 0 OR supid = '$supid' AND intpurid > 0 AND balance > 0 ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					if($dat['purid'] > 0){
						$py[] = "$dat[id]|$dat[purid]|$amount|$dat[pdate]";
					}else{
						$py[] = "$dat[id]|$dat[intpurid]|$amount|$dat[pdate]";
					}
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
						if($dat['purid'] > 0){
							$py[] = "$dat[id]|$dat[purid]|$dat[balance]|$dat[pdate]";
						}else{
							$py[] = "$dat[id]|$dat[intpurid]|$dat[balance]|$dat[pdate]";
						}
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "
				INSERT INTO suppurch (
					supid, purid, pdate, balance, div
				) VALUES (
					'$supid', '0', '$edate', '-$amount', '".USER_DIV."'
				)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch (supid, purid, pdate, balance, div) VALUES ('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	return $py;

}



# records for CT
function recordCT($amount, $cusnum, $odate)
{

	db_connect();

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

			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran (cusnum, odate, balance, div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran (cusnum, odate, balance, div) VALUES ('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance=0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



function isrefa($des1,$des2)
{

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_refs WHERE dets='i' AND (action='c' OR action='cr') AND ((position(lower(ref) IN lower('$des1'))>0)OR(position(lower(ref) IN lower('$des2'))>0))";
	$Rl = db_exec($Sl) or errDie("unable to get customer list.");

	$data = pg_fetch_array($Rl);

	$nom = $data['account'];
	$nom += 0;

	return $nom;

}




function isrefcp($des1,$des2)
{

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_refs WHERE dets='i' AND (action='cp') AND ((position(lower(ref) IN lower('$des1'))>0)OR(position(lower(ref) IN lower('$des2'))>0))";
	$Rl = db_exec($Sl) or errDie("unable to get customer list.");

	$data = pg_fetch_array($Rl);

	$nom = $data['account'];
	$nom += 0;

	return $nom;

}




function isrefsp($des1,$des2)
{

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_refs WHERE dets='i' AND (action='sp') AND ((position(lower(ref) IN lower('$des1'))>0)OR(position(lower(ref) IN lower('$des2'))>0))";
	$Rl = db_exec($Sl) or errDie("unable to get customer list.");

	$data = pg_fetch_array($Rl);

	$nom = $data['account'];
	$nom += 0;

	return $nom;

}




function isaref ($amount,$des1,$des2,$t)
{

	$amount += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_arefs WHERE amount='$amount' AND des1='$des1' AND des2='$des2' AND pn='$t'";
	$Ri = db_exec($Sl) or errDie("Unable to check statement refs.");

	$data = pg_fetch_array($Ri);

	$account = $data['account'];

	$account += 0;

	return $account;

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

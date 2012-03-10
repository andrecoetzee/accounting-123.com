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
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($HTTP_GET_VARS["cusnum"])) {
	$OUTPUT = printStmnt($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.</li>";
}

require("template.php");

# show invoices
function printStmnt ($HTTP_GET_VARS)
{
	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>$e[msg]</li>";
		}
		return $err;
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.</li>";
	}
	$cust = pg_fetch_array($custRslt);

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	if(!(open())) {
		# Query server
		$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND date >= '$fdate' AND div = '".USER_DIV."' ORDER BY branch,date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				$totout += $st['amount'];
			}
		}
	} else {
		# Query server
		$sql = "SELECT * FROM open_stmnt WHERE cusnum = '$cusnum' AND balance != '0' AND div = '".USER_DIV."' ORDER BY date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				$totout += $st['balance'];
			}
		}
	}

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);

	$rbal=$balbf;

	// 	Check if it is an open item statement
	if(!(open())) {
		# Query server
		$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND date >= '$fdate' AND div = '".USER_DIV."' ORDER BY branch,date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			$stmnt .= "<tr><td colspan=4>No invoices for this month.</td></tr>";
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				# Format date
				$st['date'] = explode("-", $st['date']);
				$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
				$st['amount']=sprint($st['amount']);
				
				if(substr($st['type'],0,7)=="Invoice") {
					$ex="INV";
				} elseif(substr($st['type'],0,17)=="Non-Stock Invoice") {
					$ex="INV";
				} elseif(substr($st['type'],0,21)=="Non Stock Credit Note") {
					$ex="CR";
				} elseif(substr($st['type'],0,11)=="Credit Note") {
					$ex="CR";
				} else {
					$ex="";
				}

				$rbal=sprint($rbal+$st['amount']);
	
				$stmnt .= "<tr><td align=center>$st[date]</td><td align=center>$ex $st[invid]</td><td align=center>$st[docref]</td><td>$st[type]  $st[branch]</td><td align=right>$cust[currency] $st[amount]</td><td align=right>$cust[currency] $rbal</td></tr>";
			}
		}
	} else {
		# Query server
		$sql = "SELECT * FROM open_stmnt WHERE cusnum = '$cusnum' AND balance != '0' AND div = '".USER_DIV."' ORDER BY date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			$stmnt .= "<tr><td colspan=4>No invoices for this month.</td></tr>";
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				# Format date
				$st['date'] = explode("-", $st['date']);
				$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
				$st['balance']=sprint($st['balance']);
				
				if(substr($st['type'],0,7)=="Invoice") {
					$ex="INV";
				} elseif(substr($st['type'],0,17)=="Non-Stock Invoice") {
					$ex="INV";
				} elseif(substr($st['type'],0,21)=="Non Stock Credit Note") {
					$ex="CR";
				} elseif(substr($st['type'],0,11)=="Credit Note") {
					$ex="CR";
				} else {
					$ex="";
				}

				$rbal=sprint($rbal+$st['balance']);
	
				$stmnt .= "<tr><td align=center>$st[date]</td><td align=center>$ex $st[invid]</td><td align=center>$st[docref]</td><td>$st[type]  $st[branch]</td><td align=right>$cust[currency] $st[balance]</td><td align=right>$cust[currency] $rbal</td></tr>";

			}
		}
	}

	if($cust['location'] == 'int')
		$cust['balance'] = $cust['fbalance'];

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust['balance'] = sprint($cust['balance']);

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$curr = ageage($cust['cusnum'], 0, $cust['fcid'], $cust['location']);
		$age30 = ageage($cust['cusnum'], 1, $cust['fcid'], $cust['location']);
		$age60 = ageage($cust['cusnum'], 2, $cust['fcid'], $cust['location']);
		$age90 = ageage($cust['cusnum'], 3, $cust['fcid'], $cust['location']);
		$age120 = ageage($cust['cusnum'], 4, $cust['fcid'], $cust['location']);
	}else{
		$curr = age($cust['cusnum'], 29, $cust['fcid'], $cust['location']);
		$age30 = age($cust['cusnum'], 59, $cust['fcid'], $cust['location']);
		$age60 = age($cust['cusnum'], 89, $cust['fcid'], $cust['location']);
		$age90 = age($cust['cusnum'], 119, $cust['fcid'], $cust['location']);
		$age120 = age($cust['cusnum'], 149, $cust['fcid'], $cust['location']);
	}
	
	$custtot=($curr+$age30+$age60+$age90+$age120);
	
	if(sprint($custtot)!=sprint($cust['balance'])) {
		$curr=sprint($curr+$cust['balance']-$custtot);
		$custtot=sprint($cust['balance']);
	}

	$age = "<table cellpadding='3' cellspacing='1' border=0 width=100% bordercolor='#000000'>
		<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days +</th></tr>
		<tr><td align=right>$cust[currency] $curr</td><td align=right>$cust[currency] $age30</td><td align=right>$cust[currency] $age60</td><td align=right>$cust[currency] $age90</td><td align=right>$cust[currency] $age120</td></tr>
		</table>";

	db_conn("cubit");

	if(!(isset($print))) {

		// Retrieve the template settings
		db_conn("cubit");
		$sql = "SELECT filename FROM template_settings WHERE template='statements'";
		$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
		$template = pg_fetch_result($tsRslt, 0);

		#get the default comment
		$get_com = "SELECT * FROM settings WHERE constant = 'DEFAULT_STMNT_COMMENTS' LIMIT 1";
		$run_com = db_exec($get_com) or errDie("Could not get default comments");
		if(pg_numrows($run_com) < 1){
			$default_stmnt_comments = "";
		}else {
			$arr = pg_fetch_array($run_com);
			$default_stmnt_comments = base64_decode($arr['value']);
		}
		$show_comment = "<textarea name='default_stmnt_comments' cols='40' rows='4'>$default_stmnt_comments</textarea>";

		$buttonz="<input type=button value='[X] Close' onClick='javascript:window.close();'> | <input type=button value='View PDF' onClick=\"javascript:document.location.href='$template?cusnum=$cusnum&sort=branch'\"> | <input type=button value='View By Date Range' onClick=\"javascript:document.location.href='cust-stmnt-date.php?cusnum=$cusnum'\">  | <input type=button value='Sort By Customer Branches' onClick=\"javascript:document.location.href='cust-stmnt-branch.php?cusnum=$cusnum'\"> | <input type=submit value='Print'>";
	} else {

		$show_comment = nl2br($default_stmnt_comments);

		$buttonz="";
	}

/*	db_conn("cubit");
	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve default comments.");
	$DEFAULT_COMMENTS = base64_decode(pg_fetch_result($rslt, 0));
*/	
	// Layout
	$printStmnt = "<center><h2>Monthly Statement</h2></center>
	<form action='".SELF."' method=GET>
	<input type=hidden name=cusnum value='$cusnum'>
	<input type=hidden name=print value='yes'>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr></td><td valign=top width=70%>
			<font size=5><b>".COMP_NAME."</b></font><br>
			".COMP_ADDRESS."<br>
			".COMP_PADDR."
		</td><td>
			COMPANY REG. ".COMP_REGNO."<br>
			TEL : ".COMP_TEL."<br>
			FAX : ".COMP_FAX."<br>
			VAT REG.".COMP_VATNO."<br>
		</td></tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=1 width=400 bordercolor='#000000'>
		<tr><th width=60%><b>Account No.</b></th><td width=40%>$cust[accno]</th></tr>
		<tr><td colspan=2>
			<font size=4><b>$cust[cusname] $cust[surname]</b></font><br>
			".nl2br($cust['addr1'])."<br>
		</td></tr>
		<tr><td><b>Balance Brought Forward</b></td><td>$cust[currency] $balbf</td>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
	  <tr>
	    <td>$show_comment</td>
	  </tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr><th>Date</th><th>Ref No.</th><th>Proforma Inv No.</th><th>Details</th><th>Amount</th><th>Balance</th></tr>
		$stmnt
		<tr><td><br></td></tr>
		<tr><td colspan=4 align=right>
			<table cellpadding='3' cellspacing='0' border=1 width=300 bordercolor='#000000'>
				<tr><th><b>Total Outstanding</b></th><td colspan=2>$cust[currency] $cust[balance]</td></tr>
			</table>
		</td></tr>
		<tr><td><br></td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=4>$age</td></tr>
		<tr><td><br></td></tr>
	</table>
	<p>
	$buttonz
	</form>";

// 	// Retrieve template settings from Cubit
// 	db_conn("cubit");
// 	$sql = "SELECT filename FROM template_settings WHERE template='statements'";
// 	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
// 	$template = pg_fetch_result($tsRslt, 0);
// 	
// 	if ($template == "stmnt-print.php") {
// 		$OUTPUT = $printStmnt;
// 		require("tmpl-print.php");
// 	} else {
// 		header ("Location: $template?cusnum=$cust[cusnum]");
// 	}
	$OUTPUT = $printStmnt;
	require("tmpl-print.php");
}

function age($cusnum, $days, $fcid, $loc){


	$rate = getRate($fcid);
	$bal = "balance";
	if($loc == 'int'){
		$bal = "fbalance";
		$rate = 1;
	}
	if($rate == 0) $rate = 1;

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum'] ) + 0);
}

function ageage($cusnum, $age, $fcid, $loc){

	$rate = getRate($fcid);
	$bal = "balance";
	if($loc == 'int'){
		$bal = "fbalance";
		$rate = 1;
	}
	if($rate == 0) $rate = 1;

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum']) + 0);
}
?>

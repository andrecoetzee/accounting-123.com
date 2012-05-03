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

require ("settings.php");
require("libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printStmnt($_POST);
			break;

		default:
			# decide what to do
			if (isset($_GET["cusnum"])) {
				$OUTPUT = slct($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
			break;
	}
} else {
	# decide what to do
	if (isset($_GET["cusnum"])) {
		$OUTPUT = slct($_GET);
	} else {
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

require ("template.php");

# Default view
function slct($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class='err'>Invalid Customer Number.</li>";
	}
	$cust = pg_fetch_array($custRslt);

	//layout
	$slct = "
				<h3>Customer Statement<h3>
				<table ".TMPL_tblDflts." width='400'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<input type='hidden' name='cusnum' value='$cusnum'>
					<tr>
						<th>Customer</th>
					</tr>
					<tr>
						<td class='".bg_class()."'>$cust[cusname] $cust[surname]</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<th>By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align=center>
							<input type=text size=2 name=fday maxlength=2 value='1'>-
							<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-
							<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
							&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
							<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-
							<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-
							<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
						</td>
						<td valign='bottom'><input type='submit' value='Search'></td>
					</tr>
				</form>
				</table>
				<p>
				<input type=button value='[X] Close' onClick='javascript:window.close();'>";

	return $slct;
}


# show invoices
function printStmnt ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.";
	}
	$cust = pg_fetch_array($custRslt);

	if($cust['location'] == 'int')
		$cust['balance'] = $cust['fbalance'];

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY date ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan=4>No invoices for this month.</td></tr>";
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# format date
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

			$stmnt .= "
						<tr>
							<td align='center'>$st[date]</td>
							<td align='center'>$ex $st[invid]</td>
							<td align='center'>$st[docref]</td>
							<td>$st[type] $st[branch]</td>
							<td align='right'>$cust[currency] $st[amount]</td>
						</tr>";

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	db_connect ();
	# get overlapping amount
	$sql = "SELECT sum(amount) as amount FROM stmnt WHERE cusnum = '$cusnum' AND date > '$todate' AND div = '".USER_DIV."' ";
	$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	$bal = pg_fetch_array ($balRslt);
	$cust['balance'] = ($cust['balance'] - $bal['amount']);

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust['balance'] = sprint($cust['balance']);

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$curr = ageage($cust['cusnum'], 0);
		$age30 = ageage($cust['cusnum'], 1);
		$age60 = ageage($cust['cusnum'], 2);
		$age90 = ageage($cust['cusnum'], 3);
		$age120 = ageage($cust['cusnum'], 4);
	}else{
		$curr = age($cust['cusnum'], 29);
		$age30 = age($cust['cusnum'], 59);
		$age60 = age($cust['cusnum'], 89);
		$age90 = age($cust['cusnum'], 119);
		$age120 = age($cust['cusnum'], 149);
	}

	$Sl="SELECT * FROM ages WHERE cust='$cust[cusnum]'";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)>0) {

		$ad=pg_fetch_array($Ri);

		$age = "<table cellpadding='3' cellspacing='1' border=0 width=100% bordercolor='#000000'>
		<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days +</th></tr>
		<tr><td align=right>$cust[currency] $ad[curr]</td><td align=right>$cust[currency] $ad[age30]</td>
		<td align=right>$cust[currency] $ad[age60]</td><td align=right>$cust[currency] $ad[age90]</td>
		<td align=right>$cust[currency] $ad[age120]</td></tr>
		</table>";
	} else {
		$age="";
	}

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='statements' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "pdf/cust-pdf-stmnt.php") {
		$template = "pdf/cust-pdf-stmnt-date.php";
	}

	if(!isset($print)) {
	$buttonz="<form action='$template' method=post name=form>
        <input type=hidden name=cusnum value='$cusnum'>
        <input type=hidden name=fday value='$fday'>
        <input type=hidden name=fmon value='$fmon'>
        <input type=hidden name=fyear value='$fyear'>
        <input type=hidden name=today value='$today'>
        <input type=hidden name=tomon value='$tomon'>
        <input type=hidden name=toyear value='$toyear'>
        <input type=button value='[X] Close' onClick='javascript:window.close();'> | <input type=submit value='View PDF'>  | <input type=button value='View By Date Range' onClick=\"javascript:document.location.href='cust-stmnt-date.php?cusnum=$cusnum'\">
		</form>
		<form action='cust-stmnt-date.php' method=post name=form>
        <input type=hidden name=key value=view>
        <input type=hidden name=print value=''>
        <input type=hidden name=cusnum value='$cusnum'>
        <input type=hidden name=fday value='$fday'>
        <input type=hidden name=fmon value='$fmon'>
        <input type=hidden name=fyear value='$fyear'>
        <input type=hidden name=today value='$today'>
        <input type=hidden name=tomon value='$tomon'>
        <input type=hidden name=toyear value='$toyear'>
        <input type=submit value='Print'></form>";

		#get the default comment, from the saved table first ...
		$get_saved = "SELECT * FROM saved_statement_comments WHERE cusnum = '$cusnum' ORDER BY id DESC LIMIT 1";
		$run_saved = db_exec($get_saved) or errDie("Unable to get saved statement comment");
		if(pg_numrows($run_saved) < 1){
			#no comment has been saved for this customer ... so check if there's a default now ...

			$get_com = "SELECT * FROM settings WHERE constant = 'DEFAULT_STMNT_COMMENTS' LIMIT 1";
			$run_com = db_exec($get_com) or errDie("Could not get default comments");
			if(pg_numrows($run_com) < 1){
				#there is absolutely no comment to display ...
				$default_stmnt_comments = "";
			}else {
				#found default comment ... use that
				$arr = pg_fetch_array($run_com);
				$default_stmnt_comments = base64_decode($arr['value']);
			}
		}else {
			#found a saved comment ... use it
			$sarr = pg_fetch_array($run_saved);
			$default_stmnt_comments = base64_decode($sarr['comment']);
		}

		$show_comment = "<textarea name='default_stmnt_comments' cols='40' rows='4'>$default_stmnt_comments</textarea>";
	} else {
		$show_comment = nl2br($default_stmnt_comments);
	$buttonz="";
	}

	// Layout
	$printStmnt = "<center><h2>Customer Statement</h2>
	<h3>$fday-$fmon-$fyear -- $today-$tomon-$toyear</h3></center>
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
	</p>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr><th>Date</th><th>Ref No.</th><th>Proforma Inv No.</th><th>Details</th><th>Amount</th></tr>
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
	$buttonz";

	# return $printStmnt;
	$OUTPUT = $printStmnt;
	require("tmpl-print.php");
}

function age($cusnum, $days){
	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum'] ) + 0);
}

function ageage($cusnum, $age){
	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum']) + 0);
}
?>

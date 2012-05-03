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

// Merge get vars with post vars
foreach ($_GET as $key=>$val) {
	$_POST[$key] = $val;
}

// We need the supid
if (!isset($_POST["supid"])) {
	$OUTPUT = "<li class=err>Invalid use of module.</li>";
	require ("template.php");
}

// Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "options":
			$OUTPUT = options($_POST);
			break;
		case "printStmnt":
			$OUTPUT = printStmnt($_POST);
			break;
	}
} else {
	$OUTPUT = options($_POST);
}

require("template.php");

function options ($_POST)
{
	extract ($_POST);

	if (!isset($comment)) {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$cmntRslt = db_exec($sql) or errDie("Unable to retrieve default comment from Cubit.");
		$comment = nl2br(base64_decode(pg_fetch_result($cmntRslt, 0)));
	}

	$OUTPUT = "<h3>Statement Options</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='printStmnt'>
	<input type=hidden name=supid value='$supid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th colspan=2>Options</th>
	  </tr>
	  <tr class='bg-odd'>
	    <td>Comment</td>
	    <td><textarea name=comment rows=5 cols=20>$comment</textarea></td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right><input type=submit value='Print Statement &raquo'>
	  </tr>
	</table>
	</form>";

	return $OUTPUT;
}

# show invoices
function printStmnt ($_POST)
{
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>$e[msg]</li>";
		}
		return $err;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class=err>Invalid Supplier Number.</li>";
	}
	$supp = pg_fetch_array($suppRslt);

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM sup_stmnt WHERE supid = '$supid' AND edate >= '$fdate' AND div = '".USER_DIV."' ORDER BY edate ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan=4>No previous Transactions for this month.</td></tr>";
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# Accounts details
	    		if($st['cacc']>0)
			{
	    			$accRs = get("core","*","accounts","accid",$st['cacc']);
    	    			$acc  = pg_fetch_array($accRs);
				$Dis ="$acc[topacc]/$acc[accnum] - $acc[accname]";
			}
			else
			{
				$Dis="Purchase Num: $st[ex]";
			}

			# format date
			$st['edate'] = explode("-", $st['edate']);
			$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];
			$st['amount']=sprint($st['amount']);
			$stmnt .= "<tr><td align=center>$st[edate]</td><td>$st[ref]</td><td>$Dis</td><td>$st[descript]</td><td align=right>$supp[currency] $st[amount]</td></tr>";

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	if($supp['location'] == 'int')
		$supp['balance'] = $supp['fbalance'];


	$balbf = ($supp['balance'] - $totout);
	$balbf =sprint($balbf);
	$totout=sprint($totout);
	$supp['balance'] = sprint($supp['balance']);

	# Get all ages
	$curr = age($supp['supid'], 29, $supp['location']);
	$age30 = age($supp['supid'], 59, $supp['location']);
	$age60 = age($supp['supid'], 89, $supp['location']);
	$age90 = age($supp['supid'], 119, $supp['location']);
	$age120 = age($supp['supid'], 149, $supp['location']);

	$age = "<table cellpadding='3' cellspacing='1' border=0 width=100% bordercolor='#000000'>
		<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days +</th></tr>
		<tr><td align=right>$supp[currency] $curr</td><td align=right>$supp[currency] $age30</td><td align=right>$supp[currency] $age60</td><td align=right>$supp[currency] $age90</td><td align=right>$supp[currency] $age120</td></tr>
		</table>";

	if(!isset($print)) {
		$bottonz="<input type=button value='[X] Close' onClick='javascript:window.close();'> | <input type=button value='View PDF' onClick=\"javascript:document.location.href='pdf/supp-pdf-stmnt.php?supid=$supid'\"> |<input type=button value='View By Date Range' onClick=\"javascript:document.location.href='supp-stmnt-date.php?supid=$supid'\">|<input type=button value='Print' onClick=\"javascript:document.location.href='supp-stmnt.php?supid=$supid&print=yes'\">";
	} else {
		$bottonz="";
	}
	
	// Layout
	$printStmnt = "<center><h2>Supplier Reconciliation Statement<h2></center>
	<!--<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
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
	</table>-->
	<p>
	<table cellpadding='3' cellspacing='0' border=1 width=400 bordercolor='#000000'>
		<tr><th width=60%><b>Supplier No.</b></th><td width=40%>$supp[supno]</th></tr>
		<tr><td colspan=2>
			<font size=4><b>$supp[supname]</b></font><br>
			".nl2br($supp['supaddr'])."<br>
		</td></tr>
		<tr><td><b>Balance Brought Forward</b></td><td>$supp[currency] $balbf</td>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
	  <tr>
	    <td>$comment</td>
	  </tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr><th>Date</th><th>Reference</th><th>Contra Account</th><th>Description</th><th>Amount</th></tr>
		$stmnt
		<tr><td><br></td></tr>
		<tr><td colspan=4 align=right>
			<table cellpadding='3' cellspacing='0' border=1 width=300 bordercolor='#000000'>
				<tr><th><b>Total Outstanding</b></th><td colspan=2>$supp[currency] $supp[balance]</td></tr>
			</table>
		</td></tr>
		<tr><td><br></td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=4>$age</td></tr>
		<tr><td><br></td></tr>
	</table>
	<p>
	$bottonz";

	# return $printStmnt;
	$OUTPUT = $printStmnt;
	require("tmpl-print.php");
}

# check age
function age($supid, $days, $loc){
	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	db_connect();
	# Get the current outstanding
	$sql = "SELECT sum($bal) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <='".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] += 0);
}
?>

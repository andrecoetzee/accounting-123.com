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

if (isset($HTTP_POST_VARS["supid"])){
	foreach ($HTTP_POST_VARS AS $each => $own){
		$HTTP_GET_VARS[$each] = $own;
	}
}
# Decide what to do
if (isset($HTTP_GET_VARS["supid"])) {
	$OUTPUT = printStmnt($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

require("template.php");




# show invoices
function printStmnt ($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	if(!isset($from_year))
		$from_year = date ("Y");
	if (!isset($from_month))
		$from_month = date("m");
	if (!isset($from_day))
		$from_day = "01";
	if (!isset($to_year))
		$to_year = date ("Y");
	if (!isset($to_month))
		$to_month = date ("m");
	if (!isset($to_day))
		$to_day = date ("d");


	# Get selected supplier info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class='err'>Invalid Supplier Number.</li>";
	}
	$supp = pg_fetch_array($suppRslt);

	# connect to database
	db_connect ();

	//$fdate = date("Y")."-".date("m")."-"."01";
	$fromdate = "$from_year-$from_month-$from_day";
	$todate = "$to_year-$to_month-$to_day";
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "
		SELECT * FROM sup_stmnt 
		WHERE supid = '$supid' AND edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' 
		ORDER BY edate ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan='4'>No previous Transactions for this month.</td></tr>";
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# Accounts details
	    	if($st['cacc'] > 0) {
	    		$accRs = get("core","*","accounts","accid",$st['cacc']);
    	    	$acc = pg_fetch_array($accRs);
				$Dis = "$acc[topacc]/$acc[accnum] - $acc[accname]";
			}else {
				$Dis = "Purchase Num: $st[ex]";
				$acc['accname'] = "";
			}

			# format date
			$st['edate'] = explode("-", $st['edate']);
			$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];
			$st['amount'] = sprint($st['amount']);
//							<td>$Dis</td>
			$stmnt .= "
				<tr>
					<td align='center'>$st[edate]</td>
					<td>$st[ref]</td>
					<td>$acc[accname]</td>
					<td>$st[descript]</td>
					<td align='right' nowrap>$supp[currency] $st[amount]</td>
				</tr>";

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	if($supp['location'] == 'int')
		$supp['balance'] = $supp['fbalance'];


	$balbf = ($supp['balance'] - $totout);
	$balbf = sprint($balbf);
	$totout = sprint($totout);
	$supp['balance'] = sprint($supp['balance']);

	# Get all ages
// 	$curr = age($supp['supid'], 29, $supp['location']);
// 	$age30 = age($supp['supid'], 59, $supp['location']);
// 	$age60 = age($supp['supid'], 89, $supp['location']);
// 	$age90 = age($supp['supid'], 119, $supp['location']);
// 	$age120 = age($supp['supid'], 149, $supp['location']);

	$curr = age($supp['supid'], 29);
	$age30 = age($supp['supid'], 59);
	$age60 = age($supp['supid'], 89);
	$age90 = age($supp['supid'], 119);
	$age120 = age($supp['supid'], 149);

	$supttot = sprint(($curr + $age30 + $age60 + $age90 + $age120));

	if($supttot < $supp['balance']) {
		$curr = sprint($curr + ($supp['balance'] - $supttot));
		$supttot = sprint($supttot + $supp['balance'] - $supttot);
	}

	$age = "
		<table cellpadding='3' cellspacing='0' class='border' width='750' bordercolor='#000000'>
			<tr>
				<th>120 days +</th>
				<th>90 days</th>
				<th>60 days</th>
				<th>30 days</th>
				<th>Current</th>
			</tr>
			<tr>
				<td align='right'>$supp[currency] $age120</td>
				<td align='right'>$supp[currency] $age90</td>
				<td align='right'>$supp[currency] $age60</td>
				<td align='right'>$supp[currency] $age30</td>
				<td align='right'>$supp[currency] $curr</td>
			</tr>
		</table>";

//	if(!isset($print)) {
//		$bottonz = "<input type='button' value='View PDF' > | <input type='button' value='View By Date Range' onClick=\"javascript:document.location.href='supp-stmnt-date.php?supid=$supid'\"> | <input type='button' value='Print' >";
//	} else {
		$bottonz = "";
//	}
//		<th class='thkborder'>Contra Account</th>

	$printStmnt = "";

	// Statement settings, only display when not printing
	if (!isset($print)){//!isset($key) || $key != "print") {
		$printStmnt .= "
			<center>
			<form method='POST' action='".SELF."' name='form'>
				<input type='hidden' name='supid' value='$supid' />
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_day' value='$to_day'>
			<table ".TMPL_tblDflts." style='border: 1px solid #000'>
				<tr bgcolor='".bgcolorg()."'>
					<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
					<td align='center'>&nbsp; <b>To</b> &nbsp;</td>
					<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'>
						<input type='button'  value='Print' onClick=\"javascript:document.location.href='supp-stmnt.php?supid=$supid&print=yes&from_year=$from_year&from_month=$from_month&from_day=$from_day&to_year=$to_year&to_month=$to_month&to_day=$to_day'\" />
					</td>
					<td><input type='submit' value='Apply' style='font-weight: bold' /></td>
					<td align='center'>
						<input type='button' value='View PDF' onClick=\"javascript:document.location.href='pdf/supp-pdf-stmnt.php?supid=$supid&from_year=$from_year&from_month=$from_month&from_day=$from_day&to_year=$to_year&to_month=$to_month&to_day=$to_day'\" />
					</td>
				</tr>
			</table>
			</form>
			</center>";
	}



	// Layout
	$printStmnt .= "
		<center>
		<h2>Supplier Statement<h2>
		</center>
		<!--
		<table cellpadding='3' cellspacing='0' border='0' width='750' bordercolor='#000000'>
			<tr>
				<td valign='top' width='70%'>
					<font size='5'><b>".COMP_NAME."</b></font><br>
					".COMP_ADDRESS."<br>
					".COMP_PADDR."
				</td>
				<td>
					COMPANY REG. ".COMP_REGNO."<br>
					TEL : ".COMP_TEL."<br>
					FAX : ".COMP_FAX."<br>
					VAT REG.".COMP_VATNO."<br>
				</td>
			</tr>
		</table>
		-->
		<p>
		<table cellpadding='3' cellspacing='0' class='border' width='350' bordercolor='#000000'>
			<tr>
				<th width='60%'><b>Supplier No.</b></th>
				<td width='40%'>$supp[supno]</th>
			</tr>
			<tr>
				<td colspan='2'><font size='4'><b>$supp[supname]</b></font><br>".nl2br($supp['supaddr'])."<br></td>
			</tr>
			<tr>
				<td><b>Balance Brought Forward</b></td>
				<td>$supp[currency] $balbf</td>
			</tr>
		</table>
		<p>
		<table cellpadding='3' cellspacing='0' border='0' width='750' bordercolor='#000000'>
			<tr>
				<th class='thkborder'>Date</th>
				<th class='thkborder'>Reference</th>
				<th class='thkborder'>Contra</th>
				<th class='thkborder'>Description</th>
				<th class='thkborder thkborder_right'>Amount</th>
			</tr>
			$stmnt
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td colspan='5' align='right'>
					<table cellpadding='3' cellspacing='0' class='border' width='300' bordercolor='#000000'>
						<tr>
							<th><b>Total Outstanding</b></th>
							<td colspan='2'>$supp[currency] $supp[balance]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td colspan='5'>$age</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		<p>
		$bottonz";

	// Retrieve template settings from Cubit
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='statements'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	$OUTPUT = $printStmnt;
	require("tmpl-print.php");

}


function age($supid, $days)
{

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	$getfromyear = date("Y");
	$gettoyear = date("Y");
	$month = date ("m");

	#use customer statement date for default
	$get_stmnt = "SELECT setdays FROM suppliers WHERE supid = '$supid' LIMIT 1";
	$run_stmnt = db_exec($get_stmnt) or errDie ("Unable to get statement date information.");
	if(pg_numrows($run_stmnt) < 1){
		$stmnt_day = date("d");
	}else {
		$stmnt_day = pg_fetch_result ($run_stmnt,0,0);
	}

	if ($stmnt_day == "0") 
		$stmnt_day = date("d",mktime (0, 0, 0, $month+1, 0, $gettoyear));

	$stmnt_date = "$gettoyear-$month-$stmnt_day";

	if ($days == 29){
		#current
		$from_date = date("Y-m-d",mktime(0,0,0,$month,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month + 1,$stmnt_day-1,$gettoyear));
	}elseif ($days == 59){
		#30 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 1,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month,$stmnt_day-1,$gettoyear));
	}elseif ($days == 89){
		#60 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 2,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 1,$stmnt_day-1,$gettoyear));
	}elseif ($days == 119){
		#90 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 3,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 2,$stmnt_day-1,$gettoyear));
	}elseif ($days == 149){
		#120 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 4,$stmnt_day,$gettoyear-5));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 3,$stmnt_day-1,$gettoyear));
	}else {
		$from_date = $stmnt_date;
		$to_date = $stmnt_date;
	}

	$oldmethod = TRUE;
	$newmethod = FALSE;

	// check allocation state
	$get_check = "SELECT id, allocation_linked FROM cubit.sup_stmnt WHERE supid = '$supid'";
	$run_check = db_exec ($get_check) or errDie ("Unable to get allocation check information.");
	if (pg_numrows ($run_check) > 0){
		while ($tarr = pg_fetch_array ($run_check)){
			if (strlen ($tarr['allocation_linked']) > 0) {
				$old_method = FALSE;
				$newmethod = TRUE;
			}
		}
	}

	if ($newmethod) {
		$get_entries = "
			SELECT amount, allocation_balance FROM cubit.sup_stmnt 
			WHERE supid = '$supid' AND edate BETWEEN '$from_date' AND '$to_date'";
		$run_entries = db_exec ($get_entries) or errDie ("Unable to get statement information");
		if (pg_numrows ($run_entries) < 1){
			$amount = 0;
		}else {
			while ($aarr = pg_fetch_array ($run_entries)){
				if ($aarr['amount'] > 0){
					$amount += $aarr['allocation_balance'];
				}else {
					$amount -= $aarr['allocation_balance'];
				}
			}
		}

		return sprint ($amount);

	}else {
		# Get the current outstanding
		$sql = "
			SELECT sum(balance) 
			FROM suppurch 
			WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
		$rs = db_exec($sql) or errDie("Unable to access database");
		$sum = pg_fetch_array($rs);

		/*
		# Get the current outstanding
		$sql = "SELECT sum(balance) FROM purch_int WHERE supid = '$supid' AND balance > 0 AND received = 'y' AND pdate >='".extlib_ago($days)."' AND pdate <='".extlib_ago($days-30)."'";
		$rsint = db_exec($sql) or errDie("Unable to access database");
		$sumint = pg_fetch_array($rsint);
		*/

		# Take care of nasty zero
		return sprint($sum['sum']);
	}
}


# check age
// function age($supid, $days, $loc)
// {
// 
// 	$bal = "balance";
// 	if($loc == 'int')
// 		$bal = "fbalance";
// 
// 	$ldays  = $days;
// 	if($days == 149)
// 		$ldays = (365 * 10);
// 
// 	db_connect();
// 
// 	# Get the current outstanding
// 	$sql = "
// 		SELECT sum($bal) FROM suppurch 
// 		WHERE supid = '$supid' AND pdate BETWEEN '".extlib_ago($ldays)."' AND '".extlib_ago($days-29)."' AND div = '".USER_DIV."'";
// 	$rs = db_exec($sql) or errDie("Unable to access database");
// 	$sum = pg_fetch_array($rs);
// 
// 	# Take care of nasty zero
// 	return sprint($sum['sum'] += 0);
// 
// }


?>
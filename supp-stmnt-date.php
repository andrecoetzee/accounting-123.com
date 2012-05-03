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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printStmnt($_POST);
			break;
		default:
			# decide what to do
			if (isset($_GET["supid"])) {
				$OUTPUT = slct($_GET);
			} else {
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
			}
			break;
	}
} else {
	# decide what to do
	if (isset($_GET["supid"])) {
		$OUTPUT = slct($_GET);
	} else {
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

require("template.php");




# Default view
function slct($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 20, "Invalid supplier number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}



	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Suppleir");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class='err'>Invalid supplier Number.</li>";
	}
	$supp = pg_fetch_array($suppRslt);

	//layout
	$slct = "
		<h3>Supplier Reconciliation Statement<h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<input type='hidden' name='supid' value='$supid'>
			<tr>
				<th>Supplier</th>
			</tr>
			<tr>
				<td class='".bg_class()."'>$supp[supname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<input type='button' value='[X] Close' onClick='javascript:parent.window.close();'>";
	return $slct;

}




# Show invoices
function printStmnt ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}



	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class='err'>Invalid Supplier Number.</li>";
	}
	$supp = pg_fetch_array($suppRslt);

	if($supp['location'] == 'int')
		$supp['balance'] = $supp['fbalance'];

	# connect to database
	db_connect ();
	// $fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM sup_stmnt WHERE supid = '$supid' AND edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' ORDER BY edate ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan='4'>No previous Transactions for this month.</td></tr>";
	}else {
		while ($st = pg_fetch_array ($stRslt)) {
			# Accounts details
	    	if($st['cacc']>0){
	    		$accRs = get("core","*","accounts","accid",$st['cacc']);
    	    	$acc  = pg_fetch_array($accRs);
				$Dis ="$acc[topacc]/$acc[accnum] - $acc[accname]";
			}else{
				$Dis="Purchase Num: $st[ex]";
			}
			
			# format date
			$st['edate'] = explode("-", $st['edate']);
			$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];
			$st['amount']=sprint($st['amount']);
			$stmnt .= "
				<tr>
					<td align='center'>$st[edate]</td>
					<td>$st[ref]</td>
					<td>$Dis</td>
					<td>$st[descript]</td>
					<td align='right'>$supp[currency] $st[amount]</td>
				</tr>";

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	# get overlapping amount
	db_connect ();
	$sql = "SELECT sum(amount) as amount FROM sup_stmnt WHERE supid = '$supid' AND edate > '$todate' AND div = '".USER_DIV."' ";
	$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	$bal = pg_fetch_array ($balRslt);
	$supp['balance'] = ($supp['balance'] - $bal['amount']);

	$balbf = ($supp['balance'] - $totout);
	$balbf = sprint($balbf);
	$totout = sprint($totout);
	$supp['balance'] = sprint($supp['balance']);

	if(!isset($print)) {
        $buttonz="
        <p>
        <form action='pdf/supp-pdf-stmnt-date.php' method='POST' name='form'>
	        <input type='hidden' name='supid' value='$supid'>
	        <input type='hidden' name='fday' value='$from_day'>
	        <input type='hidden' name='fmon' value='$from_month'>
	        <input type='hidden' name='fyear' value='$from_year'>
	        <input type='hidden' name='today' value='$to_day'>
	        <input type='hidden' name='tomon' value='$to_month'>
	        <input type='hidden' name='toyear' value='$to_year'>
	        <input type='button' value='[X] Close' onClick='javascript:parent.window.close();'> | <input type='submit' value='View PDF'>
		</form>
		<form action='supp-stmnt-date.php' method='POST' name='form'>
	        <input type='hidden' name='key' value='view'>
	        <input type='hidden' name='print' value=''>
	        <input type='hidden' name='supid' value='$supid'>
	        <input type='hidden' name='from_day' value='$from_day'>
	        <input type='hidden' name='from_month' value='$from_month'>
	        <input type='hidden' name='from_year' value='$from_year'>
	        <input type='hidden' name='to_day' value='$to_day'>
	        <input type='hidden' name='to_month' value='$to_month'>
	        <input type='hidden' name='to_year' value='$to_year'>
	        <input type='submit' value='Print'>
       </form>";
        } else {
			$buttonz="";
        }


	// Layout
	$printStmnt = "
		<center>
			<h2>Reconciliation Statement</h2>
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
		<table cellpadding='3' cellspacing='0' border='1' width='400' bordercolor='#000000'>
			<tr>
				<th width='60%'><b>Supplier No.</b></th>
				<td width='40%'>$supp[supno]</th>
			</tr>
			<tr>
				<td colspan='2'>
					<font size='4'><b>$supp[supname]</b></font><br>
					".nl2br($supp['supaddr'])."<br>
				</td>
			</tr>
			<tr>
				<td><b>Balance Brought Forward</b></td>
				<td>$supp[currency] $balbf</td>
			</tr>
		</table>
		<p>
		<table cellpadding='3' cellspacing='0' border='0' width='750' bordercolor='#000000'>
			<tr>
				<th>Date</th>
				<th>Reference</th>
				<th>Contra Account</th>
				<th>Description</th>
				<th>Amount</th>
			</tr>
			$stmnt
			<tr><td><br></td></tr>
			<tr>
				<td colspan='4' align='right'>
					<table cellpadding='3' cellspacing='0' border='1' width='300' bordercolor='#000000'>
						<tr>
							<th><b>Total Outstanding</b></th>
							<td colspan='2'>$supp[currency] $supp[balance]</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		$buttonz";

	# return $printStmnt;
	$OUTPUT = $printStmnt;
	require("tmpl-print.php");

}


?>
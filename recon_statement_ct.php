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

# Decide what to do
if (isset($HTTP_GET_VARS["supid"])) {
	$OUTPUT = printStmnt($HTTP_GET_VARS);
} else {
	$OUTPUT = select();
}

require("template.php");




function select($message="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	if (empty($search)) $search = "[(EMPTY SEARCH FIELD)]";

	$sql = "SELECT supid, supno, supname FROM cubit.suppliers
			WHERE supno ILIKE '$search%' OR supname ILIKE '$search%'
			ORDER BY supno ASC";
	$suppliers_rslt = db_exec($sql) or errDie("Unable to retrieve suppliers.");

	if ($search == "[(EMPTY SEARCH FIELD)]") $search = "";

	$suppliers_out = "";
	while (list($supid, $supno, $supname) = pg_fetch_array($suppliers_rslt)) {
		$suppliers_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$supno</td>
			<td>$supname</td>
			<td><a href='".SELF."?key=display&supid=$supid'>Select</a></td>
		</tr>";
	}
	
	if (empty($suppliers_out) && empty($search)) {
		$suppliers_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>
				<li>
					Please enter the first few letters of the creditors name or
					supplier no.
				</li>
			</td>
		</tr>";
	} elseif (empty($suppliers_out)) {
		$suppliers_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>
				<li>No results found.</li>
			</td>
		</tr>";
	}
		
	
	$OUTPUT = "
	<center>
	<h3>Creditor Recon Statement</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='select' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$message</td>
		</tr>
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Supplier No.</th>
			<th>Name</th>
			<th>Select</th>
		</tr>
		$suppliers_out
	</table>
	</center>";
	return $OUTPUT;

}



# show invoices
function printStmnt ($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	$fields = array();
	$fields["creditor_balance"] = 0;

	extract ($fields, EXTR_SKIP);

	if (!is_numeric($creditor_balance))
		$creditor_balance = 0;

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
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM sup_stmnt WHERE supid = '$supid' AND edate >= '$fdate' AND div = '".USER_DIV."' ORDER BY edate ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan='4'>No previous Transactions for this month.</td></tr>";
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# Accounts details
	    	if($st['cacc']>0) {
    			$accRs = get("core","*","accounts","accid",$st['cacc']);
    			$acc = pg_fetch_array($accRs);
				$Dis = "$acc[topacc]/$acc[accnum] - $acc[accname]";
			}else {
				$Dis = "Purchase Num: $st[ex]";
			}

			# format date
			$st['edate'] = explode("-", $st['edate']);
			$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];
			$st['amount'] = sprint($st['amount']);
			$stmnt .= "
						<tr>
							<td align='center'>$st[edate]</td>
							<td>$st[ref]</td>
							<td>$Dis</td>
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
	$curr = age($supp['supid'], 29, $supp['location']);
	$age30 = age($supp['supid'], 59, $supp['location']);
	$age60 = age($supp['supid'], 89, $supp['location']);
	$age90 = age($supp['supid'], 119, $supp['location']);
	$age120 = age($supp['supid'], 149, $supp['location']);

	$supttot = sprint(($curr + $age30 + $age60 + $age90 + $age120));

    if($supttot < $supp['balance']) {
		$curr = sprint($curr + ($supp['balance'] - $supttot));
		$supttot = sprint($supttot + $supp['balance'] - $supttot);
    }

	$age = "
				<table cellpadding='3' cellspacing='0' class='border' width=100% bordercolor='#000000'>
					<tr>
						<th>Current</th>
						<th>30 days</th>
						<th>60 days</th>
						<th>90 days</th>
						<th>120 days +</th>
					</tr>
					<tr>
						<td align='right'>$supp[currency] $curr</td>
						<td align='right'>$supp[currency] $age30</td>
						<td align='right'>$supp[currency] $age60</td>
						<td align='right'>$supp[currency] $age90</td>
						<td align='right'>$supp[currency] $age120</td>
					</tr>
				</table>";

	if(!isset($print)) {
		$bottonz = "<input type='button' value='[X] Close' onClick='javascript:parent.window.close();'> | <input type='button' value='View PDF' onClick=\"javascript:document.location.href='pdf/supp-pdf-stmnt.php?supid=$supid'\"> |<input type=button value='View By Date Range' onClick=\"javascript:document.location.href='supp-stmnt-date.php?supid=$supid'\">|<input type=button value='Print' onClick=\"javascript:document.location.href='supp-stmnt.php?supid=$supid&print=yes'\">";
	} else {
		$bottonz = "";
	} 

	$sql = "SELECT balance FROM cubit.recon_creditor_balances
			WHERE supid='$supid'";
	$cbalance_rslt = db_exec($sql) or errDie("Unable to retrieve creditor balance.");
	$creditor_balance = pg_fetch_result($cbalance_rslt, 0);

	$total_balance = sprint($supp["balance"] + $creditor_balance);
	$diff_balance = sprint($supp["balance"] - $creditor_balance);

	$sql = "SELECT date, reason, amount FROM cubit.recon_balance_ct
				LEFT JOIN cubit.recon_reasons
					ON recon_balance_ct.reason_id=recon_reasons.id
			WHERE supid='$supid' AND date>='$fdate'";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");
	
	$balance_out = "";
	while (list($date, $reason, $amount) = pg_fetch_array($balance_rslt)) {
		$balance_out .= "
		<tr>
			<td>$date</td>
			<td>$reason</td>
			<td align='right'>$amount</td>
		</tr>";
	}
	
	$sql = "
	SELECT date, comment FROM cubit.recon_comments_ct
	WHERE supid='$supp[supid]' ORDER BY id DESC";
	$comments_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
	
	$comments_out = "";
	while ($comments_data = pg_fetch_array($comments_rslt)) {
		$comments_out .= "
		<tr>
			<td>$comments_data[date]</td>
			<td>".base64_decode(nl2br($comments_data["comment"]))."</td>
		</tr>";
	}

	// Layout
	$printStmnt = "
	<center>
		<h2>Supplier Reconciliation Statement<h2>
	</center>
	<!--<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
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
	</table>-->
	<p>
	<table cellpadding='3' cellspacing='0' class='border' width='400' bordercolor='#000000'>
		<tr>
			<th width='60%'><b>Supplier No.</b></th>
			<td width='40%'>$supp[supno]</th>
		</tr>
		<tr>
			<td colspan='2'>
				<font size=4><b>$supp[supname]</b></font><br>
				".nl2br($supp['supaddr'])."<br>
			</td>
		</tr>
		<tr>
			<td><b>Balance Brought Forward</b></td>
			<td>$supp[currency] $balbf</td>
		</tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr>
			<th class='thkborder'>Date</th>
			<th class='thkborder'>Reference</th>
			<th class='thkborder'>Contra Account</th>
			<th class='thkborder'>Description</th>
			<th class='thkborder thkborder_right'>Amount</th>
		</tr>
		$stmnt
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td colspan='5' align='right'>
				<table cellpadding='3' cellspacing='0' class='border' width=300 bordercolor='#000000'>
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
			<td colspan='4'>$age</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
	</table>
	<p></p>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3' align='center'>
							<a href='recon_balance_ct.php?key=reason&supid=$supp[supid]'>
								Add/Remove/View Reasons
							</a>
						</th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Reason</th>
						<th>Amount</th>
					</tr>
					$balance_out
				</table>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3' align='center'>
							<a href='recon_balance_ct.php?key=comments&supid=$supp[supid]'>
								Add/Remove/View Comments
							</a>
						</th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Comment</th>
					</tr>
					$comments_out
				</table>
			</td>
		</tr>
	</table>
	<p></p>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<td>Balance According to Cubit</td>
			<td align='right'>$supp[balance]</td>
		</tr>
		<tr>
			<td>Balance According to Creditor</td>
			<td align='right'>$creditor_balance</td>
		</tr>
		<tr>
			<td>Difference in amount</td>
			<td align='right'>$diff_balance</td>
		</tr>
	</table>
	</form>
	<p></p>
	$bottonz";

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='statements'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	$OUTPUT = $printStmnt;
	require("tmpl-print.php");

}



# check age
function age($supid, $days, $loc)
{

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
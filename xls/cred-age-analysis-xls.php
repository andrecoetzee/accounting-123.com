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

require ("../settings.php");
require ("../libs/ext.lib.php");

# show current stock
$OUTPUT = printSupp ();

require ("../template.php");

# show stock
function printSupp ()
{
	# Set up table to display in
	$printSupp = "
		<table>
			<tr><th colspan='3'><h3>Creditors Age Analysis</h3></th></tr>
			<tr><th></th></tr>
			<tr>
				<th><u>Acc no.</u></th>
				<th><u>Suppliers</u></th>
				<th><u>Current</u></th>
				<th><u>30 days</u></th>
				<th><u>60 days</u></th>
				<th><u>90 days</u></th>
				<th><u>120 days</u></th>
				<th><u>Total Outstanding</u></th>
			</tr>";

	# connect to database
	db_connect();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supname ASC";
	$suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li>There are no Suppliers in Cubit.</li>";
	}

	# totals
	$totcurr = 0;
	$tot30 = 0;
	$tot60 = 0;
	$tot90 = 0;
	$tot120 = 0;
	$alltot = 0;

	while ($supp = pg_fetch_array ($suppRslt)) {
		# Get all ages
		$curr = age($supp['supid'], 29);
		$age30 = age($supp['supid'], 59);
		$age60 = age($supp['supid'], 89);
		$age90 = age($supp['supid'], 119);
		$age120 = age($supp['supid'], 149);

		# Suppliers total
		$supptot = ($curr + $age30 + $age60 + $age90 + $age120);
		
		if($supptot < $supp['balance']) {
			$curr = sprint($curr+($supp['balance']-$supptot));
			$supptot = sprint($supptot+$supp['balance']-$supptot);
			
		}

		$printSupp .= "
			<tr>
				<td>$supp[supno]</td>
				<td>$supp[supname]</td>
				<td>".CUR." $curr</td>
				<td>".CUR." $age30</td>
				<td>".CUR." $age60</td>
				<td>".CUR." $age90</td>
				<td>".CUR." $age120</td>
				<td>".CUR." $supptot</td>
			</tr>";

		# hold totals
		$totcurr += $curr;
		$tot30 += $age30;
		$tot60 += $age60;
		$tot90 += $age90;
		$tot120 += $age120;
		$alltot += $supptot;
		$i++;
	}

	$printSupp .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'><b>Totals</b></td>
				<td><b>".CUR." $totcurr</b></td>
				<td><b>".CUR." $tot30</b></td>
				<td><b>".CUR." $tot60</b></td>
				<td><b>".CUR." $tot90</b></td>
				<td><b>".CUR." $tot120</b></td>
				<td><b>".CUR." $alltot</b></td>
			</tr>
		</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("CredAgeAnalysis", $printSupp);

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

// function age($supid, $days)
// {
// 
// 	$ldays  = $days;
// 	if($days == 149)
// 		$ldays = (365 * 10);
// 
// 	# Get the current outstanding
// 	$sql = "
// 		SELECT sum(balance) FROM suppurch 
// 		WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
// 	$rs = db_exec($sql) or errDie("Unable to access database");
// 	$sum = pg_fetch_array($rs);
// 
// 	/*
// 	# Get the current outstanding
// 	$sql = "SELECT sum(balance) FROM purch_int WHERE supid = '$supid' AND balance > 0 AND received = 'y' AND pdate >='".extlib_ago($days)."' AND pdate <='".extlib_ago($days-30)."'";
// 	$rsint = db_exec($sql) or errDie("Unable to access database");
// 	$sumint = pg_fetch_array($rsint);
// 	*/
// 
// 	# Take care of nasty zero
// 	return sprint($sum['sum']);
// 
// }


?>
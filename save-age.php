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

if(isset($_POST["key"])) {
	$OUTPUT = save($_POST);
} else {
	$OUTPUT = confirm();
}

$OUTPUT .="<p><br><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='customers-view.php'>View Customers</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

require("template.php");

function confirm() {
	$out="<h3>Confirm save age analysis</h3>
	Saving the age analysis will delete the previous data.
	<form action='".SELF."' method=post>
	<input type=submit name=key value='Save Data'>
	</form>";

	return $out;


}

function save ()
{

	db_connect();
	$sql = "SELECT * FROM customers";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.";
	}

	$Sl="DELETE FROM ages";
	$Ri=db_exec($Sl);


	while($cust = pg_fetch_array($custRslt)) {

		if($cust['location'] == 'int')
			$cust['balance'] = $cust['fbalance'];

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

		$Sl="INSERT INTO ages(cust,curr,age30,age60,age90,age120) VALUES('$cust[cusnum]','$curr','$age30','$age60','$age90','$age120')";
		$Ri=db_exec($Sl);

		$age = "<table cellpadding='3' cellspacing='1' border=0 width=100% bordercolor='#000000'>
			<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days +</th></tr>
			<tr><td align=right>$cust[currency] $curr</td><td align=right>$cust[currency] $age30</td><td align=right>$cust[currency] $age60</td>
			<td align=right>$cust[currency] $age90</td><td align=right>$cust[currency] $age120</td></tr>
			</table>";

	}

	$out ="<p><br><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Done</th></tr>
	<tr class='bg-odd'><td>Age analysis saved.</td></tr>
	</table>";

	return $out;
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

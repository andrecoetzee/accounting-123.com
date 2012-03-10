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

if(div_isset("DEBT_AGE", "mon")){
	$OUTPUT = printAgeAge ();
}else{
	$OUTPUT = printAgeInv ();
}

require ("../template.php");




# Age analysis by date
function printAgeInv ()
{

	# Set up table to display in
	$printCust = "
		<h3>Debtors Age Analysis</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Acc no.</th>
				<th>Customer</th>
				<th>Contact Name</th>
				<th>Tel No.</th>
				<th>Current</th>
				<th>30 days</th>
				<th>60 days</th>
				<th>90 days</th>
				<th>120 days + </th>
				<th>Total Outstanding</th>
			</tr>";

	# Connect to database
	db_connect();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY accno ASC";
    $custRslt = db_exec ($sql) or errDie ("Unable to retrieve Customers from database.");
	if (pg_numrows ($custRslt) < 1) {
		return "<li>There are no Customers in Cubit.</li>";
	}

	# Totals
	$totcurr = 0;
	$tot30 = 0;
	$tot60 = 0;
	$tot90 = 0;
	$tot120 = 0;
	$alltot = 0;

	while ($cust = pg_fetch_array ($custRslt)) {

		# Get all ages
		$to_month = date("m");
		$to_date = "now";
		$from_date = date ("Y-m-d",mktime (0,0,0,date("m"),"01",date("Y")));

		$curr = cust_age($cust['cusnum'], 29, $cust['fcid'], $cust['location'], $to_month, $to_date, $from_date);
		$age30 = cust_age($cust['cusnum'], 59, $cust['fcid'], $cust['location'], $to_month, $to_date, $from_date);
		$age60 = cust_age($cust['cusnum'], 89, $cust['fcid'], $cust['location'], $to_month, $to_date, $from_date);
		$age90 = cust_age($cust['cusnum'], 119, $cust['fcid'], $cust['location'], $to_month, $to_date, $from_date);
		$age120 = cust_age($cust['cusnum'], 149, $cust['fcid'], $cust['location'], $to_month, $to_date, $from_date);

		# Customer total
		$custtot = sprint($curr + $age30 + $age60 + $age90 + $age120);
		
		$printCust .= "
			<tr>
				<td>$cust[accno]</td>
				<td>$cust[surname]</td>
				<td>$cust[contname]</td>
				<td>$cust[bustel]</td>
				<td>".CUR." $curr</td>
				<td>".CUR." $age30</td>
				<td>".CUR." $age60</td>
				<td>".CUR." $age90</td>
				<td>".CUR." $age120</td>
				<td>".CUR." $custtot</td>
			</tr>";

		# Hold totals
		$totcurr += $curr;
		$tot30 += $age30;
		$tot60 += $age60;
		$tot90 += $age90;
		$tot120 += $age120;
		$alltot += $custtot;
		$i++;
	}

	$totcurr = sprint($totcurr);
    $tot30 = sprint($tot30);
    $tot60 = sprint($tot60);
    $tot90 = sprint($tot90);
    $tot120 = sprint($tot120);
    $alltot = sprint($alltot);

	$printCust .= "
			".TBL_BR."
			<tr>
				<td colspan='4'><b>Totals</b></td>
				<td nowrap><b>".CUR." $totcurr</b></td>
				<td nowrap><b>".CUR." $tot30</b></td>
				<td nowrap><b>".CUR." $tot60</b></td>
				<td nowrap><b>".CUR." $tot90</b></td>
				<td nowrap><b>".CUR." $tot120</b></td>
				<td nowrap><b>".CUR." $alltot</b></td>
			</tr>
			".TBL_BR."
		</table>";
	include("temp.xls.php");
	Stream("DebtAgeAnalisys", $printCust);
	exit;

}



# Age analysis by age flag
function printAgeAge ()
{

	# Set up table to display in
	$printCust = "
		<h3>Debtors Age Analysis</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Acc no.</th>
				<th>Customer</th>
				<th>Contact Name</th>
				<th>Tel No.</th>
				<th>Current</th>
				<th>30 days</th>
				<th>60 days</th>
				<th>90 days</th>
				<th>120 days</th>
				<th>Total Outstanding</th>
			</tr>";

	# Connect to database
	db_connect();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY accno ASC";
    $custRslt = db_exec ($sql) or errDie ("Unable to retrieve Customers from database.");
	if (pg_numrows ($custRslt) < 1) {
		return "<li>There are no Customers in Cubit.</li>";
	}

	# Totals
	$totcurr = 0;
	$tot30 = 0;
	$tot60 = 0;
	$tot90 = 0;
	$tot120 = 0;
	$alltot = 0;

	while ($cust = pg_fetch_array ($custRslt)) {

		# Get all ages
		$curr = ageage($cust['cusnum'], 0);
		$age30 = ageage($cust['cusnum'], 1);
		$age60 = ageage($cust['cusnum'], 2);
		$age90 = ageage($cust['cusnum'], 3);
		$age120 = ageage($cust['cusnum'], 4);

		# Customer total
		$custtot = sprint($curr + $age30 + $age60 + $age90 + $age120);

		$printCust .= "
			<tr>
				<td>$cust[accno]</td>
				<td>$cust[surname]</td>
				<td>$cust[contname]</td>
				<td>$cust[tel]</td>
				<td>".CUR." $curr</td>
				<td>".CUR." $age30</td>
				<td>".CUR." $age60</td>
				<td>".CUR." $age90</td>
				<td>".CUR." $age120</td>
				<td>".CUR." $custtot</td>
			</tr>";

		# Hold totals
		$totcurr += $curr;
		$tot30 += $age30;
		$tot60 += $age60;
		$tot90 += $age90;
		$tot120 += $age120;
		$alltot += $custtot;
		$i++;
	}

	$totcurr = sprint($totcurr);
    $tot30 = sprint($tot30);
    $tot60 = sprint($tot60);
    $tot90 = sprint($tot90);
    $tot120 = sprint($tot120);
    $alltot = sprint($alltot);

	$printCust .= "
			".TBL_BR."
			<tr>
				<td colspan='4'><b>Totals</b></td>
				<td nowrap><b>".CUR." $totcurr</b></td>
				<td nowrap><b>".CUR." $tot30</b></td>
				<td nowrap><b>".CUR." $tot60</b></td>
				<td nowrap><b>".CUR." $tot90</b></td>
				<td nowrap><b>".CUR." $tot120</b></td>
				<td nowrap><b>".CUR." $alltot</b></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='center' colspan='10'>
					<form action='../xls/debt-age-analysis-xls.php' method=post name=form>
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>";
	include("temp.xls.php");
	Stream("DebtAgeAnalisys", $printCust);
	exit;

}



function ageage($cusnum, $age)
{

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}


?>
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
}elseif (isset($_POST["xls"])){
	$OUTPUT = excel ($_POST);
}else {
	$OUTPUT = printAgeInv ($_POST);
}

require ("../template.php");




# Age analysis by date
function printAgeInv ($_POST,$pure=TRUE)
{

	extract ($_POST);

	# Set up table to display in
	global $PRDMON;
	$from = getMonthName($PRDMON[1]) . " " . getYearOfFinMon($PRDMON[1]);
	$to = getMonthName($PRDMON[12]) . " " . getYearOfFinMon($PRDMON[12]);

	$sel1 = "";
	$sel2 = "";
	$sel3 = "";
	if (isset($show_zero) AND strlen($show_zero) > 0)
		$sel1 = "checked='yes'";
	if (isset($show_large) AND strlen($show_large) > 0)
		$sel2 = "checked='yes'";
	if (isset($show_old) AND strlen($show_old) > 0)
		$sel3 = "checked='yes'";

	if ($pure){
		$show_filter = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Filter/Sort Age Analysis</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='checkbox' name='show_zero' onClick='javascript:document.form1.submit();' value='yes' $sel1> Show Zero Balances</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='checkbox' name='show_large' onClick='javascript:document.form1.submit();' value='yes' $sel2> Show Largest Balances First</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='checkbox' name='show_old' onClick='javascript:document.form1.submit();' value='yes' $sel3> Show Oldest Balances First</td>
				</tr>
				".TBL_BR."
			</table>";

		$show_quicklinks = "
			<p>
			<table ".TMPL_tblDflts." width='15%'>
		        ".TBL_BR."
		        <tr>
		        	<th>Quick Links</th>
		        </tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='../customers-new.php'>Add Customer</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='../customers-view.php'>View Customers</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}else {
		$show_filter = "";
		$show_quicklinks = "";
	}

	# Set up table to display in
	$printCust = "
		<h3>Debtors Age Analysis</h3>
		<h4>Period: $from to $to</h4>
		<form action='".SELF."' method='POST' name='form1'>
		$show_filter
		<table ".TMPL_tblDflts.">
			<tr>
				<th><font size='1'>Acc no.</font></th>
				<th><font size='1'>Customer</font></th>
				<th><font size='1'>Sales Rep</font></th>
				<th><font size='1'>Contact Name</font></th>
				<th><font size='1'>Tel No.</font></th>
				<th><font size='1'>Current</font></th>
				<th><font size='1'>30 days</font></th>
				<th><font size='1'>60 days</font></th>
				<th><font size='1'>90 days</font></th>
				<th><font size='1'>120 days + </font></th>
				<th><font size='1'>Total Outstanding</font></th>
			</tr>";

	# Connect to database
	db_connect();

	# Query server
	$i = 0;
	$entries = array ();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' OR ddiv = '".USER_DIV."' ORDER BY accno ASC";
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

		db_con ("exten");

		# sales rep
		$get_salsp = "SELECT salesp FROM salespeople WHERE salespid = '$cust[sales_rep]' LIMIT 1";
		$run_salsp = db_exec ($get_salsp) or errDie ("Unable to get sales person information.");
		if (pg_numrows ($run_salsp) > 0){
			$sarr = pg_fetch_array ($run_salsp);
			$salesperson = $sarr['salesp'];
		}else {
			$salesperson = "";
		}

		$col1[] = $cust['accno'];
		$col2[] = $cust['surname'];
		$col22[] = $salesperson;
		$col3[] = $cust['contname'];
		$col4[] = $cust['bustel'];
		$col5[] = $curr;
		$col6[] = $age30;
		$col7[] = $age60;
		$col8[] = $age90;
		$col9[] = $age120;
		$col10[] = $custtot;
		$col11[] = $cust['cusnum'];

	}

	if (!isset($show_zero) OR (isset($show_zero) AND strlen($show_zero) > 0)){
		#get key of zero entries
		foreach ($col10 AS $each => $own){
			if ($own == 0){
				unset ($col1[$each]);
				unset ($col2[$each]);
				unset ($col22[$each]);
				unset ($col3[$each]);
				unset ($col4[$each]);
				unset ($col5[$each]);
				unset ($col6[$each]);
				unset ($col7[$each]);
				unset ($col8[$each]);
				unset ($col9[$each]);
				unset ($col10[$each]);
			}
		}
	}

	if (isset($show_large) AND strlen($show_large) > 0){
		arsort ($col10);
		$sortarr = $col10;
	}
	if (isset($show_old) AND strlen($show_old) > 0){
		arsort ($col9);
		$sortarr = $col9;
		if (array_sum ($sortarr) == 0){
			arsort ($col8);
			$sortarr = $col8;
			if (array_sum ($sortarr) == 0){
				arsort ($col7);
				$sortarr = $col7;
				if (array_sum ($sortarr) == 0){
					arsort ($col6);
					$sortarr = $col6;
				}
			}
		}
	}


	if (!isset($sortarr) OR !is_array ($sortarr))
		$sortarr = $col1;

	$counter = 0;
	foreach ($sortarr AS $key => $value){
		if ($counter == 20 AND $pure){
			$printCust .= "
				<tr>
					<th><font size='1'>Acc no.</font></th>
					<th><font size='1'>Customer</font></th>
					<th><font size='1'>Sales Rep</font></th>
					<th><font size='1'>Contact Name</font></th>
					<th><font size='1'>Tel No.</font></th>
					<th><font size='1'>Current</font></th>
					<th><font size='1'>30 days</font></th>
					<th><font size='1'>60 days</font></th>
					<th><font size='1'>90 days</font></th>
					<th><font size='1'>120 days + </font></th>
					<th><font size='1'>Total Outstanding</font></th>
				</tr>";
			$counter = 0;
		}

		$printCust .= "
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap><font size='1'>$col1[$key]</font></td>
				<td nowrap><a href='#' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'><font size='1'>$col2[$key]</font></a></td>
				<td nowrap><font size='1'>$col22[$key]</font></td>
				<td nowrap><font size='1'>$col3[$key]</font></td>
				<td nowrap><font size='1'>$col4[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col5[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col6[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col7[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col8[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col9[$key]</font></td>
				<td nowrap><font size='1' onClick='popupSized(\"../cust-stmnt.php?cusnum=$col11[$key]\",\"window1\",800,700)'>".CUR." $col10[$key]</font></td>
			</tr>";
		$counter++;
	}


	$totcurr = sprint(array_sum ($col5));
	$tot30 = sprint(array_sum ($col6));
	$tot60 = sprint(array_sum ($col7));
	$tot90 = sprint(array_sum ($col8));
	$tot120 = sprint(array_sum ($col9));
	$alltot = sprint(array_sum ($col10));


	$printCust .= "
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'><font size='1'><b>Totals</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $totcurr</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $tot30</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $tot60</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $tot90</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $tot120</b></font></td>
				<td nowrap><font size='1'><b>".CUR." $alltot</b></font></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='center' colspan='10'>
					<form action='../xls/debt-age-analysis-xls.php' method='POST' name='form'>
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>
		</form>
	    $show_quicklinks";
	return $printCust;

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
				<th>Sales Rep</th>
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

		db_con ("exten");

		# sales rep
		$get_salsp = "SELECT salesp FROM salespeople WHERE salespid = '$cust[sales_rep]' LIMIT 1";
		$run_salsp = db_exec ($get_salsp) or errDie ("Unable to get sales person information.");
		if (pg_numrows ($run_salsp) > 0){
			$sarr = pg_fetch_array ($run_salsp);
			$salesperson = $sarr['salesp'];
		}else {
			$salesperson = "";
		}

		$printCust .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$cust[accno]</td>
				<td>$cust[surname]</td>
				<td>$salesperson</td>
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
			<tr bgcolor='".bgcolorg()."'>
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
				<td align='center' colspan='10'><input type='submit' name='xls' value='Export to spreadsheet'></td>
			</tr>
		</table>
	    <p>
		<table ".TMPL_tblDflts." width='15%'>
	        ".TBL_BR."
	        <tr>
	        	<th>Quick Links</th>
	        </tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../customers-new.php'>Add Customer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../customers-view.php'>View Customers</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printCust;

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



function excel()
{

	$OUTPUT = clean_html(printAgeInv($_POST,FALSE));
	require_lib("xls");
	StreamXLS("Debtors Age Analysis", $OUTPUT);

}



?>
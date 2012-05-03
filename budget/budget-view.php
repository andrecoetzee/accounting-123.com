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
require ("../settings.php");
require ("../core-settings.php");

# show current stock
$OUTPUT = printBudget ();

require ("../template.php");




# show stock
function printBudget ()
{

	require("budget.lib.php");

	# Set up table to display in
	$printBudget = "
						<h3>Current Budgets</h3>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Budget Name</th>
								<th>Entry Date</th>
								<th>Type</th>
								<th>Budget For</th>
								<th>Period/Year</th>
								<th colspan='5'>Options</th>
							</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM budgets WHERE div = '".USER_DIV."' ORDER BY budid ASC";
    $budgRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budgRslt) < 1) {
		return "
					<li class='err'>There are no Budgets in Cubit.</li>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
				        <tr><td><br></td></tr>
				        <tr>
				        	<th>Quick Links</th>
				        </tr>
						<tr class='".bg_class()."'>
							<td><a href='budget-new.php'>New Monthly Budget</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='budget-yr-new.php'>New Yearly Budget</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>
				";
	}

	while ($budg = pg_fetch_array ($budgRslt)) {
		$vbudtype = $TYPES[$budg['budtype']];
		$vbudfor = $BUDFOR[$budg['budfor']];
		$budg['edate'] = ext_rdate($budg['edate']);

		if($budg['prdtyp'] == "yr"){
			$vfromprd = $YEARS[$budg['fromprd']];
			$vtoprd = $YEARS[$budg['toprd']];
			$det = "budget-yr-details.php";
			$rem = "budget-yr-rem.php";
			$edit = "budget-yr-edit.php";
			$rep = "budget/budget-yr-report-print.php";
			$exp="budget-yr-export.php";
		}else{
			$vfromprd = $PERIODS[$budg['fromprd']];
			$vtoprd = $PERIODS[$budg['toprd']];
			$det = "budget-details.php";
			$rem = "budget-rem.php";
			$edit = "budget-edit.php";
			$rep = "budget/budget-report-print.php";
			$exp="budget-export.php";
		}


		$printBudget .= "
							<tr class='".bg_class()."'>
								<td>$budg[budname]</td>
								<td>$budg[edate]</td>
								<td>$vbudtype</td>
								<td>$vbudfor</td>
								<td>$vfromprd to $vtoprd</td>
								<td><a href='$det?budid=$budg[budid]'>Details</a></td>";
		$printBudget .= "
								<td><a href='$edit?budid=$budg[budid]'>Edit</a></td>
								<td><a href='$rem?budid=$budg[budid]'>Remove</a></td>
								<td><a href=# onClick=printer2('$rep?budid=$budg[budid]')>Report</a></td>
								<td><a target='_blank' href='$exp?budid=$budg[budid]'>Export</a></td>
							</tr>";
		$i++;
	}

	$printBudget .= "
						</table>
					    <p>
						<table ".TMPL_tblDflts." width='15%'>
					        <tr><td><br></td></tr>
					        <tr>
					        	<th>Quick Links</th>
					        </tr>
							<tr class='".bg_class()."'>
								<td><a href='budget-new.php'>New Monthly Budget</a></td>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='budget-yr-new.php'>New Yearly Budget</a></td>
							</tr>
							<tr class='".bg_class()."'>
								<td><a href='main.php'>Main Menu</a></td>
							</tr>
						</table>";
	return $printBudget;

}


?>
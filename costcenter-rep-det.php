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
require ("core-settings.php");
require ("libs/ext.lib.php");

if (isset($_GET["ccid"]) && isset($_GET["prd"])) {
	$OUTPUT = printCenter($_GET);
}elseif(isset($_POST["key"])){
	$OUTPUT = export_data($_POST);
} else {
	# Display default output
	$OUTPUT = "<li class=err> - Invalid use of module.";
}

require ("template.php");

# show stock
function printCenter ($_GET)
{

	# Get vars
	extract ($_GET);

	# Query server
	db_connect();

	$sql = "SELECT * FROM costcenters WHERE ccid = '$ccid'";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'> - Invalid Cost Center.";
	}
	$cc = pg_fetch_array ($ccRslt);

	$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

	# get income
	$income = "";
	db_conn($prd);
	$sql = "SELECT * FROM cctran WHERE ccid = '$cc[ccid]' AND trantype = 'dt'";
	$recRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");

	if(pg_numrows($recRslt) > 0){
		$income = "
				<tr>
					<td colspan='4'><h3>Income</h3></td>
				</tr>
				<tr>
					<th>Type</th>
					<th>Date</th>
					<th>Description</th>
					<th>Amount</th>
					<th>Posted By</th>
				</tr>";
		$totinc = 0;
		for($i = 0; $rec = pg_fetch_array ($recRslt); $i ++){
			$totinc += $rec['amount'];
			$rec['edate'] = ext_rdate($rec['edate']);

			$income .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$rec[typename]</td>
						<td>$sp4 $rec[edate] $sp4</td>
						<td>$rec[description]</td>
						<td align='right'>$sp4".CUR." $rec[amount]</td>
						<td>$rec[username]</td>
					</tr>";
		}
		$totinc = sprint($totinc);
		$income .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='3' align='right'><b>Total</b></td>
					<td align='right'><b>".CUR." $totinc</b></td>
					<td><br></td>
				</tr>";
	}

	#get expenses
	$expense = "";
	db_conn($prd);
	$sql = "SELECT * FROM cctran WHERE ccid = '$cc[ccid]' AND trantype = 'ct'";
	$recRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");

	if(pg_numrows($recRslt) > 0){
		$expense = "
				<tr><td><br></td></tr>
				<tr>
					<td colspan='4'><h3>Expenses</h3></td>
				</tr>
				<tr>
					<th>Type</th>
					<th>Date</th>
					<th>Description</th>
					<th>Amount</th>
					<th>Posted By</th>
				</tr>";
		$totexp = 0;
		for($i = 0; $rec = pg_fetch_array ($recRslt); $i ++){
			$totexp += $rec['amount'];
			$rec['edate'] = ext_rdate($rec['edate']);

			$expense .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$rec[typename]</td>
						<td>$sp4 $rec[edate] $sp4</td>
						<td>$rec[description]</td>
						<td align='right'>$sp4".CUR." $rec[amount]</td>
						<td>$rec[username]</td>
					</tr>";
		}
		$totexp = sprint($totexp);
		$expense .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='3' align='right'><b>Total</b></td>
					<td align='right'><b>".CUR." $totexp</b></td>
				</tr>";
	}

	$printCenter = "
				<center>
				<h3>Cost Centers Detailed Report</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Cost Center</th>
					<tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>$sp4 $cc[centername] ($cc[centercode]) $sp4</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					$income
					$expense
				</table>
				<p>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='export'>
					<input type='hidden' name='ccid' value='$ccid'>
					<input type='hidden' name='prd' value='$prd'>
					<input type='submit' value='Export To Spreadsheet'>
				</form>
				<p>
				<p>
				<input type='button' value='[X] Close' onClick='javascript:window.close();'>";
	return $printCenter;

}



function export_data ($_POST)
{
	require_lib ("xls");
	extract ($_POST);
	$data = clean_html(printCenter($_POST));
	//$data =get_data($_POST);
	StreamXLS ("report","$data");
}

?>
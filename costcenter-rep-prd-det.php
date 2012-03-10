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

if (isset($HTTP_GET_VARS["ccid"]) && isset($HTTP_GET_VARS["from_prd"]) && isset($HTTP_GET_VARS["to_prd"])) {
	$OUTPUT = printCenter($HTTP_GET_VARS);
}elseif(isset($HTTP_POST_VARS["key"])){
	$OUTPUT = export_data($HTTP_POST_VARS);
} else {
	# Display default output
	$OUTPUT = "<li class='err'> - Invalid use of module.</li>";
}

require ("template.php");

# show stock
function printCenter ($HTTP_GET_VARS)
{

	# Get vars
	extract ($HTTP_GET_VARS);

	if(!isset($ccid) OR strlen($ccid) < 1){
		return "<li class='err'>Invalid Cost Center. Cost Center Not Found.</li>";
	}

	# Query server
	db_connect();

	$sql = "SELECT * FROM costcenters WHERE ccid = '$ccid'";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'> - Invalid Cost Center.</li>";
	}
	$cc = pg_fetch_array ($ccRslt);

	$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

	# get income
	$income = "";


	$income = "
		<tr>
			<td colspan='4'><h3>Income</h3></td>
		</tr>";

	#create selection date range
	$from_finyear = getYearOfFinMon($from_prd);
	$to_finyear = getYearOfFinMon($to_prd);

//print "$from_prd -> $to_prd<br>";
	$from_month = date("m",mktime(0,0,0,$from_prd,1,$from_finyear));
	$to_month = date("m",mktime(0,0,0,$to_prd,1,$to_finyear));

	$search = "edate >= '$from_finyear-$from_month-01' AND edate <= '$to_finyear-$to_month-".date("d",mktime(0,0,0,$to_prd+1,-1,$to_finyear))."' AND ";

//	$search = "edate >= '$from_finyear-$from_prd-01' AND edate <= '$to_finyear-$to_prd-".date("d",mktime(0,0,0,$to_prd,-1,$to_finyear))."' AND ";

	$flag = TRUE;
	$x = $from_prd;
	while ($flag){
//	for($x=$from_prd;$x<=$to_prd;$x++){
		if($x == 13)
			$x = 1;

		db_conn($x);
		$sql = "SELECT * FROM cctran WHERE $search ccid = '$cc[ccid]' AND trantype = 'dt'";
		$recRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
		$tottotinc = 0;
		if(pg_numrows($recRslt) > 0){
			$income .= "
				<tr>
					<th colspan='5'>".date("F",mktime(0,0,0,$x,1,date("Y")))."</th>
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

		if($x == $to_prd)
			$flag = FALSE;
		$x++;
	}

	#get expenses
	$expense = "";
	$expense = "
		<tr><td><br></td></tr>
		<tr>
			<td colspan='4'><h3>Expenses</h3></td>
		</tr>";


	$flag = TRUE;
	$x = $from_prd;
	while ($flag){
//	for($x=$from_prd;$x<=$to_prd;$x++){

		if($x == 13)
			$x = 1;

		db_conn($x);
		$sql = "SELECT * FROM cctran WHERE $search ccid = '$cc[ccid]' AND trantype = 'ct'";
		$recRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
	
		if(pg_numrows($recRslt) > 0){
			$expense .= "
				<tr>
					<th colspan='5'>".date("F",mktime(0,0,0,$x,1,date("Y")))."</th>
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

		if($x == $to_prd)
			$flag = FALSE;
		$x++;

	}

	$printCenter = "
		<center>
		<h3>Cost Centers Detailed Period Review</h3>
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
			<input type='hidden' name='from_prd' value='$from_prd'>
			<input type='hidden' name='to_prd' value='$to_prd'>
			<input type='submit' value='Export To Spreadsheet'>
		</form>
		<p>
		<p>
		<input type='button' value='[X] Close' onClick='javascript:window.close();'>";
	return $printCenter;

}



function export_data ($HTTP_POST_VARS)
{
	require_lib ("xls");
	extract ($HTTP_POST_VARS);
	$data = clean_html(printCenter($HTTP_POST_VARS));
	//$data =get_data($HTTP_POST_VARS);
	StreamXLS ("report","$data");
}

?>
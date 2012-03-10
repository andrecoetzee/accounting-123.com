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

if(isset($HTTP_POST_VARS["export"])) {
	$OUTPUT = export ();
} else {
	$OUTPUT = printStk ();
}

require ("template.php");



# show stock
function printStk ()
{

	# Set up table to display in
	$printStk = "
		<center>
		<h3>Available Stock</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Store</th>
				<th>Category</th>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Product class</th>
				<th>Available units</th>
				<th>Cost Amount</th>
				<th>Minimun Level</th>
				<th>Maximum Level</th>
				<th>Selling Price</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_avail = 0;
	$tot_cost = 0;
	$tot_sell = 0;

    $sql = "SELECT * FROM stock WHERE units > 0 AND div = '".USER_DIV."' ORDER BY stkdes ASC";
    $stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li>There are no available stock found in Cubit.
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	while ($stk = pg_fetch_array ($stkRslt)) {
		$stk['units'] = $stk['units']-$stk['alloc'];
		# alternate bgcolor

		# get warehouse
		db_conn("exten");

		$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		$stk['csamt'] = sprint ($stk['csamt']);
		$stk['selamt'] = sprint ($stk['selamt']);

		$printStk .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$wh[whname]</td>
				<td>$stk[catname]</td>
				<td>$stk[stkcod]</td>
				<td align='center'>$stk[stkdes]</td>
				<td>$stk[prdcls]</td>
				<td align='right'>".sprint3($stk['units'])."</td>
				<td align='right'>".CUR." $stk[csamt]</td>
				<td align='right'>$stk[minlvl]</td>
				<td align='right'>$stk[maxlvl]</td>
				<td align='left' nowrap>".CUR." ".sprint($stk['selamt']*$stk['units'])." (".CUR." $stk[selamt] each)</td>
			</tr>";

		#handle totals
		if ($stk['units'] > 0)
			$tot_avail += $stk['units'];
		if ($stk['csamt'] > 0)
			$tot_cost += $stk['csamt'];
		if ($stk['selamt'] > 0)
			$tot_sell += ($stk['selamt']*$stk['units']);

	}

	$printStk .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'>Totals:</td>
			<td align='right'>".sprint3($tot_avail)."</td>
			<td align='right'>".CUR." ".sprint ($tot_cost)."</td>
			<td colspan='2'></td>
			<td align='left'>".CUR." ".sprint ($tot_sell)."</td>
		</tr>
		<tr><td><br></td></tr>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='export' value='yes'>
			<tr>
				<td colspan='2'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</form>
		</table>
   		<p>
		<table ".TMPL_tblDflts." width='15%'>
   			<tr><td><br></td></tr>
   		 	<tr>
   		 		<th>Quick Links</th>
   		 	</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printStk;

}




function export ()
{

	# Set up table to display in
	$printStk = "
		<center>
		<h3>Available Stock</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Store</th>
				<th>Category</th>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Product class</th>
				<th>Available units</th>
				<th>Cost Amount</th>
				<th>Minimun Level</th>
				<th>Maximum Level</th>
				<th>Selling Price</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_avail = 0;
	$tot_cost = 0;
	$tot_sell = 0;

    $sql = "SELECT * FROM stock WHERE units > 0 AND div = '".USER_DIV."' ORDER BY stkdes ASC";
    $stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li>There are no available stock found in Cubit.
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
	while ($stk = pg_fetch_array ($stkRslt)) {
		$stk['units'] = $stk['units']-$stk['alloc'];
		# alternate bgcolor

		# get warehouse
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		$printStk .= "
			<tr>
				<td>$wh[whname]</td>
				<td>$stk[catname]</td>
				<td>$stk[stkcod]</td>
				<td align='center'>$stk[stkdes]</td>
				<td>$stk[prdcls]</td>
				<td align='right'>".sprint3($stk['units'])."</td>
				<td align='right'>".CUR." ".sprint ($stk['csamt'])."</td>
				<td align='right'>$stk[minlvl]</td>
				<td align='right'>$stk[maxlvl]</td>
				<td align='left'>".CUR." ".sprint($stk['selamt']*$stk['units'])." (".CUR." $stk[selamt] each)</td>
			</tr>";

		#handle totals
		if ($stk['units'] > 0)
			$tot_avail += $stk['units'];
		if ($stk['csamt'] > 0)
			$tot_cost += $stk['csamt'];
		if ($stk['selamt'] > 0)
			$tot_sell += ($stk['selamt']*$stk['units']);
	}

	$printStk .= "
		<tr>
			<td colspan='5'>Totals:</td>
			<td align='right'>".sprint3($tot_avail)."</td>
			<td align='right'>".CUR." ".sprint ($tot_cost)."</td>
			<td colspan='2'></td>
			<td align='left'>".CUR." ".sprint ($tot_sell)."</td>
		</tr>
		</table>";

   	$OUTPUT = $printStk;

	include("xls/temp.xls.php");
	Stream("Report", $OUTPUT);
	return $printStk;

}




?>

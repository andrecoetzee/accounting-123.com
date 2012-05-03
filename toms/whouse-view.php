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

# show current stock
$OUTPUT = printWh ();

require ("../template.php");



# show stock
function printWh ()
{

	# Set up table to display in
	$printWh = "
					<h3>Stores</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Number</th>
							<th>Store</th>
							<th>Stock Account</th>
							<th>Cost Of Sales Account</th>
							<th>Stock Control Account</th>
							<th colspan='2'>Options</th>
						</tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
	$showremove = "";
    $sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
    $whRslt = db_exec ($sql) or errDie ("Unable to retrieve Stores from database.");
	if (pg_numrows ($whRslt) < 1) {
		return "
					<li class='err'>There are no Stores in Cubit.</li>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='whouse-add.php'>Add Store</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='../main.php'>Main Menu</a></td>
						</tr>
					</table>";
	}
	while ($wh = pg_fetch_array ($whRslt)) {
		# get ledger account name(stk)
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$wh[stkacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accstk = pg_fetch_array($accRslt);

		# get ledger account name(cos)
		$sql = "SELECT accname FROM accounts WHERE accid = '$wh[cosacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acccos = pg_fetch_array($accRslt);

		# get ledger account name(cos)
		$sql = "SELECT accname FROM accounts WHERE accid = '$wh[conacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acccon = pg_fetch_array($accRslt);

		db_connect ();
		#check for stock
		$get_stock = "SELECT * FROM stock WHERE whid = '$wh[whid]'";
		$run_stock = db_exec($get_stock);
		if(pg_numrows($run_stock) < 1){
			#store has no stock ... do nothing
			$showremove = "<a href='whouse-rem.php?whid=$wh[whid]'>Remove</a>";
		}else {
			$showremove = "Store has stock allocated to it";
		}

		#check for transactions
		$get_check = "SELECT * FROM inv_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM recinv_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM corders_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM sorders_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM dnote_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM pos_quote_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM pslip_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM purint_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$get_check = "SELECT * FROM recinv_items WHERE whid = '$wh[whid]' LIMIT 1";
		$run_check = db_exec($get_check);
		if(pg_numrows($run_check) > 0){$showremove = "Store has transactions";}

		$printWh .= "
						<tr class='".bg_class()."'>
							<td>$wh[whno]</td>
							<td>$wh[whname]</td>
							<td>$accstk[accname]</td>
							<td>$acccos[accname]</td>
							<td>$acccon[accname]</td>
							<td><a href='whouse-edit.php?whid=$wh[whid]'>Edit</a></td>
							<td>$showremove</td>
						</tr>";
		$i++;
	}

	$printWh .= "
					</table>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='whouse-add.php'>Add Store</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='../main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $printWh;

}


?>
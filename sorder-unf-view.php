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

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	switch ($button) {
		case "allsel":
			$OUTPUT = printSord();
			break;
		case "cancelsel":
			$OUTPUT = cancel();
			break;
	}
} else {
	# show current stock
	$OUTPUT = printSord ();
}

require ("template.php");

##
# Functions
##

# show Sales Orders
function printSord ()
{

	extract ($_REQUEST);

	if (isset($button)) {
		list($button) = array_keys($button);
	}

	# Set up table to display in
	$printSord = "
		<h3>Incomplete Sales Orders</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='view' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Username</th>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Sales Order No.</th>
				<th>Sales Order Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
				<th colspan='3'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM sorders WHERE done = 'n' AND div = '".USER_DIV."' ORDER BY sordid DESC";
	$sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders from database.");
	if (pg_numrows ($sordRslt) < 1) {
		$printSord .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='11'><li>No previous incomplete Sales Orders.</td>
			</tr>
			<tr><td><br></td></tr>";
	}else{
		while ($sord = pg_fetch_array ($sordRslt)) {
			# Get selected customer info
			db_connect();
			$sql = "SELECT * FROM customers WHERE cusnum = '$sord[cusnum]' AND div = '".USER_DIV."'";
			$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
			if (pg_numrows ($custRslt) < 1) {
				$sql = "SELECT * FROM sord_data WHERE sordid = '$sord[sordid]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
				$cust = pg_fetch_array($custRslt);
				$cust['cusname'] = $cust['customer'];
				$cust['surname'] = "";
			}else{
				$cust = pg_fetch_array($custRslt);
			}

			# get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$sord[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				$dept['deptname'] = "<i class='err'>Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			# format date
			$sord['odate'] = explode("-", $sord['odate']);
			$sord['odate'] = $sord['odate'][2]."-".$sord['odate'][1]."-".$sord['odate'][0];

			$cont = "sorder-new.php";
			$bcurr = CUR;
			if($sord['location'] == 'int'){
				$cont = "intsorder-new.php";
				$bcurr = $sord['currency'];
			}

			if (isset($button) && $button == "allsel") {
				$checked = "checked='checked'";
			} else {
				$checked = "";
			}

			$printSord .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$sord[username]</td>
					<td>$dept[deptname]</td>
					<td>$sord[salespn]</td>
					<td>$sord[sordid]</td>
					<td align='center'>$sord[odate]</td>
					<td>$cust[cusname] $cust[surname]</td>
					<td align='right'>$sord[ordno]</td>
					<td>$bcurr $sord[total]</td>
					<td><a href='$cont?sordid=$sord[sordid]&cont=true&letters=&done='>Continue</a></td>
					<td><a href='sorder-unf-cancel.php?sordid=$sord[sordid]'>Cancel</a></td>
					<td><input type='checkbox' name='rem[$sord[sordid]]' value='$sord[sordid]' $checked /></td>
				</tr>";
			$i++;
		}
	}

	// Layout
	$printSord .= "
			<tr>
				<td colspan='20' align='right'>
					<input type='submit' name='button[cancelsel]' value='Cancel Selected' />
					<input type='submit' name='button[allsel]' value='Select All' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("sorder-canc-view.php", "View Cancelled Sales Orders"),
			ql("sorder-new.php", "New Sales Order"),
			ql("customers-new.php", "New Customer")
		);
	return $printSord;

}



function cancel()
{

	extract ($_REQUEST);

	if (isset($rem) && is_array($rem)) {
		foreach ($rem as $sordid) {
			# Get Sales Order info
			db_connect();
			$sql = "SELECT * FROM sorders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
			$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
			if (pg_numrows ($sordRslt) < 1) {
				return "<i class='err'>Not Found</i>";
			}
			$sord = pg_fetch_array($sordRslt);

			db_connect();

			# begin updating
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# Todays date (sql formatted)
			$date = date("Y-m-d");

			# Record (sordid, username, date)
			$sql = "
				INSERT INTO cancelled_sord (
					sordid, deptid, username, date, deptname, div
				) VALUES (
					'$sordid', '$sord[deptid]', '".USER_NAME."', '$date', '$sord[deptname]', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert Sales Order record to Cubit.",SELF);

			# Update the Sales Order (make balance less)
			$sql = "DELETE FROM sorders WHERE sordid = '$sordid' AND done='n' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit.",SELF);

			# Get selected stock in this Sales Order
			db_connect();
			$sql = "SELECT * FROM sorders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);

			# Delete Sales Order items
			$sql = "DELETE FROM sorders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to delete Sales Order items from Cubit.",SELF);

			while($stkd = pg_fetch_array($stkdRslt)){
				# Update stock(alloc - qty)
				$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}

			# commit updating
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}
	}
	return printSord();
}

?>

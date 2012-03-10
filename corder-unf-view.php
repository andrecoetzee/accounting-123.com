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
	case "cancel":
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
	<h3>Incomplete Consignment Orders</h3>
	<form method='post' action='".SELF."'>
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
			<th>Balance</th>
			<th colspan='20'>Options</th>
		</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM corders WHERE done = 'n' AND div = '".USER_DIV."' ORDER BY sordid DESC";
        $sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders from database.");
		if (pg_numrows ($sordRslt) < 1) {
			$printSord = "<li>No previous incomplete Consignment Orders.";
		}else{
			while ($sord = pg_fetch_array ($sordRslt)) {
				# Get selected customer info
				db_connect();

				$cust['cusname'] = $sord['cusname'];
				$cust['surname'] = $sord['surname'];


				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# format date
				$sord['odate'] = explode("-", $sord['odate']);
				$sord['odate'] = $sord['odate'][2]."-".$sord['odate'][1]."-".$sord['odate'][0];

				if (isset($button) && $button == "allsel") {
					$checked = "checked='checked'";
				} else {
					$checked = "";
				}

				$printSord .= "
				<tr bgcolor='$bgColor'>
					<td>$sord[username]</td>
					<td>$sord[deptname]</td>
					<td>$sord[salespn]</td>
					<td>$sord[sordid]</td>
					<td align=center>$sord[odate]</td>
					<td>$cust[cusname] $cust[surname]</td>
					<td align=right>$sord[ordno]</td>
					<td>".CUR." $sord[total]</td>
					<td>".CUR." $sord[balance]</td>
					<td><a href='corder-new.php?sordid=$sord[sordid]&cont=true&letters=&done='>Continue</a></td>
					<td><a href='corder-unf-cancel.php?sordid=$sord[sordid]'>Cancel</a></td>
					<td><input type='checkbox' name='rem[$sord[sordid]]' value='$sord[sordid]' $checked /></td>
				</tr>";
				$i++;
			}
		}

		// Layout
		$printSord .= "
			<tr>
				<td colspan='20' align='right'>
					<input type='submit' name='button[cancel]' value='Cancel Selected' />
					<input type='submit' name='button[allsel]' value='Select All' />
				</td>
			</tr>
		</table>
		</form>
        <p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
	<tr class=datacell><td><a href='corder-canc-view.php'>View Cancelled Consignment Orders</td></tr>
        <tr class=datacell><td><a href='corder-new.php'>New Consignment Order</td></tr>
        <tr class=datacell><td><a href='main.php'>Main Menu</td></tr>
        </table>";

	return $printSord;
}

function cancel()
{
	extract ($_REQUEST);

	if (isset($rem) && is_array($rem)) {
		foreach ($rem as $sordid) {
			# Get Sales Order info
			db_connect();
			$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
			$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
			if (pg_numrows ($sordRslt) < 1) {
				return "<i class=err>Not Found</i>";
			}
			$sord = pg_fetch_array($sordRslt);


			# begin updating
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# Todays date (sql formatted)
			$date = date("Y-m-d");

			db_connect();
			# Record (sordid, username, date)
			$sql = "
			INSERT INTO cancelled_cord(sordid, deptid, username, date,
				deptname, div)
			VALUES ('$sordid', '$sord[deptid]', '".USER_NAME."', '$date',
				'$sord[deptname]', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert Sales Order record to Cubit.",SELF);

			$sql = "DELETE FROM corders WHERE sordid = '$sordid' AND done='n' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit.",SELF);

			# Get selected stock in this Sales Order
			db_connect();
			$sql = "SELECT * FROM corders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit2.",SELF);

			# Delete Sales Order items
			$sql = "DELETE FROM corders_items WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
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

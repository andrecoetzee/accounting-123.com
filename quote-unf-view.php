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
		$OUTPUT = printQuo();
		break;
	case "cancel":
		$OUTPUT = cancel();
		break;
	}
} else {
	# show current stock
	$OUTPUT = printQuo ();
}

require ("template.php");

##
# Functions
##

# show quotes
function printQuo ()
{
	extract ($_REQUEST);

	if (isset($button)) {
		list($button) = array_keys($button);
	}

	# Set up table to display in
	$printQuo = "
	<h3>Incomplete Quotes</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='printquo' />
	<table ".TMPL_tblDflts.">
       	<tr>
			<th>Username</th>
			<th>Department</th>
			<th>Sales Person</th>
			<th>Quote No.</th>
			<th>Quote Date</th>
			<th>Customer Name</th>
			<th>Order No</th>
			<th>Grand Total</th>
			<th>Balance</th>
			<th colspan='5'>Options</th>
		</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM quotes WHERE done = 'n' AND div = '".USER_DIV."' ORDER BY quoid DESC";
        $quoRslt = db_exec ($sql) or errDie ("Unable to retrieve quotes from database.");
		if (pg_numrows ($quoRslt) < 1) {
			$printQuo = "<li>No previous incomplete quotes.";
		}else{
			while ($quo = pg_fetch_array ($quoRslt)) {
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$quo[cusnum]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$sql = "SELECT * FROM quote_data WHERE quoid = '$quo[quoid]' AND div = '".USER_DIV."'";
					$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
					$cust = pg_fetch_array($custRslt);
					$cust['cusname'] = $cust['customer'];
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$quo[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<i class=err>Not Found</i>";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}

				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# format date
				$quo['odate'] = explode("-", $quo['odate']);
				$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];

				if (isset($button) && $button == "allsel") {
					$checked = "checked='checked'";
				} else {
					$checked = "";
				}

				$printQuo .= "
				<tr bgcolor='$bgColor'>
					<td>$quo[username]</td>
					<td>$dept[deptname]</td>
					<td>$quo[salespn]</td>
					<td>$quo[quoid]</td>
					<td align=center>$quo[odate]</td>
					<td>$cust[cusname] $cust[surname]</td>
					<td align=right>$quo[ordno]</td>
					<td>".CUR." $quo[total]</td>
					<td>".CUR." $quo[balance]</td>
					<td><a href='quote-new.php?quoid=$quo[quoid]&cont=true&letters=&done='>Continue</a></td>
					<td><a href='quote-unf-cancel.php?quoid=$quo[quoid]'>Cancel</a></td>
					<td><input type='checkbox' name='rem[$quo[quoid]]' value='$quo[quoid]' $checked/></td>
				</tr>";
				$i++;
			}
		}

		// Layout
		$printQuo .= "
			<tr>
				<td colspan='20' align='right'>
					<input type='submit' name='button[cancel]' value='Cancel Selected' />
					<input type='submit' name='button[allsel]' value='Select All' />
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("quote-canc-view.php", "View Cancelled Quotes"),
			ql("quote-new.php", "New Quote"),
			ql("customers-new.php", "New Customer")
		);

	return $printQuo;
}

function cancel()
{
	extract ($_REQUEST);

	if (isset($rem) && is_array($rem)) {
		foreach ($rem as $quoid) {
			# Get Quote info
			db_connect();
			$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
			$quoRslt = db_exec ($sql) or errDie ("Unable to get Quote information");
			if (pg_numrows ($quoRslt) < 1) {
				return "<i class=err>Not Found</i>";
			}
			$quo = pg_fetch_array($quoRslt);

			# begin updating
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			db_connect();
			# Todays date (sql formatted)
			$date = date("Y-m-d");

			# Record (quoid, username, date)
			$sql = "
			INSERT INTO cancelled_quo (quoid, deptid, username, date,
				deptname, div)
			VALUES ('$quoid', '$quo[deptid]', '".USER_NAME."', '$date',
				'$quo[deptname]', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert Quote record to Cubit.",SELF);

			# Update the Quote (make balance less)
			$sql = "DELETE FROM quotes WHERE quoid = '$quoid' AND done='n' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to remove Quote from Cubit.",SELF);

			# Get selected stock in this Quote
			db_connect();
			$sql = "SELECT * FROM quote_items WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);

			# Delete Quote items
			$sql = "DELETE FROM quote_items WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to delete Quote items from Cubit.",SELF);

			# while($stkd = pg_fetch_array($stkdRslt)){
			#	# Update stock(alloc - qty)
			#	$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]'";
			#	$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			# }

			# commit updating
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}
	}
	return printQuo();
}

?>

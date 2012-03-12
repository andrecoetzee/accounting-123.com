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

$OUTPUT = printSord ($_POST);

require ("template.php");

##
# Functions
##

# show Sales Orders
function printSord ($_POST)
{

	extract ($_POST);

	#nothing to remove ? set var anyway ...
	if(!isset($cancorderid))
		$cancorderid = array();

	#get the entries to remove here ...
	foreach ($cancorderid as $each){

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

					# Get Sales Order info
					db_connect();
					$sql = "SELECT * FROM corders WHERE sordid = '$each' AND accepted != 'c' AND div = '".USER_DIV."'";
					$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
					if (pg_numrows ($sordRslt) < 1) {
						return "<li class='err'>Sales Order Not Found</li>";
					}
					$sord = pg_fetch_array($sordRslt);

					# todays date (sql formatted)
					$date = date("Y-m-d");
			
					# get selected stock in this Sales Order
					db_connect();
					$sql = "SELECT * FROM corders_items  WHERE sordid = '$each' AND div = '".USER_DIV."'";
					$stkdRslt = db_exec($sql);
			
					while($stkd = pg_fetch_array($stkdRslt)){
						# update stock(alloc - qty)
						$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
					}
			
					# remove the Sales Order
					$sql = "DELETE FROM corders WHERE sordid = '$each' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to remove Sales Order from Cubit.",SELF);
			
					#record (sordid, username, date)
					$sql = "INSERT INTO cancelled_cord(sordid, deptid, username, date, deptname, div) VALUES('$each', '$sord[deptid]', '".USER_NAME."', '$date','$sord[deptname]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert Sales Order record to Cubit.",SELF);
			
			/* - End Copying - */
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}

	#select all clicked gets set here ...
	if(isset($all)){
		$ch = "checked";
	}else {
		$ch = "";
	}

		# Set up table to display in
		$printSord = "
				<h3>View Consignment Orders</h3>
				<form action='".SELF."' method='POST'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Department</th>
						<th>Sales Person</th>
						<th>Consignment Order No.</th>
						<th>Date</th>
						<th>Customer Name</th>
						<th>Order No</th>
						<th>Grand Total</th>
						<th colspan='5'>Options</th>
						<th>Remove</th>
					</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM corders WHERE accepted != 'c' AND done = 'y' AND div = '".USER_DIV."' ORDER BY sordid DESC";
		$sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders from database.");
		if (pg_numrows ($sordRslt) < 1) {
			return "
					<li class='err'>No previous Consignment Orders.</li>
					<p>
					<table ".TMPL_tblDflts.">
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='corder-new.php'>New Consignment Order</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='main.php'>Main Menu</td>
						</tr>
					</table>";
		}else{
			while ($sord = pg_fetch_array ($sordRslt)) {
				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# format date
				$sord['odate'] = explode("-", $sord['odate']);
				$sord['odate'] = $sord['odate'][2]."-".$sord['odate'][1]."-".$sord['odate'][0];

				$printSord .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$sord[deptname]</td>
								<td>$sord[salespn]</td>
								<td>$sord[sordid]</td>
								<td align='center'>$sord[odate]</td>
								<td>$sord[cusname] $sord[surname]</td>
								<td align='right'>$sord[ordno]</td>
								<td>".CUR." $sord[total]</td>
								<td><a href='corder-details.php?sordid=$sord[sordid]'>Details</a></td>
								<td><a href='corder-new.php?sordid=$sord[sordid]&cont=1'>Edit</a></td>
								<td><a href='corder-cancel.php?sordid=$sord[sordid]'>Cancel</a></td>
								<td><a target='_blank' href='corder-print.php?sordid=$sord[sordid]'>Print</a></td>
								<td><a href='corder-accept.php?sordid=$sord[sordid]'>Invoice</a></td>
								<td><input type='checkbox' name='cancorderid[]' value='$sord[sordid]' $ch></td>
							</tr>";

				$i++;
			}
		}

		// Layout
		$printSord .= "
						<tr>
							<td colspan='9'></td>
							<td colspan='2'><input type='submit' name='all' value='Select All'></td>
							<td colspan='2'><input type='submit' value='Cancel Selected'></td>
						</tr>
					</table>
					</form>
					<p>
					<table ".TMPL_tblDflts.">
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='corder-new.php'>New Consignment Order</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><a href='main.php'>Main Menu</td>
						</tr>
					</table>";

	return $printSord;
}
?>

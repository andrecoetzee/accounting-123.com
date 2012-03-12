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
require_lib("docman");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
        	$OUTPUT = printInv($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
	# Display default output
	$OUTPUT = slct();
}

require ("template.php");




# Default view
function slct()
{

	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY surname ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'> There are no Customers in Cubit.</li>";
	}
	$custs = "<select name='cusnum'>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	//layout
	$slct = "
		<h3>View Recurring Invoices<h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'  colspan='2'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Customer</td>
				<td>$custs</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>OR</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Input customer account number</td>
				<td><input type='text' name='accnum' size='10'></td>
				<td valign='bottom'><input type='submit' value='View'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>All Customers</td>
				<td><input type='submit' name='all' value='List All &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("rec-invoice-new.php", "New Recurring Invoice"),
			ql("customers-new.php", "New Customer")
		);
	return $slct;

}




# Show invoices
function printInv ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return $confirm;
	}




	$accnum = remval($accnum);
	if(strlen($accnum) > 0) {
		db_conn('cubit');

		$Sl = "SELECT * FROM customers WHERE lower(accno)=lower('$accnum')";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>Invalid account number</li>".slct();
		}

		$cd = pg_fetch_array($Ri);

		$cusnum = $cd['cusnum'];
	}

	$rfromdate = ext_rdate($fromdate);
	$rtodate = ext_rdate($todate);

	# Set up table to display in
	$printInv = "
		<h3>View Recurring invoices. Date Range $rfromdate to $rtodate</h3>
		<form action='rec-invoice-proc.php' method='GET'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
				<th colspan='5'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1 = 0;

	if(isset($all)){
		$sql = "
			SELECT * FROM rec_invoices 
			WHERE odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."' ORDER BY surname";
	} else {
		$sql = "
			SELECT * FROM rec_invoices 
			WHERE odate >= '$fromdate' AND odate <= '$todate' AND cusnum = $cusnum AND div = '".USER_DIV."' ORDER BY surname";
	}
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li class='err'>No Recurring Invoices found for the selected customer.</li><br>";
	}else{
		$counter = 0;
		while ($inv = pg_fetch_array ($invRslt)) {

			$inv['total'] = sprint($inv['total']);
			$inv['balance'] = sprint($inv['balance']);
			$tot1 = $tot1 + $inv['total'];

			# Format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			if (isset($selnum) AND $counter < 1000){
				$ch = "checked";
			}else {
				if(isset($f)) {
					$ch = "checked";
				} else {
					$ch = "";
				}
			}

			$printInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>RI $inv[invid]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>$inv[ordno]</td>
					<td align='right'>".CUR." $inv[total]</td>
					<td><input type='checkbox' name='invids[]' value='$inv[invid]' $ch></td>
					<td><a href='rec-invoice-details.php?invid=$inv[invid]'>Details</a></td>
					<td><a href='rec-invoice-new.php?invid=$inv[invid]&cont=1&letters='>Edit</a></td>
					<td><a href='rec-invoice-run.php?invid=$inv[invid]'>Invoice</a></td>
					<td><a href='rec-invoice-rem.php?invid=$inv[invid]'>Remove</a></td>
				</tr>";
			$i++;
			$counter++;
		}
	}

	if($i > 0 ) {
		$tot1 = sprint($tot1);
		$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6'>Totals:$i</td>
				<td align='right'>".CUR." $tot1</td>
				<td><br></td>
				<td colspan='10'><input type='submit' name='edit' value='Edit Item Prices On Selected'> <input type='submit' value='Process Selected' name='proc'></td>
			</tr>";

		$printInv .= "
				<tr><td><br></td></tr>
			</form>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='view'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='to_day' value='$to_day'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='accnum' value='$accnum'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<input type='hidden' name='all' value=''>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='6'></td>
					<td align='right'></td>
					<td><br></td>
					<td colspan='10'><input type='submit' value='Select All' name='f'> &nbsp; <input type='submit' value='Select 1000' name='selnum'></td>
				</tr>
			</form>";
	}

	$printInv .= "</table>"
		.mkQuickLinks(
			ql("rec-invoice-new.php", "New Recurring Invoice"),
			ql("customers-new.php", "New Customer")
		);
	return $printInv;

}


?>

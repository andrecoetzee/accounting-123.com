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

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
		case "selall":
			$OUTPUT = printInv($_POST);
			break;
	}
} elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printInv($_POST);
			break;
        case "remove":
        	$OUTPUT = removeInv($_POST);
        	break;
        case "write":
        	$OUTPUT = writeRemove ($_POST);
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




##
# Functions
##

function slct()
{

	$slct = "
		<h3>View Incomplete Invoices<h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search' style='width: 100%'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr class='datacell'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
	        <script>document.write(getQuicklinkSpecial());</script>
	        <tr class='datacell'>
	        	<td><a href='main.php'>Main Menu</td>
	        </tr>
		</table>";
	return $slct;

}


# show invoices
function printInv ($_POST)
{

	# get vars
	extract ($_POST);

	if (isset($button)) {
		list($button) = array_keys($button);
	}

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
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
    	    return $confirm;
	}


	# Set up table to display in
	$printInv = "
		<h3>Incomplete Invoices</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='remove'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Username</th>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
				<th colspan='4'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$totgrd = 0;
	$sql = "SELECT * FROM invoices WHERE odate >= '$fromdate' AND odate <= '$todate' AND done = 'n' AND printed ='n' AND div = '".USER_DIV."' ORDER BY invid DESC";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li>No previous incomplete invoices.</li>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {

			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			$cont = "cust-credit-stockinv.php";
			if($inv['location'] == 'int'){
				$cont = "intinvoice-new.php";
			}

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
			$bcurr = CUR;
			if($inv['location'] == 'int'){
				$bcurr = $inv['currency'];
			}
			
			if (isset($button) && $button == "selall") {
				$checked = "checked='checked'";
			} else {
				$checked = "";
			}
			
			$inv['total'] = sprint($inv['total']);
			$printInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[username]</td>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>TI $inv[invid]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>$inv[ordno]</td>
					<td>$bcurr $inv[total]</td>
					<td><a href='$cont?invid=$inv[invid]&cont=true&letters=&done='>Continue</a></td>
					<td><a href='invoice-unf-cancel.php?invid=$inv[invid]'>Cancel</a></td>
					<td><input type='checkbox' name='remids[]' value='$inv[invid]' $checked></td>
				</tr>";
			$totgrd += $inv['total'];
			$i++;
		}
	}

	// Layout
	if($i > 0) {
		$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'><b>Total</b></td>
				<td colspan='7'><b>".CUR." ".sprint ($totgrd)."</b></td>
			<tr>
			<tr>
				<td colspan='11' align='right'>
					<input type='submit' value='Cancel Selected'>
					<input type='submit' name='button[selall]' value='Select All' />
				</td>
			</tr>
		</table>
		</form>";
	}

	$printInv .= "
		<p>
		<table ".TMPL_tblDflts."'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr class='datacell'>
				<td><a href='invoice-canc-view.php'>View Cancelled Invoices</td>
			</tr>
			<tr class='datacell'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}




function removeInv ($_POST)
{

	# get vars
	extract ($_POST);

//print "<pre>";
//var_dump($remids);
//print "</pre>";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach ($remids as $each){
		$v->isOk ($each, "num", 1, 20, "Invalid invoice number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	$details = "
		<h3>Confirm Cancel Incomplete Invoices</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>";

	foreach ($remids as $each){

		# Get invoice info
		db_connect();
		$sql = "SELECT * FROM invoices WHERE invid = '$each' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);
	
		if(strlen($inv['cusnum']) > 0){
			# Get selected customer info
			db_connect();
			$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
			$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
			if (pg_numrows ($custRslt) < 1) {
				$cust['cusname'] = "<li>Not Selected</li>";
				$cust['surname'] = "";
			}else{
				$cust = pg_fetch_array($custRslt);
			}
		}else{
			$cust['cusname'] = "<i>Not Selected</i>";
			$cust['surname'] = "";
		}
	
		# get department
		db_conn("exten");

		$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<i class='err'>Not Found</i>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}

		/* -- Final Layout -- */
		$details .= "
				<input type='hidden' name='remids[]' value='$each'>
				<input type='hidden' name='deptids[]' value='$inv[deptid]'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Details</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Invoice Number</td>
					<td>TI $each</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Department</td>
					<td valign='center'>$dept[deptname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Sales Person</td>
					<td>$inv[salespn]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'>$cust[cusname] $cust[surname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Sub Total</td>
					<td>".CUR." $inv[subtot]</td>
				</tr>
				<tr><td><br></td></tr>
			</table>";
	}



	$details .= "
		<table ".TMPL_tblDflts.">
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td><input type='submit' value='Cancel &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("invoice-canc-view.php", "View Cancelled Invoices"),
			ql("invoice-unf-view.php", "View Incomplete Invoices"),
			ql("cust-credit-stockinv.php", "New Invoice"),
			ql("invoice-view.php", "View Invoices")
		);
	return $details;

}



function writeRemove($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach ($remids as $each => $own){
		$v->isOk ($remids[$each], "num", 1, 20, "Invalid invoice number.");
		$v->isOk ($deptids[$each], "num", 1, 20, "Invalid department number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$err<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	
	$write = "
		<table ".TMPL_tblDflts." width='40%'>
			<tr>
				<th> Incomplete Invoices Cancelled </th>
			</tr>";

foreach ($remids as $each => $own){

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$remids[$each]' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	db_connect();
	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# todays date (sql formatted)
		$date = date("Y-m-d");

		#record (invid, username, date)
		$sql = "INSERT INTO cancelled_inv(invid, deptid, username, date, deptname, div) VALUES('$remids[$each]', '$deptids[$each]', '".USER_NAME."', '$date', '$inv[deptname]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice record to Cubit.",SELF);

		# update the invoice (make balance less)
		$sql = "DELETE FROM invoices WHERE invid = '$remids[$each]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove invoice from Cubit.",SELF);

		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM inv_items WHERE invid = '$remids[$each]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# update stock(alloc - qty)
			$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			if(strlen($stkd['serno']) > 0)
				ext_unresvSer($stkd['serno'], $stkd['stkid']);
		}

		# Delete invoice items
		$sql = "DELETE FROM inv_items WHERE invid = '$remids[$each]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to delete invoice items from Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	/* -- Final Layout -- */
	$write .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoice No. <b>$remids[$each]</b> has been cancelled.</td>
		</tr>";
}

	$write .= "</table><br>"
				.mkQuickLinks(
					ql("invoice-canc-view.php", "View Cancelled Invoices"),
					ql("invoice-unf-view.php", "View Incomplete Invoices"),
					ql("cust-credit-stockinv.php", "New Invoice"),
					ql("invoice-view.php", "View Invoices")
				);
	return $write;

}


?>
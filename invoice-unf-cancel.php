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

# get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			# decide what to do
			if (isset($HTTP_GET_VARS["invid"])) {
				$OUTPUT = details($HTTP_GET_VARS);
			} else {
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
			}
	}
} else {
	# decide what to do
	if (isset($HTTP_GET_VARS["invid"])) {
		$OUTPUT = details($HTTP_GET_VARS);
	} else {
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# get templete
require("template.php");




# details
function details($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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



	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
			$cust['cusname'] = "<li>Not Selected";
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
	$details = "
		<h3>Confirm Cancel Incomplete Invoice</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='invid' value='$invid'>
			<input type='hidden' name='deptid' value='$inv[deptid]'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice Number</td>
				<td>TI $invid</td>
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


# details
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");

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

	# Get invoice info
	db_connect();

	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
		$sql = "INSERT INTO cancelled_inv (invid, deptid, username, date, deptname, div) VALUES ('$invid', '$deptid', '".USER_NAME."', '$date', '$inv[deptname]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice record to Cubit.",SELF);

		# update the invoice (make balance less)
		$sql = "DELETE FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove invoice from Cubit.",SELF);

		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# update stock(alloc - qty)
			$sql = "UPDATE stock SET alloc = (alloc - '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			if(strlen($stkd['serno']) > 0)
				ext_unresvSer($stkd['serno'], $stkd['stkid']);
		}

		# Delete invoice items
		$sql = "DELETE FROM inv_items WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to delete invoice items from Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	/* -- Final Layout -- */
	$write = "
		<table ".TMPL_tblDflts." width='40%'>
			<tr>
				<th> Incomplete Invoice Cancelled </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice No. <b>$invid</b> has been cancelled.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
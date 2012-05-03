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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			# decide what to do
			if (isset($_GET["sordid"])) {
				$OUTPUT = details($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
	}
} else {
	# decide what to do
	if (isset($_GET["sordid"])) {
		$OUTPUT = details($_GET);
	} else {
		$OUTPUT = "<li class=err>Invalid use of module.";
	}
}

# get templete
require("template.php");

# details
function details($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($sordid, "num", 1, 20, "Invalid Consignment Order number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Sales Order info
	db_connect();
	$sql = "SELECT * FROM corders WHERE sordid = '$sordid' AND div = '".USER_DIV."'";
	$sordRslt = db_exec ($sql) or errDie ("Unable to get Sales Order information");
	if (pg_numrows ($sordRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$sord = pg_fetch_array($sordRslt);

	/* -- Final Layout -- */
	$details = "<h3>Confirm Cancel Incomplete Consignment Order</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=sordid value='$sordid'>
	<input type=hidden name=deptid value='$sord[deptid]'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
		<tr><th colspan=2>Details</th></tr>
		<tr class='bg-odd'><td>Consignment Order Number</td><td>$sordid</td></tr>
		<tr class='bg-even'><td>Department</td><td valign=center>$sord[deptname]</td></tr>
		<tr class='bg-odd'><td>Sales Person</td><td>$sord[salespn]</td></tr>
		<tr class='bg-even'><td>Customer</td><td valign=center>$sord[cusname] $sord[surname]</td></tr>
		<tr class='bg-odd'><td>Sub Total</td><td>".CUR." $sord[subtot]</td></tr>
		<tr><td><br></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Cancel &raquo'></td></tr>
	</table>
	</form>
	<p>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='corder-canc-view.php'>View Cancelled Consignment Orders</td></tr>
		<tr class='bg-odd'><td><a href='corder-unf-view.php'>View Incomplete Consignment Orders</td></tr>
		<tr class='bg-odd'><td><a href='corder-new.php'>New Consignment Order</a></td></tr>
		<tr class='bg-odd'><td><a href='corder-view.php'>View Consignment Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $details;
}

# details
function write($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($sordid, "num", 1, 20, "Invalid Sales Order number.");
	$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$err<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

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
		$sql = "INSERT INTO cancelled_cord(sordid, deptid, username, date, deptname, div) VALUES('$sordid', '$deptid', '".USER_NAME."', '$date', '$sord[deptname]', '".USER_DIV."')";
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


	/* -- Final Layout -- */
	$write = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
		<tr><th> Incomplete Consignment Order Cancelled </th></tr>
		<tr class='bg-odd'><td>Consignment Order No. <b>$sordid</b> has been cancelled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='corder-unf-view.php'>View Incomplete Consignment Orders</td></tr>
		<tr class='bg-odd'><td><a href='corder-new.php'>New Consignment Order</a></td></tr>
		<tr class='bg-odd'><td><a href='corder-view.php'>View Consignment Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

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
			if (isset($_GET["quoid"])) {
				$OUTPUT = details($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
	}
} else {
	# decide what to do
	if (isset($_GET["quoid"])) {
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid Quote number.");

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

	# Get Quote info
	db_connect();
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get Quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	/* -- Final Layout -- */
	$details = "<h3>Confirm Cancel Incomplete Quote</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=quoid value='$quoid'>
	<input type=hidden name=deptid value='$quo[deptid]'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
		<tr><th colspan=2>Details</th></tr>
		<tr class='bg-odd'><td>Quote Number</td><td>$quoid</td></tr>
		<tr class='bg-even'><td>Department</td><td valign=center>$quo[deptname]</td></tr>
		<tr class='bg-odd'><td>Sales Person</td><td>$quo[salespn]</td></tr>
		<tr class='bg-even'><td>Customer</td><td valign=center>$quo[cusname] $quo[surname]</td></tr>
		<tr class='bg-odd'><td>Sub Total</td><td>".CUR." $quo[subtot]</td></tr>
		<tr><td><br></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Confirm &raquo'></td></tr>
	</table>
	</form>
	<p>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='quote-canc-view.php'>View Cancelled Quotes</td></tr>
		<tr class='bg-odd'><td><a href='quote-unf-view.php'>View Incomplete Quotes</td></tr>
		<tr class='bg-odd'><td><a href='quote-new.php'>New Quote</a></td></tr>
		<tr class='bg-odd'><td><a href='quote-view.php'>View Quotes</a></td></tr>
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid Quote number.");
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
		$sql = "INSERT INTO cancelled_quo(quoid, deptid, username, date, deptname, div) VALUES('$quoid', '$deptid', '".USER_NAME."', '$date', '$quo[deptname]', '".USER_DIV."')";
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

	/* -- Final Layout -- */
	$write = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
		<tr><th> Incomplete Quote Cancelled </th></tr>
		<tr class='bg-odd'><td>Quote No. <b>$quoid</b> has been cancelled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='quote-unf-view.php'>View Incomplete Quotes</td></tr>
		<tr class='bg-odd'><td><a href='quote-new.php'>New Quote</a></td></tr>
		<tr class='bg-odd'><td><a href='quote-view.php'>View Quotes</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

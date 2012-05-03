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
			if (isset($_GET["calloutid"])) {
				$OUTPUT = details($_GET);
			} else {
				$OUTPUT = "<li class=err>Invalid use of module.";
			}
	}
} else {
	# decide what to do
	if (isset($_GET["calloutid"])) {
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
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutid, "num", 1, 20, "Invalid Quote number.");

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
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to get Quote information");
	if (pg_numrows ($calloutRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$callout = pg_fetch_array($calloutRslt);

	/* -- Final Layout -- */
	$details = "
		<h3>Confirm Cancel Incomplete Call Out Documents</h3>
		<form action='".SELF."' method='post'>
		<input type='hidden' name='key' value='write'>
		<input type='hidden' name='calloutid' value='$calloutid'>
		<input type='hidden' name='deptid' value='$callout[deptid]'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2>Details</th></tr>
			<tr class='bg-odd'><td>Job Number</td><td>$calloutid</td></tr>
			<tr class='bg-even'><td>Department</td><td valign=center>$callout[deptname]</td></tr>
			<tr class='bg-odd'><td>Call Out Person</td><td>$callout[calloutp]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$callout[cusname] $callout[surname]</td></tr>
			<tr><td><br></td></tr>
			<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Confirm &raquo'></td></tr>
		</table>
		</form>
		<p>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='callout-canc-view.php'>View Cancelled Call Out Documents</td></tr>
			<tr class='bg-odd'><td><a href='callout-unf-view.php'>View Incomplete Call Out Documents</td></tr>
			<tr class='bg-odd'><td><a href='callout-new.php'>New Call Out Document</a></td></tr>
			<tr class='bg-odd'><td><a href='callout-view.php'>View Call Out Documents</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $details;
}

# details
function write($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutid, "num", 1, 20, "Invalid Callout  number.");
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
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to get callout document information");
	if (pg_numrows ($calloutRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$callout = pg_fetch_array($calloutRslt);



# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		db_connect();
		# Todays date (sql formatted)
		$date = date("Y-m-d");

		# Record (calloutid, username, date)
		$sql = "INSERT INTO cancelled_callout(calloutid, deptid, username, date, deptname, div) VALUES('$calloutid', '$deptid', '".USER_NAME."', '$date', '$callout[deptname]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert call out document record to Cubit.",SELF);

		# Update the Call Out Document (make balance less)
		$sql = "DELETE FROM callout_docs WHERE calloutid = '$calloutid' AND done='n' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove call out document from Cubit.",SELF);

		# Get selected stock in this call out document
		db_connect();
		$sql = "SELECT * FROM callout_docs_items WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		# Delete Call Out items
		$sql = "DELETE FROM callout_docs_items WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to delete call out document items from Cubit.",SELF);

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
				<tr><th> Incomplete Call Out Document Cancelled </th></tr>
				<tr class='bg-odd'><td>Job No. <b>$calloutid</b> has been cancelled.</td></tr>
			</table>
			<p>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><th>Quick Links</th></tr>
				<tr class='bg-odd'><td><a href='callout-unf-view.php'>View Incomplete Call Out Documents</td></tr>
				<tr class='bg-odd'><td><a href='callout-new.php'>New Call Out Document</a></td></tr>
				<tr class='bg-odd'><td><a href='callout-view.php'>View Call Out Documents</a></td></tr>
				<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
			</table>";

	return $write;
}
?>

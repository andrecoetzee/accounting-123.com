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
	$v->isOk ($calloutid, "num", 1, 20, "Invalid call out document number.");

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

	# Get quote info
	db_connect();
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to get call out document information");
	if (pg_numrows ($calloutRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$callout = pg_fetch_array($calloutRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><tr>";
		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM callout_docs_items  WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			if($stkd['account']==0) {
			
				# get warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
	
				# get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);
				
			} else {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			# put in product
			$products .="<tr class='bg-odd'><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td></tr>";
	}
	$products .= "</table>";

	/* -- Final Layout -- */
	$details = "<center><h3>Cancel Call Out Documents</h3>
	<form action='".SELF."' method='post'>
	<input type='hidden' name='key' value='write'>
	<input type='hidden' name='calloutid' value='$calloutid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border='0' width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=40%>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$callout[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$callout[cusname] $callout[surname]</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($callout['cusaddr'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center>$callout[cordno]</td></tr>
			<tr class='bg-odd'><td>Customer Vat Number</td><td>$callout[cusvatno]</td></tr>
			<tr><th colspan=2 valign=top>Comments</th></tr>
			<tr class='bg-even'><td colspan=2 align=center>".nl2br($callout['comm'])."</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Quote Details </th></tr>
			<tr class='bg-even'><td>Job No.</td><td valign=center>$callout[calloutid]</td></tr>
			<tr class='bg-even'><td>Call Out Person</td><td valign=center>$callout[calloutp]</td></tr>
			<tr class='bg-odd'><td>Service Date</td><td valign=center>$callout[odate]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<p>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='callout-new.php'>New Call Out Document</a></td></tr>
			<tr class='bg-odd'><td><a href='callout-view.php'>View Call Out Documents</a></td></tr>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='Write'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutid, "num", 1, 20, "Invalid call out document number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get call out document info
	db_connect();
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND accepted != 'c' AND div = '".USER_DIV."'";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to get call out document information");
	if (pg_numrows ($calloutRslt) < 1) {
		return "<li class=err>Call Out Document Not Found</li>";
	}
	$callout = pg_fetch_array($calloutRslt);

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$callout[cusnum]' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($custRslt) < 1) {
		$sql = "SELECT * FROM callout_data WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
		$cust = pg_fetch_array($custRslt);
		$cust['cusname'] = $cust['customer'];
		$cust['surname'] = "";
		$cust['addr1'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);
	}

	db_connect();
	/* - Start Copying - */
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# todays date (sql formatted)
		$date = date("Y-m-d");

		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM callout_docs_items  WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		# remove the Quote
		$sql = "DELETE FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Call Out documents from Cubit.",SELF);

		#record (calloutid, username, date)
		$sql = "INSERT INTO cancelled_callout(calloutid, deptid, username, date, div) VALUES('$calloutid', '$callout[deptid]', '".USER_NAME."', '$date', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert Call Out Document record to Cubit.",SELF);


	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	/* - End Copying - */

	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Call Out Document Cancelled</th></tr>
		<tr class='bg-even'><td>Call Out Document for customer <b>$cust[cusname] $cust[surname]</b> has been canceled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='callout-view.php'>View Call Out Documents</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

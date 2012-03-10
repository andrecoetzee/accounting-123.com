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
            case "slctcust":
				$OUTPUT = slctCust($HTTP_POST_VARS);
				break;

			case "print":
				$OUTPUT = printInv($HTTP_POST_VARS);
				break;

            default:
				$OUTPUT = view();
			}
} else {
	$OUTPUT = view();
}

# get templete
require("template.php");

# Default view
function view()
{

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}


	//layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=slctcust>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>Statement</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters maxlength=5></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        return $view;
}

# Default view
function view_err($HTTP_POST_VARS, $err = "")
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}


	//layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=slctcust>
		<input type=hidden name=cussel value=cussel>
		<tr><th colspan=2>Statement</th></tr>
		<tr><td colspan=2>$err</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Department</td><td valign=center>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>First Letters of customer</td><td valign=center><input type=text size=5 name=letters value='$letters' maxlength=5></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        return $view;
}

# Default view
function slctCust($HTTP_POST_VARS)
{

	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	$v->isOk ($letters, "string", 0, 5, "Invalid First 3 Letters.");

	# no done button
	$done = "";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return view_err($HTTP_POST_VARS, $err);
	}

		db_connect();
		# Query server for customer info
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$deptid' AND lower(surname) LIKE lower('$letters%') ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($HTTP_POST_VARS, $err);
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='-S' disabled selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}

		//layout
		$slct = "<br><br><form action='".SELF."' method=post name=form>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
			<input type=hidden name=key value=print>
			<tr><th colspan=2>Statement</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Customer</td><td valign=center>$customers</td></tr>
			<tr><td><br></td></tr>
			<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='customers-new.php'>New Customer</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

        return $slct;
}

# show invoices
function printInv ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return view_err($HTTP_POST_VARS, $err);
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.";
	}
	$cust = pg_fetch_array($custRslt);

	# connect to database
	db_connect ();
	$fdate = "01-".date("m")."-".date("Y");
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND date >= '$fdate' ORDER BY date ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4>No previous invoices.</td></tr>";
	}else{
		$i = 0;
		while ($st = pg_fetch_array ($stRslt)) {
			# Get selected customer info
			db_connect();
			$sql = "SELECT * FROM customers WHERE cusnum = '$st[cusnum]'";
			$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
			if (pg_numrows ($custRslt) < 1) {
				$sql = "SELECT * FROM inv_data WHERE invid = '$st[invid]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information data");
				$cust = pg_fetch_array($custRslt);
				$cust['cusname'] = $cust['customer'];
				$cust['surname'] = "";
			}else{
				$cust = pg_fetch_array($custRslt);
			}

			# Get invoice info
			db_connect();
			$sql = "SELECT * FROM invoices WHERE invid = '$st[invid]'";
			$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
			if (pg_numrows ($invRslt) < 1) {
				$stmnt .= "<tr><td colspan=4 class=err>Invoice No. <b>$st[invid]</b> note found.</td></tr>";
				$i++;
				continue;
			}
			$inv = pg_fetch_array($invRslt);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# format date
			$st['date'] = explode("-", $st['date']);
			$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];

			$stmnt .= "<tr bgcolor='$bgColor'><td>$st[date]</td><td>$st[invid]</td><td>$st[type]</td><td>".CUR." $st[amount]</td></tr>";

			# keep track of da totals
			$totout += $st['amount'];
			$i++;
		}
	}

	$balbf = ($cust['balance'] - $totout);

	// Layout
	$printInv = "
	<h3>Monthly Statement</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr bgcolor='".TMPL_tblDataColor1."'><th>Account No.</th><td>$cust[accno]</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Customer : </td><td>$cust[cusname] $cust[surname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Balance Brought Forward</td><td>".CUR." $balbf</td>
		<tr><td><br></td></tr>
		<tr><th>Date</th><th>Invoice No.</th><th>Details</th><th>Amount</th></tr>
		$stmnt
		<tr><td><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total Outstanding</b></td><td colspan=2>".CUR." $totout</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='cust-credit-stockinv.php'>New Invoice</td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
	</table>";

	return $printInv;
}
?>

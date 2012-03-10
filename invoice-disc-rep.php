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
				$OUTPUT = printDisc($HTTP_POST_VARS);
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
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
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
	$view = "
			<br><br>
			<form action='".SELF."' method='POST' name='form'>
			<table ".TMPL_tblDflts." width='400'>
				<input type='hidden' name='key' value='slctcust'>
				<input type='hidden' name='cussel' value='cussel'>
				<tr>
					<th colspan='2'>Invoice Discounts</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Department</td>
					<td valign='center'>$depts</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>First Letters of customer</td>
					<td valign='center'><input type='text' size='5' name='letters' maxlength='5'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td></td>
					<td valign='center' align='right'><input type='submit' value='Continue &raquo'></td>
				</tr>
			</table>
			</form>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='sales-reports.php'>Sales Reports</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

        return $view;
}

# Default view
function view_err($HTTP_POST_VARS, $err = "")
{
	# get vars
	extract ($HTTP_POST_VARS);

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.";
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
	$view = "
			<br><br>
			<form action='".SELF."' method='POST' name='form'>
			<table ".TMPL_tblDflts." width='400'>
				<input type='hidden' name='key' value='slctcust'>
				<tr>
					<th colspan='2'>Invoice Discounts</th>
				</tr>
				<tr>
					<td colspan='2'>$err</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Department</td>
					<td valign='center'>$depts</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>First Letters of customer</td>
					<td valign='center'><input type='text' size='5' name='letters' value='$letters' maxlength='5'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
					<td valign='center'><input type='submit' value='Continue &raquo'></td>
				</tr>
			</table>
			</form>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='sales-reports.php'>Sales Reports</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
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
		$sql = "SELECT * FROM customers WHERE deptid = '$deptid' AND lower(surname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$err = "<li class=err>No customer names starting with <b>$letters</b> in database.";
			return view_err($HTTP_POST_VARS, $err);
		}else{
			# connect to database
			db_connect ();
			$discs = "";
			$gtottrad = 0;
			$gtotitems = 0;
			$gtotdel = 0;
			$ginvtot = 0;

			while($cust = pg_fetch_array($custRslt)){
				$tottrad = 0;
				$totitems = 0;
				$totdel = 0;
				$invtot=0;
				$i = 0;

				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# Query server
				$sql = "SELECT * FROM inv_discs WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."' ORDER BY inv_date DESC";
				$discRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
				if (pg_numrows ($discRslt) < 1) {
					$discs .= "<tr bgcolor='$bgColor'><td>$cust[cusname] $cust[surname]</td><td>0</td><td>".CUR." 0</td><td>".CUR." 0</td><td>".CUR." 0</td></tr>";
				}else{
					$invno = pg_numrows ($discRslt);
					while ($disc = pg_fetch_array ($discRslt)) {
						# keep track of da totals
						$tottrad += $disc['traddisc'];
						$totitems += $disc['itemdisc'];
						$totdel += $disc['delchrg'];
						$invtot += $disc['total'];


						# keep track of da totals for all
						$gtottrad += $disc['traddisc'];
						$gtotitems += $disc['itemdisc'];
						$gtotdel += $disc['delchrg'];
						$ginvtot += $disc['total'];
					}

					if($invtot == "0"){
						$per = "0";
					}else {
						$per=sprint($tottrad/$invtot*100);
					}

					vmoney($tottrad);
					vmoney($totitems);
					vmoney($totdel);

					$discs .= "
						<tr bgcolor='$bgColor'>
							<td>$cust[cusname] $cust[surname]</td>
							<td>$invno</td>
							<td>".CUR." $tottrad</td>
							<td>".CUR." $totitems</td>
							<td>".CUR." $totdel</td>
							<td>".CUR." ".sprint($invtot)."</td>
							<td>$per%</td>
						</tr>";
					$i++;
				}
			}
		}

	if($ginvtot == "0"){
		$totper = "0";
	}else {
		$totper=sprint($gtottrad/$ginvtot*100);
		$ginvtot = sprint ($ginvtot);
	}

	vmoney($gtottrad);
	vmoney($gtotitems);
	vmoney($gtotdel);
	vmoney($invtot);

	// Layout
	$printInv = "
	<h3>Invoice Discounts</h3>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Customer</th>
		<th>Number of Invoices</th>
		<th>Trade Discount</th>
		<th>Total Items Discount</th>
		<th>Total Delivery Charges</th>
		<th>Invoices Total(ex VAT)</th>
		<th>% Trade Discount</tr>
	$discs
	".TBL_BR."
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td colspan='2'><b>Total</b></td>
		<td>".CUR." $gtottrad</td>
		<td>".CUR." $gtotitems</td>
		<td>".CUR." $gtotdel</td>
		<td>".CUR." $ginvtot</td>
		<td>$totper%</td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='sales-reports.php'>Sales Reports</td></tr>
		<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
	</table>";

	return $printInv;
}
?>

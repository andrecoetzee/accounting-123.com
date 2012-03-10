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
require_lib("docman");

if (isset ($_POST["key"])){
	switch ($_POST["key"]){
		case "view":
			$OUTPUT = printInvoice ();
			break;
		default:
			$OUTPUT = slct();
	}
}else {
	$OUTPUT = slct ();
}

$OUTPUT .= "<p>".mkQuickLinks(
	ql ("nons-invoice-new.php","New Non-Stock Invoices")
);

require ("template.php");



function slct()
{

	extract($_GET);

	db_connect ();

	$cust_arr = array ();
	$ncust_arr = array ();

	#get list of all customers
	$get_cust = "SELECT surname FROM customers ORDER by surname";
	$run_cust = db_exec($get_cust) or errDie("Unable to get customers information.");
	if(pg_numrows($run_cust) > 0)
		while ($temp = pg_fetch_array($run_cust))
			$cust_arr[] = $temp['surname'];
	
	#now get the non stock invoices customers
	$get_ncust = "SELECT distinct(cusname) FROM nons_invoices ORDER BY cusname";
	$run_ncust = db_exec($get_ncust) or errDie("Unable to get customers information.");
	if(pg_numrows($run_ncust) > 0)
		while ($temp = pg_fetch_array($run_ncust))
			$ncust_arr[] = $temp['cusname'];

	$allcust_arr = array_merge($cust_arr,$ncust_arr);
	$allcust_arr = array_unique($allcust_arr);
	ksort($allcust_arr);
	
	#make customer drop ...
	$cust_drop = "<select name='customer'>";
	$cust_drop .= "<option value='0'>All</option>";
	foreach ($ncust_arr as $each){
		$cust_drop .= "<option value='$each'>$each</option>";
	}
	$cust_drop .= "</select>";

	$slct = "
		<h3>View Incomplete Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			".(isset($mode)?"<input type='hidden' name='mode' value='$mode'>":"")."
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
			</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
			<tr>
				<th>Select Customer</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$cust_drop</td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slct;

}

# show
function printInvoice ()
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	$fromdate = mkdate($from_year, $from_month, $from_day);
	$todate = mkdate($to_year, $to_month, $to_day);

	$v->isOk ($fromdate, "date", 1, 1, "Invalid from date.");
	$v->isOk ($todate, "date", 1, 1, "Invalid to date.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return $err;
	}

	# Set up table to display in
	$printOrd = "
		<center>
		<h3>View Incomplete Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts.">
		<form action='invoice-proc.php' method='GET'>
			<tr>
				<th>Invoice Num</th>
				<th>Proforma Inv No.</th>
				<th>Invoice Date</th>
				<th>Customer</th>
				<th>Total</th>
				<th colspan='2'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$cust_search = "";
	if(isset ($customer) AND ($customer != "0")){
		$cust_search = "AND cusname = '$customer'";
	}

	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND sdate >= '$fromdate' 	AND sdate <= '$todate' AND div = '".USER_DIV."' AND done = 'n' $cust_search ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "<li class='err'> There are no incomplete non stock invoices found.</li>";
	}

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['sdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		if ($nonstks["xrate"] == 0.00) {
			$tot_subtot += $nonstks["subtot"];
			$tot_total += $nonstks["total"];
		} else {
			$tot_subtot += $nonstks["subtot"] * $nonstks["xrate"];
			$tot_total += $nonstks["total"] * $nonstks["xrate"];
		}

		# calculate the Sub-Total

		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}

		if (isset($nonstks['multiline']) AND ($nonstks['multiline'] == "yes"))
			$edit = "nons-multiline-invoice-new.php";
		else 
			$edit = "nons-invoice-new.php";

		$cur = CUR;

		$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$nonstks[docref]</td>
				<td>$date</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>$cur $nonstks[total]</td>
				<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Continue</a></td>
			</tr>";
		$i++;
	}

	$tot_total = sprint($tot_total);

	$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>Totals</td>
				<td align='right'>".CUR." $tot_total</td>
			</tr>
		</table>";
	return $printOrd;

}



?>

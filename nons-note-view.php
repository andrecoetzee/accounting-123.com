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


if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			require_lib("docman");
			$OUTPUT = printPurch ($_POST);
			break;
	 case "export":
			$OUTPUT = export ($_POST);
			break;
		default:
			$OUTPUT = slct ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct ();
}

require ("template.php");

# Default view
function slct()
{
	//layout
	$slct = "
			<h3>View Returned Non-Stock Orders</h3>
			<table ".TMPL_tblDflts." width='580'>
			<form action='".SELF."' method=post name=form>
				<input type='hidden' name='key' value='view'>
				<tr>
					<th colspan='2'>By Date Range</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center' colspan='2'>
						".mkDateSelect("from",date("Y"),date("m"),"01")."
						&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
						".mkDateSelect("to")."
					</td>
				</tr>
				<tr>
					<td valign='bottom' colspan='2'><input type='submit' value='Search'></td>
				</tr>
			</form>
			</table>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='nons-purchase-new.php'>New Order</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-view.php'>View Stock</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
			return $slct;

}


# show stock
function printPurch ($_POST)
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
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
				<center>
				<h3>Returned Non-Stock Orders</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Purchase No.</th>
						<th>Purchase Date</th>
						<th>Supplier</th><th>Sub Total</th>
						<th>Delivery Charges</th>
						<th>Vat</th>
						<th>Total</th>
						<th>Delevery Reference No.</th>
						<th>Documents</th>
						<th colspan='5'>Options</th>
					</tr>";

	# connect to database
	db_conn ("cubit");

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$sql = "SELECT * FROM nons_purchasesn WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
   	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Non-Stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li> There are no returned Non-Stock Orders found.
			<p>
			<table ".TMPL_tblDflts.">
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr class='".bg_class()."'>
					<td><a href='stock-report.php'>Stock Control Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-view.php'>View Stock</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {
		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# calculate the Sub-Total
		$stkp['total']=sprint($stkp['total']);
		$stkp['shipchrg']=sprint($stkp['shipping']);
		$subtot = ($stkp['subtot']);
		$subtot=sprint($subtot);
		$tot1=sprint(($tot1+$subtot));
		$tot2=sprint(($tot2+$stkp['shipchrg']));
		$tot3=sprint(($tot3+$stkp['total']));
		$vat=($stkp['vat']);
		$tot4=sprint($tot4+$vat);
		# Get documents
		$docs = doclib_getdocs("npur", $stkp['purnum']);


		$printOrd .= "
					<tr class='".bg_class()."'>
						<td>$stkp[purnum]</td>
						<td>$date</td>
						<td>$stkp[supplier]</td>
						<td align='right'>".CUR." $subtot</td>
						<td align='right'>".CUR." $stkp[shipchrg]</td>
						<td align='right'>".CUR." $vat</td>
						<td align='right'>".CUR." $stkp[total]</td>
						<td>$stkp[refno]</td>
						<td>$docs</td>";

		$printOrd .= "
						<td><a href='nons-note-det.php?id=$stkp[id]'>Details</a></td>
					</tr>";
		
		$i++;
	}


	$printOrd .= "
				<tr class='".bg_class()."'>
					<td colspan='3'>Totals</td>
					<td align='right'>".CUR." $tot1</td>
					<td align='right'>".CUR." $tot2</td>
					<td align='right'>".CUR." $tot4</td>
					<td align='right'>".CUR." $tot3</td>
				</tr>
				<tr><td><br></td></tr>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='export'>
					<input type='hidden' name='fday' value='$from_day'>
					<input type='hidden' name='fmon' value='$from_month'>
					<input type='hidden' name='fyear' value='$from_year'>
					<input type='hidden' name='today' value='$to_day'>
					<input type='hidden' name='tomon' value='$to_month'>
					<input type='hidden' name='toyear' value='$to_year'>
					<tr>
						<td colspan='4'><input type='submit' value='Export to Spreadsheet'></td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr><td><br></td></tr>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $printOrd;

}


function export ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
				<center>
				<h3>Returned Non-Stock Orders</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Purchase No.</th>
						<th>Purchase Date</th>
						<th>Supplier</th>
						<th>Sub Total</th>
						<th>Delivery Charges</th>
						<th>Vat</th>
						<th>Total</th>
						<th>Delivery Reference No.</th>
					</tr>";

	# connect to database
	db_conn ("cubit");

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$sql = "SELECT * FROM nons_purchasesn WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
   	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Non-Stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
				<li> There are no returned Non-Stock Orders found.
				<p>
				<table ".TMPL_tblDflts.">
					<tr><td><br></td></tr>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='nons-purchase-new.php'>New Non-Stock Order</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
					<tr class='".bg_class()."'>
						<td><a href='stock-report.php'>Stock Control Reports</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='stock-view.php'>View Stock</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {
		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# calculate the Sub-Total
		$stkp['total']=sprint($stkp['total']);
		$stkp['shipchrg']=sprint($stkp['shipping']);
		$subtot = ($stkp['subtot']);
		$subtot=sprint($subtot);
		$tot1=sprint(($tot1+$subtot));
		$tot2=sprint(($tot2+$stkp['shipchrg']));
		$tot3=sprint(($tot3+$stkp['total']));
		$vat=($stkp['vat']);
		$tot4=sprint($tot4+$vat);
		# Get documents
		$docs = "";


		$printOrd .= "
					<tr>
						<td>$stkp[purnum]</td>
						<td>$date</td>
						<td>$stkp[supplier]</td>
						<td align='right'>".CUR." $subtot</td>
						<td align='right'>".CUR." $stkp[shipchrg]</td>
						<td align='right'>".CUR." $vat</td>
						<td align='right'>".CUR." $stkp[total]</td>
						<td>$stkp[refno]</td>";

		$printOrd .= "</tr>";
		
		$i++;
	}

	$printOrd .= "
				<tr>
					<td colspan='3'>Totals</td>
					<td align='right'>".CUR." $tot1</td>
					<td align='right'>".CUR." $tot2</td>
					<td align='right'>".CUR." $tot4</td>
					<td align='right'>".CUR." $tot3</td>
				</tr>
			</table>";

	$OUTPUT=$printOrd;
	
	include("xls/temp.xls.php");
	Stream("Purchases", $OUTPUT);

	return $printOrd;
}


?>
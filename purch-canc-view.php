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


// Merge post vars and get vars
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
        	require_lib("docman");
			$OUTPUT = printPurch ($_POST);
			break;
		case "export":
			$OUTPUT = export ($_POST);
			break;
		case "delete_confirm":
			$OUTPUT = delete_confirm($_POST);
			break;
		case "delete_write":
			$OUTPUT = delete_write($_POST);
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

	db_connect ();

	$supplier_drop = "<select name='supplier'>";
	$supplier_drop .= "<option value='0'>All Suppliers</option>";
	$get_supps = "SELECT * FROM suppliers WHERE blocked IS NULL or blocked != 'yes' ORDER BY supname";
	$run_supps = db_exec ($get_supps) or errDie ("Unable to get supplier information");
	if (pg_numrows ($run_supps) > 0){
		while ($sarr = pg_fetch_array ($run_supps)){
			$supplier_drop .= "<option value='$sarr[supid]'>$sarr[supname]</option>";
		}
	}
	$supplier_drop .= "</select>";

	// Layout
	$slct = "
		<h3>View Canceled Orders</h3>
		<table ".TMPL_tblDflts." width='460'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr>
				<th>Supplier(s)</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$supplier_drop</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='purchase-new.php'>New Order</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-report.php'>Stock Control Reports</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



# show stock
function printPurch ($_POST)
{

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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
		<center>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>
		<h3>View Stock Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>No.</th>
				<th>Order No.</th>
				<th>Supp Inv No.</th>
				<th>Order Date</th>
				<th>Supplier</th>
				<th>Sub Total</th>
				<th>Delivery Charges</th>
				<th>Vat</th>
				<th>Total</th>
				<th>Documents</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$supsql = "";
	if (isset ($supplier) AND $supplier != 0){
		$supsql = " AND supid = '$supplier'";
	}

	$sql = "SELECT * FROM cancelled_purch WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' $supsql ORDER BY pdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock purchases from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li class='err'> No stock Orders found</li><br>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='purchase-new.php'>New Order</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='purchase-view.php'>View Purchases</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}else{
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			# Date format
			$date = explode("-", $stkp['pdate']);
			$date = $date[2]."-".$date[1]."-".$date[0];

			# Calculate the Sub-Total
			$stkp['total'] = sprint($stkp['total']);
			$stkp['shipchrg'] = sprint($stkp['shipping']);
			$subtot = ($stkp['subtot']);
			$subtot = sprint($subtot);
			$vat = sprint($stkp['vat']);
			$tot1 = sprint(($tot1+$subtot));
			$tot2 = sprint(($tot2+$stkp['shipchrg']));
			$tot3 = sprint(($tot3+$stkp['total']));
			$tot4 = sprint($tot4+$vat);

			# Get documents
			$docs = doclib_getdocs("pur", $stkp['purnum']);

			$printOrd .= "
				<tr class='".bg_class()."'>
					<td>$stkp[purnum]</td>
					<td>$stkp[ordernum]</td>
					<td>$stkp[supinv]</td>
					<td>$date</td>
					<td>$stkp[supname]</td>
					<td align='right'>".CUR." $subtot</td>
					<td align='right'>".CUR." $stkp[shipchrg]</td>
					<td align='right'>".CUR." $vat</td>
					<td align='right'>".CUR." $stkp[total]</td>
					<td>$docs</td>
					<td><a href='purch-canc-print.php?purid=$stkp[purid]'>Print</a></td>";
			$i++;
		}
	}

	$printOrd .= "
			<tr class='".bg_class()."'>
				<td colspan='5'>Totals</td>
				<td align='right'>".CUR." $tot1</td>
				<td align='right'>".CUR." $tot2</td>
				<td align='right'>".CUR." $tot4</td>
				<td align='right'>".CUR." $tot3</td>
				<td colspan='8'>&nbsp</td>
			</tr>
			<tr>
				<td colspan='17' align='right'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='purchase-new.php'>New Order</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-report.php'>Stock Control Reports</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
		<center>
		<h3>View Canceled Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>No.</th>
				<th>Order No.</th>
				<th>Supp Inv No.</th>
				<th>Order Date</th>
				<th>Supplier</th>
				<th>Sub Total</th>
				<th>Delivery Charges</th>
				<th>Vat</th>
				<th>Total</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$supsql = "";
	if (isset ($supplier) AND $supplier != 0){
		$supsql = " AND supid = '$supplier'";
	}

	$sql = "SELECT * FROM purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' $supsql ORDER BY pdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock purchases from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li> No stock Orders found</li>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='purchase-new.php'>New Order</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='purchase-view.php'>View Purchases</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}else{
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			# Date format
			$date = explode("-", $stkp['pdate']);
			$date = $date[2]."-".$date[1]."-".$date[0];

			# Calculate the Sub-Total
			$stkp['total'] = sprint($stkp['total']);
			$stkp['shipchrg'] = sprint($stkp['shipping']);
			$subtot = ($stkp['subtot']);
			$subtot = sprint($subtot);
			$vat = sprint($stkp['vat']);
			$tot1 = sprint(($tot1+$subtot));
			$tot2 = sprint(($tot2+$stkp['shipchrg']));
			$tot3 = sprint(($tot3+$stkp['total']));
			$tot4 = sprint($tot4+$vat);

			# Get documents
			$docs ="";

			$printOrd .= "
				<tr>
					<td>$stkp[purnum]</td>
					<td>$stkp[ordernum]</td>
					<td>$stkp[supinv]</td>
					<td>$date</td>
					<td>$stkp[supname]</td>
					<td align='right'>".CUR." $subtot</td>
					<td align='right'>".CUR." $stkp[shipchrg]</td>
					<td align='right'>".CUR." $vat</td>
					<td align='right'>".CUR." $stkp[total]</td>";

			$edit = "purchase-new.php";
			$recv = "purch-recv.php";
			$complt = "<a href='purch-complete.php?purid=$stkp[purid]'>Complete</a>";
			$recinv = "<a href='purch-recinvcd.php?purid=$stkp[purid]'>Record Invoice</a>";
			if($stkp['invcd'] == 'y')
				$recinv = "Invoice Recorded";

			if($stkp['cash'] == 'y'){
				$edit = "purchase-new-cash.php";
				$recv = "purch-recv-cash.php";
				$complt = "<br>";
				$recinv = "";
			}

			if($stkp['received'] != "y" && $subtot == 0){
				/*
				$printOrd .= "<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
					<td><br></td>
					<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
				</tr>";
				*/

			}elseif($stkp['received'] != "y"){
				if($stkp['edit'] != 1 && $stkp['apprv'] != 'y' && $stkp['invcd'] != 'y'){
					/*
					$printOrd .= "<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
						<td><a href='purch-apprv.php?purid=$stkp[purid]'>Approve</a></td>
						<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
					</tr>";
					*/

				}elseif($stkp['edit'] != 1 && $stkp['apprv'] == 'y'){
					if(getSetting("PURCH_APPRV") == 'napprv'&& $stkp['invcd'] != 'y'){
      					/*
						$printOrd .= "<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
      						<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>";
      					*/

					}else{
						//$printOrd .= "<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>";
					}
					if($stkp['rsubtot']>0) {
						$rec="";
					} elseif($stkp['iamount']>0) {
						$rec="";
					} else {
						$rec="<a href='$recv?purid=$stkp[purid]&invoice=no'>Receive & Record Invoice</a>";
					}
					/*
					$printOrd .= "<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
						<td>$recinv</td>
						<td>$rec</td>
					</tr>";
					*/

				}else{
					/*
					$printOrd .= "<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
						<td>$recinv</td>
						<td>$complt</td>
					</tr>";
					*/
				}
			}else{
				if($stkp['invcd'] != 'y'){
					//$printOrd .= "<td colspan=3>$recinv</td></tr>";
				}else{
					//$printOrd .= "<td colspan=3><br></td></tr>";
				}
			}
			$i++;
		}
	}

	$printOrd .= "
			<tr>
				<td colspan='5'>Totals</td>
				<td align='right'>".CUR." $tot1</td>
				<td align='right'>".CUR." $tot2</td>
				<td align='right'>".CUR." $tot4</td>
				<td align='right'>".CUR." $tot3</td>
			</tr>
		</table>";

	$OUTPUT = $printOrd;
	include("xls/temp.xls.php");
	Stream("Purchases", $OUTPUT);
	return $printOrd;

}



function delete_confirm($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($purid, "num", 0, 9, "Invalid purchase id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm = "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	// Retrieve the info from Cubit.
	db_conn("cubit");
	$sql = "SELECT * FROM purchases WHERE purid='$purid'";
	$purRslt = db_exec($sql) or errDie("Unable to retrieve purchases from Cubit.");
	$purData = pg_fetch_array($purRslt);

	// Date format
	$date = explode("-", $purData["pdate"]);
	$date = $date[2]."-".$date[1]."-".$date[0];

	// Calculate the totals
	$purData["total"] = sprint($purData["total"]);
	$purData["shipchrg"] = sprint($purData["shipping"]);
	$subtot = ($purData["subtot"]);
	$subtot = sprint($subtot);
	$vat = sprint($purData['vat']);

	$OUTPUT = "
		<h3>Delete Stock Order</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='delete_write'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>No.</td>
				<td>$purData[purnum]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order No.</td>
				<td>$purData[ordernum]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order Date</td>
				<td>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Supplier</td>
				<td>$purData[supname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Sub Total</td>
				<td>".CUR."$subtot</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Delivery Charges</td>
				<td>".CUR."$purData[shipchrg]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Vat</td>
				<td>".CUR."$vat</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total</td>
				<td>".CUR."$purData[total]</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}



function delete_write($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($purid, "num", 1, 9, "Invalid purchase id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	// Do Cubit update
	db_conn("cubit");
	$sql = "DELETE FROM purchases WHERE purid='$purid'";
	$purRslt = db_exec($sql) or errDie("Unable to remove order from Cubit.");

	// See if all went well
	if (pg_affected_rows($purRslt) > 0) {
		$OUTPUT = "<li>Stock order has been successfully removed.</li>";
	} else {
		$OUTPUT = "<li class='err'>Purchase number <b>$purid</b> was not found.</li>";
	}
	return $OUTPUT;

}


?>

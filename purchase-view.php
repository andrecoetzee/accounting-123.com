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

// Merge post vars and get vars
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "view":
			$OUTPUT = printPurch ($_POST);
			break;
		case "export":
			if (isset ($_POST["export"])) 
				$OUTPUT = export ($_POST);
			else 
				$OUTPUT = printPurch ($_POST);
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
		<h3>View Stock Orders</h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
			<tr>
				<th>Supplier(s)</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$supplier_drop</td>
			</tr>
			<tr><td><br></td></tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("purchase-new.php", "New Order"),
			ql("stock-view.php", "View Stock")
		);
	return $slct;

}




function printPurch ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new validate ();
	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	$v->isOk ($fromdate,"date", 1,1,"Invalid From Date.");
	$v->isOk ($todate,"date", 1,1,"Invalid To Date.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	$check1 = "";
	$check2 = "";
	if (!isset ($filter) OR $filter == "summary") 
		$check1 = "checked='yes'";
	else 
		$check2 = "checked='yes'";

	$ocheck1 = "";
	$ocheck2 = "";
	if (!isset ($order) OR $order == "date") 
		$ocheck1 = "checked='yes'";
	else 
		$ocheck2 = "checked='yes'";

	# Set up table to display in
	$printOrd = "
		<center>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>

			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>

		<h3>View Stock Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Filter</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='filter' value='summary' $check1 onClick=\"document.form1.submit();\"> Summary</td>
				<td><input type='radio' name='filter' value='detailed' $check2 onClick=\"document.form1.submit();\"> Detailed</td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Order</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='order' value='date' $ocheck1 onClick=\"document.form1.submit();\"> Date</td>
				<td><input type='radio' name='order' value='numeric' $ocheck2 onClick=\"document.form1.submit();\"> Numeric</td>
			</tr>
		</table>
		<br>
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
				<th colspan='8'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	$supsql = "";
	if (isset ($supplier) AND $supplier != 0){
		$supsql = " AND supid = '$supplier'";
	}

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;
	$tot3 = 0;
	$tot4 = 0;

	if (isset ($order) AND $order == "numeric"){
		$orderby = "purnum::integer";
	}else {
		$orderby = "pdate";
	}

	$sql = "SELECT * FROM purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' $supsql ORDER BY $orderby DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock purchases from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li class='err'> No Stock Orders Could be Found.</li><br>"
			.mkQuickLinks(
				ql("purchase-new.php", "New Order"),
				ql("stock-view.php", "View Stock")
			);
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
			$tot1 = sprint(($tot1 + $subtot));
			$tot2 = sprint(($tot2 + $stkp['shipchrg']));
			$tot3 = sprint(($tot3 + $stkp['total']));
			$tot4 = sprint($tot4 + $vat);

			# Get documents
			$docs = doclib_getdocs("pur", $stkp['purnum']);

			# Alternate bgcolor
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
					<td><a href='purch-det.php?purid=$stkp[purid]'>Details</a></td>
					<td><a href='purch-cancel.php?purid=$stkp[purid]'>Delete</td>
					<td><a href='javascript: printer(\"purch-print.php?purid=$stkp[purid]\");'>Print</a></td>";

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
//			<td><input type='checkbox' name='email_sel[$stkp[purid]]' value='$stkp[supid]'></td>
			if($stkp['received'] != "y" && $subtot == 0){
				$printOrd .= "
						<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
						<td>&nbsp</td>
						<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
						<td></td>
					</tr>";

			}elseif($stkp['received'] != "y"){
				if($stkp['edit'] != 1 && $stkp['apprv'] != 'y' && $stkp['invcd'] != 'y'){
					$printOrd .= "
							<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
							<td><a href='purch-apprv.php?purid=$stkp[purid]'>Approve</a></td>
							<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
						</tr>";

				}elseif($stkp['edit'] != 1 && $stkp['apprv'] == 'y'){
					if(getSetting("PURCH_APPRV") == 'napprv'&& $stkp['invcd'] != 'y'){
      					$printOrd .= "
						<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
						<td><a href='purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>";

					}else{
						$printOrd .= "<td> <a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>";
					}
					if($stkp['rsubtot'] > 0) {
						$rec = "";
					} elseif($stkp['iamount'] > 0) {
						$rec = "";
					} else {
						$rec = "<a href='$recv?purid=$stkp[purid]&invoice=no'>Receive & Record Invoice</a>";
					}
					$printOrd .= "
							<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
							<td>$recinv</td>
							<td>$rec</td>
						</tr>";
				}else{
					$printOrd .= "
							<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
							<td>$recinv</td>
							<td>$complt</td>
						</tr>";
				}
			}else{
				if($stkp['invcd'] != 'y'){
					$printOrd .= "<td colspan='3'>$recinv</td></tr>";
				}else{
					$printOrd .= "<td colspan='3'><br></td></tr>";
				}
			}

			db_connect ();

			if (isset ($filter) AND $filter == "detailed"){

				$stockcodes = array ();
				$get_codes = "SELECT * FROM suppstock WHERE suppid = '$stkp[supid]' ORDER BY stkid";
				$run_codes = db_exec ($get_codes) or errDie ("Unable to get supplier stock code information");
				if (pg_numrows ($run_codes) > 0){
					while ($codarr = pg_fetch_array ($run_codes)){
						if (strlen ($codarr['stkcod']) > 0) 
							$stockcodes[$codarr['stkid']]['stkcod'] = $codarr['stkcod'];
						if (strlen ($codarr['stkdes']) > 0) 
							$stockcodes[$codarr['stkid']]['stkdes'] = $codarr['stkdes'];
					}
				}

				$get_items = "SELECT * FROM pur_items WHERE purid = '$stkp[purid]'";
				$run_items = db_exec ($get_items) or errDie ("Unable to get stock items information.");
				if (pg_numrows ($run_items) > 0){
					$printOrd .= "
						<tr class='".bg_class()."'>
							<th colspan='4'></th>
							<th>Code</th>
							<th>Description</th>
							<th>Quantity</th>
							<th>Unit Cost</th>
							<th>Total</th>
						</tr>";
					while ($piarr = pg_fetch_array ($run_items)){
						$get_stock = "SELECT * FROM stock WHERE stkid = '$piarr[stkid]' LIMIT 1";
						$run_stock = db_exec ($get_stock) or errDie ("Unable to get stock informarion.");
						if (pg_numrows ($run_stock) > 0){
							$stk = pg_fetch_array ($run_stock);
							if (isset ($stockcodes[$stk['stkid']]['stkcod']))
								$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
							if (isset ($stockcodes[$stk['stkid']]['stkdes']))
								$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];
						}
						$printOrd .= "
							<tr class='".bg_class()."'>
								<td colspan='4'></td>
								<td>$stk[stkcod]</td>
								<td>$stk[stkdes]</td>
								<td>$piarr[qty]</td>
								<td>$piarr[unitcost]</td>
								<td>$piarr[amt]</td>
							</tr>";
					}
					$printOrd .= "
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
				}
			}

			$i++;
		}
	}

	$printOrd .= "
			<tr class='".bg_class()."'>
				<td colspan='5'>Totals</td>
				<td align='right' nowrap='t'>".CUR." $tot1</td>
				<td align='right' nowrap='t'>".CUR." $tot2</td>
				<td align='right' nowrap='t'>".CUR." $tot4</td>
				<td align='right' nowrap='t'>".CUR." $tot3</td>
				<td colspan='8'>&nbsp</td>
			</tr>
			<tr>
				<td colspan='17' align='right'><input type='submit' name='export' value='Export to Spreadsheet'></td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("purchase-new.php", "New Order"),
			ql("stock-view.php", "View Stock")
		);
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
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;
	$tot3 = 0;
	$tot4 = 0;

	$sql = "SELECT * FROM purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock purchases from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li> There are no stock Orders found.</li>"
			.mkQuickLinks(
				ql("purchase-new.php", "New Order"),
				ql("stock-view.php", "View Stock")
			);
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
			$docs = "";

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
					if($stkp['rsubtot'] > 0) {
						$rec = "";
					} elseif($stkp['iamount'] > 0) {
						$rec = "";
					} else {
						$rec = "<a href='$recv?purid=$stkp[purid]&invoice=no'>Receive & Record Invoice</a>";
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

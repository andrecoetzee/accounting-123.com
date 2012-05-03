<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = show_allocate_entries ($_POST);
			break;
		case "allocate":
			if (isset ($_POST["switchnow"])){
				$OUTPUT = show_allocate_entries ($_POST);
			}else {
				$OUTPUT = allocate_entries ($_POST);
			}
			break;
		default:
			$OUTPUT = get_data_filter ();
	}
}elseif ($_GET["reallocate"]) {
	$OUTPUT = reallocate ($_GET);
}else {
	$OUTPUT = get_data_filter ();
}

$OUTPUT .= mkQuickLinks (
	ql ("debtor-payments-allocation.php","Allocate Customer Receipts")
);

require ("template.php");




function get_data_filter ()
{

	db_connect ();

	$get_cust = "SELECT * FROM customers WHERE blocked = 'no' ORDER BY surname";
	$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_cust) < 1){
		return "<li class='err'>No Customers Found.</li>";
	}else {
		$cust_drop = "<select name='customer'>";
		while ($carr = pg_fetch_array ($run_cust)){
			$cust_drop .= "<option value='$carr[cusnum]'>$carr[surname]</option>";
		}
		$cust_drop .= "</select>";
	}

	$display = "
		<h2>Detailed Statement Entries</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Statement Criteria</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Customer</td>
				<td>$cust_drop</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date Range</td>
				<td>
					".mkDateSelect("from",date("Y"),date("m"),"01")." 
					To 
					".mkDateSelect("to")."
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Allocate'></td>
			</tr>
		</form>
		</table>";
	return $display;

}




function show_allocate_entries ($_POST,$err=TBL_BR)
{

	extract ($_POST);

	# get header information
	$get_cust = "SELECT surname FROM customers WHERE cusnum = '$customer' LIMIT 1";
	$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_cust) < 1){
		$customer_name = "";
	}else {
		$customer_name = pg_fetch_result ($run_cust,0,0);
	}

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	# compile the listing structure
	if ($switch == "normal"){
		$get_entries = "
			SELECT * FROM stmnt 
			WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '' 
			ORDER BY invid,amount DESC";
		$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
		if (pg_numrows($run_entries) < 1){
			$listing = "
				<tr class='".bg_class()."'>
					<td colspan='6'>No Entries Found.</td>
				</tr>";
		}else {
			$listing = "";
			while ($earr = pg_fetch_array ($run_entries)){
				if (sprint ($earr['amount']) > 0){
					if (isset ($allocation) AND is_array ($allocation) AND in_array ("$earr[id]", $allocation)){
						$showradio = "<input type='radio' name='allocate' value='$earr[id]' checked='yes'>";
					}else {
						$showradio = "<input type='radio' name='allocate' value='$earr[id]'>";
					}

					$showcheck = "";
				}else {
					$showradio = "";
					if (isset ($allocation) AND is_array ($allocation) AND in_array ("$earr[id]", $allocation)){
						$showcheck = "<input type='checkbox' name='entries[]' value='$earr[id]' checked>";
					}else {
						$showcheck = "<input type='checkbox' name='entries[]' value='$earr[id]'>";
					}
				}

				$listing .= "
					<tr class='".bg_class()."'>
						<td>$showradio</td>
						<td>$showcheck</td>
						<td>$earr[invid]</td>
						<td>$earr[docref]</td>
						<td>$earr[type]</td>
						<td>".CUR." ".sprint($earr['amount'])."</td>
					</tr>";
			}
		}
	}else {
		$get_entries = "
			SELECT * FROM stmnt 
			WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '' 
			ORDER BY invid,amount DESC";
		$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
		if (pg_numrows($run_entries) < 1){
			$listing = "
				<tr class='".bg_class()."'>
					<td colspan='5'>No Entries Found.</td>
				</tr>";
		}else {
			$listing = "";
			while ($earr = pg_fetch_array ($run_entries)){
				if (sprint ($earr['amount']) > 0){
					if (isset ($allocation) AND is_array ($allocation) AND in_array ("$earr[id]", $allocation)){
						$showradio = "<input type='checkbox' name='entries[]' value='$earr[id]' checked>";
					}else {
						$showradio = "<input type='checkbox' name='entries[]' value='$earr[id]'>";
					}
					$showcheck = "";
				}else {
					$showradio = "";
					if (isset ($allocation) AND is_array ($allocation) AND in_array ("$earr[id]", $allocation)){
						$showcheck = "<input type='radio' name='allocate' value='$earr[id]' checked='yes'>";
					}else {
						$showcheck = "<input type='radio' name='allocate' value='$earr[id]'>";
					}
				}
	
				$listing .= "
					<tr class='".bg_class()."'>
						<td>$showradio</td>
						<td>$showcheck</td>
						<td>$earr[invid]</td>
						<td>$earr[docref]</td>
						<td>$earr[type]</td>
						<td>".CUR." ".sprint($earr['amount'])."</td>
					</tr>";
			}
		}
	}

	# misc code
	$helptext = "You can only allocate unallocated credits, so in order to reallocate a debit or to unallocate a debit you have to first unallocate the credit.";

	if (!isset($switch) OR strlen ($switch) < 1){
		$switch = "normal";
	}

	if (isset ($switchnow) AND strlen ($switchnow) > 0){
		if ($switch == "normal"){
			$switch = "reverse";
		}else {
			$switch = "normal";
		}
	}

	$switch_button = "<input type='submit' name='switchnow' value='Change Allocation Method'>";
	$switch_key = "<input type='hidden' name='switch' value='$switch'>";

	$get_bal = "SELECT sum(amount) as balance FROM stmnt WHERE cusnum = '$customer'";
	$run_bal = db_exec ($get_bal) or errDie ("Unable to get customer balance information.");
	if (pg_numrows ($run_bal) > 0){
		$barr = pg_fetch_array ($run_bal);
		$balance = sprint ($barr['balance']);
	}else {
		$balance = 0.00;
	}



	# NORMAL PAYMENTS ALLOCATED TO INVOICES

	$get_entries = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '0' AND amount > 0 
		ORDER BY invid,amount DESC";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		$listing1 = "
			<tr class='".bg_class()."'>
				<td colspan='5'>No Allocated Entries Found.</td>
			</tr>";
	}else {
		$listing1 = "
			<tr>
				<th>Reference</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>";
		while ($earr = pg_fetch_array ($run_entries)){

			$outstanding = abs($earr['amount']);

			#get all allocated entries
			$get_entries2 = "SELECT * FROM stmnt WHERE allocation = '$earr[id]' ORDER BY date,amount";
			$run_entries2 = db_exec($get_entries2) or errDie ("Unable to get allocated entries information.");
			if(pg_numrows($run_entries2) < 1){
				$listing1 .= "
					<tr class='".bg_class()."'>
						<td colspan='4'>No Allocated Entries Found.</td>
					</tr>";
			}else {
				$total = 0;
				$listing1 .= "<input type='hidden' name='alloc[]' value='$earr[id]'>";
				while ($earr2 = pg_fetch_array ($run_entries2)){
					$total = $total + $earr2['amount'];
					$outstanding = $outstanding - abs($earr2['amount']);
				}
			}

			if ($outstanding == 0)
				continue;

			$listing1 .= "
				<tr class='".bg_class()."'>
					<th>$earr[type]</th>
					<th>$earr[invid]</th>
					<th>$earr[date]</th>
					<th>$earr[docref]</th>
					<th>Total: ".CUR." ".sprint($earr['amount'])."</th>
					<th>Outstanding: ".CUR." ".sprint($outstanding)."</th>
				</tr>";

		}
	}



	$get_entries2 = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '0' AND amount <= 0 
		ORDER BY invid,amount DESC";
	$run_entries2 = db_exec($get_entries2) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries2) < 1){
		$listing2 = "
			<tr class='".bg_class()."'>
				<td colspan='5'>No Allocated Entries Found.</td>
			</tr>";
	}else {
		$listing2 = "";
		while ($earr2 = pg_fetch_array ($run_entries2)){

			$outstanding2 = $earr2['amount'];

			#get all allocated entries
			$get_entries22 = "SELECT * FROM stmnt WHERE allocation = '$earr2[id]' ORDER BY date,amount";
			$run_entries22 = db_exec($get_entries22) or errDie ("Unable to get allocated entries information.");
			if(pg_numrows($run_entries22) < 1){
				$listing2 .= "
					<tr class='".bg_class()."'>
						<td colspan='4'>No Allocated Entries Found.</td>
					</tr>";
			}else {
				$total2 = 0;
				$listing2 .= "<input type='hidden' name='alloc[]' value='$earr[id]'>";
				while ($earr3 = pg_fetch_array ($run_entries22)){
					$listing2 .= "
						<tr class='".bg_class()."'>
							<td><input type='checkbox' name='entries[]' value='$earr2[id]'></td>
							<td>$earr3[docref] ($earr3[invid])</td>
							<td>$earr3[type]</td>
							<td>".CUR." ".sprint ($earr3['amount'])."</td>
						</tr>";
					$total2 = $total2 + $earr3['amount'];
					$outstanding2 = $outstanding2 + abs($earr3['amount']);
				}
			}

			$listing2 .= "
				<tr class='".bg_class()."'>
					<th colspan='3'>$earr2[type]</th>
					<th>$earr2[invid]</th>
					<th>$earr2[date]</th>
					<th>$earr2[docref]</th>
					<th>Total: ".CUR." ".sprint($earr2['amount'])."</th>
					<th>Outstanding: ".CUR." ".sprint($outstanding)."</th>
				</tr>";

		}
	}


	$display = "
		<script>
			function showPhonetical(obj) {
				XPopupShow('$helptext', getObject('phonetic_show'));
			}
		</script>
		<h2>Detailed Statement Entries</h2>
		<form action='debtor-payments-unallocation.php' method='POST' name='form1'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='customer' value='$customer'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_day' value='$to_day'>
		<table ".TMPL_tblDflts.">
			$err
			<tr class='".bg_class()."'>
				<th>Customer</th>
				<td>$customer_name</td>
			</tr>
			<tr class='".bg_class()."'>
				<th>Date Range</th>
				<td>$from_date to $to_date</td>
			</tr>
			<tr class='".bg_class()."'>
				<th>Total Outstanding Balance</th>
				<td>".CUR." $balance</td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Click on the button below to unallocate credit/debits</td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='submit' value='Unallocate Payments'></td>
				<td align='right'><input type='button'  onClick='showPhonetical(this);' value='Additional Help'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2'><li class='err'>Below you can allocate a credit to multiple debits on the debtors statement</li></td>
			</tr>
		</table>
		</form>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='allocate'>
			<input type='hidden' name='customer' value='$customer'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_day' value='$to_day'>
			$switch_key
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr class='".bg_class()."'>
							<td align='center' colspan='5'>$switch_button</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Link Ct</th>
							<th>To Dt</th>
							<th>Invoice</th>
							<th>Reference</th>
							<th>Description</th>
							<th>Amount</th>
						</tr>
						$listing
						".TBL_BR."
						<tr>
							<td colspan='5' align='right'><input type='submit' value='Allocate'></td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td colspan='3'><h4>Payments Allocated To Invoices</h4></td>
						</tr>
						$listing1
						".TBL_BR."
						".TBL_BR."
						<tr>
							<td colspan='3'><h4>Invoices Allocated To Payments</h4></td>
						</tr>
						$listing2
					</table>
				</td>
			</tr>
		</table>
		</form>";
	return $display;

}



function allocate_entries ($_POST)
{

	extract ($_POST);

	if ((isset($allocate) AND strlen($allocate) > 0) AND (isset($entries) AND is_array ($entries))){
		#all vars set
	}else {
		return show_allocate_entries($_POST,"<li class='err'>Please Select At Least 1 Receipt And Payment.</li>");
	}



	#update the allocation
	pglib_transaction ("BEGIN") or errDie ("Unable to start transaction.");

	db_connect ();

	#get receipt date for allocation for the payments
	$get_cdate = "SELECT date FROM stmnt WHERE id = '$allocate' LIMIT 1";
	$run_cdate = db_exec($get_cdate) or errDie ("Unable to get payment allocation date.");
	$alloc_date = pg_fetch_result ($run_cdate,0,0);

	$upd_sql1 = "UPDATE stmnt SET allocation = '0' WHERE id = '$allocate'";
	$run_upd1 = db_exec($upd_sql1) or errDie ("Unable to update customer statement information.");

	foreach ($entries AS $each){
		$upd_sql = "UPDATE stmnt SET allocation = '$allocate', allocation_date = '$alloc_date' WHERE id = '$each'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement information.");
	}

	pglib_transaction ("COMMIT") or errDie ("Unable to complete transaction.");

	return show_allocate_entries ($_POST,"<li class='err'>Allocation Complete.</li>");

}


function reallocate ($_GET)
{

	$allocate = $_GET["reallocate"];
	$from_date = $_GET["from_date"];
	$to_date = $_GET["to_date"];

	$from_date_arr = explode ("-", $from_date);
	$to_date_arr = explode ("-", $to_date);

	$array = array();

	$array["from_year"] = $from_date_arr[0];
	$array["from_month"] = $from_date_arr[1];
	$array["from_day"] = $from_date_arr[2];
	$array["to_year"] = $to_date_arr[0];
	$array["to_month"] = $to_date_arr[1];
	$array["to_day"] = $to_date_arr[2];



	db_connect ();

	$get_entries = "SELECT id, cusnum FROM stmnt WHERE allocation = '$allocate'";
	$run_entries = db_exec ($get_entries) or errDie ("Unable to get allocation information.");
	if (pg_numrows($run_entries) > 0){
		$allocation = array ();
		$allocation[] = $allocate;
		while ($aarr = pg_fetch_array ($run_entries)){
			$array["customer"] = $aarr["cusnum"];
			$allocation[] = $aarr['id'];
		}
		$array["allocation"] = $allocation;
	}

	$get_switch = "SELECT amount FROM stmnt WHERE id = '$allocate' LIMIT 1";
	$run_switch = db_exec ($get_switch) or errDie ("Unable to get allocation method information.");
	if (pg_numrows ($run_switch) > 0){
		$amt_tmp = pg_fetch_result ($run_switch,0,0);
		$amt_tmp += 0;
		if ($amt_tmp <= 0){
			$array["switch"] = "reverse";
		}else {
			$array["switch"] = "normal";
		}
	}else {
		$array["switch"] = "normal";
	}

	$upd_sql = "UPDATE stmnt SET allocation = '' WHERE id = '$allocate'";
	$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement information.");

	$upd_sql2 = "UPDATE stmnt SET allocation = '' WHERE allocation = '$allocate'";
	$run_upd2 = db_exec($upd_sql2) or errDie ("Unable to update customer allocation information.");


	return show_allocate_entries ($array);

}


?>

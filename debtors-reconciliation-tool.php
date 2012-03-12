<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = show_allocate_entries ($_POST);
			break;
		case "allocate":
			if (isset ($_POST["update"])){
				$OUTPUT = update_allocate_entries ($_POST);
			}elseif (isset ($_POST["view"])){
				$OUTPUT = show_allocate_entries ($_POST);
			}else {
				$OUTPUT = allocate_entries ($_POST);
			}
			break;
		default:
			$OUTPUT = get_data_filter ();
	}
}elseif ($_GET["remid"]) {
	$OUTPUT = reallocate ($_GET);
}elseif(isset ($_GET["customer"])){
	$process = array (
		"from_day" => $_GET["from_day"],
		"from_month" => $_GET["from_month"],
		"from_year" => $_GET["from_year"],
		"to_day" => $_GET["to_day"],
		"to_month" => $_GET["to_month"],
		"to_year" => $_GET["to_year"],
		"customer" => $_GET["customer"]
	);
	$OUTPUT = show_allocate_entries ($process,$_GET["err"]);
}else {
	$OUTPUT = get_data_filter ();
}

$OUTPUT .= mkQuickLinks (
	ql ("debtors-reconciliation-tool.php", "Debtors Reconciliation Tool")
	//ql ("debtor-payments-allocation.php","Allocate Customer Receipts")
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
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td>$cust_drop</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date Range</td>
				<td>
					".mkDateSelect("from",date("Y"),date("m"),"01")." 
					To 
					".mkDateSelect("to")."
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='View Allocation'></td>
			</tr>
		</form>
		</table>";
	return $display;

}




function show_allocate_entries ($_POST,$err=TBL_BR)
{

	extract ($_POST);

	$proc_new = "UPDATE stmnt SET allocation_balance = abs(amount) WHERE allocation_processed = '0'";
	$run_proc = db_exec ($proc_new) or errDie ("Unable to update new entries");

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

	if (isset ($viewall) AND $viewall == "yes"){
		$viewsql = "AND allocation_balance = '0'";
	}else {
		$viewsql = "AND allocation_balance > '0'";
	}

	// payments
	$get_entries = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' $viewsql AND amount < 0 
		ORDER BY invid, amount DESC";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		$current_listing = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'>No Entries Found.</td>
			</tr>";
	}else {
		$current_listing = "";
		while ($earr = pg_fetch_array ($run_entries)){

			$linkedarr = explode ("|", $earr['allocation_linked']);
			$empty1 = array_shift($linkedarr);

			$amountsarr = explode ("|", $earr['allocation_amounts']);
			$empty2 = array_shift($amountsarr);

			$qry = array ();
			$invoices = "";
			if (is_array ($linkedarr) AND count ($linkedarr) > 0){
		
				foreach ($linkedarr AS $each){
					$qry[] = "id = '$each'";
				}
				$runqry = implode (" OR ", $qry);
			
				$get_invs = "SELECT * FROM stmnt WHERE $runqry";
				$run_invs = db_exec ($get_invs) or errDie ("Unable to get linked invoices");
				if (pg_numrows ($run_invs) < 1){
					$invoices = "<tr><td>No Invoices Found.</td></tr>";
					continue;
				}else {

					$allocate_payment_amount = 0;
					while ($iarr = pg_fetch_array ($run_invs)){

						$amountkey = array_search("$iarr[id]", $linkedarr);


						if (isset ($amountkey) AND is_array ($amountsarr) AND isset ($amountsarr[$amountkey])){

							$allocate_invoice_amount = $amountsarr[$amountkey];
							if ($amountsarr[$amountkey] == "xxx"){
								$amount_display = "<input type='button' onClick=\"popupSized('debtors-reconciliation-tool-amounts.php?allocate=$iarr[id]&from=$earr[id]&from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&customer=$customer','allocate','400','300');\" value='Allocate'>";
							}else {
								$amount_display = CUR." ".sprint ($amountsarr[$amountkey]);
							}
						}else {
							if ($iarr['amount'] < 0){
								$allocate_invoice_amount = abs($iarr['amount']);
							}else {
								$allocate_invoice_amount = abs($earr['amount']);
							}
						}

						if ($iarr['amount'] < 0){
							$radio = "<input type='radio' name='debit' value='$iarr[id]'>";
							//$amount_display = "$iarr[allocation_balance]";
						}else {
							$radio = "";
							//$amount_display = "<input type='button' onClick=\"popupSized('debtors-reconciliation-tool-amounts.php?allocate=$iarr[id]&from=$earr[id]&from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&customer=$customer','allocate','400','300');\" value='Allocate'>";
						}

						$invoices .= "
							<tr>
								<td>$iarr[type]</td>
								<td>$iarr[date]</td>
								<td align='right'>$iarr[invid]</td>
								<td>$iarr[docref]</td>
								<td>".CUR." ".sprint($iarr['amount'])."</td>
								<td>".CUR." ".sprint($iarr['allocation_balance'])."</td>
								<td>$amount_display</td>
								<td><a href='debtors-reconciliation-tool.php?remid=$iarr[id]&fromid=$earr[id]&from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&customer=$customer'>Unallocate</a></td>
							</tr>";
						$allocate_payment_amount += abs($iarr['amount']);

					}
				}
			}else {
				continue;
			}

			# if this is the payment reverse, swap amounts
			if ($earr['amount'] < 0){
				$allocate_payment_amount = abs($earr['amount']);
				$radio = "<input type='radio' name='debit' value='$earr[id]'>";
				$amount_display = "$earr[allocation_balance]";
				$heading1 = "Balance";
				$heading2 = "Allocated";
			}else {
				$radio = "";
// PROBLEMATIC ????
				$amount_display = "<input type='button' onClick=\"popupSized('debtors-reconciliation-tool-amounts.php?allocate=$earr[id]&from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&customer=$customer','allocate','400','300');\" value='Allocate'>";
				$heading1 = "Allocated";
				$heading2 = "Balance";
			}

			$current_listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td valign='top'>
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th>Allocate</th>
								<th>Description</th>
								<th>Date</th>
								<th>Reference</th>
								<th>Full Amount</th>
								<th>$heading1</th>
							</tr>
							<tr>
								<td>$radio</td>
								<td>$earr[type]</td>
								<td>$earr[date]</td>
								<td>$earr[docref]</td>
								<td>".CUR." ".sprint($earr['amount'])."</td>
								<td align='right'>$amount_display</td>
							</tr>
						</table>
					</td>
					<td>&nbsp;&nbsp;</td>
					<td>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Description</th>
								<th>Date</th>
								<th>Invoice</th>
								<th>Reference</th>
								<th>Full Amount</th>
								<th>Balance</th>
								<th>$heading2</th>
							</tr>
							$invoices
						</table>
					</td>
				</tr>
				".TBL_BR."";
		}
	}


	$get_bal = "SELECT sum(amount) as balance FROM stmnt WHERE cusnum = '$customer'";
	$run_bal = db_exec ($get_bal) or errDie ("Unable to get customer balance information.");
	if (pg_numrows ($run_bal) > 0){
		$barr = pg_fetch_array ($run_bal);
		$balance = sprint ($barr['balance']);
	}else {
		$balance = 0.00;
	}

	// get unallocated entries
	//AND allocation_balance = abs(amount) 

	$get_entries = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation_balance > 0 
		ORDER BY invid,amount DESC";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		$listing = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'>No Entries Found.</td>
			</tr>";
	}else {
		$listing = "";
		while ($earr = pg_fetch_array ($run_entries)){
			if (sprint ($earr['amount']) > 0){
				$showcheck = "<input type='checkbox' name='credit[]' value='$earr[id]'>";
				$showradio = "";
			}else {
				$showcheck = "";
				$showradio = "<input type='radio' name='debit' value='$earr[id]'>";
			}
	
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$showradio</td>
					<td>$showcheck</td>
					<td>$earr[invid]</td>
					<td>$earr[date]</td>
					<td>$earr[docref]</td>
					<td>$earr[type]</td>
					<td>".CUR." ".sprint($earr['amount'])."</td>
					<td>".CUR." ".sprint($earr['allocation_balance'])."</td>
				</tr>";
		}
	}

	if (isset ($viewall) AND $viewall == "yes"){
		$checkviewall = "checked='yes'";
	}else {
		$checkviewall = "";
	}

	$display = "
		<script>
			function showPhonetical(obj) {
				XPopupShow('$helptext', getObject('phonetic_show'));
			}
		</script>
		<h2>Debtors Reconciliation Tool</h2>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='allocate'>
			<input type='hidden' name='customer' value='$customer'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_day' value='$to_day'>
		<table ".TMPL_tblDflts.">
			$err
			<tr bgcolor='".bgcolorg()."'>
				<th>Customer</th>
				<td>$customer_name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Date Range</th>
				<td>$from_date to $to_date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Total Outstanding Balance</th>
				<td>".CUR." $balance</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>View Completed Entries</th>
				<td><input type='checkbox' name='viewall' value='yes' $checkviewall> <input type='submit' name='view' value='View'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='3'>Current Entries</td>
			</tr>
			".TBL_BR."
			$current_listing
		</table>

		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Link Dt</th>
				<th>To Ct</th>
				<th>Invoice</th>
				<th>Date</th>
				<th>Reference</th>
				<th>Description</th>
				<th>Amount</th>
				<th>Balance</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td colspan='5' align='right'><input type='submit' value='Allocate'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function allocate_entries ($_POST)
{

	extract ($_POST);

	if ((isset($credit) AND is_array($credit)) AND (isset($debit) AND strlen ($debit) > 0)){
		#all vars set
	}else {
		return show_allocate_entries($_POST,"<li class='err'>Please Select At Least 1 Receipt And Payment.</li>");
	}



	#update the allocation
	pglib_transaction ("BEGIN") or errDie ("Unable to start transaction.");

	db_connect ();

	#get receipt date for allocation for the payments
	$get_info = "SELECT * FROM stmnt WHERE id = '$debit' LIMIT 1";
	$run_info = db_exec($get_info) or errDie ("Unable to get payment allocation information.");
	if (pg_numrows ($run_info) < 1){
		return "Allocation information not found.";
	}

	$arr = pg_fetch_array ($run_info);

	$templinkedarr = explode ("|", $arr['allocation_linked']);
	$empty1 = array_shift($templinkedarr);

	$tempamountsarr = explode ("|", $arr['allocation_amounts']);
	$empty2 = array_shift($tempamountsarr);

	if (in_array ($debit, $templinkedarr)){
		return show_allocate_entries ($_POST, "<li class='err'>Allocation Allready Exists.</li><br>");
	}

	foreach ($credit AS $each){
		if (in_array ($each, $templinkedarr)){
			return show_allocate_entries ($_POST, "<li class='err'>Allocation Allready Exists.</li><br>");
		}
	}

	$vals = "";
	$amountsvals .= "";
	foreach ($credit AS $each){

		$vals .= "|$each";
		$amountsvals .= "|xxx";

		$upd_sql = "
			UPDATE stmnt 
			SET allocation_linked = allocation_linked || '|$debit', allocation_amounts = allocation_amounts || '|xxx' 
			WHERE id = '$each'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement information.");

	}

	$upd_sql1 = "
		UPDATE stmnt 
		SET allocation_linked = allocation_linked || '$vals', allocation_amounts = allocation_amounts || '$amountsvals' 
		WHERE id = '$debit'";
	$run_upd1 = db_exec($upd_sql1) or errDie ("Unable to update customer statement information.");

	pglib_transaction ("COMMIT") or errDie ("Unable to complete transaction.");

	return show_allocate_entries ($_POST,"<li class='err'>Allocation Complete.</li><br>");

}



function update_allocate_entries ($_POST) 
{

	extract ($_POST);

	if (!isset ($payment_amount) OR !isset ($invoice_amount)) {
		return "Invalid Use Of Module.";
	}


	foreach ($invoice_amount AS $key => $each){
print "$key --> $each<br>";
	}

	print "updating ...";

}


function reallocate ($_POST)
{

	extract ($_POST);

	db_connect ();

	$get_entries = "SELECT * FROM stmnt WHERE id = '$remid' LIMIT 1";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		return "Invalid Use Of Module.";
	}

	$rarr = pg_fetch_array ($run_entries);

	$linkedarr = explode ("|", $rarr['allocation_linked']);
	$empty1 = array_shift($linkedarr);

	$amountsarr = explode ("|", $rarr['allocation_amounts']);
	$empty2 = array_shift($amountsarr);

	$get_entries = "SELECT * FROM stmnt WHERE id = '$fromid' LIMIT 1";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		return "Invalid Use Of Module.";
	}

	$farr = pg_fetch_array ($run_entries);

	$flinkedarr = explode ("|", $farr['allocation_linked']);
	$empty1 = array_shift($flinkedarr);

	$famountsarr = explode ("|", $farr['allocation_amounts']);
	$empty2 = array_shift($famountsarr);

	$newrembalance = 0;
	$newremlinked = "";
	$newremamounts = "";
	foreach ($linkedarr AS $key => $each){

		if ($each != $fromid){
			$newremlinked .= "|$each";
			if (strlen ($amountsarr[$key]) > 0) 
				$newremamounts .= "|$amountsarr[$key]";
		}else {
			$newrembalance += $amountsarr[$key];
		}
	}

	$newfrombalance = 0;
	$newfromlinked = "";
	$newfromamounts = "";
	foreach ($flinkedarr AS $key => $each){
		if ($each != $remid){
			$newfromlinked .= "|$each";
			if (strlen($famountsarr[$key]) > 0)
				$newfromamounts .= "|$famountsarr[$key]";
		}else {
			$newfrombalance += $famountsarr[$key];
		}
	}

	$upd_sql1 = "
		UPDATE stmnt 
		SET allocation_linked = '$newremlinked', allocation_amounts = '$newremamounts', 
			allocation_balance = allocation_balance + '$newrembalance' 
		WHERE id = '$remid'";
	$run_upd1 = db_exec ($upd_sql1) or errDie ("Unable to update entry information.");

	$upd_sql2 = "
		UPDATE stmnt 
		SET allocation_linked = '$newfromlinked', allocation_amounts = '$newfromamounts', 
			allocation_balance = allocation_balance + '$newfrombalance' 
		WHERE id = '$fromid'";
	$run_upd2 = db_exec ($upd_sql2) or errDie ("Unable to update entry information.");

	$sendarray = array (
		"from_day" => $from_day,
		"from_month" => $from_month,
		"from_year" => $from_year,
		"to_day" => $to_day,
		"to_month" => $to_month,
		"to_year" => $to_year,
		"customer" => $customer
	);

	return show_allocate_entries ($sendarray);

}


?>
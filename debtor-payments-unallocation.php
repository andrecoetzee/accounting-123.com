<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = show_allocate_entries ($_POST);
			break;
		case "allocate":
			$OUTPUT = allocate_entries ($_POST);
			break;
		default:
			$OUTPUT = get_data_filter ();
	}
}else {
	return "<li class='err'>Invalid Use Of Module.</li>";
}

require ("template.php");




function show_allocate_entries ($_POST,$err=TBL_BR)
{

	extract ($_POST);


	if (!isset($customer) OR !isset($from_year) OR !isset($to_year)){
		return "<li class='err'>Invalid Use Of Module. (1)</li>";
	}

	$get_cust = "SELECT surname FROM customers WHERE cusnum = '$customer' LIMIT 1";
	$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_cust) < 1){
		$customer_name = "";
	}else {
		$customer_name = pg_fetch_result ($run_cust,0,0);
	}

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	# NORMAL PAYMENTS ALLOCATED TO INVOICES

	$get_entries = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '0' AND amount > 0 
		ORDER BY invid,amount DESC";
	$run_entries = db_exec($get_entries) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries) < 1){
		$listing = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'>No Allocated Entries Found.</td>
			</tr>";
	}else {
		$listing = "";
		while ($earr = pg_fetch_array ($run_entries)){

			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<th>$earr[invid]</th>
					<th>$earr[docref]</th>
					<th>$earr[type]</th>
					<th>".CUR." ".sprint($earr['amount'])."</th>
				</tr>";

			$outstanding = abs($earr['amount']);

			#get all allocated entries
			$get_entries2 = "SELECT * FROM stmnt WHERE allocation = '$earr[id]' ORDER BY date,amount";
			$run_entries2 = db_exec($get_entries2) or errDie ("Unable to get allocated entries information.");
			if(pg_numrows($run_entries2) < 1){
				$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='4'>No Allocated Entries Found.</td>
					</tr>";
			}else {
				$total = 0;
				$listing .= "<input type='hidden' name='alloc[]' value='$earr[id]'>";
				while ($earr2 = pg_fetch_array ($run_entries2)){
					$listing .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='checkbox' name='entries[]' value='$earr2[id]'></td>
							<td>$earr2[docref] ($earr2[invid])</td>
							<td>$earr2[type]</td>
							<td>".CUR." ".sprint ($earr2['amount'])."</td>
						</tr>";
					$total = $total + $earr2['amount'];
					$outstanding = $outstanding - abs($earr2['amount']);
				}
			}
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'></td>
					<td align='right'><b>Total:</b></td>
					<td>".CUR." ".sprint ($total)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'></td>
					<td align='right'><b>Outstanding:</b></td>
					<td>".CUR." ".sprint ($outstanding)."</b></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='4' align='center'><a href='debtor-payments-allocation.php?reallocate=$earr[id]&from_date=$from_date&to_date=$to_date'>Unallocate</a></td>
				</tr>
				".TBL_BR."";
		}
	}

	# INVOICES ALLOCATED TO PAYMENTS

	$get_entries2 = "
		SELECT * FROM stmnt 
		WHERE cusnum = '$customer' AND date <= '$to_date' AND date >= '$from_date' AND allocation = '0' AND amount <= 0 
		ORDER BY invid,amount DESC";
	$run_entries2 = db_exec($get_entries2) or errDie ("Unable to get customer information.");
	if (pg_numrows($run_entries2) < 1){
		$listing2 = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='5'>No Allocated Entries Found.</td>
			</tr>";
	}else {
		$listing2 = "";
		while ($earr2 = pg_fetch_array ($run_entries2)){

			$listing2 .= "
				<tr bgcolor='".bgcolorg()."'>
					<th>$earr2[invid]</th>
					<th>$earr2[docref]</th>
					<th>$earr2[type]</th>
					<th>".CUR." ".sprint($earr2['amount'])."</th>
				</tr>";

			$outstanding2 = $earr2['amount'];

			#get all allocated entries
			$get_entries22 = "SELECT * FROM stmnt WHERE allocation = '$earr2[id]' ORDER BY date,amount";
			$run_entries22 = db_exec($get_entries22) or errDie ("Unable to get allocated entries information.");
			if(pg_numrows($run_entries22) < 1){
				$listing2 .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='4'>No Allocated Entries Found.</td>
					</tr>";
			}else {
				$total2 = 0;
				$listing2 .= "<input type='hidden' name='alloc[]' value='$earr[id]'>";
				while ($earr3 = pg_fetch_array ($run_entries22)){
					$listing2 .= "
						<tr bgcolor='".bgcolorg()."'>
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
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'></td>
					<td align='right'><b>Total:</b></td>
					<td>".CUR." ".sprint ($total2)."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'></td>
					<td align='right'><b>Outstanding:</b></td>
					<td>".CUR." ".sprint ($outstanding2)."</b></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='4' align='center'><a href='debtor-payments-allocation.php?reallocate=$earr2[id]&from_date=$from_date&to_date=$to_date'>Unallocate</a></td>
				</tr>
				".TBL_BR."";
		}
	}

	$helptext = "You can unallocate any debit(s) from a credit(s), if all debits from a credit is unallocated, the credit is also unallocated automatically.";

	$display = "
		<script>
			function showPhonetical(obj) {
				XPopupShow('$helptext', getObject('phonetic_show'));
			}
		</script>
		<h2>Detailed Statement Entries</h2>
		<form action='debtor-payments-allocation.php' method='POST' name='form1'>
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
			<tr bgcolor='".bgcolorg()."'>
				<th>Customer</th>
				<td>$customer_name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Date Range</th>
				<td>$from_date to $to_date</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Click on the button below to allocate credit/debits</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='submit' value='Allocate Payments'></td>
				<td align='right'><input type='button'  onClick='showPhonetical(this);' value='Additional Help'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2'><li class='err'>Below you can unallocate credits and debits on the debtors statement</li></td>
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
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<td valign='top' width='50%'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr><th colspan='4'>Payments Allocated To Invoices</th></tr>
						$listing
					</table>
				</td>
				<td valign='top' width='50%'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr><th colspan='4'>Invoices Allocated To Payments</th></tr>
						$listing2
					</table>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='5' align='right'><input type='submit' value='Unallocate Selected'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function allocate_entries ($_POST)
{

	extract ($_POST);

	if ((isset($alloc) AND is_array ($alloc)) AND (isset($entries) AND is_array ($entries))){
		#all set
	}else {
		return show_allocate_entries($_POST,"<li class='err'>Please Select At Least 1 Receipt And Payment.</li>");
	}

	#update the allocation
	pglib_transaction ("BEGIN") or errDie ("Unable to start transaction.");

	db_connect ();

	#remove each of the selected entries ...
	foreach ($entries AS $each){
		$upd_sql = "UPDATE stmnt SET allocation = '' WHERE id = '$each'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement information.");
	}

	#check if we should remove the receipt itself
	foreach ($alloc AS $each){
		$check_sql = "SELECT * FROM stmnt WHERE allocation = '$each'";
		$run_check = db_exec($check_sql) or errDie ("Unable to check customer information.");
		if (pg_numrows($run_check) < 1){
			#this receipt has no invoices ... remove it from allocation listing ...
			$upd_sql2 = "UPDATE stmnt SET allocation = '' WHERE id = '$each'";
			$run_upd2 = db_exec($upd_sql2) or errDie ("Unable to update customer allocation information.");
		}
	}

	pglib_transaction ("COMMIT") or errDie ("Unable to complete transaction.");

	return show_allocate_entries($_POST,"<li class='err'>Allocation Complete.</li>");

}


?>

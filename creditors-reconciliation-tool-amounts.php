<?

require ("settings.php");

if (isset ($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			$OUTPUT = write_amount ();
			break;
		default:
			$OUTPUT = get_amount();
	}
}else {
	$OUTPUT = get_amount ();
}

require ("template.php");



function get_amount ()
{

	extract ($_REQUEST);

	db_connect ();

	$get_ent = "SELECT id, allocation_linked, allocation_amounts FROM sup_stmnt WHERE id = '$allocate' LIMIT 1";
	$run_ent = db_exec ($get_ent) or errDie ("Unable to get entry allocation amount information.");
	if (pg_numrows ($run_ent) < 1){
		return "Invalid Use Of Module.";
	}

	$arr = pg_fetch_array ($run_ent);

	$linkedarr = explode ("|", $arr['allocation_linked']);
	$empty1 = array_shift($linkedarr);

	$amountsarr = explode ("|", $arr['allocation_amounts']);
	$empty2 = array_shift($amountsarr);

	//$amountkey = array_search("$arr[id]", $linkedarr);
	$amountkey = array_search("$from", $linkedarr);

	if ($amountsarr[$amountkey] == "xxx"){
		$amount = 0;
	}else {
		$amount = $amountsarr[$amountkey];
	}

	$display = "
		<h4>Set Amount</h4>
		<form name='form1' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='allocate' value='$allocate'>
			<input type='hidden' name='from' value='$from'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='supplier' value='$supplier'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Amount</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' size='8' name='newamount' value='$amount'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function write_amount ()
{

	extract ($_REQUEST);

	db_connect ();

	$get_ent = "SELECT id, amount, allocation_linked, allocation_amounts FROM sup_stmnt WHERE id = '$allocate' LIMIT 1";
	$run_ent = db_exec ($get_ent) or errDie ("Unable to get entry allocation amount information.");
	if (pg_numrows ($run_ent) < 1){
		return "Invalid Use Of Module.";
	}

	$arr = pg_fetch_array ($run_ent);

	$linkedarr = explode ("|", $arr['allocation_linked']);
	$empty1 = array_shift($linkedarr);

	$amountsarr = explode ("|", $arr['allocation_amounts']);
	$empty2 = array_shift($amountsarr);

	$amountkey = array_search("$from", $linkedarr);

// 	$total = 0;
// 	// calculate the current total to check if it exeeds the payment amount
// 	foreach ($amountsarr AS $each){
// 		$each += 0;
// 		$total += $each;
// 	}

	// get current list
	$amountsarr[$amountkey] = $newamount;

	$newlinked = "";
	$newamounts = "";
	foreach ($linkedarr AS $key => $each){
		$newlinked .= "|$each";
// 		if ($each == $from){
// 			$amountsarr[$key] = $amount;
// 		}
		$newamounts .= "|$amountsarr[$key]";
	}



	// update reverse allocation
	$get_ent = "SELECT id, allocation_linked, allocation_amounts, amount FROM sup_stmnt WHERE id = '$from' LIMIT 1";
	$run_ent = db_exec ($get_ent) or errDie ("Unable to get entry allocation amount information.");
	if (pg_numrows ($run_ent) < 1){
		return "Invalid Use Of Module.";
	}

	$farr = pg_fetch_array ($run_ent);




	$flinkedarr = explode ("|", $farr['allocation_linked']);
	$empty1 = array_shift($flinkedarr);

	$famountsarr = explode ("|", $farr['allocation_amounts']);
	$empty2 = array_shift($famountsarr);

	$total = 0;
	// calculate the current total to check if it exeeds the payment amount
	foreach ($famountsarr AS $each){
		$each += 0;
		$total += $each;
	}

	$famountkey = array_search("$allocate", $flinkedarr);

	if ($total + $newamount > abs($farr['amount'])){
		return "
			<script>
				window.close();
				window.opener.document.location='creditors-reconciliation-tool.php?from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&supplier=$supplier&err=<li class=err>Amount too large.</li><br>';
			</script>";
	}

	$famountsarr[$famountkey] = $newamount;

	$fnewlinked = "";
	$fnewamounts = "";
	foreach ($flinkedarr AS $key => $each){
		$fnewlinked .= "|$each";
		$fnewamounts .= "|$famountsarr[$key]";
	}

	$upd_sql = "
		UPDATE sup_stmnt 
		SET allocation_linked = '$newlinked', allocation_amounts = '$newamounts', 
			allocation_balance = allocation_balance - '$newamount', allocation_processed = '1' 
		WHERE id = '$allocate'";
	$run_upd = db_exec ($upd_sql) or errDie ("Unable to update allocation information.");

	$upd_sql = "
		UPDATE sup_stmnt 
		SET allocation_linked = '$fnewlinked', allocation_amounts = '$fnewamounts', 
			allocation_balance = allocation_balance - '$newamount', allocation_processed = '1' 
		WHERE id = '$from'";
	$run_upd = db_exec ($upd_sql) or errDie ("Unable to update allocation information.");

/*
			window.opener.document.getElementByName(\"key\").value = 'confirm';
			if (window.opener && !window.opener.closed) {
				window.opener.location.reload();
			} 
*/

	return "
		<script>
			window.close();
			window.opener.document.location='creditors-reconciliation-tool.php?from_day=$from_day&from_month=$from_month&from_year=$from_year&to_day=$to_day&to_month=$to_month&to_year=$to_year&supplier=$supplier';
		</script>";

}


?>
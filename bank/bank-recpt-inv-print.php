<?

require ("../settings.php");

$OUTPUT = show_receipt ($HTTP_GET_VARS);

require ("../tmpl-print.php");



function show_receipt ($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);

	if(!isset($recid) OR strlen($recid) < 1){
		return "<li class='err'>Invalid use of module. Invalid Receipt ID.</li>";
	}

	db_connect ();

	$get_rec = "SELECT * FROM cashbook WHERE cashid = '$recid' LIMIT 1";
	$run_rec = db_exec($get_rec) or errDie ("Unable to get receipt information.");
	if (pg_numrows($run_rec) < 1){
		return "<li class='err'>Receipt information not found.</li>";
	}else {
		$cash_arr = pg_fetch_array ($run_rec);

		#get customer information
		$get_cust = "SELECT accno,surname,paddr1 FROM customers WHERE cusnum = '$cash_arr[cusnum]' LIMIT 1";
		$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
		if(pg_numrows($run_cust) < 1){
			$cus_addr = "";
			$cus_accno = "";
		}else {
			$cus_arr = pg_fetch_array ($run_cust);
			$cus_addr = $cus_arr['paddr1'];
			$cus_accno = $cus_arr['accno'];
		}

		$inv_ids = explode ("|", $cash_arr['rinvids']);
		$inv_amts = explode ("|", $cash_arr['amounts']);

		$null1 = array_shift($inv_ids);
		$null2 = array_shift($inv_amts);

		$listing = "";
		$total = 0;
		foreach ($inv_ids AS $key => $each){
			$listing .= "
				<tr>
					<td>$cash_arr[date]</td>
					<td>$cash_arr[reference]</td>
					<td>".CUR." ".sprint ($inv_amts[$key])."</td>
				</tr>";
			$total = $total + $inv_amts[$key];
		}

		$unalloc = $cash_arr['amount'] - $total;

		if ($unalloc > 0){
			$listing .= "
				<tr>
					<td>$cash_arr[date]</td>
					<td>$cash_arr[reference] (Unallocated)</td>
					<td>".CUR." ".sprint ($unalloc)."</td>
				</tr>";
			$total += $unalloc;
		}

		$listing .= "
			<tr>
				<td colspan='2' align='right'><b>Total:</b></td>
				<td>".CUR." ".sprint ($total)."</td>
			</tr>";

		$receiptnumber = $cash_arr['cashid'];

	}

	$comments = getCSetting("DEFAULT_BANK_RECPT_COMMENTS");

	$rborder = "style='border-right: 2px solid #000'";

	$display = "
		<style>
			table { border: 2px solid #000 }
		</style>
		<table border='0' cellpadding='2' cellspacing='2' width='80%' align='center'>
			<tr>
				<td width='30%'></td>
				<td align='center'><font size='5'><b>".COMP_NAME."</b></font></td>
				<td align='right'><font size='4'><b>Customer Receipt</b></font></td>
			</tr>
		</table>
		<p>
		<table cellpadding='1' cellspacing='0' width='80%' align='center'>
			<tr>
				<td $rborder>
					<b>".COMP_NAME."</b><br>
					".COMP_ADDRESS."<br>
					".COMP_PADDR."
				</td>
				<td>
					<b>Received From:</b><br>
					$cash_arr[name]<br>
					$cus_addr<br>
					<br>
					<b>Account Number:</b> $cus_accno<br>
					<b>Receipt Number:</b> $receiptnumber
				</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='80%' align='center'>
			<tr>
				<td><b>Date</b></td>
				<td><b>Ref Num</b></td>
				<td><b>Amount</b></td>
			</tr>
			$listing
			".TBL_BR."
			".TBL_BR."
		</table>
		<p>
		<table ".TMPL_tblDflts." width='80%' align='center'>
			".TBL_BR."
			<tr>
				<td width='60%'>".nl2br(base64_decode($comments))."</td>
				<td align='right'>_____________________________________</td>
			</tr>
			<tr>
				<td></td>
				<td align='center'>Signature</td>
			</tr>
		</table>
		<div style='position:absolute;left:11%'>
		<font size='1'>&#169 Cubit Accounting Software</font>
		</div>";
	return $display;

}


?>

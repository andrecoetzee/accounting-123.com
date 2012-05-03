<?

require ("../settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "record":
			$OUTPUT = run_trans ($_POST);
			break;
		default:
			$OUTPUT = get_trans ($_POST);
	}
}else {
	$OUTPUT = get_trans ($_POST);
}

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql("record-trans.php", "Add Replay Transaction"),
				ql("export-xml.php", "Export Replay Transactions To File"),
				ql("replay-file-trans.php", "Replay Transaction File")
			);

require ("../template.php");



function get_trans ($_POST)
{

	extract ($_POST);
	
	$display = "
					<h2>Record Transaction</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='record'>
						<tr>
							<th>Select Transaction Type To Record</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>
								<select size='4' name='transtype'>
									<option value='journal'>Journal Transaction</option>
									<option value='debtor'>Debtor Transaction</option>
									<option value='creditor'>Creditor Transaction</option>
									<option value='stock'>Stock Transaction</option>
								</select>
							</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Record'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function run_trans ($_POST)
{

	extract ($_POST);

	db_conn ("exten");

	#get list of all current entries
	$get_list = "SELECT * FROM tranreplay WHERE ttype = '$transtype' ORDER BY tdate";
	$run_list = db_exec($get_list) or errDie("Unable to get transaction batch information");
	if(pg_numrows($run_list) < 1){
		$listing = "
						<tr class='".bg_class()."'>
							<td colspan='6'>No Transactions Found.</td>
						</tr>
					";
	}else {
		$listing = "";
		db_conn("core");
		while ($tarr = pg_fetch_array($run_list)){

			#get the accounts details
			$get_dtacc = "SELECT accname FROM accounts WHERE accid = '$tarr[debitacc]' LIMIT 1";
			$run_dtacc = db_exec($get_dtacc) or errDie("Unable to get debit account information.");
			$dtarr = pg_fetch_array($run_dtacc);
			$debitaccname = $dtarr['accname'];

			$get_ctacc = "SELECT accname FROM accounts WHERE accid = '$tarr[creditacc]' LIMIT 1";
			$run_ctacc = db_exec($get_ctacc) or errDie("Unable to get credit account information.");
			$ctarr = pg_fetch_array($run_ctacc);
			$creditaccname = $ctarr['accname'];
			
			$listing .= "
							<tr class='".bg_class()."'>
								<td>$debitaccname</td>
								<td>$creditaccname</td>
								<td align='center'>$tarr[tdate]</td>
								<td>$tarr[refno]</td>
								<td>$tarr[amount]</td>
								<td>$tarr[details]</td>
							</tr>
						";
		}
	}

	#now create requested entry in a popup
	switch ($transtype){
		case "journal":
			$openscript = "record-journal-trans.php";
			break;
		case "debtor":
			$openscript = "record-debtor-trans.php";
			break;
		case "creditor":
			$openscript = "record-creditor-trans.php";
			break;
		case "stock":
			$openscript = "record-stock-trans.php";
			break;
		default:
			return "<li class='err'>Invalid Use Of Module. Please Select A Transaction Type.</li>";
	}


	$dopopup = "
					<script>
						popupOpen ('$openscript','window1','width=930,height=600');
					</script>
				";

	$display = "
					$dopopup
					<h2>Current Recorded Transactions</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form1'>
						<input type='hidden' name='key' value='record'>
						<input type='hidden' name='transtype' value='$transtype'>
						<tr>
							<th>Debit Account</th>
							<th>Credit Account</th>
							<th>Transaction Date</th>
							<th>Reference Number</th>
							<th>Amount</th>
							<th>Details</th>
						</tr>
						$listing
					</form>
					</table>
				";
	return $display;

}


?>
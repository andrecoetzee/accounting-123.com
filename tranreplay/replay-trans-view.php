<?

require ("../settings.php");

$OUTPUT = show_trans ($HTTP_POST_VARS);

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql("record-trans.php", "Add Replay Transaction"),
				ql("export-xml.php", "Export Replay Transactions To File"),
				ql("replay-file-trans.php", "Replay Transaction File")
			);

require ("../template.php");



function show_trans ()
{

	db_conn ("exten");

	$get_trans = "SELECT * FROM tranreplay ORDER BY id";
	$run_trans = db_exec($get_trans) or errDie("Unable to get transaction listing.");
	if(pg_numrows($run_trans) < 1){
		$listing = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='8'>No Transactions Found.</td>
						</tr>
					";
	}else {

		$listing = "";
		while ($tarr = pg_fetch_array($run_trans)){

			db_conn ("core");

			if(($tarr['debitacc'] != "0") AND ($tarr['debitacc'] != "1")){
				#get account info
				$get_acc = "SELECT * FROM accounts WHERE accid = '$tarr[debitacc]' LIMIT 1";
				$run_acc = db_exec($get_acc) or errDie("Unable to get debit account information.");
				if(pg_numrows($run_acc) > 0){
					$aarr = pg_fetch_array($run_acc);
					$showdebit = $aarr['accname'];
				}else {
					$showdebit = "";
				}
			}else {
				$showdebit = "";
			}

			if(($tarr['creditacc'] != "0") AND ($tarr['creditacc'] != "1")){
				#get account info
				$get_acc = "SELECT * FROM accounts WHERE accid = '$tarr[creditacc]' LIMIT 1";
				$run_acc = db_exec($get_acc) or errDie("Unable to get credit account information.");
				if(pg_numrows($run_acc) > 0){
					$aarr = pg_fetch_array($run_acc);
					$showcredit = $aarr['accname'];
				}else {
					$showcredit = "";
				}
			}else {
				$showcredit = "";
			}

			db_connect ();

			if($tarr['iid'] != "0"){
				switch ($tarr['ttype']){
					case "stock":
						#get stock info
						$get_stock = "SELECT * FROM stock WHERE stkid = '$tarr[iid]' LIMIT 1";
						$run_stock = db_exec($get_stock) or errDie("Unable to get stock information.");
						if(pg_numrows($run_stock) > 0){
							$sarr = pg_fetch_array($run_stock);
							$showiid = $sarr['stkdes'];
						}else {
							$showiid = "";
						}
						break;
					case "debtor":
						#get debtor information
						$get_debtor = "SELECT * FROM customers WHERE cusnum = '$tarr[iid]' LIMIT 1";
						$run_debtor = db_exec($get_debtor) or errDie("Unable to get debtor information.");
						if(pg_numrows($run_debtor) > 0){
							$darr = pg_fetch_array($run_debtor);
							$showiid = $darr['surname'];
						}else {
							$showiid = "";
						}
						break;
					case "creditor":
						#get supplier information
						$get_supplier = "SELECT * FROM suppliers WHERE supid = '$tarr[iid]' LIMIT 1";
						$run_supplier = db_exec($get_supplier) or errDie("Unable to get supplier information.");
						if(pg_numrows($run_supplier) > 0){
							$sarr = pg_fetch_array($run_supplier);
							$showii = $sarr['supname'];
						}else {
							$showiid = "";
						}
						break;
					default:
						$showiid = "";
				}
			}else {
				$showiid = "Journal";
			}
			$listing .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$showdebit</td>
								<td>$showcredit</td>
								<td>$tarr[tdate]</td>
								<td>$tarr[refno]</td>
								<td>$tarr[amount]</td>
								<td>$tarr[vat]</td>
								<td>$tarr[details]</td>
								<td>$showiid</td>
							</tr>
						";
		}
	}
	
	$display = "
					<h2>Replay Transactions Listing</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<tr>
							<th>Debit Account</th>
							<th>Credit Account</th>
							<th>Transaction Date</th>
							<th>Reference Number</th>
							<th>Amount</th>
							<th>VAT</th>
							<th>Details</th>
							<th>Transaction Involves</th>
						</tr>
						$listing
					</form>
					</table>";
	return $display;
	
}

?>
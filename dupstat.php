<?

	require ("settings.php");
	
	if (isset($_POST["key"])){
		$OUTPUT = run_confirm ();
	}else {
		$OUTPUT = get_confirm ();
	}
	
	require ("template.php");



function get_confirm ()
{


	$display = "
					<h3>Confirm Removal Of Duplicate Statement Entries</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<td colspan='2'><li class='err'>Please Ensure You Have A Current Backup Of All Your Data</li></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Confirm'></td>
						</tr>
					</form>
					</table>
			";
	return $display;

}



function run_confirm ()
{

	db_connect ();

	$get_dup1 = "SELECT * FROM stmnt WHERE type = 'Payment Received.' AND invid = '0'";
	$run_dup1 = db_exec($get_dup1) or errDie("Unable to get statement information.");
	if (pg_numrows($run_dup1) < 1){
		# 0 entries found ... do nothing
	}else {
		#go through each and check for matching amount ...
		while ($d1arr = pg_fetch_array ($run_dup1)){
			$get_dup2 = "SELECT * FROM stmnt WHERE amount = '$d1arr[amount]' AND cusnum = '$d1arr[cusnum]' AND id != '$d1arr[id]' AND type LIKE '%Payment for Invoice No.%' AND date = '$d1arr[date]' LIMIT 1";
			$run_dup2 = db_exec($get_dup2) or errDie ("Unable to get statement info");
			if (pg_numrows($run_dup2) > 0){
				#found a duplicate payment
				$rem_orig_dup = "DELETE FROM stmnt WHERE id = '$d1arr[id]'";
				$run_orig_dup = db_exec($rem_orig_dup) or errDie ("Unable to remove duplicate statement entry.");
			}
		}
	}
	return "DONE";

}

?>
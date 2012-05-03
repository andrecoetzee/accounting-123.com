<?

require ("settings.php");

if (isset ($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			$OUTPUT = save_note ();
			break;
		default:
			$OUTPUT = view_note();
	}
}else {
	$OUTPUT = view_note ();
}

require ("template.php");



function view_note ()
{

	extract ($_REQUEST);

	if (!isset ($cusnum) OR strlen ($cusnum) < 1){
		return "Invalid Use Of Module. Invalid Customer Number.";
	}

	db_connect ();

	$get_cust_note = "SELECT * FROM customers_note WHERE cusnum = '$cusnum' LIMIT 1";
	$run_cust_note = db_exec ($get_cust_note) or errDie ("Unable to get customer note information.");
	if (pg_numrows ($run_cust_note) > 0){
		$custarr = pg_fetch_array ($run_cust_note);
		$note = $custarr['note'];
	}

	$display = "
		<h3>Customer Note</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='cusnum' value='$cusnum'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Note</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><textarea name='note' cols='60' rows='12'>$note</textarea></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='center'><input type='submit' value='Save & Close'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function save_note ()
{

	extract ($_REQUEST);

	db_connect ();

	$check_sql = "SELECT * FROM customers_note WHERE cusnum = '$cusnum' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get customer note information.");
	if (pg_numrows ($run_check) > 0){
		$upd_sql = "UPDATE customers_note SET note = '$note' WHERE cusnum = '$cusnum'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to get customer information.");
	}else {
		$ins_sql = "INSERT INTO customers_note (cusnum,note) VALUES ('$cusnum', '".pg_escape_string($note)."')";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record customer note information.");
	}

	print "
		<script>
			window.close();
		</script>";

}


?>
<?

require ("../settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "remove":
			$OUTPUT = remove_trans ($_POST);
			break;
		default:
	}
}else {
	$OUTPUT = get_confirm();
}

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql("record-trans.php", "Add Replay Transaction"),
				ql("export-xml.php", "Export Replay Transactions To File"),
				ql("replay-file-trans.php", "Replay Transaction File")
			);

require ("../template.php");




function get_confirm ()
{

	$display = "
					<h2>Confirm Removal Of All Replay Transactions</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='remove'>
						<tr>
							<td><li class='err'>Pressing Confirm Will Remove All Replay Transactions Currently Recorded.</li></td>
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



function remove_trans ($_POST)
{

	extract ($_POST);

	db_conn("exten");

	$rem_sql = "DELETE FROM tranreplay";
	$run_rem = db_exec($rem_sql) or errDie("Unable to remove replay entries.");

	$display = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Removed</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>All Replay Transactions Have Been Removed.</td>
						</tr>
					</table><p>
				";
	return $display;

}


?>
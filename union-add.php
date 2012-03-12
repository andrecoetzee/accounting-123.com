<?

require("settings.php");

if (isset ($_POST["key"])){
	switch ($_POST["key"]){
		case "write":
			$OUTPUT = write_union ($_POST);
			break;
		default:
			$OUTPUT = get_union ();
	}
}else {
	$OUTPUT = get_union ();
}

require ("template.php");




function get_union ()
{

	$display = "
		<h4>Add Union Information</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Union Information</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Union Name</td>
				<td><input type='text' size='20' name='union_name' value='$union_name'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Union Rate</td>
				<td><input type='text' size='10' name='union_rate' value='$union_rate'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function write_union ($_POST)
{

	extract ($_POST);

	db_connect ();

	$ins_sql = "INSERT INTO unions (union_name,date_added,req_perc) VALUES ('$union_name','now','$union_rate')";
	$run_ins = db_exec ($ins_sql) or errDie ("Unable to record union information.");

	return "
		<script>
			window.opener.document.form1.submit();
			window.close();
		</script>";
}


?>
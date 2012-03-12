<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# Get settings
require ("settings.php");
require ("libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm_addr ($_POST);
			break;
		default:
			$OUTPUT = "<li>Invalid use of module</li>";
	}
} else {
	$OUTPUT = edit_addr ($_GET);
}

# display output
require ("template.php");

function edit_addr($_GET, $err="")
{

	extract ($_GET);

	if(!isset($invid) OR (strlen($invid) < 1))
		invalid_use ();

	#get the current del_addr

	db_connect ();

	$get_addr = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."' LIMIT 1";
	$run_addr = db_exec($get_addr) or errDie("Unable to get invoice information");
	if(pg_numrows($run_addr) < 1){
		$del_addr = "";
	}else {
		$arr = pg_fetch_array($run_addr);
		$del_addr = $arr['del_addr'];
	}

	$display = "
		<h2>Edit Current Delivery Address</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='invid' value='$invid'>
			<tr>
				<td><textarea name='del_addr' cols='50' rows='5'>$del_addr</textarea></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Save & Close &raquo'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function confirm_addr ($_POST)
{

	extract ($_POST);

	if(!isset($invid) OR (strlen($invid) < 1))
		invalid_use ();

	db_connect ();

	#update database
	$update_sql = "UPDATE invoices SET del_addr = '$del_addr' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$run_update = db_exec($update_sql) or errDie("Unable to update delivery address");

	return "
		<script>
			opener.document.form.submit()
			window.close()
		</script>";


}







?>

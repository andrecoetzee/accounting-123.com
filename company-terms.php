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

require ("settings.php");

if ($_POST) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default : 
			$OUTPUT = enter ($_GET);
        }
}else {
	$OUTPUT = enter ($_GET);
}

require ("template.php");



function enter ($_GET)
{

	# get vars
	extract ($_GET);

	if(!isset($terms)){
		db_connect ();
		$sql = "SELECT terms FROM compinfo";
		$rs = db_exec($sql) or errDie ("Unable To Read Terms");
		if (pg_numrows($rs) < 1){
			$terms = "";
		}else{
			$compdata = pg_fetch_array($rs);
			$terms = $compdata['terms'];
		}
	}

	$enter = "
				<h3>Company Terms</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<tr>
						<th colspan=2>Terms</th>
					</tr>
					<tr>
						<td><textarea rows='10' cols='60' name='terms' maxlength='200'>$terms</textarea>
					</tr>
					".TBL_BR."
					<tr>
						<td><input type='submit' value='Confirm'></td>
					</tr>
				</form>
				</table>
				<p>
				<tr>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</tr>
	";
	return $enter;

}



function confirm ($_POST)
{

	extract ($_POST);

	$terms = remval($terms);

	if (strlen($terms) > 1024){
		return "<h3>Company Terms Are Too Long</h3>";
	}

	$confirm = "<h3>Confirm Company Terms</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST'>
	        		<input type='hidden' name='key' value='write'>
					<input type='hidden' name='terms' value='$terms'>
					<tr>
						<th colspan='2'>Terms</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><pre>".wordwrap($terms, 60)."</pre></td>
					</tr>
					".TBL_BR."
					<tr>
						<td><input type='submit' value='Write'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</tr>
				";
	return $confirm;

}



function write ($_POST)
{

	# get vars
	extract ($_POST);

	$terms = remval($terms);

	if (strlen($terms) > 1024){
		return "<h3>Company Terms Too Long</h3>";
	}

	db_connect ();

	$sql = "UPDATE compinfo SET terms = '$terms'";
	$allow = db_exec($sql) or errDie ("Unable To Update Company Terms");

	return "
				<table ".TMPL_tblDflts.">
					<tr>
						<td><h3>Company Terms have been Successfully Updated</h3></h3></td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";

}

?>

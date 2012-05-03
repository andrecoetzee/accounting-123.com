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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_GET['fcid'])){
				$OUTPUT = edit ($_GET['fcid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['fcid'])){
		$OUTPUT = edit ($_GET['fcid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

require ("template.php");

##
# functions
##

# Enter settings
function edit($fcid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fcid, "num", 1, 20, "Invalid Currency.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	
	# Select Stock
	db_connect();

	$sql = "SELECT * FROM currency  WHERE fcid = '$fcid'";
	$curRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($curRslt) < 1){
		return "<li> Invalid Carrency.</li>";
	}else{
		$cur = pg_fetch_array($curRslt);
	}
	
	$Sl = "SELECT * FROM customers WHERE fcid='$fcid'";
	$Ri = db_exec($Sl);
	
	if(pg_num_rows($Ri) > 0) {
		return "<li class='err'>There are customers in the system with selected currency</li>";
	}
	
	$Sl = "SELECT * FROM suppliers WHERE fcid='$fcid'";
	$Ri = db_exec($Sl);
	
	if(pg_num_rows($Ri)>0) {
		return "<li class='err'>There are supliers in the system with selected currency</li>";
	}
	
	# Connect to db
	$enter = "
		<h3>Confirm Remove Currency</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='fcid' value=$cur[fcid]>
			<input type='hidden' name='key' value='write'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
		<tr class='".bg_class()."'>
			<td>Currency Name</td>
			<td>$cur[descrip]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Symbol</td>
			<td>$cur[symbol]</td>
		</tr>
		<tr><td><br></td></tr>
		<tr>
			<td align='right' colspan='2'><input type='submit' value='Remove &raquo'></td>
		</tr>
	</form>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='currency-view.php'>View Currency</a></td>
		</tr>
		<tr class='".bg_class()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";
	return $enter;

}



# write to db
function write ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fcid, "num", 1, 20, "Invalid Currency.");
	
	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM currency  WHERE fcid = '$fcid'";
	$curRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($curRslt) < 1){
		return "<li> Invalid Carrency.</li>";
	}else{
		$cur = pg_fetch_array($curRslt);
	}
	
	$Sl = "SELECT * FROM customers WHERE fcid='$fcid'";
	$Ri = db_exec($Sl);
	
	if(pg_num_rows($Ri) > 0) {
		return "<li class='err'>There are customers in the system with selected currency</li>";
	}
	
	$Sl = "SELECT * FROM suppliers WHERE fcid='$fcid'";
	$Ri = db_exec($Sl);
	
	if(pg_num_rows($Ri) > 0) {
		return "<li class='err'>There are supliers in the system with selected currency</li>";
	}

	# Connect to db
	db_connect ();

	$Sql = "DELETE FROM currency WHERE fcid = '$fcid'";
	$setRslt = db_exec ($Sql) or errDie ("Unable to update currency to Cubit.");
	

	# status report
	$write = "
	<table ".TMPL_tblDflts." width='50%'>
		<tr>
			<th>Currency Removed</th>
		</tr>
		<tr class='datacell'>
			<td>Currency $cur[descrip] $cur[symbol] has been removed.</td>
		</tr>
	</table>
	<p>
	<tr>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='#88BBFF'>
			<td><a href='currency-view.php'>View Currency</a></td>
		</tr>
		<tr bgcolor='#88BBFF'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";
	return $write;

}


?>

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
		default:
			$OUTPUT = enter();
        }
} else {
	$OUTPUT = enter();
}

require ("template.php");

##
# functions
##

# Enter settings
function enter()
{
	$warn = "checked=yes";
	$block = "";

	db_connect();
	$sql = "SELECT * FROM set WHERE label = 'CUST_INV_WARN'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed
	if(pg_numrows($rslt) > 0){
		$set = pg_fetch_array($rslt);
		if($set['value'] == 'block'){
			$warn = "";
			$block = "checked=yes";
		}
	}

	# Connect to db
	$enter = "
			<h3>Cubit Settings</h3>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<input type=hidden name=key value=confirm>
				<tr><th colspan=2>Warn/Block on Customers Reaching Credit Limit</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td><input type=radio size=20 name=choice value='warn' $warn>Warn on Reaching Limit</td>
					<td><input type=radio size=20 name=choice value='block' $block>Block on Reaching Limit</td>
				</tr>
				<tr><td><br></td></tr>
				<tr><td align=right colspan=2><input type=submit value='Continue &raquo'></td></tr>
			</form>
			</table>
			<p>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $enter;

}


# confirm entered info
function confirm($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($choice, "string", 1, 50, "Invalid Selection.");

	# display errors, if any
	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class='err' colspan='2'>$theseErrors</td></tr>
		<tr><td colspan='2'><br></td></tr>";
		return entererr($accc, $Errors);
	}

	if($choice == 'block'){
		$typ = "Block on Reaching Credit Limit";
	}else{
		$typ = "Warn on Reaching Credit Limit";
	}

	$confirm = "
			<h3>Cubit Settings</h3>
			<h4>Confirm</h4>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method='post'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='choice' value='$choice'>
				<tr><th colspan>Response</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$typ</td></tr>
				<tr><td><br></td></tr>
				<tr><td align=right colspan=2><input type=submit value='Confirm &raquo'></td></tr>
			</form>
			</table>
			<p>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";

	return $confirm;
}

# write to db
function write ($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($choice, "string", 1, 50, "Invalid Selection.");

	# Display errors, if any
	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class=err colspan=2>$theseErrors</td></tr>
		<tr><td colspan=2><br></td></tr>";
		return entererr($accc, $Errors);
	}

	if($choice == 'block'){
		$descript = "Block on Reaching Credit Limit";
	}else{
		$descript = "Warn on Reaching Credit Limit";
	}

	# Connect to db
	db_connect ();

	# Check if setting exists
	$sql = "SELECT label FROM set WHERE label = 'CUST_INV_WARN' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = '$choice', descript = '$descript' WHERE label = 'CUST_INV_WARN' AND div = '".USER_DIV."'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Stock Purchases Approval', 'CUST_INV_WARN', '$choice', '$descript', '".USER_DIV."')";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	# status report
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Cubit Settings</th></tr>
		<tr class=datacell><td>Setting have been successfully added to Cubit.</td></tr>
	</table>
	<p>
	<tr>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='set-view.php'>View Settings</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
        return $write;

}


?>

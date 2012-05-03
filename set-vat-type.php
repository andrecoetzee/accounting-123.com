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

	$chin = "checked=yes";
	$chex = "";

	db_connect();

	$sql = "SELECT * FROM set WHERE label = 'SELAMT_VAT'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed
	if(pg_numrows($rslt) > 0){
		$set = pg_fetch_array($rslt);
		if($set['value'] == 'exc'){
			$chin = "";
			$chex = "checked=yes";
		}
	}

	# Connect to db
	$enter = "
		<h3>Cubit Settings</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr><th colspan='2'>Vat type on stock selling price</th></tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='typ' value='Inclusive' $chin>Vat Inclusive</td>
				<td><input type='radio' size='20' name='typ' value='Exclusive' $chex>Vat Exclusive</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $enter;

}



# confirm entered info
function confirm($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Vat type Selection.");

	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class='err' colspan='2'>$theseErrors</td></tr>
		<tr><td colspan=2><br></td></tr>";
		return entererr($accc, $Errors);
	}

	$confirm = "
		<h3>Cubit Settings</h3>
		<h4>Confirm</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='typ' value='$typ'>
			<tr>
				<th colspan>Vat type on stock selling price</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align=center>Vat $typ</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write to db
function write ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Vat type Selection.");

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

	$descript = "Vat $typ";

	# Connect to db
	db_connect ();

	$typ = strtolower(substr($typ, 0, 3));

	# Check if setting exists
	$sql = "SELECT label FROM set WHERE label = 'SELAMT_VAT' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = '$typ', descript = '$descript' WHERE label = 'SELAMT_VAT' AND div = '".USER_DIV."'";
	}else{
		$Sql = "
			INSERT INTO set (
				type, label, value, descript, div
			) VALUES (
				'Vat type on stock selling price', 'SELAMT_VAT', '$typ', '$descript', '".USER_DIV."'
			)";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	# status report
	$write ="
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Cubit Settings</th>
			</tr>
			<tr class='datacell'>
				<td>Setting have been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<tr>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='set-view.php'>View Settings</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
        return $write;

}



function printSet ()
{
	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE label = 'DEBT_AGE'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class=err> No Setting currently in database.";
	} else {
		$set = pg_fetch_array ($rslt);

		$OUTPUT = "
			<h3>Settings</h3>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Setting Type</th>
					<th>Current Setting</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>$set[type]</td>
					<td>$set[descript]</td>
				</tr>
			</table>";
	}

	$OUTPUT .= "
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUTPUT;

}


?>

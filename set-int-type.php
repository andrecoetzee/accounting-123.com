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
require ("core-settings.php");

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
	/*
	db_connect();
	# check if setting exists
	$sql = "SELECT label FROM set WHERE label = 'INT_TYPE'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		return printSet();
	}
	*/

	core_connect();


	$sql = "SELECT * FROM accounts WHERE acctype ='I' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$numrows = pg_numrows($accRslt);
	if(empty($numrows)){
		return "<li>ERROR : There are no income accounts in Cubit.</li>";
	}
	$slctAcc = "<select name='accid'>";
	while($acc = pg_fetch_array($accRslt)){
		if(isb($acc['accid'])) {
			continue;
		}
		$slctAcc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$slctAcc .= "</select>";

	# Connect to db
	$enter = "
		<h3>Cubit Settings</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='3'>Interest Calculation</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='typ' value='perc' checked='yes'>Use Percentage <input type='text' name='perc' value='0' size='4'> %</td>
				<td><input type='radio' size='20' name='typ' value='brac'>Use Interest Brackets</td>
				<td><input type='radio' size='20' name='typ' value='rate'>Use Customer Specific rate | Default rate <input type='text' name='dperc' value='0' size='4'>%</td>
			</tr>
			<tr><td><br></td></tr>
			<tr class='".bg_class()."'>
				<td>Interest Received Account</td>
				<td>$slctAcc</td>
				<td><br></td>
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

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Interest calculation selection.");
	$v->isOk ($perc, "float", 0, 50, "Invalid interest percentage.");
	$v->isOk ($dperc, "float", 0, 50, "Invalid Default interest percentage.");
	$v->isOk ($accid, "num", 1, 70, "Invalid Account Number.");
	$perc += 0; // nasty zero
	$dperc += 0; // nasty zero

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

	# Get account name for thy lame User's Sake
	$accRslt = get("core", "*", "accounts", "accid", $accid);
	$acc = pg_fetch_array($accRslt);


	$confirm = "
		<h3>Cubit Settings</h3>
		<h4>Confirm</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='typ' value='$typ'>
			<input type='hidden' name='perc' value='$perc'>
			<input type='hidden' name='dperc' value='$dperc'>
			<input type='hidden' name='accid' value='$accid'>
			<tr>
				<th colspan='2'>Interest Calculation</th>
			</tr>
			<tr class='".bg_class()."'>";

	if($typ == "perc"){
		$confirm .= "<td colspan='2'>Use Interest Percentage $perc %</td>";
	}elseif($typ == "rate"){
		$confirm .= "<td colspan='2'>User Customer Specific rate | Default rate $dperc %</td>";
	}else{
		$confirm .= "<td colspan='2'>Use Interest Brackets</td>";
	}

	$confirm .= "
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Interest Received Account</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
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




# write user to db
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Interest Calculation selection.");
	$v->isOk ($perc, "num", 0, 50, "Invalid interest percentage.");
	$v->isOk ($dperc, "float", 0, 50, "Invalid Default interest percentage.");
	$v->isOk ($accid, "num", 1, 70, "Invalid Account Number.");

	# display errors, if any
	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "
			<tr>
				<td class='err' colspan='2'>$theseErrors</td>
			</tr>
			<tr><td colspan='2'><br></td></tr>";
		return entererr($accc, $Errors);
	}



	if($typ == "perc"){
		$descript = "Use Interest Percentage $perc %";
		$typ = $perc;
	}elseif($typ == "rate"){
		$descript = "User Customer Specific rate | Default rate $dperc %";
		$typ = "r".$dperc;
	}else{
		$descript = "Use Interest Brackets";
	}

	# Connect to db
	db_connect ();

	# Check if setting exists
	$sql = "SELECT label FROM set WHERE label = 'INT_TYPE'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = '$typ', descript = '$descript' WHERE label = 'INT_TYPE' AND div = '".USER_DIV."'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Interest Calculation', 'INT_TYPE', '$typ', '$descript', '".USER_DIV."')";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	core_connect();

	$link = "DELETE FROM salesacc WHERE name = 'SalesInt' AND div = '".USER_DIV."';INSERT INTO salesacc(name, accnum, div) VALUES('SalesInt', '$accid', '".USER_DIV."')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Sales Account link to Database.", SELF);

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
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='set-view.php'>View Settings</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
//	return $write;
	header ("Location: calc-int.php");

}




function printSet ()
{

	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE label = 'INT_TYPE'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class='err'> No Setting currently in database.";
	} else {
		// Set up table to display in
		$OUTPUT = "
			<h3>Settings</h3>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Setting Type</th>
					<th>Current Setting</th>
				</tr>";

		$set = pg_fetch_array ($rslt);

		$OUTPUT .= "
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
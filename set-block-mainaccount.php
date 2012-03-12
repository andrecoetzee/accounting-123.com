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
	# Connect to db
	$enter = "<h3>Cubit Settings</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Block Main accounts</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=radio size=20 name=typ value='use' checked=yes>Block</td>
		<td><input type=radio size=20 name=typ value='nuse'>Don't Block</td></td></tr>
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
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Interest calculation selection.");

    # display errors, if any
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

	$confirm ="<h3>Cubit Settings</h3>
	<h4>Confirm</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=typ value='$typ'>
	<tr><th colspan=2>Block main accounts</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>";

	if($typ == "use"){
		$confirm .= "<td colspan=2>Block</td>";
	}else{
		$confirm .= "<td colspan=2>Don't Block</td>";
	}

	$confirm .= "</tr>
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

# write user to db
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typ, "string", 1, 50, "Invalid Interest Calculation selection.");

    # display errors, if any
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

	if($typ == "use"){
		$descript = "Block main accounts";
	}else{
		$descript = "Dont block main accounts";
	}

	# Connect to db
	db_connect ();

	# Check if setting exists
	$sql = "SELECT label FROM set WHERE label = 'BLOCK'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = '$typ', descript = '$descript' WHERE label = 'BLOCK' AND div = '".USER_DIV."'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Block main accounts', 'BLOCK', '$typ', '$descript', '".USER_DIV."')";
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

function printSet ()
{
	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE label = 'CC_USE'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class=err> No Setting currently in database.";
	} else {
		// Set up table to display in
		$OUTPUT = "
		<h3>Settings</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Setting Type</th><th>Current Setting</th></tr>";

		$set =pg_fetch_array ($rslt);
		$bgColor = TMPL_tblDataColor1;

		$OUTPUT .= "<tr bgcolor='$bgColor'><td>$set[type]</td><td>$set[descript]</td></tr>";
		$OUTPUT .= "</table>";
	}

	$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}
?>

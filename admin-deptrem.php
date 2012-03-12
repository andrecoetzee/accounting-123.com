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
/*
 * admin-usrrem.php :: Module to remove users
 */

require ("settings.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['deptid'])){
				$OUTPUT = confirm ($_GET['deptid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['deptid'])){
		$OUTPUT = confirm ($_GET['deptid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# require template
require ("template.php");




// Confirm removal
function confirm ($deptid)
{

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 50, "Invalid User Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class='err'>".$e["msg"]."</li>";
		}
		$theseErrors .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

    // Query server
    Db_Connect ();
    $sql = "SELECT * FROM depts WHERE deptid = '$deptid'";
	$depRslt = db_exec($sql) or errDie ("ERROR: Unable to department.", SELF);
    if(pg_numrows($depRslt) < 1){
		return "<li> - Invalid User Department ID.</li>";
    }
	$dep = pg_fetch_array ($depRslt);

	$OUTPUT = "
		<h3>Confirm User Department removal</h3>
		<h4>Are you sure you want to delete this User Department?</h4>
		<p>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='rem'>
			<input type='hidden' name='deptid' value='$deptid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='datacell'>
				<td>User Department</td>
				<td align='center'>$dep[dept]</td>
			</tr>
			<tr>
				<td><br></td>
				<td align='center'><input type='button' value='&laquo; Cancel' onClick='Javascript:history.back();'>&nbsp;<input type='submit' value='Remove Dept &raquo;'></td>
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
	return $OUTPUT;

}




// Removes stock from database
function rem ($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 50, "Invalid User Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class='err'>".$e["msg"]."</li>";
		}
		$theseErrors .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM depts WHERE deptid = '$deptid'";
	$depRslt = db_exec($sql) or errDie ("ERROR: Unable to department.", SELF);
	if(pg_numrows($depRslt) < 1){
		return "<li> - Invalid User Department ID";
	}
	$dep = pg_fetch_array ($depRslt);

	// Query server
	$sql = "DELETE FROM depts WHERE deptid = '$deptid'";
	$RemRslt = db_exec ($sql) or errDie ("ERROR: Unable to delete department: $dep[dept]", SELF);          // Die with custom error if failed

	// Remove department access
	$sql = "DELETE FROM deptscripts WHERE dept = '$deptid'";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to delete access for User Department : $dep[depts]", SELF);

	// Provide some info on status
	$OUTPUT = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>User Department deleted from database</th>
			</tr>
			<tr class='datacell'>
				<td>User Department <b>$dep[dept]</b>, was successfully deleted.</td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUTPUT;

}



?>
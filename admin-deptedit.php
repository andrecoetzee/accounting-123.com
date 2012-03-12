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
 * admin-usredit.php :: Module to edit user details
 */

require ("settings.php");          // Get global variables & functions

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			if(isset($_GET['deptid'])){
				$OUTPUT = edit ($_GET['deptid']);
			}else{
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if(isset($_GET['deptid'])){
		$OUTPUT = edit ($_GET['deptid']);
	}else{
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# require template
require ("template.php");



/*
 * Functions
 *
 */

// Prints a form to edit user with
function edit ($deptid)
{

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
	$depRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit department.", SELF);
    $dep = pg_fetch_array ($depRslt);

    $Out  = "
    	<table ".TMPL_tblDflts." width='300'>
        	<tr>
        		<th colspan='2'>Select Permissions</th>
        	</tr>";

    $sql = "SELECT DISTINCT script, name FROM scripts ORDER by script";
    $rslt = db_exec($sql);
    $i = 0;

    while($scr = pg_fetch_array($rslt)) {

		$Sql = "SELECT script FROM deptscripts WHERE dept = '$deptid' and script = '$scr[name]'";
		$sRs = db_exec($Sql);

		//  print $Sql;
		if (pg_numrows ($sRs) > 0){
			$Ch ="checked";
		}else {
			$Ch = "";
		}

		$Out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' nowrap><input type='checkbox' $Ch name='perm[]' value='$scr[name]'>".strtoupper($scr['script'])."</td>
			</tr>";
		$i++;
    }
    $Out .= "</table>";

    // Layout
	$OUTPUT = "
		<h3>Edit User Department</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='deptid' value='$deptid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='datacell'>
				<td>User Department</td>
				<td align='center'><input type='text' size='20' name='dept' value='$dep[dept]'></td>
			</tr>
			<tr>
				<td><br></td>
				<td align='center'><input type=submit value='Commit changes'>&nbsp;<input type='reset' value='Reset form'></td>
			</tr>
		</table>
		$Out
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUTPUT;

}




// Confirm that entered info is correct
function confirm ($_POST) // Function args
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 50, "Invalid User Department ID.");
    $v->isOk ($dept, "string", 1, 50, "Invalid User Department.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class='err'>".$e["msg"]."</li>";
		}
		$theseErrors .= "
			<p>
			<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
	        <table ".TMPL_tblDflts.">
		        <tr>
		        	<th>Quick Links</th>
		        </tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
		return $theseErrors;
	}

	# connect to db
	db_connect ();

	$confirm = "
        <h3>Edit User Department</h3>
        <h4>Confirm entry</h4>
        <table ".TMPL_tblDflts." width='300'>
        <form action='".SELF."' method='POST'>
	        <input type='hidden' name='key' value='write'>
	        <input type='hidden' name='deptid' value='$deptid'>
	        <input type='hidden' name='dept' value='$dept'>
	        <tr>
	        	<th>Field</th>
	        	<th>Value</th>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>User Department</td>
	        	<td>$dept</td>
	        </tr>
	        <tr><td colspan='2'><br></td></tr>
	        <tr>
	        	<th colspan='2'>Select Permissions</th>
	        </tr>";

    if(isset($perm)){
        foreach($perm as $key => $value){
            $sql = "SELECT script FROM scripts WHERE name = '$value'";
	        $scrRslt = db_exec ($sql);
            $scr = pg_fetch_array ($scrRslt);
            $confirm .= "
            	<tr bgcolor='".bgcolorg()."'>
            		<td colspan='2'><input type='hidden' name='perm[]' value='$value'>$scr[script]</td>
            	</tr>";
         }
    }

	$confirm .= "
			<tr>
				<td align='right' colspan='3'><input type='submit' value='Confirm &raquo'></td>
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
    $v->isOk ($deptid, "num", 1, 50, "Invalid User Department ID.");
    $v->isOk ($dept, "string", 1, 50, "Invalid User Department.");

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

	# connect to db
	db_connect ();

	# exit if dept exists
	$sql = "SELECT dept FROM depts WHERE dept = '$dept' AND deptid != '$deptid'";
	$deptRslt = db_exec ($sql) or errDie ("Unable to check database for existing username.");
	if (pg_numrows ($deptRslt) > 0) {
		return "User Department, $dept, already exists in database.";
	}

    # write dept
    $sql = "UPDATE depts SET dept = '$dept' WHERE deptid = '$deptid'";
	$Rslt = db_exec ($sql) or errDie ("Unable to add dept to database.");

    #remove previous permissions
    $sql = "DELETE FROM deptscripts WHERE dept = '$deptid'";
	$Rslt = db_exec ($sql) or errDie ("Unable to add dept to database.");

    # Write Permissions
    if(isset($perm)){
        foreach($perm as $key => $value){
			$sql = "INSERT INTO deptscripts (dept, script) VALUES ('$deptid', '$value')";
			$nwRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
        }
    }

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>User Department Edited</th>
			</tr>
			<tr class='datacell'>
				<td>User Department <b>$dept</b>, was successfully edited.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='admin-deptadd.php'>Add another User Department</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='admin-deptview.php'>View User Departments</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
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
			$OUTPUT = enter ();
    }
} else {
        $OUTPUT = enter ();
}

require ("template.php");




function enter ($dept = "",$err="")
{

	# connect to db
	db_connect ();

	$enter = "
		<h3>Add new User Department</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			$err
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."User Department</td>
				<td><input type='text' size='20' name='dept' value='$dept'></td>
			</tr>
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
	return $enter;

}




# confirm entered info
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($dept, "string", 1, 50, "Invalid User Department.");

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
        return enter($dept, $Errors);
    }

	$confirm = "
		<h3>Add User Department</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
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

	// list scripts
	db_connect();

	$sql = "SELECT DISTINCT name, script FROM scripts ORDER BY script";
	$rslt = db_exec($sql);
	$i = 0;
	while($scr = pg_fetch_array($rslt)){
		$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' nowrap><input type='checkbox' name='perm[]' value='$scr[name]'>".strtoupper($scr['script'])."</td>
			</tr>";
		$i++;
	}

	$confirm .= "
			<tr>
				<td align='right' colspan='3'><input type='submit' value='Add Dept &raquo'></td>
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
	$v->isOk ($dept, "string", 1, 50, "Invalid User Department.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class='err'>$e[msg]</li>";
		}
		$theseErrors .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}



	# connect to db
	db_connect ();

	# exit if user exists
	$sql = "SELECT dept FROM depts WHERE dept = '$dept'";
	$deptRslt = db_exec ($sql) or errDie ("Unable to check database for existing username.");
	if (pg_numrows ($deptRslt) > 0) {
		return "
			<li class='err'>User Department, $dept, already exists.</li>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='admin-deptadd.php'>Add User Department</a></td>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='admin-deptview.php'>View User Departments</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

    # write dept
    $sql = "INSERT INTO depts (dept) VALUES ('$dept')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add dept to database.");

    $deptid = pglib_lastid("depts","deptid");

    # Write Permissions
	if(isset($perm)){
		foreach($perm as $key => $value){

			#get a description for this permission ...
			$get_desc = "SELECT script FROM scripts WHERE name = '$value' LIMIT 1";
			$run_desc = db_exec($get_desc) or errDie ("Unable to get script description information.");
			if (pg_numrows($run_desc) > 0)
				$script_desc = pg_fetch_result ($run_desc,0,0);
			else 
				$script_desc = "";

			$sql = "INSERT INTO deptscripts (dept, script,scriptname) VALUES ('$deptid', '$value', '$script_desc')";
			$nwRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
        }
    }

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New User Department added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New User Department <b>$dept</b>, was successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='admin-deptadd.php'>Add User Department</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='admin-deptview.php'>View User Departments</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
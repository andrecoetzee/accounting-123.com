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
require ("libs/ext.lib.php");          // Get global variables & functions

// If form was submitted, edit entry or confirm entry or write entry
if ($_GET) {
	if ($_GET['username']) {
		// print form for data entry
		$OUTPUT = editUser ($_GET);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} elseif ($_POST) {
	if ($_POST['a'] == "confirm") {
		// ask for confirmation
		$perm=(isset($_POST['perm'])) ? $_POST['perm'] : '';
		$OUTPUT = confirmUser ($_POST);

	} elseif ($_POST['a'] == "write") {
		// write changes to database
		$OUTPUT = writeUser ($_POST);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} else {
	// Invalid use, display error
	errDie ("ERROR: Invalid use of module.", SELF);
}

# require template
require ("template.php");




function editUser ($_POST)
{

	extract ($_POST);

	$username = substr ($username, 0, 255);

	if(!isset($active_dept))
		$active_dept = "0";

	// check content of variable
	if (preg_match ("/[^\w]/", $username)) {  // Alphanum, 4-10
		$OUTPUT = "Invalid user name.";
	} else {

	 	db_connect ();

		// Query server
		$sql = "SELECT * FROM users WHERE username='$username' AND div='".USER_DIV."'";
		$prnUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $username.", SELF);  // Die with custom error if failed
		if(pg_numrows($prnUsrRslt) < 1){
			return "<li class='err'>Invalid Use Of Module. User Not Found.</li>";
		}

		$myUsr = pg_fetch_array ($prnUsrRslt);

		$sql = "SELECT * FROM depts ORDER BY dept";
		$rslt = db_exec($sql);
		$i = 0;
		$count = 0;

		$dept_drop = "<select name='active_dept' onChange='javascript:document.form1.submit();'>";
		$dept_drop .= "<option value='0'>Select A Department</option>";
		while($darr = pg_fetch_array($rslt)) {
			if($darr['deptid'] == $active_dept){
				$dept_drop .= "<option value='$darr[deptid]' selected>$darr[dept]</option>";
			}else {
				$dept_drop .= "<option value='$darr[deptid]'>$darr[dept]</option>";
			}
		}
		$dept_drop .= "</select>";

		if(!isset($active_dept) OR $active_dept == "0"){
			$department = "";
		}else {
			$department = "";
			$get_dept = "SELECT dept FROM depts WHERE deptid = '$active_dept' LIMIT 1";
			$run_dept = db_exec($get_dept) or errDie ("Unable to get department information.");
			if(pg_numrows($run_dept) < 1){
				return "<li class='err'>Department Information Not Found.</li>";
			}else {
				$dept_name = pg_fetch_result ($run_dept,0,0);
			}
			
			$department .= "
				<tr>
					<th colspan='2'>Select User Permissions</th>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='2'><input type='submit' name='deps[$active_dept]' value='Add'><input type='submit' name='depsrem[$active_dept]' value='Remove'> $dept_name</td>
				</tr>";

			$get_scripts = "SELECT * from deptscripts WHERE dept = '$active_dept' ORDER BY script,scriptname";
			$run_scripts = db_exec($get_scripts) or errDie ("Unable to get department script permission information.");
			if(pg_numrows($run_scripts) < 1){
				return "<li class='err'>Department Has No Permission Scripts.</li>";
			}else {
				while($scr = pg_fetch_array($run_scripts)) {

					$Tp['script'] = $scr['scriptname'];

					#check if this script should be ticked ...
					$Sql = "SELECT script FROM userscripts WHERE username='$username' and script='$scr[script]' LIMIT 1";
					$Ex = db_exec($Sql);
					if (pg_numrows ($Ex) > 0) {$Ch ="checked";}else{$Ch="";}

					$department .= "
						<tr class='".bg_class()."'>
							<td>..... <input type='checkbox' name='perm[]' $Ch value='$scr[script]'> $Tp[script]</td>
						</tr>";

				}
			}
		}

		# Connect to db
		db_connect ();

		$tarr = array("Yes" => "Yes", "No" => "No");
		$tsel = extlib_cpsel("tool", $tarr, $myUsr['help']);

		$sql = "SELECT empnum, enum, sname, fnames FROM cubit.employees";
		$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employees.");

		$employee_sel = "
			<select name='empnum'>
				<option value='0'>[None]</option>";
		while ($emp_data = pg_fetch_array($emp_rslt)) {
			if ($myUsr["empnum"] == $emp_data["empnum"]) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			
			$employee_sel .= "<option value='$emp_data[empnum]' $sel>$emp_data[sname] $emp_data[fnames] - $emp_data[enum]</option>";
		}
		$employee_sel .= "</select>";

		$pgroups_arr = explode (",",$myUsr['payroll_groups']);

		$get_pays = "SELECT * FROM emp_groups ORDER BY emp_group";
		$run_pays = db_exec ($get_pays) or errDie ("Unable to get payroll group information.");
		if (pg_numrows ($run_pays) < 1){
			$payroll_groups = "
				<tr>
					<td colspan='2'>No Payroll Groups Found.</td>
				</tr>";
		}else {
			$payroll_groups = "";
			while ($garr = pg_fetch_array ($run_pays)){

				$checked = "";
				if (in_array("$garr[id]",$pgroups_arr)) 
					$checked = "checked='yes'";

				$payroll_groups .= "
					<tr class='".bg_class()."'>
						<td>".ucfirst($garr['emp_group'])."</td>
						<td><input type='checkbox' name='payroll_group[$garr[id]]' value='$garr[id]' $checked></td>
					</tr>";
			}
		}
		$payroll_groups .= "<tr><td><br></td></tr>";

		# Set up table & form for edit (a is action, so the script knows what to do)
		$OUTPUT = "
			<h3>Edit user</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form1'>
				<input type='hidden' name='a' value='confirm'>
				<input type='hidden' name='username' value='$username'>
				<input type='hidden' name='oldusrnme' value='$username'>
				<input type='hidden' name='old_dept' value='$active_dept'>
				<input type='hidden' name='div' value='2'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='datacell'>
					<td>Username</td>
					<td align='center'><input type='text' size='20' name='username' value='$username'></td>
				</tr>
				<tr class='datacell2'>
					<td>Password</td>
					<td align='center'>
						<table border='0' cellpadding='2' cellspacing='0'>
							<tr>
								<td><input type='radio' name='chgpass' value='no' checked></td>
								<td colspan='2'>Don't change password</td>
							</tr>
							<tr>
								<td>Or</td>
							</tr>
							<tr>
								<td><input type='radio' name='chgpass' value='yes'></td>
								<td>Password</td>
								<td><input type='password' size='20' name='password' value=''></td>
							</tr>
							<tr>
								<td><br></td>
								<td>Confirm password</td>
								<td><input type='password' size='20' name='password2' value=''></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Show Tooltips</td>
					<td>$tsel</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Employee</td>
					<td>$employee_sel</td>
				</tr>
				<tr>
					<td><br></td>
					<td align='center'><input type='submit' name='next' value='Commit changes'>&nbsp;<input type='reset' value='Reset form'></td>
				</tr>
				".TBL_BR."
			</table>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Payroll Group Permissions</th>
				</tr>
				$payroll_groups
			</table>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Departments</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>$dept_drop</td>
				</tr>
				".TBL_BR."
			</table>
			<table ".TMPL_tblDflts.">
				$department
			</table>
			<tr>
				<td><br></td>
				<td align='center'><input type='submit' name='next' value='Commit changes'></td>
			</tr>
			</form>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}
	return $OUTPUT;

}



// Confirm that entered info is correct
function confirmUser ($_POST) // Function args
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($oldusrnme, "string", 1, 20, "Invalid old username.");
	$v->isOk ($username, "string", 1, 20, "Invalid username.");
	$v->isOk ($chgpass, "string", 2, 3, "Tempering with 'change pass' detected.");

	# change to upper case
	$chgpass = strtoupper($chgpass);

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

	$OUTPUT = "";
	db_conn("cubit");
	if ($chgpass == "YES") {
                $v->isOk ($password, "string", 1, 20, "Invalid password.");
        	$v->isOk ($password2, "string", 1, 20, "Invalid password.");
                $v->pwMatch ($password, $password2, "Passwords do not match.");

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

                # make MD#5 of new password
                $MD5_PASS = md5 ($password);
        } else {
		$sql = db_exec("SELECT password FROM users WHERE username='$oldusrnme'");
                if ( pg_num_rows($sql) < 1 )
			errDie("No such user :/", SELF);
		$MD5_PASS = pg_result($sql, 0, 0);
	}

	$_POST['MD5_PASS'] = $MD5_PASS;
	$_POST['empnum'] = $empnum;
	$_POST['tool'] = $tool;

	// write user
	$OUTPUT .= writeUser($_POST);

	db_connect ();

	#we only remove the department that the user selected ...
	$get_dept_scripts = "SELECT script FROM deptscripts WHERE dept = '$old_dept'";
	$run_dept_scripts = db_exec($get_dept_scripts) or errDie ("Unable to get department script information.");
	if(pg_numrows($run_dept_scripts) < 1){
		#no scripts for this department
	}else {
		while ($ddarr = pg_fetch_array ($run_dept_scripts)){
			$Sql = "DELETE FROM userscripts WHERE username='$username' AND script = '$ddarr[script]'";
			$Ex = db_exec($Sql) or errDie ("Unable to clear old user script permissions.");
		}
	}



//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'top_menu.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'getimg.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'diary.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'diary-day.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'glodiary.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'glodiary-day.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'todo.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'index_die.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
//	$Sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', 'index-services.php', '".USER_DIV."')";
//	$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

	#add permissions from this department
	if (isset($perm) AND ($perm != '')) {
		foreach($perm as $key => $value){
			$sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', '$value', '".USER_DIV."')";
			$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
		}
	}

	#add whole department if they were selected
	if(isset($deps)){
		foreach($deps as $key => $value){
			$sql = "SELECT script FROM deptscripts WHERE dept = '$key'";
			$depRs = db_exec($sql);

			while($depscr = pg_fetch_array($depRs)){
				$sql = "INSERT INTO userscripts (username, script, div) VALUES ('$username', '$depscr[script]', '".USER_DIV."')";
				$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
			}
		}
	}

	#remove whole departments if they were selected
	if(isset($depsrem)){
		foreach($depsrem as $key => $value){
			$sql = "SELECT script FROM deptscripts WHERE dept = '$key'";
			$depRs = db_exec($sql);

			while($depscr = pg_fetch_array($depRs)){
				$sql = "DELETE FROM userscripts WHERE username='$username' AND script='$depscr[script]'";
				$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
			}
		}
	}

	// Provide some info on status
	$OUTPUT = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Committed changes to user</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>User, $username, was successfully edited.</td>
			</tr>
		</table>";
	$OUTPUT .= editUser($_POST);
	return $OUTPUT;

}



function writeUser ($_POST)
{

	extract ($_POST);

	// Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	$oldusrnme = substr ($oldusrnme, 0, 255);
	$username    = substr ($username,    0, 255);
	$MD5_PASS  = substr ($MD5_PASS,  0, 32);

	// Do some regex checking to make sure the stuff entered is ok
	if (preg_match ("/[^\w]/", $oldusrnme)) {                           // Alphanum, 4-10
		errDie ("ERROR: Tampering with 'oldusrnme' suspected.", SELF);
	} elseif (preg_match ("/[^\w]/", $username)) {                       // Alphanum, 4-10
		$OUTPUT = "Invalid user name.\n<br><a href='Javascript:history.back();'>Back</a>\n";
	} elseif (preg_match ("/[^\w]/", $MD5_PASS)) {                     // Alphanum, 32
		$OUTPUT = "Invalid password.\n<br><a href='Javascript:history.back();'>Back</a>\n";
	} else {
		// if everything went fine above, write new user to database
		db_connect ();

		$payroll_group_sql = ", payroll_groups = '".implode (",",$payroll_group)."'";

		$sql = "UPDATE users SET username='$username', password='$MD5_PASS', empnum='$empnum', help = '$tool', div = '$div' $payroll_group_sql  WHERE username='$oldusrnme' ";
		$nwUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $oldusrnme", SELF);          // Die with custom error if failed

		# update the permissions database
		$sql = "UPDATE userscripts SET username='$username' WHERE username='$oldusrnme'";
		$nwUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $oldusrnme", SELF);          // Die with custom error if failed
	}
	return "";

}


?>
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
# admin-usradd.php :: Module to add users to the system
##

require ("settings.php");

if ($HTTP_POST_VARS) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmUser ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeUser ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterUser ();
        }
}
else {
	$OUTPUT = enterUser ();
}

require ("template.php");



##
# functions
##

# enter new users details
function enterUser ($username="", $err="")
{

	db_connect ();

	$brans = "<select name='div'>";
	$sql = "SELECT * FROM branches ORDER BY branname ASC";
	$branRslt = db_exec($sql);
	if(pg_numrows($branRslt) < 1){
		return "<li>There are no branches in Cubit.";
	}else{
		while($bran = pg_fetch_array($branRslt)){
			// Defaults to head office
			if ($bran["div"] == 2) {
				$selected = "selected";
			} else {
				$selected = "";
			}

			$brans .= "<option value='$bran[div]' $selected>($bran[brancod]) $bran[branname]</option>";
		}
	}
	$brans .= "</select>";

	// Locale stuff ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_LOCALE'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve the default locale from Cubit.");
	$locale_user = pg_fetch_result($rslt, 0);

	if (empty($locale_user)) {
		$locale_user = "en_ZA";
	}

	require ("locale_codes.php");

	// Retrieve list of locales from the locales directory
	define("LOCALE_DIR", "./locale");
	$h_dir = opendir(LOCALE_DIR);
	$ar_dir = array();
	while (false !== ($dir = readdir($h_dir))) {
		$ar_dir[] = $dir;
	}
	$locale_sel = "<select name='locale' style='width: 180px'>";
	foreach ($ar_dir as $locale_code) {
		if (is_dir(LOCALE_DIR ."/". $locale_code) && preg_match("/[a-z]{2,2}_[A-Z]{2,2}/", $locale_code)) {
			if ($locale_code == $locale_user) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$ar_locale = explode("_", $locale_code);

			// Retrieve the name of the langauge
			foreach ($ar_languages as $lang_name=>$lang_code) {
				if ($ar_locale[0] == $lang_code) {
					$language = $lang_name;
				}
			}

			// Retrieve the name of the country
			foreach ($ar_countries as $country_name=>$country_code) {
				if ($ar_locale[1] == $country_code) {
					$country = $country_name;
				}
			}

			$locale_sel .= "<option value='$locale_code' $selected>$language ($country)</option>";
		}
	}
	$locale_sel .= "</select>";
	// -----------------------------------------------------------------------

/*

        <tr bgcolor='".TMPL_tblDataColor1."'>
		<td>Branch</td>
		<td>$brans</td>
	</tr>

*/

	$sql = "SELECT empnum, enum, sname, fnames FROM cubit.employees ORDER BY sname ASC, fnames";
	$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employees.");
	
	$emp_sel = "
		<select name='empnum'>
			<option value='0'>[None]</option>";
	while ($emp_data = pg_fetch_array($emp_rslt)) {
		$emp_sel .= "<option value='$emp_data[empnum]'>$emp_data[sname] $emp_data[fnames] - $emp_data[enum]</option>";
	}
	$emp_sel .= "</select>";

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
				<tr bgcolor='".bgcolorg()."'>
					<td>".ucfirst($garr['emp_group'])."</td>
					<td><input type='checkbox' name='payroll_group[$garr[id]]' value='$garr[id]' $checked></td>
				</tr>";
		}
	}
	$payroll_groups .= "<tr><td><br></td></tr>";

	$enterUser = "
		<h3>Add new user to cubit</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts." width='650'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='f1' value='0'>
			<input type='hidden' name='div' value='2'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Username</td>
				<td><input type='text' size='20' name='username' value='$username'> must not contain spaces</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Password</td>
				<td><input type='password' size='20' name='password'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Confirm password</td>
				<td><input type='password' size='20' name='password2'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Locale</td>
				<td>$locale_sel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Show Tooltips</td>
				<td>
					<select name='tool'>
						<option value='Yes'>Yes</option>
						<option value='No'>No</option>
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."POS User</td>
				<td>
					<select name='ispos'>
						<option value='No'>No</option>
						<option value='Yes'>Yes</option>
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td>$emp_sel</td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Payroll Group Permissions</th>
			</tr>
			$payroll_groups
		</table>
		<table ".TMPL_tblDflts." width='650'>
			<tr>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>

		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $enterUser;

}




# confirm entered info
function confirmUser ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require ("libs/validate.lib.php");

	$v = new  validate ();

	$v->isOk ($username, "string", 1, 20, "Invalid user name.");
	$username2 = str_replace(" ", "", $username);
	if(strlen($username) > strlen($username2))
		$v->isOk ($username, "num", 0, 0, "Error : user name must not contain spaces.");
	$v->isOk ($div, "num", 1, 20, "Invalid Branch.");
	$v->isOk ($password, "string", 1, 20, "Invalid password.");
	if(isset($f1)) {
		$v->isOk ($password2, "string", 1, 20, "Invalid password 2.");
		$v->pwMatch ($password, $password2, "Passwords do not match.");
	}
	$v->isOk ($tool, "string", 1, 3, "Invalid tooltips selection.");
	$v->isOk ($ispos, "string", 1, 3, "Invalid POS user selection.");

        # display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}
        $theseErrors = "
		<tr>
			<td class='err' colspan='2'>$theseErrors</td>
		</tr>
		<tr>
			<td colspan='2'><br></td>
		</tr>";
        return enterUser($username,$theseErrors);
        exit;
   }



	if(!isset($dept_sel))
		$dept_sel = "0";

	# Get branch name
	db_connect();

	$sql = "SELECT branname FROM branches WHERE div = '$div'";
	$branRslt = db_exec($sql);
	$bran = pg_fetch_array($branRslt);

	if(isset($f1)) {
		$ex = "<input type='hidden' name='f2' value=''>";
		# exit if user exists
		$sql = "SELECT username FROM users WHERE username = '$username'";
		$usrRslt = db_exec ($sql) or errDie ("Unable to check cubit for existing username.");
		if (pg_numrows ($usrRslt) > 0) {
			return "
				<li class='err'>User, $username, already exists in cubit.</li>
				<br>
				".mkQuickLinks(
					ql("admin-usradd.php","Add New User")
				);
		}

	} else {
		$ex = "";
	}

	require ("locale_codes.php");
	$ar_locale = explode("_", $locale);

	// Retrieve the name of the langauge
	foreach ($ar_languages as $lang_name=>$lang_code) {
		if ($ar_locale[0] == $lang_code) {
			$language = $lang_name;
		}
	}

	// Retrieve the name of the country
	foreach ($ar_countries as $country_name=>$country_code) {
		if ($ar_locale[1] == $country_code) {
			$country = $country_name;
		}
	}


	if ($empnum) {
		$sql = "SELECT sname, fnames, enum FROM cubit.employees WHERE empnum='$empnum'";
		$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employee.");
		$emp_data = pg_fetch_array($emp_rslt);
		
		$employee = "$emp_data[sname] $emp_data[fnames] - $emp_data[enum]";
	} else {
		$employee = "[None]";
	}

	if (isset ($payroll_group) AND is_array($payroll_group)){
		$sendpayroll = "";
		foreach ($payroll_group AS $each){
			$sendpayroll .= "<input type='hidden' name='payroll_group[]' value='$each'>";
		}
	}

	$confirmUser = "
		<h3>Add user to Cubit</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='username' value='$username'>
			<input type='hidden' name='div' value='$div'>
			<input type='hidden' name='password' value='$password'>
			<input type='hidden' name='locale' value='$locale'>
			<input type='hidden' name='tool' value='$tool'>
			<input type='hidden' name='ispos' value='$ispos'>
			<input type='hidden' name='empnum' value='$empnum' />
			<input type='hidden' name='old_dept' value='$dept_sel' />
			$sendpayroll
			$ex
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Username</td>
				<td>$username</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Password</td>
				<td>*</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Locale</td>
				<td>$language ($country)</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Show Tooltips</td>
				<td>$tool</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>POS User</td>
				<td>$ispos</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td>$employee</td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
		</table>";

	if ($ispos == 'No') {
		// add the department selection
		$confirmUser.= "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>User Settings</th>
				</tr>";

		// create the administrator setting
		$rslt=db_exec("SELECT admin FROM users WHERE username='$username' ");
		if (pg_num_rows($rslt) == 0 || pg_result($rslt,0,0)==0)
			$Ch = "";
		else
			$Ch = "checked";

		$confirmUser.= "
			<tr bgcolor=".bgcolorg().">
				<td><input $Ch type='checkbox' name='admin' value='1'> ADMINISTRATOR</td>
			</tr>";

		$confirmUser.= "
			</table>
			<br>";

		// add the department selection
		$confirmUser.="
			<table ".TMPL_tblDflts.">
				<tr>
					<td align='right' colspan='3'><input type='submit' name='doneBtn' value='Done &raquo'></td>
				</tr>
				".TBL_BR."
			</table>
			<br>";

		$get_depts = "SELECT * FROM depts ORDER BY dept";
		$run_depts = db_exec($get_depts) or errDie ("Unable to get department information.");
		if (pg_numrows($run_depts) < 1){
			return "<li class='err'>No Department Information Found.</li>";
		}else {
			$department_drop = "<select name='dept_sel' onChange='document.form.submit()'>";
			$department_drop .= "<option value='0'>Select Department</option>";
			while ($darr = pg_fetch_array ($run_depts)){
				if($dept_sel == $darr['deptid']){
					$department_drop .= "<option value='$darr[deptid]' selected>$darr[dept]</option>";
				}else {
					$department_drop .= "<option value='$darr[deptid]'>$darr[dept]</option>";
				}
			}
			$department_drop .= "</select>";
		}

		$confirmUser .= "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Select Department</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$department_drop</td>
				</tr>
				".TBL_BR."
			</table>";

		$confirmUser .= "
			<table ".TMPL_tblDflts." width='65%'>
				<tr>
					<th colspan='4'>Select user Permissions</th>
				</tr>
				<tr>
					<td valign='top' colspan='2'>
						<table width='100%' cellpadding='1' cellspacing='1'>";

		db_connect();

		$sql = "SELECT * FROM depts WHERE deptid = '$dept_sel'";
		$rslt = db_exec($sql);
		$i = 0;
		while($dep = pg_fetch_array($rslt)) {
			$confirmUser .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'><input type='submit' name='deps[$dep[deptid]]' value='Add'><input type='submit' name='depsrem[$dep[deptid]]' value='Remove'> $dep[dept]</td>
				</tr>";
			$sql = "SELECT * FROM deptscripts WHERE dept='$dep[deptid]' ORDER BY script";
			$srslt = db_exec($sql);
			$i++;

			// Remove checked = yes on the $confirmUser line in this while loop
			while($scr = pg_fetch_array($srslt)) {

				$Tp['script'] = $scr['scriptname'];

				$Sql = "SELECT script FROM userscripts WHERE username='$username' and script='$scr[script]' LIMIT 1";
				$Ex = db_exec($Sql);
				if (pg_numrows ($Ex) > 0) {
					$Ch = "checked";
				}else {
					$Ch = "";
				}
				$Tp['script'] = strtoupper($Tp['script']);
				$confirmUser .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>
							<table>
								<tr>
									<td>.....</td>
									<td><input type='checkbox' name='perm[]' $Ch value='$scr[script]'></td>
									<td>$Tp[script]</td>
								</tr>
							</table>
						</td>
					</tr>";


			}
			$confirmUser .= "<tr bgcolor='".bgcolorg()."'><td colspan=2><br></td></tr>";

			if ($i == "9"){
				$confirmUser .= "
						</table>
					</td>
					<td valign='top'>
						<table width='100%' cellpadding='1' cellspacing='1'>";
			}
		}
	}

    $confirmUser .= "
					</table>
				</td>
			</tr>
			<tr>
				<td align='right' colspan='3'><input type='submit' name='doneBtn' value='Done &raquo'></td>
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
	return $confirmUser;

}



# write user to db
function writeUser ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

//	if(!isset($doneBtn))
	//	return confirmUser($HTTP_POST_VARS);


	# validate input
	require ("libs/validate.lib.php");
	$v = new  validate ();
	$v->isOk ($div, "num", 1, 20, "Invalid Branch.");
	$v->isOk ($username, "string", 1, 20, "Invalid user name.");
	$v->isOk ($password, "string", 1, 20, "Invalid password.");
	$v->isOk ($tool, "string", 1, 3, "Invalid tooltips selection.");
	$v->isOk ($ispos, "string", 1, 3, "Invalid POS user selection.");

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
	if ( ! isset($admin)) $admin=0;

	if(!isset($doneBtn) OR $admin == "1"){
		if(isset($f2)) {
			# exit if user exists
			$sql = "SELECT username FROM users WHERE username = '$username'";
			$usrRslt = db_exec ($sql) or errDie ("Unable to check cubit for existing username.");
			if (pg_numrows ($usrRslt) > 0) {
				return "
					<li class='err'>User, $username, already exists in cubit.</li>
					<br>
					".mkQuickLinks(
						ql("admin-usradd.php","Add New User")
					);
			}
	
			# get md5 hash of password
			$password = md5 ($password);
	
			$sql = "
				INSERT INTO users (
					username, password, services_menu, admin, locale, div, help, empnum, payroll_groups
				) VALUES (
					'$username', '$password', 'L', $admin, '$locale', '$div', '$tool', '$empnum', '".implode (",",$payroll_group)."'
				)";
			$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to cubit.");
		} else {
			// update the admin variable
			db_exec("UPDATE users SET admin = '$admin' WHERE username='$username'");
		}
	}

	#remove all entries for seleted department ...
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

	if ($ispos == "No") {
		$Sql = "DELETE FROM userscripts WHERE username = '$username'";
//		$Ex = db_exec($Sql);

		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'top_menu.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'diary.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'diary-day.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'glodiary.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'glodiary-day.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'todo.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'index_die.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'index-services.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");

		# write permissions
		if(isset($perm)){
			foreach($perm as $key => $value){
				$sql = "INSERT INTO userscripts (username, script) VALUES ('$username', '$value')";
				$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to cubit.");
			}
		}

		if(isset($deps)){
			foreach($deps as $key => $value){
				$sql = "SELECT script FROM deptscripts WHERE dept = '$key'";
				$depRs = db_exec($sql);

				while($depscr = pg_fetch_array($depRs)){
					$sql = "INSERT INTO userscripts (username, script) VALUES ('$username', '$depscr[script]')";
					$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to cubit.");
				}
			}
		}

		if(isset($depsrem)){
			foreach($depsrem as $key => $value){
				$sql = "SELECT script FROM deptscripts WHERE dept = '$key'";
				$depRs = db_exec($sql);

				while($depscr = pg_fetch_array($depRs)){
					$sql = "DELETE FROM userscripts WHERE username='$username' AND script='$depscr[script]'";
					$nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to cubit.");
				}
			}
		}

	} else {
		$Sql = "DELETE FROM userscripts WHERE username='$username'";
//		$Ex = db_exec($Sql);

		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'top_menu.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'diary.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'diary-day.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'glodiary.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'glodiary-day.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'todo.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'index_die.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'index-services.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");

		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'pos-invoice-new.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'pos-slip.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'pos-invoice-print.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
		$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'index-sales.php')";
		$Ex = db_exec ($Sql) or errDie ("Unable to add user to cubit.");
	}

//	if(isset($doneBtn)){
//		$get_real_scripts = "SELECT distinct(script) FROM userscripts WHERE username = '$username'";
//		$run_real_scripts = db_exec($get_real_scripts) or errDie ("Unable to get script information.");
//		if(pg_numrows($run_real_scripts) < 1){
//			return "No Scripts Permission For This User Found.";
//		}
//		$remove_all_temp = "DELETE FROM userscripts WHERE username = '$username'";
//		$run_remove_temp = db_exec($remove_all_temp) or errDie ("Unable to remove temporary permission files.");
//
//		while ($sc_arr = pg_fetch_array ($run_real_scripts)){
//			$insert_this_perm = "INSERT INTO userscripts (username,script) VALUES ('$username', '$sc_arr[script]')";
//			$run_insert_perm = db_exec($insert_this_perm) or errDie ("Unable to update permission information.");
//		}
//	}


	if(!isset($doneBtn)){
		return confirmUser($HTTP_POST_VARS);
	}

	# status report
	$writeUser = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New user added to cubit</th>
			</tr>
			<tr class='datacell'>
				<td>New user, $username, was successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='admin-usradd.php'>Add another user</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return enterUser($username, "<li class='yay'>Successfully added $username</li><br>");
	return $writeUser;

}


?>

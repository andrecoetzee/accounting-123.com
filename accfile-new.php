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
# get settings
require ("settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("template.php");

# enter new data
function enter ()
{

	db_connect();
	# Get Departments
	$depts = "<select name='deptid[]'>";
	$sql = "SELECT * FROM depts ORDER BY dept ASC";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<li>There are no departments in Cubit.";
	}else{
		while($dept = pg_fetch_array($deptRslt)){
				$depts .= "<option value='$dept[deptid]'>$dept[dept]</option>";
		}
	}
	$depts .="</select>";

	$file = file('FOUND');
	$perm = "";
	foreach($file as $key => $val){
		list($script, $name) = explode("|", $val);
		$perm .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=scripts[] value='$script'>$script</td><td>$depts</td><td><input type=text size=30 name=names[] value='$name'></td></tr>";
	}



	$enter =
	"<h3>New Script(s)</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Name</th><th>Department</th></tr>
		$perm
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}


# confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}

		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	# Get all permission exceptions

	$perm = "";
	foreach($scripts as $key => $script){
		$names[$key] = strtoupper($names[$key]);
		$script = rtrim($script);
		$perm .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=scripts[] value='$script'>$script</td><td><input type=hidden name=deptid[] value='$deptid[$key]'>$deptid[$key]</td><td><input type=hidden name=names[] value='$names[$key]'>$names[$key]</td></tr>";
	}

	
	/*
	# get department
	db_conn("cubit");
	$Sl = "SELECT * FROM deptscripts WHERE script = '$script'";
	$Rs = db_exec($Sl);
	if(pg_numrows($Rs) > 0){
		return "<li class=err>Script already exitss.";
	}
	*/


	// $name= strtoupper($name);
	// $script = rtrim($script);

	# Get existing users
	db_connect();
	$users = "<select size=1 name=username>\n";
	$sql = "SELECT * FROM users ORDER BY username";
	$usrRslt = db_exec ($sql) or die ("Unable to get usernames from database.");
	if (pg_numrows ($usrRslt) < 1) {
		$OUTPUT = "No users found in Cubit.";
		require ("template.php");
	}
	while ($myUsers = pg_fetch_array ($usrRslt)) {
		$users .= "<option value='$myUsers[username]'>$myUsers[username]</option>\n";
	}

	$confirm =
	"<h3>Access</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<tr><th>Name</th><th>Department</th></tr>
		$perm
		<tr><td><br></td></tr>
		<tr><td>$users</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_connect ();

	$sql = "";
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		foreach($scripts as $key => $script){
			$Sl="INSERT INTO scripts (script,name) VALUES ('$names[$key]', '$script');";
			$Rs = db_exec ($Sl) or errDie ("Unable to move script to the system.", SELF);

			$sql .= "$Sl<br>";

			$Sl="INSERT INTO deptscripts (dept,script) VALUES ('$deptid[$key]','$script');";
			$Rs = db_exec ($Sl) or errDie ("Unable to move script to the system.", SELF);

			$sql .= "$Sl<br>";

			$Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', '$script')";
			$Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");
		}

	pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Script added</th></tr>
	<tr class=datacell><td>Please edit the users.</td></tr>
	<tr><td>$sql<td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Again</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

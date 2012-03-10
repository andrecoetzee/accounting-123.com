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

if (isset($HTTP_POST_VARS["key"])) {
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

require ("template.php");

##
# functions
##

# enter new user's details
function enter ()
{
	# connect to db
	db_connect ();


        $enter ="<h3>Add New Script to access control database</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <tr><th>File name</th><th>Function</th></tr>";

        for($i = 0; $i <= 10; $i++){
                $enter .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=file[]></td><td><input type=text size=50 name=name[]></td></tr>";
        }

        $enter .= "<tr><td><br></td></tr>
        <tr><td colspan=2><input type=submit value='Add Scripts &raquo'></td></tr>
        </form></table>";

	return $enter;
}

# confirm entered info
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($file as $key => $value){
		if(strlen($file[$key]) > 0){
			$v->isOk ($name[$key], "string", 1, 255, "Invalid script function.");
			$v->isOk ($file[$key], "string", 1, 255, "Invalid file name.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	# connect to db
	db_connect ();

	$confirm ="<h3>New Scripts</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<tr><th>File name</th><th>Function</th></tr>";
	$ssql = "";
	foreach($file as $key => $value){
			if(strlen($file[$key]) > 0){
				$confirm .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=file[] value='$file[$key]'>$file[$key]</td><td><input type=hidden name=name[] value='$name[$key]'>$name[$key]</td></tr>";
				$ssql .= "INSERT INTO scripts (name, script) VALUES ('".rtrim($file[$key])."', '".strtoupper(rtrim($name[$key]))."')<br>";
			}
	}

	$confirm .= "<tr><td><br></td></tr>
	<tr><td colspan=2>$ssql</td></tr>
	<tr><td colspan=2><input type=submit value='Add Scripts &raquo'></td></tr>
	</form></table>";

	return $confirm;
}

# write user to db
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($file as $key => $value){
                $v->isOk ($name[$key], "string", 1, 255, "Invalid script function.");
	        $v->isOk ($file[$key], "string", 1, 255, "Invalid file name.");
        }

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	# connect to db
	db_connect ();

        foreach($file as $key => $value){
                # exit if script exists
	        $sql = "SELECT * FROM scripts WHERE name = '$file[$key]'";
	        $Rslt = db_exec ($sql) or errDie ("Unable to check database for scripts scripts.");
	        if (pg_numrows ($Rslt) > 0) {
		        return "Script, <b> $file[$key] </b>, already exists in database.";
	        }
        }

        $write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>New Scripts added to database</th></tr>";

        foreach($file as $key => $value){
                $sql = "INSERT INTO scripts (name, script) VALUES ('".rtrim($file[$key])."', '".strtoupper(rtrim($name[$key]))."')";
	        	$Rslt = db_exec ($sql) or errDie ("Unable to add script to database.");
                $write .= "<tr class=datacell><td>New file, $name[$key] ($file[$key]), was successfully added to Cubit.</td></tr>";
        }

		# Clean '\n' from the script names
        $sql = "UPDATE scripts SET name = trim(both '\n' from name)";
        $rslt = db_exec($sql);

		# status report
		$write .="<tr><td><br></td></tr>
        <tr class=datacell><td><a href='".SELF."'>Insert More</a></td></tr>
        </table>";

        return $write;
}
?>

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

require ("libs/settings.php");          // Get global variables & functions

// If form was submitted, edit entry or confirm entry or write entry
if ($_GET) {
	if ($_GET['username']) {
		// print form for data entry
		$OUTPUT = editUser ($_GET['username']);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} elseif ($_POST) {
	if ($_POST['a'] == "confirm") {
		// ask for confirmation
		$OUTPUT = confirmUser ($_POST['oldusrnme'], $_POST['username'], $_POST['chgpass'], $_POST['password'], $_POST['password2'], $_POST['perm'], $_POST['depart']);

	} elseif ($_POST['a'] == "write") {
		// write changes to database
		$OUTPUT = writeUser ($_POST['oldusrnme'], $_POST['username'], $_POST['MD5_PASS'], $_POST['depart']);
	} else {
		// Invalid use, display error
		errDie ("ERROR: Invalid use of module.", SELF);
	}
} else {
	// Invalid use, display error
	errDie ("ERROR: Invalid use of module.", SELF);
}

# require template
require ("libs/template.php");

/*
 * Functions
 *
 */

// Prints a form to edit user with
function editUser ($username)
{
	$username = substr ($username, 0, 255);                      // Chop off anything after 255 chars







	// check content of variable
	if (preg_match ("/[^\w\s]/", $username)) {                          // Alphanum, 4-10
		$OUTPUT = "Invalid user name.";
	} else {                                                             // If stkcod is ok, display edit form
		// Connect to database
		Db_Connect ();
                $Out  ="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
        ";      $Out .="<tr><th colspan=2>Select user permissions</th></tr>";

                $Out .="<tr>"; $Out .="<td>";
                $Out .="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
        ";       $Out .="<tr><th colspan=2>Cubit</th></tr>";
		// Query server
		$sql = "SELECT * FROM users WHERE username='$username'";
		$prnUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $username.", SELF);          // Die with custom error if failed







        $sql = "SELECT * FROM scripts ORDER by script";
        $rslt = db_exec($sql);
        $i = 0;
        while($scr = pg_fetch_array($rslt)){
               // print nl2br ($scr["name"]); exit;


                $Sql = "SELECT script FROM userscripts WHERE username='$username' and script='$scr[name]'";
                $Ex = db_exec($Sql);                                                                              $e=(pg_numrows ($Ex));
              //  print $Sql;
                if (pg_numrows ($Ex) > 0) {$Ch ="checked";}      else {$Ch="";} // print"<br><br>user:$username<br>script:$scr[name]<br>num:$e<br><br>";  exit;  // exit;
                $Out .="<tr class='".bg_class()."'><td colspan=2><input type=checkbox $Ch name=perm[] value='$scr[name]'>$scr[script]</td></tr>";
                $i++;
        }
        $Out .="</table>";
        $Out .="</td>";
        $Out .="<td valign=top>";
        $Out .="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
        ";        $Out .="<tr><th colspan=2>Cubit</th></tr>";

        $sql = "SELECT * FROM ascripts ORDER by script";
        $rslt = db_exec($sql);

        while($scr = pg_fetch_array($rslt)){
                $Sql = "SELECT script FROM userscripts WHERE username='$username' and script='$scr[name]'";
              //  print $Sql;
                $Ex = db_exec($Sql);
                if (pg_numrows ($Ex) > 0) {$Ch ="checked";}      else {$Ch="";}
                $Out .="<tr class='".bg_class()."'><td colspan=2><input type=checkbox $Ch name=perm[] value='$scr[name]'>$scr[script]</td></tr>";
                $i++;
        }
           $Out .="</td>"; $Out .="</tr>";
         $Out .="</table>"; $Out .="</table>";
        // Get value from sql query
		$myUsr = pg_fetch_array ($prnUsrRslt);


                $dep=  $myUsr['depart'];


               if ($dep=='Administrator') {$s1="selected";} else {$s1="";}
               if ($dep=='Case officer') {$s2="selected";} else {$s2="";}
               if ($dep=='Background Officer') {$s3="selected";} else {$s3="";}
               if ($dep=='Financial Officer') {$s4="selected";} else {$s4="";}
               if ($dep=='Information Officer') {$s5="selected";} else {$s5="";}
               if ($dep=='Personal Assistant') {$s6="selected";} else {$s6="";}
               if ($dep=='Personnel Department') {$s7="selected";} else {$s7="";}





                 $sels ="<select size=1 name=depart>
             <option $s1 value='Administrator'>Administrator</option>
             <option $s2 value='Case officer'>Case officer</option>
             <option $s3 value='Background Officer'>Background Officer</option>
             <option $s4 value='Financial Officer'>Financial Officer</option>
             <option $s5 value='Information Officer'>Information Officer</option>
             <option $s6 value='Personal Assistant'>Personal Assistant</option>
             <option $s7 value='Personnel Department'>Personnel Department</option>
             </select>";









                // Set up table & form for edit (a is action, so the script knows what to do)
		$OUTPUT = "
		<h3>Edit user</h3>

		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=a value=confirm>
		<input type=hidden name=oldusrnme value='$myUsr[username]'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class=datacell><td>User name</td><td align=center><input type=text size=20 name=username value='$myUsr[username]'></td></tr>
		<tr class=datacell2><td>Password</td><td align=center>
			<table border=0 cellpadding=2 cellspacing=0>
			<tr><td>
				<input type=radio name=chgpass value=no checked>
			</td><td colspan=2>
				Don't change password
			</td></tr>
			<tr><td>
				Or
			</td></tr>
			<tr><td>
				<input type=radio name=chgpass value=yes>
			</td><td>
				Password
			</td><td>
				<input type=password size=20 name=password value=''>
			</td></tr>
			<tr><td>
				<br>
			</td><td>
				Confirm password
			</td><td>
				<input type=password size=20 name=password2 value=''>
			</td></tr>


                        </table>


		</td></tr>
                 <tr class=datacell><td>Department</td><td colspan=2 align=center> $sels  </td> </tr>
		<tr><td><br></td><td align=center><input type=submit value='Commit changes'>&nbsp;<input type=reset value='Reset form'></td></tr>

		</table>
                $Out

                </form>
		<p>";
	}
	// call template to display the form and die
	return $OUTPUT;
}

// Confirm that entered info is correct
function confirmUser ($oldusrnme, $username, $chgpass, $password, $password2, $perm,$depart) // Function args
{
	// Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	$oldusrnme = substr ($oldusrnme, 0, 255);
	$username    =  substr ($username,    0, 255);
	$chgpass   =  strtoupper(substr ($chgpass,   0, 3));
	$password    = substr ($password,    0, 15);
	$password2   = substr ($password2,   0, 15);


	// Do some regex checking to make sure the stuff entered is ok
	if (preg_match ("/[^\w\s]/", $oldusrnme)) {                           // Alphanum, 4-10
		errDie ("ERROR: Tampering with 'oldusrnme' suspected.", SELF);

	} elseif (preg_match ("/[^\w\s]/", $username)) {                       // Alphanum, 4-10
		$OUTPUT = "Invalid user name.\n<br><a href='Javascript:history.back();'>Back</a>\n";

	} elseif (preg_match ("/[^\w\s]/", $chgpass)) {                       // Alphanum, 2-3
		errDie ("ERROR: Tampering with 'chgpass' suspected.", SELF);

	} else {
		if ($chgpass == "YES") {
			if (preg_match ("/[^\w\s]/", $password)) {                     // Alphanum, 8-15
				$OUTPUT = "Invalid password.\n<br><a href='Javascript:history.back();'>Back</a>\n";

			} elseif (preg_match ("/[^\w\s]/", $password2)) {                     // Alphanum, 8-15
				$OUTPUT = "Invalid password (2).\n<br><a href='Javascript:history.back();'>Back</a>\n";

			} elseif ($password != $password2) {                                      // Alphanum, 8-15
				$OUTPUT = "Passwords do not match.\n<br><a href='Javascript:history.back();' class=nav>Back</a>\n";
                                return $OUTPUT;
			} else {
				$MD5_PASS = md5 ($password);
                        }

		} elseif ($chgpass == "NO") {
			Db_Connect();

			$sql = "SELECT password FROM users WHERE username='$oldusrnme'";
			$passRslt = db_exec ($sql) or errDie ("ERROR: Unable to select old password.", SELF);
			$myPass = pg_fetch_array ($passRslt);
			$MD5_PASS = $myPass['password'];

		} else {
			errDie("Tampering with 'chgpass' suspected. Logging.", SELF);
		}




       Db_Connect ();


       $Sql = "DELETE FROM userscripts WHERE username='$username'";
       $Ex = db_exec($Sql);


       $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'new_con.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'list_cons.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

         $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'find_con.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");






        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'view_con.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'req_funds.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

         $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'req_info.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'view_req.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'req_back.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'req_gen.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'list_cases.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");


         $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'die_one.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'die_view.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");


        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'addinftocase.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");


        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'casediaries.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");

        $Sql = "INSERT INTO userscripts (username, script) VALUES ('$username', 'viewcasediaries.php')";
        $Ex = db_exec ($Sql) or errDie ("Unable to add user to database.");













       foreach($perm as $key => $value){
            //    print "$key => $value <br>\n";
                $sql = "INSERT INTO userscripts (username, script) VALUES ('$username', '$value')";
	        $nwUsrRslt = db_exec ($sql) or errDie ("Unable to add user to database.");
        }








		$OUTPUT = "
		<h3>Edit user</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=a value=write>
		<input type=hidden name=oldusrnme value='$oldusrnme'>
		<input type=hidden name=username value='$username'>
                <input type=hidden name=depart value='$depart'>
		<input type=hidden name=MD5_PASS value='$MD5_PASS'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class=datacell><td>User name</td><td align=center>$username</td></tr>
		<tr class=datacell2><td>Password</td><td align=center>*********</td></tr>
                <tr class=datacell2><td>Department</td><td align=center>$depart</td></tr>
		<tr><td><br></td><td align=center><input type=button value='&laquo; Back' onClick='Javascript:history.back();'>&nbsp;<input type=submit value='Confirm edit &raquo;'></td></tr>
		</form>
		</table>
		";
	}
	return $OUTPUT;
}

// Takes form submission and writes it to Cubit (Similar to above, because we don't trust user-submitted stuff :-))
function writeUser ($oldusrnme, $username, $MD5_PASS,$depart)
{
	// Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	$oldusrnme = substr ($oldusrnme, 0, 255);
	$username    = substr ($username,    0, 255);
	$MD5_PASS  = substr ($MD5_PASS,  0, 32);

	// Do some regex checking to make sure the stuff entered is ok
	if (preg_match ("/[^\w\s]/", $oldusrnme)) {                           // Alphanum, 4-10
		errDie ("ERROR: Tampering with 'oldusrnme' suspected.", SELF);

	} elseif (preg_match ("/[^\w\s]/", $username)) {                       // Alphanum, 4-10
		$OUTPUT = "Invalid user name.\n<br><a href='Javascript:history.back();'>Back</a>\n";

	} elseif (preg_match ("/[^\w\s]/", $MD5_PASS)) {                     // Alphanum, 32
		$OUTPUT = "Invalid password.\n<br><a href='Javascript:history.back();'>Back</a>\n";

	} else {
		// if everything went fine above, write new user to database
		Db_Connect ();

		$sql = "UPdate users SET username='$username',depart='$depart', password='$MD5_PASS'  WHERE username='$oldusrnme'";
		$nwUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $oldusrnme", SELF);          // Die with custom error if failed

                # update the permissions database
                $sql = "UPdate userscripts SET username='$username' WHERE username='$oldusrnme'";
		$nwUsrRslt = db_exec ($sql) or errDie ("ERROR: Unable to edit user: $oldusrnme", SELF);          // Die with custom error if failed

		// Provide some info on status
		$OUTPUT = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Committed changes to user</th></tr>
		<tr class=datacell><td>User, $username, was successfully edited.</td></tr>
		</table> <p>

        <table border=0 cellpadding='2' cellspacing='1'>
        <tr><th>Quick Links</th></tr>

        <tr bgcolor='#88BBFF'><td><a href='index_sets.php'>Settings</a></td></tr>
        <tr bgcolor='#88BBFF'><td><a href='index.php'>Main Menu</a></td></tr>
        </tr>
		";
	}
	return $OUTPUT;
}

?>

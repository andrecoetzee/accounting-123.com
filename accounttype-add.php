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
require_lib("validate");

// puts get vars into post vars for easier access
if ( isset($HTTP_GET_VARS) ) {
	foreach ($HTTP_GET_VARS as $arrname => $arrval) {
		$HTTP_POST_VARS[$arrname] = $arrval;
	}
}

if ( isset($HTTP_POST_VARS["key"]) ) {
	switch ($HTTP_POST_VARS["key"]) {
		case "write":
			$OUTPUT = write();
			break;
		case "edit":
			$OUTPUT = edit();
			break;
		case "write_edit":
			$OUTPUT = write_edit();
			break;
		case "delete":
			$OUTPUT = delete_ask();
			break;
		case "delete_confirm":
			$OUTPUT = delete_confirm();
			break;
		default:
			$OUTPUT = enter();
	}
} else {
	# show current stock
	$OUTPUT = enter ();
}

require ("template.php");



// lists the currently added account types
function viewAccountTypes()
{

	$OUTPUT = "
		<h3>Current Bank Account Types</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account Type</th>
				<th colspan='2'>Options</th>
			</tr>";

	db_connect();

	$sql = "SELECT * FROM bankacctypes";
	$rslt = db_exec($sql) or errDie("Error getting Bank Account Types from Database. (SL)", SELF);

    $i = 0;
	while ( $row = pg_fetch_array($rslt) ) {

		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$row[accname]</td>
				<td><a href='accounttype-add.php?key=edit&acctype_id=$row[acctype_id]'>Edit</a></td>
				<td><a href='accounttype-add.php?key=delete&acctype_id=$row[acctype_id]'>Delete</a></td>
			</tr>";
	}

	$OUTPUT .= "
		</table>
		<center>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
		</center>";
	return $OUTPUT;

}


function enter()
{

	global $HTTP_POST_VARS;

	$OUTPUT = "
 		<h3>Add New Bank Account Type</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='text' name='accname'>
			<input type='submit' value='Create'>
		</form>";

	$OUTPUT .= viewAccountTypes();
	return $OUTPUT;

}


// displays the previous value in an edit box
// enter the new bank account type
function edit()
{

	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	$v = & new Validate();
	$OUTPUT = "";

	if ( isset($acctype_id) )
		$v->isOk($acctype_id, "num", 1, 9, "Invalid Account Type Specified");
	else
		$v->addError("", "No Account Type Specified");

	if ( $v->isError() ) {
		$errors = $v->getErrors();

		foreach ( $errors as $errnum => $errval ) {
			$OUTPUT .= "<li class='err'>$errval[msg]</li>";
		}

		$OUTPUT .= enter();

		return $OUTPUT;
	}

	// get the entry from Cubit
	$rslt = db_exec("SELECT accname FROM bankacctypes WHERE acctype_id='$acctype_id'")
		or errDie("Error getting account type name from database (QR)",SELF);
	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT .= "<li class='err'>No Such Account Type</li>";
		$OUTPUT .= enter();

		return enter();
	}

        // read the account name
	$accname = pg_fetch_result($rslt, 0, 0);

	$OUTPUT = "
 		<h3>Edit New Bank Account Type</h3>
		<form action='".SELF."' method=POST>
			<input type='hidden' name='key' value='write_edit'>
			<input type='hidden' name='acctype_id' value='$acctype_id'>
			<input type='text' name='accname' value='$accname'>
			<input type='submit' value='Edit'>
		</form>";

	$OUTPUT .= viewAccountTypes();
	return $OUTPUT;

}


// write the data to Cubit
function write()
{

	global $HTTP_POST_VARS;

	$v = & new Validate();
	$OUTPUT = "";

    // set it to blank
	if ( isset($HTTP_POST_VARS["accname"]) )
		$v->isOk($HTTP_POST_VARS["accname"], "string", 1, 100, "Invalid Account Type Name");
	else
		$v->addError("", "No Account Type Name specified");

	if ( $v->isError() ) {
		$errors = $v->getErrors();
		foreach ( $errors as $errnum => $errval ) {
			$OUTPUT .= "<li class='err'>Error #$errnum: $errval[msg]</li>";
		}
		$OUTPUT .= enter();
		return $OUTPUT;
	}

	db_connect();

	$sql = "INSERT INTO bankacctypes (accname) VALUES('$HTTP_POST_VARS[accname]')";
	$rslt = db_exec($sql) or errDie("Error inserting account type into database. (QR)", SELF);

	if ( pg_cmdtuples($rslt) < 1 )
		return "Error inserting account type into database. (CT)";

	$OUTPUT .= "Added Bank Account Type.<br>";
	$OUTPUT .= enter();
	return $OUTPUT;

}


// write the edit data to Cubit
function write_edit()
{

	global $HTTP_POST_VARS;

	$v = & new Validate();
	$OUTPUT = "";

    // set it to blank
	if ( isset($HTTP_POST_VARS["accname"]) )
		$v->isOk($HTTP_POST_VARS["accname"], "string", 1, 100, "Invalid Account Type Name");
	else
		$v->addError("", "No Account Type Name specified");

	if ( isset($HTTP_POST_VARS["acctype_id"]) )
		$v->isOk($HTTP_POST_VARS["acctype_id"], "num", 1, 9, "Invalid Account Type Specified");
	else
		$v->addError("", "No Account Type specified");

	if ( $v->isError() ) {
		$errors = $v->getErrors();
		foreach ( $errors as $errnum => $errval ) {
			$OUTPUT .= "<li class='err'>$errval[msg]</li>";
		}
		$OUTPUT .= edit();
		return $OUTPUT;
	}

	db_connect();

	$sql = "UPDATE bankacctypes SET accname='$HTTP_POST_VARS[accname]' WHERE acctype_id='$HTTP_POST_VARS[acctype_id]'";
	$rslt = db_exec($sql) or errDie("Error updating account type in database. (QR)", SELF);

	if ( pg_cmdtuples($rslt) < 1 ) 
		return "Error updating account type in database. (CT)";

	$OUTPUT .= "Updated Bank Account Type.<br>";
	$OUTPUT .= enter();
	return $OUTPUT;

}

// asks if user is sure he wants to delete this entry
function delete_ask()
{

	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	$v = & new Validate();
	$OUTPUT = "";

	if ( isset($acctype_id) )
		$v->isOk($acctype_id, "num", 1, 9, "Invalid Account Type Specified");
	else
		$v->addError("", "No Account Type Specified");

	if ( $v->isError() ) {
		$errors = $v->getErrors();
		foreach ( $errors as $errnum => $errval ) {
			$OUTPUT .= "<li class='err'>$errval[msg]</li>";
		}
		$OUTPUT .= enter();
		return $OUTPUT;
	}

	// get the entry from Cubit
	$rslt = db_exec("SELECT accname FROM bankacctypes WHERE acctype_id='$acctype_id'")
		or errDie("Error getting account name from database. (QR)", SELF);
	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT .= "<li class='err'>No Such Account Type</li>";
		$OUTPUT .= enter();

		return enter();
	}

    // read the account name
	$accname = pg_fetch_result($rslt, 0, 0);

	// ask the user
	$OUTPUT .= "
		Are you sure you wish to delete the Bank Account Type named '$accname'?<br>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='delete_confirm'>
			<input type='hidden' name='acctype_id' value='$acctype_id'>
			<input type='submit' name='delete_yes' value='Yes'>
			<input type='submit' name='delete_no' value='No'>
		</form>";
	$OUTPUT .= viewAccountTypes();
	return $OUTPUT;

}

// deletes the entry from Cubit
function delete_confirm()
{

	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	$v = & new Validate();
	$OUTPUT = "";

        // if user selected no, just display the normal page again
	if ( isset($delete_no) ) return enter();

	if ( isset($acctype_id) )
		$v->isOk($acctype_id, "num", 1, 9, "Invalid Account Type Specified");
	else
		$v->addError("", "No Account Type Specified");

	if ( $v->isError() ) {
		$errors = $v->getErrors();
		foreach ( $errors as $errnum => $errval ) {
			$OUTPUT .= "<li class='err'>$errval[msg]</li>";
		}
		$OUTPUT .= enter();
		return $OUTPUT;
	}

	// delete the entry
	$rslt = db_exec("DELETE FROM bankacctypes WHERE acctype_id='$acctype_id'")
		or errDie("Error deleting account type. (QR)", SELF);

	if ( pg_cmdtuples($rslt) < 1 ) {
		$OUTPUT .= "Error deleting account type. (CT)<br>";
	} else {
		$OUTPUT .= "Deleted Account Type.<br>";
	}
	$OUTPUT .= enter();
	return $OUTPUT;

}


?>
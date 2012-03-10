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
require("settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
        	$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			if (isset($HTTP_GET_VARS['id'])){
				$OUTPUT = edit ($HTTP_GET_VARS['id']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($HTTP_GET_VARS['id'])){
		$OUTPUT = edit ($HTTP_GET_VARS['id']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# get template
require("template.php");



 # confirm
function edit($id)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid employee department id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM departments WHERE id = '$id'";
	$depRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($depRslt) < 1){
		return "<li> Invalid Department ID.";
	}else{
		$dep = pg_fetch_array($depRslt);
	}

	extract ($dep);

	// layout
	$edit = "
		<h3>Edit Employee Department</h3>
		<table ".TMPL_tblDflts." width='45%'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='id' value='$id'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td><input type='text' size='20' name='department' value='$department'></td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='left'><input type='submit' value='Edit &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='empdepartment-add.php'>Add Employee Department</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='empdepartment-view.php'>View Employee Department</a></td>
			</tr>
				<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $edit;

}




# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid department id.");
	$v->isOk ($department, "string", 0, 50, "Invalid department.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT department FROM departments WHERE lower(department) = lower('$department') AND id != '$id'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Department with name : <b>$department</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Layout
	$confirm = "
		<h3>Edit Employee Department</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='department' value='$department'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category Code</td>
				<td>$catcod</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category Name</td>
				<td>$cat</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Description</td>
				<td><pre>$descript</pre></td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='left'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-view.php'>View Employee Department</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write
function write($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid department id.");
	$v->isOk ($department, "string", 0, 50, "Invalid department.");


	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT department FROM departments WHERE lower(department) = lower('$department') AND id != '$id'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Department with name : <b>$department</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Insert the customer
	db_connect();
	$sql = "UPDATE departments SET department = '$department' WHERE id = '$id'";
	$rslt = db_exec($sql) or errDie("Unable to update customer department in Cubit.",SELF);

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee Department edited</th>
			</tr>
			<tr class='datacell'>
				<td>Employee Department, $department has been successfully edited.</td></tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-add.php'>Add Employee Department</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-view.php'>View Employee Departments</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
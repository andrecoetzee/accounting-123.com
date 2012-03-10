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
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# Get template
require("template.php");




# Default view
function view()
{

	//layout
	$view = "
		<h3>Add Employee Department</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			".frmupdate_passon()."
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td><input type='text' size='20' name='department'></td>
			</tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Add &raquo'></td>
			</tr>
		</table>
		<P>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-view.php'>View Employee Departments</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</form>
		</table>";
	return $view;

}




# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($department, "string", 0, 50, "Invalid department.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm."<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
				<p>
				<table ".TMPL_tblDflts." width='100'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='empdepartment-view.php'>View Employee Departments</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</form>
				</table>";
	}

	// Layout
	$confirm = "
		<h3>Add Employee Department</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			".frmupdate_passon()."
			<input type='hidden' name='key value='write'>
			<input type='hidden' name='department' value='$department'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td>$department</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='left'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-view.php'>View Employee Departments</a></td></tr>
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
	$v->isOk ($department, "string", 0, 50, "Invalid employee department.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "
			<p>
			<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
			<P>
			<table ".TMPL_tblDflts." width='100'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='empdepartment-view.php'>View Employee Departments</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</form>
			</table>";
		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT department FROM departments WHERE lower(department) = lower('$department')";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Department with name : <b>$department</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		//return $error;
	}

	// insert into stock
	$sql = "INSERT INTO department(department, div) VALUES('$department', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert customer department into to Cubit.",SELF);

// 	if (frmupdate_passon()) {
// 		$newlst = new dbSelect("department", "cubit", grp(
// 			m("cols", "department"),
// 			m("where", "div='".USER_DIV."'"),
// 			m("order", "department ASC")
// 		));
// 		$newlst->run();
// 
// 		$a = array();
// 		if ($newlst->num_rows() > 0) {
// 			while ($row = $newlst->fetch_array()) {
// 				$a[$row["id"]] = "($row[catcod]) $row[cat]";
// 			}
// 		}
// 
// 		$js = frmupdate_exec(array($a), true);
// 	} else {
// 		$js = "";
// 	}

	$write = "
		$js
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New Employee Department added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New Employee Department, $dep has been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-view.php'>View Employee Departments</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
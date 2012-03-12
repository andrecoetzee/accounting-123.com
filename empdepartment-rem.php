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

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['id'])){
					$OUTPUT = confirm ($_GET['id']);
			} else {
					$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
        if (isset($_GET['id'])){
                $OUTPUT = confirm ($_GET['id']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# get template
require("template.php");

# confirm
function confirm($catid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 50, "Invalid Employee Department id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class='err'>-".$e["msg"]."<br>";
			}
					return $confirm;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM departments WHERE id = '$id'";
		$depRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
		if(pg_numrows($depRslt) < 1){
			return "<li> Invalid Employee Department ID.";
		}else{
			$dep = pg_fetch_array($depRslt);
		}

		# get stock vars
		extract ($dep);

		// Layout
		$confirm =
		"<h3>Remove Employee Department</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type=hidden name=key value=rem>
			<input type=hidden name=id value='$id'>
			<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr bgcolor='".bgcolorg()."'><td>Department</td><td>$department</td></tr>
			<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Confirm &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='empdepartment-view.php'>View Employee Departments</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $confirm;
}

# write
function rem($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid employee department id.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM departments WHERE id = '$id'";
	$catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($catRslt) < 1){
			return "<li> Invalid Stock Category ID.";
	}else{
			$dep = pg_fetch_array($catRslt);
	}

	# get stock vars
	extract ($dep);

	// remove stock
	db_connect();
	$sql = "DELETE FROM departments WHERE id = '$id'";
	$rslt = db_exec($sql) or errDie("Unable to remove employee department from Cubit.",SELF);

	$write ="
	<table ".TMPL_tblDflts." width='50%'>
	<tr><th>Employee Department removed from Cubit</th></tr>
	<tr class=datacell><td>Employee Department $department has been successfully removed from Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='empdepartment-add.php'>Add Employee Department</a></td></tr>
			<tr bgcolor='#88BBFF'><td><a href='empdepartment-view.php'>View Stock Categories</a></td></tr>
   			<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

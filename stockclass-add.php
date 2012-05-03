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

# decide what to do
if (isset ($_POST["key"])) {
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

# display output
require ("template.php");




# enter new data
function enter ()
{

	$enter = "
				<h3>Add Classification</h3>
				<form action='".SELF."' method='POST'>
					".frmupdate_passon()."
				<table ".TMPL_tblDflts.">
					<input type='hidden' name='key' value='confirm'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Classification Code</td>
						<td><input type='text' size='10' name='classcode'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Classification</td>
						<td><input type='text' size='20' name='classname'></td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
					</tr>
				</table>
				</form>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='stockclass-view.php'>View Classifications</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $enter;

}



# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($classcode, "string", 1, 255, "Invalid Classification code.");
	$v->isOk ($classname, "string", 1, 255, "Invalid Classification name.");

	# display errors, if any
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
	$sql = "SELECT classcode FROM stockclass WHERE lower(classcode) = lower('$classcode') AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Classification with code : <b>$classcode</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$confirm = "
					<h3>Confirm Classification</h3>
					<form action='".SELF."' method='POST'>
						".frmupdate_passon()."
					<table ".TMPL_tblDflts.">
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='classcode' value='$classcode'>
						<input type='hidden' name='classname' value='$classname'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Classification code</td>
							<td>$classcode</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Classification</td>
							<td>$classname</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
							<td valign='left'><input type='submit' value='Write &raquo;'></td>
						</tr>
					</table>
					</form>
					<p>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='stockclass-view.php'>View Classifications</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $confirm;

}


# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($classcode, "string", 1, 255, "Invalid Classification code.");
	$v->isOk ($classname, "string", 1, 255, "Invalid Classification name.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}




	# check stock code
	db_connect();
	$sql = "SELECT classcode FROM stockclass WHERE lower(classcode) = lower('$classcode') AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> A Classification with code : <b>$classcode</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# connect to db
	db_connect ();

	# write to db
	$sql = "INSERT INTO stockclass(classcode, classname, div) VALUES ('$classcode', '$classname', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add class to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class='err'>Unable to add classname to database.</li>";
	}

	if (frmupdate_passon()) {
		$newlst = new dbSelect("stockclass", "cubit", grp(
			m("cols", "clasid, classname"),
			m("where", "div='".USER_DIV."'"),
			m("order", "classname ASC")
		));
		$newlst->run();

		$a = array();
		if ($newlst->num_rows() > 0) {
			while ($row = $newlst->fetch_array()) {
				$a[$row["clasid"]] = $row["classname"];
			}
		}

		$js = frmupdate_exec(array($a), true);
	} else {
		$js = "";
	}

	$write = "
				$js
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Classification added to system</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>New Classification <b>$classname</b>, has been successfully added to the system.</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='stockclass-view.php'>View Classifications</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $write;

}



?>
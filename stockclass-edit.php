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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				if (isset($_GET['clasid'])){
					$OUTPUT = edit ($_GET['clasid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['clasid'])){
			$OUTPUT = edit ($_GET['clasid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("template.php");

function edit($clasid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
        $clasRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($clasRslt) < 1){
                return "<li> Invalid Category ID.";
        }else{
                $clas = pg_fetch_array($clasRslt);
        }

	$enter =
	"<h3>Edit Classification</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=clasid value='$clas[clasid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Classification code</td><td align=center><input type=text size=20 name=classcode value='$clas[classcode]'></td></tr>
	<tr class='bg-even'><td>Classification</td><td align=center><input type=text size=20 name=classname value='$clas[classname]'></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
 	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='stockclass-view.php'>View Classifications</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($classname, "string", 1, 255, "Invalid Classification name.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification id.");

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

	# check stock code
	db_connect();
	$sql = "SELECT classcode FROM stockclass WHERE lower(classcode) = lower('$classcode') AND clasid != '$clasid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A Classification with code : <b>$classcode</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$confirm =
	"<h3>Confirm Edit Classification</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=classcode value='$classcode'>
	<input type=hidden name=classname value='$classname'>
	<input type=hidden name=clasid value='$clasid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Classification code</td><td align=center>$classcode</td></tr>
	<tr class='bg-even'><td>Classification</td><td align=center>$classname</td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='stockclass-view.php'>View Classifications</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($classname, "string", 1, 255, "Invalid Classification name.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification id.");

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

	# write to db
	$sql = "UPDATE stockclass SET classcode = '$classcode', classname = '$classname' WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec ($sql) or errDie ("Unable to edit classification on system.", SELF);
	if (pg_cmdtuples ($clasRslt) < 1) {
		return "<li class=err>Unable to edit classification.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Classification edited</th></tr>
	<tr class=datacell><td>Classification <b>$classname</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='stockclass-view.php'>View Classifications</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

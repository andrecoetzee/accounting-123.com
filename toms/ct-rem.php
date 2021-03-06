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
require ("../settings.php");

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
				if (isset($_GET['id'])){
					$OUTPUT = rem ($_GET['id']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['id'])){
			$OUTPUT = rem ($_GET['id']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function rem($id)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 50, "Invalid id.");

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
		db_conn("exten");
		$sql = "SELECT * FROM ct WHERE id = '$id'AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid ID.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

	$enter =
	"<h3>Confirm Remove Credit Term</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Days</td><td align=center>$cat[days]</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='ct-view.php'>View Credit Terms</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
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
	$v->isOk ($id, "num", 1, 50, "Invalid id.");

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
	db_conn ("exten");

	# write to db
	$sql = "DELETE FROM ct WHERE id = '$id' AND div = '".USER_DIV."'";
	$catRslt = db_exec ($sql) or errDie ("Unable to remove category from system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class=err>Unable to remove credit term from database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Credit Term Removed</th></tr>
	<tr class=datacell><td>Credit term has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='ct-view.php'>View Credit Terms</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

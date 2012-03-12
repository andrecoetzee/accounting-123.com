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
				if (isset($_GET['salespid'])){
					$OUTPUT = rem ($_GET['salespid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['salespid'])){
			$OUTPUT = rem ($_GET['salespid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function rem($salespid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($salespid, "num", 1, 50, "Invalid Sales Person id.");

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
		$sql = "SELECT * FROM salespeople WHERE salespid = '$salespid' AND div = '".USER_DIV."'";
        $salespRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($salespRslt) < 1){
                return "<li> Invalid Sales Person ID.";
        }else{
                $salesp = pg_fetch_array($salespRslt);
        }

	$enter =
	"<h3>Confirm Remove Sales Person</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=salespid value='$salesp[salespid]'>
	<input type=hidden name=salesp value='$salesp[salesp]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Number</td><td>$salesp[salespno]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Sales Person</td><td>$salesp[salesp]</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='salesp-view.php'>View Sales Person</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($salespid, "num", 1, 50, "Invalid Sales Person id.");

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
	$sql = "DELETE FROM salespeople WHERE salespid = '$salespid' AND div = '".USER_DIV."'";
	$salespRslt = db_exec ($sql) or errDie ("Unable to remove Sales Person from system.", SELF);
	if (pg_cmdtuples ($salespRslt) < 1) {
		return "<li class=err>Unable to remove Sales Person from database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Sales Person Removed</th></tr>
	<tr class=datacell><td>Sales Person <b>$salesp</b>, has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='salesp-view.php'>View Sales Persons</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

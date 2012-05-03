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
					$OUTPUT = edit ($_GET['salespid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['salespid'])){
			$OUTPUT = edit ($_GET['salespid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function edit($salespid)
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
	"<h3>Edit Sales Person</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=salespid value='$salesp[salespid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Number</td><td align=center><input type=text size=20 name=salespno value='$salesp[salespno]'></td></tr>
	<tr class='bg-even'><td>Sales Person</td><td align=center><input type=text size=20 name=salesp value='$salesp[salesp]'></td></tr>
	<tr class='bg-odd'><td>Commission</td><td align=center><input type=text size=10 name=com value='$salesp[com]'></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>"
	.mkQuickLinks(
		ql("salesp-add.php", "Add Sales Person"),
		ql("salesp-view.php", "View Sales People")
	);

	return $enter;
}

function confirm ($_POST) {
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($salespid, "num", 1, 50, "Invalid Sales Person id.");
	$v->isOk ($salespno, "num", 1, 10, "Invalid Sales Person number.");
	$v->isOk ($salesp, "string", 1, 255, "Invalid Sales Person name.");
	$com+=0;

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

	$confirm =
	"<h3>Confirm Edit Sales Person</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=salesp value='$salesp'>
	<input type=hidden name=salespid value='$salespid'>
	<input type=hidden name=salespno value='$salespno'>
	<input type=hidden name=com value='$com'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Number</td><td>$salespno</td></tr>
	<tr class='bg-even'><td>Sales Person</td><td>$salesp</td></tr>
	<tr class='bg-odd'><td>Commission</td><td>$com</td></tr>
	<tr><td align=right></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='salesp-view.php'>View Sales Persons</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($salespid, "num", 1, 50, "Invalid Sales Person id.");
	$v->isOk ($salespno, "num", 1, 10, "Invalid Sales Person number.");
	$v->isOk ($salesp, "string", 1, 255, "Invalid Sales Person name.");
 	$com+=0;


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
	$sql = "UPDATE salespeople SET salespno = '$salespno', salesp = '$salesp',com='$com' WHERE salespid = '$salespid' AND div = '".USER_DIV."'";
	$salespRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);
	if (pg_cmdtuples ($salespRslt) < 1) {
		return "<li class=err>Unable to add salesp to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Sales Person edited</th></tr>
	<tr class=datacell><td>Sales Person <b>$salesp</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='salesp-view.php'>View Sales Persons</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

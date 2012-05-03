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
			if (isset($_GET['calloutpid'])){
				$OUTPUT = edit ($_GET['calloutpid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
		if (isset($_GET['calloutpid'])){
			$OUTPUT = edit ($_GET['calloutpid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function edit($calloutpid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($calloutpid, "num", 1, 50, "Invalid Call Out Person id.");

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
	$sql = "SELECT * FROM calloutpeople WHERE calloutpid = '$calloutpid' AND div = '".USER_DIV."'";
        $calloutpRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($calloutpRslt) < 1){
                return "<li> Invalid Call Out Person ID.";
        }else{
                $calloutp = pg_fetch_array($calloutpRslt);
        }

	$enter = "
			<h3>Edit Call Out Person</h3>
			<form action='".SELF."' method=post>
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='calloutpid' value='$calloutp[calloutpid]'>
				<tr><th>Field</th><th>Value</th></tr>
				<tr class='bg-odd'><td>Call Out Person</td><td align='center'><input type='text' size='20' name='calloutp' value='$calloutp[calloutp]'></td></tr>
				<tr class='bg-even'><td>Contact Number</td><td align='center'><input type='text' size='20' name='telno' value='$calloutp[telno]'></td></tr>
				<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
			</table></form>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out Persons</a></td></tr>
				<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($calloutpid, "num", 1, 50, "Invalid Call Out Person id.");
	$v->isOk ($calloutp, "string", 1, 255, "Invalid Call Out Person.");
	$v->isOk ($telno, "string", 1, 255, "Invalid Call Out Person Contact Number.");

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

	$confirm = "
			<h3>Confirm Edit Call Out Person</h3>
			<form action='".SELF."' method='post'>
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='calloutp' value='$calloutp'>
				<input type='hidden' name='calloutpid' value='$calloutpid'>
				<input type='hidden' name='telno' value='$telno'>
				<tr><th>Field</th><th>Value</th></tr>
				<tr class='bg-odd'><td>Call Out Person</td><td>$calloutp</td></tr>
				<tr class='bg-even'><td>Contact Number</td><td>$telno</td></tr>
				<tr><td align='right'></td><td valign='left'><input type='submit' value='Write &raquo;'></td></tr>
			</table></form>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out Persons</a></td></tr>
				<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($calloutpid, "num", 1, 50, "Invalid Call Out Person id.");
	$v->isOk ($calloutp, "string", 1, 255, "Invalid Call Out Person name.");
	$v->isOk ($telno, "string", 1, 255, "Invalid Call Out Person Contact Number.");

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
	$sql = "UPDATE calloutpeople SET calloutp = '$calloutp',telno='$telno' WHERE calloutpid = '$calloutpid' AND div = '".USER_DIV."'";
	$calloutpRslt = db_exec ($sql) or errDie ("Unable to update call out person information.", SELF);
	if (pg_cmdtuples ($calloutpRslt) < 1) {
		return "<li class=err>Unable to update call out person information.";
	}

	$write = "
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
				<tr><th>Call Out Person Edited</th></tr>
				<tr class='datacell'><td>Call Out Person <b>$calloutp</b>, has been edited.</td></tr>
			</table>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<tr class='bg-odd'><td><a href='calloutp-view.php'>View Call Out Persons</a></td></tr>
				<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
			</table>";

	return $write;
}
?>

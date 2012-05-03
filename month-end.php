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
		case "close":
			$OUTPUT = close ();
			break;
		default:
			$OUTPUT = confirm ();
	}
} else {
	$OUTPUT = confirm ();
}

# get template
require("template.php");

# confirm
function confirm()
{
	db_conn('cubit');

	if(!(div_isset("DEBT_AGE", "mon"))) {
		return "<li class=err>You are using the system date for age analysis<br>
			If you want to record month end manually please change the Age Analysis period type under 'Settings', 'Admin'";


	}

	$Sl="SELECT * FROM monthcloses WHERE type='Monthclose' ORDER BY id DESC LIMIT 1";
	$Rx=db_exec($Sl) or errDie("Unable to get monthclose from db.");

	if(pg_numrows($Rx)<1) {
		$Note="This is the first time you are closing the month";
	} else {
		$data=pg_fetch_array($Rx);
		$Note="<li class=err>The last month close was on $data[closedate] by $data[closeby].</li>";
	}



	// Layout
	$confirm =
	"<h3>Confirm month end for Age Analisys</h3>
	$Note
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=close>
	<tr><th colspan=2>Month End Date</th></tr>
		<tr class='bg-odd'><td colspan=2 align=center>".date("d F Y")."</td></tr>
		<tr><td><br></td></tr>
		<tr><td></td><td align=right><input type=submit value='Write &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# write
function close()
{

	db_connect();
	/*** Update Invoices ***/
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$Sl="SELECT * FROM cashbook WHERE cusnum>0 AND div='".USER_DIV."'";
		$Ri=db_exec($Sl);

		while($cd=pg_fetch_array($Ri)) {
			$rages=explode("|",$cd['rages']);
			$nr="";
			foreach($rages as $age) {
				if($age=="") {
					continue;
				}
				$age+=1;
				$nr.="|$age";
			}
			$Sl="UPDATE cashbook SET rages='$nr' WHERE cashid='$cd[cashid]'";
			$Rl=db_exec($Sl);
		}

		# 90 - 120
		$sql = "UPDATE invoices SET age = 4 WHERE age = 3 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 60 - 90
		$sql = "UPDATE invoices SET age = 3 WHERE age = 2 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 30 - 60
		$sql = "UPDATE invoices SET age = 2 WHERE age = 1 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# Current - 30
		$sql = "UPDATE invoices SET age = 1 WHERE age = 0 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

	// Non Stock Invoices
		# 90 - 120
		$sql = "UPDATE nons_invoices SET age = 4 WHERE age = 3 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 60 - 90
		$sql = "UPDATE nons_invoices SET age = 3 WHERE age = 2 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 30 - 60
		$sql = "UPDATE nons_invoices SET age = 2 WHERE age = 1 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# Current - 30
		$sql = "UPDATE nons_invoices SET age = 1 WHERE age = 0 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

	// Custran
		# 90 - 120
		$sql = "UPDATE custran SET age = 4 WHERE age = 3 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 60 - 90
		$sql = "UPDATE custran SET age = 3 WHERE age = 2 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# 30 - 60
		$sql = "UPDATE custran SET age = 2 WHERE age = 1 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

		# Current - 30
		$sql = "UPDATE custran SET age = 1 WHERE age = 0 AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock category from Cubit.",SELF);

	/*** Commit Update Invoices ***/
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$date=date("Y-m-d");

	$Sl="INSERT INTO monthcloses (type,closedate,closeby) VALUES ('Monthclose','$date','".USER_NAME."')";
	$Rx=db_exec($Sl) or errDie("Monthend was done, but error makeing record of it.");

	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Month end has been recorded</th></tr>
		<tr class=datacell><td>Date : ".date("d F Y")."</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

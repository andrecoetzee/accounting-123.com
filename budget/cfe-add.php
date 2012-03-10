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
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");

# enter new data
function enter ()
{
	$enter =
	"<h3>New cash flow budget entry </h3>
	<form action='".SELF."' method=post>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Description</td><td><input type=text size=20 name=des value=''></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>".REQ."Funds In/Out?</td><td valign=center>In<input type=radio name=funds value='in' checked=yes>Out<input type=radio name=funds value='out'></td></tr>
		<tr><th colspan=2>Date</th></tr>
	 	<tr bgcolor='".TMPL_tblDataColor1."'><td>".REQ."Date</td><td>".mkDateSelect("date")."</td>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Amount</td><td><input type=text size=20 name=amount value=''></td></tr>
		</table>
	</td></tr>
	<tr><td valign=bottom colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View cash flow budget entries</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $enter;
}

# error func
function enter_err ($HTTP_POST_VARS, $err="")
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Keep the charge vat option stable
	$chadd = "";
	$chpurch = "";
	if($funds == "in"){
		$chadd = "checked=yes";
	}else{
		$chpurch = "checked=yes";
	}

	$enter =
	"<h3>New cash flow budget entry</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<table cellpadding=0 cellspacing=0>
	<tr><td colspan=2>$err</td></tr>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Description</td><td><input type=text size=20 name=des value='$des'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Funds</td><td valign=center>In<input type=radio name=funds value='in' $chadd> Purchase Asset<input type=radio name=funds value='out' $chpurch></td></tr>
		<tr><th colspan=2>Date</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>".mkDateSelect("date")."</td>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td><input type=text size=20 name=amount value='$amount'></td></tr>
		</table>
	</td></tr>
	<tr><td valign=bottom colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View Cash flow budget entries</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($des, "string", 1, 255, "Invalid decription.");
	$v->isOk ($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	$v->isOk ($funds, "string", 1, 255, "Invalid method.");

	# mix dates
	$date = $date_year."-".$date_month."-".$date_day;

	$date_year+=0;
	$date_month+=0;
	$date_day+=0;

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return enter_err($HTTP_POST_VARS, $confirm);
		exit;
	}

	$confirm =
	"<h3>Confirm cash flow budget entry</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=day value='$date_day'>
	<input type=hidden name=mon value='$date_month'>
	<input type=hidden name=year value='$date_year'>
	<input type=hidden name=funds value='$funds'>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td><input type=hidden name=des value='$des'>$des</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Funds</td><td>$funds</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td><input type=hidden name=bdate value='$date'>$date</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td align=right><input type=hidden name=amount value='$amount'>".CUR." $amount</td></tr>
		<tr><td><input type=submit name=back value='&laquo; Correction'></td><td valign=bottom align=right><input type=submit value='Write &raquo;'></td></tr>
		</table>
	</td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View cash flow budget entries</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		return enter_err($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($des, "string", 1, 255, "Invalid decription.");
	$v->isOk ($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk ($bdate, "string", 10,10, "Invalid Date .");
	$v->isOk ($funds, "string", 1, 255, "Invalid method.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>$e[msg]</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	//$date = date("Y-m-d");
	$date=$bdate;

	db_connect ();

	if($funds=="out") {
		$amount=$amount*-1;
	}

	$Sl = "INSERT INTO cf(description, date, amount, div) VALUES ('$des','$date','$amount', '".USER_DIV."')";
	$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);
	if (pg_cmdtuples ($Rs) < 1) {
		return "<li class=err>Unable to add entry to database.</li>";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Cash flow budget entry added to the system</th></tr>
	<tr class=datacell><td>New cash flow budget entry has been added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-add.php'>New cash flow budget entry</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View cash flow budget entries</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

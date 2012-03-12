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
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter ($_GET);
} else {
	$OUTPUT = "Invalid.";
}

# display output
require ("../template.php");

# enter new data
function enter ($HTTP_VARS) {

	extract($HTTP_VARS);

	$id+=0;

	$Sl="SELECT * FROM cf WHERE id='$id'";
	$Ri=db_exec($Sl);

	$i=pg_fetch_array($Ri);

	$chadd = "";
	$chpurch = "";
	if($i['amount']>0){
		$chadd = "checked=yes";
	}else{
		$chpurch = "checked=yes";
		$i['amount']=abs($i['amount']);
	}

	$dates=explode("-",$i['date']);

	$enter =
	"<h3>Edit cash flow budget entry </h3>
	<form action='".SELF."' method=post>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td><input type=text size=20 name=des value='$i[description]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Funds In/Out?</td><td valign=center>In<input type=radio name=funds value='in' $chadd>Out<input type=radio name=funds value='out' $chpurch></td></tr>
	<tr><th colspan=2>Date</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td><table><tr><td><input type=text size=2 name=day maxlength=2 value='$dates[2]'>
	</td><td>-</td><td><input type=text size=2 name=mon maxlength=2  value='$dates[1]'></td><td>-</td><td>
	<input type=text size=4 name=year maxlength=4 value='$dates[0]'></td></tr></table></td>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td><input type=text size=20 name=amount value='$i[amount]'></td></tr>
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
function enter_err ($_POST, $err="")
{
	# get vars
	foreach ($_POST as $key => $value) {
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
	<input type=hidden name=id value='$id'>
	<table cellpadding=0 cellspacing=0>
	<tr><td colspan=2>$err</td></tr>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Description</td><td><input type=text size=20 name=des value='$des'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Funds</td><td valign=center>In<input type=radio name=funds value='in' $chadd> Purchase Asset<input type=radio name=funds value='out' $chpurch></td></tr>
		<tr><th colspan=2>Date</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td><table><tr><td><input type=text size=2 name=day maxlength=2 value='$day'>
		</td><td>-</td><td><input type=text size=2 name=mon maxlength=2  value='$mon'></td><td>-</td><td>
		<input type=text size=4 name=year maxlength=4 value='$year'></td></tr></table></td>
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
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($des, "string", 1, 255, "Invalid decription.");
	$v->isOk ($amount, "float", 1, 255, "Invalid amount.");
	$v->isOk ($day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($mon, "num", 1,2, "Invalid Date month.");
	$v->isOk ($year, "num", 1,4, "Invalid Date Year.");
	$v->isOk ($funds, "string", 1, 255, "Invalid method.");

	# mix dates
	$date = $year."-".$mon."-".$day;

	$year+=0;
	$mon+=0;
	$day+=0;

	if(!checkdate($mon, $day, $year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return enter_err($_POST, $confirm);
		exit;
	}

	$confirm =
	"<h3>Confirm cash flow budget entry</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=mon value='$mon'>
	<input type=hidden name=year value='$year'>
	<input type=hidden name=funds value='$funds'>
	<input type=hidden name=id value='$id'>
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
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		return enter_err($_POST);
	}

	$id+=0;
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
			$confirmCust .= "<li class=err>".$e["msg"];
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

	$Sl="UPDATE cf SET description='$des',date='$date',amount='$amount' WHERE id='$id'";
	$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Cash flow budget entry updated</th></tr>
	<tr class=datacell><td>New cash flow budget entry has been updated in the system.</td></tr>
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

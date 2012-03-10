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

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get templete
require("template.php");

# Default view
function view()
{
	# Check if year has been opened
	core_connect();
	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);
	if(pg_numrows($cRs) > 0){
		header("Location: finyearnames-view.php");
		exit;
	}

	// Layout
	$view = "
	<h3>Add Financial Year Names</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>This Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr1 value=y2003></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>2nd Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr2 value=y2004></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>3rd Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr3 value=y2005></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>4th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr4 value=y2006></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>5th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr5 value=y2007></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>6th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr6 value=y2008></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>7th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr7 value=y2009></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>8th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr8 value=y2010></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>9th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr9 value=y2011></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>10th Financial Year</td><td valign=center><input type=text size=14 maxlength=14 name=yr10 value=y2012></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Year Names &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}


# confirm
function confirm($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($yr1, "string", 1, 14, "Invalid 1st Financial year.");
	$v->isOk ($yr2, "string", 1, 14, "Invalid 2nd Financial year.");
	$v->isOk ($yr3, "string", 1, 14, "Invalid 3rd Financial year.");
	$v->isOk ($yr4, "string", 1, 14, "Invalid 4th Financial year.");
	$v->isOk ($yr5, "string", 1, 14, "Invalid 5th Financial year.");
	$v->isOk ($yr6, "string", 1, 14, "Invalid 6th Financial year.");
	$v->isOk ($yr7, "string", 1, 14, "Invalid 7th Financial year.");
	$v->isOk ($yr8, "string", 1, 14, "Invalid 8th Financial year.");
	$v->isOk ($yr9, "string", 1, 14, "Invalid 9th Financial year.");
	$v->isOk ($yr10, "string", 1, 14, "Invalid 10th Financial year.");

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

	# Check if year has been opened
	core_connect();
	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);
	if(pg_numrows($cRs) > 0){
		header("Location: finyearnames-view.php");
		exit;
	}

	# rtrim the names
	$yr1 = preg_replace("[\s]", "_", trim($yr1));
	$yr2 = preg_replace("[\s]", "_", trim($yr2));
	$yr3 = preg_replace("[\s]", "_", trim($yr3));
	$yr4 = preg_replace("[\s]", "_", trim($yr4));
	$yr5 = preg_replace("[\s]", "_", trim($yr5));
	$yr6 = preg_replace("[\s]", "_", trim($yr6));
	$yr7 = preg_replace("[\s]", "_", trim($yr7));
	$yr8 = preg_replace("[\s]", "_", trim($yr8));
	$yr9 = preg_replace("[\s]", "_", trim($yr9));
	$yr10 = preg_replace("[\s]", "_", trim($yr10));

	$confirm =
	"<h3>Add Financial Year Names to Database</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=yr1 value='$yr1'>
	<input type=hidden name=yr2 value='$yr2'>
	<input type=hidden name=yr3 value='$yr3'>
	<input type=hidden name=yr4 value='$yr4'>
	<input type=hidden name=yr5 value='$yr5'>
	<input type=hidden name=yr6 value='$yr6'>
	<input type=hidden name=yr7 value='$yr7'>
	<input type=hidden name=yr8 value='$yr8'>
	<input type=hidden name=yr9 value='$yr9'>
	<input type=hidden name=yr10 value='$yr10'>
	<tr><td colspan=2><li class=err>Please note: if the following databases exist, they will be dropped.<p>
	".($yr1."_audit").", ".($yr2."_audit").", ".($yr3."_audit").", ".($yr4."_audit").",<br>
	".($yr5."_audit").", ".($yr6."_audit").", ".($yr7."_audit").", ".($yr8."_audit").",<br>
	".($yr9."_audit").", ".($yr10."_audit")."</td></tr>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>This Financial Year</td><td valign=center>$yr1</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>2nd Financial Year</td><td valign=center>$yr2</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>3rd Financial Year</td><td valign=center>$yr3</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>4th Financial Year</td><td valign=center>$yr4</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>5th Financial Year</td><td valign=center>$yr5</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>6th Financial Year</td><td valign=center>$yr6</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>7th Financial Year</td><td valign=center>$yr7</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>8th Financial Year</td><td valign=center>$yr8</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>9th Financial Year</td><td valign=center>$yr9</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>10th Financial Year</td><td valign=center>$yr10</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm Names &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# write
function write($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($yr1, "string", 1, 14, "Invalid 1st Financial year.");
	$v->isOk ($yr2, "string", 1, 14, "Invalid 2nd Financial year.");
	$v->isOk ($yr3, "string", 1, 14, "Invalid 3rd Financial year.");
	$v->isOk ($yr4, "string", 1, 14, "Invalid 4th Financial year.");
	$v->isOk ($yr5, "string", 1, 14, "Invalid 5th Financial year.");
	$v->isOk ($yr6, "string", 1, 14, "Invalid 6th Financial year.");
	$v->isOk ($yr7, "string", 1, 14, "Invalid 7th Financial year.");
	$v->isOk ($yr8, "string", 1, 14, "Invalid 8th Financial year.");
	$v->isOk ($yr9, "string", 1, 14, "Invalid 9th Financial year.");
	$v->isOk ($yr10, "string", 1, 14, "Invalid 10th Financial year.");

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

	# Drop there year audit DBs if they exist
	for($i = 1; $i <= 10; $i++){
		$y = "yr".$i;
		$yrdb = $$y."_audit";
		if(db_exs($yrdb)){
			$sql = "DROP DATABASE $yrdb";
			$rs = @db_exec($sql);
		}
	}


	# Writes to Database and return to calling function
	core_connect();

	# Empty the year name table
	$sql = "TRUNCATE TABLE year";
	$rslt = db_exec($sql);
	for($i = 1; $i <= 10; $i++){
		$y = "yr".$i;
		$sql = "INSERT INTO year VALUES('".$$y."', '$y')";
		$rslt = db_exec($sql) or errDie("Could not set year name in Cubit",SELF);
	}

	return "<center><h3>Financial year names have been set.</h3>
	<a href='finyear-range.php' class=nav>Set Period range for Financial years</a>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
}

function db_exs($dbname){
	db_connect();
	$sql = "SELECT datname FROM pg_database WHERE datname = '$dbname'";
	$rs = db_exec($sql);
	if(pg_numrows($rs) > 0){
		return true;
	}else{
		return false;
	}
}
?>

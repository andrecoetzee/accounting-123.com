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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view()
{

	# Just months
	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

	# Check if year has been opened
	core_connect();
	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);
	if(pg_numrows($cRs) > 0){
		# Get the range
		core_connect();
		$sql = "SELECT * FROM range";
		$Rslt = db_exec($sql);
		if(pg_numrows($Rslt) < 1){
			$OUTPUT = "<center><li class=err>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.";
			require("template.php");
		}
		$range = Pg_fetch_array($Rslt);

		global $PRDMON, $MONPRD;
		$pmon = 0;
		$fyear = getFinYear() - (int)($PRDMON[1] > 1);

		$prddesc = array();
		for ($i = 1; $i <= 12; $i++) {
			$mon = $PRDMON[$i];

			if ($mon < $pmon) {
				++$fyear;
			}
			$pmon = $mon;

			if ($i == 1) {
				$smonth = getMonthName($mon)." $fyear";
			} else if ($i == 12) {
				$endmon = getMonthName($mon)." $fyear";
			}
		}

		$prddesc = implode(" to ", $prddesc);

		$ret = "<p><p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td colspan=2><li class=err>Financial year period range has already been set.</td></tr>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Financial Year Starts in</td><td align=center>$smonth</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Financial Year Ends in</td><td align=center>$endmon</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

		return $ret;
	}

	$month=1;
	$smonth = "<select name=smonth>";
	while($month <= 12){
			$smonth .="<option value='$month'>$months[$month]</option>";
			$month++;
	}
	$smonth .="</select>";

	//layout
	$view = "
	<h3>Set Financial Year Period Range</h3>
	<form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<input type=hidden name=key value=confirm>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Financial Years Start in</td><td valign=center>$smonth</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</form>
	</table>";

	return $view;
}

function confirm($_POST)
{
        # Get vars
		foreach ($_POST as $key => $value) {
		$$key = $value;
		}

        # Validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($smonth, "num", 1, 2, "Invalid Financial year starting month.");

        # Display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}

		// Months array
		$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

		// calc
		$endmon = ($smonth - 1);
		if(intval($endmon == 0)) $endmon = 12;

		//layout
		$confirm = "
		<h3>Financial year Period Range</h3>
		<form action='".SELF."' method=post name=form>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type=hidden name=key value=write>
			<input type=hidden name=smon value='$smonth'>
			<input type=hidden name=endmon value='$endmon'>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Financial Year Starts in</td><td align=center>$months[$smonth]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Financial Year Ends in</td><td align=center>$months[$endmon]</td></tr>
			<tr><td><br></td></tr>
			<tr><td><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table></form>";

		return $confirm;
}

# write
function write($_POST)
{
        # get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($smon, "num", 1, 2, "Invalid Financial year starting month.");
		$v->isOk ($endmon, "num", 1, 2, "Invalid Financial year ending month.");

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

		// connect
		core_connect();
		$Sql = "TRUNCATE range";
		$Rs = db_exec($Sql) or errDie("Unable to empty year range", SELF);

		$sql = "INSERT INTO range(\"start\", \"end\") VALUES('$smon', '$endmon')";
        $Rslt = db_exec($sql) or errDie("Unable to insert year range", SELF);

		return "<br><center><b>Period Range has been set successfully for financial Years</b>
        	<p><a href='yr-open.php' class=nav>Open a Financial Year</a>";
}
?>

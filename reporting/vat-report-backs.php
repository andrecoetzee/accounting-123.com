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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "view":
				if(isset($_POST["amt"])){
					$OUTPUT = viewRepAmt($_POST);
					break;
				}elseif(isset($_POST["inp"])){
					$OUTPUT = viewRepIn($_POST);
					break;
				}elseif(isset($_POST["out"])){
					$OUTPUT = viewRepOut($_POST);
					break;
				}elseif(isset($_POST["srchout"])){
					$OUTPUT = viewOut($_POST);
					break;
				}elseif(isset($_POST["srchin"])){
					$OUTPUT = viewIn($_POST);
					break;
				}else{
					$OUTPUT = viewRep($_POST);
					break;
				}
				break;

			default:
				$OUTPUT = view();
			}
} else {
	$OUTPUT = view();
}

# get templete
require("../template.php");

# Default view
function view()
{
	// Layout
	$view = "
	<h3>Vat Report<h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=view>
		<tr><th colspan=2>Transactions By Date Range</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'></td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Vat Balance</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=submit name=amt value='View Amount'></td><td align=center><input type=submit name=srch value='Search by Date'></td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>VAT Input</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=submit name=inp value='View Amount'></td><td align=center><input type=submit name=srchin value='Search by Date'></td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>VAT Output</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=submit name=out value='View Amount'></td><td align=center><input type=submit name=srchout value='Search by Date'></td></tr>
		<tr><td><br></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# Default view
function viewRep($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;
		if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printRep = "
	<h3>Vat Report</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
	<tr><th>Date</th><th>Reference</th><th>Amount</th><th>Description</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=10><li>No previous vat Transactions.</li></td></tr>";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){
			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "<tr bgcolor='$bgColor'><td>$vat[edate]</td><td>$vat[ref]</td><td>".CUR." $vat[amount]</td><td>$vat[descript]</td></tr>";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b>Total vat balance</b></td><td colspan=2 align=right><b>".CUR." $vattot</b></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center colspan=10>
			<form action='../xls/vat-report-xls.php' method=post name=form>
			<input type=hidden name=key value=view>
			<input type=hidden name=fday value='$fday'>
			<input type=hidden name=fmon value='$fmon'>
			<input type=hidden name=fyear value='$fyear'>
			<input type=hidden name=today value='$today'>
			<input type=hidden name=tomon value='$tomon'>
			<input type=hidden name=toyear value='$toyear'>
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}

# Default view
function viewRepAmt()
{


	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount < 0 AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount > 0 AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);
	$pvat['sum'] = sprint($pvat['sum'] * (-1));

	$totbal = sprint($rvat['sum'] - $pvat['sum']);

	# Set up table to display in
	$printRep = "
	<h3>Vat Report</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
    <tr><th colspan=2>Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Total Vat Paid</td><td>".CUR." $pvat[sum]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Total Vat Received</td><td>".CUR." $rvat[sum]</td></tr>
	<tr><td><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><b>Total Vat Balance</b></td><td><b>".CUR." $totbal</b></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center colspan=10>
			<form action='../xls/vat-report-xls.php' method=post name=form>
			<input type=hidden name=key value=view>
			<input type=hidden name=amt value=' '>
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}

# Vat input transactions
function viewIn($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;
		if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printRep = "
	<h3>Vat Report - Input</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
	<tr><th>Date</th><th>Reference</th><th>Amount</th><th>Description</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND amount < 0 AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=10><li>No previous vat Transactions.</li></td></tr>";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){
			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "<tr bgcolor='$bgColor'><td>$vat[edate]</td><td>$vat[ref]</td><td>".CUR." $vat[amount]</td><td>$vat[descript]</td></tr>";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b>Total vat balance</b></td><td colspan=2 align=right><b>".CUR." $vattot</b></td></tr>
	<tr><td><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}

# Default view
function viewRepIn()
{


	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount < 0 AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	$pvat['sum'] = sprint($pvat['sum']);

	$totbal = sprint($pvat['sum']);

	# Set up table to display in
	$printRep = "
	<h3>Vat Report</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
    <tr><th colspan=2>Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Total VAT Input</td><td>".CUR." $pvat[sum]</td></tr>
	<tr><td><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}

# Vat output transactions
function viewOut($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;
		if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printRep = "
	<h3>Vat Report - Output</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
	<tr><th>Date</th><th>Reference</th><th>Amount</th><th>Description</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND amount > 0 AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=10><li>No previous vat Transactions.</li></td></tr>";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){
			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "<tr bgcolor='$bgColor'><td>$vat[edate]</td><td>$vat[ref]</td><td>".CUR." $vat[amount]</td><td>$vat[descript]</td></tr>";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b>Total vat balance</b></td><td colspan=2 align=right><b>".CUR." $vattot</b></td></tr>
	<tr><td><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}

# Default view
function viewRepOut()
{


	# connect to database
	db_connect ();

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount > 0 AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);

	$totbal = sprint($rvat['sum']);

	# Set up table to display in
	$printRep = "
	<h3>Vat Report</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
    <tr><th colspan=2>Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Total VAT Output</td><td>".CUR." $rvat[sum]</td></tr>
	<tr><td><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printRep;
}
?>

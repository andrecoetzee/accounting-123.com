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

require ("settings.php");
require ("libs/ext.lib.php");

if (isset($_GET["stkid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "view":
				$OUTPUT = printStk($_POST);
				break;

			case "report":
				$OUTPUT = report($_POST);
				break;

			default:
				$OUTPUT = slct();
				break;
		}
	} else {
			# Display default output
			$OUTPUT = slct();
	}
}

require ("template.php");

# Default view
function slct()
{
	//layout
	$view = "<P><P>
	<form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=250>
		<input type=hidden name=key value=view>
		<tr><th colspan=2>Non-Stock Sales Report</th></tr>
		<tr class='bg-odd'><td align=center colspan=2>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'></td></tr>
		<tr><td><br></td></tr>
		<tr class='bg-odd'><td align=center colspan=2><input type=submit value='View'></td></tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='sales-reports.php'>Sales Reports</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# show stock
function printStk ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");

	# mix dates
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

	# connect to database
	db_connect ();

	// Layout
	$report = "
	<h3>Non-Stock Sales Report</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Invoice no.</th><th>Date</th><th>Vat</th><th>SubTotal</th><th>Total</th></tr>";

	# Get all relevant records
	db_connect();
	$sql = "SELECT * FROM salesrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND typ = 'non' AND div = '".USER_DIV."'";
	$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

	$i = 0;
	$tot = 0;
	$totvat = 0;
	$totexc = 0;
	while ($rec = pg_fetch_array ($recRslt)) {

		# Calculate profit
		$tot += $rec['total'];
		$totvat += $rec['vat'];
		$excvat = sprint($rec['total'] - $rec['vat']);
		$totexc += $excvat;

		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$report .= "<tr bgcolor='$bgColor'><td>$rec[invnum]</td><td>$rec[edate]</td><td>".CUR." $rec[vat]</td><td>".CUR." $excvat</td><td>".CUR." $rec[total]</td></tr>";
		$i++;
	}

	$tot = sprint($tot);
	$totvat = sprint($totvat);
	$totexc = sprint($totexc);

	$report .= "<tr class='bg-even'><td colspan=2><b>Totals</b></td><td>".CUR." $totvat</td><td>".CUR." $totexc</td><td>".CUR." $tot</td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='sales-reports.php'>Sales Reports</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $report;
}
?>

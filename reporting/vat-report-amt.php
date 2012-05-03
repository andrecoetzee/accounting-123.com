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
				$OUTPUT = viewRep($_POST);
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
	<h3>View Vat Report<h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=view>
		<tr><th>By Date Range</th></tr>
		<tr class='bg-odd'><td align=center>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
		</td><td valign=bottom><input type=submit value='Search'></td></tr>
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
function viewRep()
{


	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount < 1";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE amount > 0";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);
	$pvat['sum'] = sprint($pvat['sum']);

	$totbal = sprint($rvat['sum'] - $pvat['sum']);

	# Set up table to display in
	$printRep = "
	<h3>Vat Report</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
    <tr><th colspan=2>Details</th></tr>
	<tr class='bg-odd'><td>Total Vat Paid</td><td>".CUR." $pvat[sum]</td></tr>
	<tr class='bg-even'><td>Total Vat Received</td><td>".CUR." $rvat[sum]</td></tr>
	<tr><td><br></td></tr>
	<tr class='bg-odd'><td><b>Total Vat Balance</b></td><td><b>".CUR." $totbal</b></td></tr>
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

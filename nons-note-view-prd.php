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
require ("core-settings.php");
require_lib("docman");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printPurch ($_POST);
			break;

		default:
			$OUTPUT = slct ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct ();
}

require ("template.php");

# Default view
function slct()
{
	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class=err>ERROR : There are no periods set for the current year";
	}
	$Prds = "<select name=prd>";
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$Prds .= "</select>";

	//layout
	$slct = "
	<h3>View Received Non-Stock Orders</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=view>
		<tr><th colspan=2>By Date Range</th></tr>
		<tr class='bg-odd'><td align=center colspan=2>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'></td></tr>
		<tr class='bg-even'><td>Select Period</td><td>$Prds</td><td valign=bottom><input type=submit value='Search'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='nons-purchase-new.php'>New Order</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='stock-report.php'>Stock Control Reports</a></td></tr>
		<tr class='bg-even'><td><a href='stock-view.php'>View Stock</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $slct;
}

# show stock
function printPurch ($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
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

	# Set up table to display in
	$printOrd = "<center>
	<h3>Received Non-Stock Orders</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Purchase No.</th><th>Purchase Date</th><th>Supplier</th><th>Sub Total</th><th>Delivery Charges</th><th>Vat</th><th>Total</th><th>Delevery Reference No.</th><th>Documents</th><th colspan=5>Options</th></tr>";

	# connect to database
	db_conn ($prd);

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

    $sql = "SELECT * FROM nons_purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND done = 'y' AND div = '".USER_DIV."' ORDER BY pdate DESC";
    $stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock Non-Stock Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li> There are no stock Non-Stock Orders found.
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='nons-purchase-new.php'>New Non-Stock Order</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='stock-report.php'>Stock Control Reports</a></td></tr>
			<tr class='bg-even'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {
		# date format
        $date = explode("-", $stkp['pdate']);
        $date = $date[2]."-".$date[1]."-".$date[0];

		# calculate the Sub-Total
		$stkp['total']=sprint($stkp['total']);
		$stkp['shipchrg']=sprint($stkp['shipping']);
		$subtot = ($stkp['subtot']);
		$subtot=sprint($subtot);
		$tot1=sprint(($tot1+$subtot));
		$tot2=sprint(($tot2+$stkp['shipchrg']));
		$tot3=sprint(($tot3+$stkp['total']));
		$vat=($stkp['vat']);
		$tot4=sprint($tot4+$vat);
		# Get documents
		$docs = doclib_getdocs("npur", $stkp['purnum']);

		$printOrd .= "<tr class='".bg_class()."'><td>$stkp[purnum]</td><td>$date</td><td>$stkp[supplier]</td><td align=right>".CUR." $subtot</td><td align=right>".CUR." $stkp[shipchrg]</td><td align=right>".CUR." $vat</td><td align=right>".CUR." $stkp[total]</td><td>$stkp[refno]</td><td>$docs</td>";

		if($stkp['returned'] != 'y'){
			$printOrd .= "<td><a href='nons-purch-return.php?purid=$stkp[purid]&prd=$prd'>Return</a></td><td><a href='nons-purch-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td></tr>";
		}else{
			$printOrd .= "<td><br></td><td><a href='nons-purch-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td></tr>";
		}
		$i++;
	}
	
	$printOrd .= "<tr class='".bg_class()."'><td colspan=3>Totals</td><td align=right>".CUR." $tot1</td><td align=right>".CUR." $tot2</td><td align=right>".CUR." $tot4</td><td align=right>".CUR." $tot3</td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='nons-purchase-new.php'>New Non-Stock Order</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printOrd;
}
?>

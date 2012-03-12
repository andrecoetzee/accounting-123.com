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

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "There are no Warehouses found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

	# Select the stock category
	db_connect();
	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
	}
	$cats .="</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .="</select>";

	//layout
	$view = "<h3>Stock Sales Report</h3>
	<table cellpadding=5><tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=view>
			<tr><th colspan=2>Store</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2>$whs</td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2>
			<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
			&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
			<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Category</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$cats</td><td valign=bottom><input type=submit name=cat value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Classification</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$class</td><td valign=bottom><input type=submit name=class value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>All Categories and Classifications</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2><input type=submit name=all value='View All'></td></tr>
			</form>
		</table>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='sales-reports.php'>Sales Reports</a></td></tr>
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
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");
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

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND catid = '$catid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
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

	# Query server
	$i = 0;
    $stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "<li class=err> There are no stock items found.</li>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='sales-reports.php'>Sales Reports</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	// Layout
	$report = "
	<h3>Stock Sales Report</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Code</th><th>Description</th><th>Quantity Sold</th><th>Total Selling Price</th><th>Gross Profit</th></tr>";

	$i = 0;
	$totprof = 0;
	$totqty = 0;
	$totcsprice = 0;
	while ($stk = pg_fetch_array ($stkRslt)) {
		# Get all relevant records
		db_connect();
		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'invoice' AND div = '".USER_DIV."'";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$rec = pg_fetch_array($recRslt);

		# zeros
		$rec['qty'] += 0;
		$rec['csprice'] += 0;
		$rec['csamt'] += 0;

		# Calculate profit
		$prof = ($rec['csprice'] - $rec['csamt']);
		$totprof += $prof;
		$totcsprice += $rec['csprice'];
		$totqty += $rec['qty'];

		# Limit to 30 chars
		$stk['stkdes'] = extlib_rstr($stk['stkdes'], 30);

		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$report .= "<tr bgcolor='$bgColor'><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$rec[qty]</td><td>".CUR." $rec[csprice]</td><td>".CUR." $prof</td></tr>";
		$i++;
	}

	$report .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Totals</b></td><td>$totqty</td><td>".CUR." $totcsprice</td><td>".CUR." $totprof</td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='sales-reports.php'>Sales Reports</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $report;
}
?>

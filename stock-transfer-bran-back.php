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
require("core-settings.php");
require ("libs/ext.lib.php");

if (isset($_GET["stkid"])) {
	$OUTPUT = details($_GET["stkid"]);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "view":
				$OUTPUT = printStk($_POST);
				break;

			case "details2":
				$OUTPUT = details2($_POST);
				break;

			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
				$OUTPUT = write($_POST);
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
	$view = "<h3>Stock Transfer</h3>
	<table cellpadding=5><tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=view>
			<tr><th colspan=2>Store</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2>$whs</td></tr>
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
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $view;
}

# Show stock
function printStk ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

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

	# Set up table to display in
	$printStk = "
    <h3>Current Stock</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Code</th><th>Description</th><th>Class</th><th>On Hand</th><th>Cost Amount</th><th>Allocated</th><th>On order</th><th>Unit</th></tr>";

	# Connect to database
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
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-view.php'>Back</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
	}

	while ($stk = pg_fetch_array ($stkRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printStk .= "<tr bgcolor='$bgColor'><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stk[classname]</td><td align=right>$stk[units]</td><td align=right>".CUR." $stk[csamt]</td><td align=right>$stk[alloc]</td><td align=right>$stk[ordered]</td>
		<td>$stk[suom]</td>";

		# If there is stock on hand
		if(($stk['units'] - $stk['alloc']) > 0){
			$printStk .= "<td>&nbsp;&nbsp;<a href='stock-transfer-bran.php?stkid=$stk[stkid]'>Transfer</a>&nbsp;&nbsp;</td></tr>";
		}else{
			$printStk .= "<td><br></td></tr>";
		}
		$i++;
	}

	$printStk .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printStk;
}

# Confirm
function details($stkid)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# Get stock vars
	foreach ($stk as $key => $value) {
		$$key = $value;
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	db_connect();
	# Select the stock warehouse
	$brans= "<select name='sdiv'>";
	$sql = "SELECT * FROM branches WHERE div != '$div' ORDER BY branname ASC";
	$branRslt = db_exec($sql);
	if(pg_numrows($branRslt) < 1){
		return "<li>There are no other branches in Cubit.
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
	}else{
		while($bran = pg_fetch_array($branRslt)){
			$brans .= "<option value='$bran[div]'>$bran[branname]</option>";
		}
	}
	$brans .="</select>";

	# available stock units
	$avstk = ($units - $alloc);

	// Layout
	$details =
	"<center>
	<h3>Transfer Stock</h3>
	<h4>Stock Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details2>
	<input type=hidden name=stkid value='$stkid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Warehouse</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$catname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Serial No.</td><td>$serno</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$stkcod</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($stkdes)."</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>On Hand</td><td>$units</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Allocated</td><td>$alloc</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Available</td><td>$avstk</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>On Order</td><td>$ordered</td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Transfer to</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To Branch</td><td>$brans</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $details;
}

# Confirm
function details2($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	db_connect();
	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	db_conn("exten");
	# Select the stock warehouse
	$whs = "<select name='whid'>";
	$sql = "SELECT whid,whname,whno FROM warehouses WHERE div = '$sdiv' ORDER BY whname ASC";
	$swhRslt = db_exec($sql);
	if(pg_numrows($swhRslt) < 1){
			return "<li>There are no stores on the seleted branch: <b>$sbran[branname]</b>.
			<p>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
				<tr><td><br></td></tr>
				<tr><th>Quick Links</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
			</table>";
	}else{
		while($swh = pg_fetch_array($swhRslt)){
			$whs .= "<option value='$swh[whid]'>($swh[whno]) $swh[whname]</option>";
		}
	}
	$whs .="</select>";

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	// Layout
	$details =
	"<center>
	<h3>Transfer Stock</h3>
	<h4>Stock Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=stkid value='$stkid'>
	<input type=hidden name=sdiv value='$sdiv'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$bran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Warehouse</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$stk[catname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Serial No.</td><td>$stk[serno]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($stk['stkdes'])."</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>On Hand</td><td>$stk[units]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Allocated</td><td>$stk[alloc]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Available</td><td>$avstk</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>On Order</td><td>$stk[ordered]</td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Transfer to</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To Branch</td><td>$sbran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>To Store </td><td>$whs</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Number of units</td><td><input type=text size=7 name='tunits' value='1'></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $details;
}

# Confirm
function confirm($_POST)
{
	# Get stock vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "num", 1, 50, "Invalid number of units.");


	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
	}else{
			$stk = pg_fetch_array($stkRslt);
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '$sdiv'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	db_connect();
	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	# Get stock from selected warehouse
	db_connect();
	$sql = "SELECT * FROM stock WHERE whid = '$whid' AND lower(stkcod) = lower('$stk[stkcod]') AND div = '$sdiv'";
	$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sstkRslt) < 1){
		$sstk = $stk;
		$head = "New Stock";
		$data = "<tr bgcolor='".TMPL_tblDataColor1."'><td>Location</td><td>Shelf <input type=text size=5 name='shelf'> Row <input type=text size=5 name='row'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Level</td><td>Minimum <input type=text size=5 name='minlvl' value='$stk[minlvl]'> Maximum <input type=text size=5 name='maxlvl' value='$stk[maxlvl]'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Selling price per unit</td><td>".CUR." <input type=hidden name='selamt' value='$stk[selamt]'>$stk[selamt]</td></tr>";
	}else{
		$sstk = pg_fetch_array($sstkRslt);
		$data = "";
		$head = "";
	}

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	// Layout
	$confirm =
	"<center>
	<h3>Transfer Stock</h3>
	<h4>Confirm Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=stkid value='$stkid'>
	<input type=hidden name=sstkid value='$sstk[stkid]'>
	<input type=hidden name=sdiv value='$sdiv'>
	<input type=hidden name=whid value='$whid'>
	<input type=hidden name=tunits value='$tunits'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$bran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Warehouse</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$stk[catname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Serial No.</td><td>$stk[serno]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($stk['stkdes'])."</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>On Hand</td><td>$stk[units]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Allocated</td><td>$stk[alloc]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Available</td><td>$avstk</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>On Order</td><td>$stk[ordered]</td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Transfer to $head</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>To Branch</td><td>$sbran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To Store </td><td>$swh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$sstk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($sstk['stkdes'])."</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Number of units</td><td>$tunits</td></tr>
		$data
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='transfer &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# Write
function write($_POST)
{
	# get stock vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sstkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "num", 1, 50, "Invalid number of units.");
	if($stkid == $sstkid){
		$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
		$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
		$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
		$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
		$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
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

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
	}else{
			$stk = pg_fetch_array($stkRslt);
	}

	if($stkid == $sstkid){
		$sstk = $stk;
		$head = "New Stock";
		$data = "<tr bgcolor='".TMPL_tblDataColor1."'><td>Location</td><td>Shelf : <input type=hidden name='shelf' value='$shelf'>$shelf - Row : <input type=hidden name='row' value='$row'>$row</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Level</td><td>Minimum : <input type=hidden name='minlvl' value='$minlvl'>$minlvl -  Maximum : <input type=hidden name='maxlvl' value='$maxlvl'>$maxlvl</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Selling price per unit</td><td>".CUR." <input type=hidden name='selamt' value='$stk[selamt]'>$stk[selamt]</td></tr>";
	}else{
		$sql = "SELECT * FROM stock WHERE stkid = '$sstkid' AND div = '$sdiv'";
		$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($sstkRslt) < 1){
				return "<li> Invalid Stock ID.";
		}else{
				$sstk = pg_fetch_array($sstkRslt);
		}
		$head = "";
		$data = "";
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$whid' AND div = '$sdiv'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	/* Start Stock transfering */

		db_connect();
		$csamt = ($tunits * $stk['csprice']);
		if($stkid == $sstkid){
			# Create new stock item on the other hand
			$sql = "INSERT INTO stock(stkcod, serno, stkdes, prdcls, classname, csamt, units, buom, suom, rate, shelf, row, minlvl, maxlvl, csprice, selamt, catid, catname, whid, blocked, type, alloc, com, div) ";
			$sql .= "VALUES('$sstk[stkcod]', '$sstk[serno]', '$sstk[stkdes]', '$sstk[prdcls]', '$sstk[classname]', '$csamt',  '$tunits', '$sstk[buom]', '$sstk[suom]', '$sstk[rate]', '$shelf', '$row', '$minlvl', '$maxlvl', '$sstk[csprice]', '$sstk[selamt]', '$sstk[catid]', '$sstk[catname]', '$whid', 'n', '$sstk[type]', '0', '0', '$sdiv')";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

			# Reduce on the other hand
			$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);
		}else{
			# Move units and csamt
			$sql = "UPDATE stock SET units = (units + '$tunits'), csamt = (csamt + '$csamt') WHERE stkid = '$sstkid' AND div = '$sdiv'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

			# Reduce on the other hand
			$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);
		}

		# todays date
		$date = date("d-m-Y");

		$refnum = getrefnum($date);
		$srefnum = getrefnum($date);

		# dt(conacc) ct(stkacc)
		writetrans($wh['conacc'], $wh['stkacc'], $date, $refnum, $csamt, "Stock Transfer", USER_DIV);

		writetrans($swh['stkacc'], $swh['conacc'], $date, $srefnum, $csamt, "Stock Transfer", $sdiv);

	/* End Stock transfering */

	db_connect();
	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
	}else{
			$stk = pg_fetch_array($stkRslt);
	}

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	# return
	$write = "<h3> Stock has been Transfered </h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$bran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Warehouse</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$stk[catname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($stk['stkdes'])."</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>On Hand</td><td>$stk[units]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Allocated</td><td>$stk[alloc]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Available</td><td>$avstk</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>On Order</td><td>$stk[ordered]</td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Transfered to $head</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>To Branch</td><td>$sbran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To Store </td><td>$swh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$sstk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td>".nl2br($sstk['stkdes'])."</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Number of units transfered</td><td>$tunits</td></tr>
		$data
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
function writetrans($dtacc, $ctacc, $date, $refnum, $amount, $details, $div)
{
        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($date, "date", 1, 14, "Invalid date.");
        $v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Insert the records into the transect table
		db_conn(PRD_DB);
		$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details, div) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details', '$div')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Update the balances by adding appropriate values to the trial_bal Table
		core_connect();
		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '$div'";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '$div'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

	# commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
}
?>

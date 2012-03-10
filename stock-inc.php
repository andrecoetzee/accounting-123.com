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
require("core-settings.php");
require ("libs/ext.lib.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "details":
			$OUTPUT = details($HTTP_POST_VARS);
			break;

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
	$OUTPUT = view();
}


# get templete
require("template.php");

# Default view
function view()
{

	# Select warehouse
	db_conn("exten");
	# Get pricelist
	$pricelists = "<select name='listid' style='width: 120'>";
	$sql = "SELECT * FROM pricelist ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
			return "<li>There are no Price lists in Cubit.";
	}else{
			while($list = pg_fetch_array($listRslt)){
					$pricelists .= "<option value='$list[listid]'>$list[listname]</option>";
			}
	}
	$pricelists .="</select>";


	# Select the stock category
	db_connect();
	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat ORDER BY cat ASC";
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
	$sql = "SELECT * FROM stockclass ORDER BY classname ASC";
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
	$view = "<h3>Increase Stock Items Selling Prices</h3>
	<table cellpadding=5><tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=details>
			<tr><th colspan=2>Options</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Pricelist</td><td>$pricelists</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Increase Type</td><td><input type=radio name='inctype' value=per checked=yes>Percentage <input type=text name=perc size=4 maxlength=4> % <br> <input type=radio name='inctype' value='man'>Manual</td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Category</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$cats</td><td valign=bottom><input type=submit name=cat value='Increase'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Classification</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$class</td><td valign=bottom><input type=submit name=class value='Increase'></td></tr>
			<tr><td><br></td></tr>
			</form>
		</table>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='toms/pricelist-view.php'>View Price Lists</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# details
function details($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# lets get cooking
	if($inctype == 'per'){
		return cook($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM plist_prices WHERE catid = '$catid' AND listid = '$listid'";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM plist_prices WHERE clasid = '$clasid' AND listid = '$listid'";
	}

	# display errors, if any
	if ($v->isError ()) {
		$error = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.";
	}else{
			$list = pg_fetch_array($listRslt);
	}

	$enter =
	"<h3>Increase Stock Items Selling Prices</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=listid value='$list[listid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Price list</td><td align=center>$list[listname]</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Continue &raquo;'></td></tr>
	<tr><td colspan=2><h3>Prices</h3><td><tr>
	<tr><th>Item</th><th>Old Price</th><th>New Price</th></tr>";

		# Query server
		$i = 0;
		$stkpRslt = db_exec ($searchs) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class=err> There are no stock item on the selected pricelist.";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$enter .= "<tr bgcolor='$bgColor'><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td><td>".CUR." $stkp[price]</td><td align=right>".CUR." <input type=text name=prices[] size=8 value='$stkp[price]'></td></tr>";
		}

	$enter .= "
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Continue &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='toms/pricelist-view.php'>View Price Lists</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $enter;
}

# cook up some prices
function cook($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM plist_prices WHERE catid = '$catid' AND listid = '$listid'";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM plist_prices WHERE clasid = '$clasid' AND listid = '$listid'";
	}

	# display errors, if any
	if ($v->isError ()) {
		$error = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Query server
	db_conn("exten");
	$i = 0;
	$stkpRslt = db_exec ($searchs) or errDie ("Unable to retrieve stock items from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li class=err> There are no stock item on the selected pricelist.";
	}

	for($i = 0; $stkp = pg_fetch_array ($stkpRslt); $i++) {
		$csprice = ($stkp['price'] * ($perc/100));
		$csprice = round(($csprice + $stkp['price']), 2);

		$stkids[$i] = $stkp['stkid'];
		$prices[$i] = $csprice;
	}
	$HTTP_POST_VARS['stkids'] = $stkids;
	$HTTP_POST_VARS['prices'] = $prices;

	return confirm($HTTP_POST_VARS);
}

# Confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class=err> there is not stock for the price list.";
	}

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

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.";
	}else{
			$list = pg_fetch_array($listRslt);
	}

	$confirm =
	"<h3>Confirm Stock Selling Prices Increase</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=listid value='$listid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Price list</td><td align=center>$list[listname]</td></tr>
	<tr><td colspan=2><br><td><tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	<tr><td colspan=2><h3>Prices</h3><td><tr>
	<tr><th>Item</th><th>Old Price</th><th>New Price</th></tr>";

	# Query server
	foreach($stkids as $key => $value){
		# format price
		$prices[$key] = sprint($prices[$key]);

		db_connect();
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		db_conn("exten");
		$sql = "SELECT price FROM plist_prices WHERE stkid = '$stkids[$key]' AND listid = '$listid'";
		$rslt = db_exec($sql) or errDie("Unable to fetch price list items from Cubit.",SELF);
		$stkp = pg_fetch_array ($rslt);

		# Alternate bgcolor
		$bgColor = ($key % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td><td>".CUR." $stkp[price]</td><td>".CUR." <input type=hidden name=prices[] size=8 value='$prices[$key]'>$prices[$key]</td></tr>";
	}

	$confirm .= "
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='toms/pricelist-view.php'>View Price Lists</a></td></tr>
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
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class=err> There is not stock for the price list.";
	}

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

	# connect to db
	db_conn ("exten");

	$sql = "SELECT * FROM pricelist WHERE listid = '$listid'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.";
	}else{
			$list = pg_fetch_array($listRslt);
	}

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# change price list item prices
		foreach($stkids as $key => $value){
			$sql = "UPDATE plist_prices SET price = '$prices[$key]' WHERE stkid = '$stkids[$key]' AND listid = '$listid'";
			$rslt = db_exec($sql) or errDie("Unable to update price list items to Cubit.",SELF);
		}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Layout
	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Selling Prices Increased</th></tr>
	<tr class=datacell><td>Selling Prices in price list : <b>$list[listname]</b>, has been have been increased.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='toms/pricelist-view.php'>View Price Lists</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

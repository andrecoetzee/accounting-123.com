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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "details":
			$OUTPUT = details($_POST);
			break;
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
	$sql = "SELECT * FROM pricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($list = pg_fetch_array($listRslt)){
			$pricelists .= "<option value='$list[listid]'>$list[listname]</option>";
		}
	}
	$pricelists .= "</select>";


	# Select the stock category
	db_connect();

	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
	}
	$cats .= "</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

	//layout
	$view = "
		<h3>Decrease Stock Items Selling Prices</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='details'>
						<tr>
							<th colspan='2'>Options</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Pricelist</td>
							<td>$pricelists</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Decrease Type</td>
							<td><input type='radio' name='dectype' value='per' checked='yes'>Percentage <input type='text' name='perc' size='4' maxlength='4'> % <br> <input type='radio' name='dectype' value='man'>Manual</td>
						</tr>
						".TBL_BR."
						<tr>
							<th colspan='2'>By Category</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$cats</td>
							<td valign='bottom'><input type='submit' name='cat' value='Decrease'></td>
						</tr>
						".TBL_BR."
						<tr>
							<th colspan='2'>By Classification</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$class</td>
							<td valign='bottom'><input type='submit' name='class' value='Decrease'></td>
						</tr>
						".TBL_BR."
						</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-inc.php'>Increase Selling Price</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-dec.php'>Decrease Selling Price</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}



# details
function details($_POST)
{

	# get vars
	extract ($_POST);

	# lets get cooking
	if($dectype == 'per'){
		return cook($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM plist_prices WHERE catid = '$catid' AND listid = '$listid' AND div = '".USER_DIV."'";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM plist_prices WHERE clasid = '$clasid' AND listid = '$listid' AND div = '".USER_DIV."'";
	}

	# display errors, if any
	if ($v->isError ()) {
		$error = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Select Stock
	db_conn("exten");

	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
		return "<li> Invalid Price List ID.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	$enter = "
		<h3>Decrease Stock Items Selling Prices</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='listid' value='$list[listid]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$list[listname]</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Old Price</th>
				<th>New Price</th>
			</tr>";

		# Query server
		$i = 0;
		$stkpRslt = db_exec ($searchs) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class='err'> There are no stock item on the selected pricelist.</li>";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			if (!isset ($stk['stkid']) OR strlen ($stk['stkid']) < 1) 
				continue;

			$enter .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td>".CUR." ".sprint($stkp["price"])."</td>
					<td align='right'>".CUR." <input type='text' name='prices[]' size='8' value='$stkp[price]'></td>
				</tr>";
		}

	$enter .= "
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-inc.php'>Increase Selling Price</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-dec.php'>Decrease Selling Price</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $enter;

}



# cook up some prices
function cook($_POST)
{

	# get vars
	extract ($_POST);

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
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Query server
	db_conn("exten");

	$i = 0;
	$stkpRslt = db_exec ($searchs) or errDie ("Unable to retrieve stock items from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li class='err'> There are no stock item on the selected pricelist.</li>";
	}

	for($i = 0; $stkp = pg_fetch_array ($stkpRslt); $i++) {
		$csprice = ($stkp['price'] * ($perc/100));
		$csprice = round(($stkp['price'] - $csprice), 2);

		$stkids[$i] = $stkp['stkid'];
		$prices[$i] = $csprice;
	}
	$_POST['stkids'] = $stkids;
	$_POST['prices'] = $prices;

	return confirm($_POST);
}



# Confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

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
		return "<li class='err'> there is not stock for the price list.</li>";
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Select Stock
	db_conn("exten");

	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
		return "<li> Invalid Price List ID.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	$confirm = "
		<h3>Confirm Stock Selling Prices Decrease</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listid' value='$listid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$list[listname]</td>
			</tr>
			<tr><td colspan='2'><br><td><tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Old Price</th>
				<th>New Price</th>
			</tr>";

	# Query server
	foreach($stkids as $key => $value){
		# format price
		$prices[$key] = sprint($prices[$key]);

		db_connect();
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		db_conn("exten");
		$sql = "SELECT price FROM plist_prices WHERE stkid = '$stkids[$key]' AND listid = '$listid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to fetch price list items from Cubit.",SELF);
		$stkp = pg_fetch_array ($rslt);

		if (!isset ($stk['stkid']) OR strlen ($stk['stkid']) < 1) 
			continue;

		$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>".CUR." ".sprint($stkp["price"])."</td>
				<td>".CUR." <input type='hidden' name='prices[]' size='8' value='$prices[$key]'>$prices[$key]</td>
			</tr>";
	}

	$confirm .= "
			".TBL_BR."
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-inc.php'>Increase Selling Price</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-dec.php'>Decrease Selling Price</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

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
		return "<li class='err'> There is not stock for the price list.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_conn ("exten");

	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.</li>";
	}else{
			$list = pg_fetch_array($listRslt);
	}

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# change price list item prices
	foreach($stkids as $key => $value){
		$sql = "UPDATE plist_prices SET price = '$prices[$key]' WHERE stkid = '$stkids[$key]' AND listid = '$listid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update price list items to Cubit.",SELF);
	}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Selling Prices Decreased</th>
			</tr>
			<tr class='datacell'>
				<td>Selling Prices in price list : <b>$list[listname]</b>, has been have been decreased.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='toms/pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-inc.php'>Increase Selling Price</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-price-dec.php'>Decrease Selling Price</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>

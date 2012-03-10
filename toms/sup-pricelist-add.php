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
require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			if (isset ($_REQUEST["continue"]))
				$OUTPUT = confirm ($HTTP_POST_VARS);
			else 
				$OUTPUT = enter ();
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");



# enter new data
function enter ()
{

	extract ($_REQUEST);

	$listing = "";

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	if (isset ($next)){
		$offset += SHOW_LIMIT;
	}
	if (isset ($prev)){
		$offset -= SHOW_LIMIT;
	}

	if (!isset ($offset) OR $offset < 0) 
		$offset = 0;

	$searchsql = "";
	if ((isset ($filter) AND strlen ($filter) > 0) OR (isset($next)) OR (isset($prev)) ){
		if (isset ($class) AND $class != "0"){
			$searchsql .= " AND prdcls = '$class'";
		}
		if (isset ($category) AND $category != "0"){
			$searchsql .= " AND catid = '$category'";
		}
		if (isset ($store) AND $store != "0"){
			$searchsql .= " AND whid = '$store'";
		}
	}else {
		$searchsql = "AND stkid = '0'";
	}

	# Query server
	$i = 0;
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' $searchsql ORDER BY stkcod ASC OFFSET $offset LIMIT ".SHOW_LIMIT."";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		//return "<li class='err'> There are no stock items in the selected warehouse.</li>";
		$listing .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>No Results</td>
			</tr>";
	}
	$count = 0;
	while ($stk = pg_fetch_array ($stkRslt)) {
		$stk['selamt'] = sprint ($stk['selamt']);
		$listing .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='checkbox' name='chk[]' value='$stk[stkid]' checked></td>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td align='right'>".CUR." $stk[selamt]</td>
				<td align='right'>".CUR." <input type='text' name='prices[]' size='8' value='$stk[csprice]'> $vattype</td>
			</tr>";
		$count++;
	}

	# Select classification
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) > 0){
		$classification_drop = "<select name='class'>";
		$classification_drop .= "<option value='0'>All Classifications</option>";
		while($clas = pg_fetch_array($clasRslt)){
			if (isset ($class) AND $class == $clas['clasid']){
				$classification_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
			}else {
				$classification_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
			}
		}
		$classification_drop .= "</select>";
	}else {
		$classification_drop = "<input type='hidden' name='class' value='0'>No Classifications Found.";
	}

	# Select category
	$sql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) > 0){
		$category_drop = "<select name='category'>";
		$category_drop .= "<option value='0'>All Categories</option>";
		while($cat = pg_fetch_array($catRslt)){
			if (isset ($category) AND $category == $cat['catid']){
				$category_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
			}else {
				$category_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
			}
		}
	}else {
		$category_drop = "<input type='hidden' name='category' value='0'>No Categories Found.";
	}
	$category_drop .= "</select>";

	db_conn ("exten");

	# Select store
	$get_wh = "SELECT * FROM warehouses ORDER BY whname";
	$run_wh = db_exec ($get_wh) or errDie ("Unable to get warehouses information.");
	if (pg_numrows ($run_wh) > 0){
		$store_drop = "<select name='store'>";
		$store_drop .= "<option value='0'>All Stores</option>";
		while ($sarr = pg_fetch_array ($run_wh)){
			if (isset ($store) AND $store == $sarr['whid']){
				$store_drop .= "<option value='$sarr[whid]' selected>($sarr[whno]) $sarr[whname]</option>";
			}else {
				$store_drop .= "<option value='$sarr[whid]'>($sarr[whno]) $sarr[whname]</option>";
			}
		}
		$store_drop .= "</select>";
	}else {
		$store_drop = "<input type='hidden' name='store' value='0'>No Stores Found.";
	}

	if ($offset != 0 AND $count == SHOW_LIMIT){
		$listing .= "
			<tr>
				<td><input type='submit' name='prev' value='Previous'></td>
				<td align='right'><input type='submit' name='next' value='Next'></td>
			</tr>";
	}elseif ($offset != 0) {
		$listing .= "
			<tr>
				<td><input type='submit' name='prev' value='Previous'></td>
				<td align='right'></td>
			</tr>";
	}else {
		$listing .= "
			<tr>
				<td></td>
				<td align='right'><input type='submit' name='next' value='Next'></td>
			</tr>";
	}

	foreach ($chk AS $key => $value) {
		$sendvars .= "<input type='hidden' name='chk[]' value='$chk[$key]'>";
		$sendvars .= "<input type='hidden' name='stkids[]' value='$stkids[$key]'>";
		$sendvars .= "<input type='hidden' name='prices[]' value='$prices[$key]'>";
	}

	$enter = "
		<h3>Add Supplier Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			$sendvars
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'><input type='text' size='20' name='listname' value='$listname'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Filter</th>
			</tr>
			<tr>
				<th colspan='2'>Store</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>$store_drop</td>
			</tr>
			<tr>
				<th colspan='2'>Category</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>$category_drop</td>
			</tr>
			<tr>
				<th colspan='2'>Classification</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>$classification_drop</td>
			</tr>
			<tr>
				<td colspan='2' align='center'><input type='submit' name='filter' value='Search'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Select</th>
				<th>Item</th>
				<th>Selling Amount</th>
				<th>Price Amount</th>
			</tr>
			$listing
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' name='continue' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $enter;

}



# confirm new data
function confirm ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	$listing = "";

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	if(isset($chk)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> Please select at least one stock item.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	# Query server
	foreach($stkids as $key => $value){
		if(!in_array($stkids[$key], $chk))
			continue;
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		$listing .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>".CUR." <input type='hidden' name='prices[]' size='8' value='$prices[$key]'>$prices[$key] $vattype</td>
			</tr>";
	}

	$confirm = "
		<h3>Confirm Supplier Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listname' value='$listname'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$listname</td>
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
				<th>Price Amount</th>
			</tr>
			$listing
			<tr><td><br></td></tr>
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
				<td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}



# write new data
function write ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> there is not stock for the price list.</li>";
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

	# write to db
	$sql = "INSERT INTO spricelist(listname, div) VALUES ('$listname', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to price list to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class='err'>Unable to add price list to database.</li>";
	}

	# get next ordnum
	$listid = pglib_lastid ("spricelist", "listid");

	# Insert price list items
	foreach($stkids as $key => $value){

		db_connect();

		$sql = "SELECT stkid, prdcls, catid FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		db_conn ("exten");

		$sql = "
			INSERT INTO splist_prices (
				listid, stkid, catid, clasid, price, div
			) VALUES (
				'$listid', '$stkids[$key]', '$stk[catid]', '$stk[prdcls]', '$prices[$key]', '".USER_DIV."'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);

	}

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Supplier Price list added to system</th>
			</tr>
			<tr class='datacell'>
				<td>New Supplier Price list <b>$listname</b>, has been successfully added to the system.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>

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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			if (isset ($_POST["search"])){
				$OUTPUT = edit ($_POST['listid']);
			}else {
				$OUTPUT = confirm($_POST);
			}
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_GET['listid'])){
				$OUTPUT = edit ($_GET['listid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['listid'])){
		$OUTPUT = edit ($_GET['listid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("../template.php");



function edit($listid)
{

	extract ($_POST);

	if (!isset ($offset) OR $offset < 0){
		$offset = 0;
	}

	if (isset($next)){
		$offset += SHOW_LIMIT;
	}

	$listing = "";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	# Select Stock
	db_conn("exten");

	$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
		return "<li> Invalid Price List ID.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	db_connect();

	$category_drop = "<select name='stockcat'>";
	$sql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			if (isset ($stockcat) AND $stockcat == $cat['catid']){
				$category_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
			}else {
				$category_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
			}
		}
	}
	$category_drop .= "</select>";

	# Select classification
	$classification_drop = "<select name='stockclass'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			if (isset ($stockclass) AND $stockclass == $clas['clasid']){
				$classification_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
			}else {
				$classification_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
			}
		}
	}
	$classification_drop .= "</select>";

	db_con ("exten");

	# Query server
	$i = 0;
// 	$sql = "SELECT * FROM splist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' ORDER BY stkid ASC";
	$sql = "
		SELECT listid, stkid, catid, clasid, price, div, supstkcod 
		FROM splist_prices 
		WHERE listid = '$listid' AND div = '".USER_DIV."' $searchsql 
		ORDER BY stkid ASC OFFSET $offset 
		LIMIT ".SHOW_LIMIT;
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li class='err'> There are no stock item on the selected pricelist.</li>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {
		db_connect();
		# get stock details
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($stkRslt) < 1) {
			db_conn("exten");
			$Sl = "DELETE FROM plist_prices WHERE stkid='$stkp[stkid]' AND div = '".USER_DIV."'";
			$Rs = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
		} else{
			$stk = pg_fetch_array ($stkRslt);
			$listing .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td align='right'>".CUR." <input type='text' name='prices[]' size='8' value='$stkp[price]'> $vattype</td>
				</tr>";
		}
	}

	$buttons = "";
	if ($offset != 0){
		$buttons = "
			<tr>
				<td align='left'><input type='submit' name='prev' value='Previous'></td>
				<td><input type='submit' name='next' value='Next'></td>
			</tr>";
	}

	$enter = "
		<h3>Edit Supplier Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='listid' value='$list[listid]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Supplier Price list</td>
				<td align='center'><input type='text' size='20' name='listname' value='$list[listname]'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Category</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2' align='center'>$category_drop</td>
			</tr>
			<tr>
				<th colspan='2'>Classification</th>
			</tr>
			<tr bgcolor='".bgcolor()."'>
				<td colspan='2' align='center'>$classification_drop</td>
			</tr>
			<tr>
				<th colspan='2'>Stock Code</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2' align='center'><input type='text' name='stockcode' value='$stockcode'></td>
			</tr>
			<tr>
				<td colspan='2' align='center'><input type='submit' name='search' value='Search'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' name='continue' value='Confirm &raquo;'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'><h3>Prices</h3></td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
			</tr>
			$listing
			$buttons
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' name='continue' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $enter;

}



# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> There are no stock items on the price list.</li>";
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

	$confirm = "
		<h3>Confirm Edit Supplier Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listname' value='$listname'>
			<input type='hidden' name='listid' value='$listid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Price list</td>
				<td align='center'>$listname</td>
			</tr>
			<tr><td colspan='2'><br><td></tr>
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
			</tr>";

	# Query server
	db_connect();

	foreach($stkids as $key => $value){
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		$confirm .= "
			<tr class='".bg_class()."'>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>".CUR." <input type='hidden' name='prices[]' size='8' value='$prices[$key]'>$prices[$key] $vattype</td>
			</tr>";
	}

	$confirm .= "
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
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
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
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

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Write to db
		$sql = "UPDATE spricelist SET listname = '$listname' WHERE listid = '$listid' AND div = '".USER_DIV."'";
		$listRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);
		if (pg_cmdtuples ($listRslt) < 1) {
			return "<li class='err'>Unable to add listname to database.</li>";
		}

		# Insert new price list items
		foreach($stkids as $key => $value){
			$sql = "UPDATE splist_prices SET price = '$prices[$key]' WHERE stkid = '$stkids[$key]' AND listid = '$listid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update price list items to Cubit.",SELF);
		}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Supplier Price List edited</th>
			</tr>
			<tr class='datacell'>
				<td>Supplier Price List <b>$listname</b>, has been edited.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr class='".bg_class()."'><td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td></tr>
			<tr class='".bg_class()."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

	return $write;
}
?>
